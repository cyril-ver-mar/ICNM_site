"""Feedback delivery seam: validate POST → journal (local) / mail (host) (ticket 30)."""

from __future__ import annotations

import pytest

from src.core.feedback import (
    DEFAULT_FEEDBACK_TO,
    plan_feedback_delivery,
    resolve_delivery_mode,
    resolve_recipient,
    validate_feedback,
)


def test_validate_accepts_complete_payload():
    row = validate_feedback(
        {
            "name": "  Иван Иванов  ",
            "email": "ivan@example.com",
            "message": "Здравствуйте, вопрос по сотрудничеству.",
        }
    )
    assert row["ok"] is True
    assert row["name"] == "Иван Иванов"
    assert row["email"] == "ivan@example.com"
    assert "сотрудничеству" in row["message"]


def test_validate_rejects_missing_or_bad_email():
    for raw in (
        {"name": "", "email": "a@b.co", "message": "hi"},
        {"name": "Ann", "email": "", "message": "hi"},
        {"name": "Ann", "email": "not-an-email", "message": "hi"},
        {"name": "Ann", "email": "ann@example.com", "message": ""},
        {"name": "Ann", "email": "ann@", "message": "hi"},
    ):
        row = validate_feedback(raw)
        assert row["ok"] is False
        assert row["error"] == "fields"


def test_delivery_mode_local_is_journal():
    assert resolve_delivery_mode(environment="local") == "journal"
    assert resolve_delivery_mode(environment="development") == "journal"
    assert resolve_delivery_mode(home_url="http://localhost:8080/") == "journal"
    assert resolve_delivery_mode(home_url="http://127.0.0.1:8080/feedback/") == "journal"


def test_delivery_mode_host_is_mail():
    assert resolve_delivery_mode(environment="production") == "mail"
    assert resolve_delivery_mode(environment="staging") == "mail"
    assert resolve_delivery_mode(home_url="https://ichnm.by/") == "mail"
    assert resolve_delivery_mode(home_url="https://new.ichnm.by/") == "mail"


def test_delivery_mode_localhost_beats_default_production():
    """Docker images often report WP_ENVIRONMENT_TYPE=production on localhost."""
    assert (
        resolve_delivery_mode(
            environment="production",
            home_url="http://localhost:8080/",
        )
        == "journal"
    )


def test_delivery_mode_constant_override_wins():
    assert (
        resolve_delivery_mode(override="journal", environment="production") == "journal"
    )
    assert resolve_delivery_mode(override="mail", environment="local") == "mail"
    with pytest.raises(ValueError):
        resolve_delivery_mode(override="crm")


def test_recipient_from_constant_option_or_default():
    assert resolve_recipient() == DEFAULT_FEEDBACK_TO
    assert resolve_recipient(constant="desk@ichnm.by") == "desk@ichnm.by"
    assert resolve_recipient(option="office@ichnm.by") == "office@ichnm.by"
    assert (
        resolve_recipient(constant="desk@ichnm.by", option="office@ichnm.by")
        == "desk@ichnm.by"
    )
    assert resolve_recipient(constant="not-email", option="office@ichnm.by") == (
        "office@ichnm.by"
    )
    assert resolve_recipient(constant="bad", option="also-bad") == DEFAULT_FEEDBACK_TO


def test_plan_journal_vs_mail_without_side_effects():
    payload = validate_feedback(
        {
            "name": "Ann",
            "email": "ann@example.com",
            "message": "Hello",
        }
    )
    assert payload["ok"] is True

    journal = plan_feedback_delivery(
        payload,
        mode="journal",
        recipient="ichnm@ichnm.by",
    )
    assert journal["ok"] is True
    assert journal["mode"] == "journal"
    assert journal["action"] == "journal"
    assert "Ann" in journal["body"]
    assert journal["recipient"] == "ichnm@ichnm.by"

    mail = plan_feedback_delivery(
        payload,
        mode="mail",
        recipient="desk@ichnm.by",
    )
    assert mail["ok"] is True
    assert mail["mode"] == "mail"
    assert mail["action"] == "mail"
    assert mail["recipient"] == "desk@ichnm.by"
    assert mail["subject"]
    assert "ann@example.com" in mail["body"]


def test_plan_propagates_validation_error():
    bad = validate_feedback({"name": "", "email": "x", "message": ""})
    planned = plan_feedback_delivery(bad, mode="mail", recipient="ichnm@ichnm.by")
    assert planned["ok"] is False
    assert planned["error"] == "fields"

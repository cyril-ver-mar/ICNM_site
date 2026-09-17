"""Feedback form seam: validate POST → journal (local) / mail (host).

Mirrors ``wp-content/plugins/ichnm-site/includes/feedback.php`` (ticket 30).
No CRM; recipient is constant / option / default institute address.
"""

from __future__ import annotations

import re
from typing import Any, Literal, Mapping

DEFAULT_FEEDBACK_TO = "ichnm@ichnm.by"
MAIL_SUBJECT = "Обратная связь с сайта ИХНМ"

_EMAIL_RE = re.compile(
    r"^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+@"
    r"[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?"
    r"(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)+$"
)

DeliveryMode = Literal["journal", "mail"]


def _trim(value: Any) -> str:
    if value is None:
        return ""
    return str(value).strip()


def _is_email(value: str) -> bool:
    if not value or len(value) > 180:
        return False
    return bool(_EMAIL_RE.match(value))


def validate_feedback(raw: Mapping[str, Any]) -> dict[str, Any]:
    """Return ``{ok, name, email, message}`` or ``{ok: False, error: 'fields'}``."""
    name = _trim(raw.get("name"))
    email = _trim(raw.get("email")).lower()
    message = _trim(raw.get("message"))
    if not name or len(name) > 120:
        return {"ok": False, "error": "fields"}
    if not message or len(message) > 5000:
        return {"ok": False, "error": "fields"}
    if not _is_email(email):
        return {"ok": False, "error": "fields"}
    return {"ok": True, "name": name, "email": email, "message": message}


def resolve_delivery_mode(
    *,
    override: str | None = None,
    environment: str | None = None,
    home_url: str | None = None,
) -> DeliveryMode:
    """Choose journal (Docker/local) vs mail (PHP host).

    Localhost home URL wins over WordPress's default ``production``
    environment type (common on Docker images).
    """
    if override is not None:
        mode = override.strip().lower()
        if mode not in ("journal", "mail"):
            raise ValueError(f"unknown feedback delivery mode: {override!r}")
        return mode  # type: ignore[return-value]

    url = (home_url or "").strip().lower()
    if "localhost" in url or "127.0.0.1" in url:
        return "journal"

    env = (environment or "").strip().lower()
    if env in ("local", "development"):
        return "journal"
    if env in ("production", "staging"):
        return "mail"

    return "mail"


def resolve_recipient(
    *,
    constant: str | None = None,
    option: str | None = None,
    default: str = DEFAULT_FEEDBACK_TO,
) -> str:
    """Prefer ``ICHNM_FEEDBACK_TO``, then option ``ichnm_feedback_to``, then default."""
    for candidate in (constant, option, default):
        value = _trim(candidate)
        if value and _is_email(value):
            return value.lower()
    return DEFAULT_FEEDBACK_TO


def feedback_mail_body(name: str, email: str, message: str) -> str:
    return f"Имя: {name}\nEmail: {email}\n\n{message}\n"


def plan_feedback_delivery(
    validated: Mapping[str, Any],
    *,
    mode: DeliveryMode,
    recipient: str,
) -> dict[str, Any]:
    """Pure plan: validation result + mode → journal or mail action (no I/O)."""
    if not validated.get("ok"):
        return {"ok": False, "error": validated.get("error") or "fields"}

    body = feedback_mail_body(
        str(validated["name"]),
        str(validated["email"]),
        str(validated["message"]),
    )
    to = resolve_recipient(constant=recipient)
    return {
        "ok": True,
        "mode": mode,
        "action": mode,
        "recipient": to,
        "subject": MAIL_SUBJECT,
        "body": body,
        "name": validated["name"],
        "email": validated["email"],
        "message": validated["message"],
    }

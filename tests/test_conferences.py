"""Conference archive path contract: AIST/events → /conferences/{slug}/."""

from __future__ import annotations

from src.core.conferences import (
    conference_archive_html,
    conference_path,
)
from src.core.copy import load_migrated_copy


def test_conference_path_is_preview_style_slug_url():
    assert conference_path("aist-2025") == "conferences/aist-2025/"
    assert conference_path("/reaktiv-2018/") == "conferences/reaktiv-2018/"


def test_archive_html_links_titles_to_conference_pages():
    rows = [
        {
            "slug": "aist-2025",
            "kind": "aist",
            "title": "AIST-2025 (X)",
            "when": "21–23 октября 2025, Минск",
            "series": "AIST",
        },
        {
            "slug": "belszm",
            "kind": "other",
            "title": "Соорганизация «БелСЗМ»",
            "when": "",
            "series": "БелСЗМ",
        },
    ]
    html = conference_archive_html(rows)
    assert 'href="conferences/aist-2025/"' in html
    assert ">AIST-2025 (X)<" in html
    assert 'href="conferences/belszm/"' in html
    assert "aist.ichnm.by" not in html  # registration stays off the archive cards


def test_migrated_copy_aist_2025_has_archive_link_target():
    rows = load_migrated_copy().get("conferences") or []
    aist_2025 = next(r for r in rows if r.get("slug") == "aist-2025")
    assert aist_2025.get("files")
    html = conference_archive_html(rows)
    assert conference_path("aist-2025") in html
    assert conference_path("aist-2009") in html

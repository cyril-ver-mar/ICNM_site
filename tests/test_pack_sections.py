"""Unit pack contracts: lab anchors and admin-unit chrome without WordPress."""

from __future__ import annotations

import re

from src.core.copy import load_migrated_copy
from src.core.pack_sections import (
    ADMIN_UNIT_REQUIRED_CLASSES,
    LAB_PACK_SECTION_IDS,
    admin_unit_pack_html,
    lab_pack_section_ids,
    lab_pack_skeleton_html,
    section_ids_in_html,
)


def test_lab_pack_section_order_about_through_contacts():
    ids = lab_pack_section_ids()
    assert ids == LAB_PACK_SECTION_IDS
    assert ids[0] == "about"
    assert ids[-1] == "contacts"
    assert ids == (
        "about",
        "directions",
        "projects",
        "equipment",
        "services",
        "staff",
        "pubs",
        "contacts",
    )


def test_lab_pack_skeleton_exposes_required_anchors():
    html = lab_pack_skeleton_html()
    found = section_ids_in_html(html)
    assert found == list(LAB_PACK_SECTION_IDS)
    for section_id in LAB_PACK_SECTION_IDS:
        assert f'href="#{section_id}"' in html
        assert f'id="{section_id}"' in html


def test_lab_fixture_from_migrated_copy_keeps_pack_contract():
    labs = load_migrated_copy().get("labs") or []
    assert labs, "expected at least one lab in migrated copy"
    html = lab_pack_skeleton_html(lab=labs[0])
    assert section_ids_in_html(html) == list(LAB_PACK_SECTION_IDS)


def test_admin_unit_requires_unit_back_and_people_list():
    assert ADMIN_UNIT_REQUIRED_CLASSES == ("unit-back", "people-list")
    copy = load_migrated_copy()
    units = copy.get("admin_units") or []
    assert units, "expected admin units in migrated copy"
    html = admin_unit_pack_html(units[0])
    for class_name in ADMIN_UNIT_REQUIRED_CLASSES:
        assert re.search(rf'class="[^"]*\b{class_name}\b', html)
    assert "people-list" in html
    assert "unit-back" in html


def test_admin_unit_empty_people_still_has_list_slot():
    html = admin_unit_pack_html({"id": "hr", "title": "Отдел кадров", "phone": "", "people": []})
    assert 'class="unit-back"' in html or "unit-back" in html
    assert "people-list" in html


def test_preview_lab_and_admin_html_honour_pack_contract():
    """Honest preview builders must keep the same anchors/classes as the pure contract."""
    from src.core.preview import render_site_files
    from src.core.site_model import load_site_model

    files = render_site_files(load_site_model())
    lab_html = files["labs/nano/index.html"]
    assert section_ids_in_html(lab_html) == list(LAB_PACK_SECTION_IDS)
    for class_name in ADMIN_UNIT_REQUIRED_CLASSES:
        assert class_name in files["hr.html"]

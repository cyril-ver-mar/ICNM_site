"""Council / union / SMU vitrines and structure-hub links (ticket 11)."""

from __future__ import annotations

from src.core.copy import load_migrated_copy
from src.core.site_model import load_site_model
from src.core.structure_vitrines import (
    COMMUNITY_PAGE_IDS,
    COUNCIL_PAGE_ID,
    VITRINE_PAGE_IDS,
    council_listing_html,
    community_vitrine_html,
    honest_page_ready,
    structure_hub_html,
    structure_hub_link_ids,
)


def test_model_places_union_and_smu_after_admin_units():
    model = load_site_model()
    structure_ids = [child.id for child in model.menu_item("structure").children]
    assert structure_ids[-2:] == list(COMMUNITY_PAGE_IDS)
    assert "scientific-council" in [c.id for c in model.menu_item("about").children]


def test_council_union_smu_have_honest_copy_not_preview_filled_fish():
    copy = load_migrated_copy()
    pages = copy.get("pages") or {}
    for page_id in VITRINE_PAGE_IDS:
        assert honest_page_ready(pages, page_id), f"{page_id} needs paragraphs or empty_slot"
        body = pages[page_id]
        joined = " ".join(str(p) for p in (body.get("paragraphs") or []))
        assert "preview-filled" not in joined.lower()
        # Letter-coded fish labs (А–Д) must not appear as official body copy.
        assert "Лаборатория А" not in joined


def test_council_listing_uses_person_cards_linking_to_people_ids():
    copy = load_migrated_copy()
    people = copy.get("council_people") or []
    assert people, "council_people required in migrated_copy"
    html = council_listing_html(people)
    assert "ichnm-person-card" in html
    assert "ichnm-person-card-photo" in html
    assert "Биография и публикации" not in html
    for row in people:
        pid = str(row["id"])
        assert f"/people/{pid}/" in html
        assert str(row.get("role") or "") in html


def test_structure_hub_lists_community_after_admin_without_staff_dump():
    copy = load_migrated_copy()
    labs = copy.get("labs") or []
    admin = copy.get("admin_units") or []
    ids = structure_hub_link_ids(labs, admin)
    assert ids["community"] == list(COMMUNITY_PAGE_IDS)
    assert ids["admin"] == [str(u["id"]) for u in admin]
    assert ids["labs"]

    html = structure_hub_html(labs, admin)
    assert "Общественные объединения" in html
    assert "union" in html and "young-scientists" in html
    assert "ichnm-person-card" not in html
    assert "/people/" not in html
    # No dumped staff names from admin unit people lists.
    for unit in admin:
        for person in unit.get("people") or []:
            name = str(person.get("name") or "").strip()
            if name and name != "Фамилия Имя Отчество":
                assert name not in html


def test_union_and_smu_vitrines_keep_honest_slots_and_smu_cards():
    copy = load_migrated_copy()
    union_html = community_vitrine_html("union", copy["pages"]["union"])
    assert "профсоюз" in union_html.lower() or "Профсоюз" in union_html or "профсоюзн" in union_html.lower()
    assert "ichnm-person-card" not in union_html  # chair is in copy text until roster arrives

    smu_html = community_vitrine_html(
        "young-scientists",
        copy["pages"]["young-scientists"],
        people=None,  # default SMU placeholders
    )
    assert "ichnm-person-card" in smu_html
    assert "/people/smu-chair/" in smu_html
    assert COUNCIL_PAGE_ID == "scientific-council"

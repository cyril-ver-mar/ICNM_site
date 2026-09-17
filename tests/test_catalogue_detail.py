"""Catalogue detail seam: science / developments / facilities (ticket 15)."""

from __future__ import annotations

from src.core import roster
from src.core.catalogue_detail import (
    CATALOGUE_PARENTS,
    catalogue_detail_html,
    catalogue_detail_path,
    find_catalogue_item,
    normalize_catalogue_detail,
)


def test_parents_are_science_developments_facilities():
    assert CATALOGUE_PARENTS == ("science", "developments", "facilities")


def test_fixture_slug_from_migrated_copy_has_required_fields():
    item = find_catalogue_item("developments", "immuno-spheres")
    assert item is not None
    detail = normalize_catalogue_detail("developments", item)
    assert detail["parent"] == "developments"
    assert detail["slug"] == "immuno-spheres"
    assert detail["title"]
    assert detail["lead"]
    assert detail["path"] == "/developments/immuno-spheres/"
    assert detail["photo_kind"] == "cover"
    assert detail["detail_heading"] == "Продукт / результат"
    assert detail["lab_id"] == "lab-nano"
    assert detail["lab_href"]
    assert "labs/" in detail["lab_href"]
    assert detail["staff_id"] == "kulikouskaya"
    assert detail["staff_href"].endswith("/people/kulikouskaya/")
    assert detail["staff_label"]


def test_facilities_uses_spec_heading_and_equipment_photo_kind():
    item = find_catalogue_item("facilities", "vaktime-plasma-lab")
    assert item is not None
    detail = normalize_catalogue_detail("facilities", item)
    assert detail["detail_heading"] == "Спецификация"
    assert detail["detail_text"]
    assert detail["photo_kind"] == "equipment"
    assert detail["path"] == "/facilities/vaktime-plasma-lab/"


def test_institute_topic_without_lab_omits_lab_and_staff_links():
    item = find_catalogue_item("science", "thin-films")
    assert item is not None
    detail = normalize_catalogue_detail("science", item)
    assert detail["title"].startswith("Тонкоплёночные")
    assert detail["lab_id"] == ""
    assert detail["lab_href"] == ""
    assert detail["staff_id"] == ""
    assert detail["staff_href"] == ""
    assert detail["contacts"]
    assert detail["detail_text"]  # product


def test_detail_html_has_honest_photo_slot_and_links_when_known():
    item = find_catalogue_item("developments", "immuno-spheres")
    assert item is not None
    detail = normalize_catalogue_detail("developments", item)
    html = catalogue_detail_html(detail)

    assert 'class="photo-slot"' in html
    assert "is-filled" not in html
    assert "<img" not in html
    assert "preview-filled" not in html.lower()
    assert "Лаборатория А" not in html
    assert detail["title"] in html
    assert detail["lead"] in html
    assert "Описание и контакты" in html
    assert "Продукт / результат" in html
    assert "Лаборатория:" in html
    assert detail["lab_href"] in html
    assert "Закреплено:" in html
    assert detail["staff_href"] in html
    assert catalogue_detail_path("developments") in html or "developments" in html


def test_detail_html_without_staff_has_no_people_links():
    item = find_catalogue_item("science", "thin-films")
    assert item is not None
    detail = normalize_catalogue_detail("science", item)
    html = catalogue_detail_html(detail)
    assert "/people/" not in html
    assert "Закреплено:" not in html
    assert "Лаборатория:" not in html


def test_every_catalog_parent_resolves_at_least_one_migrated_slug():
    for parent in CATALOGUE_PARENTS:
        rows = {
            "science": roster.science_catalog,
            "developments": roster.developments_catalog,
            "facilities": roster.facilities_catalog,
        }[parent]()
        assert rows, f"{parent} catalogue empty"
        slug = str(rows[0]["slug"])
        found = find_catalogue_item(parent, slug)
        assert found is not None
        detail = normalize_catalogue_detail(parent, found)
        assert detail["slug"] == slug
        assert detail["path"] == f"/{parent}/{slug}/"

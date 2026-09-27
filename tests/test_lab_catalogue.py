"""Lab catalogue CPT seed seam (ticket 87)."""

from src.core.copy import load_migrated_copy
from src.core.lab_catalogue import (
    catalogue_key,
    lab_slug_from_row,
    seed_import_entries,
    seed_rows_for_parent,
)


def test_catalogue_key_stable():
    assert catalogue_key("direction", "nano-carriers") == "direction:nano-carriers"
    assert catalogue_key("facility", "vaktime-plasma-lab") == "facility:vaktime-plasma-lab"
    assert catalogue_key("development", "immuno-spheres") == "development:immuno-spheres"


def test_lab_slug_from_lab_id():
    assert lab_slug_from_row({"lab_id": "lab-nano"}) == "nano"
    assert lab_slug_from_row({"lab_slug": "films", "lab_id": "lab-films"}) == "films"
    assert lab_slug_from_row({}) == ""


def test_seed_import_includes_known_slugs():
    entries = seed_import_entries()
    keys = {e["key"] for e in entries}
    assert "direction:nano-carriers" in keys
    assert "facility:vaktime-plasma-lab" in keys
    assert "development:immuno-spheres" in keys
    # Institute-level facility without lab
    assert any(e["key"] == "facility:inspec-mixsplitter" and e["lab_slug"] == "" for e in entries)


def test_seed_rows_lab_filter_shape():
    copy = load_migrated_copy()
    science = seed_rows_for_parent("science", copy)
    assert science
    nano_dirs = [r for r in science if lab_slug_from_row(r) == "nano"]
    assert nano_dirs, "nano lab directions must seed"
    assert all(r.get("slug") for r in nano_dirs)

    fac = seed_rows_for_parent("facilities", copy)
    assert any(str(r.get("slug")) == "vaktime-plasma-lab" for r in fac)


def test_import_keys_unique_per_type():
    entries = seed_import_entries()
    by_type: dict[str, set[str]] = {}
    for e in entries:
        by_type.setdefault(e["type"], set())
        assert e["slug"] not in by_type[e["type"]], f"duplicate slug {e['type']}:{e['slug']}"
        by_type[e["type"]].add(e["slug"])

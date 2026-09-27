"""Lab accent + thin-film grouping seams (tickets 55 / 57)."""

from src.core.lab_accent import (
    LAB_ACCENT_BY_SLUG,
    THIN_FILM_CHILD_SLUGS,
    THIN_FILM_DEPT_TITLE,
    lab_accent_token,
    thin_film_child_slugs,
)
from src.core.roster import labs
from src.core.structure_vitrines import structure_hub_groups, structure_hub_html_tiles


def test_known_lab_same_accent_token() -> None:
    assert lab_accent_token("nano") == LAB_ACCENT_BY_SLUG["nano"]
    assert lab_accent_token("films") == LAB_ACCENT_BY_SLUG["films"]
    assert lab_accent_token("unknown") == ""


def test_thin_film_three_children_from_roster() -> None:
    slugs = {str(lab.get("slug") or "") for lab in labs()}
    children = thin_film_child_slugs()
    assert children == THIN_FILM_CHILD_SLUGS
    assert len(children) == 3
    assert set(children) <= slugs


def test_structure_hub_groups_thin_film_and_rest() -> None:
    groups = structure_hub_groups(labs())
    assert groups["thin_film_title"] == THIN_FILM_DEPT_TITLE
    assert [lab["slug"] for lab in groups["thin_film"]] == list(THIN_FILM_CHILD_SLUGS)
    rest_slugs = [lab["slug"] for lab in groups["other"]]
    assert "composites" in rest_slugs
    assert "woodchem" in rest_slugs
    for child in THIN_FILM_CHILD_SLUGS:
        assert child not in rest_slugs


def test_structure_tiles_html_has_zaveduyushchiy_and_no_sub_tile() -> None:
    people = {
        "kulikouskaya": {"name": "Куликовская Виктория"},
        "ivanova": {"name": "Иванова Наталья"},
        "muravsky": {"name": "Муравский Анатолий"},
    }
    # Head ids from roster labs nano/films/lcd.
    html = structure_hub_html_tiles(labs(), [], people_by_id=people)
    assert "ichnm-structure-sub" not in html
    assert THIN_FILM_DEPT_TITLE in html
    assert "заведующий" in html
    assert "ichnm-thin-film-group" in html

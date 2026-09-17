"""Publication record seam: normalize + fixture fragment parser (ticket 08).

Ticket 09: migrated_copy publications_items must all normalise without person links.
"""

from __future__ import annotations

from pathlib import Path

from src.core.copy import load_migrated_copy
from src.core.publications import (
    normalize_doi,
    normalize_publication,
    parse_publication_fragment,
)

FIXTURE = Path(__file__).resolve().parent / "fixtures" / "publications_fragment.html"

_PERSON_KEYS = frozenset(
    {
        "person_id",
        "author_ids",
        "people",
        "person_href",
        "authors_href",
        "staff_id",
    }
)


def test_normalize_doi_to_absolute_https():
    assert normalize_doi("10.1234/abc") == "https://doi.org/10.1234/abc"
    assert normalize_doi("doi:10.1234/abc") == "https://doi.org/10.1234/abc"
    assert normalize_doi("DOI: 10.1234/abc") == "https://doi.org/10.1234/abc"
    assert normalize_doi("https://doi.org/10.1234/abc") == "https://doi.org/10.1234/abc"
    assert normalize_doi("http://doi.org/10.1234/abc") == "https://doi.org/10.1234/abc"


def test_empty_doi_omitted_from_normalized_record():
    assert normalize_doi("") == ""
    assert normalize_doi("   ") == ""
    assert normalize_doi(None) == ""

    row = normalize_publication(
        {
            "cite": "Иванов И. И. Пример // Журнал. 2020.",
            "doi": "",
            "laboratory": "Лаборатория наноструктур",
            "lab_id": "lab-nano",
        }
    )
    assert "doi" not in row
    assert row["cite"].startswith("Иванов")
    assert row["laboratory"] == "Лаборатория наноструктур"
    assert row["lab_id"] == "lab-nano"


def test_normalize_builds_cite_from_authors_title_journal_year():
    row = normalize_publication(
        {
            "authors": "Smith J., Doe A.",
            "title": "Example paper",
            "journal": "Nature Chemistry",
            "year": 2023,
            "doi": "10.1038/example",
            "lab_id": "lab-films",
            "laboratory": "Лаборатория плёнок",
        }
    )
    assert row["cite"] == "Smith J., Doe A. Example paper. Nature Chemistry. 2023"
    assert row["doi"] == "https://doi.org/10.1038/example"
    assert row["year"] == 2023
    assert row["lab_id"] == "lab-films"
    assert row["laboratory"] == "Лаборатория плёнок"


def test_normalize_prefers_explicit_gost_cite():
    row = normalize_publication(
        {
            "cite": "Рогачёв А. А. Химия новых материалов. Минск : БГУ, 2016. 343 с.",
            "authors": "ignored",
            "title": "ignored",
            "year": 2016,
            "lab_id": "lab-nano",
        }
    )
    assert "Химия новых материалов" in row["cite"]
    assert "ignored" not in row["cite"]
    assert row["year"] == 2016


def test_normalized_shape_has_no_person_hyperlink_fields():
    row = normalize_publication(
        {
            "cite": "Test cite // Journal. 2022.",
            "doi": "10.0000/x",
            "lab_id": "lab-lcd",
            "laboratory": "Лаборатория ЖК",
            "person_id": "rogachev",
            "author_ids": ["rogachev", "ignatovich"],
            "people": [{"id": "rogachev", "href": "/people/rogachev/"}],
        }
    )
    assert _PERSON_KEYS.isdisjoint(row.keys())
    assert "/people/" not in str(row.values())


def test_parse_fixture_fragment_to_normalized_records():
    html = FIXTURE.read_text(encoding="utf-8")
    rows = parse_publication_fragment(
        html,
        laboratory="Лаборатория наноструктур",
        lab_id="lab-nano",
    )
    assert len(rows) == 3

    first = rows[0]
    assert "Hybrid microcapsules" in first["cite"]
    assert first["doi"] == "https://doi.org/10.1134/S1061933X24010011"
    assert first["lab_id"] == "lab-nano"
    assert first["laboratory"] == "Лаборатория наноструктур"
    assert first.get("year") == 2024

    second = rows[1]
    assert "Тонкие полимерные плёнки" in second["cite"]
    assert "doi" not in second
    assert second.get("year") == 2021

    third = rows[2]
    assert third["doi"] == "https://doi.org/10.1134/S107042722001001X"
    assert third.get("year") == 2020

    for row in rows:
        assert "person_id" not in row
        assert "/people/" not in str(row.values())


def test_migrated_publications_items_normalize_without_person_links():
    items = load_migrated_copy().get("publications_items") or []
    assert items, "publications_items starter catalogue required (ticket 09)"
    assert len(items) >= 30

    for raw in items:
        assert isinstance(raw, dict)
        assert _PERSON_KEYS.isdisjoint(raw.keys())
        assert "/people/" not in str(raw.values())
        doi = str(raw.get("doi") or "")
        assert "ichnm.mock" not in doi
        assert "10.0000/" not in doi
        assert "макет" not in str(raw.get("cite") or "").lower()

        payload = {
            "cite": raw["cite"],
            "lab_id": raw.get("lab_id") or "",
            "laboratory": raw.get("laboratory") or "",
        }
        if raw.get("doi"):
            payload["doi"] = raw["doi"]
        if raw.get("year") is not None:
            payload["year"] = raw["year"]
        norm = normalize_publication(payload)
        assert _PERSON_KEYS.isdisjoint(norm.keys())
        assert norm["cite"]
        if raw.get("doi"):
            assert norm["doi"].startswith("https://doi.org/")


def test_lab_pack_publications_have_no_mock_dois():
    for lab in load_migrated_copy().get("labs") or []:
        for row in lab.get("publications") or []:
            if not isinstance(row, dict):
                continue
            doi = str(row.get("doi") or "")
            assert "ichnm.mock" not in doi
            assert "макет" not in str(row.get("cite") or "").lower()

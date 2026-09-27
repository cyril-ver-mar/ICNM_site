"""Publication year grouping — mirrors ichnm_publications_group_by_year (ticket 48)."""

from __future__ import annotations


def publications_group_by_year(rows: list[dict]) -> dict[int, list[dict]]:
    groups: dict[int, list[dict]] = {}
    for row in rows:
        year = int(row.get("year") or 0)
        if year < 1900:
            year = 0
        groups.setdefault(year, []).append(row)
    return dict(sorted(groups.items(), key=lambda item: item[0], reverse=True))


def test_group_by_year_descending_and_insert_old():
    rows = [
        {"year": 2024, "id": 1},
        {"year": 2022, "id": 2},
        {"year": 2024, "id": 3},
        {"year": 2019, "id": 4},
    ]
    grouped = publications_group_by_year(rows)
    assert list(grouped.keys()) == [2024, 2022, 2019]
    assert [r["id"] for r in grouped[2024]] == [1, 3]

    # Inserting an older year creates a new section in place when re-grouped.
    rows.append({"year": 2018, "id": 5})
    grouped2 = publications_group_by_year(rows)
    assert list(grouped2.keys()) == [2024, 2022, 2019, 2018]
    assert grouped2[2018][0]["id"] == 5


def test_missing_year_bucket():
    rows = [{"year": 0, "id": 9}, {"id": 10}]
    grouped = publications_group_by_year(rows)
    assert list(grouped.keys()) == [0]
    assert len(grouped[0]) == 2

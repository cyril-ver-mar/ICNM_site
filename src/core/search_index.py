"""Public search index: people, units, facilities, developments."""

from __future__ import annotations

from typing import Any

from src.core import roster
from src.core.copy import load_migrated_copy


def build_search_index() -> list[dict[str, str]]:
    items: list[dict[str, str]] = []
    for person in roster.all_people().values():
        units = []
        for aff in person.get("affiliations") or []:
            _href, title = roster.unit_link(str(aff.get("unit_id") or ""), 0)
            role = aff.get("role") or ""
            units.append(f"{title} {role}".strip())
        items.append(
            {
                "kind": "person",
                "title": str(person.get("name") or ""),
                "href": roster.person_href(str(person["id"]), 0),
                "lead": person.get("role") or "",
                "text": " ".join(
                    [
                        str(person.get("name") or ""),
                        str(person.get("role") or ""),
                        str(person.get("degree") or ""),
                        *units,
                    ]
                ),
            }
        )
    for lab in roster.labs():
        slug = lab.get("slug") or lab["id"]
        items.append(
            {
                "kind": "unit",
                "title": str(lab["title"]),
                "href": f"labs/{slug}/index.html",
                "lead": str(lab.get("kicker") or "Лаборатория"),
                "text": " ".join(
                    [
                        str(lab["title"]),
                        str(lab.get("kicker") or ""),
                        str(lab.get("about_filled") or ""),
                    ]
                ),
            }
        )
    for unit in load_migrated_copy().get("admin_units") or []:
        items.append(
            {
                "kind": "unit",
                "title": str(unit["title"]),
                "href": f"{unit['id']}.html",
                "lead": "Административное подразделение",
                "text": f"{unit['title']} {unit.get('phone') or ''}",
            }
        )
    for unit_id, title, lead in (
        ("union", "Профсоюз", "Первичная организация"),
        ("young-scientists", "Совет молодых учёных", "СМУ Института"),
        ("leadership", "Руководство", "Дирекция"),
        ("scientific-council", "Учёный совет", "Состав совета"),
    ):
        href, _title = roster.unit_link(unit_id, 0)
        items.append(
            {
                "kind": "unit",
                "title": title,
                "href": href,
                "lead": lead,
                "text": f"{title} {lead}",
            }
        )
    for row in roster.developments_catalog():
        items.append(
            {
                "kind": "development",
                "title": str(row.get("title") or ""),
                "href": f"developments/{row['slug']}/index.html",
                "lead": str(row.get("lab_title") or "Разработка"),
                "text": " ".join(
                    [
                        str(row.get("title") or ""),
                        str(row.get("lead") or ""),
                        str(row.get("lab_title") or ""),
                        roster.catalog_meta(row),
                    ]
                ),
            }
        )
    for row in roster.facilities_catalog():
        slug = str(row.get("slug") or "")
        if not slug:
            continue
        items.append(
            {
                "kind": "facility",
                "title": str(row.get("title") or ""),
                "href": f"facilities/{slug}/index.html",
                "lead": str(row.get("lab_title") or "Прибор"),
                "text": " ".join(
                    [
                        str(row.get("title") or ""),
                        str(row.get("lead") or ""),
                        str(row.get("spec") or ""),
                        str(row.get("lab_title") or ""),
                        roster.catalog_meta(row),
                    ]
                ),
            }
        )
    return [row for row in items if row.get("title")]

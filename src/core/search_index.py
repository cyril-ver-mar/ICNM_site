"""Public search index: people, units, facilities, developments."""

from __future__ import annotations

from typing import Any

from src.core import i18n, roster
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
        loc_title = i18n.lab_title(str(lab["id"]), str(lab["title"]))
        items.append(
            {
                "kind": "unit",
                "title": loc_title,
                "href": f"labs/{slug}/index.html",
                "lead": str(lab.get("kicker") or i18n.string("search_unit_lab")),
                "text": " ".join(
                    [
                        str(lab["title"]),
                        loc_title,
                        str(lab.get("kicker") or ""),
                        str(lab.get("about_filled") or ""),
                    ]
                ),
            }
        )
    for unit in load_migrated_copy().get("admin_units") or []:
        loc_title = i18n.menu_title(str(unit["id"]), str(unit["title"]))
        items.append(
            {
                "kind": "unit",
                "title": loc_title,
                "href": f"{unit['id']}.html",
                "lead": i18n.string("search_unit_admin"),
                "text": f"{unit['title']} {loc_title} {unit.get('phone') or ''}",
            }
        )
    for unit_id, title, lead_key in (
        ("union", "Профсоюз", "search_unit_union"),
        ("young-scientists", "Совет молодых учёных", "search_unit_smu"),
        ("leadership", "Руководство", "search_unit_lead"),
        ("scientific-council", "Учёный совет", "search_unit_council"),
    ):
        href, loc_title = roster.unit_link(unit_id, 0)
        lead = i18n.string(lead_key)
        shown = i18n.menu_title(unit_id, title)
        items.append(
            {
                "kind": "unit",
                "title": shown,
                "href": href,
                "lead": lead,
                "text": f"{title} {shown} {lead}",
            }
        )
    for row in roster.developments_catalog():
        items.append(
            {
                "kind": "development",
                "title": str(row.get("title") or ""),
                "href": f"developments/{row['slug']}/index.html",
                "lead": str(row.get("lab_title") or i18n.string("search_dev")),
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
                "lead": str(row.get("lab_title") or i18n.string("search_fac")),
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

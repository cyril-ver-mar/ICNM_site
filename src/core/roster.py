"""People and lab-sourced catalogues for the public site."""

from __future__ import annotations

from typing import Any

from src.core import i18n
from src.core.copy import load_migrated_copy

_COMMUNITY_UNITS = frozenset({"union", "young-scientists"})

SMU_PEOPLE: tuple[dict[str, Any], ...] = (
    {
        "id": "smu-chair",
        "name": "Фамилия Имя Отчество",
        "role": "Председатель совета молодых учёных",
        "initials": "П",
        "affiliations": [{"unit_id": "young-scientists", "role": "Председатель совета молодых учёных"}],
    },
    {
        "id": "smu-deputy",
        "name": "Фамилия Имя Отчество",
        "role": "Заместитель председателя",
        "initials": "З",
        "affiliations": [{"unit_id": "young-scientists", "role": "Заместитель председателя"}],
    },
    {
        "id": "smu-secretary",
        "name": "Фамилия Имя Отчество",
        "role": "Секретарь",
        "initials": "С",
        "affiliations": [{"unit_id": "young-scientists", "role": "Секретарь"}],
    },
)


def _copy() -> dict[str, Any]:
    return load_migrated_copy()


def labs() -> list[dict[str, Any]]:
    return list(_copy().get("labs") or [])


def lab_by_id(lab_id: str) -> dict[str, Any] | None:
    return next((lab for lab in labs() if lab.get("id") == lab_id), None)


def people_by_id() -> dict[str, dict[str, Any]]:
    found: dict[str, dict[str, Any]] = {}
    for person in _copy().get("people") or []:
        found[str(person["id"])] = dict(person)
    for person in _copy().get("leadership_people") or []:
        pid = str(person["id"])
        if pid not in found:
            row = dict(person)
            row.setdefault(
                "affiliations",
                [{"unit_id": "leadership", "role": person.get("role") or ""}],
            )
            found[pid] = row
        else:
            merged = dict(person)
            merged.update(found[pid])
            for key in ("bio", "interests", "publications", "degree", "phone", "email"):
                if not merged.get(key) and person.get(key):
                    merged[key] = person[key]
            if not merged.get("affiliations") and person.get("affiliations"):
                merged["affiliations"] = person["affiliations"]
            found[pid] = merged
    return found


def all_people() -> dict[str, dict[str, Any]]:
    """Every person who can have a public page, including dummy office/council/SMU rows."""
    found = people_by_id()
    for person in _copy().get("council_people") or []:
        pid = str(person["id"])
        if pid in found:
            continue
        row = dict(person)
        row.setdefault(
            "affiliations",
            [{"unit_id": "scientific-council", "role": person.get("role") or ""}],
        )
        found[pid] = row
    names = {
        str(row.get("name") or "").strip(): pid
        for pid, row in found.items()
        if str(row.get("name") or "").strip()
    }
    for unit in _copy().get("admin_units") or []:
        for person in unit.get("people") or []:
            pid = str(person.get("id") or "")
            if not pid or pid in found:
                continue
            name = str(person.get("name") or "").strip()
            if name and name in names:
                continue
            row = dict(person)
            row.setdefault(
                "affiliations",
                [{"unit_id": unit["id"], "role": person.get("role") or ""}],
            )
            found[pid] = row
            if name:
                names[name] = pid
    for person in SMU_PEOPLE:
        pid = str(person["id"])
        if pid not in found:
            found[pid] = dict(person)
    return found


def person(person_id: str) -> dict[str, Any] | None:
    return all_people().get(person_id)


def people_for_unit(unit_id: str) -> list[dict[str, Any]]:
    out = []
    for row in all_people().values():
        for aff in row.get("affiliations") or []:
            if aff.get("unit_id") == unit_id:
                item = dict(row)
                item["unit_role"] = aff.get("role") or row.get("role") or ""
                out.append(item)
                break
    return out


def leadership_people() -> list[dict[str, Any]]:
    order = _copy().get("leadership_order") or [
        "rogachev",
        "ignatovich",
        "zuraev",
        "agabekov",
        "mikhailovsky",
        "lukovskaya",
    ]
    by_id = all_people()
    out = []
    for pid in order:
        row = by_id.get(pid)
        if not row:
            continue
        role = row.get("role") or ""
        for aff in row.get("affiliations") or []:
            if aff.get("unit_id") == "leadership" and aff.get("role"):
                role = aff["role"]
                break
        item = dict(row)
        item["unit_role"] = role
        out.append(item)
    return out


def unit_link(unit_id: str, depth: int = 0) -> tuple[str, str]:
    prefix = "../" * depth
    lab = lab_by_id(unit_id)
    if lab:
        slug = lab.get("slug") or lab["id"]
        return (
            f"{prefix}labs/{slug}/index.html",
            i18n.lab_title(str(lab["id"]), str(lab["title"])),
        )
    titles = {
        "leadership": "Руководство",
        "scientific-council": "Учёный совет",
        "hr": "Отдел кадров",
        "labor-protection": "Охрана труда",
        "engineering": "Главный инженер",
        "accounting": "Бухгалтерия",
        "union": "Профсоюз",
        "young-scientists": "Совет молодых учёных",
        "structure": "Структура",
    }
    files = {
        "leadership": "leadership.html",
        "scientific-council": "scientific-council.html",
        "hr": "hr.html",
        "labor-protection": "labor-protection.html",
        "engineering": "engineering.html",
        "accounting": "accounting.html",
        "union": "union.html",
        "young-scientists": "young-scientists.html",
        "structure": "structure.html",
    }
    title = i18n.menu_title(unit_id, titles.get(unit_id, unit_id))
    href = prefix + files.get(unit_id, f"{unit_id}.html")
    return href, title


def person_href(person_id: str, depth: int = 0) -> str:
    return f"{'../' * depth}people/{person_id}/index.html"


def person_nav_current(person: dict[str, Any]) -> str:
    affs = person.get("affiliations") or []
    if not affs:
        return "structure"
    return str(affs[0].get("unit_id") or "structure")


def community_unit_ids() -> frozenset[str]:
    return _COMMUNITY_UNITS


def lab_directions() -> list[dict[str, Any]]:
    rows = []
    for lab in labs():
        for item in lab.get("directions") or []:
            row = dict(item)
            row["lab_id"] = lab["id"]
            row["lab_title"] = lab["title"]
            row["head_id"] = lab.get("head_id") or ""
            rows.append(row)
    return rows


def lab_developments() -> list[dict[str, Any]]:
    rows = []
    for lab in labs():
        for item in lab.get("developments") or []:
            row = dict(item)
            row["lab_id"] = lab["id"]
            row["lab_title"] = lab["title"]
            row.setdefault("staff_id", lab.get("head_id") or "")
            rows.append(row)
    return rows


def lab_equipment() -> list[dict[str, Any]]:
    rows = []
    for lab in labs():
        for item in lab.get("equipment") or []:
            row = dict(item)
            row["lab_id"] = lab["id"]
            row["lab_title"] = lab["title"]
            rows.append(row)
    return rows


def lab_publications() -> list[dict[str, Any]]:
    rows = []
    for lab in labs():
        for item in lab.get("publications") or []:
            row = dict(item)
            row["lab_id"] = lab["id"]
            row["lab_title"] = lab["title"]
            rows.append(row)
    return rows


def head_contact_line(lab: dict[str, Any]) -> str:
    return person_contact_line(str(lab.get("head_id") or ""))


def person_contact_line(person_id: str) -> str:
    row = person(person_id)
    if not row:
        return ""
    bits = [str(row["name"])]
    if row.get("phone"):
        bits.append(str(row["phone"]))
    if row.get("email"):
        bits.append(str(row["email"]))
    return " · ".join(bits)


def science_catalog() -> list[dict[str, Any]]:
    rows = []
    for item in _copy().get("science_topics") or []:
        row = dict(item)
        row["scope"] = "institute"
        rows.append(row)
    for item in lab_directions():
        row = dict(item)
        row["scope"] = "lab"
        rows.append(row)
    return rows


def developments_catalog() -> list[dict[str, Any]]:
    rows = []
    seen: set[str] = set()
    for item in lab_developments():
        row = dict(item)
        row["scope"] = "lab"
        rows.append(row)
        seen.add(str(row.get("slug") or ""))
    for item in _copy().get("developments_items") or []:
        slug = str(item.get("slug") or "")
        if slug in seen:
            continue
        row = dict(item)
        row["scope"] = "institute"
        rows.append(row)
    return rows


def facilities_catalog() -> list[dict[str, Any]]:
    rows = []
    seen: set[str] = set()
    for item in lab_equipment():
        row = dict(item)
        row["scope"] = "lab"
        rows.append(row)
        seen.add(str(row.get("slug") or ""))
    for item in _copy().get("facilities_items") or []:
        slug = str(item.get("slug") or "")
        if slug in seen:
            continue
        row = dict(item)
        row["scope"] = "institute"
        rows.append(row)
    return rows


def catalog_meta(item: dict[str, Any]) -> str:
    bits: list[str] = []
    lab_title = item.get("lab_title") or ""
    if lab_title:
        bits.append(str(lab_title))
    staff_id = str(item.get("staff_id") or item.get("head_id") or "")
    contact = person_contact_line(staff_id) if staff_id else ""
    if contact:
        bits.append(contact)
    elif item.get("contacts"):
        bits.append(str(item["contacts"]))
    return " · ".join(bits)

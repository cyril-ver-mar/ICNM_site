"""Lab catalogue CPT seed helpers (mirrors PHP import keys).

Used by pytest without WordPress. Source of truth for day-to-day edits is WP CPT;
this module only normalises migrated_copy rows into stable import keys / lab slugs.
"""

from __future__ import annotations

from typing import Any, Mapping

from src.core.copy import load_migrated_copy

PARENT_TO_TYPE: dict[str, str] = {
    "science": "direction",
    "facilities": "facility",
    "developments": "development",
}

FIELD_BY_PARENT: dict[str, str] = {
    "science": "directions",
    "facilities": "equipment",
    "developments": "developments",
}

INSTITUTE_KEYS: dict[str, str] = {
    "science": "science_topics",
    "facilities": "facilities_items",
    "developments": "developments_items",
}


def catalogue_key(post_type: str, slug: str) -> str:
    """Stable meta key: ``{type}:{slug}``."""
    return f"{post_type}:{slug.strip()}"


def lab_slug_from_row(row: Mapping[str, Any]) -> str:
    """Resolve lab slug from lab_slug or lab_id (``lab-nano`` → ``nano``)."""
    slug = str(row.get("lab_slug") or "").strip()
    if slug:
        return slug
    lab_id = str(row.get("lab_id") or "").strip()
    if lab_id.startswith("lab-"):
        return lab_id[4:]
    return lab_id


def _normalize_row(row: Mapping[str, Any], *, lab_id: str = "", lab_title: str = "", head_id: str = "") -> dict[str, Any]:
    out = dict(row)
    if lab_id and not out.get("lab_id"):
        out["lab_id"] = lab_id
    if lab_title and not out.get("lab_title"):
        out["lab_title"] = lab_title
    out["lab_slug"] = lab_slug_from_row(out)
    if head_id and not out.get("staff_id") and not out.get("head_id"):
        out["head_id"] = head_id
    return out


def seed_rows_for_parent(parent: str, copy: Mapping[str, Any] | None = None) -> list[dict[str, Any]]:
    """Collect seed rows for one hub parent (institute + lab packs)."""
    if parent not in PARENT_TO_TYPE:
        return []
    data = copy if copy is not None else load_migrated_copy()
    field = FIELD_BY_PARENT[parent]
    institute_key = INSTITUTE_KEYS[parent]
    rows: list[dict[str, Any]] = []
    seen: set[str] = set()

    lab_rows: list[dict[str, Any]] = []
    for lab in data.get("labs") or []:
        if not isinstance(lab, dict):
            continue
        lab_id = str(lab.get("id") or "")
        lab_title = str(lab.get("title") or "")
        head_id = str(lab.get("head_id") or "")
        for item in lab.get(field) or []:
            if isinstance(item, str):
                continue
            if not isinstance(item, dict):
                continue
            norm = _normalize_row(item, lab_id=lab_id, lab_title=lab_title, head_id=head_id)
            if parent == "science" and head_id and not norm.get("staff_id"):
                norm["head_id"] = head_id
            elif parent == "developments" and head_id and not norm.get("staff_id"):
                norm["staff_id"] = head_id
            slug = str(norm.get("slug") or "").strip()
            if slug:
                seen.add(slug)
            lab_rows.append(norm)

    institute_raw = data.get(institute_key) or []
    institute: list[dict[str, Any]] = []
    if isinstance(institute_raw, list):
        for item in institute_raw:
            if not isinstance(item, dict):
                continue
            slug = str(item.get("slug") or "").strip()
            if slug and slug in seen:
                continue
            institute.append(_normalize_row(item))

    if parent == "science":
        rows = list(institute) + lab_rows
    else:
        rows = institute + lab_rows
    return rows


def seed_import_entries(copy: Mapping[str, Any] | None = None) -> list[dict[str, Any]]:
    """Flat list of CPT import entries with type, key, slug, lab_slug, title."""
    data = copy if copy is not None else load_migrated_copy()
    out: list[dict[str, Any]] = []
    for parent, post_type in PARENT_TO_TYPE.items():
        for row in seed_rows_for_parent(parent, data):
            slug = str(row.get("slug") or "").strip()
            if not slug:
                continue
            out.append(
                {
                    "type": post_type,
                    "parent": parent,
                    "slug": slug,
                    "key": catalogue_key(post_type, slug),
                    "lab_slug": lab_slug_from_row(row),
                    "title": str(row.get("title") or slug),
                    "staff_id": str(row.get("staff_id") or row.get("head_id") or ""),
                }
            )
    return out

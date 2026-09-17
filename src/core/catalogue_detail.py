"""Catalogue detail contract for science / developments / facilities.

Mirrors honest preview ``_catalog_detail_body``: title, lead, contacts,
lab/staff links when ids are known, spec|product, honest photo-slot.
Never emits preview-filled fish imagery.
"""

from __future__ import annotations

from html import escape
from typing import Any, Mapping

from src.core import roster

CATALOGUE_PARENTS: tuple[str, ...] = ("science", "developments", "facilities")

PARENT_TITLES: dict[str, str] = {
    "science": "Направления работы",
    "developments": "Разработки",
    "facilities": "Материальная база",
}

_DEFAULT_CONTACTS = "ichnm@ichnm.by"
_PHOTO_PENDING = "Фото появится после передачи файла"
_PHOTO_SLOT_PREFIX = "Место для фото: "
_CONTACTS_HEADING = "Описание и контакты"
_LAB_LABEL = "Лаборатория: "
_ASSIGNED_LABEL = "Закреплено: "
_SPEC_HEADING = "Спецификация"
_PRODUCT_HEADING = "Продукт / результат"


def catalogue_detail_path(parent: str, slug: str = "") -> str:
    """Public path: ``/science/``, ``/science/{slug}/``, etc."""
    base = f"/{parent.strip('/')}/"
    if not slug:
        return base
    return f"{base}{slug.strip('/')}/"


def _unit_href_title(unit_id: str, depth: int) -> tuple[str, str]:
    """Lab/unit link: WP absolute paths at depth 0, preview-relative otherwise."""
    href, title = roster.unit_link(unit_id, depth)
    if depth != 0:
        return href, title
    lab = roster.lab_by_id(unit_id)
    if lab:
        slug = str(lab.get("slug") or lab["id"])
        return f"/labs/{slug}/", title
    if href.endswith(".html"):
        return "/" + href[: -len(".html")].lstrip("/") + "/", title
    if href.startswith("labs/"):
        clean = href.replace("/index.html", "/").rstrip("/")
        return f"/{clean}/", title
    return href, title


def find_catalogue_item(parent: str, slug: str) -> dict[str, Any] | None:
    """Return the catalogue row for ``parent`` + ``slug``, or ``None``."""
    if parent not in CATALOGUE_PARENTS:
        return None
    target = str(slug or "").strip()
    if not target:
        return None
    catalog = {
        "science": roster.science_catalog,
        "developments": roster.developments_catalog,
        "facilities": roster.facilities_catalog,
    }[parent]
    for row in catalog():
        if str(row.get("slug") or "") == target:
            return dict(row)
    return None


def normalize_catalogue_detail(
    parent: str,
    item: Mapping[str, Any],
    *,
    depth: int = 0,
) -> dict[str, Any]:
    """Normalize one catalogue row into the locked detail contract."""
    if parent not in CATALOGUE_PARENTS:
        raise ValueError(f"unknown catalogue parent: {parent!r}")

    slug = str(item.get("slug") or "").strip()
    title = str(item.get("title") or "").strip()
    lead = str(item.get("lead") or "").strip()
    is_facilities = parent == "facilities"
    detail_text = str(item.get("spec") or item.get("product") or "").strip()
    contacts = str(item.get("contacts") or "").strip()
    if not contacts:
        contacts = roster.catalog_meta(dict(item)) or _DEFAULT_CONTACTS

    lab_id = str(item.get("lab_id") or "").strip()
    lab_href = ""
    lab_title = ""
    if lab_id:
        lab_href, lab_title = _unit_href_title(lab_id, depth)

    staff_id = str(item.get("staff_id") or item.get("head_id") or "").strip()
    staff_href = ""
    staff_label = ""
    if staff_id:
        staff_label = roster.person_contact_line(staff_id) or staff_id
        staff_href = (
            f"/people/{staff_id}/" if depth == 0 else roster.person_href(staff_id, depth)
        )

    return {
        "parent": parent,
        "slug": slug,
        "title": title,
        "lead": lead,
        "contacts": contacts,
        "detail_heading": _SPEC_HEADING if is_facilities else _PRODUCT_HEADING,
        "detail_text": detail_text,
        "photo_kind": "equipment" if is_facilities else "cover",
        "lab_id": lab_id,
        "lab_href": lab_href,
        "lab_title": lab_title,
        "staff_id": staff_id,
        "staff_href": staff_href,
        "staff_label": staff_label,
        "path": catalogue_detail_path(parent, slug),
        "parent_path": catalogue_detail_path(parent),
        "parent_title": PARENT_TITLES[parent],
    }


def _honest_photo_slot(label: str) -> str:
    return (
        f'<div class="photo-slot" role="img" '
        f'aria-label="{escape(_PHOTO_SLOT_PREFIX + label)}">'
        f"<p>{escape(_PHOTO_PENDING)}</p></div>"
    )


def catalogue_detail_html(detail: Mapping[str, Any]) -> str:
    """Honest detail body HTML matching preview contour (no filled fish)."""
    title = str(detail.get("title") or "")
    lead = str(detail.get("lead") or "")
    contacts = str(detail.get("contacts") or "")
    detail_heading = str(detail.get("detail_heading") or _PRODUCT_HEADING)
    detail_text = str(detail.get("detail_text") or "")
    parent_path = str(detail.get("parent_path") or catalogue_detail_path(str(detail.get("parent") or "")))
    parent_title = str(detail.get("parent_title") or PARENT_TITLES.get(str(detail.get("parent") or ""), ""))

    parts: list[str] = [
        f"<h1>{escape(title)}</h1>",
        _honest_photo_slot(title),
    ]
    if lead:
        parts.append(f"<p>{escape(lead)}</p>")
    parts.append(f"<h2>{escape(_CONTACTS_HEADING)}</h2>")
    if contacts:
        parts.append(f"<p>{escape(contacts)}</p>")

    lab_href = str(detail.get("lab_href") or "")
    lab_title = str(detail.get("lab_title") or "")
    if lab_href and lab_title:
        parts.append(
            f"<p>{escape(_LAB_LABEL)}"
            f'<a href="{escape(lab_href)}">{escape(lab_title)}</a></p>'
        )

    staff_href = str(detail.get("staff_href") or "")
    staff_label = str(detail.get("staff_label") or "")
    if staff_href and staff_label:
        parts.append(
            f"<p>{escape(_ASSIGNED_LABEL)}"
            f'<a href="{escape(staff_href)}">{escape(staff_label)}</a></p>'
        )

    parts.append(f"<h2>{escape(detail_heading)}</h2>")
    if detail_text:
        parts.append(f"<p>{escape(detail_text)}</p>")
    if parent_path and parent_title:
        parts.append(f'<p><a href="{escape(parent_path)}">{escape(parent_title)}</a></p>')
    return "".join(parts)

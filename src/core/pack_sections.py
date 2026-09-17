"""Pure-Python contracts for laboratory and admin-unit page packs.

Mirrors the section anchors / chrome classes used by the honest preview builder
and the WordPress content-sync lab/admin HTML — without requiring Docker.
"""

from __future__ import annotations

import re
from typing import Any, Mapping

# Locked lab-pack order: about → … → contacts (DECISIONS / cutover readiness seam).
LAB_PACK_SECTION_IDS: tuple[str, ...] = (
    "about",
    "directions",
    "projects",
    "equipment",
    "services",
    "staff",
    "pubs",
    "contacts",
)

ADMIN_UNIT_REQUIRED_CLASSES: tuple[str, ...] = ("unit-back", "people-list")

_SECTION_ID_RE = re.compile(r'<section\s+[^>]*\bid="([^"]+)"', re.IGNORECASE)


def lab_pack_section_ids() -> tuple[str, ...]:
    return LAB_PACK_SECTION_IDS


def section_ids_in_html(html: str) -> list[str]:
    return _SECTION_ID_RE.findall(html)


def lab_pack_skeleton_html(lab: Mapping[str, Any] | None = None) -> str:
    """Minimal lab pack HTML with required nav anchors and section ids.

    Empty slots are intentional: the contract is the section set, not filled copy.
    """
    _ = lab  # fixture may pass a real lab; content does not affect required ids
    nav = "".join(f'<a href="#{sid}">{sid}</a>' for sid in LAB_PACK_SECTION_IDS)
    sections = "".join(
        f'<section id="{sid}"><h2>{sid}</h2></section>' for sid in LAB_PACK_SECTION_IDS
    )
    return (
        f'<nav class="lab-local" aria-label="lab">{nav}</nav>'
        f"{sections}"
    )


def admin_unit_pack_html(unit: Mapping[str, Any]) -> str:
    """Minimal admin-unit pack: back link, optional phone, people-list (empty OK)."""
    phone = str(unit.get("phone") or "").strip()
    people = unit.get("people") or []
    parts = [
        '<p class="unit-back"><a href="structure.html">Ко всем подразделениям</a></p>',
    ]
    if phone:
        parts.append(f"<p>Тел. подразделения: {phone}</p>")
    cards: list[str] = []
    for person in people:
        if not isinstance(person, Mapping):
            continue
        name = str(person.get("name") or "").strip() or str(person.get("id") or "")
        if not name:
            continue
        role = str(person.get("role") or "")
        cards.append(f'<article class="person-card"><h3>{name}</h3><p>{role}</p></article>')
    parts.append(f'<div class="people-list">{"".join(cards)}</div>')
    return "".join(parts)

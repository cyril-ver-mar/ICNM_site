"""Scientific council, union/SMU vitrines, and structure-hub link lists.

Pure Python contracts for ticket 11 — mirrors WordPress content-sync and
honest preview without Docker. Structure hub lists units only; staff live on
unit/lab/person pages (DECISIONS).
"""

from __future__ import annotations

from html import escape
from typing import Any, Mapping, Sequence

from src.core.person_page import person_list_card_html
from src.core.roster import SMU_PEOPLE

COUNCIL_PAGE_ID = "scientific-council"
COMMUNITY_PAGE_IDS: tuple[str, ...] = ("union", "young-scientists")
VITRINE_PAGE_IDS: tuple[str, ...] = (
    COUNCIL_PAGE_ID,
    "union",
    "young-scientists",
)

COMMUNITY_TITLES: dict[str, str] = {
    "union": "Профсоюз",
    "young-scientists": "Совет молодых учёных",
}


def honest_page_ready(pages: Mapping[str, Any], page_id: str) -> bool:
    """True when the page has non-empty paragraphs or an explicit empty_slot."""
    body = pages.get(page_id)
    if not isinstance(body, Mapping):
        return False
    paragraphs = body.get("paragraphs") or []
    if any(str(p).strip() for p in paragraphs if p is not None):
        return True
    slot = body.get("empty_slot")
    if isinstance(slot, str) and slot.strip():
        return True
    if slot is True:
        return True
    return False


def person_href(person_id: str) -> str:
    return f"/people/{person_id}/"


def council_listing_html(
    people: Sequence[Mapping[str, Any]],
    *,
    intro_paragraphs: Sequence[str] | None = None,
) -> str:
    """Leadership-style whole-card links to people/{id}/."""
    parts: list[str] = []
    for para in intro_paragraphs or []:
        text = str(para).strip()
        if text:
            parts.append(f"<p>{escape(text)}</p>")
    cards = ['<div class="people-list ichnm-card-grid ichnm-leadership-grid">']
    for row in people:
        if not isinstance(row, Mapping):
            continue
        pid = str(row.get("id") or "").strip()
        if not pid:
            continue
        cards.append(
            person_list_card_html(
                name=str(row.get("name") or pid),
                role=str(row.get("role") or ""),
                href=person_href(pid),
                degree=str(row.get("degree") or ""),
                phone=str(row.get("phone") or ""),
                initials=str(row.get("initials") or ""),
            )
        )
    cards.append("</div>")
    parts.append("".join(cards))
    return "".join(parts)


def structure_hub_link_ids(
    labs: Sequence[Mapping[str, Any]],
    admin_units: Sequence[Mapping[str, Any]],
    *,
    community_ids: Sequence[str] = COMMUNITY_PAGE_IDS,
) -> dict[str, list[str]]:
    lab_ids = [
        str(lab.get("id") or lab.get("slug") or "")
        for lab in labs
        if isinstance(lab, Mapping) and (lab.get("id") or lab.get("slug"))
    ]
    admin_ids = [
        str(unit.get("id") or "")
        for unit in admin_units
        if isinstance(unit, Mapping) and unit.get("id")
    ]
    return {
        "labs": [i for i in lab_ids if i],
        "admin": [i for i in admin_ids if i],
        "community": [str(i) for i in community_ids],
    }


def structure_hub_html(
    labs: Sequence[Mapping[str, Any]],
    admin_units: Sequence[Mapping[str, Any]],
    *,
    community_ids: Sequence[str] = COMMUNITY_PAGE_IDS,
    community_titles: Mapping[str, str] | None = None,
) -> str:
    """Hub of unit links only — never embeds person cards or people/ URLs."""
    titles = dict(COMMUNITY_TITLES)
    if community_titles:
        titles.update({str(k): str(v) for k, v in community_titles.items()})

    parts: list[str] = ['<h2>Лаборатории</h2><ul class="ichnm-structure-labs">']
    for lab in labs:
        if not isinstance(lab, Mapping):
            continue
        slug = str(lab.get("slug") or lab.get("id") or "").strip()
        if not slug:
            continue
        title = str(lab.get("title") or slug)
        parts.append(
            f'<li><a href="/labs/{escape(slug)}/">{escape(title)}</a></li>'
        )
    parts.append("</ul>")

    parts.append("<h2>Административные подразделения</h2><ul>")
    for unit in admin_units:
        if not isinstance(unit, Mapping):
            continue
        uid = str(unit.get("id") or "").strip()
        if not uid:
            continue
        title = str(unit.get("title") or uid)
        parts.append(f'<li><a href="/{escape(uid)}/">{escape(title)}</a></li>')
    parts.append("</ul>")

    parts.append("<h2>Общественные объединения</h2><ul>")
    for cid in community_ids:
        cid = str(cid)
        title = titles.get(cid, cid)
        parts.append(f'<li><a href="/{escape(cid)}/">{escape(title)}</a></li>')
    parts.append("</ul>")
    return "".join(parts)


def community_vitrine_html(
    page_id: str,
    page_body: Mapping[str, Any],
    *,
    people: Sequence[Mapping[str, Any]] | None = None,
) -> str:
    """Honest paragraphs; SMU may add placeholder person cards."""
    parts: list[str] = []
    for para in page_body.get("paragraphs") or []:
        text = str(para).strip()
        if text:
            parts.append(f"<p>{escape(text)}</p>")
    slot = page_body.get("empty_slot")
    if isinstance(slot, str) and slot.strip():
        parts.append(f'<p class="ichnm-empty-slot">{escape(slot.strip())}</p>')
    elif slot is True and not parts:
        parts.append(
            '<p class="ichnm-empty-slot">Текст появится после передачи материалов.</p>'
        )

    roster: Sequence[Mapping[str, Any]]
    if people is not None:
        roster = people
    elif page_id == "young-scientists":
        roster = list(SMU_PEOPLE)
    else:
        roster = ()

    if roster:
        parts.append('<div class="people-list">')
        for row in roster:
            if not isinstance(row, Mapping):
                continue
            pid = str(row.get("id") or "").strip()
            if not pid:
                continue
            parts.append(
                person_list_card_html(
                    name=str(row.get("name") or pid),
                    role=str(row.get("unit_role") or row.get("role") or ""),
                    href=person_href(pid),
                    degree=str(row.get("degree") or ""),
                    phone=str(row.get("phone") or ""),
                    initials=str(row.get("initials") or ""),
                )
            )
        parts.append("</div>")
    return "".join(parts)

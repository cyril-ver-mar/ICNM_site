"""Scientific council, union/SMU vitrines, and structure-hub link lists.

Pure Python contracts for ticket 11 — mirrors WordPress content-sync and
honest preview without Docker. Structure hub lists units only; staff live on
unit/lab/person pages (DECISIONS).
"""

from __future__ import annotations

from html import escape
from typing import Any, Mapping, Sequence

from src.core.lab_accent import (
    THIN_FILM_CHILD_SLUGS,
    THIN_FILM_DEPT_TITLE,
    lab_accent_token,
)
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

COMMUNITY_CHAIR_FALLBACK: dict[str, str] = {
    "union": "Южик Любовь Ивановна",
    "young-scientists": "Фамилия Имя Отчество",
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


def structure_hub_groups(
    labs: Sequence[Mapping[str, Any]],
    *,
    thin_film_slugs: Sequence[str] = THIN_FILM_CHILD_SLUGS,
) -> dict[str, Any]:
    """Split labs into thin-film department children and the rest (ticket 55)."""
    by_slug: dict[str, Mapping[str, Any]] = {}
    order: list[str] = []
    for lab in labs:
        if not isinstance(lab, Mapping):
            continue
        slug = str(lab.get("slug") or "").strip()
        if not slug:
            continue
        by_slug[slug] = lab
        order.append(slug)
    thin: list[Mapping[str, Any]] = []
    for slug in thin_film_slugs:
        if slug in by_slug:
            thin.append(by_slug[slug])
    thin_set = set(thin_film_slugs)
    other = [by_slug[s] for s in order if s not in thin_set]
    return {
        "thin_film_title": THIN_FILM_DEPT_TITLE,
        "thin_film": thin,
        "other": other,
    }


def _lab_tile_html(
    lab: Mapping[str, Any],
    *,
    people_by_id: Mapping[str, Mapping[str, Any]] | None = None,
) -> str:
    slug = str(lab.get("slug") or "").strip()
    title = str(lab.get("title") or slug)
    accent = lab_accent_token(slug)
    accent_attr = f' style="--lab-accent:{escape(accent)}"' if accent else ""
    head_name = ""
    head_id = str(lab.get("head_id") or "").strip()
    if head_id and people_by_id and head_id in people_by_id:
        head_name = str(people_by_id[head_id].get("name") or "").strip()
    parts = [
        f'<li class="ichnm-lab-tile" data-lab-slug="{escape(slug)}"{accent_attr}>',
        f'<a href="/labs/{escape(slug)}/">{escape(title)}',
    ]
    if head_name:
        parts.append(
            f'<p class="ichnm-structure-head">'
            f'<span class="ichnm-structure-role">заведующий</span> {escape(head_name)}</p>'
        )
    parts.append("</a></li>")
    return "".join(parts)


def structure_hub_html_tiles(
    labs: Sequence[Mapping[str, Any]],
    admin_units: Sequence[Mapping[str, Any]],
    *,
    people_by_id: Mapping[str, Mapping[str, Any]] | None = None,
    community_ids: Sequence[str] = COMMUNITY_PAGE_IDS,
    community_titles: Mapping[str, str] | None = None,
    community_chairs: Mapping[str, str] | None = None,
) -> str:
    """Tile-grid structure hub (tickets 55–56): one tile per unit, thin-film group."""
    titles = dict(COMMUNITY_TITLES)
    if community_titles:
        titles.update({str(k): str(v) for k, v in community_titles.items()})
    chairs = dict(COMMUNITY_CHAIR_FALLBACK)
    if community_chairs:
        chairs.update({str(k): str(v) for k, v in community_chairs.items()})

    groups = structure_hub_groups(labs)
    parts: list[str] = ['<h2>Лаборатории</h2>']
    parts.append('<div class="ichnm-thin-film-group">')
    parts.append(
        f'<p class="ichnm-thin-film-label">{escape(str(groups["thin_film_title"]))}</p>'
    )
    parts.append('<ul class="lab-grid ichnm-structure-grid">')
    for lab in groups["thin_film"]:
        parts.append(_lab_tile_html(lab, people_by_id=people_by_id))
    parts.append("</ul></div>")
    if groups["other"]:
        parts.append('<ul class="lab-grid ichnm-structure-grid">')
        for lab in groups["other"]:
            parts.append(_lab_tile_html(lab, people_by_id=people_by_id))
        parts.append("</ul>")

    parts.append('<h2>Административные подразделения</h2>')
    parts.append('<ul class="dir-grid ichnm-structure-grid">')
    for unit in admin_units:
        if not isinstance(unit, Mapping):
            continue
        uid = str(unit.get("id") or "").strip()
        if not uid:
            continue
        title = str(unit.get("title") or uid)
        head_name = ""
        unit_people = unit.get("people") or []
        if isinstance(unit_people, Sequence) and unit_people:
            first = unit_people[0]
            if isinstance(first, Mapping):
                head_name = str(first.get("name") or first.get("role") or "").strip()
        parts.append(f'<li><a href="/{escape(uid)}/">{escape(title)}')
        if head_name and not head_name.startswith("Фамилия"):
            parts.append(
                f'<p class="ichnm-structure-head">{escape(head_name)}</p>'
            )
        parts.append("</a></li>")
    parts.append("</ul>")

    parts.append('<h2>Общественные объединения</h2>')
    parts.append('<ul class="dir-grid ichnm-structure-grid">')
    for cid in community_ids:
        cid = str(cid)
        title = titles.get(cid, cid)
        chair = chairs.get(cid, "")
        parts.append(f'<li><a href="/{escape(cid)}/">{escape(title)}')
        if chair:
            parts.append(
                f'<p class="ichnm-structure-head">'
                f'<span class="ichnm-structure-role">председатель</span> '
                f"{escape(chair)}</p>"
            )
        parts.append("</a></li>")
    parts.append("</ul>")
    return "".join(parts)


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

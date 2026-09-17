"""Person-page contracts: affiliations and scientific metrics.

Mirrors honest preview + WordPress person HTML (ticket 03) without Docker.
Locked metric order: ORCID → Google Scholar → Scopus Author → eLIBRARY/РИНЦ → ResearchGate.
A network is omitted when both URL and numbers are missing. List cards are whole-link
with no «Биография и публикации» caption.
"""

from __future__ import annotations

from html import escape
from typing import Any, Callable, Mapping, Sequence

METRIC_FIELDS: tuple[str, ...] = (
    "orcid",
    "google_scholar",
    "scopus_author",
    "elibrary",
    "researchgate",
)

METRIC_LABELS: dict[str, str] = {
    "orcid": "ORCID",
    "google_scholar": "Google Scholar",
    "scopus_author": "Scopus Author",
    "elibrary": "eLIBRARY / РИНЦ",
    "researchgate": "ResearchGate",
}

UnitResolver = Callable[[str], tuple[str, str]]


def metric_fields() -> tuple[str, ...]:
    return METRIC_FIELDS


def profile_href(field: str, raw: str) -> str:
    value = (raw or "").strip()
    if not value:
        return ""
    lower = value.lower()
    if lower.startswith("http://") or lower.startswith("https://"):
        return value
    if field == "orcid":
        return f"https://orcid.org/{value}"
    return ""


def _profiles_for(person: Mapping[str, Any]) -> dict[str, str]:
    profiles = dict(person.get("profiles") or {})
    if person.get("orcid") and not profiles.get("orcid"):
        profiles["orcid"] = str(person["orcid"])
    return {str(k): str(v) for k, v in profiles.items() if v}


def visible_metric_networks(
    person: Mapping[str, Any],
    fields: Sequence[str] | None = None,
) -> list[dict[str, Any]]:
    """Networks that have a URL and/or bibliometric numbers, in locked order."""
    order = tuple(fields) if fields is not None else METRIC_FIELDS
    profiles = _profiles_for(person)
    bibliometrics = person.get("bibliometrics") or {}
    rows: list[dict[str, Any]] = []
    for field in order:
        href = profile_href(field, str(profiles.get(field) or person.get(field) or ""))
        stats = bibliometrics.get(field) or {}
        h_index = stats.get("h_index") if isinstance(stats, Mapping) else None
        citations = stats.get("citations") if isinstance(stats, Mapping) else None
        if not href and h_index is None and citations is None:
            continue
        if h_index == "":
            h_index = None
        if citations == "":
            citations = None
        if not href and h_index is None and citations is None:
            continue
        rows.append(
            {
                "field": field,
                "label": METRIC_LABELS.get(field, field),
                "href": href,
                "h_index": h_index,
                "citations": citations,
            }
        )
    return rows


def affiliations_items(
    affiliations: Sequence[Mapping[str, Any]] | None,
    resolve_unit: UnitResolver,
) -> list[tuple[str, str]]:
    """Return (href, label) for each affiliation; label is «Unit — role» when role set."""
    items: list[tuple[str, str]] = []
    for aff in affiliations or []:
        if not isinstance(aff, Mapping):
            continue
        unit_id = str(aff.get("unit_id") or "").strip()
        if not unit_id:
            continue
        href, title = resolve_unit(unit_id)
        role = str(aff.get("role") or "").strip()
        label = title
        if role:
            label = f"{title} — {role}"
        items.append((href, label))
    return items


def affiliations_html(
    affiliations: Sequence[Mapping[str, Any]] | None,
    resolve_unit: UnitResolver,
    *,
    heading: str = "Подразделения",
) -> str:
    items = affiliations_items(affiliations, resolve_unit)
    if not items:
        return ""
    lis = "".join(
        f'<li><a href="{escape(href)}">{escape(label)}</a></li>' for href, label in items
    )
    return f"<h2>{escape(heading)}</h2><ul class=\"plain-list\">{lis}</ul>"


def metrics_html(
    person: Mapping[str, Any],
    fields: Sequence[str] | None = None,
    *,
    include_empty_state: bool = True,
) -> str:
    rows = visible_metric_networks(person, fields)
    if not rows:
        if not include_empty_state:
            return ""
        return (
            '<div class="empty-state">'
            "<h2>Профили и показатели ещё не указаны</h2>"
            "<p>ORCID, Google Scholar, Scopus, eLIBRARY/РИНЦ и ResearchGate появятся "
            "после передачи ссылок. Индекс Хирша и число цитирований вносит "
            "сотрудник или редактор — сайт базы сам не опрашивает.</p>"
            "</div>"
        )
    bits_rows: list[str] = []
    for row in rows:
        bits: list[str] = []
        if row["h_index"] is not None:
            bits.append(f"h-индекс {escape(str(row['h_index']))}")
        if row["citations"] is not None:
            bits.append(f"цитирований {escape(str(row['citations']))}")
        if row["href"]:
            bits.append(
                f'<a href="{escape(row["href"])}" rel="noopener noreferrer">профиль</a>'
            )
        bits_rows.append(
            f"<dt>{escape(row['label'])}</dt><dd>{' · '.join(bits) or '—'}</dd>"
        )
    return (
        "<h2>Наукометрия</h2>"
        '<p class="metrics-note">Цифры и ссылки вносит сотрудник или редактор. '
        "Сайт не подтягивает базы автоматически.</p>"
        f'<dl class="metrics-list">{"".join(bits_rows)}</dl>'
    )


def person_list_card_html(
    *,
    name: str,
    role: str = "",
    href: str,
    degree: str = "",
    phone: str = "",
    initials: str = "",
) -> str:
    """Whole-card hyperlink; never adds «Биография и публикации»."""
    mark = initials or (name[:1] if name else "")
    parts = [
        f'<a class="ichnm-person-card" href="{escape(href)}">',
        f'<span class="ichnm-person-card-photo" aria-hidden="true">{escape(mark)}</span>',
        '<span class="ichnm-person-card-body">',
        f'<span class="ichnm-person-card-role">{escape(role)}</span>',
        f'<span class="ichnm-person-card-name">{escape(name)}</span>',
    ]
    if degree:
        parts.append(f'<span class="ichnm-person-card-degree">{escape(degree)}</span>')
    if phone:
        parts.append(f'<span class="ichnm-person-card-phone">Тел. {escape(phone)}</span>')
    parts.append("</span></a>")
    return "".join(parts)

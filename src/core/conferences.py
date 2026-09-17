"""Conference archive URLs shared by honest preview and WordPress hubs.

AIST and events link titles to ``conferences/{slug}/``. Registration stays on
aist.ichnm.by and is not part of these archive cards.
"""

from __future__ import annotations

from html import escape
from typing import Any, Mapping, Sequence


def conference_path(slug: str) -> str:
    """Return the public path for one conference page (trailing slash)."""
    clean = str(slug or "").strip().strip("/")
    if not clean:
        raise ValueError("conference slug is required")
    return f"conferences/{clean}/"


def conference_archive_html(
    rows: Sequence[Mapping[str, Any]],
    *,
    href_prefix: str = "",
) -> str:
    """Build archive markup with clickable titles → conferences/{slug}/."""
    aist = [row for row in rows if row.get("kind") == "aist"]
    other = [row for row in rows if row.get("kind") != "aist"]

    def cards(items: Sequence[Mapping[str, Any]]) -> str:
        bits: list[str] = []
        for item in items:
            slug = str(item.get("slug") or "").strip()
            title = str(item.get("title") or slug)
            if not slug or not title:
                continue
            href = f"{href_prefix}{conference_path(slug)}"
            when = str(item.get("when") or "")
            series = str(item.get("series") or "")
            bits.append('<article class="conf-card">')
            if series:
                bits.append(f'<p class="leader-role">{escape(series)}</p>')
            bits.append(f'<h3><a href="{escape(href, quote=True)}">{escape(title)}</a></h3>')
            if when:
                bits.append(f"<p>{escape(when)}</p>")
            bits.append("</article>")
        if not bits:
            return ""
        return f'<div class="conf-grid">{"".join(bits)}</div>'

    chunks: list[str] = []
    aist_cards = cards(aist)
    if aist_cards:
        chunks.append("<h2>Архив AIST</h2>")
        chunks.append(aist_cards)
    other_cards = cards(other)
    if other_cards:
        chunks.append("<h2>Другие конференции Института</h2>")
        chunks.append(other_cards)
    return "".join(chunks)

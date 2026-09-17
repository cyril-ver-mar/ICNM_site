"""Cooperation map: partner pins + Natural Earth country outline.

WordPress mirrors this HTML shape in ichnm_world_map_html() (ticket 12).
Not a watermarked atlas; not OSM/Google raster.
"""

from __future__ import annotations

import re
from html import escape
from pathlib import Path
from typing import Any, Mapping, Sequence

_HERE = Path(__file__).resolve().parent
WORLD_SVG_PATH = _HERE.parents[1] / "assets" / "maps" / "world-countries.svg"

_REQUIRED = ("slug", "title", "x", "y", "place", "note")


def partner_pins(copy: Mapping[str, Any]) -> list[dict[str, Any]]:
    """Normalize migrated_copy partners into map pin records."""
    rows = copy.get("partners") or []
    out: list[dict[str, Any]] = []
    for row in rows:
        if not isinstance(row, Mapping):
            continue
        slug = str(row.get("slug") or row.get("title") or "").strip()
        title = str(row.get("title") or "").strip()
        if not slug or not title:
            continue
        try:
            x = float(row.get("x", 50))
            y = float(row.get("y", 50))
        except (TypeError, ValueError):
            continue
        out.append(
            {
                "slug": slug,
                "title": title,
                "place": str(row.get("place") or "").strip(),
                "note": str(row.get("note") or "").strip(),
                "x": x,
                "y": y,
            }
        )
    return out


def world_outline_markup(path: Path | None = None) -> str:
    """Inline Natural Earth SVG (strip XML declaration) for theme-coloured fills."""
    target = path or WORLD_SVG_PATH
    raw = target.read_text(encoding="utf-8")
    return re.sub(r"<\?xml[^?]*\?>", "", raw).strip()


def _photo_slot(label: str) -> str:
    return (
        f'<div class="photo-slot" role="img" aria-label="{escape("Место для фото: " + label)}">'
        '<span aria-hidden="true">На</span>'
        "<p>Фото появится после передачи файла</p>"
        "</div>"
    )


def world_map_html(
    pins: Sequence[Mapping[str, Any]],
    *,
    outline: str,
    aria_label: str = "Карта научного сотрудничества",
) -> str:
    """WP-shaped figure: inline outline, pins, hover cards with photo slot."""
    if not pins or not outline.strip():
        return ""
    parts = [
        f'<figure class="ichnm-world-map" aria-label="{escape(aria_label)}">',
        outline,
    ]
    for pin in pins:
        slug = escape(str(pin["slug"]))
        title = escape(str(pin["title"]))
        place = escape(str(pin.get("place") or ""))
        note = escape(str(pin.get("note") or ""))
        x = float(pin["x"])
        y = float(pin["y"])
        # Upper-half pins open the card below so it stays in frame (ticket 21).
        pop_attr = ' data-pop="below"' if y < 42.0 else ""
        parts.append(
            f'<div class="ichnm-map-hotspot"{pop_attr} style="left:{x}%;top:{y}%">'
            f'<button type="button" class="ichnm-map-pin" aria-describedby="ichnm-pop-{slug}">'
            f'<span class="screen-reader-text">{title}</span></button>'
            f'<div class="ichnm-map-pop" id="ichnm-pop-{slug}">'
            f"{_photo_slot(str(pin['title']))}"
        )
        if place:
            parts.append(f'<p class="ichnm-map-place">{place}</p>')
        parts.append(f"<h3>{title}</h3>")
        if note:
            parts.append(f"<p>{note}</p>")
        parts.append("</div></div>")
    parts.append("</figure>")
    return "".join(parts)

"""Lab accent colour tokens for structure / developments / facilities tiles."""

from __future__ import annotations

# One map for structure + catalogue chrome (ticket 57).
# Values are CSS colour strings; day/night contrast checked via theme tokens.
LAB_ACCENT_BY_SLUG: dict[str, str] = {
    "nano": "#0d9488",  # teal
    "films": "#2563eb",  # blue
    "lcd": "#ca8a04",  # amber
    "composites": "#16a34a",  # green
    "woodchem": "#c2410c",  # rust
}

THIN_FILM_DEPT_TITLE = "Отдел физико-химии тонкоплёночных материалов"
# Three film/surface labs under the thin-film department (ticket 55).
THIN_FILM_CHILD_SLUGS: tuple[str, ...] = ("nano", "films", "lcd")


def lab_accent_token(lab_slug: str) -> str:
    """Return accent colour for a lab slug, or empty if unknown."""
    return LAB_ACCENT_BY_SLUG.get((lab_slug or "").strip(), "")


def thin_film_child_slugs() -> tuple[str, ...]:
    return THIN_FILM_CHILD_SLUGS

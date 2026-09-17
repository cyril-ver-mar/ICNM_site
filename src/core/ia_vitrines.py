"""Honest contour for education / documents / vacancies vitrines.

Pure-Python contract used by pytest and mirrored by WordPress content-sync.
PDF labels are slots only — never preview-filled fish downloads.
"""

from __future__ import annotations

from html import escape
from typing import Any, Mapping

EDUCATION_HUB_ID = "education"
DOCUMENTS_HUB_ID = "documents"
VACANCIES_ID = "vacancies"

EDUCATION_CHILD_IDS: tuple[str, ...] = (
    "aspirantura",
    "doctorate",
    "defense-council",
    "internships",
    "courses",
)

DOCUMENTS_CHILD_IDS: tuple[str, ...] = (
    "charter",
    "anti-corruption",
    "e-appeals",
)

EDUCATION_DOCUMENTS_VACANCIES_IDS: tuple[str, ...] = (
    EDUCATION_HUB_ID,
    *EDUCATION_CHILD_IDS,
    DOCUMENTS_HUB_ID,
    *DOCUMENTS_CHILD_IDS,
    VACANCIES_ID,
)

# Honest PDF reception slots (labels only; files arrive in the colleague packet).
PAGE_FILE_SLOTS: dict[str, tuple[str, ...]] = {
    "charter": ("Устав ГНУ «ИХНМ НАН Беларуси».pdf",),
    "anti-corruption": (
        "Положение о противодействии коррупции.pdf",
        "План мероприятий.pdf",
    ),
    "aspirantura": (
        "Правила приёма в аспирантуру.pdf",
        "Перечень специальностей.pdf",
    ),
    "doctorate": ("Правила приёма в докторантуру.pdf",),
    "defense-council": (
        "Состав совета по защитам.pdf",
        "Специальности.pdf",
    ),
    "internships": ("Положение о стажировках.pdf",),
    "courses": ("Программы курсов.pdf",),
}

_FISH_MARKERS = (
    "Макет:",
    "макетн",
    "preview-filled",
    "dummy text",
    "рыба",
)


def page_has_honest_body(block: Mapping[str, Any] | None) -> bool:
    """True when the page has real paragraphs and/or an explicit empty-slot marker."""
    if not isinstance(block, Mapping):
        return False
    paragraphs = [str(p).strip() for p in (block.get("paragraphs") or []) if str(p).strip()]
    empty_slot = str(block.get("empty_slot") or "").strip()
    return bool(paragraphs) or bool(empty_slot)


def vitrine_body_is_fish_free(block: Mapping[str, Any] | None) -> bool:
    """Reject preview-filled fish markers in paragraphs / list / empty_slot."""
    if not isinstance(block, Mapping):
        return True
    chunks: list[str] = []
    for key in ("paragraphs", "list"):
        for item in block.get(key) or []:
            chunks.append(str(item))
    empty_slot = block.get("empty_slot")
    if empty_slot:
        chunks.append(str(empty_slot))
    blob = "\n".join(chunks).lower()
    for marker in _FISH_MARKERS:
        if marker.lower() in blob:
            return False
    return True


def honest_file_slots_html(labels: list[str] | tuple[str, ...]) -> str:
    """Empty PDF shelf HTML — never `is-filled`, never mock downloads."""
    items: list[str] = []
    for label in labels:
        text = str(label).strip()
        if not text:
            continue
        items.append(
            f'<li class="file-slot"><span>{escape(text)}</span>'
            f"<small>Файл не загружен</small></li>"
        )
    if not items:
        return ""
    return (
        '<ul class="file-shelf" aria-label="Слоты для документов">'
        + "".join(items)
        + "</ul>"
    )


def file_slots_for_page(page_id: str, block: Mapping[str, Any] | None = None) -> tuple[str, ...]:
    """Prefer copy-declared slots; fall back to the locked PAGE_FILE_SLOTS map."""
    if isinstance(block, Mapping):
        declared = tuple(
            str(label).strip()
            for label in (block.get("file_slots") or [])
            if str(label).strip()
        )
        if declared:
            return declared
    return PAGE_FILE_SLOTS.get(page_id, ())

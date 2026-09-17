"""Colleague packet ingest: resolve incoming slots into vitrine attachments.

Pure-Python seam mirrored by WordPress ``content-sync`` attach helpers.
Empty / zero-byte files are never treated as content.
"""

from __future__ import annotations

import csv
import json
import re
from html import escape
from pathlib import Path
from typing import Any, Callable, Mapping

from src.core.publications import normalize_publication

# Minimum bytes for opaque binaries (PDF/AI/EPS) to count as real packet content.
_MIN_BINARY_BYTES = 64
_TEXTISH_SUFFIXES = {
    ".csv",
    ".tsv",
    ".txt",
    ".json",
    ".svg",
    ".html",
    ".htm",
    ".md",
}

# Page id → accepted basenames (stem, lowercased, without extension).
DOCUMENT_PAGE_ALIASES: dict[str, tuple[str, ...]] = {
    "charter": ("ustav", "charter", "устав"),
    "anti-corruption": (
        "anticorruption",
        "anti-corruption",
        "anti_corruption",
        "противодействие-коррупции",
        "polozhenie-korrupcii",
    ),
    "e-appeals": (
        "e-appeals",
        "e_appeals",
        "electronic-appeals",
        "электронные-обращения",
        "obrashcheniya",
    ),
}

DOCUMENT_PAGE_IDS: tuple[str, ...] = tuple(DOCUMENT_PAGE_ALIASES.keys())

_SOCIAL_LABELS: dict[str, str] = {
    "facebook": "Facebook",
    "vk": "ВКонтакте",
    "telegram": "Telegram",
    "instagram": "Instagram",
    "youtube": "YouTube",
}

_PUB_TABLE_EXTENSIONS = {".csv", ".tsv", ".json", ".xlsx", ".ods"}
_ROSTER_EXTENSIONS = {".csv", ".tsv", ".xlsx", ".ods", ".docx", ".json"}
_BRAND_EXTENSIONS = {".svg", ".pdf", ".ai", ".eps"}

_EVIDENCE_NAME_RE = re.compile(
    r"^(ichnm-lab-\d+-t4\.html|ichnm-publications-normalized\.json|readme\.md)$",
    re.IGNORECASE,
)


def is_nonempty_content_file(path: Path | None) -> bool:
    """True when path exists, is a regular file, and is large enough to be real content."""
    if path is None or not path.is_file():
        return False
    name = path.name
    if name.startswith(".") or name.lower() == ".gitkeep":
        return False
    try:
        size = path.stat().st_size
    except OSError:
        return False
    if size <= 0:
        return False
    suffix = path.suffix.lower()
    if suffix in _TEXTISH_SUFFIXES:
        try:
            text = path.read_text(encoding="utf-8", errors="ignore")
        except OSError:
            return False
        return bool(text.strip())
    return size >= _MIN_BINARY_BYTES


def _stem_key(path: Path) -> str:
    return path.stem.strip().lower().replace(" ", "-").replace("_", "-")


def resolve_document_attachments(documents_dir: Path) -> dict[str, Path]:
    """Map document page ids to the first matching non-empty file in ``documents_dir``."""
    if not documents_dir.is_dir():
        return {}
    files = sorted(
        p for p in documents_dir.iterdir() if is_nonempty_content_file(p)
    )
    out: dict[str, Path] = {}
    for page_id, aliases in DOCUMENT_PAGE_ALIASES.items():
        alias_keys = {
            a.strip().lower().replace("_", "-").replace(" ", "-") for a in aliases
        }
        for path in files:
            stem = _stem_key(path)
            if stem in alias_keys or any(
                stem.startswith(alias + "-") for alias in alias_keys
            ):
                out[page_id] = path
                break
    return out


def attach_status_for_page(page_id: str, attachments: Mapping[str, Path]) -> str:
    """``attached`` when a non-empty file is bound; otherwise honest ``stub``."""
    path = attachments.get(page_id)
    return "attached" if is_nonempty_content_file(path) else "stub"


def document_shelf_html(
    page_id: str,
    *,
    labels: list[str] | tuple[str, ...],
    attachments: Mapping[str, Path],
    href_for: Callable[[Path], str] | None = None,
) -> str:
    """File shelf HTML: filled download when attached, honest stub otherwise."""
    path = attachments.get(page_id)
    filled = is_nonempty_content_file(path)
    items: list[str] = []

    if filled and path is not None:
        href = href_for(path) if href_for else path.name
        label = (labels[0] if labels else path.name).strip() or path.name
        items.append(
            f'<li class="file-slot is-filled">'
            f'<a href="{escape(href, quote=True)}">{escape(label)}</a>'
            f"<small>Скачать</small></li>"
        )
        # Extra declared slots without a matching file stay honest stubs.
        for label in list(labels)[1:]:
            text = str(label).strip()
            if text:
                items.append(
                    f'<li class="file-slot"><span>{escape(text)}</span>'
                    f"<small>Файл не загружен</small></li>"
                )
    else:
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


def parse_icnm_social_urls(path: Path) -> list[dict[str, str]]:
    """Parse ``network URL`` lines; skip blanks, comments, and networks without URL."""
    if not path.is_file() or path.stat().st_size == 0:
        return []
    rows: list[dict[str, str]] = []
    seen: set[str] = set()
    for raw in path.read_text(encoding="utf-8").splitlines():
        line = raw.strip()
        if not line or line.startswith("#"):
            continue
        parts = line.split(None, 1)
        if len(parts) < 2:
            continue
        network = parts[0].strip().lower()
        href = parts[1].strip()
        if not href or not href.lower().startswith(("http://", "https://")):
            continue
        if network in seen:
            continue
        seen.add(network)
        rows.append(
            {
                "network": network,
                "label": _SOCIAL_LABELS.get(network, network.title()),
                "href": href,
            }
        )
    return rows


def _dedupe_pub_key(row: Mapping[str, Any]) -> str:
    cite = str(row.get("cite") or "").strip().lower()
    doi = str(row.get("doi") or "").strip().lower()
    lab = str(row.get("laboratory") or row.get("lab_id") or "").strip().lower()
    return f"{lab}|{cite}|{doi}"


def _rows_from_csv(path: Path) -> list[dict[str, Any]]:
    text = path.read_text(encoding="utf-8-sig")
    lines = [line for line in text.splitlines() if line.strip()]
    if not lines:
        return []
    dialect = csv.excel_tab if "\t" in lines[0] else csv.excel
    reader = csv.DictReader(lines, dialect=dialect)
    out: list[dict[str, Any]] = []
    for raw in reader:
        if not raw:
            continue
        cleaned = {str(k).strip(): (v.strip() if isinstance(v, str) else v) for k, v in raw.items() if k}
        if not any(str(v).strip() for v in cleaned.values() if v is not None):
            continue
        try:
            out.append(normalize_publication(cleaned))
        except ValueError:
            continue
    return out


def _rows_from_json(path: Path) -> list[dict[str, Any]]:
    raw = json.loads(path.read_text(encoding="utf-8"))
    items: list[Any]
    if isinstance(raw, list):
        items = raw
    elif isinstance(raw, dict):
        items = list(raw.get("items") or raw.get("publications") or raw.get("publications_items") or [])
    else:
        return []
    out: list[dict[str, Any]] = []
    for item in items:
        if not isinstance(item, Mapping):
            continue
        try:
            out.append(normalize_publication(item))
        except ValueError:
            continue
    return out


def load_incoming_publication_rows(publications_dir: Path) -> list[dict[str, Any]]:
    """Load colleague table rows (csv/json); skip evidence HTML and empty placeholders.

    Idempotent: duplicate cite+doi+lab collapse to one normalised row.
    xlsx/ods are detected but require a separate converter session — ignored here
    with zero rows (documented path) unless a csv/json sibling exists.
    """
    if not publications_dir.is_dir():
        return []
    collected: list[dict[str, Any]] = []
    seen: set[str] = set()
    for path in sorted(publications_dir.iterdir()):
        if not path.is_file() or not is_nonempty_content_file(path):
            continue
        if _EVIDENCE_NAME_RE.match(path.name):
            continue
        suffix = path.suffix.lower()
        if suffix not in _PUB_TABLE_EXTENSIONS:
            continue
        if suffix in {".xlsx", ".ods"}:
            # Documented: convert to csv/json for automated import.
            continue
        rows = _rows_from_csv(path) if suffix in {".csv", ".tsv"} else _rows_from_json(path)
        for row in rows:
            key = _dedupe_pub_key(row)
            if key in seen:
                continue
            seen.add(key)
            collected.append(row)
    return collected


def resolve_roster_files(rosters_dir: Path) -> list[Path]:
    """Non-empty roster tables ready for people / lab-tab merge."""
    if not rosters_dir.is_dir():
        return []
    return sorted(
        p
        for p in rosters_dir.iterdir()
        if p.is_file()
        and is_nonempty_content_file(p)
        and p.suffix.lower() in _ROSTER_EXTENSIONS
    )


def resolve_nas_brand_vector(brand_dir: Path) -> Path | None:
    """First non-empty NAS emblem vector in ``brand/`` (svg preferred)."""
    if not brand_dir.is_dir():
        return None
    candidates = [
        p
        for p in brand_dir.iterdir()
        if p.is_file()
        and is_nonempty_content_file(p)
        and p.suffix.lower() in _BRAND_EXTENSIONS
        and "ichnm" not in p.stem.lower()  # do not treat ICNM mark drops as NAS
    ]
    if not candidates:
        return None
    preferred = [p for p in candidates if "nas" in p.stem.lower() or "nan" in p.stem.lower()]
    pool = preferred or candidates
    pool.sort(key=lambda p: (0 if p.suffix.lower() == ".svg" else 1, p.name.lower()))
    return pool[0]


def default_incoming_root(repo_root: Path | None = None) -> Path:
    root = repo_root or Path(__file__).resolve().parents[2]
    return root / "assets" / "incoming"

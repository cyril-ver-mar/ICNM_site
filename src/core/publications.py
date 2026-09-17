"""Publication catalogue seam: normalize records and parse raw list fragments.

Locked shape (DECISIONS): GOST cite *or* authors / title / journal / year,
optional DOI as absolute ``https://doi.org/…``, laboratory / lab_id.
No person hyperlinks in the record.
"""

from __future__ import annotations

import re
from html import unescape
from typing import Any, Mapping

_DOI_PREFIXES = (
    "https://doi.org/",
    "http://doi.org/",
    "https://dx.doi.org/",
    "http://dx.doi.org/",
    "doi:",
)

_TAG_RE = re.compile(r"<[^>]+>")
_LI_RE = re.compile(r"<li\b[^>]*>(.*?)</li>", re.IGNORECASE | re.DOTALL)
_HREF_DOI_RE = re.compile(
    r'href=["\']((?:https?://(?:dx\.)?doi\.org/|doi:)[^"\']+)["\']',
    re.IGNORECASE,
)
_INLINE_DOI_RE = re.compile(
    r"(?:DOI\s*[:：]\s*|doi:\s*)(10\.\d{4,9}/\S+)",
    re.IGNORECASE,
)
_YEAR_RE = re.compile(r"\b(19|20)\d{2}\b")

# Keys that must never appear on a normalised catalogue row.
_PERSON_LINK_KEYS = frozenset(
    {
        "person_id",
        "author_ids",
        "people",
        "person_href",
        "authors_href",
        "staff_id",
    }
)


def normalize_doi(raw: str | None) -> str:
    """Return absolute ``https://doi.org/…`` or ``""`` when blank."""
    if raw is None:
        return ""
    value = str(raw).strip()
    if not value:
        return ""
    lower = value.lower()
    for prefix in _DOI_PREFIXES:
        if lower.startswith(prefix):
            ident = value[len(prefix) :].strip()
            return f"https://doi.org/{ident}" if ident else ""
    if value.lower().startswith("http://") or value.lower().startswith("https://"):
        # Already an absolute URL that is not a known DOI host — keep as-is.
        return value
    return f"https://doi.org/{value}"


def _build_cite_from_parts(raw: Mapping[str, Any]) -> str:
    bits = [
        str(raw.get("authors") or "").strip(),
        str(raw.get("title") or "").strip(),
        str(raw.get("journal") or "").strip(),
    ]
    year = raw.get("year")
    if year not in (None, ""):
        bits.append(str(year).strip())
    cleaned = [bit.rstrip(" .") for bit in bits if bit]
    return ". ".join(cleaned)


def _coerce_year(value: Any) -> int | None:
    if value in (None, ""):
        return None
    try:
        return int(value)
    except (TypeError, ValueError):
        text = str(value)
        match = _YEAR_RE.search(text)
        if match:
            return int(match.group(0))
        return None


def normalize_publication(raw: Mapping[str, Any]) -> dict[str, Any]:
    """Normalise one publication dict to the locked catalogue shape."""
    cite = str(raw.get("cite") or "").strip()
    if not cite:
        cite = _build_cite_from_parts(raw)
    if not cite:
        raise ValueError("publication requires cite or authors/title/journal/year")

    row: dict[str, Any] = {"cite": cite}

    doi = normalize_doi(raw.get("doi") if "doi" in raw else None)
    if doi:
        row["doi"] = doi

    lab_id = str(raw.get("lab_id") or "").strip()
    if lab_id:
        row["lab_id"] = lab_id

    laboratory = str(raw.get("laboratory") or raw.get("lab_title") or "").strip()
    if laboratory:
        row["laboratory"] = laboratory

    year = _coerce_year(raw.get("year"))
    if year is None and cite:
        year = _coerce_year(cite)
    if year is not None:
        row["year"] = year

    # Keep structured parts only when cite was synthesised from them.
    if not str(raw.get("cite") or "").strip():
        for key in ("authors", "title", "journal"):
            value = str(raw.get(key) or "").strip()
            if value:
                row[key] = value

    assert _PERSON_LINK_KEYS.isdisjoint(row.keys())
    return row


def _strip_tags(html: str) -> str:
    text = _TAG_RE.sub(" ", html)
    text = unescape(text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


def _extract_doi_from_item(html: str) -> str:
    href_match = _HREF_DOI_RE.search(html)
    if href_match:
        return normalize_doi(href_match.group(1))
    inline = _INLINE_DOI_RE.search(_strip_tags(html))
    if inline:
        return normalize_doi(inline.group(1).rstrip(".,);]"))
    return ""


def _cite_without_doi_noise(text: str) -> str:
    cleaned = _INLINE_DOI_RE.sub("", text)
    cleaned = re.sub(r"\bDOI\s*[:：]\s*", "", cleaned, flags=re.IGNORECASE)
    cleaned = re.sub(r"\s+", " ", cleaned).strip(" .;")
    return cleaned


def parse_publication_fragment(
    html_or_text: str,
    *,
    laboratory: str = "",
    lab_id: str = "",
) -> list[dict[str, Any]]:
    """Parse a raw list fragment (``<li>`` items or plain lines) into records."""
    fragment = html_or_text or ""
    items = _LI_RE.findall(fragment)
    if not items:
        items = [
            line.strip()
            for line in fragment.splitlines()
            if line.strip() and not line.strip().startswith("<!--")
        ]
        # Drop bare HTML wrappers if someone passed a non-list blob.
        items = [line for line in items if not re.match(r"</?(ul|ol|div)\b", line, re.I)]

    rows: list[dict[str, Any]] = []
    for item in items:
        doi = _extract_doi_from_item(item) if "<" in item else ""
        text = _strip_tags(item) if "<" in item else item.strip()
        if not doi:
            doi = _extract_doi_from_item(text)
        cite = _cite_without_doi_noise(text)
        if not cite:
            continue
        payload: dict[str, Any] = {"cite": cite}
        if doi:
            payload["doi"] = doi
        if lab_id:
            payload["lab_id"] = lab_id
        if laboratory:
            payload["laboratory"] = laboratory
        year = _coerce_year(cite)
        if year is not None:
            payload["year"] = year
        rows.append(normalize_publication(payload))
    return rows

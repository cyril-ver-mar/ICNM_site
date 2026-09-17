"""Colleague packet ingest seam (ticket 33).

Incoming slots → document attach HTML, publications rows, social URLs,
roster / brand presence — without committing empty PDFs as content.
"""

from __future__ import annotations

from pathlib import Path

from src.core.colleague_packet import (
    DOCUMENT_PAGE_ALIASES,
    attach_status_for_page,
    document_shelf_html,
    is_nonempty_content_file,
    load_incoming_publication_rows,
    parse_icnm_social_urls,
    resolve_document_attachments,
    resolve_nas_brand_vector,
    resolve_roster_files,
)
from src.core.publications import normalize_publication


def _write(path: Path, data: bytes | str) -> Path:
    path.parent.mkdir(parents=True, exist_ok=True)
    if isinstance(data, bytes):
        path.write_bytes(data)
    else:
        path.write_text(data, encoding="utf-8")
    return path


def test_empty_and_gitkeep_are_not_content(tmp_path: Path):
    empty = _write(tmp_path / "ustav.pdf", b"")
    keep = _write(tmp_path / ".gitkeep", "")
    tiny = _write(tmp_path / "stub.pdf", b"%PDF")  # too small / not real packet
    real = _write(tmp_path / "charter.pdf", b"%PDF-1.4\n" + b"x" * 200)

    assert not is_nonempty_content_file(empty)
    assert not is_nonempty_content_file(keep)
    assert not is_nonempty_content_file(tiny)
    assert is_nonempty_content_file(real)


def test_resolve_document_attachments_maps_aliases(tmp_path: Path):
    docs = tmp_path / "documents"
    _write(docs / "ustav.pdf", b"%PDF-1.4\n" + b"a" * 200)
    _write(docs / "anticorruption.pdf", b"%PDF-1.4\n" + b"b" * 200)
    _write(docs / "e-appeals.pdf", b"%PDF-1.4\n" + b"c" * 200)
    _write(docs / "empty.pdf", b"")

    attached = resolve_document_attachments(docs)
    assert set(attached) == {"charter", "anti-corruption", "e-appeals"}
    assert attached["charter"].name == "ustav.pdf"
    assert "empty" not in attached
    assert DOCUMENT_PAGE_ALIASES["charter"]


def test_missing_document_keeps_honest_stub_shelf():
    html = document_shelf_html(
        "charter",
        labels=["Устав ГНУ «ИХНМ НАН Беларуси».pdf"],
        attachments={},
    )
    assert "file-shelf" in html
    assert "file-slot" in html
    assert "is-filled" not in html
    assert "Файл не загружен" in html
    assert "ichnm-empty-slot" not in html  # shelf stub, not empty-slot paragraph


def test_present_document_fills_shelf_not_empty_slot(tmp_path: Path):
    pdf = _write(tmp_path / "charter.pdf", b"%PDF-1.4\n" + b"d" * 200)
    html = document_shelf_html(
        "charter",
        labels=["Устав ГНУ «ИХНМ НАН Беларуси».pdf"],
        attachments={"charter": pdf},
        href_for=lambda p: f"/media/incoming/documents/{p.name}",
    )
    assert "is-filled" in html
    assert "Файл не загружен" not in html
    assert "href=" in html
    assert "charter.pdf" in html
    assert attach_status_for_page("charter", {"charter": pdf}) == "attached"
    assert attach_status_for_page("charter", {}) == "stub"


def test_parse_icnm_social_skips_empty_and_comment_lines(tmp_path: Path):
    text = """
# institute profiles
facebook https://facebook.com/ichnm
vk
telegram https://t.me/ichnm

instagram
youtube https://youtube.com/@ichnm
"""
    path = _write(tmp_path / "urls.txt", text)
    rows = parse_icnm_social_urls(path)
    assert [r["network"] for r in rows] == ["facebook", "telegram", "youtube"]
    assert all(r["href"].startswith("http") for r in rows)
    assert parse_icnm_social_urls(tmp_path / "missing.txt") == []


def test_load_incoming_publication_rows_csv_idempotent_keys(tmp_path: Path):
    csv = _write(
        tmp_path / "pubs.csv",
        "cite,laboratory,doi,year\n"
        '"Иванов И. И. Пример // Журнал. 2020.",Лаборатория наноструктур,10.1234/abc,2020\n'
        '"Иванов И. И. Пример // Журнал. 2020.",Лаборатория наноструктур,10.1234/abc,2020\n'
        ",,,\n",
    )
    rows = load_incoming_publication_rows(tmp_path)
    assert len(rows) == 1
    norm = normalize_publication(rows[0])
    assert norm["doi"] == "https://doi.org/10.1234/abc"
    assert "person_id" not in norm
    assert norm["laboratory"] == "Лаборатория наноструктур"
    # Second call same input → same shape (idempotent source).
    assert load_incoming_publication_rows(tmp_path) == rows
    assert csv.name == "pubs.csv"


def test_load_incoming_skips_evidence_html_and_empty_tables(tmp_path: Path):
    _write(tmp_path / "ichnm-lab-13-t4.html", "<ul><li>fish</li></ul>")
    _write(tmp_path / "ichnm-publications-normalized.json", "[]")
    _write(tmp_path / "README.md", "# slot")
    assert load_incoming_publication_rows(tmp_path) == []


def test_roster_and_nas_brand_resolution(tmp_path: Path):
    rosters = tmp_path / "rosters"
    brand = tmp_path / "brand"
    _write(rosters / "roster-nano.csv", "id,name\nnano-1,Иванов\n")
    _write(rosters / ".gitkeep", "")
    _write(
        brand / "nas-emblem.svg",
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
        '<circle cx="50" cy="50" r="40"/></svg>\n',
    )
    _write(brand / "empty.ai", b"")

    found = resolve_roster_files(rosters)
    assert len(found) == 1
    assert found[0].name == "roster-nano.csv"
    assert resolve_nas_brand_vector(brand).name == "nas-emblem.svg"
    assert resolve_nas_brand_vector(tmp_path / "missing") is None


def test_anti_corruption_accepts_multiple_named_pdfs(tmp_path: Path):
    docs = tmp_path / "documents"
    _write(docs / "anticorruption.pdf", b"%PDF-1.4\n" + b"e" * 200)
    attached = resolve_document_attachments(docs)
    assert "anti-corruption" in attached

# 01 — Publication seam + pytest

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-remaining-ia-and-publications.md

## What to build

Чистый шов `src/core`: нормализация записи публикации (cite или authors/title/journal/year, optional DOI → `https://doi.org/…`, laboratory / lab_id). Фикстура «сырой фрагмент → записи». Pytest без Docker. Без person-hyperlinks в shape.

## Acceptance

- [x] Pytest зелёный на нормализации и парсере/фикстуре
- [x] DOI нормализуется к абсолютному URL; пустой DOI опускается
- [x] Документирован прогон одной командой (README или pytest.ini уже ок)

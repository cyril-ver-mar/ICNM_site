# 01 — Catalogue detail seam + pytest

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-preview-parity-chrome.md

## What to build

Чистый шов `src/core`: контракт detail-страницы каталога (science / developments / facilities) — title, lead, lab/staff links when known, spec|product, честный photo-slot; без рыбы preview-filled. Pytest без Docker.

## Acceptance

- [x] Pytest на обязательные поля/ссылки detail
- [x] Фикстура хотя бы одного slug из migrated_copy
- [x] Нет person-hyperlinks там, где DECISIONS запрещают (публикации — не этот тикет)

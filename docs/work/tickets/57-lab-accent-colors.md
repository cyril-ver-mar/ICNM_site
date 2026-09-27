# 57 — Lab accent colors across catalogues

Status: done
Blocked by: 55
Spec: docs/work/specs/2026-09-21-acceptance-chrome-ia-person.md

## What to build

Каждой лаборатории — accent color; тонкая accent-линия над плиткой на Структуре и на плитках той же лаборатории в Разработках и Матбазе. Цвета контрастны в day и night. Одна карта lab_id → token. Обновить DECISIONS (новое).

## Acceptance

- [x] Карта lab_id → accent token (одна для structure / developments / facilities)
- [x] Accent-линия на structure lab tiles
- [x] Та же линия на developments и facilities tiles той же lab
- [x] Контраст day + night (checklist / smoke)
- [x] Unit: known lab → same token
- [x] DECISIONS: lab accent correlation across catalogues

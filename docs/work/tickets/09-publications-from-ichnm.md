# 02 — Стартовый каталог публикаций с ichnm.by

Status: done  
Blocked by: 01  
Spec: docs/work/specs/2026-09-17-remaining-ia-and-publications.md

## What to build

Вытащить со старого ichnm.by то, что ещё открывается; привести к seam из 01; положить в migrated copy / сид; CPT `publication` + хаб `/publications/` показывают стартовый список с DOI и лабораторией, без ссылок на людей. Дыры честные. График годов остаётся mock.

## Acceptance

- [x] В copy есть стартовый список (не preview-filled рыба)
- [x] Хаб и CPT отражают список после seed
- [x] Карточки: cite + optional DOI + lab; нет person links

## Notes (2026-09-17)

- Scraped lab pack tabs «Публикации» on ichnm.by (labs 13/75/79/191/195). About «Публикации и монографии» = aggregates only.
- `publications_items`: 208 real cites (39 with DOI). Mock `10.0000/ichnm.mock.*` removed from lab packs; sparse real cites kept on packs.
- Evidence: `assets/incoming/publications/`. Import: `ichnm_import_publications()` reads top-level items + non-mock lab rows. Seed version bumped to 26.

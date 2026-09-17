# 33 — Colleague packet ingest

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-cutover-polish-remaining.md

## What to build

Конвейер из слотов `assets/incoming/` (и чеклиста colleague-packet) в витрины: PDF документов → страницы Устав / Антикоррупция / Эл. обращения; таблица публикаций → CPT; ростеры → people/lab tabs по мере данных; вектор НАН → brand; URL соцсетей ИХНМ → footer только если не пустые. Пустые файлы не коммитить как контент.

## Acceptance

- [x] Документы: файл в incoming → страница не empty-slot (seam attach)
- [x] Публикации: импорт таблицы идемпотентен / документирован
- [x] Соцсети ИХНМ не рисуют пустые иконки
- [x] README/чеклист обновлён статусами или шагами «после кладём файл»

## Notes

- Seam: `src/core/colleague_packet.py` + `tests/test_colleague_packet.py`
- WP: `includes/colleague-packet.php` → `ichnm_apply_colleague_packet()` из `ichnm_sync_content` (в т.ч. при уже актуальном seed)
- Seed version **32**
- Ростеры: детект + документированный путь в migrated_copy (не silent xlsx→CPT)

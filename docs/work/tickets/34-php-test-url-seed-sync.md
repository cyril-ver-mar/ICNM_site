# 34 — PHP test URL + seed sync

Status: blocked  
Blocked by: PHP-тариф / hosting credentials (Active.by or Hoster.by panel) — see DECISIONS operational gaps  
Spec: docs/work/specs/2026-09-17-cutover-polish-remaining.md

## What to build

Выкладка на PHP test URL (Active.by / Hoster.by): backup из Docker → деплой overlay → при необходимости `ichnm_sync_content(true)` (seed **32**, после тикета 33; earlier notes said 31) → `wp-smoke` + ручной row V (visual vs preview). DNS `ichnm.by` не трогать. Тикет 33 подключать по наличию файлов коллег, не блокировать test URL пустыми слотами.

## Acceptance

- [x] `./scripts/wp-backup.sh` даёт архив; README в exports понятен
- [ ] Test URL отвечает 200 на обязательные пути smoke *(blocked: нет PHP credentials / test URL)*
- [x] Seed sync выполнен или зафиксирован как «уже = 32» (local Docker option = const)
- [x] Smoke row V отмечен или заведены замечания *(HTTP local OK; row V remains manual gate — notes in smoke-checklist)*
- [x] Handoff-заметка обновлена *(секция blocked / waiting on; без выдуманного URL, без секретов)*

## Notes (2026-09-17)

- Backup: `exports/wp-backup-20260917-2108/` (prefer latest; README names seed **32** + restore/smoke steps).
- Local smoke: all required paths 200 on `http://localhost:8080`.
- Seed: `ICHNM_CONTENT_SEED_VERSION` = **32**; WP option = **32** (no force sync needed locally).
- Remote deploy impossible without panel access — handoff lists exact human next steps.
- No commit/push; DNS / Forever Java / MX / `aist.ichnm.by` untouched.

# 06 — Smoke-приёмка, бэкап и handoff на PHP-хостинг

Status: done  
Blocked by: 02, 03, 04, 05  
Spec: docs/work/specs/2026-09-17-wordpress-cutover-readiness.md

## What to build

Чеклист ручного/скриптового smoke для test URL; актуальный `wp-backup` → exports; обновлённый hosting handoff (тариф, DNS cutover, что не трогать на старом Java). Без переключения домена в этом тикете.

## Acceptance

- [x] Чеклист: главная, lab pack, admin unit, person, новости, поиск, sitemap, feedback, EN shell
- [x] Бэкап-скрипт документирован и отрабатывает локально
- [x] Handoff-док обновлён под текущий контур (Polylang, seed, volumes)

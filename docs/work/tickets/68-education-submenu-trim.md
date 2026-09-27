# 68 — Education submenu: drop courses / ПК

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-21-acceptance-vitrine-bvi-maps.md

## What to build

Убрать из меню и витрин «курсы» и «повышения квалификации» (и redirects при наличии). Дети «Научно-ориентированного образования»: **аспирантура, докторантура, совет по защитам, стажировки** — nested submenu. Стажировки остаются (пользователь не ответил иначе; DECISIONS держит). Обновить `site_model.json`, `ia_vitrines.py`, content-sync, DECISIONS.

Научно-исследовательская работа студентов и трудоустройство выпускников — **не** здесь; см. тикет 80.

## Acceptance

- [x] Education children ⊆ {aspirantura, doctorate, defense-council, internships}
- [x] Нет courses / advanced-training в меню и хабе
- [x] Nested submenu под родителем образования
- [x] DECISIONS / CONTEXT обновлены
- [x] pytest: education tree seam

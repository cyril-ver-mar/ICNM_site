# 69 — BVI: icon, contrast, restore without reload

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-21-acceptance-vitrine-bvi-maps.md

## What to build

Кнопка BVI: убрать видимый текст «BVI»; увеличить иконку очков/глаза; сохранить единый размер рамки tool-кнопок. Режим BVI: токены без white-on-white / low contrast (приглушить decorative chrome). Toggle on/off восстанавливает UI (шапка tools, chrome) **без reload**; persistence не ломает соседние controls.

## Acceptance

- [x] Кнопка icon-only (нет подписи «BVI»), aria-label сохранён
- [x] Иконка крупнее в той же рамке tool
- [x] High-contrast BVI читаем (нет white-on-white на tools/chrome)
- [x] On → off без F5: tools и chrome на месте
- [x] Seam/smoke: BVI preference restore

# 53 — Theme day/night only (clock default)

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-21-acceptance-chrome-ia-person.md

## What to build

Убрать `auto` из UI-цикла и из persisted modes, которые пользователь листает. При отсутствии сохранённого preference default = day|night по локальным часам. После выбора пользователь переключает только day ↔ night. Обновить DECISIONS (реверс lock 2026-09-18 «auto in cycle»). Seam: `get/set` только day|night; `defaultFromLocalClock(now)`.

## Acceptance

- [x] В цикле toggle только `day` и `night` (нет `auto`)
- [x] Первый визит без storage получает clock-based class
- [x] Persisted preference только `day`|`night`
- [x] Unit/smoke на seam theme preference
- [x] DECISIONS обновлён: auto убран из пользовательского цикла

# 55 — Structure tiles: dedupe, заведующий, thin-film group, restore units

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-21-acceptance-chrome-ia-person.md

## What to build

На Структуре: одна плитка на подразделение (убрать лишнюю плитку снизу); у лабораторий — FIO с подписью роли **заведующий**; под «Лаборатории» с отступом — «Отдел физико-химии тонкопленочных материалов» и три связанные unit-плитки, остальные labs вне indent; восстановить пропавшие units из roster/модели. Обновить DECISIONS (structure tile rules). Seam: thin-film parent + 3 child lab ids; все ожидаемые units присутствуют.

## Acceptance

- [x] Нет лишней «плитки под плиткой» у unit
- [x] Lab tile: имя + заведующий (роль + FIO)
- [x] Thin-film dept под Лаборатории с indent + 3 children; остальные labs вне группы
- [x] Пропавшие units восстановлены из модели/roster
- [x] Pytest инвариант дерева (или эквивалентный seam)
- [x] DECISIONS: lab tile = заведующий; thin-film grouping

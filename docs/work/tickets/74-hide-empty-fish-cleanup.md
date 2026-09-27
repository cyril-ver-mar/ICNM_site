# 74 — Hide empty sections + fish markers

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-21-acceptance-vitrine-bvi-maps.md

## What to build

Пустые секции на lab pack, person, институтских каталогах: **не рендерить**. Убрать empty-slot fish prose («Перечень проектов появится…»). Оставшиеся fish people/equipment: fish-icon в photo slot как маркер удаления. Honest contour без fish-контента.

## Acceptance

- [x] Пустая секция не в DOM
- [x] Нет fish prose «появится…» на lab/person/catalogues
- [x] Fish photo marker на dummy people/equipment
- [x] pytest: empty section filter seam
- [x] DECISIONS: hide empty

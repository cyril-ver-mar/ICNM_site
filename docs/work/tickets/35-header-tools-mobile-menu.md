# 35 — Header tools + mobile menu parity

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-18-wp-preview-parity-acceptance.md

## What to build

Выровнять tool-кнопки шапки (высота, padding, скругление). Компактная кнопка BVI. Гамбургер только в узком breakpoint — на wide не занимает место language switcher. Mobile overlay: дерево через «+»/«−» как в preview, не всё раскрыто сразу.

## Acceptance

- [ ] Tool-кнопки (язык, тема, BVI, меню) одного визуального размера и radius
- [ ] BVI не доминирует над соседними controls
- [ ] Wide viewport: гамбургер скрыт; language switcher на месте
- [ ] Narrow: гамбургер виден; подменю раскрываются по «+», не все ветки открыты по умолчанию
- [ ] Smoke: `/` wide + narrow

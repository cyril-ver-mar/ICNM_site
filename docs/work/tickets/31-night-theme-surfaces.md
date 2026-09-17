# 31 — Night theme surfaces

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-cutover-polish-remaining.md

## What to build

Добить ночной режим: карточки, полосы, scrolled chrome не остаются «белыми островами» на `html.theme-night`. Токены уже есть; расширить поверхности витрин.

## Acceptance

- [x] Home news/entry/dir-grid и ключевые inner cards читаемы в night
- [x] Scrolled home chrome: night paper + ink (не белая полоса с белым текстом)
- [x] BVI / reduced-motion не ломаются
- [x] Карта Минска в этом тикете не пересобирается

## Notes

Surfaces in `wp-content/themes/ichnm-kadence/style.css` (`html.theme-night`): home news/entry/dir-grid, catalogue/cover/info cards, lab equip, file slots, pub chart/items, people cards, about figures, search panel, map fill tokens (no SVG rebuild). Scrolled home chrome: night paper+ink; day-only light menu-toggle; mark halo restored on night scroll.

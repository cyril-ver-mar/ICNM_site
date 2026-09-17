# 32 — Veil + BVI smoke

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-cutover-polish-remaining.md

## What to build

Проверить и поправить page-open veil и кнопку BVI: veil off при `prefers-reduced-motion` и активном BVI; chrome/tool buttons остаютсяusable.

## Acceptance

- [x] Veil не крутится при reduced-motion и при BVI-active
- [x] Кнопка BVI в шапке открывает режим плагина; меню/язык доступны
- [x] Заметка в smoke-checklist (кратко) или галочка в row существующих проверок

## Notes

- CSS: veil `display:none` under reduced-motion and BVI (`bvi-active` / `bvi-body` / `is-bvi`); wipe/stagger gated on `html.js-motion.is-entered` (was leaving solid navy overlay when animation was only cancelled).
- `chrome.js`: separable veil block — strips `js-motion` when reduce/BVI; MutationObserver if plugin classes arrive late.
- Boot script: skip `js-motion` when BVI cookie/class present (cookie consent boot left intact).
- Smoke: row **M** (Veil + BVI) in `docs/work/smoke-checklist.md`.

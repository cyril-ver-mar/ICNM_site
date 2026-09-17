# 07 — Карта мира: hover-карточки в кадре

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-preview-parity-chrome.md

## What to build

На `/cooperation/` pop партнёров не клипается `overflow:hidden`; пины сверху открывают карточку вниз. BVI по-прежнему может скрывать карту.

## Acceptance

- [x] Hover/focus на пине показывает карточку целиком (в т.ч. верхние пины)
- [x] Карта не режет pop
- [x] Reduced-motion не ломает доступность карточки

# 07 — Визуальный проход Kadence / IBOCH (отдельный срез)

Status: done  
Blocked by: 06  
Spec: docs/work/specs/2026-09-17-wordpress-cutover-readiness.md

## What to build

Сблизить ритм и типографику с iboch.by **без** пиксельного клона: где уместно — блоки Kadence на витринах, сохраняя institute chrome (NAS, меню, BVI). Не ломать уже принятый контентный контур.

## Acceptance

- [x] Согласован список страниц для визуального прохода (главная + 3–5 витрин)
- [x] Chrome института остаётся единственным header
- [x] Reduced-motion и BVI не ломаются

## Locked page list (agreed)

1. Home `/`
2. Structure hub `/structure/`
3. One lab pack e.g. `/labs/nano/`
4. Science catalogue `/science/`
5. News hub `/news/`
6. Contacts `/contacts/` (included)

## Notes

- Visual pass only (CSS + small `page.php` wide-body class). Tickets 01–06 content/sync/roles/smoke untouched.
- Theme `style.css` → 0.3.14: type scale, section spacing, card/list rhythm; Kadence boxed content-area margins/padding neutralized so institute chrome owns vertical rhythm.
- Kadence `#masthead` remains in DOM but hidden; sole visible header is `.ichnm-chrome`.
- Veil / `prefers-reduced-motion` / BVI hooks kept (`chrome.js` + CSS). Hero kicker inherits under BVI.
- Spot-check: Docker `localhost:8080` returned 200 for all locked URLs. Browser MCP screenshot unavailable this session — visual judgment from HTML + CSS review.

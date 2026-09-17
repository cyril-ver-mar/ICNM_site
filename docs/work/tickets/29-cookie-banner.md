# 29 — Cookie banner (preview parity)

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-cutover-polish-remaining.md

## What to build

Cookie-баннер и согласие как в honest preview: необходимые cookie (язык, тема, BVI); аналитика v1 выключена; ссылки на страницы политики cookie и персональных данных.

## Acceptance

- [x] Баннер показывается до согласия; после accept/reject не возвращается (ключ `ichnm-cookies` или эквивалент)
- [x] Ссылки ведут на cookies / personal-data
- [x] Аналитика не включается
- [x] Страницы политики уже в сиде остаются доступны

## Notes

- Theme `ichnm-kadence` 0.3.28: `ichnm_render_cookie_banner()` in `functions.php` (wp_footer 15); early `cookies-ok` in boot script; consent block at end of `chrome.js`; `.cookie-banner` styles in `style.css`.
- Consent payload `{ necessary: true, analytics: bool }` stored in localStorage and cookie `ichnm-cookies` (1y, SameSite=Lax). Analytics preference is stored but no analytics scripts load in v1.

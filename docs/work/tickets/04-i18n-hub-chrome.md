# 04 — Языковые оболочки: хабы лент и подписи chrome

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-wordpress-cutover-readiness.md

## What to build

На EN/BE/ZH оболочки хабов (новости, мероприятия, публикации, поиск) и оставшиеся русские подписи внутри шаблонов хабов/каталогов получают структурные строки языка. Меню продолжает remap через Polylang. Share-slug `/en/about/` не требуется без Pro.

## Acceptance

- [x] `/en/news-en/` (и аналоги) используют шаблон хаба, не пустую page
- [x] Заголовки секций хаба и ключевые CTA не на русском при `pll_current_language != ru`
- [x] Переключалка с хаба ведёт на эквивалент языка

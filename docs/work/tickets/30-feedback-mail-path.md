# 30 — Feedback mail path

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-cutover-polish-remaining.md

## What to build

Зафиксировать доставку формы «Написать нам»: локально — честный журнал WordPress; на PHP-хостинге — `wp_mail` на институтский ящик (option/constant). Без отдельного CRM.

## Acceptance

- [x] Seam: валидация POST → journal (local) / mail (host) покрыта тестом без Docker где возможно
- [x] Успех/ошибка показаны пользователю по-русски (и оболочки i18n chrome)
- [x] Адрес получателя настраивается без правки шаблонов страниц
- [x] Smoke: POST на `/feedback/` не 500

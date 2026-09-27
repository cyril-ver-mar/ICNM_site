# 40 — Photo slots: contain + gradient placeholders

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-18-wp-preview-parity-acceptance.md

## What to build

«Об институте»: фото в окнах `object-fit: contain`, пустые поля — заполнитель фона. Нет фото сотрудника — градиентный photo-slot как в preview (не плоская серая плашка).

## Acceptance

- [x] Нестандартный кадр на About не обрезается жёстко (contain)
- [x] Пустые зоны слота имеют placeholder
- [x] Карточки людей без фото: градиент preview-class

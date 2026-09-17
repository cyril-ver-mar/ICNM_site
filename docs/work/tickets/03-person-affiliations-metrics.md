# 03 — Персональная страница: аффилиации и метрики

Status: done  
Blocked by: 01  
Spec: docs/work/specs/2026-09-17-wordpress-cutover-readiness.md

## What to build

Страница `people/{id}/` перечисляет все аффилиации; блок научных метрик — locked order ORCID → Google Scholar → Scopus Author → eLIBRARY/РИНЦ → ResearchGate; сеть скрыта, если нет и URL, и чисел. Карточка целиком — ссылка (уже в списках).

## Acceptance

- [x] У человека с несколькими ролями видны все аффилиации со ссылками на units/labs
- [x] Метрики рендерятся только при данных; порядок сетей соблюдён
- [x] Нет подписи «Биография и публикации» на карточках списков

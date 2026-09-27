# 59 — Person metrics show+link or hide + admin fields

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-21-acceptance-chrome-ia-person.md

## What to build

Метрики (h-index / science-intensive): если значение есть — показать и связать с профилем сети; если нет — не рендерить. Порядок: ORCID → Google Scholar → Scopus Author → eLIBRARY/РИНЦ → ResearchGate. В админке — явные input fields для метрик. Seam: метрика рендерится iff value и/или URL по правилам. Обновить DECISIONS.

## Acceptance

- [x] Метрика с value+URL: показана и связана
- [x] Пустая метрика не рендерится
- [x] Порядок сетей как lock
- [x] Админка: input fields для метрик при создании/редактировании сотрудника
- [x] Pytest на visibility seam
- [x] DECISIONS уточняет show+link / hide + admin fields

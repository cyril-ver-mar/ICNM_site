# 01 — Инварианты модели и контракт пакетов подразделений

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-wordpress-cutover-readiness.md

## What to build

Зафиксировать шов `src/core`: pytest на IA (меню, четыре языка структуры, нет ленты объявлений, labs/admin_units/people согласованы). Зафиксировать контракт секций пакета лаборатории и admin unit (обязательные якоря / unit-back + people-list) так, чтобы регресс ловился без Docker.

## Acceptance

- [x] Pytest по модели сайта зелёный на известных инвариантах из DECISIONS
- [x] Тест/фикстура: lab pack содержит секции about → contacts; admin unit — unit-back и список людей
- [x] Документирован способ прогона одной командой

## Run

```bash
.venv/bin/pytest -q
```

(или `.venv/bin/pytest -q -m "not slow"` — маркеры объявлены в `pytest.ini`; сейчас slow-тестов нет.)

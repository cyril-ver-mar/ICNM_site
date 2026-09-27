# 79 — BSU→ICNM staff document shelves

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-21-acceptance-vitrine-bvi-maps.md

## What to build

**Implement shelves** (approved 2026-09-21) — не полный вузовский каталог, **нет** корневого «Сотрудникам».

### Documents

Новый child subsection **«Для сотрудника»** (`for-staff` / `staff-docs`): workplace certificates / адм. процедуры — справки с места работы / из трудовой / о зарплате / пособия: кто выдаёт, контакты/процесс; honest empty PDF slots если файла нет.

Также на Documents (и/или children) разместить:

- ПВТР
- Этика (ссылка на кодекс НАН OK)
- ПДн (существующая политика — связать)
- Видеонаблюдение — slot если есть
- Коллективный договор — Documents и/или Union pack

### Contacts / reception

- График приёма
- Командировки (учёный секретарь / reception as fits)

Научный слой (НИР студентов, трудоустройство) — тикет 80.

PDF только slots до файлов института. Обновить DECISIONS + `site_model` + content-sync.

## Acceptance

- [x] Documents child «Для сотрудника» с процедурами/слотами
- [x] ПВТР, этика (NAS link OK), ПДн, видеонаблюдение slot, коллективный договор на полках
- [x] График приёма / командировки на Contacts или Documents
- [x] Нет корневого «Сотрудникам»
- [x] DECISIONS / CONTEXT: Documents «Для сотрудника»

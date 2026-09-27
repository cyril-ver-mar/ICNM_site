# 78 — Tile click edge, pubs plaque, documents, night, maps, overflow

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-21-acceptance-vitrine-bvi-maps.md

## What to build

Один полировочный пакет chrome/catalogues:

1. Whole-tile hyperlink **кроме** нижней контактной кромки (телефоны/email copyable); fallback: tile link + `user-select` contacts.
2. Публикации: sidebar color plaque лаборатории; breadcrumbs spacing как у About children.
3. Документы hub: устав, антикоррупция, реквизиты deep-link; **без** duplicate requisites; «Реквизиты» title-only.
4. About-level: убрать лишнюю строку «Об институте» над «Материальная база»; audit double title.
5. Hide empty institute-level catalogue sections (связка с 74).
6. Night: role/meta + publication years contrast via tokens.
7. Contacts: Яндекс.Карты слева от схематики; приёмная Силина → контакт руководства (из roster).
8. AIST archive + cooperation location: overflow top edge fixed.

## Acceptance

- [x] Tile: contact edge selectable / not swallowed by navigate
- [x] Pubs: lab plaque + crumb spacing
- [x] Documents: 3 tiles, no dup requisites, title-only
- [x] Facilities hero без лишнего «Об институте»
- [x] Night roles/years читаемы
- [x] Contacts: Yandex left + leadership contact
- [x] AIST/cooperation без overflow верхней кромки
- [x] DECISIONS deltas для documents / contacts / tile click

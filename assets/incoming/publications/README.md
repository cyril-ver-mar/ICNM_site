# Publications — evidence + colleague slot

## Scraped starter (ticket 09)

| File | Role |
|------|------|
| `ichnm-lab-*-t4.html` | Raw «Публикации» tab HTML from ichnm.by lab packs (2026-09-17) |
| `ichnm-publications-normalized.json` | Normalized catalogue + source URLs (evidence; live list is `src/core/migrated_copy.json` → `publications_items`) |

Source lab URLs:

- https://ichnm.by/c.eco?q=lab:l:selected_lab:v:13 (nano)
- https://ichnm.by/c.eco?q=lab:l:selected_lab:v:75 (films)
- https://ichnm.by/c.eco?q=lab:l:selected_lab:v:79 (lcd)
- https://ichnm.by/c.eco?q=lab:l:selected_lab:v:191 (composites)
- https://ichnm.by/c.eco?q=lab:l:selected_lab:v:195 (woodchem)

Institute page «Публикации и монографии» (`…selected_info:v:21`) has **aggregate counts only**, no cite list.

## Colleague spreadsheet

Drop xlsx/csv/ods here when the institute sends a full table. Prefer **csv or json** for automated import (`ichnm_import_incoming_publications`). Convert xlsx/ods to csv first. Do not commit empty placeholders as content.

Import paths:

1. Starter scraped catalogue — `publications_items` in `migrated_copy` → `ichnm_import_publications()`
2. Colleague spreadsheet — non-evidence csv/json in this folder → `ichnm_import_incoming_publications()` (idempotent `incoming-pub-…` meta keys)

See [`docs/work/notes/colleague-packet.md`](../../../docs/work/notes/colleague-packet.md).

# 84 — Lab pack queries catalogue CPTs by lab

Status: done
Blocked by: 83
Spec: docs/work/specs/2026-09-23-lab-catalogue-cpts.md

## What to build

`ichnm_lab_pack_html` lists directions / equipment / developments from CPT queries filtered by `_ichnm_lab_slug` (not from JSON arrays for day-to-day). Keep projects / staff / publications behaviour. Links still deep-link to CPT singles / facility URLs. Fallback to seed arrays only if CPT query returns empty (pre-migration safety).

## Acceptance

- [x] Lab pack directions / equipment / developments come from CPT when records exist for that lab
- [x] Tile hrefs match catalogue detail permalinks
- [x] Empty CPT + empty seed still hides the section (ticket 74)

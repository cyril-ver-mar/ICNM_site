# 86 — Feed-editor CRUD on catalogue CPTs

Status: done
Blocked by: 82
Spec: docs/work/specs/2026-09-23-lab-catalogue-cpts.md

## What to build

Extend `ichnm_feed_post_types()` with `direction`, `facility`, `development` so Редактор лент ИХНМ can CRUD them like publications. Keep pages / department / person / chrome locked. Update deny messages and admin_menu removals so catalogue menus stay visible to the role.

## Acceptance

- [x] Feed-editor can create/edit/publish the three catalogue types
- [x] Feed-editor still cannot edit vitrine pages, department, person, or Appearance
- [x] Role refreshes on init / activation

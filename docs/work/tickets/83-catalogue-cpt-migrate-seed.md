# 83 — Migrate catalogue rows into CPTs (stable slugs, no wipe)

Status: done
Blocked by: 82
Spec: docs/work/specs/2026-09-23-lab-catalogue-cpts.md

## What to build

One-time seed import from `labs[].directions|equipment|developments` and institute `science_topics` / `facilities_items` / `developments_items` into the three CPTs. Stable key `_ichnm_catalogue_key` = `{type}:{slug}`. Re-sync must not overwrite title/body/meta when `_ichnm_editor_owned` (or non-empty editor meta); only fill empty meta. Trash leftover child detail pages after CPT import. Call from `ichnm_sync_content`; bump `ICHNM_CONTENT_SEED_VERSION`.

## Acceptance

- [x] Import creates CPT posts for known slugs (e.g. immuno-spheres, vaktime-plasma-lab, nano-carriers)
- [x] Second sync with editor-owned post keeps editor title/body
- [x] Empty meta still seed-filled on first touch
- [x] Child page duplicates for catalogue details removed or no longer preferred for permalinks
- [x] Seed version bumped

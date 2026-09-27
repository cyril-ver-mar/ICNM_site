# 81 — Для студентов, whole-tile click, BVI restore

Status: done
Blocked by: 67–80 follow-ups
Spec: docs/work/specs/2026-09-21-acceptance-vitrine-bvi-maps.md (deltas)

## What to build

1. **IA — «Для студентов»**  
   Move **Научно-исследовательская работа студентов** and **Трудоустройство выпускников** out of the science folder into a new top-level menu section **«Для студентов»**, after Новости and Мероприятия (before Контакты). Keep both pages as children. Update `site_model.json`, menu seed (content seed bump), DECISIONS / CONTEXT.

2. **Tiles — revert contact-edge**  
   Undo «whole tile link except bottom contacts». Restore person/staff tiles without a separate bottom phone/email strip. Hover highlights the entire tile; any click follows the person hyperlink.

3. **BVI restore (critical)**  
   After turning BVI **off**, the site must not stay a solid dark window until full reload. Fix veil / `js-motion` / BVI class restore so on↔off restores normal chrome and theme without reload.

## Acceptance

- [x] Top menu: … Новости → Мероприятия → **Для студентов** → Контакты
- [x] Science folder no longer lists NIR / employment
- [x] Person tiles: single `<a class="ichnm-person-card">` with phone/email inside; whole-tile hover + click
- [x] BVI off: no solid navy veil; chrome/theme visible without F5
- [x] `ICHNM_CONTENT_SEED_VERSION` bumped (36); DECISIONS / CONTEXT / project-decisions updated

## Notes

- Root cause of BVI dark pane: enabling BVI calls `enterPage(false)` (drops `js-motion`); BVI CSS hid `.ichnm-page-veil` with `display:none`; disabling BVI removed that hide while the veil’s default was still a full-screen navy layer with no wipe animation. Fix: veil `display:none` unless `html.js-motion` is active; on BVI off force `is-entered`, hide veil, clear leftover plugin panels.

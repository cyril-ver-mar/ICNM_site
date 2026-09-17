# 04 — Inner pages: packs, person, catalogues, maps

Status: done  
Blocked by: 01  
Spec: docs/work/specs/2026-09-17-wp-visual-preview-parity.md

## What to build

Visual pass for lab pack, person page, catalogue hub/detail, contacts (Minsk), cooperation map — kickers, section jump, card grids, map frames matching preview proportions.

## Acceptance

- [x] Lab pack sections readable with preview-like local nav / contacts block
- [x] Catalogue cards / detail photo-slot rhythm matches preview
- [x] Minsk + world maps keep fixed sizes from prior tickets; polish only

## Notes

- Theme `style.css` 0.3.23: section-jump, cover-grid/cover-card, info-grid, leader-profile, lab-local/contacts/photo-slot polish; map pin glow; map-slot frame without touching city-map `max-width: 42rem` or coop/pop `overflow: visible`.
- Light class hooks in `content-sync.php`: catalogue photo-slots, person `leader-profile`, contacts info-grid + map-slot wrapper, lab-kicker before local nav.
- Templates: `page.php` kickers, `single-person.php` crumbs + wide body, `page-news.php` section-jump.
- Re-run content sync / seed to refresh stored HTML for cards, people, contacts.

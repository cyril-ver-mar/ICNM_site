# 03 — Home bands visual (hero, stats, dir-grid, news)

Status: done  
Blocked by: 01  
Spec: docs/work/specs/2026-09-17-wp-visual-preview-parity.md

## What to build

Align home section rhythm with preview: hero layout over lattice, stats-band, dir-grid cards, news cards, entry cards, next-event banner — spacing, borders, type sizes from preview.

## Acceptance

- [x] Side-by-side home feels like preview (not Kadence blog)
- [x] Lattice still visible under hero gradient
- [x] News/entry cards share preview-like borders/gaps

## Notes

- CSS: `style.css` homepage block ported from `preview/site.css` (`.hero`, `.stats-band`, `.home-band`, `.dir-grid`, `.news-card`, `.event-banner`).
- Markup: dual preview class aliases on `front-page.php`; news band gets `band-muted`; next-event title moved into navy banner on home.
- Sticky chrome / `body.home` hero pull-under left to ticket 24.
- Theme version `0.3.22`.

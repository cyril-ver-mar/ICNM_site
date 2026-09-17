# 05 — Footer + visual smoke note

Status: done  
Blocked by: 02, 03, 04  
Spec: docs/work/specs/2026-09-17-wp-visual-preview-parity.md

## What to build

Footer grid/legal/social/map compact like preview; add short visual checklist note to smoke-checklist. Spec → done when accepted.

## Acceptance

- [x] Footer not a Kadence default strip
- [x] Smoke-checklist mentions visual pass pages
- [x] Spec status done

## Notes

- Theme `style.css` 0.3.24: preview-class footer grid (identity / on-site / social / compact Minsk map) + NAS pictogram strip; Kadence `.site-footer` hidden.
- `functions.php`: `ichnm_render_footer()` from site_model (pictograms via `assets/footer/` → ichnm-source; ICNM socials only when href set).
- Smoke: row **V** — home / lab / contacts side-by-side vs `preview/`.

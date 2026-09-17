# 01 — Design tokens + base type from preview

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-wp-visual-preview-parity.md

## What to build

Port preview CSS variables (navy, paper, accent, ink, wrap, fonts) into `ichnm-kadence` `:root` / body; set Montserrat (or preview stack) and base type scale so pages share preview colour/type DNA.

## Acceptance

- [x] Theme tokens mirror preview `--navy` / `--paper` / `--accent` (or mapped `--ichnm-*`)
- [x] Body font matches preview family
- [x] Existing pages still readable; no broken layout from token rename

## Notes

- Dual-define: preview names + `--ichnm-*` aliases (`--ichnm-muted` → preview `--band` for backward compat).
- Fonts: Google Montserrat 600/700 + Roboto 400/500/700 via `functions.php` (`ichnm-fonts` + preconnect).
- Theme version bumped to 0.3.20 for stylesheet cache bust.

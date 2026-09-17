# 02 — Sticky chrome + home scroll paper

Status: done  
Blocked by: 01  
Spec: docs/work/specs/2026-09-17-wp-visual-preview-parity.md

## What to build

Header sticky like preview; on home, glass/navy then paper+ink when scrolled (preview `is-scrolled`). Menu/tools remain usable. BVI ok.

## Acceptance

- [x] Chrome sticky on scroll
- [x] Home: scrolled state paper background + dark text on tools/menu
- [x] Inner pages: stable navy chrome (or preview equivalent)

## Notes

- `chrome.js`: passive scroll listener toggles `.is-scrolled` on `.ichnm-chrome` when `scrollY > 24` (same threshold as preview).
- CSS: home uses `body.home` + `--header-glass` / paper vars; hero tucked under chrome for glass; mobile drawer stays navy; BVI forces black chrome.
- Theme version `0.3.21` for cache bust. Scope kept to chrome.js + chrome CSS (ticket 25 owns home band polish).

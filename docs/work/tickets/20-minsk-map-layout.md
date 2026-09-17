# 06 — Карта Минска: размер и вёрстка

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-preview-parity-chrome.md

## What to build

Починить регрессию: карта Минска на `/contacts/` (и footer) — city-map как в preview (aspect ~8/5, ограниченный max-width), inline SVG с CSS-заливками, **не** full-page / не на всю ширину контента без меры. Shortcode `[ichnm_minsk_map]` ок.

## Acceptance

- [x] На `/contacts/` карта видна (ocean/land/pin)
- [x] Карта не занимает весь viewport и не «ломает» колонку текста
- [x] max-width / aspect близки к preview city-map

## Notes

Root cause: absolute SVG/hotspots without a complete `.ichnm-world-map` containing block (map paint vars + `position: relative` + sized frame); contacts was also forced wide so the prose column blew out. Fix: base map frame + city-map `max-width: 42rem` / `aspect-ratio: 8/5`; footer wrapper `max-width: 17rem`; contacts stays prose-width. Seed not bumped (shortcode renders at runtime).

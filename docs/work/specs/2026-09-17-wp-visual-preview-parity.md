# WordPress visual → honest preview

Status: done

Tracker: `docs/work/specs/`, `docs/work/tickets/`.

IA/content parity slices are done. Product locks: `docs/DECISIONS.md` (look from iboch.by, **not** a pixel clone of IBOCH; honest preview is the institute contour to match).

## Problem

PHP/WordPress child theme already carries institute chrome, home, packs, catalogues, and maps, but **look and feel** still diverge from honest `preview/` (CSS tokens, sticky header behaviour, typography, band/card rhythm, lab/person/page chrome). Editors and приёмка see «другой сайт» next to `preview/index.html`.

## Solution

Bring **visual** of the Kadence child (`ichnm-kadence`) to honest preview: port tokens, header scroll behaviour, type scale, section/card rhythm, and key page surfaces from `preview/site.css` (+ lattice already present). Keep WordPress templates and seeded content; do not rebuild as static HTML. Not a pixel-perfect IBOCH clone.

## User stories

1. As приёмщик, I want `/` and inner pages to feel like `preview/`, so that sign-off compares one institute look.
2. As visitor, I want sticky header, navy→paper on scroll (home), and readable tool buttons, so that chrome matches preview behaviour.
3. As visitor of lab/person/catalogue pages, I want the same spacing, cards, and kickers as preview, so that packs do not look like a different theme.

## Implementation decisions

- Source of truth for **look**: `preview/site.css` (+ `preview/site.js` only where motion already agreed). WP templates stay; CSS/markup classes aligned toward preview names where cheap.
- Scope pages (minimum): home, chrome (all pages), lab pack, person, science hub + one detail, contacts, cooperation.
- BVI and `prefers-reduced-motion` remain respected (veil/lattice off).
- Kadence parent stays; hide its chrome; institute styles win.
- Seed bump only if markup classes in synced HTML must change.

## Testing decisions (seams)

1. No new core algorithm seam required; visual acceptance is checklist + side-by-side screenshots (home, lab, contacts).
2. Smoke still green; optional note in smoke-checklist for “visual pass done”.
3. Not seam: DNS, colleague PDFs, IBOCH Customizer dump.

## Out of scope

- DNS / PHP hosting order
- preview-filled fish look
- Full EN/BE/ZH visual copy translation
- Pixel-perfect match to iboch.by

## Notes

- Grill lock: goal = WP looks like honest preview contour.
- Ticket numbering continues from 23.
- Closed by tickets 23–27 (tokens, sticky chrome, home bands, inner pages, footer + smoke note V).

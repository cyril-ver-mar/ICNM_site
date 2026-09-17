# 28 — Chrome leftovers (Kadence credit)

Status: done  
Blocked by: none  
Spec: docs/work/specs/2026-09-17-cutover-polish-remaining.md

## What to build

Убрать хвост Kadence из публичного DOM (скрытый `.site-footer` / theme credit «Kadence WP»), чтобы рядом с институтским подвалом не светилась чужая оболочка.

## Acceptance

- [x] В исходнике главной / контактов нет видимого и screen-reader credit «Kadence WP тема»
- [x] Институтский footer (`ichnm-footer`) на месте; NAS pack и карта-компакт не сломаны
- [x] Smoke paths 200

## Notes

- `ichnm-kadence/functions.php`: on `wp` remove `Kadence\footer_markup` from `kadence_footer` so `#colophon` / theme credit never enter the public DOM (CSS hide alone left screen-reader text).
- Institute footer stays on `wp_footer` via `ichnm_render_footer()`; NAS pictograms + compact Minsk map unchanged.
- Verified `/` and `/contacts/`: no `Kadence WP` / `kadencewp.com` / `#colophon`; `./scripts/wp-smoke.sh` all required 200.

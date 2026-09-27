# Lab catalogue CPTs (directions / facilities / developments)

Status: ready-for-agent

Tracker: `docs/work/specs/`, `docs/work/tickets/` (local markdown).

Product locks: `docs/DECISIONS.md`. Glossary: `docs/CONTEXT.md`. Target stack: WordPress + Kadence child + `ichnm-site`. Honest `preview/` HTML parity for this slice is **optional / deferred** — v1 day-to-day editing ships in WP.

## Problem

Lab pack lists (направления, оборудование, разработки) and institute hubs still come from JSON arrays in `migrated_copy` that content-sync writes into page HTML. Editors cannot add or edit a direction / facility / development the way they manage **Персоналии** or **Публикации**. Re-running seed risks overwriting hand-edited catalogue text. Day-to-day work must not require editing JSON arrays.

## Solution

Three public custom post types, each with its own admin menu like Персоналии:

| CPT | Admin label | Public path base | Hub page |
|-----|-------------|------------------|----------|
| `direction` | Направления | `/science/{slug}/` | Направления работы |
| `facility` | Оборудование | `/facilities/{slug}/` | Материальная база |
| `development` | Разработки | `/developments/{slug}/` | Разработки |

Each record supports title, body (editor), files (attachments / file shelf), featured photo; required meta **lab** (department / lab slug); optional **person** (staff assignment); development optionally links to a **direction** slug.

Lab pack pages and institute hubs **query** these CPTs (by lab meta / all published). First migration seeds CPTs from `labs[].directions|equipment|developments` plus institute `science_topics` / `facilities_items` / `developments_items`, with stable slugs. After an editor saves a record, content-sync must not wipe that meta or body (person-admin pattern). Feed-editor role gains CRUD on these three types (same staff role that already publishes publications).

## User stories

1. As a feed editor, I want separate admin menus for Направления / Оборудование / Разработки, so that I can add a lab catalogue item without touching JSON or page HTML.
2. As a feed editor, I want to set laboratory (and optionally person; for a development, optionally a direction), so that cards show correct attribution on hubs and lab packs.
3. As a visitor on a lab pack, I want directions / equipment / developments drawn from the CPT catalogue for that lab, so that admin edits appear without re-seeding arrays.
4. As a visitor on science / developments / facilities hubs, I want the same CPT records with lab (+ staff) attribution, so that institute catalogues stay consistent with DECISIONS.
5. As an administrator running content sync after migration, I want editor-owned catalogue meta and body preserved, so that a seed bump does not erase hand edits.
6. As a developer, I want a one-time seed import with stable slugs and a bumped `ICHNM_CONTENT_SEED_VERSION`, so that Docker / test URL catch up idempotently.

## Implementation decisions

- Register CPTs in `ichnm_register_post_types` with `capability_type` = `post`, `map_meta_cap` true, `show_in_rest` true, `has_archive` false (hubs remain pages). Rewrite bases: `science`, `facilities`, `developments` so singles keep existing public URLs. Supports: title, editor, thumbnail, custom-fields (files via media library / existing file-shelf HTML where needed).
- Admin meta box (catalogue-admin, person-admin style): lab select (from department / migrated labs); optional person select; development → direction slug select. On editor `save_post`, set `_ichnm_editor_owned` and store meta; sync uses a lock flag so import does not mark ownership.
- Import: collect rows from `ichnm_catalogue_rows_for_parent` (and institute-only topics); upsert by `_ichnm_catalogue_key` = `{type}:{slug}`. If post exists and is editor-owned (or has editor meta), only fill **empty** meta keys — do not overwrite title/content. Otherwise refresh from seed HTML (reuse `ichnm_catalogue_detail_html` shape into post_content).
- Stop relying on child **pages** for new detail URLs once CPTs exist; trash leftover child pages keyed `_ichnm_catalogue_key` after CPT import to avoid duplicate paths. Permalink helper prefers CPT `get_permalink`.
- Lab pack HTML: query CPT by `_ichnm_lab_slug` (fallback: seed arrays only if query empty during early boot). Hubs: query all published of each type; split institute (empty lab) vs lab-attributed for section titles when useful.
- Search index: facilities + developments (and optionally directions) from CPT posts, not only top-level JSON items.
- Roles: add `direction`, `facility`, `development` to `ichnm_feed_post_types()`; keep vitrine pages / department / person locked. Polylang: register the three types like `publication`.
- Preview HTML: deferred; Python seam only for row-key / normalize helpers used by pytest.
- Update `docs/DECISIONS.md` + `docs/CONTEXT.md` (+ project-decisions rule summary) when locking.

## Testing decisions (seams)

- Python: `lab_catalogue` helpers — stable key `{type}:{slug}`, row collection from migrated copy shape, lab_slug resolution (no WordPress).
- PHP / Docker smoke (ticket): after `ichnm_sync_content(true)`, CPT counts > 0; lab pack and hub list a known slug; edit meta + re-sync keeps editor body; feed-editor can create a direction.
- Existing catalogue_detail / site_model attribution tests stay green (JSON still feeds preview; WP path is additive).

## Out of scope

- Translating catalogue body copy into EN/BE/ZH (structure shells only in v1).
- Scientific **projects** CPT (stay lab-pack-only per DECISIONS).
- Publications CPT changes (already shipped).
- Honest preview HTML rebuild for catalogue lists.
- Changing public path bases away from `/science|facilities|developments/{slug}/`.

## Notes

- Locked from design conversation 2026-09-23 («делай»).
- Seed bump required when import/menu/content registration lands.
- Sync command (local): `docker compose run --rm wpcli eval 'ichnm_sync_content(true);'`

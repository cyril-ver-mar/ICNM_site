# 82 — Register direction / facility / development CPTs + admin meta

Status: done
Blocked by: none
Spec: docs/work/specs/2026-09-23-lab-catalogue-cpts.md

## What to build

Register three CPTs (`direction`, `facility`, `development`) with admin menus like Персоналии; rewrite bases `science` / `facilities` / `developments`; `has_archive` false. Admin meta box: lab (required), optional person; development also optional direction slug. Save sets `_ichnm_editor_owned`. Theme single templates with crumbs to the matching hub. Polylang registers the three types.

## Acceptance

- [x] Three CPT menus visible for administrators
- [x] Meta box saves lab / person / direction_slug
- [x] Editor save sets `_ichnm_editor_owned`
- [x] Singles resolve under existing public path bases
- [x] Polylang includes the new types

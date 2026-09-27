# 87 — Smoke + DECISIONS/CONTEXT + Python seam

Status: done
Blocked by: 84, 85, 86
Spec: docs/work/specs/2026-09-23-lab-catalogue-cpts.md

## What to build

Python seam + pytest for catalogue import keys / lab_slug resolution from migrated copy. Update DECISIONS / CONTEXT / project-decisions. Docker or documented smoke: force sync, spot-check one lab pack + one hub + one CPT admin path. Mark tickets 82–87 done when green.

## Acceptance

- [x] Pytest for lab_catalogue seam green
- [x] DECISIONS + CONTEXT document catalogue CPTs + feed-editor access + no wipe on sync
- [x] Smoke notes / sync command documented on tickets or handoff
- [x] Tickets 82–86 acceptance checked off

## Smoke (when Docker is up)

```bash
docker compose run --rm wpcli eval 'ichnm_sync_content(true); echo get_option("ichnm_content_seed_version");'
# Expect option 37. Spot-check: /labs/nano/, /science/, /wp-admin/ edit.php?post_type=direction
```

2026-09-23: local Docker daemon was not running in the implement session; pytest seam passed; WP force-sync deferred to next `local-setup` / test URL.

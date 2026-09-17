# ICNM public site

Official WordPress + Kadence site for the Institute of Chemistry of New Materials (NAS Belarus).

**Как открыть сайт:** [docs/КАК-ОТКРЫТЬ-САЙТ.md](docs/КАК-ОТКРЫТЬ-САЙТ.md)

Product locks: `docs/DECISIONS.md`. Glossary: `docs/CONTEXT.md`. Spec: `docs/work/specs/2026-08-30-ichnm-public-site-v1.md`.

## Quick open (preview, no Docker)

```bash
chmod +x scripts/open-site.sh
./scripts/open-site.sh
```

Opens `preview/index.html` in the browser.

## Tests

Model / pack-contract seam (no Docker):

```bash
.venv/bin/pytest -q
```

Full install + run:

```bash
python3 -m venv .venv
.venv/bin/pip install -r requirements-dev.txt
.venv/bin/pytest
```

## Local WordPress (Docker Desktop required)

```bash
./scripts/local-setup.sh
```

http://localhost:8080 — admin / admin (change the password).

Smoke (Docker up): `./scripts/wp-smoke.sh` — see `docs/work/smoke-checklist.md`.  
Backup → `exports/`: `./scripts/wp-backup.sh` — handoff `docs/work/2026-09-02-hosting-handoff.md`.

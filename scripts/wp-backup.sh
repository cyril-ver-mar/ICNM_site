#!/usr/bin/env bash
# Export a portable WordPress snapshot for PHP hosting handoff (not Java Forever).
set -euo pipefail
cd "$(dirname "$0")/.."

STAMP="$(date +%Y%m%d-%H%M)"
OUT_DIR="${1:-exports/wp-backup-$STAMP}"
mkdir -p "$OUT_DIR"

echo "Exporting WordPress snapshot to $OUT_DIR"

if ! docker compose ps --status running 2>/dev/null | grep -q wordpress; then
  echo "WordPress containers are not running. Start with: docker compose up -d" >&2
  exit 1
fi

echo "→ database dump"
docker compose exec -T db mysqldump \
  -uwordpress -pwordpress \
  --databases wordpress \
  --single-transaction \
  --quick \
  --routines \
  --no-tablespaces \
  > "$OUT_DIR/wordpress.sql"

echo "→ wp-content (themes, plugins, uploads overlays)"
mkdir -p "$OUT_DIR/wp-content"
tar -C wp-content -czf "$OUT_DIR/wp-content-overlay.tgz" \
  themes/ichnm-kadence \
  plugins/ichnm-site

# Full uploads from the WordPress volume (tar may exit 1 if files change mid-read).
set +e
docker compose exec -T wordpress sh -c 'cd /var/www/html && tar czf - wp-content/uploads' \
  > "$OUT_DIR/uploads.tgz"
tar_rc=$?
set -e
if [[ ! -s "$OUT_DIR/uploads.tgz" ]]; then
  rm -f "$OUT_DIR/uploads.tgz"
  echo "uploads.tgz skipped (empty or unavailable)"
elif [[ $tar_rc -ne 0 && $tar_rc -ne 1 ]]; then
  echo "uploads.tgz warning: tar exit $tar_rc (file kept if non-empty)"
fi

cat > "$OUT_DIR/README.txt" <<EOF
ICNM WordPress local export — $STAMP
=====================================

Contents
- wordpress.sql          MySQL dump (database name: wordpress)
- wp-content-overlay.tgz Child theme ichnm-kadence + plugin ichnm-site
- uploads.tgz            Media from the container (if present)

Target hosting
- PHP 8.3 + MySQL/MariaDB (Active.by / Hoster.by PHP tariff)
- Do NOT deploy onto the current Forever Java stack

Restore sketch
1. Create empty DB and user on PHP hosting.
2. Import wordpress.sql (then search-replace localhost:8080 → test URL).
3. Unpack WordPress core (or use hoster installer), then overlay:
   - themes/ichnm-kadence
   - plugins/ichnm-site
   - uploads/
4. Set siteurl/home, permalinks /%postname%/, activate theme + ichnm-site.
5. Keep aist.ichnm.by and mail MX untouched when cutting over ichnm.by later.

See also: docs/work/2026-09-02-hosting-handoff.md
EOF

# Keep exports out of git if ignored; still write locally.
echo "Done: $OUT_DIR"
ls -lh "$OUT_DIR"

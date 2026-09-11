#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose up -d wordpress db
echo "Waiting for WordPress files..."
for i in $(seq 1 90); do
  if docker compose exec -T wordpress test -f /var/www/html/wp-includes/version.php; then
    break
  fi
  sleep 2
done
docker compose run --rm wpcli core is-installed || docker compose run --rm wpcli core install \
  --url="http://localhost:8080" \
  --title="ИХНМ НАН Беларуси" \
  --admin_user="admin" \
  --admin_password="admin" \
  --admin_email="ihnm@ichnm.by" \
  --skip-email
docker compose run --rm wpcli theme install kadence --activate
docker compose run --rm wpcli theme activate ichnm-kadence
docker compose run --rm wpcli plugin activate ichnm-site
docker compose run --rm wpcli plugin install polylang --activate || true
docker compose run --rm wpcli plugin install button-visually-impaired --activate || true
docker compose run --rm wpcli rewrite structure '/%postname%/' --hard
docker compose run --rm wpcli eval 'if (function_exists("ichnm_sync_content")) { ichnm_sync_content(true); echo "ichnm content synced\n"; }'
echo "Local site: http://localhost:8080"
echo "Admin: http://localhost:8080/wp-admin  (admin / admin) — change the password."

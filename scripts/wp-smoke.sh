#!/usr/bin/env bash
# Thin HTTP smoke for ICNM WordPress (local Docker or remote test URL).
# Exit 0 only if every required path returns HTTP 200 after redirects.
# Manual checklist: docs/work/smoke-checklist.md
set -euo pipefail
cd "$(dirname "$0")/.."

BASE_URL="${BASE_URL:-http://localhost:8080}"
BASE_URL="${BASE_URL%/}"

# Required paths: ticket 06 + remaining IA (14) + preview parity (22).
REQUIRED=(
  /
  /labs/nano/
  /accounting/
  /people/agabekov/
  /news/
  /search/
  /sitemap/
  /feedback/
  /en/home-en/
  /education/
  /aspirantura/
  /scientific-council/
  /publications/
  /documents/
  /vacancies/
  /union/
  /young-scientists/
  /cooperation/
  /science/thin-films/
  /developments/immuno-spheres/
  /facilities/vaktime-plasma-lab/
  /conferences/aist-2025/
  /contacts/
)

# Optional extras (reported, do not fail the run).
OPTIONAL=(
  /science/
  /developments/
  /facilities/
  /aist/
  /events/
  /doctorate/
  /defense-council/
  /internships/
  /courses/
  /charter/
  /anti-corruption/
  /e-appeals/
  /en/structure-en/
  /be/home-be/
  /zh/home-zh/
  /hr/
)

if ! command -v curl >/dev/null 2>&1; then
  echo "curl is required" >&2
  exit 1
fi

probe() {
  local path="$1"
  local url="${BASE_URL}${path}"
  # -L follow redirects; -o discard body; -s silent; -w status only
  local code
  code="$(curl -sS -o /dev/null -w '%{http_code}' -L --max-time 30 "$url" || echo "000")"
  printf '%s\t%s\n' "$code" "$path"
}

echo "Smoke against $BASE_URL"
echo "---- required ----"
fail=0
while IFS= read -r line; do
  code="${line%%$'\t'*}"
  path="${line#*$'\t'}"
  if [[ "$code" == "200" ]]; then
    echo "OK  $code  $path"
  else
    echo "FAIL $code  $path"
    fail=1
  fi
done < <(for p in "${REQUIRED[@]}"; do probe "$p"; done)

echo "---- optional ----"
for p in "${OPTIONAL[@]}"; do
  line="$(probe "$p")"
  code="${line%%$'\t'*}"
  path="${line#*$'\t'}"
  if [[ "$code" == "200" ]]; then
    echo "OK  $code  $path"
  else
    echo "skip $code  $path"
  fi
done

if [[ "$fail" -ne 0 ]]; then
  echo >&2
  echo "Required paths failed. If Docker is down: docker compose up -d" >&2
  echo "Full manual list: docs/work/smoke-checklist.md" >&2
  exit 1
fi

echo
echo "All required paths returned 200. Still walk the manual checklist for UI."

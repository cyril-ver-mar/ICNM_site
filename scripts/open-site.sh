#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ ! -x .venv/bin/python ]]; then
  python3 -m venv .venv
  .venv/bin/pip install -q -r requirements-dev.txt
fi
.venv/bin/python scripts/build_preview.py
if command -v open >/dev/null 2>&1; then
  open preview/index.html
elif command -v xdg-open >/dev/null 2>&1; then
  xdg-open preview/index.html
else
  echo "Open preview/index.html in a browser."
fi

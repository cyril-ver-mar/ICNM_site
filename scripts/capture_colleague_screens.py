#!/usr/bin/env python3
"""Capture preview-filled screenshots for colleague Word templates.

Uses Google Chrome headless. Output:
  docs/work/colleague-packet/templates/_screens/*.png

Usage (from repo root):
  python scripts/capture_colleague_screens.py
"""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs" / "work" / "colleague-packet" / "templates" / "_screens"
PREVIEW = ROOT / "preview-filled"

CHROME_CANDIDATES = [
    Path("/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"),
    Path("/Applications/Chromium.app/Contents/MacOS/Chromium"),
]

SHOTS = [
    ("person-full", "people/ivanova/index.html", 1800),
    ("lab-pack", "labs/films/index.html", 2200),
    ("development", "developments/polaroid-films/index.html", 1600),
    ("direction", "science/anisotropic-films/index.html", 1400),
    ("structure", "structure.html", 1600),
    ("union", "union.html", 1400),
]


def find_chrome() -> Path:
    for path in CHROME_CANDIDATES:
        if path.is_file():
            return path
    raise SystemExit("Google Chrome not found. Install Chrome or update CHROME_CANDIDATES.")


def main() -> None:
    chrome = find_chrome()
    OUT.mkdir(parents=True, exist_ok=True)
    if not PREVIEW.is_dir():
        raise SystemExit(f"Missing {PREVIEW}; run scripts/build_preview.py first.")

    for name, rel, height in SHOTS:
        target = PREVIEW / rel
        if not target.is_file():
            print(f"SKIP {name}: missing {rel}", file=sys.stderr)
            continue
        out = OUT / f"{name}.png"
        url = target.resolve().as_uri()
        cmd = [
            str(chrome),
            "--headless=new",
            "--disable-gpu",
            "--hide-scrollbars",
            "--no-sandbox",
            f"--window-size=1280,{height}",
            f"--screenshot={out}",
            url,
        ]
        subprocess.run(cmd, check=False, capture_output=True)
        if out.is_file():
            print(f"Wrote {out.relative_to(ROOT)} ({out.stat().st_size} bytes)")
        else:
            print(f"FAILED {name}", file=sys.stderr)


if __name__ == "__main__":
    main()

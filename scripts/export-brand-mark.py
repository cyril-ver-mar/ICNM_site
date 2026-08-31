#!/usr/bin/env python3
"""Rebuild web SVG/PNG from assets/brand/ИХНМ-Иконка.ai (PDF-based Illustrator)."""

from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "assets" / "brand" / "ИХНМ-Иконка.ai"
OUT_DIR = ROOT / "assets" / "brand"


def main() -> int:
    try:
        import pymupdf
    except ImportError:
        print("Install pymupdf in the venv: .venv/bin/pip install pymupdf", file=sys.stderr)
        return 1
    if not SRC.is_file():
        print(f"Missing {SRC}", file=sys.stderr)
        return 1
    doc = pymupdf.open(SRC)
    page = doc[0]
    (OUT_DIR / "ichnm-mark.svg").write_text(page.get_svg_image(), encoding="utf-8")
    pix = page.get_pixmap(matrix=pymupdf.Matrix(4, 4), alpha=True)
    pix.save(OUT_DIR / "ichnm-mark.png")
    print(f"Wrote {OUT_DIR / 'ichnm-mark.svg'} and {OUT_DIR / 'ichnm-mark.png'}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

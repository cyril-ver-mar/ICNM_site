#!/usr/bin/env python3
"""Rebuild web SVG/PNG from assets/brand/ИХНМ-Иконка.ai (the live header mark)."""

from __future__ import annotations

import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "assets" / "brand" / "ИХНМ-Иконка.ai"
OUT_DIR = ROOT / "assets" / "brand"
THEME_ASSETS = ROOT / "wp-content" / "themes" / "ichnm-kadence" / "assets"


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
    svg_path = OUT_DIR / "ichnm-mark.svg"
    png_path = OUT_DIR / "ichnm-mark.png"
    svg_path.write_text(page.get_svg_image(), encoding="utf-8")
    pix = page.get_pixmap(matrix=pymupdf.Matrix(4, 4), alpha=True)
    pix.save(png_path)
    if THEME_ASSETS.is_dir():
        shutil.copyfile(svg_path, THEME_ASSETS / "ichnm-mark.svg")
        shutil.copyfile(png_path, THEME_ASSETS / "ichnm-mark.png")
    print(
        f"Wrote {svg_path} and {png_path} "
        f"({page.rect.width:.0f}×{page.rect.height:.0f} pt, png {pix.width}×{pix.height})"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

#!/usr/bin/env python3
"""Write the public HTML preview next to this repo's preview/ folder."""

from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from src.core.copy import use_copy
from src.core.filled import filled_copy
from src.core.preview import render_site_files
from src.core.site_model import load_site_model

_BRAND_DIR = ROOT / "assets" / "brand"
_BRAND_FILES = ("ichnm-mark.svg", "ichnm-mark.png", "nas-emblem.webp")
_MEDIA_SUFFIXES = {".svg", ".png", ".gif", ".jpg", ".jpeg", ".webp", ".pdf", ".doc", ".docx"}
_EXTRA_DIRS = (
    ("footer", ROOT / "assets" / "footer"),
    ("maps", ROOT / "assets" / "maps"),
    ("about", ROOT / "assets" / "about"),
    ("aist", ROOT / "assets" / "aist"),
    ("news", ROOT / "assets" / "news"),
)


def _write_tree(out: Path, files: dict[str, str]) -> int:
    for rel, html in files.items():
        path = out / rel
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(html, encoding="utf-8")
    media = out / "media"
    media.mkdir(parents=True, exist_ok=True)
    copied = 0
    for name in _BRAND_FILES:
        src = _BRAND_DIR / name
        if src.is_file():
            (media / name).write_bytes(src.read_bytes())
            copied += 1
    for folder, src_dir in _EXTRA_DIRS:
        dest = media / folder
        dest.mkdir(parents=True, exist_ok=True)
        if not src_dir.is_dir():
            continue
        for src in src_dir.rglob("*"):
            if not src.is_file() or src.suffix.lower() not in _MEDIA_SUFFIXES:
                continue
            target = dest / src.relative_to(src_dir)
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(src.read_bytes())
            copied += 1
    print(f"Wrote {len(files)} files to {out} (+{copied} brand assets)")
    return copied


def main() -> None:
    model = load_site_model()
    _write_tree(ROOT / "preview", render_site_files(model))
    with use_copy(filled_copy()):
        _write_tree(ROOT / "preview-filled", render_site_files(model))
    print(f"Open honest contour: {ROOT / 'preview' / 'index.html'}")
    print(f"Open filled mock: {ROOT / 'preview-filled' / 'index.html'}")


if __name__ == "__main__":
    main()

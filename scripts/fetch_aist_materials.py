#!/usr/bin/env python3
"""Download public AIST files from aist.ichnm.by into assets/aist/."""

from __future__ import annotations

import json
import sys
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DEST = ROOT / "assets" / "aist"
BASE = "http://aist.ichnm.by/"
UA = "ICNM-preview-builder/1.0 (institute archive)"

# Working files on aist.ichnm.by as of 2026-09-01. Skip 404s, dead tut.by hosts,
# and the «Навука» newspaper PDF (whole issue, not an AIST proceedings file).
FILES: tuple[tuple[str, str], ...] = (
    ("doc/info_aist_2025.pdf", "info_aist_2025.pdf"),
    ("doc/PROGRAMMA_AIST_2025.pdf", "PROGRAMMA_AIST_2025.pdf"),
    ("doc/Abstract_AICT_2025.pdf", "Abstract_AICT_2025.pdf"),
    ("doc/Resolution_AICT_2025.pdf", "Resolution_AICT_2025.pdf"),
    ("doc/info_aist_2023.pdf", "info_aist_2023.pdf"),
    ("doc/PROGRAMMA_AIST_2023.pdf", "PROGRAMMA_AIST_2023.pdf"),
    ("doc/Abstract_AICT_2023.pdf", "Abstract_AICT_2023.pdf"),
    ("doc/Sbornic_2023.pdf", "Sbornic_2023.pdf"),
    ("doc/info_aist_2021.pdf", "info_aist_2021.pdf"),
    ("doc/info_aist_2021_eng.pdf", "info_aist_2021_eng.pdf"),
    ("doc/PROGRAMMA_AIST_2021.pdf", "PROGRAMMA_AIST_2021.pdf"),
    ("doc/Abstract_AICT_2021.pdf", "Abstract_AICT_2021.pdf"),
    ("aist/doc/Sbornic_2021.pdf", "Sbornic_2021.pdf"),
    ("doc/info_aist_2019.doc", "info_aist_2019.doc"),
    ("doc/PROGRAMMA_AIST_2019.doc", "PROGRAMMA_AIST_2019.doc"),
    ("doc/Abstract_AICT_2019.pdf", "Abstract_AICT_2019.pdf"),
    ("doc/Sbornic_2019.pdf", "Sbornic_2019.pdf"),
    ("doc/Abstract_AICT_2017.pdf", "Abstract_AICT_2017.pdf"),
    ("doc/Sbornic_2017.pdf", "Sbornic_2017.pdf"),
    ("doc/Abstract_AICT_2015.pdf", "Abstract_AICT_2015.pdf"),
    ("aist/doc/Sbornic_2015.pdf", "Sbornic_2015.pdf"),
    ("doc/Resolution_AICT_2015.doc", "Resolution_AICT_2015.doc"),
    ("doc/PROGRAMMA_AIST_2013.doc", "PROGRAMMA_AIST_2013.doc"),
    ("doc/Abstract_AICT_2013.pdf", "Abstract_AICT_2013.pdf"),
    ("doc/Sbornic_2013.pdf", "Sbornic_2013.pdf"),
    ("doc/info_aist_2011.doc", "info_aist_2011.doc"),
    ("doc/PROGRAMMA_AIST_2011.doc", "PROGRAMMA_AIST_2011.doc"),
    ("doc/Abstract_AICT_2011.pdf", "Abstract_AICT_2011.pdf"),
    ("doc/PROGRAMMA_AIST_2009.doc", "PROGRAMMA_AIST_2009.doc"),
)


def _get(url: str) -> bytes:
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=90) as response:
        return response.read()


def main() -> int:
    DEST.mkdir(parents=True, exist_ok=True)
    manifest: list[dict[str, object]] = []
    failed = 0
    for remote, name in FILES:
        url = urllib.request.urljoin(BASE, remote)
        dest = DEST / name
        try:
            data = _get(url)
        except urllib.error.URLError as exc:
            print(f"FAIL {url}: {exc}", file=sys.stderr)
            failed += 1
            continue
        dest.write_bytes(data)
        row = {
            "file": name,
            "source": url,
            "bytes": len(data),
        }
        manifest.append(row)
        print(f"OK {len(data):9}  {name}")
    (DEST / "manifest.json").write_text(
        json.dumps(
            {
                "source": BASE.rstrip("/"),
                "fetched": "2026-09-01",
                "files": manifest,
            },
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )
    print(f"Wrote {len(manifest)} files to {DEST} ({failed} failed)")
    return 1 if failed else 0


if __name__ == "__main__":
    raise SystemExit(main())

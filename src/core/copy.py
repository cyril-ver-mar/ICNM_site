from __future__ import annotations

from functools import cache
from pathlib import Path
from typing import Any
import json


def migrated_copy_path() -> Path:
    return Path(__file__).resolve().parent / "migrated_copy.json"


@cache
def load_migrated_copy() -> dict[str, Any]:
    return json.loads(migrated_copy_path().read_text(encoding="utf-8"))


def page_copy(page_id: str) -> dict[str, Any]:
    pages = load_migrated_copy().get("pages", {})
    return dict(pages.get(page_id, {}))

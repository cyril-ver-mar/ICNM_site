from __future__ import annotations

from contextlib import contextmanager
from contextvars import ContextVar
from functools import cache
from pathlib import Path
from typing import Any, Iterator
import json

_override: ContextVar[dict[str, Any] | None] = ContextVar("migrated_override", default=None)


def migrated_copy_path() -> Path:
    return Path(__file__).resolve().parent / "migrated_copy.json"


@cache
def _from_disk() -> dict[str, Any]:
    return json.loads(migrated_copy_path().read_text(encoding="utf-8"))


def disk_copy() -> dict[str, Any]:
    """Copy as stored on disk (honest empty slots). Ignores the filled override."""
    return _from_disk()


def load_migrated_copy() -> dict[str, Any]:
    override = _override.get()
    if override is not None:
        return override
    return _from_disk()


def page_copy(page_id: str) -> dict[str, Any]:
    pages = load_migrated_copy().get("pages", {})
    return dict(pages.get(page_id, {}))


def is_filled_copy() -> bool:
    return bool(load_migrated_copy().get("_filled"))


@contextmanager
def use_copy(data: dict[str, Any]) -> Iterator[None]:
    token = _override.set(data)
    try:
        yield
    finally:
        _override.reset(token)

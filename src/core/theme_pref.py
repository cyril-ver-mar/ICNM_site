"""Theme preference: day | night only; clock sets default."""

from __future__ import annotations

THEME_MODES: tuple[str, ...] = ("day", "night")


def night_by_clock(hour: int) -> bool:
    """Night window matches chrome.js: 21:00–06:59 local."""
    return hour >= 21 or hour < 7


def default_from_local_clock(hour: int) -> str:
    """Persisted-preference default when storage is empty."""
    return "night" if night_by_clock(hour) else "day"


def normalize_mode(raw: str | None) -> str | None:
    """Return day|night, or None when unset / legacy auto / unknown."""
    if raw in THEME_MODES:
        return raw
    return None


def cycle_mode(current: str | None) -> str:
    """User toggle: day ↔ night only (no auto)."""
    if current == "night":
        return "day"
    return "night"


def effective_night(mode: str | None, hour: int) -> bool:
    """Resolve night class from preference or clock default."""
    resolved = normalize_mode(mode)
    if resolved is None:
        resolved = default_from_local_clock(hour)
    return resolved == "night"

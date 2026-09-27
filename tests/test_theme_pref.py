from src.core.theme_pref import (
    cycle_mode,
    default_from_local_clock,
    effective_night,
    normalize_mode,
    night_by_clock,
)


def test_night_by_clock_window() -> None:
    assert night_by_clock(21) is True
    assert night_by_clock(3) is True
    assert night_by_clock(7) is False
    assert night_by_clock(12) is False
    assert night_by_clock(20) is False


def test_default_from_local_clock() -> None:
    assert default_from_local_clock(22) == "night"
    assert default_from_local_clock(10) == "day"


def test_normalize_drops_auto() -> None:
    assert normalize_mode("day") == "day"
    assert normalize_mode("night") == "night"
    assert normalize_mode("auto") is None
    assert normalize_mode(None) is None
    assert normalize_mode("bogus") is None


def test_cycle_day_night_only() -> None:
    assert cycle_mode("day") == "night"
    assert cycle_mode("night") == "day"
    assert cycle_mode(None) == "night"
    assert cycle_mode("auto") == "night"


def test_effective_night_modes() -> None:
    assert effective_night("day", 23) is False
    assert effective_night("night", 10) is True
    assert effective_night(None, 22) is True
    assert effective_night(None, 10) is False
    assert effective_night("auto", 22) is True  # treated as unset → clock
    assert effective_night("auto", 10) is False

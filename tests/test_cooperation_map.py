"""Cooperation map contracts: partners + Natural Earth outline (ticket 12)."""

from __future__ import annotations

from src.core.cooperation_map import (
    WORLD_SVG_PATH,
    partner_pins,
    world_map_html,
    world_outline_markup,
)
from src.core.copy import load_migrated_copy


def test_partners_have_pin_coords_and_notes():
    pins = partner_pins(load_migrated_copy())
    assert len(pins) >= 5
    for pin in pins:
        assert pin["slug"]
        assert pin["title"]
        assert 0.0 <= pin["x"] <= 100.0
        assert 0.0 <= pin["y"] <= 100.0
        assert pin["note"]
        assert pin["place"]


def test_world_svg_is_natural_earth_outline_not_raster():
    assert WORLD_SVG_PATH.is_file()
    raw = WORLD_SVG_PATH.read_text(encoding="utf-8")
    assert "world-outline" in raw
    assert "world-ocean" in raw
    assert "world-land" in raw
    assert "openstreetmap" not in raw.lower()
    assert "google" not in raw.lower()
    assert ".jpg" not in raw.lower()
    assert ".png" not in raw.lower()


def test_world_map_html_inlines_svg_pins_and_photo_slots():
    pins = partner_pins(load_migrated_copy())
    html = world_map_html(pins, outline=world_outline_markup())
    assert 'class="ichnm-world-map"' in html
    assert "world-outline" in html
    assert "world-ocean" in html
    assert "world-land" in html
    assert "<img" not in html
    assert "photo-slot" in html
    assert "Борескова" in html
    assert "ichnm-map-pin" in html
    assert "world.jpg" not in html


def test_world_map_html_upper_pins_open_pop_below():
    """Ticket 21: pins in the upper half get data-pop=below so cards stay in frame."""
    outline = '<svg class="world-outline"></svg>'
    html = world_map_html(
        [
            {
                "slug": "high",
                "title": "High pin",
                "place": "North",
                "note": "Upper half",
                "x": 50.0,
                "y": 19.4,
            },
            {
                "slug": "low",
                "title": "Low pin",
                "place": "South",
                "note": "Lower half",
                "x": 50.0,
                "y": 55.0,
            },
        ],
        outline=outline,
    )
    assert 'data-pop="below"' in html
    assert 'class="ichnm-map-hotspot" data-pop="below" style="left:50.0%;top:19.4%"' in html
    assert 'class="ichnm-map-hotspot" style="left:50.0%;top:55.0%"' in html
    assert html.count('data-pop="below"') == 1

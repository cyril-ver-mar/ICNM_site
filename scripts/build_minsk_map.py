#!/usr/bin/python3
"""Build a themeable Minsk outline SVG (same class as the world map)."""

from __future__ import annotations

import json
import math
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "assets" / "maps" / "minsk.svg"

# Institute of Chemistry of New Materials, 36 F. Skaryna St. (OSM research node)
PIN_LAT = 53.9254
PIN_LON = 27.69835

W, H = 800, 500
PAD = 32


LAT0 = 53.90  # Minsk; keep the city outline from stretching east–west


def project(lon: float, lat: float) -> tuple[float, float]:
    return lon * math.cos(math.radians(LAT0)), lat


def rdp(points: list[tuple[float, float]], epsilon: float) -> list[tuple[float, float]]:
    if len(points) < 3:
        return points
    start, end = points[0], points[-1]
    dx, dy = end[0] - start[0], end[1] - start[1]
    length = math.hypot(dx, dy) or 1e-12
    max_d, idx = 0.0, 0
    for i in range(1, len(points) - 1):
        px, py = points[i]
        dist = abs(dx * (start[1] - py) - (start[0] - px) * dy) / length
        if dist > max_d:
            max_d, idx = dist, i
    if max_d > epsilon:
        left = rdp(points[: idx + 1], epsilon)
        right = rdp(points[idx:], epsilon)
        return left[:-1] + right
    return [start, end]


def closed_rdp(ring: list[tuple[float, float]], epsilon: float) -> list[tuple[float, float]]:
    pts = ring[:-1] if len(ring) > 1 and ring[0] == ring[-1] else list(ring)
    simple = rdp(pts, epsilon)
    if len(simple) < 3:
        return ring
    if simple[0] != simple[-1]:
        simple.append(simple[0])
    return simple


def path_d(ring: list[tuple[float, float]]) -> str:
    parts = []
    for i, (x, y) in enumerate(ring):
        cmd = "M" if i == 0 else "L"
        parts.append(f"{cmd}{x:.1f} {y:.1f}")
    parts.append("Z")
    return "".join(parts)


def line_d(pts: list[tuple[float, float]]) -> str:
    parts = []
    for i, (x, y) in enumerate(pts):
        cmd = "M" if i == 0 else "L"
        parts.append(f"{cmd}{x:.1f} {y:.1f}")
    return "".join(parts)


def outer_rings_from_geojson(geom: dict) -> list[list[list[float]]]:
    kind = geom.get("type")
    coords = geom.get("coordinates") or []
    if kind == "Polygon" and coords:
        return [coords[0]]
    if kind == "MultiPolygon":
        return [poly[0] for poly in coords if poly]
    return []


def main() -> None:
    city = json.loads(Path("/tmp/minsk_city.json").read_text())
    waters = json.loads(Path("/tmp/minsk_water.json").read_text())
    river = json.loads(Path("/tmp/minsk_svisloch.json").read_text())

    outer = city["coordinates"][0][0]
    mx = [project(p[0], p[1]) for p in outer]
    minx, maxx = min(p[0] for p in mx), max(p[0] for p in mx)
    miny, maxy = min(p[1] for p in mx), max(p[1] for p in mx)
    scale = min((W - 2 * PAD) / (maxx - minx), (H - 2 * PAD) / (maxy - miny))
    cx, cy = (minx + maxx) / 2, (miny + maxy) / 2

    def xy(lon: float, lat: float) -> tuple[float, float]:
        x, y = project(lon, lat)
        return W / 2 + (x - cx) * scale, H / 2 - (y - cy) * scale

    def proj_ring(ring: list[list[float]], epsilon: float) -> list[tuple[float, float]]:
        pts = [xy(p[0], p[1]) for p in ring]
        return closed_rdp(pts, epsilon)

    land_parts = [path_d(proj_ring(outer, 2.2))]

    water_paths = []
    for geom in waters.values():
        for ring in outer_rings_from_geojson(geom):
            if len(ring) < 8:
                continue
            water_paths.append(path_d(proj_ring(ring, 1.1)))

    river_paths = []
    for el in river.get("elements", []):
        geom = el.get("geometry") or []
        if len(geom) < 4:
            continue
        pts = [xy(p["lon"], p["lat"]) for p in geom]
        simple = rdp(pts, 1.15)
        if len(simple) >= 2:
            river_paths.append(line_d(simple))

    pin = xy(PIN_LON, PIN_LAT)
    pin_left = pin[0] / W * 100
    pin_top = pin[1] / H * 100

    water_svg = "\n    ".join(f'<path d="{d}"/>' for d in water_paths)
    river_svg = "\n    ".join(f'<path d="{d}"/>' for d in river_paths)
    land_d = "".join(land_parts)

    svg = f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" class="world-outline" preserveAspectRatio="xMidYMid meet" aria-hidden="true" data-pin-left="{pin_left:.2f}" data-pin-top="{pin_top:.2f}">
  <rect class="world-ocean" width="{W}" height="{H}"/>
  <g class="world-land" fill-rule="evenodd">
    <path d="{land_d}"/>
  </g>
  <g class="city-water">
    {water_svg}
  </g>
  <g class="city-river">
    {river_svg}
  </g>
</svg>
"""
    OUT.write_text(svg, encoding="utf-8")
    print(f"Wrote {OUT} ({OUT.stat().st_size} bytes)")
    print(f"pin left={pin_left:.2f}% top={pin_top:.2f}%")
    print(f"land subpaths={len(land_parts)} water={len(water_paths)} river={len(river_paths)}")


if __name__ == "__main__":
    main()

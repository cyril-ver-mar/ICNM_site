#!/usr/bin/python3
"""Build themeable Minsk outline SVG variants for preview picking.

Sources (ODbL): Nominatim / OSM polygons in assets/maps/source/.
Default production file stays assets/maps/minsk.svg (compact land, water, river).
Variants live in assets/maps/variants/ for side-by-side review.
"""

from __future__ import annotations

import json
import math
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "assets" / "maps" / "source"
OUT = ROOT / "assets" / "maps" / "minsk.svg"
VAR = ROOT / "assets" / "maps" / "variants"
PREVIEW_MAPS = ROOT / "preview" / "media" / "maps"
PREVIEW_FILLED_MAPS = ROOT / "preview-filled" / "media" / "maps"

# Institute of Chemistry of New Materials, 36 F. Skaryna St.
PIN_LAT = 53.9254
PIN_LON = 27.69835

W, H = 800, 500
PAD = 32
LAT0 = 53.90

# Drop admin exclaves + Uruchye east lobe (white boundary on OSM/Yandex).
EAST_CLIP_LON = 27.725


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


def largest_outer_ring(geom: dict) -> list[list[float]]:
    kind = geom.get("type")
    coords = geom.get("coordinates") or []
    if kind == "Polygon" and coords:
        return coords[0]
    if kind == "MultiPolygon" and coords:
        return max((poly[0] for poly in coords if poly), key=len)
    return []


def clip_east(ring: list[list[float]], east_lon: float) -> list[list[float]]:
    """Pull eastern admin lobes back to a meridian (drop Uruchye / exclaves feel)."""
    if len(ring) < 4:
        return ring
    pts = ring[:-1] if ring[0] == ring[-1] else list(ring)
    out: list[list[float]] = []
    for lon, lat in pts:
        out.append([min(lon, east_lon), lat])
    # Collapse runs of identical clipped points.
    dedup: list[list[float]] = []
    for p in out:
        if not dedup or (abs(dedup[-1][0] - p[0]) > 1e-7 or abs(dedup[-1][1] - p[1]) > 1e-7):
            dedup.append(p)
    if dedup[0] != dedup[-1]:
        dedup.append(dedup[0])
    return dedup


def shrink_ring(ring: list[tuple[float, float]], factor: float) -> list[tuple[float, float]]:
    pts = ring[:-1] if ring and ring[0] == ring[-1] else list(ring)
    if len(pts) < 3:
        return ring
    cx = sum(p[0] for p in pts) / len(pts)
    cy = sum(p[1] for p in pts) / len(pts)
    shrunk = [(cx + (x - cx) * factor, cy + (y - cy) * factor) for x, y in pts]
    shrunk.append(shrunk[0])
    return shrunk


def iter_polygons(geom: dict):
    kind = geom.get("type")
    coords = geom.get("coordinates") or []
    if kind == "Polygon" and coords:
        yield coords[0]
    elif kind == "MultiPolygon":
        for poly in coords:
            if poly:
                yield poly[0]


def write_svg(
    path: Path,
    *,
    land_d: str,
    water_paths: list[str],
    river_paths: list[str],
    mkad_d: str | None,
    green_paths: list[str],
    pin_left: float,
    pin_top: float,
    include_mkad: bool,
    include_green: bool,
) -> None:
    water_svg = "\n    ".join(f'<path d="{d}"/>' for d in water_paths)
    river_svg = "\n    ".join(f'<path d="{d}"/>' for d in river_paths)
    green_svg = "\n    ".join(f'<path d="{d}"/>' for d in green_paths)
    mkad_svg = f'  <g class="city-mkad">\n    <path d="{mkad_d}"/>\n  </g>\n' if include_mkad and mkad_d else ""
    green_block = f'  <g class="city-green">\n    {green_svg}\n  </g>\n' if include_green and green_paths else ""
    svg = f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" class="world-outline" preserveAspectRatio="xMidYMid meet" aria-hidden="true" data-pin-left="{pin_left:.2f}" data-pin-top="{pin_top:.2f}">
  <rect class="world-ocean" width="{W}" height="{H}"/>
  <g class="world-land" fill-rule="evenodd">
    <path d="{land_d}"/>
  </g>
{green_block}  <g class="city-water">
    {water_svg}
  </g>
  <g class="city-river">
    {river_svg}
  </g>
{mkad_svg}</svg>
"""
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(svg, encoding="utf-8")
    print(f"Wrote {path.relative_to(ROOT)} ({path.stat().st_size} bytes)")


def main() -> None:
    city = json.loads((SRC / "city.geojson").read_text(encoding="utf-8"))
    greens = json.loads((SRC / "greens.geojson").read_text(encoding="utf-8"))

    city_geom = city["features"][0]["geometry"]
    admin_outer = largest_outer_ring(city_geom)
    compact_outer = clip_east(admin_outer, EAST_CLIP_LON)

    # Frame from full admin extent so the pin stays near its familiar place;
    # land fill uses the compact ring (eastern lobes become ocean).
    mx = [project(p[0], p[1]) for p in admin_outer]
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

    admin_land = path_d(proj_ring(admin_outer, 2.2))
    compact_pts = proj_ring(compact_outer, 2.2)
    compact_land = path_d(compact_pts)

    # Approximate MKAD as a smoothed inset of the compact city ring (Overpass flaky).
    mkad_pts = closed_rdp(shrink_ring(compact_pts, 0.93), 3.0)
    mkad_d = path_d(mkad_pts)

    water_paths: list[str] = []
    green_paths: list[str] = []
    for feat in greens.get("features", []):
        props = feat.get("properties") or {}
        geom = feat.get("geometry") or {}
        q = str(props.get("query") or props.get("display_name") or "").lower()
        name = str(props.get("display_name") or "").lower()
        is_water = any(
            token in q or token in name
            for token in ("водохран", "вадасхов", "дразды", "цнян", "заслав", "чыжоў", "чижов")
        )
        if geom.get("type") in {"Point", "MultiPoint", "LineString", "MultiLineString"}:
            continue
        for ring in iter_polygons(geom):
            if len(ring) < 6:
                continue
            d = path_d(proj_ring(ring, 1.4 if is_water else 2.0))
            if is_water:
                water_paths.append(d)
            else:
                green_paths.append(d)

    # Keep a short Svisloch-ish polyline from existing reservoirs midpoints if river missing.
    river_paths: list[str] = []
    svisloch_path = SRC / "svisloch.geojson"
    if svisloch_path.is_file():
        svis = json.loads(svisloch_path.read_text(encoding="utf-8"))
        for feat in svis.get("features", []):
            geom = feat.get("geometry") or {}
            coords = geom.get("coordinates") or []
            lines = [coords] if geom.get("type") == "LineString" else coords
            for line in lines:
                # Keep only the city-bounded segment.
                clipped = [p for p in line if 27.40 <= p[0] <= 27.78 and 53.82 <= p[1] <= 53.98]
                if len(clipped) < 4:
                    continue
                pts = [xy(p[0], p[1]) for p in clipped]
                simple = rdp(pts, 1.2)
                if len(simple) >= 2:
                    river_paths.append(line_d(simple))

    # Same projection as the previous production SVG (pin 65.66/29.13) — reuse river/water.
    if not river_paths and (SRC / "legacy_river.paths").is_file():
        river_paths = [
            line.strip()
            for line in (SRC / "legacy_river.paths").read_text(encoding="utf-8").splitlines()
            if line.strip()
        ]
    if not water_paths and (SRC / "legacy_water.paths").is_file():
        water_paths = [
            line.strip()
            for line in (SRC / "legacy_water.paths").read_text(encoding="utf-8").splitlines()
            if line.strip()
        ]
    elif (SRC / "legacy_water.paths").is_file() and len(water_paths) < 2:
        # Prefer named reservoirs when present; otherwise fall back to legacy set.
        legacy = [
            line.strip()
            for line in (SRC / "legacy_water.paths").read_text(encoding="utf-8").splitlines()
            if line.strip()
        ]
        water_paths = list(dict.fromkeys(water_paths + legacy))

    pin = xy(PIN_LON, PIN_LAT)
    pin_left = pin[0] / W * 100
    pin_top = pin[1] / H * 100

    VAR.mkdir(parents=True, exist_ok=True)

    variants = [
        ("minsk-v1-admin.svg", admin_land, False, False, "admin boundary (with eastern lobes)"),
        ("minsk-v2-compact.svg", compact_land, False, False, "compact land (east clipped)"),
        ("minsk-v3-mkad.svg", compact_land, True, False, "compact + MKAD stroke"),
        ("minsk-v4-green.svg", compact_land, False, True, "compact + parks / woods"),
        ("minsk-v5-mkad-green.svg", compact_land, True, True, "compact + MKAD + greens"),
    ]

    meta = []
    for name, land, use_mkad, use_green, label in variants:
        out = VAR / name
        write_svg(
            out,
            land_d=land,
            water_paths=water_paths,
            river_paths=river_paths,
            mkad_d=mkad_d,
            green_paths=green_paths,
            pin_left=pin_left,
            pin_top=pin_top,
            include_mkad=use_mkad,
            include_green=use_green,
        )
        meta.append({"file": name, "label": label})

    # Production default: compact (no eastern lobe), water/river only — pick later if user prefers layers.
    write_svg(
        OUT,
        land_d=compact_land,
        water_paths=water_paths,
        river_paths=river_paths,
        mkad_d=mkad_d,
        green_paths=green_paths,
        pin_left=pin_left,
        pin_top=pin_top,
        include_mkad=False,
        include_green=False,
    )

    for dest_root in (PREVIEW_MAPS, PREVIEW_FILLED_MAPS):
        if not dest_root.parent.is_dir():
            continue
        dest_root.mkdir(parents=True, exist_ok=True)
        shutil.copy2(OUT, dest_root / "minsk.svg")
        for name, *_ in variants:
            shutil.copy2(VAR / name, dest_root / name)

    (VAR / "README.md").write_text(
        "# Minsk map variants\n\n"
        "Open `preview/minsk-map-variants.html` and pick a look.\n\n"
        + "\n".join(f"- `{m['file']}` — {m['label']}" for m in meta)
        + "\n\nRebuild: `python3 scripts/build_minsk_map.py`\n",
        encoding="utf-8",
    )
    print(f"pin left={pin_left:.2f}% top={pin_top:.2f}%")
    print(f"water={len(water_paths)} green={len(green_paths)} river={len(river_paths)}")


if __name__ == "__main__":
    main()

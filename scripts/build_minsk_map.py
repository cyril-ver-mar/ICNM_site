#!/usr/bin/python3
"""Build themeable Minsk outline SVG variants for preview picking.

Sources (ODbL): Nominatim / OSM polygons in assets/maps/source/.
Hand greens: assets/maps/source/greens_hand.geojson (digitized from user annotation).
Default production: revised land (no eastern forest belt) + OSM greens + hand greens.

Eastern forest belt (user blue zone on the annotation): forests east of MKAD
(Kolodishchi / Stiklevo / Trostenets) that are in the admin city but should not
appear on the schematic city silhouette. Cut at approx eastern MKAD.
"""

from __future__ import annotations

import json
import math
import re
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

# User blue zone: eastern forests beyond MKAD (Kolodishchi–Stiklevo–Trostenets).
# ~eastern MKAD lon; Uruchye (~27.69) stays west of the cut.
EAST_FOREST_CUT_LON = 27.705
EAST_FOREST_LAT = (53.810, 53.970)


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
    """Main city ring only — distant eastern exclaves (poly1/poly2) stay out."""
    kind = geom.get("type")
    coords = geom.get("coordinates") or []
    if kind == "Polygon" and coords:
        return coords[0]
    if kind == "MultiPolygon" and coords:
        return max((poly[0] for poly in coords if poly), key=len)
    return []


def remove_eastern_forest_belt(
    ring: list[list[float]],
    cut_lon: float = EAST_FOREST_CUT_LON,
    lat_range: tuple[float, float] = EAST_FOREST_LAT,
) -> list[list[float]]:
    """Drop the eastern forest belt (user blue zone) past eastern MKAD.

    Points inside the blue-zone latitude band with lon > cut_lon are pulled to
    cut_lon so the silhouette follows the ring-road edge instead of admin forests.
    """
    if len(ring) < 4:
        return ring
    pts = ring[:-1] if ring[0] == ring[-1] else list(ring)
    lo, hi = lat_range
    out: list[list[float]] = []
    for lon, lat in pts:
        if lo <= lat <= hi and lon > cut_lon:
            out.append([cut_lon, lat])
        else:
            out.append([lon, lat])
    dedup: list[list[float]] = []
    for p in out:
        if not dedup or abs(dedup[-1][0] - p[0]) > 1e-7 or abs(dedup[-1][1] - p[1]) > 1e-7:
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


def point_in_ring(lon: float, lat: float, ring: list[list[float]]) -> bool:
    pts = ring[:-1] if ring and ring[0] == ring[-1] else ring
    inside = False
    n = len(pts)
    j = n - 1
    for i in range(n):
        xi, yi = pts[i][0], pts[i][1]
        xj, yj = pts[j][0], pts[j][1]
        if ((yi > lat) != (yj > lat)) and (
            lon < (xj - xi) * (lat - yi) / ((yj - yi) or 1e-15) + xi
        ):
            inside = not inside
        j = i
    return inside


def ring_centroid(ring: list[list[float]]) -> tuple[float, float]:
    pts = ring[:-1] if ring and ring[0] == ring[-1] else ring
    if not pts:
        return 0.0, 0.0
    return sum(p[0] for p in pts) / len(pts), sum(p[1] for p in pts) / len(pts)


def collect_green_paths(
    fc: dict,
    *,
    revised_outer: list[list[float]],
    proj_ring,
    require_inside: bool = True,
    default_eps: float = 2.0,
) -> list[str]:
    paths: list[str] = []
    for feat in fc.get("features", []):
        props = feat.get("properties") or {}
        geom = feat.get("geometry") or {}
        kind = str(props.get("kind") or props.get("type") or "").lower()
        if kind in {"reservoir", "canal", "water"}:
            continue
        if geom.get("type") in {"Point", "MultiPoint", "LineString", "MultiLineString"}:
            continue
        for ring in iter_polygons(geom):
            if len(ring) < 4:
                continue
            if require_inside:
                clon, clat = ring_centroid(ring)
                if not point_in_ring(clon, clat, revised_outer):
                    continue
            eps = 1.6 if kind in {"park", "garden", "nature_reserve", "hand"} else default_eps
            paths.append(path_d(proj_ring(ring, eps)))
    return paths


def write_svg(
    path: Path,
    *,
    land_d: str,
    water_paths: list[str],
    river_paths: list[str],
    mkad_d: str | None,
    green_osm_paths: list[str],
    green_hand_paths: list[str],
    pin_left: float,
    pin_top: float,
    include_mkad: bool,
    include_green_osm: bool,
    include_green_hand: bool,
) -> None:
    water_svg = "\n    ".join(f'<path d="{d}"/>' for d in water_paths)
    river_svg = "\n    ".join(f'<path d="{d}"/>' for d in river_paths)
    osm_svg = "\n    ".join(f'<path d="{d}"/>' for d in green_osm_paths)
    hand_svg = "\n    ".join(f'<path d="{d}"/>' for d in green_hand_paths)
    mkad_svg = (
        f'  <g class="city-mkad">\n    <path d="{mkad_d}"/>\n  </g>\n'
        if include_mkad and mkad_d
        else ""
    )
    green_blocks = ""
    if include_green_osm and green_osm_paths:
        green_blocks += f'  <g class="city-green city-green-osm">\n    {osm_svg}\n  </g>\n'
    if include_green_hand and green_hand_paths:
        green_blocks += f'  <g class="city-green city-green-hand">\n    {hand_svg}\n  </g>\n'
    svg = f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" class="world-outline" preserveAspectRatio="xMidYMid meet" aria-hidden="true" data-pin-left="{pin_left:.2f}" data-pin-top="{pin_top:.2f}">
  <rect class="world-ocean" width="{W}" height="{H}"/>
  <g class="world-land" fill-rule="evenodd">
    <path d="{land_d}"/>
  </g>
{green_blocks}  <g class="city-water">
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


_PATH_TOKEN = re.compile(
    r"[MmLlHhVvCcSsQqTtAaZz]|[-+]?(?:\d*\.\d+|\d+)(?:[eE][-+]?\d+)?"
)


def _svg_path_points(d: str) -> list[tuple[float, float]]:
    """Walk an SVG path into absolute points (enough for Illustrator land/water)."""
    tokens = _PATH_TOKEN.findall(d)
    i = 0
    pts: list[tuple[float, float]] = []
    cx = cy = 0.0
    startx = starty = 0.0
    last_cmd = ""

    def num() -> float:
        nonlocal i
        v = float(tokens[i])
        i += 1
        return v

    while i < len(tokens):
        t = tokens[i]
        if re.match(r"[A-Za-z]", t):
            cmd = t
            i += 1
        else:
            cmd = last_cmd
            if cmd == "M":
                cmd = "L"
            elif cmd == "m":
                cmd = "l"
        last_cmd = cmd
        if cmd == "M":
            cx, cy = num(), num()
            startx, starty = cx, cy
            pts.append((cx, cy))
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx, cy = num(), num()
                pts.append((cx, cy))
                last_cmd = "L"
        elif cmd == "m":
            cx += num()
            cy += num()
            startx, starty = cx, cy
            pts.append((cx, cy))
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx += num()
                cy += num()
                pts.append((cx, cy))
                last_cmd = "l"
        elif cmd == "L":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx, cy = num(), num()
                pts.append((cx, cy))
        elif cmd == "l":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx += num()
                cy += num()
                pts.append((cx, cy))
        elif cmd == "H":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx = num()
                pts.append((cx, cy))
        elif cmd == "h":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx += num()
                pts.append((cx, cy))
        elif cmd == "V":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cy = num()
                pts.append((cx, cy))
        elif cmd == "v":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cy += num()
                pts.append((cx, cy))
        elif cmd in ("C", "c"):
            rel = cmd == "c"
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                coords = [num() for _ in range(6)]
                if rel:
                    pts.append((cx + coords[0], cy + coords[1]))
                    pts.append((cx + coords[2], cy + coords[3]))
                    cx += coords[4]
                    cy += coords[5]
                else:
                    pts.append((coords[0], coords[1]))
                    pts.append((coords[2], coords[3]))
                    cx, cy = coords[4], coords[5]
                pts.append((cx, cy))
        elif cmd in ("S", "s", "Q", "q"):
            n = 4
            rel = cmd.islower()
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                coords = [num() for _ in range(n)]
                if rel:
                    for k in range(0, n - 2, 2):
                        pts.append((cx + coords[k], cy + coords[k + 1]))
                    cx += coords[-2]
                    cy += coords[-1]
                else:
                    for k in range(0, n - 2, 2):
                        pts.append((coords[k], coords[k + 1]))
                    cx, cy = coords[-2], coords[-1]
                pts.append((cx, cy))
        elif cmd in ("T", "t"):
            rel = cmd == "t"
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                if rel:
                    cx += num()
                    cy += num()
                else:
                    cx, cy = num(), num()
                pts.append((cx, cy))
        elif cmd in ("A", "a"):
            rel = cmd == "a"
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                for _ in range(5):
                    num()
                if rel:
                    cx += num()
                    cy += num()
                else:
                    cx, cy = num(), num()
                pts.append((cx, cy))
        elif cmd in ("Z", "z"):
            cx, cy = startx, starty
            pts.append((cx, cy))
        else:
            raise RuntimeError(f"unknown SVG path command {cmd!r}")
    return pts


def _rewrite_path_uniform(d: str, scale: float, ox: float, oy: float) -> str:
    tokens = _PATH_TOKEN.findall(d)
    i = 0
    out: list[str] = []
    cx = cy = 0.0
    startx = starty = 0.0
    last_cmd = ""

    def num() -> float:
        nonlocal i
        v = float(tokens[i])
        i += 1
        return v

    def T(x: float, y: float) -> tuple[float, float]:
        return ox + x * scale, oy + y * scale

    while i < len(tokens):
        t = tokens[i]
        if re.match(r"[A-Za-z]", t):
            cmd = t
            i += 1
        else:
            cmd = last_cmd
            if cmd == "M":
                cmd = "L"
            elif cmd == "m":
                cmd = "l"
        last_cmd = cmd
        if cmd == "M":
            cx, cy = num(), num()
            startx, starty = cx, cy
            x, y = T(cx, cy)
            out.append(f"M{x:.1f} {y:.1f}")
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx, cy = num(), num()
                x, y = T(cx, cy)
                out.append(f"L{x:.1f} {y:.1f}")
                last_cmd = "L"
        elif cmd == "m":
            cx += num()
            cy += num()
            startx, starty = cx, cy
            x, y = T(cx, cy)
            out.append(f"M{x:.1f} {y:.1f}")
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx += num()
                cy += num()
                x, y = T(cx, cy)
                out.append(f"L{x:.1f} {y:.1f}")
                last_cmd = "l"
        elif cmd == "L":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx, cy = num(), num()
                x, y = T(cx, cy)
                out.append(f"L{x:.1f} {y:.1f}")
        elif cmd == "l":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx += num()
                cy += num()
                x, y = T(cx, cy)
                out.append(f"L{x:.1f} {y:.1f}")
        elif cmd == "H":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx = num()
                x, y = T(cx, cy)
                out.append(f"L{x:.1f} {y:.1f}")
        elif cmd == "h":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cx += num()
                x, y = T(cx, cy)
                out.append(f"L{x:.1f} {y:.1f}")
        elif cmd == "V":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cy = num()
                x, y = T(cx, cy)
                out.append(f"L{x:.1f} {y:.1f}")
        elif cmd == "v":
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                cy += num()
                x, y = T(cx, cy)
                out.append(f"L{x:.1f} {y:.1f}")
        elif cmd in ("C", "c"):
            rel = cmd == "c"
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                coords = [num() for _ in range(6)]
                if rel:
                    pts = [
                        (cx + coords[0], cy + coords[1]),
                        (cx + coords[2], cy + coords[3]),
                        (cx + coords[4], cy + coords[5]),
                    ]
                    cx, cy = pts[2]
                else:
                    pts = [
                        (coords[0], coords[1]),
                        (coords[2], coords[3]),
                        (coords[4], coords[5]),
                    ]
                    cx, cy = pts[2]
                tp = [T(*q) for q in pts]
                out.append("C" + " ".join(f"{a:.1f} {b:.1f}" for a, b in tp))
        elif cmd in ("S", "s"):
            rel = cmd == "s"
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                coords = [num() for _ in range(4)]
                if rel:
                    pts = [
                        (cx + coords[0], cy + coords[1]),
                        (cx + coords[2], cy + coords[3]),
                    ]
                    cx, cy = pts[1]
                else:
                    pts = [(coords[0], coords[1]), (coords[2], coords[3])]
                    cx, cy = pts[1]
                tp = [T(*q) for q in pts]
                out.append("S" + " ".join(f"{a:.1f} {b:.1f}" for a, b in tp))
        elif cmd in ("Q", "q"):
            rel = cmd == "q"
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                coords = [num() for _ in range(4)]
                if rel:
                    pts = [
                        (cx + coords[0], cy + coords[1]),
                        (cx + coords[2], cy + coords[3]),
                    ]
                    cx, cy = pts[1]
                else:
                    pts = [(coords[0], coords[1]), (coords[2], coords[3])]
                    cx, cy = pts[1]
                tp = [T(*q) for q in pts]
                out.append("Q" + " ".join(f"{a:.1f} {b:.1f}" for a, b in tp))
        elif cmd in ("T", "t"):
            rel = cmd == "t"
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                if rel:
                    cx += num()
                    cy += num()
                else:
                    cx, cy = num(), num()
                x, y = T(cx, cy)
                out.append(f"T{x:.1f} {y:.1f}")
        elif cmd in ("A", "a"):
            rel = cmd == "a"
            while i < len(tokens) and not re.match(r"[A-Za-z]", tokens[i]):
                for _ in range(5):
                    num()
                if rel:
                    cx += num()
                    cy += num()
                else:
                    cx, cy = num(), num()
                x, y = T(cx, cy)
                out.append(f"L{x:.1f} {y:.1f}")
        elif cmd in ("Z", "z"):
            cx, cy = startx, starty
            out.append("Z")
        else:
            raise RuntimeError(f"unknown SVG path command {cmd!r}")
    return "".join(out)


def promote_hand_final(hand_path: Path, dest: Path, *, pad: float = 16.0, width: int = 800) -> tuple[float, float, int]:
    """Uniform fill-width crop of Illustrator hand SVG → production minsk.svg.

    Returns (pin_left_pct, pin_top_pct, height).
    """
    raw = hand_path.read_text(encoding="utf-8")
    land_m = re.search(r'id="minsk-land"[^>]*\sd="([^"]+)"', raw)
    if not land_m:
        raise RuntimeError(f"{hand_path} has no #minsk-land path")
    land_d = land_m.group(1)
    water_ds = re.findall(r'<path class="st6" d="([^"]+)"', raw)
    river_ds = re.findall(r'<path class="st3" d="([^"]+)"', raw)
    pin_m = re.search(
        r'id="pin-skorina-36"[^>]*\scx="([^"]+)"[^>]*\scy="([^"]+)"', raw
    )
    if not pin_m:
        raise RuntimeError(f"{hand_path} is missing #pin-skorina-36")
    cx, cy = float(pin_m.group(1)), float(pin_m.group(2))

    pts = _svg_path_points(land_d)
    minx = min(p[0] for p in pts)
    miny = min(p[1] for p in pts)
    maxx = max(p[0] for p in pts)
    maxy = max(p[1] for p in pts)
    bw, bh = maxx - minx, maxy - miny
    scale = (width - 2 * pad) / bw
    height = int(round(bh * scale + 2 * pad))
    ox = pad - minx * scale
    oy = pad - miny * scale

    pin_left = (cx * scale + ox) / width * 100
    pin_top = (cy * scale + oy) / height * 100

    land_out = _rewrite_path_uniform(land_d, scale, ox, oy)
    water_out = [_rewrite_path_uniform(d, scale, ox, oy) for d in water_ds]
    river_out = [_rewrite_path_uniform(d, scale, ox, oy) for d in river_ds]
    water_svg = "\n    ".join(f'<path d="{d}"/>' for d in water_out)
    river_svg = "\n    ".join(f'<path d="{d}"/>' for d in river_out)
    svg = f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} {height}" class="world-outline" preserveAspectRatio="xMidYMid meet" aria-hidden="true" data-pin-left="{pin_left:.2f}" data-pin-top="{pin_top:.2f}" data-source="hand-edited-fill-width">
  <rect class="world-ocean" width="{width}" height="{height}"/>
  <g class="world-land" fill-rule="evenodd">
    <path id="minsk-land" d="{land_out}"/>
  </g>
  <g class="city-water">
    {water_svg}
  </g>
  <g class="city-river">
    {river_svg}
  </g>
</svg>
"""
    dest.parent.mkdir(parents=True, exist_ok=True)
    dest.write_text(svg, encoding="utf-8")
    edit = ROOT / "assets" / "maps" / "edit" / "minsk-hand-cropped.svg"
    edit.parent.mkdir(parents=True, exist_ok=True)
    edit.write_text(svg, encoding="utf-8")
    print(
        f"Promoted hand SVG → {dest.relative_to(ROOT)} "
        f"({width}×{height}, pin {pin_left:.2f}%/{pin_top:.2f}%)"
    )
    return pin_left, pin_top, height


def main() -> None:
    city = json.loads((SRC / "city.geojson").read_text(encoding="utf-8"))
    greens_osm = json.loads((SRC / "greens_osm.geojson").read_text(encoding="utf-8"))
    greens_hand = {"features": []}
    hand_path = SRC / "greens_hand.geojson"
    if hand_path.is_file():
        greens_hand = json.loads(hand_path.read_text(encoding="utf-8"))
    water_fc = {"features": []}
    water_path = SRC / "water.geojson"
    if water_path.is_file():
        water_fc = json.loads(water_path.read_text(encoding="utf-8"))

    city_geom = city["features"][0]["geometry"]
    admin_outer = largest_outer_ring(city_geom)
    revised_outer = remove_eastern_forest_belt(admin_outer)

    mx = [project(p[0], p[1]) for p in revised_outer]
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
    revised_pts = proj_ring(revised_outer, 2.2)
    revised_land = path_d(revised_pts)

    mkad_pts = closed_rdp(shrink_ring(revised_pts, 0.93), 3.0)
    mkad_d = path_d(mkad_pts)

    water_paths: list[str] = []
    for feat in water_fc.get("features", []):
        geom = feat.get("geometry") or {}
        if geom.get("type") in {"Point", "MultiPoint", "LineString", "MultiLineString"}:
            continue
        for ring in iter_polygons(geom):
            if len(ring) < 6:
                continue
            water_paths.append(path_d(proj_ring(ring, 1.4)))

    green_osm_paths = collect_green_paths(
        greens_osm, revised_outer=revised_outer, proj_ring=proj_ring, default_eps=2.4
    )
    # Hand fields are intentional schematic blobs — keep even if centroid sits near edge.
    green_hand_paths = collect_green_paths(
        greens_hand,
        revised_outer=revised_outer,
        proj_ring=proj_ring,
        require_inside=False,
        default_eps=1.2,
    )

    river_paths: list[str] = []
    svisloch_path = SRC / "svisloch.geojson"
    if svisloch_path.is_file():
        svis = json.loads(svisloch_path.read_text(encoding="utf-8"))
        for feat in svis.get("features", []):
            geom = feat.get("geometry") or {}
            coords = geom.get("coordinates") or []
            lines = [coords] if geom.get("type") == "LineString" else coords
            for line in lines:
                clipped = [p for p in line if 27.40 <= p[0] <= 27.72 and 53.82 <= p[1] <= 53.98]
                if len(clipped) < 4:
                    continue
                pts = [xy(p[0], p[1]) for p in clipped]
                simple = rdp(pts, 1.2)
                if len(simple) >= 2:
                    river_paths.append(line_d(simple))

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

    # name, land, mkad, osm, hand, label
    variants = [
        ("minsk-v1-admin.svg", admin_land, False, False, False, "admin (with eastern forest belt)"),
        ("minsk-v2-revised.svg", revised_land, False, False, False, "no eastern forest belt (blue zone removed)"),
        ("minsk-v3-mkad.svg", revised_land, True, False, False, "revised + MKAD"),
        ("minsk-v4-green-osm.svg", revised_land, False, True, False, "revised + OSM parks/woods"),
        ("minsk-v5-green-hand.svg", revised_land, False, False, True, "revised + hand green fields (your annotation)"),
        ("minsk-v6-green-both.svg", revised_land, False, True, True, "revised + OSM + hand greens"),
        ("minsk-v7-mkad-both.svg", revised_land, True, True, True, "revised + MKAD + OSM + hand"),
    ]

    meta = []
    for name, land, use_mkad, use_osm, use_hand, label in variants:
        write_svg(
            VAR / name,
            land_d=land,
            water_paths=water_paths,
            river_paths=river_paths,
            mkad_d=mkad_d,
            green_osm_paths=green_osm_paths,
            green_hand_paths=green_hand_paths,
            pin_left=pin_left,
            pin_top=pin_top,
            include_mkad=use_mkad,
            include_green_osm=use_osm,
            include_green_hand=use_hand,
        )
        meta.append({"file": name, "label": label})

    # Aliases for older links / builder default.
    shutil.copy2(VAR / "minsk-v2-revised.svg", VAR / "minsk-v2-compact.svg")
    shutil.copy2(VAR / "minsk-v4-green-osm.svg", VAR / "minsk-v4-green.svg")
    shutil.copy2(VAR / "minsk-v7-mkad-both.svg", VAR / "minsk-v5-mkad-green.svg")

    # Hand-edited Illustrator silhouette is production when present.
    hand_final = SRC / "minsk-hand-final.svg"
    if hand_final.is_file():
        pin_left, pin_top, _height = promote_hand_final(hand_final, OUT)
        for dest_root in (PREVIEW_MAPS, PREVIEW_FILLED_MAPS):
            if not dest_root.parent.is_dir():
                continue
            dest_root.mkdir(parents=True, exist_ok=True)
            shutil.copy2(OUT, dest_root / "minsk.svg")
        (VAR / "minsk-hand.svg").write_text(OUT.read_text(encoding="utf-8"), encoding="utf-8")
        print(f"pin from hand file left={pin_left:.2f}% top={pin_top:.2f}%")
        return

    # Production: no eastern forest + both green layers.
    write_svg(
        OUT,
        land_d=revised_land,
        water_paths=water_paths,
        river_paths=river_paths,
        mkad_d=mkad_d,
        green_osm_paths=green_osm_paths,
        green_hand_paths=green_hand_paths,
        pin_left=pin_left,
        pin_top=pin_top,
        include_mkad=False,
        include_green_osm=True,
        include_green_hand=True,
    )

    for dest_root in (PREVIEW_MAPS, PREVIEW_FILLED_MAPS):
        if not dest_root.parent.is_dir():
            continue
        dest_root.mkdir(parents=True, exist_ok=True)
        shutil.copy2(OUT, dest_root / "minsk.svg")
        for name, *_ in variants:
            shutil.copy2(VAR / name, dest_root / name)
        for alias in ("minsk-v2-compact.svg", "minsk-v4-green.svg", "minsk-v5-mkad-green.svg"):
            shutil.copy2(VAR / alias, dest_root / alias)

    (VAR / "README.md").write_text(
        "# Minsk map variants\n\n"
        "Open `preview/minsk-map-variants.html` and pick a look.\n\n"
        "Eastern forest belt (user blue zone: Kolodishchi / Stiklevo forests past MKAD) "
        f"is removed in v2+ at lon ≈ {EAST_FOREST_CUT_LON}.\n"
        "Greens: OSM (`greens_osm.geojson`) and hand fields (`greens_hand.geojson` from annotation).\n"
        "Reference photo: `assets/maps/source/minsk-annotation-east-forests-greens.jpg`.\n\n"
        + "\n".join(f"- `{m['file']}` — {m['label']}" for m in meta)
        + "\n\nRebuild: `python3 scripts/build_minsk_map.py`\n"
        "Refresh preview HTML: `python3 scripts/build_preview.py`\n",
        encoding="utf-8",
    )
    print(f"pin left={pin_left:.2f}% top={pin_top:.2f}%")
    print(
        f"water={len(water_paths)} osm_green={len(green_osm_paths)} "
        f"hand_green={len(green_hand_paths)} river={len(river_paths)}"
    )
    print(
        f"admin max lon={max(p[0] for p in admin_outer):.4f} "
        f"revised max lon={max(p[0] for p in revised_outer):.4f}"
    )


if __name__ == "__main__":
    main()

# Minsk map variants

Open `preview/minsk-map-variants.html` and pick a look.

Eastern forest belt (user blue zone: Kolodishchi / Stiklevo forests past MKAD) is removed in v2+ at lon ≈ 27.705.
Greens: OSM (`greens_osm.geojson`) and hand fields (`greens_hand.geojson` from annotation).
Reference photo: `assets/maps/source/minsk-annotation-east-forests-greens.jpg`.

- `minsk-v1-admin.svg` — admin (with eastern forest belt)
- `minsk-v2-revised.svg` — no eastern forest belt (blue zone removed)
- `minsk-v3-mkad.svg` — revised + MKAD
- `minsk-v4-green-osm.svg` — revised + OSM parks/woods
- `minsk-v5-green-hand.svg` — revised + hand green fields (your annotation)
- `minsk-v6-green-both.svg` — revised + OSM + hand greens
- `minsk-v7-mkad-both.svg` — revised + MKAD + OSM + hand

Rebuild: `python3 scripts/build_minsk_map.py`
Refresh preview HTML: `python3 scripts/build_preview.py`

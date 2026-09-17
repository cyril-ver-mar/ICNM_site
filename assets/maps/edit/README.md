# Minsk outline — hand edit

## Source of truth (your file)

- `minsk-full-east-uncropped.svg` — Illustrator export (edited contour)
- Archive copy: `../source/minsk-hand-final.svg`

## Production

Crop empty right → `../minsk.svg` and `minsk-hand-cropped.svg`.

Rebuild crop after a new edit:

```bash
python3 <<'PY'
# or re-run the crop step used in session; then:
python3 scripts/build_preview.py
PY
```

Do not use auto-revised east cut when this hand file exists — the hand silhouette is canonical.

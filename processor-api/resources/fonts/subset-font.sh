#!/bin/sh
# Rebuild HanaMinA-subset.ttf from the system-installed HanaMinA and character-set.txt.
#
# Run from processor-api/ with the app container up. fonttools is deliberately NOT in the app
# image - this runs at most a couple of times a year, whenever the dictionary import brings in
# characters the current subset does not cover.
#
#   php artisan pdf:extract-font-character-set   # refresh character-set.txt first
#   sh resources/fonts/subset-font.sh
#
# See README.md in this directory before reaching for this: the subset font is not currently
# wired into any PDF layout.
set -e

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT

docker cp laravel_app:/usr/share/fonts/truetype/hanazono/HanaMinA.ttf "$WORK/HanaMinA.ttf"
cp resources/fonts/character-set.txt "$WORK/character-set.txt"

docker run --rm -v "$WORK:/work" python:3.12-slim sh -c '
  set -e
  pip install --quiet fonttools
  # --layout-features="" drops GSUB/GPOS features and --no-hinting drops the instruction
  # bytecode: Dompdf renders outlines straight to PDF and uses neither.
  pyftsubset /work/HanaMinA.ttf \
    --text-file=/work/character-set.txt \
    --output-file=/work/HanaMinA-subset.ttf \
    --name-IDs="*" \
    --layout-features="" \
    --no-hinting \
    --notdef-outline \
    --drop-tables+=DSIG
  python - <<PY
from fontTools.ttLib import TTFont
import os
wanted = {ord(c) for c in open("/work/character-set.txt", encoding="utf-8").read().split("\n") if c}
font = TTFont("/work/HanaMinA-subset.ttf")
missing = wanted - set(font.getBestCmap())
print("size    :", os.path.getsize("/work/HanaMinA-subset.ttf"), "bytes")
print("glyphs  :", font["maxp"].numGlyphs)
print("wanted  :", len(wanted))
print("missing :", len(missing), "".join(sorted(chr(c) for c in missing))[:80])
raise SystemExit(1 if missing else 0)
PY
'

cp "$WORK/HanaMinA-subset.ttf" resources/fonts/HanaMinA-subset.ttf
echo "Wrote resources/fonts/HanaMinA-subset.ttf"

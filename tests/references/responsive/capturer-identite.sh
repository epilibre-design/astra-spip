#!/usr/bin/env bash
# Capture des oracles d'identité, sans appeler les générateurs de production SPIP.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
: "${WP:?}" "${ASTRA_REFERENCE_ARCHIVE:?}" "${ASTRA_IMAGES:?}"
URL=${URL:-http://127.0.0.1:8421}
php "$ICI/../verifier.php" "$WP" "$ASTRA_REFERENCE_ARCHIVE" "$ICI/../cible.json"
mkdir -p "$ICI/attendus-identite"
trap 'wp --path="$WP" eval-file "$ICI/../wp/R0-defaut.php" >/dev/null' EXIT
mapfile -t CAS < <(python3 -c 'import json,sys; print("\n".join(json.load(open(sys.argv[1]))))' "$ICI/identite.json")
SORTIES=${SORTIES:-${TMPDIR:-/tmp}/astra-oracles-identite}
mkdir -p "$SORTIES"
for cas in "${CAS[@]}"; do
  wp --path="$WP" eval-file "$ICI/../wp/$cas.php" > /dev/null
  curl -fsS "$URL/" > "$SORTIES/$cas.html"
  php -r '$h=file_get_contents($argv[1]); if (!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s",$h,$m)) exit(1); echo $m[1];' "$SORTIES/$cas.html" > "$SORTIES/$cas.css"
  python3 "$ICI/identite.py" "$SORTIES/$cas.css" > "$ICI/attendus-identite/$cas.css"
  if [[ -n "${PW:-}" ]]; then NODE_PATH="$PW/node_modules" node "$ICI/sonder-identite.cjs" "$URL/" "$SORTIES/$cas.styles.json"; fi
  echo "$cas : capturé"
done
python3 - "$ICI" <<'PY'
import sys,json,hashlib
from pathlib import Path
p=Path(sys.argv[1]); r={'wordpress':'7.1.3','astra':'4.13.8','profil':'base 16 px, drapeaux zéro/conversion absents','adaptations':{'booléens':'complétés par appareil selon les défauts SPIP','tailles_entierement_vides':'option omise selon le contrat sans réglage'},'sha256':{f.name:hashlib.sha256(f.read_bytes()).hexdigest() for f in sorted((p/'attendus-identite').glob('*.css'))}}
(p/'manifest-identite.json').write_text(json.dumps(r,ensure_ascii=False,indent=2)+'\n')
PY

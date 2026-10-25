#!/usr/bin/env bash
# Usage : WP=… PW=… [SORTIES=…] tests/references/capturer.sh R1-entete-complet [...]
# Applique chaque configuration au WordPress de test et enregistre ses références.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
URL=${URL:-http://127.0.0.1:8401}
SORTIES=${SORTIES:-$ICI/sorties}
: "${WP:?WP doit indiquer la racine du WordPress de test}"
: "${PW:?PW doit indiquer le dossier contenant node_modules/playwright}"
: "${ASTRA_REFERENCE_ARCHIVE:?Archive officielle Astra cible requise}"
php "$ICI/verifier.php" "$WP" "$ASTRA_REFERENCE_ARCHIVE" "$ICI/cible.json"

for conf in "$@"; do
	sortie="$SORTIES/$conf"
	rm -rf "$sortie"
	mkdir -p "$sortie"
	wp --path="$WP" eval-file "$ICI/wp/$conf.php"
	wp --path="$WP" option get astra-settings --format=json > "$sortie/options.json"
	chemin=$(php -r '$j = json_decode(file_get_contents($argv[1]), true); echo $j["chemin"] ?? "/";' "$ICI/wp/$conf.json")
	curl -sf "$URL$chemin" -o "$sortie/accueil.html"
	curl -sf "$URL/?p=1" -o "$sortie/article.html"
	php -r '$h = file_get_contents($argv[1]); if (!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s", $h, $m)) { exit(1); } echo $m[1];' "$sortie/accueil.html" > "$sortie/dynamique.css" \
		|| { echo "$conf : CSS dynamique introuvable" >&2; exit 1; }
	[ -s "$sortie/dynamique.css" ] || { echo "$conf : CSS dynamique vide" >&2; exit 1; }
	NODE_PATH="$PW/node_modules" node "$ICI/capture.cjs" "$URL$chemin" "$sortie" "$ICI/wp/$conf.json"
	echo "$conf : OK ($(wc -c < "$sortie/accueil.html") octets HTML, $(wc -c < "$sortie/dynamique.css") octets CSS)"
done

#!/usr/bin/env bash
# Capture le CSS dynamique de WordPress pour chaque groupe de styles et en tire les attendus.
# Usage : WP=<racine WordPress> [URL=http://127.0.0.1:8401] tests/references/styles/capturer.sh
# Écrit attendus/base.css (sans option) et attendus/<groupe>.css (règles ajoutées ou modifiées
# par les options du groupe, par rapport à base.css), puis remet le WordPress en état R0.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
URL=${URL:-http://127.0.0.1:8401}
: "${WP:?WP doit indiquer la racine du WordPress de test}"
css() {
	curl -sf "$URL/" | php -r '$h = stream_get_contents(STDIN); if (!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s", $h, $m)) { exit(1); } echo $m[1];'
}
for groupe in base menu bouton bouton-entete identite pied sociaux menu-mobile; do
	GROUPE=$groupe wp --path="$WP" eval-file "$ICI/appliquer.php"
	css > "$ICI/attendus/.$groupe.complet.css"
done
mv "$ICI/attendus/.base.complet.css" "$ICI/attendus/base.css"
for groupe in menu bouton bouton-entete identite pied sociaux menu-mobile; do
	python3 "$ICI/regles.py" "$ICI/attendus/base.css" "$ICI/attendus/.$groupe.complet.css" > "$ICI/attendus/$groupe.css"
	rm "$ICI/attendus/.$groupe.complet.css"
	echo "$groupe : $(wc -l < "$ICI/attendus/$groupe.css") règles"
done
wp --path="$WP" eval-file "$ICI/../wp/R0-defaut.php"

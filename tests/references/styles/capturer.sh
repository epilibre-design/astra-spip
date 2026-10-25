#!/usr/bin/env bash
# Capture le CSS dynamique de WordPress pour chaque groupe de styles et en tire les attendus.
# Usage : WP=<racine WordPress> [URL=http://127.0.0.1:8401] tests/references/styles/capturer.sh
# Écrit attendus/base.css (sans option) et attendus/<groupe>.css (règles ajoutées ou modifiées
# par les options du groupe, par rapport à base.css ; pour les graisses des titres, règles de
# graisse du CSS complet ; pour les marges des rangées, règles de marges du CSS complet des
# références R24), puis remet le WordPress en état R0.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
URL=${URL:-http://127.0.0.1:8401}
: "${WP:?WP doit indiquer la racine du WordPress de test}"
: "${ASTRA_REFERENCE_ARCHIVE:?Archive officielle Astra cible requise}"
php "$ICI/../verifier.php" "$WP" "$ASTRA_REFERENCE_ARCHIVE" "$ICI/../cible.json"

css() {
	curl -sf "$URL/" | php -r '$h = stream_get_contents(STDIN); if (!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s", $h, $m)) { exit(1); } echo $m[1];'
}
for groupe in base menu bouton bouton-rayon bouton-entete identite pied sociaux menu-mobile; do
	GROUPE=$groupe wp --path="$WP" eval-file "$ICI/appliquer.php"
	css > "$ICI/attendus/.$groupe.complet.css"
done
mv "$ICI/attendus/.base.complet.css" "$ICI/attendus/base.css"
for groupe in menu bouton bouton-rayon bouton-entete identite pied sociaux menu-mobile; do
	python3 "$ICI/regles.py" "$ICI/attendus/base.css" "$ICI/attendus/.$groupe.complet.css" > "$ICI/attendus/$groupe.css"
	rm "$ICI/attendus/.$groupe.complet.css"
	echo "$groupe : $(wc -l < "$ICI/attendus/$groupe.css") règles"
done
# Graisses des titres : règles de graisse du CSS complet (graisses.py), comparées par la cascade.
for groupe in titres titres-drapeau drapeau-polices titres-vide titres-vide-drapeau titres-generale-vide titres-generale-heritee; do
	GROUPE=$groupe wp --path="$WP" eval-file "$ICI/appliquer.php"
	css > "$ICI/attendus/.$groupe.complet.css"
	python3 "$ICI/graisses.py" "$ICI/attendus/.$groupe.complet.css" > "$ICI/attendus/$groupe.css"
	rm "$ICI/attendus/.$groupe.complet.css"
	echo "$groupe : $(wc -l < "$ICI/attendus/$groupe.css") règles de graisse"
done
# Marges des rangées : références R24 (placement propre à chacune), règles de marges du CSS complet
# (marges.py), comparées dans l'ordre.
for valeurs in marges-pied:R24-pied-marges marges-toutes:R24-marges-toutes marges-rangees-vides:R24-marges-rangees-vides; do
	wp --path="$WP" eval-file "$ICI/../wp/${valeurs#*:}.php"
	css > "$ICI/attendus/.${valeurs%%:*}.complet.css"
	python3 "$ICI/marges.py" "$ICI/attendus/.${valeurs%%:*}.complet.css" > "$ICI/attendus/${valeurs%%:*}.css"
	rm "$ICI/attendus/.${valeurs%%:*}.complet.css"
	echo "${valeurs%%:*} : $(wc -l < "$ICI/attendus/${valeurs%%:*}.css") règles de marges"
done
wp --path="$WP" eval-file "$ICI/../wp/R0-defaut.php"

#!/usr/bin/env bash
# Capture le CSS dynamique de WordPress sur une page à en-tête transparent, pour chaque variante.
# Usage : WP=<racine WordPress> [URL=http://127.0.0.1:8401] tests/references/transparent/capturer.sh
# Écrit attendus/<variante>.css : règles ajoutées ou modifiées par rapport à la même page sans
# en-tête transparent (regles.py des styles), puis remet le WordPress en état R0.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
URL=${URL:-http://127.0.0.1:8401}
: "${WP:?WP doit indiquer la racine du WordPress de test}"
: "${ASTRA_REFERENCE_ARCHIVE:?Archive officielle Astra cible requise}"
php "$ICI/../verifier.php" "$WP" "$ASTRA_REFERENCE_ARCHIVE" "$ICI/../cible.json"

css() {
	curl -sf "$URL/?pagename=notre-jardin" | php -r '$h = stream_get_contents(STDIN); if (!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s", $h, $m)) { exit(1); } echo $m[1];'
}
VARIANTES=(garden ordinateur mobile couleurs)
VARIANTE=base wp --path="$WP" eval-file "$ICI/appliquer.php"
css > "$ICI/attendus/.base.css"
for variante in "${VARIANTES[@]}"; do
	VARIANTE=$variante wp --path="$WP" eval-file "$ICI/appliquer.php"
	css > "$ICI/attendus/.$variante.complet.css"
	python3 "$ICI/../styles/regles.py" "$ICI/attendus/.base.css" "$ICI/attendus/.$variante.complet.css" > "$ICI/attendus/$variante.css"
	python3 "$ICI/../styles/regles.py" "$ICI/attendus/.$variante.complet.css" "$ICI/attendus/.base.css" > "$ICI/attendus/$variante.retirees.css"
	rm "$ICI/attendus/.$variante.complet.css"
	echo "$variante : $(wc -l < "$ICI/attendus/$variante.css") règles, $(wc -l < "$ICI/attendus/$variante.retirees.css") retirées"
done
rm "$ICI/attendus/.base.css"
wp --path="$WP" eval-file "$ICI/../wp/R0-defaut.php"

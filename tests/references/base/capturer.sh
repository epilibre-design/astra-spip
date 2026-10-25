#!/usr/bin/env bash
# Compléments de R0 indispensables au CSS de base ; site cible privé, hors capture concurrente.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
: "${WP:?}" "${URL:?}" "${ASTRA_REFERENCE_ARCHIVE:?}"
DEST=${1:?Dossier R0 de sortie requis}
mkdir -p "$DEST"
php "$ICI/../verifier.php" "$WP" "$ASTRA_REFERENCE_ARCHIVE" "$ICI/../cible.json"
wp --path="$WP" eval-file "$ICI/preparer.php" > "$DEST/base-urls.json"
PAGE=$(php -r '$j=json_decode(file_get_contents($argv[1]),true);echo $j["page"];' "$DEST/base-urls.json")
ARCHIVE=$(php -r '$j=json_decode(file_get_contents($argv[1]),true);echo $j["archive"];' "$DEST/base-urls.json")
curl -fsS "$URL/" -o "$DEST/accueil.html"
curl -fsS "$URL/?p=1" -o "$DEST/article.html"
curl -fsS "$PAGE" -o "$DEST/page.html"
curl -fsS "$URL/?s=astra-fixture-absente" -o "$DEST/recherche.html"
# La réponse 404 attendue conserve son corps HTML : curl sans --fail pour cette seule URL.
curl -sS "$URL/?p=99999999" -o "$DEST/404.html"
curl -fsS "$ARCHIVE" -o "$DEST/archive.html"
BASE_ETROITE=1 wp --path="$WP" eval-file "$ICI/preparer.php" > /dev/null
curl -fsS "$PAGE" -o "$DEST/etroite.html"
wp --path="$WP" eval-file "$ICI/preparer.php" > /dev/null
php -r '$h=file_get_contents($argv[1]);if(!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s",$h,$m)){exit(1);}echo $m[1];' "$DEST/accueil.html" > "$DEST/dynamique.css"
wp --path="$WP" --skip-themes --skip-plugins option get astra-settings --format=json > "$DEST/options.json"
echo 'CSS de base : sept contextes capturés ; WordPress remis en état R0.'

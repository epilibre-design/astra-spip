#!/usr/bin/env bash
# WordPress 7.1.3 sur MySQL avec Astra 4.13.8 officiel, pour la page de démonstration des blocs
# (wp2spip lit une base MySQL). Usage :
#   WP_BLOCS=<dossier> ASTRA_WP=<thème astra 4.13.8> BLOCS_DB_NAME=… BLOCS_DB_USER=… BLOCS_DB_PASS=… \
#   scripts/install-wp-blocs.sh
# La base doit exister : le script ne la crée pas. Tables préfixées wpblocs_.
set -euo pipefail
: "${WP_BLOCS:?}" "${ASTRA_WP:?}" "${BLOCS_DB_NAME:?}" "${BLOCS_DB_USER:?}" "${BLOCS_DB_PASS:?}"
ICI=$(cd "$(dirname "$0")/.." && pwd)
URL_WP_BLOCS=${URL_WP_BLOCS:-http://127.0.0.1:8405}
BLOCS_DB_HOST=${BLOCS_DB_HOST:-localhost}
mkdir -p "$WP_BLOCS"
cd "$WP_BLOCS"
[ -f wp-load.php ] || wp core download --version=7.1.3 --locale=fr_FR
[ -f wp-config.php ] || wp config create --dbname="$BLOCS_DB_NAME" --dbuser="$BLOCS_DB_USER" --dbpass="$BLOCS_DB_PASS" \
	--dbhost="$BLOCS_DB_HOST" --dbprefix=wpblocs_
wp db check > /dev/null
wp core is-installed || wp core install --url="$URL_WP_BLOCS" --title="Mon site" --admin_user=admin --admin_password=admin --admin_email=admin@example.test --skip-email
[ -e wp-content/themes/astra ] || ln -s "$ASTRA_WP" wp-content/themes/astra
wp theme activate astra
: "${ASTRA_REFERENCE_ARCHIVE:?Archive Astra cible requise}"
php "$ICI/tests/references/verifier.php" "$WP_BLOCS" "$ASTRA_REFERENCE_ARCHIVE" "$ICI/tests/references/cible.json" > /dev/null
wp eval-file "$ICI/tests/references/blocs/page.php"
echo "WordPress des blocs prêt : $URL_WP_BLOCS ($WP_BLOCS)"

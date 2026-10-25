#!/usr/bin/env bash
# Références SQLite privées, sans reprise du wp-config.php ni de la base d'un autre site.
set -euo pipefail
ICI=$(cd "$(dirname "$0")/.." && pwd)
: "${WP_REFERENCE:?}" "${URL_WP:?}" "${ASTRA_REFERENCE_ARCHIVE:?}" "${SQLITE_PLUGIN:?}"
CIBLE=${CIBLE:-$ICI/tests/references/cible.json}
CIBLE=$(realpath "$CIBLE")
ASTRA_REFERENCE_ARCHIVE=$(realpath "$ASTRA_REFERENCE_ARCHIVE")
SQLITE_PLUGIN=$(realpath "$SQLITE_PLUGIN")
# Valider les sources avant toute écriture dans le dossier cible.
php -r '
$m=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
if(hash_file("sha256",$argv[2])!==$m["archive_sha256"]
 || hash_file("sha256",$argv[3]."/load.php")!==$m["sqlite_plugin"]["load_sha256"]
 || hash_file("sha256",$argv[3]."/db.copy")!==$m["sqlite_plugin"]["dropin_sha256"]) {fwrite(STDERR,"Sources non conformes\n");exit(1);}
' "$CIBLE" "$ASTRA_REFERENCE_ARCHIVE" "$SQLITE_PLUGIN"
mkdir -p "$WP_REFERENCE"
WP_REFERENCE=$(realpath "$WP_REFERENCE")
MARQUEUR="$WP_REFERENCE/.astra-reference-installee"
if [[ ! -f "$MARQUEUR" ]]; then
	if [[ -n $(find "$WP_REFERENCE" -mindepth 1 -maxdepth 1 -print -quit) ]]; then
		echo 'Dossier non vide sans marqueur : installation refusée' >&2
		exit 1
	fi
	# Marqueur de préparation : une installation interrompue peut être reprise sans écraser des données.
	printf '%s\n' "$WP_REFERENCE" > "$MARQUEUR"
fi
[[ $(cat "$MARQUEUR") == "$WP_REFERENCE" ]] || { echo 'Marqueur de dossier incorrect' >&2; exit 1; }
VERSION=$(php -r '$m=json_decode(file_get_contents($argv[1]),true);echo $m["wordpress"];' "$CIBLE")
if [[ ! -f "$WP_REFERENCE/wp-load.php" ]]; then
	ARCHIVE_CORE=$(mktemp)
	trap 'rm -f "$ARCHIVE_CORE"' EXIT
	URL_CORE=$(php -r '$m=json_decode(file_get_contents($argv[1]),true);echo $m["wordpress_archive_url"];' "$CIBLE")
	curl -fsSL "$URL_CORE" -o "$ARCHIVE_CORE"
	php -r '$m=json_decode(file_get_contents($argv[1]),true);exit(hash_file("sha256",$argv[2])===$m["wordpress_archive_sha256"]?0:1);' "$CIBLE" "$ARCHIVE_CORE"
	# tar préserve les chemins longs que l'extracteur wp-cli local tronque.
	tar -xzf "$ARCHIVE_CORE" --strip-components=1 -C "$WP_REFERENCE"
fi
wp --path="$WP_REFERENCE" core verify-checksums --version="$VERSION" --locale=en_US
mkdir -p "$WP_REFERENCE/wp-content/plugins" "$WP_REFERENCE/wp-content/database" "$WP_REFERENCE/wp-content/themes"
if [[ ! -f "$WP_REFERENCE/wp-config.php" ]]; then
	cp -R "$SQLITE_PLUGIN" "$WP_REFERENCE/wp-content/plugins/sqlite-database-integration"
	cp "$WP_REFERENCE/wp-content/plugins/sqlite-database-integration/db.copy" "$WP_REFERENCE/wp-content/db.php"
	wp --path="$WP_REFERENCE" config create --dbname=reference --dbuser=reference --dbpass=reference --skip-check
	DB_CIBLE="$WP_REFERENCE/wp-content/database/reference.sqlite"
	wp --path="$WP_REFERENCE" config set DB_PATH "$DB_CIBLE"
	wp --path="$WP_REFERENCE" config set WP_HOME "$URL_WP"
	wp --path="$WP_REFERENCE" config set WP_SITEURL "$URL_WP"
	wp --path="$WP_REFERENCE" config set WP_ENVIRONMENT_TYPE local
	wp --path="$WP_REFERENCE" config set WP_DEBUG false --raw
	wp --path="$WP_REFERENCE" config set AUTOMATIC_UPDATER_DISABLED true --raw
	wp --path="$WP_REFERENCE" config set DISABLE_WP_CRON true --raw
	unzip -q "$ASTRA_REFERENCE_ARCHIVE" -d "$WP_REFERENCE/wp-content/themes"
fi
# Vérifier le DB_PATH réel avant de charger WordPress (donc avant toute ouverture SQLite).
DB_CIBLE="$WP_REFERENCE/wp-content/database/reference.sqlite"
DB_CONFIG=$(wp --path="$WP_REFERENCE" config get DB_PATH)
[[ $(realpath -m "$DB_CONFIG") == "$DB_CIBLE" ]] || { echo 'Base hors du dossier privé' >&2; exit 1; }
[[ ! -L "$WP_REFERENCE/wp-content/database" && ! -L "$DB_CIBLE" ]] || { echo 'Lien de base interdit' >&2; exit 1; }
if ! wp --path="$WP_REFERENCE" --skip-themes --skip-plugins core is-installed; then
	# Mot de passe aléatoire propre au site, non enregistré dans le manifeste ni affiché.
	wp --path="$WP_REFERENCE" --skip-themes --skip-plugins core install --url="$URL_WP" --title='Mon site' --admin_user=admin --admin_password="$(php -r 'echo bin2hex(random_bytes(24));')" --admin_email=admin@example.test --skip-email
	wp --path="$WP_REFERENCE" --skip-themes --skip-plugins theme activate astra
	wp --path="$WP_REFERENCE" --skip-themes --skip-plugins eval-file "$ICI/tests/references/wp/preparer-site.php"
fi
# Même langue que les références SPIP et les anciennes captures WordPress.
wp --path="$WP_REFERENCE" --skip-themes --skip-plugins language core install fr_FR --activate
wp --path="$WP_REFERENCE" --skip-themes --skip-plugins language theme install astra fr_FR
php "$ICI/tests/references/verifier.php" "$WP_REFERENCE" "$ASTRA_REFERENCE_ARCHIVE" "$CIBLE"

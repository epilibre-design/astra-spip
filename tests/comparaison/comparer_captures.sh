#!/usr/bin/env bash
# Comparaison visuelle SPIP / WordPress des configurations de référence.
# Usage : WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh <racine SPIP> <URL SPIP> [Rn|Pn …]
# Les configurations Pn (pages uniques) comparent en plus le contenu (#primary).
# Le site SPIP doit avoir les contenus du SPIP de test (scripts/install-spip-test.sh) : site « Mon site »,
# rubriques Jardinage > Potager et Actualités, comme les références WordPress. Pour chaque
# configuration : application au WordPress de test et au site SPIP, captures et comparaison au pixel.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
RACINE=$1
URL_SPIP=$2
shift 2
URL_WP=${URL_WP:-http://127.0.0.1:8401}
SORTIES=${SORTIES:-$ICI/sorties}
: "${WP:?WP doit indiquer la racine du WordPress de test}"
: "${PW:?PW doit indiquer le dossier contenant node_modules/playwright et pixelmatch}"
: "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
CONFS=("$@")
[ ${#CONFS[@]} -gt 0 ] || CONFS=(R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique)

# Copyright identique à celui d'Astra dans WordPress.
(cd "$RACINE" && $SPIP_CLI php:eval 'include_spip("inc/config"); ecrire_config("astra/texte_pied", "Copyright © [annee] [site] | Powered by [Thème WordPress Astra->https://wpastra.com]");' > /dev/null)

code=0
for conf in "${CONFS[@]}"; do
	echo "== $conf"
	wp --path="$WP" eval-file "$ICI/../references/wp/$conf.php"
	"$ICI/appliquer.sh" "$RACINE" "$conf" > /dev/null
	menu=$(php -r 'require $argv[1]; echo astra_configurations_reference()[$argv[2]]["menu_mobile"] ?? "dropdown";' "$ICI/configurations.php" "$conf")
	chemin_wp=$(php -r '$j = json_decode(file_get_contents($argv[1]), true); echo $j["chemin"] ?? "/";' "$ICI/../references/wp/$conf.json")
	identifiant=$(php -r 'require $argv[1]; echo astra_configurations_reference()[$argv[2]]["page"] ?? "";' "$ICI/configurations.php" "$conf")
	chemin_spip=/
	contenu=
	if [ -n "$identifiant" ]; then
		chemin_spip=/$( (cd "$RACINE" && $SPIP_CLI php:eval "include_spip('inc/filtres'); echo generer_objet_url((int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', 'page=' . sql_quote('$identifiant')]), 'article', '', '', true);") | sed -E 's#^https?://[^/]+/?##')
	fi
	case $conf in P*) contenu=contenu ;; esac
	NODE_PATH="$PW/node_modules" node "$ICI/comparer_captures.cjs" "$URL_WP$chemin_wp" "$URL_SPIP$chemin_spip" "$SORTIES/$conf" "$menu" "$contenu" || code=1
done

wp --path="$WP" eval-file "$ICI/../references/wp/R0-defaut.php"
"$ICI/appliquer.sh" "$RACINE" R0-defaut > /dev/null
(cd "$RACINE" && $SPIP_CLI php:eval 'include_spip("inc/config"); effacer_config("astra/texte_pied");' > /dev/null)
exit $code

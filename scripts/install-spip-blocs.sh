#!/usr/bin/env bash
# SPIP 4.4 importé par wp2spip depuis le WordPress des blocs, avec Astra SPIP actif.
# Usage : SPIP_BLOCS=<dossier vide> WP_BLOCS=<dossier> SPIP_CLI=… [SPIP_CLI_SVP=…] [DEPOT=<dépôt SVP>] \
#   scripts/install-spip-blocs.sh
# preparer_spip.sh télécharge Pages et Polyhier par plugins:svp:telecharger, réparé par la MR 91 de
# spip-cli : SPIP_CLI_SVP désigne un exécutable spip-cli qui la contient (par défaut, celui de SPIP_CLI).
set -euo pipefail
: "${SPIP_BLOCS:?}" "${WP_BLOCS:?}" "${SPIP_CLI:?}"
ICI=$(cd "$(dirname "$0")/.." && pwd)
WP2SPIP=${WP2SPIP:-/src/wordpress/wp2spip}
URL_SPIP_BLOCS=${URL_SPIP_BLOCS:-http://127.0.0.1:8406}
# preparer_spip.sh attend un exécutable : par défaut, dernier mot de SPIP_CLI (« php …/bin/spip » → …/bin/spip).
SPIP_ADMIN_PASS=adminadmin "$WP2SPIP/outils/preparer_spip.sh" --spip "$SPIP_BLOCS" --wordpress "$WP_BLOCS" \
	--wp2spip lien --spip-cli "${SPIP_CLI_SVP:-${SPIP_CLI##* }}" --adresse "$URL_SPIP_BLOCS" ${DEPOT:+--depot "$DEPOT"} --importer
cd "$SPIP_BLOCS"
# wordpress:importer remplace adresse_site par l'adresse du WordPress (preparer_spip.sh ne lui
# transmet pas l'option qui la conserve) : on rétablit celle du SPIP importé.
export URL_SPIP_BLOCS
$SPIP_CLI php:eval 'include_spip("inc/meta"); ecrire_meta("adresse_site", getenv("URL_SPIP_BLOCS"));'
[ -e plugins/astra ] || ln -s "$ICI" plugins/astra
$SPIP_CLI plugins:activer astra -y
$SPIP_CLI plugins:maj:bdd
$SPIP_CLI php:eval 'include_spip("inc/plugin"); echo test_plugin_actif("astra") ? "actif" : "inactif";' | grep -q '^actif$'
$SPIP_CLI cache:vider
echo "SPIP importé prêt : $URL_SPIP_BLOCS ($SPIP_BLOCS)"

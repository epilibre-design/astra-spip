#!/usr/bin/env bash
# RECETTE-28 : capture des routes d'Astra pour plusieurs configurations du builder (comparaison avant/après).
# Usage : SPIP_CLI=… NODE_PATH=… tests/comparaison/avant_apres.sh <racine du site SPIP> <URL servie> <dossier> [configurations…]
# Sans configuration : R0-defaut, R1-entete-complet, R3-pied-colonnes, R25-nav-offcanvas, R16-transparent-page,
# R24-marges-toutes, P1-page, P2-accueil. Comparer ensuite : node tests/comparaison/avant_apres.cjs comparer <avant> <après>.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
RACINE=$1; URL=$2; SORTIE=$3; shift 3
CONFS=("$@"); [ ${#CONFS[@]} -gt 0 ] || CONFS=(R0-defaut R1-entete-complet R3-pied-colonnes R25-nav-offcanvas R16-transparent-page R24-marges-toutes P1-page P2-accueil)
for conf in "${CONFS[@]}"; do
	"$ICI/appliquer.sh" "$RACINE" "$conf" >/dev/null
	node "$ICI/avant_apres.cjs" capturer "$URL" "$SORTIE" "$conf"
	echo "capturé : $conf"
done

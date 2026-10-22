#!/usr/bin/env bash
# Usage : SPIP_CLI=… tests/comparaison/appliquer.sh <racine du site SPIP> <configuration>
# Écrit une configuration transposée (configurations.php) dans astra/hfb et vide le cache.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
: "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
cd "$1"
$SPIP_CLI php:eval "
include_spip('inc/config');
require_once '$ICI/configurations.php';
\$c = astra_configurations_reference();
if (!isset(\$c['$2'])) { echo 'configuration-inconnue'; return; }
\$c['$2'] ? ecrire_config('astra/hfb', \$c['$2']) : effacer_config('astra/hfb');
echo 'configuration-appliquee';
" | grep -q '^configuration-appliquee$' || { echo "Configuration $2 non appliquée" >&2; exit 1; }
$SPIP_CLI cache:vider > /dev/null
echo "$2 appliquée"

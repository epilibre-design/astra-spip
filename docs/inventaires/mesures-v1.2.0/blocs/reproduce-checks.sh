#!/bin/bash
# Existing isolated fixtures/servers must be running (see ENVIRONMENT.md).
set -u
cd /tmp/astra-main-mesures-20261023
export NODE_PATH=/tmp/claude-1000/-src-astra-spip/dc097d1c-c7f6-455a-b514-0ce253b369df/scratchpad/pw/node_modules
export PATH=/home/gilles/.local/bin:$PATH
export SPIP_CLI='php /src/spip-cli-custom/bin/spip'
result=/tmp/astra-main-mesures-resultats-20261023/blocs
node tests/comparaison/comparer_blocs.cjs 'http://127.0.0.1:8465/?pagename=demonstration-des-blocs' 'http://127.0.0.1:8466/spip.php?article6' "$result/captures" > "$result/comparer_blocs.log" 2>&1
echo "$?" > "$result/comparer_blocs.exit"
php tests/comparaison/comparer_medias.php /tmp/astra-main-blocs-20261023/spip /tmp/astra-main-blocs-20261023/wp > "$result/comparer_medias.log" 2>&1
echo "$?" > "$result/comparer_medias.exit"
curl -sS 'http://127.0.0.1:8465/?pagename=demonstration-des-blocs' -o "$result/page-wordpress.html"
WP=/tmp/astra-main-blocs-20261023/wp PAGE="$result/page-wordpress.html" node tests/e2e/cascade_blocs.cjs > "$result/cascade_blocs.log" 2>&1
echo "$?" > "$result/cascade_blocs.exit"

#!/usr/bin/env bash
set -uo pipefail
export WP=/tmp/astra-main-mesures-sites-20261023/wp
export PW=/tmp/claude-1000/-src-astra-spip/dc097d1c-c7f6-455a-b514-0ce253b369df/scratchpad/pw
export SPIP_CLI=/src/spip-cli-custom/bin/spip
export URL=http://127.0.0.1:8461
export URL_WP=$URL
repo=/tmp/astra-main-mesures-20261023
resultats=/tmp/astra-main-mesures-resultats-20261023
confs=(R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee P1-page P2-accueil P3-page-pleine)
export SORTIES=$resultats/references
"$repo/tests/references/capturer.sh" "${confs[@]}" > "$resultats/references.log" 2>&1
references=$?
printf '%s\n' "$references" > "$resultats/references.exit"
export SORTIES=$resultats/captures
"$repo/tests/comparaison/comparer_captures.sh" /tmp/astra-main-mesures-sites-20261023/spip http://127.0.0.1:8464 "${confs[@]}" > "$resultats/captures.log" 2>&1
captures=$?
printf '%s\n' "$captures" > "$resultats/captures.exit"
export REFERENCES=$resultats/references
php "$repo/tests/comparaison/comparer_html.php" /tmp/astra-main-mesures-sites-20261023/spip http://127.0.0.1:8464 "${confs[@]}" > "$resultats/html.log" 2>&1
html=$?
printf '%s\n' "$html" > "$resultats/html.exit"
printf 'Références : %s ; captures : %s ; HTML : %s\n' "$references" "$captures" "$html"
if (( references || captures || html )); then exit 1; fi

#!/usr/bin/env bash
# Usage : tests/references/normaliser.sh fichier.html
# Retire d'un HTML WordPress ce qui varie d'une exécution à l'autre (versions, nonces).
sed -E 's/([?&]ver=)[^"&'"'"' ]*/\1X/g; s/("nonce":")[^"]*"/\1X"/g; s/(name="_wpnonce" value=")[^"]*"/\1X"/g' "$1"

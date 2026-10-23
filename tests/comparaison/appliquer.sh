#!/usr/bin/env bash
# Usage : SPIP_CLI=… tests/comparaison/appliquer.sh <racine du site SPIP> <configuration>
# Écrit une configuration transposée (configurations.php) dans astra/hfb, avec les réglages
# d'accueil et de pages (astra/accueil_*, astra/pages), les styles (astra/styles), les polices
# (astra/police_*) et le slogan (méta slogan_site), et vide le cache.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
: "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
cd "$1"
$SPIP_CLI php:eval "
include_spip('inc/config');
require_once '$ICI/configurations.php';
\$c = astra_configurations_reference();
if (!isset(\$c['$2'])) { echo 'configuration-inconnue'; return; }
\$conf = \$c['$2'];
\$id = function (\$identifiant) {
	return (int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', 'page=' . sql_quote(\$identifiant)]);
};
foreach (['accueil_type', 'accueil_page', 'pages', 'styles', 'police_texte', 'police_titres'] as \$cle) {
	effacer_config('astra/' . \$cle);
}
ecrire_meta('slogan_site', '');
foreach (\$conf['meta'] ?? [] as \$meta => \$valeur) {
	ecrire_meta(\$meta, \$valeur);
}
if (!empty(\$conf['styles'])) {
	ecrire_config('astra/styles', \$conf['styles']);
}
foreach (\$conf['astra'] ?? [] as \$cle => \$valeur) {
	ecrire_config('astra/' . \$cle, is_string(\$valeur) && \$valeur[0] === '@' ? \$id(substr(\$valeur, 1)) : \$valeur);
}
foreach (\$conf['pages'] ?? [] as \$identifiant => \$reglages) {
	ecrire_config('astra/pages/' . \$id(\$identifiant), \$reglages);
}
\$hfb = astra_configuration_hfb(\$conf);
\$hfb ? ecrire_config('astra/hfb', \$hfb) : effacer_config('astra/hfb');
echo 'configuration-appliquee';
" | grep -q '^configuration-appliquee$' || { echo "Configuration $2 non appliquée" >&2; exit 1; }
$SPIP_CLI cache:vider > /dev/null
echo "$2 appliquée"

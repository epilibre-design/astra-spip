#!/usr/bin/env sh
# Installe un SPIP 4.4 de test dans vendor/spip/spip, avec le plugin astra actif.
# SPIP_CLI doit désigner un spip-cli incluant le correctif MR !91, par exemple :
#   SPIP_CLI="php /src/spip-cli-custom/bin/spip" composer install-spip-test
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
SPIP_ROOT="$ROOT_DIR/vendor/spip/spip"
: "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli (avec le correctif MR !91)}"

mkdir -p "$ROOT_DIR/vendor/spip"
if [ ! -f "$SPIP_ROOT/ecrire/inc_version.php" ]; then
	$SPIP_CLI core:telecharger -d "$SPIP_ROOT" -R 4.4
fi

cd "$SPIP_ROOT"
$SPIP_CLI core:preparer
mkdir -p plugins/auto
[ -e plugins/astra ] || ln -s "$ROOT_DIR" plugins/astra

if [ ! -f config/connect.php ]; then
	$SPIP_CLI core:installer \
		--db-server=sqlite3 --db-host='' --db-login='' --db-pass='' \
		--db-database=spip_test --db-prefix=spip \
		--admin-nom='Admin Test' --admin-login=admin --admin-email=admin@example.test \
		--admin-pass=adminadmin --adresse-site='http://localhost'
fi

# plugins:activer peut sortir avec le code 0 sans rien activer : vérifier ensuite.
$SPIP_CLI plugins:activer astra -y
$SPIP_CLI php:eval 'include_spip("inc/plugin"); echo test_plugin_actif("astra") ? "astra-actif\n" : "astra-inactif\n";' | grep -q '^astra-actif$' \
	|| { echo "Le plugin astra n'est pas actif" >&2; exit 1; }

# Rubriques et articles de démonstration (menus) : recréés à chaque exécution.
# Insertions SQL directes : objet_instituer() refuse de publier sans auteur connecté.
$SPIP_CLI php:eval '
include_spip("inc/rubriques");
sql_delete("spip_articles");
sql_delete("spip_rubriques");
$date = date("Y-m-d H:i:s");
$rubrique = function ($titre, $parent) use ($date) {
	return sql_insertq("spip_rubriques", ["titre" => $titre, "id_parent" => $parent, "statut" => "publie", "date" => $date, "lang" => "fr"]);
};
$article = function ($titre, $id_rubrique) use ($date) {
	return sql_insertq("spip_articles", ["titre" => $titre, "texte" => "Texte.", "id_rubrique" => $id_rubrique, "statut" => "publie", "date" => $date, "lang" => "fr"]);
};
$jardinage = $rubrique("Jardinage", 0);
$article("Article de Jardinage", $jardinage);
$article("Article du potager", $rubrique("Potager", $jardinage));
$article("Article des actualités", $rubrique("Actualités", 0));
calculer_rubriques();
propager_les_secteurs();
echo sql_countsel("spip_rubriques", "statut=\"publie\""), " rubriques publiées\n";
'
echo "Environnement d'intégration prêt."

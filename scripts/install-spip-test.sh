#!/usr/bin/env sh
# Installe un SPIP 4.4 de test dans vendor/spip/spip, avec le plugin astra actif.
# SPIP_CLI doit désigner un spip-cli incluant le correctif MR !91, par exemple :
#   SPIP_CLI="php /src/spip-cli-custom/bin/spip" composer install-spip-test
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
SPIP_ROOT="${SPIP_TEST_ROOT:-$ROOT_DIR/vendor/spip/spip}"
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

# Dépendances obligatoires avant l’activation d’Astra. Tags amont figés.
installer_git() { # <préfixe> <tag>
 if [ ! -f "plugins/auto/$1/paquet.xml" ]; then
  git clone --quiet --depth 1 --branch "$2" "https://git.spip.net/spip-contrib-extensions/$1.git" "plugins/auto/$1"
 fi
}
installer_git saisies 6.4.1
installer_git menus 3.1.2
$SPIP_CLI plugins:activer saisies menus -y
$SPIP_CLI plugins:maj:bdd
for p in saisies menus; do
 $SPIP_CLI php:eval "include_spip('inc/plugin'); echo test_plugin_actif('$p') ? 'actif' : 'inactif';" | grep -q '^actif$' \
  || { echo "Le plugin $p n'est pas actif" >&2; exit 1; }
done

# plugins:activer peut sortir avec le code 0 sans rien activer : vérifier ensuite.
$SPIP_CLI plugins:activer astra -y
$SPIP_CLI php:eval 'include_spip("inc/plugin"); echo test_plugin_actif("astra") ? "astra-actif\n" : "astra-inactif\n";' | grep -q '^astra-actif$' \
	|| { echo "Le plugin astra n'est pas actif" >&2; exit 1; }

# Plugins Pages (GPL v3) et a2a (GPL v3) : archives récupérées directement sur files.spip.org,
# plugins:svp:telecharger ne trouvant pas les plugins sous SQLite.
installer_archive() { # <préfixe> <chemin de l'archive> <dossier dans l'archive>
	if [ ! -f "plugins/auto/$1/paquet.xml" ]; then
		archive=$(mktemp -d)
		curl -sfL -o "$archive/plugin.zip" "https://files.spip.org/spip-zone/$2"
		unzip -q "$archive/plugin.zip" -d "$archive"
		mv "$archive/$3" "plugins/auto/$1"
		rm -rf "$archive"
	fi
}
installer_archive pages spip-contrib-extensions/pages-70f6d-pages-v2.4.1.zip pages-v2.4.1
installer_archive a2a spip-contrib-extensions/a2a-9baa6-a2a-4.2.1.zip a2a-4.2.1
$SPIP_CLI plugins:activer pages a2a -y
# plugins:activer ne lance pas l'installation des plugins (champ spip_articles.page, table spip_articles_lies).
$SPIP_CLI plugins:maj:bdd
for p in pages a2a; do
	$SPIP_CLI php:eval "include_spip('inc/plugin'); echo test_plugin_actif('$p') ? 'actif' : 'inactif';" | grep -q '^actif$' \
		|| { echo "Le plugin $p n'est pas actif" >&2; exit 1; }
done

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
// Mêmes menus que les références WordPress (tests/references/wp/commun.php) : Jardinage > Potager, Actualités.
$jardinage = $rubrique("10. Jardinage", 0);
$article("Article de Jardinage", $jardinage);
$article("Article du potager", $rubrique("Potager", $jardinage));
$article("Article des actualités", $rubrique("20. Actualités", 0));
// Pages uniques (plugin Pages) : hors rubrique, id_rubrique = -1. Mêmes titres et textes que
// les références WordPress (tests/references/wp/commun.php, ref_pages()).
sql_delete("spip_articles_lies");
$page = function ($titre, $identifiant) use ($date) {
	return sql_insertq("spip_articles", ["titre" => $titre, "texte" => "Texte de la page.", "id_rubrique" => -1, "page" => $identifiant, "statut" => "publie", "accepter_forum" => "non", "date" => $date, "lang" => "fr"]);
};
$page("Mentions légales", "mentions");
$jardin = $page("Notre jardin", "notre_jardin");
foreach ([1 => $page("Potager collectif", "potager_collectif"), 2 => $page("Verger", "verger")] as $rang => $enfant) {
	sql_insertq("spip_articles_lies", ["id_article" => $jardin, "id_article_lie" => $enfant, "rang" => $rang, "type_liaison" => "sous_page"]);
}
$page("Plein écran", "plein_ecran");
calculer_rubriques();
propager_les_secteurs();
include_spip("inc/config");
ecrire_meta("nom_site", "Mon site");
ecrire_meta("descriptif_site", "");
echo sql_countsel("spip_rubriques", "statut=\"publie\""), " rubriques publiées\n";
'
echo "Environnement d'intégration prêt."

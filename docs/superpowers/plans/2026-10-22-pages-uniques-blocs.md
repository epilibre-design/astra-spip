# Pages uniques et blocs natifs — plan d'exécution

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** afficher dans Astra SPIP les pages uniques comme des pages d'Astra (gabarit, sous-pages, accueil statique, réglages par page) et styler les blocs natifs WordPress conservés par wp2spip.

**Architecture:** un pipeline `styliser` choisit `page_unique.html` pour les articles hors rubrique, sans écraser les variantes du plugin Pages ; les règles métier (réglages par page, arbre des pages, accueil) sont des fonctions PHP de `inc/astra_pages.php`, testées par PHPUnit ; le CSS des blocs est généré depuis WordPress 7.1.3 par un script Python qui ne retire que des sélecteurs entiers démontrés impossibles. Chaque fonction visible est comparée à une référence WordPress (structure HTML et captures).

**Tech Stack:** SPIP 4.4 (squelettes, CVT, pipelines), PHP 7.4+ pour le plugin, PHPUnit 11.5 (outillage PHP 8.2+), Python 3 (bibliothèque standard), Playwright + pixelmatch + pngjs, WP-CLI, plugins SPIP Pages 2.4.1 et a2a 4.2.1, wp2spip (dépôt local).

Spec : `docs/superpowers/specs/2026-10-22-pages-uniques-blocs-design.md`.

## Global Constraints

- Version vérifiée : **SPIP 4.4** ; le plugin reste compatible **PHP 7.4** (`php7.4 -l` sur chaque fichier PHP modifié).
- Licence du plugin : **GPL-3.0-or-later** ; CSS de WordPress : GPL v2 ou ultérieure, crédité ; Pages (GPL v3) et a2a (GPL v3) en `utilise`, jamais en `necessite`.
- **Textes et photos des modèles de démarrage jamais versionnés** ; contenus de test écrits pour les tests, images de test générées par script.
- `tiptap_spip` n'est jamais modifié.
- Messages de commit sans trailer (ni `Co-Authored-By`, ni `Claude-Session`).
- Pièges SPIP : une balise à paramètres mange l'espace qui suit → écrire `[(#ENV{x})]` ; pas de boucle ni d'`INCLURE` dans `[( )]` (utiliser des boucles `CONDITION`) ; Playwright doit utiliser un user-agent de navigateur ordinaire (HeadlessChrome = robot, toujours servi depuis le cache).
- Outils : `SPIP_CLI="php /src/spip-cli-custom/bin/spip"`, `PW=<dossier avec node_modules/playwright, pixelmatch, pngjs>`, `WP=<racine du WordPress de test SQLite>` ; SPIP d'intégration dans `vendor/spip/spip`, servi sur `http://127.0.0.1:8404`.

## Fichiers

| Fichier | Rôle |
|---|---|
| `inc/astra_pages.php` (créé) | règles des pages uniques : réglages par page, dispositions, arbre des pages, accueil, contexte |
| `astra_pipelines.php` (créé) | pipelines `styliser`, `affiche_milieu`, `trig_supprimer_objets_lies` |
| `page_unique.html` (créé) | gabarit de page d'Astra |
| `sommaire.html` (modifié) | accueil statique ou derniers articles |
| `inclure/sommaire-articles.html` (créé) | ancien contenu de `sommaire.html` (derniers articles) |
| `inclure/hfb/menu-items.html` (modifié) | source `pages` à deux niveaux |
| `formulaires/configurer_astra.html`, `formulaires/configurer_astra.php` (créé) | réglage de l'accueil |
| `formulaires/editer_astra_page.html`, `.php` (créés) | réglages par page |
| `prive/squelettes/inclure/astra-page.html` (créé) | encart du formulaire sur la fiche de la page |
| `astra_fonctions.php` (modifié) | filtres publics : `astra_body_class` (cas `page`), `astra_accueil_page`, accès aux fonctions de `inc/astra_pages.php` |
| `css/astra-blocs.css` (généré) | CSS des blocs |
| `inclure/head.html` (modifié) | chargement de `css/astra-blocs.css` |
| `css/astra-spip.css` (modifié) | correspondances SPIP des blocs (B2), seulement pour des écarts mesurés |
| `tests/references/extraire_css_blocs.py` (créé) | générateur du CSS des blocs |
| `tests/references/test_extraire_css_blocs.py` (créé) | tests unitaires du générateur |
| `tests/references/wp/commun.php` (modifié), `P1-page.*`, `P2-accueil.*`, `P3-page-pleine.*` (créés) | références WordPress des pages |
| `tests/references/capturer.sh` (modifié) | chemin capturé lu dans le JSON |
| `tests/comparaison/configurations.php`, `appliquer.sh`, `comparer_html.php`, `comparer_captures.*` (modifiés) | configurations P1–P3, contenu de page et classes du `body` comparés |
| `scripts/install-spip-test.sh` (modifié) | a2a et pages de test |
| `scripts/install-wp-blocs.sh`, `scripts/install-spip-blocs.sh` (créés) | WordPress MySQL des blocs et SPIP importé par wp2spip |
| `tests/references/blocs/generer_images.php`, `images/*.png`, `page.php`, `importe.html`, `pertes.json` (créés) | page de démonstration, images de test, HTML de cascade, pertes caractérisées |
| `tests/comparaison/comparer_blocs.cjs`, `comparer_medias.php` (créés) | comparaison bloc par bloc et des médias |
| `tests/e2e/cascade_blocs.cjs` (créé) | test de cascade du CSS généré |
| `tests/unit/PagesTest.php`, `tests/integration/Pages*Test.php` (créés) | tests PHPUnit |

---

### Task 1 : environnement de test — a2a, pages de test, déclarations

**Files:**
- Modify: `scripts/install-spip-test.sh` (bloc « Plugin Pages » et insertion des contenus)
- Modify: `paquet.xml`
- Create: `tests/integration/PagesEnvironnementTest.php`

**Interfaces:**
- Produces : dans le SPIP d'intégration, plugins `pages` et `a2a` actifs ; pages uniques publiées, `accepter_forum = 'non'`, champ `page` : `mentions` (« Mentions légales »), `notre-jardin` (« Notre jardin »), `potager-collectif` (« Potager collectif »), `verger` (« Verger »), `plein-ecran` (« Plein écran ») ; liens a2a `sous_page` notre-jardin → potager-collectif (rang 1), notre-jardin → verger (rang 2). Texte de chaque page : `Texte de la page.`

- [ ] **Step 1 : test d'environnement (échoue)**

```php
<?php

use PHPUnit\Framework\TestCase;

/** Environnement d'intégration des pages uniques (scripts/install-spip-test.sh). */
final class PagesEnvironnementTest extends TestCase {

	public function testPluginsActifs(): void {
		$this->assertTrue((bool) test_plugin_actif('pages'));
		$this->assertTrue((bool) test_plugin_actif('a2a'));
	}

	public function testPagesEtSousPages(): void {
		$ids = [];
		foreach (['mentions', 'notre-jardin', 'potager-collectif', 'verger', 'plein-ecran'] as $page) {
			$ids[$page] = (int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', "statut='publie'", 'page=' . sql_quote($page)]);
			$this->assertGreaterThan(0, $ids[$page], $page);
		}
		$liens = sql_allfetsel('id_article_lie', 'spip_articles_lies', ['id_article=' . $ids['notre-jardin'], "type_liaison='sous_page'"], '', 'rang');
		$this->assertSame([$ids['potager-collectif'], $ids['verger']], array_map('intval', array_column($liens, 'id_article_lie')));
	}
}
```

- [ ] **Step 2 : lancer, constater l'échec**

Run: `composer tests-integration -- --filter PagesEnvironnementTest`
Expected: FAIL (`a2a` inactif, pages absentes).

- [ ] **Step 3 : script d'installation**

Dans `scripts/install-spip-test.sh`, remplacer le bloc qui télécharge Pages par une fonction générique, puis activer les deux plugins :

```sh
# Plugins Pages (GPL v3) et a2a (GPL v3) : archives récupérées directement sur files.spip.org,
# plugins:svp:telecharger ayant échoué ici avec le dépôt plugins.spip.net (« n'est pas référencé »).
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
```

Vérifier le nom du dossier contenu dans l'archive a2a (`unzip -l`) et corriger le troisième argument s'il diffère de `a2a-4.2.1`.

Dans le `php:eval` des contenus, remplacer la ligne de la page « Mentions légales » par :

```php
// Pages uniques (plugin Pages) : hors rubrique, id_rubrique = -1. Mêmes titres et textes que
// les références WordPress (tests/references/wp/commun.php, ref_pages()).
sql_delete("spip_articles_lies");
$page = function ($titre, $identifiant) use ($date) {
	return sql_insertq("spip_articles", ["titre" => $titre, "texte" => "Texte de la page.", "id_rubrique" => -1, "page" => $identifiant, "statut" => "publie", "accepter_forum" => "non", "date" => $date, "lang" => "fr"]);
};
$page("Mentions légales", "mentions");
$jardin = $page("Notre jardin", "notre-jardin");
foreach ([1 => $page("Potager collectif", "potager-collectif"), 2 => $page("Verger", "verger")] as $rang => $enfant) {
	sql_insertq("spip_articles_lies", ["id_article" => $jardin, "id_article_lie" => $enfant, "rang" => $rang, "type_liaison" => "sous_page"]);
}
$page("Plein écran", "plein-ecran");
```

- [ ] **Step 4 : déclarations du plugin**

Dans `paquet.xml`, après la ligne `<licence …>` :

```xml
	<utilise nom="pages" compatibilite="[2.0.0;]" />
	<utilise nom="a2a" compatibilite="[4.2.0;]" />
```

- [ ] **Step 5 : réinstaller et lancer les tests**

Run: `SPIP_CLI="php /src/spip-cli-custom/bin/spip" composer install-spip-test && composer tests-integration`
Expected: PASS, dont `PagesEnvironnementTest` (2 tests) et `HfbPagesTest` (inchangé).

- [ ] **Step 6 : commit**

```bash
git add scripts/install-spip-test.sh paquet.xml tests/integration/PagesEnvironnementTest.php
git commit -m "Installer a2a et les pages de test dans l'environnement d'intégration"
```

---

### Task 2 : références WordPress P1–P3 et outillage de comparaison

**Files:**
- Modify: `tests/references/wp/commun.php`
- Create: `tests/references/wp/P1-page.php`, `P1-page.json`, `P2-accueil.php`, `P2-accueil.json`, `P3-page-pleine.php`, `P3-page-pleine.json`
- Modify: `tests/references/capturer.sh`, `tests/references/extraire_css.py` (liste des configurations exclues), `tests/references/README.md`
- Modify: `tests/comparaison/configurations.php`, `tests/comparaison/appliquer.sh`, `tests/comparaison/comparer_html.php`, `tests/comparaison/comparer_captures.sh`, `tests/comparaison/comparer_captures.cjs`

**Interfaces:**
- Consumes : pages de la tâche 1 (mêmes titres et identifiants).
- Produces :
  - `tests/references/sorties/P{1,2,3}-*/accueil.html` : page capturée au chemin `chemin` du JSON (nom de fichier conservé pour tous les outils) ;
  - entrées `astra_configurations_reference()['P1-page' | 'P2-accueil' | 'P3-page-pleine']` avec les clés supplémentaires `page` (identifiant Pages de la page comparée, absent pour P2), `astra` (réglages `astra/<clé>` ; valeur `@<identifiant>` = id de la page) et `pages` (réglages par page, indexés par identifiant) ;
  - `appliquer.sh` efface à chaque application `astra/accueil_type`, `astra/accueil_page`, `astra/pages`, puis écrit `astra`, `pages` et `hfb` de la configuration ;
  - `comparer_html.php` compare en plus `body` (classes filtrées) et `contenu` (`#content`, jusqu'à `.entry-content` incluse) pour les configurations qui ont une clé `page` ou `astra` ;
  - `comparer_captures.cjs` accepte un 5e argument `contenu` qui ajoute les vues `contenu-ordinateur` et `contenu-mobile` (`#primary`).

- [ ] **Step 1 : pages et menu de pages côté WordPress**

Ajouter à `tests/references/wp/commun.php` :

```php
// Pages uniques des références P* : mêmes titres, textes et ordre que le SPIP d'intégration
// (scripts/install-spip-test.sh). Recréées à l'identique à chaque appel ; la page d'exemple
// de WordPress est supprimée pour que les menus de pages soient comparables.
function ref_pages(): array {
	if ($exemple = get_page_by_path('sample-page')) {
		wp_delete_post($exemple->ID, true);
	}
	$ids = [];
	foreach ([
		['mentions', 'Mentions légales', ''],
		['notre-jardin', 'Notre jardin', ''],
		['potager-collectif', 'Potager collectif', 'notre-jardin'],
		['verger', 'Verger', 'notre-jardin'],
		['plein-ecran', 'Plein écran', ''],
	] as $ordre => [$slug, $titre, $parent]) {
		$existante = get_page_by_path($parent ? "$parent/$slug" : $slug);
		$donnees = [
			'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $titre,
			'post_content' => 'Texte de la page.', 'comment_status' => 'closed', 'menu_order' => $ordre,
			'post_parent' => $parent ? $ids[$parent] : 0,
		];
		if ($existante) {
			$donnees['ID'] = $existante->ID;
		}
		$ids[$slug] = wp_insert_post($donnees);
		delete_post_meta($ids[$slug], 'site-post-title');
		delete_post_meta($ids[$slug], 'ast-site-content-layout');
	}
	return $ids;
}

// Menu de pages à deux niveaux, dans l'ordre du menu SPIP (par titre ; sous-pages par rang).
function ref_menu_pages(string $emplacement): void {
	$ids = ref_pages();
	$menu = wp_get_nav_menu_object('Pages');
	if (!$menu) {
		$id = wp_create_nav_menu('Pages');
		$ajouter = function (string $slug, int $parent = 0) use ($id, $ids) {
			return wp_update_nav_menu_item($id, 0, [
				'menu-item-title' => get_the_title($ids[$slug]), 'menu-item-object' => 'page',
				'menu-item-object-id' => $ids[$slug], 'menu-item-type' => 'post_type',
				'menu-item-parent-id' => $parent, 'menu-item-status' => 'publish',
			]);
		};
		$ajouter('mentions');
		$jardin = $ajouter('notre-jardin');
		$ajouter('potager-collectif', $jardin);
		$ajouter('verger', $jardin);
		$ajouter('plein-ecran');
		$menu = wp_get_nav_menu_object('Pages');
	}
	$emplacements = get_theme_mod('nav_menu_locations', []);
	$emplacements[$emplacement] = $menu->term_id;
	set_theme_mod('nav_menu_locations', $emplacements);
}
```

Dans `ref_etat_initial()`, après `remove_theme_mods();`, ajouter :

```php
	update_option('show_on_front', 'posts');
	update_option('page_on_front', 0);
```

- [ ] **Step 2 : configurations P1–P3 côté WordPress**

`tests/references/wp/P1-page.php` :

```php
<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu_pages('primary');
ref_menu_pages('mobile_menu');
```

`tests/references/wp/P1-page.json` :

```json
{
	"chemin": "/?pagename=notre-jardin",
	"ordinateur": [
		"article.page .entry-title",
		".ast-builder-menu-1 .current-menu-item"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		".ast-builder-menu-1 .menu-item-has-children .sub-menu"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	]
}
```

`tests/references/wp/P2-accueil.php` :

```php
<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu_pages('primary');
ref_menu_pages('mobile_menu');
update_option('show_on_front', 'page');
update_option('page_on_front', get_page_by_path('notre-jardin')->ID);
```

`tests/references/wp/P2-accueil.json` : identique à `P1-page.json` avec `"chemin": "/"`.

`tests/references/wp/P3-page-pleine.php` :

```php
<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu_pages('primary');
ref_menu_pages('mobile_menu');
$id = get_page_by_path('plein-ecran')->ID;
update_post_meta($id, 'site-post-title', 'disabled');
update_post_meta($id, 'ast-site-content-layout', 'full-width-container');
```

`tests/references/wp/P3-page-pleine.json` :

```json
{
	"chemin": "/?pagename=plein-ecran",
	"ordinateur": [
		"article.page .entry-content"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		"body.ast-page-builder-template"
	],
	"menuMobile": "dropdown",
	"menuOuvert": []
}
```

- [ ] **Step 3 : `capturer.sh` lit le chemin**

Remplacer les deux lignes `curl` par :

```bash
	chemin=$(php -r '$j = json_decode(file_get_contents($argv[1]), true); echo $j["chemin"] ?? "/";' "$ICI/wp/$conf.json")
	curl -sf "$URL$chemin" -o "$sortie/accueil.html"
	curl -sf "$URL/?p=1" -o "$sortie/article.html"
```

et l'appel de `capture.cjs` par `node "$ICI/capture.cjs" "$URL$chemin" "$sortie" "$ICI/wp/$conf.json"`. Dans `tests/references/README.md`, documenter : « `accueil.html` est la page capturée au chemin `chemin` du JSON (l'accueil par défaut) ».

`extraire_css.py` ne doit pas lire les configurations P : sa liste `CONFIGURATIONS` est explicite (R1–R4), rien à changer ; l'indiquer dans sa docstring (« configurations du builder seulement »).

- [ ] **Step 4 : capturer les références**

Run: `WP=$WP PW=$PW tests/references/capturer.sh P1-page P2-accueil P3-page-pleine R0-defaut`
Expected: `P1-page : OK (…)`, `P2-accueil : OK`, `P3-page-pleine : OK`, `R0-defaut : OK` (R0 recapturée pour s'assurer que `ref_pages()` ne la modifie pas : `git diff --stat tests/references/sorties` est sans objet, le dossier n'est pas versionné ; comparer à la main `R0-defaut/accueil.html` avant et après, seules les classes `menu-item-N` peuvent changer).

Relever et noter dans `tests/references/README.md` les classes du `body` de chaque page (elles servent à la tâche 3) :

```bash
for c in P1-page P2-accueil P3-page-pleine; do grep -o '<body[^>]*>' tests/references/sorties/$c/accueil.html; done
```

- [ ] **Step 5 : configurations SPIP correspondantes**

Ajouter dans `astra_configurations_reference()` (`tests/comparaison/configurations.php`) :

```php
		'P1-page' => [
			'page' => 'notre-jardin',
			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
		],
		'P2-accueil' => [
			'astra' => ['accueil_type' => 'page', 'accueil_page' => '@notre-jardin'],
			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
		],
		'P3-page-pleine' => [
			'page' => 'plein-ecran',
			'pages' => ['plein-ecran' => ['titre' => 'masquer', 'disposition' => 'pleine']],
			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
		],
```

Ajouter dans le même fichier :

```php
/** Partie « builder » d'une configuration : sans les clés propres aux comparaisons de pages. */
function astra_configuration_hfb(array $c): array {
	unset($c['page'], $c['astra'], $c['pages']);
	return $c;
}
```

- [ ] **Step 6 : `appliquer.sh`**

Remplacer le `php:eval` par :

```bash
$SPIP_CLI php:eval "
include_spip('inc/config');
require_once '$ICI/configurations.php';
\$c = astra_configurations_reference();
if (!isset(\$c['$2'])) { echo 'configuration-inconnue'; return; }
\$conf = \$c['$2'];
\$id = function (\$identifiant) {
	return (int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', 'page=' . sql_quote(\$identifiant)]);
};
effacer_config('astra/accueil_type');
effacer_config('astra/accueil_page');
effacer_config('astra/pages');
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
```

Les comparateurs calculent eux-mêmes le chemin de la page comparée (steps 7 et 8).

- [ ] **Step 7 : `comparer_html.php`**

Après `require_once $ici . '/configurations.php';`, ajouter :

```php
/** Chemin SPIP de la page comparée : page unique de la configuration, sinon l'accueil. */
function chemin_spip(string $racine, array $conf): string {
	if (empty($conf['page'])) {
		return '/';
	}
	$php = 'include_spip("inc/filtres"); echo generer_objet_url((int) sql_getfetsel("id_article", "spip_articles", ["id_rubrique=-1", "page=" . sql_quote(' . var_export($conf['page'], true) . ')]), "article", "", "", false);';
	$url = trim((string) shell_exec('cd ' . escapeshellarg($racine) . ' && ' . getenv('SPIP_CLI') . ' php:eval ' . escapeshellarg($php)));
	return '/' . ltrim(preg_replace('#^https?://[^/]+#', '', $url), '/');
}

/** Classes du body comparables : sans celles que WordPress seul produit. */
function classes_body(DOMXPath $x): array {
	$b = $x->query('//body')->item(0);
	$c = $b ? preg_split('/\s+/', trim($b->getAttribute('class'))) : [];
	$c = array_values(array_filter($c, function ($x) {
		return $x !== '' && !preg_match('/^(wp-|group-blog$|[a-z]+-template-default$)/', $x);
	}));
	sort($c);
	return $c;
}
```

Remplacer `function est_element(…)` par deux prédicats et adapter `squelette()` :

```php
/** Racines d'éléments du builder : on compare leur premier niveau, pas leur contenu. */
function est_element(DOMElement $e): bool {
	return (bool) preg_match('/(^| )(ast-builder-layout-element|footer-widget-area|header-widget-area)( |$)/', $e->getAttribute('class'));
}

/** Contenu rédactionnel : la balise elle-même est comparée, pas ses enfants. */
function est_contenu(DOMElement $e): bool {
	return (bool) preg_match('/(^| )entry-content( |$)/', $e->getAttribute('class'));
}
```

et dans `squelette()`, remplacer la ligne `$suite = …` par :

```php
	if (est_contenu($e)) {
		return $lignes;
	}
	$suite = $niveaux > 0 ? $niveaux - 1 : (est_element($e) ? 1 : -1);
```

Remplacer `parties()` par une version à deux arguments :

```php
function parties(string $html, bool $page): array {
	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	$doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
	libxml_clear_errors();
	$x = new DOMXPath($doc);
	$chemins = ['entete' => '//header[@id="masthead"]', 'panneau' => '//*[@id="ast-mobile-popup-wrapper"]', 'pied' => '//footer[@id="colophon"]'];
	if ($page) {
		$chemins['contenu'] = '//*[@id="content"]';
	}
	$resultat = [];
	foreach ($chemins as $nom => $chemin) {
		$n = $x->query($chemin)->item(0);
		$resultat[$nom] = $n ? squelette($n) : [];
	}
	if ($page) {
		$resultat['body'] = classes_body($x);
	}
	return $resultat;
}
```

Dans la boucle principale, remplacer les deux appels :

```php
		$conf_c = astra_configurations_reference()[$conf];
		$page = isset($conf_c['page']) || isset($conf_c['astra']);
		$spip = parties((string) file_get_contents(rtrim($url, '/') . chemin_spip($racine, $conf_c)), $page);
		$wp = parties((string) file_get_contents($reference), $page);
```

- [ ] **Step 8 : `comparer_captures`**

Dans `comparer_captures.cjs`, lire un 5e argument et ajouter les vues de contenu :

```js
const [urlWp, urlSpip, dossier, menuMobile, contenu] = process.argv.slice(2);
```

```js
if (contenu === 'contenu') {
  VUES.push({ nom: 'contenu-ordinateur', largeur: 1280, selecteur: '#primary' });
  VUES.push({ nom: 'contenu-mobile', largeur: 390, selecteur: '#primary' });
}
```

(placer ce bloc juste après la déclaration de `VUES`). Dans `comparer_captures.sh`, remplacer la ligne `NODE_PATH=… node …` par :

```bash
	chemin_wp=$(php -r '$j = json_decode(file_get_contents($argv[1]), true); echo $j["chemin"] ?? "/";' "$ICI/../references/wp/$conf.json")
	identifiant=$(php -r 'require $argv[1]; echo astra_configurations_reference()[$argv[2]]["page"] ?? "";' "$ICI/configurations.php" "$conf")
	chemin_spip=/
	contenu=
	if [ -n "$identifiant" ]; then
		chemin_spip=/$( (cd "$RACINE" && $SPIP_CLI php:eval "include_spip('inc/filtres'); echo generer_objet_url((int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', 'page=' . sql_quote('$identifiant')]), 'article', '', '', false);") | sed -E 's#^https?://[^/]+/?##')
	fi
	case $conf in P*) contenu=contenu ;; esac
	NODE_PATH="$PW/node_modules" node "$ICI/comparer_captures.cjs" "$URL_WP$chemin_wp" "$URL_SPIP$chemin_spip" "$SORTIES/$conf" "$menu" "$contenu" || code=1
```

- [ ] **Step 9 : non-régression de l'outillage**

Run:
```bash
SPIP_CLI="php /src/spip-cli-custom/bin/spip" php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404 R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique
```
Expected: 15 lignes « identique » (rien ne change pour les R).

Run: `SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404 P1-page`
Expected: FAIL sur `contenu` et `body` (le gabarit de page n'existe pas encore) — c'est l'état de départ de la tâche 3.

- [ ] **Step 10 : commit**

```bash
git add tests/references tests/comparaison
git commit -m "Ajouter les références WordPress des pages uniques"
```

---

### Task 3 : règles des pages et gabarit de page (A1, A4 côté affichage)

**Files:**
- Create: `inc/astra_pages.php`, `astra_pipelines.php`, `page_unique.html`, `tests/unit/PagesTest.php`, `tests/integration/PagesGabaritTest.php`
- Modify: `astra_fonctions.php` (inclusion, `astra_body_class`, filtres), `paquet.xml` (pipeline `styliser`), `phpunit.xml` (rien) 

**Interfaces:**
- Produces (dans `inc/astra_pages.php`) :
  - `astra_page_reglages_defaut(): array` → `['titre' => 'afficher', 'disposition' => 'defaut']`
  - `astra_page_dispositions(): array` → `['defaut', 'normale', 'etroite', 'pleine']`
  - `astra_page_normaliser($reglages): array` (valeurs inconnues → défaut)
  - `astra_page_disposition_effective(string $disposition): string` (`defaut` → `normale`)
  - `astra_page_classes_disposition(string $disposition): array` (`normale` → `['ast-plain-container']`, `etroite` → `['ast-narrow-container']`, `pleine` → `['ast-page-builder-template']`)
  - `astra_page_classes_body(int $id, array $reglages, bool $accueil): string`
  - `astra_page_contexte(int $id_article, array $reglages, bool $accueil): array` → `['type_page', 'id_article', 'titre', 'disposition']`
  - `astra_est_page_unique(int $id_article): bool` (SQL) ; `astra_page_reglages(int $id_article): array` (config)
- Produces (squelettes) : `page_unique.html`, appelé avec `id_article` et facultativement `accueil=oui`.

- [ ] **Step 1 : tests unitaires (échouent)**

`tests/unit/PagesTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_pages.php';

final class PagesTest extends TestCase {

	public function testNormaliser(): void {
		$this->assertSame(['titre' => 'afficher', 'disposition' => 'defaut'], astra_page_normaliser(null));
		$this->assertSame(['titre' => 'masquer', 'disposition' => 'pleine'], astra_page_normaliser(['titre' => 'masquer', 'disposition' => 'pleine', 'autre' => 1]));
		$this->assertSame(['titre' => 'afficher', 'disposition' => 'defaut'], astra_page_normaliser(['titre' => 'x', 'disposition' => 'y']));
	}

	public function testDispositions(): void {
		$this->assertSame('normale', astra_page_disposition_effective('defaut'));
		$this->assertSame('etroite', astra_page_disposition_effective('etroite'));
		$this->assertSame(['ast-plain-container'], astra_page_classes_disposition('normale'));
		$this->assertSame(['ast-narrow-container'], astra_page_classes_disposition('etroite'));
		$this->assertSame(['ast-page-builder-template'], astra_page_classes_disposition('pleine'));
	}

	public function testClassesBody(): void {
		$c = explode(' ', astra_page_classes_body(7, ['titre' => 'afficher', 'disposition' => 'defaut'], false));
		foreach (['page-template-default', 'page', 'page-id-7', 'ast-desktop', 'ast-plain-container', 'ast-no-sidebar', 'ast-single-post', 'ast-hfb-header', 'ast-normal-title-enabled'] as $attendue) {
			$this->assertContains($attendue, $c);
		}
		$this->assertNotContains('home', $c);
		$c = explode(' ', astra_page_classes_body(7, ['titre' => 'masquer', 'disposition' => 'pleine'], true));
		$this->assertContains('home', $c);
		$this->assertContains('ast-page-builder-template', $c);
		$this->assertNotContains('ast-plain-container', $c);
	}

	public function testContexte(): void {
		$this->assertSame(
			['type_page' => 'page', 'id_article' => 7, 'titre' => 'afficher', 'disposition' => 'normale'],
			astra_page_contexte(7, ['titre' => 'afficher', 'disposition' => 'defaut'], false)
		);
		$this->assertSame('sommaire', astra_page_contexte(7, astra_page_reglages_defaut(), true)['type_page']);
	}
}
```

- [ ] **Step 2 : lancer, constater l'échec**

Run: `composer tests-unit -- --filter PagesTest`
Expected: FAIL (fichier `inc/astra_pages.php` absent).

- [ ] **Step 3 : `inc/astra_pages.php`**

```php
<?php
/**
 * Astra pour SPIP — pages uniques (plugin Pages) : réglages par page, dispositions,
 * arbre des pages pour les menus, page d'accueil statique.
 *
 * @license GPL-3.0-or-later
 */

/** Réglages d'une page sans configuration propre. */
function astra_page_reglages_defaut(): array {
	return ['titre' => 'afficher', 'disposition' => 'defaut'];
}

/** Dispositions proposées pour une page. */
function astra_page_dispositions(): array {
	return ['defaut', 'normale', 'etroite', 'pleine'];
}

/** Réglages valides d'une page : clés connues seulement, valeurs inconnues remplacées par le défaut. */
function astra_page_normaliser($reglages): array {
	$reglages = is_array($reglages) ? $reglages : [];
	$r = astra_page_reglages_defaut();
	if (in_array($reglages['titre'] ?? '', ['afficher', 'masquer'], true)) {
		$r['titre'] = $reglages['titre'];
	}
	if (in_array($reglages['disposition'] ?? '', astra_page_dispositions(), true)) {
		$r['disposition'] = $reglages['disposition'];
	}
	return $r;
}

/** Disposition appliquée : celle des pages d'Astra par défaut (normal-width-container → plain-container). */
function astra_page_disposition_effective(string $disposition): string {
	return $disposition === 'defaut' ? 'normale' : $disposition;
}

/** Classes du body d'Astra pour une disposition (inc/markup-extras.php d'Astra 4.11.0). */
function astra_page_classes_disposition(string $disposition): array {
	$classes = ['normale' => 'ast-plain-container', 'etroite' => 'ast-narrow-container', 'pleine' => 'ast-page-builder-template'];
	return [$classes[astra_page_disposition_effective($disposition)] ?? 'ast-plain-container'];
}

/**
 * Classes du body d'une page, comme Astra sur WordPress (référence : tests/references/sorties/P*).
 * Les pages n'ont pas de barre latérale par défaut dans Astra.
 */
function astra_page_classes_body(int $id, array $reglages, bool $accueil): string {
	$classes = $accueil ? ['home'] : [];
	array_push($classes, 'page-template-default', 'page', 'page-id-' . $id, 'ast-desktop');
	$classes = array_merge($classes, astra_page_classes_disposition($reglages['disposition']));
	array_push($classes, 'ast-no-sidebar', 'astra-' . (defined('_ASTRA_VERSION_THEME') ? _ASTRA_VERSION_THEME : '4.11.0'), 'ast-single-post', 'ast-inherit-site-logo-transparent', 'ast-hfb-header');
	if ($reglages['titre'] === 'afficher') {
		$classes[] = 'ast-normal-title-enabled';
	}
	return implode(' ', $classes);
}

/** Contexte transmis par le gabarit de page aux inclusions (spec, A4). */
function astra_page_contexte(int $id_article, array $reglages, bool $accueil): array {
	return [
		'type_page' => $accueil ? 'sommaire' : 'page',
		'id_article' => $id_article,
		'titre' => $reglages['titre'],
		'disposition' => astra_page_disposition_effective($reglages['disposition']),
	];
}

/** Vrai si l'article est une page unique (hors rubrique). */
function astra_est_page_unique(int $id_article): bool {
	return $id_article > 0 && (int) sql_getfetsel('id_rubrique', 'spip_articles', 'id_article=' . $id_article) === -1;
}

/** Réglages effectifs d'une page (méta astra/pages/<id>). */
function astra_page_reglages(int $id_article): array {
	include_spip('inc/config');
	return astra_page_normaliser(lire_config('astra/pages/' . $id_article));
}
```

Comme `inc/astra_hfb.php`, le fichier n'a pas de garde `_ECRIRE_INC_VERSION` : les tests unitaires le chargent sans SPIP (les fonctions qui appellent `sql_*` ou `lire_config` ne sont alors pas appelées).

- [ ] **Step 4 : tests unitaires**

Run: `composer tests-unit`
Expected: PASS (37 tests existants + 4).

- [ ] **Step 5 : test d'intégration du gabarit (échoue)**

`tests/integration/PagesGabaritTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

/** Gabarit de page (page_unique.html) et choix par le pipeline styliser. */
final class PagesGabaritTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/HfbRenduTest.php';
		include_spip('inc/astra_pages');
	}

	protected function tearDown(): void {
		effacer_config('astra/pages');
	}

	public static function id(string $page): int {
		return (int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', 'page=' . sql_quote($page)]);
	}

	private function page(int $id): DOMXPath {
		return HfbRenduTest::xpath(recuperer_fond('article', ['id_article' => $id]));
	}

	public function testStyliserChoisitLeGabaritDePage(): void {
		$styliser = charger_fonction('styliser', 'public');
		$this->assertStringEndsWith('page_unique', $styliser('article', ['id_article' => self::id('notre-jardin')])[0] ?? '');
		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'id_rubrique>0', '', 'id_article', '0,1');
		$this->assertStringEndsWith('/article', $styliser('article', ['id_article' => $article])[0] ?? '');
	}

	public function testBalisageDePage(): void {
		$id = self::id('notre-jardin');
		$x = $this->page($id);
		$this->assertSame(1, $x->query('//article[@id="post-' . $id . '"][contains(@class, "type-page")][@itemtype="https://schema.org/CreativeWork"]')->length);
		$this->assertSame('Notre jardin', trim($x->query('//article//header[contains(@class, "entry-header")]/h1[@class="entry-title"]')->item(0)->textContent));
		$this->assertSame(1, $x->query('//div[contains(@class, "entry-content")][@data-ast-blocks-layout="true"]')->length);
		$this->assertSame(0, $x->query('//*[contains(@class, "entry-meta")]')->length);
		$this->assertSame(0, $x->query('//nav[contains(@class, "post-navigation")]')->length);
		$this->assertSame(0, $x->query('//*[@id="secondary"]')->length);
		$classes = explode(' ', $x->query('//body')->item(0)->getAttribute('class'));
		$this->assertContains('page-id-' . $id, $classes);
		$this->assertContains('ast-plain-container', $classes);
		$this->assertSame('https://schema.org/WebPage', $x->query('//body')->item(0)->getAttribute('itemtype'));
	}

	public function testTitreMasqueEtDisposition(): void {
		$id = self::id('plein-ecran');
		ecrire_config('astra/pages/' . $id, ['titre' => 'masquer', 'disposition' => 'pleine']);
		$x = $this->page($id);
		$this->assertSame(0, $x->query('//h1[@class="entry-title"]')->length, 'Titre non produit, comme dans Astra');
		$classes = explode(' ', $x->query('//body')->item(0)->getAttribute('class'));
		$this->assertContains('ast-page-builder-template', $classes);
		$this->assertNotContains('ast-normal-title-enabled', $classes);
		ecrire_config('astra/pages/' . $id, ['titre' => 'afficher', 'disposition' => 'etroite']);
		$this->assertContains('ast-narrow-container', explode(' ', $this->page($id)->query('//body')->item(0)->getAttribute('class')));
	}
}
```

Run: `composer tests-integration -- --filter PagesGabaritTest`
Expected: FAIL (`page_unique` jamais choisi).

- [ ] **Step 6 : pipeline `styliser`**

`astra_pipelines.php` :

```php
<?php
/**
 * Astra pour SPIP — pipelines.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Gabarit de page pour les pages uniques (plugin Pages).
 *
 * Priorité : variante article=<page>.html choisie par Pages, puis page_unique.html du site,
 * puis celui du plugin (find_in_path). On n'agit que si le squelette retenu est encore
 * article.html : une variante déjà choisie n'est jamais remplacée, et si ce pipeline passe
 * avant celui de Pages, Pages remplace de toute façon le résultat par sa variante.
 */
function astra_styliser($flux) {
	if (
		test_espace_prive()
		|| ($flux['args']['fond'] ?? '') !== 'article'
		|| !defined('_DIR_PLUGIN_PAGES')
		|| basename((string) $flux['data']) !== 'article'
	) {
		return $flux;
	}
	include_spip('inc/astra_pages');
	if (!astra_est_page_unique((int) ($flux['args']['contexte']['id_article'] ?? 0))) {
		return $flux;
	}
	$ext = $flux['args']['ext'];
	if ($f = find_in_path("page_unique.$ext")) {
		$flux['data'] = substr($f, 0, -strlen(".$ext"));
	}
	return $flux;
}
```

Dans `paquet.xml`, après les `<utilise>` :

```xml
	<pipeline nom="styliser" inclure="astra_pipelines.php" />
```

Puis `$SPIP_CLI plugins:activer astra -y` (relit les déclarations) depuis `vendor/spip/spip`.

- [ ] **Step 7 : fonctions publiques**

Dans `astra_fonctions.php`, après les `include_spip` existants : `include_spip('inc/astra_pages');`, puis ajouter :

```php
/** Réglages effectifs d'une page unique : `#ID_ARTICLE|astra_page_reglages`. */
function filtre_astra_page_reglages_dist($id_article): array {
	return astra_page_reglages((int) $id_article);
}

/** Contexte des inclusions du gabarit de page : `#ID_ARTICLE|astra_page_contexte{#ENV{accueil}}`. */
function filtre_astra_page_contexte_dist($id_article, $accueil = ''): array {
	return astra_page_contexte((int) $id_article, astra_page_reglages((int) $id_article), $accueil === 'oui');
}
```

Dans `astra_body_class()`, ajouter un troisième paramètre et un retour anticipé en tête de fonction :

```php
function astra_body_class(string $type, $id = 0, string $accueil = ''): string {
	$id = (int) $id;
	if ($type === 'page') {
		return astra_page_classes_body($id, astra_page_reglages($id), $accueil === 'oui');
	}
```

(le reste de la fonction est inchangé ; mettre à jour son bloc de documentation : « `page` : page unique, `$accueil = 'oui'` sur l'accueil statique »).

- [ ] **Step 8 : `page_unique.html`**

```html
[(#REM)

	Page unique (plugin Pages) : gabarit de page d'Astra (page.php, content-page.php).
	Choisi par le pipeline styliser (astra_pipelines.php) pour les articles hors rubrique.

	Paramètres : id_article ; accueil = 'oui' sur l'accueil statique (sommaire.html).

]<BOUCLE_page(ARTICLES){id_article}{id_rubrique=-1}>
#SET{r,#ID_ARTICLE|astra_page_reglages}
#SET{ctx,#ID_ARTICLE|astra_page_contexte{#ENV{accueil}}}
<!DOCTYPE html>
<html dir="#LANG_DIR" lang="#LANG" class="[(#LANG_DIR)][ (#LANG)] no-js">
<head>
<title>[(#TITRE|supprimer_numero|textebrut)] – [(#NOM_SITE_SPIP|textebrut)]</title>
[<meta name="description" content="(#INTRODUCTION{150}|attribut_html)" />]
[(#ENV{accueil}|!={oui}|et{#ID_ARTICLE|=={#VAL|astra_accueil_page}})<link rel="canonical" href="#URL_SITE_SPIP/" />]
<INCLURE{fond=inclure/head} />
</head>
<body itemtype="https://schema.org/WebPage" itemscope="itemscope" class="[(#VAL{page}|astra_body_class{#ID_ARTICLE,#ENV{accueil}})]">
<INCLURE{fond=inclure/header}{type_page=#GET{ctx}|table_valeur{type_page}}{id_article}{titre_page=#GET{ctx}|table_valeur{titre}}{disposition=#GET{ctx}|table_valeur{disposition}} />
<div id="content" class="site-content">
	<div class="ast-container">
		<div id="primary" class="content-area primary">
			<main id="main" class="site-main">
				<article class="post-#ID_ARTICLE page type-page status-publish ast-article-single" id="post-#ID_ARTICLE" itemtype="https://schema.org/CreativeWork" itemscope="itemscope">
					<BOUCLE_titre(CONDITION){si #GET{r}|table_valeur{titre}|=={afficher}}>
					<header class="entry-header[ (#LOGO_ARTICLE|?{'',ast-no-thumbnail})]">
						[<div class="post-thumb-img-content post-thumb">(#LOGO_ARTICLE|image_reduire{1200}|inserer_attribut{class,attachment-large size-large wp-post-image}|inserer_attribut{alt,''}|inserer_attribut{itemprop,image})</div>]
						<h1 class="entry-title" itemprop="headline">[(#TITRE|supprimer_numero)]</h1>
					</header><!-- .entry-header -->
					</BOUCLE_titre>
					<div class="entry-content clear" data-ast-blocks-layout="true" itemprop="text">
						[<div class="chapo">(#CHAPO)</div>]
						#TEXTE
						[<div class="ps">(#PS)</div>]
						[<div class="notes"><h2 class="screen-reader-text"><:astra:notes:></h2>(#NOTES)</div>]
						<B_documents>
						<div class="ast-documents">
							<h2><:astra:documents_joints:></h2>
							<ul class="ast-documents-liste">
								<BOUCLE_documents(DOCUMENTS){id_article}{mode=document}{vu=non}{par num titre, titre}>
								<li><a href="#URL_DOCUMENT" type="#MIME_TYPE">[(#TITRE|sinon{#FICHIER|basename})]</a> <small>(#TYPE_DOCUMENT[, (#TAILLE|taille_en_octets)])</small>[<div class="ast-document-descriptif">(#DESCRIPTIF)</div>]</li>
								</BOUCLE_documents>
							</ul>
						</div>
						</B_documents>
					</div><!-- .entry-content .clear -->
				</article><!-- #post-## -->
				<INCLURE{fond=inclure/commentaires}{id_article}{id_forum} />
			</main><!-- #main -->
		</div><!-- #primary -->
	</div> <!-- ast-container -->
</div><!-- #content -->
<INCLURE{fond=inclure/footer}{type_page=#GET{ctx}|table_valeur{type_page}}{id_article} />
</body>
</html>
</BOUCLE_page>
```

`astra_accueil_page` est créé à la tâche 5 ; d'ici là, ajouter dès maintenant dans `astra_fonctions.php` la version minimale, complétée à la tâche 5 :

```php
/** Identifiant de la page d'accueil statique, 0 si l'accueil liste les articles. */
function astra_accueil_page($x = ''): int {
	return astra_conf('accueil_type') === 'page' ? (int) astra_conf('accueil_page') : 0;
}
```

et dans `astra_config_defaut()` : `'accueil_type' => 'articles',` et `'accueil_page' => '',`.

- [ ] **Step 9 : tests d'intégration et PHP 7.4**

Run: `composer tests-integration && for f in inc/astra_pages.php astra_pipelines.php astra_fonctions.php; do php7.4 -l $f; done`
Expected: PASS ; `No syntax errors detected` ×3.

- [ ] **Step 10 : comparaison P1 et P3 avec WordPress**

Run:
```bash
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404 P1-page P3-page-pleine
WP=$WP PW=$PW SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404 P1-page P3-page-pleine
```
Expected: `identique` pour `entete`, `panneau`, `pied`, `contenu`, `body` ; captures sous 1 %. Les sous-menus du menu de pages ne sont pas encore faits (tâche 4) : un écart limité au menu de l'en-tête (`entete`, et `menu-mobile` en capture) est attendu à cette étape et doit disparaître à la tâche 4. Tout autre écart (classes du `body`, balisage de l'article, largeur pleine) se corrige ici, en ajustant `astra_page_classes_body()` ou `page_unique.html` d'après `tests/references/sorties/P*/accueil.html`. Si `css/astra-dynamic.css` ne contient pas une règle de disposition présente dans la référence (capture de `contenu-*` différente avec un HTML identique), générer les règles manquantes avec `extraire_css.py` étendu par un argument `--configurations P1-page,P3-page-pleine --sortie css/astra-pages.css` et charger cette feuille dans `inclure/head.html` après `astra-hfb.css` (même méthode que pour le builder).

- [ ] **Step 11 : commit**

```bash
git add inc/astra_pages.php astra_pipelines.php page_unique.html astra_fonctions.php paquet.xml tests/unit/PagesTest.php tests/integration/PagesGabaritTest.php
git commit -m "Afficher les pages uniques avec le gabarit de page d'Astra"
```

---

### Task 4 : coexistence avec Pages et sous-pages dans le menu (A1 priorité, A2)

**Files:**
- Modify: `inc/astra_pages.php` (arbre), `inclure/hfb/menu-items.html` (source `pages`)
- Create: `tests/integration/PagesStyliserTest.php`, `tests/integration/PagesMenuTest.php`

**Interfaces:**
- Consumes : `astra_styliser()` (tâche 3), pages et liens de la tâche 1.
- Produces : `astra_pages_arbre(): array` → liste ordonnée de `['id' => int, 'enfants' => int[]]` ; racines = pages uniques publiées sans parente publiée ; enfants publiés par `rang`.

- [ ] **Step 1 : test de coexistence (échoue ou passe partiellement)**

`tests/integration/PagesStyliserTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

/** Priorité des squelettes : variante du plugin Pages, page_unique.html du site, puis d'Astra. */
final class PagesStyliserTest extends TestCase {

	private static array $crees = [];

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/PagesGabaritTest.php';
		include_spip('astra_pipelines');
		include_spip('pages_pipelines');
	}

	protected function tearDown(): void {
		foreach (self::$crees as $f) {
			@unlink($f);
		}
		self::$crees = [];
	}

	private function creer(string $nom): string {
		$dossier = _DIR_RACINE . 'squelettes';
		if (!is_dir($dossier)) {
			mkdir($dossier);
		}
		$f = "$dossier/$nom.html";
		file_put_contents($f, "<p>$nom</p>");
		self::$crees[] = $f;
		return substr($f, 0, -5);
	}

	/** Flux du pipeline styliser tel que le reçoivent les deux plugins pour le fond article. */
	private function flux(int $id): array {
		return ['args' => ['fond' => 'article', 'ext' => 'html', 'contexte' => ['id_article' => $id], 'connect' => ''], 'data' => _DIR_PLUGINS . 'astra/article'];
	}

	public function testVarianteDePagesPrioritaireDansLesDeuxOrdres(): void {
		$id = PagesGabaritTest::id('notre-jardin');
		$variante = $this->creer('article=notre-jardin');
		$this->assertSame(realpath($variante . '.html'), realpath(pages_styliser(astra_styliser($this->flux($id)))['data'] . '.html'));
		$this->assertSame(realpath($variante . '.html'), realpath(astra_styliser(pages_styliser($this->flux($id)))['data'] . '.html'));
	}

	public function testGabaritDuSitePrioritaireSurCeluiDAstra(): void {
		$id = PagesGabaritTest::id('verger');
		$site = $this->creer('page_unique');
		$this->assertSame(realpath($site . '.html'), realpath(astra_styliser($this->flux($id))['data'] . '.html'));
	}

	public function testGabaritDAstraSansSurcharge(): void {
		$id = PagesGabaritTest::id('verger');
		$this->assertSame(realpath(_DIR_PLUGINS . 'astra/page_unique.html'), realpath(astra_styliser($this->flux($id))['data'] . '.html'));
	}
}
```

`find_in_path` met en cache ses résultats : si `testGabaritDuSitePrioritaireSurCeluiDAstra` échoue alors que le fichier existe, appeler `find_in_path('page_unique.html', '', false, true)` n'est pas possible dans le pipeline ; vider le cache en tête de test avec `$GLOBALS['path_files'] = [];` et `$GLOBALS['path_sig'] = '';` (variables de `ecrire/inc/utils.php`, vérifier leurs noms dans SPIP 4.4 avant de les utiliser).

Run: `composer tests-integration -- --filter PagesStyliserTest`
Expected: PASS si la tâche 3 est conforme ; sinon corriger `astra_styliser()` (ne pas modifier le test).

- [ ] **Step 2 : test du menu (échoue)**

`tests/integration/PagesMenuTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

/** Menu de pages à deux niveaux (liens a2a sous_page). */
final class PagesMenuTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/PagesGabaritTest.php';
		include_spip('inc/astra_pages');
	}

	protected function setUp(): void {
		ecrire_config('astra/hfb', ['elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]]]);
	}

	protected function tearDown(): void {
		effacer_config('astra/hfb');
		sql_updateq('spip_articles', ['statut' => 'publie'], 'id_article=' . PagesGabaritTest::id('notre-jardin'));
	}

	private function menu(int $id_article): DOMXPath {
		return HfbRenduTest::xpath(recuperer_fond('inclure/header', ['type_page' => 'page', 'id_article' => $id_article]));
	}

	public function testArbre(): void {
		$p = [];
		foreach (['mentions', 'notre-jardin', 'potager-collectif', 'verger', 'plein-ecran'] as $n) {
			$p[$n] = PagesGabaritTest::id($n);
		}
		$this->assertSame([
			['id' => $p['mentions'], 'enfants' => []],
			['id' => $p['notre-jardin'], 'enfants' => [$p['potager-collectif'], $p['verger']]],
			['id' => $p['plein-ecran'], 'enfants' => []],
		], astra_pages_arbre());
	}

	public function testSousMenuEtEtatActif(): void {
		$jardin = PagesGabaritTest::id('notre-jardin');
		$verger = PagesGabaritTest::id('verger');
		$x = $this->menu($verger);
		$d = '//*[@id="ast-desktop-header"]//ul[@id="ast-hf-menu-1"]';
		$parent = $d . '/li[contains(@class, "menu-item-article-' . $jardin . '")]';
		$this->assertSame(1, $x->query($parent . '[contains(@class, "menu-item-has-children")][contains(@class, "current-menu-ancestor")]')->length);
		$this->assertSame(2, $x->query($parent . '/ul[@class="sub-menu"]/li')->length);
		$this->assertSame(1, $x->query($parent . '/ul/li[contains(@class, "menu-item-article-' . $verger . '")][contains(@class, "current-menu-item")]/a[@aria-current="page"]')->length);
		$this->assertSame(0, $x->query($d . '/li[contains(@class, "menu-item-article-' . $verger . '")]')->length, 'Une sous-page n\'est pas au premier niveau');
	}

	public function testParenteDepubliee(): void {
		$jardin = PagesGabaritTest::id('notre-jardin');
		$verger = PagesGabaritTest::id('verger');
		sql_updateq('spip_articles', ['statut' => 'prop'], 'id_article=' . $jardin);
		$d = '//*[@id="ast-desktop-header"]//ul[@id="ast-hf-menu-1"]';
		$this->assertSame(1, $this->menu(0)->query($d . '/li[contains(@class, "menu-item-article-' . $verger . '")]')->length, 'L\'enfant remonte au premier niveau');
		sql_updateq('spip_articles', ['statut' => 'publie'], 'id_article=' . $jardin);
		$this->assertSame(0, $this->menu(0)->query($d . '/li[contains(@class, "menu-item-article-' . $verger . '")]')->length, 'Il revient en sous-menu');
	}

	public function testMenuDuPiedSurUnNiveau(): void {
		ecrire_config('astra/hfb', [
			'pied' => ['below' => ['below_1' => ['menu']]],
			'elements' => ['pied' => ['menu' => ['source' => 'pages', 'id' => 0]]],
		]);
		$x = HfbRenduTest::xpath(recuperer_fond('inclure/footer', ['type_page' => 'page']));
		$this->assertSame(0, $x->query('//ul[@id="astra-footer-menu"]//ul')->length);
		$this->assertSame(3, $x->query('//ul[@id="astra-footer-menu"]/li')->length);
	}
}
```

Run: `composer tests-integration -- --filter PagesMenuTest`
Expected: FAIL (`astra_pages_arbre` indéfinie).

- [ ] **Step 3 : `astra_pages_arbre()`**

Ajouter à `inc/astra_pages.php` :

```php
/**
 * Arbre des pages uniques publiées pour les menus : racines (pages sans parente publiée,
 * par numéro puis titre) et leurs enfants publiés (liens a2a sous_page, par rang). Une page
 * dont la parente n'est plus publiée remonte au premier niveau. Sans a2a : liste à plat.
 */
function astra_pages_arbre(): array {
	$pages = sql_allfetsel('id_article, 0+titre AS num, titre', 'spip_articles', ['id_rubrique=-1', "statut='publie'"], '', 'num, titre');
	$publiees = array_map('intval', array_column($pages, 'id_article'));
	$enfants = [];
	$a_parente = [];
	include_spip('inc/plugin');
	if ($publiees && test_plugin_actif('a2a')) {
		$liens = sql_allfetsel('id_article, id_article_lie', 'spip_articles_lies', [
			sql_in('id_article', $publiees), sql_in('id_article_lie', $publiees), "type_liaison='sous_page'",
		], '', 'id_article, rang');
		foreach ($liens as $l) {
			$enfants[(int) $l['id_article']][] = (int) $l['id_article_lie'];
			$a_parente[(int) $l['id_article_lie']] = true;
		}
	}
	$arbre = [];
	foreach ($publiees as $id) {
		if (empty($a_parente[$id])) {
			$arbre[] = ['id' => $id, 'enfants' => $enfants[$id] ?? []];
		}
	}
	return $arbre;
}
```

Dans `astra_fonctions.php` :

```php
/** Arbre des pages pour la source de menu « pages » : `#VAL|astra_pages_arbre`. */
function filtre_astra_pages_arbre_dist($x = ''): array {
	return astra_pages_arbre();
}
```

Vérifier que `{par num titre}` de SPIP et `0+titre AS num` donnent le même ordre sur SQLite (comparer avec la boucle actuelle `<BOUCLE_pages(ARTICLES){id_rubrique=-1}{par num titre, titre}>` dans le test `testArbre`) ; sinon trier en PHP avec `intval($titre)` puis `strcmp`.

- [ ] **Step 4 : squelette du menu**

Dans `inclure/hfb/menu-items.html`, remplacer le bloc `<BOUCLE_si_pages …>…</BOUCLE_si_pages>` par :

```html
<BOUCLE_si_pages(CONDITION){si #ENV{source}|=={pages}|et{#VAL{pages}|test_plugin_actif}}>
<BOUCLE_pages(DATA){source table, #VAL|astra_pages_arbre}>
#SET{enfants,#ENV{niveaux}|=={1}|?{#ARRAY,#VALEUR{enfants}}}
<BOUCLE_page(ARTICLES){id_article=#VALEUR{id}}>
<li class="menu-item menu-item-article-#ID_ARTICLE[ (#GET{enfants}|oui)menu-item-has-children][ (#ID_ARTICLE|=={#ENV{id_article}}|oui)current-menu-item][ (#GET{enfants}|find{#ENV{id_article}}|oui)current-menu-ancestor]"><a href="#URL_ARTICLE" class="menu-link"[ (#ID_ARTICLE|=={#ENV{id_article}}|oui)aria-current="page"]>[(#TITRE|supprimer_numero)][(#GET{enfants}|oui)<span role="presentation" class="dropdown-menu-toggle ast-header-navigation-arrow" tabindex="0"><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></span>]</a>
	<BOUCLE_si_sous_pages(CONDITION){si #GET{enfants}}>
	<button class="ast-menu-toggle" aria-expanded="false"><span class="screen-reader-text"><:astra:deplier_menu:></span><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></button>
	<ul class="sub-menu">
		<BOUCLE_sous_pages(DATA){source table, #GET{enfants}}><BOUCLE_sous_page(ARTICLES){id_article=#VALEUR}>
		<li class="menu-item menu-item-article-#ID_ARTICLE[ (#ID_ARTICLE|=={#ENV{id_article}}|oui)current-menu-item]"><a href="#URL_ARTICLE" class="menu-link"[ (#ID_ARTICLE|=={#ENV{id_article}}|oui)aria-current="page"]>[(#TITRE|supprimer_numero)]</a></li>
		</BOUCLE_sous_page></BOUCLE_sous_pages>
	</ul>
	</BOUCLE_si_sous_pages>
</li>
</BOUCLE_page>
</BOUCLE_pages>
</BOUCLE_si_pages>
```

Les deux SVG sont recopiés de la boucle des rubriques du même fichier (lignes 18 et 20) ; en cas de divergence, c'est le fichier qui fait foi.

- [ ] **Step 5 : tests et comparaison**

Run: `composer tests-integration`
Expected: PASS, y compris `HfbPagesTest` (la page « mentions » reste au premier niveau, 3 menus).

Run:
```bash
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404 P1-page P3-page-pleine
WP=$WP PW=$PW SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404 P1-page
```
Expected: tout `identique`, captures sous 1 % (menu mobile ouvert compris).

- [ ] **Step 6 : commit**

```bash
git add inc/astra_pages.php astra_fonctions.php inclure/hfb/menu-items.html tests/integration/PagesStyliserTest.php tests/integration/PagesMenuTest.php
git commit -m "Afficher les sous-pages dans le menu de pages"
```

---

### Task 5 : page d'accueil statique (A3)

**Files:**
- Modify: `sommaire.html`, `formulaires/configurer_astra.html`, `astra_fonctions.php` (`astra_accueil_page`), `lang/astra_fr.php`, `lang/astra_en.php`
- Create: `inclure/sommaire-articles.html`, `formulaires/configurer_astra.php`, `tests/integration/PagesAccueilTest.php`

**Interfaces:**
- Consumes : `page_unique.html` (paramètre `accueil=oui`), `astra_est_page_unique()`.
- Produces : configuration `astra/accueil_type` (`articles` | `page`) et `astra/accueil_page` (id) ; `astra_accueil_page($x = ''): int`.

- [ ] **Step 1 : tests (échouent)**

`tests/integration/PagesAccueilTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

/** Page d'accueil statique. */
final class PagesAccueilTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/PagesGabaritTest.php';
		include_spip('inc/astra_pages');
	}

	protected function tearDown(): void {
		effacer_config('astra/accueil_type');
		effacer_config('astra/accueil_page');
		sql_updateq('spip_articles', ['statut' => 'publie'], 'id_article=' . PagesGabaritTest::id('notre-jardin'));
		set_request('accueil_type', null);
		set_request('accueil_page', null);
	}

	private function accueil(): DOMXPath {
		return HfbRenduTest::xpath(recuperer_fond('sommaire', []));
	}

	public function testAccueilParDefaut(): void {
		$this->assertContains('blog', explode(' ', $this->accueil()->query('//body')->item(0)->getAttribute('class')));
	}

	public function testAccueilStatique(): void {
		$id = PagesGabaritTest::id('notre-jardin');
		ecrire_config('astra/accueil_type', 'page');
		ecrire_config('astra/accueil_page', $id);
		$x = $this->accueil();
		$classes = explode(' ', $x->query('//body')->item(0)->getAttribute('class'));
		$this->assertContains('home', $classes);
		$this->assertContains('page-id-' . $id, $classes);
		$this->assertSame('Notre jardin', trim($x->query('//h1[@class="entry-title"]')->item(0)->textContent));
		$this->assertSame(0, $x->query('//link[@rel="canonical"]')->length);
		$propre = HfbRenduTest::xpath(recuperer_fond('article', ['id_article' => $id]));
		$this->assertSame(1, $propre->query('//link[@rel="canonical"]')->length, 'URL canonique vers l\'accueil sur l\'adresse de la page');
	}

	public function testRepliSiLaPageNEstPlusPubliee(): void {
		$id = PagesGabaritTest::id('notre-jardin');
		ecrire_config('astra/accueil_type', 'page');
		ecrire_config('astra/accueil_page', $id);
		sql_updateq('spip_articles', ['statut' => 'prop'], 'id_article=' . $id);
		$this->assertContains('blog', explode(' ', $this->accueil()->query('//body')->item(0)->getAttribute('class')));
	}

	public function testVerificationDuFormulaire(): void {
		include_spip('formulaires/configurer_astra');
		set_request('accueil_type', 'page');
		set_request('accueil_page', (string) PagesGabaritTest::id('notre-jardin'));
		$this->assertSame([], formulaires_configurer_astra_verifier_dist());
		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'id_rubrique>0', '', 'id_article', '0,1');
		set_request('accueil_page', (string) $article);
		$this->assertArrayHasKey('accueil_page', formulaires_configurer_astra_verifier_dist());
		set_request('accueil_page', '999999');
		$this->assertArrayHasKey('accueil_page', formulaires_configurer_astra_verifier_dist());
		set_request('accueil_type', 'autre');
		$this->assertArrayHasKey('accueil_type', formulaires_configurer_astra_verifier_dist());
	}
}
```

Run: `composer tests-integration -- --filter PagesAccueilTest`
Expected: FAIL.

- [ ] **Step 2 : `sommaire.html` et `inclure/sommaire-articles.html`**

Déplacer le contenu actuel de `sommaire.html` (de `<!DOCTYPE html>` à `</html>`) dans `inclure/sommaire-articles.html`, précédé de :

```html
[(#REM)

	Accueil « derniers articles » (index du blog d'Astra), quand aucune page d'accueil statique
	n'est choisie ou que la page choisie n'est plus publiée.

]
```

Nouveau `sommaire.html` :

```html
[(#REM)

	Page d'accueil : page unique choisie dans la configuration du thème (accueil statique),
	sinon derniers articles. La boucle ne trouve rien si la page n'est plus publiée.

]<BOUCLE_accueil_page(ARTICLES){id_article=#VAL|astra_accueil_page}{id_rubrique=-1}><INCLURE{fond=page_unique}{id_article}{accueil=oui} /></BOUCLE_accueil_page><INCLURE{fond=inclure/sommaire-articles}{env} /><//B_accueil_page>
```

Si le compilateur refuse un filtre dans le critère, calculer d'abord `#SET{accueil,#VAL|astra_accueil_page}` puis `{id_article=#GET{accueil}}`.

- [ ] **Step 3 : formulaire de configuration**

Dans `formulaires/configurer_astra.html`, ajouter avant le `<fieldset>` « affichage » :

```html
		<BOUCLE_si_pages_cfg(CONDITION){si #VAL{pages}|test_plugin_actif}>
		<fieldset>
			<legend><:astra:cfg_accueil:></legend>
			<div class="editer-groupe">
				#SET{accueil_type,#ENV{accueil_type}|sinon{#VAL{accueil_type}|astra_conf}}
				<div class="editer editer_accueil_type[ (#ENV*{erreurs/accueil_type}|oui)erreur]">
					<label for="accueil_type"><:astra:cfg_accueil_type:></label>
					[<span class="erreur_message">(#ENV*{erreurs/accueil_type})</span>]
					<select name="accueil_type" id="accueil_type">
						<option value="articles"[(#GET{accueil_type}|=={articles}|oui)selected="selected"]><:astra:cfg_accueil_articles:></option>
						<option value="page"[(#GET{accueil_type}|=={page}|oui)selected="selected"]><:astra:cfg_accueil_page:></option>
					</select>
				</div>
				<div class="editer editer_accueil_page[ (#ENV*{erreurs/accueil_page}|oui)erreur]">
					<label for="accueil_page"><:astra:cfg_accueil_page_choix:></label>
					[<span class="erreur_message">(#ENV*{erreurs/accueil_page})</span>]
					<select name="accueil_page" id="accueil_page">
						<option value=""></option>
						<BOUCLE_pages_accueil(ARTICLES){id_rubrique=-1}{par num titre, titre}>
						<option value="#ID_ARTICLE"[(#ID_ARTICLE|=={#ENV{accueil_page}}|oui)selected="selected"]>[(#TITRE|supprimer_numero)]</option>
						</BOUCLE_pages_accueil>
					</select>
				</div>
			</div>
		</fieldset>
		</BOUCLE_si_pages_cfg>
```

`formulaires/configurer_astra.php` (le chargement et l'enregistrement restent ceux de SPIP, `cvt_configurer.php`, qui les fournit en l'absence de fonctions `charger` et `traiter`) :

```php
<?php
/**
 * Astra pour SPIP — vérification du formulaire de configuration du thème.
 * Chargement et enregistrement : automatiques (formulaire configurer_, méta astra).
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_astra_verifier_dist() {
	$erreurs = [];
	$type = _request('accueil_type');
	if ($type !== null && !in_array($type, ['articles', 'page'], true)) {
		$erreurs['accueil_type'] = _T('astra:cfg_accueil_type_erreur');
	}
	if ($type === 'page') {
		include_spip('inc/astra_pages');
		$id = (int) _request('accueil_page');
		if (!astra_est_page_unique($id) || sql_getfetsel('statut', 'spip_articles', 'id_article=' . $id) !== 'publie') {
			$erreurs['accueil_page'] = _T('astra:cfg_accueil_page_erreur');
		}
	}
	return $erreurs;
}
```

Chaînes à ajouter dans `lang/astra_fr.php` (section C) et `lang/astra_en.php` :

```php
	'cfg_accueil' => 'Page d’accueil',
	'cfg_accueil_articles' => 'Derniers articles',
	'cfg_accueil_page' => 'Une page unique',
	'cfg_accueil_page_choix' => 'Page affichée en accueil',
	'cfg_accueil_page_erreur' => 'Choisissez une page unique publiée.',
	'cfg_accueil_type' => 'L’accueil affiche',
	'cfg_accueil_type_erreur' => 'Choix inconnu.',
```

```php
	'cfg_accueil' => 'Home page',
	'cfg_accueil_articles' => 'Latest articles',
	'cfg_accueil_page' => 'A static page',
	'cfg_accueil_page_choix' => 'Page displayed as home page',
	'cfg_accueil_page_erreur' => 'Choose a published static page.',
	'cfg_accueil_type' => 'The home page displays',
	'cfg_accueil_type_erreur' => 'Unknown choice.',
```

Vérifier sur le site de test (`http://127.0.0.1:8404/ecrire/?exec=configurer_astra`) que l'enregistrement automatique reprend bien les deux `<select>` (les champs sont recensés depuis le squelette) : enregistrer « Une page unique / Notre jardin », puis `$SPIP_CLI config:lire astra/accueil_page` renvoie l'identifiant.

- [ ] **Step 4 : tests, comparaison P2**

Run: `composer tests-integration`
Expected: PASS.

Run:
```bash
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404 P2-accueil
WP=$WP PW=$PW SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404 P2-accueil
```
Expected: tout `identique`, captures sous 1 %. Classes du `body` attendues d'après la référence : `home` en plus des classes de P1.

- [ ] **Step 5 : commit**

```bash
git add sommaire.html inclure/sommaire-articles.html formulaires/configurer_astra.html formulaires/configurer_astra.php astra_fonctions.php lang tests/integration/PagesAccueilTest.php
git commit -m "Permettre une page d'accueil statique"
```

---

### Task 6 : formulaire des réglages par page (A4 saisie)

**Files:**
- Create: `formulaires/editer_astra_page.html`, `formulaires/editer_astra_page.php`, `prive/squelettes/inclure/astra-page.html`, `tests/integration/PagesReglagesTest.php`
- Modify: `astra_pipelines.php` (`affiche_milieu`, `trig_supprimer_objets_lies`), `paquet.xml`, `lang/astra_fr.php`, `lang/astra_en.php`

**Interfaces:**
- Consumes : `astra_est_page_unique()`, `astra_page_reglages()`, `astra_page_normaliser()`, `astra_page_dispositions()`.
- Produces : `#FORMULAIRE_EDITER_ASTRA_PAGE{id_article}` ; champs `affichage_titre` (`afficher` | `masquer`) et `disposition` ; écrit `astra/pages/<id_article>`.

- [ ] **Step 1 : tests (échouent)**

`tests/integration/PagesReglagesTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

/** Formulaire editer_astra_page : droits de l'article, pas ceux de la configuration du site. */
final class PagesReglagesTest extends TestCase {

	private static int $redacteur = 0;
	private static int $autre = 0;
	private static int $page = 0;

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/PagesGabaritTest.php';
		include_spip('formulaires/editer_astra_page');
		include_spip('inc/autoriser');
		$auteur = function ($login) {
			sql_delete('spip_auteurs', 'login=' . sql_quote($login));
			return (int) sql_insertq('spip_auteurs', ['nom' => $login, 'login' => $login, 'statut' => '1comite', 'webmestre' => 'non']);
		};
		self::$redacteur = $auteur('redacteur_page');
		self::$autre = $auteur('redacteur_autre');
		sql_delete('spip_articles', 'page=' . sql_quote('page-redacteur'));
		self::$page = (int) sql_insertq('spip_articles', ['titre' => 'Page du rédacteur', 'texte' => 'Texte.', 'id_rubrique' => -1, 'page' => 'page-redacteur', 'statut' => 'prop', 'lang' => 'fr', 'date' => date('Y-m-d H:i:s')]);
		sql_insertq('spip_auteurs_liens', ['id_auteur' => self::$redacteur, 'objet' => 'article', 'id_objet' => self::$page, 'vu' => 'non']);
	}

	public static function tearDownAfterClass(): void {
		sql_delete('spip_auteurs_liens', 'id_objet=' . self::$page . " AND objet='article'");
		sql_delete('spip_articles', 'id_article=' . self::$page);
		sql_delete('spip_auteurs', sql_in('id_auteur', [self::$redacteur, self::$autre]));
		effacer_config('astra/pages');
		$GLOBALS['visiteur_session'] = [];
	}

	private function connecter(int $id_auteur): void {
		$GLOBALS['visiteur_session'] = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . $id_auteur);
	}

	private function poster(string $titre, string $disposition): void {
		set_request('affichage_titre', $titre);
		set_request('disposition', $disposition);
	}

	public function testRedacteurAuteurPeutEnregistrer(): void {
		$this->connecter(self::$redacteur);
		$this->assertFalse(autoriser('configurer', '_astra'), 'Le rédacteur ne peut pas configurer le site');
		$valeurs = formulaires_editer_astra_page_charger_dist(self::$page);
		$this->assertTrue($valeurs['editable']);
		$this->poster('masquer', 'pleine');
		$this->assertSame([], formulaires_editer_astra_page_verifier_dist(self::$page));
		$this->assertArrayHasKey('message_ok', formulaires_editer_astra_page_traiter_dist(self::$page));
		$this->assertSame(['titre' => 'masquer', 'disposition' => 'pleine'], lire_config('astra/pages/' . self::$page));
	}

	public function testRedacteurNonAuteurRefuse(): void {
		$this->connecter(self::$autre);
		$this->assertFalse(formulaires_editer_astra_page_charger_dist(self::$page)['editable']);
		$this->poster('afficher', 'etroite');
		$this->assertArrayHasKey('message_erreur', formulaires_editer_astra_page_traiter_dist(self::$page));
		$this->assertNotSame(['titre' => 'afficher', 'disposition' => 'etroite'], lire_config('astra/pages/' . self::$page));
	}

	public function testArticleOrdinaireRefuse(): void {
		$this->connecter(self::$redacteur);
		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'id_rubrique>0', '', 'id_article', '0,1');
		$this->assertFalse(formulaires_editer_astra_page_charger_dist($article));
	}

	public function testValeursInvalides(): void {
		$this->connecter(self::$redacteur);
		$this->poster('cache', 'immense');
		$erreurs = formulaires_editer_astra_page_verifier_dist(self::$page);
		$this->assertArrayHasKey('affichage_titre', $erreurs);
		$this->assertArrayHasKey('disposition', $erreurs);
	}

	public function testSuppressionDeLaPageEffaceSesReglages(): void {
		ecrire_config('astra/pages/' . self::$page, ['titre' => 'masquer', 'disposition' => 'pleine']);
		pipeline('trig_supprimer_objets_lies', [['type' => 'article', 'id' => self::$page]]);
		$this->assertNull(lire_config('astra/pages/' . self::$page));
	}
}
```

Run: `composer tests-integration -- --filter PagesReglagesTest`
Expected: FAIL.

- [ ] **Step 2 : formulaire CVT**

`formulaires/editer_astra_page.php` :

```php
<?php
/**
 * Astra pour SPIP — réglages d'affichage d'une page unique (titre, disposition).
 *
 * Nommé editer_ et non configurer_ : SPIP impose l'autorisation « configurer » à tout
 * formulaire configurer_* (ecrire/inc/cvt_configurer.php). Ici, ce sont les droits de
 * modification de la page qui comptent.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/astra_pages');
include_spip('inc/autoriser');

function formulaires_editer_astra_page_charger_dist($id_article) {
	$id_article = (int) $id_article;
	if (!astra_est_page_unique($id_article)) {
		return false;
	}
	$r = astra_page_reglages($id_article);
	return [
		'affichage_titre' => $r['titre'],
		'disposition' => $r['disposition'],
		'dispositions' => astra_page_dispositions(),
		'editable' => autoriser('modifier', 'article', $id_article),
	];
}

function formulaires_editer_astra_page_verifier_dist($id_article) {
	$erreurs = [];
	if (!in_array(_request('affichage_titre'), ['afficher', 'masquer'], true)) {
		$erreurs['affichage_titre'] = _T('astra:page_erreur_choix');
	}
	if (!in_array(_request('disposition'), astra_page_dispositions(), true)) {
		$erreurs['disposition'] = _T('astra:page_erreur_choix');
	}
	return $erreurs;
}

function formulaires_editer_astra_page_traiter_dist($id_article) {
	$id_article = (int) $id_article;
	if (!astra_est_page_unique($id_article) || !autoriser('modifier', 'article', $id_article)) {
		return ['message_erreur' => _T('astra:page_erreur_droits')];
	}
	include_spip('inc/config');
	ecrire_config('astra/pages/' . $id_article, astra_page_normaliser([
		'titre' => _request('affichage_titre'),
		'disposition' => _request('disposition'),
	]));
	include_spip('inc/invalideur');
	suivre_invalideur("id='article/$id_article'");
	return ['message_ok' => _T('astra:page_reglages_enregistres'), 'editable' => true];
}
```

`formulaires/editer_astra_page.html` :

```html
<div class="formulaire_spip formulaire_editer formulaire_editer_astra_page formulaire_editer_astra_page-#ENV{id_article}">
	[<div class="reponse_formulaire reponse_formulaire_ok" role="status">(#ENV*{message_ok})</div>]
	[<div class="reponse_formulaire reponse_formulaire_erreur" role="alert">(#ENV*{message_erreur})</div>]
	[(#ENV{editable}|oui)
	<form method="post" action="#ENV{action}"><div>
		#ACTION_FORMULAIRE
		<div class="editer-groupe">
			<div class="editer editer_affichage_titre[ (#ENV*{erreurs/affichage_titre}|oui)erreur]">
				<label for="affichage_titre"><:astra:page_titre:></label>
				[<span class="erreur_message">(#ENV*{erreurs/affichage_titre})</span>]
				<select name="affichage_titre" id="affichage_titre">
					<option value="afficher"[(#ENV{affichage_titre}|=={afficher}|oui)selected="selected"]><:astra:page_titre_afficher:></option>
					<option value="masquer"[(#ENV{affichage_titre}|=={masquer}|oui)selected="selected"]><:astra:page_titre_masquer:></option>
				</select>
			</div>
			<div class="editer editer_disposition[ (#ENV*{erreurs/disposition}|oui)erreur]">
				<label for="disposition"><:astra:page_disposition:></label>
				[<span class="erreur_message">(#ENV*{erreurs/disposition})</span>]
				<select name="disposition" id="disposition">
					<BOUCLE_dispositions(DATA){source table, #ENV{dispositions}}>
					<option value="#VALEUR"[(#VALEUR|=={#ENV{disposition}}|oui)selected="selected"]>[(#VAL{astra:page_disposition_}|concat{#VALEUR}|_T)]</option>
					</BOUCLE_dispositions>
				</select>
			</div>
		</div>
		<p class="boutons"><input type="submit" class="btn submit" value="<:bouton_enregistrer:>" /></p>
	</div></form>
	]
</div>
```

`prive/squelettes/inclure/astra-page.html` :

```html
<div class="ajax">
	#FORMULAIRE_EDITER_ASTRA_PAGE{#ENV{id_article}}
</div>
```

- [ ] **Step 3 : pipelines**

Ajouter à `astra_pipelines.php` :

```php
/** Réglages d'affichage sur la fiche d'une page unique dans l'espace privé. */
function astra_affiche_milieu($flux) {
	$e = trouver_objet_exec($flux['args']['exec'] ?? '');
	$id = (int) ($flux['args']['id_article'] ?? 0);
	if ($e && empty($e['edition']) && ($e['type'] ?? '') === 'article' && $id) {
		include_spip('inc/astra_pages');
		if (astra_est_page_unique($id)) {
			$flux['data'] .= recuperer_fond('prive/squelettes/inclure/astra-page', ['id_article' => $id]);
		}
	}
	return $flux;
}

/** Réglages d'une page supprimée : effacés avec elle. */
function astra_trig_supprimer_objets_lies($objets) {
	include_spip('inc/config');
	foreach ((array) $objets as $o) {
		if (($o['type'] ?? '') === 'article' && !empty($o['id'])) {
			effacer_config('astra/pages/' . (int) $o['id']);
		}
	}
	return $objets;
}
```

Dans `paquet.xml` :

```xml
	<pipeline nom="affiche_milieu" inclure="astra_pipelines.php" />
	<pipeline nom="trig_supprimer_objets_lies" inclure="astra_pipelines.php" />
```

Chaînes (`lang/astra_fr.php` puis `lang/astra_en.php`) :

```php
	'page_disposition' => 'Disposition du contenu',
	'page_disposition_defaut' => 'Celle des pages',
	'page_disposition_etroite' => 'Étroite',
	'page_disposition_normale' => 'Normale',
	'page_disposition_pleine' => 'Pleine largeur',
	'page_erreur_choix' => 'Choix inconnu.',
	'page_erreur_droits' => 'Vous ne pouvez pas modifier cette page.',
	'page_reglages_enregistres' => 'Réglages d’affichage enregistrés.',
	'page_titre' => 'Titre de la page',
	'page_titre_afficher' => 'Afficher',
	'page_titre_masquer' => 'Masquer',
```

```php
	'page_disposition' => 'Content layout',
	'page_disposition_defaut' => 'Same as pages',
	'page_disposition_etroite' => 'Narrow',
	'page_disposition_normale' => 'Normal',
	'page_disposition_pleine' => 'Full width',
	'page_erreur_choix' => 'Unknown choice.',
	'page_erreur_droits' => 'You cannot edit this page.',
	'page_reglages_enregistres' => 'Display settings saved.',
	'page_titre' => 'Page title',
	'page_titre_afficher' => 'Show',
	'page_titre_masquer' => 'Hide',
```

Réactiver le plugin (`$SPIP_CLI plugins:activer astra -y`) pour prendre en compte les pipelines.

- [ ] **Step 4 : tests, contrôle dans l'espace privé, comparaison P3**

Run: `composer tests-integration && php7.4 -l formulaires/editer_astra_page.php && php7.4 -l astra_pipelines.php`
Expected: PASS.

Contrôle manuel : `http://127.0.0.1:8404/ecrire/?exec=article&id_article=<id de plein-ecran>` (admin / adminadmin) montre l'encart ; enregistrer « Masquer / Pleine largeur » ; la page publique n'a plus de titre.

Run: `WP=$WP PW=$PW SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404 P3-page-pleine` et `comparer_html.php … P3-page-pleine`
Expected: identiques, captures sous 1 %.

- [ ] **Step 5 : commit**

```bash
git add formulaires/editer_astra_page.* prive/squelettes/inclure/astra-page.html astra_pipelines.php paquet.xml lang tests/integration/PagesReglagesTest.php
git commit -m "Régler le titre et la disposition de chaque page unique"
```

---

### Task 7 : générateur du CSS des blocs (B1)

**Files:**
- Create: `tests/references/extraire_css_blocs.py`, `tests/references/test_extraire_css_blocs.py`, `css/astra-blocs.css` (généré)
- Modify: `inclure/head.html`, `paquet.xml` (crédit), `README.md`

**Interfaces:**
- Consumes : `instructions()` et `paires()` de `tests/references/extraire_css.py` ; sources WordPress dans `$WP/wp-includes/css/dist/block-library/common.css` et `$WP/wp-includes/blocks/<bloc>/style.css` ; `global-styles-inline-css` d'une page capturée (tâche 9 ; d'ici là, `tests/references/sorties/P1-page/accueil.html`).
- Produces : `satisfiable(selecteur) -> bool`, `filtrer_liste(prelude) -> str | None`, `filtrer_css(css) -> str` ; `css/astra-blocs.css`.

- [ ] **Step 1 : tests unitaires (échouent)**

`tests/references/test_extraire_css_blocs.py` :

```python
"""Tests de l'évaluation des sélecteurs : python3 -m unittest tests/references/test_extraire_css_blocs.py"""
import os
import sys
import unittest

sys.path.insert(0, os.path.dirname(__file__))
from extraire_css_blocs import filtrer_css, filtrer_liste, satisfiable  # noqa: E402


class Selecteurs(unittest.TestCase):

    def test_classes(self):
        self.assertTrue(satisfiable('.wp-block-columns > .wp-block-column'))
        self.assertFalse(satisfiable('.wp-block-columns.has-background'))
        self.assertTrue(satisfiable('figure'))

    def test_not_sur_classe_perdue_reste_vrai(self):
        self.assertTrue(satisfiable('.wp-block-columns:not(.is-not-stacked-on-mobile) > .wp-block-column'))

    def test_not_sur_classe_conservee(self):
        self.assertTrue(satisfiable('.wp-block-group:not(.wp-block-columns)'))

    def test_is_et_where(self):
        self.assertTrue(satisfiable(':where(.wp-block-group.has-background, .wp-block-group) > p'))
        self.assertFalse(satisfiable(':is(.has-background, .is-style-outline) p'))
        self.assertTrue(satisfiable(':is(.wp-block-quote, .has-x)'))

    def test_attribut_style(self):
        self.assertFalse(satisfiable('.wp-block-column[style*=flex-basis]'))
        self.assertTrue(satisfiable('.wp-block-cover[data-x]'))

    def test_pseudo_element_et_echappement(self):
        self.assertTrue(satisfiable('.wp-block-quote::before'))
        self.assertFalse(satisfiable('.md\\:flex'))

    def test_liste_partielle_garde_les_selecteurs_intacts(self):
        self.assertEqual(
            filtrer_liste(':is(.wp-block-group, .has-background) p, .has-text-color, .wp-block-quote'),
            ':is(.wp-block-group, .has-background) p,.wp-block-quote')
        self.assertIsNone(filtrer_liste('.has-text-color, .is-style-outline'))

    def test_media_query(self):
        css = '@media (min-width:782px){.wp-block-columns{flex-wrap:nowrap}.has-x{color:red}}.is-y{color:blue}'
        self.assertEqual(filtrer_css(css), '@media (min-width:782px){.wp-block-columns{flex-wrap:nowrap}}')

    def test_commentaires_et_keyframes(self):
        css = '/* a { } */@keyframes x{from{opacity:0}to{opacity:1}}.wp-block-cover{animation:x 1s}'
        self.assertEqual(filtrer_css(css), '@keyframes x{from{opacity:0}to{opacity:1}}.wp-block-cover{animation:x 1s}')


if __name__ == '__main__':
    unittest.main()
```

Run: `python3 -m unittest tests/references/test_extraire_css_blocs.py`
Expected: FAIL (`ModuleNotFoundError`).

- [ ] **Step 2 : générateur**

`tests/references/extraire_css_blocs.py` :

```python
#!/usr/bin/env python3
"""
Génère css/astra-blocs.css : CSS des blocs natifs WordPress conservés par wp2spip.

Usage : WP=<racine WordPress> python3 tests/references/extraire_css_blocs.py <page capturée> > css/astra-blocs.css

Sources (WordPress 7.1.3) : styles communs, styles des blocs gardés par wp2spip, puis
global-styles-inline-css de la page capturée. wp2spip ne garde que les classes wp-block-* et
retire l'attribut style : un sélecteur qui exige une autre classe, hors négation, ne peut plus
rien sélectionner. Le générateur retire ces sélecteurs entiers d'une liste, sans jamais réécrire
l'intérieur d'un sélecteur (la spécificité de :is() et :not() est celle de leur argument le plus
spécifique : retirer une branche, même impossible, changerait la cascade). En cas de doute, le
sélecteur est gardé. Les règles identiques à celles de css/astra-dynamic.css ne sont pas répétées.
"""
import os
import re
import sys

sys.path.insert(0, os.path.dirname(__file__))
from extraire_css import instructions, paires  # noqa: E402

BLOCS = ['paragraph', 'heading', 'list', 'quote', 'pullquote', 'code', 'preformatted', 'verse',
         'separator', 'table', 'columns', 'column', 'group', 'buttons', 'button', 'cover', 'media-text']
PSEUDO_LISTES = ('is', 'where', 'matches', '-webkit-any', '-moz-any')


def conservee(classe):
    return classe.startswith('wp-block-')


def fin_groupe(texte, debut, ouvrant, fermant):
    """Indice du caractère fermant correspondant à texte[debut] (== ouvrant)."""
    profondeur, guillemet = 0, None
    for i in range(debut, len(texte)):
        c = texte[i]
        if guillemet:
            if c == guillemet and texte[i - 1] != '\\':
                guillemet = None
        elif c in '"\'':
            guillemet = c
        elif c == ouvrant:
            profondeur += 1
        elif c == fermant:
            profondeur -= 1
            if profondeur == 0:
                return i
    raise ValueError('groupe non fermé : ' + texte[debut:])


def decouper_liste(prelude):
    """Sélecteurs complexes d'une liste (virgules de premier niveau)."""
    resultat, profondeur, courant, guillemet = [], 0, '', None
    for c in prelude:
        if guillemet:
            courant += c
            if c == guillemet:
                guillemet = None
            continue
        if c in '"\'':
            guillemet = c
        elif c in '([':
            profondeur += 1
        elif c in ')]':
            profondeur -= 1
        elif c == ',' and profondeur == 0:
            resultat.append(courant.strip())
            courant = ''
            continue
        courant += c
    if courant.strip():
        resultat.append(courant.strip())
    return resultat


def satisfiable(selecteur):
    """Faux seulement si le sélecteur ne peut, de façon démontrée, rien sélectionner après l'import."""
    i, n = 0, len(selecteur)
    while i < n:
        c = selecteur[i]
        if c == '\\':
            i += 2
        elif c == '.':
            j = i + 1
            while j < n and (selecteur[j].isalnum() or selecteur[j] in '-_\\' or ord(selecteur[j]) > 127):
                j += 2 if selecteur[j] == '\\' else 1
            if not conservee(selecteur[i + 1:j]):
                return False
            i = j
        elif c == '[':
            j = fin_groupe(selecteur, i, '[', ']')
            if re.match(r'\[\s*style\b', selecteur[i:j + 1]):
                return False
            i = j + 1
        elif c == ':':
            m = re.match(r'::?(-?[a-zA-Z][\w-]*)', selecteur[i:])
            if not m:
                i += 1
                continue
            nom, j = m.group(1).lower(), i + m.end()
            if j < n and selecteur[j] == '(':
                k = fin_groupe(selecteur, j, '(', ')')
                if nom in PSEUDO_LISTES and not any(satisfiable(b) for b in decouper_liste(selecteur[j + 1:k])):
                    return False
                j = k + 1  # :not(), :has(), :nth-*() : gardés (prudence)
            i = j
        elif c in '"\'':
            i = selecteur.index(c, i + 1) + 1
        else:
            i += 1
    return True


def filtrer_liste(prelude):
    """Liste de sélecteurs sans les sélecteurs impossibles, chacun intact ; None si aucun ne reste."""
    gardes = [s for s in decouper_liste(prelude) if satisfiable(s)]
    return ','.join(gardes) if gardes else None


def filtrer_css(css):
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    sortie = []
    for instr in instructions(css):
        if instr.startswith('@'):
            entete, _, corps = instr.partition('{')
            nom = entete.split()[0].lower()
            if nom in ('@media', '@supports', '@layer', '@container') and corps:
                interieur = filtrer_css(corps[:-1])
                if interieur:
                    sortie.append(entete.strip() + '{' + interieur + '}')
            else:
                sortie.append(instr)  # @keyframes, @font-face, @import… : tels quels
            continue
        prelude, _, bloc = instr.partition('{')
        liste = filtrer_liste(prelude.strip())
        if liste is not None:
            sortie.append(liste + '{' + bloc)
    return ''.join(sortie)


def main():
    wp = os.environ['WP']
    page = sys.argv[1]
    sources = [open(os.path.join(wp, 'wp-includes/css/dist/block-library/common.css'), encoding='utf-8').read()]
    for bloc in BLOCS:
        chemin = os.path.join(wp, 'wp-includes/blocks', bloc, 'style.css')
        if os.path.exists(chemin):
            sources.append(open(chemin, encoding='utf-8').read())
    m = re.search(r'<style id=[\'"]global-styles-inline-css[\'"][^>]*>(.*?)</style>', open(page, encoding='utf-8').read(), re.S)
    if m:
        sources.append(m.group(1))
    genere = filtrer_css('\n'.join(sources))
    dynamique = set(paires(open(os.path.join(os.path.dirname(__file__), '..', '..', 'css', 'astra-dynamic.css'), encoding='utf-8').read()))
    print('/*')
    print(' * Astra pour SPIP — CSS des blocs natifs WordPress conservés par wp2spip.')
    print(' *')
    print(' * Règles de WordPress 7.1.3 (wp-includes, GPL v2 ou ultérieure) dont un sélecteur au moins')
    print(' * peut encore correspondre au HTML importé. Fichier produit par')
    print(' * tests/references/extraire_css_blocs.py : ne pas modifier à la main.')
    print(' */')
    for contexte, regle in paires(genere):
        if (contexte, regle) in dynamique:
            continue
        print((contexte + '{' + regle + '}') if contexte else regle)


if __name__ == '__main__':
    main()
```

- [ ] **Step 3 : tests unitaires**

Run: `python3 -m unittest tests/references/test_extraire_css_blocs.py -v`
Expected: 9 tests OK. En cas d'échec de `test_media_query` dû à des espaces conservés par `instructions()`, normaliser la sortie (`strip()` des règles) dans `filtrer_css`, pas dans le test.

- [ ] **Step 4 : générer et charger la feuille**

Run: `WP=$WP python3 tests/references/extraire_css_blocs.py tests/references/sorties/P1-page/accueil.html > css/astra-blocs.css && wc -c css/astra-blocs.css && grep -c 'is-not-stacked-on-mobile' css/astra-blocs.css`
Expected: fichier non vide ; au moins 1 occurrence de `:not(.is-not-stacked-on-mobile)`.

Dans `inclure/head.html`, entre `astra-dynamic-css` et `astra-hfb-css` :

```html
<link rel="stylesheet" id="astra-blocs-css" href="[(#CHEMIN{css/astra-blocs.css}|timestamp)]" media="all" />
```

Dans `paquet.xml` :

```xml
	<credit lien="https://wordpress.org/">CSS des blocs : WordPress 7.1.3 (GPL v2 ou ultérieure)</credit>
```

Dans `README.md`, section licences : « `css/astra-blocs.css` reprend des règles de WordPress 7.1.3 (GPL v2 ou ultérieure), générées par `tests/references/extraire_css_blocs.py`. »

- [ ] **Step 5 : non-régression**

Run: `composer tests-unit && composer tests-integration && WP=$WP PW=$PW SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404`
Expected: PASS ; 25 vues R0–R4 sous 1 % (la nouvelle feuille ne doit pas toucher l'en-tête ni le pied).

- [ ] **Step 6 : commit**

```bash
git add tests/references/extraire_css_blocs.py tests/references/test_extraire_css_blocs.py css/astra-blocs.css inclure/head.html paquet.xml README.md
git commit -m "Générer le CSS des blocs natifs conservés par wp2spip"
```

---

### Task 8 : test de cascade du CSS généré

**Files:**
- Create: `tests/references/blocs/importe.html`, `tests/e2e/cascade_blocs.cjs`

**Interfaces:**
- Consumes : `css/astra-blocs.css`, `css/astra-dynamic.css`, sources WordPress (tâche 7).
- Produces : test qui compare, élément par élément, des propriétés calculées avec la feuille d'origine et avec la feuille générée.

- [ ] **Step 1 : HTML importé de référence**

`tests/references/blocs/importe.html` (fragment, tel que wp2spip le produit : classes `wp-block-*` seules, pas d'attribut `style`) :

```html
<div class="entry-content clear" data-ast-blocks-layout="true">
<div class="wp-block-columns"><div class="wp-block-column"><p>Colonne 1</p></div><div class="wp-block-column"><p>Colonne 2</p></div></div>
<div class="wp-block-columns"><div class="wp-block-column"><p>A</p></div><div class="wp-block-column"><p>B</p></div><div class="wp-block-column"><p>C</p></div></div>
<div class="wp-block-group"><div class="wp-block-group"><p>Groupe imbriqué</p></div></div>
<blockquote class="wp-block-quote wp-block-quote-is-layout-flow"><p>Citation</p><cite>Auteur</cite></blockquote>
<figure class="wp-block-pullquote"><blockquote><p>Exergue</p><cite>Auteur</cite></blockquote></figure>
<figure class="wp-block-table"><table><thead><tr><th>A</th><th>B</th></tr></thead><tbody><tr><td>1</td><td>2</td></tr></tbody></table></figure>
<div class="wp-block-buttons"><div class="wp-block-button"><a href="#" class="spip_in">Bouton</a></div><div class="wp-block-button"><a href="#" class="spip_in">Second</a></div></div>
<hr class="wp-block-separator">
<div class="wp-block-cover"><span class="spip_document spip_documents"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" width="40" height="40" alt=""></span><p>Couverture</p></div>
<div class="wp-block-media-text"><span class="spip_document spip_documents"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" width="40" height="40" alt=""></span><p>Média et texte</p></div>
<pre class="wp-block-code"><code>code</code></pre>
<pre class="wp-block-verse">vers</pre>
</div>
```

Après la tâche 9, remplacer ce fragment par le contenu réellement importé (`.entry-content` de la page de démonstration dans le SPIP importé), en conservant les classes et la structure.

- [ ] **Step 2 : script de test**

`tests/e2e/cascade_blocs.cjs` :

```js
// Cascade du CSS des blocs : les propriétés calculées sur le HTML importé doivent être les mêmes
// avec les feuilles d'origine de WordPress et avec css/astra-blocs.css.
// Usage : WP=<racine WordPress> PAGE=<page capturée> NODE_PATH=$PW/node_modules node tests/e2e/cascade_blocs.cjs
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const RACINE = path.join(__dirname, '..', '..');
const WP = process.env.WP;
const BLOCS = ['paragraph', 'heading', 'list', 'quote', 'pullquote', 'code', 'preformatted', 'verse',
  'separator', 'table', 'columns', 'column', 'group', 'buttons', 'button', 'cover', 'media-text'];
const PROPRIETES = ['display', 'flex-wrap', 'flex-basis', 'flex-grow', 'align-items', 'gap', 'column-gap', 'row-gap',
  'margin-top', 'margin-bottom', 'padding-top', 'padding-left', 'border-top-width', 'border-left-width',
  'font-size', 'font-style', 'text-align', 'width', 'min-height', 'position', 'box-sizing'];

const lire = f => fs.readFileSync(f, 'utf8');
const dynamique = lire(path.join(RACINE, 'css/astra-dynamic.css'));
const page = lire(process.env.PAGE);
const globales = (page.match(/<style id=['"]global-styles-inline-css['"][^>]*>([\s\S]*?)<\/style>/) || [, ''])[1];
const origine = [lire(path.join(WP, 'wp-includes/css/dist/block-library/common.css'))]
  .concat(BLOCS.map(b => path.join(WP, 'wp-includes/blocks', b, 'style.css')).filter(fs.existsSync).map(lire))
  .concat([globales]).join('\n');
const genere = lire(path.join(RACINE, 'css/astra-blocs.css'));
const html = lire(path.join(RACINE, 'tests/references/blocs/importe.html'));

async function mesurer(browser, css, largeur) {
  const p = await browser.newPage({ viewport: { width: largeur, height: 900 } });
  await p.setContent(`<!doctype html><html><head><style>${dynamique}</style><style>${css}</style></head><body>${html}</body></html>`);
  const valeurs = await p.evaluate(props => [...document.querySelectorAll('.entry-content *')].map((e, i) => {
    const s = getComputedStyle(e);
    return { i, nom: e.tagName.toLowerCase() + (e.className ? '.' + String(e.className).split(' ').join('.') : ''), v: props.map(x => s.getPropertyValue(x)) };
  }), PROPRIETES);
  await p.close();
  return valeurs;
}

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  const ecarts = [];
  for (const largeur of [1280, 600]) {
    const a = await mesurer(browser, origine, largeur);
    const b = await mesurer(browser, genere, largeur);
    a.forEach((e, k) => e.v.forEach((v, j) => {
      if (v !== b[k].v[j]) ecarts.push(`${largeur} px, ${e.nom} : ${PROPRIETES[j]} = ${v} (WordPress) / ${b[k].v[j]} (généré)`);
    }));
  }
  await browser.close();
  if (ecarts.length) {
    console.error(ecarts.join('\n'));
    process.exit(1);
  }
  console.log('Cascade des blocs : OK');
})();
```

- [ ] **Step 3 : vérifier que le test détecte une réécriture**

Copier `css/astra-blocs.css` de côté, remplacer à la main dans le fichier une règle `:where(… , .has-…)`/`:is(…)` par sa version sans la branche impossible (ou supprimer la règle `.wp-block-columns:not(.is-not-stacked-on-mobile) > .wp-block-column`), lancer :

Run: `WP=$WP PAGE=tests/references/sorties/P1-page/accueil.html NODE_PATH=$PW/node_modules node tests/e2e/cascade_blocs.cjs`
Expected: FAIL avec au moins un écart (largeur 600 px pour la règle des colonnes). Restaurer le fichier.

- [ ] **Step 4 : lancer sur la feuille générée**

Run: même commande.
Expected: `Cascade des blocs : OK`. Un écart réel signale un défaut du générateur : corriger `extraire_css_blocs.py` et ajouter le cas aux tests unitaires.

- [ ] **Step 5 : commit**

```bash
git add tests/references/blocs/importe.html tests/e2e/cascade_blocs.cjs
git commit -m "Vérifier la cascade du CSS des blocs généré"
```

---

### Task 9 : page de démonstration, import par wp2spip, comparaison bloc par bloc (B2)

**Files:**
- Create: `scripts/install-wp-blocs.sh`, `scripts/install-spip-blocs.sh`, `tests/references/blocs/generer_images.php`, `tests/references/blocs/images/` (PNG générés), `tests/references/blocs/page.php`, `tests/references/blocs/pertes.json`, `tests/comparaison/comparer_blocs.cjs`, `tests/comparaison/comparer_medias.php`
- Modify: `css/astra-spip.css` (correspondances, seulement si un écart est mesuré), `tests/references/README.md`, `README.md`

**Interfaces:**
- Consumes : CSS des blocs (tâches 7–8), gabarit de page (tâche 3), wp2spip (`/src/wordpress/wp2spip`, `outils/preparer_spip.sh`, commande `wordpress:importer`).
- Produces : WordPress MySQL des blocs (variable `WP_BLOCS`, servi sur `http://127.0.0.1:8405`), SPIP importé (`SPIP_BLOCS`, servi sur `http://127.0.0.1:8406`), comparateurs.

Prérequis : une base MySQL locale existante et un compte qui y a tous les droits (`BLOCS_DB_NAME`, `BLOCS_DB_USER`, `BLOCS_DB_PASS`) ; le compte n'a pas le droit de créer une base, le script ne la crée donc pas. Gilles a fourni une base de test ; ses identifiants ne sont pas versionnés, on les passe par l'environnement. Les tables WordPress portent le préfixe `wpblocs_` pour partager la base sans collision. Le WordPress de test actuel utilise SQLite, que wp2spip ne lit pas ; le SPIP importé reste en SQLite (défaut de `preparer_spip.sh`).

- [ ] **Step 1 : images de test**

`tests/references/blocs/generer_images.php` :

```php
<?php
// Images de test des blocs : aplats et motifs générés (aucune photographie). Domaine public (CC0).
// Usage : php tests/references/blocs/generer_images.php
foreach (['couverture' => [64, 128, 96], 'media' => [180, 90, 40]] as $nom => [$r, $v, $b]) {
	$im = imagecreatetruecolor(800, 500);
	imagefill($im, 0, 0, imagecolorallocate($im, $r, $v, $b));
	$clair = imagecolorallocate($im, min(255, $r + 80), min(255, $v + 80), min(255, $b + 80));
	for ($x = 0; $x < 800; $x += 100) {
		imagefilledrectangle($im, $x, 0, $x + 40, 500, $clair);
	}
	imagepng($im, __DIR__ . "/images/$nom.png", 9);
}
```

Run: `mkdir -p tests/references/blocs/images && php tests/references/blocs/generer_images.php && sha256sum tests/references/blocs/images/*.png`
Expected: deux fichiers PNG ; noter les empreintes dans `tests/references/README.md` (licence CC0, générées par ce script).

- [ ] **Step 2 : WordPress MySQL des blocs**

`scripts/install-wp-blocs.sh` :

```bash
#!/usr/bin/env bash
# WordPress 7.1.3 sur MySQL avec Astra 4.11.0 officiel, pour la page de démonstration des blocs
# (wp2spip lit une base MySQL). Usage :
#   WP_BLOCS=<dossier> ASTRA_WP=<thème astra 4.11.0> BLOCS_DB_NAME=… BLOCS_DB_USER=… BLOCS_DB_PASS=… \
#   scripts/install-wp-blocs.sh
# La base doit exister : le script ne la crée pas.
set -euo pipefail
: "${WP_BLOCS:?}" "${ASTRA_WP:?}" "${BLOCS_DB_NAME:?}" "${BLOCS_DB_USER:?}" "${BLOCS_DB_PASS:?}"
mkdir -p "$WP_BLOCS"
cd "$WP_BLOCS"
[ -f wp-load.php ] || wp core download --version=7.1.3 --locale=fr_FR
[ -f wp-config.php ] || wp config create --dbname="$BLOCS_DB_NAME" --dbuser="$BLOCS_DB_USER" --dbpass="$BLOCS_DB_PASS" \
	--dbhost=localhost --dbprefix=wpblocs_
wp db check >/dev/null
wp core is-installed || wp core install --url=http://127.0.0.1:8405 --title="Mon site" --admin_user=admin --admin_password=admin --admin_email=admin@example.test --skip-email
[ -e wp-content/themes/astra ] || ln -s "$ASTRA_WP" wp-content/themes/astra
wp theme activate astra
wp eval-file "$(dirname "$0")/../tests/references/blocs/page.php"
echo "WordPress des blocs prêt : php -S 127.0.0.1:8405 -t $WP_BLOCS $(dirname "$0")/../tools/wp-router.php"
```

Si `tools/wp-router.php` n'est pas dans le dépôt (il est dans le scratchpad de la session précédente), l'y ajouter : routeur `php -S` qui redirige les dossiers vers leur version avec `/` final et sert `index.php` sinon.

- [ ] **Step 3 : page de démonstration**

`tests/references/blocs/page.php` (exécuté par `wp eval-file`) :

```php
<?php
// Page « Démonstration des blocs » : un exemple de chaque bloc conservé par wp2spip, textes
// écrits pour les tests. Images importées depuis tests/references/blocs/images.
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$media = [];
foreach (['couverture', 'media'] as $nom) {
	$existant = get_page_by_title($nom, OBJECT, 'attachment');
	if ($existant) {
		$media[$nom] = $existant->ID;
		continue;
	}
	$tmp = wp_tempnam($nom . '.png');
	copy(__DIR__ . "/images/$nom.png", $tmp);
	$media[$nom] = media_handle_sideload(['name' => "$nom.png", 'tmp_name' => $tmp], 0, $nom);
}
$url = function ($nom) use ($media) {
	return wp_get_attachment_url($media[$nom]);
};

$contenu = <<<HTML
<!-- wp:paragraph --><p>Paragraphe de démonstration.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Titre de section</h2><!-- /wp:heading -->
<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Premier</li><!-- /wp:list-item --><!-- wp:list-item --><li>Second</li><!-- /wp:list-item --></ul><!-- /wp:list -->
<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>Colonne 1</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>Colonne 2</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->
<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>A</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>B</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>C</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->
<!-- wp:group --><div class="wp-block-group"><!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>Groupe imbriqué</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->
<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>Une citation de démonstration.</p><!-- /wp:paragraph --><cite>Une autrice</cite></blockquote><!-- /wp:quote -->
<!-- wp:pullquote --><figure class="wp-block-pullquote"><blockquote><p>Une citation en exergue.</p><cite>Un auteur</cite></blockquote></figure><!-- /wp:pullquote -->
<!-- wp:table --><figure class="wp-block-table"><table><thead><tr><th>Colonne</th><th>Valeur</th></tr></thead><tbody><tr><td>Un</td><td>1</td></tr><tr><td>Deux</td><td>2</td></tr></tbody></table></figure><!-- /wp:table -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.org/">Premier bouton</a></div><!-- /wp:button --><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.org/2">Second bouton</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
<!-- wp:separator --><hr class="wp-block-separator has-alpha-channel-opacity"/><!-- /wp:separator -->
<!-- wp:cover {"url":"{$url('couverture')}","id":{$media['couverture']},"dimRatio":50} --><div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-{$media['couverture']}" alt="" src="{$url('couverture')}" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph --><p>Texte sur la couverture</p><!-- /wp:paragraph --></div></div><!-- /wp:cover -->
<!-- wp:media-text {"mediaId":{$media['media']},"mediaType":"image"} --><div class="wp-block-media-text is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="{$url('media')}" alt="" class="wp-image-{$media['media']} size-full"/></figure><div class="wp-block-media-text__content"><!-- wp:paragraph --><p>Texte à côté du média.</p><!-- /wp:paragraph --></div></div><!-- /wp:media-text -->
<!-- wp:code --><pre class="wp-block-code"><code>echo "code";</code></pre><!-- /wp:code -->
<!-- wp:verse --><pre class="wp-block-verse">Des vers
de démonstration</pre><!-- /wp:verse -->
HTML;

$page = get_page_by_path('demonstration-des-blocs');
$id = wp_insert_post(['ID' => $page ? $page->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'demonstration-des-blocs', 'post_title' => 'Démonstration des blocs', 'post_content' => $contenu, 'comment_status' => 'closed']);
update_option('wp2spip_blocs_page', $id);
echo "Page $id\n";
```

Contrôler dans l'éditeur de blocs (`/wp-admin/post.php?post=<id>&action=edit`) qu'aucun bloc n'est signalé invalide ; corriger le HTML enregistré du bloc concerné sinon (le balisage attendu est celui que l'éditeur régénère).

- [ ] **Step 4 : SPIP importé**

`scripts/install-spip-blocs.sh` :

```bash
#!/usr/bin/env bash
# SPIP 4.4 importé par wp2spip depuis le WordPress des blocs, avec Astra SPIP actif.
# Usage : SPIP_BLOCS=<dossier vide> WP_BLOCS=<dossier> SPIP_CLI=… scripts/install-spip-blocs.sh
set -euo pipefail
: "${SPIP_BLOCS:?}" "${WP_BLOCS:?}" "${SPIP_CLI:?}"
ICI=$(cd "$(dirname "$0")/.." && pwd)
WP2SPIP=${WP2SPIP:-/src/wordpress/wp2spip}
SPIP_ADMIN_PASS=adminadmin "$WP2SPIP/outils/preparer_spip.sh" --spip "$SPIP_BLOCS" --wordpress "$WP_BLOCS" \
	--wp2spip lien --spip-cli "$SPIP_CLI" --adresse http://127.0.0.1:8406 --importer
cd "$SPIP_BLOCS"
[ -e plugins/astra ] || ln -s "$ICI" plugins/astra
$SPIP_CLI plugins:activer astra -y
$SPIP_CLI plugins:maj:bdd
$SPIP_CLI php:eval 'include_spip("inc/plugin"); echo test_plugin_actif("astra") ? "actif" : "inactif";' | grep -q '^actif$'
$SPIP_CLI cache:vider
echo "SPIP importé prêt : php -S 127.0.0.1:8406 -t $SPIP_BLOCS"
```

Run: `SPIP_BLOCS=… WP_BLOCS=… SPIP_CLI=… scripts/install-spip-blocs.sh`
Expected: bilan de wp2spip sans erreur ; la page « Démonstration des blocs » existe (`page = wordpress_page_<id WordPress>`).

Si `preparer_spip.sh` s'arrête sur `plugins:svp:telecharger`, relancer avec `--depot https://files.spip.net/spip-zone/archives.xml` (dépôt qui fonctionne pour site-tuto) et le consigner dans le README.

- [ ] **Step 5 : vérification des médias (test d'abord)**

`tests/comparaison/comparer_medias.php` :

```php
<?php
/**
 * Médias des blocs couverture et média/texte : le document affiché par SPIP doit provenir de la
 * même image que dans WordPress (empreinte SHA-256 du fichier original).
 * Usage : SPIP_CLI=… php tests/comparaison/comparer_medias.php <racine SPIP importé> <racine WP_BLOCS>
 */
[$script, $spip, $wp] = $argv;
$cli = getenv('SPIP_CLI');
$wp_json = shell_exec('wp --path=' . escapeshellarg($wp) . ' eval ' . escapeshellarg('
	$p = get_post(get_option("wp2spip_blocs_page"));
	$r = [];
	foreach (parse_blocks($p->post_content) as $b) {
		if ($b["blockName"] === "core/cover") { $r[] = ["cover", get_attached_file($b["attrs"]["id"]), $p->ID]; }
		if ($b["blockName"] === "core/media-text") { $r[] = ["media-text", get_attached_file($b["attrs"]["mediaId"]), $p->ID]; }
	}
	echo json_encode($r);'));
$attendus = json_decode((string) $wp_json, true);
$id_wp = $attendus[0][2] ?? 0;
$spip_json = shell_exec('cd ' . escapeshellarg($spip) . ' && ' . $cli . ' php:eval ' . escapeshellarg('
	$a = sql_fetsel("id_article, texte", "spip_articles", "page=" . sql_quote("wordpress_page_' . (int) $id_wp . '"));
	preg_match_all("#<div class=\"wp-block-(cover|media-text)\">\s*<doc(\d+)#", $a["texte"], $m, PREG_SET_ORDER);
	$r = [];
	foreach ($m as $x) {
		$f = sql_getfetsel("fichier", "spip_documents", "id_document=" . (int) $x[2]);
		$r[] = [$x[1], _DIR_IMG . $f, (int) $x[2], $a["id_article"]];
	}
	echo json_encode($r);'));
$obtenus = json_decode((string) $spip_json, true);
$echec = count($attendus) !== count($obtenus) || !$attendus;
foreach ($attendus as $i => [$bloc, $fichier]) {
	[$bloc_s, $fichier_s, $id_doc] = $obtenus[$i] ?? ['', '', 0];
	$ok = $bloc === $bloc_s && is_file($fichier) && is_file($spip . '/' . $fichier_s) && hash_file('sha256', $fichier) === hash_file('sha256', $spip . '/' . $fichier_s);
	echo ($ok ? 'ok    ' : 'ÉCART ') . "$bloc : document $id_doc\n";
	$echec = $echec || !$ok;
}
exit($echec ? 1 : 0);
```

Run: `SPIP_CLI=… php tests/comparaison/comparer_medias.php $SPIP_BLOCS $WP_BLOCS`
Expected: `ok    cover : document N`, `ok    media-text : document M`. Un `ÉCART` est un défaut d'import (wp2spip) ou de test : l'analyser, ne pas l'ignorer. Contrôler aussi dans le HTML public que chaque bloc affiche bien son document : `curl -s <URL de la page SPIP> | grep -o 'spip_document_[0-9]*'` contient `N` et `M`.

- [ ] **Step 6 : comparaison bloc par bloc**

`tests/references/blocs/pertes.json` (vide au départ ; une entrée par perte caractérisée, voir step 7) :

```json
[]
```

`tests/comparaison/comparer_blocs.cjs` :

```js
// Compare chaque bloc de la page de démonstration dans WordPress et dans le SPIP importé.
// Usage : NODE_PATH=$PW/node_modules node comparer_blocs.cjs <URL page WordPress> <URL page SPIP> <dossier>
// Blocs appariés dans l'ordre : enfants directs de .entry-content portant une classe wp-block-*.
// Seuil 1 % ; une perte caractérisée (tests/references/blocs/pertes.json) masque sa zone et
// vérifie sa propriété à la place.
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const { PNG } = require('pngjs');
const pixelmatch = require('pixelmatch');

const [urlWp, urlSpip, dossier] = process.argv.slice(2);
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
const SEUIL = 0.01;
const pertes = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'references', 'blocs', 'pertes.json'), 'utf8'));

async function blocs(browser, url, largeur, prefixe, masques) {
  const page = await browser.newPage({ viewport: { width: largeur, height: 900 }, userAgent: AGENT });
  await page.goto(url, { waitUntil: 'load' });
  await page.addStyleTag({ content: '*,*::before,*::after{transition:none!important;animation:none!important}' });
  const elements = await page.locator('.entry-content > [class*="wp-block-"]').all();
  const resultat = [];
  for (const [i, e] of elements.entries()) {
    const nom = (await e.getAttribute('class')).split(' ').find(c => c.startsWith('wp-block-'));
    for (const m of masques.filter(p => p.bloc === nom)) {
      await e.locator(m.masque).evaluateAll(n => n.forEach(x => { x.style.visibility = 'hidden'; }));
    }
    const fichier = `${dossier}/${prefixe}-${largeur}-${i}-${nom}.png`;
    await e.screenshot({ path: fichier });
    resultat.push({ nom, fichier });
  }
  const verifs = [];
  for (const p of masques) {
    const v = await page.locator(`.entry-content > .${p.bloc} ${p.cible || ''}`.trim()).first().evaluate((x, prop) => getComputedStyle(x).getPropertyValue(prop), p.propriete).catch(() => null);
    verifs.push({ p, v });
  }
  await page.close();
  return { resultat, verifs };
}

(async () => {
  fs.mkdirSync(dossier, { recursive: true });
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  let echec = false;
  for (const largeur of [1280, 390]) {
    const wp = await blocs(browser, urlWp, largeur, 'wordpress', pertes);
    const spip = await blocs(browser, urlSpip, largeur, 'spip', pertes);
    if (wp.resultat.map(b => b.nom).join() !== spip.resultat.map(b => b.nom).join()) {
      console.log(`ÉCART ${largeur} px : blocs différents\n  WordPress : ${wp.resultat.map(b => b.nom).join(' ')}\n  SPIP      : ${spip.resultat.map(b => b.nom).join(' ')}`);
      echec = true;
      continue;
    }
    wp.resultat.forEach((b, i) => {
      const a = PNG.sync.read(fs.readFileSync(b.fichier));
      const c = PNG.sync.read(fs.readFileSync(spip.resultat[i].fichier));
      const l = Math.min(a.width, c.width);
      const h = Math.min(a.height, c.height);
      const ra = new PNG({ width: l, height: h });
      const rc = new PNG({ width: l, height: h });
      PNG.bitblt(a, ra, 0, 0, l, h, 0, 0);
      PNG.bitblt(c, rc, 0, 0, l, h, 0, 0);
      const diff = new PNG({ width: l, height: h });
      const part = pixelmatch(ra.data, rc.data, diff.data, l, h, { threshold: 0.1 }) / (l * h);
      fs.writeFileSync(`${dossier}/diff-${largeur}-${i}-${b.nom}.png`, PNG.sync.write(diff));
      const tailles = Math.abs(a.width - c.width) > 2 || Math.abs(a.height - c.height) > 2;
      const ko = part > SEUIL || tailles;
      echec = echec || ko;
      console.log(`${ko ? 'ÉCART' : 'ok   '} ${largeur} px ${b.nom} : ${(part * 100).toFixed(2)} %${tailles ? ` ; ${a.width}×${a.height} / ${c.width}×${c.height}` : ''}`);
    });
    for (const { p, v } of spip.verifs) {
      const ok = v === p.attendu;
      echec = echec || !ok;
      console.log(`${ok ? 'ok   ' : 'ÉCART'} ${largeur} px perte ${p.bloc} ${p.perdu} : ${p.propriete} = ${v} (attendu ${p.attendu})`);
    }
  }
  await browser.close();
  process.exit(echec ? 1 : 0);
})();
```

Run: `NODE_PATH=$PW/node_modules node tests/comparaison/comparer_blocs.cjs http://127.0.0.1:8405/?pagename=demonstration-des-blocs "<URL SPIP de la page>" tests/comparaison/sorties/blocs`
Expected à ce stade : liste des écarts mesurés (premier relevé).

- [ ] **Step 7 : traiter chaque écart**

Pour chaque ligne `ÉCART`, ouvrir `diff-*.png` et comparer le HTML des deux blocs (`curl` des deux pages, bloc concerné). Trois cas, et seulement trois :

1. **Classe SPIP différente** (lien de bouton, document dans une couverture ou un bloc média/texte, paragraphes ajoutés par SPIP) : ajouter dans `css/astra-spip.css` la correspondance, en reprenant les valeurs de la règle WordPress équivalente lue dans `css/astra-blocs.css` ou `css/astra-dynamic.css` (ne pas inventer de valeur). Correspondances prévues par la spec, à n'écrire que si l'écart est mesuré :

```css
/* Blocs importés par wp2spip : éléments produits par SPIP à la place des classes WordPress.
   Chaque règle reprend les déclarations de la règle WordPress citée. */

/* = .wp-block-button__link (wp-includes/blocks/button/style.css, WordPress 7.1.3) */
.wp-block-button > a{align-content:center;box-sizing:border-box;cursor:pointer;display:inline-block;height:100%;text-align:center;word-break:break-word}

/* = .wp-block-cover .wp-block-cover__image-background (wp-includes/blocks/cover/style.css) */
.wp-block-cover > .spip_documents img{border:none;bottom:0;box-shadow:none;height:100%;left:0;margin:0;max-height:none;max-width:none;-o-object-fit:cover;object-fit:cover;outline:none;padding:0;position:absolute;right:0;top:0;width:100%}

/* = .wp-block-media-text>.wp-block-media-text__media (wp-includes/blocks/media-text/style.css) */
.wp-block-media-text > .spip_documents{align-self:center}
```

Couleurs et marges du bouton : Astra les pose sur `.wp-block-button .wp-block-button__link` dans `css/astra-dynamic.css`. Les relever avec
`grep -o '[^}]*\.wp-block-button \.wp-block-button__link[^{]*{[^}]*}' css/astra-dynamic.css`
et ajouter une règle `.wp-block-button > a{…}` avec les mêmes déclarations, précédée de la mention de la règle source. Les marges des paragraphes que SPIP ajoute dans une colonne (`.wp-block-column > p`) ne sont neutralisées que si la capture montre l'écart, en reprenant la valeur que WordPress applique au premier et au dernier enfant (`margin-block-start:0` d'après `css/astra-blocs.css`, à relire).

2. **Perte d'import caractérisée** : une classe ou un attribut présent dans le HTML WordPress et absent du HTML importé (ex. `has-alpha-channel-opacity` du séparateur, `is-stacked-on-mobile` du bloc média/texte, `wp-block-cover__inner-container`). Ajouter une entrée dans `tests/references/blocs/pertes.json` :

```json
{
	"bloc": "wp-block-separator",
	"perdu": "classe has-alpha-channel-opacity",
	"propriete": "opacity",
	"cible": "",
	"attendu": "1",
	"masque": ":scope",
	"effet": "le séparateur garde l'opacité par défaut au lieu de 0.4"
}
```

et la décrire dans `tests/references/README.md` (bloc, élément perdu relevé dans les deux HTML, propriété, effet). `masque` ne couvre que la zone touchée (`:scope` pour le bloc entier seulement si la propriété touche tout le bloc). Relancer : la ligne devient `ok … perte …`.

3. **Défaut du CSS généré ou du gabarit** : corriger le générateur (tâche 7, avec un test unitaire) ou `page_unique.html`.

Un écart qui n'entre dans aucun de ces cas n'est pas consigné comme perte : il reste un défaut à corriger.

- [ ] **Step 8 : relance complète et documentation**

Run: `node tests/comparaison/comparer_blocs.cjs …` puis `php tests/comparaison/comparer_medias.php …`
Expected: code 0 pour les deux.

Mettre à jour `tests/references/blocs/importe.html` avec le `.entry-content` réellement importé (tâche 8, step 1) et relancer `tests/e2e/cascade_blocs.cjs` : OK.

Dans `README.md`, section Tests, ajouter les commandes de `install-wp-blocs.sh`, `install-spip-blocs.sh`, `comparer_blocs.cjs`, `comparer_medias.php` et `cascade_blocs.cjs`, avec les ports 8405 et 8406.

- [ ] **Step 9 : commit**

```bash
git add scripts/install-wp-blocs.sh scripts/install-spip-blocs.sh tests/references/blocs tests/comparaison/comparer_blocs.cjs tests/comparaison/comparer_medias.php css/astra-spip.css tests/references/README.md README.md tests/references/blocs/importe.html
git commit -m "Comparer les blocs importés par wp2spip à WordPress"
```

---

### Task 10 : version, non-régression, documentation

**Files:**
- Modify: `paquet.xml` (version), `README.md`, `docs/superpowers/specs/2026-10-22-pages-uniques-blocs-design.md` (statut)

- [ ] **Step 1 : version**

Dans `paquet.xml`, `version="1.0.0"` → `version="1.1.0"` : c'est la version minimale qu'exigera le lot 2 du site Garden (accueil statique, réglages par page).

- [ ] **Step 2 : suite complète**

Run:
```bash
composer tests-unit && composer tests-integration
python3 -m unittest tests/references/test_extraire_css_blocs.py
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404
WP=$WP PW=$PW SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404
URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire.cjs
RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
for f in $(git diff --name-only main@{1} -- '*.php' | grep -v '^tests/'); do php7.4 -l "$f"; done
```
Expected: tout passe ; `comparer_html` et `comparer_captures` couvrent R0–R4 et P1–P3.

- [ ] **Step 3 : README et statut de la spec**

README : section « Pages uniques » (gabarit, sous-pages avec a2a, accueil statique, réglages par page, dépendances facultatives Pages et a2a) et section « Blocs WordPress importés » (ce qui est stylé, pertes caractérisées renvoyant à `tests/references/README.md`). Spec : `Statut : implémentée (version 1.1.0)`.

- [ ] **Step 4 : commit**

```bash
git add paquet.xml README.md docs/superpowers/specs/2026-10-22-pages-uniques-blocs-design.md
git commit -m "Publier la version 1.1.0 : pages uniques et blocs natifs"
```

# Builder simplifié d'en-tête et de pied de page — plan d'exécution

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Composer l'en-tête et le pied de page d'astra-spip par rangées, zones et éléments, avec le HTML du Header/Footer Builder d'Astra 4.11.0.

**Architecture:** Les placements reprennent la structure des options d'Astra ; une normalisation dédiée applique les valeurs par défaut et un registre explicite des éléments. Des fonctions PHP pures calculent les rangées, colonnes et panneau mobile à afficher, comme `Astra_Builder_Helper` ; les squelettes bouclent dessus et incluent un squelette par élément. Le WordPress de test, configuré par wp-cli à partir d'un état initial fixé, sert de référence pour le HTML, le CSS dynamique et les captures.

**Tech Stack:** SPIP 4.4 (squelettes, CVT, boucles DATA), PHP 7.4–8.5 pour le plugin, PHP 8.2+ et PHPUnit 11.5 pour les tests, spip-cli avec le correctif MR !91 (`spip-cli-custom`), WordPress + Astra 4.11.0 + wp-cli, Playwright (Chromium).

Spécification : `docs/superpowers/specs/2026-10-22-builder-entete-pied-design.md`.

## Global Constraints

- Licence du plugin : GPL-3.0-or-later ; code repris d'Astra : GPL-2.0-or-later. Vérifier la licence de toute dépendance ou ressource ajoutée (icônes comprises).
- Aucun contenu de modèle de démarrage (texte, photo) n'est versionné.
- Code du plugin exécutable en PHP 7.4 à 8.5 : pas de type `mixed`, d'union de types, de `match`, d'arguments nommés, de `str_contains`/`str_starts_with`, de `array_is_list`.
- Version SPIP vérifiée dans ce chantier : 4.4. SPIP 4.2 et 5.0 restent déclarés dans `paquet.xml` mais sont présentés comme non vérifiés pour le builder (README) ; SPIP 5.0 fera l'objet d'un chantier séparé.
- Fichiers de langue au format `return [...]` ; pas de critère `{fusion}`.
- `css/main.min.css` et `js/frontend.min.js` restent les fichiers d'Astra, non modifiés.
- Sans configuration `astra/hfb`, le rendu reste identique au rendu actuel.
- Commandes spip-cli : `$SPIP_CLI` (par exemple `php /src/spip-cli-custom/bin/spip`), qui doit inclure le correctif MR !91.
- Messages de commit sans trailer.

## Découpage et portée de ce plan

Le plan couvre les **tâches 1 à 3** en détail. Le balisage exact des éléments et du panneau mobile, le CSS dynamique à fusionner et les règles de normalisation de la comparaison HTML ne sont connus qu'après les captures de la tâche 1 : les tâches 4 à 9 font l'objet d'un **second plan**, rédigé à la fin de la tâche 1, dans `docs/superpowers/plans/2026-10-22-builder-entete-pied-elements.md`.

| Tâche | Contenu | Plan |
|---|---|---|
| 1 | Références WordPress reproductibles, contrôles bloquants | ici |
| 2 | Registre, normalisation et calcul des rangées (tests unitaires) | ici |
| 3 | Environnement de tests d'intégration SPIP 4.4 | ici |
| 4 | Squelettes des éléments et de l'en-tête ordinateur, tests d'intégration | second plan |
| 5 | En-tête mobile (menu déroulant et panneau latéral) | second plan |
| 6 | Pied de page à colonnes | second plan |
| 7 | Formulaire `configurer_astra_hfb`, chaînes de langue fr/en, test de bout en bout | second plan |
| 8 | Comparaison HTML automatique SPIP/WordPress | second plan |
| 9 | Comparaison visuelle automatique (seuil en pixels), README | second plan |

Chaque tâche du second plan qui crée ou modifie un squelette ajoute ses propres tests d'intégration : le contrôle de non-régression de la tâche 2 vérifie seulement que l'ajout PHP ne perturbe pas l'existant.

## Fichiers

| Fichier | Rôle |
|---|---|
| `tests/references/wp/commun.php` | État initial fixé, catégories, menus, widgets (exécuté par `wp eval-file`) |
| `tests/references/wp/widgets-base.json` | Widgets de la barre latérale de l'état initial |
| `tests/references/wp/R0-defaut.php` … `R4-pied-asymetrique.php` | Configurations de référence |
| `tests/references/wp/R0-defaut.json` … `R4-pied-asymetrique.json` | Éléments attendus pour chaque configuration (contrôles bloquants) |
| `tests/references/capture.cjs` | Captures Playwright et contrôles d'éléments visibles et du menu mobile |
| `tests/references/capturer.sh` | Applique une configuration et enregistre ses références |
| `tests/references/normaliser.sh` | Normalise un HTML WordPress pour comparer deux captures |
| `tests/references/README.md` | Mode d'emploi et relevés |
| `inc/astra_hfb.php` | Fonctions pures : défauts, registre, normalisation, calcul |
| `astra_fonctions.php` | Inclut `inc/astra_hfb.php` ; `astra_hfb_rangees()`, `astra_hfb_panneau()` |
| `tests/unit/HfbTest.php` | Tests unitaires |
| `scripts/install-spip-test.sh` | Installe un SPIP 4.4 de test dans `vendor/spip/spip` avec spip-cli |
| `tests/bootstrap_integration.php` | Charge le SPIP de test |
| `tests/integration/PluginActifTest.php`, `HfbConfigurationTest.php` | Tests d'intégration |
| `composer.json`, `phpunit.xml` | Outillage de test |
| `.gitignore` | Ignore `vendor/`, `.phpunit.cache/`, `tests/references/sorties/` |

---

### Task 1: Références WordPress reproductibles

**Files:**
- Create: `tests/references/wp/commun.php`, `tests/references/wp/widgets-base.json`
- Create: `tests/references/wp/R0-defaut.php`, `R1-entete-complet.php`, `R2-entete-centre.php`, `R3-pied-colonnes.php`, `R4-pied-asymetrique.php` et les `.json` correspondants
- Create: `tests/references/capture.cjs`, `tests/references/capturer.sh`, `tests/references/normaliser.sh`, `tests/references/README.md`
- Modify: `.gitignore`

**Interfaces:**
- Consumes: WordPress de test servi sur `http://127.0.0.1:8401` (racine `$WP`), wp-cli (`wp`), Playwright dans `$PW` (dossier contenant `node_modules/playwright`), Chromium `~/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome`.
- Produces: pour chaque configuration `Rn`, le dossier `$SORTIES/Rn/` (par défaut `tests/references/sorties/Rn/`) contenant `accueil.html`, `article.html`, `dynamique.css` (non vide), `ordinateur.png`, `mobile.png`, `mobile-menu.png` (si un menu mobile est attendu), `options.json`. Le second plan s'appuie sur ces fichiers.

Variables utilisées dans les commandes :

```bash
S=/tmp/claude-1000/-src-astra-spip/dc097d1c-c7f6-455a-b514-0ce253b369df/scratchpad
export WP=$S/wp PW=$S/pw
```

- [ ] **Step 1: Figer les widgets de la barre latérale**

Enregistrer les widgets actuels de `sidebar-1` et les options `widget_<type>` qui les décrivent (widgets classiques ou blocs, utilisés par les références du portage initial) :

```bash
mkdir -p tests/references/wp
wp --path=$WP eval '$ids = get_option("sidebars_widgets")["sidebar-1"] ?? []; $options = []; foreach ($ids as $id) { $type = preg_replace("/-\d+$/", "", $id); $options["widget_" . $type] = get_option("widget_" . $type); } echo wp_json_encode(["options" => $options, "sidebars_widgets" => ["wp_inactive_widgets" => [], "sidebar-1" => $ids, "array_version" => 3]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);' > tests/references/wp/widgets-base.json
php -r '$j = json_decode(file_get_contents("tests/references/wp/widgets-base.json"), true); echo count($j["sidebars_widgets"]["sidebar-1"]), " widgets\n";'
```

Expected : un nombre de widgets ≥ 1. S'il vaut 0, la barre latérale de référence est vide : le noter dans le README et continuer.

- [ ] **Step 2: Écrire les fonctions communes**

`tests/references/wp/commun.php` :

```php
<?php
// Fonctions partagées par les configurations de référence du builder (wp eval-file).
// Chaque configuration part de ref_etat_initial() : le résultat ne dépend pas
// des configurations appliquées auparavant. Les menus et catégories, créés au premier
// passage, sont réutilisés ensuite pour garder des identifiants stables.

function ref_etat_initial(): void {
	update_option('astra-settings', [
		'theme-auto-version' => '4.11.0',
		'site-sidebar-layout' => 'right-sidebar',
		'single-post-sidebar-layout' => 'default',
		'single-post-ast-content-layout' => 'default',
	]);
	remove_theme_mods();
	delete_option('widget_text');
	$base = json_decode(file_get_contents(__DIR__ . '/widgets-base.json'), true);
	foreach ($base['options'] as $option => $valeur) {
		update_option($option, $valeur);
	}
	update_option('sidebars_widgets', $base['sidebars_widgets']);
	ref_categories();
}

// Catégories utilisées par les menus : Jardinage (avec la sous-catégorie Potager) et Actualités.
function ref_categories(): array {
	$ids = [];
	foreach ([['jardinage', 'Jardinage', ''], ['potager', 'Potager', 'jardinage'], ['actualites', 'Actualités', '']] as [$slug, $nom, $parent]) {
		$terme = get_term_by('slug', $slug, 'category');
		if (!$terme) {
			$r = wp_insert_term($nom, 'category', ['slug' => $slug, 'parent' => $parent ? $ids[$parent] : 0]);
			$ids[$slug] = $r['term_id'];
		} else {
			$ids[$slug] = $terme->term_id;
		}
	}
	return $ids;
}

// Menu Jardinage > Potager, Actualités, affecté à un emplacement du thème.
// Un menu déjà créé est réutilisé tel quel : ses entrées gardent les mêmes identifiants
// (classes menu-item-N) d'une capture à l'autre.
function ref_menu(string $nom, string $emplacement): void {
	$cats = ref_categories();
	$menu = wp_get_nav_menu_object($nom);
	if ($menu) {
		$emplacements = get_theme_mod('nav_menu_locations', []);
		$emplacements[$emplacement] = $menu->term_id;
		set_theme_mod('nav_menu_locations', $emplacements);
		return;
	}
	$id = wp_create_nav_menu($nom);
	$ajouter = function (string $slug, int $parent = 0) use ($id, $cats) {
		return wp_update_nav_menu_item($id, 0, [
			'menu-item-title' => get_term($cats[$slug])->name,
			'menu-item-object' => 'category',
			'menu-item-object-id' => $cats[$slug],
			'menu-item-type' => 'taxonomy',
			'menu-item-parent-id' => $parent,
			'menu-item-status' => 'publish',
		]);
	};
	$jardinage = $ajouter('jardinage');
	$ajouter('potager', $jardinage);
	$ajouter('actualites');
	$emplacements = get_theme_mod('nav_menu_locations', []);
	$emplacements[$emplacement] = $id;
	set_theme_mod('nav_menu_locations', $emplacements);
}

function ref_reglages(array $reglages): void {
	$s = get_option('astra-settings', []);
	foreach ($reglages as $cle => $valeur) {
		$s[$cle] = $valeur;
	}
	update_option('astra-settings', $s);
}

function ref_widget_texte(string $zone, string $titre, string $texte): void {
	$widgets = get_option('widget_text', []);
	$n = max(array_merge([1], array_filter(array_keys($widgets), 'is_int'))) + 1;
	$widgets[$n] = ['title' => $titre, 'text' => $texte, 'filter' => true, 'visual' => true];
	update_option('widget_text', $widgets);
	$zones = get_option('sidebars_widgets', []);
	$zones[$zone][] = 'text-' . $n;
	update_option('sidebars_widgets', $zones);
}
```

- [ ] **Step 3: Écrire les cinq configurations**

`tests/references/wp/R0-defaut.php` :

```php
<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
```

`tests/references/wp/R1-entete-complet.php` :

```php
<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_menu('Secondaire', 'secondary_menu');
ref_reglages([
	'header-desktop-items' => [
		'popup'   => ['popup_content' => ['mobile-menu']],
		'above'   => ['above_left' => ['html-1'], 'above_left_center' => [], 'above_center' => [], 'above_right_center' => [], 'above_right' => ['social-icons-1']],
		'primary' => ['primary_left' => ['logo'], 'primary_left_center' => [], 'primary_center' => [], 'primary_right_center' => [], 'primary_right' => ['menu-1', 'search', 'button-1']],
		'below'   => ['below_left' => [], 'below_left_center' => [], 'below_center' => ['menu-2'], 'below_right_center' => [], 'below_right' => []],
	],
	'header-mobile-items' => [
		'popup'   => ['popup_content' => ['mobile-menu']],
		'above'   => ['above_left' => [], 'above_center' => ['html-1'], 'above_right' => []],
		'primary' => ['primary_left' => ['logo'], 'primary_center' => [], 'primary_right' => ['search', 'mobile-trigger']],
		'below'   => ['below_left' => [], 'below_center' => [], 'below_right' => []],
	],
	'mobile-header-type' => 'dropdown',
	'header-html-1' => '<p>Ouvert du lundi au vendredi</p>',
	'header-button1-text' => 'Nous contacter',
	'header-button1-link-option' => ['url' => 'http://127.0.0.1:8401/', 'new_tab' => false, 'link_rel' => ''],
]);
```

`tests/references/wp/R2-entete-centre.php` :

```php
<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_reglages([
	'header-desktop-items' => [
		'popup'   => ['popup_content' => ['mobile-menu']],
		'above'   => ['above_left' => [], 'above_left_center' => [], 'above_center' => [], 'above_right_center' => [], 'above_right' => []],
		'primary' => ['primary_left' => ['menu-1'], 'primary_left_center' => [], 'primary_center' => ['logo'], 'primary_right_center' => [], 'primary_right' => ['account', 'search']],
		'below'   => ['below_left' => ['widget-1'], 'below_left_center' => [], 'below_center' => [], 'below_right_center' => [], 'below_right' => ['html-2']],
	],
	'header-mobile-items' => [
		'popup'   => ['popup_content' => ['mobile-menu', 'search']],
		'above'   => ['above_left' => [], 'above_center' => [], 'above_right' => []],
		'primary' => ['primary_left' => ['mobile-trigger'], 'primary_center' => ['logo'], 'primary_right' => ['account']],
		'below'   => ['below_left' => [], 'below_center' => [], 'below_right' => []],
	],
	'mobile-header-type' => 'off-canvas',
	'header-html-2' => '<p>Livraison gratuite dès 50 €</p>',
]);
ref_widget_texte('header-widget-1', 'Horaires', 'Lundi–vendredi, 9 h – 18 h');
```

`tests/references/wp/R3-pied-colonnes.php` :

```php
<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_menu('Pied', 'footer_menu');
ref_reglages([
	'footer-desktop-items' => [
		'above'   => ['above_1' => ['html-1'], 'above_2' => ['social-icons-1'], 'above_3' => [], 'above_4' => [], 'above_5' => []],
		'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4'], 'primary_5' => []],
		'below'   => ['below_1' => ['copyright'], 'below_2' => ['menu'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
	],
	'hba-footer-column' => '2',
	'hba-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
	'hb-footer-column' => '4',
	'hb-footer-layout' => ['desktop' => '4-equal', 'tablet' => '4-equal', 'mobile' => 'full'],
	'hbb-footer-column' => '2',
	'hbb-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
	'footer-html-1' => '<p>Un site éditorial propulsé par SPIP</p>',
]);
foreach ([1, 2, 3, 4] as $i) {
	ref_widget_texte('footer-widget-' . $i, 'Colonne ' . $i, 'Texte de la colonne ' . $i);
}
```

`tests/references/wp/R4-pied-asymetrique.php` :

```php
<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_menu('Pied', 'footer_menu');
ref_reglages([
	'footer-desktop-items' => [
		'above'   => ['above_1' => [], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
		'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['html-1', 'social-icons-1'], 'primary_3' => ['menu'], 'primary_4' => [], 'primary_5' => []],
		'below'   => ['below_1' => ['copyright'], 'below_2' => ['html-2'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
	],
	'hb-footer-column' => '3',
	'hb-footer-layout' => ['desktop' => '3-cwide', 'tablet' => '3-cwide', 'mobile' => 'full'],
	'hbb-footer-column' => '2',
	'hbb-footer-layout' => ['desktop' => '2-lheavy', 'tablet' => '2-lheavy', 'mobile' => 'full'],
	'footer-menu-layout' => ['desktop' => 'vertical', 'tablet' => 'vertical', 'mobile' => 'vertical'],
	'footer-html-1' => '<p>Suivez-nous</p>',
	'footer-html-2' => '<p>Mentions légales</p>',
]);
ref_widget_texte('footer-widget-1', 'À propos', 'Quelques mots sur le site.');
```

- [ ] **Step 4: Écrire les éléments attendus**

Chaque fichier `.json` liste les sélecteurs qui doivent être **visibles** en ordinateur (1280 px) et en mobile (390 px), ceux qui doivent seulement **exister** dans le DOM, le menu mobile attendu (`dropdown`, `off-canvas` ou `null`) et les sélecteurs qui doivent exister une fois le menu mobile ouvert (`menuOuvert`, dont le sous-menu). Les classes viennent des gabarits d'Astra 4.11.0.

`tests/references/wp/R0-defaut.json` :

```json
{
	"ordinateur": [
		".site-primary-header-wrap .site-branding",
		".ast-builder-menu-1",
		".site-below-footer-wrap .ast-footer-copyright"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		".ast-builder-menu-1 .sub-menu"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	]
}
```

`tests/references/wp/R1-entete-complet.json` :

```json
{
	"ordinateur": [
		".ast-above-header-wrap .ast-header-html-1",
		".ast-above-header-wrap .ast-header-social-wrap",
		".site-primary-header-wrap .ast-header-search",
		".site-primary-header-wrap .ast-header-button-1",
		".ast-below-header-wrap .ast-builder-menu-2"
	],
	"mobile": [
		".ast-mobile-header-wrap .ast-header-html-1",
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		".ast-builder-menu-1 .sub-menu",
		".ast-builder-menu-2 .sub-menu"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	]
}
```

`tests/references/wp/R2-entete-centre.json` :

```json
{
	"ordinateur": [
		".ast-grid-center-col-layout .site-branding",
		".site-primary-header-wrap .ast-header-account",
		".ast-below-header-wrap [class*='header-widget-area']",
		".ast-below-header-wrap .ast-header-html-2"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle",
		".ast-mobile-header-wrap .ast-header-account"
	],
	"presents": [
		".ast-mobile-popup-drawer"
	],
	"menuMobile": "off-canvas",
	"menuOuvert": [
		"#ast-mobile-popup .ast-builder-menu-mobile .sub-menu"
	]
}
```

`tests/references/wp/R3-pied-colonnes.json` :

```json
{
	"ordinateur": [
		".site-above-footer-wrap .ast-footer-html-1",
		".site-above-footer-wrap .ast-footer-social-wrap",
		".site-primary-footer-wrap .footer-widget-area",
		".site-below-footer-wrap .ast-footer-copyright",
		".site-below-footer-wrap .footer-nav-wrap"
	],
	"mobile": [
		".site-below-footer-wrap .ast-footer-copyright"
	],
	"presents": [
		".site-primary-footer-wrap.ast-builder-grid-row-4-equal",
		".site-primary-footer-wrap .site-footer-section-4"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	]
}
```

`tests/references/wp/R4-pied-asymetrique.json` :

```json
{
	"ordinateur": [
		".site-primary-footer-wrap .footer-nav-wrap",
		".site-primary-footer-wrap .ast-footer-html-1",
		".site-below-footer-wrap .ast-footer-html-2"
	],
	"mobile": [
		".site-below-footer-wrap .ast-footer-copyright"
	],
	"presents": [
		".site-primary-footer-wrap.ast-builder-grid-row-3-cwide",
		".site-below-footer-wrap.ast-builder-grid-row-2-lheavy"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	]
}
```

- [ ] **Step 5: Écrire le script de capture avec contrôles bloquants**

`tests/references/capture.cjs` :

```js
// Usage : NODE_PATH=$PW/node_modules node capture.cjs <url> <dossier> <attendu.json>
// Captures ordinateur (1280 px) et mobile (390 px), menu mobile ouvert.
// Sort en erreur (code 1) si : erreur JavaScript, sélecteur attendu non visible ou absent,
// menu mobile attendu qui ne s'ouvre pas.
const fs = require('fs');
const { chromium } = require('playwright');
(async () => {
  const [url, dossier, fichierAttendu] = process.argv.slice(2);
  const attendu = JSON.parse(fs.readFileSync(fichierAttendu, 'utf8'));
  const echecs = [];
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  for (const [nom, largeur] of [['ordinateur', 1280], ['mobile', 390]]) {
    const page = await browser.newPage({ viewport: { width: largeur, height: 900 } });
    page.on('pageerror', e => echecs.push(`${nom} : erreur JavaScript : ${e.message}`));
    await page.goto(url, { waitUntil: 'load', timeout: 90000 });
    await page.screenshot({ path: `${dossier}/${nom}.png`, fullPage: true });
    for (const sel of attendu[nom] || []) {
      if (!(await page.locator(sel).first().isVisible())) echecs.push(`${nom} : non visible : ${sel}`);
    }
    if (nom === 'ordinateur') {
      for (const sel of attendu.presents || []) {
        if ((await page.locator(sel).count()) === 0) echecs.push(`absent du DOM : ${sel}`);
      }
    }
    if (nom === 'mobile' && attendu.menuMobile) {
      const bouton = page.locator('.ast-mobile-header-wrap .menu-toggle').first();
      if (!(await bouton.isVisible())) {
        echecs.push('mobile : bouton du menu non visible');
      } else {
        await bouton.click();
        const panneau = attendu.menuMobile === 'off-canvas'
          ? page.locator('#ast-mobile-popup.active, .ast-mobile-popup-drawer.active').first()
          : page.locator('.ast-mobile-header-content').first();
        try {
          await panneau.waitFor({ state: 'visible', timeout: 3000 });
          await page.waitForTimeout(800); // fin de l'animation d'ouverture
          for (const sel of attendu.menuOuvert || []) {
            if ((await page.locator(sel).count()) === 0) echecs.push(`mobile : absent du menu ouvert : ${sel}`);
          }
        } catch (e) {
          echecs.push(`mobile : le menu ${attendu.menuMobile} ne s'ouvre pas`);
        }
        await page.screenshot({ path: `${dossier}/mobile-menu.png`, fullPage: false });
      }
    }
    await page.close();
  }
  await browser.close();
  if (echecs.length) {
    console.error(echecs.join('\n'));
    process.exit(1);
  }
})();
```

- [ ] **Step 6: Écrire les scripts d'orchestration et de normalisation**

`tests/references/normaliser.sh` :

```bash
#!/usr/bin/env bash
# Usage : tests/references/normaliser.sh fichier.html
# Retire d'un HTML WordPress ce qui varie d'une exécution à l'autre (versions, nonces).
sed -E 's/([?&]ver=)[^"&'"'"' ]*/\1X/g; s/("nonce":")[^"]*"/\1X"/g; s/(name="_wpnonce" value=")[^"]*"/\1X"/g' "$1"
```

`tests/references/capturer.sh` :

```bash
#!/usr/bin/env bash
# Usage : WP=… PW=… [SORTIES=…] tests/references/capturer.sh R1-entete-complet [...]
# Applique chaque configuration au WordPress de test et enregistre ses références.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
URL=${URL:-http://127.0.0.1:8401}
SORTIES=${SORTIES:-$ICI/sorties}
: "${WP:?WP doit indiquer la racine du WordPress de test}"
: "${PW:?PW doit indiquer le dossier contenant node_modules/playwright}"
for conf in "$@"; do
	sortie="$SORTIES/$conf"
	rm -rf "$sortie"
	mkdir -p "$sortie"
	wp --path="$WP" eval-file "$ICI/wp/$conf.php"
	wp --path="$WP" option get astra-settings --format=json > "$sortie/options.json"
	curl -sf "$URL/" -o "$sortie/accueil.html"
	curl -sf "$URL/?p=1" -o "$sortie/article.html"
	php -r '$h = file_get_contents($argv[1]); if (!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s", $h, $m)) { exit(1); } echo $m[1];' "$sortie/accueil.html" > "$sortie/dynamique.css" \
		|| { echo "$conf : CSS dynamique introuvable" >&2; exit 1; }
	[ -s "$sortie/dynamique.css" ] || { echo "$conf : CSS dynamique vide" >&2; exit 1; }
	NODE_PATH="$PW/node_modules" node "$ICI/capture.cjs" "$URL/" "$sortie" "$ICI/wp/$conf.json"
	echo "$conf : OK ($(wc -c < "$sortie/accueil.html") octets HTML, $(wc -c < "$sortie/dynamique.css") octets CSS)"
done
```

Puis :

```bash
chmod +x tests/references/capturer.sh tests/references/normaliser.sh
printf 'vendor/\n.phpunit.cache/\ntests/references/sorties/\n' >> .gitignore
```

- [ ] **Step 7: Capturer les cinq configurations**

Run :

```bash
tests/references/capturer.sh R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique
```

Expected : cinq lignes « Rn : OK (…) », code de sortie 0.

En cas d'échec d'un contrôle, ne pas affaiblir le contrôle : relire le HTML capturé (`tests/references/sorties/Rn/accueil.html`) pour déterminer si la configuration n'a pas produit l'élément (corriger la configuration) ou si le sélecteur ne correspond pas au balisage réel d'Astra (corriger le sélecteur dans le `.json` et noter la classe réelle dans le README). Pour R2, si `header-widget-1` n'est pas rendu, vérifier avec `wp --path=$WP sidebar list` **après** application de R2 (la zone n'est enregistrée qu'une fois le composant placé).

- [ ] **Step 8: Vérifier la reproductibilité**

Run :

```bash
SORTIES=$S/repro tests/references/capturer.sh R4-pied-asymetrique R1-entete-complet
diff <(tests/references/normaliser.sh tests/references/sorties/R1-entete-complet/accueil.html) \
     <(tests/references/normaliser.sh $S/repro/R1-entete-complet/accueil.html) && echo REPRODUCTIBLE
diff tests/references/sorties/R1-entete-complet/dynamique.css $S/repro/R1-entete-complet/dynamique.css && echo CSS-IDENTIQUE
```

Expected : `REPRODUCTIBLE` et `CSS-IDENTIQUE` (R1 capturé après R4 donne le même résultat que R1 capturé après R0). Si une différence subsiste, l'ajouter à `normaliser.sh` seulement si elle est par nature variable (date, nonce) ; sinon, compléter `ref_etat_initial()`.

Relancer enfin R0 pour laisser le WordPress de test dans l'état par défaut :

```bash
SORTIES=$S/repro tests/references/capturer.sh R0-defaut
```

- [ ] **Step 9: Relever les données nécessaires au second plan**

Run :

```bash
A=$S/upstream/astra
grep -rhoE "'(full|[2-6]-[a-z]+)'" $A/inc/customizer/configurations/builder/footer/ $A/inc/builder/type/footer/ | sort -u
grep -rln "social" $A/inc/builder/type/base/ | head
grep -n -i "licen" $A/readme.txt | head
```

Consigner dans `tests/references/README.md` : la liste exacte des répartitions de colonnes ; le fichier source des icônes sociales et leur licence (sans mention dédiée, elles relèvent de la licence GPL d'Astra) ; pour chaque élément, la classe racine et l'extrait de balisage relevés dans le HTML capturé (y compris le panneau mobile `dropdown` de R1 et `off-canvas` de R2).

- [ ] **Step 10: Écrire le README des références**

`tests/references/README.md` décrit : le rôle des références ; les prérequis (`WP`, `PW`, Chromium, wp-cli) ; l'état initial fixé par `ref_etat_initial()` et ce qu'il ne fixe pas (les articles de démonstration du WordPress de test) ; la commande du step 7 et le contrôle de reproductibilité du step 8 ; une ligne par configuration Rn ; les relevés du step 9. Il précise que `sorties/` n'est pas versionné.

- [ ] **Step 11: Commit**

```bash
git add .gitignore tests/references docs/superpowers
git commit -m "Ajouter les références WordPress du builder d'en-tête et de pied de page

Cinq configurations d'Astra appliquées par wp-cli à partir d'un état
initial fixé, avec un script qui enregistre le HTML, le CSS dynamique
et des captures, et échoue si un élément attendu manque ou si le menu
mobile ne s'ouvre pas. Spécification et plan du builder simplifié."
```

- [ ] **Step 12: Rédiger le second plan**

À partir des HTML et CSS capturés, écrire `docs/superpowers/plans/2026-10-22-builder-entete-pied-elements.md` (tâches 4 à 9) avec, pour chaque élément, le balisage exact recopié des références, les tests d'intégration de chaque squelette, et les règles de normalisation de la comparaison HTML. Le committer.

---

### Task 2: Registre, normalisation et calcul des rangées

**Files:**
- Create: `inc/astra_hfb.php`, `tests/unit/HfbTest.php`, `composer.json`, `phpunit.xml`
- Modify: `astra_fonctions.php` (inclusion, `astra_hfb_rangees()`, `astra_hfb_panneau()`)

**Interfaces:**
- Consumes: rien des autres tâches (noms de zones et d'éléments fixés par la spécification).
- Produces (toutes dans `inc/astra_hfb.php`, sans dépendance à SPIP, PHP 7.4) :
  - `astra_hfb_defauts(): array` — configuration complète par défaut (`entete_ordinateur`, `entete_mobile`, `pied`, `rangees`, `menu_mobile`, `elements`).
  - `astra_hfb_registre(): array` — `id => liste des contextes autorisés` parmi `ordinateur`, `mobile`, `panneau`, `pied`.
  - `astra_hfb_normaliser($config): array` — configuration complète : défauts appliqués selon les règles de fusion, puis éléments inconnus, hors contexte, en double ou au-delà du nombre de colonnes retirés.
  - `astra_hfb_element(string $id, string $groupe): array` — `['id' => 'menu-2', 'fond' => 'inclure/hfb/menu', 'numero' => '2', 'groupe' => 'entete']`.
  - `astra_hfb_calculer_entete(array $items): array` — rangées non vides, chacune `['rangee' => 'primary', 'cotes' => bool, 'centre' => bool, 'zones' => ['left' => [...], 'left_center' => [...], 'center' => [...], 'right_center' => [...], 'right' => [...]]]`, éléments décrits par `astra_hfb_element(…, 'entete')`. `$items` doit être normalisé.
  - `astra_hfb_calculer_panneau(array $mobile): array` — éléments du panneau (`groupe` `entete`), vide si le panneau est vide ou si aucune rangée mobile ne contient `mobile-trigger`.
  - `astra_hfb_calculer_pied(array $items, array $rangees): array` — rangées non vides, chacune `['rangee' => 'below', 'classes' => '…', 'colonnes' => [1 => [...], 2 => [...]]]`, éléments décrits par `astra_hfb_element(…, 'pied')`.
- Produces (dans `astra_fonctions.php`) : `astra_hfb_config(): array` (configuration normalisée lue par `lire_config('astra/hfb')`), `astra_hfb_rangees(string $contexte): array`, `astra_hfb_panneau(): array`.

- [ ] **Step 1: Installer PHPUnit**

`composer.json` :

```json
{
	"name": "epilibre-design/astra-spip",
	"description": "Portage pour SPIP du thème WordPress Astra",
	"type": "spip-plugin",
	"license": "GPL-3.0-or-later",
	"require-dev": {
		"phpunit/phpunit": "^11.5"
	},
	"config": {
		"platform": {"php": "8.2"},
		"sort-packages": true
	},
	"scripts": {
		"tests-unit": "phpunit --colors --testsuite unit"
	}
}
```

`phpunit.xml` :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="inc/astra_hfb.php" cacheDirectory=".phpunit.cache" colors="true">
	<testsuites>
		<testsuite name="unit">
			<directory suffix="Test.php">tests/unit</directory>
		</testsuite>
		<testsuite name="integration">
			<directory suffix="Test.php">tests/integration</directory>
		</testsuite>
	</testsuites>
</phpunit>
```

Run : `composer install` — Expected : `vendor/bin/phpunit --version` affiche PHPUnit 11.5.x.

- [ ] **Step 2: Écrire les tests (échec attendu)**

`tests/unit/HfbTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

final class HfbTest extends TestCase {

	private static function ids(array $elements): array {
		return array_column($elements, 'id');
	}

	// Valeurs par défaut

	public function testEnTeteParDefautNeContientQueLaRangeePrincipale(): void {
		$c = astra_hfb_normaliser([]);
		$rangees = astra_hfb_calculer_entete($c['entete_ordinateur']);
		$this->assertSame(['primary'], array_column($rangees, 'rangee'));
		$this->assertTrue($rangees[0]['cotes']);
		$this->assertFalse($rangees[0]['centre']);
		$this->assertSame(['logo'], self::ids($rangees[0]['zones']['left']));
		$this->assertSame(['menu-1'], self::ids($rangees[0]['zones']['right']));
	}

	public function testConfigurationNonTableauDonneLesDefauts(): void {
		$this->assertSame(astra_hfb_normaliser([]), astra_hfb_normaliser(''));
		$this->assertSame(astra_hfb_normaliser([]), astra_hfb_normaliser(null));
	}

	// Règles de fusion

	public function testZoneAbsenteHeriteDuDefaut(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['above' => ['above_center' => ['html-1']]]]);
		$this->assertSame(['html-1'], $c['entete_ordinateur']['above']['above_center']);
		$this->assertSame(['logo'], $c['entete_ordinateur']['primary']['primary_left']);
		$this->assertSame(['menu-1'], $c['entete_ordinateur']['primary']['primary_right']);
	}

	public function testZoneExplicitementVideRetireLesElementsParDefaut(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['primary' => ['primary_right' => []]]]);
		$this->assertSame([], $c['entete_ordinateur']['primary']['primary_right']);
		$this->assertSame(['logo'], $c['entete_ordinateur']['primary']['primary_left']);
	}

	public function testReglagesDeRangeeHeritentCleParCle(): void {
		$c = astra_hfb_normaliser(['rangees' => ['entete_primary' => ['hauteur' => 100]]]);
		$this->assertSame(100, $c['rangees']['entete_primary']['hauteur']);
		$this->assertSame(1, $c['rangees']['entete_primary']['bordure']);
		$this->assertSame(50, $c['rangees']['entete_above']['hauteur']);
	}

	public function testReglagesDElementHeritentCleParCle(): void {
		$c = astra_hfb_normaliser(['elements' => ['entete' => ['menu-1' => ['id' => 3]]]]);
		$this->assertSame(['source' => 'racine', 'id' => 3], $c['elements']['entete']['menu-1']);
	}

	public function testMenuMobileInvalideRevientAuDeroulant(): void {
		$this->assertSame('off-canvas', astra_hfb_normaliser(['menu_mobile' => 'off-canvas'])['menu_mobile']);
		$this->assertSame('dropdown', astra_hfb_normaliser(['menu_mobile' => 'plein-ecran'])['menu_mobile']);
	}

	// Registre

	public function testElementsInconnusOuHorsContexteRetires(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['primary' => [
			'primary_right' => ['menu-99', 'unknown', 'copyright', 'widget-4', 'mobile-trigger', '../../config/connect', 'menu-1'],
		]]]);
		$this->assertSame(['menu-1'], $c['entete_ordinateur']['primary']['primary_right']);
	}

	public function testDoublonDansUnMemeBuilderRetire(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['primary' => [
			'primary_left' => ['menu-1', 'logo'],
			'primary_right' => ['menu-1', 'search'],
		]]]);
		$this->assertSame(['menu-1', 'logo'], $c['entete_ordinateur']['primary']['primary_left']);
		$this->assertSame(['search'], $c['entete_ordinateur']['primary']['primary_right']);
	}

	public function testMemeIdentifiantPermisDansDeuxBuildersDifferents(): void {
		$c = astra_hfb_normaliser([
			'entete_ordinateur' => ['above' => ['above_left' => ['html-1']]],
			'pied' => ['below' => ['below_1' => ['html-1', 'copyright']]],
		]);
		$this->assertSame(['html-1'], $c['entete_ordinateur']['above']['above_left']);
		$this->assertSame(['html-1', 'copyright'], $c['pied']['below']['below_1']);
	}

	public function testElementDecritSonSqueletteEtSonGroupe(): void {
		$this->assertSame(['id' => 'menu-2', 'fond' => 'inclure/hfb/menu', 'numero' => '2', 'groupe' => 'entete'], astra_hfb_element('menu-2', 'entete'));
		$this->assertSame(['id' => 'social-icons-1', 'fond' => 'inclure/hfb/social-icons', 'numero' => '1', 'groupe' => 'pied'], astra_hfb_element('social-icons-1', 'pied'));
		$this->assertSame(['id' => 'mobile-trigger', 'fond' => 'inclure/hfb/mobile-trigger', 'numero' => '', 'groupe' => 'entete'], astra_hfb_element('mobile-trigger', 'entete'));
	}

	// En-tête

	public function testRangeeAvecSeulementUnCentreNAPasDeCotes(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['above' => ['above_center' => ['html-1']]]]);
		$rangees = astra_hfb_calculer_entete($c['entete_ordinateur']);
		$this->assertSame(['above', 'primary'], array_column($rangees, 'rangee'));
		$this->assertFalse($rangees[0]['cotes']);
		$this->assertTrue($rangees[0]['centre']);
	}

	public function testZoneCentreGaucheDonneDesCotes(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['below' => ['below_left_center' => ['html-2']]]]);
		$rangees = astra_hfb_calculer_entete($c['entete_ordinateur']);
		$this->assertTrue($rangees[1]['cotes']);
		$this->assertSame(['html-2'], self::ids($rangees[1]['zones']['left_center']));
	}

	// En-tête mobile et panneau

	public function testEnTeteMobileParDefaut(): void {
		$c = astra_hfb_normaliser([]);
		$rangees = astra_hfb_calculer_entete($c['entete_mobile']);
		$this->assertSame(['primary'], array_column($rangees, 'rangee'));
		$this->assertSame(['mobile-trigger'], self::ids($rangees[0]['zones']['right']));
		$this->assertSame([], $rangees[0]['zones']['left_center']);
		$this->assertSame(['mobile-menu'], self::ids(astra_hfb_calculer_panneau($c['entete_mobile'])));
	}

	public function testPanneauSansBoutonDeMenuNonAffiche(): void {
		$c = astra_hfb_normaliser(['entete_mobile' => ['primary' => ['primary_right' => []]]]);
		$this->assertSame([], astra_hfb_calculer_panneau($c['entete_mobile']));
	}

	public function testDoublonEntreRangeesMobilesEtPanneauRetire(): void {
		$c = astra_hfb_normaliser(['entete_mobile' => ['popup' => ['popup_content' => ['logo', 'mobile-menu', 'search', 'mobile-trigger']]]]);
		$this->assertSame(['mobile-menu', 'search'], $c['entete_mobile']['popup']['popup_content']);
	}

	public function testMobileTriggerInterditEnOrdinateurMaisPermisEnMobile(): void {
		$c = astra_hfb_normaliser([
			'entete_ordinateur' => ['primary' => ['primary_right' => ['mobile-trigger']]],
			'entete_mobile' => ['above' => ['above_center' => ['mobile-menu']]],
		]);
		$this->assertSame([], $c['entete_ordinateur']['primary']['primary_right']);
		$this->assertSame(['mobile-menu'], $c['entete_mobile']['above']['above_center']);
	}

	// Pied de page

	public function testPiedParDefautReproduitLeRenduActuel(): void {
		$c = astra_hfb_normaliser([]);
		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
		$this->assertSame(['below'], array_column($rangees, 'rangee'));
		$this->assertSame(
			'site-below-footer-wrap ast-builder-grid-row-container site-footer-focus-item ast-builder-grid-row-full ast-builder-grid-row-tablet-full ast-builder-grid-row-mobile-full ast-footer-row-stack ast-footer-row-tablet-stack ast-footer-row-mobile-stack',
			$rangees[0]['classes']
		);
		$this->assertSame([1], array_keys($rangees[0]['colonnes']));
		$this->assertSame(['copyright'], self::ids($rangees[0]['colonnes'][1]));
		$this->assertSame('pied', $rangees[0]['colonnes'][1][0]['groupe']);
	}

	public function testPiedQuatreColonnes(): void {
		$c = astra_hfb_normaliser([
			'pied' => ['primary' => ['primary_1' => ['widget-1'], 'primary_4' => ['widget-4']]],
			'rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => '4-equal']],
		]);
		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
		$this->assertSame(['primary', 'below'], array_column($rangees, 'rangee'));
		$this->assertStringContainsString('ast-builder-grid-row-4-equal ast-builder-grid-row-tablet-4-equal ast-builder-grid-row-mobile-full', $rangees[0]['classes']);
		$this->assertSame([1, 2, 3, 4], array_keys($rangees[0]['colonnes']));
		$this->assertSame([], $rangees[0]['colonnes'][2]);
	}

	public function testElementsAuDelaDuNombreDeColonnesRetiresEtRangeeMasquee(): void {
		$c = astra_hfb_normaliser([
			'pied' => ['above' => ['above_3' => ['html-1']]],
			'rangees' => ['pied_above' => ['colonnes' => 2]],
		]);
		$this->assertSame([], $c['pied']['above']['above_3']);
		$this->assertSame(['below'], array_column(astra_hfb_calculer_pied($c['pied'], $c['rangees']), 'rangee'));
	}

	public function testSixiemeColonneRendue(): void {
		$c = astra_hfb_normaliser([
			'pied' => ['primary' => ['primary_6' => ['widget-2']]],
			'rangees' => ['pied_primary' => ['colonnes' => 6, 'repartition' => '6-equal']],
		]);
		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
		$this->assertSame('primary', $rangees[0]['rangee']);
		$this->assertSame(['widget-2'], self::ids($rangees[0]['colonnes'][6]));
	}

	public function testRepartitionInvalideRemplaceeParFull(): void {
		$c = astra_hfb_normaliser(['rangees' => ['pied_below' => ['repartition' => '" onload="x']]]);
		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
		$this->assertStringContainsString('ast-builder-grid-row-full ', $rangees[0]['classes']);
	}
}
```

Run : `vendor/bin/phpunit --testsuite unit` — Expected : FAIL (« Failed opening required … inc/astra_hfb.php » ou « Call to undefined function astra_hfb_normaliser() »).

- [ ] **Step 3: Écrire l'implémentation**

`inc/astra_hfb.php` :

```php
<?php
/**
 * Astra pour SPIP — builder d'en-tête et de pied de page.
 *
 * Fonctions pures (sans dépendance à SPIP, PHP 7.4) : valeurs par défaut, registre des
 * éléments, normalisation de la configuration et calcul des rangées à afficher, selon la
 * logique d'Astra_Builder_Helper (Astra 4.11.0) et des gabarits
 * template-parts/header/builder/header-row.php et template-parts/footer/builder/footer-row.php.
 */

const ASTRA_HFB_RANGEES = ['above', 'primary', 'below'];
const ASTRA_HFB_ZONES = ['left', 'left_center', 'center', 'right_center', 'right'];
const ASTRA_HFB_ZONES_MOBILE = ['left', 'center', 'right'];
const ASTRA_HFB_COLONNES_MAX = 6;

function astra_hfb_defauts(): array {
	$entete = function (array $zones, array $remplies = []) {
		$rangees = [];
		foreach (ASTRA_HFB_RANGEES as $r) {
			foreach ($zones as $z) {
				$rangees[$r][$r . '_' . $z] = $remplies[$r . '_' . $z] ?? [];
			}
		}
		return $rangees;
	};
	$pied = [];
	foreach (ASTRA_HFB_RANGEES as $r) {
		for ($i = 1; $i <= ASTRA_HFB_COLONNES_MAX; $i++) {
			$pied[$r][$r . '_' . $i] = [];
		}
	}
	$pied['below']['below_1'] = ['copyright'];
	return [
		'entete_ordinateur' => $entete(ASTRA_HFB_ZONES, ['primary_left' => ['logo'], 'primary_right' => ['menu-1']]),
		'entete_mobile' => ['popup' => ['popup_content' => ['mobile-menu']]]
			+ $entete(ASTRA_HFB_ZONES_MOBILE, ['primary_left' => ['logo'], 'primary_right' => ['mobile-trigger']]),
		'pied' => $pied,
		'rangees' => [
			'entete_above' => ['hauteur' => 50, 'fond' => '', 'bordure' => 1],
			'entete_primary' => ['hauteur' => 80, 'fond' => '', 'bordure' => 1],
			'entete_below' => ['hauteur' => 60, 'fond' => '', 'bordure' => 1],
			'pied_above' => ['colonnes' => 2, 'repartition' => '2-equal', 'hauteur' => 60, 'fond' => ''],
			'pied_primary' => ['colonnes' => 3, 'repartition' => '3-equal', 'hauteur' => '', 'fond' => ''],
			'pied_below' => ['colonnes' => 1, 'repartition' => 'full', 'hauteur' => 80, 'fond' => ''],
		],
		'menu_mobile' => 'dropdown',
		'elements' => [
			'entete' => [
				'menu-1' => ['source' => 'racine', 'id' => 0],
				'menu-2' => ['source' => 'racine', 'id' => 0],
			],
			'pied' => [
				'menu' => ['source' => 'racine', 'id' => 0],
			],
		],
	];
}

/**
 * Éléments autorisés et contextes où ils peuvent être placés
 * (ordinateur, mobile = rangées mobiles, panneau = panneau mobile, pied).
 */
function astra_hfb_registre(): array {
	$entete = ['ordinateur', 'mobile', 'panneau'];
	$partout = ['ordinateur', 'mobile', 'panneau', 'pied'];
	return [
		'logo' => $entete,
		'menu-1' => $entete,
		'menu-2' => $entete,
		'mobile-menu' => ['mobile', 'panneau'],
		'mobile-trigger' => ['mobile'],
		'html-1' => $partout,
		'html-2' => $partout,
		'button-1' => $entete,
		'search' => $entete,
		'social-icons-1' => $partout,
		'widget-1' => $partout,
		'widget-2' => $partout,
		'widget-3' => ['pied'],
		'widget-4' => ['pied'],
		'account' => $entete,
		'copyright' => ['pied'],
		'menu' => ['pied'],
	];
}

/**
 * Configuration complète : défauts appliqués selon les règles de fusion de la spécification,
 * puis placements filtrés par le registre.
 */
function astra_hfb_normaliser($config): array {
	$defauts = astra_hfb_defauts();
	$config = is_array($config) ? $config : [];
	$n = $defauts;

	foreach (['entete_ordinateur', 'entete_mobile', 'pied'] as $builder) {
		$source = isset($config[$builder]) && is_array($config[$builder]) ? $config[$builder] : [];
		foreach ($defauts[$builder] as $rangee => $zones) {
			$sr = isset($source[$rangee]) && is_array($source[$rangee]) ? $source[$rangee] : [];
			foreach ($zones as $zone => $defaut) {
				if (array_key_exists($zone, $sr)) {
					$n[$builder][$rangee][$zone] = is_array($sr[$zone]) ? array_values($sr[$zone]) : [];
				}
			}
		}
	}

	$source = isset($config['rangees']) && is_array($config['rangees']) ? $config['rangees'] : [];
	foreach ($defauts['rangees'] as $cle => $reglages) {
		if (isset($source[$cle]) && is_array($source[$cle])) {
			$n['rangees'][$cle] = array_replace($reglages, array_intersect_key($source[$cle], $reglages));
		}
	}

	$n['menu_mobile'] = in_array($config['menu_mobile'] ?? '', ['dropdown', 'off-canvas'], true) ? $config['menu_mobile'] : 'dropdown';

	$registre = astra_hfb_registre();
	foreach (['entete', 'pied'] as $groupe) {
		$source = isset($config['elements'][$groupe]) && is_array($config['elements'][$groupe]) ? $config['elements'][$groupe] : [];
		foreach ($source as $id => $reglages) {
			if (isset($registre[$id]) && is_array($reglages)) {
				$n['elements'][$groupe][$id] = array_replace($defauts['elements'][$groupe][$id] ?? [], $reglages);
			}
		}
	}

	return astra_hfb_filtrer($n);
}

/**
 * Retire les éléments inconnus, hors contexte, en double dans un même builder,
 * ou placés au-delà du nombre de colonnes d'une rangée du pied.
 */
function astra_hfb_filtrer(array $n): array {
	$registre = astra_hfb_registre();
	$vus = [];
	$garder = function ($ids, string $contexte, string $builder) use ($registre, &$vus) {
		$gardes = [];
		foreach ($ids as $id) {
			if (!is_string($id) || !isset($registre[$id]) || !in_array($contexte, $registre[$id], true) || isset($vus[$builder][$id])) {
				continue;
			}
			$vus[$builder][$id] = true;
			$gardes[] = $id;
		}
		return $gardes;
	};

	foreach (ASTRA_HFB_RANGEES as $r) {
		foreach ($n['entete_ordinateur'][$r] as $zone => $ids) {
			$n['entete_ordinateur'][$r][$zone] = $garder($ids, 'ordinateur', 'ordinateur');
		}
	}
	foreach (ASTRA_HFB_RANGEES as $r) {
		foreach ($n['entete_mobile'][$r] as $zone => $ids) {
			$n['entete_mobile'][$r][$zone] = $garder($ids, 'mobile', 'mobile');
		}
	}
	$n['entete_mobile']['popup']['popup_content'] = $garder($n['entete_mobile']['popup']['popup_content'], 'panneau', 'mobile');

	foreach (ASTRA_HFB_RANGEES as $r) {
		$nombre = astra_hfb_nombre_colonnes($n['rangees']['pied_' . $r]);
		for ($i = 1; $i <= ASTRA_HFB_COLONNES_MAX; $i++) {
			$zone = $r . '_' . $i;
			$n['pied'][$r][$zone] = $i <= $nombre ? $garder($n['pied'][$r][$zone], 'pied', 'pied') : [];
		}
	}
	return $n;
}

function astra_hfb_nombre_colonnes(array $reglages): int {
	return max(1, min(ASTRA_HFB_COLONNES_MAX, (int) ($reglages['colonnes'] ?? 1)));
}

/**
 * Décrit un élément : squelette à inclure, numéro et groupe de réglages
 * (menu-2 → inclure/hfb/menu, 2).
 */
function astra_hfb_element(string $id, string $groupe): array {
	if (preg_match('/^(.+?)-(\d+)$/', $id, $m)) {
		return ['id' => $id, 'fond' => 'inclure/hfb/' . $m[1], 'numero' => $m[2], 'groupe' => $groupe];
	}
	return ['id' => $id, 'fond' => 'inclure/hfb/' . $id, 'numero' => '', 'groupe' => $groupe];
}

function astra_hfb_decrire(array $ids, string $groupe): array {
	return array_map(function ($id) use ($groupe) {
		return astra_hfb_element($id, $groupe);
	}, $ids);
}

/**
 * Rangées non vides d'un en-tête normalisé (ordinateur ou mobile).
 */
function astra_hfb_calculer_entete(array $items): array {
	$resultat = [];
	foreach (ASTRA_HFB_RANGEES as $r) {
		$zones = [];
		foreach (ASTRA_HFB_ZONES as $z) {
			$zones[$z] = astra_hfb_decrire($items[$r][$r . '_' . $z] ?? [], 'entete');
		}
		$cotes = (bool) ($zones['left'] || $zones['left_center'] || $zones['right_center'] || $zones['right']);
		$centre = (bool) $zones['center'];
		if ($cotes || $centre) {
			$resultat[] = ['rangee' => $r, 'cotes' => $cotes, 'centre' => $centre, 'zones' => $zones];
		}
	}
	return $resultat;
}

/**
 * Éléments du panneau mobile, s'il doit être affiché : non vide et ouvert par un mobile-trigger.
 */
function astra_hfb_calculer_panneau(array $mobile): array {
	$bouton = false;
	foreach (ASTRA_HFB_RANGEES as $r) {
		foreach ($mobile[$r] ?? [] as $ids) {
			$bouton = $bouton || in_array('mobile-trigger', $ids, true);
		}
	}
	return $bouton ? astra_hfb_decrire($mobile['popup']['popup_content'] ?? [], 'entete') : [];
}

/**
 * Rangées non vides d'un pied de page normalisé, avec leurs classes et leurs colonnes.
 */
function astra_hfb_calculer_pied(array $items, array $rangees): array {
	$resultat = [];
	foreach (ASTRA_HFB_RANGEES as $r) {
		$reglages = $rangees['pied_' . $r] ?? [];
		$nombre = astra_hfb_nombre_colonnes($reglages);
		$colonnes = [];
		$occupee = false;
		for ($i = 1; $i <= $nombre; $i++) {
			$colonnes[$i] = astra_hfb_decrire($items[$r][$r . '_' . $i] ?? [], 'pied');
			$occupee = $occupee || $colonnes[$i];
		}
		if (!$occupee) {
			continue;
		}
		$repartition = preg_match('/^(full|[2-6]-[a-z]+)$/', (string) ($reglages['repartition'] ?? '')) ? $reglages['repartition'] : 'full';
		$resultat[] = [
			'rangee' => $r,
			'classes' => implode(' ', [
				'site-' . $r . '-footer-wrap',
				'ast-builder-grid-row-container',
				'site-footer-focus-item',
				'ast-builder-grid-row-' . $repartition,
				'ast-builder-grid-row-tablet-' . $repartition,
				'ast-builder-grid-row-mobile-full',
				'ast-footer-row-stack',
				'ast-footer-row-tablet-stack',
				'ast-footer-row-mobile-stack',
			]),
			'colonnes' => $colonnes,
		];
	}
	return $resultat;
}
```

- [ ] **Step 4: Lancer les tests**

Run : `vendor/bin/phpunit --testsuite unit` — Expected : `OK (22 tests, …)`.

Vérifier aussi que le fichier fonctionne en PHP 7.4 (binaire `php7.4` installé sur la machine de développement) :

```bash
php7.4 -l inc/astra_hfb.php
php7.4 -r 'require "inc/astra_hfb.php"; $c = astra_hfb_normaliser([]); echo implode(",", array_column(astra_hfb_calculer_entete($c["entete_ordinateur"]), "rangee")), "\n";'
```

Expected : « No syntax errors detected », puis `primary`.

- [ ] **Step 5: Brancher la configuration SPIP**

Avant toute modification, enregistrer le rendu actuel :

```bash
S=/tmp/claude-1000/-src-astra-spip/dc097d1c-c7f6-455a-b514-0ce253b369df/scratchpad
curl -s "http://127.0.0.1:8402/?var_mode=recalcul" -o $S/avant-hfb.html
```

Puis, dans `astra_fonctions.php`, après la définition de `_ASTRA_VERSION_THEME`, ajouter :

```php
include_once __DIR__ . '/inc/astra_hfb.php';

/**
 * Configuration du builder d'en-tête et de pied de page, normalisée.
 */
function astra_hfb_config(): array {
	include_spip('inc/config');
	return astra_hfb_normaliser(lire_config('astra/hfb', []));
}

/**
 * Rangées à afficher pour un contexte du builder : entete_ordinateur, entete_mobile ou pied.
 */
function astra_hfb_rangees(string $contexte): array {
	$config = astra_hfb_config();
	if ($contexte === 'pied') {
		return astra_hfb_calculer_pied($config['pied'], $config['rangees']);
	}
	if ($contexte === 'entete_ordinateur' || $contexte === 'entete_mobile') {
		return astra_hfb_calculer_entete($config[$contexte]);
	}
	return [];
}

/**
 * Éléments du panneau mobile (vide s'il ne doit pas être affiché).
 */
function astra_hfb_panneau(): array {
	return astra_hfb_calculer_panneau(astra_hfb_config()['entete_mobile']);
}
```

- [ ] **Step 6: Vérifier dans SPIP 4.4 que le rendu par défaut est inchangé**

Run :

```bash
curl -s "http://127.0.0.1:8402/?var_mode=recalcul" -o $S/apres-hfb.html
diff $S/avant-hfb.html $S/apres-hfb.html && echo IDENTIQUE
(cd $S/spip44 && $SPIP_CLI php:eval 'include_once _DIR_PLUGINS . "astra/astra_fonctions.php"; echo implode(",", array_column(astra_hfb_rangees("entete_ordinateur"), "rangee")), "\n";')
tail -5 $S/spip44.log
```

Expected : `IDENTIQUE` ; la commande spip-cli affiche `primary` ; aucune erreur PHP nouvelle dans le journal.

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock phpunit.xml inc/astra_hfb.php tests/unit astra_fonctions.php
git commit -m "Ajouter le registre, la normalisation et le calcul des rangées du builder

Les placements reprennent la structure des options d'Astra. Une zone
absente hérite du défaut, une zone vide retire ses éléments ; un registre
écarte les éléments inconnus, hors contexte ou en double. Les rangées,
colonnes et panneau mobile sont calculés comme dans Astra_Builder_Helper.
Le rendu par défaut est inchangé."
```

---

### Task 3: Environnement de tests d'intégration SPIP 4.4

**Files:**
- Create: `scripts/install-spip-test.sh`, `tests/bootstrap_integration.php`, `tests/integration/PluginActifTest.php`, `tests/integration/HfbConfigurationTest.php`
- Modify: `composer.json` (scripts), `README.md` (section Tests)

**Interfaces:**
- Consumes: `astra_hfb_rangees()`, `astra_hfb_panneau()`, `astra_hfb_config()` (tâche 2) ; `$SPIP_CLI`.
- Produces: un SPIP 4.4 de test dans `vendor/spip/spip` (SQLite, plugin `astra` lié et actif, rubriques de démonstration), la commande `composer tests-integration`, et un modèle de test d'intégration que les tâches du second plan reprennent pour leurs squelettes (`recuperer_fond` + assertions sur le HTML).

- [ ] **Step 1: Écrire le script d'installation**

`scripts/install-spip-test.sh` :

```sh
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

# Rubriques de démonstration (menus) : idempotent.
$SPIP_CLI php:eval '
include_spip("action/editer_objet");
if (!sql_countsel("spip_rubriques")) {
	foreach (["Jardinage", "Actualités"] as $titre) {
		$id = objet_inserer("rubrique", 0);
		objet_modifier("rubrique", $id, ["titre" => $titre]);
		$a = objet_inserer("article", $id);
		objet_modifier("article", $a, ["titre" => "Article de " . $titre, "texte" => "Texte."]);
		objet_instituer("article", $a, ["statut" => "publie"]);
	}
	$parent = sql_getfetsel("id_rubrique", "spip_rubriques", "titre=" . sql_quote("Jardinage"));
	$id = objet_inserer("rubrique", $parent);
	objet_modifier("rubrique", $id, ["titre" => "Potager"]);
	$a = objet_inserer("article", $id);
	objet_modifier("article", $a, ["titre" => "Article du potager", "texte" => "Texte."]);
	objet_instituer("article", $a, ["statut" => "publie"]);
}
echo sql_countsel("spip_rubriques", "statut=\"publie\""), " rubriques publiées\n";
'
echo "Environnement d'intégration prêt."
```

Puis `chmod +x scripts/install-spip-test.sh`.

- [ ] **Step 2: Ajouter les scripts Composer**

Dans `composer.json`, remplacer le bloc `scripts` par :

```json
	"scripts": {
		"install-spip-test": "sh scripts/install-spip-test.sh",
		"tests-unit": "phpunit --colors --testsuite unit",
		"tests-integration": [
			"test -f vendor/spip/spip/config/connect.php || { echo 'SPIP de test absent : lancez SPIP_CLI=… composer install-spip-test' >&2; exit 1; }",
			"phpunit --colors --testsuite integration --bootstrap tests/bootstrap_integration.php"
		]
	}
```

- [ ] **Step 3: Installer l'environnement**

Run :

```bash
export SPIP_CLI="php /src/spip-cli-custom/bin/spip"
composer install-spip-test
```

Expected : « 3 rubriques publiées » puis « Environnement d'intégration prêt. ». Si `core:telecharger -R 4.4` échoue faute de réseau, copier `/src/jetables/v4.4.21` vers `vendor/spip/spip` (sans `config/connect.php` ni `tmp/`, `local/`, `IMG/`) et relancer.

- [ ] **Step 4: Écrire le bootstrap et les tests d'intégration (échec attendu sur le second)**

`tests/bootstrap_integration.php` :

```php
<?php
// Charge le SPIP 4.4 de test installé par scripts/install-spip-test.sh.
$spipRoot = dirname(__DIR__) . '/vendor/spip/spip';
chdir($spipRoot);
// SPIP 4.4 démarre son noyau (SpipLeague) par l'autoloader de Composer.
require_once $spipRoot . '/vendor/autoload.php';
require_once $spipRoot . '/ecrire/inc_version.php';
include_spip('inc/plugin');
actualise_plugins_actifs();
include_spip('inc/config');
include_spip('public/assembler');
include_once _DIR_PLUGINS . 'astra/astra_fonctions.php';
```

`tests/integration/PluginActifTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

final class PluginActifTest extends TestCase {

	public function testPluginAstraActif(): void {
		$this->assertTrue(test_plugin_actif('astra'));
	}

	public function testEnTeteParDefautInchange(): void {
		effacer_config('astra/hfb');
		$html = recuperer_fond('inclure/header', ['type_page' => 'sommaire']);
		$this->assertStringContainsString('site-primary-header-wrap', $html);
		$this->assertStringContainsString('ast-builder-menu-1', $html);
		$this->assertStringNotContainsString('ast-above-header-wrap', $html);
	}
}
```

`tests/integration/HfbConfigurationTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

final class HfbConfigurationTest extends TestCase {

	protected function tearDown(): void {
		effacer_config('astra/hfb');
	}

	public function testConfigurationEnregistreeEstRelueEtNormalisee(): void {
		ecrire_config('astra/hfb', [
			'entete_ordinateur' => ['above' => ['above_center' => ['html-1', 'copyright']]],
			'elements' => ['entete' => ['html-1' => ['texte' => 'Bonjour']]],
		]);
		$config = astra_hfb_config();
		$this->assertSame(['html-1'], $config['entete_ordinateur']['above']['above_center']);
		$this->assertSame(['texte' => 'Bonjour'], $config['elements']['entete']['html-1']);
		$this->assertSame(['above', 'primary'], array_column(astra_hfb_rangees('entete_ordinateur'), 'rangee'));
	}

	public function testChangementDeConfigurationPrisEnCompteDansLeMemeProcessus(): void {
		$this->assertSame(['primary'], array_column(astra_hfb_rangees('entete_ordinateur'), 'rangee'));
		ecrire_config('astra/hfb', ['entete_ordinateur' => ['below' => ['below_center' => ['search']]]]);
		$this->assertSame(['primary', 'below'], array_column(astra_hfb_rangees('entete_ordinateur'), 'rangee'));
	}

	public function testListeVideEnregistreeRetireLeMenu(): void {
		ecrire_config('astra/hfb', ['entete_ordinateur' => ['primary' => ['primary_right' => []]]]);
		$rangees = astra_hfb_rangees('entete_ordinateur');
		$this->assertSame([], $rangees[0]['zones']['right']);
	}

	public function testPanneauMobileSuitLaConfiguration(): void {
		$this->assertSame(['mobile-menu'], array_column(astra_hfb_panneau(), 'id'));
		ecrire_config('astra/hfb', ['entete_mobile' => ['popup' => ['popup_content' => ['search', 'mobile-menu']]]]);
		$this->assertSame(['search', 'mobile-menu'], array_column(astra_hfb_panneau(), 'id'));
	}
}
```

Run : `composer tests-integration`.

Expected : `PluginActifTest` passe. `testListeVideEnregistreeRetireLeMenu` doit **échouer** si SPIP ne conserve pas une liste vide dans la méta (sérialisation d'un tableau vide) : dans ce cas, le constater, puis passer au step 5. S'il passe, le step 5 se limite à documenter le constat.

- [ ] **Step 5: Garantir la conservation des listes vides**

Si le test précédent échoue (la clé de zone disparaît à l'enregistrement), la normalisation n'est pas en cause : c'est le stockage. Vérifier :

```bash
(cd vendor/spip/spip && $SPIP_CLI php:eval 'include_spip("inc/config"); ecrire_config("astra/hfb", ["entete_ordinateur" => ["primary" => ["primary_right" => []]]]); var_export(lire_config("astra/hfb"));')
```

Si `primary_right` est absent de la sortie, le formulaire (tâche 7) devra enregistrer une zone vide sous la forme d'une chaîne vide `''`, que `astra_hfb_normaliser()` traite déjà comme une liste vide explicite (`is_array($sr[$zone]) ? … : []`). Ajouter alors à `tests/unit/HfbTest.php` :

```php
	public function testChaineVideEquivautAUneListeVide(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['primary' => ['primary_right' => '']]]);
		$this->assertSame([], $c['entete_ordinateur']['primary']['primary_right']);
	}
```

et modifier le test d'intégration pour enregistrer `'primary_right' => ''`. Noter la règle dans la spécification (section « Règles de fusion »). Relancer `composer tests-unit` et `composer tests-integration` : Expected OK.

- [ ] **Step 6: Documenter les tests**

Ajouter au `README.md`, avant « ## Licence » :

```markdown
## Tests

Prérequis : PHP 8.2+ et Composer pour l'outillage (le plugin lui-même fonctionne en PHP 7.4+),
spip-cli avec le correctif de la MR !91 pour l'environnement d'intégration.

    composer install
    composer tests-unit                                   # sans SPIP
    SPIP_CLI="php /chemin/spip-cli/bin/spip" composer install-spip-test
    composer tests-integration                            # SPIP 4.4 de test, SQLite

Les références WordPress du builder sont décrites dans `tests/references/README.md`.

Le builder d'en-tête et de pied de page est vérifié sur SPIP 4.4 uniquement : SPIP 4.2 et 5.0,
bien que déclarés compatibles, ne sont pas vérifiés pour cette fonction.
```

- [ ] **Step 7: Commit**

```bash
git add scripts tests/bootstrap_integration.php tests/integration tests/unit composer.json README.md docs/superpowers/specs
git commit -m "Ajouter l'environnement de tests d'intégration SPIP 4.4

Un SPIP 4.4 de test est installé par spip-cli dans vendor/spip/spip,
avec le plugin actif et des rubriques de démonstration. Premiers tests :
plugin actif, en-tête par défaut inchangé, configuration relue et
normalisée."
```

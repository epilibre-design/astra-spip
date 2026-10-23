# En-tête transparent — plan de mise en œuvre

> **Pour les agents :** sous-compétence requise : superpowers:subagent-driven-development (recommandé) ou superpowers:executing-plans, tâche par tâche. Les étapes sont des cases à cocher (`- [ ]`).

**But :** reprendre l'en-tête transparent d'Astra 4.11.0 (`inc/addons/transparent-header/`) : l'en-tête se superpose au début du contenu, avec ses propres couleurs, sur tout le site ou page par page, comme les six pages du modèle Garden Maintenance.

**Architecture :** un module pur, `inc/astra_entete_transparent.php`, porte la règle d'activation (`is_transparent_header()` d'Astra transposée aux pages SPIP) et le CSS du module d'Astra (sélecteurs copiés, media queries et conditions d'émission d'origine). Les options globales entrent au registre des styles (groupe `entete-transparent`, méta `astra/styles`) ; le réglage par page s'ajoute aux réglages de page (`astra/pages/<id>`, clé `transparent`). Les gabarits transmettent leur contexte à `inclure/head` ; le body reçoit `ast-theme-transparent-header` et le head un bloc `astra-transparent-inline-css`, seulement quand l'en-tête est transparent. L'équivalence est prouvée par des attendus capturés sur WordPress (quatre variantes) et par les références R16 et R17.

**Pile technique :** PHP 7.4+ (plugin), SPIP 4.4, PHPUnit 11.5, WordPress de test avec Astra 4.11.0 et WP-CLI, Playwright (Chromium), Python 3 (`regles.py`).

**Spec :** `docs/superpowers/specs/2026-10-23-entete-transparent-design.md` (statut « à relire », questions ouvertes 1 et 2 tranchées ci-dessous au plus étroit, à confirmer). Écarts listés plus bas.

## Contraintes globales

- Rien ne change sans réglage : sans option `transparent-header-*` ni réglage de page, aucune classe ni aucun bloc CSS ne s'ajoute ; R0–R11 et P1–P3 restent identiques (HTML, captures, styles calculés).
- Avec un réglage, l'effet est celui d'Astra 4.11.0 : mêmes classes, mêmes sélecteurs, mêmes media queries, mêmes conditions d'émission ; fichier d'Astra cité en commentaire.
- Aucune chaîne libre en CSS : toute valeur passe par `astra_styles_normaliser()`.
- Valeurs d'Astra conservées sous leurs noms ; le `site.json` de Garden se reprend tel quel.
- Le plugin reste compatible PHP 7.4 (vérifié : `php7.4 -l` et sortie identique à PHP 8.4).
- Langue du code, des commentaires et des messages : français, comme le dépôt.
- Dépôt partagé avec Codex : `git add` fichier par fichier ; ne jamais indexer `.gitignore`, `.ignore`, `tests/references/__pycache__/*.pyc`, `docs/inventaires/2026-10-23-preparation-publication.md`.
- Messages de commit sans trailer (ni `Co-Authored-By`, ni `Claude-Session`).
- Tests d'intégration : `vendor/spip/spip/plugins/astra` doit pointer vers l'arbre de travail testé (dans un worktree : `cp -a <dépôt principal>/vendor vendor && ln -sfn "$PWD" vendor/spip/spip/plugins/astra`). Tests d'intégration et comparaisons écrivent dans la même base : ne pas les lancer en même temps.
- Commandes : `vendor/bin/phpunit --testsuite unit [--filter …]` ; `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php [--filter …]` ; comparaisons comme dans `README.md` (SPIP de test servi avec `PHP_CLI_SERVER_WORKERS=4`, ici sur le port 8414 ; WordPress de test sur 8401).
- Les correctifs s'appliquent depuis la racine du dépôt avec `git apply` (enregistrer le bloc dans un fichier, puis `git apply chemin/du/fichier.patch`), dans l'ordre des tâches. Ce plan est indépendant de celui des compléments du builder (`2026-10-23-complements-builder.md`) : il s'applique sur `origin/main` ; appliqué après lui, seules les lignes voisines de `lang/`, `inc/astra_styles.php`, `inc/astra_styles_saisie.php`, `tests/comparaison/configurations.php` et `tests/references/README.md` sont à fusionner.

## Choix et écarts à la spec (à valider)

1. **Question 1 : réglage par page limité aux pages uniques**, comme Garden. Les articles suivent le réglage global et ses exclusions.
2. **Question 2 : logo propre à l'en-tête transparent non repris.** Les règles de largeur de ce logo, qu'Astra émet toujours (défauts 150, 120 et 100 px), sont émises à l'identique ; elles ne visent aucun élément.
3. **Références renumérotées R16 et R17** (la spec dit R15 et R16 ; R15 est pris par Google Reviews dans le plan des compléments du builder). R17 montre l'exclusion d'une archive (catégorie Jardinage) ; le cas « réglage global sur une page » est couvert par les tests d'intégration.
4. **Valeurs tablette et mobile appliquées** pour ce module : la restriction par appareil d'Astra repose sur ses media queries, et Astra lit les couleurs de chaque appareil (avec repli de la tablette sur l'ordinateur, puis du mobile sur la tablette pour les fonds). La spec des styles n'applique que l'ordinateur ; ici, suivre Astra n'a pas de coût et la variante `couleurs` le prouve.
5. **Drapeau `transparent-header-default-border`** ajouté au registre (non listé par la spec) : il fixe le défaut du séparateur (0 px sur une installation récente, aucun si le drapeau est faux, cas de Garden ; `astra_get_transparent_header_default_value()`).
6. **Neutralisation** de la marge de 60 px de `#primary` au-delà de 1200 px (`css/astra-dynamic.css`), qu'Astra n'émet pas sur une page à en-tête transparent (`inc/class-astra-dynamic-css.php`, `gtn_margin_top`) : règle `.ast-theme-transparent-header.ast-plain-container.ast-no-sidebar #primary{margin-top:0;margin-bottom:0;}`, déclarée dans `attendus/neutralisations.css`.
7. **Couleurs propres aux éléments** de l'en-tête transparent (texte libre, recherche, icônes sociales, bouton, séparateur vertical, compte, couleurs du bouton du menu mobile) non reprises : absentes de la liste de la spec et vides dans Garden. Seule la règle que cette partie d'Astra émet sans réglage (fond transparent du bouton du menu mobile) est reprise. `header-sections-dynamic.css.php` ne concerne que l'en-tête sans builder : non repris.
8. **Signature** : la règle est `astra_entete_transparent_actif(array $s, string $contexte, string $reglage_page)` (pure, testée cas par cas) ; `astra_entete_transparent(string $type, $id, $accueil)` dans `astra_fonctions.php` lit la configuration, avec les mêmes paramètres qu'`astra_body_class()`.
9. **`comparer_captures.sh`** compare aussi le contenu d'une référence qui affiche une page (clé `page`), pour vérifier la superposition de l'en-tête et du titre (R16).

## Vérification préalable du plan

Le code de ce plan a été écrit et exécuté dans un worktree jetable de `origin/main` (7e3dde8),
puis découpé en un commit par tâche ; chaque correctif ci-dessous est le diff de ces commits,
appliqué dans l'ordre sur 7e3dde8 (arbre final identique à celui de l'essai) :

- étape 2 des tâches 1 et 2 : tests appliqués sur l'état précédent, échecs relevés tels qu'indiqués ;
- tests après la tâche 3 : unitaires 80 tests, 1299 assertions ; intégration 72 tests,
  245 assertions (avant : 71 et 69) ;
- attendus WordPress : les quatre variantes produisent exactement les règles d'Astra, plus la
  neutralisation déclarée ;
- `capturer.sh` sur R0–R11, R16, R17, P1–P3, puis `comparer_html.php` (défaut, puis R16 et R17) :
  tout identique, classes du `body` de R16 comprises ;
- `comparer_captures.sh` sur ces 17 références : 124 vues sous le seuil (plus grand écart 0,35 %,
  R11, pied sur ordinateur, comme en 1.2.0 ; R16 et R17 : 0,00 %), styles calculés égaux aux trois
  largeurs, contenu de R16 comparé ;
- position mesurée dans Chromium sur R16 : `#masthead` de 80 px (1280) et 200 px (390), contenu et
  titre à 0 px du haut de la page, identiques dans WordPress et SPIP ;
- `php7.4 -l` sur les fichiers PHP modifiés : aucune erreur ; CSS produit sous PHP 7.4 identique
  octet pour octet à celui de PHP 8.4.

## Fichiers

| Fichier | Rôle | Tâche |
|---|---|---|
| `inc/astra_entete_transparent.php` | Règle d'activation, dernière rangée remplie, CSS du module | 1 |
| `inc/astra_styles.php`, `inc/astra_styles_saisie.php` | Options du groupe `entete-transparent`, section du formulaire « Couleurs et typographie » | 1 |
| `tests/references/transparent/` | `appliquer.php`, `capturer.sh`, valeurs et attendus WordPress des quatre variantes, neutralisation | 1 |
| `inc/astra_pages.php`, `formulaires/editer_astra_page.{html,php}` | Réglage `transparent` d'une page | 2 |
| `astra_fonctions.php` | `astra_entete_transparent()`, `astra_entete_transparent_inline()`, classe du body | 2 |
| Gabarits (`article`, `rubrique`, `mot`, `auteur`, `recherche`, `404`, `page_unique`, `inclure/sommaire-articles`), `inclure/head.html`, `inclure/head-vars.html` | Contexte transmis au head, bloc `astra-transparent-inline-css` | 2 |
| `lang/astra_{fr,en}.php` | Libellés | 1, 2 |
| `tests/unit/*`, `tests/integration/*` | Tests | 1, 2 |
| `tests/references/wp/R16-*`, `R17-*`, `commun.php`, `tests/comparaison/{configurations.php,comparer_captures.sh}` | Références visuelles | 3 |
| `README.md`, `CREDITS.md`, `tests/references/README.md` | Documentation, provenance | 3 |

---

### Tâche 1 : règle d'activation et CSS du module

Règle reprise de `Astra_Ext_Transparent_Header_Markup::is_transparent_header()` : réglage global `transparent-header-enable`, exclusions (404, recherche et archives par leurs options séparées, avec repli sur `transparent-header-disable-archive` ; accueil des derniers articles ; pages ; articles), puis le réglage de la page (`active`, `desactive`) qui l'emporte. Contextes SPIP : `page` (page unique, y compris en accueil statique), `article`, `accueil_articles`, `rubrique`, `mot`, `auteur`, `recherche`, `404`.

CSS repris de `classes/dynamic-css/dynamic.css.php` (largeur du logo propre, base de positionnement, couleurs par appareil, restriction par appareil, séparateur sous la dernière rangée remplie) et de la partie « Transparent Header » d'`inc/class-astra-dynamic-css.php` (bouton du menu mobile), plus la neutralisation (écart 6). Points de rupture d'Astra : 921 et 544 px.

**Fichiers :**
- Créer : `inc/astra_entete_transparent.php`, `tests/unit/EnteteTransparentTest.php`, `tests/references/transparent/{appliquer.php,capturer.sh}`, `tests/references/transparent/valeurs/{garden,ordinateur,mobile,couleurs}.json`, `tests/references/transparent/attendus/{garden,ordinateur,mobile,couleurs}{,.retirees}.css`, `tests/references/transparent/attendus/neutralisations.css`
- Modifier : `inc/astra_styles.php`, `inc/astra_styles_saisie.php`, `lang/astra_fr.php`, `lang/astra_en.php`, `tests/integration/StylesFormulaireTest.php`

**Interfaces :**
- Produit : `astra_entete_transparent_actif(array $s, string $contexte, string $reglage_page = 'defaut'): bool` ; `astra_entete_transparent_derniere_rangee(array $hfb, string $appareil): string` ; `astra_entete_transparent_css(array $styles, array $hfb): string` ; constantes `ASTRA_TRANSPARENT_TABLETTE` (921) et `ASTRA_TRANSPARENT_MOBILE` (544).
- Consomme : `astra_styles_normaliser()`, `astra_styles_ecrire()`, `astra_styles_element_place()` (`inc/astra_styles.php`), `astra_hfb_normaliser()` (`inc/astra_hfb.php`), `StylesAttendusTest::regles()` (tests).

Les attendus se capturent sur le WordPress de test : `WP=… tests/references/transparent/capturer.sh` (sortie attendue : `garden : 25 règles, 1 retirées`, `ordinateur : 20 règles, 1 retirées`, `mobile : 22 règles, 1 retirées`, `couleurs : 58 règles, 1 retirées`). Le correctif de l'étape 1 contient leur résultat.

- [ ] **Étape 1 : écrire les tests, l'outillage et les attendus**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/integration/StylesFormulaireTest.php b/tests/integration/StylesFormulaireTest.php
index 637dda8..9c8b652 100644
--- a/tests/integration/StylesFormulaireTest.php
+++ b/tests/integration/StylesFormulaireTest.php
@@ -44,7 +44,7 @@ final class StylesFormulaireTest extends TestCase {
 
 	public function testRenduDesChamps(): void {
 		$html = recuperer_fond('formulaires/configurer_astra_styles', ['editable' => true, 's' => ['button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'em']]]);
-		$this->assertSame(12, substr_count($html, '<fieldset'));
+		$this->assertSame(13, substr_count($html, '<fieldset'));
 		$this->assertStringContainsString('name="s[header-menu1-color-responsive][desktop]"', $html);
 		$this->assertStringContainsString('name="s[button-radius-fields][desktop][top]" value="8"', $html);
 		$this->assertMatchesRegularExpression('~name="s\[button-radius-fields\]\[desktop-unit\]".*?<option value="em" selected="selected">~s', $html);
diff --git a/tests/references/transparent/appliquer.php b/tests/references/transparent/appliquer.php
new file mode 100644
index 0000000..0d84861
--- /dev/null
+++ b/tests/references/transparent/appliquer.php
@@ -0,0 +1,17 @@
+<?php
+/**
+ * Référence de l'en-tête transparent : état P1 (page « Notre jardin », menu de pages), puis les
+ * options de valeurs/<variante>.json ; la page a l'en-tête transparent (méta de page `enabled`),
+ * sauf pour la variante « base ».
+ * Usage : VARIANTE=<variante> wp --path=$WP eval-file tests/references/transparent/appliquer.php
+ */
+require dirname(__DIR__) . '/wp/commun.php';
+ref_etat_initial();
+ref_menu_pages('primary');
+ref_menu_pages('mobile_menu');
+$ids = ref_pages();
+$variante = getenv('VARIANTE') ?: 'base';
+if ($variante !== 'base') {
+	update_post_meta($ids['notre-jardin'], 'theme-transparent-header-meta', 'enabled');
+	ref_reglages(json_decode(file_get_contents(__DIR__ . "/valeurs/$variante.json"), true));
+}
diff --git a/tests/references/transparent/attendus/couleurs.css b/tests/references/transparent/attendus/couleurs.css
new file mode 100644
index 0000000..f51f2ed
--- /dev/null
+++ b/tests/references/transparent/attendus/couleurs.css
@@ -0,0 +1,58 @@
+.ast-theme-transparent-header [data-section="section-header-mobile-trigger"] .ast-button-wrap .ast-mobile-menu-trigger-minimal{background:transparent;}
+.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:150px;}
+.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:150px; width:150px;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:120px;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:120px; width:120px;}
+[@media (max-width:543px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:100px;}
+[@media (max-width:543px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:100px; width:100px;}
+[@media (min-width:921px)] .ast-theme-transparent-header #masthead{position:absolute;left:0;right:0;}
+[@media (min-width:921px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar{background:none;}
+[@media (min-width:921px)] body.elementor-editor-active.ast-theme-transparent-header #masthead,.fl-builder-edit .ast-theme-transparent-header #masthead,body.vc_editor.ast-theme-transparent-header #masthead,body.brz-ed.ast-theme-transparent-header #masthead{z-index:0;}
+[@media (min-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .custom-mobile-logo-link{display:none;}
+[@media (min-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .transparent-custom-logo{display:inline-block;}
+[@media (min-width:921px)] .ast-theme-transparent-header .ast-above-header,.ast-theme-transparent-header .ast-above-header.ast-above-header-bar{background-image:none;background-color:transparent;}
+[@media (min-width:921px)] .ast-theme-transparent-header .ast-below-header,.ast-theme-transparent-header .ast-below-header.ast-below-header-bar{background-image:none;background-color:transparent;}
+.ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-menu,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .ast-mobile-header-wrap .main-header-bar{background-color:#101010;}
+.ast-theme-transparent-header .main-header-bar .ast-search-menu-icon form{background-color:#101010;}
+.ast-theme-transparent-header .ast-above-header,.ast-theme-transparent-header .ast-above-header.ast-above-header-bar{background-color:#111111;}
+.ast-theme-transparent-header .site-title a,.ast-theme-transparent-header .site-title a:focus,.ast-theme-transparent-header .site-title a:hover,.ast-theme-transparent-header .site-title a:visited{color:#202020;}
+.ast-theme-transparent-header .site-header .site-title a:hover{color:#232323;}
+.ast-theme-transparent-header .site-header .site-description{color:#202020;}
+.ast-theme-transparent-header .ast-builder-menu .main-header-menu,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .sub-menu,.ast-theme-transparent-header .ast-builder-menu .main-header-menu,.ast-theme-transparent-header.ast-header-break-point .ast-builder-menu .main-header-bar-wrap .main-header-menu,.ast-flyout-menu-enable.ast-header-break-point.ast-theme-transparent-header .main-header-bar-navigation .site-navigation,.ast-fullscreen-menu-enable.ast-header-break-point.ast-theme-transparent-header .main-header-bar-navigation .site-navigation,.ast-flyout-above-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation-wrap .ast-above-header-navigation,.ast-flyout-below-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-below-header-navigation-wrap .ast-below-header-actual-nav,.ast-fullscreen-above-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation-wrap,.ast-fullscreen-below-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-below-header-navigation-wrap,.ast-theme-transparent-header .main-header-menu .menu-link{background-color:#353535;}
+.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu .menu-link,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu{background-color:#434343;}
+.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link{color:#404040;}
+.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link:hover,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .menu-item,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .ast-menu-toggle,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link{color:#424242;}
+.ast-theme-transparent-header .ast-builder-menu .main-header-menu,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item > .menu-link,.ast-theme-transparent-header .ast-masthead-custom-menu-items,.ast-theme-transparent-header .ast-masthead-custom-menu-items a,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-above-header-navigation a,.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation a,.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation > ul.ast-above-header-menu > .menu-item-has-children:not(.current-menu-item) > .ast-menu-toggle,.ast-theme-transparent-header .ast-below-header-menu,.ast-theme-transparent-header .ast-below-header-menu a,.ast-header-break-point.ast-theme-transparent-header .ast-below-header-menu a,.ast-header-break-point.ast-theme-transparent-header .ast-below-header-menu,.ast-theme-transparent-header .main-header-menu .menu-link{color:#303030;}
+.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item:hover > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item:hover > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .ast-masthead-custom-menu-items a:hover,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .focus > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .focus > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-item > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-ancestor > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-ancestor > .ast-menu-toggle,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-item > .menu-link,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-ancestor > .menu-link,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-ancestor > .ast-menu-toggle,.ast-theme-transparent-header .main-header-menu .menu-item:hover > .menu-link,.ast-theme-transparent-header .main-header-menu .current-menu-item > .menu-link,.ast-theme-transparent-header .main-header-menu .current-menu-ancestor > .menu-link{color:#333333;}
+.ast-theme-transparent-header div.ast-masthead-custom-menu-items,.ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget,.ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget-title,.ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element{color:#505050;}
+.ast-theme-transparent-header div.ast-masthead-custom-menu-items a,.ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget a,.ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element a{color:#535353;}
+.ast-theme-transparent-header div.ast-masthead-custom-menu-items a:hover,.ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget a:hover,.ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element a:hover{color:#545454;}
+.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link{background-color:transparent;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead{position:absolute;left:0;right:0;}
+[@media (max-width:921px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar{background:none;}
+[@media (max-width:921px)] body.elementor-editor-active.ast-theme-transparent-header #masthead,.fl-builder-edit .ast-theme-transparent-header #masthead,body.vc_editor.ast-theme-transparent-header #masthead,body.brz-ed.ast-theme-transparent-header #masthead{z-index:0;}
+[@media (max-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .custom-mobile-logo-link{display:none;}
+[@media (max-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .transparent-custom-logo{display:inline-block;}
+[@media (max-width:921px)] .ast-theme-transparent-header .ast-above-header,.ast-theme-transparent-header .ast-above-header.ast-above-header-bar{background-image:none;background-color:transparent;}
+[@media (max-width:921px)] .ast-theme-transparent-header .ast-below-header,.ast-theme-transparent-header .ast-below-header.ast-below-header-bar{background-image:none;background-color:transparent;}
+[@media (max-width:921px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-menu,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .ast-mobile-header-wrap .main-header-bar{background-color:#101010;}
+[@media (max-width:921px)] .ast-theme-transparent-header .main-header-bar .ast-search-menu-icon form{background-color:#101010;}
+[@media (max-width:921px)] .ast-theme-transparent-header.ast-header-break-point .ast-above-header,.ast-theme-transparent-header.ast-header-break-point .ast-above-header-bar .main-header-menu{background-color:#121212;}
+[@media (max-width:921px)] .ast-theme-transparent-header.ast-header-break-point .ast-below-header,.ast-theme-transparent-header.ast-header-break-point .ast-below-header-bar .main-header-menu{background-color:#131313;}
+[@media (max-width:921px)] .ast-theme-transparent-header .site-title a,.ast-theme-transparent-header .site-title a:focus,.ast-theme-transparent-header .site-title a:hover,.ast-theme-transparent-header .site-title a:visited,.ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a,.ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:hover,.ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:focus,.ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:visited{color:#212121;}
+[@media (max-width:921px)] .ast-theme-transparent-header .site-header .site-description{color:#212121;}
+[@media (max-width:921px)] .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link{color:#414141;}
+[@media (max-width:921px)] .ast-theme-transparent-header .ast-builder-menu .main-header-menu,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item > .menu-link,.ast-theme-transparent-header .ast-masthead-custom-menu-items,.ast-theme-transparent-header .ast-masthead-custom-menu-items a,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .main-header-menu .menu-link{color:#313131;}
+[@media (max-width:921px)] .ast-theme-transparent-header div.ast-masthead-custom-menu-items,.ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget,.ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget-title,.ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element{color:#515151;}
+[@media (max-width:544px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-menu,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .ast-mobile-header-wrap .main-header-bar{background-color:#101010;}
+[@media (max-width:544px)] .ast-theme-transparent-header .main-header-bar .ast-search-menu-icon form{background-color:#101010;}
+[@media (max-width:544px)] .ast-theme-transparent-header.ast-header-break-point .ast-above-header,.ast-theme-transparent-header.ast-header-break-point .ast-above-header-bar .main-header-menu{background-color:#121212;}
+[@media (max-width:544px)] .ast-theme-transparent-header.ast-header-break-point .ast-below-header,.ast-theme-transparent-header.ast-header-break-point .ast-below-header-bar .main-header-menu{background-color:#141414;}
+[@media (max-width:544px)] .ast-theme-transparent-header .site-title a,.ast-theme-transparent-header .site-title a:focus,.ast-theme-transparent-header .site-title a:hover,.ast-theme-transparent-header .site-title a:visited,.ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a,.ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:hover,.ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:focus,.ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:visited{color:#222222;}
+[@media (max-width:544px)] .ast-theme-transparent-header .site-header .site-description{color:#222222;}
+[@media (max-width:544px)] .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu,.ast-theme-transparent-header.astra-hfb-header .ast-builder-menu [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu,.ast-header-break-point.ast-flyout-menu-enable.astra-hfb-header .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link,.ast-theme-transparent-header.astra-hfb-header .ast-builder-menu [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu .menu-link,.ast-header-break-point.ast-flyout-menu-enable.astra-hfb-header .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu .menu-link,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu,.ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu{background-color:#444444;}
+[@media (max-width:544px)] .ast-theme-transparent-header .ast-builder-menu .main-header-menu,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link,.ast-theme-transparent-header .ast-masthead-custom-menu-items,.ast-theme-transparent-header .ast-masthead-custom-menu-items a,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .main-header-menu .menu-link{color:#323232;}
+[@media (max-width:544px)] .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item:hover > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item:hover > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .ast-masthead-custom-menu-items a:hover,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .focus > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .focus > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-item > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-ancestor > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-ancestor > .ast-menu-toggle,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-item > .menu-link,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-ancestor > .menu-link,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-ancestor > .ast-menu-toggle,.ast-theme-transparent-header .main-header-menu .menu-item:hover > .menu-link,.ast-theme-transparent-header .main-header-menu .current-menu-item > .menu-link,.ast-theme-transparent-header .main-header-menu .current-menu-ancestor > .menu-link{color:#343434;}
+[@media (max-width:544px)] .ast-theme-transparent-header div.ast-masthead-custom-menu-items,.ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget,.ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget-title,.ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element{color:#525252;}
+[@media (max-width:544px)] .ast-theme-transparent-header div.ast-masthead-custom-menu-items a:hover,.ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget a:hover{color:#555555;}
+.ast-theme-transparent-header #ast-desktop-header > .ast-main-header-wrap > .main-header-bar,.ast-theme-transparent-header.ast-header-break-point #ast-mobile-header > .ast-main-header-wrap > .main-header-bar{border-bottom-width:1px;border-bottom-style:solid;border-bottom-color:var(--ast-global-color-4);}
diff --git a/tests/references/transparent/attendus/couleurs.retirees.css b/tests/references/transparent/attendus/couleurs.retirees.css
new file mode 100644
index 0000000..51257cb
--- /dev/null
+++ b/tests/references/transparent/attendus/couleurs.retirees.css
@@ -0,0 +1 @@
+[@media (min-width:1200px)] .ast-plain-container.ast-no-sidebar #primary{margin-top:60px;margin-bottom:60px;}
diff --git a/tests/references/transparent/attendus/garden.css b/tests/references/transparent/attendus/garden.css
new file mode 100644
index 0000000..1fb54d5
--- /dev/null
+++ b/tests/references/transparent/attendus/garden.css
@@ -0,0 +1,25 @@
+.ast-theme-transparent-header [data-section="section-header-mobile-trigger"] .ast-button-wrap .ast-mobile-menu-trigger-minimal{background:transparent;}
+.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:150px;}
+.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:150px; width:150px;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:120px;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:120px; width:120px;}
+[@media (max-width:543px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:100px;}
+[@media (max-width:543px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:100px; width:100px;}
+[@media (min-width:921px)] .ast-theme-transparent-header #masthead{position:absolute;left:0;right:0;}
+[@media (min-width:921px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar{background:none;}
+[@media (min-width:921px)] body.elementor-editor-active.ast-theme-transparent-header #masthead,.fl-builder-edit .ast-theme-transparent-header #masthead,body.vc_editor.ast-theme-transparent-header #masthead,body.brz-ed.ast-theme-transparent-header #masthead{z-index:0;}
+[@media (min-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .custom-mobile-logo-link{display:none;}
+[@media (min-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .transparent-custom-logo{display:inline-block;}
+[@media (min-width:921px)] .ast-theme-transparent-header .ast-above-header,.ast-theme-transparent-header .ast-above-header.ast-above-header-bar{background-image:none;background-color:transparent;}
+[@media (min-width:921px)] .ast-theme-transparent-header .ast-below-header,.ast-theme-transparent-header .ast-below-header.ast-below-header-bar{background-image:none;background-color:transparent;}
+.ast-theme-transparent-header .site-title a,.ast-theme-transparent-header .site-title a:focus,.ast-theme-transparent-header .site-title a:hover,.ast-theme-transparent-header .site-title a:visited{color:var(--ast-global-color-2);}
+.ast-theme-transparent-header .site-header .site-description{color:var(--ast-global-color-2);}
+.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link{background-color:transparent;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead{position:absolute;left:0;right:0;}
+[@media (max-width:921px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar{background:none;}
+[@media (max-width:921px)] body.elementor-editor-active.ast-theme-transparent-header #masthead,.fl-builder-edit .ast-theme-transparent-header #masthead,body.vc_editor.ast-theme-transparent-header #masthead,body.brz-ed.ast-theme-transparent-header #masthead{z-index:0;}
+[@media (max-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .custom-mobile-logo-link{display:none;}
+[@media (max-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .transparent-custom-logo{display:inline-block;}
+[@media (max-width:921px)] .ast-theme-transparent-header .ast-above-header,.ast-theme-transparent-header .ast-above-header.ast-above-header-bar{background-image:none;background-color:transparent;}
+[@media (max-width:921px)] .ast-theme-transparent-header .ast-below-header,.ast-theme-transparent-header .ast-below-header.ast-below-header-bar{background-image:none;background-color:transparent;}
+.ast-theme-transparent-header #ast-desktop-header > .ast-main-header-wrap > .main-header-bar,.ast-theme-transparent-header.ast-header-break-point #ast-mobile-header > .ast-main-header-wrap > .main-header-bar{border-bottom-width:0px;border-bottom-style:solid;}
diff --git a/tests/references/transparent/attendus/garden.retirees.css b/tests/references/transparent/attendus/garden.retirees.css
new file mode 100644
index 0000000..51257cb
--- /dev/null
+++ b/tests/references/transparent/attendus/garden.retirees.css
@@ -0,0 +1 @@
+[@media (min-width:1200px)] .ast-plain-container.ast-no-sidebar #primary{margin-top:60px;margin-bottom:60px;}
diff --git a/tests/references/transparent/attendus/mobile.css b/tests/references/transparent/attendus/mobile.css
new file mode 100644
index 0000000..c46e29f
--- /dev/null
+++ b/tests/references/transparent/attendus/mobile.css
@@ -0,0 +1,22 @@
+.ast-theme-transparent-header [data-section="section-header-mobile-trigger"] .ast-button-wrap .ast-mobile-menu-trigger-minimal{background:transparent;}
+.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:150px;}
+.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:150px; width:150px;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:120px;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:120px; width:120px;}
+[@media (max-width:543px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:100px;}
+[@media (max-width:543px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:100px; width:100px;}
+[@media (min-width:921px)] .transparent-custom-logo{display:none;}
+[@media (max-width:921px)] .transparent-custom-logo{display:block;}
+[@media (max-width:921px)] .ast-transparent-desktop-logo{display:none;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead{position:absolute;left:0;right:0;}
+[@media (max-width:921px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar{background:none;}
+[@media (max-width:921px)] body.elementor-editor-active.ast-theme-transparent-header #masthead,.fl-builder-edit .ast-theme-transparent-header #masthead,body.vc_editor.ast-theme-transparent-header #masthead,body.brz-ed.ast-theme-transparent-header #masthead{z-index:0;}
+[@media (max-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .custom-mobile-logo-link{display:none;}
+[@media (max-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .transparent-custom-logo{display:inline-block;}
+[@media (max-width:921px)] .ast-theme-transparent-header .ast-above-header,.ast-theme-transparent-header .ast-above-header.ast-above-header-bar{background-image:none;background-color:transparent;}
+[@media (max-width:921px)] .ast-theme-transparent-header .ast-below-header,.ast-theme-transparent-header .ast-below-header.ast-below-header-bar{background-image:none;background-color:transparent;}
+[@media (max-width:921px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-menu,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .ast-mobile-header-wrap .main-header-bar{background-color:rgba(0,0,0,0.5);}
+[@media (max-width:921px)] .ast-theme-transparent-header .main-header-bar .ast-search-menu-icon form{background-color:rgba(0,0,0,0.5);}
+[@media (max-width:544px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-menu,.ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .ast-mobile-header-wrap .main-header-bar{background-color:rgba(0,0,0,0.5);}
+[@media (max-width:544px)] .ast-theme-transparent-header .main-header-bar .ast-search-menu-icon form{background-color:rgba(0,0,0,0.5);}
+[@media (max-width:921px)] .ast-theme-transparent-header.ast-header-break-point #ast-mobile-header > .ast-main-header-wrap > .main-header-bar{border-bottom-width:0px;border-bottom-style:solid;}
diff --git a/tests/references/transparent/attendus/mobile.retirees.css b/tests/references/transparent/attendus/mobile.retirees.css
new file mode 100644
index 0000000..51257cb
--- /dev/null
+++ b/tests/references/transparent/attendus/mobile.retirees.css
@@ -0,0 +1 @@
+[@media (min-width:1200px)] .ast-plain-container.ast-no-sidebar #primary{margin-top:60px;margin-bottom:60px;}
diff --git a/tests/references/transparent/attendus/neutralisations.css b/tests/references/transparent/attendus/neutralisations.css
new file mode 100644
index 0000000..5a6daeb
--- /dev/null
+++ b/tests/references/transparent/attendus/neutralisations.css
@@ -0,0 +1 @@
+[@media (min-width:1200px)] .ast-theme-transparent-header.ast-plain-container.ast-no-sidebar #primary{margin-top:0;margin-bottom:0;}
diff --git a/tests/references/transparent/attendus/ordinateur.css b/tests/references/transparent/attendus/ordinateur.css
new file mode 100644
index 0000000..a685a4a
--- /dev/null
+++ b/tests/references/transparent/attendus/ordinateur.css
@@ -0,0 +1,20 @@
+.ast-theme-transparent-header [data-section="section-header-mobile-trigger"] .ast-button-wrap .ast-mobile-menu-trigger-minimal{background:transparent;}
+.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:150px;}
+.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:150px; width:150px;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:120px;}
+[@media (max-width:921px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:120px; width:120px;}
+[@media (max-width:543px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg{width:100px;}
+[@media (max-width:543px)] .ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img{max-width:100px; width:100px;}
+[@media (min-width:921px)] .ast-theme-transparent-header #masthead{position:absolute;left:0;right:0;}
+[@media (min-width:921px)] .ast-theme-transparent-header .main-header-bar,.ast-theme-transparent-header.ast-header-break-point .main-header-bar{background:none;}
+[@media (min-width:921px)] body.elementor-editor-active.ast-theme-transparent-header #masthead,.fl-builder-edit .ast-theme-transparent-header #masthead,body.vc_editor.ast-theme-transparent-header #masthead,body.brz-ed.ast-theme-transparent-header #masthead{z-index:0;}
+[@media (min-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .custom-mobile-logo-link{display:none;}
+[@media (min-width:921px)] .ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .transparent-custom-logo{display:inline-block;}
+[@media (min-width:921px)] .ast-theme-transparent-header .ast-above-header,.ast-theme-transparent-header .ast-above-header.ast-above-header-bar{background-image:none;background-color:transparent;}
+[@media (min-width:921px)] .ast-theme-transparent-header .ast-below-header,.ast-theme-transparent-header .ast-below-header.ast-below-header-bar{background-image:none;background-color:transparent;}
+[@media (min-width:922px)] .ast-theme-transparent-header .ast-builder-menu .main-header-menu,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link,.ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item > .menu-link,.ast-theme-transparent-header .ast-masthead-custom-menu-items,.ast-theme-transparent-header .ast-masthead-custom-menu-items a,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-above-header-navigation a,.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation a,.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation > ul.ast-above-header-menu > .menu-item-has-children:not(.current-menu-item) > .ast-menu-toggle,.ast-theme-transparent-header .ast-below-header-menu,.ast-theme-transparent-header .ast-below-header-menu a,.ast-header-break-point.ast-theme-transparent-header .ast-below-header-menu a,.ast-header-break-point.ast-theme-transparent-header .ast-below-header-menu,.ast-theme-transparent-header .main-header-menu .menu-link{color:#ffffff;}
+[@media (min-width:922px)] .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link,.ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link{background-color:transparent;}
+[@media (max-width:921px)] .transparent-custom-logo{display:none;}
+[@media (min-width:921px)] .ast-transparent-mobile-logo{display:none;}
+[@media (max-width:920px)] .ast-transparent-mobile-logo{display:block;}
+[@media (min-width:921px)] .ast-theme-transparent-header #ast-desktop-header > .ast-main-header-wrap > .main-header-bar{border-bottom-width:2px;border-bottom-style:solid;border-bottom-color:#c0392b;}
diff --git a/tests/references/transparent/attendus/ordinateur.retirees.css b/tests/references/transparent/attendus/ordinateur.retirees.css
new file mode 100644
index 0000000..51257cb
--- /dev/null
+++ b/tests/references/transparent/attendus/ordinateur.retirees.css
@@ -0,0 +1 @@
+[@media (min-width:1200px)] .ast-plain-container.ast-no-sidebar #primary{margin-top:60px;margin-bottom:60px;}
diff --git a/tests/references/transparent/capturer.sh b/tests/references/transparent/capturer.sh
new file mode 100755
index 0000000..0c4ea48
--- /dev/null
+++ b/tests/references/transparent/capturer.sh
@@ -0,0 +1,25 @@
+#!/usr/bin/env bash
+# Capture le CSS dynamique de WordPress sur une page à en-tête transparent, pour chaque variante.
+# Usage : WP=<racine WordPress> [URL=http://127.0.0.1:8401] tests/references/transparent/capturer.sh
+# Écrit attendus/<variante>.css : règles ajoutées ou modifiées par rapport à la même page sans
+# en-tête transparent (regles.py des styles), puis remet le WordPress en état R0.
+set -euo pipefail
+ICI=$(cd "$(dirname "$0")" && pwd)
+URL=${URL:-http://127.0.0.1:8401}
+: "${WP:?WP doit indiquer la racine du WordPress de test}"
+css() {
+	curl -sf "$URL/?pagename=notre-jardin" | php -r '$h = stream_get_contents(STDIN); if (!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s", $h, $m)) { exit(1); } echo $m[1];'
+}
+VARIANTES=(garden ordinateur mobile couleurs)
+VARIANTE=base wp --path="$WP" eval-file "$ICI/appliquer.php"
+css > "$ICI/attendus/.base.css"
+for variante in "${VARIANTES[@]}"; do
+	VARIANTE=$variante wp --path="$WP" eval-file "$ICI/appliquer.php"
+	css > "$ICI/attendus/.$variante.complet.css"
+	python3 "$ICI/../styles/regles.py" "$ICI/attendus/.base.css" "$ICI/attendus/.$variante.complet.css" > "$ICI/attendus/$variante.css"
+	python3 "$ICI/../styles/regles.py" "$ICI/attendus/.$variante.complet.css" "$ICI/attendus/.base.css" > "$ICI/attendus/$variante.retirees.css"
+	rm "$ICI/attendus/.$variante.complet.css"
+	echo "$variante : $(wc -l < "$ICI/attendus/$variante.css") règles, $(wc -l < "$ICI/attendus/$variante.retirees.css") retirées"
+done
+rm "$ICI/attendus/.base.css"
+wp --path="$WP" eval-file "$ICI/../wp/R0-defaut.php"
diff --git a/tests/references/transparent/valeurs/couleurs.json b/tests/references/transparent/valeurs/couleurs.json
new file mode 100644
index 0000000..ec1f79f
--- /dev/null
+++ b/tests/references/transparent/valeurs/couleurs.json
@@ -0,0 +1,74 @@
+{
+	"transparent-header-bg-color-responsive": {
+		"desktop": "#101010",
+		"tablet": "",
+		"mobile": ""
+	},
+	"hba-transparent-header-bg-color-responsive": {
+		"desktop": "#111111",
+		"tablet": "#121212",
+		"mobile": ""
+	},
+	"hbb-transparent-header-bg-color-responsive": {
+		"desktop": "",
+		"tablet": "#131313",
+		"mobile": "#141414"
+	},
+	"transparent-header-color-site-title-responsive": {
+		"desktop": "#202020",
+		"tablet": "#212121",
+		"mobile": "#222222"
+	},
+	"transparent-header-color-h-site-title-responsive": {
+		"desktop": "#232323",
+		"tablet": "",
+		"mobile": ""
+	},
+	"transparent-menu-color-responsive": {
+		"desktop": "#303030",
+		"tablet": "#313131",
+		"mobile": "#323232"
+	},
+	"transparent-menu-h-color-responsive": {
+		"desktop": "#333333",
+		"tablet": "",
+		"mobile": "#343434"
+	},
+	"transparent-menu-bg-color-responsive": {
+		"desktop": "#353535",
+		"tablet": "",
+		"mobile": ""
+	},
+	"transparent-submenu-color-responsive": {
+		"desktop": "#404040",
+		"tablet": "#414141",
+		"mobile": ""
+	},
+	"transparent-submenu-h-color-responsive": {
+		"desktop": "#424242",
+		"tablet": "",
+		"mobile": ""
+	},
+	"transparent-submenu-bg-color-responsive": {
+		"desktop": "#434343",
+		"tablet": "",
+		"mobile": "#444444"
+	},
+	"transparent-content-section-text-color-responsive": {
+		"desktop": "#505050",
+		"tablet": "#515151",
+		"mobile": "#525252"
+	},
+	"transparent-content-section-link-color-responsive": {
+		"desktop": "#535353",
+		"tablet": "",
+		"mobile": ""
+	},
+	"transparent-content-section-link-h-color-responsive": {
+		"desktop": "#545454",
+		"tablet": "",
+		"mobile": "#555555"
+	},
+	"transparent-header-main-sep": 1,
+	"transparent-header-main-sep-color": "var(--ast-global-color-4)"
+}
diff --git a/tests/references/transparent/valeurs/garden.json b/tests/references/transparent/valeurs/garden.json
new file mode 100644
index 0000000..a52a68d
--- /dev/null
+++ b/tests/references/transparent/valeurs/garden.json
@@ -0,0 +1,14 @@
+{
+	"transparent-header-enable": 0,
+	"transparent-header-disable-archive": 1,
+	"transparent-header-disable-latest-posts-index": 1,
+	"transparent-header-on-devices": "both",
+	"transparent-header-main-sep": 0,
+	"transparent-header-main-sep-color": "",
+	"transparent-header-color-site-title-responsive": {
+		"desktop": "var(--ast-global-color-2)",
+		"tablet": "",
+		"mobile": ""
+	},
+	"transparent-header-default-border": false
+}
diff --git a/tests/references/transparent/valeurs/mobile.json b/tests/references/transparent/valeurs/mobile.json
new file mode 100644
index 0000000..2c22865
--- /dev/null
+++ b/tests/references/transparent/valeurs/mobile.json
@@ -0,0 +1,8 @@
+{
+	"transparent-header-on-devices": "mobile",
+	"transparent-header-bg-color-responsive": {
+		"desktop": "",
+		"tablet": "rgba(0,0,0,0.5)",
+		"mobile": ""
+	}
+}
diff --git a/tests/references/transparent/valeurs/ordinateur.json b/tests/references/transparent/valeurs/ordinateur.json
new file mode 100644
index 0000000..86a9ed9
--- /dev/null
+++ b/tests/references/transparent/valeurs/ordinateur.json
@@ -0,0 +1,10 @@
+{
+	"transparent-header-on-devices": "desktop",
+	"transparent-header-main-sep": 2,
+	"transparent-header-main-sep-color": "#c0392b",
+	"transparent-menu-color-responsive": {
+		"desktop": "#ffffff",
+		"tablet": "#eeeeee",
+		"mobile": ""
+	}
+}
diff --git a/tests/unit/EnteteTransparentTest.php b/tests/unit/EnteteTransparentTest.php
new file mode 100644
index 0000000..10c761a
--- /dev/null
+++ b/tests/unit/EnteteTransparentTest.php
@@ -0,0 +1,88 @@
+<?php
+
+use PHPUnit\Framework\Attributes\DataProvider;
+use PHPUnit\Framework\TestCase;
+
+require_once dirname(__DIR__, 2) . '/inc/astra_entete_transparent.php';
+require_once __DIR__ . '/StylesAttendusTest.php';
+
+/**
+ * En-tête transparent : règle d'activation (is_transparent_header() d'Astra 4.11.0) et CSS,
+ * comparé aux règles capturées sur WordPress (tests/references/transparent/attendus/).
+ */
+final class EnteteTransparentTest extends TestCase {
+
+	private const DIR = __DIR__ . '/../references/transparent';
+
+	public function testRienSansReglage(): void {
+		foreach (['page', 'article', 'accueil_articles', 'rubrique', 'mot', 'auteur', 'recherche', '404'] as $contexte) {
+			$this->assertFalse(astra_entete_transparent_actif([], $contexte), $contexte);
+		}
+	}
+
+	public function testReglageDeLaPagePrioritaire(): void {
+		$this->assertTrue(astra_entete_transparent_actif([], 'page', 'active'));
+		$this->assertFalse(astra_entete_transparent_actif(['transparent-header-enable' => 1], 'page', 'desactive'));
+		$this->assertTrue(astra_entete_transparent_actif(['transparent-header-enable' => 1, 'transparent-header-disable-page' => 1], 'page', 'active'));
+	}
+
+	public function testExclusionsDuReglageGlobal(): void {
+		$global = ['transparent-header-enable' => 1];
+		// Défauts d'Astra : archives (404, recherche, rubriques, mots, auteurs) et accueil des
+		// derniers articles exclus ; pages et articles transparents.
+		$this->assertTrue(astra_entete_transparent_actif($global, 'page'));
+		$this->assertTrue(astra_entete_transparent_actif($global, 'article'));
+		foreach (['accueil_articles', 'rubrique', 'mot', 'auteur', 'recherche', '404'] as $contexte) {
+			$this->assertFalse(astra_entete_transparent_actif($global, $contexte), $contexte);
+		}
+		$sans_exclusion = $global + ['transparent-header-disable-archive' => 0, 'transparent-header-disable-latest-posts-index' => 0];
+		foreach (['accueil_articles', 'rubrique', 'recherche', '404'] as $contexte) {
+			$this->assertTrue(astra_entete_transparent_actif($sans_exclusion, $contexte), $contexte);
+		}
+		// Options séparées : elles l'emportent sur transparent-header-disable-archive.
+		$this->assertTrue(astra_entete_transparent_actif($global + ['transparent-header-disable-404-page' => 0], '404'));
+		$this->assertFalse(astra_entete_transparent_actif($sans_exclusion + ['transparent-header-disable-search-page' => 1], 'recherche'));
+		$this->assertFalse(astra_entete_transparent_actif($sans_exclusion + ['transparent-header-disable-archive-pages' => 1], 'mot'));
+		$this->assertFalse(astra_entete_transparent_actif($global + ['transparent-header-disable-page' => 1], 'page'));
+		$this->assertFalse(astra_entete_transparent_actif($global + ['transparent-header-disable-posts' => 1], 'article'));
+		// Valeur invalide : retirée par la normalisation, défaut d'Astra.
+		$this->assertFalse(astra_entete_transparent_actif(['transparent-header-enable' => 'oui'], 'page'));
+	}
+
+	public static function variantes(): array {
+		return ['garden' => ['garden'], 'ordinateur' => ['ordinateur'], 'mobile' => ['mobile'], 'couleurs' => ['couleurs']];
+	}
+
+	#[DataProvider('variantes')]
+	public function testEquivalenceAvecWordPress(string $variante): void {
+		$valeurs = json_decode(file_get_contents(self::DIR . "/valeurs/$variante.json"), true);
+		$nous = StylesAttendusTest::regles(astra_entete_transparent_css($valeurs, []));
+		$lignes = function (string $f): array {
+			return array_values(array_filter(array_map('trim', file(self::DIR . "/attendus/$f"))));
+		};
+		$attendues = $lignes("$variante.css");
+		$neutralisations = $lignes('neutralisations.css');
+		$this->assertSame([], array_values(array_diff($attendues, $nous)), 'règles d\'Astra manquantes');
+		$this->assertSame([], array_values(array_diff($nous, $attendues, $neutralisations)), 'règles ni attendues ni neutralisations');
+		// Chaque règle qu'Astra n'émet plus sur une page transparente est une règle fixe de
+		// css/astra-dynamic.css, défaite par une neutralisation.
+		$fixes = StylesAttendusTest::regles(file_get_contents(dirname(__DIR__, 2) . '/css/astra-dynamic.css'));
+		foreach ($lignes("$variante.retirees.css") as $retiree) {
+			$this->assertContains($retiree, $fixes);
+		}
+		$this->assertCount(count($lignes("$variante.retirees.css")), $neutralisations);
+	}
+
+	public function testSeparateurParDefaut(): void {
+		$sel = '.ast-theme-transparent-header #ast-desktop-header > .ast-main-header-wrap > .main-header-bar,.ast-theme-transparent-header.ast-header-break-point #ast-mobile-header > .ast-main-header-wrap > .main-header-bar';
+		// Installation récente : 0 px ; drapeau transparent-header-default-border à faux : aucun.
+		$this->assertStringContainsString($sel . '{border-bottom-width:0px;border-bottom-style:solid;}', astra_entete_transparent_css([], []));
+		$this->assertStringContainsString($sel . '{border-bottom-style:none;}', astra_entete_transparent_css(['transparent-header-default-border' => false], []));
+	}
+
+	public function testDerniereRangeeRemplie(): void {
+		$hfb = ['entete_ordinateur' => ['below' => ['below_center' => ['menu-2']]]];
+		$this->assertSame('.ast-theme-transparent-header #ast-desktop-header > .ast-below-header-wrap > .ast-below-header', astra_entete_transparent_derniere_rangee($hfb, 'desktop'));
+		$this->assertSame('.ast-theme-transparent-header.ast-header-break-point #ast-mobile-header > .ast-main-header-wrap > .main-header-bar', astra_entete_transparent_derniere_rangee($hfb, 'mobile'));
+	}
+}
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : arrêt au chargement (`require_once(…/inc/astra_entete_transparent.php): Failed to open stream`, puis « An error occurred inside PHPUnit ») ;
- intégration : 1 échec (`StylesFormulaireTest::testRenduDesChamps`, 12 sections au lieu de 13).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/inc/astra_entete_transparent.php b/inc/astra_entete_transparent.php
new file mode 100644
index 0000000..1f3a184
--- /dev/null
+++ b/inc/astra_entete_transparent.php
@@ -0,0 +1,252 @@
+<?php
+/**
+ * Astra pour SPIP — en-tête transparent (inc/addons/transparent-header/ d'Astra 4.11.0).
+ *
+ * Fonctions pures : règle d'activation (Astra_Ext_Transparent_Header_Markup::is_transparent_header())
+ * et CSS du module (classes/dynamic-css/dynamic.css.php, et la partie « Transparent Header -
+ * Component header specific CSS compatibility » d'inc/class-astra-dynamic-css.php). Le CSS de
+ * header-sections-dynamic.css.php ne concerne que l'en-tête sans builder : il n'est pas repris.
+ *
+ * @license GPL-3.0-or-later
+ */
+
+require_once __DIR__ . '/astra_styles.php';
+require_once __DIR__ . '/astra_hfb.php';
+
+/** Points de rupture d'Astra (astra_get_tablet_breakpoint(), astra_get_mobile_breakpoint()). */
+const ASTRA_TRANSPARENT_TABLETTE = 921;
+const ASTRA_TRANSPARENT_MOBILE = 544;
+
+/**
+ * Vrai si la page a l'en-tête transparent. $contexte : page (page unique, y compris en accueil
+ * statique), article, accueil_articles (accueil qui liste les derniers articles), rubrique, mot,
+ * auteur, recherche, 404. $reglage_page : réglage de la page unique (defaut, active, desactive).
+ */
+function astra_entete_transparent_actif(array $s, string $contexte, string $reglage_page = 'defaut'): bool {
+	$s = astra_styles_normaliser($s);
+	$vrai = function (string $option, $defaut = 0) use ($s): bool {
+		return (string) ($s[$option] ?? $defaut) === '1' || ($s[$option] ?? null) === true;
+	};
+	// Anciennes options : 404, recherche et archives retombent sur transparent-header-disable-archive.
+	$exclue = function (string $option) use ($s, $vrai): bool {
+		return isset($s[$option]) && $s[$option] !== '' ? $vrai($option) : $vrai('transparent-header-disable-archive', 1);
+	};
+	$actif = $vrai('transparent-header-enable');
+	if ($actif) {
+		switch ($contexte) {
+			case '404':
+				$actif = !$exclue('transparent-header-disable-404-page');
+				break;
+			case 'recherche':
+				$actif = !$exclue('transparent-header-disable-search-page');
+				break;
+			case 'rubrique':
+			case 'mot':
+			case 'auteur':
+				$actif = !$exclue('transparent-header-disable-archive-pages');
+				break;
+			case 'accueil_articles':
+				$actif = !$vrai('transparent-header-disable-latest-posts-index', 1);
+				break;
+			case 'page':
+				$actif = !$vrai('transparent-header-disable-page');
+				break;
+			case 'article':
+				$actif = !$vrai('transparent-header-disable-posts');
+				break;
+		}
+	}
+	if ($reglage_page === 'active') {
+		return true;
+	}
+	if ($reglage_page === 'desactive') {
+		return false;
+	}
+	return $actif;
+}
+
+/**
+ * Dernière rangée remplie de l'en-tête, comme astra_get_transparent_header_last_active_row() :
+ * sélecteur de sa barre, vide si l'en-tête de cet appareil n'a aucune rangée remplie.
+ */
+function astra_entete_transparent_derniere_rangee(array $hfb, string $appareil): string {
+	$n = astra_hfb_normaliser($hfb);
+	$rangees = $appareil === 'desktop' ? $n['entete_ordinateur'] : $n['entete_mobile'];
+	$prefixe = $appareil === 'desktop' ? '.ast-theme-transparent-header #ast-desktop-header > ' : '.ast-theme-transparent-header.ast-header-break-point #ast-mobile-header > ';
+	$selecteur = '';
+	foreach (['above', 'primary', 'below'] as $rangee) {
+		$remplie = false;
+		foreach ($rangees[$rangee] ?? [] as $ids) {
+			$remplie = $remplie || !empty($ids);
+		}
+		if ($remplie) {
+			$selecteur = $rangee === 'primary' ? $prefixe . '.ast-main-header-wrap > .main-header-bar' : $prefixe . '.ast-' . $rangee . '-header-wrap > .ast-' . $rangee . '-header';
+		}
+	}
+	return $selecteur;
+}
+
+/**
+ * CSS de l'en-tête transparent, pour une page où il est actif : règles du module d'Astra dans son
+ * ordre et ses media queries, puis la neutralisation de la marge de #primary qu'Astra n'émet pas
+ * sur une page à en-tête transparent (css/astra-dynamic.css la contient).
+ */
+function astra_entete_transparent_css(array $styles, array $hfb): string {
+	$s = astra_styles_normaliser($styles);
+	$t = ASTRA_TRANSPARENT_TABLETTE;
+	$m = ASTRA_TRANSPARENT_MOBILE;
+	$min = "(min-width:{$t}px)";
+	$max_t = "(max-width:{$t}px)";
+	$max_m = "(max-width:{$m}px)";
+	$v = function (string $option, string $appareil) use ($s): string {
+		$x = $s[$option][$appareil] ?? '';
+		return is_string($x) ? $x : '';
+	};
+	// Fonds : la tablette reprend l'ordinateur, le mobile la tablette (astra_get_prop() avec repli).
+	$fond = function (string $option) use ($v): array {
+		$o = $v($option, 'desktop');
+		$t = $v($option, 'tablet') !== '' ? $v($option, 'tablet') : $o;
+		$m = $v($option, 'mobile') !== '' ? $v($option, 'mobile') : ($t !== '' ? $t : $o);
+		return ['desktop' => $o, 'tablet' => $t, 'mobile' => $m];
+	};
+	$fonds = ['' => $fond('transparent-header-bg-color-responsive'), 'hba' => $fond('hba-transparent-header-bg-color-responsive'), 'hbb' => $fond('hbb-transparent-header-bg-color-responsive')];
+	$appareils = $s['transparent-header-on-devices'] ?? 'both';
+	$regles = [];
+
+	// Largeur du logo propre à l'en-tête transparent : défauts d'Astra (logo propre hors périmètre).
+	foreach (['' => 150, $max_t => 120, '(max-width:543px)' => 100] as $media => $largeur) {
+		$regles[] = [$media, '.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg', ['width' => $largeur . 'px']];
+		$regles[] = [$media, '.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img', [' max-width' => $largeur . 'px', ' width' => $largeur . 'px']];
+	}
+
+	$base = [
+		['.ast-theme-transparent-header #masthead', ['position' => 'absolute', 'left' => '0', 'right' => '0']],
+		['.ast-theme-transparent-header .main-header-bar, .ast-theme-transparent-header.ast-header-break-point .main-header-bar', ['background' => 'none']],
+		['body.elementor-editor-active.ast-theme-transparent-header #masthead, .fl-builder-edit .ast-theme-transparent-header #masthead, body.vc_editor.ast-theme-transparent-header #masthead, body.brz-ed.ast-theme-transparent-header #masthead', ['z-index' => '0']],
+		['.ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .custom-mobile-logo-link', ['display' => 'none']],
+		['.ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .transparent-custom-logo', ['display' => 'inline-block']],
+		['.ast-theme-transparent-header .ast-above-header, .ast-theme-transparent-header .ast-above-header.ast-above-header-bar', ['background-image' => 'none', 'background-color' => 'transparent']],
+		['.ast-theme-transparent-header .ast-below-header, .ast-theme-transparent-header .ast-below-header.ast-below-header-bar', ['background-image' => 'none', 'background-color' => 'transparent']],
+	];
+	$couleurs = function (string $appareil) use ($v, $fonds): array {
+		$titre = '.ast-theme-transparent-header .site-title a, .ast-theme-transparent-header .site-title a:focus, .ast-theme-transparent-header .site-title a:hover, .ast-theme-transparent-header .site-title a:visited';
+		if ($appareil !== 'desktop') {
+			$titre .= ', .ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a, .ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:hover, .ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:focus, .ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:visited';
+		}
+		$bp = $appareil === 'desktop' ? '' : '.ast-header-break-point';
+		$rangees = $appareil === 'desktop'
+			? ['.ast-theme-transparent-header .ast-above-header, .ast-theme-transparent-header .ast-above-header.ast-above-header-bar', '.ast-theme-transparent-header .ast-below-header, .ast-theme-transparent-header .ast-below-header.ast-below-header-bar']
+			: ['.ast-theme-transparent-header.ast-header-break-point .ast-above-header, .ast-theme-transparent-header.ast-header-break-point .ast-above-header-bar .main-header-menu', '.ast-theme-transparent-header.ast-header-break-point .ast-below-header, .ast-theme-transparent-header.ast-header-break-point .ast-below-header-bar .main-header-menu'];
+		$menu_fond = $appareil === 'desktop'
+			? '.ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .sub-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header.ast-header-break-point .ast-builder-menu .main-header-bar-wrap .main-header-menu, '
+			: '.ast-theme-transparent-header.ast-header-break-point .ast-builder-menu .main-header-menu, .ast-theme-transparent-header.ast-header-break-point .ast-builder-menu  .main-header-menu .sub-menu, .ast-theme-transparent-header.ast-header-break-point .ast-builder-menu  .main-header-menu, .ast-theme-transparent-header.ast-header-break-point .ast-builder-menu .main-header-bar-wrap .main-header-menu, ';
+		$menu_fond .= '.ast-flyout-menu-enable.ast-header-break-point.ast-theme-transparent-header .main-header-bar-navigation .site-navigation, .ast-fullscreen-menu-enable.ast-header-break-point.ast-theme-transparent-header .main-header-bar-navigation .site-navigation, .ast-flyout-above-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation-wrap .ast-above-header-navigation, .ast-flyout-below-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-below-header-navigation-wrap .ast-below-header-actual-nav, .ast-fullscreen-above-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation-wrap, .ast-fullscreen-below-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-below-header-navigation-wrap, .ast-theme-transparent-header .main-header-menu .menu-link';
+		$sous_menu_fond = $appareil === 'desktop'
+			? '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu'
+			: '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu, .ast-theme-transparent-header.astra-hfb-header .ast-builder-menu [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.astra-hfb-header .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header.astra-hfb-header .ast-builder-menu [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.astra-hfb-header .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu';
+		$sous_menu = '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link';
+		$sous_menu_survol = [
+			'desktop' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link:hover,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .menu-item,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link',
+			'tablet' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link:hover,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .menu-item,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link',
+			'mobile' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link:hover,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .menu-item,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-item.focus > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link',
+		][$appareil];
+		$menu = [
+			'desktop' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item > .menu-link, .ast-theme-transparent-header .ast-masthead-custom-menu-items, .ast-theme-transparent-header .ast-masthead-custom-menu-items a, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-above-header-navigation a, .ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation a, .ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation > ul.ast-above-header-menu > .menu-item-has-children:not(.current-menu-item) > .ast-menu-toggle, .ast-theme-transparent-header .ast-below-header-menu, .ast-theme-transparent-header .ast-below-header-menu a, .ast-header-break-point.ast-theme-transparent-header .ast-below-header-menu a, .ast-header-break-point.ast-theme-transparent-header .ast-below-header-menu, .ast-theme-transparent-header .main-header-menu .menu-link',
+			'tablet' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item > .menu-link, .ast-theme-transparent-header .ast-masthead-custom-menu-items, .ast-theme-transparent-header .ast-masthead-custom-menu-items a,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-link',
+			'mobile' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link, .ast-theme-transparent-header .ast-masthead-custom-menu-items, .ast-theme-transparent-header .ast-masthead-custom-menu-items a, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-link',
+		][$appareil];
+		$menu_survol = '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item:hover > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .ast-masthead-custom-menu-items a:hover, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .focus > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .focus > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-item > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-ancestor > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-ancestor > .ast-menu-toggle, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-item > .menu-link, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-ancestor > .menu-link, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-item > .ast-menu-toggle, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-ancestor > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .main-header-menu .current-menu-item > .menu-link, .ast-theme-transparent-header .main-header-menu .current-menu-ancestor > .menu-link';
+		$texte = '.ast-theme-transparent-header div.ast-masthead-custom-menu-items, .ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget, .ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget-title, .ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element';
+		$lien = '.ast-theme-transparent-header div.ast-masthead-custom-menu-items a, .ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget a';
+		$lien_survol = '.ast-theme-transparent-header div.ast-masthead-custom-menu-items a:hover, .ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget a:hover';
+		if ($appareil === 'desktop') {
+			$lien .= ', .ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element a';
+			$lien_survol .= ', .ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element a:hover';
+		}
+		$titre_couleur = $v('transparent-header-color-site-title-responsive', $appareil);
+		$r = [
+			['.ast-theme-transparent-header .main-header-bar, .ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-menu, .ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-bar, .ast-theme-transparent-header.ast-header-break-point .ast-mobile-header-wrap .main-header-bar', ['background-color' => $fonds[''][$appareil]]],
+			['.ast-theme-transparent-header .main-header-bar .ast-search-menu-icon form', ['background-color' => $fonds[''][$appareil]]],
+			[$rangees[0], ['background-color' => $fonds['hba'][$appareil]]],
+			[$rangees[1], ['background-color' => $fonds['hbb'][$appareil]]],
+			[$titre, ['color' => $titre_couleur]],
+			['.ast-theme-transparent-header .site-header .site-title a:hover', ['color' => $v('transparent-header-color-h-site-title-responsive', $appareil)]],
+			['.ast-theme-transparent-header .site-header .site-description', ['color' => $titre_couleur]],
+			[$menu_fond, ['background-color' => $v('transparent-menu-bg-color-responsive', $appareil)]],
+			[$sous_menu_fond, ['background-color' => $v('transparent-submenu-bg-color-responsive', $appareil)]],
+			[$sous_menu, ['color' => $v('transparent-submenu-color-responsive', $appareil)]],
+			[$sous_menu_survol, ['color' => $v('transparent-submenu-h-color-responsive', $appareil)]],
+			[$menu, ['color' => $v('transparent-menu-color-responsive', $appareil)]],
+			[$menu_survol, ['color' => $v('transparent-menu-h-color-responsive', $appareil)]],
+			[$texte, ['color' => $v('transparent-content-section-text-color-responsive', $appareil)]],
+			[$lien, ['color' => $v('transparent-content-section-link-color-responsive', $appareil)]],
+			[$lien_survol, ['color' => $v('transparent-content-section-link-h-color-responsive', $appareil)]],
+		];
+		// astra_has_submenu_transperent_styling() : vrai sur une installation récente.
+		if ($appareil === 'desktop') {
+			$r[] = ['.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link', ['background-color' => 'transparent']];
+		}
+		return $r;
+	};
+	$ajouter = function (string $media, array $bloc) use (&$regles) {
+		foreach ($bloc as [$selecteur, $declarations]) {
+			$regles[] = [$media, $selecteur, $declarations];
+		}
+	};
+
+	if ($appareils === 'both' || $appareils === 'desktop') {
+		$ajouter($min, $base);
+		$ajouter($appareils === 'both' ? '' : '(min-width:' . ($t + 1) . 'px)', $couleurs('desktop'));
+	}
+	if ($appareils === 'mobile') {
+		$regles[] = [$min, '.transparent-custom-logo', ['display' => 'none']];
+		$regles[] = [$max_t, '.transparent-custom-logo', ['display' => 'block']];
+		$regles[] = [$max_t, '.ast-transparent-desktop-logo', ['display' => 'none']];
+	}
+	if ($appareils === 'desktop') {
+		$regles[] = [$max_t, '.transparent-custom-logo', ['display' => 'none']];
+		$regles[] = [$min, '.ast-transparent-mobile-logo', ['display' => 'none']];
+		$regles[] = ['(max-width:' . ($t - 1) . 'px)', '.ast-transparent-mobile-logo', ['display' => 'block']];
+	}
+	if ($appareils === 'both' || $appareils === 'mobile') {
+		$ajouter($max_t, $base);
+		$ajouter($max_t, $couleurs('tablet'));
+		$ajouter($max_m, $couleurs('mobile'));
+	}
+
+	// Séparateur sous la dernière rangée remplie de l'en-tête.
+	// Défaut : 0 sur une installation récente, vide si transparent-header-default-border est faux
+	// (astra_get_transparent_header_default_value(), inc/core/builder/class-astra-builder-options.php).
+	$sep = $s['transparent-header-main-sep'] ?? (($s['transparent-header-default-border'] ?? true) === false ? '' : 0);
+	$sep_css = $sep !== '' && $sep !== null
+		? ['border-bottom-width' => $sep . 'px', 'border-bottom-style' => 'solid', 'border-bottom-color' => $s['transparent-header-main-sep-color'] ?? '']
+		: ['border-bottom-style' => 'none'];
+	$ordi = astra_entete_transparent_derniere_rangee($hfb, 'desktop');
+	$mobile = astra_entete_transparent_derniere_rangee($hfb, 'mobile');
+	if ($appareils === 'both') {
+		$regles[] = ['', $ordi !== '' && $mobile !== '' ? $ordi . ', ' . $mobile : $ordi . $mobile, $sep_css];
+	} elseif ($appareils === 'mobile') {
+		$regles[] = [$max_t, $mobile, $sep_css];
+	} else {
+		$regles[] = [$min, $ordi, $sep_css];
+	}
+
+	// Bouton du menu mobile (inc/class-astra-dynamic-css.php), couleurs propres non reprises.
+	if (astra_styles_element_place('mobile-trigger', $hfb, 'entete')) {
+		$style = $s['mobile-header-toggle-btn-style'] ?? 'minimal';
+		$bouton = '.ast-theme-transparent-header [data-section="section-header-mobile-trigger"] .ast-button-wrap';
+		if ($style === 'fill') {
+			$regles[] = ['', "$bouton .ast-mobile-menu-trigger-fill, $bouton .ast-mobile-menu-trigger-minimal", ['border' => 'none']];
+		} elseif ($style === 'outline') {
+			$regles[] = ['', "$bouton .ast-mobile-menu-trigger-outline", ['background' => 'transparent']];
+		} else {
+			$regles[] = ['', "$bouton .ast-mobile-menu-trigger-minimal", ['background' => 'transparent']];
+		}
+	}
+
+	// Neutralisation : css/astra-dynamic.css donne 60 px à #primary au-delà de 1200 px, règle
+	// qu'Astra n'émet pas quand l'en-tête est transparent ; la marge reste celle de la règle de base.
+	$regles[] = ['(min-width:1200px)', '.ast-theme-transparent-header.ast-plain-container.ast-no-sidebar #primary', ['margin-top' => '0', 'margin-bottom' => '0']];
+
+	return astra_styles_ecrire($regles);
+}
diff --git a/inc/astra_styles.php b/inc/astra_styles.php
index 2d076db..d7193d4 100644
--- a/inc/astra_styles.php
+++ b/inc/astra_styles.php
@@ -118,6 +118,36 @@ function astra_styles_registre(): array {
 			"$b-social-1-color-type" => ['type' => 'enum', 'valeurs' => ['custom', 'official'], 'defaut' => 'custom'],
 		]);
 	}
+	$booleen = ['type' => 'booleen'];
+	$ajouter('entete-transparent', 'inc/addons/transparent-header/classes/dynamic-css/dynamic.css.php', [
+		'transparent-header-enable' => $booleen + ['defaut' => 0],
+		'transparent-header-disable-archive' => $booleen + ['defaut' => 1],
+		'transparent-header-disable-404-page' => $booleen,
+		'transparent-header-disable-search-page' => $booleen,
+		'transparent-header-disable-archive-pages' => $booleen,
+		'transparent-header-disable-index' => $booleen,
+		'transparent-header-disable-latest-posts-index' => $booleen + ['defaut' => 1],
+		'transparent-header-disable-page' => $booleen,
+		'transparent-header-disable-posts' => $booleen,
+		'transparent-header-on-devices' => ['type' => 'enum', 'valeurs' => ['desktop', 'mobile', 'both'], 'defaut' => 'both'],
+		'transparent-header-bg-color-responsive' => $couleur_r,
+		'hba-transparent-header-bg-color-responsive' => $couleur_r,
+		'hbb-transparent-header-bg-color-responsive' => $couleur_r,
+		'transparent-header-color-site-title-responsive' => $couleur_r,
+		'transparent-header-color-h-site-title-responsive' => $couleur_r,
+		'transparent-menu-color-responsive' => $couleur_r,
+		'transparent-menu-h-color-responsive' => $couleur_r,
+		'transparent-menu-bg-color-responsive' => $couleur_r,
+		'transparent-submenu-color-responsive' => $couleur_r,
+		'transparent-submenu-h-color-responsive' => $couleur_r,
+		'transparent-submenu-bg-color-responsive' => $couleur_r,
+		'transparent-content-section-text-color-responsive' => $couleur_r,
+		'transparent-content-section-link-color-responsive' => $couleur_r,
+		'transparent-content-section-link-h-color-responsive' => $couleur_r,
+		'transparent-header-main-sep' => ['type' => 'nombre'],
+		'transparent-header-main-sep-color' => $couleur,
+		'transparent-header-default-border' => $booleen + ['defaut' => true],
+	]);
 	return $r;
 }
 
diff --git a/inc/astra_styles_saisie.php b/inc/astra_styles_saisie.php
index d550d89..c37de51 100644
--- a/inc/astra_styles_saisie.php
+++ b/inc/astra_styles_saisie.php
@@ -17,7 +17,7 @@ function astra_styles_sections(): array {
 		'menu_2' => '/^header-menu2-/',
 		'bouton' => '/^(button-|theme-button-|font-[a-z]+-button$)/',
 		'bouton_entete' => '/^header-button1-/',
-		'identite' => '/(site-title|site-tagline|logo-width)/',
+		'identite' => '/^(?!(hba-|hbb-)?transparent-).*(site-title|site-tagline|logo-width)/',
 		'widget_1' => '/^footer-widget-1-/',
 		'widget_2' => '/^footer-widget-2-/',
 		'widget_3' => '/^footer-widget-3-/',
@@ -25,6 +25,7 @@ function astra_styles_sections(): array {
 		'copyright' => '/copyright/',
 		'sociaux_entete' => '/^header-social-1-/',
 		'sociaux_pied' => '/^footer-social-1-/',
+		'entete_transparent' => '/^(hba-|hbb-)?transparent-/',
 	];
 }
 
diff --git a/lang/astra_en.php b/lang/astra_en.php
index 69d7205..2dbf8a7 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -248,6 +248,8 @@ return [
 	'style_font_size_site_title' => 'Site title size',
 	'style_font_weight_button' => 'Font weight',
 	'style_footer_copyright_color' => 'Text color',
+	'style_hba_transparent_header_bg_color_responsive' => 'Above row background',
+	'style_hbb_transparent_header_bg_color_responsive' => 'Below row background',
 	'style_header_color_h_site_title' => 'Site title color on hover',
 	'style_header_color_site_tagline' => 'Tagline color',
 	'style_header_color_site_title' => 'Site title color',
@@ -270,6 +272,31 @@ return [
 	'style_social_space' => 'Space between icons (px)',
 	'style_theme_button_border_group_border_size' => 'Border width (px)',
 	'style_theme_button_padding' => 'Padding',
+	'style_transparent_content_section_link_color_responsive' => 'Free text link color',
+	'style_transparent_content_section_link_h_color_responsive' => 'Free text link color on hover',
+	'style_transparent_content_section_text_color_responsive' => 'Free text color',
+	'style_transparent_header_bg_color_responsive' => 'Primary row background',
+	'style_transparent_header_color_h_site_title_responsive' => 'Site title color on hover',
+	'style_transparent_header_color_site_title_responsive' => 'Site title and tagline color',
+	'style_transparent_header_default_border' => '0 px separator by default (compatibility)',
+	'style_transparent_header_disable_404_page' => 'Disable on the 404 page',
+	'style_transparent_header_disable_archive' => 'Disable on archives, search and 404 page',
+	'style_transparent_header_disable_archive_pages' => 'Disable on sections, keywords and authors',
+	'style_transparent_header_disable_index' => 'Disable on the posts page',
+	'style_transparent_header_disable_latest_posts_index' => 'Disable on the latest posts home page',
+	'style_transparent_header_disable_page' => 'Disable on pages',
+	'style_transparent_header_disable_posts' => 'Disable on articles',
+	'style_transparent_header_disable_search_page' => 'Disable on search',
+	'style_transparent_header_enable' => 'Enable on the whole site',
+	'style_transparent_header_main_sep' => 'Separator width (px)',
+	'style_transparent_header_main_sep_color' => 'Separator color',
+	'style_transparent_header_on_devices' => 'Devices',
+	'style_transparent_menu_bg_color_responsive' => 'Menu background',
+	'style_transparent_menu_color_responsive' => 'Menu link color',
+	'style_transparent_menu_h_color_responsive' => 'Menu link color on hover',
+	'style_transparent_submenu_bg_color_responsive' => 'Submenu background',
+	'style_transparent_submenu_color_responsive' => 'Submenu link color',
+	'style_transparent_submenu_h_color_responsive' => 'Submenu link color on hover',
 	'style_widget_color' => 'Text color',
 	'style_widget_content_font_size' => 'Font size',
 	'style_widget_font_size' => 'Title size',
@@ -281,7 +308,10 @@ return [
 	'styles_couleur_explication' => 'A palette color (<code>var(--ast-global-color-0)</code> to <code>8</code>), hexadecimal or <code>rgba()</code>.',
 	'styles_defaut' => 'Astra setting',
 	'styles_droite' => 'right',
+	'styles_enum_both' => 'Desktop and mobile',
 	'styles_enum_custom' => 'Custom',
+	'styles_enum_desktop' => 'Desktop',
+	'styles_enum_mobile' => 'Mobile',
 	'styles_enum_official' => 'Brand colors',
 	'styles_erreurs_saisie' => 'Some values were refused: correct or clear them.',
 	'styles_espacement' => 'Letter spacing',
@@ -294,6 +324,7 @@ return [
 	'styles_section_bouton' => 'Button',
 	'styles_section_bouton_entete' => 'Header button',
 	'styles_section_copyright' => 'Copyright',
+	'styles_section_entete_transparent' => 'Transparent header',
 	'styles_section_identite' => 'Site identity',
 	'styles_section_menu_1' => 'Primary menu',
 	'styles_section_menu_2' => 'Secondary menu',
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index b61fb3d..33d592b 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -248,6 +248,8 @@ return [
 	'style_font_size_site_title' => 'Taille du nom du site',
 	'style_font_weight_button' => 'Graisse',
 	'style_footer_copyright_color' => 'Couleur du texte',
+	'style_hba_transparent_header_bg_color_responsive' => 'Fond de la rangée haute',
+	'style_hbb_transparent_header_bg_color_responsive' => 'Fond de la rangée basse',
 	'style_header_color_h_site_title' => 'Couleur du nom du site au survol',
 	'style_header_color_site_tagline' => 'Couleur du slogan',
 	'style_header_color_site_title' => 'Couleur du nom du site',
@@ -270,6 +272,31 @@ return [
 	'style_social_space' => 'Espace entre les icônes (px)',
 	'style_theme_button_border_group_border_size' => 'Épaisseur de la bordure (px)',
 	'style_theme_button_padding' => 'Marges intérieures',
+	'style_transparent_content_section_link_color_responsive' => 'Couleur des liens des textes libres',
+	'style_transparent_content_section_link_h_color_responsive' => 'Couleur des liens des textes libres au survol',
+	'style_transparent_content_section_text_color_responsive' => 'Couleur des textes libres',
+	'style_transparent_header_bg_color_responsive' => 'Fond de la rangée principale',
+	'style_transparent_header_color_h_site_title_responsive' => 'Couleur du nom du site au survol',
+	'style_transparent_header_color_site_title_responsive' => 'Couleur du nom du site et du slogan',
+	'style_transparent_header_default_border' => 'Séparateur de 0 px par défaut (compatibilité)',
+	'style_transparent_header_disable_404_page' => 'Désactiver sur la page 404',
+	'style_transparent_header_disable_archive' => 'Désactiver sur les archives, la recherche et la page 404',
+	'style_transparent_header_disable_archive_pages' => 'Désactiver sur les rubriques, mots-clés et auteurs',
+	'style_transparent_header_disable_index' => 'Désactiver sur la page des articles',
+	'style_transparent_header_disable_latest_posts_index' => 'Désactiver sur l’accueil des derniers articles',
+	'style_transparent_header_disable_page' => 'Désactiver sur les pages',
+	'style_transparent_header_disable_posts' => 'Désactiver sur les articles',
+	'style_transparent_header_disable_search_page' => 'Désactiver sur la recherche',
+	'style_transparent_header_enable' => 'Activer sur tout le site',
+	'style_transparent_header_main_sep' => 'Épaisseur du séparateur (px)',
+	'style_transparent_header_main_sep_color' => 'Couleur du séparateur',
+	'style_transparent_header_on_devices' => 'Appareils',
+	'style_transparent_menu_bg_color_responsive' => 'Fond du menu',
+	'style_transparent_menu_color_responsive' => 'Couleur des liens du menu',
+	'style_transparent_menu_h_color_responsive' => 'Couleur des liens du menu au survol',
+	'style_transparent_submenu_bg_color_responsive' => 'Fond des sous-menus',
+	'style_transparent_submenu_color_responsive' => 'Couleur des liens des sous-menus',
+	'style_transparent_submenu_h_color_responsive' => 'Couleur des liens des sous-menus au survol',
 	'style_widget_color' => 'Couleur du texte',
 	'style_widget_content_font_size' => 'Taille du texte',
 	'style_widget_font_size' => 'Taille des titres',
@@ -281,7 +308,10 @@ return [
 	'styles_couleur_explication' => 'Une couleur de la palette (<code>var(--ast-global-color-0)</code> à <code>8</code>), hexadécimale ou <code>rgba()</code>.',
 	'styles_defaut' => 'Réglage d’Astra',
 	'styles_droite' => 'droite',
+	'styles_enum_both' => 'Ordinateur et mobile',
 	'styles_enum_custom' => 'Personnalisées',
+	'styles_enum_desktop' => 'Ordinateur',
+	'styles_enum_mobile' => 'Mobile',
 	'styles_enum_official' => 'Couleurs des marques',
 	'styles_erreurs_saisie' => 'Certaines valeurs sont refusées : corrigez-les ou videz-les.',
 	'styles_espacement' => 'Espacement des lettres',
@@ -294,6 +324,7 @@ return [
 	'styles_section_bouton' => 'Bouton',
 	'styles_section_bouton_entete' => 'Bouton de l’en-tête',
 	'styles_section_copyright' => 'Copyright',
+	'styles_section_entete_transparent' => 'En-tête transparent',
 	'styles_section_identite' => 'Identité du site',
 	'styles_section_menu_1' => 'Menu principal',
 	'styles_section_menu_2' => 'Menu secondaire',
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (80 tests, 1299 assertions)`, intégration `OK (69 tests, 232 assertions)`

- [ ] **Étape 5 : commit**

```bash
git add inc/astra_entete_transparent.php inc/astra_styles.php inc/astra_styles_saisie.php lang/astra_fr.php lang/astra_en.php tests/unit/EnteteTransparentTest.php tests/integration/StylesFormulaireTest.php tests/references/transparent/appliquer.php tests/references/transparent/capturer.sh tests/references/transparent/valeurs/*.json tests/references/transparent/attendus/*.css
git commit -m "En-tête transparent : règle d'activation et CSS d'Astra"
```

### Tâche 2 : réglage par page et gabarits

Réglage `transparent` de `astra/pages/<id>` (`defaut`, `active`, `desactive` ; défaut `defaut`), proposé par le formulaire `editer_astra_page`. `astra_entete_transparent()` choisit le contexte : une page unique suit son réglage, même en accueil statique (le gabarit transmet `accueil=oui`, déjà fourni par `sommaire.html`) ; l'accueil qui liste les derniers articles a son exclusion. La classe `ast-theme-transparent-header` s'insère entre `ast-inherit-site-logo-transparent` et `ast-hfb-header`, comme dans Astra. Chaque gabarit passe `contexte`, `id` (et `accueil`) à `inclure/head`, qui les transmet à `inclure/head-vars` ; le bloc `astra-transparent-inline-css` suit celui des styles et n'est émis que s'il n'est pas vide.

**Fichiers :**
- Modifier : `inc/astra_pages.php`, `formulaires/editer_astra_page.html`, `formulaires/editer_astra_page.php`, `astra_fonctions.php`, `404.html`, `article.html`, `auteur.html`, `mot.html`, `page_unique.html`, `recherche.html`, `rubrique.html`, `inclure/sommaire-articles.html`, `inclure/head.html`, `inclure/head-vars.html`, `lang/astra_fr.php`, `lang/astra_en.php`
- Modifier : `tests/unit/PagesTest.php`, `tests/integration/PagesReglagesTest.php`
- Créer : `tests/integration/EnteteTransparentRenduTest.php`

**Interfaces :**
- Produit : `astra_page_transparents(): array` ; `astra_page_classes_body(int $id, array $reglages, bool $accueil, bool $parente = false, bool $transparent = false): string` ; `astra_entete_transparent(string $type, $id = 0, $accueil = ''): bool` ; `astra_entete_transparent_inline($type, $id = 0, $accueil = ''): string` (filtre `#ENV{contexte}|astra_entete_transparent_inline{#ENV{id},#ENV{accueil}}`).
- Consomme : `astra_entete_transparent_actif()`, `astra_entete_transparent_css()` (tâche 1).

- [ ] **Étape 1 : écrire les tests**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/integration/EnteteTransparentRenduTest.php b/tests/integration/EnteteTransparentRenduTest.php
new file mode 100644
index 0000000..d19c119
--- /dev/null
+++ b/tests/integration/EnteteTransparentRenduTest.php
@@ -0,0 +1,66 @@
+<?php
+
+use PHPUnit\Framework\TestCase;
+
+/**
+ * En-tête transparent dans les gabarits : classe ast-theme-transparent-header du body et bloc
+ * astra-transparent-inline-css du head, seulement sur les pages où il est actif.
+ */
+final class EnteteTransparentRenduTest extends TestCase {
+
+	public static function setUpBeforeClass(): void {
+		require_once __DIR__ . '/PagesGabaritTest.php';
+	}
+
+	protected function tearDown(): void {
+		foreach (['styles', 'pages', 'accueil_type', 'accueil_page'] as $cle) {
+			effacer_config('astra/' . $cle);
+		}
+	}
+
+	/** [classe présente, bloc CSS présent] pour un rendu. */
+	private function transparent(string $html): array {
+		$x = PagesGabaritTest::analyser($html);
+		$classes = explode(' ', $x->query('//body')->item(0)->getAttribute('class'));
+		$style = $x->query('//style[@id="astra-transparent-inline-css"]');
+		return [in_array('ast-theme-transparent-header', $classes, true), $style->length === 1 && strpos($style->item(0)->textContent, '#masthead{position:absolute;') !== false];
+	}
+
+	private function rubrique(): string {
+		return recuperer_fond('rubrique', ['id_rubrique' => (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre=' . sql_quote('10. Jardinage'))]);
+	}
+
+	private function article(): string {
+		return recuperer_fond('article', ['id_article' => (int) sql_getfetsel('id_article', 'spip_articles', 'titre=' . sql_quote('Article du potager'))]);
+	}
+
+	public function testRienSansReglage(): void {
+		$page = PagesGabaritTest::id('notre_jardin');
+		foreach ([recuperer_fond('article', ['id_article' => $page]), $this->article(), $this->rubrique(), recuperer_fond('sommaire', [])] as $html) {
+			$this->assertSame([false, false], $this->transparent($html));
+		}
+	}
+
+	public function testPageActivee(): void {
+		$page = PagesGabaritTest::id('notre_jardin');
+		ecrire_config('astra/pages/' . $page, ['transparent' => 'active']);
+		$this->assertSame([true, true], $this->transparent(recuperer_fond('article', ['id_article' => $page])));
+		$this->assertSame([false, false], $this->transparent(recuperer_fond('article', ['id_article' => PagesGabaritTest::id('mentions')])));
+		// Accueil statique : réglage de la page choisie.
+		ecrire_config('astra/accueil_type', 'page');
+		ecrire_config('astra/accueil_page', $page);
+		$this->assertSame([true, true], $this->transparent(recuperer_fond('sommaire', [])));
+	}
+
+	public function testReglageGlobalEtExclusions(): void {
+		ecrire_config('astra/styles', ['transparent-header-enable' => 1]);
+		$this->assertSame([true, true], $this->transparent($this->article()));
+		$this->assertSame([true, true], $this->transparent(recuperer_fond('article', ['id_article' => PagesGabaritTest::id('mentions')])));
+		// Exclusions par défaut d'Astra : archives et accueil des derniers articles.
+		$this->assertSame([false, false], $this->transparent($this->rubrique()));
+		$this->assertSame([false, false], $this->transparent(recuperer_fond('sommaire', [])));
+		// Une page désactivée l'emporte sur le réglage global.
+		ecrire_config('astra/pages/' . PagesGabaritTest::id('mentions'), ['transparent' => 'desactive']);
+		$this->assertSame([false, false], $this->transparent(recuperer_fond('article', ['id_article' => PagesGabaritTest::id('mentions')])));
+	}
+}
diff --git a/tests/integration/PagesReglagesTest.php b/tests/integration/PagesReglagesTest.php
index 48ed164..3e2f46a 100644
--- a/tests/integration/PagesReglagesTest.php
+++ b/tests/integration/PagesReglagesTest.php
@@ -36,9 +36,10 @@ final class PagesReglagesTest extends TestCase {
 		$GLOBALS['visiteur_session'] = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . $id_auteur);
 	}
 
-	private function poster(string $titre, string $disposition): void {
+	private function poster(string $titre, string $disposition, string $transparent = 'defaut'): void {
 		set_request('affichage_titre', $titre);
 		set_request('disposition', $disposition);
+		set_request('transparent', $transparent);
 	}
 
 	public function testRedacteurAuteurPeutEnregistrer(): void {
@@ -46,10 +47,10 @@ final class PagesReglagesTest extends TestCase {
 		$this->assertFalse(autoriser('configurer', '_astra'), 'Le rédacteur ne peut pas configurer le site');
 		$valeurs = formulaires_editer_astra_page_charger_dist(self::$page);
 		$this->assertTrue($valeurs['editable']);
-		$this->poster('masquer', 'pleine');
+		$this->poster('masquer', 'pleine', 'active');
 		$this->assertSame([], formulaires_editer_astra_page_verifier_dist(self::$page));
 		$this->assertArrayHasKey('message_ok', formulaires_editer_astra_page_traiter_dist(self::$page));
-		$this->assertSame(['titre' => 'masquer', 'disposition' => 'pleine'], lire_config('astra/pages/' . self::$page));
+		$this->assertSame(['titre' => 'masquer', 'disposition' => 'pleine', 'transparent' => 'active'], lire_config('astra/pages/' . self::$page));
 	}
 
 	public function testRedacteurNonAuteurRefuse(): void {
@@ -68,10 +69,11 @@ final class PagesReglagesTest extends TestCase {
 
 	public function testValeursInvalides(): void {
 		$this->connecter(self::$redacteur);
-		$this->poster('cache', 'immense');
+		$this->poster('cache', 'immense', 'enabled');
 		$erreurs = formulaires_editer_astra_page_verifier_dist(self::$page);
 		$this->assertArrayHasKey('affichage_titre', $erreurs);
 		$this->assertArrayHasKey('disposition', $erreurs);
+		$this->assertArrayHasKey('transparent', $erreurs);
 	}
 
 	/** Page jetable avec des réglages, pour les tests de suppression. */
diff --git a/tests/unit/PagesTest.php b/tests/unit/PagesTest.php
index d5dc9ee..2799eac 100644
--- a/tests/unit/PagesTest.php
+++ b/tests/unit/PagesTest.php
@@ -7,9 +7,9 @@ require_once dirname(__DIR__, 2) . '/inc/astra_pages.php';
 final class PagesTest extends TestCase {
 
 	public function testNormaliser(): void {
-		$this->assertSame(['titre' => 'afficher', 'disposition' => 'defaut'], astra_page_normaliser(null));
-		$this->assertSame(['titre' => 'masquer', 'disposition' => 'pleine'], astra_page_normaliser(['titre' => 'masquer', 'disposition' => 'pleine', 'autre' => 1]));
-		$this->assertSame(['titre' => 'afficher', 'disposition' => 'defaut'], astra_page_normaliser(['titre' => 'x', 'disposition' => 'y']));
+		$this->assertSame(['titre' => 'afficher', 'disposition' => 'defaut', 'transparent' => 'defaut'], astra_page_normaliser(null));
+		$this->assertSame(['titre' => 'masquer', 'disposition' => 'pleine', 'transparent' => 'active'], astra_page_normaliser(['titre' => 'masquer', 'disposition' => 'pleine', 'transparent' => 'active', 'autre' => 1]));
+		$this->assertSame(['titre' => 'afficher', 'disposition' => 'defaut', 'transparent' => 'defaut'], astra_page_normaliser(['titre' => 'x', 'disposition' => 'y', 'transparent' => 'enabled']));
 	}
 
 	public function testDispositions(): void {
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 1 échec (`PagesTest::testNormaliser`) ;
- intégration : 4 échecs (`EnteteTransparentRenduTest::testPageActivee`, `testReglageGlobalEtExclusions`, `PagesReglagesTest::testRedacteurAuteurPeutEnregistrer`, `testValeursInvalides`) ; `EnteteTransparentRenduTest::testRienSansReglage` passe déjà.

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/404.html b/404.html
index a0a4728..bef6ced 100644
--- a/404.html
+++ b/404.html
@@ -8,7 +8,7 @@
 <head>
 <title><:astra:page_introuvable:> – [(#NOM_SITE_SPIP|textebrut)]</title>
 <meta name="robots" content="noindex" />
-<INCLURE{fond=inclure/head} />
+<INCLURE{fond=inclure/head}{contexte=404} />
 </head>
 <body itemtype="https://schema.org/WebPage" itemscope="itemscope" class="[(#VAL{404}|astra_body_class)]">
 <INCLURE{fond=inclure/header}{type_page=404} />
diff --git a/article.html b/article.html
index 15849ba..ea428da 100644
--- a/article.html
+++ b/article.html
@@ -9,7 +9,7 @@
 <head>
 <title>[(#TITRE|supprimer_numero|textebrut)] – [(#NOM_SITE_SPIP|textebrut)]</title>
 [<meta name="description" content="(#INTRODUCTION{150}|attribut_html)" />]
-<INCLURE{fond=inclure/head}{type_page=article} />
+<INCLURE{fond=inclure/head}{type_page=article}{contexte=article}{id=#ID_ARTICLE} />
 <BOUCLE_commentaires_css(CONDITION){si #PARAMETRES_FORUM|oui|sinon{#ID_ARTICLE|astra_a_commentaires}}><link rel="stylesheet" id="astra-commentaires-css" href="[(#CHEMIN{css/astra-commentaires.css}|timestamp)]" media="all" /></BOUCLE_commentaires_css>
 </head>
 <body itemtype="https://schema.org/Blog" itemscope="itemscope" class="[(#VAL{article}|astra_body_class{#ID_ARTICLE})]">
diff --git a/astra_fonctions.php b/astra_fonctions.php
index 31062cf..4e22a3c 100644
--- a/astra_fonctions.php
+++ b/astra_fonctions.php
@@ -18,6 +18,7 @@ include_once __DIR__ . '/inc/astra_hfb.php';
 include_once __DIR__ . '/inc/astra_hfb_saisie.php';
 include_once __DIR__ . '/inc/astra_pages.php';
 include_once __DIR__ . '/inc/astra_styles_saisie.php';
+include_once __DIR__ . '/inc/astra_entete_transparent.php';
 
 /** Réglages effectifs d'une page unique : `#ID_ARTICLE|astra_page_reglages`. */
 function filtre_astra_page_reglages_dist($id_article): array {
@@ -150,6 +151,34 @@ function astra_styles_inline(): string {
 	return astra_styles_css(is_array($styles) ? $styles : [], is_array($hfb) ? $hfb : []);
 }
 
+/**
+ * En-tête transparent de la page (astra_entete_transparent_actif()) : $type et $id comme pour
+ * astra_body_class() ; une page unique suit son réglage, même en accueil statique ; l'accueil qui
+ * liste les derniers articles a son exclusion propre.
+ */
+function astra_entete_transparent(string $type, $id = 0, $accueil = ''): bool {
+	include_spip('inc/config');
+	$styles = lire_config('astra/styles');
+	$styles = is_array($styles) ? $styles : [];
+	if ($type === 'page') {
+		return astra_entete_transparent_actif($styles, 'page', astra_page_reglages((int) $id)['transparent']);
+	}
+	return astra_entete_transparent_actif($styles, $type === 'sommaire' ? 'accueil_articles' : $type);
+}
+
+/**
+ * CSS de l'en-tête transparent, vide si la page ne l'a pas :
+ * `#ENV{contexte}|astra_entete_transparent_inline{#ENV{id},#ENV{accueil}}`.
+ */
+function astra_entete_transparent_inline($type, $id = 0, $accueil = ''): string {
+	if (!astra_entete_transparent((string) $type, $id, $accueil)) {
+		return '';
+	}
+	$styles = lire_config('astra/styles');
+	$hfb = lire_config('astra/hfb');
+	return astra_entete_transparent_css(is_array($styles) ? $styles : [], is_array($hfb) ? $hfb : []);
+}
+
 /**
  * Mode du menu mobile : 'dropdown' ou 'off-canvas'.
  */
@@ -270,7 +299,7 @@ function astra_barre_a(string $type, string $position): bool {
 function astra_body_class(string $type, $id = 0, $accueil = ''): string {
 	$id = (int) $id;
 	if ($type === 'page') {
-		return astra_page_classes_body($id, astra_page_reglages($id), $accueil === 'oui', astra_page_a_sous_pages($id));
+		return astra_page_classes_body($id, astra_page_reglages($id), $accueil === 'oui', astra_page_a_sous_pages($id), astra_entete_transparent('page', $id, $accueil));
 	}
 	$disposition = astra_disposition($type);
 	$etroit = ($type === 'article' && astra_conf('disposition_articles') === 'narrow');
@@ -321,6 +350,9 @@ function astra_body_class(string $type, $id = 0, $accueil = ''): string {
 		$classes[] = 'ast-single-post';
 	}
 	$classes[] = 'ast-inherit-site-logo-transparent';
+	if (astra_entete_transparent($type, $id)) {
+		$classes[] = 'ast-theme-transparent-header';
+	}
 	$classes[] = 'ast-hfb-header';
 	if ($type === 'article') {
 		$classes[] = 'ast-normal-title-enabled';
diff --git a/auteur.html b/auteur.html
index 9d6b20c..5e43139 100644
--- a/auteur.html
+++ b/auteur.html
@@ -8,7 +8,7 @@
 <head>
 <title>[(#NOM|textebrut)] – [(#NOM_SITE_SPIP|textebrut)]</title>
 [<meta name="description" content="(#BIO|couper{150}|attribut_html)" />]
-<INCLURE{fond=inclure/head} />
+<INCLURE{fond=inclure/head}{contexte=auteur}{id=#ID_AUTEUR} />
 </head>
 <body itemtype="https://schema.org/ProfilePage" itemscope="itemscope" class="[(#VAL{auteur}|astra_body_class{#ID_AUTEUR})]">
 <INCLURE{fond=inclure/header}{type_page=auteur} />
diff --git a/formulaires/editer_astra_page.html b/formulaires/editer_astra_page.html
index b6b8674..d8c82ae 100644
--- a/formulaires/editer_astra_page.html
+++ b/formulaires/editer_astra_page.html
@@ -22,6 +22,15 @@
 					</BOUCLE_dispositions>
 				</select>
 			</div>
+			<div class="editer editer_transparent[ (#ENV*{erreurs/transparent}|oui)erreur]">
+				<label for="transparent"><:astra:page_transparent:></label>
+				[<span class="erreur_message">(#ENV*{erreurs/transparent})</span>]
+				<select name="transparent" id="transparent">
+					<BOUCLE_transparents(DATA){source table, #ENV{transparents}}>
+					<option value="#VALEUR"[(#VALEUR|=={#ENV{transparent}}|oui)selected="selected"]>[(#VAL{astra:page_transparent_}|concat{#VALEUR}|_T)]</option>
+					</BOUCLE_transparents>
+				</select>
+			</div>
 		</div>
 		<p class="boutons"><input type="submit" class="btn submit" value="<:bouton_enregistrer:>" /></p>
 	</div></form>
diff --git a/formulaires/editer_astra_page.php b/formulaires/editer_astra_page.php
index e230f88..b2bc13a 100644
--- a/formulaires/editer_astra_page.php
+++ b/formulaires/editer_astra_page.php
@@ -1,6 +1,6 @@
 <?php
 /**
- * Astra pour SPIP — réglages d'affichage d'une page unique (titre, disposition).
+ * Astra pour SPIP — réglages d'affichage d'une page unique (titre, disposition, en-tête transparent).
  *
  * Nommé editer_ et non configurer_ : SPIP impose l'autorisation « configurer » à tout
  * formulaire configurer_* (ecrire/inc/cvt_configurer.php). Ici, ce sont les droits de
@@ -26,6 +26,8 @@ function formulaires_editer_astra_page_charger_dist($id_article) {
 		'affichage_titre' => $r['titre'],
 		'disposition' => $r['disposition'],
 		'dispositions' => astra_page_dispositions(),
+		'transparent' => $r['transparent'],
+		'transparents' => astra_page_transparents(),
 		'editable' => autoriser('modifier', 'article', $id_article),
 	];
 }
@@ -38,6 +40,9 @@ function formulaires_editer_astra_page_verifier_dist($id_article) {
 	if (!in_array(_request('disposition'), astra_page_dispositions(), true)) {
 		$erreurs['disposition'] = _T('astra:page_erreur_choix');
 	}
+	if (!in_array(_request('transparent'), astra_page_transparents(), true)) {
+		$erreurs['transparent'] = _T('astra:page_erreur_choix');
+	}
 	return $erreurs;
 }
 
@@ -50,6 +55,7 @@ function formulaires_editer_astra_page_traiter_dist($id_article) {
 	ecrire_config('astra/pages/' . $id_article, astra_page_normaliser([
 		'titre' => _request('affichage_titre'),
 		'disposition' => _request('disposition'),
+		'transparent' => _request('transparent'),
 	]));
 	include_spip('inc/invalideur');
 	suivre_invalideur("id='article/$id_article'");
diff --git a/inc/astra_pages.php b/inc/astra_pages.php
index c3a0d6a..4409dd3 100644
--- a/inc/astra_pages.php
+++ b/inc/astra_pages.php
@@ -8,7 +8,12 @@
 
 /** Réglages d'une page sans configuration propre. */
 function astra_page_reglages_defaut(): array {
-	return ['titre' => 'afficher', 'disposition' => 'defaut'];
+	return ['titre' => 'afficher', 'disposition' => 'defaut', 'transparent' => 'defaut'];
+}
+
+/** En-tête transparent d'une page (theme-transparent-header-meta d'Astra : default, enabled, disabled). */
+function astra_page_transparents(): array {
+	return ['defaut', 'active', 'desactive'];
 }
 
 /** Dispositions proposées pour une page. */
@@ -26,6 +31,9 @@ function astra_page_normaliser($reglages): array {
 	if (in_array($reglages['disposition'] ?? '', astra_page_dispositions(), true)) {
 		$r['disposition'] = $reglages['disposition'];
 	}
+	if (in_array($reglages['transparent'] ?? '', astra_page_transparents(), true)) {
+		$r['transparent'] = $reglages['transparent'];
+	}
 	return $r;
 }
 
@@ -45,7 +53,7 @@ function astra_page_classes_disposition(string $disposition): array {
  * Les pages n'ont pas de barre latérale par défaut dans Astra ; `page-parent` marque une page
  * qui a des sous-pages.
  */
-function astra_page_classes_body(int $id, array $reglages, bool $accueil, bool $parente = false): string {
+function astra_page_classes_body(int $id, array $reglages, bool $accueil, bool $parente = false, bool $transparent = false): string {
 	$classes = $accueil ? ['home'] : [];
 	array_push($classes, 'page-template-default', 'page', 'page-id-' . $id);
 	if ($parente) {
@@ -53,7 +61,11 @@ function astra_page_classes_body(int $id, array $reglages, bool $accueil, bool $
 	}
 	$classes[] = 'ast-desktop';
 	$classes = array_merge($classes, astra_page_classes_disposition($reglages['disposition']));
-	array_push($classes, 'ast-no-sidebar', 'astra-' . (defined('_ASTRA_VERSION_THEME') ? _ASTRA_VERSION_THEME : '4.11.0'), 'ast-single-post', 'ast-inherit-site-logo-transparent', 'ast-hfb-header');
+	array_push($classes, 'ast-no-sidebar', 'astra-' . (defined('_ASTRA_VERSION_THEME') ? _ASTRA_VERSION_THEME : '4.11.0'), 'ast-single-post', 'ast-inherit-site-logo-transparent');
+	if ($transparent) {
+		$classes[] = 'ast-theme-transparent-header';
+	}
+	$classes[] = 'ast-hfb-header';
 	if ($reglages['titre'] === 'afficher') {
 		$classes[] = 'ast-normal-title-enabled';
 	}
diff --git a/inclure/head-vars.html b/inclure/head-vars.html
index acf19fd..cd92ad3 100644
--- a/inclure/head-vars.html
+++ b/inclure/head-vars.html
@@ -1,7 +1,8 @@
 [(#REM)
 
 	Variables CSS d'Astra pilotées par la configuration du thème
-	(palette globale, largeurs de conteneur, polices)
+	(palette globale, largeurs de conteneur, polices), et CSS de l'en-tête transparent
+	(paramètres contexte, id, accueil).
 
 ][<style id="astra-polices-inline-css">
 (#VAL|astra_polices_inline)
@@ -16,4 +17,6 @@
 (#VAL|astra_hfb_css)
 </style>][<style id="astra-styles-inline-css">
 (#VAL|astra_styles_inline)
+</style>][<style id="astra-transparent-inline-css">
+(#ENV{contexte}|astra_entete_transparent_inline{#ENV{id},#ENV{accueil}})
 </style>]
diff --git a/inclure/head.html b/inclure/head.html
index d9629be..8143fbf 100644
--- a/inclure/head.html
+++ b/inclure/head.html
@@ -1,7 +1,8 @@
 [(#REM)
 
 	En-tête HTML commun : feuilles de style Astra et variables issues de la configuration.
-	Paramètre : type_page = article pour le CSS propre aux articles (css/astra-article.css).
+	Paramètres : type_page = article pour le CSS propre aux articles (css/astra-article.css) ;
+	contexte, id, accueil : page servie, comme pour astra_body_class (en-tête transparent).
 
 ]<meta charset="#CHARSET" />
 <meta name="viewport" content="width=device-width, initial-scale=1" />
@@ -14,6 +15,6 @@
 ]<link rel="stylesheet" id="astra-blocs-css" href="[(#CHEMIN{css/astra-blocs.css}|timestamp)]" media="all" />
 <link rel="stylesheet" id="astra-hfb-css" href="[(#CHEMIN{css/astra-hfb.css}|timestamp)]" media="all" />
 <link rel="stylesheet" id="astra-pages-css" href="[(#CHEMIN{css/astra-pages.css}|timestamp)]" media="all" />
-<INCLURE{fond=inclure/head-vars} />
+<INCLURE{fond=inclure/head-vars}{contexte}{id}{accueil} />
 <link rel="stylesheet" id="astra-spip-css" href="[(#CHEMIN{css/astra-spip.css}|timestamp)]" media="all" />
 #INSERT_HEAD
diff --git a/inclure/sommaire-articles.html b/inclure/sommaire-articles.html
index c8708d5..a6c6bd8 100644
--- a/inclure/sommaire-articles.html
+++ b/inclure/sommaire-articles.html
@@ -8,7 +8,7 @@
 <head>
 <title>[(#NOM_SITE_SPIP|textebrut)][ – (#SLOGAN_SITE_SPIP|textebrut)]</title>
 [<meta name="description" content="(#DESCRIPTIF_SITE_SPIP|couper{150}|attribut_html)" />]
-<INCLURE{fond=inclure/head} />
+<INCLURE{fond=inclure/head}{contexte=sommaire} />
 </head>
 <body itemtype="https://schema.org/Blog" itemscope="itemscope" class="[(#VAL{sommaire}|astra_body_class)]">
 <INCLURE{fond=inclure/header}{type_page=sommaire} />
diff --git a/lang/astra_en.php b/lang/astra_en.php
index 2dbf8a7..52502b4 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -199,6 +199,10 @@ return [
 	'page_titre_afficher' => 'Show',
 	'page_titre_masquer' => 'Hide',
 	'page_introuvable' => 'Page not found',
+	'page_transparent' => 'Transparent header',
+	'page_transparent_active' => 'Enabled',
+	'page_transparent_defaut' => 'Site setting',
+	'page_transparent_desactive' => 'Disabled',
 	'pagination' => 'Post pagination',
 	'panneau_fermer' => 'Close menu',
 	'par' => 'By',
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index 33d592b..8072640 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -199,6 +199,10 @@ return [
 	'page_titre_afficher' => 'Afficher',
 	'page_titre_masquer' => 'Masquer',
 	'page_introuvable' => 'Page introuvable',
+	'page_transparent' => 'En-tête transparent',
+	'page_transparent_active' => 'Activé',
+	'page_transparent_defaut' => 'Réglage du site',
+	'page_transparent_desactive' => 'Désactivé',
 	'pagination' => 'Pagination des articles',
 	'panneau_fermer' => 'Fermer le menu',
 	'par' => 'Par',
diff --git a/mot.html b/mot.html
index 8914694..98a8a30 100644
--- a/mot.html
+++ b/mot.html
@@ -8,7 +8,7 @@
 <head>
 <title>[(#TITRE|supprimer_numero|textebrut)] – [(#NOM_SITE_SPIP|textebrut)]</title>
 [<meta name="description" content="(#DESCRIPTIF|sinon{#TEXTE}|couper{150}|attribut_html)" />]
-<INCLURE{fond=inclure/head} />
+<INCLURE{fond=inclure/head}{contexte=mot}{id=#ID_MOT} />
 </head>
 <body itemtype="https://schema.org/Blog" itemscope="itemscope" class="[(#VAL{mot}|astra_body_class{#ID_MOT})]">
 <INCLURE{fond=inclure/header}{type_page=mot} />
diff --git a/page_unique.html b/page_unique.html
index f2ae838..ff18038 100644
--- a/page_unique.html
+++ b/page_unique.html
@@ -14,7 +14,7 @@
 <title>[(#TITRE|supprimer_numero|textebrut)] – [(#NOM_SITE_SPIP|textebrut)]</title>
 [<meta name="description" content="(#INTRODUCTION{150}|attribut_html)" />]
 [(#ENV{accueil}|!={oui}|et{#ID_ARTICLE|=={#VAL|astra_accueil_page}}|oui)<link rel="canonical" href="#URL_SITE_SPIP/" />]
-<INCLURE{fond=inclure/head} />
+<INCLURE{fond=inclure/head}{contexte=page}{id=#ID_ARTICLE}{accueil} />
 <BOUCLE_commentaires_css(CONDITION){si #PARAMETRES_FORUM|oui|sinon{#ID_ARTICLE|astra_a_commentaires}}><link rel="stylesheet" id="astra-commentaires-css" href="[(#CHEMIN{css/astra-commentaires.css}|timestamp)]" media="all" /></BOUCLE_commentaires_css>
 </head>
 <body itemtype="https://schema.org/WebPage" itemscope="itemscope" class="[(#VAL{page}|astra_body_class{#ID_ARTICLE,#ENV{accueil}})]">
diff --git a/recherche.html b/recherche.html
index d9128c5..a8339d7 100644
--- a/recherche.html
+++ b/recherche.html
@@ -7,7 +7,7 @@
 <head>
 <title>[(#VAL{astra:resultats_recherche}|_T{#ARRAY{recherche,#ENV{recherche}}}|textebrut)] – [(#NOM_SITE_SPIP|textebrut)]</title>
 <meta name="robots" content="noindex" />
-<INCLURE{fond=inclure/head} />
+<INCLURE{fond=inclure/head}{contexte=recherche} />
 </head>
 <body itemtype="https://schema.org/SearchResultsPage" itemscope="itemscope" class="[(#VAL{recherche}|astra_body_class)]">
 <INCLURE{fond=inclure/header}{type_page=recherche} />
diff --git a/rubrique.html b/rubrique.html
index 9b41e9e..f9b0432 100644
--- a/rubrique.html
+++ b/rubrique.html
@@ -8,7 +8,7 @@
 <head>
 <title>[(#TITRE|supprimer_numero|textebrut)] – [(#NOM_SITE_SPIP|textebrut)]</title>
 [<meta name="description" content="(#DESCRIPTIF|sinon{#TEXTE}|couper{150}|attribut_html)" />]
-<INCLURE{fond=inclure/head} />
+<INCLURE{fond=inclure/head}{contexte=rubrique}{id=#ID_RUBRIQUE} />
 </head>
 <body itemtype="https://schema.org/Blog" itemscope="itemscope" class="[(#VAL{rubrique}|astra_body_class{#ID_RUBRIQUE})]">
 <INCLURE{fond=inclure/header}{type_page=rubrique}{id_rubrique}{id_secteur} />
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (80 tests, 1299 assertions)`, intégration `OK (72 tests, 245 assertions)`

Rendu sans réglage inchangé : `php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414` (références WordPress capturées par `tests/references/capturer.sh`) : tout identique.

- [ ] **Étape 5 : commit**

```bash
git add inc/astra_pages.php formulaires/editer_astra_page.html formulaires/editer_astra_page.php astra_fonctions.php 404.html article.html auteur.html mot.html page_unique.html recherche.html rubrique.html inclure/sommaire-articles.html inclure/head.html inclure/head-vars.html lang/astra_fr.php lang/astra_en.php tests/unit/PagesTest.php tests/integration/PagesReglagesTest.php tests/integration/EnteteTransparentRenduTest.php
git commit -m "En-tête transparent : réglage par page, classe du body et CSS dans les gabarits"
```

### Tâche 3 : références visuelles et documentation

**Fichiers :**
- Créer : `tests/references/wp/R16-transparent-page.{php,json}`, `tests/references/wp/R17-transparent-global.{php,json}`
- Modifier : `tests/references/wp/commun.php` (`ref_pages()` retire la méta `theme-transparent-header-meta`), `tests/comparaison/configurations.php`, `tests/comparaison/comparer_captures.sh`, `README.md`, `CREDITS.md`, `tests/references/README.md`

- [ ] **Étape 1 : appliquer**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/CREDITS.md b/CREDITS.md
index 8dd7e9b..1f906b1 100644
--- a/CREDITS.md
+++ b/CREDITS.md
@@ -30,7 +30,8 @@ et le texte de la GPL v2 dans [licenses/GPL-2.0-or-later.txt](licenses/GPL-2.0-o
 | Gabarits `header.php`, `footer.php`, `page.php`, `single.php`, `archive.php`, `search.php`, `404.php`, `searchform.php`, `template-parts/` et composants du builder | Squelettes à la racine, `inclure/`, `inclure/hfb/`, `inclure/widgets/`, `formulaires/recherche.html`, `modeles/pagination_astra.html` | Reproduction et adaptation du balisage, des classes, des attributs et de la structure ; les requêtes et appels WordPress sont remplacés par les boucles, balises et formulaires SPIP |
 | Options, palette et organisation du builder | `astra_fonctions.php`, `inc/astra_hfb.php`, formulaires de configuration | Transposition des valeurs par défaut et des conventions Astra ; interface et stockage adaptés à SPIP |
 | Styles du Customizer : `inc/class-astra-dynamic-css.php` (bouton, identité), `inc/builder/type/header/menu/dynamic-css/dynamic.css.php`, `inc/builder/type/base/dynamic-css/{button,widget,social}/class-astra-*-component-dynamic-css.php`, `inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php` | `inc/astra_styles.php`, `inc/styles/*.php` | Transposition des règles ordinateur : sélecteurs (ceux du bouton global copiés à l'identique), ordre, conditions d'émission et valeurs par défaut ; écriture comme `astra_parse_css()` et `trim_css()` |
-| CSS dynamique rendu par WordPress avec les valeurs du modèle Garden Maintenance | `tests/references/styles/attendus/*.css` | Extraits de la sortie d'Astra 4.11.0 (GPL v2 ou ultérieure), capturés par `tests/references/styles/capturer.sh` pour les tests d'équivalence |
+| En-tête transparent : `inc/addons/transparent-header/classes/class-astra-ext-transparent-header-markup.php` (`is_transparent_header()`), `classes/dynamic-css/dynamic.css.php`, partie « Transparent Header » d'`inc/class-astra-dynamic-css.php` | `inc/astra_entete_transparent.php` | Transposition de la règle d'activation aux pages SPIP et des règles CSS : sélecteurs copiés à l'identique, media queries, ordre et conditions d'émission |
+| CSS dynamique rendu par WordPress avec les valeurs du modèle Garden Maintenance | `tests/references/styles/attendus/*.css`, `tests/references/transparent/attendus/*.css` | Extraits de la sortie d'Astra 4.11.0 (GPL v2 ou ultérieure), capturés par `tests/references/styles/capturer.sh` et `tests/references/transparent/capturer.sh` pour les tests d'équivalence |
 | Règles Astra appliquées aux contenus | `css/astra-spip.css` | Adaptations aux éléments produits par SPIP ; les reprises de règles WordPress/Astra sont signalées dans les commentaires |
 
 Notices de paternité amont conservées :
diff --git a/README.md b/README.md
index 82560c4..9cae8ca 100644
--- a/README.md
+++ b/README.md
@@ -79,9 +79,9 @@ rubrique s'affichent avec le gabarit de page d'Astra (`page.php`, `content-page.
   afficher les derniers articles (défaut) ou une page unique publiée, comme le réglage « Page
   d'accueil » de WordPress. Cette page porte alors la classe `home` et son adresse propre
   renvoie à l'accueil (lien `canonical`).
-- **Réglages par page** : un bloc de la fiche de chaque page permet de masquer le titre et de
-  choisir la disposition du contenu (celle des pages, normale, étroite ou pleine largeur), comme
-  les réglages de page d'Astra. Ils sont enregistrés dans la méta `astra/pages/<id_article>` et
+- **Réglages par page** : un bloc de la fiche de chaque page permet de masquer le titre, de
+  choisir la disposition du contenu (celle des pages, normale, étroite ou pleine largeur) et
+  d'activer ou désactiver l'en-tête transparent, comme les réglages de page d'Astra. Ils sont enregistrés dans la méta `astra/pages/<id_article>` et
   supprimés avec la page.
 
 Pages et a2a sont déclarés en `<utilise>` : le thème fonctionne sans eux.
@@ -130,6 +130,7 @@ avec leur effet et la propriété vérifiée, dans
 - Affichage des auteurs, des extraits, du lien « Accueil » et du bouton de retour en haut.
 - Neuvième couleur de la palette : hexadécimale ou `rgba()`, comme dans Astra.
 - **Couleurs et typographie** (Configuration › Squelettes) : réglages de style du Customizer d'Astra, sous leurs noms d'options (`header-menu1-color-responsive`, `button-radius-fields`…) — menus, bouton, bouton de l'en-tête, identité du site (couleurs, tailles, affichage du nom et du slogan, largeur du logo), widgets et copyright du pied, icônes sociales. Les valeurs d'un `site.json` de modèle Astra se reprennent telles quelles ; seule la valeur ordinateur est appliquée, les valeurs tablette et mobile sont conservées. Sans réglage, rien ne change.
+- **En-tête transparent** (même formulaire) : l'en-tête se superpose au début du contenu, avec ses propres couleurs, sur tout le site ou page par page, avec les exclusions d'Astra (archives, recherche, 404, accueil des derniers articles, pages, articles) et la restriction par appareil ; règles d'Astra 4.11.0 sur les trois appareils (`inc/astra_entete_transparent.php`). Logo propre à l'en-tête transparent : non repris.
 - Texte du pied de page (raccourcis SPIP, marqueurs `[annee]` et `[site]`).
 
 ## Tests
diff --git a/tests/comparaison/comparer_captures.sh b/tests/comparaison/comparer_captures.sh
index c9b0a70..532d706 100755
--- a/tests/comparaison/comparer_captures.sh
+++ b/tests/comparaison/comparer_captures.sh
@@ -39,7 +39,9 @@ for conf in "${CONFS[@]}"; do
 	if [ -n "$rubrique" ]; then
 		chemin_spip=/$( (cd "$RACINE" && $SPIP_CLI php:eval "include_spip('inc/filtres'); echo generer_objet_url((int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre LIKE ' . sql_quote('%$rubrique')), 'rubrique', '', '', true);") | sed -E 's#^https?://[^/]+/?##')
 	fi
+	# Pages uniques (Pn, et toute référence qui affiche une page) : contenu comparé aussi.
 	case $conf in P*) contenu=contenu ;; esac
+	[ -n "$identifiant" ] && contenu=contenu
 	NODE_PATH="$PW/node_modules" node "$ICI/comparer_captures.cjs" "$URL_WP$chemin_wp" "$URL_SPIP$chemin_spip" "$SORTIES/$conf" "$menu" "$contenu" "$ICI/../references/wp/$conf.json" || code=1
 done
 
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 1c8c53a..395d51d 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -112,6 +112,16 @@ function astra_configurations_reference(): array {
 			'astra' => ['police_texte' => "'Raleway', sans-serif", 'police_titres' => "'Poppins', sans-serif"],
 			'meta' => ['slogan_site' => 'Un site propulsé par SPIP'],
 		]),
+		'R16-transparent-page' => [
+			'page' => 'notre_jardin',
+			'pages' => ['notre_jardin' => ['transparent' => 'active']],
+			'styles' => json_decode(file_get_contents(dirname(__DIR__) . '/references/transparent/valeurs/garden.json'), true),
+			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
+		],
+		'R17-transparent-global' => [
+			'rubrique' => 'Jardinage',
+			'styles' => ['transparent-header-enable' => 1] + json_decode(file_get_contents(dirname(__DIR__) . '/references/transparent/valeurs/garden.json'), true),
+		],
 		'P1-page' => [
 			'page' => 'notre_jardin',
 			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
diff --git a/tests/references/README.md b/tests/references/README.md
index 151c8f0..f926b9b 100644
--- a/tests/references/README.md
+++ b/tests/references/README.md
@@ -73,6 +73,8 @@ Vérifié le 2026-10-22 : HTML et CSS identiques.
 | R9-pied-couleurs | Même placement ; couleurs et tailles des quatre widgets et du copyright |
 | R10-sociaux | Même placement ; taille, espacement et couleurs des icônes sociales du pied |
 | R11-combinee | Toutes les options précédentes, police du texte Raleway et des titres Poppins (cascade de plusieurs réglages sur le même bouton et le même menu) |
+| R16-transparent-page | Page « Notre jardin » avec l'en-tête transparent activé par la page (méta `theme-transparent-header-meta` à `enabled`), réglage global désactivé, valeurs de Garden (`transparent/valeurs/garden.json`) ; contenu, classes du `body`, position de l'en-tête et styles calculés comparés |
+| R17-transparent-global | En-tête transparent sur tout le site, page de la catégorie Jardinage : archive exclue par défaut, en-tête normal |
 | P1-page | Page « Notre jardin » (`/?pagename=notre-jardin`), menu de pages à deux niveaux (`ref_menu_pages()`) |
 | P2-accueil | Même menu, page « Notre jardin » en page d'accueil statique (`/`) |
 | P3-page-pleine | Page « Plein écran », titre désactivé, conteneur `full-width-container` |
@@ -117,6 +119,24 @@ Non couvert dans cette version (valeurs conservées, aucun CSS) : valeurs tablet
 fonds de type `image` ou `gradient` ; fond, fond au survol et rayon des icônes sociales
 (`*-social-1-bg-color`, `-bg-h-color`, `-radius-fields`), absents des valeurs du modèle.
 
+## En-tête transparent : attendus
+
+`tests/references/transparent/` fixe le CSS qu'Astra 4.11.0 ajoute sur une page à en-tête
+transparent (page « Notre jardin », méta de page `enabled`) :
+
+- `appliquer.php` : état P1, puis les options de `valeurs/<variante>.json` (`VARIANTE=…`) ;
+- `capturer.sh` : `WP=… tests/references/transparent/capturer.sh` compare chaque variante à la
+  même page sans en-tête transparent et écrit `attendus/<variante>.css` (règles ajoutées ou
+  modifiées) et `attendus/<variante>.retirees.css` (règles qu'Astra n'émet plus) ;
+- variantes : `garden` (valeurs du modèle), `ordinateur` et `mobile` (restriction par appareil,
+  séparateur), `couleurs` (toutes les couleurs, valeurs tablette et mobile différentes) ;
+- `attendus/neutralisations.css` : règle qu'Astra SPIP émet en plus pour défaire la marge de
+  60 px de `#primary` que `css/astra-dynamic.css` contient et qu'Astra n'émet pas sur une page à
+  en-tête transparent.
+
+`tests/unit/EnteteTransparentTest.php` vérifie l'égalité des règles et que chaque règle retirée
+est neutralisée.
+
 ## Relevés pour le portage
 
 ### Répartitions des colonnes du pied
diff --git a/tests/references/wp/R16-transparent-page.json b/tests/references/wp/R16-transparent-page.json
new file mode 100644
index 0000000..d8c3afa
--- /dev/null
+++ b/tests/references/wp/R16-transparent-page.json
@@ -0,0 +1,39 @@
+{
+	"chemin": "/?pagename=notre-jardin",
+	"ordinateur": [
+		"article.page .entry-title",
+		".ast-builder-menu-1 .current-menu-item"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		"body.ast-theme-transparent-header"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": "#masthead",
+			"proprietes": ["position", "left", "right"]
+		},
+		{
+			"selecteur": ".ast-main-header-wrap .main-header-bar",
+			"proprietes": ["background-color", "border-bottom-width", "border-bottom-style"]
+		},
+		{
+			"selecteur": ".site-title a",
+			"proprietes": ["color"]
+		},
+		{
+			"selecteur": "#primary",
+			"proprietes": ["margin-top", "margin-bottom", "padding-top"]
+		},
+		{
+			"selecteur": "article.page .entry-title",
+			"proprietes": ["margin-top"]
+		}
+	]
+}
diff --git a/tests/references/wp/R16-transparent-page.php b/tests/references/wp/R16-transparent-page.php
new file mode 100644
index 0000000..50aed96
--- /dev/null
+++ b/tests/references/wp/R16-transparent-page.php
@@ -0,0 +1,5 @@
+<?php
+// En-tête transparent activé sur la page « Notre jardin » (méta de page), réglage global désactivé,
+// réglages de l'en-tête transparent de Garden Maintenance (transparent/valeurs/garden.json).
+putenv('VARIANTE=garden');
+require dirname(__DIR__) . '/transparent/appliquer.php';
diff --git a/tests/references/wp/R17-transparent-global.json b/tests/references/wp/R17-transparent-global.json
new file mode 100644
index 0000000..72a856e
--- /dev/null
+++ b/tests/references/wp/R17-transparent-global.json
@@ -0,0 +1,26 @@
+{
+	"chemin": "/?category_name=jardinage",
+	"ordinateur": [
+		".ast-builder-menu-1 .current-menu-item"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		"body:not(.ast-theme-transparent-header)"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": "#masthead",
+			"proprietes": ["position"]
+		},
+		{
+			"selecteur": ".ast-main-header-wrap .main-header-bar",
+			"proprietes": ["background-color", "border-bottom-width"]
+		}
+	]
+}
diff --git a/tests/references/wp/R17-transparent-global.php b/tests/references/wp/R17-transparent-global.php
new file mode 100644
index 0000000..4676a1e
--- /dev/null
+++ b/tests/references/wp/R17-transparent-global.php
@@ -0,0 +1,9 @@
+<?php
+// En-tête transparent sur tout le site (réglage global), avec les exclusions par défaut d'Astra :
+// la page de la catégorie Jardinage, archive, garde l'en-tête normal.
+require __DIR__ . '/commun.php';
+ref_etat_initial();
+ref_menu('Principal', 'primary');
+ref_menu('Principal', 'mobile_menu');
+$valeurs = json_decode(file_get_contents(dirname(__DIR__) . '/transparent/valeurs/garden.json'), true);
+ref_reglages(['transparent-header-enable' => 1] + $valeurs);
diff --git a/tests/references/wp/commun.php b/tests/references/wp/commun.php
index e7cda23..9074ddb 100644
--- a/tests/references/wp/commun.php
+++ b/tests/references/wp/commun.php
@@ -136,6 +136,7 @@ function ref_pages(): array {
 		$ids[$slug] = wp_insert_post($donnees);
 		delete_post_meta($ids[$slug], 'site-post-title');
 		delete_post_meta($ids[$slug], 'ast-site-content-layout');
+		delete_post_meta($ids[$slug], 'theme-transparent-header-meta');
 	}
 	return $ids;
 }
````

- [ ] **Étape 2 : vérifier**

```bash
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R16-transparent-page R17-transparent-global P1-page P2-accueil P3-page-pleine
php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414
php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R16-transparent-page R17-transparent-global
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R16-transparent-page R17-transparent-global P1-page P2-accueil P3-page-pleine
for f in $(git diff --name-only origin/main -- '*.php' ':!tests'); do php7.4 -l "$f"; done
```

Attendu : `comparer_html.php` : tout identique ; `comparer_captures.sh` : toutes les vues sous le seuil, R16 et R17 à 0,00 %, styles calculés égaux ; `No syntax errors detected` pour chaque fichier.

- [ ] **Étape 3 : commit**

```bash
git add tests/references/wp/R16-transparent-page.php tests/references/wp/R16-transparent-page.json tests/references/wp/R17-transparent-global.php tests/references/wp/R17-transparent-global.json tests/references/wp/commun.php tests/comparaison/configurations.php tests/comparaison/comparer_captures.sh README.md CREDITS.md tests/references/README.md
git commit -m "En-tête transparent : références R16 et R17, documentation"
```

## Effet attendu sur Garden

Un lot de `garden-spip` posera les options `transparent-header-*` du `site.json` (dont `transparent-header-default-border` à faux) et le réglage `transparent` = `active` des six pages ; la ligne « en-tête transparent » et la couleur du slogan des « Écarts connus » sont alors à remesurer. Ce lot fera l'objet de son propre plan.

# Réglages de compatibilité et options importées — plan de mise en œuvre

> **Pour les agents :** sous-compétence requise : superpowers:subagent-driven-development (recommandé) ou superpowers:executing-plans, tâche par tâche. Les étapes sont des cases à cocher (`- [ ]`).

**But :** combler les écarts de Garden Maintenance qu'aucune spec précédente ne couvre : drapeaux de compatibilité `submenu-below-header` et `can-support-widget-and-editor-fonts`, graisses des titres, marge du bouton de l'en-tête, alignements du copyright et des icônes sociales du pied ; version 1.4.0.

**Architecture :** un module pur, `inc/astra_compatibilite.php`, normalise la méta `astra/compatibilite` et choisit la feuille des règles de `submenu-below-header`, sorties de `css/astra-dynamic.css` vers `css/compat/` (extraites du rendu de WordPress par `extraire_css.py --compat`). Les autres options entrent au registre des styles : groupe `titres` (module `inc/styles/titres.php`, qui lit aussi le drapeau des polices), marge du bouton de l'en-tête (module existant), groupe `alignements` (type `alignement`, trois appareils appliqués, module `inc/styles/alignements.php`). `astra_styles_css()` reçoit les drapeaux et émet son bloc dès qu'un drapeau est posé. Chaque réglage est prouvé contre WordPress Astra 4.11.0 : références R18 à R23, comparaison HTML, captures et styles calculés à 1280, 768 et 390 px.

**Pile technique :** PHP 7.4+ (plugin), SPIP 4.4, PHPUnit 11.5, WordPress de test avec Astra 4.11.0 et WP-CLI, Playwright (Chromium), Python 3.

**Spec :** `docs/superpowers/specs/2026-10-23-compatibilite-reglages-design.md` (relue par Codex ; décisions du plan reportées dans la spec par ce même commit).

## Contraintes globales

- Rien ne change sans réglage : sans `astra/compatibilite` ni option des nouveaux groupes, R0–R17 et P1–P3 restent identiques (HTML, captures, styles calculés) ; la sortie des règles de `css/astra-dynamic.css` vers `css/compat/` est vérifiée par ces mêmes comparaisons.
- Avec un réglage, l'effet est celui d'Astra 4.11.0 : mêmes sélecteurs, mêmes conditions d'émission, mêmes media queries ; fichier d'Astra cité en commentaire. Aucune règle des feuilles `css/compat/` n'est écrite à la main.
- Aucune chaîne libre dans le CSS : graisses, alignements et côtés par listes fermées ou nombres bornés.
- Valeurs d'un `site.json` reprises telles quelles (le drapeau `can-support-widget-and-editor-fonts` vaut `false` dans Garden : Astra ne teste que sa présence).
- Le plugin reste compatible PHP 7.4 (vérifié : `php7.4 -l` et sortie identique à PHP 8.4).
- Langue du code, des commentaires et des messages : français, comme le dépôt.
- Dépôt partagé avec Codex : `git add` fichier par fichier ; ne jamais indexer `.gitignore`, `.ignore`, `tests/references/__pycache__/*.pyc`, `docs/inventaires/2026-10-23-preparation-publication.md`.
- Messages de commit sans trailer (ni `Co-Authored-By`, ni `Claude-Session`).
- Tests d'intégration : `vendor/spip/spip/plugins/astra` doit pointer vers l'arbre de travail testé (dans un worktree : `cp -a <dépôt principal>/vendor vendor && ln -sfn "$PWD" vendor/spip/spip/plugins/astra`). Tests d'intégration et comparaisons écrivent dans la même base : ne pas les lancer en même temps.
- Commandes : `vendor/bin/phpunit --testsuite unit [--filter …]` ; `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php [--filter …]`. Comparaisons : `WP=… PW=… SPIP_CLI=… tests/references/capturer.sh <Rn…>`, puis `SPIP_CLI=… php tests/comparaison/comparer_html.php <racine SPIP> <URL> [Rn…]` (`comparer_html.php` applique chaque configuration par spip-cli) et `WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh <racine SPIP> <URL> [Rn…]`. Le SPIP de test est servi avec `PHP_CLI_SERVER_WORKERS=4` (ici sur le port 8414), le WordPress de test sur 8401.
- Les correctifs s'appliquent depuis la racine du dépôt avec `git apply` (enregistrer le bloc dans un fichier, puis `git apply chemin/du/fichier.patch`), dans l'ordre des tâches, sur `origin/main` (525bcf4, v1.3.0).

## Décisions (écarts à la spec, reportés dans la spec par ce commit)

1. **Appareil sans alignement : la cascade d'Astra, pas `center`** (§ 3 : « appareil sans valeur : défaut `center` »). Astra n'émet aucune règle pour un appareil sans valeur ; la règle de l'ordinateur, sans media query, s'applique alors aussi à la tablette, et la règle tablette (921 px) au mobile. Mesuré sur WordPress avec le seul ordinateur à `left` : `left` aux trois largeurs. Astra SPIP émet donc, pour chaque appareil, la valeur que la cascade d'Astra lui donne (référence `R22-pied-alignements-ordinateur`).
2. **Marge du bouton de l'en-tête : côtés vides selon l'appareil** (§ 2 : « la marge de `theme-button-padding` (15 × 30 px sans réglage) »). Sans marge des boutons réglée, celle-ci vaut 15/30, 14/28 et 12/24 selon l'appareil (`css/astra-dynamic.css`) ; mesuré sur WordPress (marge enregistrée vide : 15/30 à 1280, 14/28 à 768, 12/24 à 390). Un côté réglé s'applique à toutes les largeurs. Références `R21-bouton-entete-marge` (Garden), `-valeur` (en partie réglée) et `-vide`.
3. **Graisses : équivalence par la cascade.** Astra modifie des règles figées (graisse retirée d'une règle qui porte aussi la taille) : la comparaison règle à règle des autres groupes ne s'applique pas. `tests/references/styles/graisses.py` extrait les règles de graisse des titres du CSS complet de WordPress ; `StylesTitresTest` calcule, des deux côtés, la graisse de chaque titre (spécificité, puis ordre). Variantes `titres`, `titres-drapeau`, `drapeau-polices`, et les valeurs vides ou `inherit` (décision 8) : `titres-vide`, `titres-vide-drapeau`, `titres-generale-vide`, `titres-generale-heritee`.
4. **Noms.** Le module des titres suit ceux des autres groupes : `inc/styles/titres.php`, `astra_styles_titres(array $s, array $hfb, array $c)` et `astra_titres_graisses(array $s, array $c)` (la spec nomme `astra_titres_css(array $styles, array $compatibilite)`). `astra_styles_css(array $styles, array $hfb, array $compatibilite = [])` passe les drapeaux à tous les modules.
5. **Références** : R20 en deux (`R20-titres-graisses`, `R20-titres-graisses-drapeau`), R21 en trois, R22 en trois (`-ordinateur`, `-tablette`). R19, R20 et R23 affichent la page « Notre jardin » avec les six niveaux de titres (`ref_texte_titres()` côté WordPress, clé `textes` côté SPIP, remise à « Texte de la page. » à chaque configuration) : les graisses de h1 à h6 sont mesurées dans le navigateur.
6. **Alignements** : groupe de registre `alignements` (les autres options du pied et des icônes sociales ne s'émettent pas pour un simple alignement), type `alignement` (`left`, `center`, `right`, vide), sections « Copyright » et « Icônes sociales du pied » du formulaire, trois sélecteurs. À la première saisie, l'option est gardée dès qu'un des trois appareils a une valeur (les autres options ne regardent que l'ordinateur). Sans valeur qui s'applique (ni l'appareil, ni ceux dont la cascade le fait hériter), le copyright hérite de l'alignement, mais les icônes sociales restent centrées : `css/astra-hfb.css` contient une règle fixe `[data-section^="section-fb-social-icons-"] .footer-social-inner-wrap{text-align:center}` (référence `R22-pied-alignements-tablette` : tablette seule réglée).
7. **Version 1.4.0** : nouveaux réglages, aucune rupture.
8. **Graisses vides ou `inherit`** (§ 1 : « `headings-font-weight` refuse `inherit` »). Une option absente prend le défaut d'Astra ; une valeur vide (importée) reste vide. Astra passe la graisse par `astra_get_css_value( …, 'font' )`, qui rend vide pour `''` et pour `inherit` : la propriété n'est pas émise. Mesuré sur WordPress : `font-weight-h2` vide laisse le h2 à la graisse des titres et n'émet pas `h2.widget-title`, qui garde la graisse 400 de `.widget-title` ; `headings-font-weight` vide ou `inherit` (défaut des installations d'avant Astra 4.0, `inc/core/class-astra-theme-options.php`) n'émet pas la règle générale. Astra SPIP accepte donc `inherit` pour `headings-font-weight` et neutralise le CSS figé par les graisses de `css/main.min.css` (titres hérités, `.site-title` à 400) ; un niveau vide donne `hn.widget-title{font-weight:400}` sans le drapeau.

Constat relevé pendant l'essai : un commentaire contenant une apostrophe en tête de `css/astra-dynamic.css` faisait lire la suite du fichier comme une chaîne par `extraire_css.py` (`instructions()`), qui régénérait alors `astra-hfb.css` et `astra-pages.css` incomplets. Le commentaire ajouté par la tâche 1 n'en contient pas ; `astra-hfb.css` et `astra-pages.css` régénérés sont identiques.

## Vérification préalable du plan

Le code de ce plan a été écrit et exécuté dans un worktree jetable de `origin/main` (525bcf4,
v1.3.0), puis réordonné en un commit par tâche ; chaque correctif ci-dessous est le diff de ces
commits, appliqué dans l'ordre sur 525bcf4 (l'arbre final est identique à celui de l'essai) :

- étape 2 de chaque tâche : tests de la tâche appliqués sur l'état de la tâche précédente, échecs
  relevés tels qu'indiqués ;
- tests après la tâche 5 : unitaires 112 tests, 1548 assertions ; intégration 83 tests,
  289 assertions (avant : 93 et 82) ;
- mutations du module des titres (neutralisation retirée ; niveaux comparés à 600 au lieu de la
  graisse des titres, le défaut relevé par Codex sur la spec ; valeur vide traitée comme
  `inherit`, le défaut relevé par Codex sur le plan) : `StylesTitresTest` échoue dans chaque cas ;
  icônes sociales sans valeur alignées au début (`inherit` au lieu de `center`) : `StylesTest`
  échoue, et `R22-pied-alignements-tablette` signale l'écart à 1280 px (WordPress `center`, SPIP
  `start`) ; première saisie limitée à l'ordinateur : `StylesSaisieTest` échoue ;
- comportements d'Astra mesurés sur WordPress avant d'écrire le code : marge du bouton de
  l'en-tête enregistrée vide (15/30, 14/28, 12/24 selon la largeur ; 17/26 partout avec la marge
  des boutons de Garden ; côtés réglés partout), alignements avec l'ordinateur seul (`left` aux
  trois largeurs) ; graisses vides ou `inherit` (variantes `titres-vide`, `titres-vide-drapeau`,
  `titres-generale-vide`, `titres-generale-heritee`, capturées sur WordPress) ;
- `php7.4 -l` sur les fichiers PHP modifiés hors `tests/` : aucune erreur ; CSS produit pour les
  valeurs de Garden, la variante `titres`, les quatre variantes vides ou `inherit` et
  `alignements-tablette` identique octet pour octet sous PHP 7.4 et 8.4 ;
- `extraire_css.py --compat` reproduit les deux feuilles de `css/compat/` ; `extraire_css.py` et
  `--pages` reproduisent `css/astra-hfb.css` et `css/astra-pages.css` (constat ci-dessus) ;
  `styles/capturer.sh` complet : aucun attendu existant modifié ;
- `capturer.sh` sur les 32 références (R0–R23 avec leurs variantes, P1–P3), puis
  `comparer_html.php` (défaut, puis R12–R23) : tout identique sauf les deux nommages de R13 ;
- `comparer_captures.sh` sur ces 32 références : 249 vues et jeux de styles sous le seuil (plus
  grand écart 0,73 %, R13, pied sur mobile), styles calculés égaux aux trois largeurs ; R18 :
  `#masthead` de 113 px à 1280 px (81 px sans le drapeau), des deux côtés ;
- `tests/e2e/formulaire_styles.cjs` (avec un slogan) : OK.

## Fichiers

| Fichier | Rôle | Tâche |
|---|---|---|
| `inc/astra_compatibilite.php` | Normalisation d'`astra/compatibilite`, feuille de `submenu-below-header`, drapeau des polices | 1 |
| `css/compat/sous-menu-fix.css`, `css/compat/sous-menu-ancien.css`, `css/astra-dynamic.css` | Règles de `submenu-below-header`, sorties du CSS figé | 1 |
| `astra_fonctions.php`, `inclure/head.html` | Lecture des drapeaux, feuille chargée après `astra-dynamic.css`, drapeaux passés aux styles | 1, 2 |
| `inc/styles/titres.php`, `inc/astra_styles.php`, `inc/astra_styles_saisie.php` | Groupe `titres`, drapeaux dans `astra_styles_css()` ; marge du bouton de l'en-tête ; groupe `alignements` | 2, 3, 4 |
| `inc/styles/bouton_entete.php`, `inc/styles/alignements.php`, `formulaires/configurer_astra_styles.html` | Marge et sa neutralisation ; alignements et leurs trois sélecteurs | 3, 4 |
| `lang/astra_{fr,en}.php` | Libellés | 2, 3, 4 |
| `tests/references/extraire_css.py`, `tests/references/styles/graisses.py`, `tests/references/styles/capturer.sh` | Extraction des feuilles de compatibilité, des règles de graisse | 1, 2 |
| `tests/references/wp/R18`–`R23`, `commun.php`, `tests/references/styles/valeurs/*.json`, `attendus/{titres,titres-drapeau,drapeau-polices}.css` | Références WordPress, valeurs, attendus | 1–5 |
| `tests/comparaison/appliquer.sh`, `configurations.php`, `comparer_captures.sh` | Clés `compatibilite` et `textes`, configurations SPIP, liste par défaut | 1–5 |
| `tests/unit/*`, `tests/integration/StylesFormulaireTest.php` | Tests | 1–4 |
| `paquet.xml`, `README.md`, `CREDITS.md`, `tests/references/README.md` | Version, documentation, provenance | 5 |

---

### Tâche 1 : drapeaux de compatibilité et `submenu-below-header`

Spec § 1. Méta `astra/compatibilite` : `submenu-below-header` (booléen) et `can-support-widget-and-editor-fonts` (présence), toute autre clé retirée. Les deux règles d'Astra qui dépendent de `submenu-below-header` (`.main-header-menu .menu-item…`, `.main-header-menu > .menu-item > .menu-link…`) quittent `css/astra-dynamic.css` pour `css/compat/sous-menu-fix.css` ; `css/compat/sous-menu-ancien.css` porte les quatre règles qu'Astra émet avec le drapeau à faux (`.ast-flex`, `.main-header-bar{padding:1em 0}`, `.ast-site-identity{padding:0}`, `.header-main-layout-1/3 …`). Une seule est chargée, juste après `astra-dynamic.css` : aucune règle qui suit dans les feuilles du thème ne vise ces propriétés avec la même spécificité (vérifié), la cascade est celle d'Astra.

**Fichiers :**
- Créer : `inc/astra_compatibilite.php`, `css/compat/sous-menu-fix.css`, `css/compat/sous-menu-ancien.css`, `tests/unit/CompatibiliteTest.php`, `tests/references/wp/R18-compat-sous-menu.php`, `tests/references/wp/R18-compat-sous-menu.json`
- Modifier : `css/astra-dynamic.css`, `astra_fonctions.php`, `inclure/head.html`, `tests/references/extraire_css.py`, `tests/comparaison/appliquer.sh`, `tests/comparaison/configurations.php`

**Interfaces :**
- Produit : `astra_compatibilite_normaliser($valeurs): array`, `astra_compatibilite_sous_menu_fix(array $c): bool`, `astra_compatibilite_polices_anciennes(array $c): bool`, `astra_compatibilite_feuille_sous_menu(array $c): string` (`inc/astra_compatibilite.php`) ; `astra_compatibilite(): array` et le filtre `astra_compatibilite_sous_menu` (`astra_fonctions.php`).
- Configurations de comparaison : clé `compatibilite` (écrite dans `astra/compatibilite`, effacée avant chaque configuration).

- [ ] **Étape 1 : écrire les tests, la référence et l'extraction**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/appliquer.sh b/tests/comparaison/appliquer.sh
index a6330db..557aca4 100755
--- a/tests/comparaison/appliquer.sh
+++ b/tests/comparaison/appliquer.sh
@@ -2,7 +2,8 @@
 # Usage : SPIP_CLI=… tests/comparaison/appliquer.sh <racine du site SPIP> <configuration>
 # Écrit une configuration transposée (configurations.php) dans astra/hfb, avec les réglages
 # d'accueil et de pages (astra/accueil_*, astra/pages), les styles (astra/styles), les polices
-# (astra/police_*) et le slogan (méta slogan_site), et vide le cache.
+# (astra/police_*), les drapeaux de compatibilité (astra/compatibilite) et le slogan (méta
+# slogan_site), et vide le cache.
 set -euo pipefail
 ICI=$(cd "$(dirname "$0")" && pwd)
 : "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
@@ -16,7 +17,7 @@ if (!isset(\$c['$2'])) { echo 'configuration-inconnue'; return; }
 \$id = function (\$identifiant) {
 	return (int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', 'page=' . sql_quote(\$identifiant)]);
 };
-foreach (['accueil_type', 'accueil_page', 'pages', 'styles', 'police_texte', 'police_titres'] as \$cle) {
+foreach (['accueil_type', 'accueil_page', 'pages', 'styles', 'police_texte', 'police_titres', 'compatibilite'] as \$cle) {
 	effacer_config('astra/' . \$cle);
 }
 ecrire_meta('slogan_site', '');
@@ -26,6 +27,9 @@ foreach (\$conf['meta'] ?? [] as \$meta => \$valeur) {
 if (!empty(\$conf['styles'])) {
 	ecrire_config('astra/styles', \$conf['styles']);
 }
+if (!empty(\$conf['compatibilite'])) {
+	ecrire_config('astra/compatibilite', \$conf['compatibilite']);
+}
 foreach (\$conf['astra'] ?? [] as \$cle => \$valeur) {
 	ecrire_config('astra/' . \$cle, is_string(\$valeur) && \$valeur[0] === '@' ? \$id(substr(\$valeur, 1)) : \$valeur);
 }
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 3ab17d8..d713b78 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -174,6 +174,9 @@ function astra_configurations_reference(): array {
 			'rubrique' => 'Jardinage',
 			'styles' => ['transparent-header-enable' => 1] + json_decode(file_get_contents(dirname(__DIR__) . '/references/transparent/valeurs/garden.json'), true),
 		],
+		'R18-compat-sous-menu' => [
+			'compatibilite' => ['submenu-below-header' => false],
+		],
 		'P1-page' => [
 			'page' => 'notre_jardin',
 			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
diff --git a/tests/references/extraire_css.py b/tests/references/extraire_css.py
index aae2ac2..210b24d 100644
--- a/tests/references/extraire_css.py
+++ b/tests/references/extraire_css.py
@@ -5,6 +5,8 @@ builder seulement : les références P* des pages uniques ne sont pas lues).
 
 Usage : python3 tests/references/extraire_css.py [sorties] > css/astra-hfb.css
         python3 tests/references/extraire_css.py --pages [sorties] > css/astra-pages.css
+        python3 tests/references/extraire_css.py --compat sous-menu-fix [sorties] > css/compat/sous-menu-fix.css
+        python3 tests/references/extraire_css.py --compat sous-menu-ancien [sorties] > css/compat/sous-menu-ancien.css
 
 Compare le CSS dynamique des configurations R1 à R4 à celui de R0 (disposition par
 défaut, déjà portée dans css/astra-dynamic.css) et ne garde que les règles absentes de R0,
@@ -163,6 +165,32 @@ def main_pages(sorties):
     imprimer(nouvelles)
 
 
+# Variantes des drapeaux de compatibilité : (référence avec le drapeau, sens, description).
+# « fix » : règles de R0 absentes de la référence (émises seulement sans le drapeau) ;
+# « ancien » : règles de la référence absentes de R0 (émises seulement avec le drapeau).
+COMPAT = {
+    'sous-menu-fix': ('R18-compat-sous-menu', 'r0', 'submenu-below-header absent ou vrai (installation récente)'),
+    'sous-menu-ancien': ('R18-compat-sous-menu', 'ref', 'submenu-below-header à faux (site antérieur à Astra 1.5.0, Starter Templates)'),
+}
+
+
+def main_compat(variante, sorties):
+    """Règles qui dépendent d'un drapeau de compatibilité, dans l'ordre d'Astra."""
+    reference, sens, description = COMPAT[variante]
+    lire = lambda conf: open(os.path.join(sorties, conf, 'dynamique.css'), encoding='utf-8').read()
+    r0, ref = paires(lire('R0-defaut')), paires(lire(reference))
+    source, autre = (r0, set(ref)) if sens == 'r0' else (ref, set(r0))
+    print('/*')
+    print(' * Astra pour SPIP — CSS de compatibilité : ' + description + '.')
+    print(' *')
+    print(' * Règles d\'Astra 4.11.0 (Astra_Dynamic_CSS::astra_submenu_below_header_fix()) qui dépendent du')
+    print(' * drapeau, chargées selon astra/compatibilite. Fichier produit par')
+    print(' * tests/references/extraire_css.py --compat ' + variante + ' à partir des références WordPress :')
+    print(' * ne pas modifier à la main.')
+    print(' */')
+    imprimer([p for p in source if p not in autre])
+
+
 def imprimer(regles):
     contexte_courant = None
     for contexte, regle in regles:
@@ -178,7 +206,9 @@ def imprimer(regles):
 
 
 if __name__ == '__main__':
-    if len(sys.argv) > 1 and sys.argv[1] == '--pages':
+    if len(sys.argv) > 2 and sys.argv[1] == '--compat':
+        main_compat(sys.argv[2], sys.argv[3] if len(sys.argv) > 3 else os.path.join(os.path.dirname(__file__), 'sorties'))
+    elif len(sys.argv) > 1 and sys.argv[1] == '--pages':
         main_pages(sys.argv[2] if len(sys.argv) > 2 else os.path.join(os.path.dirname(__file__), 'sorties'))
     else:
         main()
diff --git a/tests/references/wp/R18-compat-sous-menu.json b/tests/references/wp/R18-compat-sous-menu.json
new file mode 100644
index 0000000..25a57eb
--- /dev/null
+++ b/tests/references/wp/R18-compat-sous-menu.json
@@ -0,0 +1,47 @@
+{
+	"ordinateur": [
+		".site-primary-header-wrap .site-branding",
+		".ast-builder-menu-1",
+		".site-below-footer-wrap .ast-footer-copyright"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": "#masthead",
+			"proprietes": [
+				"height"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .main-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-bottom",
+				"height"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .ast-site-identity",
+			"proprietes": [
+				"padding-top",
+				"padding-bottom"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .ast-builder-menu-1 .menu-item",
+			"proprietes": [
+				"display",
+				"justify-content"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R18-compat-sous-menu.php b/tests/references/wp/R18-compat-sous-menu.php
new file mode 100644
index 0000000..6ea761c
--- /dev/null
+++ b/tests/references/wp/R18-compat-sous-menu.php
@@ -0,0 +1,8 @@
+<?php
+// Drapeau de compatibilité submenu-below-header à faux (site.json de Garden Maintenance) :
+// Astra_Dynamic_CSS::astra_submenu_below_header_fix() renvoie faux.
+require __DIR__ . '/commun.php';
+ref_etat_initial();
+ref_menu('Principal', 'primary');
+ref_menu('Principal', 'mobile_menu');
+ref_reglages(['submenu-below-header' => false]);
diff --git a/tests/unit/CompatibiliteTest.php b/tests/unit/CompatibiliteTest.php
new file mode 100644
index 0000000..4808d74
--- /dev/null
+++ b/tests/unit/CompatibiliteTest.php
@@ -0,0 +1,46 @@
+<?php
+
+use PHPUnit\Framework\TestCase;
+
+require_once dirname(__DIR__, 2) . '/inc/astra_compatibilite.php';
+
+/**
+ * Drapeaux de compatibilité d'Astra 4.11.0 (méta astra/compatibilite) : normalisation et choix des
+ * règles qui en dépendent.
+ */
+final class CompatibiliteTest extends TestCase {
+
+	public function testNormalisation(): void {
+		$this->assertSame([], astra_compatibilite_normaliser(null));
+		$this->assertSame([], astra_compatibilite_normaliser(['inconnu' => true, 'submenu-below-header' => 'non']));
+		// Valeurs du site.json de Garden Maintenance : can-support-widget-and-editor-fonts vaut false,
+		// mais Astra ne teste que sa présence.
+		$this->assertSame(
+			['submenu-below-header' => false, 'can-support-widget-and-editor-fonts' => true],
+			astra_compatibilite_normaliser(['submenu-below-header' => false, 'can-support-widget-and-editor-fonts' => false])
+		);
+		$this->assertSame(['submenu-below-header' => true], astra_compatibilite_normaliser(['submenu-below-header' => '1']));
+	}
+
+	public function testFeuilleDuSousMenu(): void {
+		// Sans réglage, ou drapeau vrai : règles d'une installation récente (rendu inchangé).
+		$this->assertSame('css/compat/sous-menu-fix.css', astra_compatibilite_feuille_sous_menu([]));
+		$this->assertSame('css/compat/sous-menu-fix.css', astra_compatibilite_feuille_sous_menu(['submenu-below-header' => true]));
+		$this->assertSame('css/compat/sous-menu-ancien.css', astra_compatibilite_feuille_sous_menu(['submenu-below-header' => false]));
+		foreach (['fix', 'ancien'] as $variante) {
+			$this->assertFileExists(dirname(__DIR__, 2) . "/css/compat/sous-menu-$variante.css");
+		}
+	}
+
+	public function testReglesDuSousMenuSortiesDuCssFige(): void {
+		// Les règles qui dépendent du drapeau ne sont plus dans css/astra-dynamic.css.
+		$fige = file_get_contents(dirname(__DIR__, 2) . '/css/astra-dynamic.css');
+		$this->assertStringNotContainsString('.main-header-menu .menu-item, #astra-footer-menu .menu-item', $fige);
+		$this->assertStringContainsString('.main-header-menu .menu-item, #astra-footer-menu .menu-item', file_get_contents(dirname(__DIR__, 2) . '/css/compat/sous-menu-fix.css'));
+	}
+
+	public function testPolicesAnciennes(): void {
+		$this->assertFalse(astra_compatibilite_polices_anciennes([]));
+		$this->assertTrue(astra_compatibilite_polices_anciennes(astra_compatibilite_normaliser(['can-support-widget-and-editor-fonts' => false])));
+	}
+}
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : arrêt au chargement (`require_once(…/inc/astra_compatibilite.php): Failed to open stream`, puis « An error occurred inside PHPUnit ») ;
- intégration : OK (82 tests, 283 assertions) — aucun test d'intégration nouveau ; R18 vérifie le rendu à l'étape 4.

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/astra_fonctions.php b/astra_fonctions.php
index 12b36c3..744292a 100644
--- a/astra_fonctions.php
+++ b/astra_fonctions.php
@@ -19,6 +19,7 @@ include_once __DIR__ . '/inc/astra_hfb_saisie.php';
 include_once __DIR__ . '/inc/astra_pages.php';
 include_once __DIR__ . '/inc/astra_styles_saisie.php';
 include_once __DIR__ . '/inc/astra_entete_transparent.php';
+include_once __DIR__ . '/inc/astra_compatibilite.php';
 
 /** Réglages effectifs d'une page unique : `#ID_ARTICLE|astra_page_reglages`. */
 function filtre_astra_page_reglages_dist($id_article): array {
@@ -141,6 +142,17 @@ function astra_polices_inline(): string {
 	});
 }
 
+/** Drapeaux de compatibilité (méta astra/compatibilite), normalisés. */
+function astra_compatibilite(): array {
+	include_spip('inc/config');
+	return astra_compatibilite_normaliser(lire_config('astra/compatibilite'));
+}
+
+/** Feuille des règles de submenu-below-header : `#VAL|astra_compatibilite_sous_menu|find_in_path`. */
+function astra_compatibilite_sous_menu($x = ''): string {
+	return astra_compatibilite_feuille_sous_menu(astra_compatibilite());
+}
+
 /**
  * CSS des styles du Customizer (méta astra/styles) : vide sans réglage.
  */
diff --git a/css/astra-dynamic.css b/css/astra-dynamic.css
index a4e93fa..52865d5 100644
--- a/css/astra-dynamic.css
+++ b/css/astra-dynamic.css
@@ -1,5 +1,7 @@
 /* Astra 4.11.0 — CSS dynamique (réglages par défaut du Customizer), extrait du rendu WordPress.
-   Les couleurs globales et largeurs sont définies par le plugin (inclure/head-vars.html). */
+   Les couleurs globales et largeurs sont définies par le plugin (inclure/head-vars.html).
+   Les règles qui dépendent du drapeau de compatibilité submenu-below-header sont dans
+   css/compat/, chargé juste après ce fichier selon astra/compatibilite. */
 :root{--ast-post-nav-space:0;--ast-container-default-xlg-padding:2.5em;--ast-container-default-lg-padding:2.5em;--ast-container-default-slg-padding:2em;--ast-container-default-md-padding:2.5em;--ast-container-default-sm-padding:2.5em;--ast-container-default-xs-padding:2.4em;--ast-container-default-xxs-padding:1.8em;--ast-code-block-background:#ECEFF3;--ast-comment-inputs-background:#F9FAFB;--ast-normal-container-width:1200px;--ast-narrow-container-width:750px;--ast-blog-title-font-weight:600;--ast-blog-meta-weight:600;--ast-global-color-primary:var(--ast-global-color-4);--ast-global-color-secondary:var(--ast-global-color-5);--ast-global-color-alternate-background:var(--ast-global-color-6);--ast-global-color-subtle-background:var(--ast-global-color-7);--ast-bg-style-guide:#F8FAFC;--ast-shadow-style-guide:0px 0px 4px 0 #00000057;--ast-global-dark-bg-style:#fff;--ast-global-dark-lfs:#fbfbfb;--ast-widget-bg-color:#fafafa;--ast-wc-container-head-bg-color:#fbfbfb;--ast-title-layout-bg:#eeeeee;--ast-search-border-color:#e7e7e7;--ast-lifter-hover-bg:#e6e6e6;--ast-gallery-block-color:#000;--srfm-color-input-label:var(--ast-global-color-2);}
 html{font-size:100%;}
 a{color:var(--ast-global-color-0);}
@@ -363,8 +365,6 @@ h1,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content
 .ast-header-break-point .main-header-bar{border-bottom-width:1px;}
 @media (min-width:922px){.main-header-bar{border-bottom-width:1px;}}
 @media (min-width:922px){.ast-left-sidebar #primary,.ast-right-sidebar #primary{width:70%;}#secondary{width:30%;}}
-.main-header-menu .menu-item, #astra-footer-menu .menu-item, .main-header-bar .ast-masthead-custom-menu-items{-js-display:flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;-moz-box-pack:center;-ms-flex-pack:center;justify-content:center;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;-moz-box-orient:vertical;-moz-box-direction:normal;-ms-flex-direction:column;flex-direction:column;}
-.main-header-menu > .menu-item > .menu-link, #astra-footer-menu > .menu-item > .menu-link{height:100%;-webkit-box-align:center;-webkit-align-items:center;-moz-box-align:center;-ms-flex-align:center;align-items:center;-js-display:flex;display:flex;}
 .ast-header-break-point .main-navigation ul .menu-item .menu-link .icon-arrow:first-of-type svg{top:.2em;margin-top:0px;margin-left:0px;width:.65em;transform:translate(0, -2px) rotateZ(270deg);}
 .ast-mobile-popup-content .ast-submenu-expanded > .ast-menu-toggle{transform:rotateX(180deg);overflow-y:auto;}
 @media (min-width:922px){.ast-builder-menu .main-navigation > ul > li:last-child a{margin-right:0;}}
diff --git a/css/compat/sous-menu-ancien.css b/css/compat/sous-menu-ancien.css
new file mode 100644
index 0000000..070826c
--- /dev/null
+++ b/css/compat/sous-menu-ancien.css
@@ -0,0 +1,12 @@
+/*
+ * Astra pour SPIP — CSS de compatibilité : submenu-below-header à faux (site antérieur à Astra 1.5.0, Starter Templates).
+ *
+ * Règles d'Astra 4.11.0 (Astra_Dynamic_CSS::astra_submenu_below_header_fix()) qui dépendent du
+ * drapeau, chargées selon astra/compatibilite. Fichier produit par
+ * tests/references/extraire_css.py --compat sous-menu-ancien à partir des références WordPress :
+ * ne pas modifier à la main.
+ */
+.ast-flex{-webkit-align-content:center;-ms-flex-line-pack:center;align-content:center;-webkit-box-align:center;-webkit-align-items:center;-moz-box-align:center;-ms-flex-align:center;align-items:center;}
+.main-header-bar{padding:1em 0;}
+.ast-site-identity{padding:0;}
+.header-main-layout-1 .ast-flex.main-header-container, .header-main-layout-3 .ast-flex.main-header-container{-webkit-align-content:center;-ms-flex-line-pack:center;align-content:center;-webkit-box-align:center;-webkit-align-items:center;-moz-box-align:center;-ms-flex-align:center;align-items:center;}
diff --git a/css/compat/sous-menu-fix.css b/css/compat/sous-menu-fix.css
new file mode 100644
index 0000000..9a19394
--- /dev/null
+++ b/css/compat/sous-menu-fix.css
@@ -0,0 +1,10 @@
+/*
+ * Astra pour SPIP — CSS de compatibilité : submenu-below-header absent ou vrai (installation récente).
+ *
+ * Règles d'Astra 4.11.0 (Astra_Dynamic_CSS::astra_submenu_below_header_fix()) qui dépendent du
+ * drapeau, chargées selon astra/compatibilite. Fichier produit par
+ * tests/references/extraire_css.py --compat sous-menu-fix à partir des références WordPress :
+ * ne pas modifier à la main.
+ */
+.main-header-menu .menu-item, #astra-footer-menu .menu-item, .main-header-bar .ast-masthead-custom-menu-items{-js-display:flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;-moz-box-pack:center;-ms-flex-pack:center;justify-content:center;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;-moz-box-orient:vertical;-moz-box-direction:normal;-ms-flex-direction:column;flex-direction:column;}
+.main-header-menu > .menu-item > .menu-link, #astra-footer-menu > .menu-item > .menu-link{height:100%;-webkit-box-align:center;-webkit-align-items:center;-moz-box-align:center;-ms-flex-align:center;align-items:center;-js-display:flex;display:flex;}
diff --git a/inc/astra_compatibilite.php b/inc/astra_compatibilite.php
new file mode 100644
index 0000000..17bd7c7
--- /dev/null
+++ b/inc/astra_compatibilite.php
@@ -0,0 +1,43 @@
+<?php
+/**
+ * Astra pour SPIP — drapeaux de compatibilité d'Astra 4.11.0 (méta astra/compatibilite,
+ * docs/superpowers/specs/2026-10-23-compatibilite-reglages-design.md).
+ *
+ * Un site installé avec une ancienne version d'Astra garde des drapeaux dans astra-settings ; les
+ * Starter Templates les exportent dans site.json. Ils ne s'éditent pas dans le Customizer : ils
+ * viennent d'un import (lot de Garden, clé config.astra.compatibilite).
+ *
+ * @license GPL-3.0-or-later
+ */
+
+/**
+ * Drapeaux acceptés : submenu-below-header (booléen, Astra_Dynamic_CSS::astra_submenu_below_header_fix())
+ * et can-support-widget-and-editor-fonts (présence seule, support_font_css_to_widget_and_in_editor()
+ * teste isset). Toute autre clé est retirée, comme une option de style inconnue.
+ */
+function astra_compatibilite_normaliser($valeurs): array {
+	$r = [];
+	$v = is_array($valeurs) ? $valeurs : [];
+	if (array_key_exists('submenu-below-header', $v) && (is_bool($v['submenu-below-header']) || in_array($v['submenu-below-header'], [0, 1, '0', '1'], true))) {
+		$r['submenu-below-header'] = (bool) $v['submenu-below-header'];
+	}
+	if (array_key_exists('can-support-widget-and-editor-fonts', $v) && $v['can-support-widget-and-editor-fonts'] !== null) {
+		$r['can-support-widget-and-editor-fonts'] = true;
+	}
+	return $r;
+}
+
+/** Vrai sauf si submenu-below-header est posé à faux (astra_submenu_below_header_fix()). */
+function astra_compatibilite_sous_menu_fix(array $c): bool {
+	return ($c['submenu-below-header'] ?? true) !== false;
+}
+
+/** Vrai si can-support-widget-and-editor-fonts est posé (graisses des titres d'avant Astra 3.6.0). */
+function astra_compatibilite_polices_anciennes(array $c): bool {
+	return isset($c['can-support-widget-and-editor-fonts']);
+}
+
+/** Feuille des règles qui dépendent de submenu-below-header, extraite du rendu d'Astra. */
+function astra_compatibilite_feuille_sous_menu(array $c): string {
+	return astra_compatibilite_sous_menu_fix($c) ? 'css/compat/sous-menu-fix.css' : 'css/compat/sous-menu-ancien.css';
+}
diff --git a/inclure/head.html b/inclure/head.html
index 8143fbf..c591512 100644
--- a/inclure/head.html
+++ b/inclure/head.html
@@ -11,6 +11,7 @@
 #INSERT_HEAD_CSS
 <link rel="stylesheet" id="astra-theme-css-css" href="[(#CHEMIN{css/main.min.css}|timestamp)]" media="all" />
 <link rel="stylesheet" id="astra-dynamic-css" href="[(#CHEMIN{css/astra-dynamic.css}|timestamp)]" media="all" />
+<link rel="stylesheet" id="astra-compat-sous-menu-css" href="[(#VAL|astra_compatibilite_sous_menu|find_in_path|timestamp)]" media="all" />
 [(#ENV{type_page}|=={article}|oui)<link rel="stylesheet" id="astra-article-css" href="[(#CHEMIN{css/astra-article.css}|timestamp)]" media="all" />
 ]<link rel="stylesheet" id="astra-blocs-css" href="[(#CHEMIN{css/astra-blocs.css}|timestamp)]" media="all" />
 <link rel="stylesheet" id="astra-hfb-css" href="[(#CHEMIN{css/astra-hfb.css}|timestamp)]" media="all" />
````

Les deux feuilles de `css/compat/` sont la sortie de `extraire_css.py --compat` (captures R0 et R18). Pour les produire soi-même plutôt que d'appliquer leur partie du correctif :

```bash
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R0-defaut R18-compat-sous-menu
python3 tests/references/extraire_css.py --compat sous-menu-fix > css/compat/sous-menu-fix.css
python3 tests/references/extraire_css.py --compat sous-menu-ancien > css/compat/sous-menu-ancien.css
```

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (97 tests, 1429 assertions)`, intégration `OK (82 tests, 283 assertions)`

```bash
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R18-compat-sous-menu P1-page P3-page-pleine
for v in fix ancien; do python3 tests/references/extraire_css.py --compat sous-menu-$v | diff - css/compat/sous-menu-$v.css; done
python3 tests/references/extraire_css.py | diff - css/astra-hfb.css
python3 tests/references/extraire_css.py --pages | diff - css/astra-pages.css
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R0-defaut R18-compat-sous-menu
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R0-defaut R18-compat-sous-menu
```

Attendu : aucune différence des quatre `diff` ; HTML identique ; toutes les vues sous le seuil ; R18 : 8 valeurs de styles égales aux trois largeurs (`#masthead` de 113 px à 1280 px des deux côtés, contre 81 px pour R0).

- [ ] **Étape 5 : commit**

```bash
git add inc/astra_compatibilite.php css/compat/sous-menu-fix.css css/compat/sous-menu-ancien.css css/astra-dynamic.css astra_fonctions.php inclure/head.html tests/unit/CompatibiliteTest.php tests/references/extraire_css.py tests/references/wp/R18-compat-sous-menu.php tests/references/wp/R18-compat-sous-menu.json tests/comparaison/appliquer.sh tests/comparaison/configurations.php
git commit -m "Compatibilité : drapeaux d'astra/compatibilite et submenu-below-header"
```

### Tâche 2 : graisses des titres et drapeau des polices

Spec § 1, « Graisses des titres ». Groupe `titres` du registre : `headings-font-weight` (défaut 600), `font-weight-h1` à `h6` (défaut `inherit`) ; option absente : défaut ; valeur vide gardée (décision 8). Pour chaque niveau, graisse d'Astra `G` (valeur de repli `inherit`, ou `normal` avec le drapeau : elle prend alors `headings-font-weight`, notée `H`) ; le module émet, après le CSS figé : la règle générale si `H` diffère de 600 (si `H` est vide ou vaut `inherit`, sa neutralisation : titres à `inherit`, `.site-title` à 400, `.site-title a` à `inherit`), puis `hn,.entry-content hn{font-weight:T}` si `T` (`G`, ou la graisse générale quand `G` vaut `inherit` ou est vide) diffère de la graisse générale (`H`, ou `inherit` quand la règle générale n'a pas de graisse) ; sans le drapeau, `hn.widget-title{font-weight:G}` (n = 1 à 3 ; 400 quand `G` est vide) si cette graisse diffère de 600 ; avec le drapeau, la neutralisation `h1.widget-title,h2.widget-title,h3.widget-title{font-weight:400}` (valeur de `.widget-title` dans `css/main.min.css`). Le bloc `astra-styles-inline-css` est produit dès qu'un drapeau est posé.

**Fichiers :**
- Créer : `inc/styles/titres.php`, `tests/unit/StylesTitresTest.php`, `tests/references/styles/graisses.py`, `tests/references/styles/valeurs/{titres,titres-drapeau,drapeau-polices,titres-vide,titres-vide-drapeau,titres-generale-vide,titres-generale-heritee}.json`, `tests/references/styles/attendus/` (mêmes noms, `.css`), `tests/references/wp/R19-compat-polices-widgets.{php,json}`, `tests/references/wp/R20-titres-graisses.{php,json}`, `tests/references/wp/R20-titres-graisses-drapeau.{php,json}`
- Modifier : `inc/astra_styles.php`, `inc/astra_styles_saisie.php`, `astra_fonctions.php`, `lang/astra_fr.php`, `lang/astra_en.php`, `tests/integration/StylesFormulaireTest.php`, `tests/references/styles/capturer.sh`, `tests/references/wp/commun.php`, `tests/comparaison/appliquer.sh`, `tests/comparaison/configurations.php`

**Interfaces :**
- Consomme : `astra_compatibilite_normaliser()`, `astra_compatibilite_polices_anciennes()` (tâche 1).
- Produit : `astra_titres_graisses(array $s, array $c): array` et `astra_styles_titres(array $s, array $hfb, array $c = []): array` (`inc/styles/titres.php`) ; `astra_styles_css(array $styles, array $hfb, array $compatibilite = []): string` ; `ASTRA_STYLES_MODULES` commence par `titres`.
- Configurations de comparaison : clé `textes` (texte d'une page unique, par identifiant).

- [ ] **Étape 1 : écrire les tests, les références et les attendus**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/appliquer.sh b/tests/comparaison/appliquer.sh
index 557aca4..5254282 100755
--- a/tests/comparaison/appliquer.sh
+++ b/tests/comparaison/appliquer.sh
@@ -2,8 +2,8 @@
 # Usage : SPIP_CLI=… tests/comparaison/appliquer.sh <racine du site SPIP> <configuration>
 # Écrit une configuration transposée (configurations.php) dans astra/hfb, avec les réglages
 # d'accueil et de pages (astra/accueil_*, astra/pages), les styles (astra/styles), les polices
-# (astra/police_*), les drapeaux de compatibilité (astra/compatibilite) et le slogan (méta
-# slogan_site), et vide le cache.
+# (astra/police_*), les drapeaux de compatibilité (astra/compatibilite), les textes des pages
+# uniques et le slogan (méta slogan_site), et vide le cache.
 set -euo pipefail
 ICI=$(cd "$(dirname "$0")" && pwd)
 : "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
@@ -33,6 +33,11 @@ if (!empty(\$conf['compatibilite'])) {
 foreach (\$conf['astra'] ?? [] as \$cle => \$valeur) {
 	ecrire_config('astra/' . \$cle, is_string(\$valeur) && \$valeur[0] === '@' ? \$id(substr(\$valeur, 1)) : \$valeur);
 }
+// Textes des pages uniques : celui de scripts/install-spip-test.sh, sauf ceux de la configuration.
+sql_updateq('spip_articles', ['texte' => 'Texte de la page.'], 'id_rubrique=-1');
+foreach (\$conf['textes'] ?? [] as \$identifiant => \$texte) {
+	sql_updateq('spip_articles', ['texte' => \$texte], 'id_article=' . \$id(\$identifiant));
+}
 foreach (\$conf['pages'] ?? [] as \$identifiant => \$reglages) {
 	ecrire_config('astra/pages/' . \$id(\$identifiant), \$reglages);
 }
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index d713b78..80cbb75 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -31,6 +31,13 @@ function astra_configurations_reference(): array {
 		];
 	};
 	$tous = ['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'];
+	// Graisses des titres (R19, R20) : page « Notre jardin » avec les six niveaux de titres,
+	// comme ref_texte_titres() côté WordPress.
+	$texte = '';
+	for ($n = 1; $n <= 6; $n++) {
+		$texte .= "<h$n>Titre de niveau $n</h$n>\n";
+	}
+	$titres = ['page' => 'notre_jardin', 'textes' => ['notre_jardin' => $texte . '<p>Texte de la page.</p>']];
 	return [
 		'R0-defaut' => [],
 		'R1-entete-complet' => [
@@ -177,6 +184,9 @@ function astra_configurations_reference(): array {
 		'R18-compat-sous-menu' => [
 			'compatibilite' => ['submenu-below-header' => false],
 		],
+		'R19-compat-polices-widgets' => $styles([], $titres + ['compatibilite' => ['can-support-widget-and-editor-fonts' => false]]),
+		'R20-titres-graisses' => $styles(['titres'], $titres),
+		'R20-titres-graisses-drapeau' => $styles(['titres'], $titres + ['compatibilite' => ['can-support-widget-and-editor-fonts' => false]]),
 		'P1-page' => [
 			'page' => 'notre_jardin',
 			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
diff --git a/tests/integration/StylesFormulaireTest.php b/tests/integration/StylesFormulaireTest.php
index 202cade..b7655fa 100644
--- a/tests/integration/StylesFormulaireTest.php
+++ b/tests/integration/StylesFormulaireTest.php
@@ -45,7 +45,7 @@ final class StylesFormulaireTest extends TestCase {
 
 	public function testRenduDesChamps(): void {
 		$html = recuperer_fond('formulaires/configurer_astra_styles', ['editable' => true, 's' => ['button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'em']]]);
-		$this->assertSame(14, substr_count($html, '<fieldset'));
+		$this->assertSame(15, substr_count($html, '<fieldset'));
 		$this->assertMatchesRegularExpression('~name="s\[mobile-header-toggle-btn-style\]".*?<option value="outline">Contour</option>~s', $html);
 		$this->assertStringContainsString('name="s[header-menu1-color-responsive][desktop]"', $html);
 		$this->assertStringContainsString('name="s[button-radius-fields][desktop][top]" value="8"', $html);
diff --git a/tests/references/styles/attendus/drapeau-polices.css b/tests/references/styles/attendus/drapeau-polices.css
new file mode 100644
index 0000000..d2b7dd0
--- /dev/null
+++ b/tests/references/styles/attendus/drapeau-polices.css
@@ -0,0 +1 @@
+h1,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content h4,h5,.entry-content h5,h6,.entry-content h6,.site-title,.site-title a{font-weight:600;}
diff --git a/tests/references/styles/attendus/titres-drapeau.css b/tests/references/styles/attendus/titres-drapeau.css
new file mode 100644
index 0000000..604ba5d
--- /dev/null
+++ b/tests/references/styles/attendus/titres-drapeau.css
@@ -0,0 +1,4 @@
+h1,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content h4,h5,.entry-content h5,h6,.entry-content h6,.site-title,.site-title a{font-weight:700;}
+h2,.entry-content h2{font-weight:700;}
+h3,.entry-content h3{font-weight:300;}
+h4,.entry-content h4{font-weight:600;}
diff --git a/tests/references/styles/attendus/titres-generale-heritee.css b/tests/references/styles/attendus/titres-generale-heritee.css
new file mode 100644
index 0000000..5e7a82c
--- /dev/null
+++ b/tests/references/styles/attendus/titres-generale-heritee.css
@@ -0,0 +1,3 @@
+h2,.entry-content h2{font-weight:500;}
+h1.widget-title{font-weight:inherit;}
+h2.widget-title{font-weight:500;}
diff --git a/tests/references/styles/attendus/titres-generale-vide.css b/tests/references/styles/attendus/titres-generale-vide.css
new file mode 100644
index 0000000..29c9532
--- /dev/null
+++ b/tests/references/styles/attendus/titres-generale-vide.css
@@ -0,0 +1,2 @@
+h2,.entry-content h2{font-weight:500;}
+h2.widget-title{font-weight:500;}
diff --git a/tests/references/styles/attendus/titres-vide-drapeau.css b/tests/references/styles/attendus/titres-vide-drapeau.css
new file mode 100644
index 0000000..e44fdd1
--- /dev/null
+++ b/tests/references/styles/attendus/titres-vide-drapeau.css
@@ -0,0 +1,2 @@
+h1,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content h4,h5,.entry-content h5,h6,.entry-content h6,.site-title,.site-title a{font-weight:700;}
+h3,.entry-content h3{font-weight:300;}
diff --git a/tests/references/styles/attendus/titres-vide.css b/tests/references/styles/attendus/titres-vide.css
new file mode 100644
index 0000000..b302a11
--- /dev/null
+++ b/tests/references/styles/attendus/titres-vide.css
@@ -0,0 +1,8 @@
+h1,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content h4,h5,.entry-content h5,h6,.entry-content h6,.site-title,.site-title a{font-weight:700;}
+h1,.entry-content h1{font-weight:700;}
+h3,.entry-content h3{font-weight:300;}
+h4,.entry-content h4{font-weight:700;}
+h5,.entry-content h5{font-weight:700;}
+h6,.entry-content h6{font-weight:700;}
+h1.widget-title{font-weight:700;}
+h3.widget-title{font-weight:300;}
diff --git a/tests/references/styles/attendus/titres.css b/tests/references/styles/attendus/titres.css
new file mode 100644
index 0000000..9e130ba
--- /dev/null
+++ b/tests/references/styles/attendus/titres.css
@@ -0,0 +1,10 @@
+h1,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content h4,h5,.entry-content h5,h6,.entry-content h6,.site-title,.site-title a{font-weight:700;}
+h1,.entry-content h1{font-weight:700;}
+h2,.entry-content h2{font-weight:normal;}
+h3,.entry-content h3{font-weight:300;}
+h4,.entry-content h4{font-weight:600;}
+h5,.entry-content h5{font-weight:700;}
+h6,.entry-content h6{font-weight:700;}
+h1.widget-title{font-weight:700;}
+h2.widget-title{font-weight:normal;}
+h3.widget-title{font-weight:300;}
diff --git a/tests/references/styles/capturer.sh b/tests/references/styles/capturer.sh
index 90f45eb..14dafc2 100755
--- a/tests/references/styles/capturer.sh
+++ b/tests/references/styles/capturer.sh
@@ -2,7 +2,8 @@
 # Capture le CSS dynamique de WordPress pour chaque groupe de styles et en tire les attendus.
 # Usage : WP=<racine WordPress> [URL=http://127.0.0.1:8401] tests/references/styles/capturer.sh
 # Écrit attendus/base.css (sans option) et attendus/<groupe>.css (règles ajoutées ou modifiées
-# par les options du groupe, par rapport à base.css), puis remet le WordPress en état R0.
+# par les options du groupe, par rapport à base.css ; pour les graisses des titres, règles de
+# graisse du CSS complet), puis remet le WordPress en état R0.
 set -euo pipefail
 ICI=$(cd "$(dirname "$0")" && pwd)
 URL=${URL:-http://127.0.0.1:8401}
@@ -20,4 +21,12 @@ for groupe in menu bouton bouton-entete identite pied sociaux menu-mobile; do
 	rm "$ICI/attendus/.$groupe.complet.css"
 	echo "$groupe : $(wc -l < "$ICI/attendus/$groupe.css") règles"
 done
+# Graisses des titres : règles de graisse du CSS complet (graisses.py), comparées par la cascade.
+for groupe in titres titres-drapeau drapeau-polices titres-vide titres-vide-drapeau titres-generale-vide titres-generale-heritee; do
+	GROUPE=$groupe wp --path="$WP" eval-file "$ICI/appliquer.php"
+	css > "$ICI/attendus/.$groupe.complet.css"
+	python3 "$ICI/graisses.py" "$ICI/attendus/.$groupe.complet.css" > "$ICI/attendus/$groupe.css"
+	rm "$ICI/attendus/.$groupe.complet.css"
+	echo "$groupe : $(wc -l < "$ICI/attendus/$groupe.css") règles de graisse"
+done
 wp --path="$WP" eval-file "$ICI/../wp/R0-defaut.php"
diff --git a/tests/references/styles/graisses.py b/tests/references/styles/graisses.py
new file mode 100644
index 0000000..323163e
--- /dev/null
+++ b/tests/references/styles/graisses.py
@@ -0,0 +1,25 @@
+#!/usr/bin/env python3
+"""
+Règles de graisse des titres d'un CSS dynamique d'Astra, dans l'ordre : règles de premier niveau
+dont un sélecteur vise un titre (h1 à h6, .widget-title, .site-title) et qui déclarent font-weight.
+Astra modifie des règles figées (graisse retirée d'une règle qui porte aussi la taille) : les
+graisses calculées se comparent par la cascade (tests/unit/StylesTitresTest.php), non règle à règle.
+
+Usage : python3 tests/references/styles/graisses.py complet.css > attendus/titres.css
+"""
+import os
+import re
+import sys
+
+sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))
+from extraire_css import paires  # noqa: E402
+
+TITRE = re.compile(r'(^|[\s,>.])(h[1-6]|widget-title|site-title)(?![\w-])')
+
+for contexte, regle in paires(open(sys.argv[1], encoding='utf-8').read()):
+    if contexte or '{' not in regle:
+        continue
+    selecteurs, declarations = regle.split('{', 1)
+    graisses = [d.strip() for d in declarations.rstrip('}').split(';') if d.strip().startswith('font-weight:')]
+    if graisses and TITRE.search(selecteurs):
+        print(re.sub(r'\s*,\s*', ',', selecteurs.strip()) + '{' + graisses[-1] + ';}')
diff --git a/tests/references/styles/valeurs/drapeau-polices.json b/tests/references/styles/valeurs/drapeau-polices.json
new file mode 100644
index 0000000..3c24b1f
--- /dev/null
+++ b/tests/references/styles/valeurs/drapeau-polices.json
@@ -0,0 +1,3 @@
+{
+	"can-support-widget-and-editor-fonts": false
+}
diff --git a/tests/references/styles/valeurs/titres-drapeau.json b/tests/references/styles/valeurs/titres-drapeau.json
new file mode 100644
index 0000000..560be8e
--- /dev/null
+++ b/tests/references/styles/valeurs/titres-drapeau.json
@@ -0,0 +1,8 @@
+{
+	"headings-font-weight": "700",
+	"font-weight-h1": "inherit",
+	"font-weight-h2": "normal",
+	"font-weight-h3": "300",
+	"font-weight-h4": "600",
+	"can-support-widget-and-editor-fonts": false
+}
diff --git a/tests/references/styles/valeurs/titres-generale-heritee.json b/tests/references/styles/valeurs/titres-generale-heritee.json
new file mode 100644
index 0000000..af4b228
--- /dev/null
+++ b/tests/references/styles/valeurs/titres-generale-heritee.json
@@ -0,0 +1,5 @@
+{
+	"headings-font-weight": "inherit",
+	"font-weight-h2": "500",
+	"font-weight-h3": ""
+}
diff --git a/tests/references/styles/valeurs/titres-generale-vide.json b/tests/references/styles/valeurs/titres-generale-vide.json
new file mode 100644
index 0000000..762d715
--- /dev/null
+++ b/tests/references/styles/valeurs/titres-generale-vide.json
@@ -0,0 +1,5 @@
+{
+	"headings-font-weight": "",
+	"font-weight-h2": "500",
+	"font-weight-h3": ""
+}
diff --git a/tests/references/styles/valeurs/titres-vide-drapeau.json b/tests/references/styles/valeurs/titres-vide-drapeau.json
new file mode 100644
index 0000000..c89d911
--- /dev/null
+++ b/tests/references/styles/valeurs/titres-vide-drapeau.json
@@ -0,0 +1,7 @@
+{
+	"headings-font-weight": "700",
+	"font-weight-h1": "inherit",
+	"font-weight-h2": "",
+	"font-weight-h3": "300",
+	"can-support-widget-and-editor-fonts": false
+}
diff --git a/tests/references/styles/valeurs/titres-vide.json b/tests/references/styles/valeurs/titres-vide.json
new file mode 100644
index 0000000..52023d8
--- /dev/null
+++ b/tests/references/styles/valeurs/titres-vide.json
@@ -0,0 +1,6 @@
+{
+	"headings-font-weight": "700",
+	"font-weight-h1": "inherit",
+	"font-weight-h2": "",
+	"font-weight-h3": "300"
+}
diff --git a/tests/references/styles/valeurs/titres.json b/tests/references/styles/valeurs/titres.json
new file mode 100644
index 0000000..70bb972
--- /dev/null
+++ b/tests/references/styles/valeurs/titres.json
@@ -0,0 +1,7 @@
+{
+	"headings-font-weight": "700",
+	"font-weight-h1": "inherit",
+	"font-weight-h2": "normal",
+	"font-weight-h3": "300",
+	"font-weight-h4": "600"
+}
diff --git a/tests/references/wp/R19-compat-polices-widgets.json b/tests/references/wp/R19-compat-polices-widgets.json
new file mode 100644
index 0000000..4249516
--- /dev/null
+++ b/tests/references/wp/R19-compat-polices-widgets.json
@@ -0,0 +1,80 @@
+{
+	"chemin": "/?pagename=notre-jardin",
+	"ordinateur": [
+		".site-primary-header-wrap .site-branding",
+		".site-primary-footer-wrap .footer-widget-area",
+		"article.page .entry-content"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".entry-content h1",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h2",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h3",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h4",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h5",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h6",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".footer-widget-area h2.widget-title",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".site-title",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".site-title a",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": "article.page .entry-title",
+			"proprietes": [
+				"font-weight"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R19-compat-polices-widgets.php b/tests/references/wp/R19-compat-polices-widgets.php
new file mode 100644
index 0000000..70812b0
--- /dev/null
+++ b/tests/references/wp/R19-compat-polices-widgets.php
@@ -0,0 +1,8 @@
+<?php
+// Drapeau can-support-widget-and-editor-fonts posé, sans aucune autre option de style (valeurs : tests/references/styles/valeurs/drapeau-polices.json) ; placement des
+// références des styles (titres des widgets du pied en h2), page « Notre jardin » avec les six
+// niveaux de titres.
+putenv('GROUPE=drapeau-polices');
+require dirname(__DIR__) . '/styles/appliquer.php';
+$ids = ref_pages();
+wp_update_post(['ID' => $ids['notre-jardin'], 'post_content' => ref_texte_titres()]);
diff --git a/tests/references/wp/R20-titres-graisses-drapeau.json b/tests/references/wp/R20-titres-graisses-drapeau.json
new file mode 100644
index 0000000..4249516
--- /dev/null
+++ b/tests/references/wp/R20-titres-graisses-drapeau.json
@@ -0,0 +1,80 @@
+{
+	"chemin": "/?pagename=notre-jardin",
+	"ordinateur": [
+		".site-primary-header-wrap .site-branding",
+		".site-primary-footer-wrap .footer-widget-area",
+		"article.page .entry-content"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".entry-content h1",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h2",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h3",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h4",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h5",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h6",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".footer-widget-area h2.widget-title",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".site-title",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".site-title a",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": "article.page .entry-title",
+			"proprietes": [
+				"font-weight"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R20-titres-graisses-drapeau.php b/tests/references/wp/R20-titres-graisses-drapeau.php
new file mode 100644
index 0000000..04e911d
--- /dev/null
+++ b/tests/references/wp/R20-titres-graisses-drapeau.php
@@ -0,0 +1,8 @@
+<?php
+// Graisses des titres réglées, avec le drapeau can-support-widget-and-editor-fonts (valeurs : tests/references/styles/valeurs/titres-drapeau.json) ; placement des
+// références des styles (titres des widgets du pied en h2), page « Notre jardin » avec les six
+// niveaux de titres.
+putenv('GROUPE=titres-drapeau');
+require dirname(__DIR__) . '/styles/appliquer.php';
+$ids = ref_pages();
+wp_update_post(['ID' => $ids['notre-jardin'], 'post_content' => ref_texte_titres()]);
diff --git a/tests/references/wp/R20-titres-graisses.json b/tests/references/wp/R20-titres-graisses.json
new file mode 100644
index 0000000..4249516
--- /dev/null
+++ b/tests/references/wp/R20-titres-graisses.json
@@ -0,0 +1,80 @@
+{
+	"chemin": "/?pagename=notre-jardin",
+	"ordinateur": [
+		".site-primary-header-wrap .site-branding",
+		".site-primary-footer-wrap .footer-widget-area",
+		"article.page .entry-content"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".entry-content h1",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h2",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h3",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h4",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h5",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h6",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".footer-widget-area h2.widget-title",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".site-title",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".site-title a",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": "article.page .entry-title",
+			"proprietes": [
+				"font-weight"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R20-titres-graisses.php b/tests/references/wp/R20-titres-graisses.php
new file mode 100644
index 0000000..06289d9
--- /dev/null
+++ b/tests/references/wp/R20-titres-graisses.php
@@ -0,0 +1,8 @@
+<?php
+// Graisses des titres réglées, sans le drapeau (valeurs : tests/references/styles/valeurs/titres.json) ; placement des
+// références des styles (titres des widgets du pied en h2), page « Notre jardin » avec les six
+// niveaux de titres.
+putenv('GROUPE=titres');
+require dirname(__DIR__) . '/styles/appliquer.php';
+$ids = ref_pages();
+wp_update_post(['ID' => $ids['notre-jardin'], 'post_content' => ref_texte_titres()]);
diff --git a/tests/references/wp/commun.php b/tests/references/wp/commun.php
index 3778896..631a3c9 100644
--- a/tests/references/wp/commun.php
+++ b/tests/references/wp/commun.php
@@ -153,6 +153,16 @@ function ref_pages(): array {
 	return $ids;
 }
 
+// Texte de page avec les six niveaux de titres (graisses des titres, R19 et R20) ; même texte
+// côté SPIP (clé textes de tests/comparaison/configurations.php).
+function ref_texte_titres(): string {
+	$texte = '';
+	for ($n = 1; $n <= 6; $n++) {
+		$texte .= "<h$n>Titre de niveau $n</h$n>\n";
+	}
+	return $texte . '<p>Texte de la page.</p>';
+}
+
 // Menu de pages à deux niveaux, dans l'ordre du menu SPIP (par titre ; sous-pages par rang).
 function ref_menu_pages(string $emplacement): void {
 	$ids = ref_pages();
diff --git a/tests/unit/StylesTitresTest.php b/tests/unit/StylesTitresTest.php
new file mode 100644
index 0000000..d31ff40
--- /dev/null
+++ b/tests/unit/StylesTitresTest.php
@@ -0,0 +1,117 @@
+<?php
+
+use PHPUnit\Framework\Attributes\DataProvider;
+use PHPUnit\Framework\TestCase;
+
+require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';
+require_once dirname(__DIR__, 2) . '/inc/styles/titres.php';
+require_once __DIR__ . '/StylesAttendusTest.php';
+
+/**
+ * Graisses des titres (groupe titres, drapeau can-support-widget-and-editor-fonts). Astra modifie
+ * des règles figées : l'équivalence porte sur la graisse que calcule chaque titre, par la cascade,
+ * entre WordPress (css/main.min.css puis les règles de graisse capturées, attendus/<variante>.css)
+ * et SPIP (css/main.min.css, css/astra-dynamic.css, puis le CSS produit).
+ */
+final class StylesTitresTest extends TestCase {
+
+	private const DIR = __DIR__ . '/../references/styles';
+
+	/** Titres comparés : sélecteurs qui les visent => spécificité (règles sans media seulement). */
+	private static function elements(): array {
+		$e = [];
+		for ($n = 1; $n <= 6; $n++) {
+			$e["h$n"] = ["h$n" => 1];
+			$e[".entry-content h$n"] = ["h$n" => 1, ".entry-content h$n" => 11];
+			$e["h$n.widget-title"] = ["h$n" => 1, '.widget-title' => 10, "h$n.widget-title" => 11];
+		}
+		$e['.site-title'] = ['.site-title' => 10];
+		$e['.site-title a'] = ['.site-title a' => 11];
+		return $e;
+	}
+
+	/** Graisse calculée de chaque titre : spécificité la plus forte, puis dernière règle. */
+	private static function graisses(string $css): array {
+		$r = [];
+		foreach (self::elements() as $element => $selecteurs) {
+			$retenue = [-1, ''];
+			foreach (StylesAttendusTest::regles($css) as $regle) {
+				if ($regle[0] === '[' || !preg_match('/font-weight:([^;}]+)/', $regle, $m)) {
+					continue;
+				}
+				foreach (explode(',', explode('{', $regle, 2)[0]) as $sel) {
+					$sel = preg_replace('/\s+/', ' ', trim($sel));
+					if (isset($selecteurs[$sel]) && $selecteurs[$sel] >= $retenue[0]) {
+						$retenue = [$selecteurs[$sel], trim($m[1])];
+					}
+				}
+			}
+			// Aucune règle : la graisse est héritée.
+			$r[$element] = $retenue[1] === '' ? 'inherit' : $retenue[1];
+		}
+		return $r;
+	}
+
+	public static function variantes(): array {
+		$v = ['titres', 'titres-drapeau', 'drapeau-polices', 'titres-vide', 'titres-vide-drapeau', 'titres-generale-vide', 'titres-generale-heritee'];
+		return array_combine($v, array_map(function ($x) { return [$x]; }, $v));
+	}
+
+	#[DataProvider('variantes')]
+	public function testGraissesCommeWordPress(string $variante): void {
+		$valeurs = json_decode(file_get_contents(self::DIR . "/valeurs/$variante.json"), true);
+		$main = file_get_contents(dirname(__DIR__, 2) . '/css/main.min.css');
+		$wordpress = self::graisses($main . file_get_contents(self::DIR . "/attendus/$variante.css"));
+		$spip = self::graisses($main . file_get_contents(dirname(__DIR__, 2) . '/css/astra-dynamic.css') . astra_styles_css($valeurs, [], $valeurs));
+		$this->assertSame($wordpress, $spip);
+	}
+
+	public function testSansReglageRienNEstEmis(): void {
+		$this->assertSame([], astra_styles_titres([], [], []));
+		// Valeurs par défaut d'Astra posées explicitement : rien ne diffère du CSS figé.
+		$this->assertSame([], astra_styles_titres(['headings-font-weight' => '600', 'font-weight-h1' => 'inherit'], [], []));
+	}
+
+	public function testValeursDeGarden(): void {
+		// Drapeau posé, niveaux à inherit, titres à 600 : seule la neutralisation des titres des widgets.
+		$s = ['headings-font-weight' => '600'];
+		for ($n = 1; $n <= 6; $n++) {
+			$s["font-weight-h$n"] = 'inherit';
+		}
+		$css = astra_styles_css($s, [], ['can-support-widget-and-editor-fonts' => false]);
+		$this->assertSame('h1.widget-title,h2.widget-title,h3.widget-title{font-weight:400;}', $css);
+		// Drapeau seul, sans aucune option de style : même CSS.
+		$this->assertSame($css, astra_styles_css([], [], ['can-support-widget-and-editor-fonts' => false]));
+	}
+
+	public function testNiveauEgalAuCssFigeSousUneAutreGraisse(): void {
+		// headings-font-weight à 700, h1 à 600 : la règle du h1 suit la règle générale, sinon le h1
+		// calculerait 700. Avec et sans le drapeau.
+		foreach ([[], ['can-support-widget-and-editor-fonts' => true]] as $c) {
+			$css = astra_styles_css(['headings-font-weight' => '700', 'font-weight-h1' => '600'], [], $c);
+			$generale = strpos($css, '.site-title a{font-weight:700;}');
+			$this->assertNotFalse($generale);
+			$this->assertGreaterThan($generale, strpos($css, 'h1,.entry-content h1{font-weight:600;}'));
+		}
+	}
+
+	public function testValeurVideDistincteDeLOptionAbsente(): void {
+		// Graisse du h2 vide (importée) : Astra n'émet ni la règle du niveau ni celle du titre de
+		// widget ; le h2 prend la graisse des titres, h2.widget-title celle de .widget-title (400).
+		$css = astra_styles_css(['headings-font-weight' => '700', 'font-weight-h2' => ''], [], []);
+		$this->assertStringContainsString('h2.widget-title{font-weight:400;}', $css);
+		$this->assertStringNotContainsString('h2,.entry-content h2{', $css);
+		// Option absente : défaut d'Astra (inherit), titre de widget à la graisse des titres.
+		$this->assertStringContainsString('h2.widget-title{font-weight:700;}', astra_styles_css(['headings-font-weight' => '700'], [], []));
+	}
+
+	public function testGraisseDesTitresVideOuHeritee(): void {
+		// inherit (défaut d'avant Astra 4.0) et vide sont gardés : Astra n'émet pas la règle générale.
+		$this->assertSame(['headings-font-weight' => 'inherit', 'font-weight-h2' => ''], astra_styles_normaliser(['headings-font-weight' => 'inherit', 'font-weight-h2' => '']));
+		// Neutralisation par les graisses de css/main.min.css.
+		$attendu = 'h1,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content h4,h5,.entry-content h5,h6,.entry-content h6{font-weight:inherit;}'
+			. '.site-title{font-weight:400;}.site-title a{font-weight:inherit;}';
+		$this->assertStringStartsWith($attendu, astra_styles_css(['headings-font-weight' => ''], [], []));
+		$this->assertStringStartsWith($attendu, astra_styles_css(['headings-font-weight' => 'inherit'], [], []));
+	}
+}
````

Les attendus sont la sortie de `capturer.sh` (boucle des graisses), qui laisse les autres attendus inchangés.

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : arrêt au chargement (`require_once(…/inc/styles/titres.php): Failed to open stream`, puis « An error occurred inside PHPUnit ») ;
- intégration : 1 échec (`StylesFormulaireTest::testRenduDesChamps`, 14 sections au lieu de 15).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/astra_fonctions.php b/astra_fonctions.php
index 744292a..2a6e97c 100644
--- a/astra_fonctions.php
+++ b/astra_fonctions.php
@@ -154,13 +154,14 @@ function astra_compatibilite_sous_menu($x = ''): string {
 }
 
 /**
- * CSS des styles du Customizer (méta astra/styles) : vide sans réglage.
+ * CSS des styles du Customizer (méta astra/styles) et des drapeaux de compatibilité qui changent
+ * le CSS (astra/compatibilite) : vide sans réglage.
  */
 function astra_styles_inline(): string {
 	include_spip('inc/config');
 	$styles = lire_config('astra/styles');
 	$hfb = lire_config('astra/hfb');
-	return astra_styles_css(is_array($styles) ? $styles : [], is_array($hfb) ? $hfb : []);
+	return astra_styles_css(is_array($styles) ? $styles : [], is_array($hfb) ? $hfb : [], astra_compatibilite());
 }
 
 /**
diff --git a/inc/astra_styles.php b/inc/astra_styles.php
index 8ffef02..9816b6e 100644
--- a/inc/astra_styles.php
+++ b/inc/astra_styles.php
@@ -10,6 +10,7 @@
  */
 
 require_once __DIR__ . '/astra_polices.php';
+require_once __DIR__ . '/astra_compatibilite.php';
 
 /** Taille du texte d'Astra SPIP (px) : base de la conversion px → rem d'astra_get_font_css_value(). */
 const ASTRA_STYLES_TAILLE_TEXTE = 16;
@@ -39,6 +40,13 @@ function astra_styles_registre(): array {
 	$cotes = ['type' => 'quatre-cotes'];
 	$nombre_r = ['type' => 'nombre', 'responsive' => true];
 
+	// Graisses des titres.
+	$titres = ['headings-font-weight' => ['type' => 'graisse', 'defaut' => '600']];
+	for ($n = 1; $n <= 6; $n++) {
+		$titres["font-weight-h$n"] = ['type' => 'graisse', 'defaut' => 'inherit'];
+	}
+	$ajouter('titres', 'inc/class-astra-dynamic-css.php', $titres);
+
 	$menu = 'inc/builder/type/header/menu/dynamic-css/dynamic.css.php';
 	foreach ([1, 2] as $n) {
 		$ajouter('menu', $menu, [
@@ -466,18 +474,22 @@ function astra_styles_ecrire(array $regles): string {
 }
 
 /** Modules de groupe (inc/styles/<module>.php), dans l'ordre où Astra émet leur CSS. */
-const ASTRA_STYLES_MODULES = ['bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux', 'menu_mobile'];
+const ASTRA_STYLES_MODULES = ['titres', 'bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux', 'menu_mobile'];
 
-/** CSS des styles du Customizer (vide sans réglage), dans l'ordre d'émission d'Astra. */
-function astra_styles_css(array $styles, array $hfb): string {
+/**
+ * CSS des styles du Customizer (vide sans réglage), dans l'ordre d'émission d'Astra. $compatibilite :
+ * drapeaux d'astra/compatibilite, qui changent le CSS même sans option de style.
+ */
+function astra_styles_css(array $styles, array $hfb, array $compatibilite = []): string {
 	$s = astra_styles_normaliser($styles);
-	if (!$s) {
+	$c = astra_compatibilite_normaliser($compatibilite);
+	if (!$s && !$c) {
 		return '';
 	}
 	$regles = [];
 	foreach (ASTRA_STYLES_MODULES as $module) {
 		require_once __DIR__ . "/styles/$module.php";
-		$regles = array_merge($regles, ('astra_styles_' . $module)($s, $hfb));
+		$regles = array_merge($regles, ('astra_styles_' . $module)($s, $hfb, $c));
 	}
 	return astra_styles_ecrire($regles);
 }
diff --git a/inc/astra_styles_saisie.php b/inc/astra_styles_saisie.php
index 2fe1438..f2c270d 100644
--- a/inc/astra_styles_saisie.php
+++ b/inc/astra_styles_saisie.php
@@ -13,6 +13,7 @@ require_once __DIR__ . '/astra_styles.php';
 /** Sections du formulaire : clé de langue (astra:styles_section_<clé>) => motif des options. */
 function astra_styles_sections(): array {
 	return [
+		'titres' => '/^(headings-font-weight|font-weight-h[1-6])$/',
 		'menu_1' => '/^header-menu1-/',
 		'menu_2' => '/^header-menu2-/',
 		'bouton' => '/^(button-|theme-button-|font-[a-z]+-button$)/',
diff --git a/inc/styles/titres.php b/inc/styles/titres.php
new file mode 100644
index 0000000..68072b7
--- /dev/null
+++ b/inc/styles/titres.php
@@ -0,0 +1,74 @@
+<?php
+/**
+ * Astra pour SPIP — graisses des titres (inc/class-astra-dynamic-css.php d'Astra 4.11.0 :
+ * typographie des titres et compatibilité can-support-widget-and-editor-fonts).
+ *
+ * css/astra-dynamic.css fige la graisse 600 des titres, de chaque niveau et des titres des widgets
+ * h1 à h3 ; seules les différences avec ce CSS figé sont émises, après lui.
+ *
+ * @license GPL-3.0-or-later
+ */
+
+/** Graisse figée par css/astra-dynamic.css (headings-font-weight par défaut). */
+const ASTRA_TITRES_GRAISSE_FIGEE = '600';
+
+/**
+ * Graisse que calcule chaque niveau dans Astra. $c : drapeaux de compatibilité normalisés.
+ * Retourne [graisse des titres, [n => graisse d'Astra pour le niveau n]]. Une option absente prend
+ * le défaut d'Astra ; une valeur vide (importée) reste vide : Astra n'émet alors pas la propriété.
+ */
+function astra_titres_graisses(array $s, array $c): array {
+	$h = (string) ($s['headings-font-weight'] ?? ASTRA_TITRES_GRAISSE_FIGEE);
+	// Valeur de repli : inherit, ou normal avec le drapeau (support_font_css_to_widget_and_in_editor()).
+	$repli = astra_compatibilite_polices_anciennes($c) ? 'normal' : 'inherit';
+	$niveaux = [];
+	for ($n = 1; $n <= 6; $n++) {
+		$g = (string) ($s["font-weight-h$n"] ?? 'inherit');
+		$niveaux[$n] = $g === $repli ? $h : $g;
+	}
+	return [$h, $niveaux];
+}
+
+function astra_styles_titres(array $s, array $hfb, array $c = []): array {
+	[$h, $niveaux] = astra_titres_graisses($s, $c);
+	$titres = [];
+	for ($n = 1; $n <= 6; $n++) {
+		array_push($titres, "h$n", ".entry-content h$n");
+	}
+	$regles = [];
+	// Graisse des titres vide ou inherit (défaut des installations d'avant Astra 4.0) : la règle
+	// générale n'a pas de graisse (astra_get_css_value()).
+	$sans_generale = $h === '' || $h === 'inherit';
+	if ($sans_generale) {
+		// Neutralisation par les graisses de css/main.min.css : titres hérités, .site-title à 400.
+		$regles[] = ['', implode(',', $titres), ['font-weight' => 'inherit']];
+		$regles[] = ['', '.site-title', ['font-weight' => '400']];
+		$regles[] = ['', '.site-title a', ['font-weight' => 'inherit']];
+	} elseif ($h !== ASTRA_TITRES_GRAISSE_FIGEE) {
+		$regles[] = ['', implode(',', $titres) . ',.site-title,.site-title a', ['font-weight' => $h]];
+	}
+	// Graisse des titres une fois la règle générale appliquée.
+	$generale = $sans_generale ? 'inherit' : $h;
+	// Chaque niveau calcule sa graisse, ou celle des titres quand Astra n'en émet pas (inherit ou
+	// vide) : comparée à celle que le niveau a après la règle générale.
+	foreach ($niveaux as $n => $g) {
+		$t = $g === 'inherit' || $g === '' ? $generale : $g;
+		if ($t !== $generale) {
+			$regles[] = ['', "h$n,.entry-content h$n", ['font-weight' => $t]];
+		}
+	}
+	if (!astra_compatibilite_polices_anciennes($c)) {
+		for ($n = 1; $n <= 3; $n++) {
+			// Graisse vide : Astra n'émet pas la règle, celle de .widget-title (400) s'applique.
+			$w = $niveaux[$n] === '' ? '400' : $niveaux[$n];
+			if ($w !== ASTRA_TITRES_GRAISSE_FIGEE) {
+				$regles[] = ['', "h$n.widget-title", ['font-weight' => $w]];
+			}
+		}
+	} else {
+		// Neutralisation : avec le drapeau, Astra n'émet pas les graisses des titres des widgets ; la
+		// graisse de .widget-title de css/main.min.css (400) s'applique.
+		$regles[] = ['', 'h1.widget-title,h2.widget-title,h3.widget-title', ['font-weight' => '400']];
+	}
+	return $regles;
+}
diff --git a/lang/astra_en.php b/lang/astra_en.php
index c7d40b4..989e86c 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -262,12 +262,19 @@ return [
 	'style_font_size_site_tagline' => 'Tagline size',
 	'style_font_size_site_title' => 'Site title size',
 	'style_font_weight_button' => 'Font weight',
+	'style_font_weight_h1' => 'H1 font weight',
+	'style_font_weight_h2' => 'H2 font weight',
+	'style_font_weight_h3' => 'H3 font weight',
+	'style_font_weight_h4' => 'H4 font weight',
+	'style_font_weight_h5' => 'H5 font weight',
+	'style_font_weight_h6' => 'H6 font weight',
 	'style_footer_copyright_color' => 'Text color',
 	'style_hba_transparent_header_bg_color_responsive' => 'Above row background',
 	'style_hbb_transparent_header_bg_color_responsive' => 'Below row background',
 	'style_header_color_h_site_title' => 'Site title color on hover',
 	'style_header_color_site_tagline' => 'Tagline color',
 	'style_header_color_site_title' => 'Site title color',
+	'style_headings_font_weight' => 'Headings font weight',
 	'style_menu_a_bg_color_responsive' => 'Current page background color',
 	'style_menu_a_color_responsive' => 'Current page color',
 	'style_menu_bg_obj_responsive' => 'Menu background color',
@@ -356,6 +363,7 @@ return [
 	'styles_section_menu_mobile' => 'Mobile menu button',
 	'styles_section_sociaux_entete' => 'Header social icons',
 	'styles_section_sociaux_pied' => 'Footer social icons',
+	'styles_section_titres' => 'Headings',
 	'styles_section_widget_1' => 'Footer widget 1',
 	'styles_section_widget_2' => 'Footer widget 2',
 	'styles_section_widget_3' => 'Footer widget 3',
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index af7f488..18b973c 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -262,12 +262,19 @@ return [
 	'style_font_size_site_tagline' => 'Taille du slogan',
 	'style_font_size_site_title' => 'Taille du nom du site',
 	'style_font_weight_button' => 'Graisse',
+	'style_font_weight_h1' => 'Graisse des titres h1',
+	'style_font_weight_h2' => 'Graisse des titres h2',
+	'style_font_weight_h3' => 'Graisse des titres h3',
+	'style_font_weight_h4' => 'Graisse des titres h4',
+	'style_font_weight_h5' => 'Graisse des titres h5',
+	'style_font_weight_h6' => 'Graisse des titres h6',
 	'style_footer_copyright_color' => 'Couleur du texte',
 	'style_hba_transparent_header_bg_color_responsive' => 'Fond de la rangée haute',
 	'style_hbb_transparent_header_bg_color_responsive' => 'Fond de la rangée basse',
 	'style_header_color_h_site_title' => 'Couleur du nom du site au survol',
 	'style_header_color_site_tagline' => 'Couleur du slogan',
 	'style_header_color_site_title' => 'Couleur du nom du site',
+	'style_headings_font_weight' => 'Graisse des titres',
 	'style_menu_a_bg_color_responsive' => 'Couleur de fond de la page courante',
 	'style_menu_a_color_responsive' => 'Couleur de la page courante',
 	'style_menu_bg_obj_responsive' => 'Couleur de fond du menu',
@@ -356,6 +363,7 @@ return [
 	'styles_section_menu_mobile' => 'Bouton du menu mobile',
 	'styles_section_sociaux_entete' => 'Icônes sociales de l’en-tête',
 	'styles_section_sociaux_pied' => 'Icônes sociales du pied de page',
+	'styles_section_titres' => 'Titres',
 	'styles_section_widget_1' => 'Widget 1 du pied de page',
 	'styles_section_widget_2' => 'Widget 2 du pied de page',
 	'styles_section_widget_3' => 'Widget 3 du pied de page',
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (109 tests, 1508 assertions)`, intégration `OK (82 tests, 283 assertions)`

```bash
WP=… tests/references/styles/capturer.sh && git status --short tests/references/styles
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau
```

Attendu : `capturer.sh` ne modifie aucun attendu ; HTML identique (contenu de la page compris) ; toutes les vues sous le seuil ; 10 valeurs de styles égales aux trois largeurs pour chaque référence (graisses de `.entry-content h1` à `h6`, du titre de widget h2, du nom du site, du titre de la page).

- [ ] **Étape 5 : commit**

```bash
git add inc/styles/titres.php inc/astra_styles.php inc/astra_styles_saisie.php astra_fonctions.php lang/astra_fr.php lang/astra_en.php tests/unit/StylesTitresTest.php tests/integration/StylesFormulaireTest.php tests/references/styles/graisses.py tests/references/styles/capturer.sh tests/references/styles/valeurs/titres.json tests/references/styles/valeurs/titres-drapeau.json tests/references/styles/valeurs/drapeau-polices.json tests/references/styles/attendus/titres.css tests/references/styles/attendus/titres-drapeau.css tests/references/styles/attendus/drapeau-polices.css tests/references/styles/valeurs/titres-vide.json tests/references/styles/valeurs/titres-vide-drapeau.json tests/references/styles/valeurs/titres-generale-vide.json tests/references/styles/valeurs/titres-generale-heritee.json tests/references/styles/attendus/titres-vide.css tests/references/styles/attendus/titres-vide-drapeau.css tests/references/styles/attendus/titres-generale-vide.css tests/references/styles/attendus/titres-generale-heritee.css tests/references/wp/commun.php tests/references/wp/R19-compat-polices-widgets.php tests/references/wp/R19-compat-polices-widgets.json tests/references/wp/R20-titres-graisses.php tests/references/wp/R20-titres-graisses.json tests/references/wp/R20-titres-graisses-drapeau.php tests/references/wp/R20-titres-graisses-drapeau.json tests/comparaison/appliquer.sh tests/comparaison/configurations.php
git commit -m "Styles : graisses des titres et drapeau can-support-widget-and-editor-fonts"
```

### Tâche 3 : marge du bouton de l'en-tête

Spec § 2 et décision 2. `section-hb-button-1-padding` (quatre côtés, responsive, défaut 15 × 30 px) entre au groupe `bouton-entete`. Option enregistrée : ses côtés réglés (valeur ordinateur) ; côtés vides : la marge des boutons, que `css/astra-hfb.css` masquerait (valeur ordinateur de `theme-button-padding`, ou 15/30, 14/28, 12/24 selon l'appareil sans marge des boutons réglée). Émise même sans autre option du bouton de l'en-tête, si le bouton est placé.

**Fichiers :**
- Créer : `tests/references/styles/valeurs/bouton-entete-marge{,-valeur,-vide}.json`, `tests/references/wp/R21-bouton-entete-marge{,-valeur,-vide}.{php,json}`
- Modifier : `inc/astra_styles.php`, `inc/astra_styles_saisie.php`, `inc/styles/bouton_entete.php`, `lang/astra_fr.php`, `lang/astra_en.php`, `tests/unit/StylesTest.php`, `tests/comparaison/configurations.php`

**Interfaces :**
- Produit : `astra_styles_bouton_entete_marge(array $s): array` (`inc/styles/bouton_entete.php`).

- [ ] **Étape 1 : écrire le test et les références**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 80cbb75..8b51e46 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -187,6 +187,9 @@ function astra_configurations_reference(): array {
 		'R19-compat-polices-widgets' => $styles([], $titres + ['compatibilite' => ['can-support-widget-and-editor-fonts' => false]]),
 		'R20-titres-graisses' => $styles(['titres'], $titres),
 		'R20-titres-graisses-drapeau' => $styles(['titres'], $titres + ['compatibilite' => ['can-support-widget-and-editor-fonts' => false]]),
+		'R21-bouton-entete-marge' => $styles(['bouton-entete-marge']),
+		'R21-bouton-entete-marge-valeur' => $styles(['bouton-entete-marge-valeur']),
+		'R21-bouton-entete-marge-vide' => $styles(['bouton-entete-marge-vide']),
 		'P1-page' => [
 			'page' => 'notre_jardin',
 			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
diff --git a/tests/references/styles/valeurs/bouton-entete-marge-valeur.json b/tests/references/styles/valeurs/bouton-entete-marge-valeur.json
new file mode 100644
index 0000000..c56bd6d
--- /dev/null
+++ b/tests/references/styles/valeurs/bouton-entete-marge-valeur.json
@@ -0,0 +1,25 @@
+{
+	"section-hb-button-1-padding": {
+		"desktop": {
+			"top": "10",
+			"right": "20",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"mobile": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	}
+}
diff --git a/tests/references/styles/valeurs/bouton-entete-marge-vide.json b/tests/references/styles/valeurs/bouton-entete-marge-vide.json
new file mode 100644
index 0000000..41be439
--- /dev/null
+++ b/tests/references/styles/valeurs/bouton-entete-marge-vide.json
@@ -0,0 +1,25 @@
+{
+	"section-hb-button-1-padding": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"mobile": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	}
+}
diff --git a/tests/references/styles/valeurs/bouton-entete-marge.json b/tests/references/styles/valeurs/bouton-entete-marge.json
new file mode 100644
index 0000000..f0de1de
--- /dev/null
+++ b/tests/references/styles/valeurs/bouton-entete-marge.json
@@ -0,0 +1,48 @@
+{
+	"section-hb-button-1-padding": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"mobile": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"theme-button-padding": {
+		"desktop": {
+			"top": "17",
+			"right": "26",
+			"bottom": "17",
+			"left": "26"
+		},
+		"tablet": {
+			"top": "17",
+			"right": "26",
+			"bottom": "17",
+			"left": "26"
+		},
+		"mobile": {
+			"top": "17",
+			"right": "26",
+			"bottom": "17",
+			"left": "26"
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	}
+}
diff --git a/tests/references/wp/R21-bouton-entete-marge-valeur.json b/tests/references/wp/R21-bouton-entete-marge-valeur.json
new file mode 100644
index 0000000..8f77e1e
--- /dev/null
+++ b/tests/references/wp/R21-bouton-entete-marge-valeur.json
@@ -0,0 +1,30 @@
+{
+	"ordinateur": [
+		".site-primary-header-wrap .ast-builder-menu-1",
+		".site-primary-header-wrap .ast-header-button-1",
+		".site-primary-footer-wrap .footer-widget-area",
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".ast-header-button-1 .ast-custom-button",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R21-bouton-entete-marge-valeur.php b/tests/references/wp/R21-bouton-entete-marge-valeur.php
new file mode 100644
index 0000000..f9ca270
--- /dev/null
+++ b/tests/references/wp/R21-bouton-entete-marge-valeur.php
@@ -0,0 +1,5 @@
+<?php
+// Marge du bouton de l'en-tête en partie réglée (haut 10, droite 20), autres côtés vides.
+// Placement des références des styles ; valeurs : styles/valeurs/bouton-entete-marge-valeur.json.
+putenv('GROUPE=bouton-entete-marge-valeur');
+require dirname(__DIR__) . '/styles/appliquer.php';
diff --git a/tests/references/wp/R21-bouton-entete-marge-vide.json b/tests/references/wp/R21-bouton-entete-marge-vide.json
new file mode 100644
index 0000000..8f77e1e
--- /dev/null
+++ b/tests/references/wp/R21-bouton-entete-marge-vide.json
@@ -0,0 +1,30 @@
+{
+	"ordinateur": [
+		".site-primary-header-wrap .ast-builder-menu-1",
+		".site-primary-header-wrap .ast-header-button-1",
+		".site-primary-footer-wrap .footer-widget-area",
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".ast-header-button-1 .ast-custom-button",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R21-bouton-entete-marge-vide.php b/tests/references/wp/R21-bouton-entete-marge-vide.php
new file mode 100644
index 0000000..302d31a
--- /dev/null
+++ b/tests/references/wp/R21-bouton-entete-marge-vide.php
@@ -0,0 +1,5 @@
+<?php
+// Marge du bouton de l'en-tête enregistrée vide, marge des boutons d'Astra (selon l'appareil).
+// Placement des références des styles ; valeurs : styles/valeurs/bouton-entete-marge-vide.json.
+putenv('GROUPE=bouton-entete-marge-vide');
+require dirname(__DIR__) . '/styles/appliquer.php';
diff --git a/tests/references/wp/R21-bouton-entete-marge.json b/tests/references/wp/R21-bouton-entete-marge.json
new file mode 100644
index 0000000..8f77e1e
--- /dev/null
+++ b/tests/references/wp/R21-bouton-entete-marge.json
@@ -0,0 +1,30 @@
+{
+	"ordinateur": [
+		".site-primary-header-wrap .ast-builder-menu-1",
+		".site-primary-header-wrap .ast-header-button-1",
+		".site-primary-footer-wrap .footer-widget-area",
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".ast-header-button-1 .ast-custom-button",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R21-bouton-entete-marge.php b/tests/references/wp/R21-bouton-entete-marge.php
new file mode 100644
index 0000000..484ad0a
--- /dev/null
+++ b/tests/references/wp/R21-bouton-entete-marge.php
@@ -0,0 +1,6 @@
+<?php
+// Marge du bouton de l'en-tête enregistrée vide, marge des boutons à 17 × 26 (valeurs de
+// Garden Maintenance). Placement des références des styles ; valeurs :
+// styles/valeurs/bouton-entete-marge.json.
+putenv('GROUPE=bouton-entete-marge');
+require dirname(__DIR__) . '/styles/appliquer.php';
diff --git a/tests/unit/StylesTest.php b/tests/unit/StylesTest.php
index dd7515c..432ee5c 100644
--- a/tests/unit/StylesTest.php
+++ b/tests/unit/StylesTest.php
@@ -153,6 +153,23 @@ final class StylesTest extends TestCase {
 		$this->assertStringContainsString('.ast-header-button-1 .ast-custom-button{border-top-left-radius:5px;border-top-right-radius:3px;border-bottom-right-radius:3px;border-bottom-left-radius:3px;}', $css);
 	}
 
+	public function testMargeBoutonEnteteCommeAstra(): void {
+		$sel = '.ast-header-button-1[data-section*="section-hb-button-"] .ast-builder-button-wrap .ast-custom-button';
+		// Sans l'option : marge par défaut de css/astra-hfb.css, rien d'émis.
+		$this->assertStringNotContainsString($sel, astra_styles_css(['header-button1-text-color' => ['desktop' => '#111111']], self::HFB_MENU_BOUTON));
+		// Enregistrée vide, marge des boutons à 17 × 26 (Garden) : la marge des boutons, sur tous les appareils.
+		$css = astra_styles_css(self::valeurs('bouton-entete-marge'), self::HFB_MENU_BOUTON);
+		$this->assertStringContainsString($sel . '{padding-top:17px;padding-right:26px;padding-bottom:17px;padding-left:26px;}', $css);
+		$this->assertStringNotContainsString('@media (max-width:921px){' . $sel, $css);
+		// En partie réglée, sans marge des boutons : côtés vides selon l'appareil (15/30, 14/28, 12/24).
+		$css = astra_styles_css(self::valeurs('bouton-entete-marge-valeur'), self::HFB_MENU_BOUTON);
+		$this->assertStringContainsString($sel . '{padding-top:10px;padding-right:20px;}' . $sel . '{padding-bottom:15px;padding-left:30px;}', $css);
+		$this->assertStringContainsString('@media (max-width:921px){' . $sel . '{padding-bottom:14px;padding-left:28px;}}', $css);
+		$this->assertStringContainsString('@media (max-width:544px){' . $sel . '{padding-bottom:12px;padding-left:24px;}}', $css);
+		// Bouton absent de l'en-tête : rien.
+		$this->assertSame('', astra_styles_css(self::valeurs('bouton-entete-marge-vide'), ['entete_ordinateur' => ['primary' => ['primary_right' => ['menu-1']]]]));
+	}
+
 	public function testSoulignementBouton(): void {
 		$css = astra_styles_css(['font-extras-button' => ['text-decoration' => 'underline']], []);
 		// Blocs, boutons du thème, puis surcharge du soulignement des liens de contenu.
````

- [ ] **Étape 2 : vérifier qu'il échoue**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 1 échec (`StylesTest::testMargeBoutonEnteteCommeAstra`) ;
- intégration : OK (82 tests, 283 assertions).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/inc/astra_styles.php b/inc/astra_styles.php
index 9816b6e..c2a1db1 100644
--- a/inc/astra_styles.php
+++ b/inc/astra_styles.php
@@ -91,6 +91,9 @@ function astra_styles_registre(): array {
 		'header-button1-font-weight' => ['type' => 'graisse', 'defaut' => 'inherit'],
 		'header-button1-font-extras' => ['type' => 'extras-police'],
 	]);
+	$ajouter('bouton-entete', 'inc/class-astra-extended-base-dynamic-css.php', [
+		'section-hb-button-1-padding' => $cotes_r + ['defaut' => ['desktop' => ['top' => 15, 'right' => 30, 'bottom' => 15, 'left' => 30]]],
+	]);
 	$ajouter('identite', 'inc/class-astra-dynamic-css.php', [
 		'font-size-site-title' => $taille_r + ['defaut' => ['desktop' => 26, 'desktop-unit' => 'px']],
 		'font-size-site-tagline' => $taille_r + ['defaut' => ['desktop' => 15, 'desktop-unit' => 'px']],
diff --git a/inc/astra_styles_saisie.php b/inc/astra_styles_saisie.php
index f2c270d..43918c9 100644
--- a/inc/astra_styles_saisie.php
+++ b/inc/astra_styles_saisie.php
@@ -17,7 +17,7 @@ function astra_styles_sections(): array {
 		'menu_1' => '/^header-menu1-/',
 		'menu_2' => '/^header-menu2-/',
 		'bouton' => '/^(button-|theme-button-|font-[a-z]+-button$)/',
-		'bouton_entete' => '/^header-button1-/',
+		'bouton_entete' => '/^(header-button1-|section-hb-button-1-)/',
 		'identite' => '/^(?!(hba-|hbb-)?transparent-).*(site-title|site-tagline|logo-width)/',
 		'widget_1' => '/^footer-widget-1-/',
 		'widget_2' => '/^footer-widget-2-/',
@@ -34,7 +34,7 @@ function astra_styles_sections(): array {
 /** Clé de langue du libellé d'une option : les options d'éléments semblables la partagent. */
 function astra_styles_libelle(string $option): string {
 	$o = preg_replace(
-		['/^header-menu\d-/', '/^footer-widget-\d-/', '/^header-button1-/', '/^(header|footer)-social-1-/'],
+		['/^header-menu\d-/', '/^footer-widget-\d-/', '/^(header-button1|section-hb-button-1)-/', '/^(header|footer)-social-1-/'],
 		['menu-', 'widget-', 'bouton-entete-', 'social-'],
 		$option
 	);
diff --git a/inc/styles/bouton_entete.php b/inc/styles/bouton_entete.php
index 37e9776..9ba68da 100644
--- a/inc/styles/bouton_entete.php
+++ b/inc/styles/bouton_entete.php
@@ -8,9 +8,13 @@
 
 function astra_styles_bouton_entete(array $s, array $hfb): array {
 	$p = 'header-button1';
-	if (!astra_styles_pose($s, 'bouton-entete') || !astra_styles_element_place('button-1', $hfb, 'entete')) {
+	if (!astra_styles_element_place('button-1', $hfb, 'entete')) {
 		return [];
 	}
+	// Marge intérieure (section-hb-button-1-padding) : émise même enregistrée vide.
+	if (!astra_styles_pose($s, 'bouton-entete', "$p-")) {
+		return astra_styles_bouton_entete_marge($s);
+	}
 	$sel = '.ast-header-button-1';
 	$extras = astra_styles_extras($s["$p-font-extras"] ?? []);
 	$graisse = astra_styles_ou($s, "$p-font-weight");
@@ -55,5 +59,42 @@ function astra_styles_bouton_entete(array $s, array $hfb): array {
 			'border-bottom-right-radius' => $rayon['bottom'], 'border-bottom-left-radius' => $rayon['left'],
 		]];
 	}
+	return array_merge($regles, astra_styles_bouton_entete_marge($s));
+}
+
+/**
+ * Marge intérieure du bouton de l'en-tête (prepare_advanced_margin_padding_css() de
+ * inc/class-astra-extended-base-dynamic-css.php, valeur ordinateur). Sans l'option, la marge par
+ * défaut d'Astra (15 × 30 px) est celle de css/astra-hfb.css.
+ */
+function astra_styles_bouton_entete_marge(array $s): array {
+	$marge = $s['section-hb-button-1-padding'] ?? null;
+	if ($marge === null) {
+		return [];
+	}
+	$sel = '.ast-header-button-1[data-section*="section-hb-button-"] .ast-builder-button-wrap .ast-custom-button';
+	$cotes = ['top' => [15, 14, 12], 'right' => [30, 28, 24], 'bottom' => [15, 14, 12], 'left' => [30, 28, 24]];
+	$valeurs = $neutres = [];
+	foreach (array_keys($cotes) as $cote) {
+		$valeurs["padding-$cote"] = astra_styles_cote($marge, $cote);
+	}
+	$regles = [['', $sel, $valeurs]];
+	// Neutralisation : une fois l'option enregistrée, Astra n'émet pas ses côtés vides ; ils prennent
+	// la marge des boutons (theme-button-padding), que css/astra-hfb.css masquerait. Sans marge des
+	// boutons réglée, celle-ci dépend de l'appareil (css/astra-dynamic.css : 15/30, 14/28, 12/24).
+	$boutons = $s['theme-button-padding'] ?? '';
+	$reglee = astra_styles_renseigne(astra_styles_ordi($boutons));
+	$medias = ['' => 0, '(max-width:921px)' => 1, '(max-width:544px)' => 2];
+	foreach ($medias as $media => $i) {
+		$d = [];
+		foreach ($cotes as $cote => $defauts) {
+			if ($valeurs["padding-$cote"] === '') {
+				$d["padding-$cote"] = $reglee ? astra_styles_cote($boutons, $cote, $defauts[0]) : $defauts[$i] . 'px';
+			}
+		}
+		if ($d && ($media === '' || !$reglee)) {
+			$regles[] = [$media, $sel, $d];
+		}
+	}
 	return $regles;
 }
diff --git a/lang/astra_en.php b/lang/astra_en.php
index 989e86c..7b638e3 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -246,6 +246,7 @@ return [
 	'style_bouton_entete_font_family' => 'Font',
 	'style_bouton_entete_font_size' => 'Font size',
 	'style_bouton_entete_font_weight' => 'Font weight',
+	'style_bouton_entete_padding' => 'Padding',
 	'style_bouton_entete_text_color' => 'Text color',
 	'style_bouton_entete_text_h_color' => 'Text color on hover',
 	'style_button_bg_color' => 'Background color',
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index 18b973c..cd4569d 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -246,6 +246,7 @@ return [
 	'style_bouton_entete_font_family' => 'Police',
 	'style_bouton_entete_font_size' => 'Taille du texte',
 	'style_bouton_entete_font_weight' => 'Graisse',
+	'style_bouton_entete_padding' => 'Marge intérieure',
 	'style_bouton_entete_text_color' => 'Couleur du texte',
 	'style_bouton_entete_text_h_color' => 'Couleur du texte au survol',
 	'style_button_bg_color' => 'Couleur de fond',
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (110 tests, 1523 assertions)`, intégration `OK (82 tests, 283 assertions)`

```bash
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide
```

Attendu : HTML identique ; toutes les vues sous le seuil ; 4 valeurs de marge égales aux trois largeurs pour chaque référence.

- [ ] **Étape 5 : commit**

```bash
git add inc/astra_styles.php inc/astra_styles_saisie.php inc/styles/bouton_entete.php lang/astra_fr.php lang/astra_en.php tests/unit/StylesTest.php tests/references/styles/valeurs/bouton-entete-marge.json tests/references/styles/valeurs/bouton-entete-marge-valeur.json tests/references/styles/valeurs/bouton-entete-marge-vide.json tests/references/wp/R21-bouton-entete-marge.php tests/references/wp/R21-bouton-entete-marge.json tests/references/wp/R21-bouton-entete-marge-valeur.php tests/references/wp/R21-bouton-entete-marge-valeur.json tests/references/wp/R21-bouton-entete-marge-vide.php tests/references/wp/R21-bouton-entete-marge-vide.json tests/comparaison/configurations.php
git commit -m "Styles : marge intérieure du bouton de l'en-tête"
```

### Tâche 4 : alignements du copyright et des icônes sociales du pied

Spec § 3 et décisions 1 et 6. `footer-copyright-alignment` et `footer-social-1-alignment` (type `alignement`, groupe `alignements`) : dès que l'option est enregistrée et l'élément placé dans le pied, les trois règles, dans l'ordre et les media queries d'Astra, chaque appareil sans valeur prenant celle que la cascade d'Astra lui donne, à défaut l'alignement sans règle d'Astra (hérité pour le copyright, centré pour les icônes sociales). Le formulaire propose trois sélecteurs ; la fusion de la saisie garde les trois appareils pour ce type, et une première saisie sur la tablette ou le mobile seuls.

**Fichiers :**
- Créer : `inc/styles/alignements.php`, `tests/references/styles/valeurs/alignements{,-ordinateur,-tablette}.json`, `tests/references/wp/R22-pied-alignements{,-ordinateur,-tablette}.{php,json}`
- Modifier : `inc/astra_styles.php`, `inc/astra_styles_saisie.php`, `formulaires/configurer_astra_styles.html`, `lang/astra_fr.php`, `lang/astra_en.php`, `tests/unit/StylesTest.php`, `tests/unit/StylesSaisieTest.php`, `tests/integration/StylesFormulaireTest.php`, `tests/comparaison/configurations.php`

**Interfaces :**
- Produit : `astra_styles_alignements(array $s, array $hfb): array`, `ASTRA_STYLES_ALIGNEMENTS` (`inc/styles/alignements.php`) ; type `alignement` dans `astra_styles_valider()` ; champ `appareils` d'`astra_styles_champs()`.

- [ ] **Étape 1 : écrire les tests et les références**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 8b51e46..6f0c9a2 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -190,6 +190,9 @@ function astra_configurations_reference(): array {
 		'R21-bouton-entete-marge' => $styles(['bouton-entete-marge']),
 		'R21-bouton-entete-marge-valeur' => $styles(['bouton-entete-marge-valeur']),
 		'R21-bouton-entete-marge-vide' => $styles(['bouton-entete-marge-vide']),
+		'R22-pied-alignements' => $styles(['alignements']),
+		'R22-pied-alignements-ordinateur' => $styles(['alignements-ordinateur']),
+		'R22-pied-alignements-tablette' => $styles(['alignements-tablette']),
 		'P1-page' => [
 			'page' => 'notre_jardin',
 			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
diff --git a/tests/integration/StylesFormulaireTest.php b/tests/integration/StylesFormulaireTest.php
index b7655fa..1e324e4 100644
--- a/tests/integration/StylesFormulaireTest.php
+++ b/tests/integration/StylesFormulaireTest.php
@@ -68,6 +68,34 @@ final class StylesFormulaireTest extends TestCase {
 		$this->assertFalse(astra_entete_transparent_actif(lire_config('astra/styles'), 'rubrique'));
 	}
 
+	public function testAllerRetourDesReglagesDeCompatibilite(): void {
+		// Valeurs de Garden : graisses des titres, marge du bouton de l'en-tête enregistrée vide,
+		// alignements des trois appareils. Un enregistrement sans modification ne change pas le CSS.
+		$dir = dirname(__DIR__) . '/references/styles/valeurs';
+		$garden = ['headings-font-weight' => '600'];
+		for ($n = 1; $n <= 6; $n++) {
+			$garden["font-weight-h$n"] = 'inherit';
+		}
+		$garden += json_decode(file_get_contents("$dir/bouton-entete-marge.json"), true) + json_decode(file_get_contents("$dir/alignements.json"), true);
+		$hfb = ['pied' => ['below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]], 'rangees' => ['pied_below' => ['colonnes' => 2]]];
+		$c = ['can-support-widget-and-editor-fonts' => true];
+		ecrire_config('astra/styles', $garden);
+		$avant = astra_styles_css($garden, $hfb, $c);
+		$this->assertStringContainsString('@media (max-width:544px){.ast-footer-copyright{text-align:center;}', $avant);
+		$this->assertStringContainsString('.footer-social-inner-wrap{text-align:right;}', $avant);
+		$saisie = $this->saisieDuFormulaire();
+		$this->assertSame(['desktop' => 'left', 'tablet' => 'left', 'mobile' => 'center'], $saisie['footer-copyright-alignment']);
+		set_request('s', $saisie);
+		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
+		formulaires_configurer_astra_styles_traiter_dist();
+		$this->assertSame($avant, astra_styles_css(lire_config('astra/styles'), $hfb, $c));
+		// Alignement mobile changé par le formulaire : enregistré.
+		$saisie['footer-copyright-alignment']['mobile'] = 'right';
+		set_request('s', $saisie);
+		formulaires_configurer_astra_styles_traiter_dist();
+		$this->assertSame('right', lire_config('astra/styles')['footer-copyright-alignment']['mobile']);
+	}
+
 	/** Champs du formulaire rendu, postés tels quels (sélecteurs : option choisie, sinon la première). */
 	private function saisieDuFormulaire(): array {
 		$doc = new DOMDocument();
diff --git a/tests/references/styles/valeurs/alignements-ordinateur.json b/tests/references/styles/valeurs/alignements-ordinateur.json
new file mode 100644
index 0000000..b8feac6
--- /dev/null
+++ b/tests/references/styles/valeurs/alignements-ordinateur.json
@@ -0,0 +1,10 @@
+{
+	"footer-copyright-alignment": {
+		"desktop": "left"
+	},
+	"footer-social-1-alignment": {
+		"desktop": "right",
+		"tablet": "",
+		"mobile": ""
+	}
+}
diff --git a/tests/references/styles/valeurs/alignements-tablette.json b/tests/references/styles/valeurs/alignements-tablette.json
new file mode 100644
index 0000000..01caead
--- /dev/null
+++ b/tests/references/styles/valeurs/alignements-tablette.json
@@ -0,0 +1,8 @@
+{
+	"footer-copyright-alignment": {
+		"tablet": "right"
+	},
+	"footer-social-1-alignment": {
+		"tablet": "right"
+	}
+}
diff --git a/tests/references/styles/valeurs/alignements.json b/tests/references/styles/valeurs/alignements.json
new file mode 100644
index 0000000..aa89904
--- /dev/null
+++ b/tests/references/styles/valeurs/alignements.json
@@ -0,0 +1,12 @@
+{
+	"footer-copyright-alignment": {
+		"desktop": "left",
+		"tablet": "left",
+		"mobile": "center"
+	},
+	"footer-social-1-alignment": {
+		"desktop": "right",
+		"tablet": "right",
+		"mobile": "center"
+	}
+}
diff --git a/tests/references/wp/R22-pied-alignements-ordinateur.json b/tests/references/wp/R22-pied-alignements-ordinateur.json
new file mode 100644
index 0000000..97ec4a6
--- /dev/null
+++ b/tests/references/wp/R22-pied-alignements-ordinateur.json
@@ -0,0 +1,33 @@
+{
+	"ordinateur": [
+		".site-primary-header-wrap .ast-builder-menu-1",
+		".site-primary-header-wrap .ast-header-button-1",
+		".site-primary-footer-wrap .footer-widget-area",
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".ast-footer-copyright",
+			"proprietes": [
+				"text-align"
+			]
+		},
+		{
+			"selecteur": "[data-section=\"section-fb-social-icons-1\"] .footer-social-inner-wrap",
+			"proprietes": [
+				"text-align"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R22-pied-alignements-ordinateur.php b/tests/references/wp/R22-pied-alignements-ordinateur.php
new file mode 100644
index 0000000..a703499
--- /dev/null
+++ b/tests/references/wp/R22-pied-alignements-ordinateur.php
@@ -0,0 +1,5 @@
+<?php
+// Alignements réglés pour l'ordinateur seul : tablette et mobile prennent la valeur de
+// l'ordinateur, comme dans Astra. Valeurs : styles/valeurs/alignements-ordinateur.json.
+putenv('GROUPE=alignements-ordinateur');
+require dirname(__DIR__) . '/styles/appliquer.php';
diff --git a/tests/references/wp/R22-pied-alignements-tablette.json b/tests/references/wp/R22-pied-alignements-tablette.json
new file mode 100644
index 0000000..97ec4a6
--- /dev/null
+++ b/tests/references/wp/R22-pied-alignements-tablette.json
@@ -0,0 +1,33 @@
+{
+	"ordinateur": [
+		".site-primary-header-wrap .ast-builder-menu-1",
+		".site-primary-header-wrap .ast-header-button-1",
+		".site-primary-footer-wrap .footer-widget-area",
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".ast-footer-copyright",
+			"proprietes": [
+				"text-align"
+			]
+		},
+		{
+			"selecteur": "[data-section=\"section-fb-social-icons-1\"] .footer-social-inner-wrap",
+			"proprietes": [
+				"text-align"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R22-pied-alignements-tablette.php b/tests/references/wp/R22-pied-alignements-tablette.php
new file mode 100644
index 0000000..75728b0
--- /dev/null
+++ b/tests/references/wp/R22-pied-alignements-tablette.php
@@ -0,0 +1,6 @@
+<?php
+// Alignements réglés pour la tablette seule : sur ordinateur, le copyright hérite et les icônes
+// sociales restent centrées (règle fixe d'Astra) ; le mobile prend la valeur de la tablette.
+// Valeurs : styles/valeurs/alignements-tablette.json.
+putenv('GROUPE=alignements-tablette');
+require dirname(__DIR__) . '/styles/appliquer.php';
diff --git a/tests/references/wp/R22-pied-alignements.json b/tests/references/wp/R22-pied-alignements.json
new file mode 100644
index 0000000..97ec4a6
--- /dev/null
+++ b/tests/references/wp/R22-pied-alignements.json
@@ -0,0 +1,33 @@
+{
+	"ordinateur": [
+		".site-primary-header-wrap .ast-builder-menu-1",
+		".site-primary-header-wrap .ast-header-button-1",
+		".site-primary-footer-wrap .footer-widget-area",
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".ast-footer-copyright",
+			"proprietes": [
+				"text-align"
+			]
+		},
+		{
+			"selecteur": "[data-section=\"section-fb-social-icons-1\"] .footer-social-inner-wrap",
+			"proprietes": [
+				"text-align"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R22-pied-alignements.php b/tests/references/wp/R22-pied-alignements.php
new file mode 100644
index 0000000..f9bd715
--- /dev/null
+++ b/tests/references/wp/R22-pied-alignements.php
@@ -0,0 +1,5 @@
+<?php
+// Alignements du copyright et des icônes sociales du pied de Garden Maintenance, sur les trois
+// appareils. Placement des références des styles ; valeurs : styles/valeurs/alignements.json.
+putenv('GROUPE=alignements');
+require dirname(__DIR__) . '/styles/appliquer.php';
diff --git a/tests/unit/StylesSaisieTest.php b/tests/unit/StylesSaisieTest.php
index fd58a5e..794b7a4 100644
--- a/tests/unit/StylesSaisieTest.php
+++ b/tests/unit/StylesSaisieTest.php
@@ -69,6 +69,13 @@ final class StylesSaisieTest extends TestCase {
 		$this->assertSame([], astra_styles_saisie_fusionner([], ['button-color' => '', 'header-menu1-color-responsive' => ['desktop' => '']]));
 	}
 
+	public function testPremiereSaisieDUnAlignementMobileSeul(): void {
+		// Alignement saisi pour le mobile seul : enregistré dès la première saisie.
+		$saisie = ['footer-copyright-alignment' => ['desktop' => '', 'tablet' => '', 'mobile' => 'right']];
+		$this->assertSame($saisie, astra_styles_saisie_fusionner([], $saisie));
+		$this->assertSame([], astra_styles_saisie_fusionner([], ['footer-copyright-alignment' => ['desktop' => '', 'tablet' => '', 'mobile' => '']]));
+	}
+
 	public function testValeursRefusees(): void {
 		$this->assertSame(
 			['button-color', 'font-size-button'],
diff --git a/tests/unit/StylesTest.php b/tests/unit/StylesTest.php
index 432ee5c..eb0c7c5 100644
--- a/tests/unit/StylesTest.php
+++ b/tests/unit/StylesTest.php
@@ -170,6 +170,26 @@ final class StylesTest extends TestCase {
 		$this->assertSame('', astra_styles_css(self::valeurs('bouton-entete-marge-vide'), ['entete_ordinateur' => ['primary' => ['primary_right' => ['menu-1']]]]));
 	}
 
+	public function testAlignementsDuPiedSurLesTroisAppareils(): void {
+		$pied = ['pied' => ['below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]], 'rangees' => ['pied_below' => ['colonnes' => 2]]];
+		$social = '[data-section="section-fb-social-icons-1"] .footer-social-inner-wrap';
+		// Valeurs de Garden : les trois règles, dans l'ordre et les media queries d'Astra.
+		$css = astra_styles_css(self::valeurs('alignements'), $pied);
+		$this->assertStringContainsString('.ast-footer-copyright{text-align:left;}@media (max-width:921px){.ast-footer-copyright{text-align:left;}}@media (max-width:544px){.ast-footer-copyright{text-align:center;}}', $css);
+		$this->assertStringContainsString($social . '{text-align:right;}@media (max-width:921px){' . $social . '{text-align:right;}}@media (max-width:544px){' . $social . '{text-align:center;}}', $css);
+		// Ordinateur seul : tablette et mobile prennent sa valeur (aucune règle d'Astra ne les recentre).
+		$css = astra_styles_css(['footer-copyright-alignment' => ['desktop' => 'left']], $pied);
+		$this->assertStringContainsString('@media (max-width:544px){.ast-footer-copyright{text-align:left;}}', $css);
+		// Tablette seule : sur ordinateur, le copyright hérite et les icônes sociales restent centrées
+		// (règle fixe de css/astra-hfb.css) ; le mobile prend la valeur de la tablette.
+		$css = astra_styles_css(self::valeurs('alignements-tablette'), $pied);
+		$this->assertStringContainsString('.ast-footer-copyright{text-align:inherit;}@media (max-width:921px){.ast-footer-copyright{text-align:right;}}@media (max-width:544px){.ast-footer-copyright{text-align:right;}}', $css);
+		$this->assertStringContainsString($social . '{text-align:center;}@media (max-width:921px){' . $social . '{text-align:right;}}@media (max-width:544px){' . $social . '{text-align:right;}}', $css);
+		// Élément absent du pied : rien.
+		$this->assertSame('', astra_styles_css(self::valeurs('alignements'), ['pied' => ['below' => ['below_1' => ['html-1']]]]));
+		$this->assertSame([], astra_styles_normaliser(['footer-copyright-alignment' => ['desktop' => 'justify']]));
+	}
+
 	public function testSoulignementBouton(): void {
 		$css = astra_styles_css(['font-extras-button' => ['text-decoration' => 'underline']], []);
 		// Blocs, boutons du thème, puis surcharge du soulignement des liens de contenu.
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 2 échecs (`StylesSaisieTest::testPremiereSaisieDUnAlignementMobileSeul`, `StylesTest::testAlignementsDuPiedSurLesTroisAppareils`) ;
- intégration : 1 échec (`StylesFormulaireTest::testAllerRetourDesReglagesDeCompatibilite`).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/formulaires/configurer_astra_styles.html b/formulaires/configurer_astra_styles.html
index 7f9dd6b..a36b1cb 100644
--- a/formulaires/configurer_astra_styles.html
+++ b/formulaires/configurer_astra_styles.html
@@ -29,7 +29,7 @@
 			#SET{erreur,#ENV*{erreurs}|table_valeur{#VAL{s_}|concat{#GET{c}|table_valeur{option}}}}
 			<div class="editer editer_s_[(#GET{c}|table_valeur{option})][ (#GET{erreur}|oui)erreur]">
 				<label for="s_[(#GET{c}|table_valeur{option})]">[(#GET{c}|table_valeur{libelle}|_T)]</label>
-				[(#GET{c}|table_valeur{responsive}|oui)<p class="explication"><:astra:styles_responsive:></p>]
+				[(#GET{c}|table_valeur{responsive}|et{#GET{c}|table_valeur{appareils}|non}|oui)<p class="explication"><:astra:styles_responsive:></p>]
 				[<span class="erreur_message">(#GET{erreur})</span>]
 				<BOUCLE_couleur(CONDITION){si #GET{c}|table_valeur{type}|=={couleur}}>
 				<input type="text" class="text" id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom})]" value="[(#GET{c}|table_valeur{valeur})]" list="astra-styles-palette" />
@@ -60,6 +60,16 @@
 					<BOUCLE_valeurs(DATA){source tableau, #GET{c}|table_valeur{valeurs}}><option value="#VALEUR"[(#VALEUR|=={#GET{c}|table_valeur{valeur}}|oui)selected="selected"]>[(#VAL{astra:styles_enum_}|concat{#VALEUR}|_T)]</option></BOUCLE_valeurs>
 				</select>
 				</BOUCLE_enum>
+				<BOUCLE_alignement(CONDITION){si #GET{c}|table_valeur{type}|=={alignement}}>
+				<span class="astra-styles-appareils" id="s_[(#GET{c}|table_valeur{option})]">
+					<BOUCLE_appareils(DATA){source tableau, #GET{c}|table_valeur{appareils}}>
+					<select name="#VALEUR{nom}" aria-label="[(#VAL{astra:styles_appareil_}|concat{#VALEUR{appareil}}|_T)]">
+						<option value="">[(#VAL{astra:styles_appareil_}|concat{#VALEUR{appareil}}|_T)] : <:astra:styles_defaut:></option>
+						<BOUCLE_alignements(DATA){liste left, center, right}><option value="#VALEUR"[(#VALEUR|=={#_appareils:VALEUR{valeur}}|oui)selected="selected"]>[(#VAL{astra:styles_appareil_}|concat{#_appareils:VALEUR{appareil}}|_T)] : [(#VAL{astra:styles_enum_}|concat{#VALEUR}|_T)]</option></BOUCLE_alignements>
+					</select>
+					</BOUCLE_appareils>
+				</span>
+				</BOUCLE_alignement>
 				<BOUCLE_booleen(CONDITION){si #GET{c}|table_valeur{type}|=={booleen}}>
 				#SET{v,#GET{c}|table_valeur{valeur}|strval}
 				<select id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom})]">
diff --git a/inc/astra_styles.php b/inc/astra_styles.php
index c2a1db1..3c0837b 100644
--- a/inc/astra_styles.php
+++ b/inc/astra_styles.php
@@ -129,6 +129,11 @@ function astra_styles_registre(): array {
 			"$b-social-1-color-type" => ['type' => 'enum', 'valeurs' => ['custom', 'official'], 'defaut' => 'custom'],
 		]);
 	}
+	// Alignements du pied : les trois appareils s'appliquent (type alignement), contrairement aux
+	// autres styles.
+	$alignement = ['type' => 'alignement', 'responsive' => true, 'defaut' => ['desktop' => 'center', 'tablet' => 'center', 'mobile' => 'center']];
+	$ajouter('alignements', 'inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php', ['footer-copyright-alignment' => $alignement]);
+	$ajouter('alignements', $sociaux, ['footer-social-1-alignment' => $alignement]);
 	$ajouter('menu-mobile', 'inc/builder/type/header/mobile-trigger/dynamic-css/dynamic.css.php', [
 		'mobile-header-toggle-btn-style' => ['type' => 'enum', 'valeurs' => ['minimal', 'fill', 'outline'], 'defaut' => 'minimal'],
 		'mobile-header-toggle-btn-color' => $couleur,
@@ -267,6 +272,8 @@ function astra_styles_valider(array $d, $v) {
 			return in_array((string) $v, ['', 'inherit', 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'], true) && !is_bool($v) ? $v : null;
 		case 'enum':
 			return in_array($v, $d['valeurs'], true) ? $v : null;
+		case 'alignement':
+			return in_array($v, ['', 'left', 'center', 'right'], true) ? $v : null;
 		case 'booleen':
 			return is_bool($v) || in_array($v, [0, 1, '0', '1', ''], true) ? $v : null;
 		case 'fond':
@@ -477,7 +484,7 @@ function astra_styles_ecrire(array $regles): string {
 }
 
 /** Modules de groupe (inc/styles/<module>.php), dans l'ordre où Astra émet leur CSS. */
-const ASTRA_STYLES_MODULES = ['titres', 'bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux', 'menu_mobile'];
+const ASTRA_STYLES_MODULES = ['titres', 'bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux', 'alignements', 'menu_mobile'];
 
 /**
  * CSS des styles du Customizer (vide sans réglage), dans l'ordre d'émission d'Astra. $compatibilite :
diff --git a/inc/astra_styles_saisie.php b/inc/astra_styles_saisie.php
index 43918c9..57ff39f 100644
--- a/inc/astra_styles_saisie.php
+++ b/inc/astra_styles_saisie.php
@@ -67,6 +67,10 @@ function astra_styles_champs($styles, string $section): array {
 			'unite' => is_array($v) ? ($v['desktop-unit'] ?? 'px') : 'px',
 			'unites' => $d['unites'] ?? [],
 			'valeurs' => $d['valeurs'] ?? [],
+			// Alignements : une valeur par appareil, toutes appliquées.
+			'appareils' => $d['type'] === 'alignement' ? array_map(function ($a) use ($option, $v) {
+				return ['appareil' => $a, 'nom' => "s[$option][$a]", 'valeur' => is_array($v) ? ($v[$a] ?? '') : ''];
+			}, ASTRA_STYLES_APPAREILS) : [],
 		];
 	}
 	return $champs;
@@ -79,7 +83,8 @@ function astra_styles_nom(string $nom, string $cle): string {
 
 /**
  * Fusionne la saisie dans les réglages enregistrés : pour une option responsive, seules la valeur
- * `desktop` et son unité changent, `tablet` et `mobile` sont gardés ; les champs d'une structure
+ * `desktop` et son unité changent, `tablet` et `mobile` sont gardés (sauf les alignements, saisis
+ * pour les trois appareils) ; les champs d'une structure
  * (côtés, fond, extras de police) absents de la saisie sont gardés. Une option jamais réglée et
  * laissée vide n'est pas enregistrée.
  */
@@ -96,7 +101,7 @@ function astra_styles_saisie_fusionner(array $existant, array $saisie): array {
 			foreach (is_array($v) ? $v : [] as $cle => $val) {
 				if ($cle === 'desktop' && is_array($val)) {
 					$x[$cle] = array_merge(is_array($x[$cle] ?? null) ? $x[$cle] : [], $val);
-				} elseif ($cle === 'desktop' || $cle === 'desktop-unit') {
+				} elseif ($cle === 'desktop' || $cle === 'desktop-unit' || ($d['type'] === 'alignement' && in_array($cle, ASTRA_STYLES_APPAREILS, true))) {
 					$x[$cle] = $val;
 				}
 			}
@@ -104,7 +109,10 @@ function astra_styles_saisie_fusionner(array $existant, array $saisie): array {
 		} elseif (is_array($v)) {
 			$v = array_merge(is_array($avant) ? $avant : [], $v);
 		}
-		if ($avant === null && !astra_styles_renseigne($d['responsive'] ? astra_styles_ordi($v) : $v)) {
+		// Première saisie : gardée si une valeur saisie est renseignée (l'ordinateur seul est saisi,
+		// sauf pour les alignements, saisis sur les trois appareils).
+		$saisi = $d['responsive'] && $d['type'] !== 'alignement' ? astra_styles_ordi($v) : $v;
+		if ($avant === null && !astra_styles_renseigne($saisi)) {
 			continue;
 		}
 		$r[$o] = $v;
diff --git a/inc/styles/alignements.php b/inc/styles/alignements.php
new file mode 100644
index 0000000..71b1c60
--- /dev/null
+++ b/inc/styles/alignements.php
@@ -0,0 +1,38 @@
+<?php
+/**
+ * Astra pour SPIP — alignements du copyright (inc/builder/type/footer/copyright/dynamic-css/
+ * dynamic.css.php) et des icônes sociales du pied (inc/builder/type/base/dynamic-css/social/
+ * class-astra-social-component-dynamic-css.php) d'Astra 4.11.0, sur les trois appareils.
+ *
+ * @license GPL-3.0-or-later
+ */
+
+/**
+ * Options d'alignement : option => [élément du pied, sélecteur d'Astra, alignement sans règle
+ * d'Astra]. Les icônes sociales restent centrées par une règle fixe de css/astra-hfb.css
+ * ([data-section^="section-fb-social-icons-"] .footer-social-inner-wrap) ; le copyright hérite.
+ */
+const ASTRA_STYLES_ALIGNEMENTS = [
+	'footer-copyright-alignment' => ['copyright', '.ast-footer-copyright', 'inherit'],
+	'footer-social-1-alignment' => ['social-icons-1', '[data-section="section-fb-social-icons-1"] .footer-social-inner-wrap', 'center'],
+];
+
+function astra_styles_alignements(array $s, array $hfb): array {
+	$regles = [];
+	foreach (ASTRA_STYLES_ALIGNEMENTS as $option => [$element, $sel, $sans]) {
+		if (!isset($s[$option]) || !astra_styles_element_place($element, $hfb, 'pied')) {
+			continue;
+		}
+		$v = $s[$option];
+		[$o, $t, $m] = [$v['desktop'] ?? '', $v['tablet'] ?? '', $v['mobile'] ?? ''];
+		// Astra émet la règle de chaque appareil qui a une valeur : sans media query pour
+		// l'ordinateur, puis 921 et 544 px. Neutralisation : css/astra-dynamic.css et
+		// css/astra-hfb.css centrent sur les trois appareils ; un appareil sans valeur prend celle
+		// que la cascade d'Astra lui donne (la tablette celle de l'ordinateur, le mobile celle de la
+		// tablette, sinon de l'ordinateur), à défaut celle qui s'applique sans règle d'Astra.
+		$regles[] = ['', $sel, ['text-align' => $o ?: $sans]];
+		$regles[] = ['(max-width:921px)', $sel, ['text-align' => $t ?: ($o ?: $sans)]];
+		$regles[] = ['(max-width:544px)', $sel, ['text-align' => $m ?: ($t ?: ($o ?: $sans))]];
+	}
+	return $regles;
+}
diff --git a/lang/astra_en.php b/lang/astra_en.php
index 7b638e3..5339fa8 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -269,6 +269,7 @@ return [
 	'style_font_weight_h4' => 'H4 font weight',
 	'style_font_weight_h5' => 'H5 font weight',
 	'style_font_weight_h6' => 'H6 font weight',
+	'style_footer_copyright_alignment' => 'Alignment',
 	'style_footer_copyright_color' => 'Text color',
 	'style_hba_transparent_header_bg_color_responsive' => 'Above row background',
 	'style_hbb_transparent_header_bg_color_responsive' => 'Below row background',
@@ -295,6 +296,7 @@ return [
 	'style_mobile_header_toggle_btn_color' => 'Icon color',
 	'style_mobile_header_toggle_btn_style' => 'Button style',
 	'style_mobile_header_toggle_icon_size' => 'Icon size (px)',
+	'style_social_alignment' => 'Alignment',
 	'style_social_color' => 'Icon color',
 	'style_social_color_type' => 'Icon colors',
 	'style_social_h_color' => 'Icon color on hover',
@@ -333,19 +335,25 @@ return [
 	'style_widget_link_color' => 'Link color',
 	'style_widget_link_h_color' => 'Link color on hover',
 	'style_widget_title_color' => 'Title color',
+	'styles_appareil_desktop' => 'Desktop',
+	'styles_appareil_mobile' => 'Mobile',
+	'styles_appareil_tablet' => 'Tablet',
 	'styles_bas' => 'bottom',
 	'styles_casse' => 'Case',
 	'styles_couleur_explication' => 'A palette color (<code>var(--ast-global-color-0)</code> to <code>8</code>), hexadecimal or <code>rgba()</code>.',
 	'styles_defaut' => 'Astra setting',
 	'styles_droite' => 'right',
 	'styles_enum_both' => 'Desktop and mobile',
+	'styles_enum_center' => 'center',
 	'styles_enum_custom' => 'Custom',
 	'styles_enum_desktop' => 'Desktop',
 	'styles_enum_fill' => 'Fill',
+	'styles_enum_left' => 'left',
 	'styles_enum_minimal' => 'Minimal',
 	'styles_enum_mobile' => 'Mobile',
 	'styles_enum_official' => 'Brand colors',
 	'styles_enum_outline' => 'Outline',
+	'styles_enum_right' => 'right',
 	'styles_erreurs_saisie' => 'Some values were refused: correct or clear them.',
 	'styles_espacement' => 'Letter spacing',
 	'styles_explication' => 'Astra Customizer settings, under the same names. An empty field keeps Astra’s setting.',
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index cd4569d..23afe36 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -269,6 +269,7 @@ return [
 	'style_font_weight_h4' => 'Graisse des titres h4',
 	'style_font_weight_h5' => 'Graisse des titres h5',
 	'style_font_weight_h6' => 'Graisse des titres h6',
+	'style_footer_copyright_alignment' => 'Alignement',
 	'style_footer_copyright_color' => 'Couleur du texte',
 	'style_hba_transparent_header_bg_color_responsive' => 'Fond de la rangée haute',
 	'style_hbb_transparent_header_bg_color_responsive' => 'Fond de la rangée basse',
@@ -295,6 +296,7 @@ return [
 	'style_mobile_header_toggle_btn_color' => 'Couleur de l’icône',
 	'style_mobile_header_toggle_btn_style' => 'Style du bouton',
 	'style_mobile_header_toggle_icon_size' => 'Taille de l’icône (px)',
+	'style_social_alignment' => 'Alignement',
 	'style_social_color' => 'Couleur des icônes',
 	'style_social_color_type' => 'Couleurs des icônes',
 	'style_social_h_color' => 'Couleur des icônes au survol',
@@ -333,19 +335,25 @@ return [
 	'style_widget_link_color' => 'Couleur des liens',
 	'style_widget_link_h_color' => 'Couleur des liens au survol',
 	'style_widget_title_color' => 'Couleur des titres',
+	'styles_appareil_desktop' => 'Ordinateur',
+	'styles_appareil_mobile' => 'Mobile',
+	'styles_appareil_tablet' => 'Tablette',
 	'styles_bas' => 'bas',
 	'styles_casse' => 'Casse',
 	'styles_couleur_explication' => 'Une couleur de la palette (<code>var(--ast-global-color-0)</code> à <code>8</code>), hexadécimale ou <code>rgba()</code>.',
 	'styles_defaut' => 'Réglage d’Astra',
 	'styles_droite' => 'droite',
 	'styles_enum_both' => 'Ordinateur et mobile',
+	'styles_enum_center' => 'centré',
 	'styles_enum_custom' => 'Personnalisées',
 	'styles_enum_desktop' => 'Ordinateur',
 	'styles_enum_fill' => 'Plein',
+	'styles_enum_left' => 'gauche',
 	'styles_enum_minimal' => 'Minimal',
 	'styles_enum_mobile' => 'Mobile',
 	'styles_enum_official' => 'Couleurs des marques',
 	'styles_enum_outline' => 'Contour',
+	'styles_enum_right' => 'droite',
 	'styles_erreurs_saisie' => 'Certaines valeurs sont refusées : corrigez-les ou videz-les.',
 	'styles_espacement' => 'Espacement des lettres',
 	'styles_explication' => 'Réglages du Customizer d’Astra, sous les mêmes noms. Un champ vide garde le réglage d’Astra.',
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (112 tests, 1548 assertions)`, intégration `OK (83 tests, 289 assertions)`

```bash
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette
URL=http://127.0.0.1:8414 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire_styles.cjs
```

Attendu : HTML identique ; toutes les vues sous le seuil ; 2 valeurs (`text-align`) égales aux trois largeurs pour chaque référence. Pour `formulaire_styles.cjs`, donner d'abord un slogan au site de test (`ecrire_meta('slogan_site', 'Un site propulsé par SPIP')`) et le retirer après : `Formulaire des styles : OK`.

- [ ] **Étape 5 : commit**

```bash
git add inc/styles/alignements.php inc/astra_styles.php inc/astra_styles_saisie.php formulaires/configurer_astra_styles.html lang/astra_fr.php lang/astra_en.php tests/unit/StylesTest.php tests/unit/StylesSaisieTest.php tests/integration/StylesFormulaireTest.php tests/references/styles/valeurs/alignements.json tests/references/styles/valeurs/alignements-ordinateur.json tests/references/styles/valeurs/alignements-tablette.json tests/references/wp/R22-pied-alignements.php tests/references/wp/R22-pied-alignements.json tests/references/wp/R22-pied-alignements-ordinateur.php tests/references/wp/R22-pied-alignements-ordinateur.json tests/references/wp/R22-pied-alignements-tablette.php tests/references/wp/R22-pied-alignements-tablette.json tests/comparaison/configurations.php
git commit -m "Styles : alignements du copyright et des icônes sociales du pied, trois appareils"
```

### Tâche 5 : valeurs de Garden ensemble, documentation, version 1.4.0

**Fichiers :**
- Créer : `tests/references/styles/valeurs/garden-compat.json`, `tests/references/wp/R23-garden-compat.{php,json}`
- Modifier : `paquet.xml`, `README.md`, `CREDITS.md`, `tests/references/README.md`, `tests/comparaison/comparer_captures.sh`, `tests/comparaison/configurations.php`

- [ ] **Étape 1 : appliquer**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/CREDITS.md b/CREDITS.md
index 1994dfc..b259224 100644
--- a/CREDITS.md
+++ b/CREDITS.md
@@ -23,13 +23,14 @@ et le texte de la GPL v2 dans [licenses/GPL-2.0-or-later.txt](licenses/GPL-2.0-o
 | `assets/css/minified/main.min.css` | `css/main.min.css` | Copie identique, vérifiée octet par octet contre l'archive officielle |
 | `assets/js/minified/frontend.min.js` | `js/frontend.min.js` | Copie identique, vérifiée octet par octet ; version lisible dans `assets/js/unminified/frontend.js` de l'archive |
 | CSS produit par le thème et ses réglages du Customizer | `css/astra-dynamic.css` | Extraction d'un rendu WordPress ; couleurs, polices et largeurs rendues configurables pour SPIP |
+| CSS des drapeaux de compatibilité : `Astra_Dynamic_CSS::astra_submenu_below_header_fix()` (`inc/class-astra-dynamic-css.php`) | `css/compat/*.css` | Extraction d'un rendu WordPress, drapeau posé ou non (`tests/references/extraire_css.py --compat`) |
 | CSS produit par le Header/Footer Builder | `css/astra-hfb.css`, styles générés par `inclure/head-vars.html` et `inc/astra_hfb.php` | Extraction et transposition des rangées, répartitions, espacements et réglages du builder ; extracteur : `tests/references/extraire_css.py` |
 | CSS des dispositions de page | `css/astra-pages.css` | Extraction des règles de page, dont la pleine largeur, par `tests/references/extraire_css.py --pages` |
 | `inc/dynamic-css/single-post.php` | `css/astra-article.css` | Sélection des règles touchant le contenu des articles ; chargement adapté à SPIP |
 | `inc/dynamic-css/comments-flex.php` | `css/astra-commentaires.css` | Règle d'espacement des listes ; chargement conditionné aux commentaires dans SPIP |
 | Gabarits `header.php`, `footer.php`, `page.php`, `single.php`, `archive.php`, `search.php`, `404.php`, `searchform.php`, `template-parts/` et composants du builder | Squelettes à la racine, `inclure/`, `inclure/hfb/`, `inclure/widgets/`, `formulaires/recherche.html`, `modeles/pagination_astra.html` | Reproduction et adaptation du balisage, des classes, des attributs et de la structure ; les requêtes et appels WordPress sont remplacés par les boucles, balises et formulaires SPIP |
 | Options, palette et organisation du builder | `astra_fonctions.php`, `inc/astra_hfb.php`, formulaires de configuration | Transposition des valeurs par défaut et des conventions Astra ; interface et stockage adaptés à SPIP |
-| Styles du Customizer : `inc/class-astra-dynamic-css.php` (bouton, identité), `inc/builder/type/header/menu/dynamic-css/dynamic.css.php`, `inc/builder/type/base/dynamic-css/{button,widget,social}/class-astra-*-component-dynamic-css.php`, `inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php` | `inc/astra_styles.php`, `inc/styles/*.php` | Transposition des règles ordinateur : sélecteurs (ceux du bouton global copiés à l'identique), ordre, conditions d'émission et valeurs par défaut ; écriture comme `astra_parse_css()` et `trim_css()` |
+| Styles du Customizer : `inc/class-astra-dynamic-css.php` (bouton, identité, graisses des titres et `support_font_css_to_widget_and_in_editor()`), `inc/class-astra-extended-base-dynamic-css.php` (marge du bouton de l'en-tête), `inc/builder/type/header/menu/dynamic-css/dynamic.css.php`, `inc/builder/type/base/dynamic-css/{button,widget,social}/class-astra-*-component-dynamic-css.php`, `inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php` | `inc/astra_styles.php`, `inc/styles/*.php` | Transposition des règles ordinateur : sélecteurs (ceux du bouton global copiés à l'identique), ordre, conditions d'émission et valeurs par défaut ; écriture comme `astra_parse_css()` et `trim_css()` |
 | En-tête transparent : `inc/addons/transparent-header/classes/class-astra-ext-transparent-header-markup.php` (`is_transparent_header()`), `classes/dynamic-css/dynamic.css.php`, partie « Transparent Header » d'`inc/class-astra-dynamic-css.php` | `inc/astra_entete_transparent.php` | Transposition de la règle d'activation aux pages SPIP et des règles CSS : sélecteurs copiés à l'identique, media queries, ordre et conditions d'émission |
 | CSS dynamique rendu par WordPress avec les valeurs du modèle Garden Maintenance | `tests/references/styles/attendus/*.css`, `tests/references/transparent/attendus/*.css` | Extraits de la sortie d'Astra 4.11.0 (GPL v2 ou ultérieure), capturés par `tests/references/styles/capturer.sh` et `tests/references/transparent/capturer.sh` pour les tests d'équivalence |
 | Règles Astra appliquées aux contenus | `css/astra-spip.css` | Adaptations aux éléments produits par SPIP ; les reprises de règles WordPress/Astra sont signalées dans les commentaires |
diff --git a/README.md b/README.md
index 87dfff8..2e3dd64 100644
--- a/README.md
+++ b/README.md
@@ -115,6 +115,9 @@ avec leur effet et la propriété vérifiée, dans
   dispositions de page), puis rendu paramétrable : palette et largeurs passent par des variables CSS.
 - `css/astra-hfb.css` : CSS ajouté par le builder d'Astra pour les rangées et éléments, extrait
   des références WordPress par `tests/references/extraire_css.py`.
+- `css/compat/sous-menu-fix.css`, `css/compat/sous-menu-ancien.css` : règles qui dépendent du
+  drapeau de compatibilité `submenu-below-header`, extraites par
+  `tests/references/extraire_css.py --compat` ; une seule est chargée, selon `astra/compatibilite`.
 - `inclure/head-vars.html` : variables CSS issues de la configuration (`--ast-global-color-0` à `8`,
   largeurs de conteneur, polices) et réglages des rangées du builder.
 - `css/astra-article.css` et `css/astra-commentaires.css` : règles qu'Astra n'émet que sur les
@@ -137,6 +140,13 @@ avec leur effet et la propriété vérifiée, dans
 - Neuvième couleur de la palette : hexadécimale ou `rgba()`, comme dans Astra.
 - **Couleurs et typographie** (Configuration › Squelettes) : réglages de style du Customizer d'Astra, sous leurs noms d'options (`header-menu1-color-responsive`, `button-radius-fields`…) — menus, bouton, bouton de l'en-tête, identité du site (couleurs, tailles, affichage du nom et du slogan, largeur du logo), widgets et copyright du pied, icônes sociales, bouton du menu mobile (minimal, plein ou contour). Les valeurs d'un `site.json` de modèle Astra se reprennent telles quelles ; seule la valeur ordinateur est appliquée, les valeurs tablette et mobile sont conservées. Sans réglage, rien ne change.
 - **En-tête transparent** (même formulaire) : l'en-tête se superpose au début du contenu, avec ses propres couleurs, sur tout le site ou page par page, avec les exclusions d'Astra (archives, recherche, 404, accueil des derniers articles, pages, articles) et la restriction par appareil ; règles d'Astra 4.11.0 sur les trois appareils (`inc/astra_entete_transparent.php`). Logo propre à l'en-tête transparent : non repris.
+- Graisses des titres (`headings-font-weight`, `font-weight-h1` à `h6`), marge intérieure du
+  bouton de l'en-tête et alignements du copyright et des icônes sociales du pied (même formulaire ;
+  les alignements s'appliquent sur les trois appareils).
+- **Drapeaux de compatibilité** (méta `astra/compatibilite`, sans formulaire : ils viennent d'un
+  import) : `submenu-below-header` et `can-support-widget-and-editor-fonts`, qu'un `site.json` de
+  Starter Template garde d'un site antérieur et qui changent le CSS d'Astra (hauteur de l'en-tête,
+  graisses des titres et des titres des widgets).
 - Texte du pied de page (raccourcis SPIP, marqueurs `[annee]` et `[site]`).
 
 ## Tests
@@ -156,6 +166,7 @@ Les références WordPress du builder sont décrites dans `tests/references/READ
 références, un WordPress de test et le SPIP de test servi par un serveur web :
 
     WP=… PW=… SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404
+    WP=… PW=… SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404 R14-bouton-mobile R16-transparent-page R17-transparent-global R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat
     WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404
     URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire.cjs
     RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
@@ -163,14 +174,16 @@ références, un WordPress de test et le SPIP de test servi par un serveur web :
     URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire_styles.cjs
 
 - `comparer_html.php` compare l'ossature HTML de l'en-tête, du panneau mobile et du pied de
-  page à celle de WordPress, pour les configurations de référence du builder (R0–R5, R12, R13 et R15) et
-  les trois de pages (P1–P3, qui comparent aussi le contenu et les classes du `body`) : balises, classes et
+  page à celle de WordPress : sans argument, pour les configurations de référence du builder
+  (R0–R5, R12, R13, R15 et R18) et les trois de pages (P1–P3, qui comparent aussi le contenu et
+  les classes du `body`) ; les références qui posent des styles (R6–R11, R14, R16, R17, R19–R23)
+  se passent en arguments, comme dans la deuxième commande. Sont comparés : balises, classes et
   identifiants des rangées, des zones et des éléments, et du premier niveau à l'intérieur de
   chaque élément. Les contenus, les formulaires et les SVG ne sont pas comparés : ce n'est pas
   une identité complète du HTML (R13 : deux différences de nommage attendues, voir
   `tests/references/README.md`) ;
-- `comparer_captures.sh` compare au pixel près l'en-tête, le pied de page et le menu mobile
-  ouvert, sur ordinateur et sur mobile (seuil : 1 % de pixels différents, variable `SEUIL`) ; les
+- `comparer_captures.sh` compare au pixel près (sans argument : R0–R23 ; P1–P3 en arguments)
+  l'en-tête, le pied de page et le menu mobile ouvert, sur ordinateur et sur mobile (seuil : 1 % de pixels différents, variable `SEUIL`) ; les
   images et leurs différences sont enregistrées dans `tests/comparaison/sorties/`. Une référence
   peut aussi lister des styles calculés à comparer (`styles` dans `tests/references/wp/<conf>.json`) :
   R5 vérifie ainsi la hauteur, le fond et le séparateur des rangées de l'en-tête, qu'un filet
@@ -178,7 +191,9 @@ références, un WordPress de test et le SPIP de test servi par un serveur web :
   valeurs de Garden Maintenance) comparent aussi les styles calculés au survol et au focus, à
   1280, 768 et 390 px, et vérifient que les polices locales sont chargées des deux côtés ;
   R12–R15 couvrent les répartitions par appareil, les widgets menu, le bouton du menu mobile et
-  l'icône Google Reviews ;
+  l'icône Google Reviews ; R16 et R17 l'en-tête transparent ; R18–R23 les drapeaux de
+  compatibilité, les graisses des titres, la marge du bouton de l'en-tête et les alignements du
+  pied ;
 - `tests/e2e/formulaire_styles.cjs` vérifie le formulaire « Couleurs et typographie » de bout en
   bout (enregistrement, valeur refusée, site public) ;
 - `tests/e2e/formulaire.cjs` vérifie le formulaire de configuration de bout en bout ;
diff --git a/paquet.xml b/paquet.xml
index 21dae56..28d61e5 100644
--- a/paquet.xml
+++ b/paquet.xml
@@ -1,7 +1,7 @@
 <paquet
 	prefix="astra"
 	categorie="squelette"
-	version="1.3.0"
+	version="1.4.0"
 	schema="1.0.0"
 	etat="test"
 	compatibilite="[4.2.0;5.0.*]"
diff --git a/tests/comparaison/comparer_captures.sh b/tests/comparaison/comparer_captures.sh
index 6aec137..d5bd334 100755
--- a/tests/comparaison/comparer_captures.sh
+++ b/tests/comparaison/comparer_captures.sh
@@ -16,7 +16,7 @@ SORTIES=${SORTIES:-$ICI/sorties}
 : "${PW:?PW doit indiquer le dossier contenant node_modules/playwright et pixelmatch}"
 : "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
 CONFS=("$@")
-[ ${#CONFS[@]} -gt 0 ] || CONFS=(R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google)
+[ ${#CONFS[@]} -gt 0 ] || CONFS=(R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google R16-transparent-page R17-transparent-global R18-compat-sous-menu R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat)
 
 # Copyright identique à celui d'Astra dans WordPress.
 (cd "$RACINE" && $SPIP_CLI php:eval 'include_spip("inc/config"); ecrire_config("astra/texte_pied", "Copyright © [annee] [site] | Powered by [Thème WordPress Astra->https://wpastra.com]");' > /dev/null)
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 6f0c9a2..7c2bcb9 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -193,6 +193,7 @@ function astra_configurations_reference(): array {
 		'R22-pied-alignements' => $styles(['alignements']),
 		'R22-pied-alignements-ordinateur' => $styles(['alignements-ordinateur']),
 		'R22-pied-alignements-tablette' => $styles(['alignements-tablette']),
+		'R23-garden-compat' => $styles(['garden-compat'], $titres + ['compatibilite' => astra_configuration_styles(['garden-compat'])]),
 		'P1-page' => [
 			'page' => 'notre_jardin',
 			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
diff --git a/tests/references/README.md b/tests/references/README.md
index f5a9981..b5c6884 100644
--- a/tests/references/README.md
+++ b/tests/references/README.md
@@ -79,6 +79,17 @@ Vérifié le 2026-10-22 : HTML et CSS identiques.
 | R15-sociaux-google | Pied : copyright et icônes sociales Facebook et Google Reviews (couleur de marque `#dc4e41`) |
 | R16-transparent-page | Page « Notre jardin » avec l'en-tête transparent activé par la page (méta `theme-transparent-header-meta` à `enabled`), réglage global désactivé, valeurs de Garden (`transparent/valeurs/garden.json`) ; contenu, classes du `body`, position de l'en-tête et styles calculés comparés |
 | R17-transparent-global | En-tête transparent sur tout le site, page de la catégorie Jardinage : archive exclue par défaut, en-tête normal |
+| R18-compat-sous-menu | Drapeau de compatibilité `submenu-below-header` à `false` (règles de `css/compat/sous-menu-ancien.css`) ; hauteur de l'en-tête, marges de la rangée principale et de l'identité, affichage des éléments du menu comparés |
+| R19-compat-polices-widgets | Drapeau `can-support-widget-and-editor-fonts` posé, sans autre option de style ; placement de R6–R11 (titres des widgets du pied en h2), page « Notre jardin » avec les six niveaux de titres ; graisses comparées |
+| R20-titres-graisses | Graisses des titres réglées (`styles/valeurs/titres.json` : titres à 700, h1 `inherit`, h2 `normal`, h3 `300`, h4 `600` explicite), sans le drapeau ; même page |
+| R20-titres-graisses-drapeau | Mêmes graisses, avec le drapeau `can-support-widget-and-editor-fonts` |
+| R21-bouton-entete-marge | Marge du bouton de l'en-tête enregistrée vide, marge des boutons à 17 × 26 (Garden) |
+| R21-bouton-entete-marge-valeur | Marge du bouton de l'en-tête en partie réglée (haut 10, droite 20) : côtés vides selon l'appareil |
+| R21-bouton-entete-marge-vide | Marge du bouton de l'en-tête enregistrée vide, sans marge des boutons : 15/30, 14/28, 12/24 selon l'appareil |
+| R22-pied-alignements | Alignements de Garden : copyright `left` / `left` / `center`, icônes sociales `right` / `right` / `center` ; `text-align` comparé aux trois largeurs |
+| R22-pied-alignements-ordinateur | Alignements réglés pour l'ordinateur seul : tablette et mobile prennent sa valeur |
+| R22-pied-alignements-tablette | Alignements réglés pour la tablette seule : sur ordinateur, copyright hérité et icônes sociales centrées |
+| R23-garden-compat | Valeurs de Garden de R18 à R22 ensemble (deux drapeaux, graisses, marge vide, alignements) |
 | P1-page | Page « Notre jardin » (`/?pagename=notre-jardin`), menu de pages à deux niveaux (`ref_menu_pages()`) |
 | P2-accueil | Même menu, page « Notre jardin » en page d'accueil statique (`/`) |
 | P3-page-pleine | Page « Plein écran », titre désactivé, conteneur `full-width-container` |
@@ -129,6 +140,21 @@ Non couvert dans cette version (valeurs conservées, aucun CSS) : valeurs tablet
 fonds de type `image` ou `gradient` ; fond, fond au survol et rayon des icônes sociales
 (`*-social-1-bg-color`, `-bg-h-color`, `-radius-fields`), absents des valeurs du modèle.
 
+## Graisses des titres : attendus
+
+Astra modifie des règles figées de `css/astra-dynamic.css` (graisse retirée d'une règle qui porte
+aussi la taille) : les graisses ne se comparent pas règle à règle. `capturer.sh` écrit, pour les
+variantes `titres`, `titres-drapeau` et `drapeau-polices`, les règles de graisse des titres du CSS
+complet (`graisses.py`) dans `attendus/<variante>.css`. `tests/unit/StylesTitresTest.php` calcule
+par la cascade (spécificité, puis ordre) la graisse de chaque titre (h1 à h6, dans le contenu,
+titres des widgets h1 à h6, nom du site) : `css/main.min.css` puis ces règles côté WordPress,
+`css/main.min.css`, `css/astra-dynamic.css` et le CSS produit côté SPIP ; les deux doivent être
+égales.
+
+Les références R18 à R23 passent par les clés `compatibilite` (méta `astra/compatibilite`) et
+`textes` (texte d'une page unique, remis à celui de l'installation à chaque configuration) de
+`tests/comparaison/configurations.php`.
+
 ## En-tête transparent : attendus
 
 `tests/references/transparent/` fixe le CSS qu'Astra 4.11.0 ajoute sur une page à en-tête
diff --git a/tests/references/styles/valeurs/garden-compat.json b/tests/references/styles/valeurs/garden-compat.json
new file mode 100644
index 0000000..217f1a3
--- /dev/null
+++ b/tests/references/styles/valeurs/garden-compat.json
@@ -0,0 +1,67 @@
+{
+	"submenu-below-header": false,
+	"can-support-widget-and-editor-fonts": false,
+	"headings-font-weight": "600",
+	"font-weight-h1": "inherit",
+	"font-weight-h2": "inherit",
+	"font-weight-h3": "inherit",
+	"font-weight-h4": "inherit",
+	"font-weight-h5": "inherit",
+	"font-weight-h6": "inherit",
+	"section-hb-button-1-padding": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"mobile": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"theme-button-padding": {
+		"desktop": {
+			"top": "17",
+			"right": "26",
+			"bottom": "17",
+			"left": "26"
+		},
+		"tablet": {
+			"top": "17",
+			"right": "26",
+			"bottom": "17",
+			"left": "26"
+		},
+		"mobile": {
+			"top": "17",
+			"right": "26",
+			"bottom": "17",
+			"left": "26"
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"footer-copyright-alignment": {
+		"desktop": "left",
+		"tablet": "left",
+		"mobile": "center"
+	},
+	"footer-social-1-alignment": {
+		"desktop": "right",
+		"tablet": "right",
+		"mobile": "center"
+	}
+}
diff --git a/tests/references/wp/R23-garden-compat.json b/tests/references/wp/R23-garden-compat.json
new file mode 100644
index 0000000..89447cc
--- /dev/null
+++ b/tests/references/wp/R23-garden-compat.json
@@ -0,0 +1,131 @@
+{
+	"chemin": "/?pagename=notre-jardin",
+	"ordinateur": [
+		".site-primary-header-wrap .site-branding",
+		".site-primary-header-wrap .ast-header-button-1",
+		".site-primary-footer-wrap .footer-widget-area",
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": "#masthead",
+			"proprietes": [
+				"height"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .main-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-bottom",
+				"height"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .ast-site-identity",
+			"proprietes": [
+				"padding-top",
+				"padding-bottom"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .ast-builder-menu-1 .menu-item",
+			"proprietes": [
+				"display",
+				"justify-content"
+			]
+		},
+		{
+			"selecteur": ".entry-content h1",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h2",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h3",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h4",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h5",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".entry-content h6",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".footer-widget-area h2.widget-title",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".site-title",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".site-title a",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": "article.page .entry-title",
+			"proprietes": [
+				"font-weight"
+			]
+		},
+		{
+			"selecteur": ".ast-header-button-1 .ast-custom-button",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left"
+			]
+		},
+		{
+			"selecteur": ".ast-footer-copyright",
+			"proprietes": [
+				"text-align"
+			]
+		},
+		{
+			"selecteur": "[data-section=\"section-fb-social-icons-1\"] .footer-social-inner-wrap",
+			"proprietes": [
+				"text-align"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R23-garden-compat.php b/tests/references/wp/R23-garden-compat.php
new file mode 100644
index 0000000..e797452
--- /dev/null
+++ b/tests/references/wp/R23-garden-compat.php
@@ -0,0 +1,8 @@
+<?php
+// Valeurs de Garden Maintenance de la spec des réglages de compatibilité, ensemble : deux drapeaux,
+// graisses des titres, marge du bouton de l'en-tête vide, alignements du pied (valeurs :
+// styles/valeurs/garden-compat.json). Page « Notre jardin » avec les six niveaux de titres.
+putenv('GROUPE=garden-compat');
+require dirname(__DIR__) . '/styles/appliquer.php';
+$ids = ref_pages();
+wp_update_post(['ID' => $ids['notre-jardin'], 'post_content' => ref_texte_titres()]);
````

- [ ] **Étape 2 : vérifier l'ensemble**

```bash
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php
for f in $(git diff --name-only origin/main -- '*.php' ':!tests'); do php7.4 -l "$f"; done
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google R16-transparent-page R17-transparent-global R18-compat-sous-menu R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat P1-page P2-accueil P3-page-pleine
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google R16-transparent-page R17-transparent-global R18-compat-sous-menu R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414
```

Attendu : unitaires `OK (112 tests, 1548 assertions)`, intégration `OK (83 tests, 289 assertions)` ; `No syntax errors detected` pour chaque fichier ; `comparer_html.php` : tout identique sauf les deux nommages de R13 (`tests/references/README.md`) ; `comparer_captures.sh` (liste par défaut, R0–R23) : toutes les vues sous le seuil (plus grand écart vers 0,7 %, R13, pied sur mobile), styles calculés égaux ; R23 : 24 valeurs égales aux trois largeurs.

- [ ] **Étape 3 : commit**

```bash
git add paquet.xml README.md CREDITS.md tests/references/README.md tests/comparaison/comparer_captures.sh tests/comparaison/configurations.php tests/references/styles/valeurs/garden-compat.json tests/references/wp/R23-garden-compat.php tests/references/wp/R23-garden-compat.json
git commit -m "Version 1.4.0 : réglages de compatibilité et options importées"
```

## Effet attendu sur Garden

Une fois la version 1.4.0 publiée et posée par un lot de `garden-spip` (clé `config.astra.compatibilite` avec les deux drapeaux du `site.json`, options `headings-font-weight`, `font-weight-h1` à `h6`, `section-hb-button-1-padding`, `footer-copyright-alignment`, `footer-social-1-alignment`), les lignes correspondantes de « Écarts connus » du README de Garden (hauteur de l'en-tête, hors logo mobile ; graisse des titres des widgets ; marges du bouton ; alignements du pied) sont à remesurer et à retirer. Ce lot fera l'objet de son propre plan.

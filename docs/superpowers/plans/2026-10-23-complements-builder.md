# Compléments du builder — plan de mise en œuvre

> **Pour les agents :** sous-compétence requise : superpowers:subagent-driven-development (recommandé) ou superpowers:executing-plans, tâche par tâche. Les étapes sont des cases à cocher (`- [ ]`).

**But :** compléter le Header/Footer Builder d'Astra SPIP avec les cinq réglages d'Astra 4.11.0 que le modèle Garden Maintenance utilise et qui manquent : répartition des colonnes du pied par appareil, séparateurs des rangées du pied, bloc menu dans les widgets, réseau Google Reviews, style du bouton du menu mobile ; version 1.3.0.

**Architecture :** les quatre premiers réglages étendent la méta `astra/hfb` (structures de rangée et d'élément, normalisées par `inc/astra_hfb.php` et `inc/astra_hfb_saisie.php`) et le balisage des squelettes `inclure/hfb/` et `inclure/widgets/`. Le cinquième ajoute un groupe `menu-mobile` aux styles du Customizer (`inc/astra_styles.php`, module `inc/styles/menu_mobile.php`) et la classe de style au bouton. Chaque réglage est prouvé contre WordPress Astra 4.11.0 : références R5 (complétée), R12 à R15, comparaison HTML, captures et styles calculés à 1280, 768 et 390 px.

**Pile technique :** PHP 7.4+ (plugin), SPIP 4.4, PHPUnit 11.5, WordPress de test avec Astra 4.11.0 et WP-CLI, Playwright (Chromium).

**Spec :** `docs/superpowers/specs/2026-10-23-complements-builder-design.md` (direction validée par Gilles et Codex ; décisions du 24 octobre reportées dans la spec par ce même commit).

## Contraintes globales

- Rien ne change sans réglage : une configuration `astra/hfb` existante (répartition en valeur simple, rangées sans séparateur réglé, widgets sans bloc menu, bouton du menu mobile sans style) donne le même HTML et le même CSS qu'en 1.2.0 ; R0–R11 et P1–P3 restent sous le seuil des captures, styles calculés identiques.
- Avec un réglage, l'effet est celui d'Astra 4.11.0 : même balisage, mêmes sélecteurs, mêmes conditions d'émission ; fichier d'Astra cité en commentaire.
- Aucune chaîne libre dans une feuille de style ou un attribut : couleurs par `astra_styles_couleur_valide()`, épaisseurs entières bornées, répartitions et sources de menu par listes fermées.
- Valeurs responsives d'Astra conservées entières ; seule `desktop` est exploitée par les styles (sauf la répartition par appareil, dont c'est l'objet).
- Le plugin reste compatible PHP 7.4 : pas de syntaxe PHP 8 dans `inc/`, `formulaires/`, `astra_*.php` (vérifié par `php7.4 -l` sur les fichiers modifiés).
- Provenance et licence de chaque reprise établies (`CREDITS.md`, `licenses/`).
- Langue du code, des commentaires et des messages : français, comme le dépôt.
- Dépôt partagé avec Codex : `git add` fichier par fichier ; ne jamais indexer `.gitignore`, `.ignore`, `tests/references/__pycache__/*.pyc`, `docs/inventaires/2026-10-23-preparation-publication.md`.
- Messages de commit sans trailer (ni `Co-Authored-By`, ni `Claude-Session`).
- Tests d'intégration : le SPIP de test (`vendor/spip/spip`) doit avoir `plugins/astra` pointant vers l'arbre de travail testé. Dans un worktree : `cp -a <dépôt principal>/vendor vendor && ln -sfn "$PWD" vendor/spip/spip/plugins/astra`. Les tests d'intégration et les comparaisons écrivent dans la même base : ne pas les lancer en même temps.
- Commandes : `vendor/bin/phpunit --testsuite unit [--filter …]` ; `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php [--filter …]`. Comparaisons : `WP=… PW=… SPIP_CLI=… tests/references/capturer.sh <Rn…>` (références WordPress), puis `php tests/comparaison/comparer_html.php <racine SPIP> <URL> [Rn…]` et `tests/comparaison/comparer_captures.sh <racine SPIP> <URL> [Rn…]`. Le SPIP de test doit être servi avec `PHP_CLI_SERVER_WORKERS=4` (SPIP s'appelle lui-même à la première visite).
- Les correctifs de ce plan s'appliquent depuis la racine du dépôt avec `git apply` (enregistrer le bloc dans un fichier, puis `git apply chemin/du/fichier.patch`), dans l'ordre des tâches.

## Décisions (validées par Gilles le 24 octobre)

1. **Bloc menu sur deux niveaux** (§ 3 de la spec : « n'affiche qu'un niveau » ; question ouverte 2). Mesuré sur WordPress (R13) : le widget `nav_menu` affiche les sous-menus, avec `aria-expanded` sur le lien parent et une flèche `span.dropdown-menu-toggle` sans bouton. Le plan reprend ce rendu (`niveaux=2`) ; un seul niveau aurait rendu le HTML différent de WordPress.
2. **Preuve de Google Reviews par une référence dédiée `R15-sociaux-google`** (§ 4 : « la référence R1 ou R3 gagne l'icône »). R1 à R4 alimentent `extraire_css.py` (CSS figé du builder) ; les modifier changerait ce CSS. R15 place Facebook et Google Reviews dans le pied et compare HTML, captures et styles calculés.
3. **Pied des références des styles R6–R11** : leur configuration SPIP passe à la structure par appareil (`4-equal` / `full` / `full` et `2-equal` / `2-equal` / `full`), celle de `tests/references/styles/appliquer.php` côté WordPress. Avant la tâche 1, SPIP ne pouvait pas l'exprimer : la classe tablette de la rangée principale différait (`tablet-4-equal` contre `tablet-full`), sans effet visible aux largeurs comparées.
4. **Version 1.3.0** : la spec ne fixe pas de version ; nouveaux réglages, aucune rupture.
5. **R13, deux différences de nommage attendues** dans `comparer_html.php` : identifiant de la section (`nav_menu-2`, numéro d'instance du widget WordPress ; `nav_menu-pied-2` dans SPIP, unique par zone) et classe de la `nav` (`menu-<slug du menu>-container` ; `menu-<source>-container`, SPIP n'ayant pas de menus nommés). Documentées dans `tests/references/README.md`.

6. **Répartitions de l'ordinateur gardées sur tablette** (`4-lheavy`, `4-rheavy`, demandé à la relecture) : une valeur simple de la version 1.2.0 vaut pour l'ordinateur et la tablette ; Astra ne propose sur tablette, pour quatre colonnes, que `4-equal`, `2-equal` et `full`. La tablette accepte donc aussi les répartitions de l'ordinateur pour le nombre de colonnes, pour que le formulaire repropose la valeur et qu'un enregistrement ne change rien au rendu (`HfbFormulaireTest::testAllerRetourDesRepartitionsLourdesAQuatreColonnes`).

Constats hors périmètre, relevés pendant la vérification : lancé explicitement sur R11, `comparer_html.php` compare son contenu (clé `astra`) et y trouve des différences de la liste des articles (classes de catégorie, auteur, `time`) qui existent déjà sur `main` ; `tests/e2e/formulaire_styles.cjs` suppose un slogan, que `tests/comparaison/appliquer.sh` remet à vide.

## Vérification préalable du plan

Le code de ce plan a été écrit et exécuté dans un worktree jetable de `origin/main` (7e3dde8),
puis réordonné en un commit par tâche ; chaque correctif ci-dessous est le diff de ces commits,
appliqué dans l'ordre sur 7e3dde8 (l'arbre final est identique à celui de l'essai) :

- étape 2 de chaque tâche : tests de la tâche appliqués sur l'état de la tâche précédente, échecs
  relevés tels qu'indiqués ;
- tests après la tâche 6 : unitaires 83 tests, 1135 assertions ; intégration 77 tests,
  261 assertions (avant : 71 et 69) ;
- correction de la relecture (décision 6) : test d'aller-retour du formulaire et
  `repartitions.cjs` (21 répartitions tablette et mobile, dont `4-lheavy` et `4-rheavy` sur
  tablette) relancés ; les comparaisons ci-dessous datent de l'essai d'avant la correction,
  qui ne change ni le rendu ni les références ;
- `php7.4 -l` sur les fichiers PHP modifiés hors `tests/` : aucune erreur ; exécution sous PHP 7.4
  de `astra_hfb_repartition_appareils()`, `astra_hfb_css_separateur()` et du groupe
  `menu-mobile` ;
- `capturer.sh` sur les 19 références (R0–R15, P1–P3), puis `comparer_html.php` (configurations
  par défaut) : tout identique sauf les deux nommages de R13 (écart 5) ;
- `comparer_captures.sh` R0–R15, P1–P3 : 135 vues sous le seuil (plus grand écart 0,73 %, R13,
  pied sur mobile ; puis 0,35 %, R11, pied sur ordinateur, comme en 1.2.0), styles calculés
  égaux aux trois largeurs pour toutes les références qui en déclarent ;
- `tests/e2e/repartitions.cjs`, `formulaire.cjs`, `formulaire_styles.cjs` (avec un slogan) : OK ;
- SVG `google_reviews` identique octet pour octet à l'entrée de `svgs.json` d'Astra 4.11.0.

## Fichiers

| Fichier | Rôle | Tâche |
|---|---|---|
| `inc/astra_hfb.php` | Défauts, normalisation, classes et CSS des rangées, registre des réseaux | 1, 2, 3, 4 |
| `inc/astra_hfb_saisie.php` | Répartitions proposées, vérification et enregistrement de la saisie du builder | 1, 2, 3 |
| `formulaires/configurer_astra_hfb.html`, `formulaires/inc-astra-hfb-widget.html` | Sélecteurs par appareil, séparateurs, source du bloc menu | 1, 2, 3 |
| `inclure/hfb/menu-items.html`, `inclure/hfb/widget.html`, `inclure/widgets/menu.html` | Bloc menu des widgets (variante du balisage des menus) | 3 |
| `inc/astra_icones.php`, `inclure/hfb/social-icons.html` | SVG Google Reviews et sa provenance | 4 |
| `inc/astra_styles.php`, `inc/styles/menu_mobile.php`, `inc/astra_styles_saisie.php`, `astra_fonctions.php`, `inclure/hfb/mobile-trigger.html` | Groupe de styles `menu-mobile`, classe du bouton | 5 |
| `lang/astra_{fr,en}.php` | Libellés | 1, 2, 3, 5 |
| `tests/unit/*`, `tests/integration/*` | Tests unitaires et d'intégration | 1–5 |
| `tests/references/wp/R5-rangees.*`, `R12`–`R15`, `commun.php`, `tests/comparaison/configurations.php` | Références WordPress et leurs configurations SPIP | 1–5 |
| `tests/references/styles/{valeurs,attendus}/menu-mobile.*`, `capturer.sh` | Attendus du groupe `menu-mobile` | 5 |
| `tests/e2e/repartitions.cjs` | Répartitions tablette et mobile dans Chromium | 1 |
| `CREDITS.md`, `licenses/OFL-1.1-fontawesome-4.7.0.txt`, `licenses/README.md` | Provenance et licence du SVG Google Reviews | 4 |
| `paquet.xml`, `README.md`, `tests/references/README.md`, `tests/comparaison/comparer_captures.sh` | Version, documentation, liste par défaut des comparaisons | 6 |

---

### Tâche 1 : répartition des colonnes du pied par appareil

Spec § 1. `astra/hfb/rangees/pied_<rangée>/repartition` accepte la structure d'Astra `{"desktop", "tablet", "mobile"}` ; une valeur simple reste valide (ordinateur et tablette, mobile `full` : rendu inchangé). Valeurs par appareil : `astra_hfb_repartitions($colonnes, $appareil)` reprend les choix d'Astra (`Astra_Builder_Helper`, contrôle `ast-row-layout`) : sur tablette et mobile, `full` en plus, et pour trois colonnes `3-firstrow` et `3-lastrow` ; la tablette garde en plus les répartitions de l'ordinateur (décision 6). Les classes `ast-builder-grid-row-<desktop>`, `-tablet-<tablet>`, `-mobile-<mobile>` sont celles d'Astra ; le CSS des répartitions est déjà dans `css/astra-hfb.css`.

**Fichiers :**
- Modifier : `inc/astra_hfb.php`, `inc/astra_hfb_saisie.php`, `formulaires/configurer_astra_hfb.html`, `lang/astra_fr.php`, `lang/astra_en.php`
- Modifier : `tests/unit/HfbTest.php`, `tests/unit/HfbSaisieTest.php`, `tests/integration/HfbFormulaireTest.php`, `tests/comparaison/configurations.php`, `tests/e2e/repartitions.cjs`
- Créer : `tests/references/wp/R12-pied-appareils.php`, `tests/references/wp/R12-pied-appareils.json`

**Interfaces :**
- Produit : `astra_hfb_repartition_appareils($valeur): array` (`inc/astra_hfb.php`) : `['desktop' => …, 'tablet' => …, 'mobile' => …]` depuis une valeur simple ou une structure (appareil absent ou invalide : `full`, ordinateur repris pour la tablette d'une valeur simple).
- Produit : `astra_hfb_repartitions(int $colonnes, string $appareil = 'desktop'): array` et `astra_hfb_repartitions_appareil(string $appareil): array` (`inc/astra_hfb_saisie.php`, liste dédoublonnée pour un sélecteur).
- Saisie postée : `rangees[<clé>][repartition][desktop|tablet|mobile]`.

- [ ] **Étape 1 : écrire les tests et la référence**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 1c8c53a..2335c1e 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -16,8 +16,8 @@ function astra_configurations_reference(): array {
 				'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']],
 			],
 			'rangees' => [
-				'pied_primary' => ['colonnes' => 4, 'repartition' => '4-equal'],
-				'pied_below' => ['colonnes' => 2, 'repartition' => '2-equal'],
+				'pied_primary' => ['colonnes' => 4, 'repartition' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full']],
+				'pied_below' => ['colonnes' => 2, 'repartition' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full']],
 			],
 			'elements' => [
 				'entete' => ['button-1' => ['libelle' => 'Nous contacter', 'lien' => 'https://example.org/contact']],
@@ -84,6 +84,25 @@ function astra_configurations_reference(): array {
 				'widget-4' => ['blocs' => ['texte'], 'titre' => 'Colonne 4', 'texte' => 'Texte de la colonne 4'],
 			]],
 		],
+		'R12-pied-appareils' => [
+			'pied' => [
+				'above' => ['above_1' => ['html-1'], 'above_2' => ['social-icons-1']],
+				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']],
+				'below' => ['below_1' => ['copyright'], 'below_2' => ['menu']],
+			],
+			'rangees' => [
+				'pied_above' => ['colonnes' => 2, 'repartition' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full']],
+				'pied_primary' => ['colonnes' => 4, 'repartition' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full']],
+				'pied_below' => ['colonnes' => 2, 'repartition' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full']],
+			],
+			'elements' => ['pied' => [
+				'html-1' => ['texte' => 'Un site éditorial propulsé par SPIP'],
+				'widget-1' => ['blocs' => ['texte'], 'titre' => 'Colonne 1', 'texte' => 'Texte de la colonne 1'],
+				'widget-2' => ['blocs' => ['texte'], 'titre' => 'Colonne 2', 'texte' => 'Texte de la colonne 2'],
+				'widget-3' => ['blocs' => ['texte'], 'titre' => 'Colonne 3', 'texte' => 'Texte de la colonne 3'],
+				'widget-4' => ['blocs' => ['texte'], 'titre' => 'Colonne 4', 'texte' => 'Texte de la colonne 4'],
+			]],
+		],
 		'R4-pied-asymetrique' => [
 			'pied' => [
 				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['html-1', 'social-icons-1'], 'primary_3' => ['menu']],
diff --git a/tests/e2e/repartitions.cjs b/tests/e2e/repartitions.cjs
index fe8b635..73e0599 100644
--- a/tests/e2e/repartitions.cjs
+++ b/tests/e2e/repartitions.cjs
@@ -2,8 +2,12 @@
 // Usage : RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
 // Pour chaque répartition proposée par le formulaire : configuration de la rangée principale du
 // pied, puis mesure de la grille dans Chromium. Ordinateur et tablette : colonnes dans les
-// proportions d'Astra ; mobile : une seule colonne. Vérifie aussi qu'avec un conteneur de 1600 px
-// les trois rangées du pied suivent cette largeur. Modifie la configuration du site : site de test.
+// proportions d'Astra ; mobile : une seule colonne. Puis, pour chaque répartition tablette et
+// mobile proposée (structure par appareil), mesure à 800 et 390 px (répartitions de l'ordinateur
+// gardées sur tablette : à 800 px seulement) ; `3-firstrow` et `3-lastrow`
+// font en plus déborder la première ou la dernière colonne sur toute la largeur. Vérifie aussi
+// qu'avec un conteneur de 1600 px les trois rangées du pied suivent cette largeur. Modifie la
+// configuration du site : site de test.
 const { execFileSync } = require('child_process');
 const { chromium } = require('playwright');
 
@@ -20,12 +24,25 @@ const REPARTITIONS = {
   '4-equal': [1, 1, 1, 1], '4-lheavy': [2, 1, 1, 1], '4-rheavy': [1, 1, 1, 2],
   '5-equal': [1, 1, 1, 1, 1], '6-equal': [1, 1, 1, 1, 1, 1],
 };
+// Répartitions tablette et mobile d'Astra par nombre de colonnes (astra_hfb_repartitions).
+const PETITS = {
+  2: ['2-equal', '2-lheavy', '2-rheavy', 'full'],
+  3: ['3-equal', '3-cwide', '3-cheavy', '3-lheavy', '3-rheavy', '3-firstrow', '3-lastrow', 'full'],
+  4: ['4-equal', '2-equal', 'full'],
+  5: ['5-equal', 'full'],
+  6: ['6-equal', 'full'],
+};
+// Tablette seulement : répartitions de l'ordinateur gardées pour les valeurs simples de la 1.2.0.
+const TABLETTE = { 4: ['4-lheavy', '4-rheavy'] };
+const DEBORDE = { '3-firstrow': 'first', '3-lastrow': 'last' };
+const ORDINATEUR = { 2: '2-equal', 3: '3-equal', 4: '4-equal', 5: '5-equal', 6: '6-equal' };
 const APPAREILS = [
   { nom: 'ordinateur', largeur: 1280, mobile: false },
   { nom: 'tablette', largeur: 800, mobile: false },
   { nom: 'mobile', largeur: 390, mobile: true },
 ];
 const echecs = [];
+let petites = 0;
 
 function configurer(php) {
   execFileSync(SPIP_CLI[0], [...SPIP_CLI.slice(1), 'php:eval', 'include_spip("inc/config");' + php], { cwd: RACINE });
@@ -33,20 +50,28 @@ function configurer(php) {
 }
 
 function piedPrincipal(repartition, colonnes, autres = {}) {
+  const zones = { primary_1: ['html-1'] };
+  if (colonnes === 3) Object.assign(zones, { primary_2: ['menu'], primary_3: ['social-icons-1'] });
   const config = {
-    pied: { ...autres, primary: { primary_1: ['html-1'] } },
+    pied: { ...autres, primary: zones },
     rangees: { pied_primary: { colonnes, repartition } },
     elements: { pied: { 'html-1': { texte: 'Colonne 1' } } },
   };
   return 'ecrire_config("astra/hfb", json_decode(' + JSON.stringify(JSON.stringify(config)) + ', true));';
 }
 
-async function colonnes(browser, largeur, selecteur) {
+async function colonnes(browser, largeur, selecteur, deborde = '') {
   const page = await browser.newPage({ viewport: { width: largeur, height: 900 }, userAgent: AGENT });
   await page.goto(URL + '/', { waitUntil: 'load' });
-  const valeur = await page.locator(selecteur).first().evaluate(e => getComputedStyle(e).gridTemplateColumns);
+  const grille = page.locator(selecteur).first();
+  const valeur = await grille.evaluate(e => getComputedStyle(e).gridTemplateColumns);
+  // Colonne qui déborde : sa largeur est celle de la grille.
+  const pleine = deborde ? await grille.evaluate((e, d) => {
+    const enfant = d === 'first' ? e.firstElementChild : e.lastElementChild;
+    return Math.abs(enfant.getBoundingClientRect().width - e.clientWidth + parseFloat(getComputedStyle(e).paddingLeft) + parseFloat(getComputedStyle(e).paddingRight)) < 2;
+  }, deborde) : true;
   await page.close();
-  return valeur.split(' ').map(parseFloat);
+  return { px: valeur.split(' ').map(parseFloat), pleine };
 }
 
 (async () => {
@@ -55,13 +80,41 @@ async function colonnes(browser, largeur, selecteur) {
     for (const [repartition, fr] of Object.entries(REPARTITIONS)) {
       configurer(piedPrincipal(repartition, fr.length));
       for (const appareil of APPAREILS) {
-        const px = await colonnes(browser, appareil.largeur, '.site-primary-footer-wrap .ast-builder-grid-row');
+        const { px } = await colonnes(browser, appareil.largeur, '.site-primary-footer-wrap .ast-builder-grid-row');
         const attendu = appareil.mobile ? [1] : fr;
         const proportions = px.length === attendu.length && px.every((w, i) => Math.abs(w / px[0] - attendu[i] / attendu[0]) < 0.02);
         if (!proportions) echecs.push(`${repartition} / ${appareil.nom} : colonnes ${px.map(Math.round).join(' ')} px, proportions attendues ${attendu.join(':')}`);
       }
     }
 
+    // Répartitions par appareil : la même valeur sur tablette et mobile, l'ordinateur à part.
+    for (const [nombre, liste] of Object.entries(PETITS)) {
+      for (const repartition of liste) {
+        const n = Number(nombre);
+        configurer(piedPrincipal({ desktop: ORDINATEUR[n], tablet: repartition, mobile: repartition }, n));
+        for (const appareil of APPAREILS.slice(1)) {
+          const { px, pleine } = await colonnes(browser, appareil.largeur, '.site-primary-footer-wrap .ast-builder-grid-row', DEBORDE[repartition] || '');
+          const attendu = DEBORDE[repartition] ? [1, 1] : REPARTITIONS[repartition];
+          const proportions = px.length === attendu.length && px.every((w, i) => Math.abs(w / px[0] - attendu[i] / attendu[0]) < 0.02);
+          if (!proportions || !pleine) echecs.push(`${n} colonnes, ${repartition} / ${appareil.nom} : colonnes ${px.map(Math.round).join(' ')} px, proportions attendues ${attendu.join(':')}${pleine ? '' : ', colonne débordante trop étroite'}`);
+        }
+        const { px } = await colonnes(browser, 1280, '.site-primary-footer-wrap .ast-builder-grid-row');
+        if (px.length !== n) echecs.push(`${n} colonnes, ${repartition} : ${px.length} colonnes sur ordinateur`);
+        petites++;
+      }
+    }
+    for (const [nombre, liste] of Object.entries(TABLETTE)) {
+      for (const repartition of liste) {
+        const n = Number(nombre);
+        configurer(piedPrincipal({ desktop: ORDINATEUR[n], tablet: repartition, mobile: 'full' }, n));
+        const { px } = await colonnes(browser, 800, '.site-primary-footer-wrap .ast-builder-grid-row');
+        const attendu = REPARTITIONS[repartition];
+        const proportions = px.length === attendu.length && px.every((w, i) => Math.abs(w / px[0] - attendu[i] / attendu[0]) < 0.02);
+        if (!proportions) echecs.push(`${n} colonnes, ${repartition} / tablette : colonnes ${px.map(Math.round).join(' ')} px, proportions attendues ${attendu.join(':')}`);
+        petites++;
+      }
+    }
+
     // Largeur des rangées du pied : variable de largeur du conteneur, pas 1200 px.
     configurer('ecrire_config("astra/largeur_conteneur", "1600");' + piedPrincipal('3-equal', 3, { above: { above_1: ['copyright'] }, below: { below_1: ['html-2'] } }));
     const page = await browser.newPage({ viewport: { width: 1800, height: 900 }, userAgent: AGENT });
@@ -80,5 +133,5 @@ async function colonnes(browser, largeur, selecteur) {
     console.error(echecs.join('\n'));
     process.exit(1);
   }
-  console.log(`Répartitions : OK (${Object.keys(REPARTITIONS).length} répartitions × ${APPAREILS.length} appareils, largeur des rangées)`);
+  console.log(`Répartitions : OK (${Object.keys(REPARTITIONS).length} répartitions × ${APPAREILS.length} appareils, ${petites} répartitions tablette et mobile, largeur des rangées)`);
 })();
diff --git a/tests/integration/HfbFormulaireTest.php b/tests/integration/HfbFormulaireTest.php
index 8d55915..b2a2e1d 100644
--- a/tests/integration/HfbFormulaireTest.php
+++ b/tests/integration/HfbFormulaireTest.php
@@ -77,4 +77,64 @@ final class HfbFormulaireTest extends TestCase {
 		$this->assertSame('Bonjour', $config['elements']['pied']['html-1']['texte']);
 		$this->assertSame(['source' => 'rubrique', 'id' => 1], $config['elements']['entete']['menu-2']);
 	}
+
+	public function testRepartitionParAppareil(): void {
+		$valeurs = formulaires_configurer_astra_hfb_charger_dist();
+		$valeurs['place']['pied']['html-1'] = 'primary_1';
+		$valeurs['rangees']['pied_primary']['colonnes'] = '4';
+		$valeurs['rangees']['pied_primary']['repartition'] = ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'];
+		$this->poster($valeurs);
+		$this->assertSame([], formulaires_configurer_astra_hfb_verifier_dist());
+		formulaires_configurer_astra_hfb_traiter_dist();
+		$this->assertSame(['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'], lire_config('astra/hfb/rangees/pied_primary/repartition'));
+		$pied = recuperer_fond('inclure/footer', ['type_page' => 'sommaire']);
+		$this->assertStringContainsString('ast-builder-grid-row-4-equal ast-builder-grid-row-tablet-full ast-builder-grid-row-mobile-full', $pied);
+	}
+
+	public function testRenduDesSelecteursDeRepartition(): void {
+		ecrire_config('astra/hfb', ['rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => ['desktop' => '4-equal', 'tablet' => '2-equal', 'mobile' => 'full']]]]);
+		$html = recuperer_fond('formulaires/configurer_astra_hfb', ['editable' => true] + formulaires_configurer_astra_hfb_charger_dist());
+		$x = HfbRenduTest::xpath($html);
+		$choisi = function (string $appareil) use ($x): string {
+			$n = $x->query('//select[@name="rangees[pied_primary][repartition][' . $appareil . ']"]/option[@selected]');
+			return $n->length === 1 ? $n->item(0)->getAttribute('value') : '';
+		};
+		$this->assertSame('4-equal', $choisi('desktop'));
+		$this->assertSame('2-equal', $choisi('tablet'));
+		$this->assertSame('full', $choisi('mobile'));
+		ecrire_config('astra/hfb', ['rangees' => ['pied_below' => ['colonnes' => 1, 'repartition' => 'full']]]);
+		$x = HfbRenduTest::xpath(recuperer_fond('formulaires/configurer_astra_hfb', ['editable' => true] + formulaires_configurer_astra_hfb_charger_dist()));
+		$n = $x->query('//select[@name="rangees[pied_above][repartition][mobile]"]/option[@selected]');
+		$this->assertSame('full', $n->item(0)->getAttribute('value'), 'Valeur simple : mobile en pleine largeur');
+	}
+
+	public function testAllerRetourDesRepartitionsLourdesAQuatreColonnes(): void {
+		// Valeur simple de la version 1.2.0 : elle vaut pour l'ordinateur et la tablette. Le
+		// formulaire doit la reproposer telle quelle, et l'enregistrement ne rien changer au rendu.
+		$classes = function (): string {
+			preg_match('/ast-builder-grid-row-4-[a-z]+ ast-builder-grid-row-tablet-[a-z0-9-]+ ast-builder-grid-row-mobile-[a-z0-9-]+/', recuperer_fond('inclure/footer', ['type_page' => 'sommaire']), $m);
+			return $m[0] ?? '';
+		};
+		foreach (['4-lheavy', '4-rheavy'] as $repartition) {
+			effacer_config('astra/hfb');
+			ecrire_config('astra/hfb', [
+				'pied' => ['primary' => ['primary_1' => ['html-1']]],
+				'rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => $repartition]],
+			]);
+			$avant = $classes();
+			$this->assertSame("ast-builder-grid-row-$repartition ast-builder-grid-row-tablet-$repartition ast-builder-grid-row-mobile-full", $avant);
+			$valeurs = formulaires_configurer_astra_hfb_charger_dist();
+			$x = HfbRenduTest::xpath(recuperer_fond('formulaires/configurer_astra_hfb', ['editable' => true] + $valeurs));
+			$postee = [];
+			foreach (['desktop', 'tablet', 'mobile'] as $appareil) {
+				$n = $x->query('//select[@name="rangees[pied_primary][repartition][' . $appareil . ']"]/option[@selected]');
+				$postee[$appareil] = $n->length === 1 ? $n->item(0)->getAttribute('value') : '';
+			}
+			$valeurs['rangees']['pied_primary']['repartition'] = $postee;
+			$this->poster($valeurs);
+			$this->assertSame([], formulaires_configurer_astra_hfb_verifier_dist(), $repartition);
+			formulaires_configurer_astra_hfb_traiter_dist();
+			$this->assertSame($avant, $classes(), $repartition);
+		}
+	}
 }
diff --git a/tests/references/wp/R12-pied-appareils.json b/tests/references/wp/R12-pied-appareils.json
new file mode 100644
index 0000000..e8b8a30
--- /dev/null
+++ b/tests/references/wp/R12-pied-appareils.json
@@ -0,0 +1,36 @@
+{
+	"ordinateur": [
+		".site-above-footer-wrap .ast-footer-html-1",
+		".site-primary-footer-wrap .footer-widget-area",
+		".site-below-footer-wrap .ast-footer-copyright"
+	],
+	"mobile": [
+		".site-below-footer-wrap .ast-footer-copyright"
+	],
+	"presents": [
+		".site-primary-footer-wrap.ast-builder-grid-row-4-equal.ast-builder-grid-row-tablet-full.ast-builder-grid-row-mobile-full",
+		".site-above-footer-wrap.ast-builder-grid-row-tablet-2-equal.ast-builder-grid-row-mobile-full"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".site-primary-footer-wrap .ast-builder-grid-row",
+			"proprietes": ["grid-template-columns"]
+		},
+		{
+			"selecteur": ".site-above-footer-wrap .ast-builder-grid-row",
+			"proprietes": ["grid-template-columns"]
+		},
+		{
+			"selecteur": ".site-below-footer-wrap .ast-builder-grid-row",
+			"proprietes": ["grid-template-columns"]
+		},
+		{
+			"selecteur": ".site-primary-footer-wrap .site-footer-section-2",
+			"proprietes": ["display", "margin-bottom"]
+		}
+	]
+}
diff --git a/tests/references/wp/R12-pied-appareils.php b/tests/references/wp/R12-pied-appareils.php
new file mode 100644
index 0000000..2064cd1
--- /dev/null
+++ b/tests/references/wp/R12-pied-appareils.php
@@ -0,0 +1,26 @@
+<?php
+require __DIR__ . '/commun.php';
+ref_etat_initial();
+ref_menu('Principal', 'primary');
+ref_menu('Principal', 'mobile_menu');
+ref_menu('Pied', 'footer_menu');
+// Répartitions par appareil de Garden Maintenance : rangée principale en quatre colonnes sur
+// ordinateur, une seule sur tablette et mobile ; rangées haute et basse en deux colonnes sur
+// ordinateur et tablette, une sur mobile.
+ref_reglages([
+	'footer-desktop-items' => [
+		'above'   => ['above_1' => ['html-1'], 'above_2' => ['social-icons-1'], 'above_3' => [], 'above_4' => [], 'above_5' => []],
+		'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4'], 'primary_5' => []],
+		'below'   => ['below_1' => ['copyright'], 'below_2' => ['menu'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
+	],
+	'hba-footer-column' => '2',
+	'hba-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
+	'hb-footer-column' => '4',
+	'hb-footer-layout' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'],
+	'hbb-footer-column' => '2',
+	'hbb-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
+	'footer-html-1' => '<p>Un site éditorial propulsé par SPIP</p>',
+]);
+foreach ([1, 2, 3, 4] as $i) {
+	ref_widget_texte('footer-widget-' . $i, 'Colonne ' . $i, 'Texte de la colonne ' . $i);
+}
diff --git a/tests/unit/HfbSaisieTest.php b/tests/unit/HfbSaisieTest.php
index 7b1973c..8ab492b 100644
--- a/tests/unit/HfbSaisieTest.php
+++ b/tests/unit/HfbSaisieTest.php
@@ -79,6 +79,36 @@ final class HfbSaisieTest extends TestCase {
 		$this->assertSame([], astra_hfb_verifier_saisie(astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]))));
 	}
 
+	public function testRepartitionsProposeesParAppareil(): void {
+		$this->assertSame(['4-equal', '4-lheavy', '4-rheavy'], astra_hfb_repartitions(4));
+		// Tablette : choix d'Astra, puis les répartitions de l'ordinateur (valeur simple de la 1.2.0).
+		$this->assertSame(['4-equal', '2-equal', 'full', '4-lheavy', '4-rheavy'], astra_hfb_repartitions(4, 'tablet'));
+		$this->assertSame(['3-equal', '3-cwide', '3-cheavy', '3-lheavy', '3-rheavy', '3-firstrow', '3-lastrow', 'full'], astra_hfb_repartitions(3, 'tablet'));
+		$this->assertSame(['4-equal', '2-equal', 'full'], astra_hfb_repartitions(4, 'mobile'));
+		$this->assertContains('3-firstrow', astra_hfb_repartitions(3, 'mobile'));
+		$this->assertNotContains('3-firstrow', astra_hfb_repartitions(3));
+		$this->assertSame([], astra_hfb_repartitions(4, 'montre'));
+		$liste = astra_hfb_repartitions_appareil('tablet');
+		$this->assertSame(array_values(array_unique($liste)), $liste, 'Sélecteur sans doublon');
+		$this->assertContains('2-equal', $liste);
+	}
+
+	public function testVerificationDeLaRepartitionParAppareil(): void {
+		$saisie = astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]));
+		$saisie['rangees']['pied_primary'] = ['colonnes' => '4', 'repartition' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'], 'hauteur' => '', 'fond' => ''];
+		$this->assertSame([], astra_hfb_verifier_saisie($saisie));
+		$saisie['rangees']['pied_primary']['repartition']['mobile'] = '3-firstrow';
+		$this->assertArrayHasKey('rangees_pied_primary_repartition', astra_hfb_verifier_saisie($saisie), 'Répartition mobile de 3 colonnes pour une rangée de 4');
+		$saisie['rangees']['pied_primary']['repartition'] = ['desktop' => 'full', 'tablet' => 'full', 'mobile' => 'full'];
+		$this->assertArrayHasKey('rangees_pied_primary_repartition', astra_hfb_verifier_saisie($saisie), 'full n\'est pas proposé sur ordinateur pour 4 colonnes');
+	}
+
+	public function testRepartitionParAppareilDepuisSaisie(): void {
+		$r = astra_hfb_rangees_depuis_saisie(['pied_primary' => ['colonnes' => '4', 'repartition' => ['desktop' => '4-equal', 'tablet' => ' full ', 'mobile' => 'full']]]);
+		$this->assertSame(['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'], $r['pied_primary']['repartition']);
+		$this->assertSame('2-equal', astra_hfb_rangees_depuis_saisie([])['pied_above']['repartition'], 'Valeur simple par défaut conservée');
+	}
+
 	public function testReglagesDElementsAllerRetour(): void {
 		$config = astra_hfb_normaliser(['elements' => [
 			'entete' => ['menu-2' => ['source' => 'rubrique', 'id' => 4], 'social-icons-1' => ['reseaux' => [['reseau' => 'mastodon', 'url' => 'https://m.example/@a']]]],
diff --git a/tests/unit/HfbTest.php b/tests/unit/HfbTest.php
index 6cd085b..a427501 100644
--- a/tests/unit/HfbTest.php
+++ b/tests/unit/HfbTest.php
@@ -207,6 +207,21 @@ final class HfbTest extends TestCase {
 		$this->assertStringContainsString('ast-builder-grid-row-full ', $rangees[0]['classes']);
 	}
 
+	public function testRepartitionParAppareil(): void {
+		$c = astra_hfb_normaliser([
+			'pied' => ['primary' => ['primary_1' => ['widget-1']]],
+			'rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full']]],
+		]);
+		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
+		$this->assertStringContainsString('ast-builder-grid-row-4-equal ast-builder-grid-row-tablet-full ast-builder-grid-row-mobile-full', $rangees[0]['classes']);
+	}
+
+	public function testRepartitionParAppareilIncompleteOuInvalide(): void {
+		$this->assertSame(['desktop' => '2-equal', 'tablet' => 'full', 'mobile' => 'full'], astra_hfb_repartition_appareils(['desktop' => '2-equal', 'mobile' => '" x']));
+		$this->assertSame(['desktop' => '3-cwide', 'tablet' => '3-cwide', 'mobile' => 'full'], astra_hfb_repartition_appareils('3-cwide'), 'Valeur simple : rendu de la version 1.0');
+		$this->assertSame(['desktop' => 'full', 'tablet' => 'full', 'mobile' => 'full'], astra_hfb_repartition_appareils(null));
+	}
+
 	// CSS des rangées
 
 	public function testCssDesRangeesVideParDefaut(): void {
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 1 erreur (`Call to undefined function astra_hfb_repartition_appareils()`, `HfbTest::testRepartitionParAppareilIncompleteOuInvalide`) et 4 échecs (`HfbSaisieTest::testRepartitionsProposeesParAppareil`, `testVerificationDeLaRepartitionParAppareil`, `testRepartitionParAppareilDepuisSaisie`, `HfbTest::testRepartitionParAppareil`) ;
- intégration : 3 échecs (`HfbFormulaireTest::testRepartitionParAppareil`, `testRenduDesSelecteursDeRepartition`, `testAllerRetourDesRepartitionsLourdesAQuatreColonnes`).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/formulaires/configurer_astra_hfb.html b/formulaires/configurer_astra_hfb.html
index 77e4bf4..7faabc7 100644
--- a/formulaires/configurer_astra_hfb.html
+++ b/formulaires/configurer_astra_hfb.html
@@ -153,9 +153,16 @@
 						</select>
 					</td>
 					<td>
-						<select name="rangees\[#GET{cle}\]\[repartition\]" aria-label="[(#GET{libelle}|attribut_html)] : <:astra:hfb_repartition|attribut_html:>">
-							<BOUCLE_nb_rep(DATA){enum 1,6}><BOUCLE_rep(DATA){source table, #VALEUR|astra_hfb_repartitions}><option value="#VALEUR"[ (#VALEUR|=={#GET{r}|table_valeur{repartition}}|oui)selected="selected"]>[(#VAL{astra:hfb_repartition_}|concat{#VALEUR|replace{-,_}}|_T)]</option></BOUCLE_rep></BOUCLE_nb_rep>
+						#SET{rep,#GET{r}|table_valeur{repartition}|astra_hfb_repartition_appareils}
+						<BOUCLE_appareils(DATA){liste desktop,tablet,mobile}>
+						#SET{appareil,#VALEUR}
+						#SET{libelle_appareil,#VAL{astra:hfb_appareil_}|concat{#VALEUR}|_T}
+						<label class="astra-hfb-appareil">#GET{libelle_appareil}
+						<select name="rangees\[#GET{cle}\]\[repartition\]\[#GET{appareil}\]" aria-label="[(#GET{libelle}|attribut_html)] : <:astra:hfb_repartition|attribut_html:> ([(#GET{libelle_appareil}|attribut_html)])">
+							<BOUCLE_rep(DATA){source table, #GET{appareil}|astra_hfb_repartitions_appareil}><option value="#VALEUR"[ (#VALEUR|=={#GET{rep}|table_valeur{#GET{appareil}}}|oui)selected="selected"]>[(#VAL{astra:hfb_repartition_}|concat{#VALEUR|replace{-,_}}|_T)]</option></BOUCLE_rep>
 						</select>
+						</label>
+						</BOUCLE_appareils>
 					</td>
 					</BOUCLE_si_colonnes>
 					<td></td><td></td>
diff --git a/inc/astra_hfb.php b/inc/astra_hfb.php
index 3148bc5..02f8d23 100644
--- a/inc/astra_hfb.php
+++ b/inc/astra_hfb.php
@@ -264,6 +264,26 @@ function astra_hfb_calculer_panneau(array $mobile): array {
 	return $bouton ? astra_hfb_decrire($mobile['popup']['popup_content'] ?? [], 'entete') : [];
 }
 
+/**
+ * Répartition d'une rangée du pied par appareil. Valeur simple : ordinateur et tablette, mobile
+ * `full` (comportement de la version 1.0) ; structure d'Astra (`hb-footer-layout`) : une valeur
+ * par appareil, `full` pour un appareil absent ou invalide, comme footer-row.php d'Astra 4.11.0.
+ */
+function astra_hfb_repartition_appareils($valeur): array {
+	$valide = function ($v): string {
+		return is_string($v) && preg_match('/^(full|[2-6]-[a-z]+)$/', $v) ? $v : 'full';
+	};
+	if (!is_array($valeur)) {
+		$v = $valide($valeur);
+		return ['desktop' => $v, 'tablet' => $v, 'mobile' => 'full'];
+	}
+	return [
+		'desktop' => $valide($valeur['desktop'] ?? ''),
+		'tablet' => $valide($valeur['tablet'] ?? ''),
+		'mobile' => $valide($valeur['mobile'] ?? ''),
+	];
+}
+
 /**
  * Rangées non vides d'un pied de page normalisé, avec leurs classes et leurs colonnes.
  */
@@ -281,16 +301,16 @@ function astra_hfb_calculer_pied(array $items, array $rangees): array {
 		if (!$occupee) {
 			continue;
 		}
-		$repartition = preg_match('/^(full|[2-6]-[a-z]+)$/', (string) ($reglages['repartition'] ?? '')) ? $reglages['repartition'] : 'full';
+		$repartition = astra_hfb_repartition_appareils($reglages['repartition'] ?? '');
 		$resultat[] = [
 			'rangee' => $r,
 			'classes' => implode(' ', [
 				'site-' . $r . '-footer-wrap',
 				'ast-builder-grid-row-container',
 				'site-footer-focus-item',
-				'ast-builder-grid-row-' . $repartition,
-				'ast-builder-grid-row-tablet-' . $repartition,
-				'ast-builder-grid-row-mobile-full',
+				'ast-builder-grid-row-' . $repartition['desktop'],
+				'ast-builder-grid-row-tablet-' . $repartition['tablet'],
+				'ast-builder-grid-row-mobile-' . $repartition['mobile'],
 				'ast-footer-row-stack',
 				'ast-footer-row-tablet-stack',
 				'ast-footer-row-mobile-stack',
diff --git a/inc/astra_hfb_saisie.php b/inc/astra_hfb_saisie.php
index de2ff40..66d7442 100644
--- a/inc/astra_hfb_saisie.php
+++ b/inc/astra_hfb_saisie.php
@@ -250,11 +250,17 @@ function astra_hfb_verifier_saisie(array $saisie): array {
 		}
 		if (isset($defaut['colonnes'])) {
 			$colonnes = (int) ($r['colonnes'] ?? 0);
-			$repartition = (string) ($r['repartition'] ?? '');
+			$repartition = $r['repartition'] ?? '';
+			$appareils = is_array($repartition) ? ['desktop', 'tablet', 'mobile'] : ['desktop'];
 			if ($colonnes < 1 || $colonnes > ASTRA_HFB_COLONNES_MAX) {
 				$erreurs['rangees_' . $cle . '_colonnes'] = 'colonnes';
-			} elseif (!in_array($repartition, astra_hfb_repartitions($colonnes), true)) {
-				$erreurs['rangees_' . $cle . '_repartition'] = 'repartition';
+			} else {
+				foreach ($appareils as $appareil) {
+					$v = is_array($repartition) ? ($repartition[$appareil] ?? '') : $repartition;
+					if (!in_array($v, astra_hfb_repartitions($colonnes, $appareil), true)) {
+						$erreurs['rangees_' . $cle . '_repartition'] = 'repartition';
+					}
+				}
 			}
 		}
 	}
@@ -266,10 +272,14 @@ function astra_hfb_verifier_saisie(array $saisie): array {
 }
 
 /**
- * Répartitions des colonnes du pied proposées par Astra pour un nombre de colonnes.
+ * Répartitions des colonnes du pied proposées par Astra pour un nombre de colonnes et un appareil
+ * (Astra_Builder_Helper, choix du contrôle ast-row-layout, Astra 4.11.0) : tablette et mobile
+ * ajoutent `full` et, selon le nombre de colonnes, `2-equal`, `3-firstrow`, `3-lastrow`.
+ * La tablette garde en plus les répartitions de l'ordinateur (`4-lheavy`, `4-rheavy`) : une valeur
+ * simple de la version 1.2.0 s'y applique, et le formulaire doit pouvoir la reproposer.
  */
-function astra_hfb_repartitions(int $colonnes): array {
-	$toutes = [
+function astra_hfb_repartitions(int $colonnes, string $appareil = 'desktop'): array {
+	$ordinateur = [
 		1 => ['full'],
 		2 => ['2-equal', '2-lheavy', '2-rheavy'],
 		3 => ['3-equal', '3-cwide', '3-cheavy', '3-lheavy', '3-rheavy'],
@@ -277,7 +287,34 @@ function astra_hfb_repartitions(int $colonnes): array {
 		5 => ['5-equal'],
 		6 => ['6-equal'],
 	];
-	return $toutes[$colonnes] ?? [];
+	$petits = [
+		1 => ['full'],
+		2 => ['2-equal', '2-lheavy', '2-rheavy', 'full'],
+		3 => ['3-equal', '3-cwide', '3-cheavy', '3-lheavy', '3-rheavy', '3-firstrow', '3-lastrow', 'full'],
+		4 => ['4-equal', '2-equal', 'full'],
+		5 => ['5-equal', 'full'],
+		6 => ['6-equal', 'full'],
+	];
+	if (!in_array($appareil, ['desktop', 'tablet', 'mobile'], true)) {
+		return [];
+	}
+	if ($appareil === 'tablet') {
+		return array_values(array_unique(array_merge($petits[$colonnes] ?? [], $ordinateur[$colonnes] ?? [])));
+	}
+	return ($appareil === 'desktop' ? $ordinateur : $petits)[$colonnes] ?? [];
+}
+
+/**
+ * Répartitions proposées pour un appareil, tous nombres de colonnes confondus, sans doublon et
+ * dans l'ordre d'Astra : liste du sélecteur ; la vérification écarte celles qui ne vont pas avec
+ * le nombre de colonnes choisi.
+ */
+function astra_hfb_repartitions_appareil(string $appareil): array {
+	$toutes = [];
+	for ($colonnes = 1; $colonnes <= ASTRA_HFB_COLONNES_MAX; $colonnes++) {
+		$toutes = array_merge($toutes, astra_hfb_repartitions($colonnes, $appareil));
+	}
+	return array_values(array_unique($toutes));
 }
 
 /** Réglages de rangées à enregistrer à partir des champs du formulaire. */
@@ -285,6 +322,16 @@ function astra_hfb_rangees_depuis_saisie(array $saisie): array {
 	$rangees = [];
 	foreach (astra_hfb_defauts()['rangees'] as $cle => $defaut) {
 		foreach ($defaut as $reglage => $valeur) {
+			if ($reglage === 'repartition' && is_array($saisie[$cle][$reglage] ?? null)) {
+				// Une répartition par appareil (structure d'Astra) ; une valeur simple reste simple.
+				$r = $saisie[$cle][$reglage];
+				$rangees[$cle][$reglage] = [
+					'desktop' => trim((string) ($r['desktop'] ?? $valeur)),
+					'tablet' => trim((string) ($r['tablet'] ?? $valeur)),
+					'mobile' => trim((string) ($r['mobile'] ?? 'full')),
+				];
+				continue;
+			}
 			$v = trim((string) ($saisie[$cle][$reglage] ?? $valeur));
 			if (in_array($reglage, ['colonnes', 'bordure'], true) || ($reglage === 'hauteur' && $v !== '')) {
 				$v = (int) $v;
diff --git a/lang/astra_en.php b/lang/astra_en.php
index 69d7205..f630531 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -68,6 +68,9 @@ return [
 
 	// H
 	'hfb_adresse' => 'Address',
+	'hfb_appareil_desktop' => 'Desktop',
+	'hfb_appareil_mobile' => 'Mobile',
+	'hfb_appareil_tablet' => 'Tablet',
 	'hfb_bloc_articles_recents' => 'Recent posts',
 	'hfb_bloc_forums_recents' => 'Recent comments',
 	'hfb_bloc_mots_cles' => 'Keywords',
@@ -144,6 +147,8 @@ return [
 	'hfb_repartition_3_cheavy' => '3 columns, extra-wide centre',
 	'hfb_repartition_3_cwide' => '3 columns, wide centre',
 	'hfb_repartition_3_equal' => '3 equal columns',
+	'hfb_repartition_3_firstrow' => '3 columns, first full width',
+	'hfb_repartition_3_lastrow' => '3 columns, last full width',
 	'hfb_repartition_3_lheavy' => '3 columns, wide left',
 	'hfb_repartition_3_rheavy' => '3 columns, wide right',
 	'hfb_repartition_4_equal' => '4 equal columns',
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index b61fb3d..b467f00 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -68,6 +68,9 @@ return [
 
 	// H
 	'hfb_adresse' => 'Adresse',
+	'hfb_appareil_desktop' => 'Ordinateur',
+	'hfb_appareil_mobile' => 'Mobile',
+	'hfb_appareil_tablet' => 'Tablette',
 	'hfb_bloc_articles_recents' => 'Articles récents',
 	'hfb_bloc_forums_recents' => 'Commentaires récents',
 	'hfb_bloc_mots_cles' => 'Mots-clés',
@@ -144,6 +147,8 @@ return [
 	'hfb_repartition_3_cheavy' => '3 colonnes, centre très large',
 	'hfb_repartition_3_cwide' => '3 colonnes, centre large',
 	'hfb_repartition_3_equal' => '3 colonnes égales',
+	'hfb_repartition_3_firstrow' => '3 colonnes, première en pleine largeur',
+	'hfb_repartition_3_lastrow' => '3 colonnes, dernière en pleine largeur',
 	'hfb_repartition_3_lheavy' => '3 colonnes, gauche large',
 	'hfb_repartition_3_rheavy' => '3 colonnes, droite large',
 	'hfb_repartition_4_equal' => '4 colonnes égales',
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (76 tests, 1049 assertions)`, intégration `OK (72 tests, 245 assertions)`

Puis, SPIP de test servi (ici sur le port 8414) et WordPress de test sur 8401 :

```bash
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R12-pied-appareils R6-menu R10-sociaux
php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R0-defaut R3-pied-colonnes R4-pied-asymetrique R12-pied-appareils
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R0-defaut R3-pied-colonnes R4-pied-asymetrique R12-pied-appareils R6-menu R10-sociaux
RACINE=vendor/spip/spip URL=http://127.0.0.1:8414 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
```

Attendu : HTML identique (en-tête, panneau, pied) pour R0, R3, R4 et R12 ; toutes les vues sous le seuil ; R12 : `grid-template-columns` égal aux trois largeurs ; `Répartitions : OK (14 répartitions × 3 appareils, 21 répartitions tablette et mobile, largeur des rangées)`.

- [ ] **Étape 5 : commit**

```bash
git add inc/astra_hfb.php inc/astra_hfb_saisie.php formulaires/configurer_astra_hfb.html lang/astra_fr.php lang/astra_en.php tests/unit/HfbTest.php tests/unit/HfbSaisieTest.php tests/integration/HfbFormulaireTest.php tests/comparaison/configurations.php tests/e2e/repartitions.cjs tests/references/wp/R12-pied-appareils.php tests/references/wp/R12-pied-appareils.json
git commit -m "Builder : répartition des colonnes du pied par appareil"
```

### Tâche 2 : séparateurs des rangées du pied

Spec § 2. Nouveaux réglages de rangée `separateur` (épaisseur en px, entier 0 à 20, vide = défaut d'Astra) et `couleur_separateur`. Défauts d'Astra 4.11.0 (`inc/core/builder/class-astra-builder-options.php`) : rangée principale et basse 1 px, haute aucun ; couleur `var( --ast-global-color-subtle-background,--ast-global-color-7 )`. CSS porté des `dynamic.css.php` des trois rangées du pied (`inc/builder/type/footer/{above,primary,below}-footer/dynamic-css/`), émis seulement si la valeur diffère du défaut ; une épaisseur 0 là où Astra a un séparateur par défaut émet la neutralisation (`border-style:none`), comme Astra.

**Fichiers :**
- Modifier : `inc/astra_hfb.php`, `inc/astra_hfb_saisie.php`, `formulaires/configurer_astra_hfb.html`, `lang/astra_fr.php`, `lang/astra_en.php`
- Modifier : `tests/unit/HfbTest.php`, `tests/unit/HfbSaisieTest.php`, `tests/comparaison/configurations.php`, `tests/references/wp/R5-rangees.php`, `tests/references/wp/R5-rangees.json`

**Interfaces :**
- Consomme : `astra_styles_couleur_valide()` (`inc/astra_styles.php`, désormais chargé par `inc/astra_hfb.php`).
- Produit : constante `ASTRA_HFB_COULEUR_SEPARATEUR` ; `astra_hfb_css_separateur(string $barre, array $r, array $defaut): array` (règles CSS d'une rangée du pied, vide sans écart au défaut).

- [ ] **Étape 1 : écrire les tests et compléter R5**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 2335c1e..f958a20 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -120,7 +120,20 @@ function astra_configurations_reference(): array {
 			]],
 		],
 		'R5-rangees' => [
-			'rangees' => ['entete_primary' => ['hauteur' => 100, 'fond' => '#e8f5e9', 'bordure' => 0]],
+			'pied' => [
+				'above' => ['above_1' => ['html-1']],
+				'primary' => ['primary_1' => ['html-2']],
+			],
+			'rangees' => [
+				'entete_primary' => ['hauteur' => 100, 'fond' => '#e8f5e9', 'bordure' => 0],
+				'pied_above' => ['separateur' => 2, 'couleur_separateur' => '#c0392b'],
+				'pied_primary' => ['separateur' => 0],
+				'pied_below' => ['couleur_separateur' => 'var(--ast-global-color-4)'],
+			],
+			'elements' => ['pied' => [
+				'html-1' => ['texte' => 'Rangée haute'],
+				'html-2' => ['texte' => 'Rangée principale'],
+			]],
 		],
 		'R6-menu' => $styles(['menu'], ['rubrique' => 'Jardinage']),
 		'R7-bouton' => $styles(['bouton']),
diff --git a/tests/references/wp/R5-rangees.json b/tests/references/wp/R5-rangees.json
index a8c32a2..81d15c8 100644
--- a/tests/references/wp/R5-rangees.json
+++ b/tests/references/wp/R5-rangees.json
@@ -2,7 +2,9 @@
 	"ordinateur": [
 		".site-primary-header-wrap .site-branding",
 		".ast-builder-menu-1",
-		".site-below-footer-wrap .ast-footer-copyright"
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-above-footer-wrap .ast-footer-html-1",
+		".site-primary-footer-wrap .ast-footer-html-2"
 	],
 	"mobile": [
 		".ast-mobile-header-wrap .menu-toggle"
@@ -37,6 +39,30 @@
 			"proprietes": [
 				"min-height"
 			]
+		},
+		{
+			"selecteur": ".site-above-footer-wrap",
+			"proprietes": [
+				"border-top-width",
+				"border-top-style",
+				"border-top-color"
+			]
+		},
+		{
+			"selecteur": ".site-primary-footer-wrap",
+			"proprietes": [
+				"border-top-width",
+				"border-top-style",
+				"border-top-color"
+			]
+		},
+		{
+			"selecteur": ".site-below-footer-wrap",
+			"proprietes": [
+				"border-top-width",
+				"border-top-style",
+				"border-top-color"
+			]
 		}
 	]
 }
diff --git a/tests/references/wp/R5-rangees.php b/tests/references/wp/R5-rangees.php
index 4691996..effd036 100644
--- a/tests/references/wp/R5-rangees.php
+++ b/tests/references/wp/R5-rangees.php
@@ -1,5 +1,6 @@
 <?php
-// Réglages des rangées de l'en-tête (hauteur, fond, séparateur de la rangée principale).
+// Réglages des rangées de l'en-tête (hauteur, fond, séparateur de la rangée principale) et
+// séparateurs des rangées du pied (rangée principale sans filet, rangées haute et basse colorées).
 require __DIR__ . '/commun.php';
 ref_etat_initial();
 ref_menu('Principal', 'primary');
@@ -12,4 +13,15 @@ ref_reglages([
 	'hb-header-height' => ['desktop' => 100, 'tablet' => '', 'mobile' => ''],
 	'hb-header-bg-obj-responsive' => $fond('#e8f5e9'),
 	'hb-header-main-sep' => 0,
+	'footer-desktop-items' => [
+		'above'   => ['above_1' => ['html-1'], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
+		'primary' => ['primary_1' => ['html-2'], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_5' => []],
+		'below'   => ['below_1' => ['copyright'], 'below_2' => [], 'below_3' => [], 'below_4' => [], 'below_5' => []],
+	],
+	'footer-html-1' => '<p>Rangée haute</p>',
+	'footer-html-2' => '<p>Rangée principale</p>',
+	'hba-footer-separator' => 2,
+	'hba-footer-top-border-color' => '#c0392b',
+	'hb-footer-main-sep' => 0,
+	'hbb-footer-top-border-color' => 'var(--ast-global-color-4)',
 ]);
diff --git a/tests/unit/HfbSaisieTest.php b/tests/unit/HfbSaisieTest.php
index 8ab492b..cd65728 100644
--- a/tests/unit/HfbSaisieTest.php
+++ b/tests/unit/HfbSaisieTest.php
@@ -79,6 +79,21 @@ final class HfbSaisieTest extends TestCase {
 		$this->assertSame([], astra_hfb_verifier_saisie(astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]))));
 	}
 
+	public function testVerificationDesSeparateurs(): void {
+		$saisie = astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]));
+		$saisie['rangees']['pied_primary']['separateur'] = '0';
+		$saisie['rangees']['pied_below']['couleur_separateur'] = 'var(--ast-global-color-4)';
+		$this->assertSame([], astra_hfb_verifier_saisie($saisie));
+		$saisie['rangees']['pied_primary']['separateur'] = '25';
+		$saisie['rangees']['pied_below']['couleur_separateur'] = 'red';
+		$erreurs = astra_hfb_verifier_saisie($saisie);
+		$this->assertArrayHasKey('rangees_pied_primary_separateur', $erreurs);
+		$this->assertArrayHasKey('rangees_pied_below_couleur_separateur', $erreurs);
+		$r = astra_hfb_rangees_depuis_saisie(['pied_primary' => ['separateur' => '0'], 'pied_above' => ['separateur' => '']]);
+		$this->assertSame(0, $r['pied_primary']['separateur']);
+		$this->assertSame('', $r['pied_above']['separateur']);
+	}
+
 	public function testRepartitionsProposeesParAppareil(): void {
 		$this->assertSame(['4-equal', '4-lheavy', '4-rheavy'], astra_hfb_repartitions(4));
 		// Tablette : choix d'Astra, puis les répartitions de l'ordinateur (valeur simple de la 1.2.0).
@@ -134,7 +149,7 @@ final class HfbSaisieTest extends TestCase {
 	public function testRangeesDepuisSaisie(): void {
 		$r = astra_hfb_rangees_depuis_saisie(['entete_above' => ['hauteur' => ' 42 ', 'fond' => '#abc', 'bordure' => '0'], 'pied_primary' => ['colonnes' => '4', 'repartition' => '4-equal', 'hauteur' => '']]);
 		$this->assertSame(['hauteur' => 42, 'fond' => '#abc', 'bordure' => 0], $r['entete_above']);
-		$this->assertSame(['colonnes' => 4, 'repartition' => '4-equal', 'hauteur' => '', 'fond' => ''], $r['pied_primary']);
+		$this->assertSame(['colonnes' => 4, 'repartition' => '4-equal', 'hauteur' => '', 'fond' => '', 'separateur' => 1, 'couleur_separateur' => ''], $r['pied_primary']);
 		$this->assertSame(astra_hfb_defauts()['rangees']['pied_below'], $r['pied_below']);
 	}
 }
diff --git a/tests/unit/HfbTest.php b/tests/unit/HfbTest.php
index a427501..a46ca56 100644
--- a/tests/unit/HfbTest.php
+++ b/tests/unit/HfbTest.php
@@ -247,4 +247,29 @@ final class HfbTest extends TestCase {
 		$css = astra_hfb_css_rangees($c['rangees']);
 		$this->assertStringContainsString('.ast-header-break-point .ast-primary-header-bar, .ast-primary-header-bar{border-bottom-width:0px;}', $css);
 	}
+
+	public function testSeparateursDuPied(): void {
+		// Valeurs de Garden : pas de filet au-dessus de la rangée principale, filet de la couleur 4
+		// au-dessus de la rangée basse ; rangée haute : 2 px, couleur par défaut d'Astra (aucune).
+		$c = astra_hfb_normaliser(['rangees' => [
+			'pied_primary' => ['separateur' => 0],
+			'pied_below' => ['couleur_separateur' => 'var(--ast-global-color-4)'],
+			'pied_above' => ['separateur' => 2],
+		]]);
+		$css = astra_hfb_css_rangees($c['rangees']);
+		$this->assertStringContainsString('.site-primary-footer-wrap[data-section="section-primary-footer-builder"]{border-style:none;border-width:medium;border-color:currentcolor;}', $css);
+		$this->assertStringContainsString('.site-below-footer-wrap[data-section="section-below-footer-builder"]{border-style:solid;border-width:0px;border-top-width:1px;border-top-color:var(--ast-global-color-4);}', $css);
+		$this->assertStringContainsString('.site-above-footer-wrap[data-section="section-above-footer-builder"]{border-style:solid;border-width:0px;border-top-width:2px;}', $css);
+	}
+
+	public function testSeparateursParDefautOuInvalidesSansCss(): void {
+		$c = astra_hfb_normaliser(['rangees' => [
+			'pied_primary' => ['separateur' => '', 'couleur_separateur' => ''],
+			'pied_above' => ['separateur' => 0],
+			'pied_below' => ['couleur_separateur' => 'red;}body{display:none'],
+		]]);
+		$this->assertSame('', astra_hfb_css_rangees($c['rangees']), 'Épaisseur vide (défaut d\'Astra), rangée haute sans filet, couleur refusée');
+		$c = astra_hfb_normaliser(['rangees' => ['pied_primary' => ['couleur_separateur' => '#123456']]]);
+		$this->assertStringContainsString('{border-style:solid;border-width:0px;border-top-width:1px;border-top-color:#123456;}', astra_hfb_css_rangees($c['rangees']));
+	}
 }
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 4 échecs (`HfbSaisieTest::testVerificationDesSeparateurs`, `testRangeesDepuisSaisie`, `HfbTest::testSeparateursDuPied`, `testSeparateursParDefautOuInvalidesSansCss`) ;
- intégration : OK (72 tests, 245 assertions) — aucun test d'intégration nouveau ; R5 vérifie le rendu à l'étape 4.

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/formulaires/configurer_astra_hfb.html b/formulaires/configurer_astra_hfb.html
index 7faabc7..1a1d551 100644
--- a/formulaires/configurer_astra_hfb.html
+++ b/formulaires/configurer_astra_hfb.html
@@ -135,17 +135,19 @@
 			<legend><:astra:hfb_reglages_rangees:></legend>
 			<p class="explication"><:astra:hfb_explication_rangees:></p>
 			<table class="spip astra-hfb-table">
-				<thead><tr><th scope="col"><:astra:hfb_rangee:></th><th scope="col"><:astra:hfb_hauteur:></th><th scope="col"><:astra:hfb_fond:></th><th scope="col"><:astra:hfb_bordure:></th><th scope="col"><:astra:hfb_colonnes:></th><th scope="col"><:astra:hfb_repartition:></th></tr></thead>
+				<thead><tr><th scope="col"><:astra:hfb_rangee:></th><th scope="col"><:astra:hfb_hauteur:></th><th scope="col"><:astra:hfb_fond:></th><th scope="col"><:astra:hfb_bordure:></th><th scope="col"><:astra:hfb_separateur:></th><th scope="col"><:astra:hfb_couleur_separateur:></th><th scope="col"><:astra:hfb_colonnes:></th><th scope="col"><:astra:hfb_repartition:></th></tr></thead>
 				<tbody>
 				<BOUCLE_rangees(DATA){source table, #VAL|astra_hfb_defauts|table_valeur{rangees}}>
 				#SET{cle,#CLE}
 				#SET{r,#ENV{rangees}|table_valeur{#CLE}}
 				#SET{libelle,#VAL{astra:hfb_rangee_}|concat{#CLE}|_T}
 				<tr>
-					<th scope="row">#GET{libelle}<BOUCLE_erreurs_rangee(DATA){liste hauteur,fond,bordure,colonnes,repartition}>[<span class="erreur_message">(#ENV**{erreurs}|table_valeur{#VAL{rangees_}|concat{#GET{cle},_,#VALEUR}})</span>]</BOUCLE_erreurs_rangee></th>
+					<th scope="row">#GET{libelle}<BOUCLE_erreurs_rangee(DATA){liste hauteur,fond,bordure,separateur,couleur_separateur,colonnes,repartition}>[<span class="erreur_message">(#ENV**{erreurs}|table_valeur{#VAL{rangees_}|concat{#GET{cle},_,#VALEUR}})</span>]</BOUCLE_erreurs_rangee></th>
 					<td><input type="number" min="0" max="999" class="text" name="rangees\[#CLE\]\[hauteur\]" value="[(#GET{r}|table_valeur{hauteur})]" aria-label="[(#GET{libelle}|attribut_html)] : <:astra:hfb_hauteur|attribut_html:>" /></td>
 					<td><input type="text" class="text" pattern="#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?" placeholder="#eeeeee" name="rangees\[#CLE\]\[fond\]" value="[(#GET{r}|table_valeur{fond})]" aria-label="[(#GET{libelle}|attribut_html)] : <:astra:hfb_fond|attribut_html:>" /></td>
 					<td>[(#VALEUR|table_valeur{bordure}|strlen|oui)<input type="number" min="0" max="20" class="text" name="rangees\[#CLE\]\[bordure\]" value="[(#GET{r}|table_valeur{bordure})]" aria-label="[(#GET{libelle}|attribut_html)] : <:astra:hfb_bordure|attribut_html:>" />]</td>
+					<td>[(#VALEUR|table_valeur{couleur_separateur}|is_string|oui)<input type="number" min="0" max="20" class="text" name="rangees\[#CLE\]\[separateur\]" value="[(#GET{r}|table_valeur{separateur})]" aria-label="[(#GET{libelle}|attribut_html)] : <:astra:hfb_separateur|attribut_html:>" />]</td>
+					<td>[(#VALEUR|table_valeur{couleur_separateur}|is_string|oui)<input type="text" class="text" placeholder="var(--ast-global-color-4)" name="rangees\[#CLE\]\[couleur_separateur\]" value="[(#GET{r}|table_valeur{couleur_separateur})]" aria-label="[(#GET{libelle}|attribut_html)] : <:astra:hfb_couleur_separateur|attribut_html:>" />]</td>
 					<BOUCLE_si_colonnes(CONDITION){si #VALEUR|table_valeur{colonnes}}>
 					<td>
 						<select name="rangees\[#GET{cle}\]\[colonnes\]" aria-label="[(#GET{libelle}|attribut_html)] : <:astra:hfb_colonnes|attribut_html:>">
diff --git a/inc/astra_hfb.php b/inc/astra_hfb.php
index 02f8d23..e805552 100644
--- a/inc/astra_hfb.php
+++ b/inc/astra_hfb.php
@@ -8,7 +8,12 @@
  * template-parts/header/builder/header-row.php et template-parts/footer/builder/footer-row.php.
  */
 
+require_once __DIR__ . '/astra_styles.php';
+
 const ASTRA_HFB_RANGEES = ['above', 'primary', 'below'];
+
+/** Couleur par défaut des séparateurs du pied (hb-footer-main-sep-color, hbb-footer-top-border-color). */
+const ASTRA_HFB_COULEUR_SEPARATEUR = 'var( --ast-global-color-subtle-background,--ast-global-color-7 )';
 const ASTRA_HFB_ZONES = ['left', 'left_center', 'center', 'right_center', 'right'];
 const ASTRA_HFB_ZONES_MOBILE = ['left', 'center', 'right'];
 const ASTRA_HFB_COLONNES_MAX = 6;
@@ -42,9 +47,11 @@ function astra_hfb_defauts(): array {
 			'entete_above' => ['hauteur' => 50, 'fond' => '', 'bordure' => 1],
 			'entete_primary' => ['hauteur' => 80, 'fond' => '', 'bordure' => 1],
 			'entete_below' => ['hauteur' => 60, 'fond' => '', 'bordure' => 1],
-			'pied_above' => ['colonnes' => 2, 'repartition' => '2-equal', 'hauteur' => 60, 'fond' => ''],
-			'pied_primary' => ['colonnes' => 3, 'repartition' => '3-equal', 'hauteur' => '', 'fond' => ''],
-			'pied_below' => ['colonnes' => 1, 'repartition' => 'full', 'hauteur' => 60, 'fond' => ''],
+			// Séparateurs : défauts d'Astra 4.11.0 (hba-footer-separator absent, hb-footer-main-sep et
+			// hbb-footer-separator à 1) ; couleur vide = couleur par défaut d'Astra.
+			'pied_above' => ['colonnes' => 2, 'repartition' => '2-equal', 'hauteur' => 60, 'fond' => '', 'separateur' => '', 'couleur_separateur' => ''],
+			'pied_primary' => ['colonnes' => 3, 'repartition' => '3-equal', 'hauteur' => '', 'fond' => '', 'separateur' => 1, 'couleur_separateur' => ''],
+			'pied_below' => ['colonnes' => 1, 'repartition' => 'full', 'hauteur' => 60, 'fond' => '', 'separateur' => 1, 'couleur_separateur' => ''],
 		],
 		'menu_mobile' => 'dropdown',
 		'elements' => [
@@ -323,7 +330,8 @@ function astra_hfb_calculer_pied(array $items, array $rangees): array {
 
 /**
  * CSS des réglages de rangées qui diffèrent des valeurs par défaut d'Astra
- * (hauteur minimale, couleur de fond, épaisseur de la bordure basse de l'en-tête).
+ * (hauteur minimale, couleur de fond, épaisseur de la bordure basse de l'en-tête, séparateur
+ * des rangées du pied).
  */
 function astra_hfb_css_rangees(array $rangees): string {
 	$defauts = astra_hfb_defauts()['rangees'];
@@ -349,6 +357,30 @@ function astra_hfb_css_rangees(array $rangees): string {
 			$selecteur = $cle === 'entete_primary' ? '.ast-header-break-point ' . $barre . ', ' . $barre : $barre;
 			$css[] = $selecteur . '{border-bottom-width:' . (int) $r['bordure'] . 'px;}';
 		}
+		if (array_key_exists('separateur', $defaut)) {
+			$css = array_merge($css, astra_hfb_css_separateur($barre, $r, $defaut));
+		}
 	}
 	return implode("\n", $css);
 }
+
+/**
+ * Séparateur d'une rangée du pied (inc/builder/type/footer/*-footer/dynamic-css/dynamic.css.php
+ * d'Astra 4.11.0) : à partir de 1 px, bordure haute de cette épaisseur et de cette couleur. Sans
+ * séparateur alors qu'Astra en pose un par défaut (css/astra-hfb.css, css/astra-dynamic.css), la
+ * bordure est ramenée aux valeurs initiales de CSS : celles d'une rangée sans règle de bordure.
+ */
+function astra_hfb_css_separateur(string $barre, array $r, array $defaut): array {
+	// Champ vide : valeur d'Astra (explication du formulaire des rangées).
+	$epaisseur = (string) $r['separateur'] !== '' ? (string) $r['separateur'] : (string) $defaut['separateur'];
+	$couleur = (string) $r['couleur_separateur'];
+	if (!preg_match('/^\d{0,2}$/', $epaisseur) || !astra_styles_couleur_valide($couleur)
+		|| ($epaisseur === (string) $defaut['separateur'] && $couleur === (string) $defaut['couleur_separateur'])) {
+		return [];
+	}
+	if ((int) $epaisseur >= 1) {
+		$couleur = $couleur !== '' ? $couleur : ((int) $defaut['separateur'] >= 1 ? ASTRA_HFB_COULEUR_SEPARATEUR : '');
+		return [$barre . '{border-style:solid;border-width:0px;border-top-width:' . (int) $epaisseur . 'px;' . ($couleur !== '' ? 'border-top-color:' . $couleur . ';' : '') . '}'];
+	}
+	return (int) $defaut['separateur'] >= 1 ? [$barre . '{border-style:none;border-width:medium;border-color:currentcolor;}'] : [];
+}
diff --git a/inc/astra_hfb_saisie.php b/inc/astra_hfb_saisie.php
index 66d7442..fd23c6b 100644
--- a/inc/astra_hfb_saisie.php
+++ b/inc/astra_hfb_saisie.php
@@ -248,6 +248,15 @@ function astra_hfb_verifier_saisie(array $saisie): array {
 		if (isset($defaut['bordure']) && !preg_match('/^\d{1,2}$/', (string) ($r['bordure'] ?? ''))) {
 			$erreurs['rangees_' . $cle . '_bordure'] = 'nombre';
 		}
+		if (array_key_exists('separateur', $defaut)) {
+			$epaisseur = (string) ($r['separateur'] ?? '');
+			if (!preg_match('/^\d{0,2}$/', $epaisseur) || (int) $epaisseur > 20) {
+				$erreurs['rangees_' . $cle . '_separateur'] = 'nombre';
+			}
+			if (!astra_styles_couleur_valide((string) ($r['couleur_separateur'] ?? ''))) {
+				$erreurs['rangees_' . $cle . '_couleur_separateur'] = 'couleur';
+			}
+		}
 		if (isset($defaut['colonnes'])) {
 			$colonnes = (int) ($r['colonnes'] ?? 0);
 			$repartition = $r['repartition'] ?? '';
@@ -333,7 +342,7 @@ function astra_hfb_rangees_depuis_saisie(array $saisie): array {
 				continue;
 			}
 			$v = trim((string) ($saisie[$cle][$reglage] ?? $valeur));
-			if (in_array($reglage, ['colonnes', 'bordure'], true) || ($reglage === 'hauteur' && $v !== '')) {
+			if (in_array($reglage, ['colonnes', 'bordure'], true) || (in_array($reglage, ['hauteur', 'separateur'], true) && $v !== '')) {
 				$v = (int) $v;
 			}
 			$rangees[$cle][$reglage] = $v;
diff --git a/lang/astra_en.php b/lang/astra_en.php
index f630531..c390ab5 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -83,6 +83,7 @@ return [
 	'hfb_builder_ordinateur' => 'Desktop header',
 	'hfb_builder_pied' => 'Footer',
 	'hfb_colonnes' => 'Columns',
+	'hfb_couleur_separateur' => 'Separator colour',
 	'hfb_disposition' => 'Footer menu layout',
 	'hfb_disposition_horizontal' => 'Horizontal',
 	'hfb_disposition_vertical' => 'Vertical',
@@ -157,6 +158,7 @@ return [
 	'hfb_repartition_5_equal' => '5 equal columns',
 	'hfb_repartition_6_equal' => '6 equal columns',
 	'hfb_repartition_full' => '1 column',
+	'hfb_separateur' => 'Separator (px)',
 	'hfb_source' => 'Source',
 	'hfb_source_groupe_mots' => 'Articles of the « @titre@ » group',
 	'hfb_source_pages' => 'Single pages',
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index b467f00..e008891 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -83,6 +83,7 @@ return [
 	'hfb_builder_ordinateur' => 'En-tête sur ordinateur',
 	'hfb_builder_pied' => 'Pied de page',
 	'hfb_colonnes' => 'Colonnes',
+	'hfb_couleur_separateur' => 'Couleur du séparateur',
 	'hfb_disposition' => 'Disposition du menu du pied de page',
 	'hfb_disposition_horizontal' => 'Horizontale',
 	'hfb_disposition_vertical' => 'Verticale',
@@ -157,6 +158,7 @@ return [
 	'hfb_repartition_5_equal' => '5 colonnes égales',
 	'hfb_repartition_6_equal' => '6 colonnes égales',
 	'hfb_repartition_full' => '1 colonne',
+	'hfb_separateur' => 'Séparateur (px)',
 	'hfb_source' => 'Source',
 	'hfb_source_groupe_mots' => 'Articles du groupe « @titre@ »',
 	'hfb_source_pages' => 'Pages uniques',
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (79 tests, 1059 assertions)`, intégration `OK (72 tests, 245 assertions)`

```bash
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R5-rangees
php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R0-defaut R5-rangees
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R0-defaut R5-rangees
```

Attendu : HTML identique ; R5 : toutes les vues sous le seuil, `styles-1280`, `styles-768`, `styles-390` : 17 valeurs égales (dont `border-top-width`, `border-top-color` des trois rangées du pied).

- [ ] **Étape 5 : commit**

```bash
git add inc/astra_hfb.php inc/astra_hfb_saisie.php formulaires/configurer_astra_hfb.html lang/astra_fr.php lang/astra_en.php tests/unit/HfbTest.php tests/unit/HfbSaisieTest.php tests/comparaison/configurations.php tests/references/wp/R5-rangees.php tests/references/wp/R5-rangees.json
git commit -m "Builder : séparateurs des rangées du pied"
```

### Tâche 3 : bloc menu dans les widgets

Spec § 3, avec l'écart 1. Nouveau bloc `menu` de `astra_hfb_blocs()` ; réglage `menu` = `{"source", "id"}` du widget, défaut `{"source": "racine", "id": 0}`, sources validées comme celles de `menu-1` (aides communes `astra_hfb_source_id()` et `astra_hfb_source_depuis_saisie()`). Balisage du widget `nav_menu` de WordPress sous Astra, relevé sur R13 : `section.widget.widget_nav_menu`, titre `h2.widget-title` facultatif, `nav.menu-…-container[aria-label]`, `ul.menu` à deux niveaux. `inclure/hfb/menu-items.html` gagne une variante `widget=oui` (lien parent `aria-expanded`, flèche `span.dropdown-menu-toggle` sans icône, pas de bouton) ; le menu de l'en-tête garde son balisage.

**Fichiers :**
- Créer : `inclure/widgets/menu.html`, `tests/references/wp/R13-pied-widgets-menus.php`, `tests/references/wp/R13-pied-widgets-menus.json`
- Modifier : `inc/astra_hfb.php`, `inc/astra_hfb_saisie.php`, `inclure/hfb/menu-items.html`, `inclure/hfb/widget.html`, `formulaires/inc-astra-hfb-widget.html`, `lang/astra_fr.php`, `lang/astra_en.php`
- Modifier : `tests/unit/HfbTest.php`, `tests/unit/HfbSaisieTest.php`, `tests/integration/HfbRenduTest.php`, `tests/comparaison/configurations.php`, `tests/references/wp/commun.php`

**Interfaces :**
- Produit : `astra_hfb_source_id(array $menu): string` (`racine`, `rubrique:<id>`, `groupe_mots:<id>`, `pages`) et `astra_hfb_source_depuis_saisie(string $source_id): array` (`['source' => …, 'id' => …]`, source inconnue : racine) dans `inc/astra_hfb_saisie.php`.
- Produit : `inclure/widgets/menu.html`, paramètres `balise`, `titre`, `menu`, `numero`, `groupe`, `id_rubrique`, `id_secteur`, `id_article`, `type_page`.
- Produit : `ref_widget_menu(string $zone, string $titre, string $menu)` dans `tests/references/wp/commun.php`.

- [ ] **Étape 1 : écrire les tests et la référence**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index f958a20..0cf1ea6 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -103,6 +103,17 @@ function astra_configurations_reference(): array {
 				'widget-4' => ['blocs' => ['texte'], 'titre' => 'Colonne 4', 'texte' => 'Texte de la colonne 4'],
 			]],
 		],
+		'R13-pied-widgets-menus' => [
+			'pied' => [
+				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3']],
+			],
+			'rangees' => ['pied_primary' => ['colonnes' => 3, 'repartition' => '3-equal']],
+			'elements' => ['pied' => [
+				'widget-1' => ['blocs' => ['texte'], 'titre' => 'Colonne 1', 'texte' => 'Texte de la colonne 1'],
+				'widget-2' => ['blocs' => ['menu'], 'titre' => 'Liens rapides', 'menu' => ['source' => 'racine', 'id' => 0]],
+				'widget-3' => ['blocs' => ['menu'], 'titre' => 'Nos services', 'menu' => ['source' => 'racine', 'id' => 0]],
+			]],
+		],
 		'R4-pied-asymetrique' => [
 			'pied' => [
 				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['html-1', 'social-icons-1'], 'primary_3' => ['menu']],
diff --git a/tests/integration/HfbRenduTest.php b/tests/integration/HfbRenduTest.php
index 6a66bf9..62f516d 100644
--- a/tests/integration/HfbRenduTest.php
+++ b/tests/integration/HfbRenduTest.php
@@ -208,4 +208,66 @@ final class HfbRenduTest extends TestCase {
 		$this->assertSame(1, $this->compter($p, $principale . '//ul[@id="astra-footer-menu"][contains(@class, "astra-footer-vertical-menu")]'));
 		$this->assertSame(1, $this->compter($p, $f . '/' . self::classe('site-below-footer-wrap ast-builder-grid-row-2-lheavy') . '//' . self::classe('site-footer-below-section-2') . '/' . self::classe('ast-footer-html-2')));
 	}
+
+	public function testBlocMenuDansLesWidgetsDuPied(): void {
+		// Balisage du widget nav_menu de WordPress sous Astra (référence R13-pied-widgets-menus) :
+		// titre, nav étiquetée par le titre, liste .menu à deux niveaux, lien parent aria-expanded,
+		// flèche sans icône, pas de bouton de dépliage.
+		$x = $this->pied(astra_configurations_reference()['R13-pied-widgets-menus']);
+		$widget = '//' . self::classe('footer-widget-area') . '[@data-section="sidebar-widgets-footer-widget-2"]/section[@class="widget widget_nav_menu"]';
+		$this->assertSame(1, $this->compter($x, $widget . '/h2[@class="widget-title"][.="Liens rapides"]'));
+		$nav = $widget . '/nav[@aria-label="Liens rapides"]/ul[@class="menu"]';
+		$this->assertSame(1, $this->compter($x, $nav), 'nav étiquetée et liste .menu');
+		$parent = $nav . '/li[contains(@class, "menu-item-has-children")]';
+		$this->assertSame(1, $this->compter($x, $parent . '/a[@aria-expanded="false"][@class="menu-link"]/span[@role="application"][@class="dropdown-menu-toggle ast-header-navigation-arrow"][@aria-expanded="false"][not(*)]'));
+		$this->assertGreaterThan(0, $this->compter($x, $parent . '/ul[@class="sub-menu"]/li/a[@class="menu-link"]'));
+		$this->assertSame(0, $this->compter($x, $widget . '//button'), 'Pas de bouton de dépliage dans un widget');
+		$this->assertSame(1, $this->compter($x, '//' . self::classe('footer-widget-area') . '[@data-section="sidebar-widgets-footer-widget-3"]/section[contains(@class, "widget_nav_menu")]'));
+	}
+
+	public function testBlocMenuSansTitreNiSource(): void {
+		$x = $this->pied([
+			'pied' => ['primary' => ['primary_1' => ['widget-1']]],
+			'elements' => ['pied' => ['widget-1' => ['blocs' => ['menu']]]],
+		]);
+		$widget = '//section[contains(@class, "widget_nav_menu")]';
+		$this->assertSame(0, $this->compter($x, $widget . '/h2'), 'Pas de titre vide');
+		$this->assertGreaterThan(1, $this->compter($x, $widget . '/nav[@aria-label="Menu"]/ul[@class="menu"]/li'), 'Rubriques racines par défaut');
+	}
+
+	public function testBlocMenuDeChaqueSource(): void {
+		$liens = function (array $menu): array {
+			$x = $this->pied([
+				'pied' => ['primary' => ['primary_1' => ['widget-1']]],
+				'elements' => ['pied' => ['widget-1' => ['blocs' => ['menu'], 'menu' => $menu]]],
+			]);
+			$textes = [];
+			foreach ($x->query('//section[contains(@class, "widget_nav_menu")]/nav/ul[@class="menu"]/li/a') as $a) {
+				$textes[] = trim($a->textContent);
+			}
+			return $textes;
+		};
+		$this->assertSame(['Jardinage', 'Actualités'], $liens(['source' => 'racine', 'id' => 0]));
+		$jardinage = (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre=' . sql_quote('10. Jardinage'));
+		$this->assertSame(['Potager'], $liens(['source' => 'rubrique', 'id' => $jardinage]));
+		$this->assertContains('Notre jardin', $liens(['source' => 'pages', 'id' => 0]));
+		// Groupe de mots-clés : articles liés à ses mots, créés pour le test.
+		$groupe = sql_insertq('spip_groupes_mots', ['titre' => 'Services', 'tables_liees' => 'articles']);
+		$mot = sql_insertq('spip_mots', ['titre' => 'Entretien', 'id_groupe' => $groupe, 'type' => 'Services']);
+		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'titre=' . sql_quote('Article du potager'));
+		sql_insertq('spip_mots_liens', ['id_mot' => $mot, 'id_objet' => $article, 'objet' => 'article']);
+		try {
+			$this->assertSame(['Article du potager'], $liens(['source' => 'groupe_mots', 'id' => $groupe]));
+		} finally {
+			sql_delete('spip_mots_liens', 'id_mot=' . (int) $mot);
+			sql_delete('spip_mots', 'id_mot=' . (int) $mot);
+			sql_delete('spip_groupes_mots', 'id_groupe=' . (int) $groupe);
+		}
+	}
+
+	public function testMenuDEnTeteGardeSonBalisage(): void {
+		$x = $this->entete(self::configR1());
+		$this->assertGreaterThan(0, $this->compter($x, '//*[@id="ast-desktop-header"]//ul[contains(@class, "main-header-menu")]//li[contains(@class, "menu-item-has-children")]/button[@class="ast-menu-toggle"]'));
+		$this->assertSame(0, $this->compter($x, '//*[@id="ast-desktop-header"]//ul[contains(@class, "main-header-menu")]//a[@aria-expanded]'));
+	}
 }
diff --git a/tests/references/wp/R13-pied-widgets-menus.json b/tests/references/wp/R13-pied-widgets-menus.json
new file mode 100644
index 0000000..7415734
--- /dev/null
+++ b/tests/references/wp/R13-pied-widgets-menus.json
@@ -0,0 +1,16 @@
+{
+	"ordinateur": [
+		".site-primary-footer-wrap .footer-widget-area .widget_nav_menu"
+	],
+	"mobile": [
+		".site-below-footer-wrap .ast-footer-copyright"
+	],
+	"presents": [
+		".footer-widget-area[data-section=\"sidebar-widgets-footer-widget-2\"] .widget_nav_menu .menu",
+		".footer-widget-area[data-section=\"sidebar-widgets-footer-widget-3\"] .widget_nav_menu .menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	]
+}
diff --git a/tests/references/wp/R13-pied-widgets-menus.php b/tests/references/wp/R13-pied-widgets-menus.php
new file mode 100644
index 0000000..1aa8481
--- /dev/null
+++ b/tests/references/wp/R13-pied-widgets-menus.php
@@ -0,0 +1,20 @@
+<?php
+// Deux widgets « Menu de navigation » dans le pied, comme les widgets 2 et 3 de Garden
+// Maintenance : menu Principal (Jardinage > Potager, Actualités) et menu Pied.
+require __DIR__ . '/commun.php';
+ref_etat_initial();
+ref_menu('Principal', 'primary');
+ref_menu('Principal', 'mobile_menu');
+ref_menu('Pied', 'footer_menu');
+ref_reglages([
+	'footer-desktop-items' => [
+		'above'   => ['above_1' => [], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
+		'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => [], 'primary_5' => []],
+		'below'   => ['below_1' => ['copyright'], 'below_2' => [], 'below_3' => [], 'below_4' => [], 'below_5' => []],
+	],
+	'hb-footer-column' => '3',
+	'hb-footer-layout' => ['desktop' => '3-equal', 'tablet' => '3-equal', 'mobile' => 'full'],
+]);
+ref_widget_texte('footer-widget-1', 'Colonne 1', 'Texte de la colonne 1');
+ref_widget_menu('footer-widget-2', 'Liens rapides', 'Principal');
+ref_widget_menu('footer-widget-3', 'Nos services', 'Pied');
diff --git a/tests/references/wp/commun.php b/tests/references/wp/commun.php
index e7cda23..6dc2847 100644
--- a/tests/references/wp/commun.php
+++ b/tests/references/wp/commun.php
@@ -15,6 +15,7 @@ function ref_etat_initial(): void {
 	update_option('show_on_front', 'posts');
 	update_option('page_on_front', 0);
 	delete_option('widget_text');
+	delete_option('widget_nav_menu');
 	$base = json_decode(file_get_contents(__DIR__ . '/widgets-base.json'), true);
 	foreach ($base['options'] as $option => $valeur) {
 		update_option($option, $valeur);
@@ -109,6 +110,17 @@ function ref_widget_texte(string $zone, string $titre, string $texte): void {
 	update_option('sidebars_widgets', $zones);
 }
 
+// Widget « Menu de navigation » (nav_menu) affichant un menu créé par ref_menu().
+function ref_widget_menu(string $zone, string $titre, string $menu): void {
+	$widgets = get_option('widget_nav_menu', []);
+	$n = max(array_merge([1], array_filter(array_keys($widgets), 'is_int'))) + 1;
+	$widgets[$n] = ['title' => $titre, 'nav_menu' => wp_get_nav_menu_object($menu)->term_id];
+	update_option('widget_nav_menu', $widgets);
+	$zones = get_option('sidebars_widgets', []);
+	$zones[$zone][] = 'nav_menu-' . $n;
+	update_option('sidebars_widgets', $zones);
+}
+
 // Pages uniques des références P* : mêmes titres, textes et ordre que le SPIP d'intégration
 // (scripts/install-spip-test.sh). Recréées à l'identique à chaque appel ; la page d'exemple
 // de WordPress est supprimée pour que les menus de pages soient comparables.
diff --git a/tests/unit/HfbSaisieTest.php b/tests/unit/HfbSaisieTest.php
index cd65728..b20544e 100644
--- a/tests/unit/HfbSaisieTest.php
+++ b/tests/unit/HfbSaisieTest.php
@@ -79,6 +79,18 @@ final class HfbSaisieTest extends TestCase {
 		$this->assertSame([], astra_hfb_verifier_saisie(astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]))));
 	}
 
+	public function testSourceDuBlocMenuAllerRetour(): void {
+		$config = astra_hfb_normaliser(['elements' => ['pied' => ['widget-2' => ['blocs' => ['menu'], 'titre' => 'Liens', 'menu' => ['source' => 'rubrique', 'id' => 3]]]]]);
+		$saisie = astra_hfb_saisie_depuis_config($config);
+		$this->assertSame('rubrique:3', $saisie['elements']['pied']['widget-2']['menu_source_id']);
+		$this->assertSame('racine', $saisie['elements']['pied']['widget-1']['menu_source_id'], 'Défaut : rubriques racines');
+		$elements = astra_hfb_elements_depuis_saisie($saisie['elements']);
+		$this->assertSame(['source' => 'rubrique', 'id' => 3], $elements['pied']['widget-2']['menu']);
+		$this->assertSame(['menu'], $elements['pied']['widget-2']['blocs']);
+		$saisie['elements']['pied']['widget-2']['menu_source_id'] = 'sql:1';
+		$this->assertSame(['source' => 'racine', 'id' => 0], astra_hfb_elements_depuis_saisie($saisie['elements'])['pied']['widget-2']['menu']);
+	}
+
 	public function testVerificationDesSeparateurs(): void {
 		$saisie = astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]));
 		$saisie['rangees']['pied_primary']['separateur'] = '0';
diff --git a/tests/unit/HfbTest.php b/tests/unit/HfbTest.php
index a46ca56..e3bcbc5 100644
--- a/tests/unit/HfbTest.php
+++ b/tests/unit/HfbTest.php
@@ -52,6 +52,11 @@ final class HfbTest extends TestCase {
 		$this->assertSame(['source' => 'racine', 'id' => 3], $c['elements']['entete']['menu-1']);
 	}
 
+	public function testBlocMenuProposeDansLesWidgets(): void {
+		$this->assertContains('menu', astra_hfb_blocs());
+		$this->assertSame(['source' => 'racine', 'id' => 0], astra_hfb_normaliser([])['elements']['pied']['widget-3']['menu']);
+	}
+
 	public function testReglagesParDefautDesElements(): void {
 		$c = astra_hfb_normaliser([]);
 		$this->assertSame(['facebook', 'twitter', 'instagram'], array_column($c['elements']['entete']['social-icons-1']['reseaux'], 'reseau'));
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 2 échecs (`HfbSaisieTest::testSourceDuBlocMenuAllerRetour`, `HfbTest::testBlocMenuProposeDansLesWidgets`) ;
- intégration : 3 échecs (`HfbRenduTest::testBlocMenuDansLesWidgetsDuPied`, `testBlocMenuSansTitreNiSource`, `testBlocMenuDeChaqueSource`) ; `testMenuDEnTeteGardeSonBalisage`, garde-fou du menu de l'en-tête, passe déjà.

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/formulaires/inc-astra-hfb-widget.html b/formulaires/inc-astra-hfb-widget.html
index 9194854..df14c24 100644
--- a/formulaires/inc-astra-hfb-widget.html
+++ b/formulaires/inc-astra-hfb-widget.html
@@ -22,3 +22,9 @@
 	<label for="#ENV{groupe}_#ENV{id}_texte">#GET{libelle} : <:astra:hfb_texte:></label>
 	<textarea name="elements\[#ENV{groupe}\]\[#ENV{id}\]\[texte\]" id="#ENV{groupe}_#ENV{id}_texte" rows="3">[(#GET{w}|table_valeur{texte})]</textarea>
 </div>
+<div class="editer editer_#ENV{groupe}_[(#ENV{id})]_menu">
+	<label for="#ENV{groupe}_#ENV{id}_menu">#GET{libelle} : <:astra:hfb_menu_widget:></label>
+	<select name="elements\[#ENV{groupe}\]\[#ENV{id}\]\[menu_source_id\]" id="#ENV{groupe}_#ENV{id}_menu">
+		<BOUCLE_sources(DATA){source table, #VAL|astra_hfb_sources_menu}><option value="#CLE"[ (#CLE|=={#GET{w}|table_valeur{menu_source_id}}|oui)selected="selected"]>#VALEUR</option></BOUCLE_sources>
+	</select>
+</div>
diff --git a/inc/astra_hfb.php b/inc/astra_hfb.php
index e805552..5678efe 100644
--- a/inc/astra_hfb.php
+++ b/inc/astra_hfb.php
@@ -37,7 +37,7 @@ function astra_hfb_defauts(): array {
 	$pied['below']['below_1'] = ['copyright'];
 	// Icônes par défaut d'Astra, sans adresse.
 	$reseaux = [['reseau' => 'facebook', 'url' => ''], ['reseau' => 'twitter', 'url' => ''], ['reseau' => 'instagram', 'url' => '']];
-	$widget = ['blocs' => [], 'titre' => '', 'texte' => ''];
+	$widget = ['blocs' => [], 'titre' => '', 'texte' => '', 'menu' => ['source' => 'racine', 'id' => 0]];
 	return [
 		'entete_ordinateur' => $entete(ASTRA_HFB_ZONES, ['primary_left' => ['logo'], 'primary_right' => ['menu-1']]),
 		'entete_mobile' => ['popup' => ['popup_content' => ['mobile-menu']]]
@@ -83,7 +83,7 @@ function astra_hfb_defauts(): array {
  * Blocs proposés dans les zones de widgets (squelettes inclure/widgets/<bloc>.html).
  */
 function astra_hfb_blocs(): array {
-	return ['recherche', 'sous_rubriques', 'articles_recents', 'forums_recents', 'mots_cles', 'texte'];
+	return ['recherche', 'sous_rubriques', 'articles_recents', 'forums_recents', 'mots_cles', 'texte', 'menu'];
 }
 
 /**
diff --git a/inc/astra_hfb_saisie.php b/inc/astra_hfb_saisie.php
index fd23c6b..9fe9b1f 100644
--- a/inc/astra_hfb_saisie.php
+++ b/inc/astra_hfb_saisie.php
@@ -126,13 +126,29 @@ function astra_hfb_saisie_depuis_config(array $config): array {
 	return $saisie;
 }
 
+/** Valeur du champ source_id d'un menu (racine, rubrique:<id>, groupe_mots:<id>, pages). */
+function astra_hfb_source_id(array $menu): string {
+	$source = (string) ($menu['source'] ?? 'racine');
+	return in_array($source, ['rubrique', 'groupe_mots'], true) ? $source . ':' . (int) ($menu['id'] ?? 0) : $source;
+}
+
+/** Source d'un menu à partir du champ source_id ; valeur inconnue : racine. */
+function astra_hfb_source_depuis_saisie(string $source_id): array {
+	if (preg_match('/^(rubrique|groupe_mots):(\d+)$/', $source_id, $m)) {
+		return ['source' => $m[1], 'id' => (int) $m[2]];
+	}
+	return ['source' => $source_id === 'pages' ? 'pages' : 'racine', 'id' => 0];
+}
+
 /** Valeurs des champs de réglages d'éléments à partir de la configuration. */
 function astra_hfb_saisie_elements(array $elements): array {
 	foreach ($elements as $groupe => $liste) {
 		foreach ($liste as $id => $r) {
 			if (isset($r['source'])) {
-				$elements[$groupe][$id]['source_id'] = in_array($r['source'], ['rubrique', 'groupe_mots'], true)
-					? $r['source'] . ':' . (int) $r['id'] : $r['source'];
+				$elements[$groupe][$id]['source_id'] = astra_hfb_source_id($r);
+			}
+			if (isset($r['menu']) && is_array($r['menu'])) {
+				$elements[$groupe][$id]['menu_source_id'] = astra_hfb_source_id($r['menu']);
 			}
 			if (isset($r['reseaux'])) {
 				$champs = [];
@@ -170,6 +186,8 @@ function astra_hfb_elements_depuis_saisie(array $saisie): array {
 							$r['reseaux'][] = ['reseau' => $reseau, 'url' => trim((string) ($c['url'] ?? ''))];
 						}
 					}
+				} elseif ($cle === 'menu') {
+					$r['menu'] = astra_hfb_source_depuis_saisie((string) ($champs['menu_source_id'] ?? 'racine'));
 				} elseif ($cle === 'blocs') {
 					$choisis = isset($champs['blocs']) && is_array($champs['blocs']) ? $champs['blocs'] : [];
 					$r['blocs'] = array_values(array_intersect(astra_hfb_blocs(), $choisis));
@@ -178,14 +196,7 @@ function astra_hfb_elements_depuis_saisie(array $saisie): array {
 				}
 			}
 			if (array_key_exists('source', $defaut)) {
-				$source_id = (string) ($champs['source_id'] ?? 'racine');
-				if (preg_match('/^(rubrique|groupe_mots):(\d+)$/', $source_id, $m)) {
-					$r = ['source' => $m[1], 'id' => (int) $m[2]] + $r;
-				} elseif ($source_id === 'pages') {
-					$r = ['source' => 'pages', 'id' => 0] + $r;
-				} else {
-					$r = ['source' => 'racine', 'id' => 0] + $r;
-				}
+				$r = astra_hfb_source_depuis_saisie((string) ($champs['source_id'] ?? 'racine')) + $r;
 			}
 			if (isset($defaut['disposition']) && !in_array($r['disposition'], ['horizontal', 'vertical'], true)) {
 				$r['disposition'] = $defaut['disposition'];
diff --git a/inclure/hfb/menu-items.html b/inclure/hfb/menu-items.html
index 86216cd..bcaa89e 100644
--- a/inclure/hfb/menu-items.html
+++ b/inclure/hfb/menu-items.html
@@ -9,15 +9,17 @@
 	- niveaux : 2 (avec sous-menus) ou 1
 	- accueil : 'oui' pour ajouter le lien « Accueil » (réglage menu_accueil)
 	- id_rubrique, id_secteur, id_article, type_page : page courante (état actif)
+	- widget : 'oui' pour le balisage du widget « Menu de navigation » de WordPress sous Astra
+	  (lien parent aria-expanded, flèche sans icône, pas de bouton de dépliage)
 
 ][(#VAL{menu_accueil}|astra_conf|=={oui}|et{#ENV{accueil}|=={oui}}|oui)
 <li class="menu-item menu-item-home[ (#ENV{type_page}|=={sommaire}|oui)current-menu-item]"><a href="#URL_SITE_SPIP/" class="menu-link"><:astra:accueil:></a></li>]
 <BOUCLE_si_rubriques(CONDITION){si #ENV{source}|in_array{#LISTE{racine,rubrique}}}>
 <BOUCLE_rubriques(RUBRIQUES){id_parent=#ENV{source}|=={rubrique}|?{#ENV{id_source},0}}{par num titre, titre}>
 #SET{enfants,''}<BOUCLE_test_enfants(RUBRIQUES){id_parent=#ID_RUBRIQUE}{0,1}>[(#ENV{niveaux}|!={1}|oui)#SET{enfants,oui}]</BOUCLE_test_enfants>
-<li class="menu-item menu-item-rubrique-#ID_RUBRIQUE[ (#GET{enfants}|oui)menu-item-has-children][ (#ID_RUBRIQUE|=={#ENV{id_rubrique}}|oui)current-menu-item][ (#ID_RUBRIQUE|=={#ENV{id_secteur}}|et{#ID_RUBRIQUE|!={#ENV{id_rubrique}}}|oui)current-menu-ancestor]"><a href="#URL_RUBRIQUE" class="menu-link"[ (#ID_RUBRIQUE|=={#ENV{id_rubrique}}|oui)aria-current="page"]>[(#TITRE|supprimer_numero)][(#GET{enfants}|oui)<span role="presentation" class="dropdown-menu-toggle ast-header-navigation-arrow" tabindex="0"><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></span>]</a>
+<li class="menu-item menu-item-rubrique-#ID_RUBRIQUE[ (#GET{enfants}|oui)menu-item-has-children][ (#ID_RUBRIQUE|=={#ENV{id_rubrique}}|oui)current-menu-item][ (#ID_RUBRIQUE|=={#ENV{id_secteur}}|et{#ID_RUBRIQUE|!={#ENV{id_rubrique}}}|oui)current-menu-ancestor]"><a[ (#GET{enfants}|et{#ENV{widget}}|oui)aria-expanded="false"] href="#URL_RUBRIQUE" class="menu-link"[ (#ID_RUBRIQUE|=={#ENV{id_rubrique}}|oui)aria-current="page"]>[(#TITRE|supprimer_numero)][(#GET{enfants}|et{#ENV{widget}|non}|oui)<span role="presentation" class="dropdown-menu-toggle ast-header-navigation-arrow" tabindex="0"><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></span>][(#GET{enfants}|et{#ENV{widget}}|oui)<span role="application" class="dropdown-menu-toggle ast-header-navigation-arrow" tabindex="0" aria-expanded="false" aria-label="<:astra:permutateur_menu|attribut_html:>" ></span>]</a>
 	<BOUCLE_si_sous_menu(CONDITION){si #GET{enfants}}>
-	<button class="ast-menu-toggle" aria-expanded="false"><span class="screen-reader-text"><:astra:deplier_menu:></span><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></button>
+	[(#ENV{widget}|non)<button class="ast-menu-toggle" aria-expanded="false"><span class="screen-reader-text"><:astra:deplier_menu:></span><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></button>]
 	<ul class="sub-menu">
 		<BOUCLE_sous_rubriques(RUBRIQUES){id_parent=#ID_RUBRIQUE}{par num titre, titre}>
 		<li class="menu-item menu-item-rubrique-#ID_RUBRIQUE[ (#ID_RUBRIQUE|=={#ENV{id_rubrique}}|oui)current-menu-item]"><a href="#URL_RUBRIQUE" class="menu-link"[ (#ID_RUBRIQUE|=={#ENV{id_rubrique}}|oui)aria-current="page"]>[(#TITRE|supprimer_numero)]</a></li>
@@ -36,9 +38,9 @@
 <BOUCLE_pages(DATA){source table, #VAL|astra_pages_arbre}>
 #SET{enfants,#ENV{niveaux}|=={1}|?{#ARRAY,#VALEUR{enfants}}}
 <BOUCLE_page(ARTICLES){id_article=#VALEUR{id}}>
-<li class="menu-item menu-item-article-#ID_ARTICLE[ (#GET{enfants}|oui)menu-item-has-children][ (#ID_ARTICLE|=={#ENV{id_article}}|oui)current-menu-item][ (#GET{enfants}|find{#ENV{id_article}}|oui)current-menu-ancestor]"><a href="#URL_ARTICLE" class="menu-link"[ (#ID_ARTICLE|=={#ENV{id_article}}|oui)aria-current="page"]>[(#TITRE|supprimer_numero)][(#GET{enfants}|oui)<span role="presentation" class="dropdown-menu-toggle ast-header-navigation-arrow" tabindex="0"><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></span>]</a>
+<li class="menu-item menu-item-article-#ID_ARTICLE[ (#GET{enfants}|oui)menu-item-has-children][ (#ID_ARTICLE|=={#ENV{id_article}}|oui)current-menu-item][ (#GET{enfants}|find{#ENV{id_article}}|oui)current-menu-ancestor]"><a[ (#GET{enfants}|et{#ENV{widget}}|oui)aria-expanded="false"] href="#URL_ARTICLE" class="menu-link"[ (#ID_ARTICLE|=={#ENV{id_article}}|oui)aria-current="page"]>[(#TITRE|supprimer_numero)][(#GET{enfants}|et{#ENV{widget}|non}|oui)<span role="presentation" class="dropdown-menu-toggle ast-header-navigation-arrow" tabindex="0"><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></span>][(#GET{enfants}|et{#ENV{widget}}|oui)<span role="application" class="dropdown-menu-toggle ast-header-navigation-arrow" tabindex="0" aria-expanded="false" aria-label="<:astra:permutateur_menu|attribut_html:>" ></span>]</a>
 	<BOUCLE_si_sous_pages(CONDITION){si #GET{enfants}}>
-	<button class="ast-menu-toggle" aria-expanded="false"><span class="screen-reader-text"><:astra:deplier_menu:></span><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></button>
+	[(#ENV{widget}|non)<button class="ast-menu-toggle" aria-expanded="false"><span class="screen-reader-text"><:astra:deplier_menu:></span><span class="ast-icon icon-arrow"><svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg></span></button>]
 	<ul class="sub-menu">
 		<BOUCLE_sous_pages(DATA){source table, #GET{enfants}}><BOUCLE_sous_page(ARTICLES){id_article=#VALEUR}>
 		<li class="menu-item menu-item-article-#ID_ARTICLE[ (#ID_ARTICLE|=={#ENV{id_article}}|oui)current-menu-item]"><a href="#URL_ARTICLE" class="menu-link"[ (#ID_ARTICLE|=={#ENV{id_article}}|oui)aria-current="page"]>[(#TITRE|supprimer_numero)]</a></li>
diff --git a/inclure/hfb/widget.html b/inclure/hfb/widget.html
index 2de6a41..4a45c57 100644
--- a/inclure/hfb/widget.html
+++ b/inclure/hfb/widget.html
@@ -3,8 +3,8 @@
 	Éléments « widget-N » : zone de widgets, en-tête ou pied de page, composée des blocs
 	choisis (inclure/widgets/), le bloc « texte » utilisant le titre et le texte de l'élément.
 
-	Paramètres : id, numero, groupe (entete | pied), id_rubrique
+	Paramètres : id, numero, groupe (entete | pied), id_rubrique, id_secteur, id_article, type_page
 
 ]#SET{r,#ENV{groupe}|astra_hfb_reglages{#ENV{id}}}#SET{cote,#ENV{groupe}|=={pied}|?{footer,header}}<aside class="#GET{cote}-widget-area widget-area site-#GET{cote}-focus-item #GET{cote}-widget-area-inner" data-section="sidebar-widgets-#GET{cote}-widget-#ENV{numero}" aria-label="[(#ENV{groupe}|=={pied}|?{<:astra:widgets_pied:>,<:astra:widgets_entete:>}|attribut_html)] #ENV{numero}">
-	<BOUCLE_blocs(DATA){source table, #ENV{groupe}|astra_hfb_blocs_element{#ENV{id}}}><INCLURE{fond=#VAL{inclure/widgets/}|concat{#VALEUR}}{balise=section}{titre=#GET{r}|table_valeur{titre}}{texte=#GET{r}|table_valeur{texte}}{id_rubrique} /></BOUCLE_blocs>
+	<BOUCLE_blocs(DATA){source table, #ENV{groupe}|astra_hfb_blocs_element{#ENV{id}}}><INCLURE{fond=#VAL{inclure/widgets/}|concat{#VALEUR}}{balise=section}{titre=#GET{r}|table_valeur{titre}}{texte=#GET{r}|table_valeur{texte}}{menu=#GET{r}|table_valeur{menu}}{numero}{groupe}{id_rubrique}{id_secteur}{id_article}{type_page} /></BOUCLE_blocs>
 </aside>
diff --git a/inclure/widgets/menu.html b/inclure/widgets/menu.html
new file mode 100644
index 0000000..09e6310
--- /dev/null
+++ b/inclure/widgets/menu.html
@@ -0,0 +1,13 @@
+[(#REM)
+
+	Bloc de widget : menu (widget « Menu de navigation » de WordPress sous Astra, nav_menu), deux
+	niveaux comme dans WordPress, avec le titre facultatif de la zone de widgets.
+
+	Paramètres : balise (aside | section), titre, menu (source, id), numero, groupe,
+	id_rubrique, id_secteur, id_article, type_page
+
+]#SET{menu,#ENV{menu}|sinon{#ARRAY{source,racine,id,0}}}<[(#ENV{balise,aside})] id="nav_menu-#ENV{groupe}-#ENV{numero}" class="widget widget_nav_menu">[
+	<h2 class="widget-title">(#ENV{titre}|textebrut)</h2>]<nav class="menu-[(#GET{menu}|table_valeur{source})]-container" aria-label="[(#ENV{titre}|textebrut|sinon{<:astra:menu:>}|attribut_html)]"><ul class="menu">
+		<INCLURE{fond=inclure/hfb/menu-items}{source=#GET{menu}|table_valeur{source}}{id_source=#GET{menu}|table_valeur{id}}{niveaux=2}{widget=oui}{id_rubrique}{id_secteur}{id_article}{type_page} />
+	</ul></nav>
+</[(#ENV{balise,aside})]>
diff --git a/lang/astra_en.php b/lang/astra_en.php
index c390ab5..c3cd138 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -73,6 +73,7 @@ return [
 	'hfb_appareil_tablet' => 'Tablet',
 	'hfb_bloc_articles_recents' => 'Recent posts',
 	'hfb_bloc_forums_recents' => 'Recent comments',
+	'hfb_bloc_menu' => 'Menu',
 	'hfb_bloc_mots_cles' => 'Keywords',
 	'hfb_bloc_recherche' => 'Search',
 	'hfb_bloc_sous_rubriques' => 'Sections',
@@ -125,6 +126,7 @@ return [
 	'hfb_menu_mobile' => 'Mobile menu',
 	'hfb_menu_mobile_dropdown' => 'Dropdown below the header',
 	'hfb_menu_mobile_off_canvas' => 'Off-canvas panel',
+	'hfb_menu_widget' => '“Menu” block source',
 	'hfb_non_affiche' => 'Not displayed',
 	'hfb_nouvel_onglet' => 'Open in a new tab',
 	'hfb_ordre' => 'Order',
@@ -180,6 +182,7 @@ return [
 	'laisser_commentaire' => 'Leave a Comment',
 
 	// M
+	'menu' => 'Menu',
 	'menu_principal' => 'Main Menu',
 	'mots_cles' => 'Tags',
 
@@ -209,6 +212,7 @@ return [
 	'pagination' => 'Post pagination',
 	'panneau_fermer' => 'Close menu',
 	'par' => 'By',
+	'permutateur_menu' => 'Menu Toggle',
 	'precedent' => 'Previous',
 	'propulse_par' => 'Powered by',
 
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index e008891..aff56fa 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -73,6 +73,7 @@ return [
 	'hfb_appareil_tablet' => 'Tablette',
 	'hfb_bloc_articles_recents' => 'Articles récents',
 	'hfb_bloc_forums_recents' => 'Commentaires récents',
+	'hfb_bloc_menu' => 'Menu',
 	'hfb_bloc_mots_cles' => 'Mots-clés',
 	'hfb_bloc_recherche' => 'Recherche',
 	'hfb_bloc_sous_rubriques' => 'Rubriques',
@@ -125,6 +126,7 @@ return [
 	'hfb_menu_mobile' => 'Menu mobile',
 	'hfb_menu_mobile_dropdown' => 'Menu déroulant sous l’en-tête',
 	'hfb_menu_mobile_off_canvas' => 'Panneau latéral',
+	'hfb_menu_widget' => 'source du bloc « Menu »',
 	'hfb_non_affiche' => 'Non affiché',
 	'hfb_nouvel_onglet' => 'Ouvrir dans un nouvel onglet',
 	'hfb_ordre' => 'Ordre',
@@ -180,6 +182,7 @@ return [
 	'laisser_commentaire' => 'Laisser un commentaire',
 
 	// M
+	'menu' => 'Menu',
 	'menu_principal' => 'Menu principal',
 	'mots_cles' => 'Mots-clés',
 
@@ -209,6 +212,7 @@ return [
 	'pagination' => 'Pagination des articles',
 	'panneau_fermer' => 'Fermer le menu',
 	'par' => 'Par',
+	'permutateur_menu' => 'Permutateur de Menu',
 	'precedent' => 'Précédent',
 	'propulse_par' => 'Propulsé par',
 
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (81 tests, 1066 assertions)`, intégration `OK (76 tests, 259 assertions)`

```bash
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R13-pied-widgets-menus
php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R0-defaut R3-pied-colonnes R13-pied-widgets-menus
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R0-defaut R3-pied-colonnes R13-pied-widgets-menus
```

Attendu : R0 et R3 identiques ; R13 : seules les deux différences de nommage de l'écart 5 (`nav_menu-2` / `nav_menu-pied-2`, `menu-principal-container` / `menu-racine-container`, idem pour le widget 3) ; toutes les vues sous le seuil (pied mobile de R13 vers 0,7 % : décalage fractionnaire de la position du pied dans la page, géométrie relative identique).

- [ ] **Étape 5 : commit**

```bash
git add inclure/widgets/menu.html inc/astra_hfb.php inc/astra_hfb_saisie.php inclure/hfb/menu-items.html inclure/hfb/widget.html formulaires/inc-astra-hfb-widget.html lang/astra_fr.php lang/astra_en.php tests/unit/HfbTest.php tests/unit/HfbSaisieTest.php tests/integration/HfbRenduTest.php tests/comparaison/configurations.php tests/references/wp/commun.php tests/references/wp/R13-pied-widgets-menus.php tests/references/wp/R13-pied-widgets-menus.json
git commit -m "Builder : bloc menu dans les widgets"
```

### Tâche 4 : réseau Google Reviews

Spec § 4, avec l'écart 2. `astra_hfb_reseaux()` gagne `google_reviews` (« Google Reviews », `#dc4e41`, couleur du sélecteur d'Astra) ; le SVG est repris tel quel de `assets/svg/svgs.json` d'Astra 4.11.0.

**Provenance établie :** le tracé est le glyphe `google` (U+F1A0) de la fonte Font Awesome 4.7.0, par Dave Gandy, converti par Astra (coordonnées divisées par 64, axe vertical retourné). Vérification : chaque point du tracé d'Astra tombe sur le contour du glyphe de `fontawesome-webfont.svg` (4.7.0) à 0,0015 unité près. Licence de la fonte : SIL OFL 1.1 (« Font: SIL OFL 1.1, CSS: MIT License », en-tête de `font-awesome.css` 4.7.0 ; métadonnées de la fonte : « Copyright Dave Gandy 2016 »). Le logo représente la marque Google ; les règles de marque ne sont pas une licence du dessin.

**Fichiers :**
- Modifier : `inc/astra_hfb.php`, `inc/astra_icones.php`, `inclure/hfb/social-icons.html`, `CREDITS.md`, `licenses/README.md`
- Créer : `licenses/OFL-1.1-fontawesome-4.7.0.txt`
- Modifier : `tests/integration/HfbRenduTest.php`, `tests/comparaison/configurations.php`
- Créer : `tests/references/wp/R15-sociaux-google.php`, `tests/references/wp/R15-sociaux-google.json`

**Interfaces :**
- Consomme : `astra_hfb_reseaux()` et `astra_icones_sociales()` existants ; `HfbTest::testChaqueReseauAUneIcone` couvre la présence du SVG.

- [ ] **Étape 1 : écrire le test et la référence**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 0cf1ea6..11edabb 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -114,6 +114,14 @@ function astra_configurations_reference(): array {
 				'widget-3' => ['blocs' => ['menu'], 'titre' => 'Nos services', 'menu' => ['source' => 'racine', 'id' => 0]],
 			]],
 		],
+		'R15-sociaux-google' => [
+			'pied' => ['below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]],
+			'rangees' => ['pied_below' => ['colonnes' => 2, 'repartition' => '2-equal']],
+			'elements' => ['pied' => ['social-icons-1' => ['reseaux' => [
+				['reseau' => 'facebook', 'url' => 'https://example.org/facebook'],
+				['reseau' => 'google_reviews', 'url' => 'https://example.org/avis'],
+			]]]],
+		],
 		'R4-pied-asymetrique' => [
 			'pied' => [
 				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['html-1', 'social-icons-1'], 'primary_3' => ['menu']],
diff --git a/tests/integration/HfbRenduTest.php b/tests/integration/HfbRenduTest.php
index 62f516d..ec85023 100644
--- a/tests/integration/HfbRenduTest.php
+++ b/tests/integration/HfbRenduTest.php
@@ -265,6 +265,13 @@ final class HfbRenduTest extends TestCase {
 		}
 	}
 
+	public function testIconeGoogleReviews(): void {
+		// Référence R15-sociaux-google : lien, étiquette, couleur de marque du sélecteur d'Astra et SVG.
+		$x = $this->pied(astra_configurations_reference()['R15-sociaux-google']);
+		$lien = '//' . self::classe('ast-footer-social-1-wrap') . '//a[contains(@class, "ast-google_reviews")]';
+		$this->assertSame(1, $this->compter($x, $lien . '[@href="https://example.org/avis"][@aria-label="Google Reviews"][contains(@style, "--color: #dc4e41")]/span/*[local-name()="svg"]'));
+	}
+
 	public function testMenuDEnTeteGardeSonBalisage(): void {
 		$x = $this->entete(self::configR1());
 		$this->assertGreaterThan(0, $this->compter($x, '//*[@id="ast-desktop-header"]//ul[contains(@class, "main-header-menu")]//li[contains(@class, "menu-item-has-children")]/button[@class="ast-menu-toggle"]'));
diff --git a/tests/references/wp/R15-sociaux-google.json b/tests/references/wp/R15-sociaux-google.json
new file mode 100644
index 0000000..e0c6712
--- /dev/null
+++ b/tests/references/wp/R15-sociaux-google.json
@@ -0,0 +1,25 @@
+{
+	"ordinateur": [
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"presents": [
+		".ast-footer-social-1-wrap .ast-google_reviews svg"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": ".ast-footer-social-1-wrap .ast-google_reviews svg",
+			"proprietes": [
+				"width",
+				"height",
+				"fill"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R15-sociaux-google.php b/tests/references/wp/R15-sociaux-google.php
new file mode 100644
index 0000000..8b6b275
--- /dev/null
+++ b/tests/references/wp/R15-sociaux-google.php
@@ -0,0 +1,23 @@
+<?php
+// Icônes sociales du pied avec le réseau Google Reviews, activé avec la couleur de marque du
+// sélecteur d'Astra, comme dans Garden Maintenance.
+require __DIR__ . '/commun.php';
+ref_etat_initial();
+ref_menu('Principal', 'primary');
+ref_menu('Principal', 'mobile_menu');
+$element = function (string $id, string $libelle, string $couleur, string $url) {
+	return ['id' => $id, 'enabled' => true, 'source' => 'icon', 'url' => $url, 'color' => $couleur, 'background' => 'transparent', 'icon' => $id, 'label' => $libelle];
+};
+ref_reglages([
+	'footer-desktop-items' => [
+		'above'   => ['above_1' => [], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
+		'primary' => ['primary_1' => [], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_5' => []],
+		'below'   => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
+	],
+	'hbb-footer-column' => '2',
+	'hbb-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
+	'footer-social-icons-1' => ['items' => [
+		$element('facebook', 'Facebook', '#557dbc', 'https://example.org/facebook'),
+		$element('google_reviews', 'Google Reviews', '#dc4e41', 'https://example.org/avis'),
+	]],
+]);
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : OK (81 tests, 1066 assertions) — `HfbTest::testChaqueReseauAUneIcone` vérifiera la tâche à l'étape 4 ;
- intégration : 1 échec (`HfbRenduTest::testIconeGoogleReviews`).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/CREDITS.md b/CREDITS.md
index 8dd7e9b..138cdfd 100644
--- a/CREDITS.md
+++ b/CREDITS.md
@@ -119,6 +119,21 @@ leur utilisation dans les inclusions SPIP constituent les adaptations du portage
 La licence OFL des fontes et la licence MIT du code Font Awesome ne sont pas celles
 des SVG : aucune fonte ni bibliothèque JavaScript Font Awesome n'est embarquée ici.
 
+### Font Awesome 4.7.0 : Google Reviews
+
+Le SVG `google_reviews` de `inc/astra_icones.php` est identique à l'entrée du même nom
+dans `assets/svg/svgs.json` d'Astra 4.11.0. Son tracé est le glyphe `google` (U+F1A0)
+de la fonte **Font Awesome 4.7.0**, par **Dave Gandy**, converti par Astra : coordonnées
+de la fonte (1792 unités par cadratin) divisées par 64 et retournées verticalement.
+Chaque point du tracé d'Astra tombe sur le contour du glyphe à 0,0015 unité près
+(relevé du 23 octobre 2026, sur `fontawesome-webfont.svg` de la version 4.7.0).
+
+**Icône : Font Awesome 4.7.0 par Dave Gandy, fonte sous
+[SIL OFL 1.1](https://openfontlicense.org/).** La notice et le texte de la licence
+sont reproduits dans [licenses/OFL-1.1-fontawesome-4.7.0.txt](licenses/OFL-1.1-fontawesome-4.7.0.txt).
+Seul ce tracé est repris, sans la fonte. Le logo représente la marque **Google** de Google LLC ;
+les règles relatives aux marques ne constituent pas une licence libre du dessin.
+
 ### Autres icônes reprises d'Astra
 
 Les SVG `twitter-x`, `bluesky` et `email` de `inc/astra_icones.php` sont eux aussi
diff --git a/inc/astra_hfb.php b/inc/astra_hfb.php
index 5678efe..f8715af 100644
--- a/inc/astra_hfb.php
+++ b/inc/astra_hfb.php
@@ -88,7 +88,8 @@ function astra_hfb_blocs(): array {
 
 /**
  * Réseaux sociaux proposés : libellé et couleur (couleurs du sélecteur d'Astra,
- * Mastodon absent d'Astra : couleur de la marque).
+ * Mastodon absent d'Astra : couleur de la marque). google_reviews : identifiant et couleur
+ * d'Astra (classe ast-google_reviews).
  */
 function astra_hfb_reseaux(): array {
 	return [
@@ -103,6 +104,7 @@ function astra_hfb_reseaux(): array {
 		'github' => ['GitHub', '#24292e'],
 		'rss' => ['RSS', '#f09124'],
 		'email' => ['E-mail', '#ea4335'],
+		'google_reviews' => ['Google Reviews', '#dc4e41'],
 	];
 }
 
diff --git a/inc/astra_icones.php b/inc/astra_icones.php
index 0a54104..07f488c 100644
--- a/inc/astra_icones.php
+++ b/inc/astra_icones.php
@@ -7,6 +7,8 @@
  * Huit tracés sont identifiés comme Font Awesome Free, par Fonticons, Inc.
  * (https://fontawesome.com), sous CC BY 4.0 (https://creativecommons.org/licenses/by/4.0/).
  * Cette attribution n'est pas établie pour twitter-x, bluesky et email.
+ * google_reviews est le glyphe « google » (U+F1A0) de Font Awesome 4.7.0, par Dave Gandy, sous
+ * SIL OFL 1.1, tel qu'Astra l'a converti (coordonnées divisées par 64).
  * Sources, correspondances et notices : CREDITS.md et licenses/.
  * Adaptation SPIP : sélection et regroupement dans un tableau PHP ; relevé du 2026-10-23.
  */
@@ -24,5 +26,6 @@ function astra_icones_sociales(): array {
 		'github' => '<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 496 512\'><path d=\'M165.9 397.4c0 2-2.3 3.6-5.2 3.6-3.3.3-5.6-1.3-5.6-3.6 0-2 2.3-3.6 5.2-3.6 3-.3 5.6 1.3 5.6 3.6zm-31.1-4.5c-.7 2 1.3 4.3 4.3 4.9 2.6 1 5.6 0 6.2-2s-1.3-4.3-4.3-5.2c-2.6-.7-5.5.3-6.2 2.3zm44.2-1.7c-2.9.7-4.9 2.6-4.6 4.9.3 2 2.9 3.3 5.9 2.6 2.9-.7 4.9-2.6 4.6-4.6-.3-1.9-3-3.2-5.9-2.9zM244.8 8C106.1 8 0 113.3 0 252c0 110.9 69.8 205.8 169.5 239.2 12.8 2.3 17.3-5.6 17.3-12.1 0-6.2-.3-40.4-.3-61.4 0 0-70 15-84.7-29.8 0 0-11.4-29.1-27.8-36.6 0 0-22.9-15.7 1.6-15.4 0 0 24.9 2 38.6 25.8 21.9 38.6 58.6 27.5 72.9 20.9 2.3-16 8.8-27.1 16-33.7-55.9-6.2-112.3-14.3-112.3-110.5 0-27.5 7.6-41.3 23.6-58.9-2.6-6.5-11.1-33.3 2.6-67.9 20.9-6.5 69 27 69 27 20-5.6 41.5-8.5 62.8-8.5s42.8 2.9 62.8 8.5c0 0 48.1-33.6 69-27 13.7 34.7 5.2 61.4 2.6 67.9 16 17.7 25.8 31.5 25.8 58.9 0 96.5-58.9 104.2-114.8 110.5 9.2 7.9 17 22.9 17 46.4 0 33.7-.3 75.4-.3 83.6 0 6.5 4.6 14.4 17.3 12.1C428.2 457.8 496 362.9 496 252 496 113.3 383.5 8 244.8 8zM97.2 352.9c-1.3 1-1 3.3.7 5.2 1.6 1.6 3.9 2.3 5.2 1 1.3-1 1-3.3-.7-5.2-1.6-1.6-3.9-2.3-5.2-1zm-10.8-8.1c-.7 1.3.3 2.9 2.3 3.9 1.6 1 3.6.7 4.3-.7.7-1.3-.3-2.9-2.3-3.9-2-.6-3.6-.3-4.3.7zm32.4 35.6c-1.6 1.3-1 4.3 1.3 6.2 2.3 2.3 5.2 2.6 6.5 1 1.3-1.3.7-4.3-1.3-6.2-2.2-2.3-5.2-2.6-6.5-1zm-11.4-14.7c-1.6 1-1.6 3.6 0 5.9 1.6 2.3 4.3 3.3 5.6 2.3 1.6-1.3 1.6-3.9 0-6.2-1.4-2.3-4-3.3-5.6-2z\'></path></svg>',
 		'rss' => '<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 448 512\'><path d=\'M400 32H48C21.49 32 0 53.49 0 80v352c0 26.51 21.49 48 48 48h352c26.51 0 48-21.49 48-48V80c0-26.51-21.49-48-48-48zM112 416c-26.51 0-48-21.49-48-48s21.49-48 48-48 48 21.49 48 48-21.49 48-48 48zm157.533 0h-34.335c-6.011 0-11.051-4.636-11.442-10.634-5.214-80.05-69.243-143.92-149.123-149.123-5.997-.39-10.633-5.431-10.633-11.441v-34.335c0-6.535 5.468-11.777 11.994-11.425 110.546 5.974 198.997 94.536 204.964 204.964.352 6.526-4.89 11.994-11.425 11.994zm103.027 0h-34.334c-6.161 0-11.175-4.882-11.427-11.038-5.598-136.535-115.204-246.161-251.76-251.76C68.882 152.949 64 147.935 64 141.774V107.44c0-6.454 5.338-11.664 11.787-11.432 167.83 6.025 302.21 141.191 308.205 308.205.232 6.449-4.978 11.787-11.432 11.787z\'></path></svg>',
 		'email' => '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\'> <title id=\'email\'> Email </title> <path d=\'M0 3v18h24v-18h-24zm6.623 7.929l-4.623 5.712v-9.458l4.623 3.746zm-4.141-5.929h19.035l-9.517 7.713-9.518-7.713zm5.694 7.188l3.824 3.099 3.83-3.104 5.612 6.817h-18.779l5.513-6.812zm9.208-1.264l4.616-3.741v9.348l-4.616-5.607z\'/></svg>',
+		'google_reviews' => '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'28\' viewBox=\'0 0 24 28\'><title id=\'google_reviews\'> Google Reviews </title><path d=\'M12 12.281h11.328c.109.609.187 1.203.187 2C23.515 21.125 18.921 26 11.999 26c-6.641 0-12-5.359-12-12s5.359-12 12-12c3.234 0 5.953 1.188 8.047 3.141L16.78 8.282c-.891-.859-2.453-1.859-4.781-1.859-4.094 0-7.438 3.391-7.438 7.578s3.344 7.578 7.438 7.578c4.75 0 6.531-3.406 6.813-5.172h-6.813v-4.125z\'></path></svg>',
 	];
 }
diff --git a/inclure/hfb/social-icons.html b/inclure/hfb/social-icons.html
index 6cd37d4..7f6276e 100644
--- a/inclure/hfb/social-icons.html
+++ b/inclure/hfb/social-icons.html
@@ -1,6 +1,6 @@
 [(#REM)
 
-	Élément « social-icons-1 » : icônes des réseaux sociaux (Font Awesome Free, CC BY 4.0),
+	Élément « social-icons-1 » : icônes des réseaux sociaux (provenance : inc/astra_icones.php),
 	en-tête ou pied de page.
 
 	Paramètres : id, numero, groupe (entete | pied)
diff --git a/licenses/README.md b/licenses/README.md
index cf32612..b821e7a 100644
--- a/licenses/README.md
+++ b/licenses/README.md
@@ -14,6 +14,7 @@ dans [LICENSE](../LICENSE).
 | `GPL-2.0-or-later.txt` | [Texte SPDX GPL-2.0-or-later](https://github.com/spdx/license-list-data/blob/main/text/GPL-2.0-or-later.txt) ; [licence publiée par la FSF](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html) |
 | `OFL-1.1-raleway.txt` | `LICENSE` du paquet [`@fontsource/raleway@5.1.0`](https://cdn.jsdelivr.net/npm/@fontsource/raleway@5.1.0/LICENSE), copié sans modification : copyright de Raleway et texte de la SIL OFL 1.1 |
 | `OFL-1.1-poppins.txt` | `LICENSE` du paquet [`@fontsource/poppins@5.1.0`](https://cdn.jsdelivr.net/npm/@fontsource/poppins@5.1.0/LICENSE), copié sans modification : copyright de Poppins et texte de la SIL OFL 1.1 |
+| `OFL-1.1-fontawesome-4.7.0.txt` | Notice de Font Awesome 4.7.0 (en-tête de `font-awesome.css` et métadonnées de `fontawesome-webfont.svg` : « Copyright Dave Gandy 2016 ») suivie du texte de la SIL OFL 1.1, repris de `OFL-1.1-raleway.txt` ; seul le tracé `google_reviews` est concerné, la fonte n'est pas embarquée |
 | `CC-BY-4.0.txt` | [Texte SPDX CC-BY-4.0](https://github.com/spdx/license-list-data/blob/main/text/CC-BY-4.0.txt) ; [texte publié par Creative Commons](https://creativecommons.org/licenses/by/4.0/legalcode) |
 
 Les crédits d'Astra concernant des ressources non embarquées sont conservés dans
````

Puis créer la notice de licence à partir du texte de la SIL OFL 1.1 déjà présent dans le dépôt (lignes 8 et suivantes de `licenses/OFL-1.1-raleway.txt`) :

```bash
{ printf 'Font Awesome 4.7.0 by @davegandy - http://fontawesome.io - @fontawesome\nLicense - http://fontawesome.io/license (Font: SIL OFL 1.1, CSS: MIT License)\n\nCopyright Dave Gandy 2016. All rights reserved.\n\nThis Font Software is licensed under the SIL Open Font License, Version 1.1.\nThis license is copied below, and is also available with a FAQ at:\nhttp://scripts.sil.org/OFL\n\n\n'; sed -n '8,$p' licenses/OFL-1.1-raleway.txt; } > licenses/OFL-1.1-fontawesome-4.7.0.txt
sha256sum licenses/OFL-1.1-fontawesome-4.7.0.txt
```

Attendu : `26b625cda9e34c62759a9c7a5ac30588da4de8e311f0db9d58df8818793191b4  licenses/OFL-1.1-fontawesome-4.7.0.txt`.

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (81 tests, 1066 assertions)`, intégration `OK (77 tests, 260 assertions)`

Le SVG doit être identique à l'entrée d'Astra :

```bash
php -r 'require "inc/astra_icones.php"; $a = json_decode(file_get_contents($argv[1]), true)["google_reviews"]; echo astra_icones_sociales()["google_reviews"] === $a ? "identique\n" : "différent\n";' "$WP/wp-content/themes/astra/assets/svg/svgs.json"
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R15-sociaux-google
php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R15-sociaux-google
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R15-sociaux-google
```

Attendu : `identique` ; HTML identique ; toutes les vues sous le seuil ; styles du SVG (`width`, `height`, `fill`) égaux aux trois largeurs.

- [ ] **Étape 5 : commit**

```bash
git add inc/astra_hfb.php inc/astra_icones.php inclure/hfb/social-icons.html CREDITS.md licenses/README.md licenses/OFL-1.1-fontawesome-4.7.0.txt tests/integration/HfbRenduTest.php tests/comparaison/configurations.php tests/references/wp/R15-sociaux-google.php tests/references/wp/R15-sociaux-google.json
git commit -m "Icônes sociales : Google Reviews (Font Awesome 4.7.0, SIL OFL 1.1)"
```

### Tâche 5 : style du bouton du menu mobile

Spec § 5. Groupe de styles `menu-mobile` dans le registre (`inc/astra_styles.php`), sous les noms d'options d'Astra : `mobile-header-toggle-btn-style` (énumération `minimal`, `fill`, `outline`, défaut `minimal`), `-btn-color`, `-btn-bg-color`, `-border-color`, `-btn-border-size` (quatre côtés, défaut 1 px), `-border-radius-fields` (responsive), `-icon-size` (défaut 20). Module `inc/styles/menu_mobile.php` porté de `inc/builder/type/header/mobile-trigger/dynamic-css/dynamic.css.php` (règles ordinateur ; couleur de l'icône par défaut : blanc pour `fill`, couleur du thème sinon ; bordure par défaut : couleur du fond). Le bouton porte la classe `ast-mobile-menu-trigger-<style>` (filtre `astra_style_option`). Attendus WordPress du groupe et référence R14 avec les valeurs de Garden.

**Fichiers :**
- Créer : `inc/styles/menu_mobile.php`, `tests/references/styles/valeurs/menu-mobile.json`, `tests/references/styles/attendus/menu-mobile.css`, `tests/references/wp/R14-bouton-mobile.php`, `tests/references/wp/R14-bouton-mobile.json`
- Modifier : `inc/astra_styles.php`, `inc/astra_styles_saisie.php`, `astra_fonctions.php`, `inclure/hfb/mobile-trigger.html`, `lang/astra_fr.php`, `lang/astra_en.php`
- Modifier : `tests/unit/StylesTest.php`, `tests/unit/StylesAttendusTest.php`, `tests/integration/StylesFormulaireTest.php`, `tests/comparaison/configurations.php`, `tests/references/styles/capturer.sh`

**Interfaces :**
- Consomme : `astra_styles_pose()`, `astra_styles_element_place()`, `astra_styles_ou()`, `astra_styles_cote()` (`inc/astra_styles.php`).
- Produit : `astra_styles_menu_mobile(array $s, array $hfb): array` ; `ASTRA_STYLES_MODULES` gagne `menu_mobile` (dernier) ; `astra_style_option(string $option)` (`astra_fonctions.php`, valeur ordinateur normalisée ou défaut du registre).

`tests/references/styles/attendus/menu-mobile.css` est capturé sur WordPress, sans relancer tout `capturer.sh` (les autres attendus restent tels quels) :

```bash
cd tests/references/styles
GROUPE=menu-mobile wp --path="$WP" eval-file appliquer.php
curl -sf http://127.0.0.1:8401/ | php -r '$h = stream_get_contents(STDIN); preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s", $h, $m); echo $m[1];' > /tmp/menu-mobile.complet.css
python3 regles.py attendus/base.css /tmp/menu-mobile.complet.css > attendus/menu-mobile.css
wp --path="$WP" eval-file ../wp/R0-defaut.php
cd -
```

Le correctif de l'étape 1 contient le résultat attendu de cette capture.

- [ ] **Étape 1 : écrire les tests, les valeurs de Garden et les attendus**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 11edabb..9910792 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -159,6 +159,7 @@ function astra_configurations_reference(): array {
 		'R8-identite' => $styles(['identite'], ['meta' => ['slogan_site' => 'Un site propulsé par SPIP']]),
 		'R9-pied-couleurs' => $styles(['pied']),
 		'R10-sociaux' => $styles(['sociaux']),
+		'R14-bouton-mobile' => $styles(['menu-mobile']),
 		'R11-combinee' => $styles($tous, [
 			'astra' => ['police_texte' => "'Raleway', sans-serif", 'police_titres' => "'Poppins', sans-serif"],
 			'meta' => ['slogan_site' => 'Un site propulsé par SPIP'],
diff --git a/tests/integration/StylesFormulaireTest.php b/tests/integration/StylesFormulaireTest.php
index 637dda8..83333ef 100644
--- a/tests/integration/StylesFormulaireTest.php
+++ b/tests/integration/StylesFormulaireTest.php
@@ -44,7 +44,8 @@ final class StylesFormulaireTest extends TestCase {
 
 	public function testRenduDesChamps(): void {
 		$html = recuperer_fond('formulaires/configurer_astra_styles', ['editable' => true, 's' => ['button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'em']]]);
-		$this->assertSame(12, substr_count($html, '<fieldset'));
+		$this->assertSame(13, substr_count($html, '<fieldset'));
+		$this->assertMatchesRegularExpression('~name="s\[mobile-header-toggle-btn-style\]".*?<option value="outline">Contour</option>~s', $html);
 		$this->assertStringContainsString('name="s[header-menu1-color-responsive][desktop]"', $html);
 		$this->assertStringContainsString('name="s[button-radius-fields][desktop][top]" value="8"', $html);
 		$this->assertMatchesRegularExpression('~name="s\[button-radius-fields\]\[desktop-unit\]".*?<option value="em" selected="selected">~s', $html);
diff --git a/tests/references/styles/attendus/menu-mobile.css b/tests/references/styles/attendus/menu-mobile.css
new file mode 100644
index 0000000..4a26e93
--- /dev/null
+++ b/tests/references/styles/attendus/menu-mobile.css
@@ -0,0 +1 @@
+[data-section="section-header-mobile-trigger"] .ast-button-wrap .ast-mobile-menu-trigger-outline{background:transparent;color:var(--ast-global-color-0);border-top-width:2px;border-bottom-width:2px;border-right-width:2px;border-left-width:2px;border-style:solid;border-color:var(--ast-global-color-0);border-top-left-radius:100px;border-top-right-radius:100px;border-bottom-right-radius:100px;border-bottom-left-radius:100px;}
diff --git a/tests/references/styles/capturer.sh b/tests/references/styles/capturer.sh
index d01e397..90f45eb 100755
--- a/tests/references/styles/capturer.sh
+++ b/tests/references/styles/capturer.sh
@@ -10,12 +10,12 @@ URL=${URL:-http://127.0.0.1:8401}
 css() {
 	curl -sf "$URL/" | php -r '$h = stream_get_contents(STDIN); if (!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s", $h, $m)) { exit(1); } echo $m[1];'
 }
-for groupe in base menu bouton bouton-entete identite pied sociaux; do
+for groupe in base menu bouton bouton-entete identite pied sociaux menu-mobile; do
 	GROUPE=$groupe wp --path="$WP" eval-file "$ICI/appliquer.php"
 	css > "$ICI/attendus/.$groupe.complet.css"
 done
 mv "$ICI/attendus/.base.complet.css" "$ICI/attendus/base.css"
-for groupe in menu bouton bouton-entete identite pied sociaux; do
+for groupe in menu bouton bouton-entete identite pied sociaux menu-mobile; do
 	python3 "$ICI/regles.py" "$ICI/attendus/base.css" "$ICI/attendus/.$groupe.complet.css" > "$ICI/attendus/$groupe.css"
 	rm "$ICI/attendus/.$groupe.complet.css"
 	echo "$groupe : $(wc -l < "$ICI/attendus/$groupe.css") règles"
diff --git a/tests/references/styles/valeurs/menu-mobile.json b/tests/references/styles/valeurs/menu-mobile.json
new file mode 100644
index 0000000..ebd8742
--- /dev/null
+++ b/tests/references/styles/valeurs/menu-mobile.json
@@ -0,0 +1,35 @@
+{
+	"mobile-header-toggle-btn-style": "outline",
+	"mobile-header-toggle-icon-size": 20,
+	"mobile-header-toggle-btn-border-size": {
+		"top": "2",
+		"right": "2",
+		"bottom": "2",
+		"left": "2"
+	},
+	"mobile-header-toggle-border-radius-fields": {
+		"desktop": {
+			"top": 100,
+			"right": 100,
+			"bottom": 100,
+			"left": 100
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
+	"mobile-header-toggle-btn-color": "var(--ast-global-color-0)",
+	"mobile-header-toggle-border-color": "var(--ast-global-color-0)"
+}
diff --git a/tests/references/wp/R14-bouton-mobile.json b/tests/references/wp/R14-bouton-mobile.json
new file mode 100644
index 0000000..82e732d
--- /dev/null
+++ b/tests/references/wp/R14-bouton-mobile.json
@@ -0,0 +1,46 @@
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
+			"selecteur": "[data-section=\"section-header-mobile-trigger\"] .ast-button-wrap .menu-toggle",
+			"proprietes": [
+				"color",
+				"background-color",
+				"border-top-width",
+				"border-right-width",
+				"border-bottom-width",
+				"border-left-width",
+				"border-top-style",
+				"border-top-color",
+				"border-top-left-radius",
+				"border-bottom-right-radius",
+				"width",
+				"height"
+			]
+		},
+		{
+			"selecteur": "[data-section=\"section-header-mobile-trigger\"] .ast-button-wrap .menu-toggle .ast-mobile-svg",
+			"proprietes": [
+				"width",
+				"height",
+				"fill"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R14-bouton-mobile.php b/tests/references/wp/R14-bouton-mobile.php
new file mode 100644
index 0000000..4433e94
--- /dev/null
+++ b/tests/references/wp/R14-bouton-mobile.php
@@ -0,0 +1,4 @@
+<?php
+// Bouton du menu mobile en contour, valeurs de Garden Maintenance (styles du groupe menu-mobile).
+putenv('GROUPE=menu-mobile');
+require dirname(__DIR__) . '/styles/appliquer.php';
diff --git a/tests/unit/StylesAttendusTest.php b/tests/unit/StylesAttendusTest.php
index 1cf462f..aa3d8c1 100644
--- a/tests/unit/StylesAttendusTest.php
+++ b/tests/unit/StylesAttendusTest.php
@@ -51,7 +51,7 @@ final class StylesAttendusTest extends TestCase {
 	/** Groupes dont le module est écrit (ASTRA_STYLES_MODULES), par nom de fichier de référence. */
 	public static function groupes(): array {
 		$groupes = [];
-		foreach (['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'] as $g) {
+		foreach (['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux', 'menu-mobile'] as $g) {
 			if (in_array(str_replace('-', '_', $g), ASTRA_STYLES_MODULES, true)) {
 				$groupes[$g] = [$g];
 			}
diff --git a/tests/unit/StylesTest.php b/tests/unit/StylesTest.php
index 45b908d..dd7515c 100644
--- a/tests/unit/StylesTest.php
+++ b/tests/unit/StylesTest.php
@@ -28,7 +28,7 @@ final class StylesTest extends TestCase {
 
 	/** Aller-retour sans perte des structures d'origine d'Astra (valeurs du site.json de Garden). */
 	public function testStructuresDAstraConservees(): void {
-		foreach (['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'] as $groupe) {
+		foreach (['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux', 'menu-mobile'] as $groupe) {
 			$v = array_intersect_key(self::valeurs($groupe), astra_styles_registre());
 			$this->assertSame($v, astra_styles_normaliser($v), $groupe);
 		}
@@ -161,4 +161,21 @@ final class StylesTest extends TestCase {
 		$css = astra_styles_css(['button-color' => '#123456'], []);
 		$this->assertStringContainsString(',body .entry-content .wp-block-file .wp-block-file__button{text-decoration:none;}', $css);
 	}
+
+	public function testBoutonDuMenuMobileCommeAstra(): void {
+		$sel = '[data-section="section-header-mobile-trigger"] .ast-button-wrap ';
+		// Plein : icône blanche par défaut, fond de la couleur du thème.
+		$css = astra_styles_css(['mobile-header-toggle-btn-style' => 'fill'], []);
+		$this->assertStringContainsString($sel . '.mobile-menu-toggle-icon .ast-mobile-svg{width:20px;height:20px;fill:#ffffff;}', $css);
+		$this->assertStringContainsString($sel . '.ast-mobile-menu-trigger-fill{color:#ffffff;border:none;background:var(--ast-global-color-0);}', $css);
+		// Minimal (défaut) : seule la couleur change.
+		$css = astra_styles_css(['mobile-header-toggle-btn-color' => '#123456'], []);
+		$this->assertStringContainsString($sel . '.ast-mobile-menu-trigger-minimal{color:#123456;border:none;background:transparent;}', $css);
+		// Contour sans épaisseur enregistrée : 1 px, défaut d'Astra ; bordure de la couleur du fond.
+		$css = astra_styles_css(['mobile-header-toggle-btn-style' => 'outline', 'mobile-header-toggle-btn-bg-color' => '#abcdef'], []);
+		$this->assertStringContainsString('.ast-mobile-menu-trigger-outline{background:transparent;color:var(--ast-global-color-0);border-top-width:1px;border-bottom-width:1px;border-right-width:1px;border-left-width:1px;border-style:solid;border-color:#abcdef;}', $css);
+		// Sans bouton de menu placé : rien.
+		$this->assertSame('', astra_styles_css(['mobile-header-toggle-btn-style' => 'fill'], ['entete_mobile' => ['primary' => ['primary_right' => []]]]));
+		$this->assertSame([], astra_styles_normaliser(['mobile-header-toggle-btn-style' => 'plein']));
+	}
 }
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 1 échec (`StylesTest::testBoutonDuMenuMobileCommeAstra`) ; l'équivalence du groupe `menu-mobile` (`StylesAttendusTest`) n'est pas encore exécutée : elle ne porte que sur les modules écrits ;
- intégration : 1 échec (`StylesFormulaireTest::testRenduDesChamps`, 12 sections au lieu de 13).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/astra_fonctions.php b/astra_fonctions.php
index 31062cf..8c68c5f 100644
--- a/astra_fonctions.php
+++ b/astra_fonctions.php
@@ -329,6 +329,17 @@ function astra_body_class(string $type, $id = 0, $accueil = ''): string {
 	return implode(' ', $classes);
 }
 
+/**
+ * Valeur ordinateur normalisée d'une option des styles, sinon son défaut d'Astra.
+ * `#VAL{mobile-header-toggle-btn-style}|astra_style_option`
+ */
+function astra_style_option(string $option) {
+	include_spip('inc/config');
+	$v = astra_styles_normaliser([$option => lire_config('astra/styles/' . $option)])[$option] ?? '';
+	$v = astra_styles_ordi($v);
+	return $v === '' || $v === null ? astra_styles_registre()[$option]['defaut'] : $v;
+}
+
 /**
  * Titre ou slogan imprimé : visible sur au moins un appareil, comme astra_logo() d'Astra.
  * `#VAL{display-site-title-responsive}|astra_identite_visible`
diff --git a/inc/astra_styles.php b/inc/astra_styles.php
index 2d076db..c2676d4 100644
--- a/inc/astra_styles.php
+++ b/inc/astra_styles.php
@@ -118,6 +118,15 @@ function astra_styles_registre(): array {
 			"$b-social-1-color-type" => ['type' => 'enum', 'valeurs' => ['custom', 'official'], 'defaut' => 'custom'],
 		]);
 	}
+	$ajouter('menu-mobile', 'inc/builder/type/header/mobile-trigger/dynamic-css/dynamic.css.php', [
+		'mobile-header-toggle-btn-style' => ['type' => 'enum', 'valeurs' => ['minimal', 'fill', 'outline'], 'defaut' => 'minimal'],
+		'mobile-header-toggle-btn-color' => $couleur,
+		'mobile-header-toggle-btn-bg-color' => $couleur,
+		'mobile-header-toggle-border-color' => $couleur,
+		'mobile-header-toggle-btn-border-size' => $cotes + ['defaut' => ['top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1]],
+		'mobile-header-toggle-border-radius-fields' => $cotes_r,
+		'mobile-header-toggle-icon-size' => ['type' => 'nombre', 'defaut' => 20],
+	]);
 	return $r;
 }
 
@@ -427,7 +436,7 @@ function astra_styles_ecrire(array $regles): string {
 }
 
 /** Modules de groupe (inc/styles/<module>.php), dans l'ordre où Astra émet leur CSS. */
-const ASTRA_STYLES_MODULES = ['bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux'];
+const ASTRA_STYLES_MODULES = ['bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux', 'menu_mobile'];
 
 /** CSS des styles du Customizer (vide sans réglage), dans l'ordre d'émission d'Astra. */
 function astra_styles_css(array $styles, array $hfb): string {
diff --git a/inc/astra_styles_saisie.php b/inc/astra_styles_saisie.php
index d550d89..29221f3 100644
--- a/inc/astra_styles_saisie.php
+++ b/inc/astra_styles_saisie.php
@@ -25,6 +25,7 @@ function astra_styles_sections(): array {
 		'copyright' => '/copyright/',
 		'sociaux_entete' => '/^header-social-1-/',
 		'sociaux_pied' => '/^footer-social-1-/',
+		'menu_mobile' => '/^mobile-header-toggle-/',
 	];
 }
 
diff --git a/inc/styles/menu_mobile.php b/inc/styles/menu_mobile.php
new file mode 100644
index 0000000..c3c7899
--- /dev/null
+++ b/inc/styles/menu_mobile.php
@@ -0,0 +1,48 @@
+<?php
+/**
+ * Astra pour SPIP — bouton du menu mobile (inc/builder/type/header/mobile-trigger/dynamic-css/
+ * dynamic.css.php d'Astra 4.11.0, règles ordinateur).
+ *
+ * @license GPL-3.0-or-later
+ */
+
+function astra_styles_menu_mobile(array $s, array $hfb): array {
+	if (!astra_styles_pose($s, 'menu-mobile') || !astra_styles_element_place('mobile-trigger', $hfb, 'entete')) {
+		return [];
+	}
+	$sel = '[data-section="section-header-mobile-trigger"] .ast-button-wrap';
+	// theme-color d'Astra SPIP : première couleur de la palette.
+	$theme = 'var(--ast-global-color-0)';
+	$style = astra_styles_ou($s, 'mobile-header-toggle-btn-style', 'minimal');
+	$icone = astra_styles_ou($s, 'mobile-header-toggle-btn-color', $style === 'fill' ? '#ffffff' : $theme);
+	$fond = astra_styles_ou($s, 'mobile-header-toggle-btn-bg-color', $theme);
+	$taille = astra_styles_ou($s, 'mobile-header-toggle-icon-size', 20) . 'px';
+	$regles = [
+		['', "$sel .mobile-menu-toggle-icon .ast-mobile-svg", ['width' => $taille, 'height' => $taille, 'fill' => $icone]],
+		['', "$sel .mobile-menu-wrap .mobile-menu", ['color' => $icone]],
+	];
+	$rayons = $s['mobile-header-toggle-border-radius-fields'] ?? '';
+	$rayon = [
+		'border-top-left-radius' => astra_styles_cote($rayons, 'top'), 'border-top-right-radius' => astra_styles_cote($rayons, 'right'),
+		'border-bottom-right-radius' => astra_styles_cote($rayons, 'bottom'), 'border-bottom-left-radius' => astra_styles_cote($rayons, 'left'),
+	];
+	if ($style === 'minimal') {
+		$regles[] = ['', "$sel .ast-mobile-menu-trigger-minimal", ['color' => $icone, 'border' => 'none', 'background' => 'transparent']];
+	} elseif ($style === 'fill') {
+		$regles[] = ['', "$sel .ast-mobile-menu-trigger-fill", ['color' => $icone, 'border' => 'none', 'background' => $fond] + $rayon];
+	} else {
+		// Épaisseurs : défaut d'Astra (1 px) sans option ; une fois l'option enregistrée, 0 pour un côté absent.
+		$bordure = $s['mobile-header-toggle-btn-border-size'] ?? ['top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1];
+		$epaisseur = function (string $cote) use ($bordure) {
+			$v = $bordure[$cote] ?? 0;
+			return $v === '' ? '' : $v . 'px';
+		};
+		$regles[] = ['', "$sel .ast-mobile-menu-trigger-outline", [
+			'background' => 'transparent', 'color' => $icone,
+			'border-top-width' => $epaisseur('top'), 'border-bottom-width' => $epaisseur('bottom'),
+			'border-right-width' => $epaisseur('right'), 'border-left-width' => $epaisseur('left'),
+			'border-style' => 'solid', 'border-color' => astra_styles_ou($s, 'mobile-header-toggle-border-color', $fond),
+		] + $rayon];
+	}
+	return $regles;
+}
diff --git a/inclure/hfb/mobile-trigger.html b/inclure/hfb/mobile-trigger.html
index f0a2a9a..aec34fc 100644
--- a/inclure/hfb/mobile-trigger.html
+++ b/inclure/hfb/mobile-trigger.html
@@ -4,7 +4,7 @@
 
 ]<div class="ast-builder-layout-element ast-flex site-header-focus-item" data-section="section-header-mobile-trigger">
 	<div class="ast-button-wrap">
-		<button type="button" class="menu-toggle main-header-menu-toggle ast-mobile-menu-trigger-minimal" aria-expanded="false">
+		<button type="button" class="menu-toggle main-header-menu-toggle ast-mobile-menu-trigger-[(#VAL{mobile-header-toggle-btn-style}|astra_style_option)]" aria-expanded="false">
 			<span class="screen-reader-text"><:astra:menu_principal:></span>
 			<span class="mobile-menu-toggle-icon">
 				<span aria-hidden="true" class="ahfb-svg-iconset ast-inline-flex svg-baseline"><svg class="ast-mobile-svg ast-menu-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M3 13h18c0.552 0 1-0.448 1-1s-0.448-1-1-1h-18c-0.552 0-1 0.448-1 1s0.448 1 1 1zM3 7h18c0.552 0 1-0.448 1-1s-0.448-1-1-1h-18c-0.552 0-1 0.448-1 1s0.448 1 1 1zM3 19h18c0.552 0 1-0.448 1-1s-0.448-1-1-1h-18c-0.552 0-1 0.448-1 1s0.448 1 1 1z"></path></svg></span><span aria-hidden="true" class="ahfb-svg-iconset ast-inline-flex svg-baseline"><svg class="ast-mobile-svg ast-close-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M5.293 6.707l5.293 5.293-5.293 5.293c-0.391 0.391-0.391 1.024 0 1.414s1.024 0.391 1.414 0l5.293-5.293 5.293 5.293c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414l-5.293-5.293 5.293-5.293c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z"></path></svg></span>
diff --git a/lang/astra_en.php b/lang/astra_en.php
index c3cd138..0db5d5f 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -274,6 +274,13 @@ return [
 	'style_menu_h_color_responsive' => 'Link color on hover',
 	'style_menu_submenu_b_color' => 'Submenu border color',
 	'style_menu_submenu_border' => 'Submenu border width (px)',
+	'style_mobile_header_toggle_border_color' => 'Border color',
+	'style_mobile_header_toggle_border_radius_fields' => 'Corner radius',
+	'style_mobile_header_toggle_btn_bg_color' => 'Background color',
+	'style_mobile_header_toggle_btn_border_size' => 'Border width (px)',
+	'style_mobile_header_toggle_btn_color' => 'Icon color',
+	'style_mobile_header_toggle_btn_style' => 'Button style',
+	'style_mobile_header_toggle_icon_size' => 'Icon size (px)',
 	'style_social_color' => 'Icon color',
 	'style_social_color_type' => 'Icon colors',
 	'style_social_h_color' => 'Icon color on hover',
@@ -293,7 +300,10 @@ return [
 	'styles_defaut' => 'Astra setting',
 	'styles_droite' => 'right',
 	'styles_enum_custom' => 'Custom',
+	'styles_enum_fill' => 'Fill',
+	'styles_enum_minimal' => 'Minimal',
 	'styles_enum_official' => 'Brand colors',
+	'styles_enum_outline' => 'Outline',
 	'styles_erreurs_saisie' => 'Some values were refused: correct or clear them.',
 	'styles_espacement' => 'Letter spacing',
 	'styles_explication' => 'Astra Customizer settings, under the same names. An empty field keeps Astra’s setting.',
@@ -308,6 +318,7 @@ return [
 	'styles_section_identite' => 'Site identity',
 	'styles_section_menu_1' => 'Primary menu',
 	'styles_section_menu_2' => 'Secondary menu',
+	'styles_section_menu_mobile' => 'Mobile menu button',
 	'styles_section_sociaux_entete' => 'Header social icons',
 	'styles_section_sociaux_pied' => 'Footer social icons',
 	'styles_section_widget_1' => 'Footer widget 1',
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index aff56fa..d3b306a 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -274,6 +274,13 @@ return [
 	'style_menu_h_color_responsive' => 'Couleur des liens au survol',
 	'style_menu_submenu_b_color' => 'Couleur de la bordure des sous-menus',
 	'style_menu_submenu_border' => 'Épaisseur de la bordure des sous-menus (px)',
+	'style_mobile_header_toggle_border_color' => 'Couleur de la bordure',
+	'style_mobile_header_toggle_border_radius_fields' => 'Rayon des coins',
+	'style_mobile_header_toggle_btn_bg_color' => 'Couleur de fond',
+	'style_mobile_header_toggle_btn_border_size' => 'Épaisseur de la bordure (px)',
+	'style_mobile_header_toggle_btn_color' => 'Couleur de l’icône',
+	'style_mobile_header_toggle_btn_style' => 'Style du bouton',
+	'style_mobile_header_toggle_icon_size' => 'Taille de l’icône (px)',
 	'style_social_color' => 'Couleur des icônes',
 	'style_social_color_type' => 'Couleurs des icônes',
 	'style_social_h_color' => 'Couleur des icônes au survol',
@@ -293,7 +300,10 @@ return [
 	'styles_defaut' => 'Réglage d’Astra',
 	'styles_droite' => 'droite',
 	'styles_enum_custom' => 'Personnalisées',
+	'styles_enum_fill' => 'Plein',
+	'styles_enum_minimal' => 'Minimal',
 	'styles_enum_official' => 'Couleurs des marques',
+	'styles_enum_outline' => 'Contour',
 	'styles_erreurs_saisie' => 'Certaines valeurs sont refusées : corrigez-les ou videz-les.',
 	'styles_espacement' => 'Espacement des lettres',
 	'styles_explication' => 'Réglages du Customizer d’Astra, sous les mêmes noms. Un champ vide garde le réglage d’Astra.',
@@ -308,6 +318,7 @@ return [
 	'styles_section_identite' => 'Identité du site',
 	'styles_section_menu_1' => 'Menu principal',
 	'styles_section_menu_2' => 'Menu secondaire',
+	'styles_section_menu_mobile' => 'Bouton du menu mobile',
 	'styles_section_sociaux_entete' => 'Icônes sociales de l’en-tête',
 	'styles_section_sociaux_pied' => 'Icônes sociales du pied de page',
 	'styles_section_widget_1' => 'Widget 1 du pied de page',
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (83 tests, 1135 assertions)`, intégration `OK (77 tests, 261 assertions)`

```bash
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R0-defaut R14-bouton-mobile
php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R0-defaut R14-bouton-mobile
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R0-defaut R14-bouton-mobile
URL=http://127.0.0.1:8414 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire_styles.cjs
```

Attendu : HTML identique ; toutes les vues sous le seuil (en-tête mobile de R14 : 0,00 %, bouton rond à contour de 2 px) ; 15 valeurs de styles égales aux trois largeurs. Pour `formulaire_styles.cjs`, donner d'abord un slogan au site de test (`ecrire_meta('slogan_site', 'Un site propulsé par SPIP')`) et le retirer après : `Formulaire des styles : OK`.

- [ ] **Étape 5 : commit**

```bash
git add inc/styles/menu_mobile.php inc/astra_styles.php inc/astra_styles_saisie.php astra_fonctions.php inclure/hfb/mobile-trigger.html lang/astra_fr.php lang/astra_en.php tests/unit/StylesTest.php tests/unit/StylesAttendusTest.php tests/integration/StylesFormulaireTest.php tests/comparaison/configurations.php tests/references/styles/capturer.sh tests/references/styles/valeurs/menu-mobile.json tests/references/styles/attendus/menu-mobile.css tests/references/wp/R14-bouton-mobile.php tests/references/wp/R14-bouton-mobile.json
git commit -m "Styles : bouton du menu mobile (minimal, plein, contour)"
```

### Tâche 6 : version 1.3.0 et documentation

**Fichiers :**
- Modifier : `paquet.xml`, `README.md`, `tests/references/README.md`, `tests/comparaison/comparer_captures.sh`

- [ ] **Étape 1 : appliquer**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/README.md b/README.md
index 82560c4..e115685 100644
--- a/README.md
+++ b/README.md
@@ -49,11 +49,14 @@ En-tête et pied de page** :
 
 - en-tête sur ordinateur : zones gauche, centre et droite de chaque rangée ;
 - en-tête sur mobile : mêmes rangées, plus le panneau du menu (déroulant ou latéral) ;
-- pied de page : 1 à 6 colonnes par rangée, avec les répartitions d'Astra ;
+- pied de page : 1 à 6 colonnes par rangée, avec les répartitions d'Astra, choisies séparément
+  pour l'ordinateur, la tablette et le mobile (dont `3-firstrow` et `3-lastrow`) ;
 - éléments : identité du site, menus (rubriques racines, sous-rubriques d'une rubrique, articles
   d'un groupe de mots-clés, pages uniques si le plugin Pages est actif), textes libres, bouton,
-  recherche, icônes sociales, zones de widgets, compte, copyright ;
-- réglages des rangées : hauteur minimale, couleur de fond, bordure.
+  recherche, icônes sociales (dont Google Reviews), zones de widgets (texte et menu de deux
+  niveaux, comme le widget « Menu de navigation » de WordPress), compte, copyright ;
+- réglages des rangées : hauteur minimale, couleur de fond, bordure ; séparateur (épaisseur et
+  couleur) des rangées du pied.
 
 Sans réglage, l'en-tête et le pied de page sont ceux d'Astra par défaut : identité et menu
 principal, copyright centré. Le balisage produit est celui d'Astra ; `css/astra-hfb.css`
@@ -129,7 +132,7 @@ avec leur effet et la propriété vérifiée, dans
 - Polices du texte et des titres (pile CSS, ou Raleway et Poppins, fournies avec le thème et servies par le site), nombre d'articles par page.
 - Affichage des auteurs, des extraits, du lien « Accueil » et du bouton de retour en haut.
 - Neuvième couleur de la palette : hexadécimale ou `rgba()`, comme dans Astra.
-- **Couleurs et typographie** (Configuration › Squelettes) : réglages de style du Customizer d'Astra, sous leurs noms d'options (`header-menu1-color-responsive`, `button-radius-fields`…) — menus, bouton, bouton de l'en-tête, identité du site (couleurs, tailles, affichage du nom et du slogan, largeur du logo), widgets et copyright du pied, icônes sociales. Les valeurs d'un `site.json` de modèle Astra se reprennent telles quelles ; seule la valeur ordinateur est appliquée, les valeurs tablette et mobile sont conservées. Sans réglage, rien ne change.
+- **Couleurs et typographie** (Configuration › Squelettes) : réglages de style du Customizer d'Astra, sous leurs noms d'options (`header-menu1-color-responsive`, `button-radius-fields`…) — menus, bouton, bouton de l'en-tête, identité du site (couleurs, tailles, affichage du nom et du slogan, largeur du logo), widgets et copyright du pied, icônes sociales, bouton du menu mobile (minimal, plein ou contour). Les valeurs d'un `site.json` de modèle Astra se reprennent telles quelles ; seule la valeur ordinateur est appliquée, les valeurs tablette et mobile sont conservées. Sans réglage, rien ne change.
 - Texte du pied de page (raccourcis SPIP, marqueurs `[annee]` et `[site]`).
 
 ## Tests
@@ -156,11 +159,12 @@ références, un WordPress de test et le SPIP de test servi par un serveur web :
     URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire_styles.cjs
 
 - `comparer_html.php` compare l'ossature HTML de l'en-tête, du panneau mobile et du pied de
-  page à celle de WordPress, pour les six configurations de référence du builder (R0–R5) et
+  page à celle de WordPress, pour les configurations de référence du builder (R0–R5, R12, R13 et R15) et
   les trois de pages (P1–P3, qui comparent aussi le contenu et les classes du `body`) : balises, classes et
   identifiants des rangées, des zones et des éléments, et du premier niveau à l'intérieur de
   chaque élément. Les contenus, les formulaires et les SVG ne sont pas comparés : ce n'est pas
-  une identité complète du HTML ;
+  une identité complète du HTML (R13 : deux différences de nommage attendues, voir
+  `tests/references/README.md`) ;
 - `comparer_captures.sh` compare au pixel près l'en-tête, le pied de page et le menu mobile
   ouvert, sur ordinateur et sur mobile (seuil : 1 % de pixels différents, variable `SEUIL`) ; les
   images et leurs différences sont enregistrées dans `tests/comparaison/sorties/`. Une référence
@@ -169,13 +173,16 @@ références, un WordPress de test et le SPIP de test servi par un serveur web :
   d'un pixel laisserait sous le seuil des captures. Les références R6–R11 (styles du Customizer,
   valeurs de Garden Maintenance) comparent aussi les styles calculés au survol et au focus, à
   1280, 768 et 390 px, et vérifient que les polices locales sont chargées des deux côtés ;
+  R12–R15 couvrent les répartitions par appareil, les widgets menu, le bouton du menu mobile et
+  l'icône Google Reviews ;
 - `tests/e2e/formulaire_styles.cjs` vérifie le formulaire « Couleurs et typographie » de bout en
   bout (enregistrement, valeur refusée, site public) ;
 - `tests/e2e/formulaire.cjs` vérifie le formulaire de configuration de bout en bout ;
 - `tests/e2e/tableaux.cjs` vérifie que les tableaux SPIP du texte sont stylés, et pas ceux d'un
   bloc tableau importé (`figure.wp-block-table`) ;
 - `tests/e2e/repartitions.cjs` vérifie dans Chromium les quatorze répartitions de colonnes du
-  pied (proportions sur ordinateur et tablette, une colonne sur mobile) et la largeur des
+  pied (proportions sur ordinateur et tablette, une colonne sur mobile), les répartitions
+  tablette et mobile choisies à part (dont `3-firstrow` et `3-lastrow`) et la largeur des
   rangées du pied avec un conteneur de 1600 px. Ces réglages sortent des cinq configurations
   de référence.
 
diff --git a/paquet.xml b/paquet.xml
index 94ed5c4..21dae56 100644
--- a/paquet.xml
+++ b/paquet.xml
@@ -1,7 +1,7 @@
 <paquet
 	prefix="astra"
 	categorie="squelette"
-	version="1.2.0"
+	version="1.3.0"
 	schema="1.0.0"
 	etat="test"
 	compatibilite="[4.2.0;5.0.*]"
diff --git a/tests/comparaison/comparer_captures.sh b/tests/comparaison/comparer_captures.sh
index c9b0a70..53308a5 100755
--- a/tests/comparaison/comparer_captures.sh
+++ b/tests/comparaison/comparer_captures.sh
@@ -16,7 +16,7 @@ SORTIES=${SORTIES:-$ICI/sorties}
 : "${PW:?PW doit indiquer le dossier contenant node_modules/playwright et pixelmatch}"
 : "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
 CONFS=("$@")
-[ ${#CONFS[@]} -gt 0 ] || CONFS=(R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee)
+[ ${#CONFS[@]} -gt 0 ] || CONFS=(R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google)
 
 # Copyright identique à celui d'Astra dans WordPress.
 (cd "$RACINE" && $SPIP_CLI php:eval 'include_spip("inc/config"); ecrire_config("astra/texte_pied", "Copyright © [annee] [site] | Powered by [Thème WordPress Astra->https://wpastra.com]");' > /dev/null)
diff --git a/tests/references/README.md b/tests/references/README.md
index 151c8f0..50ec5f1 100644
--- a/tests/references/README.md
+++ b/tests/references/README.md
@@ -66,13 +66,17 @@ Vérifié le 2026-10-22 : HTML et CSS identiques.
 | R2-entete-centre | Menu à gauche, logo centré, compte et recherche à droite ; rangée basse : widget et texte 2 ; mobile : bouton à gauche, logo centré, compte ; panneau latéral avec menu et recherche |
 | R3-pied-colonnes | Pied : rangée haute 2 colonnes (texte, icônes), principale 4 widgets (`4-equal`), basse copyright et menu (`2-equal`) |
 | R4-pied-asymetrique | Pied : rangée principale `3-cwide` (widget ; texte et icônes ; menu vertical), basse `2-lheavy` (copyright, texte 2) |
-| R5-rangees | Disposition par défaut ; rangée principale de l'en-tête : hauteur 100 px, fond `#e8f5e9`, sans séparateur (`hb-header-main-sep` à 0) ; styles calculés comparés (`styles`). Hors des configurations lues par `extraire_css.py` |
+| R5-rangees | Disposition par défaut ; rangée principale de l'en-tête : hauteur 100 px, fond `#e8f5e9`, sans séparateur (`hb-header-main-sep` à 0) ; pied : séparateurs des trois rangées (haute 2 px `#c0392b`, principale sans séparateur, basse couleur de la palette) ; styles calculés comparés (`styles`). Hors des configurations lues par `extraire_css.py` |
 | R6-menu | Styles du Customizer (`tests/references/styles/appliquer.php`, groupe `menu`) : logo, menu et bouton dans l'en-tête, quatre widgets texte, copyright et icônes sociales dans le pied ; couleurs et sous-menus du menu principal, sur la page de la catégorie Jardinage (page courante) |
 | R7-bouton | Même placement ; bouton global (couleurs, survol, rayon, marges, police Poppins 500, taille, casse, espacement) |
 | R8-identite | Même placement ; nom du site et slogan (affiché), tailles et couleurs, largeur du logo |
 | R9-pied-couleurs | Même placement ; couleurs et tailles des quatre widgets et du copyright |
 | R10-sociaux | Même placement ; taille, espacement et couleurs des icônes sociales du pied |
 | R11-combinee | Toutes les options précédentes, police du texte Raleway et des titres Poppins (cascade de plusieurs réglages sur le même bouton et le même menu) |
+| R12-pied-appareils | Répartitions par appareil de Garden Maintenance : rangées haute (texte, icônes) et basse (copyright, menu) en `2-equal` sur ordinateur et tablette, principale (quatre widgets) en `4-equal` sur ordinateur et `full` sur tablette et mobile ; `grid-template-columns` comparé aux trois largeurs |
+| R13-pied-widgets-menus | Pied : widget texte et deux widgets « Menu de navigation » (menus Principal et Pied), comme les widgets 2 et 3 de Garden Maintenance |
+| R14-bouton-mobile | Placement de R6–R11 ; bouton du menu mobile en contour (groupe de styles `menu-mobile`, valeurs de Garden) |
+| R15-sociaux-google | Pied : copyright et icônes sociales Facebook et Google Reviews (couleur de marque `#dc4e41`) |
 | P1-page | Page « Notre jardin » (`/?pagename=notre-jardin`), menu de pages à deux niveaux (`ref_menu_pages()`) |
 | P2-accueil | Même menu, page « Notre jardin » en page d'accueil statique (`/`) |
 | P3-page-pleine | Page « Plein écran », titre désactivé, conteneur `full-width-container` |
@@ -109,6 +113,12 @@ les règles attendues et que ses autres règles sont des règles de `base.css` o
 neutralisations déclarées. Après une montée de version d'Astra, relancer `capturer.sh` et relire
 le diff des attendus.
 
+Les références R12–R15 ne sont pas lues par `extraire_css.py`. Pour R13, `comparer_html.php`
+signale deux différences de nommage attendues : l'identifiant de la section (`nav_menu-2`,
+numéro d'instance du widget WordPress ; `nav_menu-pied-2` dans SPIP) et la classe de la `nav`
+(`menu-<slug du menu>-container` ; `menu-<source>-container` dans SPIP, qui n'a pas de menus
+nommés).
+
 Relevé du 23 octobre 2026 (`comparer_captures.sh`, R0–R11) : toutes les vues sous le seuil,
 styles calculés identiques aux trois largeurs, polices chargées des deux côtés ; plus grand
 écart : 0,35 % (R11, pied sur ordinateur).
````

- [ ] **Étape 2 : vérifier l'ensemble**

```bash
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php
for f in $(git diff --name-only origin/main -- '*.php' ':!tests'); do php7.4 -l "$f"; done
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google P1-page P2-accueil P3-page-pleine
php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google P1-page P2-accueil P3-page-pleine
```

Attendu : unitaires `OK (83 tests, 1135 assertions)`, intégration `OK (77 tests, 261 assertions)` ; `No syntax errors detected` pour chaque fichier ; `comparer_html.php` (configurations du builder et des pages, sans les références des styles) : tout identique sauf R13 (écart 5) ; `comparer_captures.sh` : toutes les vues sous le seuil (plus grand écart vers 0,7 %, R13, pied sur mobile), styles calculés égaux.

- [ ] **Étape 3 : commit**

```bash
git add paquet.xml README.md tests/references/README.md tests/comparaison/comparer_captures.sh
git commit -m "Version 1.3.0 : compléments du builder"
```

## Effet attendu sur Garden

Une fois la version 1.3.0 publiée et posée par un lot de `garden-spip` (répartitions, séparateurs, widgets menu, réseau Google Reviews, valeurs `mobile-header-toggle-*` du `site.json`), les lignes correspondantes de « Écarts connus » du README de Garden sont à remesurer et à retirer. Ce lot fera l'objet de son propre plan.

# Marges des rangées du builder — plan de mise en œuvre

> **Pour les agents :** sous-compétence requise : superpowers:subagent-driven-development (recommandé) ou superpowers:executing-plans, tâche par tâche. Les étapes sont des cases à cocher (`- [ ]`).

**But :** prendre en charge les marges intérieures et extérieures des rangées du builder, du pied entier et de l'en-tête entier, comme Astra 4.11.0 sur les trois appareils, pour combler l'écart des marges du pied relevé sur Garden Maintenance ; version 1.5.0.

**Architecture :** quinze options entrent au registre des styles (groupe `marges-rangees`, type « côtés responsifs » existant, clés de registre `appareils` et `signe`) ; un module, `inc/styles/marges_rangees.php`, émet leurs règles dans l'ordre et sous les conditions d'Astra (rangée remplie, d'après `astra/hfb` normalisé) ; le formulaire « Couleurs et typographie » les saisit sur les trois appareils. Le rendu des rangées du pied suit la même condition qu'Astra (zones 1 à 5). Chaque réglage est prouvé contre WordPress Astra 4.11.0 : références R24, règles de marges comparées exactement, comparaison HTML, captures et styles calculés à 1280, 768 et 390 px.

**Pile technique :** PHP 7.4+ (plugin), SPIP 4.4, PHPUnit 11.5, WordPress de test avec Astra 4.11.0 et WP-CLI, Playwright (Chromium), Python 3.

**Spec :** `docs/superpowers/specs/2026-10-24-marges-rangees-design.md` (relue par Codex et Greptile, PR #15 ; décisions du plan reportées dans la spec par ce même commit).

## Contraintes globales

- Rien ne change sans réglage : sans option du groupe `marges-rangees`, R0–R23 et P1–P3 restent identiques (HTML, captures, styles calculés), hors rangée du pied remplie seulement en zone 6 (décision 2).
- Avec un réglage, l'effet est celui d'Astra 4.11.0 : mêmes sélecteurs, mêmes conditions d'émission, mêmes media queries (921 et 544 px), même ordre des propriétés ; fichier d'Astra cité en commentaire.
- Aucune chaîne libre dans le CSS : côtés par nombres bornés, unités par liste fermée (`px`, `em`, `%`).
- Valeurs d'un `site.json` reprises telles quelles (`section-primary-footer-builder-padding`, `section-below-footer-builder-padding` de Garden : normalisation sans perte).
- Le plugin reste compatible PHP 7.4 (vérifié : `php7.4 -l` et sortie identique à PHP 8.4).
- Langue du code, des commentaires et des messages : français, comme le dépôt.
- Dépôt partagé avec Codex : `git add` fichier par fichier ; ne jamais indexer `.gitignore`, `.ignore`, `tests/references/__pycache__/*.pyc`, `docs/inventaires/2026-10-23-preparation-publication.md` ; un `.pyc` suivi modifié par une commande se restaure avec `git checkout`.
- Messages de commit sans trailer (ni `Co-Authored-By`, ni `Claude-Session`).
- Tests d'intégration : `vendor/spip/spip/plugins/astra` doit pointer vers l'arbre de travail testé (dans un worktree : `cp -a <dépôt principal>/vendor vendor && ln -sfn "$PWD" vendor/spip/spip/plugins/astra`). Tests d'intégration et comparaisons écrivent dans la même base : ne pas les lancer en même temps.
- Commandes : `vendor/bin/phpunit --testsuite unit [--filter …]` ; `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php [--filter …]`. Comparaisons : `WP=… PW=… SPIP_CLI=… tests/references/capturer.sh <Rn…>`, puis `SPIP_CLI=… php tests/comparaison/comparer_html.php <racine SPIP> <URL> [Rn…]` et `WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh <racine SPIP> <URL> [Rn…]`. Attendus des styles : `WP=… tests/references/styles/capturer.sh`. Le SPIP de test est servi avec `PHP_CLI_SERVER_WORKERS=4` (ici sur le port 8414), le WordPress de test sur 8401 (partagé : pas deux captures en même temps).
- Les correctifs s'appliquent depuis la racine du dépôt avec `git apply` (enregistrer le bloc dans un fichier, puis `git apply chemin/du/fichier.patch`), dans l'ordre des tâches, sur `origin/main` (bfe65b8 : v1.4.0 et la spec).

## Décisions (écarts à la spec, reportés dans la spec par ce commit)

1. **Quinze options, et non quatorze** (§ « Périmètre » : 7 sections × 2). Astra a aussi `section-header-builder-layout-margin`, marge extérieure de l'en-tête entier (`.ast-hfb-header .site-header`), émise par `astra_primary_header_breakpoint_style()` (`inc/builder/type/header/primary-header/dynamic-css/dynamic.css.php`) après le test de la rangée principale : seulement si celle-ci est remplie sur ordinateur ou sur mobile. Elle figure (vide) dans le `site.json` de Garden ; elle entre au groupe avec la même structure. L'en-tête entier n'a pas de marge intérieure dans Astra.
2. **Rendu des rangées du pied aligné sur Astra.** La spec gardait le rendu d'Astra SPIP (« même si ses propres fonctions de rangées non vides en diffèrent »). L'essai de `R24-marges-rangees-vides` montre qu'Astra n'affiche pas une rangée du pied remplie seulement en zone 6 (`template-parts/footer/builder/footer-row.php` teste `is_footer_row_empty()`, zones 1 à 5), alors qu'Astra SPIP l'affichait : HTML différent. `astra_hfb_calculer_pied()` applique désormais la même condition (`astra_hfb_pied_rangee_remplie()`), partagée avec les marges ; le test `testSixiemeColonneRendue` place un élément en zone 1 en plus de la zone 6. Seule change une rangée de six colonnes remplie seulement en zone 6, qui disparaît comme dans WordPress.
3. **Appareil sans unité : rien n'est émis.** `astra_responsive_spacing()` exige la clé `<appareil>-unit` : sans elle, Astra n'émet aucun côté de cet appareil (mesuré : `R24-marges-rangees-vides`, tablette sans unité). Astra SPIP fait de même (`astra_styles_espacement()`). Le formulaire propose toujours une unité (`px` par défaut) : une valeur importée sans unité la reçoit au premier enregistrement par le formulaire, comme dans le Customizer.
4. **Preuve des conditions d'émission par les attendus.** Une règle émise pour une rangée vide ne se voit pas dans les captures (la rangée n'est pas affichée). `tests/references/styles/marges.py` extrait du CSS complet des références R24 les règles de marges (sélecteurs des rangées, du pied entier et de l'en-tête entier, déclarations de marges seulement) ; `StylesMargesTest` exige que le CSS d'Astra SPIP, avec le même placement, donne exactement ces règles, dans l'ordre. Astra n'applique pas `trim_css()` à ces règles : l'espace après la virgule des sélecteurs, sans effet, est retirée des attendus comme par `astra_styles_ecrire()`.
5. **Position dans la feuille.** Dans Astra, ces règles suivent le fond, la bordure et le séparateur de chaque rangée ; seules des règles `display` les suivent avec le même sélecteur (relevé dans le CSS de `R24-marges-toutes`). Le module `marges_rangees` vient en dernier dans `ASTRA_STYLES_MODULES`, et le bloc des styles suit les feuilles fixes : aucune règle d'Astra SPIP de même spécificité ne vise ces propriétés après lui. Les règles plus spécifiques d'Astra (ainsi `#masthead .ast-mobile-header-wrap .ast-below-header-bar{padding-left:20px}`, qui l'emporte sur la marge de la rangée basse sur mobile) s'appliquent de la même façon des deux côtés : les captures et les styles calculés de `R24-marges-toutes` le vérifient.
6. **Formulaire.** Section « Marges des rangées » (seizième), quinze options, chacune avec trois lignes (ordinateur, tablette, mobile) de quatre côtés et une unité ; `min="0"` pour les marges intérieures seulement. La clé de registre `appareils` généralise le traitement des alignements (`astra_styles_trois_appareils()`) : champs par appareil, fusion de la saisie des trois appareils et de leurs unités, première saisie gardée dès qu'un côté d'un appareil est renseigné (des unités seules ne sont pas une valeur).
7. **Bornes.** Marges intérieures de 0 à 1000 (règle `nombre` existante), marges extérieures de -1000 à 1000 (nouvelle règle `nombre-signe-1000`).
8. **Version 1.5.0** : nouveaux réglages ; le changement de rendu de la décision 2 ne touche qu'une configuration que WordPress n'affiche pas.

## Vérification préalable du plan

Le code de ce plan a été écrit et exécuté dans un worktree jetable de `origin/main` (bfe65b8,
v1.4.0 et la spec), puis réordonné en un commit par tâche ; chaque correctif ci-dessous est le diff
de ces commits, appliqué dans l'ordre sur bfe65b8 (l'arbre final est identique à celui de
l'essai) :

- comportements d'Astra relevés sur WordPress avant d'écrire le code : position des règles de
  marges dans le CSS (après le fond, la bordure et le séparateur de chaque rangée ; seules des
  règles `display` les suivent avec le même sélecteur) ; rangée haute de l'en-tête remplie sur
  mobile seulement : règles émises ; rangée principale du pied remplie seulement en zone 6 : ni
  rangée affichée, ni règle (décision 2) ; tablette sans unité : rien d'émis pour elle
  (décision 3) ; `section-header-builder-layout-margin` émise après la rangée principale
  (décision 1) ;
- étape 2 de chaque tâche : tests de la tâche appliqués sur l'état de la tâche précédente, échecs
  relevés tels qu'indiqués ;
- tests après la tâche 4 : unitaires 121 tests, 1700 assertions ; intégration 84 tests,
  295 assertions (avant : 112 et 83) ;
- mutations du module (conditions d'émission retirées ; zone 6 du pied comptée ; rangée de
  l'en-tête remplie sur mobile ignorée ; côté d'un appareil sans unité émis ; côtés dans l'ordre
  haut, droite, bas, gauche ; marge intérieure négative acceptée) : la suite unitaire échoue dans
  chaque cas ;
- `php7.4 -l` sur les fichiers PHP modifiés hors `tests/` : aucune erreur ; CSS produit pour les
  trois références R24 identique octet pour octet sous PHP 7.4 et 8.4 ;
- `styles/capturer.sh` complet : aucun attendu existant modifié ; trois attendus nouveaux (4, 19
  et 11 règles de marges) ;
- `capturer.sh` sur les 35 références (R0–R24 avec leurs variantes, P1–P3), puis
  `comparer_html.php` (défaut, puis R12–R24) : tout identique sauf les deux nommages de R13 ;
- `comparer_captures.sh` sur ces 35 références : 273 vues et jeux de styles sous le seuil (plus
  grand écart 0,73 %, R13, pied sur mobile), styles calculés égaux aux trois largeurs ; R24 : 10,
  76 et 76 valeurs ;
- `tests/e2e/formulaire_styles.cjs` (avec un slogan) : OK ; section « Marges des rangées » du
  formulaire regardée en capture : trois lignes par option, côtés et unité sur une ligne.

## Fichiers

| Fichier | Rôle | Tâche |
|---|---|---|
| `inc/astra_hfb.php` | `astra_hfb_pied_rangee_remplie()`, condition d'affichage des rangées du pied | 1 |
| `inc/astra_styles.php` | Groupe `marges-rangees` (quinze options), côtés signés, module dans `ASTRA_STYLES_MODULES` | 2 |
| `inc/styles/marges_rangees.php` | Sections, sélecteurs et conditions d'émission ; `astra_styles_espacement()` | 2 |
| `inc/astra_styles_saisie.php` | Section du formulaire (2) ; champs, fusion et première saisie sur trois appareils (3) | 2, 3 |
| `formulaires/configurer_astra_styles.html` | Trois lignes de côtés et d'unité par option | 3 |
| `lang/astra_{fr,en}.php` | Libellés des quinze options et de la section | 2 |
| `tests/references/styles/marges.py`, `capturer.sh`, `valeurs/marges-*.json`, `attendus/marges-*.css` | Extraction et attendus des règles de marges | 2 |
| `tests/references/wp/R24-*.{php,json}`, `tests/comparaison/configurations.php`, `comparer_captures.sh` | Références WordPress, configurations SPIP, liste par défaut | 2 |
| `tests/unit/HfbTest.php`, `StylesMargesTest.php`, `StylesSaisieTest.php`, `tests/integration/StylesFormulaireTest.php` | Tests | 1, 2, 3 |
| `paquet.xml`, `README.md`, `CREDITS.md`, `tests/references/README.md` | Version, documentation, provenance | 4 |

---

### Tâche 1 : rangées du pied affichées comme dans Astra

Décision 2. Une rangée du pied n'est affichée que si une de ses zones 1 à 5 a un élément, comme `template-parts/footer/builder/footer-row.php` d'Astra (`is_footer_row_empty()`, qui ne regarde pas la zone 6). La fonction sert aussi aux conditions d'émission des marges (tâche 2).

**Fichiers :**
- Modifier : `inc/astra_hfb.php`, `tests/unit/HfbTest.php`

**Interfaces :**
- Produit : `astra_hfb_pied_rangee_remplie(array $pied, string $rangee): bool` (`inc/astra_hfb.php`) ; `$pied` : partie `pied` d'`astra_hfb_normaliser()`, `$rangee` : `above`, `primary` ou `below`.

- [ ] **Étape 1 : écrire le test**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/unit/HfbTest.php b/tests/unit/HfbTest.php
index e3bcbc5..2def19b 100644
--- a/tests/unit/HfbTest.php
+++ b/tests/unit/HfbTest.php
@@ -198,7 +198,7 @@ final class HfbTest extends TestCase {
 
 	public function testSixiemeColonneRendue(): void {
 		$c = astra_hfb_normaliser([
-			'pied' => ['primary' => ['primary_6' => ['widget-2']]],
+			'pied' => ['primary' => ['primary_1' => ['widget-1'], 'primary_6' => ['widget-2']]],
 			'rangees' => ['pied_primary' => ['colonnes' => 6, 'repartition' => '6-equal']],
 		]);
 		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
@@ -206,6 +206,18 @@ final class HfbTest extends TestCase {
 		$this->assertSame(['widget-2'], self::ids($rangees[0]['colonnes'][6]));
 	}
 
+	public function testRangeeRemplieSeulementEnSixiemeColonneMasquee(): void {
+		// Astra n'affiche une rangée du pied que si une de ses zones 1 à 5 est remplie
+		// (template-parts/footer/builder/footer-row.php, is_footer_row_empty()).
+		$c = astra_hfb_normaliser([
+			'pied' => ['primary' => ['primary_6' => ['widget-2']]],
+			'rangees' => ['pied_primary' => ['colonnes' => 6, 'repartition' => '6-equal']],
+		]);
+		$this->assertSame(['below'], array_column(astra_hfb_calculer_pied($c['pied'], $c['rangees']), 'rangee'));
+		$this->assertFalse(astra_hfb_pied_rangee_remplie($c['pied'], 'primary'));
+		$this->assertTrue(astra_hfb_pied_rangee_remplie($c['pied'], 'below'));
+	}
+
 	public function testRepartitionInvalideRemplaceeParFull(): void {
 		$c = astra_hfb_normaliser(['rangees' => ['pied_below' => ['repartition' => '" onload="x']]]);
 		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
````

- [ ] **Étape 2 : vérifier qu'il échoue**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 1 échec (`HfbTest::testRangeeRemplieSeulementEnSixiemeColonneMasquee`) ;
- intégration : OK (83 tests, 289 assertions).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/inc/astra_hfb.php b/inc/astra_hfb.php
index f8715af..2604d00 100644
--- a/inc/astra_hfb.php
+++ b/inc/astra_hfb.php
@@ -293,6 +293,20 @@ function astra_hfb_repartition_appareils($valeur): array {
 	];
 }
 
+/**
+ * Vrai si une des zones 1 à 5 d'une rangée du pied normalisé a un élément : condition d'affichage
+ * de la rangée et d'émission de son CSS dans Astra (Astra_Builder_Helper::is_footer_row_empty(),
+ * template-parts/footer/builder/footer-row.php), qui ne regarde pas la zone 6.
+ */
+function astra_hfb_pied_rangee_remplie(array $pied, string $rangee): bool {
+	for ($i = 1; $i <= 5; $i++) {
+		if (!empty($pied[$rangee][$rangee . '_' . $i])) {
+			return true;
+		}
+	}
+	return false;
+}
+
 /**
  * Rangées non vides d'un pied de page normalisé, avec leurs classes et leurs colonnes.
  */
@@ -301,14 +315,12 @@ function astra_hfb_calculer_pied(array $items, array $rangees): array {
 	foreach (ASTRA_HFB_RANGEES as $r) {
 		$reglages = $rangees['pied_' . $r] ?? [];
 		$nombre = astra_hfb_nombre_colonnes($reglages);
+		if (!astra_hfb_pied_rangee_remplie($items, $r)) {
+			continue;
+		}
 		$colonnes = [];
-		$occupee = false;
 		for ($i = 1; $i <= $nombre; $i++) {
 			$colonnes[$i] = astra_hfb_decrire($items[$r][$r . '_' . $i] ?? [], 'pied');
-			$occupee = $occupee || $colonnes[$i];
-		}
-		if (!$occupee) {
-			continue;
 		}
 		$repartition = astra_hfb_repartition_appareils($reglages['repartition'] ?? '');
 		$resultat[] = [
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (113 tests, 1551 assertions)`, intégration `OK (83 tests, 289 assertions)`

- [ ] **Étape 5 : commit**

```bash
git add inc/astra_hfb.php tests/unit/HfbTest.php
git commit -m "Rangées du pied : affichées comme dans Astra (zones 1 à 5)"
```

### Tâche 2 : émission des marges des rangées

Spec § « Comportement d'Astra », « Conditions d'émission », « Périmètre », « Émission » ; décisions 1, 3, 4, 5 et 7. Quinze options au registre (groupe `marges-rangees`, `$cotes_r` avec `appareils`, et `signe` pour les marges extérieures) ; le module émet, pour chaque section dont une option est enregistrée et dont la rangée remplit la condition d'Astra, les règles ordinateur, tablette (921 px) et mobile (544 px), marges intérieures puis extérieures, côtés dans l'ordre d'Astra (haut, bas, gauche, droite). Sections dans l'ordre d'Astra : rangée basse du pied, rangée haute du pied, pied entier, rangée principale du pied, rangée principale de l'en-tête, en-tête entier, rangée haute, rangée basse de l'en-tête. La section du formulaire et les libellés entrent ici (le registre exige une section et un libellé par option) ; les options y paraissent d'abord avec la seule ligne ordinateur, la tâche 3 ajoute les deux autres.

**Fichiers :**
- Créer : `inc/styles/marges_rangees.php`, `tests/unit/StylesMargesTest.php`, `tests/references/styles/marges.py`, `tests/references/styles/valeurs/marges-{pied,toutes,rangees-vides}.json`, `tests/references/styles/attendus/marges-{pied,toutes,rangees-vides}.css`, `tests/references/wp/R24-{pied-marges,marges-toutes,marges-rangees-vides}.{php,json}`
- Modifier : `inc/astra_styles.php`, `inc/astra_styles_saisie.php`, `lang/astra_fr.php`, `lang/astra_en.php`, `tests/references/styles/capturer.sh`, `tests/comparaison/configurations.php`, `tests/comparaison/comparer_captures.sh`, `tests/integration/StylesFormulaireTest.php`

**Interfaces :**
- Consomme : `astra_hfb_pied_rangee_remplie()` (tâche 1).
- Produit : `astra_styles_marges_rangees(array $s, array $hfb): array`, `astra_styles_entete_rangee_remplie(array $entete, string $rangee): bool`, `astra_styles_espacement($valeur, string $cote, string $appareil): string`, `ASTRA_STYLES_MARGES_RANGEES` (`inc/styles/marges_rangees.php`) ; clés de registre `appareils` (booléen : trois appareils saisis et appliqués) et `signe` (booléen : côtés négatifs acceptés) ; règle `nombre-signe-1000` d'`astra_styles_filtrer_objet()` ; section `marges_rangees` d'`astra_styles_sections()`.
- Configurations de comparaison : `R24-pied-marges`, `R24-marges-toutes`, `R24-marges-rangees-vides`.

- [ ] **Étape 1 : écrire les tests, les références et les attendus**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/comparaison/comparer_captures.sh b/tests/comparaison/comparer_captures.sh
index d5bd334..455a026 100755
--- a/tests/comparaison/comparer_captures.sh
+++ b/tests/comparaison/comparer_captures.sh
@@ -16,7 +16,7 @@ SORTIES=${SORTIES:-$ICI/sorties}
 : "${PW:?PW doit indiquer le dossier contenant node_modules/playwright et pixelmatch}"
 : "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
 CONFS=("$@")
-[ ${#CONFS[@]} -gt 0 ] || CONFS=(R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google R16-transparent-page R17-transparent-global R18-compat-sous-menu R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat)
+[ ${#CONFS[@]} -gt 0 ] || CONFS=(R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google R16-transparent-page R17-transparent-global R18-compat-sous-menu R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat R24-pied-marges R24-marges-toutes R24-marges-rangees-vides)
 
 # Copyright identique à celui d'Astra dans WordPress.
 (cd "$RACINE" && $SPIP_CLI php:eval 'include_spip("inc/config"); ecrire_config("astra/texte_pied", "Copyright © [annee] [site] | Powered by [Thème WordPress Astra->https://wpastra.com]");' > /dev/null)
diff --git a/tests/comparaison/configurations.php b/tests/comparaison/configurations.php
index 7c2bcb9..c689b86 100644
--- a/tests/comparaison/configurations.php
+++ b/tests/comparaison/configurations.php
@@ -194,6 +194,27 @@ function astra_configurations_reference(): array {
 		'R22-pied-alignements-ordinateur' => $styles(['alignements-ordinateur']),
 		'R22-pied-alignements-tablette' => $styles(['alignements-tablette']),
 		'R23-garden-compat' => $styles(['garden-compat'], $titres + ['compatibilite' => astra_configuration_styles(['garden-compat'])]),
+		'R24-pied-marges' => $styles(['marges-pied']),
+		// Rangées haute et basse de l'en-tête remplies comme dans R1, rangée haute du pied d'un texte.
+		'R24-marges-toutes' => array_replace_recursive($styles(['marges-toutes']), [
+			'entete_ordinateur' => ['above' => ['above_left' => ['html-1'], 'above_right' => ['social-icons-1']], 'below' => ['below_center' => ['menu-2']]],
+			'entete_mobile' => ['above' => ['above_center' => ['html-1']]],
+			'pied' => ['above' => ['above_1' => ['html-1']]],
+			'rangees' => ['pied_above' => ['colonnes' => 1, 'repartition' => 'full']],
+			'elements' => ['entete' => ['html-1' => ['texte' => 'Ouvert du lundi au vendredi']], 'pied' => ['html-1' => ['texte' => 'Un site éditorial propulsé par SPIP']]],
+		]),
+		// Rangée haute de l'en-tête remplie sur mobile seulement, rangée principale du pied remplie en
+		// zone 6 seulement.
+		'R24-marges-rangees-vides' => array_replace_recursive($styles(['marges-rangees-vides'], [
+			'pied' => [
+				'primary' => ['primary_6' => ['widget-1']],
+				'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']],
+			],
+		]), [
+			'entete_mobile' => ['above' => ['above_center' => ['html-1']]],
+			'rangees' => ['pied_primary' => ['colonnes' => 6, 'repartition' => ['desktop' => '6-equal', 'tablet' => 'full', 'mobile' => 'full']]],
+			'elements' => ['entete' => ['html-1' => ['texte' => 'Ouvert du lundi au vendredi']]],
+		]),
 		'P1-page' => [
 			'page' => 'notre_jardin',
 			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
diff --git a/tests/integration/StylesFormulaireTest.php b/tests/integration/StylesFormulaireTest.php
index 1e324e4..c5256a9 100644
--- a/tests/integration/StylesFormulaireTest.php
+++ b/tests/integration/StylesFormulaireTest.php
@@ -45,7 +45,7 @@ final class StylesFormulaireTest extends TestCase {
 
 	public function testRenduDesChamps(): void {
 		$html = recuperer_fond('formulaires/configurer_astra_styles', ['editable' => true, 's' => ['button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'em']]]);
-		$this->assertSame(15, substr_count($html, '<fieldset'));
+		$this->assertSame(16, substr_count($html, '<fieldset'));
 		$this->assertMatchesRegularExpression('~name="s\[mobile-header-toggle-btn-style\]".*?<option value="outline">Contour</option>~s', $html);
 		$this->assertStringContainsString('name="s[header-menu1-color-responsive][desktop]"', $html);
 		$this->assertStringContainsString('name="s[button-radius-fields][desktop][top]" value="8"', $html);
diff --git a/tests/references/styles/attendus/marges-pied.css b/tests/references/styles/attendus/marges-pied.css
new file mode 100644
index 0000000..14fb32a
--- /dev/null
+++ b/tests/references/styles/attendus/marges-pied.css
@@ -0,0 +1,4 @@
+[@media (max-width:921px)] .site-below-footer-wrap[data-section="section-below-footer-builder"]{padding-top:20px;padding-bottom:20px;padding-left:20px;padding-right:20px;}
+.site-primary-footer-wrap[data-section="section-primary-footer-builder"]{padding-top:80px;}
+[@media (max-width:921px)] .site-primary-footer-wrap[data-section="section-primary-footer-builder"]{padding-top:80px;padding-bottom:80px;padding-left:80px;padding-right:80px;}
+[@media (max-width:544px)] .site-primary-footer-wrap[data-section="section-primary-footer-builder"]{padding-top:60px;padding-bottom:30px;padding-left:30px;padding-right:30px;}
diff --git a/tests/references/styles/attendus/marges-rangees-vides.css b/tests/references/styles/attendus/marges-rangees-vides.css
new file mode 100644
index 0000000..c9368e4
--- /dev/null
+++ b/tests/references/styles/attendus/marges-rangees-vides.css
@@ -0,0 +1,11 @@
+[@media (max-width:921px)] .site-below-footer-wrap[data-section="section-below-footer-builder"]{padding-top:20px;padding-bottom:20px;padding-left:20px;padding-right:20px;}
+[@media (max-width:544px)] .site-below-footer-wrap[data-section="section-below-footer-builder"]{margin-top:5px;}
+.ast-hfb-header .site-footer{padding-top:40px;margin-top:30px;}
+[@media (max-width:921px)] .ast-hfb-header .site-footer{margin-top:2em;}
+.ast-desktop .ast-primary-header-bar.main-header-bar, .ast-header-break-point #masthead .ast-primary-header-bar.main-header-bar{padding-top:10px;padding-bottom:10px;margin-top:-5px;}
+[@media (max-width:544px)] .ast-desktop .ast-primary-header-bar.main-header-bar, .ast-header-break-point #masthead .ast-primary-header-bar.main-header-bar{padding-left:5%;padding-right:5%;margin-bottom:3px;}
+.ast-hfb-header .site-header{margin-bottom:20px;}
+[@media (max-width:544px)] .ast-hfb-header .site-header{margin-bottom:0px;}
+.ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar{padding-top:5px;padding-bottom:5px;padding-left:20px;padding-right:20px;margin-top:0px;margin-bottom:0px;}
+[@media (max-width:921px)] .ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar{padding-top:0.5em;padding-bottom:0.5em;padding-left:1em;padding-right:1em;}
+[@media (max-width:544px)] .ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar{padding-top:4px;}
diff --git a/tests/references/styles/attendus/marges-toutes.css b/tests/references/styles/attendus/marges-toutes.css
new file mode 100644
index 0000000..e3f8ec8
--- /dev/null
+++ b/tests/references/styles/attendus/marges-toutes.css
@@ -0,0 +1,19 @@
+[@media (max-width:921px)] .site-below-footer-wrap[data-section="section-below-footer-builder"]{padding-top:20px;padding-bottom:20px;padding-left:20px;padding-right:20px;}
+[@media (max-width:544px)] .site-below-footer-wrap[data-section="section-below-footer-builder"]{margin-top:5px;}
+.site-above-footer-wrap[data-section="section-above-footer-builder"]{padding-top:30px;padding-bottom:30px;margin-top:15px;}
+[@media (max-width:921px)] .site-above-footer-wrap[data-section="section-above-footer-builder"]{padding-top:20px;padding-bottom:20px;padding-left:20px;padding-right:20px;}
+[@media (max-width:544px)] .site-above-footer-wrap[data-section="section-above-footer-builder"]{padding-top:10px;}
+.ast-hfb-header .site-footer{padding-top:40px;margin-top:30px;}
+[@media (max-width:921px)] .ast-hfb-header .site-footer{margin-top:2em;}
+.site-primary-footer-wrap[data-section="section-primary-footer-builder"]{padding-top:80px;margin-bottom:-20px;}
+[@media (max-width:921px)] .site-primary-footer-wrap[data-section="section-primary-footer-builder"]{padding-top:80px;padding-bottom:80px;padding-left:80px;padding-right:80px;margin-top:2em;}
+[@media (max-width:544px)] .site-primary-footer-wrap[data-section="section-primary-footer-builder"]{padding-top:60px;padding-bottom:30px;padding-left:30px;padding-right:30px;}
+.ast-desktop .ast-primary-header-bar.main-header-bar, .ast-header-break-point #masthead .ast-primary-header-bar.main-header-bar{padding-top:10px;padding-bottom:10px;margin-top:-5px;}
+[@media (max-width:544px)] .ast-desktop .ast-primary-header-bar.main-header-bar, .ast-header-break-point #masthead .ast-primary-header-bar.main-header-bar{padding-left:5%;padding-right:5%;margin-bottom:3px;}
+.ast-hfb-header .site-header{margin-bottom:20px;}
+[@media (max-width:544px)] .ast-hfb-header .site-header{margin-bottom:0px;}
+.ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar{padding-top:5px;padding-bottom:5px;padding-left:20px;padding-right:20px;margin-top:0px;margin-bottom:0px;}
+[@media (max-width:921px)] .ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar{padding-top:0.5em;padding-bottom:0.5em;padding-left:1em;padding-right:1em;}
+[@media (max-width:544px)] .ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar{padding-top:4px;}
+.ast-below-header-bar.ast-below-header, .ast-header-break-point .ast-below-header-bar.ast-below-header{padding-top:12px;padding-bottom:12px;padding-left:30px;padding-right:30px;margin-top:10px;margin-bottom:-10px;}
+[@media (max-width:544px)] .ast-below-header-bar.ast-below-header, .ast-header-break-point .ast-below-header-bar.ast-below-header{padding-top:6px;padding-bottom:6px;padding-left:10px;padding-right:10px;}
diff --git a/tests/references/styles/capturer.sh b/tests/references/styles/capturer.sh
index 14dafc2..f8fc39b 100755
--- a/tests/references/styles/capturer.sh
+++ b/tests/references/styles/capturer.sh
@@ -3,7 +3,8 @@
 # Usage : WP=<racine WordPress> [URL=http://127.0.0.1:8401] tests/references/styles/capturer.sh
 # Écrit attendus/base.css (sans option) et attendus/<groupe>.css (règles ajoutées ou modifiées
 # par les options du groupe, par rapport à base.css ; pour les graisses des titres, règles de
-# graisse du CSS complet), puis remet le WordPress en état R0.
+# graisse du CSS complet ; pour les marges des rangées, règles de marges du CSS complet des
+# références R24), puis remet le WordPress en état R0.
 set -euo pipefail
 ICI=$(cd "$(dirname "$0")" && pwd)
 URL=${URL:-http://127.0.0.1:8401}
@@ -29,4 +30,13 @@ for groupe in titres titres-drapeau drapeau-polices titres-vide titres-vide-drap
 	rm "$ICI/attendus/.$groupe.complet.css"
 	echo "$groupe : $(wc -l < "$ICI/attendus/$groupe.css") règles de graisse"
 done
+# Marges des rangées : références R24 (placement propre à chacune), règles de marges du CSS complet
+# (marges.py), comparées dans l'ordre.
+for valeurs in marges-pied:R24-pied-marges marges-toutes:R24-marges-toutes marges-rangees-vides:R24-marges-rangees-vides; do
+	wp --path="$WP" eval-file "$ICI/../wp/${valeurs#*:}.php"
+	css > "$ICI/attendus/.${valeurs%%:*}.complet.css"
+	python3 "$ICI/marges.py" "$ICI/attendus/.${valeurs%%:*}.complet.css" > "$ICI/attendus/${valeurs%%:*}.css"
+	rm "$ICI/attendus/.${valeurs%%:*}.complet.css"
+	echo "${valeurs%%:*} : $(wc -l < "$ICI/attendus/${valeurs%%:*}.css") règles de marges"
+done
 wp --path="$WP" eval-file "$ICI/../wp/R0-defaut.php"
diff --git a/tests/references/styles/marges.py b/tests/references/styles/marges.py
new file mode 100644
index 0000000..b05bfe1
--- /dev/null
+++ b/tests/references/styles/marges.py
@@ -0,0 +1,39 @@
+"""Règles des marges des rangées d'un CSS complet de WordPress, dans l'ordre d'Astra.
+
+Usage : python3 marges.py complet.css  → une règle par ligne, au format de regles.py.
+
+Garde les règles des sélecteurs de prepare_advanced_margin_padding_css() (rangées du builder,
+pied entier) et de section-header-builder-layout-margin (en-tête entier) qui ne déclarent que des
+marges : celles qu'Astra émet pour ces options. Les autres règles de ces sélecteurs (fond, bordure,
+affichage) relèvent des réglages des rangées.
+"""
+import re
+import sys
+
+from regles import regles
+
+SELECTEURS = {
+    '.site-below-footer-wrap[data-section="section-below-footer-builder"]',
+    '.site-above-footer-wrap[data-section="section-above-footer-builder"]',
+    '.ast-hfb-header .site-footer',
+    '.site-primary-footer-wrap[data-section="section-primary-footer-builder"]',
+    '.ast-desktop .ast-primary-header-bar.main-header-bar, .ast-header-break-point #masthead .ast-primary-header-bar.main-header-bar',
+    '.ast-hfb-header .site-header',
+    '.ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar',
+    '.ast-below-header-bar.ast-below-header, .ast-header-break-point .ast-below-header-bar.ast-below-header',
+}
+
+
+def marges(css):
+    out = []
+    for r in regles(css):
+        m = re.match(r'(?:\[@media [^\]]*\] )?(.*?)\{(.*)\}$', r, re.S)
+        proprietes = [d.split(':', 1)[0] for d in m.group(2).split(';') if d]
+        if m.group(1) in SELECTEURS and proprietes and all(re.match(r'(padding|margin)-(top|bottom|left|right)$', p) for p in proprietes):
+            out.append(r)
+    return out
+
+
+if __name__ == '__main__':
+    for r in marges(open(sys.argv[1], encoding='utf-8').read()):
+        print(r)
diff --git a/tests/references/styles/valeurs/marges-pied.json b/tests/references/styles/valeurs/marges-pied.json
new file mode 100644
index 0000000..7d55cd2
--- /dev/null
+++ b/tests/references/styles/valeurs/marges-pied.json
@@ -0,0 +1,48 @@
+{
+	"section-primary-footer-builder-padding": {
+		"desktop": {
+			"top": "80",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "80",
+			"right": "80",
+			"bottom": "80",
+			"left": "80"
+		},
+		"mobile": {
+			"top": "60",
+			"right": "30",
+			"bottom": "30",
+			"left": "30"
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-below-footer-builder-padding": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "20",
+			"right": "20",
+			"bottom": "20",
+			"left": "20"
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
diff --git a/tests/references/styles/valeurs/marges-rangees-vides.json b/tests/references/styles/valeurs/marges-rangees-vides.json
new file mode 100644
index 0000000..55f915f
--- /dev/null
+++ b/tests/references/styles/valeurs/marges-rangees-vides.json
@@ -0,0 +1,346 @@
+{
+	"section-above-header-builder-padding": {
+		"desktop": {
+			"top": "5",
+			"right": "20",
+			"bottom": "5",
+			"left": "20"
+		},
+		"tablet": {
+			"top": "0.5",
+			"right": "1",
+			"bottom": "0.5",
+			"left": "1"
+		},
+		"mobile": {
+			"top": "4",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "em",
+		"mobile-unit": "px"
+	},
+	"section-above-header-builder-margin": {
+		"desktop": {
+			"top": "0",
+			"right": "",
+			"bottom": "0",
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
+	"section-primary-header-builder-padding": {
+		"desktop": {
+			"top": "10",
+			"right": "",
+			"bottom": "10",
+			"left": ""
+		},
+		"tablet": {
+			"top": "7",
+			"right": "7",
+			"bottom": "7",
+			"left": "7"
+		},
+		"mobile": {
+			"top": "",
+			"right": "5",
+			"bottom": "",
+			"left": "5"
+		},
+		"desktop-unit": "px",
+		"mobile-unit": "%"
+	},
+	"section-primary-header-builder-margin": {
+		"desktop": {
+			"top": "-5",
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
+			"bottom": "3",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-below-header-builder-padding": {
+		"desktop": {
+			"top": "12",
+			"right": "30",
+			"bottom": "12",
+			"left": "30"
+		},
+		"tablet": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"mobile": {
+			"top": "6",
+			"right": "10",
+			"bottom": "6",
+			"left": "10"
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-below-header-builder-margin": {
+		"desktop": {
+			"top": "10",
+			"right": "",
+			"bottom": "-10",
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
+	"section-header-builder-layout-margin": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "20",
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
+			"bottom": "0",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-above-footer-builder-padding": {
+		"desktop": {
+			"top": "30",
+			"right": "",
+			"bottom": "30",
+			"left": ""
+		},
+		"tablet": {
+			"top": "20",
+			"right": "20",
+			"bottom": "20",
+			"left": "20"
+		},
+		"mobile": {
+			"top": "10",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-above-footer-builder-margin": {
+		"desktop": {
+			"top": "15",
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
+	"section-primary-footer-builder-padding": {
+		"desktop": {
+			"top": "80",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "80",
+			"right": "80",
+			"bottom": "80",
+			"left": "80"
+		},
+		"mobile": {
+			"top": "60",
+			"right": "30",
+			"bottom": "30",
+			"left": "30"
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-primary-footer-builder-margin": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "-20",
+			"left": ""
+		},
+		"tablet": {
+			"top": "2",
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
+		"tablet-unit": "em",
+		"mobile-unit": "px"
+	},
+	"section-below-footer-builder-padding": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "20",
+			"right": "20",
+			"bottom": "20",
+			"left": "20"
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
+	"section-below-footer-builder-margin": {
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
+			"top": "5",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-footer-builder-layout-padding": {
+		"desktop": {
+			"top": "40",
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
+	"section-footer-builder-layout-margin": {
+		"desktop": {
+			"top": "30",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "2",
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
+		"tablet-unit": "em",
+		"mobile-unit": "px"
+	}
+}
diff --git a/tests/references/styles/valeurs/marges-toutes.json b/tests/references/styles/valeurs/marges-toutes.json
new file mode 100644
index 0000000..a19667c
--- /dev/null
+++ b/tests/references/styles/valeurs/marges-toutes.json
@@ -0,0 +1,347 @@
+{
+	"section-above-header-builder-padding": {
+		"desktop": {
+			"top": "5",
+			"right": "20",
+			"bottom": "5",
+			"left": "20"
+		},
+		"tablet": {
+			"top": "0.5",
+			"right": "1",
+			"bottom": "0.5",
+			"left": "1"
+		},
+		"mobile": {
+			"top": "4",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "em",
+		"mobile-unit": "px"
+	},
+	"section-above-header-builder-margin": {
+		"desktop": {
+			"top": "0",
+			"right": "",
+			"bottom": "0",
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
+	"section-primary-header-builder-padding": {
+		"desktop": {
+			"top": "10",
+			"right": "",
+			"bottom": "10",
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
+			"right": "5",
+			"bottom": "",
+			"left": "5"
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "%"
+	},
+	"section-primary-header-builder-margin": {
+		"desktop": {
+			"top": "-5",
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
+			"bottom": "3",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-below-header-builder-padding": {
+		"desktop": {
+			"top": "12",
+			"right": "30",
+			"bottom": "12",
+			"left": "30"
+		},
+		"tablet": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"mobile": {
+			"top": "6",
+			"right": "10",
+			"bottom": "6",
+			"left": "10"
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-below-header-builder-margin": {
+		"desktop": {
+			"top": "10",
+			"right": "",
+			"bottom": "-10",
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
+	"section-header-builder-layout-margin": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "20",
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
+			"bottom": "0",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-above-footer-builder-padding": {
+		"desktop": {
+			"top": "30",
+			"right": "",
+			"bottom": "30",
+			"left": ""
+		},
+		"tablet": {
+			"top": "20",
+			"right": "20",
+			"bottom": "20",
+			"left": "20"
+		},
+		"mobile": {
+			"top": "10",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-above-footer-builder-margin": {
+		"desktop": {
+			"top": "15",
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
+	"section-primary-footer-builder-padding": {
+		"desktop": {
+			"top": "80",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "80",
+			"right": "80",
+			"bottom": "80",
+			"left": "80"
+		},
+		"mobile": {
+			"top": "60",
+			"right": "30",
+			"bottom": "30",
+			"left": "30"
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-primary-footer-builder-margin": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "-20",
+			"left": ""
+		},
+		"tablet": {
+			"top": "2",
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
+		"tablet-unit": "em",
+		"mobile-unit": "px"
+	},
+	"section-below-footer-builder-padding": {
+		"desktop": {
+			"top": "",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "20",
+			"right": "20",
+			"bottom": "20",
+			"left": "20"
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
+	"section-below-footer-builder-margin": {
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
+			"top": "5",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"desktop-unit": "px",
+		"tablet-unit": "px",
+		"mobile-unit": "px"
+	},
+	"section-footer-builder-layout-padding": {
+		"desktop": {
+			"top": "40",
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
+	"section-footer-builder-layout-margin": {
+		"desktop": {
+			"top": "30",
+			"right": "",
+			"bottom": "",
+			"left": ""
+		},
+		"tablet": {
+			"top": "2",
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
+		"tablet-unit": "em",
+		"mobile-unit": "px"
+	}
+}
diff --git a/tests/references/wp/R24-marges-rangees-vides.json b/tests/references/wp/R24-marges-rangees-vides.json
new file mode 100644
index 0000000..7cd7c1f
--- /dev/null
+++ b/tests/references/wp/R24-marges-rangees-vides.json
@@ -0,0 +1,147 @@
+{
+	"ordinateur": [
+		".site-primary-header-wrap .ast-builder-menu-1",
+		".site-primary-header-wrap .ast-header-button-1",
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .ast-header-html-1",
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
+			"selecteur": "#ast-desktop-header .ast-above-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#ast-mobile-header .ast-above-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .ast-primary-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#ast-mobile-header .ast-primary-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .ast-below-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#masthead",
+			"proprietes": [
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": ".site-above-footer-wrap",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": ".site-primary-footer-wrap",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": ".site-below-footer-wrap",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#colophon",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R24-marges-rangees-vides.php b/tests/references/wp/R24-marges-rangees-vides.php
new file mode 100644
index 0000000..7dd9ebc
--- /dev/null
+++ b/tests/references/wp/R24-marges-rangees-vides.php
@@ -0,0 +1,25 @@
+<?php
+// Conditions d'émission : marges réglées pour les quinze options (valeurs :
+// styles/valeurs/marges-rangees-vides.json, avec une tablette sans unité), rangée haute de
+// l'en-tête remplie sur mobile seulement, rangée basse de l'en-tête vide, rangée haute du pied
+// vide, rangée principale du pied remplie en zone 6 seulement : aucune règle pour les rangées
+// vides (zone 6 comprise), règles pour la rangée remplie sur mobile, l'en-tête entier et le pied
+// entier.
+putenv('GROUPE=marges-rangees-vides');
+require dirname(__DIR__) . '/styles/appliquer.php';
+ref_reglages([
+	'header-mobile-items' => [
+		'popup' => ['popup_content' => ['mobile-menu']],
+		'above' => ['above_left' => [], 'above_center' => ['html-1'], 'above_right' => []],
+		'primary' => ['primary_left' => ['logo'], 'primary_center' => [], 'primary_right' => ['mobile-trigger']],
+		'below' => ['below_left' => [], 'below_center' => [], 'below_right' => []],
+	],
+	'footer-desktop-items' => [
+		'above' => ['above_1' => [], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
+		'primary' => ['primary_1' => [], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_5' => [], 'primary_6' => ['widget-1']],
+		'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
+	],
+	'hb-footer-column' => '6',
+	'hb-footer-layout' => ['desktop' => '6-equal', 'tablet' => 'full', 'mobile' => 'full'],
+	'header-html-1' => '<p>Ouvert du lundi au vendredi</p>',
+]);
diff --git a/tests/references/wp/R24-marges-toutes.json b/tests/references/wp/R24-marges-toutes.json
new file mode 100644
index 0000000..81b26a9
--- /dev/null
+++ b/tests/references/wp/R24-marges-toutes.json
@@ -0,0 +1,153 @@
+{
+	"ordinateur": [
+		".ast-above-header-wrap .ast-header-html-1",
+		".ast-above-header-wrap .ast-header-social-wrap",
+		".site-primary-header-wrap .ast-builder-menu-1",
+		".site-primary-header-wrap .ast-header-button-1",
+		".ast-below-header-wrap .ast-builder-menu-2",
+		".site-above-footer-wrap .ast-footer-html-1",
+		".site-primary-footer-wrap .footer-widget-area",
+		".site-below-footer-wrap .ast-footer-copyright",
+		".site-below-footer-wrap .ast-footer-social-wrap"
+	],
+	"mobile": [
+		".ast-mobile-header-wrap .ast-header-html-1",
+		".ast-mobile-header-wrap .menu-toggle"
+	],
+	"presents": [
+		".ast-builder-menu-1 .sub-menu",
+		".ast-builder-menu-2 .sub-menu"
+	],
+	"menuMobile": "dropdown",
+	"menuOuvert": [
+		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
+	],
+	"styles": [
+		{
+			"selecteur": "#ast-desktop-header .ast-above-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#ast-mobile-header .ast-above-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .ast-primary-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#ast-mobile-header .ast-primary-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#ast-desktop-header .ast-below-header-bar",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#masthead",
+			"proprietes": [
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": ".site-above-footer-wrap",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": ".site-primary-footer-wrap",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": ".site-below-footer-wrap",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		},
+		{
+			"selecteur": "#colophon",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left",
+				"margin-top",
+				"margin-right",
+				"margin-bottom",
+				"margin-left"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R24-marges-toutes.php b/tests/references/wp/R24-marges-toutes.php
new file mode 100644
index 0000000..046234a
--- /dev/null
+++ b/tests/references/wp/R24-marges-toutes.php
@@ -0,0 +1,31 @@
+<?php
+// Marges des quinze options des rangées, de l'en-tête entier et du pied entier : valeurs distinctes
+// par appareil, unités mêlées, côtés en partie vides, marges extérieures négatives (valeurs :
+// styles/valeurs/marges-toutes.json). Rangées haute et basse de l'en-tête remplies comme dans R1,
+// rangée haute du pied remplie d'un texte.
+putenv('GROUPE=marges-toutes');
+require dirname(__DIR__) . '/styles/appliquer.php';
+ref_menu('Secondaire', 'secondary_menu');
+ref_reglages([
+	'header-desktop-items' => [
+		'popup' => ['popup_content' => ['mobile-menu']],
+		'above' => ['above_left' => ['html-1'], 'above_left_center' => [], 'above_center' => [], 'above_right_center' => [], 'above_right' => ['social-icons-1']],
+		'primary' => ['primary_left' => ['logo'], 'primary_left_center' => [], 'primary_center' => [], 'primary_right_center' => [], 'primary_right' => ['menu-1', 'button-1']],
+		'below' => ['below_left' => [], 'below_left_center' => [], 'below_center' => ['menu-2'], 'below_right_center' => [], 'below_right' => []],
+	],
+	'header-mobile-items' => [
+		'popup' => ['popup_content' => ['mobile-menu']],
+		'above' => ['above_left' => [], 'above_center' => ['html-1'], 'above_right' => []],
+		'primary' => ['primary_left' => ['logo'], 'primary_center' => [], 'primary_right' => ['mobile-trigger']],
+		'below' => ['below_left' => [], 'below_center' => [], 'below_right' => []],
+	],
+	'footer-desktop-items' => [
+		'above' => ['above_1' => ['html-1'], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
+		'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4'], 'primary_5' => []],
+		'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
+	],
+	'hba-footer-column' => '1',
+	'hba-footer-layout' => ['desktop' => 'full', 'tablet' => 'full', 'mobile' => 'full'],
+	'header-html-1' => '<p>Ouvert du lundi au vendredi</p>',
+	'footer-html-1' => '<p>Un site éditorial propulsé par SPIP</p>',
+]);
diff --git a/tests/references/wp/R24-pied-marges.json b/tests/references/wp/R24-pied-marges.json
new file mode 100644
index 0000000..40b80a3
--- /dev/null
+++ b/tests/references/wp/R24-pied-marges.json
@@ -0,0 +1,51 @@
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
+			"selecteur": ".site-primary-footer-wrap",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left"
+			]
+		},
+		{
+			"selecteur": ".site-below-footer-wrap",
+			"proprietes": [
+				"padding-top",
+				"padding-right",
+				"padding-bottom",
+				"padding-left"
+			]
+		},
+		{
+			"selecteur": ".site-primary-footer-wrap .site-footer-section-1",
+			"proprietes": [
+				"width"
+			]
+		},
+		{
+			"selecteur": ".site-below-footer-wrap .site-footer-section-1",
+			"proprietes": [
+				"width"
+			]
+		}
+	]
+}
diff --git a/tests/references/wp/R24-pied-marges.php b/tests/references/wp/R24-pied-marges.php
new file mode 100644
index 0000000..67e341f
--- /dev/null
+++ b/tests/references/wp/R24-pied-marges.php
@@ -0,0 +1,5 @@
+<?php
+// Marges intérieures des rangées principale et basse du pied de Garden Maintenance, sur les trois
+// appareils. Placement des références des styles ; valeurs : styles/valeurs/marges-pied.json.
+putenv('GROUPE=marges-pied');
+require dirname(__DIR__) . '/styles/appliquer.php';
diff --git a/tests/unit/StylesMargesTest.php b/tests/unit/StylesMargesTest.php
new file mode 100644
index 0000000..ee3ca78
--- /dev/null
+++ b/tests/unit/StylesMargesTest.php
@@ -0,0 +1,113 @@
+<?php
+
+use PHPUnit\Framework\Attributes\DataProvider;
+use PHPUnit\Framework\TestCase;
+
+require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';
+require_once __DIR__ . '/StylesAttendusTest.php';
+
+/**
+ * Marges des rangées du builder : règles égales, dans l'ordre, aux règles de marges qu'Astra 4.11.0
+ * émet pour les références R24 (tests/references/styles/attendus/marges-*.css, extraites par
+ * marges.py), avec le placement de chaque référence ; normalisation et conditions d'émission.
+ */
+final class StylesMargesTest extends TestCase {
+
+	/** Placement des références des styles (tests/references/styles/appliquer.php). */
+	private const STYLES = [
+		'entete_ordinateur' => ['primary' => ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'button-1']]],
+		'pied' => [
+			'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']],
+			'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']],
+		],
+		'rangees' => ['pied_primary' => ['colonnes' => 4], 'pied_below' => ['colonnes' => 2]],
+	];
+
+	private static function valeurs(string $variante): array {
+		return json_decode(file_get_contents(dirname(__DIR__) . "/references/styles/valeurs/$variante.json"), true);
+	}
+
+	/** Variante => placement de la référence WordPress R24 correspondante (tests/references/wp). */
+	public static function variantes(): array {
+		$toutes = array_replace_recursive(self::STYLES, [
+			'entete_ordinateur' => ['above' => ['above_left' => ['html-1'], 'above_right' => ['social-icons-1']], 'below' => ['below_center' => ['menu-2']]],
+			'entete_mobile' => ['above' => ['above_center' => ['html-1']]],
+			'pied' => ['above' => ['above_1' => ['html-1']]],
+			'rangees' => ['pied_above' => ['colonnes' => 1]],
+		]);
+		$vides = self::STYLES;
+		$vides['entete_mobile'] = ['above' => ['above_center' => ['html-1']]];
+		$vides['pied']['primary'] = ['primary_1' => [], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_6' => ['widget-1']];
+		$vides['rangees']['pied_primary'] = ['colonnes' => 6];
+		return [
+			'marges-pied' => ['marges-pied', self::STYLES],
+			'marges-toutes' => ['marges-toutes', $toutes],
+			'marges-rangees-vides' => ['marges-rangees-vides', $vides],
+		];
+	}
+
+	#[DataProvider('variantes')]
+	public function testReglesDAstraDansLOrdre(string $variante, array $hfb): void {
+		// Astra n'applique pas trim_css() à ces règles : l'espace après la virgule des sélecteurs,
+		// sans effet, est retirée comme le fait astra_styles_ecrire().
+		$attendues = array_values(array_filter(array_map(function ($r) {
+			return str_replace(', ', ',', trim($r));
+		}, file(dirname(__DIR__) . "/references/styles/attendus/$variante.css"))));
+		$this->assertNotEmpty($attendues);
+		$this->assertSame($attendues, StylesAttendusTest::regles(astra_styles_css(self::valeurs($variante), $hfb)));
+	}
+
+	public function testConditionsDEmission(): void {
+		$v = self::valeurs('marges-toutes');
+		$haut = '.ast-above-header.ast-above-header-bar';
+		$pied = '.ast-hfb-header .site-footer{';
+		// Rangée haute de l'en-tête vide sur les deux appareils : rien pour elle ; pied entier toujours.
+		$css = astra_styles_css($v, self::STYLES);
+		$this->assertStringNotContainsString($haut, $css);
+		$this->assertStringContainsString($pied, $css);
+		// Remplie sur ordinateur seulement, ou sur mobile seulement : émise.
+		$this->assertStringContainsString($haut, astra_styles_css($v, ['entete_ordinateur' => ['above' => ['above_right_center' => ['html-1']]]] + self::STYLES));
+		$this->assertStringContainsString($haut, astra_styles_css($v, ['entete_mobile' => ['above' => ['above_left' => ['html-1']]]] + self::STYLES));
+		// Rangée principale de l'en-tête vide sur les deux appareils : ni elle, ni l'en-tête entier.
+		$sans = ['entete_ordinateur' => ['primary' => ['primary_left' => [], 'primary_right' => []]], 'entete_mobile' => ['primary' => ['primary_left' => [], 'primary_right' => []]]] + self::STYLES;
+		$css = astra_styles_css($v, $sans);
+		$this->assertStringNotContainsString('.ast-primary-header-bar.main-header-bar', $css);
+		$this->assertStringNotContainsString('.ast-hfb-header .site-header', $css);
+		$this->assertStringContainsString($pied, $css);
+		// Rangée du pied remplie en zone 6 seulement : rien pour elle.
+		$six = self::STYLES;
+		$six['pied']['primary'] = ['primary_1' => [], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_6' => ['widget-1']];
+		$six['rangees']['pied_primary'] = ['colonnes' => 6];
+		$this->assertStringNotContainsString('.site-primary-footer-wrap', astra_styles_css($v, $six));
+		// Sans option de marge : rien.
+		$this->assertSame('', astra_styles_css([], self::STYLES));
+	}
+
+	public function testCotesCommeAstraResponsiveSpacing(): void {
+		$sel = '.site-below-footer-wrap[data-section="section-below-footer-builder"]';
+		$css = astra_styles_css(['section-below-footer-builder-margin' => [
+			'desktop' => ['top' => 0, 'right' => '', 'bottom' => '-10', 'left' => '1.5'], 'desktop-unit' => 'em',
+			// Tablette sans unité enregistrée : rien n'est émis pour elle, comme dans Astra.
+			'tablet' => ['top' => '7'],
+			'mobile' => ['top' => '', 'right' => '', 'bottom' => '', 'left' => ''], 'mobile-unit' => 'px',
+		]], self::STYLES);
+		$this->assertSame($sel . '{margin-top:0em;margin-bottom:-10em;margin-left:1.5em;}', $css);
+	}
+
+	public function testNormalisation(): void {
+		$v = [
+			'section-primary-footer-builder-padding' => ['desktop' => ['top' => '-5', 'right' => '10', 'bottom' => '1200'], 'desktop-unit' => 'px', 'tablet-unit' => 'vh'],
+			'section-primary-footer-builder-margin' => ['desktop' => ['top' => '-5', 'right' => 'x', 'bottom' => '-1200'], 'desktop-unit' => '%', 'tablet' => ['top' => '-0.5'], 'tablet-unit' => 'em'],
+		];
+		$this->assertSame([
+			// Marge intérieure négative ou hors bornes refusée, unité inconnue refusée.
+			'section-primary-footer-builder-padding' => ['desktop' => ['right' => '10'], 'desktop-unit' => 'px'],
+			// Marge extérieure négative gardée (bornée à 1000 en valeur absolue).
+			'section-primary-footer-builder-margin' => ['desktop' => ['top' => '-5'], 'desktop-unit' => '%', 'tablet' => ['top' => '-0.5'], 'tablet-unit' => 'em'],
+		], astra_styles_normaliser($v));
+		// Valeurs du modèle : conservées telles quelles.
+		$garden = self::valeurs('marges-pied');
+		$this->assertSame($garden, astra_styles_normaliser($garden));
+		$this->assertSame(self::valeurs('marges-toutes'), astra_styles_normaliser(self::valeurs('marges-toutes')));
+	}
+}
````

Les attendus `attendus/marges-*.css` sont la sortie de `WP=… tests/references/styles/capturer.sh` (dernière boucle) avec les références de ce correctif ; le script ne doit modifier aucun autre attendu.

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 6 échecs (`StylesMargesTest` : `testReglesDAstraDansLOrdre` pour les trois variantes, `testConditionsDEmission`, `testCotesCommeAstraResponsiveSpacing`, `testNormalisation`) ;
- intégration : 1 échec (`StylesFormulaireTest::testRenduDesChamps`, 15 sections au lieu de 16).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/inc/astra_styles.php b/inc/astra_styles.php
index 3c0837b..90ebced 100644
--- a/inc/astra_styles.php
+++ b/inc/astra_styles.php
@@ -134,6 +134,22 @@ function astra_styles_registre(): array {
 	$alignement = ['type' => 'alignement', 'responsive' => true, 'defaut' => ['desktop' => 'center', 'tablet' => 'center', 'mobile' => 'center']];
 	$ajouter('alignements', 'inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php', ['footer-copyright-alignment' => $alignement]);
 	$ajouter('alignements', $sociaux, ['footer-social-1-alignment' => $alignement]);
+	// Marges des rangées du builder, de l'en-tête entier et du pied entier : les trois appareils
+	// s'appliquent (appareils), les marges extérieures acceptent les nombres négatifs (signe).
+	// Défaut : Astra_Builder_Helper::$default_responsive_spacing (côtés vides, unités px).
+	$vides = ['top' => '', 'right' => '', 'bottom' => '', 'left' => ''];
+	$marge = $cotes_r + ['appareils' => true, 'defaut' => ['desktop' => $vides, 'tablet' => $vides, 'mobile' => $vides, 'desktop-unit' => 'px', 'tablet-unit' => 'px', 'mobile-unit' => 'px']];
+	$marges = [];
+	foreach (['above-header', 'primary-header', 'below-header', 'above-footer', 'primary-footer', 'below-footer'] as $rangee) {
+		$marges["section-$rangee-builder-padding"] = $marge;
+		$marges["section-$rangee-builder-margin"] = $marge + ['signe' => true];
+	}
+	$marges['section-footer-builder-layout-padding'] = $marge;
+	$marges['section-footer-builder-layout-margin'] = $marge + ['signe' => true];
+	$ajouter('marges-rangees', 'inc/class-astra-extended-base-dynamic-css.php', $marges);
+	$ajouter('marges-rangees', 'inc/builder/type/header/primary-header/dynamic-css/dynamic.css.php', [
+		'section-header-builder-layout-margin' => $marge + ['signe' => true],
+	]);
 	$ajouter('menu-mobile', 'inc/builder/type/header/mobile-trigger/dynamic-css/dynamic.css.php', [
 		'mobile-header-toggle-btn-style' => ['type' => 'enum', 'valeurs' => ['minimal', 'fill', 'outline'], 'defaut' => 'minimal'],
 		'mobile-header-toggle-btn-color' => $couleur,
@@ -246,9 +262,10 @@ function astra_styles_filtrer_objet($v, array $champs): ?array {
 			$regle === 'couleur' ? astra_styles_couleur_valide($x) : (
 			$regle === 'nombre' ? astra_styles_nombre_valide($x) : (
 			$regle === 'nombre-signe' ? ($x === '' || (is_numeric($x) && abs((float) $x) <= 100)) : (
+			$regle === 'nombre-signe-1000' ? ($x === '' || ((is_int($x) || is_float($x) || (is_string($x) && is_numeric($x))) && abs((float) $x) <= 1000)) : (
 			$regle === 'opacite' ? ($x === '' || (is_numeric($x) && (float) $x >= 0 && (float) $x <= 1)) : (
 			$regle === 'entier' ? ($x === '' || (is_numeric($x) && (int) $x == $x)) : (
-			$regle === 'image' ? (is_string($x) && ($x === '' || preg_match('#^IMG/[\w./-]+\.(png|jpe?g|gif|webp|svg)$#i', $x))) : false))))));
+			$regle === 'image' ? (is_string($x) && ($x === '' || preg_match('#^IMG/[\w./-]+\.(png|jpe?g|gif|webp|svg)$#i', $x))) : false)))))));
 		if ($ok) {
 			$r[$champ] = $x;
 		}
@@ -265,7 +282,7 @@ function astra_styles_valider(array $d, $v) {
 		case 'nombre':
 			return astra_styles_nombre_valide($v) ? $v : null;
 		case 'quatre-cotes':
-			return astra_styles_filtrer_objet($v, array_fill_keys(['top', 'right', 'bottom', 'left'], 'nombre'));
+			return astra_styles_filtrer_objet($v, array_fill_keys(['top', 'right', 'bottom', 'left'], empty($d['signe']) ? 'nombre' : 'nombre-signe-1000'));
 		case 'police':
 			return is_string($v) && ($v === '' || isset(astra_polices_locales()[$v]) || preg_match('/^[\w\s,\'"-]+$/u', $v)) ? $v : null;
 		case 'graisse':
@@ -484,7 +501,7 @@ function astra_styles_ecrire(array $regles): string {
 }
 
 /** Modules de groupe (inc/styles/<module>.php), dans l'ordre où Astra émet leur CSS. */
-const ASTRA_STYLES_MODULES = ['titres', 'bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux', 'alignements', 'menu_mobile'];
+const ASTRA_STYLES_MODULES = ['titres', 'bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux', 'alignements', 'menu_mobile', 'marges_rangees'];
 
 /**
  * CSS des styles du Customizer (vide sans réglage), dans l'ordre d'émission d'Astra. $compatibilite :
diff --git a/inc/astra_styles_saisie.php b/inc/astra_styles_saisie.php
index 57ff39f..5ec3159 100644
--- a/inc/astra_styles_saisie.php
+++ b/inc/astra_styles_saisie.php
@@ -27,6 +27,7 @@ function astra_styles_sections(): array {
 		'sociaux_entete' => '/^header-social-1-/',
 		'sociaux_pied' => '/^footer-social-1-/',
 		'menu_mobile' => '/^mobile-header-toggle-/',
+		'marges_rangees' => '/^section-((above|primary|below)-(header|footer)-builder|(header|footer)-builder-layout)-(padding|margin)$/',
 		'entete_transparent' => '/^(hba-|hbb-)?transparent-/',
 	];
 }
diff --git a/inc/styles/marges_rangees.php b/inc/styles/marges_rangees.php
new file mode 100644
index 0000000..b271553
--- /dev/null
+++ b/inc/styles/marges_rangees.php
@@ -0,0 +1,79 @@
+<?php
+/**
+ * Astra pour SPIP — marges des rangées du builder, de l'en-tête entier et du pied entier
+ * (Astra_Extended_Base_Dynamic_CSS::prepare_advanced_margin_padding_css() de
+ * inc/class-astra-extended-base-dynamic-css.php, et section-header-builder-layout-margin de
+ * inc/builder/type/header/primary-header/dynamic-css/dynamic.css.php d'Astra 4.11.0), sur les
+ * trois appareils.
+ *
+ * @license GPL-3.0-or-later
+ */
+
+/**
+ * Sections, dans l'ordre d'émission d'Astra : section => [sélecteur, rangée dont dépend
+ * l'émission]. Astra n'émet le CSS d'une rangée de l'en-tête que si elle est remplie sur
+ * ordinateur ou sur mobile (Astra_Builder_Helper::is_row_empty(), vrai pour une rangée remplie),
+ * celui d'une rangée du pied que si une de ses zones 1 à 5 est remplie (is_footer_row_empty()) ;
+ * la marge de l'en-tête entier suit la rangée principale, celle du pied entier est toujours émise.
+ */
+const ASTRA_STYLES_MARGES_RANGEES = [
+	'section-below-footer-builder' => ['.site-below-footer-wrap[data-section="section-below-footer-builder"]', 'pied:below'],
+	'section-above-footer-builder' => ['.site-above-footer-wrap[data-section="section-above-footer-builder"]', 'pied:above'],
+	'section-footer-builder-layout' => ['.ast-hfb-header .site-footer', ''],
+	'section-primary-footer-builder' => ['.site-primary-footer-wrap[data-section="section-primary-footer-builder"]', 'pied:primary'],
+	'section-primary-header-builder' => ['.ast-desktop .ast-primary-header-bar.main-header-bar, .ast-header-break-point #masthead .ast-primary-header-bar.main-header-bar', 'entete:primary'],
+	'section-header-builder-layout' => ['.ast-hfb-header .site-header', 'entete:primary'],
+	'section-above-header-builder' => ['.ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar', 'entete:above'],
+	'section-below-header-builder' => ['.ast-below-header-bar.ast-below-header, .ast-header-break-point .ast-below-header-bar.ast-below-header', 'entete:below'],
+];
+
+function astra_styles_marges_rangees(array $s, array $hfb): array {
+	$n = astra_hfb_normaliser($hfb);
+	$regles = [];
+	foreach (ASTRA_STYLES_MARGES_RANGEES as $section => [$sel, $rangee]) {
+		if (!isset($s["$section-padding"]) && !isset($s["$section-margin"])) {
+			continue;
+		}
+		if ($rangee !== '') {
+			[$builder, $r] = explode(':', $rangee);
+			$remplie = $builder === 'pied'
+				? astra_hfb_pied_rangee_remplie($n['pied'], $r)
+				: astra_styles_entete_rangee_remplie($n['entete_ordinateur'], $r) || astra_styles_entete_rangee_remplie($n['entete_mobile'], $r);
+			if (!$remplie) {
+				continue;
+			}
+		}
+		foreach (['' => 'desktop', '(max-width:921px)' => 'tablet', '(max-width:544px)' => 'mobile'] as $media => $appareil) {
+			$d = [];
+			foreach (['padding', 'margin'] as $type) {
+				foreach (['top', 'bottom', 'left', 'right'] as $cote) {
+					$d["$type-$cote"] = astra_styles_espacement($s["$section-$type"] ?? null, $cote, $appareil);
+				}
+			}
+			$regles[] = [$media, $sel, $d];
+		}
+	}
+	return $regles;
+}
+
+/** Vrai si une zone de la rangée d'un en-tête normalisé (ordinateur ou mobile) a un élément. */
+function astra_styles_entete_rangee_remplie(array $entete, string $rangee): bool {
+	foreach ($entete[$rangee] ?? [] as $ids) {
+		if ($ids) {
+			return true;
+		}
+	}
+	return false;
+}
+
+/**
+ * Un côté d'une option « côtés responsifs » pour un appareil, comme astra_responsive_spacing() :
+ * vide sans valeur ou sans unité enregistrée pour cet appareil ; 0 donne 0px.
+ */
+function astra_styles_espacement($valeur, string $cote, string $appareil): string {
+	if (!is_array($valeur) || !isset($valeur[$appareil][$cote], $valeur["$appareil-unit"])) {
+		return '';
+	}
+	$v = $valeur[$appareil][$cote];
+	return $v === '' ? '' : $v . $valeur["$appareil-unit"];
+}
diff --git a/lang/astra_en.php b/lang/astra_en.php
index 5339fa8..f3bc80a 100644
--- a/lang/astra_en.php
+++ b/lang/astra_en.php
@@ -296,6 +296,21 @@ return [
 	'style_mobile_header_toggle_btn_color' => 'Icon color',
 	'style_mobile_header_toggle_btn_style' => 'Button style',
 	'style_mobile_header_toggle_icon_size' => 'Icon size (px)',
+	'style_section_above_footer_builder_margin' => 'Footer, above row: margin',
+	'style_section_above_footer_builder_padding' => 'Footer, above row: padding',
+	'style_section_above_header_builder_margin' => 'Header, above row: margin',
+	'style_section_above_header_builder_padding' => 'Header, above row: padding',
+	'style_section_below_footer_builder_margin' => 'Footer, below row: margin',
+	'style_section_below_footer_builder_padding' => 'Footer, below row: padding',
+	'style_section_below_header_builder_margin' => 'Header, below row: margin',
+	'style_section_below_header_builder_padding' => 'Header, below row: padding',
+	'style_section_footer_builder_layout_margin' => 'Whole footer: margin',
+	'style_section_footer_builder_layout_padding' => 'Whole footer: padding',
+	'style_section_header_builder_layout_margin' => 'Whole header: margin',
+	'style_section_primary_footer_builder_margin' => 'Footer, primary row: margin',
+	'style_section_primary_footer_builder_padding' => 'Footer, primary row: padding',
+	'style_section_primary_header_builder_margin' => 'Header, primary row: margin',
+	'style_section_primary_header_builder_padding' => 'Header, primary row: padding',
 	'style_social_alignment' => 'Alignment',
 	'style_social_color' => 'Icon color',
 	'style_social_color_type' => 'Icon colors',
@@ -367,6 +382,7 @@ return [
 	'styles_section_copyright' => 'Copyright',
 	'styles_section_entete_transparent' => 'Transparent header',
 	'styles_section_identite' => 'Site identity',
+	'styles_section_marges_rangees' => 'Row spacing',
 	'styles_section_menu_1' => 'Primary menu',
 	'styles_section_menu_2' => 'Secondary menu',
 	'styles_section_menu_mobile' => 'Mobile menu button',
diff --git a/lang/astra_fr.php b/lang/astra_fr.php
index 23afe36..4501db8 100644
--- a/lang/astra_fr.php
+++ b/lang/astra_fr.php
@@ -296,6 +296,21 @@ return [
 	'style_mobile_header_toggle_btn_color' => 'Couleur de l’icône',
 	'style_mobile_header_toggle_btn_style' => 'Style du bouton',
 	'style_mobile_header_toggle_icon_size' => 'Taille de l’icône (px)',
+	'style_section_above_footer_builder_margin' => 'Pied de page, rangée haute : marge extérieure',
+	'style_section_above_footer_builder_padding' => 'Pied de page, rangée haute : marge intérieure',
+	'style_section_above_header_builder_margin' => 'En-tête, rangée haute : marge extérieure',
+	'style_section_above_header_builder_padding' => 'En-tête, rangée haute : marge intérieure',
+	'style_section_below_footer_builder_margin' => 'Pied de page, rangée basse : marge extérieure',
+	'style_section_below_footer_builder_padding' => 'Pied de page, rangée basse : marge intérieure',
+	'style_section_below_header_builder_margin' => 'En-tête, rangée basse : marge extérieure',
+	'style_section_below_header_builder_padding' => 'En-tête, rangée basse : marge intérieure',
+	'style_section_footer_builder_layout_margin' => 'Pied de page entier : marge extérieure',
+	'style_section_footer_builder_layout_padding' => 'Pied de page entier : marge intérieure',
+	'style_section_header_builder_layout_margin' => 'En-tête entier : marge extérieure',
+	'style_section_primary_footer_builder_margin' => 'Pied de page, rangée principale : marge extérieure',
+	'style_section_primary_footer_builder_padding' => 'Pied de page, rangée principale : marge intérieure',
+	'style_section_primary_header_builder_margin' => 'En-tête, rangée principale : marge extérieure',
+	'style_section_primary_header_builder_padding' => 'En-tête, rangée principale : marge intérieure',
 	'style_social_alignment' => 'Alignement',
 	'style_social_color' => 'Couleur des icônes',
 	'style_social_color_type' => 'Couleurs des icônes',
@@ -367,6 +382,7 @@ return [
 	'styles_section_copyright' => 'Copyright',
 	'styles_section_entete_transparent' => 'En-tête transparent',
 	'styles_section_identite' => 'Identité du site',
+	'styles_section_marges_rangees' => 'Marges des rangées',
 	'styles_section_menu_1' => 'Menu principal',
 	'styles_section_menu_2' => 'Menu secondaire',
 	'styles_section_menu_mobile' => 'Bouton du menu mobile',
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (119 tests, 1692 assertions)`, intégration `OK (83 tests, 289 assertions)`

```bash
WP=… tests/references/styles/capturer.sh && git status --short tests/references/styles/attendus
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R24-pied-marges R24-marges-toutes R24-marges-rangees-vides
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R24-pied-marges R24-marges-toutes R24-marges-rangees-vides
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R24-pied-marges R24-marges-toutes R24-marges-rangees-vides
```

Attendu : `capturer.sh` des styles annonce `marges-pied : 4`, `marges-toutes : 19` et `marges-rangees-vides : 11` règles de marges et ne modifie aucun attendu (`git status` vide) ; HTML identique pour les trois références ; toutes les vues sous le seuil ; styles calculés égaux aux trois largeurs : 10 valeurs pour `R24-pied-marges` (marges des rangées principale et basse du pied, largeur de leur première colonne), 76 pour les deux autres (marges des rangées de l'en-tête sur ordinateur et sur mobile, de `#masthead`, des rangées du pied et de `#colophon`). Restaurer `tests/references/__pycache__/*.pyc` s'il a changé (`git checkout`).

- [ ] **Étape 5 : commit**

```bash
git add inc/styles/marges_rangees.php inc/astra_styles.php inc/astra_styles_saisie.php lang/astra_fr.php lang/astra_en.php tests/unit/StylesMargesTest.php tests/integration/StylesFormulaireTest.php tests/references/styles/marges.py tests/references/styles/capturer.sh tests/references/styles/valeurs/marges-pied.json tests/references/styles/valeurs/marges-toutes.json tests/references/styles/valeurs/marges-rangees-vides.json tests/references/styles/attendus/marges-pied.css tests/references/styles/attendus/marges-toutes.css tests/references/styles/attendus/marges-rangees-vides.css tests/references/wp/R24-pied-marges.php tests/references/wp/R24-pied-marges.json tests/references/wp/R24-marges-toutes.php tests/references/wp/R24-marges-toutes.json tests/references/wp/R24-marges-rangees-vides.php tests/references/wp/R24-marges-rangees-vides.json tests/comparaison/configurations.php tests/comparaison/comparer_captures.sh
git commit -m "Marges des rangées du builder : émission comme dans Astra"
```

### Tâche 3 : saisie des marges sur les trois appareils

Décisions 3 et 6. Le formulaire présente, pour chaque option du groupe, trois lignes (ordinateur, tablette, mobile) de quatre côtés et une unité ; la fusion garde les côtés et les unités des trois appareils ; une première saisie est gardée dès qu'un côté d'un appareil est renseigné.

**Fichiers :**
- Modifier : `inc/astra_styles_saisie.php`, `formulaires/configurer_astra_styles.html`, `tests/unit/StylesSaisieTest.php`, `tests/integration/StylesFormulaireTest.php`

**Interfaces :**
- Consomme : clés de registre `appareils` et `signe` (tâche 2).
- Produit : `astra_styles_trois_appareils(array $d): bool` ; champs `appareils` (avec `nom_unite` et `unite` par appareil) et `signe` d'`astra_styles_champs()`.

- [ ] **Étape 1 : écrire les tests**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/tests/integration/StylesFormulaireTest.php b/tests/integration/StylesFormulaireTest.php
index c5256a9..36a3bce 100644
--- a/tests/integration/StylesFormulaireTest.php
+++ b/tests/integration/StylesFormulaireTest.php
@@ -96,6 +96,30 @@ final class StylesFormulaireTest extends TestCase {
 		$this->assertSame('right', lire_config('astra/styles')['footer-copyright-alignment']['mobile']);
 	}
 
+	public function testAllerRetourDesMargesDesRangees(): void {
+		// Valeurs de la référence R24-marges-toutes (trois appareils, unités mêlées, marges négatives) :
+		// un enregistrement sans modification ne change pas le CSS ; les options jamais réglées ne
+		// sont pas enregistrées.
+		$valeurs = json_decode(file_get_contents(dirname(__DIR__) . '/references/styles/valeurs/marges-toutes.json'), true);
+		unset($valeurs['section-above-footer-builder-margin']);
+		$hfb = ['entete_mobile' => ['above' => ['above_center' => ['html-1']]], 'pied' => ['above' => ['above_1' => ['html-1']], 'below' => ['below_1' => ['copyright']]]];
+		ecrire_config('astra/styles', $valeurs);
+		$avant = astra_styles_css($valeurs, $hfb);
+		$this->assertStringContainsString('margin-top:-5px;', $avant);
+		$saisie = $this->saisieDuFormulaire();
+		$this->assertSame(['top' => '60', 'right' => '30', 'bottom' => '30', 'left' => '30'], $saisie['section-primary-footer-builder-padding']['mobile']);
+		set_request('s', $saisie);
+		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
+		formulaires_configurer_astra_styles_traiter_dist();
+		$this->assertSame($avant, astra_styles_css(lire_config('astra/styles'), $hfb));
+		$this->assertArrayNotHasKey('section-above-footer-builder-margin', lire_config('astra/styles'));
+		// Marge tablette changée par le formulaire : enregistrée et émise.
+		$saisie['section-above-footer-builder-padding']['tablet']['top'] = '25';
+		set_request('s', $saisie);
+		formulaires_configurer_astra_styles_traiter_dist();
+		$this->assertStringContainsString('@media (max-width:921px){.site-above-footer-wrap[data-section="section-above-footer-builder"]{padding-top:25px;', astra_styles_css(lire_config('astra/styles'), $hfb));
+	}
+
 	/** Champs du formulaire rendu, postés tels quels (sélecteurs : option choisie, sinon la première). */
 	private function saisieDuFormulaire(): array {
 		$doc = new DOMDocument();
diff --git a/tests/unit/StylesSaisieTest.php b/tests/unit/StylesSaisieTest.php
index 794b7a4..5b7c2ba 100644
--- a/tests/unit/StylesSaisieTest.php
+++ b/tests/unit/StylesSaisieTest.php
@@ -76,6 +76,36 @@ final class StylesSaisieTest extends TestCase {
 		$this->assertSame([], astra_styles_saisie_fusionner([], ['footer-copyright-alignment' => ['desktop' => '', 'tablet' => '', 'mobile' => '']]));
 	}
 
+	public function testMargesDesRangeesSaisiesSurLesTroisAppareils(): void {
+		// Marges des rangées : côtés et unités des trois appareils saisis ; côtés absents de la
+		// saisie gardés.
+		$existant = ['section-primary-footer-builder-padding' => ['desktop' => ['top' => '80', 'right' => ''], 'tablet' => ['top' => '80'], 'desktop-unit' => 'px', 'tablet-unit' => 'px']];
+		$saisie = ['section-primary-footer-builder-padding' => [
+			'desktop' => ['top' => '70'], 'desktop-unit' => 'px',
+			'tablet' => ['right' => '2'], 'tablet-unit' => 'em',
+			'mobile' => ['top' => '60', 'right' => '30', 'bottom' => '30', 'left' => '30'], 'mobile-unit' => '%',
+		]];
+		$this->assertSame(['section-primary-footer-builder-padding' => [
+			'desktop' => ['top' => '70', 'right' => ''], 'tablet' => ['top' => '80', 'right' => '2'], 'desktop-unit' => 'px', 'tablet-unit' => 'em',
+			'mobile' => ['top' => '60', 'right' => '30', 'bottom' => '30', 'left' => '30'], 'mobile-unit' => '%',
+		]], astra_styles_saisie_fusionner($existant, $saisie));
+		// Première saisie : gardée dès qu'un côté d'un appareil est renseigné ; des unités seules ne
+		// sont pas une valeur.
+		$mobile = ['section-below-footer-builder-margin' => ['desktop' => ['top' => ''], 'desktop-unit' => 'px', 'mobile' => ['top' => '-5'], 'mobile-unit' => 'px']];
+		$this->assertSame($mobile, astra_styles_saisie_fusionner([], $mobile));
+		$this->assertSame([], astra_styles_saisie_fusionner([], ['section-below-footer-builder-margin' => ['desktop' => ['top' => ''], 'desktop-unit' => 'px', 'tablet-unit' => 'em']]));
+	}
+
+	public function testChampsDesMargesParAppareil(): void {
+		$champs = astra_styles_champs(['section-header-builder-layout-margin' => ['mobile' => ['bottom' => '-4'], 'mobile-unit' => 'em']], 'marges_rangees');
+		$this->assertCount(15, $champs);
+		$marge = array_column($champs, null, 'option')['section-header-builder-layout-margin'];
+		$this->assertTrue($marge['signe']);
+		$this->assertSame(['desktop', 'tablet', 'mobile'], array_column($marge['appareils'], 'appareil'));
+		$this->assertSame(['appareil' => 'mobile', 'nom' => 's[section-header-builder-layout-margin][mobile]', 'valeur' => ['bottom' => '-4'], 'nom_unite' => 's[section-header-builder-layout-margin][mobile-unit]', 'unite' => 'em'], $marge['appareils'][2]);
+		$this->assertFalse(array_column($champs, null, 'option')['section-primary-footer-builder-padding']['signe']);
+	}
+
 	public function testValeursRefusees(): void {
 		$this->assertSame(
 			['button-color', 'font-size-button'],
````

- [ ] **Étape 2 : vérifier qu'ils échouent**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php` (lancer les deux, même si le premier échoue)
Attendu :
- unitaires : 2 échecs (`StylesSaisieTest::testMargesDesRangeesSaisiesSurLesTroisAppareils`, `StylesSaisieTest::testChampsDesMargesParAppareil`) et 1 avertissement (`Undefined array key "signe"`) ;
- intégration : 1 échec (`StylesFormulaireTest::testAllerRetourDesMargesDesRangees`).

- [ ] **Étape 3 : implémenter**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/formulaires/configurer_astra_styles.html b/formulaires/configurer_astra_styles.html
index a36b1cb..e18a5b3 100644
--- a/formulaires/configurer_astra_styles.html
+++ b/formulaires/configurer_astra_styles.html
@@ -11,6 +11,10 @@
 		#ACTION_FORMULAIRE
 		<style>
 			.astra-styles-cotes input, .astra-styles-unite input { max-width: 6em; }
+			.astra-styles-appareils > .astra-styles-cotes { display: block; white-space: nowrap; }
+			.astra-styles-appareils > .astra-styles-cotes > span { display: inline-block; min-width: 5.5em; }
+			.astra-styles-appareils > .astra-styles-cotes input { max-width: 4.5em; }
+			.astra-styles-appareils > .astra-styles-cotes select { width: auto; display: inline-block; }
 		</style>
 		<p class="explication"><:astra:styles_explication:></p>
 		<datalist id="astra-styles-palette">
@@ -78,7 +82,24 @@
 					<option value="0"[(#GET{v}|=={0}|oui)selected="selected"]><:astra:styles_masque:></option>
 				</select>
 				</BOUCLE_booleen>
-				<BOUCLE_cotes(CONDITION){si #GET{c}|table_valeur{type}|=={quatre-cotes}}>
+				<BOUCLE_cotes_appareils(CONDITION){si #GET{c}|table_valeur{type}|=={quatre-cotes}|et{#GET{c}|table_valeur{appareils}}}>
+				<span class="astra-styles-appareils" id="s_[(#GET{c}|table_valeur{option})]">
+					<BOUCLE_appareils_cotes(DATA){source tableau, #GET{c}|table_valeur{appareils}}>
+					#SET{a,#VALEUR}
+					<span class="astra-styles-cotes">
+						<span>[(#VAL{astra:styles_appareil_}|concat{#GET{a}|table_valeur{appareil}}|_T)]</span>
+						<BOUCLE_cote_appareil(DATA){liste top, right, bottom, left}>
+						#SET{etiquette,#ARRAY{top,haut,right,droite,bottom,bas,left,gauche}|table_valeur{#VALEUR}}
+						<input type="number"[(#GET{c}|table_valeur{signe}|non) min="0"] step="any" class="text" name="[(#GET{a}|table_valeur{nom}|astra_styles_nom{#VALEUR})]" value="[(#GET{a}|table_valeur{valeur}|table_valeur{#VALEUR})]" aria-label="[(#VAL{astra:styles_appareil_}|concat{#GET{a}|table_valeur{appareil}}|_T)] : [(#VAL{astra:styles_}|concat{#GET{etiquette}}|_T)]" placeholder="[(#VAL{astra:styles_}|concat{#GET{etiquette}}|_T)]" />
+						</BOUCLE_cote_appareil>
+						<select name="[(#GET{a}|table_valeur{nom_unite})]" aria-label="[(#VAL{astra:styles_appareil_}|concat{#GET{a}|table_valeur{appareil}}|_T)] : unité">
+							<BOUCLE_unites_appareil(DATA){source tableau, #GET{c}|table_valeur{unites}}><option value="#VALEUR"[(#VALEUR|=={#GET{a}|table_valeur{unite}}|oui)selected="selected"]>#VALEUR</option></BOUCLE_unites_appareil>
+						</select>
+					</span>
+					</BOUCLE_appareils_cotes>
+				</span>
+				</BOUCLE_cotes_appareils>
+				<BOUCLE_cotes(CONDITION){si #GET{c}|table_valeur{type}|=={quatre-cotes}|et{#GET{c}|table_valeur{appareils}|non}}>
 				<span class="astra-styles-cotes" id="s_[(#GET{c}|table_valeur{option})]">
 					<BOUCLE_cote(DATA){liste top, right, bottom, left}>
 					#SET{etiquette,#ARRAY{top,haut,right,droite,bottom,bas,left,gauche}|table_valeur{#VALEUR}}
diff --git a/inc/astra_styles_saisie.php b/inc/astra_styles_saisie.php
index 5ec3159..5ccd524 100644
--- a/inc/astra_styles_saisie.php
+++ b/inc/astra_styles_saisie.php
@@ -32,6 +32,14 @@ function astra_styles_sections(): array {
 	];
 }
 
+/**
+ * Vrai si les trois appareils d'une option sont saisis et appliqués (alignements du pied, marges
+ * des rangées), au lieu de la seule valeur ordinateur.
+ */
+function astra_styles_trois_appareils(array $d): bool {
+	return $d['type'] === 'alignement' || !empty($d['appareils']);
+}
+
 /** Clé de langue du libellé d'une option : les options d'éléments semblables la partagent. */
 function astra_styles_libelle(string $option): string {
 	$o = preg_replace(
@@ -68,9 +76,14 @@ function astra_styles_champs($styles, string $section): array {
 			'unite' => is_array($v) ? ($v['desktop-unit'] ?? 'px') : 'px',
 			'unites' => $d['unites'] ?? [],
 			'valeurs' => $d['valeurs'] ?? [],
-			// Alignements : une valeur par appareil, toutes appliquées.
-			'appareils' => $d['type'] === 'alignement' ? array_map(function ($a) use ($option, $v) {
-				return ['appareil' => $a, 'nom' => "s[$option][$a]", 'valeur' => is_array($v) ? ($v[$a] ?? '') : ''];
+			// Marges extérieures : nombres négatifs acceptés.
+			'signe' => !empty($d['signe']),
+			// Alignements, marges des rangées : une valeur (et une unité) par appareil, toutes appliquées.
+			'appareils' => astra_styles_trois_appareils($d) ? array_map(function ($a) use ($option, $v) {
+				return [
+					'appareil' => $a, 'nom' => "s[$option][$a]", 'valeur' => is_array($v) ? ($v[$a] ?? '') : '',
+					'nom_unite' => "s[$option][$a-unit]", 'unite' => is_array($v) ? ($v["$a-unit"] ?? 'px') : 'px',
+				];
 			}, ASTRA_STYLES_APPAREILS) : [],
 		];
 	}
@@ -84,8 +97,8 @@ function astra_styles_nom(string $nom, string $cle): string {
 
 /**
  * Fusionne la saisie dans les réglages enregistrés : pour une option responsive, seules la valeur
- * `desktop` et son unité changent, `tablet` et `mobile` sont gardés (sauf les alignements, saisis
- * pour les trois appareils) ; les champs d'une structure
+ * `desktop` et son unité changent, `tablet` et `mobile` sont gardés (sauf les alignements et les
+ * marges des rangées, saisis pour les trois appareils) ; les champs d'une structure
  * (côtés, fond, extras de police) absents de la saisie sont gardés. Une option jamais réglée et
  * laissée vide n'est pas enregistrée.
  */
@@ -99,10 +112,11 @@ function astra_styles_saisie_fusionner(array $existant, array $saisie): array {
 		$avant = $existant[$o] ?? null;
 		if ($d['responsive']) {
 			$x = is_array($avant) ? $avant : [];
+			$appareils = astra_styles_trois_appareils($d) ? ASTRA_STYLES_APPAREILS : ['desktop'];
 			foreach (is_array($v) ? $v : [] as $cle => $val) {
-				if ($cle === 'desktop' && is_array($val)) {
+				if (in_array($cle, $appareils, true) && is_array($val)) {
 					$x[$cle] = array_merge(is_array($x[$cle] ?? null) ? $x[$cle] : [], $val);
-				} elseif ($cle === 'desktop' || $cle === 'desktop-unit' || ($d['type'] === 'alignement' && in_array($cle, ASTRA_STYLES_APPAREILS, true))) {
+				} elseif (in_array($cle, $appareils, true) || in_array(preg_replace('/-unit$/', '', (string) $cle), $appareils, true)) {
 					$x[$cle] = $val;
 				}
 			}
@@ -111,8 +125,9 @@ function astra_styles_saisie_fusionner(array $existant, array $saisie): array {
 			$v = array_merge(is_array($avant) ? $avant : [], $v);
 		}
 		// Première saisie : gardée si une valeur saisie est renseignée (l'ordinateur seul est saisi,
-		// sauf pour les alignements, saisis sur les trois appareils).
-		$saisi = $d['responsive'] && $d['type'] !== 'alignement' ? astra_styles_ordi($v) : $v;
+		// sauf pour les alignements et les marges des rangées, saisis sur les trois appareils ; une
+		// unité seule n'est pas une valeur).
+		$saisi = !$d['responsive'] ? $v : (astra_styles_trois_appareils($d) ? array_intersect_key($v, array_flip(ASTRA_STYLES_APPAREILS)) : astra_styles_ordi($v));
 		if ($avant === null && !astra_styles_renseigne($saisi)) {
 			continue;
 		}
````

- [ ] **Étape 4 : vérifier**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Attendu : unitaires `OK (121 tests, 1700 assertions)`, intégration `OK (84 tests, 295 assertions)`

```bash
URL=http://127.0.0.1:8414 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire_styles.cjs
```

Attendu : donner d'abord un slogan au site de test (`ecrire_meta('slogan_site', 'Un site propulsé par SPIP')`) et le retirer après : `Formulaire des styles : OK`. Regarder la section « Marges des rangées » du formulaire (capture) : trois lignes par option, libellées par appareil.

- [ ] **Étape 5 : commit**

```bash
git add inc/astra_styles_saisie.php formulaires/configurer_astra_styles.html tests/unit/StylesSaisieTest.php tests/integration/StylesFormulaireTest.php
git commit -m "Marges des rangées : saisie sur les trois appareils"
```

### Tâche 4 : documentation, version 1.5.0

**Fichiers :**
- Modifier : `paquet.xml`, `README.md`, `CREDITS.md`, `tests/references/README.md`

- [ ] **Étape 1 : appliquer**

Correctif à appliquer (`git apply`) :

````diff
diff --git a/CREDITS.md b/CREDITS.md
index b259224..18ec41b 100644
--- a/CREDITS.md
+++ b/CREDITS.md
@@ -30,7 +30,7 @@ et le texte de la GPL v2 dans [licenses/GPL-2.0-or-later.txt](licenses/GPL-2.0-o
 | `inc/dynamic-css/comments-flex.php` | `css/astra-commentaires.css` | Règle d'espacement des listes ; chargement conditionné aux commentaires dans SPIP |
 | Gabarits `header.php`, `footer.php`, `page.php`, `single.php`, `archive.php`, `search.php`, `404.php`, `searchform.php`, `template-parts/` et composants du builder | Squelettes à la racine, `inclure/`, `inclure/hfb/`, `inclure/widgets/`, `formulaires/recherche.html`, `modeles/pagination_astra.html` | Reproduction et adaptation du balisage, des classes, des attributs et de la structure ; les requêtes et appels WordPress sont remplacés par les boucles, balises et formulaires SPIP |
 | Options, palette et organisation du builder | `astra_fonctions.php`, `inc/astra_hfb.php`, formulaires de configuration | Transposition des valeurs par défaut et des conventions Astra ; interface et stockage adaptés à SPIP |
-| Styles du Customizer : `inc/class-astra-dynamic-css.php` (bouton, identité, graisses des titres et `support_font_css_to_widget_and_in_editor()`), `inc/class-astra-extended-base-dynamic-css.php` (marge du bouton de l'en-tête), `inc/builder/type/header/menu/dynamic-css/dynamic.css.php`, `inc/builder/type/base/dynamic-css/{button,widget,social}/class-astra-*-component-dynamic-css.php`, `inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php` | `inc/astra_styles.php`, `inc/styles/*.php` | Transposition des règles ordinateur : sélecteurs (ceux du bouton global copiés à l'identique), ordre, conditions d'émission et valeurs par défaut ; écriture comme `astra_parse_css()` et `trim_css()` |
+| Styles du Customizer : `inc/class-astra-dynamic-css.php` (bouton, identité, graisses des titres et `support_font_css_to_widget_and_in_editor()`), `inc/class-astra-extended-base-dynamic-css.php` (marge du bouton de l'en-tête, marges des rangées du builder), `inc/builder/type/header/primary-header/dynamic-css/dynamic.css.php` (marge de l'en-tête entier), `inc/builder/type/header/menu/dynamic-css/dynamic.css.php`, `inc/builder/type/base/dynamic-css/{button,widget,social}/class-astra-*-component-dynamic-css.php`, `inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php` | `inc/astra_styles.php`, `inc/styles/*.php` | Transposition des règles ordinateur : sélecteurs (ceux du bouton global copiés à l'identique), ordre, conditions d'émission et valeurs par défaut ; écriture comme `astra_parse_css()` et `trim_css()` |
 | En-tête transparent : `inc/addons/transparent-header/classes/class-astra-ext-transparent-header-markup.php` (`is_transparent_header()`), `classes/dynamic-css/dynamic.css.php`, partie « Transparent Header » d'`inc/class-astra-dynamic-css.php` | `inc/astra_entete_transparent.php` | Transposition de la règle d'activation aux pages SPIP et des règles CSS : sélecteurs copiés à l'identique, media queries, ordre et conditions d'émission |
 | CSS dynamique rendu par WordPress avec les valeurs du modèle Garden Maintenance | `tests/references/styles/attendus/*.css`, `tests/references/transparent/attendus/*.css` | Extraits de la sortie d'Astra 4.11.0 (GPL v2 ou ultérieure), capturés par `tests/references/styles/capturer.sh` et `tests/references/transparent/capturer.sh` pour les tests d'équivalence |
 | Règles Astra appliquées aux contenus | `css/astra-spip.css` | Adaptations aux éléments produits par SPIP ; les reprises de règles WordPress/Astra sont signalées dans les commentaires |
diff --git a/README.md b/README.md
index 2e3dd64..545b8ed 100644
--- a/README.md
+++ b/README.md
@@ -143,6 +143,11 @@ avec leur effet et la propriété vérifiée, dans
 - Graisses des titres (`headings-font-weight`, `font-weight-h1` à `h6`), marge intérieure du
   bouton de l'en-tête et alignements du copyright et des icônes sociales du pied (même formulaire ;
   les alignements s'appliquent sur les trois appareils).
+- **Marges des rangées** (même formulaire) : marges intérieures et extérieures des rangées haute,
+  principale et basse de l'en-tête et du pied, du pied entier et marge extérieure de l'en-tête
+  entier (`section-*-builder-padding`, `-margin`), saisies et appliquées sur les trois appareils,
+  avec les conditions d'émission d'Astra (rangée remplie) ; les marges extérieures acceptent les
+  nombres négatifs.
 - **Drapeaux de compatibilité** (méta `astra/compatibilite`, sans formulaire : ils viennent d'un
   import) : `submenu-below-header` et `can-support-widget-and-editor-fonts`, qu'un `site.json` de
   Starter Template garde d'un site antérieur et qui changent le CSS d'Astra (hauteur de l'en-tête,
@@ -166,7 +171,7 @@ Les références WordPress du builder sont décrites dans `tests/references/READ
 références, un WordPress de test et le SPIP de test servi par un serveur web :
 
     WP=… PW=… SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404
-    WP=… PW=… SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404 R14-bouton-mobile R16-transparent-page R17-transparent-global R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat
+    WP=… PW=… SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404 R14-bouton-mobile R16-transparent-page R17-transparent-global R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat R24-pied-marges R24-marges-toutes R24-marges-rangees-vides
     WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404
     URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire.cjs
     RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
@@ -176,13 +181,13 @@ références, un WordPress de test et le SPIP de test servi par un serveur web :
 - `comparer_html.php` compare l'ossature HTML de l'en-tête, du panneau mobile et du pied de
   page à celle de WordPress : sans argument, pour les configurations de référence du builder
   (R0–R5, R12, R13, R15 et R18) et les trois de pages (P1–P3, qui comparent aussi le contenu et
-  les classes du `body`) ; les références qui posent des styles (R6–R11, R14, R16, R17, R19–R23)
+  les classes du `body`) ; les références qui posent des styles (R6–R11, R14, R16, R17, R19–R24)
   se passent en arguments, comme dans la deuxième commande. Sont comparés : balises, classes et
   identifiants des rangées, des zones et des éléments, et du premier niveau à l'intérieur de
   chaque élément. Les contenus, les formulaires et les SVG ne sont pas comparés : ce n'est pas
   une identité complète du HTML (R13 : deux différences de nommage attendues, voir
   `tests/references/README.md`) ;
-- `comparer_captures.sh` compare au pixel près (sans argument : R0–R23 ; P1–P3 en arguments)
+- `comparer_captures.sh` compare au pixel près (sans argument : R0–R24 ; P1–P3 en arguments)
   l'en-tête, le pied de page et le menu mobile ouvert, sur ordinateur et sur mobile (seuil : 1 % de pixels différents, variable `SEUIL`) ; les
   images et leurs différences sont enregistrées dans `tests/comparaison/sorties/`. Une référence
   peut aussi lister des styles calculés à comparer (`styles` dans `tests/references/wp/<conf>.json`) :
@@ -193,7 +198,7 @@ références, un WordPress de test et le SPIP de test servi par un serveur web :
   R12–R15 couvrent les répartitions par appareil, les widgets menu, le bouton du menu mobile et
   l'icône Google Reviews ; R16 et R17 l'en-tête transparent ; R18–R23 les drapeaux de
   compatibilité, les graisses des titres, la marge du bouton de l'en-tête et les alignements du
-  pied ;
+  pied ; R24 les marges des rangées et leurs conditions d'émission ;
 - `tests/e2e/formulaire_styles.cjs` vérifie le formulaire « Couleurs et typographie » de bout en
   bout (enregistrement, valeur refusée, site public) ;
 - `tests/e2e/formulaire.cjs` vérifie le formulaire de configuration de bout en bout ;
diff --git a/paquet.xml b/paquet.xml
index 28d61e5..603ed99 100644
--- a/paquet.xml
+++ b/paquet.xml
@@ -1,7 +1,7 @@
 <paquet
 	prefix="astra"
 	categorie="squelette"
-	version="1.4.0"
+	version="1.5.0"
 	schema="1.0.0"
 	etat="test"
 	compatibilite="[4.2.0;5.0.*]"
diff --git a/tests/references/README.md b/tests/references/README.md
index b5c6884..b61455f 100644
--- a/tests/references/README.md
+++ b/tests/references/README.md
@@ -90,6 +90,9 @@ Vérifié le 2026-10-22 : HTML et CSS identiques.
 | R22-pied-alignements-ordinateur | Alignements réglés pour l'ordinateur seul : tablette et mobile prennent sa valeur |
 | R22-pied-alignements-tablette | Alignements réglés pour la tablette seule : sur ordinateur, copyright hérité et icônes sociales centrées |
 | R23-garden-compat | Valeurs de Garden de R18 à R22 ensemble (deux drapeaux, graisses, marge vide, alignements) |
+| R24-pied-marges | Marges intérieures des rangées principale et basse du pied de Garden (`styles/valeurs/marges-pied.json`) ; marges et largeur des colonnes comparées aux trois largeurs |
+| R24-marges-toutes | Les quinze options de marges (valeurs distinctes par appareil, unités mêlées, côtés en partie vides, marges extérieures négatives) ; rangées haute et basse de l'en-tête remplies comme dans R1, rangée haute du pied d'un texte |
+| R24-marges-rangees-vides | Mêmes marges, tablette sans unité pour la rangée principale de l'en-tête ; rangée haute de l'en-tête remplie sur mobile seulement, rangée basse vide, rangée haute du pied vide, rangée principale du pied remplie en zone 6 seulement (non affichée, comme dans Astra) |
 | P1-page | Page « Notre jardin » (`/?pagename=notre-jardin`), menu de pages à deux niveaux (`ref_menu_pages()`) |
 | P2-accueil | Même menu, page « Notre jardin » en page d'accueil statique (`/`) |
 | P3-page-pleine | Page « Plein écran », titre désactivé, conteneur `full-width-container` |
@@ -151,6 +154,17 @@ titres des widgets h1 à h6, nom du site) : `css/main.min.css` puis ces règles
 `css/main.min.css`, `css/astra-dynamic.css` et le CSS produit côté SPIP ; les deux doivent être
 égales.
 
+## Marges des rangées : attendus
+
+`capturer.sh` applique les références R24 (placement propre à chacune) et écrit, pour les valeurs
+`marges-pied`, `marges-toutes` et `marges-rangees-vides`, les règles de marges du CSS complet
+(`marges.py` : règles des sélecteurs des rangées, du pied entier et de l'en-tête entier qui ne
+déclarent que des marges) dans `attendus/<valeurs>.css`. `tests/unit/StylesMargesTest.php` exige
+que le CSS produit côté SPIP, avec le même placement, donne exactement ces règles, dans l'ordre
+(l'espace après la virgule des sélecteurs, qu'Astra ne retire pas ici, mise à part) : c'est la
+preuve des conditions d'émission, car une règle émise pour une rangée vide ne se voit pas dans les
+captures.
+
 Les références R18 à R23 passent par les clés `compatibilite` (méta `astra/compatibilite`) et
 `textes` (texte d'une page unique, remis à celui de l'installation à chaque configuration) de
 `tests/comparaison/configurations.php`.
````

- [ ] **Étape 2 : vérifier l'ensemble**

```bash
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php
for f in $(git diff --name-only origin/main -- '*.php' ':!tests'); do php7.4 -l "$f"; done
WP=… PW=… SPIP_CLI=… tests/references/capturer.sh R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google R16-transparent-page R17-transparent-global R18-compat-sous-menu R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat R24-pied-marges R24-marges-toutes R24-marges-rangees-vides P1-page P2-accueil P3-page-pleine
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414
SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8414 R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google R16-transparent-page R17-transparent-global R18-compat-sous-menu R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat R24-pied-marges R24-marges-toutes R24-marges-rangees-vides
WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8414 R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee R12-pied-appareils R13-pied-widgets-menus R14-bouton-mobile R15-sociaux-google R16-transparent-page R17-transparent-global R18-compat-sous-menu R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat R24-pied-marges R24-marges-toutes R24-marges-rangees-vides P1-page P2-accueil P3-page-pleine
```

Attendu : unitaires `OK (121 tests, 1700 assertions)`, intégration `OK (84 tests, 295 assertions)` ; `No syntax errors detected` pour chaque fichier ; `comparer_html.php` : tout identique sauf les deux nommages de R13 (`tests/references/README.md`) ; `comparer_captures.sh` : 273 vues et jeux de styles, tous sous le seuil (plus grand écart 0,73 %, R13, pied sur mobile, comme en 1.4.0), styles calculés égaux aux trois largeurs.

- [ ] **Étape 3 : commit**

```bash
git add paquet.xml README.md CREDITS.md tests/references/README.md
git commit -m "Version 1.5.0 : marges des rangées du builder"
```

## Effet attendu sur Garden

Une fois la version 1.5.0 publiée et posée par un lot de `garden-spip` (options `section-primary-footer-builder-padding` et `section-below-footer-builder-padding` du `site.json`, `astra_min` 1.5.0), la ligne « Pied : marges intérieures… » de « Écarts connus » du README de Garden est à remesurer et à retirer : marges de la rangée principale de 80 px en haut (1280 px), 80 px de chaque côté (768 px), 60 / 30 / 30 / 30 px (390 px), rangée basse à 20 px (768 et 390 px), colonnes du pied à la largeur du modèle. Ce lot fera l'objet de son propre plan.

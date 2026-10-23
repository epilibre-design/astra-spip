# Compléments du builder — spécification

Date : 2026-10-23. Statut : direction validée (Gilles, Codex) ; précisions de la relecture intégrées ;
décisions de Gilles du 24 octobre reportées (plan `docs/superpowers/plans/2026-10-23-complements-builder.md`).

## Objectif

Compléter le Header/Footer Builder d'Astra SPIP avec les réglages d'Astra 4.11.0 que le modèle
Garden Maintenance utilise et qui manquent (`garden-spip/README.md`, « Écarts connus ») :

1. répartition des colonnes du pied **par appareil** (tablette, mobile) ;
2. **séparateurs** des rangées du pied (épaisseur, couleur) ;
3. bloc **menu** dans les zones de widgets ;
4. réseau **Google Reviews** dans les icônes sociales ;
5. **style du bouton du menu mobile** (minimal, rempli, contour ; couleur, bordure, rayon,
   taille de l'icône).

Deuxième des trois specs issues des écarts de Garden. Elle suit les principes de la spec
« Styles du Customizer » (`2026-10-23-styles-customizer-design.md`) : rien ne change sans
réglage ; avec un réglage, balisage et CSS d'Astra 4.11.0, mêmes sélecteurs et mêmes conditions
d'émission ; aucune chaîne libre dans le CSS ; valeurs responsives d'Astra conservées entières.

## 1. Répartition par appareil

Valeurs du modèle : `hb-footer-layout` = `{"desktop": "4-equal", "tablet": "full", "mobile": "full"}`,
`hba-footer-layout` et `hbb-footer-layout` = `{"desktop": "2-equal", "tablet": "2-equal", "mobile": "full"}`.

Aujourd'hui, `astra/hfb/rangees/pied_<rangée>/repartition` est une seule valeur, appliquée à
l'ordinateur et à la tablette (`ast-builder-grid-row-tablet-<répartition>`) ; le mobile est
toujours `full` (`inc/astra_hfb.php`, `astra_hfb_calculer_pied`).

- `repartition` accepte aussi la structure d'Astra `{"desktop", "tablet", "mobile"}` ; une valeur
  simple reste valide et vaut pour l'ordinateur et la tablette, mobile `full` (comportement actuel,
  donc rendu inchangé pour les configurations existantes).
- Les classes `ast-builder-grid-row-<desktop>`, `ast-builder-grid-row-tablet-<tablet>`,
  `ast-builder-grid-row-mobile-<mobile>` sont celles qu'Astra produit
  (`inc/builder/type/footer/*/class-astra-*-footer*.php` et leurs `dynamic-css`). Valeurs acceptées
  par appareil : les répartitions d'Astra pour le nombre de colonnes de la rangée, plus `full`.
- Formulaire du builder : un sélecteur de répartition par appareil.
- La tablette accepte aussi les répartitions de l'ordinateur pour le nombre de colonnes
  (`4-lheavy`, `4-rheavy` en plus des choix d'Astra) : une valeur simple de la version 1.2.0 s'y
  applique, et le formulaire doit la reproposer pour qu'un enregistrement ne change rien au rendu.
- Les configurations SPIP des références des styles R6–R11 passent à la structure par appareil,
  celle de leur pendant WordPress (`tests/references/styles/appliquer.php`).
- Preuve : `tests/e2e/repartitions.cjs` étendu (répartitions tablette et mobile), et une référence
  WordPress `R12-pied-appareils` reprenant les valeurs de Garden.

## 2. Séparateurs des rangées du pied

Valeurs du modèle : `hb-footer-main-sep` = 0 (rangée principale, couleur `#e6e6e6`),
`hbb-footer-separator` = 1 avec `hbb-footer-top-border-color` = `var(--ast-global-color-4)`,
`hba-footer-separator` vide.

- Nouveaux réglages de rangée `astra/hfb/rangees/pied_<rangée>/{separateur, couleur_separateur}` :
  épaisseur en px (entier 0–20, vide = défaut d'Astra) et couleur (type couleur de la spec des
  styles, dont ce point dépend). Correspondance : `pied_primary` ↔ `hb-footer-main-sep(-color)`, `pied_above` ↔
  `hba-footer-separator` / `hba-footer-top-border-color`, `pied_below` ↔ `hbb-footer-separator` /
  `hbb-footer-top-border-color`.
- CSS porté des `dynamic.css.php` des trois rangées du pied d'Astra 4.11.0, émis seulement si la
  valeur diffère du défaut, comme les réglages de rangée actuels (`astra_hfb_css_rangees`).
- Preuve : la référence R5-rangees est complétée (séparateurs du pied), avec comparaison des
  styles calculés (`border-top-width`, `border-top-color`) à 1280, 768 et 390 px.

## 3. Bloc menu dans les widgets

Dans le modèle, les widgets 2 et 3 du pied contiennent un widget « Menu de navigation »
(`nav_menu`). Astra SPIP propose les blocs `recherche`, `sous_rubriques`, `articles_recents`,
`forums_recents`, `mots_cles`, `texte` (`astra_hfb_blocs`).

- Nouveau bloc `menu`, ajouté à `astra_hfb_blocs()`. Stockage : dans les réglages du widget,
  `astra/hfb/elements/<groupe>/widget-N`, à côté de `blocs`, `titre` et `texte`, une clé
  `menu` = `{"source": …, "id": …}` ; défaut dans `astra_hfb_defauts()` :
  `{"source": "racine", "id": 0}`, fusionné par `astra_hfb_normaliser()` comme les autres réglages
  d'élément. `source` accepte les sources des éléments menu (`racine`, `rubrique:<id>`,
  `groupe_mots:<id>`, `pages`, validées par la même liste que pour `menu-1`) ; `id` est un entier.
  Le bloc affiche deux niveaux, comme le widget `nav_menu` de WordPress (mesuré sur R13 : lien
  parent `aria-expanded`, flèche sans bouton). Un widget sans `menu` dans `blocs` ignore ce réglage.
- Balisage : celui du widget `nav_menu` de WordPress sous Astra (`<nav class="menu-…-container"
  aria-label="…"><ul class="menu">…</ul></nav>` dans la zone de widget), relevé sur une référence
  WordPress.
- Squelette `inclure/widgets/menu.html` ; réutilise `inclure/hfb/menu-items.html` pour la liste.
- Preuve : référence WordPress `R13-pied-widgets-menus` (deux widgets menu dans le pied),
  comparaison HTML et captures ; test d'intégration de chaque source. Deux différences de nommage
  attendues, documentées dans `tests/references/README.md` : identifiant de la section
  (`nav_menu-2` dans WordPress, numéro d'instance du widget ; `nav_menu-pied-2` dans SPIP) et classe
  de la `nav` (`menu-<slug du menu>-container` ; `menu-<source>-container`).

## 4. Google Reviews

Astra 4.11.0 propose `google_reviews` dans ses icônes sociales (`assets/svg/svgs.json`,
sélecteur du Customizer). Valeurs du modèle : réseau activé, couleur de marque `#dc4e41`.

- `astra_hfb_reseaux()` gagne `google_reviews` (libellé « Google Reviews », couleur `#dc4e41`,
  celle du sélecteur d'Astra) ; son SVG est repris de `assets/svg/svgs.json`.
- **Provenance** : avant reprise, établir l'origine du SVG `google_reviews` d'Astra (dessin
  propre à Astra ou icône d'une bibliothèque), et le recenser dans `CREDITS.md` avec les autres
  SVG d'Astra, comme le prévoit la section qui leur est consacrée. Si l'origine ne peut être
  établie, le signaler comme les SVG dont l'attribution est limitée.
- Provenance établie : glyphe `google` (U+F1A0) de Font Awesome 4.7.0, par Dave Gandy, sous
  SIL OFL 1.1, converti par Astra (coordonnées divisées par 64) ; recensé dans `CREDITS.md`,
  notice dans `licenses/OFL-1.1-fontawesome-4.7.0.txt`.
- Preuve : référence dédiée `R15-sociaux-google` (R1 à R4 alimentent `extraire_css.py`) ;
  comparaison HTML, captures et styles calculés.

## 5. Style du bouton du menu mobile

Valeurs du modèle : `mobile-header-toggle-btn-style` = `outline`, `mobile-header-toggle-btn-color` et
`mobile-header-toggle-border-color` = `var(--ast-global-color-0)`,
`mobile-header-toggle-btn-border-size` = 2 px sur chaque côté,
`mobile-header-toggle-border-radius-fields` = 100 (ordinateur), `mobile-header-toggle-icon-size` = 20.

- Réglages enregistrés sous ces noms d'options d'Astra, avec les types et la normalisation de la
  spec des styles (méta `astra/styles`, groupe `menu-mobile`) ; `btn-style` est une énumération
  (`minimal`, `fill`, `outline`).
- Balisage : la classe `ast-mobile-menu-trigger-<style>` du bouton
  (`inclure/hfb/mobile-trigger.html`, aujourd'hui toujours `minimal`) ; CSS porté de
  `inc/builder/type/header/mobile-trigger/dynamic-css/dynamic.css.php`.
- Dépend de la spec des styles (registre, normalisation, formulaire) : à implémenter après elle.
- Preuve : référence `R14-bouton-mobile` (valeurs de Garden), captures et styles calculés à 390 px.

## Tests

Mêmes critères que la spec des styles : sans réglage, R0–R5 et P1–P3 identiques ; avec réglage,
équivalence avec les références WordPress (captures au seuil de 1 %, styles calculés exacts,
1280, 768 et 390 px) ; tests unitaires de normalisation (valeurs simples et structures d'Astra
conservées, valeurs invalides retirées) et de sécurité. Les références nouvelles n'entrent pas dans
`extraire_css.py`.

## Version

Ces réglages forment la version 1.3.0 d'Astra SPIP (nouveaux réglages, aucune rupture).

## Questions tranchées

1. Ordre de mise en œuvre : la spec des styles est fusionnée (v1.2.0) ; les cinq points suivent
   l'ordre du plan.
2. Bloc menu : deux niveaux, comme WordPress (décision du 24 octobre).

Décidé à la relecture : seul Google Reviews est ajouté aux réseaux sociaux (les autres réseaux du
sélecteur d'Astra absents d'Astra SPIP restent hors périmètre).

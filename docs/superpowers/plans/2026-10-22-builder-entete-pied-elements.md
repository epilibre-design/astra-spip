# Builder d'en-tête et de pied de page — squelettes, formulaire et comparaisons (second plan)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rendre l'en-tête (ordinateur, mobile, panneau) et le pied de page à partir de la configuration du builder, avec le balisage d'Astra relevé dans les références, puis le formulaire de configuration et les comparaisons automatiques avec WordPress.

**Architecture:** `inclure/header.html` et `inclure/footer.html` bouclent sur `astra_hfb_rangees()` et `astra_hfb_panneau()` (premier plan, tâche 2). Une rangée d'en-tête est rendue par `inclure/hfb/rangee-ordinateur.html` ou `rangee-mobile.html`, qui incluent pour chaque élément `inclure/hfb/<element>.html`. Les réglages d'un élément sont lus par le filtre `astra_hfb_reglages(groupe, id)`. Le CSS que le builder d'Astra ajoute est extrait des références dans `css/astra-hfb.css`.

**Tech Stack:** SPIP 4.4, PHP 7.4+ (plugin), PHPUnit 11.5 (tests d'intégration), Playwright, WordPress de référence.

Premier plan : `docs/superpowers/plans/2026-10-22-builder-entete-pied.md`. Spécification : `docs/superpowers/specs/2026-10-22-builder-entete-pied-design.md`. Références : `tests/references/README.md` (classes et balisage relevés).

## Global Constraints

Celles du premier plan s'appliquent, notamment : PHP 7.4 pour le plugin ; SPIP 4.4 seul vérifié ; `main.min.css` et `frontend.min.js` inchangés ; rendu par défaut identique au rendu actuel ; commits sans trailer ; icônes Font Awesome créditées (CC BY 4.0).

## Source du balisage

Chaque squelette d'élément recopie le balisage d'Astra relevé dans les captures
(`tests/references/sorties/Rn/accueil.html`) : contenus remplacés par les données SPIP, attributs
propres à WordPress (`data-section` conservés, car le CSS d'Astra ne les utilise pas mais ils ne
gênent pas ; identifiants `menu-item-N` remplacés par `menu-item-rubrique-N`). Les extraits de
référence sont :

| Élément | Configuration de référence | Repère dans le HTML capturé |
|---|---|---|
| rangées d'en-tête ordinateur | R1 (haute, principale, basse), R2 (centre) | `<div id="ast-desktop-header"` |
| rangées d'en-tête mobile | R1, R2 | `<div id="ast-mobile-header"` |
| panneau déroulant | R1 | `<div class="ast-mobile-header-content` |
| panneau latéral | R2 | `<div id="ast-mobile-popup-wrapper">` (entre `#content` et `<footer>`) |
| texte, icônes sociales (en-tête) | R1 | `ast-header-html-1`, `ast-header-social-1-wrap` |
| recherche, bouton | R1 | `ast-header-search`, `ast-header-button-1` |
| compte, widget d'en-tête, texte 2 | R2 | `ast-header-account`, `header-widget-area` |
| rangées du pied, widgets, texte, icônes, menu, copyright | R3, R4 | `<footer` |

## Fichiers

| Fichier | Rôle |
|---|---|
| `astra_fonctions.php` | `astra_hfb_rangee()`, `astra_hfb_reglages()`, `astra_hfb_reseau()` |
| `inc/astra_hfb.php` | Défauts des éléments (`social-icons-1`, `menu` du pied), table des réseaux |
| `inc/astra_icones.php` | Tracés SVG des icônes sociales (Font Awesome Free, CC BY 4.0) |
| `inclure/header.html` | En-tête : boucles sur les rangées ordinateur et mobile, panneau déroulant |
| `inclure/footer.html` | Panneau latéral, rangées du pied |
| `inclure/hfb/rangee-ordinateur.html`, `rangee-mobile.html`, `zone.html` | Rangées et zones d'en-tête |
| `inclure/hfb/logo.html`, `menu.html`, `mobile-menu.html`, `mobile-trigger.html`, `html.html`, `button.html`, `search.html`, `social-icons.html`, `widget.html`, `account.html`, `copyright.html` | Éléments |
| `inclure/hfb/menu-items.html` | Entrées de menu selon la source (racine, rubrique, groupe de mots, pages) |
| `inclure/widgets/*.html` | Blocs de la barre latérale, réutilisés par les zones de widgets |
| `inclure/sidebar.html` | Inclut les blocs de `inclure/widgets/` |
| `css/astra-hfb.css`, `tests/references/extraire_css.py` | CSS du builder extrait des références |
| `formulaires/configurer_astra_hfb.html`, `.php` | Formulaire CVT |
| `prive/squelettes/contenu/configurer_astra_hfb.html`, `paquet.xml` | Page de configuration et entrée de menu |
| `lang/astra_fr.php`, `lang/astra_en.php` | Chaînes |
| `tests/integration/HfbRenduTest.php`, `HfbFormulaireTest.php` | Tests d'intégration |
| `tests/comparaison/` | Comparaison HTML et visuelle SPIP/WordPress |

---

### Task 4: Rangées et éléments de l'en-tête ordinateur

**Interfaces:**
- Consumes: `astra_hfb_config()`, `astra_hfb_rangees()` (premier plan).
- Produces:
  - `astra_hfb_rangee(string $rangee, string $contexte): array` — la rangée `$rangee` de `astra_hfb_rangees($contexte)`, ou `[]`.
  - `astra_hfb_reglages(string $groupe, string $id): array` — réglages normalisés de l'élément (`elements/<groupe>/<id>`), `[]` si aucun.
  - Squelettes d'éléments, appelés avec `{id}{numero}{groupe}{appareil}` (`appareil` : `ordinateur`, `mobile`, `panneau`) et `{id_rubrique}{id_secteur}{type_page}`.

- [ ] **Step 1: Tests d'intégration du rendu (échec attendu)** — `tests/integration/HfbRenduTest.php` : pour chaque configuration (défaut ; R1 transposée : rangée haute `html-1` à gauche et `social-icons-1` à droite, principale `logo` / `menu-1`, `search`, `button-1`, basse `menu-2` au centre ; R2 transposée : `menu-1` / `logo` au centre / `account`, `search`), écrire la configuration avec `ecrire_config('astra/hfb', …)`, calculer `recuperer_fond('inclure/header', ['type_page' => 'sommaire'])` et vérifier par XPath (DOMDocument) : présence des rangées (`.ast-above-header-wrap`, `.ast-below-header-wrap`), classes de grille (`ast-builder-grid-row-has-sides`, `ast-grid-center-col-layout`, `ast-builder-grid-row-no-center`, `ast-grid-center-col-layout-only`), sections vides `left-center`/`right-center` présentes seulement quand il y a un centre, ordre des éléments dans une zone, classes racines des éléments (tableau de `tests/references/README.md`), texte libre rendu par `propre`, lien du bouton, rubriques du menu avec sous-menu.
- [ ] **Step 2: Fonctions** `astra_hfb_rangee()` et `astra_hfb_reglages()` dans `astra_fonctions.php` ; défauts d'éléments complétés dans `inc/astra_hfb.php` (`social-icons-1` : Facebook, Twitter, Instagram sans adresse, comme Astra ; `menu` du pied : `disposition` horizontale) avec tests unitaires.
- [ ] **Step 3: Squelettes** `inclure/hfb/rangee-ordinateur.html` et `zone.html` (structure de `header-row.php` : wrap, barre, conteneur, grille, sections gauche/centre-gauche, centre, centre-droit/droite), puis les éléments `logo`, `menu` (+ `menu-items`), `html`, `button`, `search`, `social-icons` (+ `inc/astra_icones.php`), `widget` (+ découpage de `sidebar.html` en `inclure/widgets/*.html`), `account`. Refondre `inclure/header.html` (boucle `DATA` sur `astra_hfb_rangees('entete_ordinateur')`, `data-toggle-type=menu_mobile`).
- [ ] **Step 4: CSS** — `tests/references/extraire_css.py` produit `css/astra-hfb.css` : instructions du CSS dynamique de R1 à R4 absentes de celui de R0, dans l'ordre de première apparition ; chargé dans `inclure/head.html` après `astra-dynamic.css`.
- [ ] **Step 5: Vérifier** — `composer tests-unit`, `composer tests-integration` ; rendu par défaut du site de test (8402) identique hors déclencheur du cron ; captures de la configuration R1 transposée comparées à `R1-entete-complet/ordinateur.png`.
- [ ] **Step 6: Commit.**

### Task 5: En-tête mobile et panneau

- [ ] **Step 1: Tests (échec attendu)** — mêmes configurations, partie mobile : rangées mobiles (classes de `mobile-header-row.php`), `mobile-trigger`, panneau déroulant dans `#ast-mobile-header` (`.ast-mobile-header-content`) quand `menu_mobile=dropdown`, panneau latéral (`#ast-mobile-popup-wrapper`) rendu par `inclure/footer.html` quand `off-canvas`, aucun panneau sans `mobile-trigger`, `data-type` du `#ast-mobile-header`.
- [ ] **Step 2: Squelettes** `rangee-mobile.html`, `mobile-trigger.html`, `mobile-menu.html` ; panneau dans `header.html` et `footer.html`.
- [ ] **Step 3: Vérifier** — tests ; capture Playwright mobile (menu ouvert) des configurations R1 et R2 transposées, comparée aux références ; ouverture effective du menu (déroulant et latéral) avec `frontend.min.js`.
- [ ] **Step 4: Commit.**

### Task 6: Pied de page à colonnes

- [ ] **Step 1: Tests (échec attendu)** — configurations R3 et R4 transposées : rangées, classes de répartition, nombre de colonnes, éléments du pied (`html` avec la classe `footer-widget-area … ast-footer-html-N`, `social-icons` pied, `widget` pied, `menu` du pied à un niveau, `copyright`).
- [ ] **Step 2: Squelettes** — boucle `DATA` sur `astra_hfb_rangees('pied')` dans `footer.html` ; variantes « pied » des éléments ; `copyright.html` (texte actuel).
- [ ] **Step 3: Vérifier** — tests ; captures comparées à R3 et R4.
- [ ] **Step 4: Commit.**

### Task 7: Formulaire de configuration

- [ ] **Step 1: Tests (échec attendu)** — `tests/integration/HfbFormulaireTest.php` : `formulaires_configurer_astra_hfb_charger_dist()` renvoie les emplacements actuels ; `verifier` refuse zone inconnue, élément hors contexte, doublon, colonne au-delà du nombre, panneau sans `mobile-trigger` ; `traiter` enregistre toutes les zones explicitement (listes vides comprises) et les réglages d'éléments par groupe.
- [ ] **Step 2: Formulaire** CVT (pas de `_meta_casier` implicite : `charger`/`verifier`/`traiter` explicites, piloté par `astra_hfb_registre()`), page privée, entrée de menu dans `paquet.xml`, chaînes fr/en.
- [ ] **Step 3: Test de bout en bout** Playwright : connexion `admin`, saisie d'une disposition, enregistrement, vérification sur le site public.
- [ ] **Step 4: Commit.**

### Task 8: Comparaison HTML automatique

- [ ] Script `tests/comparaison/comparer_html.php` : extrait `#masthead`, le panneau et `#colophon` des pages SPIP et WordPress pour une configuration transposée, normalise (contenus textuels, URL, attributs `href`/`src`/`id` de menus, attributs `aria-label` traduits, espaces), compare la suite des balises et de leurs classes ; échoue sur une différence. Configurations transposées R0 à R4 décrites dans `tests/comparaison/configurations.php`.

### Task 9: Comparaison visuelle automatique et README

- [ ] Script `tests/comparaison/comparer_captures.cjs` : captures SPIP (mêmes tailles et menu ouvert que les références), comparaison pixel à pixel de l'en-tête et du pied avec la référence (seuil documenté), image des différences conservée. Section du README sur le builder, mention Font Awesome dans `README.md` et `paquet.xml`.

# En-tête transparent — spécification

Date : 2026-10-23. Statut : précisions de la première relecture intégrées ; décisions de Gilles du
24 octobre reportées (plan `docs/superpowers/plans/2026-10-23-entete-transparent.md`).

## Objectif

Reprendre l'en-tête transparent d'Astra 4.11.0 (`inc/addons/transparent-header/`) : l'en-tête se
superpose au début du contenu, avec ses propres couleurs. Les six pages du modèle Garden
Maintenance l'activent page par page (`theme-transparent-header-meta` = `enabled`), alors que le
réglage global est désactivé (`transparent-header-enable` = 0).

Troisième des trois specs issues des écarts de Garden. Mêmes principes que la spec « Styles du
Customizer » : rien ne change sans réglage ; avec un réglage, balisage et CSS d'Astra 4.11.0 sous
les mêmes conditions ; valeurs d'Astra conservées sous leurs noms ; aucune chaîne libre en CSS.

## Quand l'en-tête est transparent

Reprise de `Astra_Ext_Transparent_Header_Markup::is_transparent_header()` :

1. réglage de la page (pages uniques, et articles si retenu, voir les questions) :
   `enabled` → transparent, `disabled` → non transparent, `default` → règle globale ;
2. règle globale : `transparent-header-enable`, sauf exclusions d'Astra transposées aux pages SPIP :
   | Option d'Astra | Page SPIP |
   |---|---|
   | `transparent-header-disable-archive` (et ses variantes 404, recherche, archives) | `rubrique`, `mot`, `auteur`, `recherche`, `404` |
   | `transparent-header-disable-latest-posts-index` | `sommaire` avec les derniers articles |
   | `transparent-header-disable-index` | (page des articles distincte de l'accueil : sans équivalent SPIP aujourd'hui) |
   | `transparent-header-disable-page` | pages uniques |
   | `transparent-header-disable-posts` | articles |
Une fonction pure `astra_entete_transparent_actif(array $styles, string $contexte, string $reglage_page): bool`
porte cette règle (contextes : `page`, `article`, `accueil_articles`, `rubrique`, `mot`, `auteur`,
`recherche`, `404`) ; elle est testée cas par cas. `astra_entete_transparent(string $type, $id, $accueil)`,
avec les paramètres d'`astra_body_class()`, lit la configuration et dit si **la page a l'en-tête
transparent** (classe du body, CSS du module).

**Restriction par appareil.** `transparent-header-on-devices` (`desktop`, `mobile`, `both`) ne
change pas ce booléen : comme dans Astra, elle place les règles du module dans les media queries
de l'appareil concerné (`dynamic.css.php` du module), et c'est le CSS qui limite l'effet.

**Accueil statique.** Quand l'accueil affiche une page unique (`astra/accueil_type` = `page`), le
contexte reste `sommaire` mais la règle est celle **de la page choisie** : son réglage `transparent`
de `astra/pages/<id>`, puis la règle globale avec l'exclusion `transparent-header-disable-page`
(et non `-latest-posts-index`, réservée à l'accueil qui liste les derniers articles). Le gabarit
transmet donc l'identifiant de la page et `accueil=oui` (déjà fournis par `sommaire.html` à
`page_unique.html`).

## Données

- Réglages globaux dans la méta `astra/styles` (spec des styles : registre, types, normalisation),
  groupe `entete-transparent` : `transparent-header-enable`, les options `-disable-*`,
  `transparent-header-on-devices`, et les couleurs lues par
  `inc/addons/transparent-header/classes/dynamic-css/dynamic.css.php` :
  `transparent-header-bg-color-responsive`, `hba-` et `hbb-transparent-header-bg-color-responsive`,
  `transparent-header-color-site-title-responsive`, `-h-site-title-responsive`,
  `transparent-menu-{color,h-color,bg-color}-responsive`,
  `transparent-submenu-{color,h-color,bg-color}-responsive`,
  `transparent-content-section-{text-color,link-color,link-h-color}-responsive`,
  `transparent-header-main-sep`, `transparent-header-main-sep-color`.
- Drapeau `transparent-header-default-border` (booléen, vrai par défaut) : fixe le défaut du
  séparateur, 0 px sur une installation récente, aucun s'il est faux (cas de Garden ;
  `astra_get_transparent_header_default_value()`). Faux s'entend `false`, `0` ou « 0 », valeur que
  le formulaire « Couleurs et typographie » enregistre : un enregistrement sans modification garde
  son effet.
- Valeurs tablette et mobile des couleurs appliquées, comme Astra le fait pour ce module (media
  queries de la restriction par appareil, repli des fonds de la tablette sur l'ordinateur et du
  mobile sur la tablette).
- Réglage par page : nouvelle clé `transparent` dans `astra/pages/<id>` (réglages par page de la
  version 1.1.0) : `defaut`, `active`, `desactive` ; formulaire `editer_astra_page` complété.
  Limité aux pages uniques ; les articles suivent le réglage global et ses exclusions.
- Logo propre à l'en-tête transparent (`different-transparent-logo`, `transparent-header-logo`) :
  hors périmètre (Garden ne l'utilise pas) ; les règles de largeur de ce logo, qu'Astra émet
  toujours, sont émises à l'identique ; les classes de body d'Astra
  (`ast-inherit-site-logo-transparent`, déjà produite) restent celles du cas sans logo propre.

## Balisage et CSS

- Body : classe `ast-theme-transparent-header` ajoutée par `astra_body_class` quand
  `astra_entete_transparent()` est vrai, comme `add_body_class()` d'Astra.
- CSS : règles portées de `dynamic.css.php` et `header-sections-dynamic.css.php` du module,
  émises seulement quand l'en-tête est transparent (condition d'Astra), avec les couleurs posées ;
  y compris le positionnement de l'en-tête au-dessus du contenu, que les feuilles fixes d'Astra
  SPIP ne contiennent pas aujourd'hui (seules deux règles `.ast-theme-transparent-header` y
  figurent).
- Emplacement : bloc `<style id="astra-transparent-inline-css">` après celui des styles, émis
  seulement s'il n'est pas vide.
- Neutralisation : `css/astra-dynamic.css` donne 60 px de marge à `#primary` au-delà de 1200 px,
  règle qu'Astra n'émet pas sur une page à en-tête transparent ; le module la défait
  (`.ast-theme-transparent-header.ast-plain-container.ast-no-sidebar #primary{margin-top:0;margin-bottom:0;}`).
- Couleurs propres aux éléments de l'en-tête transparent (texte libre, recherche, icônes, bouton,
  séparateur vertical, compte, couleurs du bouton du menu mobile) : non reprises, vides dans Garden ;
  seule la règle que cette partie d'Astra émet sans réglage (fond transparent du bouton du menu
  mobile) l'est. `header-sections-dynamic.css.php` ne concerne que l'en-tête sans builder.

## Tests

- Unitaires : `astra_entete_transparent()` pour chaque type de page et chaque combinaison
  réglage global / réglage de page / exclusions, dont l'accueil statique (réglage de la page
  choisie) et l'accueil des derniers articles.
- Restriction par appareil : CSS produit dans les media queries attendues pour `desktop`,
  `mobile` et `both`.
- Sans réglage : R0–R5 et P1–P3 identiques.
- Attendus WordPress de quatre variantes (`tests/references/transparent/` : Garden, ordinateur
  seul, mobile seul, toutes les couleurs), comparés règle à règle.
- Aller-retour du formulaire « Couleurs et typographie » avec les valeurs de Garden : CSS inchangé.
- Références WordPress : `R16-transparent-page` (page avec `theme-transparent-header-meta` =
  `enabled`, réglage global désactivé, couleurs de Garden) et `R17-transparent-global` (réglage
  global, page d'archive exclue) ; `comparer_captures.sh` compare aussi le contenu d'une
  référence qui affiche une page ; comparaison HTML (classes du body), captures et styles calculés
  à 1280, 768 et 390 px, y compris la position de l'en-tête par rapport au premier bloc du
  contenu.

## Questions tranchées (24 octobre)

1. Réglage par page limité aux pages uniques.
2. Logo propre à l'en-tête transparent : hors périmètre.
3. Dépendance : la spec des styles est fusionnée (v1.2.0).

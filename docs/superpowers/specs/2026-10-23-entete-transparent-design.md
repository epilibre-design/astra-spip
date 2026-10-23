# En-tête transparent — spécification

Date : 2026-10-23. Statut : à relire (Gilles, Codex) ; précisions de la première relecture intégrées.

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
Une fonction `astra_entete_transparent(string $type_page, int $id_article, bool $accueil = false): bool`
porte cette règle : le booléen dit si **la page autorise la transparence** (classe du body, CSS du
module). Elle est testée cas par cas.

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
- Réglage par page : nouvelle clé `transparent` dans `astra/pages/<id>` (réglages par page de la
  version 1.1.0) : `defaut`, `active`, `desactive` ; formulaire `editer_astra_page` complété.
- Logo propre à l'en-tête transparent (`different-transparent-logo`, `transparent-header-logo`) :
  hors périmètre de cette version (Garden ne l'utilise pas) ; les classes de body d'Astra
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

## Tests

- Unitaires : `astra_entete_transparent()` pour chaque type de page et chaque combinaison
  réglage global / réglage de page / exclusions, dont l'accueil statique (réglage de la page
  choisie) et l'accueil des derniers articles.
- Restriction par appareil : CSS produit dans les media queries attendues pour `desktop`,
  `mobile` et `both`.
- Sans réglage : R0–R5 et P1–P3 identiques.
- Références WordPress : `R15-transparent-page` (page avec `theme-transparent-header-meta` =
  `enabled`, réglage global désactivé, couleurs de Garden) et `R16-transparent-global` (réglage
  global, page d'archive exclue) ; comparaison HTML (classes du body), captures et styles calculés
  à 1280, 768 et 390 px, y compris la position de l'en-tête par rapport au premier bloc du
  contenu.

## Questions ouvertes

1. Réglage par page limité aux pages uniques (comme Garden) ou étendu aux articles (Astra le
   propose sur tous les contenus) ?
2. Logo propre à l'en-tête transparent : à reprendre plus tard, ou jamais ?
3. Dépendance : cette spec s'appuie sur le registre et la normalisation de la spec des styles ;
   elle est à implémenter après elle.

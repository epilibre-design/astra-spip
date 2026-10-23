# Styles du Customizer — spécification

Date : 2026-10-23. Statut : validée (Gilles, Codex). Précisions issues de la vérification du plan (options du bouton, icônes sociales, type booléen, polices, test de migration) intégrées.

## Objectif

Reprendre dans Astra SPIP les réglages de style du Customizer d'Astra 4.11.0 qui manquent pour
reproduire les modèles Astra, en commençant par ceux qu'utilise Garden Maintenance
(`garden-spip/README.md`, « Écarts connus ») :

- menu principal : couleurs normale, au survol, de la page courante, fonds, sous-menus ;
- bouton global et boutons du builder : couleurs, survol, bordure, rayon, marges, police, taille ;
- identité du site : tailles et couleurs du titre et du slogan ;
- pied de page : couleurs des titres, textes et liens des widgets, couleur et taille du copyright ;
- icônes sociales : couleur, survol, taille ;
- palette : neuvième couleur en `rgba()` ;
- polices : fichiers locaux chargés pour les polices choisies.

C'est la première des trois specs issues des écarts de Garden. Les deux autres viendront ensuite :
les compléments du builder (répartition tablette, séparateurs du pied, bloc menu des widgets,
Google Reviews) et l'en-tête transparent.

Hors périmètre : valeurs tablette et mobile appliquées (enregistrées mais non exploitées, voir
plus bas), animations de survol du menu, mégamenus, boutons et menus de WooCommerce et EDD.

## Principes

1. **Rien ne change visuellement sans réglage.** Sans réglage, le HTML et le CSS produits
   restent identiques octet pour octet, sauf les deux suppressions de balisage de l’identité
   décrites dans « Migration ». Le CSS et le rendu visible restent inchangés à 1280, 768 et
   390 px ; aucun slogan masqué ne devient visible à la mise à jour.
2. **Avec un réglage, l'effet est celui d'Astra.** Les règles sont portées des fichiers de CSS
   dynamique d'Astra 4.11.0, avec les mêmes sélecteurs, dans le même ordre et sous les mêmes
   conditions d'émission. L'équivalence est vérifiée sur des références WordPress : captures et
   styles calculés, à 1280, 768 et 390 px.
3. **Aucune chaîne libre dans une feuille de style.** Chaque valeur est validée selon son type
   avant d'entrer dans le CSS.

## Données

### Méta `astra/styles`

Les réglages portent les **noms d'options d'Astra** (`header-menu1-color-responsive`,
`button-radius-fields`, `footer-widget-2-link-color`…). Les valeurs d'un `site.json` de modèle
ou d'un WordPress se reprennent donc telles quelles, après la normalisation commune. Le seed de
Garden et un éventuel import wp2spip l'utiliseront.

- **Options responsives.** La structure `{"desktop": …, "tablet": …, "mobile": …}` d'Astra est
  **conservée entière**. Cette version n'exploite que `desktop`. Le formulaire l'indique et ne
  modifie pas les valeurs tablette et mobile enregistrées.
- **Absence de réglage.** Une clé absente, une valeur vide (`''`, ou `desktop` vide) ou une valeur
  invalide signifient « pas de réglage » : aucune règle n'est émise et le rendu natif d'Astra
  s'applique. Une valeur invalide n'est jamais remplacée par une valeur par défaut écrite.
- **Normalisation commune** (`astra_styles_normaliser($valeurs): array`) :
  - elle sert au formulaire, au seed de Garden et à tout import ;
  - elle retire les clés inconnues ;
  - elle garde une valeur responsive entière si sa partie `desktop` est valide ou vide, et
    retire seulement une partie invalide.

### Registre

`astra_styles_registre(): array` décrit chaque clé acceptée :

| Champ | Contenu |
|---|---|
| `option` | nom de l'option d'Astra (la clé elle-même) |
| `source` | fichier d'Astra 4.11.0 qui lit l'option et émet le CSS |
| `groupe` | menu, bouton, bouton-entete, identite, pied (widgets et copyright), sociaux |
| `type` | `couleur`, `taille`, `quatre-cotes`, `nombre`, `police`, `graisse`, `enum`, `booleen`, `fond`, `extras-police` (voir « Types structurés ») |
| `responsive` | vrai si Astra stocke une valeur par appareil (voir « Types structurés ») |
| `valeurs` | pour le type `enum` : liste fermée des valeurs acceptées |
| `unites` | unités acceptées (`px`, `em`, `rem`, `%`…) et bornes |
| `defaut` | valeur par défaut d'Astra (lue dans `class-astra-theme-options.php` ou `class-astra-builder-options.php`), pour la documentation et les tests : jamais écrite |

Types acceptés :
- **couleur** : `#rgb`, `#rrggbb`, `var(--ast-global-color-N)` avec N de 0 à 8, `rgba(r, g, b, a)` ou `rgb()` (entiers 0–255, alpha de 0 à 1) ;
- **taille** : nombre borné et unité de la liste ;
- **police** : pile CSS telle qu'Astra l'enregistre (`'Poppins', sans-serif`), caractères validés
  comme aujourd'hui par `astra_police()` ; une police locale est reconnue par la première famille
  de la pile (voir « Polices locales ») ;
- **graisse** : `normal`, `bold` ou 100–900 par centaines, et `inherit` ;
- **enum** : une valeur de la liste `valeurs` du registre (par exemple
  `footer-social-1-color-type` : `custom` ou `official`) ;
- **booleen** : `true`, `false`, `1`, `0` ou vide (affichage du titre et du slogan, par appareil).

### Types structurés

Les options d'Astra ne sont pas toutes des valeurs simples. Leur structure d'origine est
conservée telle quelle ; chaque champ connu est validé, un champ inconnu est retiré, un champ
invalide est retiré seul (il compte comme absent, le reste de la structure est gardé).

**Responsive** (`responsive` vrai). Structure d'Astra :
`{"desktop": V, "tablet": V, "mobile": V, "desktop-unit": U, "tablet-unit": U, "mobile-unit": U}`,
où V est une valeur du type de la clé et U une unité de la liste `unites` de la clé. Les champs
`*-unit` n'existent que pour les tailles ; ils sont conservés même quand seule la valeur
`desktop` est exploitée. Une unité absente vaut `px`, comme dans Astra. Exemple (Garden) :
`font-size-site-title` = `{"desktop": "20", "tablet": "18", "mobile": "16", "desktop-unit": "px", "tablet-unit": "px", "mobile-unit": "px"}`.

**quatre-cotes**. Structure d'Astra (`button-radius-fields`, `header-button1-border-radius-fields`) :
`{"desktop": {"top": N, "right": N, "bottom": N, "left": N}, "tablet": {…}, "mobile": {…}, "desktop-unit": U, "tablet-unit": U, "mobile-unit": U}` ;
pour `header-button1-border-size`, non responsive : `{"top": N, "right": N, "bottom": N, "left": N}`.
Chaque côté est un nombre borné ou vide.

**fond** (`header-menu1-bg-obj-responsive` et autres `*-bg-obj-responsive`). Un objet par
appareil, champs autorisés :

| Champ | Valeurs acceptées | Exploité dans cette version |
|---|---|---|
| `background-type` | `color`, `image`, `gradient`, `''` | oui : seuls `color` et `''` produisent du CSS |
| `background-color` | type couleur | oui |
| `background-image` | URL d'un document du site (`IMG/…`) ou `''` | non, conservé |
| `background-media` | entier ou `''` | non, conservé |
| `background-repeat` | `repeat`, `no-repeat`, `repeat-x`, `repeat-y` | non, conservé |
| `background-position` | combinaisons de `left`, `center`, `right` × `top`, `center`, `bottom` | non, conservé |
| `background-size` | `auto`, `cover`, `contain` | non, conservé |
| `background-attachment` | `scroll`, `fixed`, `inherit` | non, conservé |
| `overlay-type` | `''`, `none`, `classic`, `gradient` | non, conservé |
| `overlay-color` | type couleur | non, conservé |
| `overlay-opacity` | nombre de 0 à 1 ou `''` | non, conservé |
| `overlay-gradient` | `''` (dégradé libre : refusé dans cette version, aucune chaîne libre en CSS) | non |

Le CSS produit suit `astra_get_responsive_background_obj()` (`inc/core/common-functions.php`
d'Astra 4.11.0) pour l'appareil `desktop` : type `color` sans image → `background-color` et
`background-image: none` ; type vide avec une couleur → `background-color` seul ; types `image`
et `gradient` → rien dans cette version (écart documenté, valeurs conservées).

**extras-police** (`*-font-extras`, par exemple `font-extras-section-footer-copyright`). Champs :
`line-height` (nombre) et `line-height-unit` (`''`, `px`, `em`), `letter-spacing` (nombre) et
`letter-spacing-unit` (`px`, `em`), `text-transform` (`''`, `none`, `capitalize`, `uppercase`,
`lowercase`), `text-decoration` (`''`, `initial`, `none`, `underline`, `overline`,
`line-through`). Lus comme `astra_get_font_extras()`.

Le plan relève, dans les sources d'Astra 4.11.0, la liste exhaustive des clés de chaque groupe
avec leur défaut. Familles visées :

| Groupe | Options d'Astra | Source (Astra 4.11.0) |
|---|---|---|
| menu | `header-menu1-{color,h-color,a-color}-responsive`, `-bg-obj-responsive`, `-h-bg-color-responsive`, `-a-bg-color-responsive`, `-submenu-b-color`, `-font-size`, `-font-weight`, `-font-family` ; même famille pour `menu2` | `inc/builder/type/header/menu/dynamic-css/dynamic.css.php` |
| bouton | `button-color`, `button-h-color`, `button-bg-color`, `button-bg-h-color`, `button-radius-fields`, `theme-button-padding` (quatre côtés, responsive), `theme-button-border-group-border-size`, `font-family-button`, `font-size-button`, `font-weight-button`, `font-extras-button` ; Astra 4.11.0 lit ces options, et non plus `button-v-padding` et `button-h-padding`, héritées | `inc/class-astra-dynamic-css.php` |
| bouton-entete | `header-button1-{text-color,text-h-color,back-color,back-h-color,border-color,border-h-color,border-size,border-radius-fields,font-size,font-family,font-weight}`, marges de la section | `inc/builder/type/base/dynamic-css/button/class-astra-button-component-dynamic-css.php` |
| identite | `font-size-site-title`, `font-size-site-tagline`, `header-color-site-title`, `header-color-h-site-title`, `header-color-site-tagline`, `display-site-title-responsive`, `display-site-tagline-responsive` (booléens par appareil), `ast-header-responsive-logo-width` (largeur du logo, nombre par appareil) | `inc/class-astra-dynamic-css.php`, `inc/builder/type/header/site-identity/dynamic-css/dynamic.css.php` |
| pied (widgets) | `footer-widget-N-{title-color,color,link-color,link-h-color,font-size,content-font-size}` (N de 1 à 4) | `inc/builder/type/base/dynamic-css/widget/class-astra-widget-component-dynamic-css.php` |
| pied (copyright) | `footer-copyright-color`, `font-size-section-footer-copyright` | `inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php` |
| sociaux | `{header,footer}-social-1-{color,h-color,size,space}`, `-color-type` (couleurs personnalisées seulement ; les couleurs de marque sont hors périmètre). `-bg-color`, `-bg-h-color` et `-radius-fields` ne sont pas repris dans cette version : vides dans le modèle Garden, leur CSS n'a pas d'attendu WordPress (écart documenté) | `inc/builder/type/base/dynamic-css/social/class-astra-social-component-dynamic-css.php` |

**Défaut corrigé par le groupe identité.** Aujourd'hui, `afficher_slogan` (défaut `oui`) imprime
le slogan, mais `css/astra-dynamic.css`, extrait d'un WordPress réglé par défaut, contient la règle
d'Astra `.site-header .site-description{display:none}` : le slogan n'apparaît jamais, quel que soit le
réglage (constaté sur Garden, dont le modèle affiche le slogan). `display-site-tagline-responsive`
et `display-site-title-responsive` remplacent `afficher_slogan` et `afficher_titre_site` : le
balisage et le CSS d'affichage suivent Astra (défaut d'Astra : slogan masqué).

**Migration, sans changement de rendu visible.** Elle conserve l’affichage existant.
L’exception au HTML identique est limitée à la suppression du slogan masqué sur tous les
appareils et de son enveloppe `.ast-site-title-wrap` si elle reste vide. Aucun autre changement
de balisage ou de CSS n’est accepté au titre de cette exception :
- `afficher_titre_site`, qui fonctionne aujourd'hui : `oui` (ou absent) → titre visible sur les
  trois appareils, valeur par défaut d'Astra, donc rien n'est écrit ; `non` →
  `display-site-title-responsive` = `{"desktop": false, "tablet": false, "mobile": false}` ;
- `afficher_slogan`, sans effet visible aujourd'hui : n'est pas converti en slogan visible (ce
  serait un changement de rendu non demandé) ; le slogan reste masqué, comme aujourd'hui et comme
  dans Astra par défaut. Seul `display-site-tagline-responsive`, réglé explicitement, l'affiche.
- La migration s'exécute une fois (mise à jour de la base du plugin, `astra_upgrade`), puis efface
  `afficher_slogan` et `afficher_titre_site` ; le formulaire du thème ne les propose plus.
Le logo est aujourd'hui réduit à 60 px de haut (`inclure/identite.html`) ;
`ast-header-responsive-logo-width` règle sa largeur comme Astra (modèle Garden : 40 px).

La neuvième couleur (`couleur_8`) reste dans la configuration du thème existante. Sa validation
accepte en plus `rgba()` et `rgb()`, comme la palette `global-color-palette` d'Astra (Garden :
`rgba(64,191,79,0.15)`).

## Génération du CSS

- **Un module par groupe** (`inc/styles/<groupe>.php`). Chaque fonction reçoit les réglages
  normalisés et renvoie les règles portées depuis le fichier source du registre :
  - mêmes sélecteurs, même ordre, mêmes conditions d'émission, y compris les états interactifs
    (`:hover`, `:focus`, page courante `.current-menu-item`, `.current-menu-ancestor`, sous-menus)
    quand la règle d'Astra les vise ;
  - le fichier et la fonction d'Astra sont cités en commentaire.
- **Conditions d'émission d'Astra.**
  - Une règle de composant n'est émise que si l'élément est placé dans le builder
    (`Astra_Builder_Helper::is_component_loaded` → présence dans `astra/hfb`).
  - Comme `astra_parse_css`, une déclaration vide est retirée, et un sélecteur sans déclaration
    aussi.
  - Les conditions propres à une option (par exemple `-color-type` = `custom` pour les icônes
    sociales) sont reprises telles quelles.
- **Responsive.** Seule la valeur `desktop` est exploitée. Quand Astra place la règle `desktop`
  hors de toute media query, elle vaut aussi sur tablette et mobile. C'est ce que les références
  vérifient à 768 et 390 px. Les règles tablette et mobile d'Astra ne sont pas émises.
- **Point d'entrée** `astra_styles_css(array $styles, array $hfb): string`.
  - Il assemble les groupes dans l'ordre où Astra émet leur CSS.
  - Il est inséré dans `inclure/head-vars.html`, après `astra-hfb-inline-css`, dans un
    `<style id="astra-styles-inline-css">` émis seulement s'il n'est pas vide.
  - Un groupe dont Astra émet le CSS avant celui du builder est placé à cet endroit si les
    références le montrent nécessaire : l'ordre est vérifié dans la cascade quand plusieurs
    réglages visent le même bouton ou le même menu.

## Polices locales

- **Registre fermé des polices** (`astra_polices_locales(): array`). Pour chaque police : nom,
  graisses fournies (fichiers woff2 dans `fonts/<police>/`), pile de repli, licence, source et
  version.
- **Premières polices** : Raleway et Poppins (SIL OFL 1.1).
  - Les graisses sont fixées par le plan après relevé des graisses réellement utilisées par Astra
    et par Garden (texte, titres, `strong`, bouton, menu) : il ne faut pas de graisse synthétisée
    par le navigateur.
  - Chaque police garde ses propres mentions de licence dans `licenses/` et une entrée dans
    `CREDITS.md`.
- **Chargement** : un `@font-face` (`font-display: swap`, URL du plugin passée par `timestamp`)
  est émis pour chaque police locale choisie, dans les graisses fournies, dans un bloc
  `<style id="astra-polices-inline-css">` placé avant les variables du thème. Une police est
  choisie quand la première famille d'une pile (texte, titres, bouton, menus, bouton de l'en-tête)
  est son nom. Rien n'est émis pour une pile système.
- **Formulaire** : `police_texte`, `police_titres`, `font-family-button` et les polices du menu
  proposent les polices locales, et gardent la saisie d'une pile système.
- **Aucun service tiers** n'est appelé (pas de Google Fonts).

## Formulaire

- Nouveau formulaire CVT `configurer_astra_styles` (Configuration › Squelettes › « Couleurs et
  typographie »), une section par groupe.
- Chaque champ de couleur propose les neuf couleurs de la palette (enregistrées comme
  `var(--ast-global-color-N)`, ainsi qu'Astra les stocke) ou une couleur libre.
- Pour un fond (`*-bg-obj-responsive`), seule la couleur est saisie ; les autres champs enregistrés
  sont conservés.
- Mention sur chaque champ responsif : « Ordinateur. Les valeurs tablette et mobile sont
  conservées mais pas encore appliquées. »
- `verifier()` passe par `astra_styles_normaliser()` et signale chaque valeur refusée.
- `traiter()` fusionne les valeurs `desktop` saisies dans les structures responsives existantes,
  sans toucher `tablet` ni `mobile`.

## Tests et preuves

**Critère 1 : rien ne change sans réglage.**
- Test unitaire : `astra_styles_css([], …)` vaut `''`.
- Sur R0–R5 et P1–P3, `comparer_html.php` ne tolère que les deux suppressions de balisage
  décrites dans « Migration » ; toutes les autres différences HTML et CSS sont refusées.
  `comparer_captures.sh` vérifie le rendu inchangé ; pas de nouveau `<style>` ni de `@font-face`
  sans réglage. Un titre explicitement masqué avant la mise à jour produit une option migrée :
  ce cas est vérifié séparément par les tests de migration.

**Critère 2 : avec un réglage, équivalence avec WordPress.**
- **Références.** Une référence par groupe (R6-menu, R7-bouton, R8-identite, R9-pied-couleurs,
  R10-sociaux), plus R11-combinee qui reprend les valeurs de Garden et met plusieurs réglages sur
  le même bouton et le même menu (cascade).
  - Chacune a son script WordPress (`tests/references/wp/`) et sa configuration SPIP
    (`configurations.php`, clé `styles`).
  - Aucune n'entre dans `extraire_css.py`.
- **Comparaison** à 1280, 768 et 390 px :
  - captures au seuil de 1 % de pixels différents, alignement à ±2 px, comme aujourd'hui ;
  - styles calculés (liste `styles` du JSON de référence) : égalité exacte des valeurs
    calculées, pour chaque propriété des règles portées ;
  - le comparateur sait mettre un élément à l'état `:hover` ou `:focus` (survol et focus simulés)
    et viser la page courante et un sous-menu ouvert.
- **Polices.**
  - Les captures attendent `document.fonts.ready` et vérifient, par `document.fonts.check()` et la
    liste des ressources chargées, que les fichiers des polices choisies sont chargés des deux côtés.
  - Le WordPress de test sert les mêmes fichiers woff2 localement, par un réglage
    `font-family-*` et un `@font-face` ajouté par le script de référence, sans Google Fonts.
- **Écarts résiduels.** Tout écart qui subsiste au seuil ou dans les styles calculés est documenté
  (référence, propriété, valeurs, cause) dans `tests/references/README.md`, comme les pertes des
  blocs.

**Tests unitaires.**
- Registre : chaque clé a `option`, `source`, `groupe`, `type`, `defaut`, et ses `unites` si
  c'est une taille.
- Normalisation : clé inconnue retirée, valeur invalide traitée comme absente, structure
  responsive conservée, `tablet` et `mobile` intacts.
- Structures d'origine : un jeu de valeurs repris tel quel du `site.json` de Garden
  (`hb-header-bg-obj-responsive`, `button-radius-fields`, `font-size-site-title`,
  `footer-social-1-color-type`, `font-extras-section-footer-copyright`, et un
  `*-bg-obj-responsive` complet) ressort de la normalisation identique (aller-retour sans
  perte, y compris `*-unit` et champs non exploités) ; le même jeu avec un champ invalide ou
  inconnu par structure ressort sans ce champ seulement.
- Génération : conditions d'émission de chaque groupe (élément absent du builder → rien),
  déclarations vides retirées, ordre.
- Sécurité : tentative d'injection CSS refusée pour chaque type (`;}`, `</style>`, `url(`,
  `expression(`).

**Migration.**
- Test d'intégration : configurations `afficher_slogan` = `oui` avec un slogan,
  `afficher_slogan` = `non`, `afficher_titre_site` = `non`, et aucune des deux clés ; pour chacune,
  le HTML et le CSS de l'en-tête rendus avant et après la migration sont identiques, à deux
  exceptions près, invisibles : comme Astra (`astra_logo()`), le slogan masqué sur tous les
  appareils n'est plus imprimé (il l'était, masqué par `css/astra-dynamic.css`), et l'enveloppe
  `.ast-site-title-wrap` n'est imprimée que si le titre ou le slogan l'est ; les anciennes
  clés sont effacées, et seule la configuration `afficher_titre_site` = `non` écrit
  `display-site-title-responsive`. La comparaison HTML n’autorise que ces deux suppressions :
  elle ne retire aucun autre nœud et ne masque aucune autre différence. Les captures et les
  styles calculés de l’identité (visibilité, dimensions et position du logo, du titre et des
  éléments voisins) restent identiques à 1280, 768 et 390 px, y compris lorsque le titre et le
  slogan sont tous deux masqués.
- La migration est idempotente et préserve les options `astra/styles` déjà présentes, notamment
  un réglage explicite d’affichage du titre ou du slogan.
- Puis `display-site-tagline-responsive` = visible sur ordinateur : le slogan s'affiche
  (référence WordPress R8-identite).

**Intégration et bout en bout.**
- Police locale choisie → `@font-face` émis et fichier servi ; pile système → aucun
  `@font-face`.
- `tests/e2e/formulaire_styles.cjs` : enregistrement et relecture de chaque groupe, valeurs
  tablette et mobile importées conservées après un enregistrement depuis le formulaire.

## Suite

- Garden, lot 3 (plan à part dans garden-spip) : `astra/styles` posé depuis les valeurs du
  `site.json` par la normalisation commune, puis mise à jour des « Écarts connus » de son README.
- Version d'Astra SPIP : 1.2.0, exigée par le lot 3 de Garden.

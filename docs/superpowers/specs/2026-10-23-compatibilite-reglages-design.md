# Réglages de compatibilité et options importées — spécification

Date : 2026-10-23. Statut : à relire (Gilles, Codex) ; révisée le 24 octobre après la relecture de
Codex (alignements responsifs, contrat des graisses des titres, numéros des références).

## Objectif

Combler les écarts relevés sur Garden Maintenance après le lot 4 (`garden-spip/README.md`,
« Écarts connus », relevé du 23 octobre) qu'aucune des trois specs précédentes ne couvre :

1. **drapeaux de compatibilité** d'Astra enregistrés dans un `site.json` de modèle, qui changent
   le CSS émis par Astra 4.11.0 : `submenu-below-header` et `can-support-widget-and-editor-fonts` ;
2. **marge du bouton de l'en-tête** (`section-hb-button-1-padding`) ;
3. **alignement du copyright et des icônes sociales du pied** (`footer-copyright-alignment`,
   `footer-social-1-alignment`).

Mesures du relevé, modèle contre Astra SPIP 1.2.0 :

| Écart | Modèle | Astra SPIP |
|---|---|---|
| Hauteur de l'en-tête à 1280 px | 121 px | 90 px |
| Hauteur de l'en-tête à 390 px | 104 px | 87 px (dont 12 px dus au logo mobile, hors périmètre) |
| Graisse des titres des widgets du pied | 400 | 600 |
| Marges du bouton de l'en-tête | 17 × 26 px | 15 × 30 px |
| Copyright, icônes sociales du pied | à gauche, à droite | centrés |

Hors périmètre : les valeurs tablette et mobile des styles existants (spec « Styles du Customizer »,
qui les exclut ; spec à part) ; les options de cette spec, elles, s'appliquent comme Astra les émet
(alignements sur les trois appareils, section 3), le bouton de retour en haut (réglage `astra/bouton_haut` existant,
posé par un lot de Garden), l'en-tête transparent et les compléments du builder (specs #6 et #5).

Principes, repris de la spec « Styles du Customizer » : rien ne change sans réglage ; avec un
réglage, CSS d'Astra 4.11.0, mêmes sélecteurs et mêmes conditions d'émission ; aucune chaîne libre
dans le CSS ; valeurs d'un `site.json` reprises telles quelles.

## 1. Drapeaux de compatibilité

### Comportement d'Astra 4.11.0

Un site installé avant certaines versions d'Astra garde des drapeaux dans `astra-settings`. Les
Starter Templates les exportent dans `site.json` et les réimportent : le site importé garde le
rendu de l'ancien site. Garden en a deux qui changent son rendu.

- **`submenu-below-header` à `false`.** `Astra_Dynamic_CSS::astra_submenu_below_header_fix()`
  (`inc/class-astra-dynamic-css.php`) renvoie faux. Astra émet alors le premier bloc de la
  condition (`.ast-flex` centré, `.main-header-bar{padding:1em 0}`, `.ast-site-identity{padding:0}`,
  `.header-main-layout-1/3 .ast-flex.main-header-container` centrés), et non le second
  (`.main-header-menu .menu-item, #astra-footer-menu .menu-item, …{display:flex; …}`). Sans le
  drapeau, ou à `true`, c'est l'inverse : c'est le cas actuel d'Astra SPIP.
- **`can-support-widget-and-editor-fonts` présent** (quelle que soit sa valeur : Astra teste
  `isset`). `support_font_css_to_widget_and_in_editor()` renvoie faux :
  - `h1.widget-title`, `h2.widget-title`, `h3.widget-title{font-weight:…}` ne sont pas émis ;
  - la valeur de repli de la graisse des titres h1 à h6 passe de `inherit` à `normal` (lignes 205
    à 330 du même fichier) : une graisse `inherit` enregistrée n'est plus remplacée par
    `headings-font-weight`.

### Ce qu'Astra SPIP fait

- Nouveau réglage `astra/compatibilite`, tableau fermé de drapeaux. Clés acceptées :
  `submenu-below-header` (booléen) et `can-support-widget-and-editor-fonts` (présence). Toute
  autre clé est refusée, comme une option de style inconnue.
- Les règles qui dépendent d'un drapeau sortent de `css/astra-dynamic.css` :
  - le bloc « fix » de `submenu-below-header` va dans `css/compat/sous-menu-fix.css` ;
  - le bloc « sans fix » est porté dans `css/compat/sous-menu-ancien.css` ;
  - un seul des deux est chargé : `sous-menu-fix.css` sans réglage (rendu actuel inchangé).
- Pour `can-support-widget-and-editor-fonts`, voir « Graisses des titres » ci-dessous.
- Le CSS de chaque variante est extrait d'un rendu WordPress Astra 4.11.0, drapeau posé, avec
  `extraire_css.py` (même méthode que `css/astra-dynamic.css`) ; aucune règle n'est écrite à la
  main. Provenance notée en tête de chaque fichier, comme pour `css/astra-dynamic.css`.
- Formulaire : aucun. Ces drapeaux ne s'éditent pas dans le Customizer d'Astra ; ils viennent d'un
  import. Garden les pose par son lot (clé `config.astra.compatibilite`).

### Graisses des titres

Comportement d'Astra 4.11.0 (`inc/class-astra-dynamic-css.php`, lignes 155–240, 676–760 et
2240–2256) :

- `headings-font-weight` (défaut `600` sur une installation récente) donne la graisse de
  `h1,…,h6,.site-title,.site-title a` ;
- pour chaque niveau n, `font-weight-hn` (défaut `inherit`) ; valeur de repli
  `inherit` sans le drapeau, `normal` avec. Si `font-weight-hn` vaut la valeur de repli, il prend
  `headings-font-weight`. La règle `hn,.entry-content hn` reçoit alors cette graisse, sauf si elle
  vaut `inherit` : aucune graisse n'est émise ;
- sans le drapeau seulement, `h1.widget-title`, `h2.widget-title`, `h3.widget-title` reçoivent
  la graisse de leur niveau (valeur brute, `inherit` compris).

Ce qu'Astra SPIP fait :

- **Stockage et validation.** `headings-font-weight` et `font-weight-h1` à `font-weight-h6`
  entrent au registre des styles (méta `astra/styles`, nouveau groupe `titres`, source
  `inc/class-astra-dynamic-css.php`), type `graisse` existant (`''`, `inherit`, `normal`, `bold`,
  `100` à `900`) ; défauts `600` et `inherit`. `headings-font-weight` refuse `inherit` : avec cette
  valeur, Astra ne produit pas de graisse pour `.site-title`, cas que le CSS figé ne sait pas
  défaire, et qu'aucun modèle relevé n'utilise. Une valeur refusée est retirée seule, comme pour
  les autres styles. Le drapeau reste dans `astra/compatibilite`.
- **CSS figé inchangé.** `css/astra-dynamic.css` garde ses règles (graisse 600 des titres et des
  titres des widgets) : sans réglage, rien ne change.
- **CSS produit.** Une fonction `astra_titres_css(array $styles, array $compatibilite)` calcule pour
  chaque niveau la graisse d'Astra (règle ci-dessus) et émet, dans le bloc
  `astra-styles-inline-css`, seulement ce qui diffère du CSS figé :
  - `h1,.entry-content h1,…,.site-title,.site-title a{font-weight:H}` si `H` (graisse des titres)
    diffère de 600 ;
  - puis, pour chaque niveau, `hn,.entry-content hn{font-weight:T}`, si `T` diffère de `H`.
    `T` est la graisse que le niveau doit calculer : `G`, ou `H` quand Astra n'émet aucune graisse
    pour ce niveau (`G` vaut `inherit`). La comparaison se fait avec `H` et non avec 600, car c'est
    la valeur que le niveau calcule une fois la règle générale appliquée : le CSS figé donne 600
    quand `H` vaut 600, et la règle générale émise, placée après les règles figées, donne `H`
    sinon. Les règles par niveau suivent la règle générale dans le bloc. Exemple :
    `headings-font-weight` à 700 et `font-weight-h1` à 600 émettent la règle générale à 700, puis
    `h1,.entry-content h1{font-weight:600}` ; sans cette seconde règle, le h1 calculerait 700 ;
  - sans le drapeau : `hn.widget-title{font-weight:G}` (n = 1 à 3), si `G` diffère de 600 ;
  - avec le drapeau : neutralisation `h1.widget-title,h2.widget-title,h3.widget-title{font-weight:400}`,
    valeur de `.widget-title` dans `css/main.min.css`, qu'Astra laisse s'appliquer en n'émettant pas
    ses règles. Elle est déclarée comme les neutralisations de la spec des styles.
- **Émission sans `astra/styles`.** `astra_styles_inline()` lit aussi `astra/compatibilite`, et le
  bloc est produit dès que le drapeau ou une option du groupe `titres` est posé, même sans aucune
  autre option de style.
- Valeurs de Garden : drapeau posé, `font-weight-h1` à `h6` à `inherit`, `headings-font-weight` à
  `600`. Le CSS produit se réduit à la neutralisation des titres des widgets (graisse 400, écart du
  relevé). Les titres restent à 600 par la règle de `headings-font-weight`.

## 2. Marge du bouton de l'en-tête

### Comportement d'Astra 4.11.0

`prepare_advanced_margin_padding_css( 'section-hb-button-1', … )`
(`inc/class-astra-extended-base-dynamic-css.php`) émet la marge de
`.ast-header-button-1[data-section*="section-hb-button-"] .ast-builder-button-wrap .ast-custom-button`
à partir de `section-hb-button-1-padding`. Défaut sur une installation récente : 15 × 30 px
(`inc/core/builder/class-astra-builder-options.php`, `astra_4_6_4_compatibility`). Une fois
l'option enregistrée, ses valeurs, même vides : un côté vide n'émet rien, et la marge des boutons
(`theme-button-padding`) s'applique.

### Ce qu'Astra SPIP fait

- `section-hb-button-1-padding` entre au registre des styles, type `quatre-cotes` responsif,
  défaut 15 × 30 px ordinateur.
- Même mécanisme que les rayons du bouton de l'en-tête (`inc/styles/bouton_entete.php`,
  « Neutralisation ») : option enregistrée, ses valeurs ; un côté vide ne prend plus le défaut de
  `css/astra-hfb.css`, mais la marge de `theme-button-padding` (Astra SPIP : 15 × 30 px sans
  réglage). Valeur ordinateur seule, comme les autres styles.
- Formulaire « Couleurs et typographie », groupe du bouton de l'en-tête : un champ quatre côtés.

## 3. Alignement du copyright et des icônes sociales du pied

### Comportement d'Astra 4.11.0

- `footer-copyright-alignment` : `.ast-footer-copyright{text-align:…}`
  (`inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php`).
- `footer-social-1-alignment` :
  `[data-section="section-fb-social-icons-1"] .footer-social-inner-wrap{text-align:…}`
  (`inc/builder/type/base/dynamic-css/social/class-astra-social-component-dynamic-css.php`).
- Défaut des deux : `center` sur les trois appareils.

### Ce qu'Astra SPIP fait

- Astra émet la règle des trois appareils : sans media query pour l'ordinateur,
  `(max-width:921px)` pour la tablette, `(max-width:544px)` pour le mobile. `css/astra-dynamic.css`
  contient les trois règles de la valeur par défaut (`center`).
- Valeurs de Garden : copyright `left` / `left` / `center`, icônes `right` / `right` / `center`.
- Les deux options entrent au registre des styles, type `enum` (`left`, `center`, `right`),
  responsives, dans les groupes « pied » et « icônes sociales ». Contrairement aux styles
  existants, les **trois valeurs** s'appliquent : l'équivalence est promise à 1280, 768 et 390 px.
- Dès qu'une des trois valeurs est posée, les trois règles sont émises (appareil sans valeur :
  défaut `center`), dans l'ordre et les media queries d'Astra. Une règle ordinateur seule,
  placée après le CSS figé, l'emporterait aussi sur les règles tablette et mobile figées.
- Formulaire : la saisie de ces deux options propose les trois appareils (trois sélecteurs). Les
  autres styles restent en saisie ordinateur seule.

## Preuves

**Sans réglage, rendu inchangé.** R0–R11 et P1–P3 : HTML, CSS et captures identiques à la
v1.2.0. La sortie des blocs de `css/astra-dynamic.css` vers `css/compat/` est vérifiée par
l'égalité du CSS appliqué (styles calculés) sur ces références.

**Avec un réglage, équivalence avec WordPress.** Comparaison à 1280, 768 et 390 px, comme les
specs précédentes (captures au seuil de 1 %, styles calculés exacts) :

Numéros à partir de R18 : R12 à R15 sont pris par le plan des compléments du builder, R16 et
R17 par celui de l'en-tête transparent.

- `R18-compat-sous-menu` : `submenu-below-header` à `false` ; styles calculés de `#masthead`,
  `.main-header-bar` (`padding-top`, `padding-bottom`, `height`), `.ast-site-identity`.
- `R19-compat-polices-widgets` : `can-support-widget-and-editor-fonts` posé, sans aucune autre
  option de style, avec des titres de widgets h2 ; `font-weight` des titres des widgets et des
  h1 à h6.
- `R20-titres-graisses` : graisses des titres réglées sans le drapeau, puis avec
  (`headings-font-weight` à 700, un niveau à `inherit`, un à `normal`, un à `300`, un à `600`
  explicite) : les règles et les replis des deux branches d'Astra. Le niveau à `600` vérifie,
  avec et sans le drapeau, qu'une graisse égale au CSS figé n'est pas écrasée par la règle
  générale.
- `R21-bouton-entete-marge` : `section-hb-button-1-padding` vide et `theme-button-padding`
  17 × 26 ; puis une valeur explicite.
- `R22-pied-alignements` : les tableaux complets de Garden (copyright `left` / `left` / `center`,
  icônes `right` / `right` / `center`) ; `text-align` comparé aux trois largeurs.
- `R23-garden-compat` : les valeurs de Garden ensemble (deux drapeaux, graisses, marge vide,
  alignements), pour vérifier la cascade.

Tests unitaires : registre (types, défauts, refus d'une clé de compatibilité inconnue, refus de
`inherit` pour `headings-font-weight`), choix du fichier `css/compat/` selon le drapeau,
neutralisation de la marge, graisses des titres (chaque branche de la règle d'Astra, avec et sans
le drapeau, avec et sans `astra/styles`, un niveau à `600` sous une graisse générale différente), alignements (trois règles émises dès qu'une valeur est
posée, défaut `center` pour un appareil sans valeur). Attendus capturés sur WordPress pour le
groupe `titres` et les alignements, comme pour les autres groupes.

## Effet attendu sur Garden

Une fois implémentée et posée par un lot (drapeaux et options du `site.json`), cette spec retire
cinq lignes de « Écarts connus » : hauteur de l'en-tête (sauf l'écart dû au logo mobile), graisse
des titres des widgets, marges du bouton, alignements du pied. La mesure de `garden-spip` (script
du lot 4) doit alors ne plus lister ces propriétés.

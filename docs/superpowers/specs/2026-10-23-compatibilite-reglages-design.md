# Réglages de compatibilité et options importées — spécification

Date : 2026-10-23. Statut : à relire (Gilles, Codex).

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

Hors périmètre : les valeurs tablette et mobile des styles (spec « Styles du Customizer », qui
les exclut ; spec à part), le bouton de retour en haut (réglage `astra/bouton_haut` existant,
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
- Pour `can-support-widget-and-editor-fonts`, les règles de graisse concernées (titres des
  widgets, repli des titres h1 à h6) sont produites par le générateur des styles et non plus
  figées dans `css/astra-dynamic.css` ; elles suivent le drapeau comme dans Astra.
- Le CSS de chaque variante est extrait d'un rendu WordPress Astra 4.11.0, drapeau posé, avec
  `extraire_css.py` (même méthode que `css/astra-dynamic.css`) ; aucune règle n'est écrite à la
  main. Provenance notée en tête de chaque fichier, comme pour `css/astra-dynamic.css`.
- Formulaire : aucun. Ces drapeaux ne s'éditent pas dans le Customizer d'Astra ; ils viennent d'un
  import. Garden les pose par son lot (clé `config.astra.compatibilite`).

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

- Les deux options entrent au registre des styles, type `enum` (`left`, `center`, `right`),
  responsif, valeur ordinateur appliquée ; règles émises seulement quand la valeur diffère du
  défaut, dans les groupes « pied » et « icônes sociales ».
- Formulaire : un sélecteur par option, dans les groupes existants.

## Preuves

**Sans réglage, rendu inchangé.** R0–R11 et P1–P3 : HTML, CSS et captures identiques à la
v1.2.0. La sortie des blocs de `css/astra-dynamic.css` vers `css/compat/` est vérifiée par
l'égalité du CSS appliqué (styles calculés) sur ces références.

**Avec un réglage, équivalence avec WordPress.** Comparaison à 1280, 768 et 390 px, comme les
specs précédentes (captures au seuil de 1 %, styles calculés exacts) :

- `R17-compat-sous-menu` : `submenu-below-header` à `false` ; styles calculés de `#masthead`,
  `.main-header-bar` (`padding-top`, `padding-bottom`, `height`), `.ast-site-identity`.
- `R18-compat-polices-widgets` : `can-support-widget-and-editor-fonts` posé, avec des titres de
  widgets h2 et une graisse `inherit` sur un niveau de titre ; `font-weight` des titres des
  widgets et des h1 à h6.
- `R19-bouton-entete-marge` : `section-hb-button-1-padding` vide et `theme-button-padding`
  17 × 26 ; puis une valeur explicite.
- `R20-pied-alignements` : copyright à gauche, icônes à droite.
- `R21-garden-compat` : les valeurs de Garden ensemble (deux drapeaux, marge vide, alignements),
  pour vérifier la cascade.

Tests unitaires : registre (types, défauts, refus d'une clé de compatibilité inconnue), choix du
fichier `css/compat/` selon le drapeau, neutralisation de la marge.

## Effet attendu sur Garden

Une fois implémentée et posée par un lot (drapeaux et options du `site.json`), cette spec retire
cinq lignes de « Écarts connus » : hauteur de l'en-tête (sauf l'écart dû au logo mobile), graisse
des titres des widgets, marges du bouton, alignements du pied. La mesure de `garden-spip` (script
du lot 4) doit alors ne plus lister ces propriétés.

# Marges des rangées du builder — spécification

Date : 2026-10-24. Statut : à relire (Gilles, Codex).

## Objectif

Combler un écart relevé sur Garden Maintenance après le lot 5 (`garden-spip/README.md`,
« Écarts connus », relevé du 24 octobre) : les marges intérieures des rangées du pied, réglées par
le modèle et ignorées par Astra SPIP.

Mesures du relevé, démonstration du modèle contre Astra SPIP 1.4.0 (styles calculés) :

| Rangée | Largeur | Modèle (haut / droite / bas / gauche) | Astra SPIP |
|---|---|---|---|
| Principale du pied | 1280 px | 80 / 0 / 45 / 0 | 45 / 0 / 45 / 0 |
| Principale du pied | 768 px | 80 / 80 / 80 / 80 | 45 / 0 / 45 / 0 |
| Principale du pied | 390 px | 60 / 30 / 30 / 30 | 45 / 0 / 45 / 0 |
| Basse du pied | 768 et 390 px | 20 / 20 / 20 / 20 | 20 / 0 / 20 / 0 |

Par suite, les colonnes du pied occupent 768 px au lieu de 608 à 768 px, 390 au lieu de 330 à
390 px.

Valeurs du `site.json` du modèle : `section-primary-footer-builder-padding` (ordinateur : haut 80 ;
tablette : 80 sur les quatre côtés ; mobile : 60 / 30 / 30 / 30), `section-below-footer-builder-padding`
(tablette : 20 sur les quatre côtés). Les autres options de marge des rangées y sont vides.

Principes, repris de la spec « Styles du Customizer » : rien ne change sans réglage ; avec un
réglage, CSS d'Astra 4.11.0, mêmes sélecteurs et mêmes conditions d'émission ; aucune chaîne libre
dans le CSS ; valeurs d'un `site.json` reprises telles quelles.

## Comportement d'Astra 4.11.0

Chaque rangée du builder a deux options, `<section>-padding` et `<section>-margin`, de structure
« côtés responsifs » (`desktop`, `tablet`, `mobile` × `top`, `right`, `bottom`, `left`, plus
`desktop-unit`, `tablet-unit`, `mobile-unit`), défaut `Astra_Builder_Helper::$default_responsive_spacing`
(tous les côtés vides). `Astra_Extended_Base_Dynamic_CSS::prepare_advanced_margin_padding_css()`
(`inc/class-astra-extended-base-dynamic-css.php`) émet, pour le sélecteur de la rangée, les
`padding-*` puis les `margin-*` de chaque appareil : ordinateur sans media, tablette sous
`@media (max-width:921px)`, mobile sous `@media (max-width:544px)` ; `astra_responsive_spacing()`
rend un côté vide par une chaîne vide, et `astra_parse_css()` omet la propriété. Un côté vide
laisse donc s'appliquer la règle précédente : la valeur de l'appareil plus large (cascade des
media), ou le CSS fixe du builder (`padding-top:45px;padding-bottom:45px` de la rangée principale
du pied, dans `css/astra-hfb.css`).

| Section | Sélecteur (fichier `dynamic.css.php` d'Astra) |
|---|---|
| `section-above-header-builder` | `.ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar` (`header/above-header`) |
| `section-primary-header-builder` | `.ast-desktop .ast-primary-header-bar.main-header-bar, .ast-header-break-point #masthead .ast-primary-header-bar.main-header-bar` (`header/primary-header`) |
| `section-below-header-builder` | `.ast-below-header-bar.ast-below-header, .ast-header-break-point .ast-below-header-bar.ast-below-header` (`header/below-header`) |
| `section-above-footer-builder` | `.site-above-footer-wrap[data-section="section-above-footer-builder"]` (`footer/above-footer`) |
| `section-primary-footer-builder` | `.site-primary-footer-wrap[data-section="section-primary-footer-builder"]` (`footer/primary-footer`) |
| `section-below-footer-builder` | `.site-below-footer-wrap[data-section="section-below-footer-builder"]` (`footer/below-footer`) |
| `section-footer-builder-layout` | `.ast-hfb-header .site-footer` (`footer/primary-footer`) |

La position de ces règles dans la feuille d'Astra (après le fond, la bordure et le séparateur de la
rangée, avant la visibilité) est relevée dans le rendu WordPress, comme pour les groupes existants.

## Périmètre

Les 14 options ci-dessus (7 sections × marge intérieure et marge extérieure), dans le registre des
styles (`astra_styles_registre()`, nouveau groupe `marges-rangees`), type « côtés responsifs »
déjà utilisé par `section-hb-button-1-padding` ; formulaire « Couleurs et typographie » généré
depuis le registre ; saisie et normalisation communes (`astra_styles_normaliser()`).

Contrairement aux styles de la spec « Styles du Customizer », ces options s'appliquent sur les
trois appareils, comme Astra les émet (même choix que les alignements de la spec
« Compatibilité »).

Unités : celle que la valeur enregistre pour chaque appareil (`desktop-unit`, `tablet-unit`,
`mobile-unit`), parmi celles du type existant (`px`, `em`, `%`) ; une autre unité est refusée par
la normalisation (et signalée par le seed de Garden). Le défaut d'Astra a `tablet-unit` à `em`,
sans effet tant que les côtés sont vides.

Hors périmètre : les marges des éléments (`section-footer-copyright-margin`,
`title_tagline-margin`, etc.) et les marges propres au panneau du menu mobile ; elles ne sont pas
réglées dans le modèle et feront, au besoin, une spec à part.

## Émission

Nouveau fichier `inc/styles/marges_rangees.php`, fonction `astra_styles_marges_rangees(array $s): array`,
qui rend les règles de chaque section réglée, dans l'ordre d'Astra : ordinateur, tablette (921),
mobile (544) ; une option absente ou entièrement vide n'émet rien. Les règles prennent place
dans `astra_styles_css()` à la position relevée dans le rendu WordPress (test d'équivalence existant :
sortie ⊇ attendus et différence ⊆ CSS de base ∪ neutralisations).

## Preuves

- Référence WordPress `R24-pied-marges` : valeurs de Garden (`section-primary-footer-builder-padding`,
  `section-below-footer-builder-padding`) ; comparaison des captures et des styles calculés
  (`padding-*` des rangées, largeur des colonnes) à 1280, 768 et 390 px.
- Référence WordPress `R24-marges-toutes` : chaque section réglée (marge intérieure et extérieure,
  valeurs distinctes par appareil, unités mêlées, côtés partiellement vides), pour couvrir les
  rangées de l'en-tête et la cascade des côtés vides.
- Attendus CSS capturés dans WordPress (`tests/references/styles`) et test d'équivalence ; tests
  unitaires de normalisation (structure, unités, côtés vides) ; aller-retour du formulaire.
- Rendu sans réglage identique à la version précédente (références R0–R23, P1–P3).

## Suite

Version d'Astra SPIP : 1.5.0. Garden : un lot pose les deux options du modèle et retire la ligne
correspondante des « Écarts connus ».

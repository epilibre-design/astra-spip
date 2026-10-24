# Marges des rangées du builder — spécification

Date : 2026-10-24. Statut : relue (Codex, Greptile ; PR #15) ; révisée le 24 octobre après ces
relectures (conditions d'émission selon les rangées remplies, marges extérieures négatives, unité
tablette par défaut), puis complétée par les décisions du plan
(`docs/superpowers/plans/2026-10-24-marges-rangees.md`) : quinzième option, rendu des rangées du
pied, appareil sans unité, preuve par les attendus, formulaire.

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

Chaque rangée du builder a deux options, `<section>-padding` et `<section>-margin` (l'en-tête
entier n'a que `section-header-builder-layout-margin`, émise à la suite de la rangée principale par
`astra_primary_header_breakpoint_style()`), de structure
« côtés responsifs » (`desktop`, `tablet`, `mobile` × `top`, `right`, `bottom`, `left`, plus
`desktop-unit`, `tablet-unit`, `mobile-unit`), défaut `Astra_Builder_Helper::$default_responsive_spacing`
(tous les côtés vides). `Astra_Extended_Base_Dynamic_CSS::prepare_advanced_margin_padding_css()`
(`inc/class-astra-extended-base-dynamic-css.php`) émet, pour le sélecteur de la rangée, les
`padding-*` puis les `margin-*` de chaque appareil : ordinateur sans media, tablette sous
`@media (max-width:921px)`, mobile sous `@media (max-width:544px)` ; `astra_responsive_spacing()`
rend un côté vide par une chaîne vide, et `astra_parse_css()` omet la propriété ; elle rend aussi
vide tout côté d'un appareil dont l'unité (`<appareil>-unit`) n'est pas enregistrée : rien n'est
émis pour cet appareil. Un côté vide
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
| `section-header-builder-layout` (marge extérieure seulement) | `.ast-hfb-header .site-header` (`header/primary-header`) |

La position de ces règles dans la feuille d'Astra (après le fond, la bordure et le séparateur de la
rangée, avant la visibilité) est relevée dans le rendu WordPress, comme pour les groupes existants.

### Conditions d'émission

Les règles de marge d'une rangée font partie du CSS de cette rangée, qu'Astra n'émet pas toujours
(hors aperçu du Customizer) :

- **Rangées de l'en-tête** (`astra_above_header_row_setting()`, `astra_primary_header_breakpoint_style()`,
  `astra_below_header_row_setting()`) : CSS émis seulement si la rangée contient un élément sur
  ordinateur **ou** sur mobile. Le test est `Astra_Builder_Helper::is_row_empty()`, qui, malgré son
  nom, renvoie vrai quand la rangée est remplie : une des zones `<rangée>_left`, `_left_center`,
  `_center`, `_right_center`, `_right` de `header-desktop-items` ou de `header-mobile-items` n'est
  pas vide (`has_side_columns()`, `has_center_column()`).
- **Rangées du pied** (`astra_fb_above_footer_dynamic_css()`, `astra_fb_primary_footer_dynamic_css()`,
  `astra_fb_below_footer_dynamic_css()`) : CSS émis seulement si une des zones `<rangée>_1` à
  `<rangée>_5` de `footer-desktop-items` n'est pas vide (`is_footer_row_empty()`, qui renvoie lui
  aussi vrai quand la rangée est remplie, et ne regarde pas la zone 6).
- **Pied entier** (`section-footer-builder-layout`) : règles émises au début de
  `astra_fb_primary_footer_dynamic_css()`, avant le test de la rangée principale, donc toujours.
- **En-tête entier** (`section-header-builder-layout-margin`) : règles émises après celles de la
  rangée principale de l'en-tête, donc seulement si elle est remplie sur ordinateur ou sur mobile.

Une marge réglée pour une rangée vide n'émet donc rien, comme dans Astra. Astra SPIP applique ces
conditions d'après la configuration du builder (`astra/hfb`, normalisée), avec les zones exactes
d'Astra (zone 6 du pied ignorée). Astra n'affiche d'ailleurs une rangée du pied qu'à la même
condition (`template-parts/footer/builder/footer-row.php`) : une rangée remplie seulement en
zone 6 n'apparaît pas. Astra SPIP l'affichait ; son rendu des rangées du pied suit désormais la
même condition (`astra_hfb_pied_rangee_remplie()`), partagée avec les marges.

## Périmètre

Les 15 options ci-dessus (7 sections × marge intérieure et marge extérieure, plus la marge
extérieure de l'en-tête entier), dans le registre des styles (`astra_styles_registre()`, nouveau
groupe `marges-rangees`), type « côtés responsifs » déjà utilisé par `section-hb-button-1-padding` ;
formulaire « Couleurs et typographie » généré depuis le registre (section « Marges des rangées » :
pour chaque option, trois lignes, ordinateur, tablette et mobile, de quatre côtés et une unité) ;
saisie et normalisation communes (`astra_styles_normaliser()`).

Signe des valeurs : les marges extérieures (`<section>-margin`) acceptent les nombres négatifs,
comme Astra (une marge de `-10` y donne `margin-top:-10px`, vérifié) ; le type « côtés responsifs »
reçoit une variante signée (clé `signe` du registre), utilisée par ces sept options seulement. Les
marges intérieures (`<section>-padding`) restent non négatives, comme aujourd'hui : une marge
intérieure négative est invalide en CSS, et le navigateur l'ignorerait de toute façon, ce qui
revient au côté vide.

Contrairement aux styles de la spec « Styles du Customizer », ces options s'appliquent sur les
trois appareils, comme Astra les émet (même choix que les alignements de la spec
« Compatibilité »).

Unités : celle que la valeur enregistre pour chaque appareil (`desktop-unit`, `tablet-unit`,
`mobile-unit`), parmi celles du type existant (`px`, `em`, `%`) ; une autre unité est refusée par
la normalisation (et signalée par le seed de Garden). Le défaut d'Astra
(`Astra_Builder_Helper::$default_responsive_spacing`) a les trois unités à `px`. Un appareil sans
unité enregistrée n'émet rien, comme dans Astra. Le formulaire conserve cette absence pour les
valeurs importées, avec le choix « Ne pas appliquer » : un enregistrement sans modification ne
change pas le CSS. Choisir explicitement une unité active les côtés enregistrés. Pour un appareil
sans valeur enregistrée, l'unité proposée reste `px`. Un test d'aller-retour du formulaire avec
`marges-rangees-vides.json` vérifie ces deux comportements. Bornes : marges
intérieures de 0 à 1000, marges extérieures de -1000 à 1000.

Hors périmètre : les marges des éléments (`section-footer-copyright-margin`,
`title_tagline-margin`, etc.) et les marges propres au panneau du menu mobile ; elles ne sont pas
réglées dans le modèle et feront, au besoin, une spec à part.

## Émission

Nouveau fichier `inc/styles/marges_rangees.php`, fonction
`astra_styles_marges_rangees(array $s, array $hfb): array`, qui rend les règles de chaque section
réglée dont la rangée remplit les conditions d'émission ci-dessus (`$hfb` : configuration du
builder normalisée, déjà transmise aux autres modules par `astra_styles_css()`), dans l'ordre
d'Astra : ordinateur, tablette (921), mobile (544) ; une option absente ou entièrement vide n'émet
rien. Les règles prennent place
dans `astra_styles_css()` à la position relevée dans le rendu WordPress (test d'équivalence existant :
sortie ⊇ attendus et différence ⊆ CSS de base ∪ neutralisations).

## Preuves

- Référence WordPress `R24-pied-marges` : valeurs de Garden (`section-primary-footer-builder-padding`,
  `section-below-footer-builder-padding`) ; comparaison des captures et des styles calculés
  (`padding-*` des rangées, largeur des colonnes) à 1280, 768 et 390 px.
- Référence WordPress `R24-marges-toutes` : chaque section réglée (marge intérieure et extérieure,
  valeurs distinctes par appareil, unités mêlées, côtés partiellement vides, marges extérieures
  négatives), pour couvrir les rangées de l'en-tête, la cascade des côtés vides et le signe.
- Référence WordPress `R24-marges-rangees-vides` : marges réglées pour les sept sections, avec une
  rangée de l'en-tête remplie seulement sur mobile, une rangée de l'en-tête vide sur les deux
  appareils, une rangée du pied vide et une rangée du pied remplie seulement en zone 6 : aucune
  règle pour les rangées vides (zone 6 comprise), règles pour la rangée remplie sur mobile et pour
  le pied entier.
- Attendus CSS capturés dans WordPress (`tests/references/styles`) : règles de marges du CSS
  complet des trois références (`marges.py`), que le CSS d'Astra SPIP doit reproduire exactement,
  dans l'ordre ; une règle émise pour une rangée vide ne se voyant pas dans les captures, c'est la
  preuve des conditions d'émission. Tests
  unitaires des conditions d'émission (rangées vides, remplies sur un seul appareil, zone 6 du
  pied) et de normalisation (structure, unités, côtés vides, marge extérieure négative conservée,
  marge intérieure négative refusée) ; aller-retour du formulaire.
- Rendu sans réglage identique à la version précédente (références R0–R23, P1–P3), hors rangée du
  pied remplie seulement en zone 6.

## Suite

Version d'Astra SPIP : 1.5.0. Garden : un lot pose les deux options du modèle et retire la ligne
correspondante des « Écarts connus ».

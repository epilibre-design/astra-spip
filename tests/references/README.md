# Références WordPress du builder d'en-tête et de pied de page

Ces références servent de modèle au portage du Header/Footer Builder d'Astra 4.11.0 : pour
chaque configuration, le WordPress de test produit le HTML, le CSS dynamique et des captures
que le rendu SPIP doit reproduire.

Le dossier `sorties/` n'est pas versionné : il se régénère avec les commandes ci-dessous.

## Prérequis

- Un WordPress de test avec le thème Astra officiel 4.11.0 actif, servi sur
  `http://127.0.0.1:8401` (variable `URL` pour une autre adresse) ; sa racine dans `WP`.
- wp-cli (`wp`), PHP, curl.
- Playwright installé dans un dossier `PW` (contenant `node_modules/playwright`) et Chromium dans
  `~/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome`.

## Capturer

    WP=/chemin/wordpress PW=/chemin/playwright tests/references/capturer.sh \
        R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees

Chaque configuration écrit dans `sorties/Rn/` : `accueil.html`, `article.html`,
`dynamique.css` (contenu de `<style id="astra-theme-css-inline-css">`), `ordinateur.png`
(1280 px), `mobile.png` (390 px), `mobile-menu.png` (menu mobile ouvert) et `options.json`
(option `astra-settings` appliquée). `accueil.html` est la page capturée au chemin `chemin` du
JSON (l'accueil par défaut).

Le script échoue (code 1) si : le CSS dynamique est absent ou vide, une erreur JavaScript se
produit, un sélecteur du fichier `wp/Rn.json` n'est pas visible (`ordinateur`, `mobile`) ou
absent du DOM (`presents`), le menu mobile attendu (`menuMobile`) ne s'ouvre pas, ou un
sélecteur de `menuOuvert` (sous-menu) manque une fois le menu ouvert.

## État initial

Chaque configuration commence par `ref_etat_initial()` (`wp/commun.php`) :

- option `astra-settings` remise à quatre réglages fixes (version, barre latérale à droite) ;
- `remove_theme_mods()` (emplacements de menus, logo…) ;
- widgets texte supprimés, barre latérale remise aux widgets de `wp/widgets-base.json`
  (recherche et articles récents) ;
- catégories Jardinage (avec la sous-catégorie Potager) et Actualités créées si besoin.

Les menus (`Principal`, `Secondaire`, `Pied`) sont créés au premier passage puis réutilisés :
leurs entrées gardent les mêmes identifiants (`menu-item-N`) d'une capture à l'autre. Le menu
`Principal` est affecté aux emplacements `primary` et `mobile_menu`.

Non fixé : les articles de démonstration du WordPress de test.

## Reproductibilité

Une configuration capturée après une autre doit donner le même résultat, à la normalisation
de `normaliser.sh` près (versions des ressources, nonces) :

    SORTIES=/tmp/repro tests/references/capturer.sh R4-pied-asymetrique R1-entete-complet
    diff <(tests/references/normaliser.sh sorties/R1-entete-complet/accueil.html) \
         <(tests/references/normaliser.sh /tmp/repro/R1-entete-complet/accueil.html)

Vérifié le 2026-10-22 : HTML et CSS identiques.

## Configurations

| Configuration | Contenu |
|---|---|
| R0-defaut | Disposition par défaut d'Astra, menu Principal (avec sous-menu) |
| R1-entete-complet | Rangée haute (texte, icônes sociales), principale (logo ; menu, recherche, bouton), basse (menu secondaire centré) ; mobile : texte en rangée haute, recherche et bouton de menu, menu déroulant |
| R2-entete-centre | Menu à gauche, logo centré, compte et recherche à droite ; rangée basse : widget et texte 2 ; mobile : bouton à gauche, logo centré, compte ; panneau latéral avec menu et recherche |
| R3-pied-colonnes | Pied : rangée haute 2 colonnes (texte, icônes), principale 4 widgets (`4-equal`), basse copyright et menu (`2-equal`) |
| R4-pied-asymetrique | Pied : rangée principale `3-cwide` (widget ; texte et icônes ; menu vertical), basse `2-lheavy` (copyright, texte 2) |
| R5-rangees | Disposition par défaut ; rangée principale de l'en-tête : hauteur 100 px, fond `#e8f5e9`, sans séparateur (`hb-header-main-sep` à 0) ; styles calculés comparés (`styles`). Hors des configurations lues par `extraire_css.py` |
| R6-menu | Styles du Customizer (`tests/references/styles/appliquer.php`, groupe `menu`) : logo, menu et bouton dans l'en-tête, quatre widgets texte, copyright et icônes sociales dans le pied ; couleurs et sous-menus du menu principal, sur la page de la catégorie Jardinage (page courante) |
| R7-bouton | Même placement ; bouton global (couleurs, survol, rayon, marges, police Poppins 500, taille, casse, espacement) |
| R8-identite | Même placement ; nom du site et slogan (affiché), tailles et couleurs, largeur du logo |
| R9-pied-couleurs | Même placement ; couleurs et tailles des quatre widgets et du copyright |
| R10-sociaux | Même placement ; taille, espacement et couleurs des icônes sociales du pied |
| R11-combinee | Toutes les options précédentes, police du texte Raleway et des titres Poppins (cascade de plusieurs réglages sur le même bouton et le même menu) |
| R16-transparent-page | Page « Notre jardin » avec l'en-tête transparent activé par la page (méta `theme-transparent-header-meta` à `enabled`), réglage global désactivé, valeurs de Garden (`transparent/valeurs/garden.json`) ; contenu, classes du `body`, position de l'en-tête et styles calculés comparés |
| R17-transparent-global | En-tête transparent sur tout le site, page de la catégorie Jardinage : archive exclue par défaut, en-tête normal |
| P1-page | Page « Notre jardin » (`/?pagename=notre-jardin`), menu de pages à deux niveaux (`ref_menu_pages()`) |
| P2-accueil | Même menu, page « Notre jardin » en page d'accueil statique (`/`) |
| P3-page-pleine | Page « Plein écran », titre désactivé, conteneur `full-width-container` |

Les pages des configurations P (`ref_pages()`) ont les mêmes titres, textes et sous-pages que le
SPIP d'intégration (`scripts/install-spip-test.sh`). Classes du `body` relevées :

- P1-page : `wp-singular page-template-default page page-id-21 page-parent wp-theme-astra ast-desktop ast-plain-container ast-no-sidebar astra-4.11.0 group-blog ast-single-post ast-inherit-site-logo-transparent ast-hfb-header ast-normal-title-enabled`
- P2-accueil : les mêmes, précédées de `home`
- P3-page-pleine : `wp-singular page-template-default page page-id-24 wp-theme-astra ast-desktop ast-page-builder-template ast-no-sidebar astra-4.11.0 group-blog ast-single-post ast-inherit-site-logo-transparent ast-hfb-header` (ni `ast-normal-title-enabled`, ni `page-parent`)

Les références R6–R11 reprennent les valeurs du modèle Garden Maintenance
(`tests/references/styles/valeurs/<groupe>.json`) ; elles ne sont pas lues par
`extraire_css.py` ni par `comparer_html.php` (structure inchangée), mais par
`comparer_captures.sh` : captures, styles calculés (dont `survol` et `focus`) à 1280, 768 et
390 px, polices chargées (`polices`). Le WordPress de test sert alors les polices d'Astra SPIP
par un mu-plugin écrit par `ref_polices_locales()` (`commun.php`), sans Google Fonts ;
`ref_etat_initial()` le retire.

## Styles du Customizer : attendus

`tests/references/styles/` fixe le CSS qu'Astra 4.11.0 produit pour chaque groupe de styles :

- `appliquer.php` : état R0, placement de Garden, options du groupe (`GROUPE=<groupe>`) ;
- `capturer.sh` : `WP=… tests/references/styles/capturer.sh` capture le CSS dynamique sans option
  (`attendus/base.css`) puis avec les options de chaque groupe, et écrit dans
  `attendus/<groupe>.css` les règles ajoutées ou modifiées (`regles.py`) ; il remet R0 ensuite ;
- `attendus/<groupe>.neutralisations.css` : règles qu'Astra SPIP émet en plus pour défaire une
  règle fixe de ses feuilles qu'Astra n'émet plus avec ce réglage (marges tablette et mobile
  du bouton ; rayon de 40 px du bouton de l'en-tête quand ses rayons sont enregistrés vides).

`tests/unit/StylesAttendusTest.php` vérifie que la sortie d'`astra_styles_css()` contient toutes
les règles attendues et que ses autres règles sont des règles de `base.css` ou des
neutralisations déclarées. Après une montée de version d'Astra, relancer `capturer.sh` et relire
le diff des attendus.

Relevé du 23 octobre 2026 (`comparer_captures.sh`, R0–R11) : toutes les vues sous le seuil,
styles calculés identiques aux trois largeurs, polices chargées des deux côtés ; plus grand
écart : 0,35 % (R11, pied sur ordinateur).

Non couvert dans cette version (valeurs conservées, aucun CSS) : valeurs tablette et mobile ;
fonds de type `image` ou `gradient` ; fond, fond au survol et rayon des icônes sociales
(`*-social-1-bg-color`, `-bg-h-color`, `-radius-fields`), absents des valeurs du modèle.

## En-tête transparent : attendus

`tests/references/transparent/` fixe le CSS qu'Astra 4.11.0 ajoute sur une page à en-tête
transparent (page « Notre jardin », méta de page `enabled`) :

- `appliquer.php` : état P1, puis les options de `valeurs/<variante>.json` (`VARIANTE=…`) ;
- `capturer.sh` : `WP=… tests/references/transparent/capturer.sh` compare chaque variante à la
  même page sans en-tête transparent et écrit `attendus/<variante>.css` (règles ajoutées ou
  modifiées) et `attendus/<variante>.retirees.css` (règles qu'Astra n'émet plus) ;
- variantes : `garden` (valeurs du modèle), `ordinateur` et `mobile` (restriction par appareil,
  séparateur), `couleurs` (toutes les couleurs, valeurs tablette et mobile différentes) ;
- `attendus/neutralisations.css` : règle qu'Astra SPIP émet en plus pour défaire la marge de
  60 px de `#primary` que `css/astra-dynamic.css` contient et qu'Astra n'émet pas sur une page à
  en-tête transparent.

`tests/unit/EnteteTransparentTest.php` vérifie l'égalité des règles et que chaque règle retirée
est neutralisée.

## Relevés pour le portage

### Répartitions des colonnes du pied

Classes `ast-builder-grid-row-<répartition>` présentes dans le CSS d'Astra : `full`, `2-equal`,
`2-lheavy`, `2-rheavy`, `2-full`, `3-equal`, `3-cwide`, `3-cheavy`, `3-lheavy`, `3-rheavy`,
`4-equal`, `4-lheavy`, `4-rheavy`, `5-equal`, `6-equal`.

### Classes racines des éléments (HTML capturé)

| Élément | Classe racine |
|---|---|
| `html-N` (en-tête) | `ast-builder-layout-element ast-flex site-header-focus-item ast-header-html-N`, contenu dans `.ast-header-html.inner-link-style-` |
| `button-1` | `ast-builder-layout-element ast-flex site-header-focus-item ast-header-button-1` |
| `search` | `ast-builder-layout-element ast-flex site-header-focus-item ast-header-search` |
| `account` | `ast-builder-layout-element site-header-focus-item ast-header-account`, puis `.ast-header-account-wrap` |
| `social-icons-1` | `.ast-header-social-1-wrap.ast-header-social-wrap` (en-tête), `.ast-footer-social-1-wrap.ast-footer-social-wrap` (pied) |
| `widget-N` (en-tête) | `header-widget-area widget-area site-header-focus-item header-widget-area-inner` |
| `menu-2` | `ast-builder-menu-2 ast-builder-menu ast-flex ast-builder-menu-2-focus-item ast-builder-layout-element site-header-focus-item` |
| `mobile-menu` | `ast-builder-menu-mobile ast-builder-menu ast-builder-menu-mobile-focus-item ast-builder-layout-element site-header-focus-item` |
| panneau `dropdown` | `.ast-mobile-header-content.content-align-flex-start` |
| panneau `off-canvas` | `#ast-mobile-popup.ast-mobile-popup-drawer.content-align-flex-start.ast-mobile-popup-right` dans `#ast-mobile-popup-wrapper` |
| `html-N` (pied) | `footer-widget-area widget-area site-footer-focus-item ast-footer-html-N` |
| `widget-N` (pied) | `footer-widget-area widget-area site-footer-focus-item footer-widget-area-inner` |
| `menu` (pied) | `.footer-nav-wrap` |
| `copyright` | `ast-builder-layout-element ast-flex site-footer-focus-item ast-footer-copyright` |

Le balisage complet de chaque élément est à recopier depuis `sorties/Rn/accueil.html`.

### Icônes sociales : source et licence

Astra rend les icônes par `Astra_Builder_UI_Controller::fetch_svg_icon()`, à partir de
`assets/svg/svgs.json` (1 604 icônes). Huit tracés repris dans le portage (facebook,
twitter, instagram, linkedin, youtube, mastodon, github et rss) correspondent exactement
à **Font Awesome Free**, par Fonticons, Inc., sous **CC BY 4.0**. Le `readme.txt` d'Astra
ne mentionne pas Font Awesome : il cite notamment IcoMoon-Free, ainsi que Heroicons
pour les captures.

Les SVG `twitter-x`, `bluesky` et `email` viennent également d'Astra, mais leur attribution
à Font Awesome n'est pas établie. Le relevé des correspondances, les sources, les crédits
et les notices conservées figurent dans [CREDITS.md](../../CREDITS.md).

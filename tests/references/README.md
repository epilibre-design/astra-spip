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
        R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique

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
| P1-page | Page « Notre jardin » (`/?pagename=notre-jardin`), menu de pages à deux niveaux (`ref_menu_pages()`) |
| P2-accueil | Même menu, page « Notre jardin » en page d'accueil statique (`/`) |
| P3-page-pleine | Page « Plein écran », titre désactivé, conteneur `full-width-container` |

Les pages des configurations P (`ref_pages()`) ont les mêmes titres, textes et sous-pages que le
SPIP d'intégration (`scripts/install-spip-test.sh`). Classes du `body` relevées :

- P1-page : `wp-singular page-template-default page page-id-21 page-parent wp-theme-astra ast-desktop ast-plain-container ast-no-sidebar astra-4.11.0 group-blog ast-single-post ast-inherit-site-logo-transparent ast-hfb-header ast-normal-title-enabled`
- P2-accueil : les mêmes, précédées de `home`
- P3-page-pleine : `wp-singular page-template-default page page-id-24 wp-theme-astra ast-desktop ast-page-builder-template ast-no-sidebar astra-4.11.0 group-blog ast-single-post ast-inherit-site-logo-transparent ast-hfb-header` (ni `ast-normal-title-enabled`, ni `page-parent`)

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
`assets/svg/svgs.json` (1 604 icônes). Les tracés des icônes de marques (facebook, twitter,
instagram, linkedin, youtube, mastodon, github, rss…) sont ceux de **Font Awesome Free**
(Fonticons, Inc.), dont les icônes sont sous licence **CC BY 4.0**. Le `readme.txt` d'Astra ne
le mentionne pas : il ne cite que IcoMoon-Free (CC BY 4.0 ou GPL), Heroicons (MIT) pour les
captures, et les illustrations CC0.

CC BY 4.0 est compatible avec la GPL v3 (la licence d'astra-spip), à condition de **créditer**
Font Awesome. Les icônes reprises dans le portage devront donc être accompagnées d'une mention
« Icônes de marques : Font Awesome Free par Fonticons, Inc., CC BY 4.0 » (README et `paquet.xml`).

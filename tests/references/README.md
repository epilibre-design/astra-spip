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
| R5-rangees | Disposition par défaut ; rangée principale de l'en-tête : hauteur 100 px, fond `#e8f5e9`, sans séparateur (`hb-header-main-sep` à 0) ; pied : séparateurs des trois rangées (haute 2 px `#c0392b`, principale sans séparateur, basse couleur de la palette) ; styles calculés comparés (`styles`). Hors des configurations lues par `extraire_css.py` |
| R6-menu | Styles du Customizer (`tests/references/styles/appliquer.php`, groupe `menu`) : logo, menu et bouton dans l'en-tête, quatre widgets texte, copyright et icônes sociales dans le pied ; couleurs et sous-menus du menu principal, sur la page de la catégorie Jardinage (page courante) |
| R7-bouton | Même placement ; bouton global (couleurs, survol, rayon, marges, police Poppins 500, taille, casse, espacement) |
| R8-identite | Même placement ; nom du site et slogan (affiché), tailles et couleurs, largeur du logo |
| R9-pied-couleurs | Même placement ; couleurs et tailles des quatre widgets et du copyright |
| R10-sociaux | Même placement ; taille, espacement et couleurs des icônes sociales du pied |
| R11-combinee | Toutes les options précédentes, police du texte Raleway et des titres Poppins (cascade de plusieurs réglages sur le même bouton et le même menu) |
| R12-pied-appareils | Répartitions par appareil de Garden Maintenance : rangées haute (texte, icônes) et basse (copyright, menu) en `2-equal` sur ordinateur et tablette, principale (quatre widgets) en `4-equal` sur ordinateur et `full` sur tablette et mobile ; `grid-template-columns` comparé aux trois largeurs |
| R13-pied-widgets-menus | Pied : widget texte et deux widgets « Menu de navigation » (menus Principal et Pied), comme les widgets 2 et 3 de Garden Maintenance |
| R14-bouton-mobile | Placement de R6–R11 ; bouton du menu mobile en contour (groupe de styles `menu-mobile`, valeurs de Garden) |
| R15-sociaux-google | Pied : copyright et icônes sociales Facebook et Google Reviews (couleur de marque `#dc4e41`) |
| R16-transparent-page | Page « Notre jardin » avec l'en-tête transparent activé par la page (méta `theme-transparent-header-meta` à `enabled`), réglage global désactivé, valeurs de Garden (`transparent/valeurs/garden.json`) ; contenu, classes du `body`, position de l'en-tête et styles calculés comparés |
| R17-transparent-global | En-tête transparent sur tout le site, page de la catégorie Jardinage : archive exclue par défaut, en-tête normal |
| R18-compat-sous-menu | Drapeau de compatibilité `submenu-below-header` à `false` (règles de `css/compat/sous-menu-ancien.css`) ; hauteur de l'en-tête, marges de la rangée principale et de l'identité, affichage des éléments du menu comparés |
| R19-compat-polices-widgets | Drapeau `can-support-widget-and-editor-fonts` posé, sans autre option de style ; placement de R6–R11 (titres des widgets du pied en h2), page « Notre jardin » avec les six niveaux de titres ; graisses comparées |
| R20-titres-graisses | Graisses des titres réglées (`styles/valeurs/titres.json` : titres à 700, h1 `inherit`, h2 `normal`, h3 `300`, h4 `600` explicite), sans le drapeau ; même page |
| R20-titres-graisses-drapeau | Mêmes graisses, avec le drapeau `can-support-widget-and-editor-fonts` |
| R21-bouton-entete-marge | Marge du bouton de l'en-tête enregistrée vide, marge des boutons à 17 × 26 (Garden) |
| R21-bouton-entete-marge-valeur | Marge du bouton de l'en-tête en partie réglée (haut 10, droite 20) : côtés vides selon l'appareil |
| R21-bouton-entete-marge-vide | Marge du bouton de l'en-tête enregistrée vide, sans marge des boutons : 15/30, 14/28, 12/24 selon l'appareil |
| R22-pied-alignements | Alignements de Garden : copyright `left` / `left` / `center`, icônes sociales `right` / `right` / `center` ; `text-align` comparé aux trois largeurs |
| R22-pied-alignements-ordinateur | Alignements réglés pour l'ordinateur seul : tablette et mobile prennent sa valeur |
| R22-pied-alignements-tablette | Alignements réglés pour la tablette seule : sur ordinateur, copyright hérité et icônes sociales centrées |
| R23-garden-compat | Valeurs de Garden de R18 à R22 ensemble (deux drapeaux, graisses, marge vide, alignements) |
| R24-pied-marges | Marges intérieures des rangées principale et basse du pied de Garden (`styles/valeurs/marges-pied.json`) ; marges et largeur des colonnes comparées aux trois largeurs |
| R24-marges-toutes | Les quinze options de marges (valeurs distinctes par appareil, unités mêlées, côtés en partie vides, marges extérieures négatives) ; rangées haute et basse de l'en-tête remplies comme dans R1, rangée haute du pied d'un texte |
| R24-marges-rangees-vides | Mêmes marges, tablette sans unité pour la rangée principale de l'en-tête ; rangée haute de l'en-tête remplie sur mobile seulement, rangée basse vide, rangée haute du pied vide, rangée principale du pied remplie en zone 6 seulement (non affichée, comme dans Astra) |
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

Les références R12–R15 ne sont pas lues par `extraire_css.py`. Pour R13, `comparer_html.php`
signale deux différences de nommage attendues : l'identifiant de la section (`nav_menu-2`,
numéro d'instance du widget WordPress ; `nav_menu-pied-2` dans SPIP) et la classe de la `nav`
(`menu-<slug du menu>-container` ; `menu-<source>-container` dans SPIP, qui n'a pas de menus
nommés).

Relevé du 23 octobre 2026 (`comparer_captures.sh`, R0–R11) : toutes les vues sous le seuil,
styles calculés identiques aux trois largeurs, polices chargées des deux côtés ; plus grand
écart : 0,35 % (R11, pied sur ordinateur).

Non couvert dans cette version (valeurs conservées, aucun CSS) : valeurs tablette et mobile ;
fonds de type `image` ou `gradient` ; fond, fond au survol et rayon des icônes sociales
(`*-social-1-bg-color`, `-bg-h-color`, `-radius-fields`), absents des valeurs du modèle.

## Graisses des titres : attendus

Astra modifie des règles figées de `css/astra-dynamic.css` (graisse retirée d'une règle qui porte
aussi la taille) : les graisses ne se comparent pas règle à règle. `capturer.sh` écrit, pour les
variantes `titres`, `titres-drapeau` et `drapeau-polices`, les règles de graisse des titres du CSS
complet (`graisses.py`) dans `attendus/<variante>.css`. `tests/unit/StylesTitresTest.php` calcule
par la cascade (spécificité, puis ordre) la graisse de chaque titre (h1 à h6, dans le contenu,
titres des widgets h1 à h6, nom du site) : `css/main.min.css` puis ces règles côté WordPress,
`css/main.min.css`, `css/astra-dynamic.css` et le CSS produit côté SPIP ; les deux doivent être
égales.

## Marges des rangées : attendus

`capturer.sh` applique les références R24 (placement propre à chacune) et écrit, pour les valeurs
`marges-pied`, `marges-toutes` et `marges-rangees-vides`, les règles de marges du CSS complet
(`marges.py` : règles des sélecteurs des rangées, du pied entier et de l'en-tête entier qui ne
déclarent que des marges) dans `attendus/<valeurs>.css`. `tests/unit/StylesMargesTest.php` exige
que le CSS produit côté SPIP, avec le même placement, donne exactement ces règles, dans l'ordre
(l'espace après la virgule des sélecteurs, qu'Astra ne retire pas ici, mise à part) : c'est la
preuve des conditions d'émission, car une règle émise pour une rangée vide ne se voit pas dans les
captures.

Les références R18 à R23 passent par les clés `compatibilite` (méta `astra/compatibilite`) et
`textes` (texte d'une page unique, remis à celui de l'installation à chaque configuration) de
`tests/comparaison/configurations.php`.

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

## Migration vers 4.13.8 : sites isolés et garde de capture

La migration utilise `cible.json` (WordPress 7.1.3, Astra 4.13.8) ; les commandes
historiques ci-dessus décrivent encore 4.11.0 jusqu'à qualification complète.
Créer le site neuf avec `scripts/install-wp-reference.sh` en fournissant
`WP_REFERENCE`, `URL_WP`, `ASTRA_REFERENCE_ARCHIVE` et `SQLITE_PLUGIN` (sources
SQLite Database Integration 3.1.1). L'archive du cœur est épinglée et extraite par
tar, puis contrôlée par `wp core verify-checksums`. Le dossier possède son propre
wp-config.php, son plugin SQLite copié et sa base `wp-content/database/reference.sqlite`.
Un dossier non vide sans marqueur est refusé ; la relance ne remet pas les options à zéro.
Le premier passage installe les traductions fr_FR et ajoute les 12 articles de
`wp/preparer-site.php`, pour exercer la pagination et obtenir un défilement réel
lors des captures du pied (bouton retour en haut). Ces contenus sont propres aux tests.

`php tests/references/verifier.php <WP_ROOT> <ZIP> <MANIFEST>` vérifie la version
réelle du cœur, le thème actif et chaque fichier du thème contre le ZIP officiel.
Les scripts de capture exigent `ASTRA_REFERENCE_ARCHIVE` et utilisent explicitement
`cible.json` avant leur premier changement de configuration, sans charger les hooks du thème.

Pour reconstruire l'archive historique, créer **un second dossier** et choisir
`CIBLE=tests/references/cible-4.11.0.json` dans l'installateur, avec le ZIP 4.11.0.
Vérifier ce site avec le manifeste historique explicite, puis utiliser les scripts
extraits du commit 503819e ; ne pas détourner la garde des captures de migration.
Les réglages et preuves historiques restent distincts des nouvelles références.

### Extraction de base et portée des captures de composants (4.13.8)

`base/capturer.sh <dossier-R0>` capture les sept contextes nécessaires (accueil,
article, page, page étroite, recherche vide, archive, 404), avec la garde 4.13.8.
`extraire_css_base.py <dossier-R0> base/substitutions.json` produit le CSS de base.
Le registre recense six adaptations (largeurs et portée de la colonne principale),
quatre exclusions/déplacements et 131 compléments conditionnels ; leurs déclarations
viennent des captures, leur ordre et leur provenance sont explicites. Les familles
de police par défaut restent celles de WordPress ; les polices du site sont définies
par head-vars. La palette de neuf couleurs appartient à head-vars également.

La régression réelle s'exécute avec `ASTRA_LEGACY_R0=<R0-4.11.0>` et
`ASTRA_LEGACY_CSS=<css/astra-dynamic.css-de-503819e>` :
`python3 -m unittest discover -s tests/references -p test_extraire_css_base.py`.
Elle reproduit exactement les 614 règles historiques dans leur ordre, y compris
les 60 px de #primary ; seul l'en-tête et le groupement de blocs media diffèrent.
Sans archive locale, ce cas est indiqué comme non exécuté ; les tests synthétiques
restent autonomes.

La capture de `#colophon` exclut désormais le bouton `#ast-scroll-top`, fixé à la
fenêtre et extérieur au builder. Sa présence dans une image du pied dépendait de
la hauteur des articles précédents : cela créait des différences de contenu de
fenêtre dans une mesure de composant. Le comparateur conserve la géométrie du pied,
les couleurs et les seuils ; aucun masque n'est appliqué aux autres éléments.
Son origine verticale est alignée au pixel physique le plus proche avant la
capture, sans changer ses dimensions ni ses positions internes. Cela évite de
confondre un arrondi du contenu précédent avec une différence de composant.
`tests/e2e/comparaison_composants.cjs` vérifie cette portée sur deux hauteurs de page.
Le bouton n'est pas ignoré dans la qualification : `tests/e2e/retour_haut.cjs`
compare séparément son apparition après défilement, ses dimensions et son action
avec WordPress à 1280, 768 et 390 px. Le bilan historique brut reste conservé ;
les reprises 4.11.0 et 4.13.8 indiquent explicitement la méthode normalisée.

Le profil `responsive` de `tests/e2e/css_reference.cjs` retarde le bundle de pied
pour mesurer les classes avant son exécution, aux largeurs 921, 922, 1280, 768 et
390 px. Les captures de cet état utilisent CDP : attendre `load` ou les polices
prêtes débloquerait artificiellement le script que ce test doit retarder.
Les deux moteurs sont comparés avec leur media query réelle, y compris l'arrondi
de Chromium qui rend `(max-width:921.99px)` vrai à 922 px. Les modes de barre
`overflow-y:hidden` et `scroll` sont enregistrés ; les barres sont superposées dans
le Chromium de test. La largeur CSS fractionnaire ~921,95 n'a pas été reproduite.

Les rendus supplémentaires sans JavaScript sont produits, configuration restaurée,
par `tests/fixtures/responsive/rendus.php` via `spip php:eval`. Leur JSON privé est
passé dans `ASTRA_RESPONSIVE_RENDUS`. Le profil vérifie R2, les pages, deux menus et
un panneau vide. Avec JavaScript actif, aucune nav de repli n'est créée. Le
comparateur HTML retire uniquement le `<noscript>` de cette adaptation déclarée ;
les autres `<noscript>` et toute nav de repli active restent comparés.

`tests/e2e/navigation.cjs URL offcanvas SORTIES` exerce R2/R25 et enregistre
séparément les défauts observés dans WordPress. Dans la cible 4.13.8, le piège de
focus est retiré à la fermeture et manque aux ouvertures suivantes. Son retour
au premier bouton échoue si ce bouton est masqué ; au passage ordinateur, le
focus peut revenir au body. `js/astra-navigation.js` corrige ces cas sans modifier
le bundle, les classes d'ouverture ni les réglages.

R25 reprend R2 et ajoute dans le pilote un padding technique de 24 px aux deux
panneaux ; ce n'est pas une nouvelle option de configuration SPIP. Les variables
`ASTRA_NAV_COMMANDE_MASQUEE=1` et `ASTRA_NAV_SANS_LIEN=1` ajoutent les cas techniques
avant l'initialisation du bundle : bouton masqué avant le vrai bouton et en-tête
sans lien de logo/titre. Le repli sur `#masthead` pose temporairement tabindex=-1
et le retire au blur. R25-nav-vide vérifie l'absence de panneau/déclencheur et
d'erreurs. Ces parcours Chromium ne constituent pas une certification avec un
lecteur d'écran.

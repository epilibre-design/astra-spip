# Astra pour SPIP

Portage pour SPIP du thème WordPress [Astra](https://wordpress.org/themes/astra/) de Brainstorm Force (version 4.13.8).

Le plugin reproduit le rendu d'Astra avec ses réglages par défaut : même balisage HTML que le
thème WordPress, mêmes feuilles de style et même JavaScript. Les classes CSS d'Astra
(`ast-separate-container`, `ast-right-sidebar`, `blog-layout-4`…) s'appliquent donc telles quelles.

Compatibilité : **SPIP 4.4**. Le support de SPIP 5 est suspendu.

La dernière version publiée, **1.7.0**, ajoute l’identité responsive et les alignements des widgets du pied
sur les trois appareils, ainsi que Gravatar facultatif dans les commentaires.
Voir les [notes de version](docs/releases/1.7.0.md).

## Installation

1. Copier le dossier dans `plugins/astra` (ou `plugins/auto/astra`).
2. Installer et activer **Menus 3.1.2 ou ultérieur (branche 3)** et sa dépendance **Saisies**. Menus est obligatoire, y compris pour les sites qui conservent leurs menus automatiques.
3. Activer le plugin « Astra » dans l'espace privé.
4. Régler le thème dans **Configuration › Squelettes › Thème Astra**.

Le plugin fournit ses propres squelettes et remplace ceux de `squelettes-dist`. Un dossier
`squelettes/` à la racine du site reste prioritaire et permet de surcharger n'importe quel fichier.

Le développement **1.8.0** cible SPIP 4.4 et intègre Menus obligatoirement.
Voir le [bilan de qualification](docs/inventaires/navigation-menus/bilan.md).

## Menus libres

Créer le menu dans **Squelettes › Menus**, puis sélectionner **Menu libre : [titre]**
dans le builder Astra, pour le menu principal, secondaire, celui du pied ou le bloc
« menu » d’une zone de widgets. Menus gère les liens, leur ordre et leurs sous-menus ;
Astra conserve la présentation et les commandes de navigation.

Les sources automatiques existantes (rubriques, mots-clés, pages) sont conservées.
Aucun menu n’est créé automatiquement. Un menu libre
maîtrise toutes ses entrées : ajouter aussi son lien d’accueil dans Menus si souhaité.

Sans JavaScript, les liens du menu mobile et leurs sous-liens restent affichés dans
le flux jusqu’à 922 px, y compris avec un en-tête transparent.

## Correspondance avec le thème WordPress

| WordPress (Astra)               | SPIP                                         |
|---------------------------------|----------------------------------------------|
| `index.php` (blog)              | `sommaire.html` : derniers articles ou page d'accueil statique |
| `archive.php` (catégorie)       | `rubrique.html`                              |
| `archive.php` (étiquette)       | `mot.html`                                   |
| `archive.php` (auteur)          | `auteur.html`                                |
| `single.php`                    | `article.html`                               |
| `page.php`                      | `page_unique.html` (pages uniques du plugin Pages) |
| `search.php`                    | `recherche.html`                             |
| `404.php`                       | `404.html`                                   |
| `header.php` (header builder)   | `inclure/header.html`, `inclure/hfb/rangee-*.html`, `zone.html` |
| `footer.php` (footer builder)   | `inclure/footer.html`                        |
| éléments du builder             | `inclure/hfb/<élément>.html`                 |
| widgets                         | `inclure/widgets/<bloc>.html`                |
| `sidebar.php`                   | `inclure/sidebar.html`                       |
| `comments.php`                  | `inclure/commentaires.html`, `commentaire.html` (forums SPIP) |
| `template-parts/content-blog.php` | `inclure/article-resume.html`              |
| `searchform.php`                | `formulaires/recherche.html`                 |
| pagination                      | `modeles/pagination_astra.html`              |
| Customizer                      | `formulaires/configurer_astra.html`          |
| Customizer, header/footer builder | `formulaires/configurer_astra_hfb.html`    |

## En-tête et pied de page (builder)

Comme le Header/Footer Builder d'Astra, l'en-tête et le pied de page se composent de rangées
(haute, principale, basse), de zones et d'éléments, réglés dans **Configuration › Squelettes ›
En-tête et pied de page** :

- en-tête sur ordinateur : zones gauche, centre et droite de chaque rangée ;
- en-tête sur mobile : mêmes rangées, plus le panneau du menu (déroulant ou latéral) ;
- pied de page : 1 à 6 colonnes par rangée, avec les répartitions d'Astra, choisies séparément
  pour l'ordinateur, la tablette et le mobile (dont `3-firstrow` et `3-lastrow`). Comme dans
  Astra 4.13.8, la règle tablette de `3-firstrow` ou `3-lastrow` (`max-width:921px`) vaut aussi sur
  mobile : la colonne qu'elle étend reste pleine largeur avec une autre répartition mobile
  (mesuré à 390 px, identique dans WordPress et SPIP) ;
- éléments : identité du site, menus (rubriques racines, sous-rubriques d'une rubrique, articles
  d'un groupe de mots-clés, pages uniques si le plugin Pages est actif), textes libres, bouton,
  recherche, icônes sociales (dont Google Reviews), zones de widgets (texte et menu de deux
  niveaux, comme le widget « Menu de navigation » de WordPress), compte, copyright ;
- réglages des rangées : hauteur minimale, couleur de fond, bordure ; séparateur (épaisseur et
  couleur) des rangées du pied.

Sans réglage, l'en-tête et le pied de page sont ceux d'Astra par défaut : identité et menu
principal, copyright centré. Le balisage produit est celui d'Astra ; `css/astra-hfb.css`
contient le CSS que le builder d'Astra ajoute. Chaque élément peut être surchargé dans
`squelettes/inclure/hfb/`.

La configuration est enregistrée dans la méta `astra/hfb`, avec la structure des options
d'Astra (`header-desktop-items`, `header-mobile-items`, `footer-desktop-items`).

## Gabarits assemblés

Un squelette assemblé par ailleurs peut réutiliser l’enveloppe d’Astra sans recopier ses pages :
`inclure/page-debut` et `inclure/page-fin` encadrent `#page`, `inclure/masthead` et `inclure/colophon`
rendent l’en-tête et le pied du builder. `inclure/header` et `inclure/footer` composent ces éléments et
gardent leur sortie. Paramètres, ordre et classes du `body` : [interface des gabarits assemblés](docs/interface-gabarits.md).

## Pages uniques

Avec le plugin [Pages](https://contrib.spip.net/Pages-uniques) (facultatif), les articles hors
rubrique s'affichent avec le gabarit de page d'Astra (`page.php`, `content-page.php`) : classes du
`body` et de l'article, schéma `WebPage`, ni date ni auteur. Le pipeline `styliser` choisit
`page_unique.html` ; un squelette propre à une page (`article=<identifiant>.html`) reste prioritaire.

- **Sous-pages** : avec le plugin [Liaisons entre articles (a2a)](https://contrib.spip.net/Le-plugin-a2a-pour-lier-des-articles)
  (facultatif), un lien de type « sous-page » rattache une page à sa parente. Les menus alimentés
  par les pages uniques affichent alors les sous-pages en sous-menu, dans l'ordre des liens ;
  une page dont la parente n'est plus publiée remonte au premier niveau. Sans a2a, les pages
  forment une liste à plat.
- **Page d'accueil statique** : dans **Configuration › Squelettes › Thème Astra**, l'accueil peut
  afficher les derniers articles (défaut) ou une page unique publiée, comme le réglage « Page
  d'accueil » de WordPress. Cette page porte alors la classe `home` et son adresse propre
  renvoie à l'accueil (lien `canonical`).
- **Réglages par page** : un bloc de la fiche de chaque page permet de masquer le titre, de
  choisir la disposition du contenu (celle des pages, normale, étroite ou pleine largeur) et
  d'activer ou désactiver l'en-tête transparent, comme les réglages de page d'Astra. Ils sont enregistrés dans la méta `astra/pages/<id_article>` et
  supprimés avec la page.

Pages et a2a sont déclarés en `<utilise>` : le thème fonctionne sans eux.

## Blocs WordPress importés

Un site WordPress importé par [wp2spip](https://github.com/epilibre-design/wordpress-to-spip-migrator) garde le
balisage de la plupart des blocs natifs de l'éditeur (colonnes, groupes, boutons, citations,
couverture, média et texte, tableaux…). `css/astra-blocs.css` contient le CSS de ces blocs tel que
WordPress 7.1.3 l'émet sous Astra (feuilles des blocs et styles globaux), extrait par
`tests/references/extraire_css_blocs.py`. `css/astra-spip.css` reporte les déclarations de
WordPress et d'Astra sur les éléments que SPIP produit à leur place (lien du bouton, documents de
la couverture et du bloc média et texte, classes de mise en page retirées par wp2spip).

Chaque bloc est comparé au pixel à son rendu WordPress. Quatre pertes de l'import restent
caractérisées (séparateur, assombrissement de la couverture, code, vers) : elles sont décrites,
avec leur effet et la propriété vérifiée, dans
[`tests/references/blocs/README.md`](tests/references/blocs/README.md).

### Contenus intégrés (oEmbed)

Le plugin [oEmbed](https://git.spip.net/spip-contrib-extensions/oembed), facultatif et déclaré
avec `<utilise>` dans `paquet.xml`, permet de transformer une URL isolée en lecteur ou contenu
intégré lorsque le fournisseur est pris en charge. wp2spip prépare déjà les blocs `embed`
sous cette forme ; sans oEmbed, le contenu reste un lien. oEmbed fournit le lecteur et son
conteneur responsive. La référence [R28](tests/fixtures/oembed/README.md) vérifie oEmbed
3.5.1 sous SPIP 4.4, les proportions 16:9 et 4:3 aux trois largeurs, les replis et l’absence
de réécriture éditoriale. Une adaptation ciblée retire son cadre de 6 px autour du lecteur,
sans changer son ratio ni ajouter d’enveloppe. Astra ne l’installe ni ne l’active automatiquement.
Le contrôle de découverte ne vaut pas validation de sécurité du plugin : cette version
n’emploie pas le validateur d’URL optionnel du client HTTP de SPIP. Le plugin déclare
SPIP 4.x ; son activation n’est pas forcée sous SPIP 5.

La migration depuis Astra 4.11.0 est qualifiée dans le [bilan 1.6.0](docs/inventaires/astra-4.13.8-qualification/bilan.md) : assets officiels, CSS régénéré, clavier et comportements adaptatifs. Les anciennes preuves sont conservées. Le menu mobile sans JavaScript reste une liste simple ; les widgets menu restent des listes statiques, selon les décisions documentées. Les tailles responsives d’identité et les autres fonctions non portées ne deviennent pas disponibles par le seul changement de référence.

## Feuilles de style et scripts

- `css/main.min.css` et `js/frontend.min.js` : fichiers d'origine d'Astra 4.13.8, sans modification.
- `css/astra-dynamic.css` : le CSS « dynamique » qu'Astra génère en PHP à partir des réglages du
  Customizer. Il a été extrait d'un rendu WordPress réel (réglages par défaut, toutes les
  dispositions de page), puis rendu paramétrable : palette et largeurs passent par des variables CSS.
- `css/astra-hfb.css` : CSS ajouté par le builder d'Astra pour les rangées et éléments, extrait
  des références WordPress par `tests/references/extraire_css.py`.
- `css/compat/sous-menu-fix.css`, `css/compat/sous-menu-ancien.css` : règles qui dépendent du
  drapeau de compatibilité `submenu-below-header`, extraites par
  `tests/references/extraire_css.py --compat` ; une seule est chargée, selon `astra/compatibilite`.
- `inclure/head-vars.html` : variables CSS issues de la configuration (`--ast-global-color-0` à `8`,
  largeurs de conteneur, polices) et réglages des rangées du builder.
- `css/astra-article.css` et `css/astra-commentaires.css` : règles qu'Astra n'émet que sur les
  articles et là où des commentaires sont ouverts ou présents ; chargées dans les mêmes cas.
- `css/astra-pages.css` : règles de la disposition pleine largeur des pages, extraites par
  `tests/references/extraire_css.py --pages`.
- `css/astra-blocs.css` : CSS des blocs WordPress importés (voir plus haut).
- `css/astra-spip.css` : adaptations aux contenus produits par SPIP (documents, notes, chapo,
  formulaire de forum, fil d'Ariane, blocs importés…).

## Avatars des commentaires

Le plugin SPIP [Gravatar](https://git.spip.net/spip-contrib-extensions/gravatar) est
facultatif et déclaré dans `<utilise>`. Quand Gravatar 3.x est actif sous SPIP 4.4,
Astra utilise son image locale mise en cache, à partir de l’adresse du message.
Sans image ou sans plugin, l’initiale est conservée. Aucun réglage ni collecte
supplémentaire ; le plugin gère lui-même ses récupérations et son cache.
Sous SPIP 5, où cette version de Gravatar ne s’active pas, le repli fonctionne.
Les [preuves et limites](docs/inventaires/gravatar-commentaires/bilan.md) décrivent
la comparaison avec Astra 4.13.8, sur article, réponse et page unique.

## Configuration

- Page d'accueil : derniers articles ou une page unique.
- Palette globale de neuf couleurs (les mêmes rôles que dans Astra).
- Largeur du conteneur et du conteneur étroit.
- Disposition de l'accueil et des listes : barre latérale à droite, à gauche, ou aucune.
- Disposition des articles : conteneur étroit (valeur par défaut d'Astra), barre latérale à droite ou à gauche, pleine largeur.
- Polices du texte et des titres (pile CSS, ou Raleway et Poppins, fournies avec le thème et servies par le site), nombre d'articles par page.
- Affichage des auteurs, des extraits, du lien « Accueil » et du bouton de retour en haut.
- Neuvième couleur de la palette : hexadécimale ou `rgba()`, comme dans Astra.
- **Couleurs et typographie** (Configuration › Squelettes) : réglages de style du Customizer d'Astra, sous leurs noms d'options (`header-menu1-color-responsive`, `button-radius-fields`…) — menus, bouton, bouton de l'en-tête, identité du site (couleurs, tailles, affichage du nom et du slogan, largeur du logo), widgets et copyright du pied, icônes sociales, bouton du menu mobile (minimal, plein ou contour). Les valeurs d'un `site.json` de modèle Astra se reprennent telles quelles ; la plupart des groupes appliquent la valeur ordinateur et conservent tablette/mobile. L’identité, les alignements et les marges des rangées appliquent leurs trois appareils. Sans réglage, rien ne change.
- **Identité responsive** : titre, slogan, visibilité et largeur du logo se règlent sur ordinateur,
  tablette et mobile (921/544 px). Une taille vide laisse la cascade agir ; une visibilité absente
  prend le défaut de son appareil, tandis que faux/0 reste explicite. Les valeurs déjà stockées
  sur tablette/mobile deviennent actives. La définition du fichier de logo couvre la plus grande
  largeur demandée sans agrandir l’original ; le cadre du bureau reste inchangé. Les logos distincts
  mobile/transparent et le traitement SVG ne sont pas ajoutés.
- **En-tête transparent** (même formulaire) : l'en-tête se superpose au début du contenu, avec ses propres couleurs, sur tout le site ou page par page, avec les exclusions d'Astra (archives, recherche, 404, accueil des derniers articles, pages, articles) et la restriction par appareil ; règles d'Astra 4.13.8 sur les trois appareils (`inc/astra_entete_transparent.php`). Logo propre à l'en-tête transparent : non repris.
- Graisses des titres (`headings-font-weight`, `font-weight-h1` à `h6`), marge intérieure du
  bouton de l'en-tête et alignements du copyright et des icônes sociales du pied (même formulaire ;
  les alignements s'appliquent sur les trois appareils).
- **Alignements des widgets du pied** : `footer-widget-alignment-1` à `-4`, sur les trois
  appareils, dans leurs quatre sections du formulaire. « Sans réglage » laisse agir la cascade ;
  tablette/mobile vides gardent la valeur du plus grand écran. Left/right restent physiques en
  RTL. Les couleurs ne sont pas requises ; seuls les widgets placés au pied sont concernés,
  dans le profil flex actuel d’Astra (les anciens profils non flex ne sont pas ajoutés).
- **Marges des rangées** (même formulaire) : marges intérieures et extérieures des rangées haute,
  principale et basse de l'en-tête et du pied, du pied entier et marge extérieure de l'en-tête
  entier (`section-*-builder-padding`, `-margin`), saisies et appliquées sur les trois appareils,
  avec les conditions d'émission d'Astra (rangée remplie) ; les marges extérieures acceptent les
  nombres négatifs.
- **Drapeaux de compatibilité** (méta `astra/compatibilite`, sans formulaire : ils viennent d'un
  import) : `submenu-below-header` et `can-support-widget-and-editor-fonts`, qu'un `site.json` de
  Starter Template garde d'un site antérieur et qui changent le CSS d'Astra (hauteur de l'en-tête,
  graisses des titres et des titres des widgets).
- Texte du pied de page (raccourcis SPIP, marqueurs `[annee]` et `[site]`).

## Tests

Prérequis : PHP 8.2+ et Composer pour l'outillage (le plugin lui-même fonctionne en PHP 7.4+),
spip-cli avec le correctif de la MR !91 pour l'environnement d'intégration.

    composer install
    composer tests-unit                                   # sans SPIP
    SPIP_CLI="php /chemin/spip-cli/bin/spip" composer install-spip-test
    composer tests-integration                            # SPIP 4.4 de test, SQLite

L'environnement d'intégration comprend les plugins Pages et a2a (GPL v3) et des pages uniques
(dont deux sous-pages), pour tester le gabarit de page, les menus et l'accueil statique.

Les références WordPress du builder sont décrites dans `tests/references/README.md`. Avec ces
références, un WordPress de test et le SPIP de test servi par un serveur web :

    WP=… PW=… SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404
    WP=… PW=… SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404 R14-bouton-mobile R16-transparent-page R17-transparent-global R19-compat-polices-widgets R20-titres-graisses R20-titres-graisses-drapeau R21-bouton-entete-marge R21-bouton-entete-marge-valeur R21-bouton-entete-marge-vide R22-pied-alignements R22-pied-alignements-ordinateur R22-pied-alignements-tablette R23-garden-compat R24-pied-marges R24-marges-toutes R24-marges-rangees-vides
    WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404
    URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire.cjs
    RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
    URL=http://127.0.0.1:8404 NODE_PATH=$PW/node_modules node tests/e2e/tableaux.cjs
    URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire_styles.cjs

- `comparer_html.php` compare l'ossature HTML de l'en-tête, du panneau mobile et du pied de
  page à celle de WordPress : sans argument, pour les configurations de référence du builder
  (R0–R5, R12, R13, R15 et R18) et les trois de pages (P1–P3, qui comparent aussi le contenu et
  les classes du `body`) ; les références qui posent des styles (R6–R11, R14, R16, R17, R19–R24)
  se passent en arguments, comme dans la deuxième commande. Sont comparés : balises, classes et
  identifiants des rangées, des zones et des éléments, et du premier niveau à l'intérieur de
  chaque élément. Les contenus, les formulaires et les SVG ne sont pas comparés : ce n'est pas
  une identité complète du HTML (R13 : deux différences de nommage attendues, voir
  `tests/references/README.md`) ;
- `comparer_captures.sh` compare au pixel près (sans argument : R0–R24 ; P1–P3 en arguments)
  l'en-tête, le pied de page et le menu mobile ouvert, sur ordinateur et sur mobile (seuil : 1 % de pixels différents, variable `SEUIL`) ; les
  images et leurs différences sont enregistrées dans `tests/comparaison/sorties/`. Une référence
  peut aussi lister des styles calculés à comparer (`styles` dans `tests/references/wp/<conf>.json`) :
  R5 vérifie ainsi la hauteur, le fond et le séparateur des rangées de l'en-tête, qu'un filet
  d'un pixel laisserait sous le seuil des captures. Les références R6–R11 (styles du Customizer,
  valeurs de Garden Maintenance) comparent aussi les styles calculés au survol et au focus, à
  1280, 768 et 390 px, et vérifient que les polices locales sont chargées des deux côtés ;
  R12–R15 couvrent les répartitions par appareil, les widgets menu, le bouton du menu mobile et
  l'icône Google Reviews ; R16 et R17 l'en-tête transparent ; R18–R23 les drapeaux de
  compatibilité, les graisses des titres, la marge du bouton de l'en-tête et les alignements du
  pied ; R24 les marges des rangées et leurs conditions d'émission ;
- `tests/e2e/formulaire_styles.cjs` vérifie le formulaire « Couleurs et typographie » de bout en
  bout (enregistrement, valeur refusée, site public) ;
- `tests/e2e/formulaire.cjs` vérifie le formulaire de configuration de bout en bout ;
- `tests/e2e/tableaux.cjs` vérifie que les tableaux SPIP du texte sont stylés, et pas ceux d'un
  bloc tableau importé (`figure.wp-block-table`) ;
- `tests/e2e/repartitions.cjs` vérifie dans Chromium les quatorze répartitions de colonnes du
  pied (proportions sur ordinateur et tablette, une colonne sur mobile), les répartitions
  tablette et mobile choisies à part (dont `3-firstrow` et `3-lastrow`) et la largeur des
  rangées du pied avec un conteneur de 1600 px. Ces réglages sortent des cinq configurations
  de référence.

`tests/comparaison/avant_apres.sh` capture le HTML et l’affichage (1280 et 390 px) des routes d’Astra pour
plusieurs configurations du builder, sans autre plugin de gabarits ; `avant_apres.cjs comparer` compare deux
captures (HTML entier, pixels). Il sert à vérifier qu’une réorganisation des squelettes ne change pas le rendu :

    SPIP_CLI=… NODE_PATH=$PW/node_modules tests/comparaison/avant_apres.sh vendor/spip/spip http://127.0.0.1:8404 tests/comparaison/sorties/avant
    # … modification …
    SPIP_CLI=… NODE_PATH=$PW/node_modules tests/comparaison/avant_apres.sh vendor/spip/spip http://127.0.0.1:8404 tests/comparaison/sorties/apres
    NODE_PATH=$PW/node_modules node tests/comparaison/avant_apres.cjs comparer tests/comparaison/sorties/avant tests/comparaison/sorties/apres

La comparaison des blocs importés (WordPress sur MySQL, import par wp2spip) est décrite dans
`tests/references/blocs/README.md`.

`PW` désigne un dossier où sont installés `playwright`, `pixelmatch` et `pngjs` (npm).

Le builder d’en-tête et de pied de page cible SPIP 4.4.

## Licence

GNU GPL v3 ou ultérieure (fichier `LICENSE`).

Les sources, auteurs, versions, licences et adaptations sont détaillés dans
[CREDITS.md](CREDITS.md) ; les notices amont et textes de licences sont conservés dans
[`licenses/`](licenses/).

Le portage reprend Astra 4.13.8 (Brainstorm Force / WPAstra, basé sur Underscores par
Automattic, GPL v2 ou ultérieure), des styles de WordPress 7.1.3 (ses contributeurs,
GPL v2 ou ultérieure) et les styles de normalisation crédités par Astra à Nicolas
Gallagher et Jonathan Neal (normalize.css, MIT).

Les huit SVG identifiés comme issus de Font Awesome Free sont crédités à Fonticons,
Inc., sous CC BY 4.0. Les autres SVG repris d'Astra et les limites de leur attribution
sont recensés séparément dans `CREDITS.md`.

Les [preuves de l’identité responsive](docs/inventaires/identite-responsive/bilan.md) identifient
le commit testé, les oracles WordPress, les comparaisons et les limites conservées.

Les [preuves des alignements des widgets](docs/inventaires/widgets-alignements/bilan.md)
partagent la qualification historique du lot identité ; les douze mesures Garden ne sont
mises à jour qu’après publication et un lot consommateur Garden.

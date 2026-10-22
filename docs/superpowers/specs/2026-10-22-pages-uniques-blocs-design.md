# Pages uniques et blocs natifs WordPress — spécification

Date : 2026-10-22. Statut : à relire.

## Objectif

Afficher correctement dans Astra SPIP les contenus qu'un site WordPress importé par wp2spip
apporte, quel que soit le modèle de démarrage retenu ensuite :

1. **Pages uniques** : gabarit de page d'Astra, sous-pages, page d'accueil statique, réglages
   d'affichage par page (titre, largeur du contenu) ;
2. **Blocs natifs** : CSS des blocs de l'éditeur WordPress dont wp2spip conserve la structure
   (classes `wp-block-*`).

Ces deux chantiers précèdent la décision sur Garden Maintenance : l'inventaire de Codex
(`docs/inventaires/garden-maintenance.md`) montre que ses six pages sont des pages statiques,
avec accueil statique, titre masqué et conteneur pleine largeur ; ses blocs Spectra feront
l'objet d'un chantier distinct (voir « Suites »).

## Contexte

### Ce que produit wp2spip

D'après sa documentation (`docs/site/correspondances/`) et son code (`inc/wp2spip_blocs.php`) :

- une page WordPress devient une **page unique** du plugin Pages : article hors rubrique
  (`id_rubrique = -1`), champ `page` = `wordpress_page_<ID>` ;
- la parenté des pages devient des liens du plugin **a2a** de type `sous_page`, du parent vers
  l'enfant, ordonnés par `rang` (ordre WordPress : `menu_order`, titre, ID) ; wp2spip n'installe
  a2a que si le site a des pages enfants, et ne fournit aucun squelette ;
- le réglage WordPress « page d'accueil statique » (`show_on_front`, `page_on_front`) **n'est pas
  importé** ;
- les métadonnées de page d'Astra (titre masqué, disposition du contenu) **ne sont pas
  importées**, pas plus que `_wp_page_template` ;
- blocs :
  - `columns`, `column`, `group`, `buttons`, `pullquote`, `table`, `quote` : balise englobante
    gardée, **seules les classes `wp-block-*` sont conservées**, sans attribut `style` ;
  - `paragraph`, `heading`, `list`, `code`, `preformatted`, `verse`, `separator`… : HTML intérieur
    gardé, nettoyé de la même façon ;
  - `button` : `<div class="wp-block-button">[texte->url]</div>` (le lien SPIP n'a pas la classe
    `wp-block-button__link`) ;
  - `cover`, `media-text` : `<div class="wp-block-cover|wp-block-media-text"><docN>texte</div>` ;
  - `image` : modèle `<imgN>` avec alignement ; `gallery` : album `<albumN>` ;
  - les classes de présentation (`has-*-color`, `has-text-align-*`, `is-layout-*`,
    `alignwide`, `is-style-*`, `wp-container-*`) et les styles en ligne sont **perdus**.

### Ce que charge WordPress (référence : WordPress 7.1.3, Astra 4.11.0)

Le WordPress de test ne charge pas la feuille complète de la bibliothèque de blocs : il insère
`wp-block-library-inline-css` (styles communs), une feuille par bloc présent dans la page
(`wp-block-<bloc>-inline-css`, d'après `wp-includes/blocks/<bloc>/style.css`) et
`global-styles-inline-css` (variables des préréglages et règles de disposition). Astra ne
déclare pas `wp-block-styles` : `theme.css` n'est pas chargé. Astra ajoute ses propres règles de
blocs, déjà en partie présentes dans `css/astra-dynamic.css` (83 règles `wp-block`) et
`css/main.min.css`.

Les règles de disposition générées par WordPress utilisent notamment des classes par bloc,
comme `wp-block-columns-is-layout-flex`, que le filtre de wp2spip conserve (elles commencent
par `wp-block-`), et des classes par instance (`wp-container-core-columns-is-layout-<n>`), qu'il
retire. Le rendu exact après import est donc à mesurer, pas à supposer.

### Ce que fait aujourd'hui Astra SPIP

`article.html` rend tout article, page unique comprise, comme un article de blog : métadonnées
(auteur, date), mots-clés, navigation précédent/suivant, schéma `CreativeWork`. Le menu peut
lister les pages uniques (source `pages`, un niveau, état courant depuis 977c03c). Il n'existe
ni page d'accueil statique ni réglage par page.

## Périmètre

### A. Pages uniques

#### A1. Gabarit de page

Une page unique est rendue avec le gabarit de page d'Astra (`page.php`, `template-parts/content-page.php`)
au lieu du gabarit d'article :

- `<article class="post-N page type-page status-publish ast-article-single">` et schéma `WebPage`
  sur le `body` (balisage exact relevé sur la référence WordPress, voir Validation) ;
- en-tête : titre seul, sans auteur, date ni mots-clés ; logo de l'article en image mise en avant
  s'il existe ;
- contenu : chapo, texte, post-scriptum, notes et documents joints, comme pour un article ;
- pas de navigation précédent/suivant ;
- commentaires si le forum est ouvert sur la page, comme un article ;
- classes du `body` d'une page WordPress (`page-template-default page page-id-N`…), produites par
  `astra_body_class('page', id)` ;
- disposition et barre latérale : celles des pages dans Astra (réglages `single-page-*` d'Astra).
  Sur la référence, la page d'exemple a `ast-plain-container ast-no-sidebar` : pas de barre
  latérale par défaut, contrairement aux articles.

**Sélection du gabarit** : pipeline `styliser`. Pour le fond `article` d'un article dont
`id_rubrique = -1`, le squelette `page_unique.html` du plugin est choisi s'il existe dans le
chemin. Un squelette de site peut ainsi le surcharger. Le pipeline n'agit que si le plugin Pages
est actif. Le reste de la page (`head`, en-tête, pied) reprend les inclusions existantes.

Alternative écartée : une condition dans `article.html`. Elle mélangerait les deux gabarits dans
un même fichier, et `[( )]` ne peut pas contenir d'inclusion : il faudrait des boucles
`CONDITION` sur tout le contenu.

#### A2. Sous-pages

Astra n'affiche pas les sous-pages dans le contenu d'une page ; WordPress les montre dans le
menu (sous-menus d'un menu de pages). Le portage fait de même :

- **source de menu `pages`** :
  - premier niveau : pages uniques publiées qui ne sont l'enfant d'aucune autre (pas de lien
    `sous_page` entrant), triées par numéro puis titre ;
  - second niveau (menus à sous-menus de l'en-tête et menu mobile) : leurs enfants, par `rang`
    du lien `sous_page` ;
  - état actif : `current-menu-item` sur la page affichée, `current-menu-ancestor` sur sa page
    parente ;
  - au-delà de deux niveaux : non affiché, comme pour les rubriques ;
- sans a2a, ou sans lien `sous_page` : liste à plat, comportement actuel ;
- le menu du pied reste sur un seul niveau.

Le fil d'Ariane n'est pas ajouté aux pages : Astra le désactive par défaut et le portage ne
l'affiche pas non plus sur les articles.

#### A3. Page d'accueil statique

Réglage du formulaire de configuration du thème (`configurer_astra`), section « Page d'accueil » :

- `accueil_type` : `articles` (défaut, comportement actuel) ou `page` ;
- `accueil_page` : identifiant d'une page unique publiée.

Avec `page`, `sommaire.html` rend cette page avec le gabarit A1, plus la classe `home` du `body`.
La page reste accessible à sa propre adresse (comme dans WordPress, où l'adresse de la page
redirige vers l'accueil ; la redirection n'est pas reprise : on garde l'adresse SPIP, avec une
URL canonique vers l'accueil). Si la page choisie n'est plus publiée, l'accueil revient aux
derniers articles.

La vérification du formulaire refuse une page inexistante, non publiée ou qui n'est pas une
page unique. Le réglage n'est proposé que si le plugin Pages est actif.

Hors périmètre : la « page des articles » de WordPress (`page_for_posts`). Dans SPIP, les listes
d'articles sont celles des rubriques.

#### A4. Réglages d'affichage par page

Astra permet, page par page, de masquer le titre et de choisir la disposition du contenu ; toutes
les pages de Garden Maintenance utilisent « titre masqué » et « pleine largeur ». Le portage
propose deux réglages par page unique :

- `titre` : `afficher` (défaut) ou `masquer` (titre gardé pour les lecteurs d'écran, avec la classe
  `screen-reader-text`, comme le fait Astra) ;
- `disposition` : `defaut` (celle des pages), `normale`, `etroite` ou `pleine` (conteneur pleine
  largeur, sans barre latérale), avec les classes d'Astra correspondantes (`ast-plain-container`,
  `ast-narrow-container`, `ast-full-width-layout`…, relevées sur la référence).

Stockage : méta `astra`, sous-clé `pages/<id_article>` (pas de champ ajouté à `spip_articles`).
Saisie : petit formulaire CVT `configurer_astra_page` affiché sur la fiche d'une page unique dans
l'espace privé (pipeline `affiche_milieu`), réservé à qui peut modifier la page. La configuration
d'une page supprimée est ignorée ; elle est effacée par le pipeline `trig_supprimer_objets_lies`
s'il est disponible.

L'import de ces réglages depuis les métadonnées WordPress (`site-post-title`,
`ast-site-content-layout`) relève de wp2spip et n'est pas fait ici (voir « Suites »).

### B. Blocs natifs WordPress

#### B1. Feuille `css/astra-blocs.css`

Générée par `tests/references/extraire_css_blocs.py` à partir des sources de la référence, sans
modification à la main :

1. styles communs (`wp-includes/css/dist/block-library/common.css`) ;
2. styles des blocs gardés par wp2spip (`wp-includes/blocks/<bloc>/style.css`) :
   `paragraph`, `heading`, `list`, `quote`, `pullquote`, `code`, `preformatted`, `verse`,
   `separator`, `table`, `columns`, `column`, `group`, `buttons`, `button`, `cover`,
   `media-text` ;
3. règles de `global-styles-inline-css` de la page de démonstration (variables des préréglages,
   dispositions par bloc).

Le générateur ne garde une règle que si **tous ses sélecteurs peuvent correspondre** à du HTML
importé : chaque classe d'un sélecteur doit commencer par `wp-block-`, ou être une classe
produite par SPIP et explicitement associée (liste ci-dessous). Les règles ne visant que des
classes perdues (`has-*`, `is-style-*`, `wp-container-*`…) sont écartées. Les règles déjà présentes
à l'identique dans `css/astra-dynamic.css` ne sont pas répétées.

La feuille est chargée après `css/astra-dynamic.css`, avant `css/astra-hfb.css`. Elle est
volumineuse mais statique ; le chargement conditionnel par bloc n'est pas fait (voir « Suites »).

#### B2. Correspondances SPIP

Quelques éléments n'ont pas la classe attendue par le CSS de WordPress. Des règles écrites à la
main, dans `css/astra-spip.css`, font le lien, sans dupliquer les valeurs (elles reprennent les
variables d'Astra) :

| HTML importé | Équivalent WordPress | Règle |
|---|---|---|
| `.wp-block-button > a` (lien SPIP) | `.wp-block-button__link` | style de bouton d'Astra |
| `.wp-block-cover > .spip_documents` | image de fond de la couverture | image en couverture du bloc |
| `.wp-block-media-text > .spip_documents` | `.wp-block-media-text__media` | colonne média |
| paragraphes ajoutés par SPIP dans une colonne | contenu de `.wp-block-column` | marges neutralisées |

La liste exacte est arrêtée après la première comparaison : on n'écrit une correspondance que
pour un écart mesuré.

#### B3. Ce qui n'est pas restylé

- images `<imgN>` et albums `<albumN>` : modèles SPIP, déjà stylés par le portage ; leur fidélité
  à `wp-block-image` et `wp-block-gallery` est hors périmètre ;
- couleurs, tailles et alignements choisis par bloc dans WordPress : leurs classes sont perdues à
  l'import, le rendu prend les valeurs par défaut ;
- blocs dynamiques et blocs d'extensions (dont `spectra/*`).

## Licences

- WordPress (`wp-includes`) : GPL v2 ou ultérieure, compatible avec la GPL v3 du plugin ;
  crédit ajouté à `paquet.xml` et au README, avec la version de référence.
- Pages (GPL v3) et a2a : `utilise` dans `paquet.xml`, aucune dépendance obligatoire ; la licence
  d'a2a est relevée dans son `paquet.xml` avant l'ajout.
- Contenus de démonstration : textes écrits pour les tests, aucune reprise du contenu d'un modèle
  de démarrage (règle du projet : textes et photos des modèles jamais versionnés).

## Validation

Même méthode que pour le builder : références WordPress, rendu SPIP, comparaison.

### Pages uniques

- **Références WordPress** : nouvelles configurations dans `tests/references/wp/` :
  - `P1-page` : page simple avec commentaires fermés, page parente et deux sous-pages, menu de pages ;
  - `P2-accueil` : accueil statique sur une page ;
  - `P3-page-pleine` : titre masqué et disposition pleine largeur.
- **Intégration (PHPUnit)** sur le SPIP 4.4 de test, avec Pages et a2a installés par
  `scripts/install-spip-test.sh` :
  - gabarit choisi par `styliser` pour une page unique, pas pour un article ;
  - absence des métadonnées et de la navigation ; classes du `body` ;
  - menu de pages à deux niveaux, ordre par `rang`, états courant et ancêtre ; liste à plat sans a2a ;
  - accueil statique : rendu, repli si la page est dépubliée, vérification du formulaire ;
  - réglages par page : titre masqué, disposition, droits du formulaire.
- **Comparaisons** : `comparer_html.php` et `comparer_captures.sh` étendus à `#content`
  (gabarit de page), en plus de l'en-tête et du pied, pour P1 à P3.

### Blocs

- **Page de démonstration** créée dans le WordPress de test par `tests/references/wp/blocs.php` :
  un exemple de chaque bloc de B1, avec des variantes (deux et trois colonnes, groupe imbriqué,
  citation et citation en exergue, tableau avec en-tête, boutons groupés, couverture, média et
  texte).
- **Import réel par wp2spip** dans un SPIP de test dédié (dépôt local `/src/wordpress/wp2spip`,
  commande `wordpress:importer`), pour mesurer le HTML effectivement produit, pas une
  approximation. Le texte importé est exporté (`tests/references/sorties/blocs/texte.spip`,
  non versionné) puis inséré dans un article du SPIP d'intégration.
- **Comparaison** bloc par bloc : capture de chaque bloc dans WordPress et dans SPIP, ordinateur et
  mobile. Seuil : 1 % de pixels différents pour les blocs sans classe perdue ; pour les autres,
  l'écart attendu est consigné dans `tests/references/README.md` et vérifié comme tel.
- **Générateur** : test unitaire du filtre de sélecteurs (règle gardée, écartée, sélecteur
  multiple partiellement valide).

### Non-régression

Toute la suite existante passe : tests unitaires et d'intégration, comparaisons R0–R4,
formulaires, `tests/e2e/repartitions.cjs`. Le plugin reste compatible PHP 7.4 (vérifié avec
`php7.4 -l`). SPIP 4.4 seul est vérifié.

## Découpage proposé

1. Références WordPress P1–P3 et page de démonstration des blocs ; installation de Pages et a2a
   dans l'environnement d'intégration.
2. Gabarit de page (A1), pipeline `styliser`, tests et comparaison P1.
3. Sous-pages dans le menu (A2).
4. Accueil statique (A3) et comparaison P2.
5. Réglages par page (A4) et comparaison P3.
6. Générateur et feuille `css/astra-blocs.css` (B1), test du filtre.
7. Import par wp2spip, comparaison bloc par bloc, correspondances SPIP (B2).

Les lots A et B sont indépendants : B peut commencer avant la fin de A.

## Suites (hors de cette spec)

- **wp2spip** (dépôt distinct, à proposer, pas à modifier ici) : importer l'accueil statique et les
  réglages de page d'Astra vers les clés `astra/accueil_*` et `astra/pages/<id>` ; conserver les
  classes de couleur et d'alignement utiles.
- **Builder** (relevés de l'inventaire Garden Maintenance) : répartition propre à la tablette
  (`tablet=full`), bloc « menu » dans les widgets du pied, menus à sélection et ordre libres.
- **Spectra** : conversion des neuf familles recensées par l'inventaire, sous forme d'extension de
  conversion de wp2spip et de modèles SPIP ; une spec dédiée, après décision.
- Chargement conditionnel du CSS des blocs, selon les blocs présents dans la page.
- SPIP 5.0.

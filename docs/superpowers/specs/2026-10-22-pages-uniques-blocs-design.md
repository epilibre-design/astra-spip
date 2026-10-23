# Pages uniques et blocs natifs WordPress — spécification

Date : 2026-10-22. Statut : implémentée (version 1.1.0).

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

**Sélection du gabarit** : pipeline `styliser` d'Astra SPIP, pour le fond `article` d'un article
dont `id_rubrique = -1`, seulement si le plugin Pages est actif. Le plugin Pages a lui-même un
pipeline `styliser` qui choisit `article=<identifiant de la page>.html` quand ce squelette existe.
Priorité, de la plus forte à la plus faible :

1. variante propre à une page, `article=<page>.html`, choisie par Pages (dans un squelette de site
   ou un plugin) : Astra SPIP ne remplace jamais ce choix ;
2. `page_unique.html` du site (dossier `squelettes/` ou plugin placé avant Astra dans le chemin) ;
3. `page_unique.html` d'Astra SPIP.

En pratique, le pipeline d'Astra SPIP n'agit que si le squelette retenu jusque-là est
`article.html` (fond `article` résolu sans variante) ; il cherche alors `page_unique.html` avec
`find_in_path()`, ce qui donne la priorité au site sur le plugin. Il ne dépend pas de l'ordre
d'exécution des deux pipelines : si celui d'Astra SPIP passe avant celui de Pages, Pages remplace
de toute façon le résultat par sa variante quand elle existe (à vérifier dans le test de
coexistence). Le reste de la page (`head`, en-tête, pied) reprend les inclusions existantes.

Alternative écartée : une condition dans `article.html`. Elle mélangerait les deux gabarits dans
un même fichier, et `[( )]` ne peut pas contenir d'inclusion : il faudrait des boucles
`CONDITION` sur tout le contenu.

#### A2. Sous-pages

Astra n'affiche pas les sous-pages dans le contenu d'une page ; WordPress les montre dans le
menu (sous-menus d'un menu de pages). Le portage fait de même :

- **source de menu `pages`** :
  - premier niveau : pages uniques publiées **sans parente publiée** : aucune page publiée ne
    leur est liée par un lien `sous_page` entrant. Une page dont la parente est dépubliée,
    supprimée ou n'est plus une page unique remonte ainsi au premier niveau au lieu de
    disparaître du menu. Tri par numéro puis titre ;
  - second niveau (menus à sous-menus de l'en-tête et menu mobile) : leurs enfants publiés, par
    `rang` du lien `sous_page` ;
  - une page enfant de plusieurs parentes publiées (liens multiples, possibles dans a2a) apparaît
    sous chacune d'elles ;
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

- `titre` : `afficher` (défaut) ou `masquer`. Comme dans Astra (`site-post-title` à `disabled`),
  le titre **n'est pas produit** : pas de titre caché pour les lecteurs d'écran, qui doublerait le
  titre de niveau 1 que ces pages placent en général dans leur contenu (c'est le cas des six
  pages de Garden Maintenance). Le titre du document (`<title>`) reste celui de la page ;
- `disposition` : `defaut`, `normale`, `etroite` ou `pleine`, définies ci-dessous.

**Dispositions.** Elles reprennent les dispositions de contenu d'Astra (`astra_get_content_layout()`
et classes du `body` dans `inc/markup-extras.php`) :

| Valeur SPIP | Disposition Astra | Classe du `body` | Largeur du contenu | Barre latérale |
|---|---|---|---|---|
| `defaut` | réglage des pages (`single-page-ast-content-layout`) | celle de la disposition retenue | — | — |
| `normale` | `normal-width-container` → `plain-container` | `ast-plain-container` | `--ast-normal-container-width` | selon le réglage des pages |
| `etroite` | `narrow-width-container` → `narrow-container` | `ast-narrow-container` | `--ast-narrow-container-width` | aucune (Astra n'en affiche pas en disposition étroite) |
| `pleine` | `full-width-container` → `page-builder` | `ast-page-builder-template` | toute la largeur, sans marge du conteneur | aucune |

Sur la référence WordPress, la disposition des pages par défaut donne `ast-plain-container
ast-no-sidebar`. Le HTML et le CSS de chaque disposition sont relevés sur les références P1 et P3,
pas déduits.

Stockage : méta `astra`, sous-clé `pages/<id_article>` (pas de champ ajouté à `spip_articles`).

Saisie : formulaire CVT **`editer_astra_page`**, appelé avec `id_article`, affiché sur la fiche
d'une page unique dans l'espace privé (pipeline `affiche_milieu`). Le nom `configurer_*` est écarté :
SPIP (`ecrire/inc/cvt_configurer.php`) impose à tout formulaire `configurer_*`, même doté de ses
propres fonctions CVT, l'autorisation `configurer`, réservée aux administrateurs ; un rédacteur
autorisé à modifier la page en serait exclu. `charger()` et `traiter()` vérifient
`autoriser('modifier', 'article', $id_article)` et que l'article est une page unique
(`id_rubrique = -1`) ; sinon le formulaire n'est pas éditable et rien n'est enregistré.

La configuration d'une page supprimée est ignorée à l'affichage ; elle est effacée par le pipeline
`trig_supprimer_objets_lies` s'il est disponible.

**Contexte transmis aux composants.** Le gabarit de page (et l'accueil statique) transmet aux
inclusions le contexte déjà utilisé par le builder, complété :

| Paramètre | Valeur |
|---|---|
| `type_page` | `page` (`sommaire` pour l'accueil, avec `accueil_page` renseigné) |
| `id_article` | la page affichée |
| `id_rubrique`, `id_secteur` | vides (une page unique n'a pas de rubrique) |
| `titre` | `afficher` ou `masquer` (réglage effectif, défaut résolu) |
| `disposition` | `normale`, `etroite` ou `pleine` (défaut résolu) |

Une fonction `astra_page_reglages($id_article)` renvoie le réglage effectif (valeurs par défaut
résolues) ; squelettes et tests s'appuient sur elle plutôt que sur la méta.

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

Le générateur décide, **sélecteur par sélecteur**, si un sélecteur peut encore correspondre au
HTML importé, où ne restent que les classes `wp-block-*` (et les classes produites par SPIP
associées en B2). Un simple filtrage des noms de classes ne suffit pas :
`.wp-block-columns:not(.is-not-stacked-on-mobile) > .wp-block-column` reste applicable après
l'import, puisque la classe niée a disparu et que la négation devient vraie ; c'est cette règle qui
empile les colonnes sur mobile.

Règles d'évaluation, sur l'arbre du sélecteur (analyseur CSS, pas expressions régulières) :

- une classe positive (`.x`, hors négation) : satisfiable si elle est conservée ; une classe perdue
  rend le sélecteur composé insatisfiable ;
- `:not(liste)` : satisfiable si la liste ne vise que des classes perdues (elle est alors
  toujours vraie) ; si elle vise des classes conservées, on la considère satisfiable ;
- `:is(liste)` et `:where(liste)` : satisfiables si au moins une branche l'est ;
- sélecteurs d'attribut sur `style` (`[style*=flex-basis]`) : insatisfiables, wp2spip retire
  l'attribut `style` ;
- en cas de doute (pseudo-classe ou construction non prise en charge par l'évaluation), le
  sélecteur est considéré satisfiable : on garde trop plutôt que pas assez.

**Le générateur ne réécrit jamais l'intérieur d'un sélecteur.** Il ne fait qu'une chose : retirer
d'une liste de sélecteurs (`a, b, c { … }`) les sélecteurs complexes entiers démontrés
insatisfiables, et écarter la règle si aucun ne reste. Les branches impossibles d'un `:is()` ou
d'un `:not()` restent en place : la spécificité de ces pseudo-classes est celle de leur argument le
plus spécifique, et retirer une branche, même impossible, pourrait la diminuer et changer la règle
gagnante. Retirer un sélecteur entier d'une liste ne change pas la spécificité des autres, chacun
ayant la sienne.

Les règles déjà présentes à l'identique dans `css/astra-dynamic.css` ne sont pas répétées. La
liste des classes conservées est celle de wp2spip (`wp2spip_balise_nettoyee()` : préfixe
`wp-block-`), relue dans son code à la version de référence.

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
  - coexistence avec Pages : une variante `article=<page>.html` l'emporte sur `page_unique.html` ;
    un `page_unique.html` du site l'emporte sur celui d'Astra SPIP ; les deux ordres d'exécution
    des pipelines donnent le même résultat ;
  - absence des métadonnées et de la navigation ; classes du `body` ;
  - menu de pages à deux niveaux, ordre par `rang`, états courant et ancêtre ; liste à plat sans a2a ;
  - parente dépubliée : l'enfant publié remonte au premier niveau ; parente republiée : il revient
    en sous-menu ;
  - accueil statique : rendu, repli si la page est dépubliée, vérification du formulaire ;
  - réglages par page : titre absent du HTML quand il est masqué, classes et largeur de chaque
    disposition, contexte transmis aux inclusions ;
  - droits du formulaire `editer_astra_page` : un rédacteur auteur de la page (autorisé à la
    modifier, pas à configurer le site) peut enregistrer ; un rédacteur non auteur ne le peut pas ;
    un article ordinaire est refusé.
- **Comparaisons** : `comparer_html.php` et `comparer_captures.sh` étendus à `#content`
  (gabarit de page), en plus de l'en-tête et du pied, pour P1 à P3.

### Blocs

- **Page de démonstration** créée dans le WordPress de test par `tests/references/wp/blocs.php` :
  un exemple de chaque bloc de B1, avec des variantes (deux et trois colonnes, groupe imbriqué,
  citation et citation en exergue, tableau avec en-tête, boutons groupés, couverture, média et
  texte).
- **Import réel par wp2spip** dans un SPIP de test dédié (dépôt local `/src/wordpress/wp2spip`,
  commande `wordpress:importer`), avec Astra SPIP actif, pour mesurer le HTML effectivement
  produit, pas une approximation. **La comparaison se fait sur ce site importé**, servi par
  `php -S` : le texte n'est pas recopié dans un autre site, car ses raccourcis `<docN>` et
  `<imgN>` désignent des documents de sa propre base. Le site importé est recréé par un script
  (`scripts/install-spip-blocs.sh`), à partir du WordPress de test.
- **Médias** : la page de démonstration utilise des images créées pour les tests (versionnées, de
  licence libre consignée). Le test vérifie que chaque couverture et chaque bloc média et texte
  affiche le document issu de **la même image** que dans WordPress (correspondance par empreinte
  du fichier original), pas seulement qu'un document est présent.
- **Comparaison** bloc par bloc : capture de chaque bloc dans WordPress et dans SPIP, ordinateur et
  mobile, seuil de 1 % de pixels différents. Un bloc ne peut dépasser ce seuil que pour une
  **perte d'import caractérisée**, consignée dans `tests/references/README.md` sous la forme :
  bloc, classe ou attribut perdu (relevé dans le HTML de WordPress et absent du HTML importé),
  propriété CSS concernée, effet visible attendu. Le test vérifie alors cette perte précise (par
  exemple : propriété calculée égale à la valeur par défaut) et le reste du bloc avec le seuil
  habituel, en masquant uniquement la zone concernée. Un écart sans perte caractérisée est un
  défaut à corriger, pas une exception.
- **Générateur** :
  - tests unitaires de l'évaluation des sélecteurs : classe conservée ou perdue, `:not()` sur
    classe perdue (`.wp-block-columns:not(.is-not-stacked-on-mobile) > .wp-block-column` gardé) et
    sur classe conservée, `:is()` et `:where()` avec branches mixtes (sélecteur gardé **à
    l'identique**, branches impossibles comprises), attribut `style`, liste de sélecteurs
    partiellement valide, règles dans une media query ;
  - test de cascade dans Chromium : pour des paires de règles en concurrence (dont un `:is()` avec
    une branche impossible plus spécifique), la propriété calculée sur un HTML importé est la même
    avec la feuille d'origine de WordPress et avec la feuille générée. Ce test porte sur la valeur
    calculée, pas seulement sur les éléments sélectionnés.

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

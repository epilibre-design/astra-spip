# Astra pour SPIP

Portage pour SPIP du thème WordPress [Astra](https://wordpress.org/themes/astra/) de Brainstorm Force (version 4.11.0).

Le plugin reproduit le rendu d'Astra avec ses réglages par défaut : même balisage HTML que le
thème WordPress, mêmes feuilles de style et même JavaScript. Les classes CSS d'Astra
(`ast-separate-container`, `ast-right-sidebar`, `blog-layout-4`…) s'appliquent donc telles quelles.

Compatibilité : SPIP 4.2 à 5.0.

## Installation

1. Copier le dossier dans `plugins/astra` (ou `plugins/auto/astra`).
2. Activer le plugin « Astra » dans l'espace privé.
3. Régler le thème dans **Configuration › Squelettes › Thème Astra**.

Le plugin fournit ses propres squelettes et remplace ceux de `squelettes-dist`. Un dossier
`squelettes/` à la racine du site reste prioritaire et permet de surcharger n'importe quel fichier.

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
- pied de page : 1 à 6 colonnes par rangée, avec les répartitions d'Astra ;
- éléments : identité du site, menus (rubriques racines, sous-rubriques d'une rubrique, articles
  d'un groupe de mots-clés, pages uniques si le plugin Pages est actif), textes libres, bouton,
  recherche, icônes sociales, zones de widgets, compte, copyright ;
- réglages des rangées : hauteur minimale, couleur de fond, bordure.

Sans réglage, l'en-tête et le pied de page sont ceux d'Astra par défaut : identité et menu
principal, copyright centré. Le balisage produit est celui d'Astra ; `css/astra-hfb.css`
contient le CSS que le builder d'Astra ajoute. Chaque élément peut être surchargé dans
`squelettes/inclure/hfb/`.

La configuration est enregistrée dans la méta `astra/hfb`, avec la structure des options
d'Astra (`header-desktop-items`, `header-mobile-items`, `footer-desktop-items`).

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
- **Réglages par page** : un bloc de la fiche de chaque page permet de masquer le titre et de
  choisir la disposition du contenu (celle des pages, normale, étroite ou pleine largeur), comme
  les réglages de page d'Astra. Ils sont enregistrés dans la méta `astra/pages/<id_article>` et
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

## Feuilles de style et scripts

- `css/main.min.css` et `js/frontend.min.js` : fichiers d'origine d'Astra 4.11.0, sans modification.
- `css/astra-dynamic.css` : le CSS « dynamique » qu'Astra génère en PHP à partir des réglages du
  Customizer. Il a été extrait d'un rendu WordPress réel (réglages par défaut, toutes les
  dispositions de page), puis rendu paramétrable : palette et largeurs passent par des variables CSS.
- `css/astra-hfb.css` : CSS ajouté par le builder d'Astra pour les rangées et éléments, extrait
  des références WordPress par `tests/references/extraire_css.py`.
- `inclure/head-vars.html` : variables CSS issues de la configuration (`--ast-global-color-0` à `8`,
  largeurs de conteneur, polices) et réglages des rangées du builder.
- `css/astra-article.css` et `css/astra-commentaires.css` : règles qu'Astra n'émet que sur les
  articles et là où des commentaires sont ouverts ou présents ; chargées dans les mêmes cas.
- `css/astra-pages.css` : règles de la disposition pleine largeur des pages, extraites par
  `tests/references/extraire_css.py --pages`.
- `css/astra-blocs.css` : CSS des blocs WordPress importés (voir plus haut).
- `css/astra-spip.css` : adaptations aux contenus produits par SPIP (documents, notes, chapo,
  formulaire de forum, fil d'Ariane, blocs importés…).

## Configuration

- Page d'accueil : derniers articles ou une page unique.
- Palette globale de neuf couleurs (les mêmes rôles que dans Astra).
- Largeur du conteneur et du conteneur étroit.
- Disposition de l'accueil et des listes : barre latérale à droite, à gauche, ou aucune.
- Disposition des articles : conteneur étroit (valeur par défaut d'Astra), barre latérale à droite ou à gauche, pleine largeur.
- Polices du texte et des titres, nombre d'articles par page.
- Affichage du nom du site, du slogan, des auteurs, des extraits, du lien « Accueil » et du bouton de retour en haut.
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
    WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404
    URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire.cjs
    RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
    URL=http://127.0.0.1:8404 NODE_PATH=$PW/node_modules node tests/e2e/tableaux.cjs

- `comparer_html.php` compare l'ossature HTML de l'en-tête, du panneau mobile et du pied de
  page à celle de WordPress, pour les six configurations de référence du builder (R0–R5) et
  les trois de pages (P1–P3, qui comparent aussi le contenu et les classes du `body`) : balises, classes et
  identifiants des rangées, des zones et des éléments, et du premier niveau à l'intérieur de
  chaque élément. Les contenus, les formulaires et les SVG ne sont pas comparés : ce n'est pas
  une identité complète du HTML ;
- `comparer_captures.sh` compare au pixel près l'en-tête, le pied de page et le menu mobile
  ouvert, sur ordinateur et sur mobile (seuil : 1 % de pixels différents, variable `SEUIL`) ; les
  images et leurs différences sont enregistrées dans `tests/comparaison/sorties/`. Une référence
  peut aussi lister des styles calculés à comparer (`styles` dans `tests/references/wp/<conf>.json`) :
  R5 vérifie ainsi la hauteur, le fond et le séparateur des rangées de l'en-tête, qu'un filet
  d'un pixel laisserait sous le seuil des captures ;
- `tests/e2e/formulaire.cjs` vérifie le formulaire de configuration de bout en bout ;
- `tests/e2e/tableaux.cjs` vérifie que les tableaux SPIP du texte sont stylés, et pas ceux d'un
  bloc tableau importé (`figure.wp-block-table`) ;
- `tests/e2e/repartitions.cjs` vérifie dans Chromium les quatorze répartitions de colonnes du
  pied (proportions sur ordinateur et tablette, une colonne sur mobile) et la largeur des
  rangées du pied avec un conteneur de 1600 px. Ces réglages sortent des cinq configurations
  de référence.

La comparaison des blocs importés (WordPress sur MySQL, import par wp2spip) est décrite dans
`tests/references/blocs/README.md`.

`PW` désigne un dossier où sont installés `playwright`, `pixelmatch` et `pngjs` (npm).

Le builder d'en-tête et de pied de page est vérifié sur SPIP 4.4 uniquement : SPIP 4.2 et 5.0,
bien que déclarés compatibles, ne sont pas vérifiés pour cette fonction.

## Licence

GNU GPL v3 ou ultérieure (fichier `LICENSE`).

Les sources, auteurs, versions, licences et adaptations sont détaillés dans
[CREDITS.md](CREDITS.md) ; les notices amont et textes de licences sont conservés dans
[`licenses/`](licenses/).

Le portage reprend Astra 4.11.0 (Brainstorm Force / WPAstra, basé sur Underscores par
Automattic, GPL v2 ou ultérieure), des styles de WordPress 7.1.3 (ses contributeurs,
GPL v2 ou ultérieure) et les styles de normalisation crédités par Astra à Nicolas
Gallagher et Jonathan Neal (normalize.css, MIT).

Les huit SVG identifiés comme issus de Font Awesome Free sont crédités à Fonticons,
Inc., sous CC BY 4.0. Les autres SVG repris d'Astra et les limites de leur attribution
sont recensés séparément dans `CREDITS.md`.

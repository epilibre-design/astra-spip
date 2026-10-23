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
| `index.php` (blog)              | `sommaire.html` : derniers articles          |
| `archive.php` (catégorie)       | `rubrique.html`                              |
| `archive.php` (étiquette)       | `mot.html`                                   |
| `archive.php` (auteur)          | `auteur.html`                                |
| `single.php`                    | `article.html`                               |
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

## Feuilles de style et scripts

- `css/main.min.css` et `js/frontend.min.js` : fichiers d'origine d'Astra 4.11.0, sans modification.
- `css/astra-dynamic.css` : le CSS « dynamique » qu'Astra génère en PHP à partir des réglages du
  Customizer. Il a été extrait d'un rendu WordPress réel (réglages par défaut, toutes les
  dispositions de page), puis rendu paramétrable : palette et largeurs passent par des variables CSS.
- `css/astra-hfb.css` : CSS ajouté par le builder d'Astra pour les rangées et éléments, extrait
  des références WordPress par `tests/references/extraire_css.py`.
- `inclure/head-vars.html` : variables CSS issues de la configuration (`--ast-global-color-0` à `8`,
  largeurs de conteneur, polices) et réglages des rangées du builder.
- `css/astra-spip.css` : adaptations aux contenus produits par SPIP (documents, notes, chapo,
  formulaire de forum, fil d'Ariane…).

## Configuration

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

L'environnement d'intégration comprend le plugin Pages (GPL v3) et une page unique, pour tester
les menus alimentés par les pages uniques.

Les références WordPress du builder sont décrites dans `tests/references/README.md`. Avec ces
références, un WordPress de test et le SPIP de test servi par un serveur web :

    WP=… PW=… SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8404
    WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8404
    URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire.cjs
    RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs

- `comparer_html.php` compare l'ossature HTML de l'en-tête, du panneau mobile et du pied de
  page à celle de WordPress, pour les cinq configurations de référence : balises, classes et
  identifiants des rangées, des zones et des éléments, et du premier niveau à l'intérieur de
  chaque élément. Les contenus, les formulaires et les SVG ne sont pas comparés : ce n'est pas
  une identité complète du HTML ;
- `comparer_captures.sh` compare au pixel près l'en-tête, le pied de page et le menu mobile
  ouvert, sur ordinateur et sur mobile (seuil : 1 % de pixels différents, variable `SEUIL`) ; les
  images et leurs différences sont enregistrées dans `tests/comparaison/sorties/` ;
- `tests/e2e/formulaire.cjs` vérifie le formulaire de configuration de bout en bout ;
- `tests/e2e/repartitions.cjs` vérifie dans Chromium les quatorze répartitions de colonnes du
  pied (proportions sur ordinateur et tablette, une colonne sur mobile) et la largeur des
  rangées du pied avec un conteneur de 1600 px. Ces réglages sortent des cinq configurations
  de référence.

`PW` désigne un dossier où sont installés `playwright`, `pixelmatch` et `pngjs` (npm).

Le builder d'en-tête et de pied de page est vérifié sur SPIP 4.4 uniquement : SPIP 4.2 et 5.0,
bien que déclarés compatibles, ne sont pas vérifiés pour cette fonction.

## Licence

GNU GPL v3 ou ultérieure (fichier `LICENSE`).

Le plugin reprend du code du thème Astra (© Brainstorm Force, basé sur Underscores © Automattic),
distribué sous GPL v2 ou ultérieure, ce qui permet sa redistribution sous GPL v3 :

- `css/main.min.css` et `js/frontend.min.js` : fichiers d'Astra 4.11.0, sans modification ;
- `css/astra-dynamic.css` : CSS généré par Astra, adapté ;
- le balisage HTML des squelettes, qui reproduit celui d'Astra.

`css/astra-blocs.css` reprend des règles de WordPress 7.1.3 (GPL v2 ou ultérieure), générées par
`tests/references/extraire_css_blocs.py`.

Astra intègre des icônes IcoMoon-Free, distribuées sous CC BY 4.0 ou GPL.

Les icônes des réseaux sociaux (`inc/astra_icones.php`), reprises d'Astra, sont celles de
[Font Awesome Free](https://fontawesome.com) (Fonticons, Inc.), sous licence
[CC BY 4.0](https://creativecommons.org/licenses/by/4.0/), compatible avec la GPL v3.

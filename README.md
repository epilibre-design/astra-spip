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
| `header.php` (header builder)   | `inclure/header.html`, `identite.html`, `menu.html` |
| `footer.php` (footer builder)   | `inclure/footer.html`                        |
| `sidebar.php`                   | `inclure/sidebar.html`                       |
| `comments.php`                  | `inclure/commentaires.html`, `commentaire.html` (forums SPIP) |
| `template-parts/content-blog.php` | `inclure/article-resume.html`              |
| `searchform.php`                | `formulaires/recherche.html`                 |
| pagination                      | `modeles/pagination_astra.html`              |
| Customizer                      | `formulaires/configurer_astra.html`          |

Le menu principal est construit à partir des rubriques de premier niveau et de leurs
sous-rubriques (sous-menus déroulants, menu mobile).

## Feuilles de style et scripts

- `css/main.min.css` et `js/frontend.min.js` : fichiers d'origine d'Astra 4.11.0, sans modification.
- `css/astra-dynamic.css` : le CSS « dynamique » qu'Astra génère en PHP à partir des réglages du
  Customizer. Il a été extrait d'un rendu WordPress réel (réglages par défaut, toutes les
  dispositions de page), puis rendu paramétrable : palette et largeurs passent par des variables CSS.
- `inclure/head-vars.html` : variables CSS issues de la configuration (`--ast-global-color-0` à `8`,
  largeurs de conteneur, polices).
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

Les références WordPress du builder sont décrites dans `tests/references/README.md`.

Le builder d'en-tête et de pied de page est vérifié sur SPIP 4.4 uniquement : SPIP 4.2 et 5.0,
bien que déclarés compatibles, ne sont pas vérifiés pour cette fonction.

## Licence

GNU GPL v3 ou ultérieure (fichier `LICENSE`).

Le plugin reprend du code du thème Astra (© Brainstorm Force, basé sur Underscores © Automattic),
distribué sous GPL v2 ou ultérieure, ce qui permet sa redistribution sous GPL v3 :

- `css/main.min.css` et `js/frontend.min.js` : fichiers d'Astra 4.11.0, sans modification ;
- `css/astra-dynamic.css` : CSS généré par Astra, adapté ;
- le balisage HTML des squelettes, qui reproduit celui d'Astra.

Astra intègre des icônes IcoMoon-Free, distribuées sous CC BY 4.0 ou GPL.

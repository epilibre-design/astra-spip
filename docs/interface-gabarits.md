# Interface pour les gabarits assemblés

Les pages d’Astra (`article.html`, `rubrique.html`…) sont des squelettes complets. Un squelette assemblé par ailleurs (par exemple par un moteur de blocs) peut réutiliser l’enveloppe et les éléments d’Astra par les inclusions et les fonctions ci-dessous. Elles forment une interface stable : leurs paramètres et leur balisage ne changent qu’avec une note de version. Astra ne dépend d’aucun plugin de gabarits.

Les autres squelettes de `inclure/` et `inclure/hfb/` restent internes au thème.

## Ossature d’une page

```html
<!DOCTYPE html>
<html dir="#LANG_DIR" lang="#LANG" class="[(#LANG_DIR)][ (#LANG)] no-js">
<head>
<title>…</title>
<INCLURE{fond=inclure/head}{type_page=article}{contexte=article}{id=#ENV{id_article}} />
</head>
<body class="[(#VAL{article}|astra_body_class{#ENV{id_article}})]">
<INCLURE{fond=inclure/body-debut} />
<INCLURE{fond=inclure/page-debut} />
<INCLURE{fond=inclure/masthead}{type_page=article}{id_rubrique}{id_secteur}{id_article} />
<div id="content" class="site-content"><div class="ast-container">
	<div id="primary" class="content-area primary"><main id="main" class="site-main">…</main></div>
</div></div>
<INCLURE{fond=inclure/panneau-lateral}{type_page=article}{id_rubrique}{id_secteur}{id_article} />
<INCLURE{fond=inclure/colophon}{type_page=article}{id_rubrique}{id_secteur}{id_article} />
<INCLURE{fond=inclure/page-fin} />
</body>
</html>
```

Chaque région se remplit soit par l’élément du builder (`masthead`, `colophon`), soit par un autre contenu placé au même endroit, sans mélange implicite : un squelette qui n’inclut pas `masthead` fournit son propre en-tête dans `#page`.

## Inclusions

| Inclusion | Rôle | Paramètres |
|---|---|---|
| `inclure/head` | Contenu du `<head>` : `meta` charset et viewport, feuilles d’Astra dans leur ordre, variables issues de la configuration (`head-vars`), `#INSERT_HEAD_CSS` et `#INSERT_HEAD` | `type_page` : `article` charge aussi `css/astra-article.css` ; `contexte` et `id` : type et identifiant de l’objet affiché (`sommaire`, `article`, `rubrique`, `mot`, `auteur`, `page`, `recherche`, `404`), pour l’en-tête transparent ; `accueil` : `oui` pour une page unique en accueil statique |
| `inclure/body-debut` | Script précoce qui choisit la classe de l’en-tête avant le premier affichage | Aucun |
| `inclure/page-debut` | Lien d’évitement vers `#content`, ouverture de `div#page.hfeed.site` | Aucun |
| `inclure/masthead` | `header#masthead` du header builder : rangées ordinateur et mobile, panneau du menu mobile en mode déroulant | `type_page`, `id_rubrique`, `id_secteur`, `id_article` : page courante, pour l’état actif des menus |
| `inclure/panneau-lateral` | Panneau du menu mobile en mode latéral (off-canvas) ; vide dans les autres modes | Mêmes paramètres que `masthead`. À inclure si `masthead` l’est, avant `page-fin` |
| `inclure/colophon` | `footer#colophon` du footer builder | Mêmes paramètres que `masthead` |
| `inclure/menu-libre` | Menu libre de Menus dans le balisage du menu de pied d’Astra : `div.footer-nav-wrap > ul.ast-nav-menu`, entrées `li.menu-item > a.menu-link`, classes CSS du menu reportées sur la liste. Liste seule : l’appelant fournit le `nav` et son nom accessible. Un niveau. Rien si le menu n’existe pas | `identifiant` : identifiant du menu dans Menus ; `disposition` : `horizontal` (défaut) ou `vertical` ; `type_page`, `id_rubrique`, `id_secteur`, `id_article` : état actif des entrées |
| `inclure/page-fin` | Fermeture de `#page`, bouton de retour en haut s’il est activé, scripts du thème (`frontend.min.js`, navigation) | Aucun |

`page-debut` et `page-fin` sont les deux moitiés de `#page` : elles s’emploient ensemble, et ce sont les seules inclusions de cette interface qui ne sont pas équilibrées. `masthead`, `panneau-lateral` et `colophon` sont des éléments équilibrés.

`inclure/header` et `inclure/footer` restent les inclusions des pages d’Astra. Ce sont des inclusions de compatibilité : `header` compose `page-debut` et `masthead`, `footer` compose `panneau-lateral`, `colophon` et `page-fin`. Leur sortie est celle de la version 1.8.0 (comparaison avant/après : `tests/comparaison/avant_apres.sh`).

## Classes du `body`

`astra_body_class(string $type, $id = 0, $accueil = '')` retourne les classes qu’Astra pose sur le `body` de WordPress : type de page (`single`, `archive category-…`, `error404`…), disposition (`ast-separate-container`, barre latérale), en-tête transparent, `ast-hfb-header`.

- `$type` : `sommaire`, `article`, `rubrique`, `mot`, `auteur`, `page` (page unique), `recherche` ou `404` ;
- `$id` : identifiant de l’objet affiché ;
- `$accueil` : `oui` pour une page unique en accueil statique.

Usage : `class="[(#VAL{article}|astra_body_class{#ENV{id_article}})]"`.

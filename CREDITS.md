# Crédits et provenance

Astra-spip est un portage pour SPIP réalisé par Gilles Vincent, distribué sous
[GPL-3.0-or-later](LICENSE). Les éléments provenant d'autres projets conservent leurs
crédits et leurs notices de licence. La licence du portage ne remplace pas les licences
propres aux composants ci-dessous.

Ce relevé porte uniquement sur Astra-spip. Il a été établi le **23 octobre 2026**, à
partir des fichiers présents dans le dépôt, de l'archive officielle **Astra 4.11.0**
et des sources indiquées ci-dessous. Les styles des articles et commentaires ainsi
que les images de test présents dans l'arbre de travail sont également recensés.

## Astra : code embarqué et adapté

Source de référence : [archive officielle Astra 4.11.0](https://downloads.wordpress.org/theme/astra.4.11.0.zip),
publiée par [Brainstorm Force / WPAstra](https://wpastra.com/).
Licence déclarée dans `style.css` et `readme.txt` : **GNU GPL v2 ou ultérieure**.
La notice originale est conservée dans [licenses/astra-4.11.0-readme.txt](licenses/astra-4.11.0-readme.txt),
et le texte de la GPL v2 dans [licenses/GPL-2.0-or-later.txt](licenses/GPL-2.0-or-later.txt).

| Source dans Astra 4.11.0 | Présence dans Astra-spip | Nature de la reprise |
| --- | --- | --- |
| `assets/css/minified/main.min.css` | `css/main.min.css` | Copie identique, vérifiée octet par octet contre l'archive officielle |
| `assets/js/minified/frontend.min.js` | `js/frontend.min.js` | Copie identique, vérifiée octet par octet ; version lisible dans `assets/js/unminified/frontend.js` de l'archive |
| CSS produit par le thème et ses réglages du Customizer | `css/astra-dynamic.css` | Extraction d'un rendu WordPress ; couleurs, polices et largeurs rendues configurables pour SPIP |
| CSS des drapeaux de compatibilité : `Astra_Dynamic_CSS::astra_submenu_below_header_fix()` (`inc/class-astra-dynamic-css.php`) | `css/compat/*.css` | Extraction d'un rendu WordPress, drapeau posé ou non (`tests/references/extraire_css.py --compat`) |
| CSS produit par le Header/Footer Builder | `css/astra-hfb.css`, styles générés par `inclure/head-vars.html` et `inc/astra_hfb.php` | Extraction et transposition des rangées, répartitions, espacements et réglages du builder ; extracteur : `tests/references/extraire_css.py` |
| CSS des dispositions de page | `css/astra-pages.css` | Extraction des règles de page, dont la pleine largeur, par `tests/references/extraire_css.py --pages` |
| `inc/dynamic-css/single-post.php` | `css/astra-article.css` | Sélection des règles touchant le contenu des articles ; chargement adapté à SPIP |
| `inc/dynamic-css/comments-flex.php` | `css/astra-commentaires.css` | Règle d'espacement des listes ; chargement conditionné aux commentaires dans SPIP |
| Gabarits `header.php`, `footer.php`, `page.php`, `single.php`, `archive.php`, `search.php`, `404.php`, `searchform.php`, `template-parts/` et composants du builder | Squelettes à la racine, `inclure/`, `inclure/hfb/`, `inclure/widgets/`, `formulaires/recherche.html`, `modeles/pagination_astra.html` | Reproduction et adaptation du balisage, des classes, des attributs et de la structure ; les requêtes et appels WordPress sont remplacés par les boucles, balises et formulaires SPIP |
| Options, palette et organisation du builder | `astra_fonctions.php`, `inc/astra_hfb.php`, formulaires de configuration | Transposition des valeurs par défaut et des conventions Astra ; interface et stockage adaptés à SPIP |
| Styles du Customizer : `inc/class-astra-dynamic-css.php` (bouton, identité, graisses des titres et `support_font_css_to_widget_and_in_editor()`), `inc/class-astra-extended-base-dynamic-css.php` (marge du bouton de l'en-tête, marges des rangées du builder), `inc/builder/type/header/primary-header/dynamic-css/dynamic.css.php` (marge de l'en-tête entier), `inc/builder/type/header/menu/dynamic-css/dynamic.css.php`, `inc/builder/type/base/dynamic-css/{button,widget,social}/class-astra-*-component-dynamic-css.php`, `inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php` | `inc/astra_styles.php`, `inc/styles/*.php` | Transposition des règles ordinateur : sélecteurs (ceux du bouton global copiés à l'identique), ordre, conditions d'émission et valeurs par défaut ; écriture comme `astra_parse_css()` et `trim_css()` |
| En-tête transparent : `inc/addons/transparent-header/classes/class-astra-ext-transparent-header-markup.php` (`is_transparent_header()`), `classes/dynamic-css/dynamic.css.php`, partie « Transparent Header » d'`inc/class-astra-dynamic-css.php` | `inc/astra_entete_transparent.php` | Transposition de la règle d'activation aux pages SPIP et des règles CSS : sélecteurs copiés à l'identique, media queries, ordre et conditions d'émission |
| CSS dynamique rendu par WordPress avec les valeurs du modèle Garden Maintenance | `tests/references/styles/attendus/*.css`, `tests/references/transparent/attendus/*.css` | Extraits de la sortie d'Astra 4.11.0 (GPL v2 ou ultérieure), capturés par `tests/references/styles/capturer.sh` et `tests/references/transparent/capturer.sh` pour les tests d'équivalence |
| Règles Astra appliquées aux contenus | `css/astra-spip.css` | Adaptations aux éléments produits par SPIP ; les reprises de règles WordPress/Astra sont signalées dans les commentaires |

Notices de paternité amont conservées :

> Astra WordPress Theme, Copyright 2020 WPAstra.

> Astra is based on Underscores https://underscores.me/, (C) 2012-2020 Automattic, Inc.

**Underscores**, par Automattic et ses contributeurs, est le socle déclaré par Astra,
sous GPL v2 ou ultérieure : [projet](https://underscores.me/),
[sources et licence](https://github.com/Automattic/_s). Ce crédit est conservé comme
ascendance du thème ; Astra-spip ne revendique pas une reprise autonome de tout Underscores.

### Styles de normalisation

Astra crédite **Nicolas Gallagher et Jonathan Neal** pour les styles issus de
[normalize.css](https://github.com/necolas/normalize.css). Leur présence est héritée
par `css/main.min.css`, conservé sans modification. La version exacte de normalize.css
n'est pas précisée par Astra 4.11.0 ; elle n'est pas déduite de la version du thème.
La notice **MIT**, copyright Nicolas Gallagher et Jonathan Neal, est reproduite dans
[licenses/normalize-MIT.txt](licenses/normalize-MIT.txt).

### Fil d'Ariane

Astra crédite **Justin Tadlock** pour
[Breadcrumb Trail](https://github.com/justintadlock/breadcrumb-trail), sous GPL v2 ou
ultérieure. Son adaptation amont est dans
`inc/addons/breadcrumbs/class-astra-breadcrumb-trail.php`.
`inclure/ariane.html` reprend la structure et les classes du fil d'Ariane d'Astra
(`breadcrumb-trail`, `trail-items`, etc.), avec une boucle `HIERARCHIE` SPIP.
Le moteur PHP Breadcrumb Trail n'est pas embarqué.

## WordPress : styles des blocs natifs

Les contributeurs de **WordPress** sont crédités pour les styles de blocs et les
styles globaux issus de **WordPress 7.1.3**, sous **GPL v2 ou ultérieure**.

- Sources : [distribution WordPress 7.1.3](https://wordpress.org/wordpress-7.1.3.zip),
  [arbre de sources de cette version](https://github.com/WordPress/WordPress/tree/7.1.3),
  notamment `wp-includes/css/dist/block-library/common.css`,
  `wp-includes/blocks/*/style.css` et le CSS produit par `global-styles-inline-css`.
- Reprise : `css/astra-blocs.css`, généré par
  `tests/references/extraire_css_blocs.py` ; sélection des règles dont les sélecteurs
  peuvent encore correspondre au HTML importé par wp2spip et élimination des doublons
  déjà présents dans le CSS dynamique d'Astra.
- Adaptations complémentaires : règles de `css/astra-spip.css` appliquant ces styles
  aux liens, groupes et autres éléments transformés par SPIP/wp2spip.
- Notice originale : [licenses/wordpress-7.1.3-NOTICE.txt](licenses/wordpress-7.1.3-NOTICE.txt).

Ces feuilles distribuées avec WordPress proviennent notamment du développement de
l'éditeur de blocs [Gutenberg](https://github.com/WordPress/gutenberg), par ses
contributeurs. La référence effectivement utilisée ici est la distribution WordPress
7.1.3, sans attribuer un numéro de version Gutenberg non établi. Aucun éditeur
Gutenberg JavaScript n'est embarqué dans Astra-spip.

## Icônes et SVG

### Font Awesome Free : correspondances confirmées

Huit tracés de `inc/astra_icones.php`, copiés depuis `assets/svg/svgs.json` d'Astra
4.11.0, correspondent exactement aux données SVG de
[Font Awesome Free 5.15.4](https://github.com/FortAwesome/Font-Awesome/blob/5.15.4/metadata/icons.json),
par **Fonticons, Inc.** :

| Clé Astra-spip | Icône Font Awesome |
| --- | --- |
| `facebook` | `facebook-square` (brands) |
| `twitter` | `twitter` (brands) |
| `instagram` | `instagram-square` (brands) |
| `linkedin` | `linkedin` (brands) |
| `youtube` | `youtube` (brands) |
| `mastodon` | `mastodon` (brands) |
| `github` | `github` (brands) |
| `rss` | `rss-square` (solid) |

**Icônes : Font Awesome Free par Fonticons, Inc., sous
[CC BY 4.0](https://creativecommons.org/licenses/by/4.0/).**
La licence est reproduite dans [licenses/CC-BY-4.0.txt](licenses/CC-BY-4.0.txt).
Voir aussi la [notice Font Awesome 5.15.4](https://github.com/FortAwesome/Font-Awesome/blob/5.15.4/LICENSE.txt).
Une [copie de cette notice](licenses/fontawesome-5.15.4-LICENSE.txt) est jointe au dépôt.
La comparaison établit l'origine des tracés ; elle ne prouve pas qu'Astra utilisait
précisément le paquet 5.15.4.

Les tracés sont conservés ; leur sélection, leur intégration dans un tableau PHP et
leur utilisation dans les inclusions SPIP constituent les adaptations du portage.
La licence OFL des fontes et la licence MIT du code Font Awesome ne sont pas celles
des SVG : aucune fonte ni bibliothèque JavaScript Font Awesome n'est embarquée ici.

### Font Awesome 4.7.0 : Google Reviews

Le SVG `google_reviews` de `inc/astra_icones.php` est identique à l'entrée du même nom
dans `assets/svg/svgs.json` d'Astra 4.11.0. Son tracé est le glyphe `google` (U+F1A0)
de la fonte **Font Awesome 4.7.0**, par **Dave Gandy**, converti par Astra : coordonnées
de la fonte (1792 unités par cadratin) divisées par 64 et retournées verticalement.
Chaque point du tracé d'Astra tombe sur le contour du glyphe à 0,0015 unité près
(relevé du 23 octobre 2026, sur `fontawesome-webfont.svg` de la version 4.7.0).

**Icône : Font Awesome 4.7.0 par Dave Gandy, fonte sous
[SIL OFL 1.1](https://openfontlicense.org/).** La notice et le texte de la licence
sont reproduits dans [licenses/OFL-1.1-fontawesome-4.7.0.txt](licenses/OFL-1.1-fontawesome-4.7.0.txt).
Seul ce tracé est repris, sans la fonte. Le logo représente la marque **Google** de Google LLC ;
les règles relatives aux marques ne constituent pas une licence libre du dessin.

### Autres icônes reprises d'Astra

Les SVG `twitter-x`, `bluesky` et `email` de `inc/astra_icones.php` sont eux aussi
identiques aux entrées correspondantes du `assets/svg/svgs.json` d'Astra 4.11.0.
Leur attribution générale à Font Awesome dans les anciennes notices du portage
était trop large : aucune correspondance exacte n'a été trouvée dans les métadonnées
Font Awesome Free 5.15.4 ou 6.7.2 consultées.

- `twitter-x` représente la marque **X**, dont les ressources officielles sont
  publiées dans le [kit de marque X](https://about.x.com/en/who-we-are/brand-toolkit).
- `bluesky` représente le papillon de **Bluesky Social, PBC** :
  [ressources de marque](https://bsky.social/about/support/icons).
- L'auteur initial du tracé `email` n'est pas identifié dans les notices d'Astra.

La source immédiate et la licence générale déclarée par Astra sont établies ; une
licence tierce propre à ces trois tracés n'est pas établie par cette recherche.
Ils ne sont donc pas présentés comme des SVG Font Awesome sous CC BY 4.0.
Les règles relatives aux marques ne constituent pas une licence libre du dessin.

Les SVG d'interface dans `inclure/hfb/{mobile-trigger,search,account,menu-items}.html`
et `inclure/footer.html` reprennent les icônes ou le rendu
d'Astra (menu, fermeture, recherche, compte, flèches). Leur source immédiate
est le thème 4.11.0, dont `assets/svg/svgs.json` et ses gabarits. Une attribution
individuelle à un jeu tiers n'est pas établie pour tous ces tracés.

Le tracé du SVG de réponse dans `inclure/commentaire.html` n'a pas été retrouvé
dans l'archive Astra 4.11.0 ; aucune source tierce n'est indiquée dans le fichier.
Il n'est donc pas attribué à Astra, Font Awesome ou IcoMoon par ce relevé.

Astra déclare que sa fonte d'icônes est fondée sur **IcoMoon-Free**, par **Keyamoon**,
sous CC BY 4.0 ou GPL. Ce crédit amont est conservé :
[sources IcoMoon-Free](https://github.com/Keyamoon/IcoMoon-Free),
[notice originale](licenses/icomoon-License.txt).
Aucune fonte IcoMoon n'est embarquée dans Astra-spip ; ce crédit ne suffit pas à
attribuer tous les SVG d'Astra à IcoMoon.

## Ressources et dépendances distinctes

- Les images `tests/references/blocs/images/{couverture,media}.png` sont des motifs
  produits par `tests/references/blocs/generer_images.php`, qui les déclare CC0.
  Les textes de `tests/references/blocs/page.php` sont écrits pour les tests.
- Les références HTML, CSS et captures générées depuis WordPress/Astra servent à
  comparer le portage ; elles conservent la provenance des éléments qu'elles rendent.
  Leur génération est décrite dans [tests/references/README.md](tests/references/README.md).
- `images/astra-xx.svg` est le pictogramme du plugin, distinct de la capture
  promotionnelle `screenshot.jpg` du thème WordPress. Aucune source tierce n'est
  indiquée dans ce fichier ; il n'est pas attribué ici à un jeu d'icônes amont.
- Les polices du texte, des titres, du bouton et des menus sont des piles CSS ; deux
  polices sont fournies en fichiers locaux (section « Polices locales »). Aucun chargeur
  Google Fonts n'est fourni ni appelé.
- **SPIP**, **Pages** et **a2a** sont des dépendances ou intégrations, pas du code
  embarqué dans le thème. PHPUnit et les outils de comparaison sont des dépendances
  de développement ; leurs distributions conservent leurs propres licences.

Le `readme.txt` d'Astra crédite aussi **Flexibility** (Jonathan Neal / 10up, MIT),
**Webfonts Loader** (WordPress Themes Team, MIT), **Heroicons** (MIT),
**Humaaans** et des illustrations Brainstorm Force (CC0), ainsi que **PxHere**.
Ces crédits sont conservés dans la notice amont jointe. Les scripts Flexibility et
Webfonts Loader, la capture promotionnelle et ces illustrations ne sont pas
embarqués dans Astra-spip : leur présence dans le thème WordPress ne signifie pas
qu'ils sont tous redistribués par le portage.

## Polices locales

Fichiers woff2 du sous-ensemble « latin » publiés par [Fontsource](https://fontsource.org/)
(paquets npm `@fontsource/raleway` et `@fontsource/poppins`, version **5.1.0**, déclarés
`OFL-1.1`), téléchargés le 23 octobre 2026 depuis `https://cdn.jsdelivr.net/npm/@fontsource/<police>@5.1.0/files/`
et redistribués sans modification. Le texte de licence de chaque paquet, avec la mention de
copyright de la police, est conservé dans `licenses/`.

| Fichier | Police, graisse, style | SHA-256 |
| --- | --- | --- |
| `fonts/raleway/raleway-latin-400-normal.woff2` | Raleway 400 | `3e44fb721d3be9376c6e5e946109067a04da84ae10b3f27a03ada7a3731e515c` |
| `fonts/raleway/raleway-latin-400-italic.woff2` | Raleway 400 italique | `29de03aaf7a0844578daef59eabdbd1cfe9257873765938cc51a3c9a3af843b4` |
| `fonts/raleway/raleway-latin-700-normal.woff2` | Raleway 700 | `109736135dc84f02f379825bd2b48998e17068eaf1f085df5f52e80537a4257d` |
| `fonts/poppins/poppins-latin-500-normal.woff2` | Poppins 500 | `cd36de204aca2d5fa263a731f7c20009b5e3d754ba1f1e03c33e93a48f3e7446` |
| `fonts/poppins/poppins-latin-600-normal.woff2` | Poppins 600 | `f4e80d9dfd374d02989b87a27b5ed4cb78fbb177c27f1478e9a8b0afb7513149` |

- **Raleway** : Copyright 2010 The Raleway Project Authors (impallari@gmail.com), avec le
  nom réservé (*Reserved Font Name*) « Raleway » ; SIL OFL 1.1,
  [licenses/OFL-1.1-raleway.txt](licenses/OFL-1.1-raleway.txt). L'OFL interdit d'employer un
  nom réservé pour une version modifiée. Les fichiers sont les sous-ensembles produits par
  Fontsource et diffusés par lui sous ce nom ; Astra-spip les reprend tels quels. Savoir si un
  sous-ensemble est une version modifiée au sens de l'OFL est une question signalée ici, non
  tranchée.
- **Poppins** : Copyright 2020 The Poppins Project Authors (https://github.com/itfoundry/Poppins),
  sans nom réservé ; SIL OFL 1.1, [licenses/OFL-1.1-poppins.txt](licenses/OFL-1.1-poppins.txt).

## Vérification et entretien

L'archive officielle téléchargée pour ce relevé a pour empreinte SHA-256 :

```text
ae876733703ab5d46251de0f2a6f8d28b164609e06750382813b7756a9c7fdac  astra.4.11.0.zip
44622e9d59e3d2c9c4ffb9fc53d368e250f382ab07a3df978410eaa86556cff9  css/main.min.css
e1d8d99d3074de7fc8747888add39735ddbe463a6f8f31b66e16193886f2afb5  js/frontend.min.js
```

Ces empreintes permettent d'identifier les fichiers comparés ; elles ne constituent
pas une signature de l'éditeur. Pour une mise à jour, revoir les sources, versions,
auteurs, licences et adaptations dans ce fichier, conserver les notices amont et
documenter les nouvelles reprises au plus près du code concerné. Les attributions
non établies ci-dessus restent à compléter ; ce relevé ne les transforme pas en
attributions certaines.

## Migration vers Astra 4.13.8 (25 octobre 2026)

La nouvelle référence est l'[archive officielle Astra 4.13.8](https://downloads.wordpress.org/theme/astra.4.13.8.zip),
Brainstorm Force / WPAstra, sous GPL v2 ou ultérieure. Sa notice originale est
conservée dans [licenses/astra-4.13.8-readme.txt](licenses/astra-4.13.8-readme.txt).
SHA-256 du ZIP : `3cce1a750dcb699429117a88987aa8f10be404374492eef94379d2c4df6a6572`.

Les copies directes suivantes remplacent les assets 4.11.0, sans modification :

| Source Astra 4.13.8 | Destination | SHA-256 |
| --- | --- | --- |
| assets/css/minified/main.min.css | css/main.min.css | 84aad614f8b95ebd3a4ac6f7633e77323066d31da87afe3dafc8c14cd3d0f8fc |
| assets/js/minified/frontend.min.js | js/frontend.min.js | 3b3a9a24246253beec04f3d2619082970c3aa861ca4ee34280662e3d4a509d20 |

Le relevé 4.11.0 ci-dessus et sa notice restent conservés pour la provenance
historique. Les couches transposées sont migrées et qualifiées séparément selon
les plans du 24 octobre ; la copie des deux assets ne vaut pas preuve de parité
pour le portage complet.

# Blocs Wordpress importés par wp2spip

Une page de démonstration des blocs natifs (un exemple de chaque bloc conservé par wp2spip, textes
écrits pour les tests) est créée dans un WordPress 7.1.3 sur MySQL avec Astra 4.11.0, importée par
wp2spip dans un SPIP 4.4 avec Astra SPIP, puis comparée bloc par bloc.

## Fichiers

- `generer_images.php` : génère `images/couverture.png` et `images/media.png` (aplats et bandes,
  aucune photographie, domaine public CC0). Empreintes SHA-256 :
  - `couverture.png` : `41319c81b53ef3725751e44b70ccadf0e685ec21a3946e26155c2a078523b94d`
  - `media.png` : `0d1dd350a5ee0b5c50d051a97fc6d5d96c7b5331c37c3c359a535119a2275ae6`
- `page.php` : page « Démonstration des blocs » (`wp eval-file`), aucun bloc signalé invalide par
  l'éditeur de blocs.
- `importe.html` : `.entry-content` de la page importée (images remplacées par une image vide), pour
  `tests/e2e/cascade_blocs.cjs`.
- `pertes.json` : pertes d'import caractérisées (voir plus bas).

## Installer et comparer

Prérequis : une base MySQL existante et un compte qui y a tous les droits (le script ne crée pas la
base, ses tables sont préfixées `wpblocs_`) ; wp2spip en local (`WP2SPIP`, défaut
`/src/wordpress/wp2spip`), dans une version qui contient ses PR #9 et #11 (document sans légende,
conteneurs gardés dans les blocs couverture et média et texte) ; un spip-cli qui contient la MR 91
(`plugins:svp:telecharger`), par exemple celui que clone `/src/site-tuto/bin/install.sh`.

    WP_BLOCS=<dossier> ASTRA_WP=<thème astra 4.11.0> BLOCS_DB_NAME=… BLOCS_DB_USER=… BLOCS_DB_PASS=… \
        scripts/install-wp-blocs.sh
    php -S 127.0.0.1:8405 -t $WP_BLOCS scripts/wp-router.php

    SPIP_BLOCS=<dossier vide> WP_BLOCS=… SPIP_CLI="php …/spip" SPIP_CLI_SVP=<spip-cli avec la MR 91> \
        DEPOT=https://files.spip.net/spip-zone/archives.xml scripts/install-spip-blocs.sh
    php -d date.timezone=Europe/Paris -S 127.0.0.1:8406 -t $SPIP_BLOCS

Le SPIP importé doit tourner dans le fuseau du WordPress (`Europe/Paris`) : wp2spip importe les dates
locales de WordPress (`post_date`), qu'un PHP réglé sur UTC jugerait postdatées, donc invisibles.

    NODE_PATH=$PW/node_modules node tests/comparaison/comparer_blocs.cjs \
        'http://127.0.0.1:8405/?pagename=demonstration-des-blocs' 'http://127.0.0.1:8406/spip.php?article<N>' \
        tests/comparaison/sorties/blocs
    SPIP_CLI=… php tests/comparaison/comparer_medias.php $SPIP_BLOCS $WP_BLOCS
    curl -s 'http://127.0.0.1:8405/?pagename=demonstration-des-blocs' -o tests/references/sorties/blocs/page.html
    WP=<racine WordPress> PAGE=tests/references/sorties/blocs/page.html NODE_PATH=$PW/node_modules \
        node tests/e2e/cascade_blocs.cjs

`comparer_blocs.cjs` apparie dans l'ordre les enfants directs de `.entry-content` (les blocs que wp2spip
convertit en typographie SPIP n'ont plus de classe `wp-block-*` et sont nommés par leur balise),
compare chaque paire au pixel à 1280 et 390 px (seuil 1 %, meilleur alignement vertical à ±2 px) et
vérifie la propriété de chaque perte. `comparer_medias.php` vérifie que les documents des blocs
couverture et média et texte sont les mêmes fichiers que dans WordPress (SHA-256).

`css/astra-blocs.css` se régénère depuis cette page, qui porte les styles globaux des blocs présents :
WordPress 7.1 n'émet ceux d'un bloc (`:root :where(.wp-block-pullquote)`…) que sur les pages qui le
contiennent.

    WP=<racine WordPress> python3 tests/references/extraire_css_blocs.py tests/references/sorties/blocs/page.html \
        > css/astra-blocs.css

## Correspondances

Les éléments que SPIP produit à la place des classes de WordPress (lien SPIP du bouton, document
SPIP de la couverture et du bloc média et texte, classes de mise en page `is-layout-flow` et
`is-layout-flex` retirées par wp2spip) reçoivent dans `css/astra-spip.css` les déclarations des règles
WordPress ou Astra citées en commentaire. Le réglage « empiler sur mobile » du bloc média et texte
étant perdu, sa valeur par défaut (empilé) est appliquée.

## Pertes caractérisées

| Bloc | Élément perdu (HTML WordPress → importé) | Propriété vérifiée | Effet |
|---|---|---|---|
| Séparateur | converti en filet SPIP (`----`) : `wp-block-separator`, `has-alpha-channel-opacity` | `hr` : `height` = 1px | filet sur toute la largeur du contenu au lieu du séparateur court (100 px, bordure de 2 px) |
| Couverture | `span.wp-block-cover__background.has-background-dim` (réglage `dimRatio`) | `.wp-block-cover__background` absent | image non assombrie (WordPress : noir à 50 %) ; seule cette couche est masquée dans la comparaison |
| Code | converti en `<cadre>` : `pre.wp-block-code` → `textarea.spip_cadre` | `resize` = both | code dans une zone de texte redimensionnable, hauteur fixée par SPIP |
| Vers | converti en `<poesie>` : `pre.wp-block-verse` → `blockquote.spip_poesie` | `white-space` = normal | une ligne de bloc par vers au lieu d'un texte préformaté |

# Blocs Wordpress importés par wp2spip

Une page de démonstration des blocs natifs (un exemple de chaque bloc conservé par wp2spip, textes
écrits pour les tests) est créée dans un WordPress 7.1.3 sur MySQL avec Astra 4.13.8, importée par
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

    WP_BLOCS=<dossier> ASTRA_WP=<thème astra 4.13.8> BLOCS_DB_NAME=… BLOCS_DB_USER=… BLOCS_DB_PASS=… \
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

## Qualification 4.13.8

La migration utilise wp2spip `e3c262720b518bc7007e5f37779a9d6ca84f17f1`, WordPress
7.1.3 vérifié par checksums, Pages 2.4.1 et Polyhier 4.2.0. Les mesures reposent sur
une base MySQL et deux sites privés distincts des 35 références du builder.
`BLOCS_DB_HOST`, `URL_WP_BLOCS` et `URL_SPIP_BLOCS` permettent de choisir ces
services ; leurs valeurs par défaut restent celles des commandes ci-dessus.
`ASTRA_REFERENCE_ARCHIVE` désigne le ZIP officiel épinglé par cible.json.

Si le téléchargement de wp-cli tronque des noms de fichiers du core, extraire
l'archive officielle WordPress épinglée avant le helper et lancer
`wp core verify-checksums --version=7.1.3 --locale=en_US`. Le problème a été
caractérisé pendant la préparation de la référence ; une installation incomplète
ne constitue pas un environnement de preuve.

Sur le CLI utilisé pour ces mesures, `UPPER(prefixe)=LOWER("PAGES")` ne trouve aucun
plugin sous SQLite, bien que le catalogue contienne Pages. Le [correctif ciblé](../../../docs/inventaires/astra-4.13.8-qualification/outillage/spip-cli-prefixe-sqlite.md)
a été appliqué à une copie privée du CLI ; le CLI partagé n'a pas été modifié.
Les quatre pertes du tableau restent inchangées. Après adaptation des liens de
bouton nettoyés, les 12 blocs comparés en image donnent 0,00 % aux deux largeurs.
Les deux documents restent identiques à leurs fichiers WordPress (SHA-256).
Le CSS natif régénéré et le test de cascade restent inchangés et verts.

Le profil `blocs` de css_reference.cjs mesure en plus 768 px, le rayon et l'ombre
du bouton et deux/trois colonnes. `ASTRA_CSS_RAYON_BOUTON=8px`, avec la fixture
`tests/fixtures/css/bouton.php`, vérifie qu'un rayon configuré prime sur le défaut,
sans changer les marges tablette/mobile lorsque leurs défauts s'appliquent.
Les règles originales des réglages sont conservées ; des sélecteurs SPIP de même
spécificité reprennent leurs valeurs pour le lien privé de sa classe d'origine.
Les adaptations sont contrôlées contre les déclarations natives indépendantes.

Les sondes CSS normal/wide/full sont éphémères et distinctes de l'import réel :
wp2spip retire `alignwide`, `alignfull`, les classes de disposition et les styles
inline. Les sondes n'attestent pas leur conservation par l'importeur. Elles
vérifient les règles d'Astra applicables à ce HTML natif, ainsi que la priorité
d'une graisse explicite et la taille de code dans un titre.

`tests/fixtures/css/rares.php`, chargé uniquement en CLI dans chacun des deux
moteurs, prépare logo sans menu, pied RTL et espacements page/archive. Le PNG CC0
existant sert de logo ; la référence WordPress est réglée explicitement à 96 px,
comme le rendu plafonné à 60 px du port pour ce PNG de ratio 800:500. Cela isole la
visibilité sans menu, sans prétendre porter les options de taille responsive.
Le mode `nettoyer` retire le logo de preuve SPIP et le mu-plugin RTL WordPress.
Les rendus privés sont comparés via `ASTRA_CSS_RARES`, avec le profil blocs.

Lors de la copie d'un SPIP de test, régler `adresse_site` sur son URL réelle avant
de préparer les rendus rares : le pilote utilise le lien d'accueil du HTML pour
résoudre ses assets. Un ancien `http://localhost` désignerait un autre serveur,
et le contrôle « image servie » doit alors échouer. Les fixtures historiques
d'identité conservent leur comparaison HTML stricte en adaptant seulement leur
URL d'accueil au domaine configuré du site de test.

### R28 — import des blocs embed

La [procédure oEmbed](../../fixtures/oembed/README.md) utilise le même importeur épinglé et deux copies du véritable SPIP importé, avec/sans oEmbed 3.5.1. Les figures `core/embed` WordPress deviennent URL isolée et légende : leur enveloppe n’est pas conservée. Le test de retraitement porte sur le résultat réel du plugin, pas sur une enveloppe Astra inventée. Les lecteurs 16:9 et 4:3 sont comparés en géométrie à WordPress aux trois largeurs ; les captures ne servent pas à comparer les pixels d’un service tiers. La découverte du fournisseur inconnu est caractérisée séparément du validateur d’URL : le plugin examiné ne passe pas celui-ci au client HTTP.

# Qualification de la migration Astra 4.13.8

Astra SPIP 1.6.0 prend Astra 4.13.8 comme référence de rendu. Le changement de référence ne porte pas toutes les fonctions du thème WordPress. Les chemins existants du port sont comparés et les fonctions absentes restent explicites dans le [périmètre](perimetre.md).

## Sources et traçabilité

`tests/references/cible.json` épingle WordPress 7.1.3, le ZIP officiel Astra 4.13.8 et les empreintes des assets. `cible-4.11.0.json` sert uniquement à l’archive isolée. Les gardes de capture courante refusent le thème historique. Les fichiers officiels `css/main.min.css` et `js/frontend.min.js` sont conservés sans modification ; les adaptations SPIP sont séparées et créditées dans `CREDITS.md`.

L’inventaire de départ conserve ses 184 entrées et ses classes historiques. Ses 36 entrées I/L/V ont chacune une décision dans [decisions.json](decisions.json). Le validateur rejette doublons, omissions, preuves absentes ou hors dépôt et limites sans décision explicite. Les intégrations Spectra v3, GenerateBlocks et les options de bannières RTL absentes ne sont pas présentées comme implémentées.

## Procédure de mesure

La comparaison utilise des sites privés distincts : WordPress historique, WordPress cible, SPIP candidat et paire de sites réellement importés par wp2spip. Les captures sont séquentielles ; les tests modifiant leur configuration ne sont pas exécutés simultanément. Les profils oEmbed disposent encore de deux SPIP séparés et d’un fournisseur HTTP local, sans fournisseur tiers.

Le [manifeste](manifest.json) indique le commit effectivement testé, les outils et les empreintes des rapports versionnés. Les captures PNG volumineuses restent des artefacts de travail ; les mesures JSON, leurs assertions et les scripts sont versionnés. Un rapport antérieur à la dernière modification de code ne prouve pas le commit candidat. La vérification après fusion reste à exécuter sur le commit de fusion avant publication du tag.

Commandes principales, avec les variables de racines et d’URL décrites dans `tests/references/README.md` :

```bash
php tests/references/verifier.php "$WP_REFERENCE" "$ASTRA_REFERENCE_ARCHIVE" tests/references/cible.json
composer tests-unit
composer tests-integration
python3 -m unittest discover -s tests/references
python3 tests/references/verifier_qualification.py
WP="$WP_REFERENCE" URL="$URL_WP" tests/references/styles/capturer.sh
WP="$WP_REFERENCE" URL="$URL_WP" tests/references/transparent/capturer.sh
WP="$WP_REFERENCE" URL="$URL_WP" SORTIES="$RUN_DIR/wp-final" tests/references/capturer.sh "${CONFS[@]}"
WP="$WP_REFERENCE" URL_WP="$URL_WP" SORTIES="$RUN_DIR/compare-final" tests/comparaison/comparer_captures.sh "$SPIP_ROOT" "$URL_SPIP" "${CONFS[@]}"
REFERENCES="$RUN_DIR/wp-final" php tests/comparaison/comparer_html.php "$SPIP_ROOT" "$URL_SPIP"
REFERENCES="$RUN_DIR/wp-final" php tests/comparaison/comparer_html.php "$SPIP_ROOT" "$URL_SPIP" "${CONFS_R12_R24[@]}"
php7.4 tests/references/css_multiversion.php > /tmp/astra-css-74.txt
php8.4 tests/references/css_multiversion.php > /tmp/astra-css-84.txt
cmp /tmp/astra-css-74.txt /tmp/astra-css-84.txt
```

`CONFS` contient les 41 clés de `astra_configurations_reference()` : les 35 configurations historiques, les quatre variantes R25, R26 et R27. R28 est un import réel oEmbed, vérifié par son profil dédié. La suite Python de non-régression historique emploie `ASTRA_LEGACY_R0` et `ASTRA_LEGACY_CSS` pointant sur une capture 4.11.0 et la feuille précédant la migration. Les profils détaillés de navigation, CSS, formulaires, répartitions, médias, blocs et oEmbed sont décrits dans leurs plans et les README de fixtures.

## Résultats sur le commit candidat

Le commit `d243642602c0784d45657321f96223bc1623c4c1` donne 311 vérifications réussies sur les 41 configurations, avec 0,23 % d’écart visuel maximal (seuil inchangé de 1 %). Les deux comparaisons HTML ont seulement les huit lignes de nommage historiques R13 ; leurs sorties brutes restent à 1 et sont accompagnées du bilan explicite.

Les suites donnent 140 tests unitaires / 1780 assertions et 99 tests d’intégration / 623 assertions. Trois cas d’intégration oEmbed sont ignorés dans le site ordinaire : leurs branches avec et sans plugin sont exécutées séparément sur les deux sites dédiés, puis après désactivation et réactivation. Les 28 tests Python passent, dont la reproduction de l’ancienne feuille depuis R0 sous Astra 4.11.0. Les 21 fichiers PHP concernés passent le lint PHP 7.4 et les générateurs produisent les mêmes 201344 octets sous PHP 7.4 et 8.4.

Les rapports de profils dans le manifeste complètent ces chiffres : clavier, première peinture, absence de JavaScript, formulaires, répartitions, styles calculés, médias, blocs importés et oEmbed. Les résultats initiaux rouges et les limites ne sont pas effacés par le bilan final.

## Différences caractérisées

- **HTML R13** : les seuls écarts admis sont les identifiants de widgets `nav_menu-2/3` contre `nav_menu-pied-2/3` et les noms de conteneurs de menus contre `menu-racine-container`. Les lignes sont comparées à l’archive 4.11.0 ; le code brut 1 du comparateur est conservé. Aucun échec nouveau n’est rendu vert par une normalisation générale.
- **R11** : le contenu des articles de démonstration reste hors de la seconde liste de comparaison HTML, comme avant la migration.
- **Pixels** : la normalisation du pied aligne son origine externe sur un pixel physique et masque uniquement le bouton fixe de retour en haut pendant cette capture. Ce bouton possède son propre profil. Les géométries internes restent contrôlées et le seuil historique n’augmente pas.
- **R25 vide** : la fixture WordPress repart de R0, comme SPIP. Aux deux largeurs mobiles, l’absence de l’en-tête et du déclencheur est une assertion ; aucune image d’un élément inexistant n’est fabriquée. Une mutation avec l’en-tête présent fait échouer ce contrôle.
- **Sans JavaScript** : à 768 et 390 px, Astra WordPress ne présente aucun lien de menu. Selon la décision de Gilles, SPIP expose une liste simple dans `noscript`. Avec JavaScript, ce contenu ne modifie ni la structure active ni le rendu cible.
- **Widgets menu** : listes statiques, flèches décoratives et absence de faux rôle application. Les sous-menus de l’en-tête restent interactifs. Les corrections de clavier, focus et réouverture sont séparées du bundle officiel.
- **Blocs importés** : séparateur, voile de couverture, code et vers conservent les quatre pertes d’import déjà documentées. Les essais wide/full natifs caractérisent le CSS ; ils ne prétendent pas que ces classes sont conservées par wp2spip. Les images de test sont CC0.
- **Identité** : les tailles responsives du logo ne sont pas ajoutées. La sonde sans menu fixe la largeur WordPress à 96 px pour isoler la présence du logo du plafond existant du port.
- **Borne fractionnaire** : 921,95 px réels n’ont pas été reproduits dans Chromium ; 921/922 px, les largeurs usuelles, le cache et la première peinture sont contrôlés sans annoncer cette mesure fractionnaire.
- **oEmbed** : plugin facultatif 3.5.1, contenu conservé à l’activation/désactivation, ratios 16:9 et 4:3, repli sur lien utile. Il déclare SPIP 4.x ; il n’est pas forcé sous SPIP 5 et aucun essai SPIP 5 n’est revendiqué. La découverte ne passe pas par le validateur d’URL optionnel du client SPIP : l’hypothèse de refus avant requête du plan est contredite par la mesure réelle, voir le [bilan sécurité](securite.md).

## Publication

Cette branche prépare la version 1.6.0 ; le schéma de données reste inchangé. Aucun tag n’est posé avant fusion. Le manifeste associe les preuves au commit testé ; un commit ultérieur contenant uniquement ces preuves ne change pas le code mesuré. Une nouvelle mesure sur main fusionné est nécessaire pour attribuer les preuves à la version publiée.

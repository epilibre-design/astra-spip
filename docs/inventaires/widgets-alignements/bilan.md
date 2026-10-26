# Qualification widgets-alignements

Les deux lots sont qualifiés ensemble sur le code de production `5208e6d` (avant les commits de documentation), base main `0a70f60`. Aucun changement des assets officiels, aucun seuil relevé ; paquet 1.6.0 et schéma inchangés.

185 tests unitaires / 2039 assertions ; 107 tests d’intégration / 653 assertions, dont 3 ignorés oEmbed exercés séparément : 29 assertions sans plugin et 33 avec le vrai plugin 3.5.1. Les 12 blocs comparables importés par wp2spip restent à 0,00 % aux deux largeurs ; les quatre pertes déjà documentées sont conservées.

66 configurations visuelles : 41 historiques, 15 R29 comparables et 10 R30 comparables ; 513 vérifications, écart maximal 0.23 %. Les cas sans zone comparable ne sont pas comptés comme captures réussies : leurs absences sont vérifiées par les pilotes dédiés. Les références complètes sont également capturées avant comparaison HTML.

La première sortie visuelle contient un échec d’environnement sur R29-logo-petit : un clone WordPress avait gardé DB_PATH absolu. Le clone a été isolé puis ce cas repris, code 0. Le journal initial reste disponible ; le bilan final substitue seulement cette reprise et ne dissimule aucun échec de produit.

Les comparaisons HTML brutes sortent 1 : noms de menus historiques R13, contenu de démonstration R11, et les mêmes deux noms de menu dans le nouveau R30-blocs. Tous les autres contextes sont identiques. Aucun ignore ajouté. Les styles calculés restent strictement comparés par le comparateur général.

Les neuf mutations prévues sont détectées ; témoin restauré vert. PHP 7.4 et 8.4 produisent le même CSS. Les variantes dédiées sont mesurées à 1280/768/390 et aux bornes 922/921/545/544. Le manifeste contient les SHA-256 des rapports ; les captures PNG brutes restent privées, sans recopier les images des modèles.

Commandes : `tests/references/capturer.sh` avec les listes explicites du manifeste et des matrices ; `comparer_html.php` par défaut puis listes explicites ; `comparer_captures.sh` sur les mêmes listes ; `identite_responsive.cjs`, `widgets_alignements.cjs`, `comparer_blocs.cjs` ; suites PHPUnit et Python. Variables et préparations : README des références.

13 variantes widgets : quatre zones, valeurs partielles, absence, en-tête seul, déplacement, zone 6, paragraphe explicitement aligné, menu et RTL réel. Les valeurs left/right restent physiques. La neutralisation `inherit` au bureau est propre au port : elle annule le left figé seulement pour une option partielle. Les autres règles viennent de l’oracle WordPress. Les anciens profils non flex et les widgets d’en-tête ne sont pas étendus.

[Décisions d’exécution](decisions.md) et [manifest](manifest.json).

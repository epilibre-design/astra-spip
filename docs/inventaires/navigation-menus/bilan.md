# Navigation et Menus — 26 octobre 2026

Cible : SPIP 4.4 uniquement ; développement d’Astra 1.8.0, sans publication de tag.
Menus 3.1.2 et Saisies 6.4.1 installés sur un SPIP 4.4 neuf par
`scripts/install-spip-test.sh`. Aucun site utilisateur à migrer ; aucun nouveau test
de migration. La qualification finale exclut explicitement `IdentiteMigrationTest`.

## Comportement

- CSS chargé uniquement dans `noscript` : en-tête dans le flux, navigation mobile et
  sous-liens visibles jusqu’à 922 px. Le seuil avec JavaScript reste celui d’Astra.
- Menus obligatoire ; édition native dans son interface, accessible depuis le builder.
  Nouvelle source « Menu libre : titre » pour les menus et les widgets du builder.
- Les entrées sont produites par `inclure/menu` de Menus : état de publication,
  autorisations, types et sous-menus restent traités par ce plugin. Pas de surcharge
  de son inclusion générale ni de ses types d’entrées.
- L’adaptateur ajoute les classes et commandes de l’en-tête ; pied et widgets gardent
  un arbre statique, intégral et sans boutons inertes. Les parents sans lien possèdent
  aussi une commande au clavier sur ordinateur et sur mobile.
- Un menu libre ne dépend pas de l’arbre de pages/a2a. Les autres sources restent
  disponibles ; aucune conversion ni création automatique de données éditoriales.

## Vérifications finales

- PHPUnit unitaire : **193 tests, 2 079 assertions**.
- Installation neuve complète du script : réussite, Astra/Menus/Saisies actifs.
- PHPUnit intégration sur cette installation : **101 cas, 92 exécutés,
  639 assertions, 9 ignorés**. Commande :

  ```sh
  SPIP_TEST_ROOT="$PWD/vendor/installation-spip" /src/astra-spip/vendor/bin/phpunit \
    --testsuite integration --bootstrap tests/bootstrap_integration.php \
    --filter '/^(?!.*IdentiteMigrationTest)/'
  ```

  Les 9 cas ignorés concernent les environnements dédiés Gravatar (3), oEmbed (3)
  et les images de définition responsive (3), non préparés pour ce lot.
- Navigation navigateur : 14 configurations par présentation (avec/sans JS ×
  320, 390, 768, 921, 922, 923, 1280 px), en-tête transparent et ordinaire.
- Menus navigateur : 12 configurations (avec/sans JS × 390, 922, 1280 px ×
  déroulant/panneau latéral), sous-menus et parents sans lien au clavier,
  fermeture au clavier sur ordinateur, pied statique et widgets.
- Syntaxe PHP 7.4 des fichiers PHP de production modifiés, JavaScript et shell ;
  `git diff --check`.

Tests de régression : le test de navigation échoue avant correction sur le
recouvrement du contenu à 320 px ; le test de source libre échoue avant extension
de la saisie ; un test parent sans lien reproduit le défaut relevé à la revue.

Une première suite sur le site de navigateur conservait le réglage transparent
posé pour sa reproduction : échec du test « aucun réglage », sans défaut de code.
La qualification finale utilise une installation neuve distincte. Une première
suite globale incluait le test historique de migration avant la consigne de
l’utilisateur ; ce test est exclu des commandes finales ci-dessus.

## Revue

Revue indépendante du diff : aucun Critical, deux Important relevés et corrigés :
parents sans lien dépourvus de commande ; boutons de sous-menu inertes dans le
pied. Vérifiés par tests PHP et navigateur, avec nouvelle exécution des suites.
Pas de seconde revue indépendante après corrections.

Les logs et mesures privés se trouvent dans `vendor/qualification/` du worktree.
Les tests navigateur exigent Playwright, `URL`, et pour Menus `SPIP_CLI` et
`SPIP_ROOT`, qui doivent désigner un site de test isolé.

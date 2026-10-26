# Gravatar facultatif : preuve locale

Référence : Gravatar 3.0.0, commit `dba78603edcb8c48fa55230f15b4dbecb97b8993`,
SPIP 4.4. Aucun essai d’activation sous SPIP 5. Le plugin n’est pas embarqué.

`portrait.jpg` est une image de test originale, sans personne réelle, générée par
`php generer-image.php`, GPL-3.0-or-later comme le projet. Aucune image du service Gravatar
ni d’un modèle commercial. `commentaire-avant.html` est le squelette d’Astra SPIP au
commit `0a70f60`, conservé pour comparer le repli intégralement.

Préparer un SPIP privé avec le code courant et le vrai plugin, puis :

```sh
ASTRA_GRAVATAR_PREUVE=1 vendor/bin/phpunit --testsuite integration \
  --bootstrap tests/bootstrap_integration.php --filter GravatarRenduTest
```

Le cache réel reçoit un sel de test, une image JPEG récente et un marqueur `.vide` récent,
aux noms calculés par Gravatar ; un verrou récent protège des téléchargements imprévus.
L’état précédent du sel est restauré et les fichiers de test sont retirés après chaque cas.
Ces opérations sont réservées à un site jetable, pas à un site en production.

Pour le navigateur, cloner le WordPress de référence en SQLite via l’API backup. **Corriger
DB_PATH (chemin complet de fichier), WP_HOME et WP_SITEURL du clone avant toute commande
qui charge WordPress** : copier wp-config.php seul ne suffit pas à isoler la base.
Définir WP/SPIP_ROOT/SPIP_CLI/PW et servir les deux sites privés. Aucun partage de base
ou de captures avec les références du builder.

```sh
wp --path="$WP" eval-file tests/fixtures/gravatar/wordpress.php > /tmp/gravatar-wp.json
WP_COMMENT=$(php -r '$j=json_decode(file_get_contents($argv[1]),true);echo $j["commentaire"];' /tmp/gravatar-wp.json) \
 NODE_PATH="$PW/node_modules" node tests/e2e/gravatar.cjs "$URL_WP/?p=1" "$URL_SPIP" /tmp/gravatar-rendu
```

Le pilote WordPress règle le même conteneur étroit de 750 px, sans barre latérale, et les
mêmes métadonnées affichées que SPIP. Il sert l’image locale via le filtre d’avatar WP ;
le pilote SPIP insère ses propres commentaire/réponse sur article et page, puis restaure.
Les captures mesurent d’abord la vraie géométrie, puis arrondissent seulement l’origine
sous-pixel par position relative (sans transform, qui rééchantillonnerait l’image).
La géométrie, les styles, l’espacement du nom et le SHA-256 des octets JPEG restent comparés.
Aucun changement de dimensions ni de seuil ; les requêtes HTTP externes sont refusées par
le test. Les URL blob locales de l’emoji WordPress ne constituent pas un téléchargement.

Désactiver Gravatar, déplacer son dossier hors des chemins des plugins, vider le cache,
puis lancer avec `ASTRA_GRAVATAR_MODE=sans-plugin` ; le navigateur vérifie initiales et
compilation aux trois largeurs sur article et page. Refaire l’intégration ci-dessus ;
réinstaller/réactiver ensuite le dossier d’origine et vider le cache. Les suites ordinaires
ignorent explicitement les trois tests réels sans `ASTRA_GRAVATAR_PREUVE=1`.

# Preuve oEmbed locale (R28)

Ces fixtures sont rédigées pour les tests d’Astra SPIP, sous sa licence GPL-3.0-or-later. Le lecteur est une page HTML locale ; la vignette réutilise `tests/references/blocs/images/media.png` (CC0, provenance dans ce dossier). Aucun média ni texte de fournisseur tiers n’est incorporé.

## Préparation privée

Partir de l’environnement d’import réel décrit dans `tests/references/blocs/README.md`, avec WordPress 7.1.3/Astra 4.13.8 et wp2spip `e3c262720b518bc7007e5f37779a9d6ca84f17f1`. Ne pas lancer sa suite complète de préparation pour cet essai. Le WordPress doit être isolé : la fixture écrit six pages et un mu-plugin de test.

```sh
export OEMBED_FIXTURE_URL=http://localhost:8428
export OEMBED_PREUVE_ESSAI=$(php -r 'echo bin2hex(random_bytes(8));')
OEMBED_FIXTURE_JOURNAL=/tmp/oembed-requetes.jsonl \
  PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8428 tests/fixtures/oembed/router.php
# Dans un autre terminal, mêmes variables ; chemins privés préparés :
wp --path="$WP_BLOCS" eval-file tests/fixtures/oembed/preparer-wordpress.php
(cd "$SPIP_BLOCS"; "$SPIP_CLI" wordpress:importer "$WP_BLOCS" --prefixe wpblocs_ --garder-adresse)
```

L’importeur ne met pas à jour les contenus déjà importés : préparer les pages **avant le premier import**. Pour répéter avec de nouvelles URL, recréer le site SPIP jetable ou repartir de sa sauvegarde antérieure à l’import. Ne pas modifier le texte importé pour fabriquer les attendus.

Cloner ce site importé en deux installations privées indépendantes (fichiers et sauvegarde cohérente SQLite par l’API `backup`, sans copier de WAL actif). Adapter `adresse_site` aux deux ports libres, vider leurs caches et conserver le même code Astra. Régler `astra/largeur_conteneur` à 640 dans les deux copies. Conserver les identifiants réels des articles obtenus par `titre = 'Preuve oEmbed <cas>'` ; les URL explicites sont `spip.php?page=article&id_article=<id>`.

Dans la seconde copie seulement, installer et activer oEmbed 3.5.1 au commit `e90f6a6db27f763ad78ad36c0398b39aeff4cf8c`, avec ses dépendances déclarées. Vérifier la version dans `paquet.xml`, `test_plugin_actif('oembed')` et le journal du fournisseur. Son `config/mes_options.php` inclut le chemin absolu de `tests/fixtures/oembed/options.php`. Configurer `oembed/maxwidth=640`, `oembed/maxheight=480`, `oembed/detecter_lien=oui`. Aucune option du validateur d’URL SPIP n’est assouplie.

Les URL de lecture utilisent **localhost**, car Textwheel ne transforme pas les IPv4 littérales en autoliens. Un essai séparé appelle la découverte sur `http://127.0.0.1:8428/inconnu?...` : son refus de sécurité est attendu avant toute requête HTTP. La découverte de l’URL inconnue localhost est également refusée. Les fournisseurs déclarés passent par le curl direct du plugin. Le mu-plugin WordPress autorise uniquement son endpoint `/oembed` avec le schéma, l’hôte et le port exacts ; il n’autorise pas la découverte.

## Vérifications

```sh
ASTRA_OEMBED_PREUVE=1 SPIP_TEST_ROOT="$SPIP_SANS" vendor/bin/phpunit \
  --testsuite integration --bootstrap tests/bootstrap_integration.php --filter OembedRenduTest
ASTRA_OEMBED_PREUVE=1 SPIP_TEST_ROOT="$SPIP_AVEC" vendor/bin/phpunit \
  --testsuite integration --bootstrap tests/bootstrap_integration.php --filter OembedRenduTest
NODE_PATH="$PW/node_modules" node tests/e2e/oembed.cjs "$URL_ARTICLE_SANS" sans-plugin /tmp/oembed-sans
```

Répéter le profil sans-plugin pour le témoin. Les profils `avec-plugin` et `deja-enveloppe` prennent successivement les articles large (16:9) et classique (4:3) ; `URL_WP` peut désigner la page WordPress correspondante pour comparer leurs dimensions. Le bouton de lecture oEmbed est réellement actionné avant de mesurer le conteneur vidéo et l’iframe. `erreurs` prend chacun des articles erreur HTTP, JSON invalide et fournisseur inconnu. Chaque profil vérifie 1280, 768 et 390 px et écrit mesures/captures dans le dossier fourni. Les liens de repli et légendes sont contrôlés, pas les pixels d’un lecteur externe.

La variable `ASTRA_OEMBED_PREUVE=1` rend l’absence des articles de preuve bloquante. Sans elle, les tests spécifiques sont explicitement ignorés dans la suite ordinaire. Le bootstrap continue d’utiliser `vendor/spip/spip` si `SPIP_TEST_ROOT` n’est pas renseigné.

Enfin désactiver/réactiver oEmbed dans la copie privée, vider le cache de compilation et comparer les textes SQL avant/après, sans nouvel import. Restaurer le WordPress :

```sh
OEMBED_PREUVE_ACTION=restaurer wp --path="$WP_BLOCS" eval-file tests/fixtures/oembed/preparer-wordpress.php
```

Cette commande retire le mu-plugin de preuve et restaure les réglages Astra sauvegardés. Les pages de preuve restent identifiables par leur préfixe `astra-preuve-oembed-` dans ce site jetable. SPIP 5 ne doit pas forcer l’activation de cette version du plugin, dont le manifeste ne l’autorise pas.

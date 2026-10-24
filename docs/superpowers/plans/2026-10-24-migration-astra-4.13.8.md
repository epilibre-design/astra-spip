# Migration Astra 4.13.8 — plan de coordination et de référence

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Passer le périmètre existant d'Astra SPIP à Astra 4.13.8 avec une référence vérifiée, des lots exécutables séparément et une qualification intégrée avant publication.

**Architecture:** Une branche commune `migration-astra-4.13.8`, issue de main, reçoit les lots ci-dessous. Le WordPress cible et le SPIP candidat ont leurs propres dossiers, bases et ports ; les références 4.11.0 sont archivées avant régénération. La fusion finale vers main est la seule étape qui publie le changement de référentiel.

**Tech Stack:** PHP 7.4/8.4, SPIP 4.4 de test, WordPress 7.1.3, Astra 4.13.8, wp-cli, SQLite pour le builder, MySQL existant isolé pour l'import ciblé, PHPUnit 11.5, Python 3, Playwright Chromium.

**Spec:** [Référence 4.13.8](../specs/2026-10-24-reference-astra-4.13.8-design.md), et les quatre specs qui y sont liées.

## Global Constraints

- Base de rédaction : `503819e` ; version candidate proposée : Astra SPIP 1.6.0, tag après fusion seulement.
- Thème gratuit **4.13.8**, WordPress **7.1.3**, archive SHA-256 `3cce1a750dcb699429117a88987aa8f10be404374492eef94379d2c4df6a6572`.
- Aucune nouvelle option utilisateur ni remise à zéro des données ; préserver les formulaires et les empreintes du seed Garden.
- Assets officiels copiés à l'identique ; adaptations SPIP séparées et sourcées.
- Garder les preuves historiques 4.11.0 ; conserver les ID des 35 références existantes et leurs variantes.
- Ni changement du WordPress partagé sur 8401, ni lancement des préparations complètes de wp2spip ; arrêt des serveurs par PID.
- Écritures git fichier par fichier, sans trailers ; ne pas indexer les fichiers locaux ignorés, caches Python et inventaires hors du lot.
- Une étape rouge indique un échec **à constater pendant l'exécution**, pas un résultat déjà obtenu par la rédaction de ce plan.
- Les nouveaux pilotes Playwright reprennent le userAgent de navigateur ordinaire des e2e existants ; le robot HeadlessChrome ne doit pas forcer un ancien cache SPIP. Les changements de configuration passent par appliquer.sh et son vidage du cache.

## Review Focus

1. Mauvaise version réellement active malgré un numéro de configuration correct : rejet avant mutation (Task 1).
2. Une base ou un lien du site d'un autre agent réutilisé : contrôle des chemins réels et des ports, sans supprimer le dossier source (Task 1).
3. Données anciennes et options absentes : aller-retour et compatibilité explicitement testés (plans CSS et final Task 3).
4. JS/CSS partiellement migrés : les validations de lot ne remplacent pas la qualification de la branche complète (Task 3).
5. Ancien attendu réécrit pour faire passer SPIP : provenance WordPress indépendante et bilan des écarts obligatoires (Task 3).

## Ordre et interfaces entre lots

| Ordre | Livrable | Dépendances et vérification isolée |
| --- | --- | --- |
| A | Task 1 ci-dessous : sources, garde et environnements | Aucun code futur ; validation du manifeste et captures cible indépendantes du port |
| B | Task 2 : assets et marqueur de version | A ; empreintes et intégration du marqueur, pas promesse de parité de toute la cascade |
| C | [Icônes et liens](2026-10-24-icones-liens-sociaux-astra.md) | A/B ; tests ciblés du rendu social et des données |
| D | [CSS et responsive](2026-10-24-css-responsive-astra.md), Tasks 1/2 | A/B ; CSS de groupes et cascade ; ces deux tasks ne dépendent pas du choix sans JS |
| E | [Navigation](2026-10-24-navigation-accessible-astra.md) | A/B/D ; DOM et parcours clavier de configurations appliquées |
| D bis | CSS Task 3 : seuil et première peinture | A/B/D ; choix sans JS validé avant implémentation, tests de gabarits et profil responsive |
| D ter | CSS Task 4 : blocs et cas rares | A/B/D ; import ciblé isolé et qualifications RTL/layout |
| F | [oEmbed](2026-10-24-compatibilite-oembed.md) | A/B/D Tasks 1/2 ; tests avec/sans plugin et import ciblé, sans dépendre de E |
| G | Task 3 ci-dessous : intégration complète | A–F, y compris D bis/D ter ; toutes les références, formulaires, blocs et qualification des points ouverts |

Un lot peut être rejeté séparément. Les validations transitoires sont enregistrées avec leur périmètre ; elles n'autorisent pas une fusion de l'état mixte dans main. Les commandes de lot sont exécutables sans fichier issu d'une task future.

## Environnement commun d'exécution

Variables utilisées par tous les plans :

```bash
export WP_REFERENCE=/chemin/wordpress-4138
export ASTRA_REFERENCE_ARCHIVE=/chemin/astra.4.13.8.zip
export ASTRA_WP=/chemin/astra-4.13.8
export URL_WP=http://127.0.0.1:8421
export URL_SPIP=http://127.0.0.1:8424
export SPIP_ROOT="$PWD/vendor/spip/spip"
export SPIP_CLI='php /chemin/spip-cli/bin/spip'
export SPIP_ADMIN_PASS="${SPIP_ADMIN_PASS:?définir le mot de passe du compte de test}"
export PW=/chemin/contenant/node_modules
export RUN_DIR=/chemin/preuves-migration
```

Préparer d'abord le vendor et le SPIP de test dans un worktree neuf de la branche de migration, avant tout test de Task 1 :

```bash
composer install --no-interaction
SPIP_CLI="$SPIP_CLI" sh scripts/install-spip-test.sh
test "$(readlink -f "$SPIP_ROOT/plugins/astra")" = "$PWD"
composer tests-unit
composer tests-integration
```

Ne pas exécuter ces commandes sur un vendor ou un site pointant vers un autre worktree. Le site doit être neuf ou déjà identifié comme appartenant à cette migration ; sinon créer un nouveau dossier. Conserver ces résultats de base avant d'ajouter les nouveaux tests, puis le manifeste des fichiers et réglages de référence avant modification des fixtures.

Les listes de configurations sont construites sans duplication depuis la même source :

```bash
mapfile -t CONFS < <(php -r 'require "tests/comparaison/configurations.php"; echo implode("\n", array_keys(astra_configurations_reference())), "\n";')
mapfile -t CONFS_R12_R24 < <(printf '%s\n' "${CONFS[@]}" | rg '^R(1[2-9]|2[0-4])-')
```

Sur la base 503819e, CONFS a 35 entrées, dont P1-page/P2-accueil/P3-page-pleine ; les fixtures ajoutées ensuite augmentent ce nombre, sans retirer les anciennes. R25/R26/R27/R28 sont réservés respectivement à navigation/CSS/social/oEmbed, avec variantes nommées dans chaque plan.

Les ports sont des valeurs proposées, à vérifier libres avant démarrage. `WP="$WP_REFERENCE"`, `URL="$URL_WP"` et `URL_WP` sont des noms différents utilisés par les scripts existants : les passer explicitement. Les mots de passe des comptes de test ne sont jamais enregistrés dans les rapports publiés.

## Task 1: Référence vérifiable et espaces de test isolés

**Files:**
- Create: `tests/references/cible.json`, `tests/references/environnement.php`, `tests/references/verifier.php`, `scripts/install-wp-reference.sh`, `tests/unit/ReferenceEnvironnementTest.php`.
- Modify: `tests/references/capturer.sh`, `tests/references/styles/capturer.sh`, `tests/references/transparent/capturer.sh`, `tests/references/README.md`.

**Interfaces:**
- Consumes: archive officielle ci-dessus ; sources de WordPress 7.1.3 et du plugin SQLite Database Integration 3.1.1 (drop-in 1.8.0), épinglées par empreinte à la préparation.
- Produces: `astra_reference_erreurs(array $attendue, array $reelle): array` (liste de messages, vide si conforme).
- Produces: `php tests/references/verifier.php <WP_ROOT> <ARCHIVE_ZIP> <MANIFEST_JSON>` ; code 0 si conforme, 1 sinon ; manifeste public sans données de connexion.
- Produces: `scripts/install-wp-reference.sh` utilisant `WP_REFERENCE`, `URL_WP`, `ASTRA_WP`, `ASTRA_REFERENCE_ARCHIVE`, `SQLITE_PLUGIN` (dossier de sources épinglées du plugin), sans démarrer le serveur.

- [ ] **Step 1: Écrire les tests de garde**

`ReferenceEnvironnementTest::testRejetteUneReferenceFausse` vérifie la référence correcte puis, indépendamment : Astra 4.11.0, WordPress autre que 7.1.3, thème actif autre qu'astra, SHA du ZIP incorrect et un asset altéré et une source PHP du thème altérée. Chaque cas faux retourne au moins une erreur. Attendue : `wordpress`, `astra`, `theme`, `archive_sha256`, `assets`, `integrite_sources` (true attendu) ; valeurs de la spec et empreintes exactes des deux assets. Exemple d'assertion, sans dépendre d'un site :

```php
$this->assertSame([], astra_reference_erreurs($attendue, $reelle));
$reelle['astra'] = '4.11.0';
$this->assertNotEmpty(astra_reference_erreurs($attendue, $reelle));
```

- [ ] **Step 2: Constater l'échec**

Run: `vendor/bin/phpunit --testsuite unit --filter ReferenceEnvironnementTest`. Attendu : erreur de chargement de la garde encore absente, puis assertion rouge si une garde provisoire accepte tout ; aucune écriture WordPress.

- [ ] **Step 3: Implémenter la garde et le site isolé**

`cible.json` contient les champs attendus, URL de l'archive et empreintes de la spec. `verifier.php` contrôle d’abord le ZIP puis lit la version du cœur, le thème actif, la version et les fichiers réellement installés via wp-cli avec `--skip-themes --skip-plugins`, afin que le thème erroné ne lance pas ses hooks et ne modifie pas astra-settings pendant la garde. Le drop-in SQLite nécessaire au chargement de la base reste actif. Après contrôle du SHA du ZIP, comparer également chaque fichier installé du thème au contenu correspondant de cette archive (pas seulement style.css et les deux assets) pour produire `integrite_sources` ; ne pas fabriquer le réel depuis l’attendu. Chaque script de capture appelle cette garde **avant** son premier `wp eval-file` et refuse une référence incorrecte.

L'installateur crée un WordPress 7.1.3 neuf avec une base SQLite propre dans son dossier. Il copie le plugin SQLite et son drop-in en corrigeant les chemins vers cette copie, fixe `DB_PATH` sur la base propre et vérifie son chemin réel avant le premier `wp core install`. Pas de reprise de wp-config, de base ou de symlink d'un autre site. Le thème installé est celui vérifié, le site est configuré sur `URL_WP`. Refuser un dossier non vide non marqué comme créé par cet installateur ; une relance sur le dossier marqué vérifie ses versions et sa base sans réinitialiser ses réglages. Aucun mot de passe en sortie du manifeste.

Le SPIP de base est déjà installé par la préparation commune, avec résultats conservés ; aucun échec antérieur ne sera attribué sans preuve à la migration. Vérifier de nouveau que `plugins/astra` pointe vers le worktree candidat. Les fixtures d'installation sont volontairement récréées sur ce seul SPIP de test.

- [ ] **Step 4: Vérifier**

Run: test unitaire précédent, puis installateur sur un dossier vide, relance, garde sur le site cible. Attendu : tous passent, versions 7.1.3/4.13.8 et base dans le dossier privé. Faire aussi le refus sur un dossier contenant un fichier témoin : code non nul, fichier inchangé et aucune table créée. Sur un site témoin 4.11.0, la capture doit être refusée avant modification de `astra-settings` (empreinte de l'option avant/après).

Avant de modifier les fixtures communes, archiver les sorties des 35 références avec un **autre** WordPress 4.11.0 isolé, scripts de `503819e`, mêmes réglages et `SORTIES` privé. La nouvelle garde cible n'est pas contournée pour créer cette archive historique. Conserver provenance et SHA des réglages, HTML normalisé, styles calculés et captures. Comparer aussi le SPIP encore en référence 4.11.0 à ce WordPress historique via les scripts de 503819e, avant Task 2 ; ne pas confondre sorties réutilisées et nouvelles captures.

- [ ] **Step 5: Commit**

Indexer les fichiers de cette task un par un ; message : `Fiabiliser la référence WordPress et isoler la migration Astra`.

## Task 2: Assets 4.13.8 et marqueur réellement porté

**Files:**
- Modify: `css/main.min.css`, `js/frontend.min.js`, `astra_fonctions.php`, `tests/references/wp/commun.php`, `licenses/astra-4.13.8-readme.txt` (nouveau), `CREDITS.md`.
- Create: `tests/unit/AssetsReferenceTest.php`.
- Modify: `tests/integration/PluginActifTest.php` (test de classe de version).

**Interfaces:**
- Consumes: garde et sources de Task 1.
- Produces: copies exactes des deux assets et `_ASTRA_VERSION_THEME = '4.13.8'`. Les données de compatibilité peuvent conserver une ancienne version, la classe du thème actif reste `astra-4.13.8`.

- [ ] **Step 1: Écrire les tests**

`AssetsReferenceTest::testAssetsIdentiquesA4138` compare les deux `hash_file('sha256', ...)` aux empreintes de la spec. `PluginActifTest::testClasseVersionTheme` analyse un rendu public et vérifie une seule classe `astra-4.13.8`, aucune `astra-4.11.0`. Une option de compatibilité ancienne ne change pas cette classe.

- [ ] **Step 2: Constater l'échec**

Run: `vendor/bin/phpunit --testsuite unit --filter AssetsReferenceTest`, puis `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter testClasseVersionTheme`. Attendu : empreintes et classe anciennes.

- [ ] **Step 3: Copier et actualiser les métadonnées**

Copier les deux fichiers officiels, changer le marqueur du port et la version fraîche dans `ref_etat_initial()`. Préserver les drapeaux anciens explicites des références de compatibilité. Copier la notice amont et mettre à jour la provenance actuelle sans effacer les crédits historiques. Aucune modification manuelle du bundle minifié.

- [ ] **Step 4: Vérifier**

Run: les deux tests précédents et la garde sur WP_REFERENCE. Attendu : vert. Lancer R0/R2 comme contrôle de chargement et recueillir toute erreur console ; les écarts de CSS ou d'ARIA encore affectés aux lots D/E sont consignés, jamais déclarés résolus par cette task.

- [ ] **Step 5: Commit**

Message : `Passer les assets et la version de référence à Astra 4.13.8` ; ajout fichier par fichier.

## Task 3: Qualification intégrée, documentation et version candidate

**Files:**
- Modify: `tests/references/README.md`, `tests/references/blocs/README.md`, `README.md`, `CREDITS.md`, `paquet.xml`, commentaires actuels des fichiers transposés.
- Create: `docs/inventaires/astra-4.13.8-qualification/decisions.json`, `bilan.md`, `manifest.json`, `tests/references/verifier_qualification.py`, `tests/references/test_qualification.py` et preuves ciblées nécessaires.

**Interfaces:**
- Consumes: les quatre plans de composants, toutes leurs validations ciblées, les 35 configurations inchangées d'identité et les scénarios ajoutés par profil.
- Produces: version candidate 1.6.0 ; manifeste du commit testé, traitements des I/L/V et bilan indépendant des différences. Le validateur expose `erreurs_qualification(inventaire: dict, decisions: list, racine: Path) -> list[str]` et une CLI sans argument utilisant les chemins ci-dessus, code1 si la liste d’erreurs est non vide.

- [ ] **Step 1: Écrire le contrôle de complétude**

Créer `decisions.json` avec une liste vide valide, puis un contrôle de données dans `tests/references/verifier_qualification.py` (nouveau), qui lit l’inventaire et cette liste. Il exige exactement une décision pour chaque ID I/L/V ; statut parmi `corrige`, `equivalent`, `absent`, `limite-assumee`, avec source, preuve et domaine. Dans test_qualification.py, exercer un jeu complet valide puis indépendamment un doublon, un ID manquant et une preuve absente ; chaque jeu invalide est rejeté. Chaque décision possède les clés `id`, `statut`, `source`, `preuve`, `domaine`, et les chemins de preuve sont résolus sous la racine du dépôt. `limite-assumee` exige le lien de décision explicite, pas un texte « à voir » pour un défaut utilisable non tranché.

- [ ] **Step 2: Constater l'échec puis renseigner les décisions**

Run: `python3 -m unittest discover -s tests/references -p 'test_qualification.py'`, puis `python3 tests/references/verifier_qualification.py`. Attendu initial : références manquantes, pas une exception de parsing. Renseigner les décisions à partir des lots, y compris la note générique sécurité 4.12.5 : chemins de droits, validation et sorties examinés, résultat et preuve. L'ajout d'une exception ne modifie pas la classe historique de l'inventaire de départ.

- [ ] **Step 3: Qualification complète sur la branche intégrée**

Exécuter et enregistrer les commandes ci-dessous, après vérification de la garde. Les références nouvelles de profils sont passées en plus de la liste de 35 existante obtenue de `configurations.php` et des trois Pn, sans omission des variantes R20/R21/R22/R24.

```bash
WP="$WP_REFERENCE" URL="$URL_WP" tests/references/styles/capturer.sh
WP="$WP_REFERENCE" URL="$URL_WP" tests/references/transparent/capturer.sh
composer tests-unit
composer tests-integration
# CONFS est la liste explicite des 35 configurations et des références ajoutées.
WP="$WP_REFERENCE" URL="$URL_WP" SORTIES="$RUN_DIR/wp-final" tests/references/capturer.sh "${CONFS[@]}"
REFERENCES="$RUN_DIR/wp-final" php tests/comparaison/comparer_html.php "$SPIP_ROOT" "$URL_SPIP"
REFERENCES="$RUN_DIR/wp-final" php tests/comparaison/comparer_html.php "$SPIP_ROOT" "$URL_SPIP" "${CONFS_R12_R24[@]}"
WP="$WP_REFERENCE" URL_WP="$URL_WP" SORTIES="$RUN_DIR/compare-final" tests/comparaison/comparer_captures.sh "$SPIP_ROOT" "$URL_SPIP" "${CONFS[@]}"
```

Réaliser également les profils navigation, CSS/responsive, social et oEmbed définis dans leurs plans. Les e2e des formulaires et répartitions utilisent le même compte privé que la préparation :

```bash
tests/comparaison/appliquer.sh "$SPIP_ROOT" R8-identite
URL="$URL_SPIP" LOGIN=admin MDP="$SPIP_ADMIN_PASS" NODE_PATH="$PW/node_modules" node tests/e2e/formulaire_styles.cjs
URL="$URL_SPIP" LOGIN=admin MDP="$SPIP_ADMIN_PASS" NODE_PATH="$PW/node_modules" node tests/e2e/formulaire.cjs
RACINE="$SPIP_ROOT" URL="$URL_SPIP" SPIP_CLI="$SPIP_CLI" NODE_PATH="$PW/node_modules" node tests/e2e/repartitions.cjs
tests/comparaison/appliquer.sh "$SPIP_ROOT" R0-defaut
```

Faire ensuite les contrôles de syntaxe PHP 7.4 des fichiers de production modifiés (les tests PHPUnit 11 restent exécutés sous PHP 8.4) et sorties CSS ciblées sous PHP 7.4/8.4 ; tests et comparaisons des blocs importés et médias décrits dans le plan CSS. Les commandes exactes des profils sont définies dans leur plan.

Attendu : tests verts, aucun nouveau différentiel inexpliqué, seuils existants conservés. Les exceptions historiques (notamment noms de R13 et pertes d'import) sont vérifiées sur la cible puis reconduites avec preuve ou retirées ; une sortie non nulle du comparateur n'est pas appelée verte sans bilan des différences.

- [ ] **Step 4: Documenter, committer puis remesurer le commit**

Actualiser la référence courante, les crédits et les limites ; paquet à 1.6.0 sans changement de schéma. Préserver les anciennes preuves. Commit : `Qualifier Astra SPIP 1.6.0 sur Astra 4.13.8`. Vérifier le commit installé, puis refaire les comparaisons finales sur ce commit et après fusion selon la procédure de publication. Les nouvelles preuves contiennent le SHA réel ; ne pas les attribuer à un commit antérieur à la dernière modification de code.

Le tag n'est pas posé par le plan. La fusion de la branche commune nécessite une qualification complète, et le repli sans JS doit avoir sa décision et son test avant l'implémentation du lot responsive.

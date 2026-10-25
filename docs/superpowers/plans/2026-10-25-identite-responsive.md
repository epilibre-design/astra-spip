# Identité responsive — plan de mise en œuvre

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** appliquer les tailles et visibilités du logo, du titre et du slogan sur les trois appareils, selon Astra 4.13.8.

**Architecture:** garder le générateur d'identité et isoler ses calculs responsifs dans `inc/astra_identite_responsive.php`. Le registre active la saisie des cinq options ; le HTML reste unique et une image de définition suffisante sert les différentes largeurs. Capturer les attendus WordPress avant le code de production.

**Tech Stack:** PHP 7.4+ pour le plugin, SPIP 4.4 pour les intégrations, PHPUnit 11.5 sous PHP 8.2+, WordPress 7.1.3 / Astra 4.13.8, WP-CLI, Python 3, Playwright Chromium.

**Spec:** `docs/superpowers/specs/2026-10-25-identite-responsive-design.md`, validée et fusionnée par PR #21, `main` au commit `6b2703c`.

**État :** plan rédigé, non exécuté ; aucun résultat d'essai ou de tests nouveau annoncé.

## Global Constraints

- Cinq options : `ast-header-responsive-logo-width`, `font-size-site-title`, `font-size-site-tagline`, `display-site-title-responsive`, `display-site-tagline-responsive`. Stockage `astra/styles`, sans migration SQL.
- Tailles bornées de 0 à 1000 ; unités `px`, `em`, `rem`, `vw`, `%` pour les deux tailles de police seules. Largeur de logo en px.
- Unité de police absente : px implicites. Unité inconnue : retirer valeur et unité de cet appareil seulement. Ce n'est pas le contrat des marges sans unité.
- Visibilité absente/vide : défaut de l'appareil (titre vrai, slogan faux) ; booléen faux conservé. Taille vide : absence de déclaration, cascade.
- Ordre ordinateur, `max-width:921px`, `max-width:544px` ; fonctions tablette/mobile, pas `astra_header_break_point()`.
- Aucun changement sans réglage concerné. Les valeurs tablette/mobile déjà stockées deviennent actives ; annoncer ce changement. Pas de nouveaux réglages de couleurs, logos distincts ou icônes SVG.
- Assets officiels inchangés ; provenance dans le code et `CREDITS.md` : Astra 4.13.8 amont GPL v2+, port GPL-3.0-or-later.
- Ne pas modifier Garden, publier, pousser ou taguer au titre de ce plan. Garder la version 1.6.0 pendant les lots ; version suivante dans un commit de publication après décision d'intégration des lots.
- `git add` fichier par fichier, commits français sans trailer. Ne pas indexer `.gitignore`, `.ignore`, `graft/`, caches Python ou inventaires étrangers.
- Worktree et bases privés ; intégration, e2e et captures séquentiels. Pas de tests complets de préparation de wp2spip. Arrêt des processus par PID.

## Review Focus

- Une valeur mobile seule doit activer l'identité : `testUnSeulAppareilActiveLeGroupe`, tâche 2.
- Une unité invalide ne doit pas devenir px après filtrage : `testUniteInvalideRetireLaValeurDuSeulAppareil`, tâche 2.
- Faux et unité absente doivent survivre à un POST réel : `testAllerRetourIdentiteParAppareil`, tâche 4.
- Une tablette plus large ne doit pas agrandir le bureau ni recevoir un petit fichier : `testLogoGardeLaDefinitionEtLeCadreBureau`, tâche 3.
- Taille sans desktop, trou tablette et zéro typé doivent suivre le CSS amont : R29 partiels et `testTaillesContreWordPress`, tâches 1–2 et 5.

## Fichiers et responsabilités

| Fichier | Rôle | Tâche |
| --- | --- | --- |
| `tests/references/responsive/identite.json` | Matrice d'entrées, types JSON préservés | 1 |
| `tests/references/responsive/identite.py`, `test_identite.py`, `capturer-identite.sh` | Extraction/capture indépendante | 1 |
| `tests/references/responsive/attendus-identite/*.css`, `manifest-identite.json` | Oracles et adaptations déclarées séparément | 1–2 |
| `tests/references/wp/identite-responsive.php`, `R29-identite-responsive*.{php,json}` | Pilotes WordPress et sondes | 1 |
| `tests/comparaison/configurations.php`, `tests/comparaison/appliquer.sh` | Matrice SPIP et installation/restauration du logo de test | 1 |
| `inc/astra_identite_responsive.php`, `inc/styles/identite.php`, `inc/astra_styles.php` | Helpers, validation et génération | 2 |
| `tests/unit/StylesIdentiteResponsiveTest.php` | Contrat pur et comparaison aux oracles | 2 |
| `astra_fonctions.php`, `inclure/identite.html` si nécessaire | Présence HTML et image | 3 |
| `tests/integration/IdentiteResponsiveTest.php`, `tests/e2e/identite_responsive.cjs` | HTML, ressources et rendu | 3, 5 |
| `tests/references/responsive/images-identite.json` | URL Placehold, dimensions, empreintes et fichiers locaux hors dépôt | 1 |
| `inc/astra_styles_saisie.php`, `formulaires/configurer_astra_styles.{html,php}`, `lang/astra_{fr,en}.php` | Saisie et explications | 4 |
| `tests/unit/StylesSaisieTest.php`, `tests/integration/StylesFormulaireTest.php`, `tests/e2e/formulaire_identite_responsive.cjs` | POST, conservation, erreurs | 4 |
| `README.md`, `CREDITS.md`, `tests/references/README.md`, `docs/inventaires/identite-responsive/` | Qualification et limites | 5 |

## Préparer l'exécution

- [ ] Reprendre/créer un worktree depuis `origin/main` contenant la spec. Noter le SHA de départ. `composer install`, puis `SPIP_CLI=… composer install-spip-test` sur ce seul worktree : ce script recrée les contenus de sa base.
- [ ] Vérifier que `readlink -f vendor/spip/spip/plugins/astra` est le worktree courant. Préparer un WordPress privé avec `scripts/install-wp-reference.sh` et `tests/references/cible.json`. Ne pas partager les captures du port 8401.
- [ ] Exporter `WP`, `PW`, `SPIP_CLI`, `ASTRA_REFERENCE_ARCHIVE`, `SPIP_ROOT=$PWD/vendor/spip/spip`, `URL_WP`, `URL_SPIP`, `ASTRA_PREUVES` (répertoire privé de sorties), puis `LOGIN`/`MDP` du site de test sans les journaliser. Exporter aussi `ASTRA_IMAGES`, répertoire privé des fichiers Placehold. Exemple de ports, après contrôle de disponibilité : 8441 et 8444. Quatre workers pour les serveurs PHP.
- [ ] Vérifier la cible : `php tests/references/verifier.php "$WP" "$ASTRA_REFERENCE_ARCHIVE" tests/references/cible.json`, exit 0. Lancer successivement `vendor/bin/phpunit --testsuite unit` puis `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`. Tout échec initial est expliqué avant le lot.

### Tâche 1 — Oracles WordPress et matrice R29

**Files:** nouveaux fichiers de références et manifeste d’images du tableau, plus `tests/comparaison/configurations.php` et `tests/comparaison/appliquer.sh`.

**Interfaces:**
- Consomme `ref_etat_initial()`, `ref_reglages(array $reglages)` de `wp/commun.php`, `regles(css)` de `styles/regles.py`.
- Produit `extraire_identite(css: str) -> list[str]` dans `identite.py`, `ref_identite_responsive(string $variante): void` dans le pilote WP, et `astra_configurations_identite_responsive(): array` en fin de `configurations.php`, fusionné dans le retour de `astra_configurations_reference()`.
- JSON : `variante => {styles, meta, logo, transparent}` ; styles au format `astra/styles`, `logo` désigne `logo-120x60` par défaut, ou un identifiant du manifeste d’images ci-dessous ; `transparent` est booléen. Même texte libre « Un site propulsé par SPIP » des deux côtés. Les pilotes R29 ne dupliquent pas ces données.

- [ ] **1. Écrire les tests de l'extracteur.** `test_identite.py` garde le média, l'ordre des règles et les deux font-size, retire les propriétés non ciblées et conserve les sélecteurs groupés sans les casser aux virgules de `:not()`. Exemple d'attendu : `[@media (max-width:921px)] .site-title{font-size:18px;font-size:1.125rem;display:block;}` ; la même règle à 544 doit rester distincte. Une couleur et un sélecteur de contenu n'entrent pas dans l'oracle.
- [ ] **2. Constater le rouge.** `python3 -m unittest discover -s tests/references/responsive -p test_identite.py` échoue sur l'extracteur absent/incomplet, sans attendu issu du générateur SPIP.
- [ ] **3. Préparer les images et implémenter les outils.** Selon la précision de Gilles pendant la rédaction, télécharger une fois les quatre PNG dimensionnels de Placehold : `https://placehold.co/200x100/2a2a2a/ffffff.png?text=Logo` et les mêmes URL pour 120x60, 40x20, 80x116. Vérifier dimensions/format, conserver les octets dans un répertoire privé `ASTRA_IMAGES`, et consigner URL/date/SHA-256 dans `images-identite.json`. Ne pas régénérer à chaque capture, ni attribuer CC0 à ces images : elles ne sont pas distribuées dans le dépôt. La référence générique utilise 120x60 pour isoler le repli de 60 px du port ; les cas de largeur réglée utilisent 200x100, la petite source 40x20 et le rapport non entier 80x116. Les deux sites servent exactement les mêmes fichiers. Extraire `font-size`, `display`, `width`, `max-width` des sélecteurs exacts d'identité de la spec. Partir de R0 avec le placement de R8, **sans ses styles** ; appliquer seulement ceux du cas. Compléter les seuls booléens WP par appareil selon la spec, pas les tailles. Profil de base 16 px ; drapeaux WordPress de zéro et de désactivation px/rem absents.

| Suffixe R29 (nom de base `R29-identite-responsive`) | Entrées |
| --- | --- |
| aucun | Logo 40/40/32 ; titre 20/18/16 px ; slogan 11/12/11 px ; visibilités vraies |
| `-ordinateur` | Titre desktop 20 px ; autres appareils absents |
| `-tablette` | Titre tablet 18, unité absente ; desktop/mobile absents |
| `-mobile` | Slogan mobile 11 sans unité, visibilité slogan mobile vraie seule |
| `-trous` | Titre desktop 24 px, mobile 12 px, tablette vide |
| `-vides` | Deux tableaux de tailles vides, pas d'autre réglage |
| `-zero-nombre`, `-zero-chaine` | Titre desktop 20 px, tablet respectivement nombre 0 et chaîne `"0"` |
| `-unites` | Titre 1.5 rem / 1.25 em / 4 vw ; slogan desktop 80 %, tablet 12 sans unité |
| `-visibilites` | Titre faux/vrai/faux ; slogan faux/faux/vrai |
| `-masques` | Deux visibilités fausses partout |
| `-logo-large` | Logo 32/96/48, PNG Placehold 200 × 100 |
| `-logo-sans-bureau` | Desktop absent, tablet 96, mobile 48, PNG 120 × 60 pour comparer le repli historique sans élargir le périmètre |
| `-logo-petit`, `-logo-ratio` | 32/96/48 avec respectivement PNG 40 × 20 et 80 × 116 ; limite de définition et arrondis |
| `-transparent` | Cas Garden avec activation et couleurs R16 |

Utiliser les PNG aux dimensions des cas de logo, sans matrice de couleurs, polices
ou formats. [Placehold](https://placehold.co/) sert uniquement à préparer ces images
dimensionnées ; les mêmes fichiers locaux sont employés côté WP et SPIP.

- [ ] **4. Installer le logo avant les sondes.** Côté WP, importer la copie locale via `media_handle_sideload()` et `set_theme_mod("custom_logo", $id)` ; conserver l’id réutilisable par fichier pour éviter les doublons. Côté SPIP, ajouter à l’appliqueur la lecture de `logo` : `include_spip("action/editer_logo")`, puis `logo_modifier("site", 0, "on", $copie_temporaire)` ; passer une copie, car l’API peut consommer le fichier. Ne remplacer que le logo créé par ce pilote, marqué dans une méta technique de test ; refuser un logo étranger. Nettoyer le logo de test via `logo_supprimer("site", 0, "on")` lorsqu’on quitte R29 et au retour R0. Ajouter des assertions du pilote sur les dimensions réellement servies et le nettoyage, avant toute mesure. **Décrire les sondes :** JSON R29 : `.site-title`, `.site-header .site-description`, `header .custom-logo-link img`, `#masthead` ; tailles, display, width/height, couleurs dans le cas transparent. Le cas masqué est traité par le pilote dédié d'absence de tâche 3, sans demander au comparateur générique de mesurer un élément inexistant.
- [ ] **5. Capturer.** `WP="$WP" URL="$URL_WP" tests/references/responsive/capturer-identite.sh` : garde de cible, CSS complet en temporaire, extrait dans `attendus-identite/<variante>.css`, manifeste avec versions, options et empreintes ; trap de retour à R0. Conserver aussi les cas légitimement vides. Le cas sans unité 12 donne `12px;font-size:0.75rem`.
- [ ] **6. Vérifier et committer.** Test Python vert, lecture des oracles et de leurs conditions. L'outil produit également une sonde WP de chaque cas à 1280/768/390, avant le code SPIP. Déclarer les adaptations dans un fichier séparé, jamais en modifiant l'oracle. Commit fichier par fichier : `Références : identité responsive d’Astra 4.13.8`.

### Tâche 2 — Validation et génération des cinq options

**Files:** `inc/astra_identite_responsive.php`, `inc/astra_styles.php`, `inc/styles/identite.php`, `tests/unit/StylesIdentiteResponsiveTest.php`.

**Interfaces:**
- `astra_identite_responsive_pose(array $styles): bool` : une valeur tablet/mobile d'une des cinq options, y compris faux/zéro, active le groupe ; une unité seule ne compte pas.
- `astra_identite_visible_appareil(array $valeurs, string $appareil, bool $defaut): bool` : valeur explicite sinon défaut propre à l'appareil.
- `astra_identite_taille_appareil(array $valeurs, string $appareil): string` : font-size selon Astra, sans défaut inventé.
- Garder `astra_styles_identite(array $s): array` et `astra_styles_ecrire(array $regles): string`. Ne pas changer `astra_styles_taille_police()` pour tous les groupes.

- [ ] **1. Écrire `StylesIdentiteResponsiveTest`.** Les tests nommés dans Review Focus couvrent l'activation, le refus d'unité par appareil et la comparaison aux attendus. Réutiliser `StylesAttendusTest::regles()` comme les tests de marges. Assertions minimales :

```php
$this->assertSame('18px;font-size:1.125rem', astra_identite_taille_appareil(['tablet' => '18'], 'tablet'));
$this->assertSame('', astra_identite_taille_appareil(['tablet' => '18'], 'desktop'));
$this->assertSame('80%', astra_identite_taille_appareil(['tablet' => '80', 'tablet-unit' => '%'], 'tablet'));
$this->assertFalse(astra_identite_visible_appareil(['tablet' => '0'], 'tablet', true));
$this->assertTrue(astra_identite_visible_appareil(['desktop' => false], 'tablet', true));
$this->assertSame('', astra_styles_css([], []));
```

Vérifier séparément 0, 0.0 et `"0"` : avec les drapeaux absents, le helper amont omet le zéro numérique ; la chaîne zéro en px donne `0px;font-size:0rem`. Tester négatifs, non-finis et injections ; unités connues sur chaque appareil ; faux sous quatre formes. Une unité inconnue retire sa valeur, pas celle des voisins.

- [ ] **2. Constater le rouge.** `vendor/bin/phpunit --testsuite unit --filter StylesIdentiteResponsiveTest` échoue sur les helpers/règles absents et la validation encore permissive des unités ; conserver sortie et code, sans annoncer de compteurs futurs.
- [ ] **3. Implémenter les helpers/validation.** Les deux tailles seules acceptent `%`. Absence d'unité, ou unité vide choisie dans le formulaire : clé omise et px implicites ; autre unité inconnue : retirer valeur + unité de cet appareil. Garder types et tableaux partiels. Charger le module pur une fois depuis `inc/astra_styles.php`, après les constantes, pour rendre ses fonctions disponibles aussi lors d’un rendu isolé d’identité ; activation par l'ancien test de groupe OU le helper responsive.
- [ ] **4. Assembler les règles.** Pour chaque option sans nouvelle valeur tablet/mobile, garder son chemin bureau historique. Pour une option devenue responsive, lire chaque appareil ; omettre les tailles absentes, compléter les visibilités. Suivre les sélecteurs et l'ordre de la spec, avec le diviseur 16 et la conversion entière d'Astra en px. Pour les nouveaux cas sans desktop, neutraliser le 26/15 figé par `font-size:inherit` sur le seul sélecteur concerné, puis émettre les tailles réellement posées. Cette adaptation est séparée des règles amont, et doit produire les valeurs héritées capturées sur WP ; pas de 26/15 artificiel sur tablette/mobile.
- [ ] **5. Vérifier.** Filtre puis suite unitaire complète verts. Les attendus R8 restent inchangés sauf delta explicite d'une valeur responsive déjà présente. Lint PHP 7.4 des fichiers de production et même CSS qu'avec PHP courant pour tous les cas via le même pilote temporaire lisant la matrice. Si la neutralisation ne reproduit pas l'héritage mesuré, résoudre la cascade avant de poursuivre ; ne pas modifier les valeurs témoins.
- [ ] **6. Committer.** `Identité : appliquer les tailles et visibilités par appareil`.

### Tâche 3 — HTML unique et ressource de logo suffisante

**Files:** `astra_fonctions.php`, `inclure/identite.html` seulement si nécessaire ; nouveaux `tests/integration/IdentiteResponsiveTest.php`, `tests/e2e/identite_responsive.cjs` ; pilotes et images préparés en tâche 1.

**Interfaces:**
- Garder `astra_identite_visible(string $option): bool`, en réutilisant le helper de visibilité de tâche 2.
- Garder `astra_logo_reduire($img): string`. Ajouter au module pur `astra_identite_largeur_source(array $largeurs, int $largeur_affichee): int`, maximum des largeurs positives et du repli d'affichage.
- `identite_responsive.cjs <URL_WP> <URL_SPIP> <sorties>` utilise `WP`, `SPIP_ROOT`, `SPIP_CLI` pour appliquer les variantes ; exit 1 sur une différence non déclarée.

- [ ] **1. Écrire les tests.** `testSloganVisibleSeulementSurMobile` : un seul paragraphe HTML et display none/none/block ; titre tablette seul : un titre ; deux textes masqués : aucun texte ni enveloppe vide. `testLogoGardeLaDefinitionEtLeCadreBureau` : fichier 200 × 100, largeurs 32/96/48, ressource servie au moins 96 px et bureau affiché en 32 ; sans desktop, conserver le repli bureau de 60 px de haut. Sans largeur, ancien résultat HTML inchangé. Source plus petite que 96 : aucun agrandissement du fichier au-delà de l'original, pas d'erreur.
- [ ] **2. Constater le rouge du logo.** Intégration filtrée `IdentiteResponsiveTest` : l'actuelle ressource de 32 px échoue sur sa définition. Les assertions de présence déjà satisfaites restent vertes ; ne pas forcer leur échec.
- [ ] **3. Adapter l'image.** Produire le rendu bureau de référence par l'ancien chemin (desktop positif, sinon réduction à 60 px de haut). Si une largeur plus grande est nécessaire, repartir de l'original, utiliser `image_reduire` et reprendre ses sources dans le rendu de référence en conservant les attributs d'affichage. Ne pas reconstruire une URL de cache à la main. Préserver classes/alt et éventuelles données d’image. Vérifier la taille après chargement : les attributs width/height ne prouvent pas à eux seuls le cadre CSS. Utiliser aussi `-logo-ratio` pour relever les arrondis contre WP, sans correctif arbitraire de hauteur. Les fichiers Placehold sont ceux préparés en tâche 1 ; aucune licence non établie ne leur est attribuée.
- [ ] **4. Aligner présence et display.** Employer la même règle de booléen effectif en PHP et en CSS. Ne changer le squelette que si les tests l'exigent ; garder ses blancs et classes dans le cas historique.
- [ ] **5. Vérifier.** Intégration filtrée puis complète ; restaurer styles, slogan et logo dans `tearDown()`. Pilote navigateur sur les trois largeurs, avec/sans JS, puis 922/921 et 545/544 ; cas transparent : tailles et couleurs égales à WP.
- [ ] **6. Committer.** `Identité : préserver les textes visibles et la définition du logo`.

### Tâche 4 — Formulaire des trois appareils sans perte

**Files:** registre/saisie, formulaire et deux langues, `StylesSaisieTest.php`, `StylesFormulaireTest.php`, nouvel e2e `formulaire_identite_responsive.cjs`.

**Interfaces:** garder `astra_styles_champs($styles, string $section): array`, `astra_styles_saisie_fusionner(array $existant, array $saisie): array`, et CVT. Les cinq définitions ont `appareils => true`. Lignes `appareils` : `appareil`, `nom`, `valeur`, `nom_unite`, `unite` ; faux converti en chaîne `"0"`, unité absente représentée par `""` dans le formulaire.

- [ ] **1. Tests rouges.** `testCinqChampsIdentiteSurTroisAppareils` exige 15 valeurs et six unités ; les couleurs de la section restent des champs simples. `testPremiereSaisieIdentiteMobileSeule` garde le mobile seul et ignore les unités seules. `testAllerRetourIdentiteParAppareil` poste le DOM réellement rendu : mêmes valeurs/CSS et empreintes, faux conservés sous leur type antérieur, unité absente toujours absente. Couvrir aussi des nombres importés comme nombres, pas seulement des chaînes. Effacement d'une taille mobile : tablette conservée ; effacement d'une visibilité : défaut propre mobile. Clés non postées gardées.

```php
$avant = ['font-size-site-title' => ['desktop' => '20', 'tablet' => '18', 'mobile' => '16', 'desktop-unit' => 'px']];
$apres = astra_styles_saisie_fusionner($avant, ['font-size-site-title' => ['mobile' => '', 'mobile-unit' => '']]);
$this->assertSame('18', $apres['font-size-site-title']['tablet']);
$this->assertSame('', $apres['font-size-site-title']['mobile']);
$this->assertArrayNotHasKey('tablet-unit', $apres['font-size-site-title']);
$this->assertArrayNotHasKey('mobile-unit', $apres['font-size-site-title']);
```

- [ ] **2. Constater le rouge.** Filtres unitaires `StylesSaisieTest`, intégration `StylesFormulaireTest` : champs/appareils mobiles manquants. Les échecs de migration ou de marges ne sont pas des attendus autorisés.
- [ ] **3. Implémenter.** Rendre nombre/taille/booléen par appareil pour ces définitions. Noms `s[option][appareil]`, id bureau actuel `s_option`, suffixes `_tablet`/`_mobile` et labels correspondants. Pas de fieldsets supplémentaires qui modifieraient les 16 sections. Tailles : choix « px implicites » de valeur vide ; la fusion supprime la clé d'unité vide postée pour ces seules options, sans perdre leur valeur. Unité inconnue non vide : erreur CVT, aucune écriture. Lorsqu’une feuille postée est sémantiquement identique à la valeur antérieure (booléen/nombre importé), garder son type et son octet de représentation pour préserver l’empreinte du seed ; une modification réelle reste stockée selon la normalisation. Les champs absents de la saisie restent intacts. Garder la sémantique « Ne pas appliquer » des marges. Traduire les explications des deux sortes de repli en français et anglais.
- [ ] **4. Vérifier en navigateur.** Le nouvel e2e utilise la connexion de `formulaire_styles.cjs`, saisit les cinq options à trois appareils, navigue/recharge, teste une unité refusée sans écriture (option `pt` injectée dans le select uniquement par le pilote pour simuler un POST forgé), puis le rendu public. Restaurer l'état initial en fin de test. `URL="$URL_SPIP" NODE_PATH="$PW/node_modules" node tests/e2e/formulaire_identite_responsive.cjs`, puis e2e historique du formulaire et deux suites complètes : tous verts.
- [ ] **5. Committer.** `Formulaire : éditer l’identité sur les trois appareils`.

### Tâche 5 — Qualification et documentation

**Files:** trois README/crédits du tableau ; `docs/inventaires/identite-responsive/{bilan.md,manifest.json,preuves/}` ; seuls les anciens attendus avec delta justifié sont ajustés.

**Interfaces:** références R29, pilotes des tâches 1/3 et comparateurs existants ; bilan lié au SHA testé avec SHA-256 des preuves. Pas de bump de paquet dans cette tâche.

- [ ] **1. Exécuter sur le code final.** Deux suites successives, Python, capture des oracles, e2e public et formulaire, PHP 7.4 et courant. Même image/polices des deux côtés. Les cas sans desktop ne sont pas « corrigés » en injectant 26/15 dans les données témoins.
- [ ] **2. Exercer la matrice complète.** Construire `HISTORIQUES` (tableau Bash) depuis `resultats.configurations` de `docs/inventaires/astra-4.13.8-qualification/manifest.json` (41 noms), `R29_COMPARABLES` depuis la matrice de tâche 1 en excluant `-masques` (exercé par le pilote dédié), et `ASTRA_PREUVES` hors des répertoires d'attendus. Ne pas supposer que la liste par défaut des scripts contient R25–R27 et P1–P3 ; R28 est séparé.

```bash
WP="$WP" URL="$URL_WP" tests/references/responsive/capturer-identite.sh
WP="$WP" URL="$URL_WP" PW="$PW" tests/references/capturer.sh "${HISTORIQUES[@]}" "${R29_COMPARABLES[@]}"
SPIP_CLI="$SPIP_CLI" php tests/comparaison/comparer_html.php "$SPIP_ROOT" "$URL_SPIP"
SPIP_CLI="$SPIP_CLI" php tests/comparaison/comparer_html.php "$SPIP_ROOT" "$URL_SPIP" "${R29_COMPARABLES[@]}"
WP="$WP" URL_WP="$URL_WP" PW="$PW" SPIP_CLI="$SPIP_CLI" tests/comparaison/comparer_captures.sh "$SPIP_ROOT" "$URL_SPIP" "${HISTORIQUES[@]}" "${R29_COMPARABLES[@]}"
NODE_PATH="$PW/node_modules" node tests/e2e/identite_responsive.cjs "$URL_WP" "$URL_SPIP" "$ASTRA_PREUVES/identite"
NODE_PATH="$PW/node_modules" node tests/comparaison/comparer_blocs.cjs "$URL_WP_BLOCS" "$URL_SPIP_BLOCS" "$ASTRA_PREUVES/blocs"
```

Pour HTML, ajouter également explicitement les références de styles historiques exclues de la liste par défaut ; conserver le nommage connu de R13 et les écarts de contenu R11 séparément, sans nouvel ignore général. Définir `URL_WP_BLOCS` et `URL_SPIP_BLOCS` avec les URL des **pages importées** de leur README ; leur SPIP charge le commit courant du plugin. Garder le contenu importé, ne pas relancer la préparation complète de wp2spip. R28 reste exercé dans ses clones avec/sans oEmbed, selon sa fixture. Si le lot widgets est déjà présent, ajouter ses R30 comparables et son pilote dédié à la qualification, sans en faire une dépendance de ce plan.

- [ ] **3. Vérifier la sensibilité et les limites.** Sur copies temporaires : inverser un booléen mobile, lire tablet pour desktop, transformer une unité inconnue en px, réduire la ressource du logo à desktop seul. Chaque erreur doit être détectée ; restaurer avant les mesures finales. Aucun hash d'asset officiel changé, aucun seuil relevé, aucun contenu changé pour passer un test.
- [ ] **4. Documenter et committer.** Bilan : compteurs réels, codes, SHA testé, versions, différences et neutralisations ; fichiers de preuves indexés avec empreintes. Annoncer l'activation des valeurs responsives stockées et les limites des logos distincts/SVG. Créditer les règles et la provenance Placehold des fixtures locales ; ne pas distribuer les PNG ni les présenter comme CC0. Commit : `Preuves et documentation de l’identité responsive`.

## Autorelecture et suite

Couverture : unités/zéro/données → 1–2 ; CSS et replis → 1–2/5 ; HTML/image → 3 ; saisie → 4 ; seuils/JS/transparence/historiques → 3/5 ; PHP/provenance → 1/5. Les interfaces du lot ne sont pas requises par le plan des widgets.

Faire relire ce plan avant le code. Proposition : exécution native, tâche par tâche, puis revue indépendante de la branche ; elle évite de transmettre plusieurs fois le contexte des interfaces liées. Après qualification des deux lots, décider de la version et du lot consommateur Garden, puis de la publication.

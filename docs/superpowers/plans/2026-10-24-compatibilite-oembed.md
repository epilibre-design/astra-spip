# Compatibilité oEmbed — plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Vérifier l'import embed et son affichage avec/sans le plugin SPIP, en corrigeant uniquement l'habillage Astra nécessaire.

**Architecture:** Deux installations SPIP de test distinctes exercent le même contenu importé, l'une sans plugin et l'autre avec oEmbed 3.5.1 épinglé. Un fournisseur HTTP local retourne des données et iframes de test ; les vrais pipelines du plugin exécutent la résolution. WordPress 4.13.8 présente un média de même géométrie pour la comparaison.

**Tech Stack:** SPIP 4.4, plugin oEmbed 3.5.1, wp2spip épinglé, WordPress 7.1.3/Astra4.13.8, PHPUnit intégration, Playwright, serveur de fixtures PHP.

**Spec:** [Compatibilité oEmbed](../specs/2026-10-24-compatibilite-oembed-design.md).

## Global Constraints

- Préconditions : lots A/B et CSS Tasks 1/2 du [plan directeur](2026-10-24-migration-astra-4.13.8.md).
- `<utilise nom="oembed" />` est déjà présent ; pas de dépendance obligatoire, installation automatique ou copie du plugin dans Astra.
- Sources plugin : commit `e90f6a6db27f763ad78ad36c0398b39aeff4cf8c`, version 3.5.1 ; compatibilité déclarée SPIP `[4.1.0;4.*]`.
- Aucun forçage d'activation sous SPIP 5 ; le cas sans plugin reste vérifié sur cet environnement s'il est disponible.
- Aucun téléchargement de média tiers, aucune disponibilité nécessaire de YouTube/Vimeo ; pas de test complet de préparation wp2spip.

## Review Focus

1. Balise optionnelle compilée sans plugin : URL toujours utilisable et aucune erreur (Task 1).
2. Traitement exécuté deux fois : un seul conteneur responsive (Task 2).
3. Ratio autre que 16:9 :4:3 mesuré, pas un padding universel (Task 2).
4. Fournisseur indisponible ou invalide : URL/texte conservés, pas d'exception Astra (Task 2).
5. Activation/désactivation réécrivant le texte : aucune mutation éditoriale (Task 2).

## Task 1: Fixtures locales et chemin sans oEmbed

**Files:**
- Create: `tests/fixtures/oembed/router.php`, `tests/fixtures/oembed/responses/{video-16-9,video-4-3,invalid}.json`, `tests/integration/OembedRenduTest.php`, `tests/e2e/oembed.cjs`.
- Create: `tests/fixtures/oembed/bloc-wordpress.html` (contenu rédigé pour le test), `tests/fixtures/oembed/options.php` (options de test seulement).
- Modify: documentation de préparation de l'environnement, pas le script d'installation automatique du plugin produit.

**Interfaces:**
- Consumes: `wp2spip_bloc_embed()` de l'importeur épinglé, qui produit l'URL isolée et la légende ; vrai contenu exporté/importé dans l'environnement ciblé, pas un attendu éditorial inventé.
- Produces: serveur de fixture sur un port libre proposé 8428 ; `/oembed?url=...` répond en JSON avec type video, width 640/height360 ou width 640/height480, html iframe vers `/player/...`, sans média externe ; endpoint en erreur et JSON invalide identifiables.
- Produces: CLI `node tests/e2e/oembed.cjs <URL_ARTICLE> <PROFIL> <SORTIES>`, profils `sans-plugin`, `avec-plugin`, `erreurs`, `deja-enveloppe` ; code 0 si assertions/pageerrors absentes, données JSON et captures.

- [ ] **Step 1: Écrire le cas sans plugin**

OembedRenduTest rend un article avec l'URL isolée issue du bloc de fixture et sa légende. Exiger URL/lien utilisable, légende conservée, aucun appel de balise indisponible. Relire le texte enregistré avant/après. Le pilote Chromium `sans-plugin` vérifie la présence du lien et l'absence de débordement, plus un article témoin sans embed.

- [ ] **Step 2: Exécuter la caractérisation**

Run sur le SPIP privé **sans** oEmbed : `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter OembedRenduTest`. Attendu : ce chemin peut déjà passer ; le consigner. Ne pas ajouter une modification produit pour rendre rouge un comportement déjà conforme. Une anomalie réelle du lien ou de sa légende nécessite alors un test rouge ciblé avant correction.

- [ ] **Step 3: Préparer la seconde installation et le fournisseur**

Installer le plugin épinglé uniquement dans le second site privé. Dans `tests/fixtures/oembed/options.php`, enregistrer une entrée de `$GLOBALS['oembed_providers']` correspondant aux URL locales de fixture vers l'endpoint 8428, puis activer ce fichier par les options du site **de test**. Vérifier `test_plugin_actif('oembed')`, version 3.5.1 et l'appel réel du fournisseur dans son journal. Le routeur sert les iframes statiques de test et leurs dimensions, sans requête à un service public. Chaque cas a une URL distincte pour éviter les caches du plugin ; aucune dépendance au cache d'un essai précédent.

- [ ] **Step 4: Valider et committer les fixtures**

Run: `php -l` du routeur/options, profil sans-plugin et test précédent. Vérifier que le second site peut interroger le fournisseur et que le journal expose seulement les requêtes de test, sans identifiants. Message : `Préparer les références oEmbed locales et le repli sans plugin`.

## Task 2: Vrai rendu du plugin, ratios et corrections minimales

**Files:**
- Modify: `tests/integration/OembedRenduTest.php`, `tests/e2e/oembed.cjs`, `README.md`.
- Create: `tests/references/wp/R28-oembed.php`, `tests/references/wp/R28-oembed.json`, configuration SPIP correspondante ou fixture d'import dédiée documentée dans `tests/references/blocs/README.md`.
- Modify only if failing evidence requires it: `css/astra-spip.css`, éventuellement une inclusion ciblée ; ni fork des modèles du plugin ni wrapper générique de toutes les iframes.

**Interfaces:**
- Consumes: les deux sites et les fixtures de Task 1 ; oEmbed reçoit l'URL importée via son vrai pipeline de traitement.
- Produces: proportions et repli validés, sans nouvelle API de rendu ni nouvelle donnée Astra.

- [ ] **Step 1: Écrire les assertions puis mesurer avant correction**

Dans le site avec plugin, `testTraitementUniqueAvecOembed` vérifie une occurrence du conteneur, présence de l'iframe ou du bouton de lecture prévu par le plugin, légende et texte enregistré inchangés. Activer la lecture contrôlée si le plugin la diffère. Les profils avec-plugin/deja-enveloppe mesurent le conteneur vidéo `.rwd-video-container` et son iframe après lecture (pas la figure incluant la légende), à 1280/768/390 px : largeur conteneur ≤ largeur de contenu +1 px et hauteur/largeur proche du ratio 360/640 puis 480/640 (tolérance géométrique 1 px). Pas de ratio appliqué deux fois.

R28 configure côté WordPress le même fournisseur local et des données de même géométrie dans un mu-plugin de test. Si le client HTTP sûr refuse une URL privée, le mu-plugin autorise uniquement l’hôte/port de ce fournisseur de fixture pour ce site isolé ; aucune règle de réseau de production n’est modifiée. Il est retiré en sortie de la fixture. Le test compare dimensions/styles utiles, pas les pixels d'un lecteur externe ni l'identité des wrappers HTML entre WordPress et SPIP.

- [ ] **Step 2: Exécuter le rouge ou conclure à l'absence de correction nécessaire**

Run: profil avec-plugin sur le rendu actuel sous 4.13.8 et profil deja-enveloppe sur la forme d'HTML que l'import conserve réellement. Si tout passe, l'implémentation produit se limite à documentation et déclaration facultative existante. Sinon, conserver les mesures en échec et la règle de cascade responsable avant de modifier le CSS.

- [ ] **Step 3: Corriger seulement si nécessaire et vérifier les échecs fournisseur**

Porter une règle d'habillage limitée au contenu Astra et aux classes oEmbed. Préserver le conteneur et les styles de ratio du plugin ; ne pas ajouter `.ast-oembed-container` autour. Tester fournisseur inconnu, erreur HTTP et JSON invalide sur des URL distinctes : repli utile du plugin, URL/texte toujours conservés et aucune exception du thème. Activer puis désactiver le plugin dans le site de test et vider les caches de compilation : texte SQL identique et retour au lien sans réimport.

- [ ] **Step 4: Validation du lot et commit**

Run: OembedRenduTest dans chacun des deux sites, les quatre profils et article témoin. Sous SPIP 5 disponible, exécuter seulement sans-plugin ; sinon consigner non exécuté sans prétendre activer 3.5.1. Renseigner les décisions de qualification 4.11.13-04/4.11.18-04 avec les mesures. Message : `Qualifier la compatibilité oEmbed avec Astra 4.13.8`.

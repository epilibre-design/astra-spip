# Icônes et liens sociaux 4.13.8 — plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Actualiser les SVG des réseaux existants et éviter les préfixes e-mail répétés sans modifier les données du builder.

**Architecture:** Le catalogue fermé est comparé au svgs.json officiel. Une petite fonction pure prépare l'URL au rendu ; les données persistées et les empreintes Garden restent identiques. Un cas de rendu multi-emplacements vérifie les ID dans tout le DOM.

**Tech Stack:** PHP 7.4/8.4, PHPUnit, SPIP 4.4, DOMXPath, Chromium.

**Spec:** [Icônes et liens sociaux](../specs/2026-10-24-icones-liens-sociaux-astra-design.md).

## Global Constraints

- Dépend des Tasks 1/2 du [plan directeur](2026-10-24-migration-astra-4.13.8.md) ; mêmes sources, chemins et contraintes.
- Aucun réseau ajouté : TripAdvisor, téléphone et WhatsApp ne deviennent pas des options du port.
- Pas de SVG utilisateur ni réécriture des valeurs enregistrées ; les attributs restent échappés.
- Ajouter les fichiers individuellement ; pas de modification du bundle minifié.

## Review Focus

1. `MAILTO:` ou `MaIlTo:` déjà présents : un seul préfixe (Task 1).
2. Même SVG rendu dans les parties desktop/mobile/pied masquées ou visibles : ID uniques globalement (Task 2).
3. Nom accessible perdu en supprimant un title/id : nom du lien conservé (Task 2).
4. Nouveau catalogue ou tracé copié sans crédit : comparaison entrée par entrée et notices (Task 1).
5. Enregistrement du formulaire changeant une valeur intacte : données et empreintes conservées (Task 2).

## Task 1: Catalogue fermé et préparation pure de l'e-mail

**Files:**
- Modify: `inc/astra_icones.php`, `astra_fonctions.php:205–219`, `CREDITS.md`.
- Create: `tests/unit/IconesTest.php`.

**Interfaces:**
- Consumes: `astra_icones_sociales(): array`, catalogue 4.13.8 et registre `astra_hfb_reseaux()`.
- Produces: `astra_lien_social(string $reseau, string $url): string`, dans `inc/astra_icones.php`, appelée uniquement lors de la construction de la sortie d'`astra_hfb_icones()`.

- [ ] **Step 1: Écrire les tests**

`IconesTest::testPrefixeMailInsensibleALaCasse` utilise les assertions suivantes ; `testCatalogueSansIdStatique` analyse chaque SVG du catalogue avec DOMDocument et exige zéro attribut id statique, des SVG valides et aucun ajout de clé `trip_advisor`.

```php
$this->assertSame('mailto:nom@example.test', astra_lien_social('email', 'nom@example.test'));
$this->assertSame('MAILTO:nom@example.test', astra_lien_social('email', 'MAILTO:nom@example.test'));
$this->assertSame('MaIlTo:nom@example.test', astra_lien_social('email', 'MaIlTo:nom@example.test'));
$this->assertSame('', astra_lien_social('email', ''));
$this->assertSame('https://example.test/', astra_lien_social('github', 'https://example.test/'));
```

- [ ] **Step 2: Constater l'échec**

Run: `vendor/bin/phpunit --testsuite unit --filter IconesTest`. Attendu : fonction absente et ID email/google_reviews présents ; ne pas qualifier TripAdvisor de défaut du port.

- [ ] **Step 3: Implémenter**

Reconnaître le préfixe par `stripos(..., 'mailto:') === 0`, conserver l'entrée lorsque le préfixe existe, sinon ajouter mailto à une adresse e-mail non vide. Aucun traitement pour les autres réseaux. Comparer les SVG des clés existantes au catalogue cible, retirer les ID sans consommateurs, conserver/réécrire ensemble toute éventuelle référence interne nécessaire. Documenter les trois suppressions d'ID amont (dont TripAdvisor absent du registre) et les licences des tracés réellement modifiés.

- [ ] **Step 4: Vérifier**

Run: test précédent, `vendor/bin/phpunit --testsuite unit --filter HfbSaisieTest`, puis `php7.4 -l inc/astra_icones.php` et `php7.4 -l astra_fonctions.php`. Attendu : vert, autres valeurs/couleurs et signatures publiques conservées.

- [ ] **Step 5: Commit**

Message : `Actualiser les SVG sociaux et reconnaître les préfixes e-mail`.

## Task 2: Rendu répété, noms accessibles et données intactes

**Files:**
- Create: `tests/integration/SocialRenduTest.php`, `tests/references/wp/R27-sociaux-ids.php`, `tests/references/wp/R27-sociaux-ids.json`, `tests/e2e/sociaux.cjs`.
- Modify: `tests/comparaison/configurations.php`, `tests/integration/HfbFormulaireTest.php` si un aller-retour social spécifique manque.
- Modify only if needed: `inclure/hfb/social-icons.html`, fichiers de langue existants.

**Interfaces:**
- Consumes: sortie `astra_hfb_icones()` de Task 1 ; une configuration avec email et Google Reviews en en-tête desktop/mobile et pied.
- Produces: référence R27 et CLI `node tests/e2e/sociaux.cjs <URL> <SORTIES>`, code 0 si tous les contrôles passent, rapport JSON et captures du contrôle social.

- [ ] **Step 1: Écrire les assertions de rendu**

Dans `testIdentifiantsUniquesMultiAppareils`, rendre la page complète après avoir posé les réseaux dans les trois contextes. Tous les ID du document ont une occurrence ; aucun `title#email`/`title#google_reviews` ; liens e-mail présents avec le bon href. Pour chaque lien social, le nom accessible est non vide et correspond au réseau, en FR puis EN. Une référence ARIA présente résout exactement un élément.

Le test d'aller-retour compare le tableau hfb avant/après enregistrement et le résultat d'une relance seed ciblée lorsqu'elle est disponible dans le site Garden de qualification ; une casse de schéma ne doit pas être modifiée dans le stockage.

- [ ] **Step 2: Constater puis qualifier l'état initial**

Run: `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter SocialRenduTest`. Après Task 1, le cas d'ID peut déjà passer : consigner cela, sans inventer un nouvel échec. Toute assertion de nom ou donnée en échec doit reproduire un défaut réel avant sa correction. La fixture ajoute la couverture de rendu à la preuve rouge de Task 1.

- [ ] **Step 3: Vérifier la comparaison cible**

R27 reprend le placement/configuration dans WordPress et SPIP. `sociaux.cjs` vérifie ID et noms dans tout le DOM, puis tailles/couleurs/remplissages et survol à 1280/768/390 px ; aucun test n'ignore un SVG parce qu'il est masqué. Comparer la liste des propriétés avec les données du WordPress cible et documenter tout changement de tracé. Une correction de gabarit n'est apportée que si ces tests l'exigent.

- [ ] **Step 4: Validation du lot**

Run: test d'intégration précédent ; appliquer R27 avec les scripts existants puis `NODE_PATH="$PW/node_modules" node tests/e2e/sociaux.cjs "$URL_SPIP" "$RUN_DIR/sociaux"` ; rejouer R10, R15 et R11 contre WP_REFERENCE. Attendu : aucune collision, mêmes noms/réseaux, formes/couleurs expliquées, données intactes.

- [ ] **Step 5: Commit**

Message : `Prouver le rendu social multi-emplacements sur Astra 4.13.8`.

# Gravatar facultatif des commentaires — plan d’implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** utiliser l’avatar local du plugin Gravatar actif et conserver strictement l’initiale sinon.

**Architecture:** un filtre Astra permanent garde le squelette indépendant du plugin optionnel. Il valide l’adresse puis délègue récupération et cache à Gravatar ; seuls les attributs de présentation de son image sont adaptés au thème.

**Tech Stack:** SPIP 4.4, PHP 7.4/8.4, Gravatar 3.0.0, PHPUnit et Playwright.

**Spec:** `docs/superpowers/specs/2026-10-24-gravatar-commentaires-design.md`.

## Global Constraints

- Autorisation d’exécution donnée par Gilles dans la conversation ; aucune nouvelle attente de validation du plan n’est requise.
- Référence courante Astra 4.13.8 / WordPress 7.1.3 ; les citations 4.11.0 de la spec sont historiques.
- Gravatar 3.0.0, commit `dba78603edcb8c48fa55230f15b4dbecb97b8993`, uniquement sous SPIP 4.4 ; aucune activation sous SPIP 5.
- Aucun accès réseau automatique dans les tests : cache frais réel, image locale originale de test et marqueur `.vide`.
- Pas de recherche d’auteur ni de changement du stockage des commentaires ; adresse du message exclusivement.
- Aucun bump de version ni tag ; commits sans trailers, indexation fichier par fichier ; PR distincte.

## Review Focus

- Fonctions Gravatar déjà chargées mais plugin désactivé : aucun appel fournisseur (tâche 1).
- Adresse vide, invalide ou non scalaire : repli sans téléchargement (tâche 1).
- Image doublée avec largeur inline : dimension responsive du thème conservée (tâches 1 et 3).
- Absence de fichier de plugin lors de la compilation : initiale strictement identique, accent et nom vide compris (tâche 2).
- Réponse et page unique : un seul avatar décoratif, aucun email dans le HTML (tâches 2 et 3).

### Task 1: filtre de délégation facultative

**Files:** créer `inc/astra_gravatar.php`, `tests/unit/GravatarTest.php` ; modifier `astra_fonctions.php` et `paquet.xml`.

**Interfaces:** `astra_commentaire_gravatar($email): string`, filtre exporté via les fonctions du plugin, renvoie une image ou `''`. Garde `test_plugin_actif('gravatar')` avant `include_spip('gravatar_fonctions')`, puis `function_exists` sur les deux fonctions. Valider l’email avec l’outil SPIP avant toute récupération. Appeler `gravatar($email, '404')`, puis `gravatar_balise_imgx2($image, 50, '', 'avatar avatar-50 photo')`. Garder uniquement la source locale retournée. Adapter avec `inserer_attribut` : width/height nominaux 50, suppression du style inline, alt vide, aria-hidden true.

- [ ] Écrire les tests isolés de chaque état de décision, avec compteur des appels : inactif malgré fonctions présentes, absence d’API en processus séparé, adresse invalide/vide/non scalaire, image disponible, échec ; image locale avec attributs doublés.
- [ ] Exécuter `vendor/bin/phpunit --testsuite unit --filter GravatarTest` : rouge faute de filtre.
- [ ] Implémenter le filtre et son chargement permanent ; déclarer `<utilise nom="gravatar" compatibilite="[3.0.0;3.*]" />` sans installer le plugin.
- [ ] Même filtre puis suite unitaire complète : verts ; `php7.4 -l inc/astra_gravatar.php` vert.
- [ ] Commit `Commentaires : déléguer les avatars au plugin Gravatar facultatif`.

### Task 2: squelette et vrai plugin

**Files:** modifier `inclure/commentaire.html`, commentaire CSS ; créer `tests/integration/GravatarRenduTest.php`, fixtures privées et procédure `tests/fixtures/gravatar/README.md`.

**Interfaces:** le squelette calcule l’image dans une variable puis choisit image ou span initial existant. Aucun `#GRAVATAR`. Cache réel préparé avec sel et noms calculés par la procédure amont, jpeg récent pour succès, `.vide` pour absence. Activation/désactivation par SPIP, fonctions déjà chargées sans effet quand inactif.

- [ ] Tests rouges : article/commentaire/réponse/page unique ; alt vide et aria-hidden, classe exacte, URL locale sans adresse ; fallback accents/nom vide, HTML antérieur exact ; compilation sans les fichiers du plugin.
- [ ] Exécuter les tests et constater que l’image attendue manque.
- [ ] Modifier seulement le choix d’avatar dans le squelette ; décrire l’initiale comme repli dans le CSS.
- [ ] Exécuter avec vrai plugin actif puis absent, cache compilé vidé entre états ; suites unitaires et intégration vertes, aucune requête externe.
- [ ] Commit `Commentaires : afficher Gravatar avec repli sur l’initiale`.

### Task 3: dimensions, preuves et crédits

**Files:** tests navigateur Gravatar, `docs/inventaires/gravatar-commentaires/`, README et CREDITS.

**Interfaces:** fixture JPEG de test originale produite localement, même image côté WP/SPIP ; sonde avatar et position relative du nom à 1280/768/390. La règle existante du thème gouverne les dimensions ; pas de modification générale des commentaires pour rapprocher le texte WP/SPIP.

- [ ] Capturer WordPress 4.13.8 avec avatar local ; comparer dimensions, forme, alignement et espacement. Vérifier absence de téléchargement lors de l’essai par journal d’un serveur local de contrôle.
- [ ] Exécuter le navigateur avec plugin actif puis désactivé et sur page unique ; les références historiques de commentaires et les suites restent vertes.
- [ ] Si une adaptation CSS est nécessaire, écrire un test de dimension rouge avant de la produire ; ne pas changer la taille du repli.
- [ ] Écrire manifeste SHA testé, versions, source/licence fixture et logs ; citer auteurs et licence du plugin sans copier ses sources. Garder les preuves dans cette PR pour revue atomique, puis branche preuves si demandée.
- [ ] Commit `Preuves et crédits de Gravatar dans les commentaires`.

## Autorelecture

Les six critères de la spec sont couverts par les tâches 1–3. Les références générales R0–R30 déjà qualifiées par le lot responsive restent distinctes : cette branche part de main et ne dépend pas de ce lot. Faire une revue indépendante finale, corriger les défauts importants avec tests rouges/verts, pousser et ouvrir une PR prête pour relecture ; ne pas fusionner.

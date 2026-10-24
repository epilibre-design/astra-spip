# CSS et responsive 4.13.8 — plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Régénérer une cascade 4.13.8 cohérente et intégrer ses seuils et son script précoce, sans activer les options responsives hors périmètre.

**Architecture:** Les sélecteurs fonctionnels sont préservés par les outils de preuve, puis les sorties indépendantes WordPress alimentent les CSS figés et attendus. Les générateurs transposés sont ajustés à la même source. Un module SPIP dédié centralise le seuil d'en-tête utilisé au début et en pied de page.

**Tech Stack:** Python 3, PHP 7.4/8.4, PHPUnit, Playwright Chromium, WordPress 7.1.3/Astra4.13.8.

**Spec:** [CSS et responsive](../specs/2026-10-24-css-responsive-astra-design.md).

## Global Constraints

- Utiliser les sources et environnements du [plan directeur](2026-10-24-migration-astra-4.13.8.md), après ses Tasks 1/2.
- Les tableaux responsives conservés ne sont pas tous activés ; respecter le sous-ensemble actuel et ses limites annoncées.
- `astra_header_break_point()` et `astra_get_tablet_breakpoint()` sont des sources distinctes ; valeurs nominales SPIP 921 px, pas de nouveau réglage utilisateur.
- CSS extrait régénéré, jamais corrigé seulement à la main ; main.min.css officiel intact.
- Gate Task 3 : choix utilisateur du repli sans JavaScript validé et reporté dans la spec avant toute implémentation de ce lot responsive. La rédaction du plan ne clôt pas ce choix.

## Review Focus

1. Virgule dans :where() coupée comme une liste de sélecteurs : extraction/cascade fausses (Task 1).
2. Repli CSS inopérant avec variable primaire indéfinie : champ date et bordures contrôlés (Task 2).
3. CSS de titres nouveau avec spécificité ancienne dans un autre fichier : cascade complète et styles calculés (Task 2).
4. Seuil d'en-tête pris pour celui de tablette ou premier affichage mis en cache : deux contextes, cache HTML identique et décision navigateur (Task 3).
5. Élément vide/RTL/bloc wide/full moins courant : tests de géométrie et conservation des données (Task 4).

## Task 1: Outils de preuve compatibles avec les sélecteurs 4.13.8

**Files:**
- Create: `tests/references/css_selecteurs.py`, `tests/references/test_selecteurs_css.py`, `tests/unit/SelecteursCssTest.php`.
- Modify: `tests/references/extraire_css.py`, `tests/references/styles/graisses.py`, `tests/unit/StylesAttendusTest.php`, `tests/unit/StylesTitresTest.php`.

**Interfaces:**
- Produces: `decouper_selecteurs(texte: str) -> list[str]` dans css_selecteurs.py ; découpage des virgules de niveau zéro, en respectant parenthèses, crochets, guillemets et échappements.
- Produces: `StylesAttendusTest::selecteurs(string $texte): array` avec le même contrat pour les assertions PHP ; pas une nouvelle API de production.
- Consumes: la cascade de `StylesTitresTest::graisses()` et le tableau de sélecteurs/spécificités existant.

- [ ] **Step 1: Écrire les tests de découpage et de spécificité**

Les deux langages vérifient :

```text
.a:where(.b,.c), .d             => [".a:where(.b,.c)", ".d"]
a[href=","], .d                => ["a[href=\",\"]", ".d"]
.entry-content :where(h1,h2)   => un seul sélecteur
```

Dans `StylesTitresTest::testSpecificiteWhereNulle`, la cascade d'un h2 de contenu avec `.entry-content :where(h2){font-weight:600}` puis `.wp-block-heading{font-weight:400}` retient 400 (spécificités égales, seconde règle) pour l’élément cible `.entry-content h2.wp-block-heading` ajouté à la matrice ; l'ancien `.entry-content h2` aurait retenu 600. Compléter la matrice de titres pour les formes réellement émises par 4.13.8 et ne pas attribuer de spécificité au contenu de :where(). `graisses.py` doit garder une règle visant uniquement `.entry-content :where(h1)` avec font-weight, sans exiger un h1 global en début de liste.

- [ ] **Step 2: Constater les échecs**

Run: `python3 -m unittest discover -s tests/references -p 'test_selecteurs_css.py'` et `vendor/bin/phpunit --testsuite unit --filter SelecteursCssTest`. Exécuter aussi le filtre `StylesTitresTest::testSpecificiteWhereNulle`. Attendu : fonctions absentes/ancien découpage naïf, résultat de spécificité erroné ; aucune recapture nécessaire pour cette étape.

- [ ] **Step 3: Adapter les outils**

Utiliser un parcours de caractères limité au découpage CSS nécessaire, pas un moteur CSS complet. Faire utiliser le découpage à `extraire_css.selecteurs()` et au helper PHP ; adapter la détection des titres de graisses.py. Conserver ordre, contextes media et normalisation des sélecteurs. Garder la matrice de cascade explicite pour les titres du thème et tester les cas particuliers :where rencontrés, plutôt qu'annoncer un calcul universel de CSS.

- [ ] **Step 4: Vérifier**

Run: commandes précédentes et `python3 -m unittest discover -s tests/references -p 'test_extraire_css_blocs.py'`. Attendu : vert ; les règles connues de 4.11.0 restent lisibles et les nouvelles ne sont pas perdues. Vérifier un témoin au navigateur avec les mêmes deux règles de poids pour confirmer 400.

- [ ] **Step 5: Commit**

Message : `Préserver les sélecteurs fonctionnels dans les preuves CSS`.

## Task 2: CSS figé et générateurs transposés alignés

**Files:**
- Modify: `css/astra-dynamic.css`, `css/astra-hfb.css`, `css/astra-pages.css`, `css/astra-article.css`, `css/astra-commentaires.css` si sa source change, `css/compat/*.css`, `inc/styles/{titres,menu,pied}.php`, `inc/astra_entete_transparent.php` pour les sélecteurs non responsives concernés.
- Modify: `tests/references/styles/attendus/*.css`, `tests/references/transparent/attendus/*.css`, `tests/references/extraire_css.py`, `tests/unit/StylesTest.php`, `tests/unit/StylesTitresTest.php`, `tests/unit/StylesAttendusTest.php`.
- Create: `tests/references/extraire_css_base.py`, `tests/references/base/substitutions.json`, `tests/references/test_extraire_css_base.py`, `tests/e2e/css_reference.cjs`, `tests/references/wp/R26-css-cascade.php`, `R26-css-cascade.json` ; ajouter R26 à `configurations.php`.

**Interfaces:**
- Consumes: fonctions de preuve de Task 1 et captures cible gardées ; conserver `astra_styles_css(array $styles, array $hfb, array $compatibilite = []): string` et les signatures des modules.
- Produces: CLI `node tests/e2e/css_reference.cjs <URL> <PROFIL> <SORTIES>`, profils `cascade`, `replis`, `responsive`, `blocs`, avec code non nul pour assertion/pageerror et rapport de mesures.

- [ ] **Step 1: Capturer d'abord les attendus indépendants et observer le rouge**

Capturer les 35 références cible dans un dossier privé ; puis groupes styles/transparence avec leurs scripts. Lancer StylesAttendusTest et StylesTitresTest contre ces nouveaux attendus et le port encore transposé de 4.11.0. Consigner les écarts de sélecteurs, spécificités et règles, sans supposer un nombre d'échecs.

Le profil replis reprend la sonde versionnée : input[type=date], première variable initial/indéfinie et repli #123456 ; attendre rgb(18,52,86), puis palette complète inchangée. Le profil cascade exige code à 24 px dans un texte 24 px et la priorité des styles de blocs du WordPress cible. Les tests de menus couvrent les anciens sélecteurs dans les deux CSS et le module menu, avec fixture technique 3 niveaux distincte du builder 2 niveaux.

- [ ] **Step 2: Régénérer les couches figées**

Le CSS de base n'a pas encore d'extracteur autonome : créer `extraire_css_base.py <R0_DIR> <SUBSTITUTIONS_JSON>` qui lit dynamique.css et écrit le CSS de base. Avant son corps, écrire test_extraire_css_base.py : les déclarations non répertoriées restent identiques ; une substitution désigne un sélecteur/propriété précis, ne remplace pas par valeur globale un même hexadécimal utilisé ailleurs ; les neuf variables de palette sont fournies par head-vars, sans double définition contradictoire. `substitutions.json` liste les adaptations existantes réellement nécessaires (palette, familles de police, largeurs) avec source/propriété et justification. Une capture contrôlée de palette/largeur permet de vérifier les correspondances douteuses avant de les introduire. Run rouge puis vert : `python3 -m unittest discover -s tests/references -p 'test_extraire_css_base.py'`.

`extraire_css.py` lit R0–R4 ; son mode pages utilise P1/P3 et son mode compat R18. Exécuter dans cet ordre, après capture de ces configurations dans `$RUN_DIR/wp-cible` :

```bash
python3 tests/references/extraire_css_base.py "$RUN_DIR/wp-cible/R0-defaut" tests/references/base/substitutions.json > css/astra-dynamic.css
python3 tests/references/extraire_css.py "$RUN_DIR/wp-cible" > css/astra-hfb.css
python3 tests/references/extraire_css.py --pages "$RUN_DIR/wp-cible" > css/astra-pages.css
python3 tests/references/extraire_css.py --compat sous-menu-fix "$RUN_DIR/wp-cible" > css/compat/sous-menu-fix.css
python3 tests/references/extraire_css.py --compat sous-menu-ancien "$RUN_DIR/wp-cible" > css/compat/sous-menu-ancien.css
```

Les reprises de cascade et la variable de largeur du port restent justifiées. Les en-têtes indiquent 4.13.8. La sélection d'article est actualisée depuis `inc/dynamic-css/single-post.php` : `.entry-content > *` précède le groupe de titres en :where, comme dans 4.13.8 ; comparer marges calculées d'un h2 et d'un bloc suivant. `comments-flex.php` est identique entre les extrêmes : consigner ce contrôle plutôt qu'inventer une modification. Les feuilles natives WordPress 7.1.3 ne sont pas réattribuées à Astra. Vérifier aussi les déclarations de police/largeur de `inclure/head-vars.html` dans la cascade complète ; ne les modifier que si la cible le demande.

- [ ] **Step 3: Adapter les modules**

Reprendre les formes de titres :where et leur ordre dans titres.php, les survols du menu 4.13.8 dans menu.php et les sélecteurs de copyright pour les propriétés déjà supportées dans pied.php. Garder les couleurs, sources et limites propres à la transparence. Aucune option de marge du copyright ni taille du corps n'est ajoutée. Les labels/legend reprennent la palette de la cible ; les replis imbriqués viennent des générateurs correspondants, pas d'un remplacement regex global de l'asset officiel.

- [ ] **Step 4: Validation ciblée**

Run: `vendor/bin/phpunit --testsuite unit --filter 'Styles(Attendus|Titres|Marges|Test)'`, profils cascade/replis après application de R26, R5/R6/R7/R9/R20/R21/R22/R23/R24 avec toutes variantes. Attendu : équivalence des règles/propriétés cibles, formulaires sans réglage toujours sans bloc CSS ajouté. Les défauts amont résiduels atteignables sont documentés et traités selon la spec avant clôture, pas cachés dans un seuil.

- [ ] **Step 5: Commit**

Message : `Aligner la cascade et les générateurs CSS sur Astra 4.13.8`.

## Task 3: Seuil d'en-tête et première peinture

L'étude sur une copie WordPress 7.1.3/Astra 4.13.8 a exécuté R2 en désactivant les scripts du site : zéro lien de menu visible à 768/390 px, deux à 1280 px. Ce constat est limité au mode off-canvas de R2, sans conclure pour tous les modes. [Mesures](../../inventaires/astra-4.11.0-4.13.8/sans-javascript-cible.json) et [sonde reproductible](../../inventaires/astra-4.11.0-4.13.8/verifier_sans_javascript.cjs). Le contenu de la copie et les captures restent hors dépôt ; aucun site partagé n'a été modifié.

**Gate:** La décision de navigation sans JavaScript est encore ouverte dans la spec fusionnée. Avant cette task, faire valider le repli, l'inscrire dans la spec et compléter cette task avec sa structure HTML et ses assertions exactes. Les Tasks 1/2/4 restent planifiables et vérifiables indépendamment ; cette gate n'est pas une autorisation de décider le repli en codant.

**Files:**
- Create: `inc/astra_responsive.php`, `inclure/body-debut.html`, `tests/unit/ResponsiveTest.php`, `tests/integration/ResponsiveRenduTest.php`.
- Modify: `astra_fonctions.php`, `inclure/footer.html`, `404.html`, `article.html`, `auteur.html`, `inclure/sommaire-articles.html`, `mot.html`, `page_unique.html`, `recherche.html`, `rubrique.html`, `inc/astra_entete_transparent.php`, `tests/e2e/css_reference.cjs` et les sources d'extraction des règles de visibilité concernées.

**Interfaces:**
- Produces: `astra_responsive_seuil_entete(): int` renvoie 921 pour le port ; `astra_responsive_script_precoce(int $seuil): string` produit le script amont équivalent avec borne seuil+0.99, sans lecture du seuil tablette.
- Consumes: les gabarits publics et l'objet JS `astra.break_point` existant ; les deux appels utilisent le même seuil d'en-tête, la CSS tablette garde sa source distincte.

- [ ] **Step 1: Écrire les tests**

ResponsiveTest vérifie le script pour un seuil 921 puis 800 : matchMedia 921.99 et 800.99 respectivement, aucune mention de jQuery et ordre ajout/retrait de classes conforme à l'amont. ResponsiveRenduTest compte un seul script précoce entre ouverture body et début masthead dans accueil/articles/pages/archives/recherche/404, et vérifie la même valeur nominale dans l'objet JS en pied. Les règles de transparence desktop/mobile/both sont comparées à leurs propres attendus 4.13.8.

- [ ] **Step 2: Constater l'échec**

Run: filtres ResponsiveTest/ResponsiveRenduTest des suites unit/intégration. Attendu : helper/script absent et anciennes bornes. Les tests ne sont pas rendus verts en changeant toutes les bornes typographiques 921.

- [ ] **Step 3: Implémenter le module et la stratégie validée**

Inclure le module depuis astra_fonctions.php ; appeler body-debut une fois après chaque ouverture body. Brancher le seuil sur les données JS en pied. Le cache garde un HTML indépendant du terminal ; les classes sont choisies par le navigateur avant l'en-tête. Régénérer les seules règles de visibilité qui changent et adapter les bornes du module transparent d'après sa source.

La navigation sans JS validée reçoit son test avec `javaScriptEnabled:false` et une adaptation déclarée lorsque le WordPress de référence ne donne pas de menu utilisable. Aucun masquage global en attendant le JS.

- [ ] **Step 4: Vérifier le profil responsive**

Run: profils responsive et tests précédents, fenêtres 921/922 obligatoires avec/sans scrollbar, puis 1280/768/390 ; bundle de pied retardé par interception réseau et trace de première peinture. Naviguer avec waitUntil=commit puis attendre #masthead avant de libérer le bundle ; attendre load ou DOMContentLoaded rendrait ce test trompeur car le script classique retardé peut les bloquer. Le script précoce doit déjà avoir posé les bonnes classes pendant ce blocage. Le cas~921,95 px est complémentaire si reproductible : rapporter mesure réelle ou absence documentée. Tester cache réutilisé successivement par client desktop/mobile ; aucune classe serveur spécifique au premier client.

- [ ] **Step 5: Commit**

Message : `Synchroniser l'en-tête responsive et son affichage initial avec Astra 4.13.8`.

## Task 4: Blocs importés, dispositions rares et décisions restantes

**Files:**
- Modify if a proved delta requires it: `css/astra-blocs.css`, `css/astra-spip.css`, `tests/references/extraire_css_blocs.py`, `tests/references/blocs/importe.html`, tests de cascade existants.
- Modify: profils blocs de `tests/e2e/css_reference.cjs`, documentation/qualification des entrées V du domaine.

**Interfaces:**
- Consumes: scripts existants `install-wp-blocs.sh`, `install-spip-blocs.sh`, `comparer_blocs.cjs`, `comparer_medias.php`, `cascade_blocs.cjs` ; environnement d'import **distinct**, base MySQL dédiée, Astra cible et mêmes sources wp2spip épinglées.
- Produces: comparaison d'un import réel et décisions sourcées sur colonnes, logo sans menu, RTL, espacements et compatibilités de contenu.

- [ ] **Step 1: Caractériser avant modification**

Sur une copie WordPress 7.1.3/MySQL isolée et un SPIP d'import propre, générer la page de démonstration existante et réaliser l'import ciblé. Ne pas lancer les tests complets de préparation wp2spip. Comparer chaque bloc et les quatre pertes déjà décrites ; toute modification de ces pertes nécessite une preuve, pas une suppression de leur assertion.

Le profil blocs ajoute titres/colonnes normal/wide/full et la règle de priorité rencontrée en 4.13.8. Les fixtures rares vérifient un logo sans menu principal, direction RTL du pied et espacements des pages/archives déjà supportés. Une entrée propre au runtime Spectra/GenerateBlocks absent est classée avec preuve de son absence et du HTML qui reste réellement conservé.

- [ ] **Step 2: Constater les écarts puis corriger les couches responsables**

Les sorties du comparateur donnent les propriétés/pixels qui divergent. Corriger uniquement l'extraction/nativeCSS ou l'adaptation SPIP responsable. Les modifications d'import nécessaires relèvent du dépôt wp2spip et de ses tests ciblés, avec dépendance déclarée ; aucune retouche silencieuse du contenu attendu pour simuler une conversion inexistante.

- [ ] **Step 3: Valider et committer**

Run: commandes d'installation/comparaison du README blocs avec dossiers/ports privés ; `python3 -m unittest discover -s tests/references -p 'test_extraire_css_blocs.py'`, `comparer_medias.php` et cascade_blocs.cjs. Attendu : mêmes fichiers médias, comparaisons expliquées/vertes aux seuils existants, pas de nouvelle perte cachée. Message : `Qualifier les blocs et les dispositions conservés sous Astra 4.13.8`.

# Navigation accessible 4.13.8 — plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adapter le balisage et l'intégration de navigation au bundle 4.13.8 et vérifier les parcours réels clavier/panneau/widgets.

**Architecture:** Le bundle officiel reste intact. Le HTML et le CSS extrait portent les états amont ; une adaptation SPIP séparée ne traite que le retour au déclencheur réellement utilisé si nécessaire. Le même pilote d'interaction exerce WordPress et SPIP, en distinguant les adaptations déclarées.

**Tech Stack:** SPIP 4.4, PHPUnit intégration, Playwright Chromium, JavaScript sans dépendance supplémentaire.

**Spec:** [Navigation accessible](../specs/2026-10-24-navigation-accessible-astra-design.md).

## Global Constraints

- Dépend des lots A/B et de la régénération CSS du plan directeur ; pas du futur code d'oEmbed ou du catalogue social.
- Deux niveaux de menu ; aucun nouveau réglage de contenu ou mode desktop cible-lien.
- Tester les branches pages/rubriques et les contextes menu 1/menu2/widget.
- Les références restent Astra 4.13.8 ; une adaptation SPIP au focus est nommée, pas attribuée à un correctif amont imaginaire.

## Review Focus

1. Panneau fermé parcourable au clavier malgré l'opacité : visibilité et ordre de focus (Task 1).
2. Fermeture restituant le focus à un bouton masqué ou au premier du DOM : cible réelle (Task 1).
3. Entrée/Espace exécutant deux bascules avec un contrôle natif : une seule transition (Task 2).
4. Widget dont les enfants sont toujours visibles : pas de fausse commande ni d’état fermé mensonger (Task 2).
5. Redimensionnement, panneau vide et réouverture multipliant les écouteurs : état stable sans erreur (Task 1).

## Task 1: Panneau off-canvas et focus

**Files:**
- Create: `tests/e2e/navigation.cjs`, `tests/references/wp/R25-nav-offcanvas.php`, `tests/references/wp/R25-nav-offcanvas.json`.
- Modify: `tests/comparaison/configurations.php`, `inclure/footer.html`, `inclure/hfb/mobile-trigger.html`, `css/astra-hfb.css` seulement pour une adaptation non couverte par le CSS déjà régénéré.
- Create only if required: `js/astra-navigation.js` ; inclure sa déclaration dans `inclure/footer.html` après le bundle officiel.

**Interfaces:**
- Consumes: bundle 4.13.8, visibilité du panneau 4.13.8 et configuration R2 réutilisable.
- Produces: CLI `node tests/e2e/navigation.cjs <URL> <PROFIL> <SORTIES>` ; profils `offcanvas`, `sousmenus`, `widgets`, `vide`, `evitement`. Code non nul si assertion ou pageerror, rapport JSON des états et captures.

- [ ] **Step 1: Écrire le profil offcanvas**

Avec une fenêtre 768 px et la configuration R25, vérifier panneau fermé, puis ouvrir depuis le déclencheur effectivement visible :

```js
assert.equal(await page.locator('#ast-mobile-popup').isVisible(), false);
await trigger.click();
assert.equal(await trigger.getAttribute('aria-expanded'), 'true');
assert.equal(await page.locator('#menu-toggle-close').evaluate(e => e === document.activeElement), true);
```

Ajouter Tab/Maj+Tab sur toutes les commandes visibles : le focus reste dans le panneau ; bouton, Échap et voile le ferment et rendent le focus au déclencheur. Répéter ouverture/fermeture, puis changer de largeur panneau ouvert ; aucun focus dans un élément masqué, aucun double événement. R25 inclut recherche et padding : champ cliquable et saisissable, bouton de fermeture non rétréci. La présence d'une autre commande masquée avant le déclencheur ne change pas le retour de focus.

- [ ] **Step 2: Exécuter avant correction de l'intégration**

Run sur WP_REFERENCE puis SPIP : `NODE_PATH="$PW/node_modules" node tests/e2e/navigation.cjs "$URL_SPIP" offcanvas "$RUN_DIR/nav-avant"`. Attendu : le comportement amont applicable est établi, et les échecs SPIP d'intégration sont enregistrés. Si un contrôle est déjà satisfait par le bundle et le CSS de lots précédents, le noter comme couvert, sans provoquer artificiellement un échec.

- [ ] **Step 3: Adapter au minimum**

Conserver les classes et sélecteurs attendus par le bundle ; assurer la visibilité fermée/ouvert. Si le bundle choisit un mauvais déclencheur, `astra-navigation.js` mémorise le déclencheur visible lors de l'ouverture et restaure cette cible après la fermeture ; pas de deuxième gestionnaire qui rebascule le panneau. Le cas redimensionné utilise une commande de menu équivalente visible ; sinon le premier lien visible de #masthead parmi .custom-logo-link et .site-title a. Si ces liens manquent, #masthead reçoit temporairement tabindex=-1 pour un focus logique, retiré au blur. Cette règle de repli est testée, et aucun focus n’est placé sur un élément masqué. Déclarer cet écart minimal et le comparer au résultat amont sans le dissimuler.

- [ ] **Step 4: Vérifier**

Run: profil offcanvas à 1280/768/390 px selon la présence d'une commande mobile, R2 et R25, puis profil vide (aucun déclencheur/panneau rendu). Attendu : pas d'erreur console, pas de focus fermé/masqué, une seule ouverture et retour cohérent ; les traces ne constituent pas une certification lecteur d'écran.

- [ ] **Step 5: Commit**

Message : `Intégrer la navigation off-canvas accessible d'Astra 4.13.8`.

## Task 2: Commandes de sous-menus, widgets et évitement

**Files:**
- Modify: `inclure/hfb/menu-items.html`, `inclure/widgets/menu.html` si nécessaire, `inclure/header.html`, `tests/integration/HfbRenduTest.php:212–226`.
- Modify: `tests/e2e/navigation.cjs`, `tests/references/wp/R25-nav-offcanvas.php` ou variantes `R25-nav-widgets` et `R25-nav-vide`, leurs JSON et `configurations.php`.
- Modify only for verified label changes: `lang/astra_fr.php`, `lang/astra_en.php`.

**Interfaces:**
- Consumes: pilote de Task 1 ; rendu d'un parent et de sa sous-page/rubrique, en menu 1/menu2 et widget.
- Produces: commandes sémantiques compatibles avec le JS 4.13.8, mêmes paramètres d'inclusion que les squelettes actuels.

- [ ] **Step 1: Modifier d'abord le test qui fige le vieux rôle**

Le contrôle du WordPress cible R13 révèle un défaut résiduel : les sous-menus de widgets sont visibles, mais le lien et le span annoncent aria-expanded=false ; Entrée/Espace ne fournissent pas un vrai repli de cette liste. [Mesures ciblées](../../inventaires/astra-4.11.0-4.13.8/widgets-aria-cible.json). La spec autorise une adaptation minimale pour ne pas figer un défaut amont : ce plan propose de garder le rendu de liste toujours visible et de rendre la flèche décorative, sans ajouter un menu repliable.

Dans `HfbRenduTest::testBlocMenuDansLesWidgetsDuPied`, remplacer l'assertion de `role="application"` par : pas de faux aria-expanded sur le lien parent, flèche non focalisable/décorative, sans la classe JS `dropdown-menu-toggle` ni état ARIA de commande, sous-pages et titre/nav conservés, aucune commande de dépliage factice. Le profil widgets vérifiera la visibilité constante et l'accès aux liens enfants par Tab. C'est un écart sémantique explicite à la sortie WordPress 4.13.8, sans changement visuel voulu, proposé à la relecture du plan.

Les variantes **d'en-tête réellement dépliantes** reçoivent le rôle button, un nom accessible et un état synchronisé dans les deux branches pages/rubriques, conformément au walker4.13.8. La flèche dans un lien reste un span interactif ; aucun button natif n'est imbriqué dans un lien.

- [ ] **Step 2: Constater l'échec**

Run: `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter testBlocMenuDansLesWidgetsDuPied`. Attendu : ancien rôle application ne satisfait pas l'assertion. Ce changement d'attendu est intentionnel, pas une régression à annuler.

- [ ] **Step 3: Corriger le balisage et compléter les profils**

Reprendre rôles/attributs de 4.13.8 pour les commandes d’en-tête ; noms fournis par les traductions SPIP. Le profil sousmenus utilise Entrée puis Espace et vérifie exactement une bascule, l’état ARIA et l’absence de défilement provoqué par Espace ; Tab vers une autre branche ferme celle quittée. Le profil widgets suit le contrat de liste statique de Step 1 : enfants toujours visibles, liens accessibles, aucune flèche focusable ni état fermé fictif. Tester aussi clic parent naviguant et absence d'erreur sur une feuille sans sous-menu.

Le profil evitement vérifie cible #content existante, visibilité au focus et accès au contenu. Comparer les repères des widgets et traductions d'ARIA dans les éléments réellement rendus ; consigner les décisions des entrées V affectées à cette spec. Aucun ajout de fonction WordPress absente pour solder une ligne.

- [ ] **Step 4: Validation du lot**

Run: test d'intégration précédent, profils sousmenus/widgets/evitement/vide sur leurs configurations appliquées, puis R0/R1/R2/R13 en captures et structure. Attendu : vert sur le contrat porté, ID uniques et preuves de parcours ; tout écart SPIP minimal est décrit. Vérifier aussi les sorties sans texte de titre de widget.

- [ ] **Step 5: Commit**

Message : `Mettre les commandes de sous-menu et les widgets au contrat Astra 4.13.8`.

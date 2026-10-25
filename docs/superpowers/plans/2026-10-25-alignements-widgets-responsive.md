# Alignements des widgets du pied — plan de mise en œuvre

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** appliquer `footer-widget-alignment-1` à `footer-widget-alignment-4` sur les trois appareils, avec la cascade d'Astra 4.13.8.

**Architecture:** ajouter un module de génération pur avant le module des couleurs/typographies du pied. Les quatre clés utilisent le type d'alignement existant, mais leurs replis suivent l'absence de déclaration d'Astra ; elles ne passent pas par les replis du copyright et des icônes. Les oracles sont capturés sur WordPress, indépendamment de la production SPIP.

**Tech Stack:** PHP 7.4+ pour le plugin, SPIP 4.4 pour l'intégration, PHPUnit 11.5 sous PHP 8.2+, WordPress 7.1.3 / Astra 4.13.8, WP-CLI, Python 3, Playwright Chromium.

**Spec:** `docs/superpowers/specs/2026-10-25-alignements-widgets-responsive-design.md`, validée et fusionnée par PR #21, `main` au commit `6b2703c`.

**État :** plan rédigé, non exécuté ; aucun résultat d'essai ou de tests nouveau annoncé. Exécutable sans le plan d'identité ; si les deux sont exécutés successivement, garder leurs tests et adapter les points communs sans les écraser.

## Global Constraints

- Quatre clés exactes, index final : `footer-widget-alignment-1` à `footer-widget-alignment-4`, dans `astra/styles`. Pas de nouvelle migration SQL.
- `desktop`, `tablet`, `mobile` ; valeurs `left`, `center`, `right`, `""` ; aucune unité. Pas de valeur inventée pour un appareil absent/vide/invalide.
- Sans valeur non vide valide, aucun CSS nouveau ni neutralisation. Première saisie mobile/tablette seule conservée.
- N croissant, puis pour chaque N ordinateur sans media, `max-width:921px`, `max-width:544px`. Seuils tablette/mobile, indépendants du menu d'en-tête.
- Émission seulement si `widget-N` est placé dans le pied. Pas de dépendance à une couleur réglée. HTML existant conservé : sélecteur composé sur le même `aside`, pas un descendant inexistant.
- Garder `css/astra-hfb.css` inchangé. Si l'option est active sans valeur desktop, neutralisation conditionnelle `text-align:inherit` avant les seules valeurs réellement posées ; pas de repli automatique center.
- Valeurs physiques left/right identiques en RTL. Les défauts amont RTL et anciens sont documentés, pas injectés dans les métas.
- Pas d'alignement des widgets d'en-tête, marges, couleurs/polices responsives ou sources de menus nouvelles. Pas de changement des réglages ou preuves de Garden dans cette exécution.
- Assets officiels, version de paquet 1.6.0 et schéma inchangés pendant le lot. Publication, version suivante et lot Garden décidés après qualification ; pas de tag/push implicite.
- Provenance Astra 4.13.8 dans code/crédits (amont GPL v2+, port GPL-3.0-or-later). Commits français sans trailer, ajouts fichier par fichier ; exclure les caches, fichiers ignore et inventaires étrangers.
- Worktree/base privés. Intégration, captures et e2e séquentiels ; pas de préparation complète de wp2spip. Arrêts par PID.

## Review Focus

- Sans couleur ni taille réglée, un alignement seul doit fonctionner : `testAlignementSeulSansCouleur`, tâche 2.
- La tablette seule ne doit pas laisser le left figé masquer l'héritage bureau : `testOptionPartielleNeutraliseLeLeftFige`, tâches 1–2 et 4.
- Les noms à index final doivent apparaître dans les quatre bonnes sections : `testQuatreAlignementsDansLeursSections`, tâche 3.
- Un widget absent ou placé en en-tête seul ne doit pas recevoir une règle de pied : `testConditionDePlacement`, tâche 2.
- Effacer un appareil et sauvegarder le formulaire doit conserver les voisins et leur cascade : `testAllerRetourAlignementsWidgets`, tâche 3.

## Fichiers et responsabilités

| Fichier | Rôle | Tâche |
| --- | --- | --- |
| `tests/references/responsive/widgets.json` | Entrées, placement, direction et blocs de chaque cas | 1 |
| `tests/references/responsive/widgets.py`, `test_widgets.py`, `capturer-widgets.sh` | Extraction/capture des oracles | 1 |
| `tests/references/responsive/attendus-widgets/*.css`, `manifest-widgets.json` | Règles amont et adaptations séparées | 1–2 |
| `tests/references/wp/widgets-alignements.php`, `R30-widgets-alignements*.{php,json}` | Pilotes WP et sondes | 1 |
| `tests/comparaison/configurations.php` | Même matrice côté SPIP | 1 |
| `inc/styles/alignements_widgets.php`, `inc/astra_styles.php` | Registre et génération indépendante | 2 |
| `tests/unit/StylesAlignementsWidgetsTest.php` | Valeurs, ordre et placement | 2 |
| `inc/astra_styles_saisie.php`, `lang/astra_{fr,en}.php` | Routage et libellé obligatoires dès l’enregistrement des clés | 2 |
| `inc/astra_styles_saisie.php`, `formulaires/configurer_astra_styles.html`, `lang/astra_{fr,en}.php` | Choix Sans réglage et explication propre aux widgets | 3 |
| `tests/unit/StylesSaisieTest.php`, `tests/integration/StylesFormulaireTest.php` | Première saisie et POST réel | 3 |
| `tests/e2e/widgets_alignements.cjs`, `formulaire_widgets_alignements.cjs` | Rendus/cascade et formulaire | 3–4 |
| `README.md`, `CREDITS.md`, `tests/references/README.md`, `docs/inventaires/widgets-alignements/` | Qualification, provenance, limites | 4 |

Aucun fichier de production ou outil créé par le plan d'identité n'est requis ici. Les deux extractions réutilisent seulement `tests/references/styles/regles.py`, déjà présent en 1.6.0.

## Préparer l'exécution

- [ ] Worktree depuis la spec fusionnée, SHA de départ noté ; `composer install`, `SPIP_CLI=… composer install-spip-test` sur sa base privée. Vérifier le lien `vendor/spip/spip/plugins/astra` vers le code testé. Ce script recrée les contenus : ne pas viser un site existant partagé.
- [ ] WordPress privé via `scripts/install-wp-reference.sh` et `tests/references/cible.json`. Exporter `WP`, `PW`, `SPIP_CLI`, `ASTRA_REFERENCE_ARCHIVE`, `SPIP_ROOT`, `URL_WP`, `URL_SPIP`, `ASTRA_PREUVES` hors des attendus. Quatre workers PHP ; exemple de ports libres à vérifier : 8451/8454, sans conflit avec l'autre lot. Identifiants e2e privés dans `LOGIN`/`MDP`, non journalisés.
- [ ] Garde de cible : `php tests/references/verifier.php "$WP" "$ASTRA_REFERENCE_ARCHIVE" tests/references/cible.json`, exit 0. Exécuter successivement les suites unitaires et intégration avec `vendor/bin/phpunit --testsuite unit` puis `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`. Traiter les échecs initiaux avant le lot.

### Tâche 1 — Capturer les règles et les replis WordPress

**Files:** références et configuration du tableau, sans modification de production.

**Interfaces:**
- `extraire_widgets(css: str) -> list[str]` dans `widgets.py` : seules déclarations text-align des quatre sélecteurs flex exacts, média et ordre conservés.
- `ref_widgets_alignements(string $variante): void` dans le pilote WP.
- `astra_configurations_widgets_alignements(): array` en fin de `configurations.php`, fusionné au retour de `astra_configurations_reference()` sans supprimer d'autres références.
- JSON : `variante => {styles, placement, direction, blocs}`. `placement` nomme `quatre`, `absent`, `entete`, `deplace`, `zone6` ; `direction` ltr/rtl ; `blocs` texte/menu/texte-aligne. Les deux sites utilisent les mêmes valeurs explicites et des textes de test libres.

- [ ] **1. Tests rouges de l'extracteur.** `test_widgets.py` : garder text-align de widget-1 à widget-4 sous le bon média et dans l'ordre ; omettre copyright, icônes, width et règles d'en-tête ; conserver une règle desktop distincte d'une règle à 921. L'ancien sélecteur descendant non flex est reconnu comme hors profil, pas transformé silencieusement en sélecteur composé.
- [ ] **2. Constater le rouge.** `python3 -m unittest discover -s tests/references/responsive -p test_widgets.py` échoue tant que l'extraction n'existe pas ou ne respecte pas ces assertions.
- [ ] **3. Implémenter les outils et les cas.** R0 initial, placement des quatre widgets de la référence de styles ; pas de chargement de R22 qui injecterait des alignements étrangers. Garder les données d'alignement exactement comme dans la matrice, sans compléter les trous par des défauts. Relever le profil flex et les défauts WordPress dans le manifeste.

| Suffixe de `R30-widgets-alignements` | Entrée et résultat attendu |
| --- | --- |
| aucun | Quatre tableaux left/center/center : 12 valeurs égales au relevé Garden |
| `-distincts` | N1 left/right/center ; N2 right/left/right ; N3 center/right/left ; N4 right/center/left |
| `-ordinateur` | N1 right/vide/vide : droite partout |
| `-tablette` | N1 desktop vide, tablet center, mobile vide : héritage bureau mesuré, centre sur tablette/mobile |
| `-mobile` | N1 mobile right seule : héritage bureau/tablette mesuré, mobile droite |
| `-trous` | N1 left/vide/center ; N2 left/right/vide |
| `-vides` | Quatre options absentes ou entièrement vides : aucun nouveau CSS du port |
| `-absent`, `-entete` | Option N1 réglée, widget respectivement absent / dans l'en-tête seul |
| `-deplace` | N1 déplacé dans la rangée basse, son alignement reste actif |
| `-zone6` | N1 seulement en zone 6 d'une rangée à six colonnes : rangée absente du HTML, CSS selon condition de chargement amont |
| `-blocs` | Widget texte, widget menu statique et paragraphe explicitement aligné à droite sous une zone centrée |
| `-rtl` | Cas distincts sous RTL, left/right physiques conservés |

- [ ] **4. Capturer l'oracle.** `WP="$WP" URL="$URL_WP" tests/references/responsive/capturer-widgets.sh` vérifie la cible, applique chaque cas, capture CSS complet temporaire, extrait vers `attendus-widgets/<variante>.css`, puis revient à R0 par trap. Les oracles sans règle sont admis. Capturer aussi les styles calculés sur les trois largeurs, notamment le bureau des deux cas partiels. Les règles SPIP `inherit` sont consignées à part.
- [ ] **5. Décrire les sondes et committer.** Les JSON mesurent text-align sur le **même élément** `.footer-widget-area-inner` du profil flex pour les quatre widgets ; le pilote dédié de tâche 4 gère les absents, l'ancien profil étant documenté hors périmètre. Ajouter copyright et icônes comme témoins inchangés. Python vert. Commit : `Références : alignements responsifs des widgets d’Astra`.

### Tâche 2 — Registre et module d'alignement

**Files:** créer `inc/styles/alignements_widgets.php`, `tests/unit/StylesAlignementsWidgetsTest.php` ; modifier `inc/astra_styles.php`, `inc/astra_styles_saisie.php`, `lang/astra_{fr,en}.php`.

**Interfaces:**
- `astra_styles_alignements_widgets(array $s, array $hfb): array` renvoie les triplets `[media, selecteur, declarations]`, avec styles déjà normalisés et placement contrôlé par `astra_styles_element_place()`.
- Groupe de registre `alignements-widgets`, quatre options type `alignement`, responsive vrai, source `inc/builder/type/footer/widget/dynamic-css/dynamic.css.php`. Définir `defaut` au tableau récent LTR et documenter aussi les défauts récents LTR gauche/vides, récents RTL droite/vides, anciens profils tablette/mobile centre ; ne pas les employer comme valeurs enregistrées.
- Ajouter `alignements_widgets` à `ASTRA_STYLES_MODULES`, immédiatement avant `pied`. Aucune modification de `astra_styles_alignements()` ni de sa constante copyright/icônes.

- [ ] **1. Écrire les tests rouges.** `testAlignementSeulSansCouleur`, `testOptionPartielleNeutraliseLeLeftFige`, `testConditionDePlacement`, `testOrdreDesQuatreWidgets`, `testEnumRefuseeParAppareil` ; s'appuyer sur les oracles de tâche 1, comme `StylesMargesTest` utilise `StylesAttendusTest::regles()`.

```php
$sel = '.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"].footer-widget-area-inner';
$v = ['footer-widget-alignment-1' => ['desktop' => 'right', 'tablet' => '', 'mobile' => '']];
$this->assertSame([['', $sel, ['text-align' => 'right']]], astra_styles_alignements_widgets($v, $hfbAvecWidget1));
$this->assertSame([], astra_styles_alignements_widgets($v, $hfbSansWidget1));
$this->assertSame([], astra_styles_alignements_widgets(['footer-widget-alignment-1' => ['desktop' => '', 'mobile' => '']], $hfbAvecWidget1));
```

Les fixtures HFB de la classe placent explicitement widget-1 à widget-4 et règlent quatre colonnes ; `$hfbSansWidget1` vide les zones sur les deux appareils d'en-tête et au pied pour N1. Le test tablette seule exige exactement `inherit` sans media puis `center` sous 921, pas de règle mobile. Un N2 réglé ne crée aucune règle N1. L'option d'en-tête seule ne produit aucune règle de pied ; la zone 6 se compare à l'oracle sans inventer de condition supplémentaire de CSS.

- [ ] **2. Constater le rouge.** `vendor/bin/phpunit --testsuite unit --filter StylesAlignementsWidgetsTest` échoue : options inconnues et module absent. Aucun assouplissement des tests de placement historiques.
- [ ] **3. Implémenter.** Parcourir N croissant ; ignorer absence de placement et option sans valeur non vide valide. Si desktop existe, émettre sa valeur ; sinon émettre `inherit` pour neutraliser le left figé. Puis seulement les valeurs tablet/mobile renseignées sous leur média. L'alignement n'est pas subordonné à `astra_styles_pose()` du groupe de couleurs. Ne pas toucher `css/astra-hfb.css`. Dès cette tâche, router chaque clé vers sa section `widget_N` avec l’alternative `^footer-widget-alignment-N$` et traduire sa clé commune `astra:style_widget_alignment` ; sinon les tests globaux de section unique et de libellés échoueraient avant la tâche 3.
- [ ] **4. Vérifier.** Filtre puis suite complète unitaires verts. Chaque règle ajoutée est amont ou neutralisation déclarée ; les noms et l'ordre correspondent à l'oracle. PHP 7.4 : lint des fichiers de production et CSS identique au PHP courant pour toutes les variantes, au moyen d'un même pilote temporaire lisant la matrice. Vérifier que `astra_styles_css([], [])` reste vide et que les vieux attendus du pied restent verts.
- [ ] **5. Committer.** `Widgets : appliquer les alignements du pied par appareil`.

### Tâche 3 — Quatre champs d'alignement dans les bonnes sections

**Files:** `inc/astra_styles_saisie.php`, `formulaires/configurer_astra_styles.html`, `lang/astra_{fr,en}.php`, `StylesSaisieTest.php`, `StylesFormulaireTest.php`, nouvel e2e `formulaire_widgets_alignements.cjs`. Réutiliser le HTML du type alignement, avec un libellé vide spécifique aux widgets.

**Interfaces:** garder `astra_styles_sections(): array`, `astra_styles_libelle(string $option): string`, `astra_styles_champs()` et la fusion existante. Le type alignement ouvre déjà les trois appareils ; routage et traduction de base existent depuis la tâche 2. `astra_styles_champs()` ajoute `sans_reglage => true` pour le groupe `alignements-widgets`, faux sinon, pour choisir le libellé vide sans changer les champs du copyright/icônes.

- [ ] **1. Tests rouges.** `testQuatreAlignementsDansLeursSections`, déjà vert après le routage de tâche 2, continue d’exiger N dans `widget_N` uniquement, avec noms `s[footer-widget-alignment-N][desktop|tablet|mobile]`, libellés français/anglais présents. `testPremiereSaisieAlignementWidgetTabletteSeule` garde tablet center et mobile vide, sans inventer desktop. `testAllerRetourAlignementsWidgets` poste le DOM réel pour les quatre options : mêmes CSS/valeurs, autres groupes inchangés, effacement mobile laissant la tablette agir.

```php
$saisie = ['footer-widget-alignment-3' => ['desktop' => '', 'tablet' => 'center', 'mobile' => '']];
$this->assertSame($saisie, astra_styles_saisie_fusionner([], $saisie));
$this->assertSame('astra:style_widget_alignment', astra_styles_libelle('footer-widget-alignment-3'));
```

Tester `diagonal` et `center;}body{display:none` : erreur du champ et aucun enregistrement. Les tests existants `testChaqueOptionDansUneSeuleSection` et `testLibellesTraduits` restent stricts.

- [ ] **2. Constater le rouge utile.** Ajouter `testSansReglageReserveAuxWidgets` qui exige `sans_reglage` vrai pour les quatre widgets et faux pour copyright/icônes, et un test DOM exigeant le choix vide « Sans réglage ». Filtres `StylesSaisieTest` puis intégration `StylesFormulaireTest` : ce nouveau libellé/indicateur manque. Les tests de routage et de première saisie déjà satisfaits restent verts ; ne pas leur inventer un échec.
- [ ] **3. Implémenter le libellé de l’absence.** Garder le routage de tâche 2 et sa clé « Alignement du contenu » / « Content alignment ». Ajouter `styles_sans_reglage` (« Sans réglage » / « No setting ») pour l’option vide lorsque `sans_reglage` est vrai, et l’explication « Sans réglage : la cascade s'applique ». Les autres types alignement gardent leur libellé historique. Garder les trois sélecteurs du type existant, pas une boucle nouvelle de contenu du widget.
- [ ] **4. Vérifier en navigateur.** Le nouvel e2e utilise LOGIN/MDP sur la base privée, remplit et relit les 12 sélecteurs après navigation, refuse la valeur inconnue sans écriture, puis vérifie text-align public aux trois largeurs. Restaurer la configuration initiale. `URL="$URL_SPIP" NODE_PATH="$PW/node_modules" node tests/e2e/formulaire_widgets_alignements.cjs`, e2e historique du formulaire, puis les deux suites complètes : verts.
- [ ] **5. Committer.** `Formulaire : régler les quatre alignements des widgets`.

### Tâche 4 — Qualification, non-régression et documentation

**Files:** créer `tests/e2e/widgets_alignements.cjs`, `docs/inventaires/widgets-alignements/{bilan.md,manifest.json,preuves/}` ; compléter README/crédits/références.

**Interfaces:** `widgets_alignements.cjs <URL_WP> <URL_SPIP> <sorties>` applique les cas via WP-CLI et `appliquer.sh`, compare en navigateur, exit 1 à toute différence non déclarée. Variables : `WP`, `SPIP_ROOT`, `SPIP_CLI`, `PW` ; mêmes conventions que les e2e existants.

- [ ] **1. Écrire les assertions de rendu.** Cas Garden : 12 valeurs left/center/center concordantes. Cas partiels : comparer l'héritage réel, pas une valeur left supposée. Placer successivement N1 en haut/bas puis en en-tête seul, mesurer texte et menu, vérifier la priorité du paragraphe à droite dans une zone centrée ; widget absent ou zone 6 seule : aucune zone visible, aucun artefact HTML. Cas RTL : même alignement physique pour les mêmes valeurs explicites.
- [ ] **2. Exécuter les nouvelles références.** R30 et variantes à 1280, 768, 390 ; frontières 922/921 et 545/544. Captures des quatre widgets ; contrôle que copyright, icônes et widgets non réglés n'ont pas changé. Garder les quatre classes et attributs ARIA existants, pas de rôle ou de doublon de widget ajouté.

```bash
WP="$WP" URL="$URL_WP" tests/references/responsive/capturer-widgets.sh
# HISTORIQUES : tableau Bash des 41 noms resultats.configurations du manifeste 1.6.0.
# R30_COMPARABLES : matrice de tâche 1 sans -absent, -entete, -zone6, traités par le pilote dédié.
WP="$WP" URL="$URL_WP" PW="$PW" tests/references/capturer.sh "${HISTORIQUES[@]}" "${R30_COMPARABLES[@]}"
SPIP_CLI="$SPIP_CLI" php tests/comparaison/comparer_html.php "$SPIP_ROOT" "$URL_SPIP"
SPIP_CLI="$SPIP_CLI" php tests/comparaison/comparer_html.php "$SPIP_ROOT" "$URL_SPIP" "${R30_COMPARABLES[@]}"
WP="$WP" URL_WP="$URL_WP" PW="$PW" SPIP_CLI="$SPIP_CLI" tests/comparaison/comparer_captures.sh "$SPIP_ROOT" "$URL_SPIP" "${HISTORIQUES[@]}" "${R30_COMPARABLES[@]}"
NODE_PATH="$PW/node_modules" node tests/e2e/widgets_alignements.cjs "$URL_WP" "$URL_SPIP" "$ASTRA_PREUVES/widgets"
NODE_PATH="$PW/node_modules" node tests/comparaison/comparer_blocs.cjs "$URL_WP_BLOCS" "$URL_SPIP_BLOCS" "$ASTRA_PREUVES/blocs"
```

La liste historique est extraite de `docs/inventaires/astra-4.13.8-qualification/manifest.json`, pas de la liste par défaut du shell. Pour HTML, passer aussi explicitement les anciennes références de styles. Définir `URL_WP_BLOCS` et `URL_SPIP_BLOCS` selon le README des blocs ; les pages importées utilisent le commit courant d’Astra, sans refaire une préparation complète de wp2spip. R28 se traite sur ses clones oEmbed avec/sans plugin selon son README. Si le lot identité est déjà présent, ajouter ses R29 comparables et son pilote dédié à la qualification, sans les rendre obligatoires pour une exécution indépendante de ce plan. Les cas R30 absents/zone6, pour lesquels des captures de zones n'existent pas, sont vérifiés par le pilote dédié sans produire un faux succès de capture manquante.

- [ ] **3. Tester la sensibilité puis valider le code final.** Les tests détectent : index N décalé, émission sans widget, oubli de neutralisation, center forcé sur mobile vide, sélecteur descendant au lieu du même élément. Faire ces mutations sur une copie temporaire, restaurer, puis lancer suites complètes, Python, e2e et comparaisons. PHP 7.4 et courant : même CSS ; assets officiels inchangés. Pas de seuil relevé ni changement de texte pour rapprocher des captures.
- [ ] **4. Documenter et committer.** Manifeste avec SHA testé, versions, empreintes, compteurs et codes réels ; adaptations inherit séparées des règles amont. Ne pas réécrire les preuves 1.6.0, ni augmenter les ignores historiques R13/R11. README : quatre options et héritage, limites RTL/non-flex/en-tête ; crédits de provenance. Commit : `Preuves et documentation des alignements des widgets`.

## Autorelecture et suite

Couverture : noms/données/défauts → 1–3 ; cascade/placement/neutralisation → 1–2/4 ; saisie/conservation → 3 ; frontières/RTL/blocs → 4 ; PHP/provenance/ancien rendu → 1/4. Les douze mesures de Garden ne sont mises à zéro qu'après publication et lot consommateur, hors de ce plan.

Faire relire avant le code. Proposition : exécution native, tâche par tâche, puis revue indépendante ; quatre tâches liées, pas de dépendance d'implémentation à l'identité. Les décisions de version commune et de publication suivent la qualification des lots.

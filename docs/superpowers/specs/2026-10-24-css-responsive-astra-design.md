# CSS, cascade et en-tête responsive selon Astra 4.13.8

Date : 24 octobre 2026. Statut : proposition pour relecture.
Dépendances : [référence 4.13.8](2026-10-24-reference-astra-4.13.8-design.md) et
[navigation](2026-10-24-navigation-accessible-astra-design.md) pour les états interactifs.

## Objectif et conception

Faire correspondre la cascade effective du port au thème 4.13.8 pour les fonctions
existantes : main.min.css officiel, CSS dynamique figé, règles de compatibilité,
CSS produit par les réglages, styles de blocs et adaptations SPIP. Les différentes
couches ne sont pas interchangeables.

Choix proposé : régénération depuis les références WordPress et adaptation des
générateurs transposés, avec inventaire des différences. Une feuille de surcharges
qui masquerait le vieux CSS conserverait sa cascade et ses défauts ; une copie
isolée du nouveau main.min.css mélangerait les versions. Les adaptations SPIP
nécessaires restent explicites et sourcées.

## Contrats CSS à reprendre

### Typographie et cascade des blocs

La règle des éléments inline `code,kbd,tt,var` utilise la taille relative 1 em de
4.13.8. Le test caractérisant 15 px dans un texte à 24 px devient un test de 24 px.
Tester aussi code dans un titre et les éléments rendus après import.

Les sélecteurs de titres à base de `:where()` et leur spécificité sont repris
de façon cohérente dans toutes les couches concernées, y compris
`inc/styles/titres.php` et les styles figés. Conserver les graisses, replis et
drapeaux de compatibilité selon 4.13.8. Le simple changement des sélecteurs dans
l'asset ne valide pas l'ensemble. Une règle de bloc explicitement choisie doit
avoir la priorité que lui donne le WordPress cible.

Comparer les blocs natifs conservés par wp2spip, en particulier titres, colonnes,
groupes et boutons, avec largeurs normale/wide/full. Les corrections annoncées
pour GenerateBlocks/Spectra sont distinguées de leur runtime absent : reprendre
une règle qui concerne un HTML réellement conservé n'implique pas de porter ces
constructeurs. Ne pas attribuer un changement de colonnes à 4.11.14 sans retrouver
le cas et la règle dans les sources 4.13.8.

### Variables, formulaires et couleurs

Les replis des générateurs 4.13.8 utilisent des `var()` imbriqués lorsqu'ils
référencent une autre variable. Les CSS extraits du port suivent cette sortie.
Contrôler toutes les occurrences concernées, pas seulement le fichier principal.

Reproduction de référence : `input[type=date]`, première variable du repli
indéfinie, seconde définie à #123456 ; le fond reste rgb(18,52,86). Contrôle
négatif : palette complète, pas de modification inexpliquée. Le champ texte
ordinaire n'est pas un substitut à ce cas précisément caractérisé.

Le main.min.css officiel peut contenir des reliquats de règles non corrigées
par les générateurs. Une occurrence n'est pas supprimée aveuglément : déterminer
si elle est atteinte dans le périmètre SPIP. Si 4.13.8 reste fautif sur un cas
utilisable, le consigner et définir un écart minimal avec un test spécifique,
sans modifier silencieusement l'asset officiel ni déclarer le défaut résolu.

Les couleurs de label/legend suivent la palette 4.13.8, avec contrôle des formulaires
SPIP et des contrastes. Les couleurs libres et les unités restent validées par les
types existants ; une erreur de saisie ne crée pas un nouveau défaut écrit.

### Menus et copyright

Reprendre les sélecteurs de survol 4.13.8 dans les règles figées et générées des
menus 1/2. La correction de propagation aux parents imbriqués est préventive dans
le port à deux niveaux : ne pas inventer une profondeur supplémentaire pour
annoncer un défaut visuel de Garden. Un fixture HTML technique à trois niveaux
peut isoler le sélecteur, clairement distinct du builder pris en charge.

L'en-tête transparent suit son propre module 4.13.8 ; ne pas lui appliquer par
analogie un changement absent de sa source. Les parties prises en charge du
copyright (couleur, taille, alignement) suivent les sélecteurs 4.13.8, et le
balisage possède les classes nécessaires. L'option de marge du copyright n'est
pas ajoutée dans cette migration ; les 15 options de marges de rangées existantes
restent celles de leur module, avec leurs unités et conditions d'émission.

### Périmètre des valeurs responsives

La migration corrige la visibilité et les règles déjà appliquées sur plusieurs
appareils ; elle n'active pas globalement toutes les valeurs tablette/mobile
jusqu'ici seulement enregistrées. Le futur travail sur l'identité responsive
reste distinct. Les tailles/affichages existants doivent être comparés avec un
WordPress configuré selon le même sous-ensemble pris en charge ; les limites
restantes sont explicitement conservées au README.

Le logo reste affiché lorsque le menu principal est vide. Les espacements déjà
configurables des pages et archives, et le sens RTL des composants présents,
sont comparés à 4.13.8. Les correctifs RTL de types de contenus WordPress absents
ne justifient pas d'en inventer les équivalents. Toute adaptation RTL porte
sur des éléments réellement rendus et a sa fixture.

## Frontière responsive et affichage initial

Deux sources amont sont distinguées : `astra_get_tablet_breakpoint()` pour
les règles CSS qui l'emploient, notamment `prepare_visibility_css()`, et
`astra_header_break_point()` pour le seuil de l'en-tête. Le script précoce
`set_header_break_point_early()` appelle **`astra_header_break_point()`** et
applique +0.99 à cette valeur ; le JavaScript de l'en-tête doit utiliser le même
seuil. Les deux fonctions valent 921 par défaut, mais représentent deux réglages
distincts dans Astra et ne sont pas interchangeables.

Dans le périmètre actuel du port, leurs valeurs nominales restent fixées à
921 px ; cette correction n'ajoute pas deux nouveaux réglages. La provenance
de chaque borne reste explicite, pour éviter qu'une évolution de l'un change
l'autre implicitement. Les règles concernées utilisent +0.99/max921.99 et
min 922 lorsque leur module amont le fait. Cela n'autorise pas à remplacer tous
les max 921 : typographie, espacement, visibilité et couleurs ont des fonctions
distinctes.

La classe initiale responsive est fixée par l'équivalent du petit script amont,
après l'ouverture du body et avant le rendu de l'en-tête, une seule fois par
page publique. Il ne dépend ni du bundle chargé en pied ni de jQuery. Tous les
gabarits publics, y compris accueil statique, pages, articles et archives,
passent par ce contrat. Le JS normal poursuit ensuite la gestion des changements
de largeur sans contradictions de classes ni de visibilité.

Le script précoce ne fige pas un résultat propre au premier visiteur dans le
cache HTML serveur : la décision est faite côté navigateur. Il n'utilise pas
une détection de terminal pour sélectionner une variante de cache.

Les restrictions transparent desktop/mobile/both et leurs couleurs utilisent
les bornes de leur propre source 4.13.8. Aucune largeur ne doit afficher deux
en-têtes contradictoires. Examiner aussi scrollbars et zoom ; si un défaut
résiduel existe dans 4.13.8 à une fraction extrême, le signaler comme tel et
proposer une adaptation explicite, plutôt que promettre une continuité non testée.

**Décision de Gilles — sans JavaScript.** Le menu mobile expose ses liens en
permanence dans une liste simple, rendue uniquement lorsque les scripts du site
sont désactivés, au moyen de `<noscript>`. Aucun `<details>` n'est ajouté. Les
menus interactifs et leurs sélecteurs attendus par le bundle 4.13.8 restent
inchangés ; avec JavaScript, aucun contenu de repli n'est rendu ni stylé.
Le repli suit les sources et placements des menus mobiles du builder, avec
leurs liens enfants accessibles sans commande de dépliage. Il est absent du
rendu visible ordinateur à 1280 px, présent à 768 et 390 px ; un menu vide ne
produit pas de navigation vide. C'est un écart déclaré à Astra 4.13.8 : R2 ne
montre aucun lien à 768/390 px sans JavaScript, contrairement aux deux liens
visibles à 1280 px ([mesure](../../inventaires/astra-4.11.0-4.13.8/sans-javascript-cible.json)).
Les tests utilisent des contextes `javaScriptEnabled:false` aux trois largeurs ;
avec JavaScript actif, ils comparent le rendu à WordPress, sans repli visible.
La migration n'ajoute pas de masquage global de l'en-tête en attendant le bundle.
La suppression de polyfills abandonnés par l'amont ne signifie pas une nouvelle
promesse de support des navigateurs anciens.

## Preuves attendues

| Domaine | Cas obligatoires |
| --- | --- |
| Typographie | code/kbd/var dans texte et titres ; graisses générales/h1–h6 avec et sans drapeaux |
| Cascade des blocs | Titres, colonnes normal/wide/full, groupes et boutons conservés après import |
| Replis | Variable première valide, absente/invalide ; repli de couleur valide ; champ date et bordures de builder |
| Menus | Défaut et réglages explicites des menus 1/2 ; fixture de sélecteur à trois niveaux séparée du builder |
| Frontière | 921 et 922 px obligatoires, avec et sans scrollbar ; cas fractionnaires complémentaires si reproductibles |
| Initialisation | Captures de première peinture/trace avec ressources retardées ; pas seulement une capture après stabilisation |
| Transparence |desktop/mobile/both, page explicite et règle globale, de part et d'autre des frontières |
| Configurations rares | Logo sans menu, widgets et pied RTL, espacements de page/archive déjà exposés |
| Données | Aucune option absente créée ; valeur sans unité inactive ; formulaire inchangé sans effet nouveau |

Le cas proche de 921,95 px est complémentaire, **si reproductible avec les
moyens disponibles**, et n'impose pas de construire un outillage de zoom dédié.
S'il est exécuté, les API prenant une largeur entière ne suffisent pas à
prétendre l'avoir testé : consigner largeur CSS effective, matchMedia,
innerWidth, clientWidth et visibilité. Sinon, documenter l'absence de reproduction,
sans l'annoncer comme une mesure réussie. Elle n'est pas bloquante si les bornes
CSS/JS sont vérifiées contre la source et les cas obligatoires 921/922 px passent.
Une anomalie effectivement reproduite et inexpliquée reste bloquante.

Les sorties anciennes sont conservées pour expliquer les différences ; les
attendus nouveaux proviennent de WordPress 4.13.8, avec le même cœur 7.1.3.
Les captures seules sont complétées par styles calculés, ordre des règles et
HTML normalisé. Les valeurs/tableaux responsives sont vérifiés par aller-retour
des formulaires et par le seed Garden.

## Sortie

Les entrées V affectées à ce domaine sont qualifiées avec source et fixture ou
preuve d'absence. Toutes les couches actives pointent vers 4.13.8 ; les exceptions
SPIP et défauts amont conservés sont listés. Les références générales de la
migration, les suites unitaires/intégration et les sorties PHP 7.4/8.4 pertinentes
sont vertes. Aucune référence WordPress 4.11.0 n'est simplement renommée 4.13.8.

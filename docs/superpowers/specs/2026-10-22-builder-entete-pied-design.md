# Builder simplifié d'en-tête et de pied de page — spécification

Date : 2026-10-22. Statut : validée (choix « builder simplifié », niveau 2), révisée après relecture.

## Objectif

Permettre de composer l'en-tête et le pied de page d'un site SPIP comme avec le Header/Footer
Builder d'Astra 4.11.0 : des rangées, des zones dans les rangées, des éléments dans les zones.
Le HTML produit est celui d'Astra (classes `ast-builder-*`, `site-header-*`, `site-footer-*`), si
bien que `css/main.min.css` et `js/frontend.min.js` fonctionnent sans modification.

La configuration se fait par formulaire, sans glisser-déposer.

## Contexte

- Le portage actuel fige la disposition par défaut d'Astra dans `inclure/header.html` et
  `inclure/footer.html` : identité à gauche, menu à droite ; en mobile, identité et bouton de menu
  avec menu déroulant ; copyright centré dans le pied de page.
- Le site SPIP visé est un site éditorial (pas forcément un blog). Pas de WooCommerce ni d'EDD.
- Référence : Astra officiel 4.11.0 (Brainstorm Force), installé dans le WordPress de test.
- **Version vérifiée dans ce chantier : SPIP 4.4.** Le manifeste garde sa compatibilité déclarée
  `[4.2.0;5.0.*]`, mais SPIP 4.2 et 5.0 sont **non vérifiés** pour le builder : c'est une réduction
  de la validation par rapport au portage initial (testé en 4.4 et 5.0). La vérification sur
  SPIP 5.0 (dont l'outil en ligne de commande est `bin/spip`, encore instable) fera l'objet d'un
  chantier séparé ; le README le signale.

## Modèle de données

### Structure

Les **placements** reprennent la structure des options d'Astra (`header-desktop-items`,
`header-mobile-items`, `footer-desktop-items`) : mêmes noms de rangées, de zones et d'éléments.
La fonction de rendu suit ainsi la logique d'Astra sans traduction, et les placements d'un site
WordPress pourront être importés directement. Les réglages de rangées, les réglages d'éléments et
les sources de menu ont en revanche des clés propres à SPIP : un import depuis Astra demandera une
conversion pour ces parties.

Stockage : méta `astra`, sous-clé `hfb` (lue par `lire_config('astra/hfb')`).

```php
[
	'entete_ordinateur' => [          // = header-desktop-items, sans 'popup'
		'above'   => ['above_left' => [], 'above_left_center' => [], 'above_center' => [], 'above_right_center' => [], 'above_right' => []],
		'primary' => ['primary_left' => ['logo'], 'primary_left_center' => [], 'primary_center' => [], 'primary_right_center' => [], 'primary_right' => ['menu-1']],
		'below'   => [/* idem, vides */],
	],
	'entete_mobile' => [              // = header-mobile-items
		'popup'   => ['popup_content' => ['mobile-menu']],
		'above'   => ['above_left' => [], 'above_center' => [], 'above_right' => []],
		'primary' => ['primary_left' => ['logo'], 'primary_center' => [], 'primary_right' => ['mobile-trigger']],
		'below'   => ['below_left' => [], 'below_center' => [], 'below_right' => []],
	],
	'pied' => [                       // = footer-desktop-items, colonnes 1 à 6
		'above'   => ['above_1' => [], …, 'above_6' => []],
		'primary' => ['primary_1' => [], …, 'primary_6' => []],
		'below'   => ['below_1' => ['copyright'], 'below_2' => [], …, 'below_6' => []],
	],
	'rangees' => [
		'entete_above'   => ['hauteur' => 50, 'fond' => '', 'bordure' => 1],
		'entete_primary' => ['hauteur' => 80, 'fond' => '', 'bordure' => 1],
		'entete_below'   => ['hauteur' => 60, 'fond' => '', 'bordure' => 1],
		'pied_above'     => ['colonnes' => 2, 'repartition' => '2-equal', 'hauteur' => 60, 'fond' => ''],
		'pied_primary'   => ['colonnes' => 3, 'repartition' => '3-equal', 'hauteur' => '', 'fond' => ''],
		'pied_below'     => ['colonnes' => 1, 'repartition' => 'full', 'hauteur' => 80, 'fond' => ''],
	],
	'menu_mobile' => 'dropdown',      // mobile-header-type : 'dropdown' | 'off-canvas'
	'elements' => [
		'entete' => [ /* réglages des éléments d'en-tête, partagés entre ordinateur et mobile */ ],
		'pied'   => [ /* réglages des éléments du pied de page */ ],
	],
]
```

Les valeurs par défaut (placements, hauteurs, colonnes, répartitions, `dropdown`) sont celles
d'Astra (`inc/core/builder/class-astra-builder-options.php`) et reproduisent exactement le rendu
actuel du plugin : un site sans configuration `hfb` ne voit aucun changement.

### Règles de fusion avec les valeurs par défaut

La configuration enregistrée est normalisée par une fonction dédiée (pas de fusion récursive
générique, qui réintroduirait des éléments supprimés dans les listes) :

1. **Placements** — au niveau de chaque **zone** : une clé de zone **absente** hérite de la valeur
   par défaut ; une clé **présente**, même avec une liste vide, est prise telle quelle. Une liste
   vide retire donc les éléments par défaut (par exemple `primary_right => []` retire `menu-1`).
   Une rangée ou un contexte absent hérite entièrement des défauts.
2. **Réglages de rangées** — clé par clé, une valeur absente hérite du défaut.
3. **Réglages d'éléments** — clé par clé au sein de chaque élément.
4. **`menu_mobile`** — valeur absente ou hors de `dropdown`/`off-canvas` → `dropdown`.

Le formulaire enregistre toujours toutes les zones explicitement : l'héritage ne joue que pour une
configuration partielle (écrite à la main ou par une version antérieure du plugin).

Vérifié sur SPIP 4.4 : `ecrire_config()` conserve une liste vide dans la méta (test
`HfbConfigurationTest::testListeVideEnregistreeRetireLeMenu`).

### Registre des éléments

Un registre explicite décrit chaque élément autorisé : son identifiant, le groupe de réglages
auquel il appartient (`entete` ou `pied`) et les contextes où il peut être placé. Il pilote à la
fois la normalisation, le formulaire et le rendu.

Mêmes identifiants et mêmes nombres que la version gratuite d'Astra
(`Astra_Builder_Helper::$num_of_*`) :

| Identifiant | Élément | Ordinateur | Mobile (rangées) | Mobile (panneau) | Pied |
|---|---|---|---|---|---|
| `logo` | Identité (logo, nom, slogan) | oui | oui | oui | – |
| `menu-1`, `menu-2` | Menus | oui | oui | oui | – |
| `mobile-menu` | Menu mobile | – | oui | oui | – |
| `mobile-trigger` | Bouton du menu mobile | – | oui | – | – |
| `html-1`, `html-2` | Texte libre (raccourcis SPIP) | oui | oui | oui | oui |
| `button-1` | Bouton | oui | oui | oui | – |
| `search` | Recherche | oui | oui | oui | – |
| `social-icons-1` | Icônes sociales | oui | oui | oui | oui |
| `widget-1`, `widget-2` | Zones de widgets | oui | oui | oui | oui |
| `widget-3`, `widget-4` | Zones de widgets | – | – | – | oui |
| `account` | Compte (connexion SPIP) | oui | oui | oui | – |
| `copyright` | Copyright | – | – | – | oui |
| `menu` | Menu de pied de page | – | – | – | oui |

La normalisation retire : les identifiants inconnus (`menu-99`, `unknown`…), les éléments placés
hors de leurs contextes (`copyright` dans l'en-tête, `widget-4` dans l'en-tête,
`mobile-trigger` en ordinateur), et les **doublons** : un élément n'apparaît qu'une fois par
builder (ordinateur ; mobile, panneau compris ; pied). L'ordre de parcours pour garder la première
occurrence est : rangées `above`, `primary`, `below`, puis zones de gauche à droite (colonnes
1 à 6 dans le pied), puis le panneau mobile.

### Réglages des éléments

Les éléments d'en-tête et de pied de page ont des réglages **indépendants**, même lorsqu'ils
partagent un identifiant : `elements/entete/html-1` et `elements/pied/html-1` sont deux textes
différents. Un élément d'en-tête a les mêmes réglages en ordinateur et en mobile.

| Élément | Réglages (`elements/<groupe>/<id>`) |
|---|---|
| `logo` | aucun : utilise les réglages existants `afficher_titre_site` et `afficher_slogan` |
| `menu-1`, `menu-2`, `menu` | `source` (`racine`, `rubrique`, `groupe_mots`, `pages`, `menus`), `id` (rubrique, groupe ou identifiant de menu selon la source) |
| `mobile-menu` | aucun : reprend la source de `menu-1` |
| `html-1`, `html-2` | `texte` (raccourcis SPIP) |
| `button-1` | `libelle`, `lien`, `nouvel_onglet` (`oui`/`non`), `style` (`plein`/`contour`) |
| `social-icons-1` | `reseaux` : liste de `['reseau' => 'facebook', 'url' => '…']` |
| `widget-N` | `blocs` : liste parmi `recherche`, `sous_rubriques`, `articles_recents`, `forums_recents`, `mots_cles`, `texte` ; `texte` |
| `copyright` | aucun : utilise le réglage existant `texte_pied` |
| `search`, `account`, `mobile-trigger` | aucun |

`menu-1` a pour défaut la source `racine` (comportement actuel). La source `pages` n'est proposée
que si le plugin Pages est actif, `menus` que si le plugin Menus est actif (après étude de son code
et de sa licence).

Réseaux sociaux proposés : Facebook, X, Instagram, LinkedIn, YouTube, Mastodon, Bluesky, GitHub,
flux RSS du site, e-mail. Icônes reprises d'Astra (licence vérifiée à l'étape 1).

### Contrat de l'en-tête mobile

- L'en-tête mobile a ses propres rangées (`above`, `primary`, `below`) avec trois zones (`left`,
  `center`, `right`), et un **panneau** (`popup.popup_content`) affiché à l'ouverture du menu.
- `menu_mobile` choisit la présentation du panneau : `dropdown` (contenu déroulé sous l'en-tête,
  `.ast-mobile-header-content`) ou `off-canvas` (tiroir latéral, `#ast-mobile-popup` /
  `.ast-mobile-popup-drawer`). Le balisage exact des deux variantes est relevé à l'étape 1.
- Le panneau n'est affiché que s'il contient au moins un élément **et** que `mobile-trigger` figure
  dans une rangée mobile. `verifier()` refuse un panneau non vide sans bouton de menu.
- Les éléments du panneau sont rendus avec le contexte d'appareil `mobile` (même squelette
  d'élément, variante éventuelle selon l'appareil).

### Pied de page : colonnes

Pour chaque rangée, `colonnes` (1 à 6) fixe le nombre de colonnes rendues et `repartition` leur
largeur (liste exacte des répartitions relevée dans le code d'Astra à l'étape 1).

Écart assumé avec Astra : Astra n'examine que les colonnes 1 à 5 pour décider si une rangée est
occupée, et affiche une rangée dont les éléments sont tous au-delà du nombre de colonnes sans les
rendre. Ici, la normalisation **retire les éléments placés au-delà du nombre de colonnes**, et une
rangée est affichée si l'une de ses colonnes rendues (1 à `colonnes`, jusqu'à 6) contient un
élément. Le formulaire empêche ces placements.

## Rendu

- `astra_hfb_rangees(string $contexte): array` lit et normalise la configuration, puis renvoie pour
  `entete_ordinateur`, `entete_mobile` ou `pied` les rangées à afficher, avec leurs drapeaux et
  classes calculés comme Astra, et pour chaque zone la liste ordonnée de ses éléments.
- `astra_hfb_panneau(): array` renvoie les éléments du panneau mobile (vide si le panneau ne doit
  pas être affiché).
- Chaque élément est décrit par `['id', 'fond', 'numero', 'groupe']` (`groupe` = `entete` ou
  `pied`). `inclure/header.html` et `inclure/footer.html` bouclent (`DATA`) sur ces résultats et
  incluent `<INCLURE{fond=#VALEUR{fond}}{numero=#VALEUR{numero}}{groupe=#VALEUR{groupe}}{appareil=desktop|mobile} />`.
  Le squelette d'élément lit ses réglages par `elements/<groupe>/<id>`. Chaque fichier
  `inclure/hfb/<element>.html` est surchargeable dans `squelettes/inclure/hfb/`.
- Les réglages visuels des rangées passent par des variables CSS dans `inclure/head-vars.html`.
- Le CSS dynamique d'Astra pour les rangées et éléments supplémentaires est extrait du WordPress de
  référence et fusionné dans `css/astra-dynamic.css` (script `merge_css.py`).

## Configuration

Page séparée « Thème Astra › En-tête et pied de page », formulaire CVT
`formulaires/configurer_astra_hfb` :

- pour chaque élément et chaque builder où il est autorisé : une liste « Emplacement » (non
  affiché ou une zone autorisée) et un ordre ;
- les réglages propres aux éléments (groupes `entete` et `pied`), aux rangées, et le type de menu
  mobile ;
- `verifier()` refuse : zone inexistante ou non autorisée pour l'élément, élément placé deux fois
  dans un même builder, colonne au-delà du nombre de colonnes, panneau mobile non vide sans
  `mobile-trigger`.

Le formulaire traduit les emplacements choisis vers la structure du modèle de données et
enregistre toutes les zones explicitement.

## Hors périmètre

Glisser-déposer ; zones `left_center`/`right_center` dans le formulaire (elles restent rendues si
une configuration les contient) ; en-têtes collant et transparent (Astra Pro) ; paniers
WooCommerce/EDD ; réglages typographiques par élément ; dispositions tablette distinctes du mobile ;
vérification sur SPIP 5.0.

## Contraintes techniques

- Code du plugin exécutable en **PHP 7.4 à 8.5** (plage de SPIP 4.4) : pas de syntaxe postérieure
  à PHP 7.4 (types `mixed`, unions, `match`, arguments nommés, `str_contains`…). Les contrôles
  `php7.4` du plan portent sur le nouveau code du builder ; ils ne démontrent pas à eux seuls la
  compatibilité de tout le plugin sur PHP 7.4–8.5.
- Outillage de test : **PHP 8.2+** (PHPUnit 11.5).
- Outil en ligne de commande : spip-cli avec le correctif de la MR !91 (téléchargement et
  activation de plugins), soit la copie locale `spip-cli-custom`, désignée par la variable
  d'environnement `SPIP_CLI`.

## Licences

astra-spip est sous GPL-3.0-or-later. Le code repris d'Astra est sous GPL-2.0-or-later. Toute
dépendance ajoutée (plugin Menus, Pages…) est vérifiée avant usage. Les contenus des modèles de
démarrage (textes, photos) ne sont jamais versionnés dans le dépôt.

## Vérification

1. **Tests unitaires** (PHPUnit, sans SPIP) : normalisation, registre, calcul des rangées.
2. **Références WordPress reproductibles** : configurations wp-cli versionnées, appliquées à partir
   d'un état initial fixé ; capturer une configuration seule ou après une autre donne le même
   résultat normalisé. Contrôles bloquants (erreur JavaScript, élément attendu non visible, menu
   mobile qui ne s'ouvre pas, CSS dynamique vide).
3. **Tests d'intégration SPIP 4.4** (PHPUnit sur un site SPIP installé par spip-cli) : pour chaque
   disposition, configuration écrite, en-tête et pied calculés par `recuperer_fond`, présence et
   ordre des classes attendues.
4. **Comparaison HTML automatique** entre les fragments d'en-tête et de pied produits par SPIP et
   par WordPress, après normalisation (attributs propres à WordPress, URL, contenus).
5. **Comparaison visuelle automatique** des captures (ordinateur 1280 px, mobile 390 px, menu
   mobile ouvert) avec un seuil de différence en pixels ; l'image des différences est conservée.
6. **Test de bout en bout du formulaire** (Playwright) : connexion admin, saisie d'une disposition,
   enregistrement, vérification sur le site public.

## Suites prévues (hors de cette spécification)

1. Pages uniques (plugin Pages) et sous-pages (liens a2a `sous_page`), alignés sur wp2spip ; CSS
   des classes `wp-block-*` des contenus convertis.
2. Portage du modèle de démarrage « Garden Maintenance » (id 189117, éditeur de blocs, sans
   Spectra) comme test de bout en bout.
3. Extensions éventuelles de l'éditeur tiptap_spip, sous forme de plugin d'extension
   (cf. `tiptap_spip/docs/site/developper/faq.md`), sans modifier tiptap_spip.
4. Vérification et adaptation pour SPIP 5.0.

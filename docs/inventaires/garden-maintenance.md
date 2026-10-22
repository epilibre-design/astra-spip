# Garden Maintenance — inventaire des composants à porter

Inventaire du 22 octobre 2026, établi à partir de l’export du modèle **189117**
(`garden-maintenance-4`). Il décrit les éléments effectivement enregistrés, sans importer
le site ni installer de plugin. Les propositions SPIP sont des pistes de portage, pas des
fonctionnalités déjà validées.

Le périmètre principal comprend **six pages publiées, 352 occurrences de blocs :
332 Spectra, 19 images natives et un shortcode SureForms**. Les 332 blocs Spectra se
regroupent en **neuf familles fonctionnelles**, soit 20 noms techniques de blocs.
Les composants enfants ne sont donc pas autant de composants autonomes à développer.

Le socle le plus important est la conversion des conteneurs et des styles adaptatifs.
Les interactions spécifiques sont limitées à une FAQ, quatre compteurs et une carte,
auxquelles s’ajoute le traitement du formulaire de contact.

## Sources et méthode

| Source | Rôle |
|---|---|
| `site.json`, modèle 189117 | Options Astra, widgets, dépendances et réglages d’import |
| [Export WXR](https://websitedemos.net/wp-content/uploads/astra-sites/1717/wxr.xml) | Pages, blocs imbriqués, médias, menus et formulaire |
| [Site déclaré par le modèle](https://websitedemos.net/garden-maintenance-09) | Référence visuelle à établir lors du portage |
| `/src/wordpress/wp2spip/inc/wp2spip_blocs.php` | Convertisseur existant et pipeline `wp2spip_bloc` |
| `/src/tiptap-spip/docs/site/developper/faq.md` | Exemple d’extension d’éditeur sans modifier `tiptap_spip` |
| `inc/astra_hfb.php`, `inclure/hfb/`, `inclure/widgets/` | Éléments du builder Astra SPIP réutilisables |

Les deux exports analysés se trouvent, au moment de l’inventaire, dans
`/tmp/claude-1000/-src-astra-spip/dc097d1c-c7f6-455a-b514-0ce253b369df/scratchpad/garden/`.
Ce chemin est temporaire. Les exports et leurs textes ou images ne sont pas ajoutés au dépôt.
Le fichier voisin `garden-maintenance.json` conserve seulement les comptages et les empreintes
SHA-256 permettant d’identifier les sources.

Méthode : lecture XML du WXR, décodage des attributs JSON des commentaires ouvrants
`wp:…`, comptage des occurrences imbriquées, sans compter les fermetures. Les blocs natifs
sans préfixe sont normalisés en `core/…`. Les contenus à la corbeille sont exclus des totaux
publiés ; widgets et composition réutilisable sont comptés séparément. La structure imbriquée
et les attributs ont été examinés ; le rendu WordPress et les comportements JavaScript ne
sont pas validés par cet inventaire.

**Attention au format Spectra :** les pages utilisent `spectra/*`, tandis qu’un widget
utilise encore `uagb/image`. Il faut sélectionner les sources et le moteur WordPress qui
enregistrent réellement ces deux familles de noms. Le seul nom commercial « Spectra »
ou le slug de dépendance `ultimate-addons-for-gutenberg` ne suffit pas à garantir ce support.
La valeur `[3732]` du champ `spectra-blocks-ver` de l’API n’est pas interprétée ici comme
un numéro de version du plugin.

## Répartition par page

| Page | ID WordPress | Spectra | Images natives | Shortcode | Total | Particularités |
|---|---:|---:|---:|---:|---:|---|
| Home | 92 | 95 | 5 | 0 | 100 | Sections, cartes, boutons, icônes, image d’accueil |
| About | 93 | 62 | 3 | 0 | 65 | Quatre compteurs |
| Services | 1047 | 73 | 4 | 0 | 77 | Trois listes à icônes, neuf entrées |
| Projects | 1048 | 31 | 7 | 0 | 38 | Composition d’images ; aucun bloc galerie dédié |
| FAQ | 37 | 36 | 0 | 0 | 36 | Un accordéon de cinq questions |
| Contact | 1049 | 35 | 0 | 1 | 36 | Réseaux sociaux, liste de trois entrées, carte et formulaire |
| **Total** | | **332** | **19** | **1** | **352** | |

Toutes ces pages déclarent le titre de page Astra désactivé et un conteneur pleine largeur.
L’accueil WordPress est une page statique, pas la liste des derniers articles.

## Les neuf familles Spectra

Les niveaux d’effort sont relatifs au seul modèle : faible = rendu simple ; moyen =
plusieurs variantes ou interaction ; élevé = structure imbriquée et styles transversaux.
Ils ne constituent pas une estimation de durée.

| Famille | Noms techniques et occurrences | Pages | Adaptation SPIP envisagée | Effort |
|---|---|---|---|---|
| Conteneurs et colonnes | `spectra/container` ×111 | Toutes | Structure imbriquée conservant enfants, largeur, fonds et règles de disposition ; rendu par squelettes ou transformation structurée | Élevé |
| Textes et titres | `spectra/content` ×105 | Toutes | Extraction de `text`, restitution de la balise et des styles ; conversion typographique sans perdre la hiérarchie des titres | Moyen |
| Séparateurs | `spectra/separator` ×16 | Home, About, Services, Projects, Contact | Modèle ou HTML `<hr>` avec largeur, épaisseur, couleur et alignement | Faible |
| Boutons | `spectra/buttons` ×10 ; `spectra/button` ×10 | Home, About, Services, Projects | Groupe et lien stylé, icône éventuelle, états de survol et réglages adaptatifs ; réutilisation des conventions Astra | Moyen |
| Icônes | `spectra/icons` ×5 ; `spectra/icon` ×8 | Home, Contact | SVG et liens accessibles ; mutualiser un registre d’icônes avec le builder quand possible | Faible à moyen |
| Listes à icônes | `spectra/list` ×4 ; `spectra/list-child-item` ×12 ; `spectra/list-child-icon` ×12 | Services, Contact | Un composant liste avec entrées et icônes, pas trois composants éditoriaux distincts | Moyen |
| Accordéon FAQ | `spectra/accordion` ×1 ; chacun des cinq types enfants ×5, voir ci-dessous | FAQ | Cinq éléments question/réponse ; adapter l’exemple FAQ de Tiptap pour l’édition, ajouter un rendu public repliable | Moyen |
| Compteurs | `spectra/counter` ×4 ; `spectra/counter-child-wrapper` ×4 ; `spectra/counter-child-number` ×4 | About | Valeur finale et suffixe rendus côté serveur ; animation progressive facultative selon fidélité recherchée | Moyen |
| Carte | `spectra/google-map` ×1 | Contact | Un composant carte conservant adresse, hauteur et zoom ; vérifier le mode d’intégration exact dans le rendu WordPress | Faible à moyen |

Les cinq types enfants de l’accordéon sont `spectra/accordion-child-item`,
`spectra/accordion-child-header`, `spectra/accordion-child-header-content`,
`spectra/accordion-child-header-icon` et `spectra/accordion-child-details`.
L’accordéon représente **26 blocs techniques**, les compteurs **12**, les listes **28**.

### Variantes réellement présentes

- **Conteneurs :** 21 avec disposition `constrained`, 69 `flex`, 4 `grid`, 4 `default`
  et 13 sans attribut `layout`. Les grilles explicites comportent trois ou quatre colonnes.
  Cinq conteneurs portent un fond de type image. Les autres couleurs et effets peuvent
  aussi se trouver dans `style` : ce chiffre ne compte pas tous les fonds visuels.
- **Textes :** les attributs explicitent 6 `h1`, 15 `h2`, 5 `h3`, 7 `h4`, 24 `h5`,
  3 `h6` et 28 `p`. Les 17 autres textes n’ont pas de `tagName` : résoudre leur balise
  par défaut dans les sources Spectra avant conversion.
- **Boutons :** dix liens enregistrés comme `#`, dont deux avec un attribut `icon`
  (`arrow-right` ou `arrow-right-long`). Des attributs d’icône au survol existent aussi.
  Les destinations finales devront être attribuées ; elles ne peuvent pas être déduites
  de ces liens factices.
- **Icônes isolées :** `leaf`, `certificate`, `wallet`, `play`, `twitter`, `facebook-f`,
  `pinterest`, `linkedin`. Ajouter `circle-check`, `plus`, `minus` et les flèches pour
  couvrir les listes, l’accordéon et les boutons. Une icône `play` ne prouve pas la présence
  d’un lecteur vidéo : aucun bloc vidéo n’est enregistré dans les pages publiées.
- **Animations :** 64 blocs de pages portent `spectraAnimationOnce=true`. Ce seul
  drapeau ne permet pas de déduire une animation effective, son type ou son déclenchement.
  Ces paramètres doivent être confirmés par le rendu de référence.

## Le socle commun à porter avant les effets

### Conversion des données et rendu

Les 105 `spectra/content`, les dix `spectra/button` et les huit `spectra/icon` sont
des blocs autofermants : leurs données sont dans les attributs JSON, sans HTML de secours.
Les conteneurs enregistrent leurs enfants sans fournir à eux seuls tout le HTML de rendu.
Conserver seulement le HTML existant ou supprimer les commentaires Gutenberg ferait perdre
des contenus et leur structure.

Le convertisseur local `wp2spip` expose déjà le pipeline `wp2spip_bloc`, auquel sont
transmis le bloc, ses attributs et le type. Son repli pour un bloc inconnu conserve
l’intérieur du bloc ; il ne reconstitue pas le texte stocké exclusivement dans un attribut.
Une extension de conversion doit donc prendre en charge explicitement `spectra/*`.
Le pipeline est un point d’entrée existant ; le partage du contexte de conversion des
médias et des enfants doit encore être défini pour cette extension.

### Styles adaptatifs

Prévoir un traitement commun de `style`, `layout`, `responsiveControls`, `spectraId`,
des couleurs et des références de médias. Les variantes `lg`, `md`, `sm` sont largement
utilisées, y compris sur les images natives. Les seuils et règles d’héritage doivent être
relevés dans la version Spectra de référence : ne pas les assimiler automatiquement aux
seuils du header Astra.

Les propriétés observées incluent : marges, espacements internes, écart entre enfants,
orientation et alignement flex, colonnes de grille, largeurs et hauteurs, recadrage d’image,
bordures et rayons, ombres, couleurs, gradients, typographie et états de survol.
Le traitement des variantes et des identifiants ne doit pas écraser le style d’un autre bloc.

Le thème demande **Raleway** pour le texte et **Poppins** pour les titres ; les attributs
des blocs mentionnent aussi **Inter** et Poppins. Saisir une pile de polices dans Astra SPIP
ne télécharge pas les fontes : prévoir les fichiers et les graisses nécessaires ou accepter
une différence visuelle. Les fontes et les icônes ont leurs propres licences à relever.

La palette Astra comporte une neuvième valeur en `rgba(64,191,79,0.15)` ; les réglages de
couleur du portage actuel acceptent des couleurs hexadécimales. Prévoir sa représentation
avec transparence ou documenter une adaptation. Le CSS générique `wp-block-*` ne couvre pas
à lui seul les styles `spectra/*`.

### Pages, médias et liens

- Fournir une page d’accueil statique et des pages uniques sans le cartouche de titre,
  les métadonnées de blog, les commentaires et le conteneur étroit d’un article ordinaire.
- Conserver les 19 images natives des pages, leur recadrage, leurs dimensions et leurs
  styles adaptatifs. Une image supplémentaire appartient à la composition réutilisable.
- Le WXR déclare 44 pièces jointes, mais ce ne sont pas 44 photographies nécessaires :
  il contient aussi des logos, une capture et un fichier XML. Identifier les médias
  effectivement référencés par les pages, fonds, widgets et options avant import.
- Réécrire les ID et URL de médias, liens de pages, menus et formulaires ; les adresses
  historiques mentionnent plusieurs variantes du site de démonstration.

## Formulaire de contact : remplacement ciblé de SureForms

La page Contact inclut un shortcode qui référence le formulaire WordPress **1514**.
Celui-ci est un objet `sureforms_form`, distinct des pages et composé de quatre champs
obligatoires : nom, adresse électronique, sujet et message. Les types enregistrés sont
`srfm/input` ×2, `srfm/email` ×1 et `srfm/textarea` ×1.

Un formulaire CVT SPIP peut remplacer ce besoin sans porter SureForms dans son ensemble.
Il faut préciser le destinataire, la validation, l’envoi, les messages de succès et
d’erreur ainsi que la protection contre les envois abusifs. Le WXR contient des réglages
SureForms supplémentaires ; son contenu seul ne démontre pas le comportement réel d’envoi.
Le shortcode doit être remplacé, pas simplement conservé comme texte.

## En-tête, pied et éléments hors des six pages

| Élément observé | Déjà disponible dans Astra SPIP | Adaptation restante |
|---|---|---|
| Header ordinateur : logo, menu principal, bouton | Oui, éléments du builder | Importer les placements, dimensions et styles ; affecter le menu des six pages dans l’ordre voulu |
| Header mobile : logo, bouton d’ouverture, panneau déroulant contenant `menu-1` et le bouton | Oui, registre et panneau | Importer les placements explicites, notamment l’absence de `mobile-menu` dans ce panneau |
| Pied principal à quatre widgets | Oui, structure de rangée | Reconstituer le contenu des quatre zones et leurs styles |
| Empilement du pied principal dès la tablette | Partiel | Le modèle demande `tablet=full`, alors que le builder actuel reprend la répartition ordinateur en tablette |
| Pied bas : copyright et réseaux sociaux | Oui | Importer textes, couleurs, répartition et liens |
| Widget 1 : informations de contact | Bloc texte | L’export contient deux variantes texte ; confirmer ce qui est réellement affiché |
| Widgets 2 et 3 : deux menus différents | Pas de bloc de menu dans `astra_hfb_blocs()` | Ajouter un bloc menu de widget ou un équivalent ; `pages` seul ne conserve pas une sélection et un ordre arbitraires |
| Widget 4 : logo, texte et HTML | Texte disponible ; image à adapter | Un bloc historique `uagb/image`, un widget image et plusieurs variantes de texte sont enregistrés ; confirmer les éléments rendus avant de les cumuler |

Le WXR contient **16 entrées de menus** : six dans le menu principal, cinq dans un menu
de liens rapides et cinq liens personnalisés `#` dans un menu de services. Les destinations
des services restent à définir. Le builder actuel ne propose pas une source permettant
d’importer telle quelle toute cette structure de menus et de liens personnalisés.

Le widget `uagb/image` peut devenir un modèle image SPIP avec dimensions et alignement :
il ne justifie pas, à lui seul, le portage complet des anciens blocs UAGB.

Une composition `wp_block` **1515**, « Footer Call To Action », contient huit blocs natifs :
`core/group` ×1, `core/image` ×1, `core/heading` ×3, `core/separator` ×1,
`core/buttons` ×1 et `core/button` ×1. Aucune référence `core/block` n’a été trouvée
dans les six pages ; son affichage effectif reste à confirmer. Ne pas l’ajouter
automatiquement au pied simplement parce qu’elle existe dans l’export.

Les widgets de la barre latérale incluent aussi recherche, derniers articles et commentaires,
archives, catégories et liens système. Ils ne font pas partie des neuf familles Spectra
des pages. Confirmer leur utilisation sur les pages pleine largeur avant d’élargir le portage.

## Dépendances : ce qui est nécessaire pour ce modèle

| Dépendance WordPress déclarée | Besoin concret dans SPIP |
|---|---|
| Spectra (`ultimate-addons-for-gutenberg`) | Convertir les neuf familles et leurs styles ; pas nécessaire de porter tout l’éditeur Gutenberg |
| SureForms | Remplacer un formulaire de quatre champs et son inclusion |
| SureRank SEO | Aucune famille de bloc de page propre à SureRank observée ; traiter séparément les métadonnées SEO, sans en faire une dépendance du rendu |
| Astra | Réutiliser le portage et le builder, compléter les réglages identifiés ci-dessus |

Les noms déclarés dans les métadonnées ne prouvent ni que toutes les fonctions de ces
plugins sont utilisées, ni que leur seul code suffit à générer une référence fidèle.
Les licences des sources et ressources de la version effectivement retenue doivent être
consignées avant reprise ; celles des photographies et textes du modèle restent distinctes.

## Ordre de travail proposé

| Lot | Contenu | Critère de sortie |
|---|---|---|
| 1 — Référence et pages | Identifier la version qui rend `spectra/*`, établir le rendu WordPress isolé ; pages uniques et accueil statique | Six pages identifiées, référence de chaque page sur ordinateur, tablette et mobile |
| 2 — Socle statique | Conversion des conteneurs, textes, images, séparateurs, boutons, icônes et listes ; moteur de styles commun | Aucun texte perdu ; structure et disposition correctes sur les six pages |
| 3 — Interactions et contact | FAQ, compteurs, carte, formulaire CVT | Composants utilisables au clavier ; contenu final des compteurs disponible sans animation ; formulaire validé jusqu’à l’envoi de test |
| 4 — Habillage du site | Configuration Astra, menus des widgets, image du pied, palette, fontes et styles des rangées | Header et pied cohérents avec les pages aux trois tailles |
| 5 — Édition et fidélité | Extensions Tiptap nécessaires, aller-retour source/visuel, comparaisons | Une réédition conserve les structures et styles ; différences visuelles mesurées sur chaque page |

Le lot 2 ne doit pas se limiter à charger du CSS : les blocs autofermants nécessitent
une conversion des données. Le lot 5 distingue **afficher un contenu importé** et **pouvoir
le modifier visuellement**. L’exemple `tiptap_spip_faq` est un point de départ d’extension,
pas un accordéon public déjà équivalent à Spectra. Aucun de ces lots n’exige de modifier
le cœur de `tiptap_spip`.

Les cartes de services, témoignages, appels à l’action et compositions de projets peuvent
être reconstruits par assemblage des familles recensées. Aucun bloc dédié de carrousel,
galerie, vidéo, boutique ou boucle de publications n’apparaît dans les six pages publiées.
Ne pas les ajouter au périmètre sans preuve dans le rendu de référence.

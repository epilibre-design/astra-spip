# Site Garden Maintenance pour SPIP — script d'installation

Date : 2026-10-22. Statut : à relire. Choix tranchés par Gilles le 2026-10-22 : dépôt séparé,
contenu de substitution, tiptap_spip installé, formulaire de contact CVT. Aucun code n'est écrit
avant validation de la spec.

## Objectif

Installer depuis zéro, en une commande relançable, un site SPIP 4.4 qui reproduit le modèle de
démarrage Astra **Garden Maintenance** (id 189117), sur le modèle de `/src/site-tuto/bin/install.sh`.
Le script grandit avec le portage : chaque chantier d'Astra SPIP terminé ajoute ce qu'il permet
de reproduire.

## Le modèle : `site-tuto/bin/install.sh`

Ce qui est repris tel quel :

- étapes numérotées, chacune **sautée si déjà faite** : le script peut être relancé ;
- spip-cli : `SPIP_CLI` s'il est fourni, sinon clone de spip-cli avec la MR 91
  (`refs/merge-requests/91/merge`) dans `.outils/`, `composer install --no-dev` ;
- cœur SPIP 4.4 : zip officiel (`files.spip.net/spip/stable/spip-4.4.zip`) décompressé à la racine
  du dépôt, sans écraser les fichiers Git (`spip dl` refuse un répertoire non vide) ;
- installation SQLite par `core:installer`, compte administrateur depuis `ADMIN_LOGIN`,
  `ADMIN_PASS` (aléatoire et affiché s'il manque), `ADMIN_EMAIL`, `ADRESSE_SITE` ;
- plugins : dépôt SVP `files.spip.net/spip-zone/archives.xml`, `plugins:svp:telecharger` pour chaque
  préfixe absent de `plugins/auto/`, activation de tous les plugins téléchargés et du plugin du
  site, `plugins:maj:bdd`, puis `maj_tables()` sur les tables principales (contournement d'un
  plugin installé dans le même processus que son téléchargement) ;
- droits ouverts sur `config`, `IMG`, `local`, `tmp`, `plugins/auto` (PHP et ligne de commande
  écrivent tous les deux) ;
- contenu créé par une commande spip-cli du plugin du site (`tuto:seed` → `garden:seed`).
  **Écarts avec le modèle** : le seed de site-tuto réécrit les métas et la configuration à chaque
  passage (`inc/tuto_seed.php`) et remplace les contenus avec `--maj` ; Garden ne réécrit jamais un
  réglage ou un contenu personnalisé, et le remplacement forcé passe par `--reinitialiser` (voir
  « Relances et personnalisations ») ;
- `cache:vider`, puis affichage de l'adresse et du compte créé.

Points relevés pendant le chantier du builder, à vérifier dans ce contexte :

- `plugins:activer` ne lance pas l'installation des tables d'un plugin : `plugins:maj:bdd` est
  obligatoire (déjà présent dans le modèle) ;
- dans l'environnement d'intégration d'Astra SPIP, `plugins:svp:telecharger pages` a échoué sous
  SQLite avec le dépôt `plugins.spip.net/depots/principal.xml` (« n'est pas référencé ») ;
  site-tuto utilise `archives.xml` et fonctionne : le script reprend ce dépôt et vérifie chaque
  plugin après activation (`test_plugin_actif`), car `plugins:activer` peut sortir en code 0
  sans rien activer.

## Organisation du dépôt

Retenu : **un dépôt de site distinct**, organisé comme site-tuto. Astra SPIP reste un thème
générique, sans contenu de démonstration.

```
garden-spip/
  bin/install.sh            # le script
  plugins/garden/           # plugin du site
    paquet.xml              # necessite astra, pages ; utilise a2a
    spip-cli/GardenSeed.php # commande garden:seed
    inc/garden_seed.php     # configuration Astra et contenu
    seed/                   # configuration du modèle transposée (YAML ou PHP), sans texte ni photo du modèle
  .gitignore                # cœur SPIP, plugins/auto, config, IMG, local, tmp, .outils
```

Astra SPIP est installé par le script : lien symbolique vers un dépôt local (`ASTRA_SPIP`), sinon
clone de `github.com/epilibre-design/astra-spip` (pas encore publié sur SPIP Zone).

Alternative écartée : un script de démonstration dans Astra SPIP (`demo/garden/install.sh`). Il
mêlerait au thème la configuration d'un modèle particulier.

## Relances et personnalisations

Un site installé est ensuite personnalisé (palette, menus, textes des pages). Relancer le script,
pour réparer une installation ou passer au lot suivant, ne doit pas défaire ces changements ; mais
un contenu que personne n'a touché doit recevoir les enrichissements des lots suivants (le lot 4
enrichit les six pages créées au lot 1).

### Empreintes

Pour chaque valeur qu'il fournit, le seed enregistre l'empreinte (SHA-256) de **la dernière valeur
fournie** :

- réglages : une empreinte par clé de configuration (`astra/couleur_0`, `astra/hfb`…) ;
- objets : une empreinte par champ fourni (`titre`, `texte`, `descriptif`… d'une page, entrées d'un
  menu), avec l'identifiant de l'objet créé.

Stockage : méta `garden`, sous-clés `seed/version` (dernier lot appliqué), `seed/objets/<clé>`
(identifiant SPIP) et `seed/empreintes/<clé>/<champ>`.

En comparant la valeur actuelle à cette empreinte, le seed sait si la valeur est **intacte**
(identique à ce qu'il a fourni) ou **personnalisée** (modifiée depuis).

### Opérations

| Opération | Quand | Effet |
|---|---|---|
| **Création initiale** | objet ou réglage jamais créé (aucun identifiant ni empreinte enregistrés) | crée et enregistre identifiant et empreintes ; c'est aussi ce qui reprend une installation interrompue |
| **Migration** | passage avec un lot plus récent que `seed/version`, étapes des lots manquants appliquées dans l'ordre | ajoute ce que le lot introduit ; pour une valeur que le lot modifie : **intacte** → remplacée par la nouvelle valeur, empreinte mise à jour ; **personnalisée** → conservée, mise à jour signalée (voir ci-dessous) ; met à jour `seed/version` |
| **Réapplication** | seulement sur demande : `garden:seed --reinitialiser[=<partie>]` (parties : `config`, `pages`, `menus`…), après confirmation (ou `-y`) | remplace les valeurs de la partie par celles du lot courant, personnalisées comprises, recrée les objets supprimés ; empreintes mises à jour |

Une relance au même lot ne modifie rien.

**Objets supprimés.** Un objet dont l'identifiant est enregistré mais qui n'existe plus a été
supprimé après sa création : il n'est **jamais recréé** par une relance ou une migration, seulement
par `--reinitialiser`. Seul un objet jamais créé (aucun identifiant enregistré) est créé
automatiquement.

**Signalement.** Les valeurs personnalisées pour lesquelles un lot propose une nouvelle version,
et les objets supprimés que ce lot aurait modifiés, sont listés en fin de script (clé, champ, lot)
et enregistrés dans `seed/en_attente`, pour pouvoir les appliquer ensuite une par une avec
`--reinitialiser=<clé>`.

## Version d'Astra SPIP requise

Chaque lot déclare la version minimale d'Astra SPIP dont il a besoin (accueil statique, réglages par
page, évolutions du builder). Le plugin `garden` l'exprime dans `paquet.xml`
(`<necessite nom="astra" compatibilite="[x.y.z;]" />`) et le seed la vérifie avant d'écrire la
moindre configuration : si l'Astra installé est trop ancien, le script s'arrête en code 1 avec la
version trouvée et la version requise, sans rien écrire. Un plugin actif ne suffit pas : un Astra
trop ancien ignorerait les réglages enregistrés.

Côté installation, `ASTRA_SPIP` (dépôt local) ou le clone GitHub sont suivis d'une vérification de
la version de `paquet.xml` ; le clone utilise une étiquette de version (`ASTRA_SPIP_REF`, par défaut
celle requise par le dernier lot), pas la branche `main`. Astra SPIP numérote donc ses versions à
chaque fonction dont dépend un lot (aujourd'hui : builder = version actuelle du `paquet.xml`).

## Ce que le script configure

Les valeurs viennent de `site.json` du modèle (données du personnalisateur et options), relevées
le 2026-10-22. Le plugin du site les transpose dans la configuration d'Astra SPIP ; il ne copie
aucun texte ni aucune image du modèle.

| Réglage Astra du modèle | Valeur | Dans Astra SPIP | État |
|---|---|---|---|
| `global-color-palette` | `#40bf4f`, `#0d3c00`, `#0d3c00`, `#5a5a5a`, `#f2f2f2`, `#ffffff`, `#f3f2ed`, `#222821`, `rgba(64,191,79,0.15)` | `astra/couleur_0` … `couleur_8` | 8 couleurs prises en charge ; la 9e, transparente, n'est pas une couleur hexadécimale : à prendre en charge dans le formulaire du thème |
| `body-font-family`, `headings-font-family` | Raleway 400, Poppins 600 | `astra/police_texte`, `police_titres` | La pile est appliquée mais les fontes ne sont pas chargées : à fournir (fichiers ou Google Fonts, licence OFL à consigner) |
| `site-content-width` | 1200 | `astra/largeur_conteneur` | Disponible |
| `site-layout` | `ast-full-width-layout` | — | À vérifier dans le portage |
| `header-desktop-items` | logo à gauche ; `menu-1`, `button-1` à droite | `astra/hfb/entete_ordinateur` | Disponible |
| `header-mobile-items` | logo, `mobile-trigger` ; panneau : `menu-1`, `button-1` | `astra/hfb/entete_mobile` | Disponible (registre : `menu-1` et `button-1` autorisés dans le panneau) |
| `mobile-header-type` | `dropdown` | `astra/hfb/menu_mobile` | Disponible |
| `header-button1-*` | libellé et lien factice `#` | `elements/entete/button-1` | Disponible ; libellé et lien à écrire pour le site (pas ceux du modèle) |
| `footer-desktop-items` | widgets 1–4 dans la rangée principale ; copyright et icônes sociales en bas | `astra/hfb/pied` | Disponible |
| `hb-footer-layout` | `4-equal`, **tablette `full`** | `rangees/pied_primary` | Manque : le builder reprend la répartition ordinateur sur tablette |
| `footer-social-icons-1` | Facebook, Twitter, LinkedIn, **Google Reviews** | `elements/pied/social-icons-1` | Google Reviews absent du registre des réseaux |
| Widgets 2 et 3 du pied | deux menus | `elements/pied/widget-N` | Manque : pas de bloc « menu » dans les widgets |
| `custom_logo` | logo SVG du modèle | logo du site | Image du modèle : non versionnée (voir Contenu) |
| `show_on_front`, `page_on_front` | page statique « Home » | — | Manque : accueil statique (spec pages uniques, A3) |
| Réglages des pages | titre masqué, pleine largeur | — | Manque : réglages par page (spec pages uniques, A4) |

## Contenu

Le modèle compte six pages (Home, About, Services, Projects, FAQ, Contact), trois menus, quatre
widgets et 44 pièces jointes (inventaire : `docs/inventaires/garden-maintenance.md`). Règle du
projet : les textes et photos des modèles ne sont jamais versionnés.

Retenu : **contenu de substitution**. Le seed (`plugins/garden/seed/`, versionné) crée les six
pages uniques, les menus et les widgets avec des textes écrits pour le site, en reprenant la
structure des pages du modèle (sections, colonnes, listes, FAQ, compteurs) mais pas ses textes.
Images : aucune au lot 1 ; ensuite, seulement des images libres dont l'auteur, la source et la
licence sont consignés dans le seed. Le site est montrable et redistribuable sous les licences du
dépôt.

Écarté pour l'instant : **le contenu du modèle téléchargé à l'installation** (export WXR et médias
récupérés sur websitedemos.net, non versionnés). Il supposerait :
   - vérifier que les conditions d'utilisation des contenus de démonstration de Brainstorm Force
     le permettent hors de WordPress ;
   - un chemin d'import : wp2spip lit une base WordPress, pas un fichier WXR. Il faudrait soit un
     WordPress temporaire où importer le WXR avant wp2spip, soit un lecteur de WXR dans le plugin
     du site ;
   - la conversion des blocs Spectra, sans laquelle la quasi-totalité des textes est perdue (ils
     sont stockés dans les attributs des blocs).

Cette piste ne pourrait être rouverte qu'après la conversion Spectra.

## Dépendances

| Plugin | Rôle | Licence | Source |
|---|---|---|---|
| astra | thème | GPL v3 | dépôt local ou GitHub |
| pages | pages uniques | GPL v3 | SPIP Zone |
| a2a | sous-pages (si le contenu en a) | à relever dans son `paquet.xml` | SPIP Zone |
| tiptap_spip | éditeur, installé dès le lot 1 | GPL v3 | SPIP Zone (`plugins:svp:telecharger`), ou dépôt local via `TIPTAP_SPIP` |

tiptap_spip n'est pas modifié. Les besoins d'édition propres au modèle (FAQ, compteurs, sections)
seront des extensions dans le plugin du site ou un plugin dédié, sur le modèle de
`/src/tiptap-spip/docs/site/developper/faq.md`, au lot 4.

**Formulaire de contact** : formulaire CVT `contact` du plugin du site (champs du modèle : nom,
adresse électronique, sujet, message, tous obligatoires), sans plugin de formulaires. Il est
inséré dans la page Contact par un modèle SPIP (`<formulaire|contact>`). À préciser dans la spec du
lot correspondant : destinataire (`email_webmaster` par défaut), envoi par `notifications_envoyer_mails`
ou `envoyer_mail`, messages de succès et d'erreur, protection contre les envois abusifs (champ
piège et limite de fréquence, ou plugin nospam s'il est retenu).

## Lots

Le script est livré dès le lot 1 et enrichi à chaque lot.

| Lot | Contenu du script | Dépend de |
|---|---|---|
| 1 — Socle | spip-cli, SPIP 4.4, SQLite, plugins (pages, a2a, tiptap_spip), astra ; palette (8 couleurs), largeur, en-tête et pied ; six pages de substitution et menu principal | builder (fait) |
| 2 — Pages | accueil statique, pages sans titre et pleine largeur | spec pages uniques (A3, A4) |
| 3 — Habillage | 9e couleur, fontes, répartition tablette du pied, menus dans les widgets, Google Reviews | évolutions du builder et du formulaire du thème |
| 4 — Contenu | sections de substitution (colonnes, listes, FAQ, compteurs, carte), formulaire de contact CVT, extensions d'édition | CSS des blocs ; composants à définir (spec dédiée) |

## Validation

- Depuis un dossier vide : le script se termine sans erreur et affiche le compte créé ; le site
  public répond, avec l'en-tête et le pied attendus.
- Relance immédiate : aucune étape refaite, aucun objet en double.
- Personnalisations conservées : sur un site installé, modifier le texte d'une page, une couleur de
  la palette et un placement du builder, relancer le script, vérifier que les trois sont intacts.
- Passage d'un lot au suivant : installer au lot N, personnaliser une page, en laisser une autre
  intacte, supprimer une troisième, puis relancer avec le lot N + 1 qui modifie les trois :
  - la page intacte reçoit le contenu du lot N + 1 ;
  - la page personnalisée est conservée, et sa mise à jour est signalée et listée dans
    `seed/en_attente` ;
  - la page supprimée n'est pas recréée, et sa mise à jour est signalée ;
  - un nouvel objet du lot N + 1 est créé, et la version enregistrée est N + 1.
- Installation interrompue : un premier passage arrêté après la création de trois pages, relancé,
  crée les trois autres sans doublon.
- Réapplication : `garden:seed --reinitialiser=config` remet la palette de démonstration et ne
  touche pas aux pages.
- Astra trop ancien : avec une version d'Astra SPIP inférieure à celle requise, le script s'arrête
  en code 1 sans écrire de configuration.
- Lot 4 : le modèle qui insère le formulaire de contact (`<formulaire|contact>`) est fourni par
  `garden` et le formulaire s'affiche dans la page Contact ; ouvrir chaque page dans tiptap_spip
  puis l'enregistrer sans modification laisse son texte et ses composants identiques
  (comparaison du champ `texte` avant et après). Installer l'éditeur ne prouve pas cet aller-retour.
- Vérifications automatiques en fin de script : plugins actifs (`test_plugin_actif`), configuration
  `astra/hfb` relue, six pages publiées.
- Comparaison visuelle avec le site de démonstration (`websitedemos.net/garden-maintenance-09`)
  sur ordinateur, tablette et mobile : en-tête et pied d'abord, pages à partir du lot 4. Les
  écarts dus aux manques listés ci-dessus sont consignés, pas masqués.

## Décisions

Le 2026-10-22, Gilles a retenu :

1. dépôt séparé, organisé comme site-tuto ;
2. contenu de substitution, aucun texte ni photo du modèle ;
3. tiptap_spip installé dès le lot 1 ;
4. formulaire de contact CVT propre au site.

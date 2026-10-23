# Mesures après fusion — Astra-spip v1.2.0

Date : 23 octobre 2026. Mesures exécutées sur `main` fusionné, commit
`7e3dde8b34528658340c68c35aa0ccbb6ea5226f`, également cible du tag annoté `v1.2.0`.
Le checkout mesuré est resté propre ; les scripts et les feuilles CSS proviennent de ce commit.
L’arbre Git est aussi celui de la tête revue de la PR #7 (`0020e6b`) :
`b5ce9bd47f45fb6a1d231519755b25472eda7c8c`. Les résultats ci-dessous sont de nouvelles
exécutions sur le commit fusionné, pas une reprise des résultats de la branche.

## Résultat

| Contrôle | Périmètre | Résultat |
|---|---|---|
| Captures R0–R11 et P1–P3 | 81 paires à 1280 et 390 px | succès ; maximum 0,35 %, R11, pied sur ordinateur |
| Styles calculés | 321 valeurs, 21 groupes à 1280, 768 et 390 px | aucune différence |
| Polices | 4 contrôles de chargement, WordPress et SPIP, R7 et R11 | succès ; aucun appel Google Fonts détecté par le contrôleur |
| Structure HTML prévue par le comparateur | R0–R5 et P1–P3, 33 comparaisons | identique ; code de sortie 0 |
| Structure HTML étendue volontairement | ajout de R6–R11 | 7 différences documentées ci-dessous ; code de sortie 1 |
| Blocs importés : captures | 24 paires comparables, 12 par largeur 1280/390 px | zéro pixel différent après les masquages documentés |
| Pertes d’import caractérisées | 4 propriétés par largeur, 8 contrôles | succès |
| Cascade CSS des blocs | 67 descendants × 21 propriétés × 2 largeurs (1280/600 px) | 2 814 valeurs égales |
| Médias couverture et média/texte | 2 comparaisons SHA-256 | fichiers identiques |

Le seuil des captures est de 1 % de pixels différents (pixelmatch, seuil par pixel 0,1),
avec alignement vertical recherché à ±2 px et dimensions tolérées à ±2 px. Les pourcentages
R/P ci-dessous sont ceux arrondis par l’outil à deux décimales. Les styles calculés sont
comparés par égalité exacte, avec survol, focus et sous-menu ouvert pour les références qui
les demandent. Le contrôleur attend le chargement des polices. À 768 px, il mesure les styles
calculés ; les captures d’images de ce lot sont à 1280 et 390 px.

## Captures par configuration

| Configuration | Paires de captures | Écart maximal mesuré | Résultat |
|---|---:|---:|---|
| R0-defaut | 5 | 0.00 % | succès |
| R1-entete-complet | 5 | 0.00 % | succès |
| R2-entete-centre | 5 | 0.00 % | succès |
| R3-pied-colonnes | 5 | 0.17 % | succès |
| R4-pied-asymetrique | 5 | 0.11 % | succès |
| R5-rangees | 5 | 0.00 % | succès |
| R6-menu | 5 | 0.16 % | succès |
| R7-bouton | 5 | 0.16 % | succès |
| R8-identite | 5 | 0.16 % | succès |
| R9-pied-couleurs | 5 | 0.13 % | succès |
| R10-sociaux | 5 | 0.16 % | succès |
| R11-combinee | 5 | 0.35 % | succès |
| P1-page | 7 | 0.00 % | succès |
| P2-accueil | 7 | 0.00 % | succès |
| P3-page-pleine | 7 | 0.00 % | succès |

## Différences HTML relevées par l’extension du contrôle

Le comparateur `comparer_html.php` exclut R6–R11 de son périmètre par défaut. Une exécution
supplémentaire les incluant explicitement a été conservée, sans neutraliser ses différences :

- **R6–R11, pied : six différences de classe.** WordPress produit
  `ast-builder-grid-row-tablet-full`, SPIP `ast-builder-grid-row-tablet-4-equal`. La configuration
  WordPress des styles contient la répartition tablette du modèle Garden ; la version 1.2.0
  utilise encore une répartition scalaire du builder. La répartition par appareil est prévue
  par la spec « Compléments du builder » (PR #5). Les captures de ce lot ne démontrent donc
  pas l’équivalence visuelle du pied à 768 px ; les propriétés contrôlées à cette largeur
  restent celles énumérées dans les JSON des références.
- **R11, contenu : une différence de fixture/périmètre.** La présence des polices dans la clé
  `astra` déclenche aussi la comparaison de `#content` du blog. Les fixtures n’ont pas les
  mêmes articles, catégories, auteurs et balisages de dates. Cette comparaison n’est pas une
  preuve d’équivalence du contenu éditorial de l’accueil. Les captures R11 ciblent l’en-tête,
  le pied et le menu mobile ; elles ne capturent pas ce contenu.

Le journal étendu reste en échec (`html.exit = 1`). Le journal nominal est distinct et passe
(`html-perimetre-prevu.exit = 0`). Les écarts ne sont ni filtrés ni corrigés dans la version
publiée par cette opération de mesure. Les arbres Git de la tête revue et de la fusion étant
identiques, ces différences ne sont pas introduites par le merge.

## Blocs importés

Les fixtures de blocs déjà importées ont été copiées dans des sites isolés et rendues avec
Astra-spip `7e3dde8`. Il s’agit d’une nouvelle mesure du rendu de cet import, pas d’un nouvel
import wp2spip. Le jeu initial utilisait wp2spip `e3c262720b518bc7007e5f37779a9d6ca84f17f1`.

À chaque largeur, 15 blocs sont appariés : 12 sont comparés en pixels ; séparateur, code et
vers sont contrôlés par leurs pertes de conversion. La couche d’assombrissement manquante de
la couverture est masquée conformément au protocole existant. Les quatre pertes restent :
filet SPIP pour le séparateur, absence d’assombrissement, code en zone de texte, vers en
blockquote. Les 24 paires comparables ont un écart brut exactement nul, confirmé depuis les
PNG ; cela ne signifie pas que les quatre pertes d’import ont disparu.

84 PNG sont conservés pour les blocs : 30 WordPress, 30 SPIP et 24 différences.

## Environnement et isolation

- SPIP 4.4.28 ; WordPress 7.1.3 ; thème Astra 4.11.0.
- PHP 8.4.27 ; Node 24.11.1 ; Chrome for Testing 149.0.7827.55 ; fuseau Europe/Paris.
- Sites R/P copiés dans `/tmp/astra-main-mesures-sites-20261023`, bases SQLite indépendantes,
  ports WordPress 8461 et SPIP 8464.
- Sites de blocs copiés dans `/tmp/astra-main-blocs-20261023`, SQLite SPIP privée et tables
  WordPress au préfixe distinct `wpblocs_main20261023_` dans le schéma de développement,
  ports 8465 et 8466. Les tables d’origine restent inchangées.
- Chaque SPIP charge effectivement le plugin depuis `/tmp/astra-main-mesures-20261023`,
  checkout détaché propre au commit publié. Aucun fichier produit, CSS ou seuil de test n’a
  été modifié pour obtenir ces résultats.

## Preuves et reproduction

Les journaux et les mesures JSON sont conservés dans
[mesures-v1.2.0/](mesures-v1.2.0/) :

- [identité et empreintes du code mesuré](mesures-v1.2.0/provenance.json) ;
- [mesures R/P par configuration](mesures-v1.2.0/mesures-rp.json),
  [journal des captures](mesures-v1.2.0/captures.log) ;
- [HTML nominal](mesures-v1.2.0/html-perimetre-prevu.log) et
  [HTML étendu avec les différences](mesures-v1.2.0/html.log) ;
- [mesures brutes des blocs](mesures-v1.2.0/blocs/screenshot-measures.json),
  [journal des blocs](mesures-v1.2.0/blocs/comparer_blocs.log),
  [médias](mesures-v1.2.0/blocs/comparer_medias.log) et
  [cascade](mesures-v1.2.0/blocs/cascade_blocs.log) ;
- [commandes R/P](mesures-v1.2.0/run-rp.sh) et
  [commandes des blocs](mesures-v1.2.0/blocs/reproduce-checks.sh).

Le script R/P inclut volontairement le contrôle HTML étendu, qui sort en code 1 pour les
écarts décrits ci-dessus. Pour reproduire le contrôle HTML nominal, lancer le même
`comparer_html.php` avec `SPIP_CLI` et `REFERENCES`, sans liste de configurations en arguments.

Les références HTML/CSS, captures et différences complètes sont conservées dans
[l’archive des preuves](mesures-v1.2.0/preuves.tar.gz), avec son
[empreinte SHA-256](mesures-v1.2.0/archive.sha256). Cette archive a été produite à la fin
des mesures ; son rapport interne conserve les chemins temporaires d’origine.
Les journaux et le présent rapport sont aussi accessibles directement dans cette branche.

### Incident de capture auxiliaire

Après le premier passage complet des références WordPress (code 0), un contrôle supplémentaire
plein écran a rencontré une erreur Chromium `Page.captureScreenshot: Unable to capture screenshot`
sur R7. La reprise de R7 avec le même script et les mêmes paramètres a réussi ; les trois PNG
de cette référence sont présents. La cause de cette erreur de protocole n’est pas établie.
Cet incident concerne le captureur auxiliaire de pages entières ; le lot des 81 comparaisons
R/P s’est terminé en code 0. Les journaux de l’incident et de la reprise sont conservés dans
`mesures-v1.2.0/references-final.log` (code 1) et
`mesures-v1.2.0/references-r7-reprise.log` (code 0). Le manifeste
`mesures-v1.2.0/references-manifest.json` donne les empreintes des fichiers des 15 références.

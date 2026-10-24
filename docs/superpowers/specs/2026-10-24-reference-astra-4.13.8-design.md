# Nouvelle référence Astra 4.13.8

Date : 24 octobre 2026. Statut : proposition pour relecture.
Décision de Gilles : Astra 4.13.8 remplace 4.11.0 comme référence du portage.
Cette décision porte sur le thème gratuit ; Astra Pro n'entre pas dans le périmètre.

## Objectif et périmètre

Faire correspondre l'ensemble des fonctions déjà prises en charge par Astra SPIP au
comportement d'Astra 4.13.8, sans conserver des bugs de 4.11.0 pour satisfaire les
anciens tests. La migration couvre les assets, le HTML transposé, le CSS figé extrait,
les générateurs PHP, les références et les crédits. La seule mise à jour du numéro
ou des deux assets ne suffit pas à atteindre cet objectif.

Base : Astra SPIP 1.5.0 (`ff00eda`), inventaire des 184 entrées et des fichiers
modifiés dans [le rapport](../../inventaires/2026-10-24-astra-4.11.0-4.13.8.md).
Les changements de fonctionnalités propres à WordPress, ses éditeurs et ses extensions
ne deviennent pas implicitement des fonctions SPIP. Les choix déjà documentés
(contenu de substitution, polices locales, import avec pertes caractérisées,
options responsives encore non appliquées) restent explicités.

Proposition de jalon : Astra SPIP **1.6.0** pour la migration intégrée. Le schéma de
base de données n'est pas modifié ; aucune nouvelle obligation de plugin n'est introduite.

## Approches et choix de conception

- Reprendre seulement quelques corrections en restant sur 4.11.0 était possible,
  mais ne répond plus à la décision de changer de référence.
- Remplacer les assets et renommer les attendus laisserait un mélange de versions,
  notamment pour les titres, les seuils et les règles figées du builder.
- **Choix proposé : migration coordonnée du périmètre existant**, avec lots vérifiables,
  nouvelles sorties WordPress et comparaison explicite ancien/nouveau.

Les quatre specs complémentaires définissent les comportements à reprendre :

| Spec | Responsabilité |
| --- | --- |
| [Navigation accessible](2026-10-24-navigation-accessible-astra-design.md) | Panneau, clavier, ARIA, recherche, évitement et fermeture des sous-menus |
| [CSS et responsive](2026-10-24-css-responsive-astra-design.md) | Cascade, tailles, replis, visibilité, initialisation précoce et blocs conservés |
| [Icônes et liens sociaux](2026-10-24-icones-liens-sociaux-astra-design.md) | SVG, identifiants, libellés et préfixe e-mail |
| [Compatibilité oEmbed](2026-10-24-compatibilite-oembed-design.md) | Plugin SPIP optionnel, import, proportions et absence de double enveloppe |

Le découpage proposé évite une spec par note de version : plusieurs notes corrigent
le même composant. Il sépare les nouvelles fonctionnalités discutées pour Garden
(identité responsive complète, choix des entrées de menus, sections de contenu).

## Source immuable et traçabilité

Archive officielle : [astra.4.13.8.zip](https://downloads.wordpress.org/theme/astra.4.13.8.zip),
SHA-256 `3cce1a750dcb699429117a88987aa8f10be404374492eef94379d2c4df6a6572`.

Les copies directes attendues sont vérifiées octet par octet :

| Source | Destination | SHA-256 de la source |
| --- | --- | --- |
| `assets/css/minified/main.min.css` | `css/main.min.css` | `84aad614f8b95ebd3a4ac6f7633e77323066d31da87afe3dafc8c14cd3d0f8fc` |
| `assets/js/minified/frontend.min.js` | `js/frontend.min.js` | `3b3a9a24246253beec04f3d2619082970c3aa861ca4ee34280662e3d4a509d20` |

Aucune correction manuelle opaque dans ces copies minifiées. Toute adaptation SPIP
nécessaire reste dans un fichier identifiable ou une transposition sourcée, avec
son test et son écart déclaré. Vérifier les variables globales et le balisage
attendus par le nouveau JavaScript avant de le considérer intégré.

Pour chaque fichier transposé ou extrait, conserver option, fonction et chemin
amont ; distinguer règles identiques, règles modifiées et adaptations SPIP.
Mettre à jour `CREDITS.md`, les en-têtes actuels, le lien du paquet et la notice
amont de 4.13.8 tout en conservant les mentions de Brainstorm Force et des tiers.
Les inventaires, preuves de publications et specs historiques 4.11.0 ne sont pas
réécrits comme s'ils avaient utilisé 4.13.8. Les anciennes licences nécessaires
à des éléments conservés ne sont pas supprimées sans vérification.

## Référentiel de comparaison

Un WordPress local dédié utilise **Astra 4.13.8 réellement actif**, vérifié par
wp-cli et empreinte des sources. WordPress reste à **7.1.3**, comme le référentiel
actuel ; les autres composants sont fixés et consignés pour isoler le changement
de thème. Si cet environnement ne peut pas être reproduit, la qualification est
suspendue, sans substitution silencieuse de WordPress ou d'Astra.

Les configurations fraîches de `tests/references/wp/commun.php` portent
`theme-auto-version=4.13.8`. Les références de compatibilité conservent leurs
anciennes versions et drapeaux explicites lorsque c'est précisément le scénario
testé. Ce champ de configuration ne remplace jamais le contrôle du thème actif. Les classes de version du body et les métadonnées décrivant le thème actif indiquent 4.13.8, même si un drapeau de compatibilité conserve une version de données plus ancienne.

- Archiver un état de comparaison 4.11.0 avant régénération : fichiers de réglages,
  HTML normalisé, CSS, styles calculés, captures et manifeste de provenance.
- Rejouer les **35 références existantes** R0–R24 avec leurs variantes et P1–P3,
  puis les cas comportementaux complémentaires des quatre specs. Les anciens
  identifiants ne sont ni recyclés ni renumérotés.
- Recapturer les attendus des groupes de styles et de transparence avec leurs
  scripts dédiés ; régénérer le CSS figé du builder, des pages et de compatibilité
  selon les entrées réelles d'`extraire_css.py`, pas uniquement R0.
- Comparer aussi les blocs importés : feuilles WordPress, styles globaux, adaptations
  SPIP et règles Astra constituent une même cascade. Garder WordPress 7.1.3 comme
  origine de ses feuilles natives, sans les attribuer au thème.

Chaque différence entre anciennes et nouvelles sorties possède une ligne de bilan :
configuration, sélecteur/propriété ou comportement, source 4.13.8, règle portée ou
adaptation, mesure avant/après et statut. Les références SPIP sont confrontées
aux sorties indépendantes du WordPress cible. Changer un attendu pour suivre
le code SPIP n'est pas une preuve de fidélité.

## Contrat de migration des données

Les clés `astra/*`, les types de valeurs, les réglages par page et les données de
Garden restent lisibles ; pas de remise à zéro ni d'écriture automatique de nouveaux
défauts. Les réglages de compatibilité de sites anciens conservent leur rôle selon
4.13.8. Un côté vide, un appareil sans unité, une option absente et une valeur
explicite restent distincts lorsqu'Astra les distingue.

L'enregistrement d'un formulaire sans modification conserve les valeurs et leurs
effets. Les empreintes du seed Garden restent valides : un simple changement de
référence ne transforme pas un réglage intact en personnalisation. Les modifications
visuelles résultant des corrections et des nouveaux défauts 4.13.8 sont mesurées
et annoncées. L'exigence historique « rien ne change sans réglage » est remplacée,
pour cette migration, par « aucune différence inexpliquée avec 4.13.8 » ; elle
reste valable pour les fonctionnalités hors de ce lot.

## Qualification des entrées encore ouvertes

Les 25 entrées V de l'inventaire doivent recevoir une décision traçable avant
publication. La table suivante affecte un responsable de conception ; elle ne
présume pas qu'elles nécessitent toutes du code.

| Entrées | Spec responsable |
| --- | --- |
| 4.11.2-04 ; 4.11.9-01, -03 ; 4.11.11-02 ; 4.11.12-05, -07 ; 4.11.14-05 ; 4.12.1-02 ; 4.12.6-01 ; 4.13.1-05 ; 4.13.4-02 | CSS et responsive |
| 4.11.4-03 ; 4.11.6-02 ; 4.11.9-02 ; 4.11.12-03, -06 ; 4.11.14-01 ; 4.11.17-03 ; 4.12.2-02 ; 4.12.4-08 ; 4.13.2-03 | Navigation accessible |
| 4.11.11-06 | Icônes et liens sociaux |
| 4.11.13-04 ; 4.11.18-04 | Compatibilité oEmbed |
| 4.12.5-02 | Présente spec : droits, échappement, intégrations absentes |

Décisions possibles : applicable avec correction et preuve ; équivalent déjà
présent avec preuve ; chemin ou fonction absent identifié dans le port ; amélioration
hors périmètre assumée et documentée. Une incertitude sur une fonction rendue
et utilisable ne peut pas être close par la seule mention « à voir plus tard ».
Le durcissement générique 4.12.5 exige une comparaison des contrôles pertinents
(droits, valeurs CSS et sorties d'attributs) ; ne pas lui inventer une CVE ni
assimiler `attribut_html` à une validation des protocoles. Une anomalie confirmée
fait l'objet d'un correctif et d'un test ciblés dans la migration.

## Intégration et validation

Les lots sont développés sur une branche de migration commune, ou sur des branches
empilées vers celle-ci. Ils peuvent être relus séparément ; **main ne change de
référence qu'avec l'ensemble cohérent et vert**. Les dépendances CSS/JS des deux
specs centrales sont obligatoires, pas des étapes publiées indépendamment.
La compatibilité oEmbed est validée avec et sans le plugin avant de recommander
le jalon complet ; elle n'oblige pas à activer le plugin dans les tests généraux.

Le plan ordonne les lots par dépendances et donne à chacun une validation
intermédiaire exécutable isolément sur la branche commune, même avant l'intégration
finale. Il distingue ces validations ciblées du rejeu complet obligatoire avant
fusion vers main ; un lot ne peut pas dépendre de code futur non déclaré pour
que ses propres tests soient exécutables. La question ouverte du repli sans
JavaScript est tranchée avant l'implémentation du lot responsive.

La matrice PHP/SPIP reste celle annoncée par le paquet : syntaxe PHP 7.4 pour le
code compatible, équivalence des sorties pertinentes sous PHP 7.4/8.4, suites
unitaires et intégration sur l'installation SPIP de référence. L'intégration
optionnelle n'étend pas la compatibilité déclarée d'un plugin tiers.

Critères de sortie :

1. Les copies des assets ont les empreintes ci-dessus ; les adaptations sont isolées.
2. Les 35 références de base et les scénarios supplémentaires passent contre 4.13.8 ;
   aucune hausse de seuil ou exclusion nouvelle ne masque un changement inexpliqué.
3. Les comparaisons HTML, styles calculés, captures et interactions sont couvertes
   selon leur nature ; exceptions connues reprises ou réévaluées avec preuve.
4. Toutes les entrées I/L/V ont un traitement explicite ; les H/F restent reliées
   à un périmètre absent, sans importer les extensions WordPress.
5. Les formulaires et le seed conservent les données et personnalisations.
6. Les mesures sont refaites sur le commit intégré destiné à publication.
7. Sources, licences, README et version du paquet concordent avec la référence.

Les serveurs de test utilisent des dossiers et ports dédiés ; le WordPress partagé
8401 n'est pas changé pendant une capture d'un autre agent. Aucun test complet de
préparation de wp2spip n'est requis : seuls les tests d'import concernés sont lancés.
Les processus de test sont arrêtés par PID. Le tag suit la fusion ; la rédaction
de ces specs ne pose aucun tag et ne modifie pas les assets de production.

# Identité du site sur les trois appareils — spécification

Date : 2026-10-25. Statut : proposition à relire par Gilles et Claude.
Contrôle indépendant de préparation au plan effectué : aucune question bloquante
relevée ; cela ne vaut pas validation fonctionnelle par Gilles ou Claude.
Socle : Astra SPIP 1.6.0, `main` au commit `1fd1872` ; cible Astra **4.13.8**.

## Objectif et périmètre

Appliquer les tailles et visibilités déjà enregistrées pour l'ordinateur, la tablette
et le mobile : logo du site, nom du site et slogan. Garden conserve aujourd'hui les
valeurs du modèle, mais Astra SPIP applique la seule valeur ordinateur.

Cette spec complète « Styles du Customizer » pour les cinq options ci-dessous.
Elle ne généralise pas le responsive aux menus, boutons, couleurs ou polices des
widgets. Elle ne porte ni les logos distincts mobile/transparent/sticky, ni le
sélecteur d'icônes SVG d'Astra, ni ses réglages de hauteur SVG. L'en-tête transparent
existant conserve ses couleurs et sa règle d'activation.

Le lot de Garden et le remplacement des images de substitution sont distincts.
Cette spec ne les autorise pas et ne crée pas de nouveau lot.

## Écart mesuré et résultat attendu

Source : `garden-spip`, commit de fusion `0d942fc`,
`docs/mesures/2026-10-25-astra-1.6.0/{spip,modele}.json`, accueil à trois largeurs.
Valeurs enregistrées : `plugins/garden/seed/lots/4.php`.

| Élément | Valeurs du modèle, ordinateur / tablette / mobile | Écart constaté |
| --- | --- | --- |
| Largeur du logo | 40 / 40 / 32 px | 40 px dans SPIP sur mobile, 32 dans la démo |
| Taille du titre | 20 / 18 / 16 px, convertis aussi en rem | 18,24 px calculés dans SPIP partout ; 16,416 sur tablette et 14,592 sur mobile dans la démo |
| Taille du slogan | 11 / 12 / 11 px, convertis aussi en rem | 10,032 px calculés dans SPIP sur tablette, 10,944 dans la démo |

Ces tailles calculées dépendent de la taille racine de Garden : ne pas remplacer
les valeurs enregistrées par les valeurs calculées. La conversion en rem d'Astra
et la cascade de la taille racine restent celles de la référence.
La hauteur d'en-tête doit suivre le contenu, sans correctif de hauteur arbitraire.
Le logo provisoire de Garden mesure 80 × 116 px, avec un rapport différent du
logo du modèle : cet écart de proportions reste documenté, même après correction
de la largeur mobile. La référence technique utilise la même image libre des deux côtés.

## Sources, provenance et approche

Distribution officielle : `https://downloads.wordpress.org/theme/astra.4.13.8.zip`.
Empreinte et WordPress **7.1.3** dans `tests/references/cible.json`.
Les chemins ci-dessous sont relatifs à la racine de cette distribution :

- `inc/class-astra-dynamic-css.php`, `Astra_Dynamic_CSS::return_output()` : lecture des
  options lignes 68–105 et 139–140 ; règles ordinateur 634–657 ; tablette 3709–3716
  et 3780–3789 ; mobile 3814–3821 et 3885–3897.
- `inc/core/common-functions.php`, `astra_responsive_font()` et
  `astra_get_font_css_value()`, lignes 118–217 : unité implicite, conversion px/rem,
  cas de zéro ; `astra_get_css_value()`, ligne 274 : largeur en pixels.
- `inc/core/class-astra-theme-options.php`, défauts lignes 462–480 et 773–788.
- `inc/core/builder/class-astra-builder-options.php`, largeur du logo lignes 263–267.
- `astra_get_tablet_breakpoint()` et `astra_get_mobile_breakpoint()`,
  `inc/core/common-functions.php`, lignes 1507 et 1535 : seuils de ces styles.

Approche retenue : prolonger le générateur PHP existant par appareil, comme les
marges des rangées. Elle conserve les conditions d'émission et la cascade d'Astra.
Une feuille statique à variables permanentes modifierait la cascade sans réglage ;
un relevé de styles calculés seul masquerait les conditions et les unités.
Les captures WordPress servent de preuve indépendante du générateur porté.

Les futurs commentaires de code citent fichiers, fonction et version d'origine ;
les crédits d'Astra et sa licence GPL-3.0-or-later restent dans `CREDITS.md`.
Aucun asset officiel CSS/JS n'est modifié pour réaliser ce lot.

## Données et validation

Stockage inchangé : `astra/styles`, normalisation commune du formulaire et des imports.
Aucune migration SQL, duplication de clé ou conversion destructive des tableaux.

| Option | Type et unités | Défaut du socle |
| --- | --- | --- |
| `ast-header-responsive-logo-width` | Nombre de pixels par appareil, sans clé d'unité | Trois valeurs vides |
| `font-size-site-title` | Taille et unité par appareil : `px`, `em`, `rem`, `vw`, `%` | Ordinateur 26 px dans le profil récent ; tablette et mobile vides |
| `font-size-site-tagline` | Même type | Ordinateur 15 px ; tablette et mobile vides |
| `display-site-title-responsive` | Booléen par appareil | Vrai sur les trois appareils |
| `display-site-tagline-responsive` | Booléen par appareil | Faux sur les trois appareils |

Le pourcentage est accepté seulement pour ces deux options de taille, comme par
le helper d'Astra. Les autres types et options ne sont pas élargis implicitement.
Nombres : bornes existantes du registre, de 0 à 1000 ; nombres négatifs, non finis,
unités inconnues et injections refusés. Booléens : `true`, `false`, `1`, `0`,
`"1"`, `"0"`, et valeur vide. La valeur fausse n'est jamais confondue avec une absence.
Une feuille invalide est retirée seule ; les autres appareils valides sont conservés.

Une taille ou largeur manquante/vide n'invente pas une valeur tablette ou mobile.
La taille de police sans clé d'unité utilise **px**, comme `astra_responsive_font()` ;
cela diffère des marges des rangées, inactives sans unité. Une unité fournie et
invalide ne devient pas silencieusement px : la valeur numérique de cet appareil
est retirée avec l'unité refusée et signalée au formulaire. Les autres appareils
valides restent intacts. Le retrait de la seule clé d'unité ne doit pas activer en
px une valeur qui avait été refusée.

Pour les visibilités, les tableaux partiels utilisent le contrat existant
`astra_identite_visible()` : une valeur absente/vide prend le défaut de **cet appareil**
(titre visible, slogan masqué), sans hériter du booléen de l'appareil plus large.
C'est une complétion d'entrée SPIP déclarée, pas une affirmation sur un tableau
WordPress incomplet : Astra indexe directement les trois clés. Le témoin WordPress
reçoit ces trois valeurs effectives, sans écriture de défaut dans les métas SPIP.

## CSS, cascade et HTML

Ordre : ordinateur sans media query, puis `max-width:921px`, puis
`max-width:544px`, dans l'ordre des règles correspondantes d'Astra.
Ces bornes viennent des fonctions **tablette/mobile**, pas de
`astra_header_break_point()` qui commande le passage du menu mobile. Les variantes
historiques de breakpoint et les filtres WordPress ne sont pas un nouveau réglage SPIP.

- `.site-title` : taille de police et `display` de l'appareil.
- `.site-header .site-description` : taille et `display` de l'appareil.
- Logo ordinateur : `header .custom-logo-link img`, `max-width` et `width` ;
  `.astra-logo-svg`, `width`, comme les règles déjà portées.
- Logo tablette : sélecteur d'image groupé d'Astra comprenant
  `.ast-header-break-point .site-logo-img .custom-mobile-logo-link img`.
- Logo mobile : sélecteur groupé d'Astra comprenant
  `.ast-header-break-point .site-branding img` et `.custom-logo-link img`,
  puis le `max-width` de `.custom-mobile-logo-link img`.
  Les sélecteurs sans élément correspondant restent sans effet ; aucun logo mobile
  distinct ni nouvel élément SVG n'est introduit.

Les tailles vides omettent la déclaration de l'appareil ; le navigateur conserve
la règle plus large ou son héritage. Le plan relève le CSS figé qui pourrait masquer
ce comportement. Pour les nouveaux cas partiels, toute neutralisation nécessaire
est minimale, conditionnelle et déclarée dans les preuves, avec son attendu
WordPress ; aucun repli 26/15 ne doit être ajouté arbitrairement sur tablette/mobile.
Le comportement historique ordinateur reste inchangé quand aucune nouvelle valeur
responsive n'est activée. Les cas nouvellement activés sans valeur ordinateur sont
comparés à WordPress, y compris le rendu ordinateur : un défaut figé ne suffit pas
à justifier une différence.

La taille en px garde la succession `font-size:…px;font-size:…rem` et les arrondis
d'Astra dans le profil pris en charge (taille de texte de base 16). Ne pas utiliser
la taille calculée de la racine comme diviseur. Une unité non px reste cette unité.
Les cas zéro numérique et chaîne `"0"` sont vérifiés séparément contre les helpers
4.13.8 : ne pas remplacer leur traitement par un test PHP `empty()` indifférencié.
Le drapeau WordPress `astra-zero-font-size-case-css` et le réglage global
`disable-px-to-rem-conversion` ne deviennent pas des options nouvelles de ce lot ;
les références fixent explicitement leur état par défaut, absent.

Le nom et le slogan figurent dans le HTML s'ils sont visibles sur au moins un
appareil, puis le CSS règle chaque affichage. Slogan visible uniquement sur mobile,
titre visible uniquement sur tablette, et deux éléments masqués partout sont testés.
Aucune copie de texte par appareil. Les couleurs de l'en-tête transparent restent
prioritaires selon leur cascade existante ; elles ne règlent pas la taille.

L'image servie doit conserver assez de définition pour la plus grande largeur
configurée, dans la limite du fichier d'origine. `astra_logo_reduire()` ne doit donc
pas détruire les pixels nécessaires à une tablette plus large que l'ordinateur.
Le plan choisit le mécanisme d'image, avec deux contraintes vérifiables : conserver
les dimensions d'affichage de l'ordinateur, y compris son repli lorsqu'il n'a aucune
largeur réglée ; servir une image suffisamment définie pour les autres appareils.
Sans aucune largeur réglée, le repli actuel à 60 px de haut reste inchangé.
Le plan distingue attributs intrinsèques, taille de fichier et dimensions CSS ;
aucun nouveau plafond de hauteur ne masque une largeur explicitement réglée.

## Formulaire et compatibilité

Dans « Couleurs et typographie », section Identité, ces cinq options présentent
ordinateur, tablette et mobile ; unités individuelles pour les deux tailles,
largeur en px pour le logo, choix Défaut / Visible / Masqué pour les deux booléens.
Les couleurs de la même section gardent leur champ existant. Les autres groupes
continuent d'indiquer leurs propres limitations responsives.

Un enregistrement inchangé conserve les valeurs effectives, les booléens faux,
les unités explicites et les clés non postées ; une unité absente reste absente et
s'interprète comme px. La fusion ne remplace pas tout un tableau par l'appareil édité.
La suppression d'une taille mobile laisse la tablette agir ; la suppression d'une
visibilité mobile rétablit le défaut mobile, ce que le libellé doit expliquer.
Les libellés par appareil, unités et erreurs utilisent les chaînes SPIP.

Sans `astra/styles` : mêmes HTML, CSS et captures qu'en 1.6.0. Sans ces cinq options,
aucune nouvelle règle d'identité n'est émise. Les réglages ordinateur existants
restent compatibles ; les valeurs tablette/mobile déjà stockées deviennent actives,
ce qui est un changement voulu, à annoncer dans la documentation de version.

## Preuves exigées avant fusion de l'implémentation

Nouvelles références **R29-identite-responsive** et ses variantes nommées ; les noms
historiques, dont R8 et R11, ne sont pas réaffectés.

1. Référence Garden : 40/40/32, 20/18/16 et 11/12/11, visibilités vraies,
   même logo libre et mêmes polices des deux côtés, transparence inactive. Styles
   calculés du logo, titre, slogan et hauteur d'en-tête à 1280, 768 et 390 px.
2. Références partielles : ordinateur seul ; tablette seule ; mobile seul ; trou
   sur tablette ; tailles vides ; 0 numérique et `"0"` ; unités absentes puis
   `px`, `em`, `rem`, `vw`, `%` ; valeurs différentes des fixtures historiques.
   Comparer CSS émis **et** rendu sur les trois appareils, y compris les replis.
3. Visibilités croisées, valeurs fausses sous leurs quatre formes, éléments
   absents partout, état complet et tableaux partiels complétés selon le contrat.
4. Logo ordinateur 32 / tablette 96 / mobile 48, puis ordinateur non renseigné :
   image source assez grande, définition et dimensions d'affichage contrôlées.
   Autre référence avec en-tête transparent, qui vérifie ensemble tailles et couleurs.
5. Bornes à 922/921 et 545/544 px : les styles basculent au bon seuil ; le passage
   du menu mobile reste indépendant. Tests avec JavaScript et sans JavaScript sur
   les trois largeurs principales pour la visibilité du contenu.
6. Unitaires sur validation, émission, tableaux partiels et fusion ; intégration
   sur HTML d'identité ; e2e du formulaire pour chacun des cinq champs à trois
   appareils, retrait d'une valeur et conservation des clés non postées.
7. Matrice 1.6.0 complète : références historiques, P1–P3 et blocs importés.
   Les fixtures qui portent volontairement des valeurs mobiles désormais actives
   ont un delta attendu documenté, comparé à WordPress, avant de changer leur golden.
   Les fixtures sans réglage concerné restent inchangées ; seuils non relevés.

Les preuves portent sur le commit testé, avec versions et empreintes, sources des
attendus CSS et écarts déclarés. PHP 7.4 et version courante produisent le même CSS.
Une mise à jour et remesure de Garden suivra la publication de la version qui
implémente cette spec ; le README de Garden ne retire l'écart qu'après cette mesure.

## Décisions laissées au plan

Découpage des helpers, optimisation des images respectant le contrat ci-dessus,
organisation des fixtures et neutralisations strictement nécessaires à la cascade.
Le plan ne peut élargir le périmètre à tous les styles responsifs ni changer les
valeurs du modèle pour obtenir une égalité visuelle. Version de publication à fixer
avec les autres lots ; ni tag ni publication ne sont autorisés par cette spec.

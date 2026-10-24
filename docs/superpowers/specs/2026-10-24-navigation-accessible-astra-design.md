# Navigation accessible selon Astra 4.13.8

Date : 24 octobre 2026. Statut : proposition pour relecture.
Dépendances : [migration de référence](2026-10-24-reference-astra-4.13.8-design.md)
et [CSS/responsive](2026-10-24-css-responsive-astra-design.md).

## Objectif et portée

Reprendre les corrections de navigation utilisables dans Astra SPIP : menus de
rubriques/pages, menus 1/2, menu mobile en mode déroulant ou off-canvas, widgets
menu, recherche et lien d'évitement. Le contenu du menu, les réglages du builder
et la limite actuelle de deux niveaux restent inchangés.

Origine : NAV-01 à NAV-05 de l'[inventaire](../../inventaires/2026-10-24-astra-4.11.0-4.13.8.md),
notes 4.11.2/8/9/10/12/14/17, 4.12.2/4/6 et 4.13.2. Référence de comportement :
code réellement présent dans 4.13.8, pas seulement les annonces intermédiaires.

## Conception proposée

Modifier seulement le JS laisserait le panneau fermé accessible et des rôles
ARIA incohérents. Réécrire toute la navigation remplacerait inutilement le
comportement du thème. Le choix proposé associe le bundle officiel 4.13.8 aux
adaptations nécessaires du balisage SPIP et du CSS extrait. Les sélecteurs et
états utilisés par ce bundle restent reconnaissables.

Les différences nécessaires à SPIP sont explicites et testées. Un comportement
amont manifestement incorrect dans notre intégration, tel qu'un retour du focus
sur un déclencheur masqué, ne devient pas un critère de réussite à reproduire.
L'adaptation minimale est isolée ; elle n'est pas cachée dans l'asset minifié.

## Panneau off-canvas

Le panneau fermé est invisible visuellement **et** absent des éléments accessibles
et de l'ordre de tabulation. La règle amont `visibility:hidden` est reprise avec
son état ouvert visible. Le déplacement hors écran et l'opacité ne constituent
pas à eux seuls ce contrat. Ne pas ajouter une deuxième mécanique de visibilité
incompatible avec celle d'Astra sans nécessité démontrée.

À l'ouverture :

1. mémoriser le déclencheur effectivement utilisé ;
2. appliquer les états d'ouverture avant de déplacer le focus ;
3. synchroniser le déclencheur visible, ses classes et `aria-expanded` ;
4. placer le focus sur la commande de fermeture visible ;
5. conserver Tab/Maj+Tab dans les commandes visibles du panneau tant qu'il est ouvert.

À la fermeture par bouton, Échap ou voile : retirer les états d'ouverture,
mettre à jour les attributs, libérer la navigation et rendre le focus au
même déclencheur s'il est encore présent et visible. Si un redimensionnement
l'a masqué, choisir la commande équivalente visible ; à défaut, une cible
logique visible de l'en-tête, sans focaliser un élément caché ni rouvrir le panneau.
Cette sélection du bon déclencheur est une adaptation SPIP assumée si le bundle
amont cherche seulement le premier du document.

Le redimensionnement, l'ouverture répétée et un panneau vide ne multiplient pas
les écouteurs. Un builder sans déclencheur ou sans panneau ne produit aucune
erreur JavaScript. Les boutons de fermeture ont une taille utilisable et ne
rétrécissent pas sous l'effet du padding ou d'une barre de défilement, conformément
au composant 4.13.8. La recherche placée dans le panneau reste cliquable et utilisable
au clavier, sur mobile et tablette.

## Sous-menus et widgets

Une flèche interactive est une commande nommée avec un rôle approprié, un état
`aria-expanded` et l'information de sous-menu attendue par 4.13.8. Les anciens
rôles `presentation` focalisable et `application` ne sont plus employés pour
ces commandes. Lorsque la balise native diffère de l'amont, vérifier la sémantique
et le comportement avec les sélecteurs JS ; ne pas cumuler activation native et
simulation qui ferait basculer deux fois l'état.

Entrée et Espace activent la commande une seule fois ; Espace ne fait pas défiler
la page à sa place. Lien parent et commande d'ouverture gardent leurs fonctions.
Le changement de focus vers une autre branche ou la suite du contenu ferme le
sous-menu quitté et actualise ses classes/attributs. Échap suit le comportement
utile de 4.13.8 avec une cible de focus visible.

Les deux branches de génération pages/rubriques, les menus d'en-tête et les
menus de widgets sont traités. Un correctif limité à `nav.site-navigation`
ne couvre pas les widgets. Les ID des menus déjà différenciés par appareil et
la déduplication des placements restent conservés.

Le test existant
`tests/integration/HfbRenduTest.php::testBlocMenuDansLesWidgetsDuPied`
(ligne 222 sur la base 1.5.0) exige encore `role="application"`. Son assertion
doit être remplacée explicitement par le contrat de commande corrigé de cette
spec, en conservant les vérifications de structure et d'état pertinentes. Les
assertions liées à la balise sont adaptées si nécessaire au balisage retenu.
Ce changement d'attendu est intentionnel dans la migration : l'ancien rôle ne
doit pas être conservé pour garder ce test vert. Le test modifié échoue sur
le rendu ancien avant d'être satisfait par la correction du gabarit.

## Évitement, repères et libellés

Comparer aux sorties 4.13.8 le lien d'évitement, sa visibilité au focus et sa
cible de contenu, les repères des zones de widgets, les libellés des commandes
et les textes redondants pour lecteur d'écran. Les équivalents HTML natifs
SPIP peuvent rester lorsqu'ils offrent la sémantique attendue ; ajouter un rôle
identique au rôle implicite n'est pas un objectif en soi. Les libellés restent
traduits via les fichiers de langue SPIP, jamais repris du Customizer WordPress.

Les liens publics de catégorie/étiquette et les images cliquables qui existent
dans les squelettes du port gardent un focus perceptible. Aucun réglage
WordPress `site-accessibility-*` non porté n'est inventé pour masquer un échec
de navigation au clavier. Une différence constatée est qualifiée avant de
choisir la règle de focus, avec source 4.13.8 et test de contraste/visibilité ciblé.

## Limites conservées

Menu desktop en mode « cible lien », sticky header, mégamenu et arbre de plus
de deux niveaux ne sont pas ajoutés. La règle tactile 44 px dont le sélecteur
vise un parent au troisième niveau est embarquée avec l'asset officiel ; elle
n'est pas présentée comme une correction active du menu standard à deux niveaux.

Le déplacement d'initialisation vers DOMContentLoaded annoncé en 4.11.12 n'est
pas un engagement : le bundle 4.13.8 emploie encore window.load pour le chemin
observé. L'affichage initial relève du script précoce de la spec responsive.
Le retour en haut n'est pas réécrit sous couvert d'un correctif de panier WP.

## Preuves attendues

Références dédiées, dont les noms définitifs seront alloués au plan sans collision :

| Scénario | Assertions principales |
| --- | --- |
| Off-canvas, fermé/ouvert/réouvert | Visibilité calculée, absence de focus fermé, focus initial, Tab/Maj+Tab, états ARIA |
| Trois fermetures | Bouton, Échap et voile ; retour au vrai déclencheur |
| Redimensionnement ouvert | Aucun focus masqué ; état synchronisé après passage desktop/mobile |
| Panneau avec recherche et padding | Recherche utilisable, bouton de fermeture stable, aucune erreur console |
| Sous-menus pages puis rubriques | Entrée/Espace, une seule activation, fermeture au départ du focus |
| Widgets menu puis menus 1/2 | Même contrat clavier malgré les enveloppes différentes |
| En-tête ou panneau vide | Aucun sélecteur obligatoire inexistant ni écouteur dupliqué |
| Évitement et repères | Cible présente, focus visible, libellés traduits et repères pertinents |

Les parcours sont exécutés dans Chromium, avec comparaison aux configurations
équivalentes d'un WordPress 4.13.8 local. Inspecter les propriétés sémantiques
accessibles des éléments ; une capture seule ne suffit pas. Un contrôle manuel
avec lecteur d'écran est consigné lorsqu'il est possible ; il n'est jamais
annoncé comme réalisé à partir du seul test ARIA automatisé.

Tester à 1280/768/390 px et aux limites définies par la spec responsive. Les références
existantes de menus, widgets et recherche sont rejouées avec les actifs 4.13.8.
Tout changement de HTML normalisé attendu doit renvoyer à l'un de ces contrats.

## Sortie et données

Aucune nouvelle donnée persistante ni migration du builder. L'état d'ouverture
reste éphémère ; aucun drapeau de session ou de configuration n'est enregistré.
Les tests du formulaire de builder sans modification restent verts.
Les entrées V affectées à cette spec dans le document de référence reçoivent
une décision sourcée ; les cas non portés sont nommés, pas assimilés à des tests réussis.

# Alignement des widgets du pied par appareil — spécification

Date : 2026-10-25. Statut : proposition à relire par Gilles et Claude.
Contrôle indépendant de préparation au plan effectué : aucune question bloquante
relevée ; cela ne vaut pas validation fonctionnelle par Gilles ou Claude.
Socle : Astra SPIP 1.6.0, `main` au commit `1fd1872` ; cible Astra **4.13.8**.

## Objectif

Porter les quatre options `footer-widget-alignment-1` à
`footer-widget-alignment-4` pour les widgets du pied pris en charge par Astra SPIP,
sur ordinateur, tablette et mobile. Elles sont distinctes de l'alignement du
copyright et des icônes sociales déjà disponible.

Source de l'écart : Garden, commit de fusion `0d942fc`,
`docs/mesures/2026-10-25-astra-1.6.0/alignements-{spip,modele}.json` et
`alignements-differences.txt`. Les quatre widgets sont à gauche à 1280 px des deux
côtés. À 768 et 390 px, ils restent à gauche dans SPIP et sont centrés dans la démo :
**8 différences sur 12 valeurs**. Les captures SPIP sont identiques à celles du lot 7
avant la migration 4.13.8 : cet écart ne provient pas de cette migration.

Les 814 propriétés du relevé historique ne mesurent pas cet alignement ; garder
les deux compteurs séparés pour suivre l'évolution sans fausser la comparaison.

## Sources et approche

Distribution : `https://downloads.wordpress.org/theme/astra.4.13.8.zip` ; empreinte
et WordPress 7.1.3 dans `tests/references/cible.json`.

- `inc/builder/type/footer/widget/dynamic-css/dynamic.css.php`, fonction
  `astra_fb_widget_dynamic_css()`, lignes 37–87 : condition de chargement du widget,
  trois valeurs, sélecteur selon la structure et trois émissions.
- `inc/core/builder/class-astra-builder-options.php`, lignes 2081–2085 : défauts
  d'alignement. Ordinateur : gauche en LTR, droite en RTL ; tablette et mobile :
  vides pour les nouveaux défauts, centrés pour l'ancien profil. La branche dépend
  de `$apply_new_default_color_typo_values`, pas du copyright.
- `inc/core/builder/class-astra-builder-helper.php`,
  `Astra_Builder_Helper::is_component_loaded()` et `apply_flex_based_css()` :
  présence du composant et choix de structure.
- `inc/core/common-functions.php`, `astra_parse_css()`,
  `astra_get_tablet_breakpoint()` et `astra_get_mobile_breakpoint()` : omission
  des propriétés vides et bornes des media queries.

Approche retenue : générateur PHP par widget, dans l'assemblage des styles du
pied, suivant les conditions d'Astra. Une règle CSS permanente changerait les sites
sans réglage. Une modification directe du contenu des widgets ou de leur HTML ne
représenterait pas une option de thème. Le relevé de la démo constitue un besoin ;
le WordPress contrôlé constitue l'oracle des règles et de leur cascade.

Les commentaires du code porté citent cette fonction, le fichier et Astra 4.13.8 ;
la provenance et la licence GPL-3.0-or-later sont maintenues dans `CREDITS.md`.
Aucun asset officiel n'est modifié.

## Périmètre et données

Quatre clés supplémentaires dans le registre de `astra/styles`, type `alignement`,
`responsive: true`, avec `desktop`, `tablet`, `mobile`. Valeurs : `left`, `center`,
`right`, ou chaîne vide ; aucune unité. Valeurs physiques, sans inversion automatique
de `left` et `right` sous RTL, comme les valeurs explicites d'Astra.

Pas de réutilisation des noms `footer-widget-1-alignment` : l'index est bien à la
fin de **`footer-widget-alignment-1`**. La section du formulaire et les libellés
reconnaissent cette forme, contrairement au motif actuel `^footer-widget-1-`.
Le registre précise ce nom amont, sa source, son type, les valeurs admises et les
deux branches des défauts WordPress ; aucune branche implicite n'est écrite en méta.

Une option absente, ou sans aucune valeur valide non vide, ne produit pas de CSS
nouveau. Un appareil absent/vide omet sa déclaration et laisse la cascade agir.
Un appareil invalide est retiré seul et signalé ; les valeurs valides des autres
appareils sont conservées. Pas de remplacement d'une valeur refusée par `center`.
Une première saisie sur la seule tablette ou le seul mobile est enregistrée.
La normalisation est la même pour formulaire, seed et import.

Le contrat d'absence conserve le rendu actuel de SPIP : il ne prétend pas rejouer
les défauts historiques d'une installation WordPress non renseignée. La référence
WordPress de fidélité reçoit les valeurs explicites correspondantes. Le port ne
crée pas un nouveau drapeau global de compatibilité pour ces quatre champs.

Sont exclus : alignement des widgets d'en-tête, alignement des rangées ou colonnes,
répartition des colonnes, polices et couleurs responsives des widgets, choix des
pages et liens libres des menus, menus déroulants dans les widgets, marges des
widgets et correctif `support-footer-widget-right-margin` d'Astra.
Tous les blocs déjà admis dans une zone de widget du pied héritent de son alignement ;
un bloc doté de son propre alignement explicite conserve sa priorité selon la cascade.

## Conditions et cascade

L'option N ne produit des règles que si le composant `widget-N` est effectivement
placé dans le pied, d'après le builder normalisé (`$hfb`). Elle ne doit pas exiger
une autre option telle qu'une couleur de titre pour devenir active. Un widget
placé uniquement dans l'en-tête n'est pas ciblé. Un composant dans une rangée non
rendue ne justifie pas de modification du HTML de cette rangée.

Dans le profil flex du port, sélecteur exact :

```css
.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"].footer-widget-area-inner
```

N prend 1 à 4. Astra utilise le descendant `… .footer-widget-area-inner` dans son
ancien profil non flex. Astra SPIP possède les deux classes sur le même `aside`
(`inclure/hfb/widget.html`) : conserver ce HTML. Ne pas émettre une règle qui ne
cible qu'un descendant inexistant. Les références WordPress de fidélité utilisent
le profil flex correspondant ; la branche non flex est documentée comme non portée.

Ordre : N croissant, puis pour chaque widget ordinateur sans media, tablette
`max-width:921px`, mobile `max-width:544px`. Ce sont les seuils tablette/mobile,
indépendants du seuil de basculement du menu de l'en-tête.
Le groupe s'insère à la place correspondant aux alignements dans les styles des
widgets, avant leurs styles typographiques/couleurs ; la comparaison WordPress
vérifie l'ordre effectif, y compris face aux styles du copyright et des icônes.

| Valeurs explicites ordinateur / tablette / mobile | Conséquence par la cascade |
| --- | --- |
| `left` / `center` / `center` | Cas Garden : gauche, centre, centre |
| `right` / vide / vide | Droite sur les trois appareils |
| `left` / `right` / vide | Gauche, droite, droite |
| `left` / vide / `center` | Gauche, gauche, centre |
| vide / `center` / vide | Ordinateur sans déclaration amont, centre sur tablette et mobile |
| vide / vide / `right` | Ordinateur et tablette sans déclaration amont, mobile à droite |

La feuille figée `css/astra-hfb.css` contient déjà quatre règles `text-align:left`
sur ces sélecteurs. Elles ne sont pas supprimées ou modifiées globalement : le
rendu sans réglage doit rester identique. Si une option active n'a pas de valeur
ordinateur, ce `left` figé doit être neutralisé conditionnellement pour retrouver
l'absence de déclaration d'Astra : `text-align:inherit` sur le sélecteur concerné,
avant les media queries, puis seulement les valeurs tablette/mobile réellement
posées. Cette règle supplémentaire propre au port est déclarée et comparée au
rendu WordPress, pas présentée comme une règle émise par Astra.

Pour une option entièrement absente/vide, pas de neutralisation. Pas de trois
règles systématiques avec `center` comme repli : elles contrediraient la cascade.
Les valeurs des blocs descendants, notamment un paragraphe explicitement aligné,
ne sont pas forcées par un sélecteur plus spécifique.

## Formulaire et conservation des réglages

« Couleurs et typographie » : un champ Alignement dans chacune des quatre sections
Widget, avec ordinateur, tablette et mobile et les choix Sans réglage / Gauche /
Centre / Droite. « Sans réglage » signifie absence de déclaration pour l'appareil,
avec héritage de la cascade, et non centrage par défaut.

Le champ utilise le type d'alignement existant, mais pas les replis propres au
copyright ou aux icônes. Les clés sont routées dans la bonne section et les libellés
par appareil sont traduisibles. Aucun nouveau formulaire ni réglage de contenu.

La fusion conserve les clés non postées et les trois valeurs importées ; la saisie
vide d'un appareil retire sa déclaration sans effacer les deux autres. Sauvegarder
sans modification ne change ni le CSS effectif ni les empreintes des valeurs déjà
normalisées. Les quatre sections sont testées : une expression régulière qui
n'en fait apparaître qu'une ne suffit pas.

## Preuves exigées

Nouvelles références **R30-widgets-alignements** et variantes nommées, sans
renuméroter R13 ou R22. Les profils avec/sans option utilisent exactement les
mêmes textes libres de test et le même HTML de widget.

1. Cas Garden : quatre tableaux explicites `left` / `center` / `center` ; les
   12 valeurs calculées concordent à 1280, 768 et 390 px. Captures des quatre widgets.
2. Variantes indépendantes : quatre alignements distincts ; ordinateur seul ;
   tablette seule ; mobile seul ; trous tablette/mobile ; option entièrement vide.
   Capturer le CSS WordPress, comparer les sélecteurs et l'ordre, puis les valeurs
   calculées, notamment l'héritage et la neutralisation du `left` figé.
3. Placement : option sans widget ; widget en en-tête seul ; widget dans le pied
   avec uniquement l'alignement réglé ; widget déplacé entre les rangées ; rangée
   non rendue (zone 6 seule) ; deux widgets dont un seul est réglé.
4. Rendus d'un widget texte et d'un widget menu statique, avec un bloc descendant
   explicitement aligné. Copyright, icônes et widgets non ciblés restent inchangés.
5. Bornes 922/921 et 545/544 px ; référence RTL avec les valeurs explicites pour
   distinguer la direction du texte de l'alignement physique. Pas de certification
   globale du RTL ni de changement implicite de gauche en droite.
6. Unitaires : registre et source des quatre clés, enum et refus d'injection,
   normalisation partielle, émission et absence d'émission, conditions de placement,
   cascade et neutralisation. Intégration : rendu du formulaire et des widgets.
   E2e : saisie/relecture des quatre champs sur les trois appareils, première saisie
   tablette seule, effacement d'un appareil et conservation des autres réglages.
7. Matrice historique 1.6.0, P1–P3 et blocs importés : sans nouvelle option, HTML,
   styles et captures inchangés. Tests PHP 7.4 et courant : même CSS. Aucun seuil
   visuel relevé ni changement de contenu pour faire passer une comparaison.

Preuves liées au commit testé, versions et empreintes ; attendus CSS capturés depuis
WordPress puis versionnés séparément du générateur. Le relevé complémentaire de
Garden ne devient 0/12 qu'après un lot Garden posant ces quatre options et une
nouvelle mesure. Cette spec ne modifie pas ses JSON historiques ni son README.

## Livraison et décisions du plan

Le plan fixe le découpage des fonctions et tests, l'emplacement exact d'assemblage,
et les noms des variantes de référence. Il doit garder cette spec vérifiable
indépendamment de la spec d'identité responsive ; aucune dépendance de code entre
les deux n'est requise. Une publication commune peut ensuite être décidée.
Version, publication et lot consommateur de Garden seront fixés à cette étape ;
aucun tag ni push n'est autorisé par la rédaction de cette spec seule.

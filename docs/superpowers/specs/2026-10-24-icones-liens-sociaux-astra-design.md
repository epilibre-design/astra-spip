# Icônes et liens sociaux selon Astra 4.13.8

Date : 24 octobre 2026. Statut : proposition pour relecture.
Dépendance : [migration de référence](2026-10-24-reference-astra-4.13.8-design.md).

## Objectif et source

Reprendre les corrections applicables au catalogue et au rendu social du port :
ID de titres SVG répétés, reconnaissance du préfixe e-mail, libellés et respect
des attributs SVG. Le choix et l'ordre des réseaux, les couleurs et les tailles
configurables existants ne changent pas de format.

Origine : SOC-01/SOC-02 de l'[inventaire](../../inventaires/2026-10-24-astra-4.11.0-4.13.8.md),
notes 4.11.11, 4.13.5 et 4.13.7. Sources 4.13.8 : `assets/svg/svgs.json`,
`inc/builder/controllers/class-astra-builder-ui-controller.php` et le CSS principal.

## Approches et choix proposé

Conserver les SVG 4.11.0 garderait les identifiants dupliqués. Permettre tous les
SVG utilisateurs ajouterait une fonction et une politique d'assainissement qui
ne sont pas nécessaires à cette correction. Le choix proposé actualise le
catalogue fermé des réseaux déjà proposés, avec comparaison entrée par entrée,
et reprend la protection de préfixe dans le rendu existant.

Le remplacement global de svgs.json ne vaut pas ajout automatique de ses réseaux.
Les marques et licences propres aux glyphes restent vérifiées ; les notices
Font Awesome, Google Reviews et autres sources existantes sont conservées ou
actualisées en fonction des tracés réellement incorporés.

## Contrats du rendu

### Identifiants et nom accessible

Le catalogue 4.11.0 contient trois titres à ID fixe : `email`, `google_reviews`
et `trip_advisor`. Le catalogue 4.13.8 retire ces trois ID. Le port ne propose
pas TripAdvisor : la comparaison entrée par entrée le mentionne comme réseau
absent, sans l'ajouter au registre ni créer un correctif de rendu fictif.

Pour les deux réseaux présents, le SVG e-mail ne produit plus de `title id="email"`
fixe ; même exigence pour `google_reviews`. Aucune commande ou référence ARIA
du port ne doit rester attachée à un identifiant supprimé.

Les liens gardent un nom accessible traduit, correspondant au réseau choisi,
indépendant du nombre d'occurrences. Si une référence interne SVG exige un ID,
il doit être unique par occurrence et ses références réécrites ensemble ; ne
pas simplement préfixer un ID sans contrôler ses consommateurs.

Les ID déjà uniques des menus et widgets ne sont pas réécrits au titre de ce
correctif. Un contrôle DOM de la page entière distingue les identifiants sociaux
des autres composants.

### E-mail et conservation des données

Une adresse nue reçoit `mailto:`. Une URL commençant déjà par ce schéma,
indépendamment de sa casse (`mailto:`, `MAILTO:`, `MaIlTo:`), ne reçoit pas de
second préfixe. Une valeur vide reste vide selon le traitement courant.
La reconnaissance du préfixe se fait au rendu ; elle ne réécrit pas les valeurs
persistées et n'invalide pas les empreintes du seed.

Le registre ne gagne pas implicitement téléphone ou WhatsApp, même si la note
amont traite ces réseaux dans la même entrée. Les liens HTTP(S), fragments et
adresses des autres réseaux déjà pris en charge ne sont pas convertis en e-mail.
Les attributs restent échappés après transformation.

### SVG, couleurs et labels

Le CSS 4.13.8 respecte les SVG qui portent un attribut fill explicite. Comparer
ce changement avec les glyphes réellement sélectionnés et les modes de couleur
actuels ; conserver les remplissages du catalogue, les couleurs normales/survol,
les styles de marque et les options personnalisées supportées.

Les labels publics sont fournis par le registre et les traductions SPIP ; une
correction du sélecteur d'icônes du Customizer n'est pas un motif pour copier
son interface ou ses libellés internes. Toute modification de tracé visible
est répertoriée et mesurée face au même réseau dans WordPress 4.13.8.

## Sécurité et limites

Aucun support de SVG utilisateur ou HTML arbitraire n'est ajouté. La correction
de collisions d'ID n'est pas qualifiée de XSS et n'implique pas un checkout Stripe
sur SPIP. Les protections du rendu de liens sont maintenues.

La vérification plus générale des protocoles d'URL et des droits des formulaires
est affectée à la qualification de sécurité 4.12.5 dans la spec de référence.
Elle ne peut pas être déclarée résolue par le seul échappement d'attribut,
ni être présentée comme une vulnérabilité exploitable sans démonstration du
chemin concerné. Un défaut établi dans le périmètre existant est traité et testé
avant la sortie de la migration ; pas d'élargissement opportuniste aux hooks WP.

## Preuves attendues

1. Tests de rendu d'adresse nue, de trois casses du schéma, de chaîne vide et
   d'un lien d'un autre réseau ; aucune modification du tableau enregistré.
2. Une page avec e-mail et Google Reviews placés en en-tête ordinateur, mobile
   et pied. Contrôle de tous les ID rendus, même lorsque masqués visuellement.
3. Noms accessibles présents et cohérents dans au moins les langues FR/EN
   existantes, sans référence ARIA vers un ID supprimé.
4. Même catalogue fermé et même ordre avant/après enregistrement du formulaire ;
   relance du seed Garden sans personnalisation artificiellement détectée.
5. Comparaison avec WordPress 4.13.8 des réseaux supportés, modes de couleur,
   taille et survol à 1280/768/390 px. Une vérification DOM complète les captures.
6. Source, tracé, licence et notices vérifiés pour chaque entrée modifiée.

Les ajustements de R10/R15 et du cas combiné ne suffisent pas à prouver l'unicité
si leur configuration ne place qu'une seule occurrence. Une fixture de duplication
est donc obligatoire. Les autres références générales sont rejouées dans la
migration intégrée ; aucune augmentation de seuil n'est faite pour masquer un
changement de glyphes non décrit.

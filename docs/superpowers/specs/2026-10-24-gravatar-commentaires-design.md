# Gravatar facultatif dans les commentaires — spécification

Date : 2026-10-24. Statut : exécution autorisée par Gilles le 2026-10-26.

## Objectif et décision

Afficher l'avatar fourni par le plugin SPIP Gravatar dans les commentaires d'Astra SPIP
lorsqu'il est actif et qu'une image est disponible. Sinon, conserver l'initiale actuelle.

Gilles a retenu ce mécanisme facultatif : Gravatar peut être utilisé sous SPIP 4.4 ; sous
SPIP 5, où ce plugin ne peut pas être activé, Astra SPIP conserve son repli. Aucune installation,
activation ni adaptation de Gravatar pour SPIP 5 n'est demandée.

Le commentaire de `css/astra-spip.css`, « SPIP ne fournit pas de gravatar », devient une
description exacte : l'initiale est le repli en l'absence d'image fournie par le plugin Gravatar.

## État actuel et sources

- `inclure/commentaire.html`, boucle `FORUMS` : chaque message affiche un `span` de classes
  `avatar avatar-50 photo ast-avatar-initiale`, dans `.ast-comment-avatar-wrap`. Son contenu est
  la première lettre du nom, après traitement typographique, suppression des espaces et passage
  en majuscule ; un nom vide donne `?`.
- `css/astra-spip.css` : initiale ronde de 50 px sur ordinateur, 2,5 em sous 921 px ; fond,
  couleur, bordure et ombre propres à ce substitut.
- Astra 4.13.8, `inc/blog/single-blog.php`, fonction de rendu des commentaires :
  `.ast-comment-avatar-wrap` contient `get_avatar($comment, 50)`. Les classes et styles du thème
  constituent la référence pour l'image.
- [Plugin Gravatar](https://git.spip.net/spip-contrib-extensions/gravatar), version 3.0.0,
  sources examinées au commit `dba78603edcb8c48fa55230f15b4dbecb97b8993` :
  `balise/gravatar.php` expose `#GRAVATAR{email, taille, défaut}` ; `gravatar_fonctions.php`
  fournit `gravatar()` et `gravatar_balise_imgx2()`. Le premier récupère l'image et la met en
  cache sur le serveur SPIP ; le second produit une image à la taille d'affichage demandée.
  `paquet.xml` déclare SPIP `[4.2.0;4.*]`.
- Plugin Forum de SPIP : dans une boucle `FORUMS`, le champ `email_auteur` porte l'adresse du
  contributeur ; `#EMAIL` en est aussi un alias déclaré dans `base/forum.php`.

## Approche retenue

L'intégration facultative et le repli ont été choisis dans la conversation. Le détail technique
retenu ici est un filtre PHP d'Astra, toujours disponible, appelé par le squelette.

Deux possibilités ont été examinées :

- Appeler directement `#GRAVATAR` dans une condition `#PLUGIN{gravatar}` : simple en apparence,
  mais la balise propre au plugin peut être compilée même lorsque la condition n'est pas
  satisfaite. Ce choix ne garantit pas un squelette compilable sans le plugin.
- Passer par un filtre d'Astra : vérifier l'activation, charger les fonctions du plugin, puis
  déléguer la récupération et la production de l'image à ses fonctions. Le squelette ne dépend
  d'aucune balise optionnelle. C'est l'approche retenue pour assurer le repli sans Gravatar.

Astra ne réimplémente ni le protocole Gravatar, ni le calcul de ses identifiants, ni son cache.

## Périmètre

- Messages de forum affichés par `inclure/commentaire.html`, sur les articles et les pages
  uniques, réponses comprises.
- Utilisation de l'adresse enregistrée sur le message ; aucun changement de stockage ni de
  collecte d'adresse. Aucun ajout de recherche d'adresse dans la fiche d'un auteur.
- Dépendance facultative déclarée par `<utilise>` dans `paquet.xml`, pour la version examinée
  de Gravatar et son API. Le plugin n'est jamais installé ou activé par Astra SPIP.
- Aucun nouveau réglage Astra, aucune modification de Garden ou de wp2spip.
- Les logos des auteurs, les archives d'auteurs et les autres usages de `#LOGO_AUTEUR` restent
  hors du périmètre de cette adaptation. Gravatar peut avoir ses propres effets sur ces balises.

## Choix de l'avatar

Une fonction/filtre d'Astra, nom à fixer dans le plan, reçoit l'adresse du message et renvoie
uniquement l'image HTML, ou une chaîne vide. Le squelette conserve son `span` actuel en repli.

| État | Résultat |
|---|---|
| Gravatar absent ou inactif | Aucun appel à ses fonctions ; initiale actuelle |
| Plugin actif, fonctions attendues indisponibles | Chaîne vide ; initiale actuelle |
| Plugin actif, adresse absente ou invalide | Aucun téléchargement ; initiale actuelle |
| Plugin actif, image disponible dans son cache ou récupérée par lui | Image Gravatar |
| Plugin actif, aucune image disponible ou récupération infructueuse | Initiale actuelle |

Le garde-fou principal est `test_plugin_actif('gravatar')`, avant tout appel ou chargement propre
au plugin. La seule présence d'une fonction PHP ne suffit pas à considérer le plugin actif.
Les fonctions du plugin sont chargées par les mécanismes SPIP et vérifiées avant leur appel.

La recherche utilise le comportement sans avatar de remplacement du service (`défaut = 404`) :
si aucune image n'est disponible, le substitut reste l'initiale d'Astra SPIP. La configuration
globale de taille ou d'image par défaut de Gravatar ne remplace pas ce contrat local.

L'adresse ne figure ni dans le HTML, ni dans les attributs de l'image. Astra utilise le chemin
local retourné par le plugin et ne construit aucune URL vers le service distant. L'accès au
service, ses délais et son cache restent entièrement ceux du plugin.

## Balisage, dimensions et accessibilité

- Conserver `.ast-comment-avatar-wrap` et sa place dans `.ast-comment-info` ; une seule image
  ou une seule initiale par message. Le reste du balisage du commentaire est inchangé.
- L'image porte les classes `avatar avatar-50 photo`, sans `ast-avatar-initiale`. Les styles
  de couleur et de fond du substitut ne lui sont pas appliqués.
- Taille nominale : 50 px, comme `get_avatar(..., 50)` d'Astra 4.13.8. Vérifier les dimensions
  effectives sur tablette et mobile contre la référence WordPress ; utiliser les règles du
  thème pour l'image. L'initiale garde exactement ses dimensions actuelles.
- Le HTML produit par `gravatar_balise_imgx2()` peut contenir une taille intrinsèque doublée et
  une largeur en style inline. Adapter les attributs de présentation nécessaires pour que ces
  détails n'empêchent pas le dimensionnement responsive d'Astra ; conserver la source locale.
  Cette adaptation utilise les filtres d'attributs/image de SPIP, sans remplacement global du HTML.
- L'avatar est décoratif, le nom étant déjà présent à côté : `alt=""` pour l'image et absence
  d'annonce supplémentaire, comme l'initiale actuelle marquée `aria-hidden="true"`.
- Ne pas ajouter le nom ou l'adresse comme texte alternatif, titre ou lien.

## Compatibilité et absence de régression

Sans Gravatar actif, le HTML des commentaires et leur rendu sont identiques à ceux de la
version précédente. Le filtre est disponible indépendamment du plugin ; aucun `#GRAVATAR` ni
autre balise du plugin n'apparaît dans les squelettes d'Astra.

Une activation ou désactivation suivie du recalcul usuel des squelettes doit permettre de passer
de l'image à l'initiale, et inversement, sans erreur de compilation ou de fonction manquante.
La validation du plugin Gravatar est limitée à SPIP 4.4. Le scénario sans plugin couvre le chemin
de repli d'Astra ; aucun essai d'activation de Gravatar sous SPIP 5 n'est à ajouter.

## Preuves et critères d'acceptation

1. Tests de la décision : absent, inactif même si les fonctions sont chargées, fonctions
   indisponibles, adresse vide/invalide, image disponible, absence d'image. Vérifier notamment
   qu'aucun appel au fournisseur d'avatar n'a lieu sans plugin actif.
2. Intégration SPIP 4.4 : rendre un commentaire et une réponse avec le vrai plugin actif, puis
   sans le plugin ; vérifier la source locale, les classes, l'unicité de l'avatar, le texte
   alternatif vide et le repli. Faire le même contrôle sur une page unique avec commentaires.
3. Le rendu sans plugin, y compris les noms accentués et vides, garde l'initiale et son HTML
   actuels. Compiler les squelettes dans un environnement où les fichiers Gravatar sont absents.
4. Comparaison à WordPress sous Astra 4.13.8 avec un avatar de test local identique des deux
   côtés : captures et styles calculés à 1280, 768 et 390 px (dimensions, forme, alignement,
   espacement avec le nom). Aucune dépendance à un compte Gravatar réel.
5. Tests déterministes : utiliser une image de test locale et, pour le vrai plugin, un cache
   préparé avec son mécanisme réel : sel connu, image `.jpg` valide et récente pour le succès,
   marqueur `.vide` récent pour l'absence d'image. Ne pas effectuer d'appel réseau dans les tests
   automatisés et ne pas modifier le code du plugin pour les faire passer.
6. Les suites existantes continuent de passer, ainsi que les références R0–R24 et P1–P3
   disponibles lors de l'implémentation. Aucun changement de contenu, de formulaire de réponse
   ou de fil.

Les résultats doivent identifier les commits d'Astra SPIP et de Gravatar, la version de SPIP,
le navigateur, les dimensions et les commandes. Les preuves sont conservées suivant la
convention de qualification du dépôt (preuves versionnées avec la PR, liées au SHA testé), avec la source et la licence de l'image de test.

## Crédits et suite

Mettre à jour `README.md` (intégration facultative) et `CREDITS.md` : plugin Gravatar, version/API
examinées, dépôt, auteurs déclarés (Thomas Beaumanoir, Clever Age, Fil, Cedric) et licence amont.
Aucune source du plugin n'est copiée dans Astra SPIP ; sa licence est relevée dans le fichier
`LICENSE` de la révision utilisée (texte GNU GPL v3 dans la révision examinée). Citer
`inc/blog/single-blog.php` d'Astra 4.13.8 pour le balisage.

La version de livraison et les noms des nouveaux fichiers/filtres seront fixés dans le plan,
après validation de cette spec. L’implémentation est autorisée par la demande de Gilles du 2026-10-26.

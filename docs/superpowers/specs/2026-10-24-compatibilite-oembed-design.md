# Compatibilité avec le plugin oEmbed

Date : 24 octobre 2026. Statut : proposition pour relecture.
Dépendance : [référence Astra 4.13.8](2026-10-24-reference-astra-4.13.8-design.md).
Décision de Gilles : un plugin recommandé est déclaré en `<utilise>`.

## Objectif et périmètre

Afficher correctement les contenus intégrés qui sont délégués au plugin SPIP
oEmbed, notamment les blocs WordPress embed importés par wp2spip. Astra prend
en charge leur insertion dans la mise en page ; il ne réimplémente pas la
découverte des fournisseurs, les requêtes oEmbed ou leur cache.

Sources vérifiées : [oEmbed](https://git.spip.net/spip-contrib-extensions/oembed)
version 3.5.1, commit `e90f6a6db27f763ad78ad36c0398b39aeff4cf8c`, README,
`modeles/oembed_video.html`, `css/oembed.css`, pipelines de traitement ;
`wp2spip_bloc_embed()` dans wp2spip au commit
`e3c262720b518bc7007e5f37779a9d6ca84f17f1`.

Origine Astra : EMBED-01, corrections annoncées en 4.11.13/4.11.18. Le delta
4.13.8 dans `inc/class-astra-after-setup-theme.php` ajoute le support responsive
et évite d'envelopper deux fois certains contenus de blocs. Cette logique de
filtres WordPress n'est pas à transposer littéralement à SPIP.

## Approches et choix proposé

- Garder seulement l'URL est un repli fonctionnel, mais ne fournit pas de lecteur.
- Copier le moteur WordPress ou coder un résolveur dans Astra dupliquerait le
  plugin SPIP et rendrait ses mises à jour indépendantes inutilisables.
- **Choix proposé : intégration facultative native**. wp2spip conserve l'URL et
  la légende ; oEmbed produit le contenu ; Astra assure uniquement la compatibilité
  de présentation réellement nécessaire.

Le manifeste contient `<utilise nom="oembed" />`, sans `<necessite>`. La déclaration
est déjà ajoutée sur la branche issue de l'inventaire ; sa présence reste vérifiée
lors de l'intégration. Le thème n'installe ni n'active le plugin automatiquement.

## Contrats fonctionnels

### Sans oEmbed

L'URL importée et sa légende restent lisibles ; l'URL constitue un lien utilisable.
Aucun appel de fonction ou de balise optionnelle n'échoue pendant la compilation
ou l'affichage. Les articles sans contenu intégré gardent leur rendu. Aucun
contenu n'est réécrit lors de l'activation/désactivation du plugin.

### Avec oEmbed

Les URL reconnues et autorisées par le plugin sont traitées par ses propres
pipelines. Le thème conserve son HTML, ses classes, son bouton éventuel de
lecture, ses crédits et ses légendes. Il n'ajoute pas d'enveloppe responsive Astra
autour d'un conteneur qui remplit déjà ce rôle.

La largeur ne dépasse pas le conteneur de contenu disponible. Les proportions
proviennent des données du média ; pas de 16:9 imposé à tout fournisseur. La
lecture ne déforme pas la page et ne crée pas de débordement horizontal à 1280,
768 et 390 px. Légendes et marges restent cohérentes avec le gabarit et le plugin.
Les styles ne ciblent pas toutes les iframes du site pour corriger un seul lecteur.

Si le CSS actuel suffit, aucun nouveau CSS n'est ajouté. Une adaptation nécessaire
est limitée au contexte de contenu Astra et aux classes du plugin, sourcée et
testée. Le chargement de sa feuille utilise les mécanismes habituels du plugin ;
le thème ne duplique pas l'inclusion et ne remplace pas ses modèles par défaut
sans nécessité démontrée.

### Contenu déjà intégré et échecs

Un contenu ancien possédant déjà son wrapper ou son iframe ne gagne pas une
seconde enveloppe au rendu. Les chemins d'import différents (URL extraite d'un
bloc, HTML conservé) sont caractérisés au lieu d'être présumés équivalents.
Une URL inconnue, une réponse fournisseur invalide ou un service indisponible
restent soumis au repli du plugin ; le thème ne supprime ni l'URL ni le contenu.
Aucune visite ordinaire ne réécrit l'article pour « réparer » le fournisseur.

## Compatibilité déclarée

Le paquet oEmbed examiné déclare SPIP `[4.1.0;4.*]` et nécessite le plugin médias.
L'intégration est testée sous SPIP 4.4 avec cette version d'oEmbed. Le thème
conserve sa propre plage de compatibilité ; la déclaration facultative ne rend
pas le plugin tiers compatible avec SPIP 5.

Sous SPIP 5, le cas sans oEmbed doit rester fonctionnel. Aucun forçage d'activation
ni test d'un plugin dont le manifeste l'exclut n'est demandé. La validation sur
cette version se limite à un environnement SPIP 5 disponible et au chemin sans
plugin ; si cet environnement n'est pas disponible, le statut est indiqué.

## Preuves attendues

Les essais automatisés emploient des fournisseurs/réponses locales contrôlées,
sans disponibilité obligatoire de YouTube/Vimeo ni téléchargement de vidéos.
Ils doivent passer par le vrai traitement du plugin, avec un point d'injection
ou un fournisseur de test ; afficher un HTML écrit à la main ne teste pas cette
intégration. Les fixtures n'incorporent aucun média tiers non autorisé.

| Cas | Vérification |
| --- | --- |
| Bloc embed importé, plugin absent | URL utilisable, légende conservée, aucune erreur de compilation/rendu |
| Même contenu, plugin présent | Contenu produit par oEmbed, traitement effectué une fois |
| Vidéos 16:9 puis 4:3 | Proportions mesurées sur le conteneur/iframe, largeur bornée aux trois tailles |
| HTML déjà enveloppé | Pas de second wrapper ni multiplication du ratio/padding |
| Fournisseur inconnu ou en erreur | Repli utile, contenu conservé, pas d'exception du thème |
| Activation puis désactivation | Texte enregistré identique, retour au lien sans réimport |
| Article sans embed | Aucun changement de rendu lié à l'intégration |

Le contrôle mesure géométrie, styles et nombre de conteneurs. Une identité HTML
avec les wrappers WordPress n'est pas exigée : c'est une adaptation SPIP déclarée.
La comparaison WordPress 4.13.8 s'effectue avec un média/iframe de test contrôlé
qui présente les mêmes dimensions ; les pixels d'un lecteur externe vivant ne
constituent pas une référence reproductible.

Les éventuels changements de conversion wp2spip relèvent de son dépôt et de ses
tests ciblés ; ils ne sont faits que si un défaut y est établi. Le lancement de
sa préparation complète n'est pas une condition de validation de cette spec.

## Sortie et provenance

README, `<utilise>` et comportement avec/sans plugin concordent. Les adaptations
éventuelles portent leurs crédits et licences ; le plugin et ses sources ne sont
pas embarqués dans Astra. Les deux entrées V sur les embeds sont closes avec
la répartition réelle des responsabilités et les mesures du cas d'import.

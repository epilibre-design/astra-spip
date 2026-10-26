# Gravatar dans les commentaires : qualification

Code testé : `d61257073726294e6b3525adfa750ba7757ea939`, base main `0a70f60`. SPIP 4.4.28,
WordPress 7.1.3 / Astra 4.13.8 ; vrai Gravatar 3.0.0 épinglé dans le manifeste.

145 tests unitaires / 1806 assertions ; 102 tests d’intégration / 645 assertions avec
Gravatar actif (trois cas oEmbed ignorés, car leurs clones ne sont pas cette installation).
Sans les fichiers du plugin : les trois tests Gravatar passent, 16 assertions ; la suite
complète sans plugin a aussi passé, 102 tests / 639 assertions, avant le correctif de marge
conditionnel vérifié ensuite sur l’image et le repli.

41 références historiques, 311 vérifications : aucune différence de style calculé,
écart visuel maximal 0.23 %. Aucun seuil augmenté, aucun attendu historique modifié.

Aux largeurs 1280/768/390 : octets JPEG identiques, dimensions, rayon, bordure, ombre et
espacement du nom identiques ; captures des avatars à 0,00 %. La taille nominale de 50 px
subit le même léger rétrécissement flex que WordPress et devient au plus 2.5 em sous 921 px.
Les captures sont stabilisées à l’origine du recadrage seulement, après la mesure.
Les deux fixtures ont le même conteneur étroit et les mêmes métadonnées affichées ; cela
isole l’avatar et ne constitue pas une qualification complète des formats de date ou des
barres latérales. Aucune requête externe observée. Le cache natif frais et un verrou de test
empêchent les téléchargements automatiques pendant les essais.

Le navigateur vérifie également les réponses et la page unique. Après désactivation et
retrait physique du plugin : initiales É/A présentes, aucune image ni classe propre à
Gravatar, sur article/page aux trois largeurs ; compilation sans balise optionnelle.
Le HTML du repli est comparé intégralement au squelette `0a70f60`, noms accentués et vides
compris. Le reste du commentaire et la collecte des adresses ne changent pas.

Aucun test d’activation du plugin sous SPIP 5. Aucun changement de version ni tag.
[Procédure reproductible](../../../tests/fixtures/gravatar/README.md),
[manifest et empreintes](manifest.json), [décisions](decisions.md).

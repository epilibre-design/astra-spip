# Décisions d’exécution

- Ruling: preuves versionnées dans la PR avec le code, comme les qualifications récentes — revue atomique et SHA vérifiable — coût : les fichiers ne vont pas automatiquement sur la branche historique preuves.

- Task 2: Ruling: inserer_attribut exige vider=true pour retirer style — test réel rouge/vert — coût : aucun style résiduel.

- Task 2: Ruling: sinon évite l’espace émis par la condition non — HTML intégral identique à 0a70f60 sans avatar — coût : aucun contenu ajouté.

- Task 2: Ruling: remettre le clone SPIP à R0 avant les suites — sa copie reprenait un réglage de transparence mesuré, hors état initial des tests — coût : remise à zéro seulement du site privé.

- Task 3: Ruling: neutraliser la marge du substitut seulement avec une image (classe ast-avatar-gravatar) — le vrai Astra a 0 au bureau et 0.5 em sous 921 ; test navigateur rouge puis vert — coût : aucune modification du repli sans plugin.

- Task 3: Ruling: même disposition étroite 750/sans barre latérale et mêmes dates affichées dans les deux fixtures — éviter d’attribuer au Gravatar des différences de largeur de contenu préexistantes — coût : la comparaison ne certifie pas toute la mise en page des commentaires.

- Task 3: Ruling: arrondir l’origine des captures par position relative après les mesures, jamais par transform — transform rééchantillonne à partir d’origines différentes — coût : seul le placement absolu dans la page est normalisé, dimensions/styles/espacement restent contrôlés ; pixels désormais 0 %.

- Task 3: Ruling: isoler le correctif de géométrie et ses pilotes avant le commit des preuves — les rapports peuvent ainsi identifier un SHA testé immuable — coût : un commit supplémentaire dans la tâche, sans changement de portée.

- Final: Ruling: compléter manuellement la provenance navigateur exigée par la spec, sans modifier le pilote après la revue — binaire inchangé, hash/version/mtime consignés — coût : un futur essai devra relever à nouveau son environnement tant que l’automatisation est différée.

- Final: Ruling: intégration/navigateurs non relancés par le reviewer car mutent l’état — résultats, pilotes et empreintes examinés, exécutions auteur conservées — coût : seconde validation uniquement en lecture, pas double campagne avec état.

- Final: Ruling: récupération réseau, expiration/concurrence/corruption du cache restent du ressort du vrai plugin — tests déterministes sur cache frais réel — coût : la disponibilité du service ne fait pas partie de cette qualification.

- Final: Ruling: ne pas ajouter d’activations Gravatar sous SPIP5 ni de nouvelles campagnes SPIP4.2/4.3 — demande explicite limitée à 4.4, repli compilé sans plugin — coût : pas de preuve d’exécution de ces environnements supplémentaires.

- Final: Ruling: PHP7.4 garanti ici par lint, suites exécutées sous8.4.27 — même API PHP7.4 de production — coût : aucune suite complète sous7.4 revendiquée.

- Final: Ruling: Firefox/Safari, RTL, zoom, densités multiples, CSS tiers et images non carrées restent hors qualification — aucun nouveau support annoncé — coût : d’autres preuves seront nécessaires pour ces cas.

- Final: Ruling: qualifier l’avatar et son espacement dans des contextes identiques, pas toute la page ni PR23 — branche indépendante, styles conditionnels — coût : formats de date, barres latérales et positions absolues restent distincts.

- Final: Ruling: effets Gravatar sur logos/auteurs/archives/réglages/SVP/futures3.x non évalués — délégation à l’API3.0.0 examinée — coût : évolutions amont à suivre séparément.

- Final: Ruling: attributs décoratifs vérifiés, pas de certification lecteur d’écran, charge ou interruption brutale des fixtures — périmètre de preuve explicite — coût : pas de promesse au-delà des parcours exercés.

## Point mineur différé

Final: minor (deferred): le pilote ne consigne pas automatiquement browser.version() ; relevé manuel version/Playwright/hash du binaire conservé pour identifier les essais actuels, automatisation future distincte.

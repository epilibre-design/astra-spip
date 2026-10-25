# Décisions d’exécution

- Ruling: réutiliser le WordPress privé Astra 4.13.8 de la migration (8421), copier son SPIP de test dans ce worktree et servir en 8444 — garde de cible et base isolée — coût si incorrect : preuve sur mauvaise source, détectée par verifier.php.

- Ruling: extraire les briefs français manuellement, le script task-start ne reconnaît que Task — aucune modification du plan approuvé — coût : suivi manuel des bornes, contrôlé par git log.

- Task 2: started. Ruling: l’ancien test global sans desktop exclut désormais l’identité, dont le contrat change explicitement — maintien des protections pour les boutons — coût : couvert par StylesIdentiteResponsiveTest et R29.

- Task 3: Ruling: configurer GD2 dans les tests d’image et restaurer la méta ensuite — sans moteur configuré image_reduire conserve la source et le défaut ne se reproduit pas — coût : test spécifique à un moteur réellement supporté, autres chemins couverts par le repli historique.

- Task 3: Ruling: à 922 px sans JS, Astra 4.13.8 et le port masquent tous deux leurs deux en-têtes ; les bornes contrôlent le CSS et le rendu avec JS, les trois largeurs de la spec contrôlent aussi la visibilité sans JS — pas de changement non demandé du runtime amont — coût : limite 922 sans JS préexistante conservée et signalée.

- Task 3: Ruling: rendre le cas transparent explicitement sur Notre jardin côté référence/configuration — le CSS du blog seul ne prouverait pas l’activation — coût : pilote de référence de page supplémentaire, vérifié par classe body.

- Task 4: Ruling: convertir les booléens en chaînes pour le seul chargement CVT — protege_champ(false) vaut chaîne vide avant nos champs, constaté en navigateur et test dédié RED — stockage typé conservé par la fusion — coût : affichage désormais correct aussi pour les autres booléens déjà supportés, vérifié par la suite.

- Ruling: qualifier une seule fois la matrice historique sur l’arbre combiné des deux lots — les suites et e2e propres à chaque tâche restent exécutés — coût : qualification commune explicitement liée au même SHA final.

- Ruling: le clone WordPress copiait DB_PATH absolu ; correction de DB_PATH/WP_HOME/WP_SITEURL avant les mesures Gravatar — les variantes R29 pendant le chevauchement seront recapturées et requalifiées — coût : quelques mesures responsive à refaire, aucune sortie concurrente conservée comme preuve finale.

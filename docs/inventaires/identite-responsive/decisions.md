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

- Final: Ruling: reviewer gpt-6-astra indisponible par quota avant tout avis ; reprise de la même revue par gpt-6.1-sol high disponible — conserver une véritable revue indépendante — coût : autre modèle, aucun verdict initial à substituer.

- Final: Ruling: visibilité absente garde le défaut propre à son appareil, même après desktop=false — contrat explicitement approuvé et tests — coût : d’anciennes valeurs partielles deviennent actives comme annoncé.

- Final: Ruling: conserver la limite amont sans JS à 922 px — même absence mesurée des deux côtés, hors extension demandée — coût : ce cas limite reste sans en-tête.

- Final: Ruling: RTL global, anciens profils non flex, logos distincts/SVG, Retina et extensions tierces restent hors périmètre — pas de capacité nouvelle promise au-delà des deux specs — coût : ces usages nécessiteront d’autres lots.

- Final: Ruling: le test d’inclusion CSS n’est pas seul une preuve d’exhaustivité/ordre — compléter son verdict par lecture du code/amont, tests d’ordre, rendus et neuf mutations — coût : une variation future doit conserver cette qualification complète.

## Point mineur différé

Final: minor (deferred): POST forgé avec feuille atomique remplacée par tableau vide (`mobile[]=`) peut supprimer cette valeur sans erreur ; mécanisme antérieur, DOM normal non concerné ; suivi distinct.

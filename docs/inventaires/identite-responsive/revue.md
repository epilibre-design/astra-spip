# Revue indépendante finale

Revue de `0a70f60..87751d6`, par gpt-6.1-sol (effort high), le 26 octobre 2026.
Le premier reviewer gpt-6-astra a atteint son quota sans fournir d’avis ; le reviewer
remplaçant a travaillé en contexte neuf, en lecture seule, sans base de données ni services.

Avis : intégration possible, aucun défaut Critical ou Important identifié.
Vérification indépendante : 185 tests unitaires / 2039 assertions, cinq tests Python ;
empreintes des manifestes concordantes ; code, oracles, parcours CVT et décisions relus.

## Point mineur différé

Un POST forgé `s[font-size-site-title][mobile][]=` peut effacer la valeur mobile sans
produire d’erreur de champ ; idem pour un alignement. Ses voisins sont conservés.
Ce mécanisme existait avant le lot pour les champs déjà éditables et le DOM du formulaire
ne produit jamais cette structure. Il est signalé pour un suivi distinct ; aucune correction
non testée ni extension du validateur général n’est ajoutée à ce lot.

Reproduction pure : `astra_styles_saisie_refusees(['font-size-site-title'=>['mobile'=>['']]])`
renvoie `[]` ; après fusion/normalisation sur desktop 20, mobile 12, seul desktop 20 reste.

## Limites et décisions confirmées

Le défaut propre à chaque appareil pour les booléens absents est le contrat approuvé.
L’absence d’en-tête à 922 px sans JavaScript est une limite amont relevée des deux côtés.
RTL global, anciens profils non flex, logos distincts/SVG, Retina et extensions tierces
d’images ne sont pas promis. Le test CSS par inclusion ne prouve pas seul l’absence de
règles supplémentaires : le verdict s’appuie aussi sur le code/amont, les tests d’ordre,
les rendus et les mutations. Les coûts de ces décisions sont dans `decisions.md`.

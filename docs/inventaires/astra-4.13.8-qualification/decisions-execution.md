# Décisions prises pendant l’exécution

Relevé des décisions du registre de travail ; les détails de qualification et limites restent dans le bilan et le manifeste. Les décisions sont regroupées par plan et conservées dans leur ordre d’inscription.

## migration-astra-4.13.8

1. Task 1: Ruling: wp core download tronque les chemins longs de php-ai-client, échec de verify-checksums reproduit sur le téléchargement neuf et la copie d'étude — utiliser l'archive officielle épinglée SHA256 et tar système, puis verify-checksums — coût : dépendance curl/tar explicite. Les dossiers incomplets sont conservés renommés, aucune base partagée touchée.

2. Task 1: Ruling: le WP neuf en_US ne reprend pas la langue de l'ancien package fr_FR — installer les traductions du cœur et d'Astra et activer fr_FR ; les 35 captures ont été refaites en français.

3. Task 1: Ruling: le WP neuf à un seul article ne déclenche pas le retour en haut lors de la capture du pied, écart mobile 2,27 % sans changement produit — préparer 12 articles de démonstration supplémentaires, qui activent aussi la pagination existante ; refaire les preuves sur cet état stable. Comparateur interrompu par PID, serveurs conservés.

4. Task 2: Ruling: poursuivre les assets sur le candidat pendant la comparaison historique du site figé — scripts et thème/bases historiques indépendants, aucun fichier historique ne change — coût : Task 1 clôturée après ses preuves asynchrones, et non par anticipation.

5. Task 1: Ruling: ajouter aussi la garde à comparer_captures.sh avant toute mutation des sites — c'est un quatrième point d'entrée de capture qui appelle directement wp eval-file. Refus réel du WordPress 4.11.0 constaté avant ouverture du SPIP. L'installateur désactive mises à jour automatiques/cron dans les nouveaux sites de test pour garder la source immuable.

6. Task 3: Ruling: filtrer les liens au rendu sans normaliser leur stockage — la note générique de sécurité impose d’examiner ces chemins, et le défaut réel est établi ; les empreintes Garden et anciens réglages restent inchangés. Coût si incorrect : un protocole hors liste native WordPress cesse de fonctionner au rendu ; aucune prétention à reproduire les filtres extensibles de WordPress.

7. Task 3: RED du rejeu étendu : R25-nav-vide avait 1,77 % et des hauteurs 192/81, puis timeout de capture de l’en-tête mobile invisible. Ruling: sa fixture WP doit partir de R0 comme sa configuration SPIP, les deux conservent un ordinateur par défaut et vident seulement le mobile — coût : référence ajoutée corrigée, aucune modification des 35 configurations historiques. Le comparateur exige une absence explicite dans le JSON, vérifie séparément sur les deux sites que masthead n’est pas visible et qu’aucun panneau/déclencheur n’existe, puis enregistre un rapport d’absence plutôt qu’un faux pixel vert.

8. Final: Ruling: conserver l’espace final du readme officiel licenses/astra-4.13.8-readme.txt — la notice est une copie intacte de la source épinglée, ce n’est pas un défaut du code du port ; seul signal résiduel de git diff --check — coût : ce contrôle global signale une ligne amont connue, les fichiers écrits pour la migration passent.

9. Final: Ruling: empêcher les traitements textuels des URL validées plutôt que dupliquer le parseur HTML de SPIP dans astra_url_lien — attribut_html{0} conserve l’échappement, VALEUR** désactive safehtml automatique uniquement pour la donnée sociale déjà validée ; HTML et commentaires restent du texte dans l’attribut. Le littéral {false} compile en chaîne PHP 'false' vraie : seul {0} convient — coût : maintenir ensemble validation PHP et échappement explicite, couverts par le href final et Chromium.

10. Final: Ruling: ne pas corriger la découverte SSRF d’oEmbed dans Astra — responsabilité du plugin/SPIP et absence de certification annoncée ; coût : le chemin non protégé reste une limite du plugin, non résolue ici.

11. Final: Ruling: ne pas forcer oEmbed sous SPIP 5 — plugin déclaré incompatible et aucun environnement qualifié ; coût : seule la restitution native des URL sans plugin est promise.

12. Final: Ruling: ne pas étendre la qualification aux anciens HTML arbitraires hors import wp2spip testé — le chemin réel est explicitement documenté ; coût : pas de garantie de conteneur unique pour ces autres contenus.

13. Final: Ruling: conserver les choix noJS et widgets statiques — décisions explicites de Gilles, avec assertions dédiées ; coût : écart assumé au runtime WordPress.

14. Final: Ruling: conserver les limites responsives d’identité et les options non appliquées — préexistantes et documentées, pas nouvelles fonctionnalités de la migration ; coût : les écarts Garden correspondants restent ouverts.

15. Final: Ruling: ne pas porter la conversion wide/full dans Astra — perte wp2spip distinguée des sondes CSS natives ; coût : ces dispositions restent perdues lors de cet import.

16. Final: Ruling: ne pas inventer une mesure réelle à 921,95 px — seule l’absence de reproduction et les bornes entières sont attestées ; coût : ce cas fractionnaire demeure non qualifié.

17. Final: Ruling: ne pas annoncer les fonctions WordPress, tiers et RTL général absents — le périmètre existant reste l’autorité ; coût : aucune équivalence du thème complet hors de ce périmètre.

18. Final: Ruling: maintenir le bilan R13 et la notice officielle intacte — mêmes noms historiques et source amont inchangée ; coût : code brut HTML 1 et signal d’espace final restent expliqués plutôt que masqués.


## css-responsive-astra

19. Task 1: Ruling: avancer uniquement les outils purs de découpage pendant l'archivage A — pas de dépendance aux assets B ni aux nouvelles captures ; aucune couche produit ni attendu n'est modifié — coût : ordre des commits préparatoires différent du tableau de coordination, sans état mixte publié.

20. Task 2: Ruling: inclure aussi inc/styles/bouton.php et inc/styles/alignements.php — ces deux générateurs partagent les sélecteurs modifiés constatés dans la cible ; pas de nouvelle option — coût : deux modules de plus que la liste initiale. Mettre à jour les assertions de forme existantes et les neutralisations SPIP du bouton avec le sélecteur officiel 4.13.8 ; les valeurs 17/26 et la raison (14/28,12/24 figés) restent identiques.

21. Task 2: Ruling: R0 accueil seul ne contient pas les règles conditionnelles déjà portées — ajouter base/capturer.sh et preparer.php pour article/page/étroite/recherche/archive/404 ; le registre ne stocke que sélecteurs, contextes et ordre, les déclarations restent lues dans les nouvelles captures — coût : six requêtes de référence supplémentaires, WordPress remis en R0.

22. Task 2: Ruling: avancer la borne spécifique transparente dans ce lot — les attendus 4.13.8 rendaient rouges 3 variantes ; source dynamic.css.php emploie astra_get_tablet_breakpoint('',1) pour base et séparateur, alors que les logos gardent 921. Ne pas remplacer toutes les bornes — coût : cette partie de Task 3 est déjà couverte ici.

23. Task 2: Ruling: scoper la capture du pied au composant, en excluant seulement #ast-scroll-top fixé à la fenêtre — sa présence dépend du contenu non comparé et produisait 12 écarts historiques, sans différentiel du builder. Test PNG à deux hauteurs : suppression du masque détectée par mutation, masque rétabli vert, géométrie inchangée. Le bouton est qualifié séparément sur le vrai WordPress/SPIP, pas supprimé des preuves. Même normalisation appliquée à l'outil historique, sans modifier son code de produit ni ses fixtures 4.11.0 ; les bilans bruts sont conservés. Aucun seuil augmenté.

24. Task 2: Ruling: le contenu technique SPIP retire seulement les commentaires de délimitation Gutenberg — propre() produisait sinon des paragraphes supplémentaires autour de ces marqueurs, faussant le « bloc suivant ». Cette fixture prouve le CSS, pas l'import ; l'import réel wp2spip reste à Task 4 — coût : deux types de fixture à distinguer explicitement.

25. Task 3: Ruling: ajouter astra_responsive_menus_sans_js(array) et un fragment de liens séparé — les quatre sources sont partagées conceptuellement avec les menus actifs, mais le balisage interactif ne doit pas être repris dans le repli — coût : deux fichiers/contrats supplémentaires, couverts par parité de destinations pour racine/rubrique/mots/pages.

26. Task 3: Ruling: le comparateur HTML actif ignore uniquement un noscript contenant exactement une nav.astra-navigation-sans-js — cet écart n'existe pas dans le DOM actif JS et possède sa preuve séparée. Test RED fonction absente puis GREEN : autre noscript et nav active inattendue restent détectables — coût : normalisation explicitement limitée à l'écart autorisé.

27. Task 4: Ruling: paramétrer URL_WP_BLOCS/URL_SPIP_BLOCS et BLOCS_DB_HOST dans les deux helpers existants — leurs ports/bases codés en dur ne permettent pas l'isolement requis. Valeurs par défaut conservées ; garde cible ajoutée avant préparation éditoriale — coût : trois paramètres de test, pas d'option produit.

28. Task 4: diagnostic de l'échec SVP : le catalogue contient bien Pages 2.4.1/Polyhier 4.2.0, mais le CLI fourni filtre UPPER(prefixe)=LOWER("PAGES"), faux sous SQLite (0 contre 1 pour LOWER=LOWER). Ruling: copie privée /tmp/astra-migration-spip-cli, correction SQL d'une ligne, aucun changement du CLI partagé ; téléchargement Pages réellement vert puis reprise exacte des étapes restantes du helper dans reprendre-import.sh, sans réinstaller ni contourner les contrôles de tables. Polyhier 4.2.0, Pages 2.4.1, wp2spip et Astra installés, import réel terminé, article démo id 6. Aucun test complet de préparation wp2spip lancé — coût : correctif d'outillage privé à conserver dans le manifeste de reproduction.

29. Task 4: Ruling: séparer les sondes de CSS normal/wide/full de la preuve d'import — le filtre épinglé wp2spip_balise_nettoyee() retire les classes alignwide/alignfull ; les ajouter dans une sonde éphémère native ne prétend pas les conserver à l'import. Le comparateur du vrai contenu reste inchangé, ses pertes ne sont pas masquées davantage — coût : limite de conversion déclarée, pas de fonction d'import inventée dans Astra. Les géométries des sondes et le code dans un titre sont comparés au même WordPress.

30. Task 4: rares — footer RTL (vrai is_rtl et feuille main.min-rtl.css de WordPress), espacements page et archive identiques aux trois largeurs sans changement produit. Le premier test de logo comparait une taille intrinsèque WP non réglée (800×500) au plafond existant SPIP de 60px (96×60) : ce n'est pas le défaut « disparaît sans menu ». Ruling: régler explicitement la référence à 96px sur les trois écrans pour isoler ce défaut, sans prétendre activer les tailles responsives hors périmètre. Mesures initiales conservées dans rares-mesures-initiales.json — coût : la limite existante de dimension de logo reste déclarée, non corrigée par cette migration.

31. Task 4: autre RED réel : en réglant seulement le rayon à 8px, les liens nettoyés restaient à 4px ; les sélecteurs de réglages ciblaient uniquement la classe retirée par l'importeur. Ruling: reprendre les règles du module bouton pour ces liens, à spécificité égale grâce à :is(), en gardant les règles originales et leurs valeurs intégralement comparables à WP — coût : modification supplémentaire d'inc/styles/bouton.php et preuve dédiée de cascade, sans nouvelle option.

32. Task 4: cause précise de l'erreur d'image : adresse_site valait http://localhost, alors que le clone est servi sur 8424. Adresse corrigée uniquement dans la base privée du candidat ; profil rares redevenu vert, images servies. Le contrôle de clôture global a ensuite révélé trois goldens d'identité à URL localhost figée. Ruling: adapter seulement l'URL attendue de ces fixtures au domaine configuré, en gardant toute la comparaison HTML stricte — coût : un test d'intégration supplémentaire rendu portable, sans normaliser le rendu réel ni masquer un mauvais href produit. RED des trois cas observé dans task-4-tests.log.


## navigation-accessible-astra

33. Task 1: Ruling: le padding off-canvas n'est pas une option portée dans le registre SPIP — R25 applique un padding technique identique dans le navigateur aux deux moteurs, sans ajouter d'option produit. Cela exerce le comportement flex/fermeture de la feuille 4.13.8 — coût : son réglage Customizer reste hors périmètre et n'est pas présenté comme pris en charge.

34. Task 1: Ruling: ajouter aussi une garde de Tab après réouverture dans astra-navigation.js — c'est un défaut amont atteignable confirmé sur R2/R25, contraire au contrat clavier validé. Le bundle demeure octet pour octet officiel ; l'écouteur SPIP ne rebascule jamais le panneau et respecte defaultPrevented pour ne pas doubler le piège amont encore présent — coût : écart de comportement clavier assumé, preuve cible distincte et à qualifier.

35. Task 2: Ruling: une flèche masquée mobile n'est pas la commande utilisée — le pilote teste le span sur ordinateur et le bouton natif mobile ; états toggled-on et ast-submenu-expanded respectivement. Une activation native n'est pas simulée par un second keydown SPIP — coût : deux chemins concrets de contrôle dans le même profil.

36. Task 2: Ruling: étendre l'adaptation séparée aux défauts atteignables des sous-menus — preuve de pile : navigation_accessibility affecte onclick au premier bouton du header, qui peut être celui d'un sous-menu, puis AstraToggleSubMenu rebascule l'ARIA ; un clic SVG affecte event.target, et le départ du focus mobile ne ferme pas la branche. RED 18 défauts ; retirer uniquement l'onclick global mal attribué, synchroniser ARIA avec la classe effective du LI et fermer la branche quittée, sans doubler l'activation native — coût : écart supplémentaire au runtime amont, à déclarer en qualification avec ses mesures source.

37. Task 2: Ruling: la normalisation structurelle de la flèche vide s'applique seulement à la référence WordPress — retirer dropdown-menu-toggle dans un widget est une décision explicite, pas une différence active à masquer côté SPIP. Test RED helper absent puis GREEN : icône non vide et commande d'en-tête conservent leur classe comparée — coût : seule cette classe sémantique de l'écart autorisé est normalisée.


## icones-liens-sociaux-astra

38. Task 2: Ruling: comparer le schéma mailto sans distinction de casse (valeurs brutes conservées dans JSON) — esc_url() de WordPress abaisse le schéma, SPIP conserve MAILTO ; même URL et contrat de stockage inchangé — coût : seule la casse du protocole est normalisée, pas l'adresse.


## compatibilite-oembed

39. Task 1: Ruling: les deux sites oEmbed seront des copies privées du SPIP réellement importé, conservées sur 8431 (sans) et 8430 (avec), fournisseur sur 8428. Le site des blocs 8427 reste disponible ; la référence WP MySQL 8426 est configurée pour chaque scénario puis restaurée, comme les références du builder — coût : copies de bases supplémentaires, aucune activation dans le site du candidat ni service partagé.

40. Task 1: Ruling: Les URL de lecture utilisent localhost:8428 — Textwheel transforme les noms d’hôte en liens automatiques mais pas les IPv4 littérales (caractérisation example.org/localhost/127.0.0.1). Le rouge initial venait du fournisseur de preuve, sans défaut de thème ; le refus du validateur sur 127.0.0.1:8428 reste testé séparément. Coût si incorrect : une limite des autoliens numériques SPIP demeure, hors correctif Astra.

41. Task 1: Ruling: SPIP_TEST_ROOT rend le bootstrap d’intégration paramétrable — le défaut reste vendor/spip/spip ; les deux sites isolés utilisent le même test et leur propre base. Coût si incorrect : un appelant avec cette variable mal réglée pourrait tester une autre installation.

42. Task 2: Ruling: Le refus automatique de découverte annoncé par le plan ne correspond pas aux sources exécutées — oEmbed e90f6a6 appelle recuperer_url_cache sans callback_valider_url ; SPIP 4.4.28 laisse ce callback à null. valider_url_distante refuse bien 127.0.0.1:8428, mais n’est pas appelé par ce chemin. Le routeur inconnu répond sans endpoint, les requêtes réelles sont conservées, et aucune protection SSRF du plugin n’est déclarée validée. Coût si incorrect : la sécurité de la découverte demeure une responsabilité distincte du plugin/SPIP, sans correctif dans Astra.

43. Task 2: Ruling: Le profil deja-enveloppe utilise le vrai bloc embed enveloppé dans WordPress puis importé — wp2spip extrait son URL et sa légende, il ne conserve pas l’enveloppe WordPress ; le résultat réel oEmbed repasse aussi dans propre avec un seul conteneur. Coût si incorrect : les anciens HTML arbitraires non issus de ce chemin d’import ne sont pas qualifiés.

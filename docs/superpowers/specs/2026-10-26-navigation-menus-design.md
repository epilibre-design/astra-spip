# Navigation et Menus dans Astra

Demande : corriger le repli sans JavaScript et rendre les menus libres natifs via une dépendance obligatoire à Menus. Cible décidée par l’utilisateur : SPIP 4.4 uniquement.

Le repli conserve les sources existantes et les liens imbriqués, reste dans le flux même avec un en-tête transparent et fonctionne à 922 px. Le rendu avec JavaScript est conservé.

Menus fournit l’édition et son rendu natif ; Astra adapte ce rendu aux classes et commandes de sa navigation. Les entrées restent traitées par Menus (droits, publication, types dynamiques). Nouvelle source `menus:<id>` disponible dans tous les sélecteurs du builder, y compris les widgets. Les autres sources restent disponibles comme fonctions existantes. Aucun scénario de migration : Astra est en développement et ne possède aucun site utilisateur. Aucun menu éditorial n’est créé ou réécrit automatiquement.

Approches : une gestion maison du menu libre est écartée au profit de Menus, choisi par l’utilisateur. Surcharger chaque type d’entrée du plugin dupliquerait sa logique ; une adaptation du HTML natif conserve cette logique et ses extensions. L’inclusion générale `inclure/menu` n’est pas surchargée.

Compatibilité : `[4.4.0;4.4.*]`, Menus >=3.1.2 dans la branche 3, Saisies fourni par ses dépendances. Pas de publication de version dans ce lot ; PR relisible avant fusion.

Vérification : tests unitaires de configuration et adaptation, vrais squelettes SPIP avec Menus, navigateur avec et sans JS aux seuils 921/922/923 et sur mobile ; en-tête transparent, menu imbriqué, pied de page et widget.

# CLI de préparation : sélection des préfixes sous SQLite

Source : [SPIP-Cli](https://git.spip.net/spip-contrib-outils/spip-cli), copie locale
au commit `0ab5976433b384913d92d7b993ee5a71f66b63aa` avec les correctifs de préparation.
SHA-256 du fichier utilisé avant correction
`src/Command/PluginsSvpTelecharger.php` :
`5668cbd6fbd09dbde3096eb70936f599c4eb13fbfe164de44b7586bcd54d959a`.

Le filtre de cette copie compare `UPPER(pl.prefixe)` à `LOWER(...)`. Dans la base
SQLite réelle du SPIP fraîchement installé, cette recherche trouve zéro Pages ;
la comparaison des deux côtés en minuscules en trouve un.

Pour reproduire, copier ce CLI dans un dossier privé, puis remplacer seulement
`UPPER(pl.prefixe) = LOWER(` par `LOWER(pl.prefixe) = LOWER(` dans ce fichier.
Utiliser l'exécutable de la copie comme `SPIP_CLI` / `SPIP_CLI_SVP` pour la
préparation ciblée des blocs. Le CLI partagé n'a pas été modifié. Pages 2.4.1 et
Polyhier 4.2.0 ont ensuite été téléchargés réellement, leurs tables vérifiées et
l'import lancé. Aucun test complet de préparation wp2spip n'a été lancé.

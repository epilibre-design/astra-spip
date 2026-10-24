<?php
// Valeurs de Garden Maintenance de la spec des réglages de compatibilité, ensemble : deux drapeaux,
// graisses des titres, marge du bouton de l'en-tête vide, alignements du pied (valeurs :
// styles/valeurs/garden-compat.json). Page « Notre jardin » avec les six niveaux de titres.
putenv('GROUPE=garden-compat');
require dirname(__DIR__) . '/styles/appliquer.php';
$ids = ref_pages();
wp_update_post(['ID' => $ids['notre-jardin'], 'post_content' => ref_texte_titres()]);

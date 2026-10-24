<?php
// Drapeau can-support-widget-and-editor-fonts posé, sans aucune autre option de style (valeurs : tests/references/styles/valeurs/drapeau-polices.json) ; placement des
// références des styles (titres des widgets du pied en h2), page « Notre jardin » avec les six
// niveaux de titres.
putenv('GROUPE=drapeau-polices');
require dirname(__DIR__) . '/styles/appliquer.php';
$ids = ref_pages();
wp_update_post(['ID' => $ids['notre-jardin'], 'post_content' => ref_texte_titres()]);

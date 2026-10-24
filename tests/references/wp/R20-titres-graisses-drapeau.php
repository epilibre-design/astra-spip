<?php
// Graisses des titres réglées, avec le drapeau can-support-widget-and-editor-fonts (valeurs : tests/references/styles/valeurs/titres-drapeau.json) ; placement des
// références des styles (titres des widgets du pied en h2), page « Notre jardin » avec les six
// niveaux de titres.
putenv('GROUPE=titres-drapeau');
require dirname(__DIR__) . '/styles/appliquer.php';
$ids = ref_pages();
wp_update_post(['ID' => $ids['notre-jardin'], 'post_content' => ref_texte_titres()]);

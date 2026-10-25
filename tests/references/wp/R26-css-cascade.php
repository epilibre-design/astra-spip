<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu_pages('primary');
ref_menu_pages('mobile_menu');
ref_reglages(['font-weight-h2' => '700', 'header-menu1-color-responsive' => ['desktop' => '#111111'], 'header-menu1-h-color-responsive' => ['desktop' => '#00ff00'], 'header-menu1-h-bg-color-responsive' => ['desktop' => '#112233']]);
$ids = ref_pages();
wp_update_post(['ID' => $ids['notre-jardin'], 'post_content' => file_get_contents(dirname(__DIR__) . '/base/contenu-cascade.html')]);

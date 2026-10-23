<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu_pages('primary');
ref_menu_pages('mobile_menu');
$id = get_page_by_path('plein-ecran')->ID;
update_post_meta($id, 'site-post-title', 'disabled');
update_post_meta($id, 'ast-site-content-layout', 'full-width-container');

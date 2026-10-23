<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu_pages('primary');
ref_menu_pages('mobile_menu');
update_option('show_on_front', 'page');
update_option('page_on_front', get_page_by_path('notre-jardin')->ID);

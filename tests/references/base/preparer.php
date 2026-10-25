<?php
// Configurations techniques de base : mêmes options R0, pages normale et étroite.
require dirname(__DIR__) . '/wp/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
$pages = ref_pages();
$id = $pages['notre-jardin'];
if (getenv('BASE_ETROITE') === '1') {
	update_post_meta($id, 'ast-site-content-layout', 'narrow-width-container');
}
$categories = ref_categories();
echo json_encode(['page' => get_permalink($id), 'archive' => get_category_link($categories['jardinage'])], JSON_UNESCAPED_SLASHES);

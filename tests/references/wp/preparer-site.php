<?php
// Site de test neuf : pagination et pied accessibles par un défilement réel.
for ($i = 1; $i <= 12; $i++) {
	$slug = 'astra-reference-demo-' . $i;
	if (!get_page_by_path($slug, OBJECT, 'post')) {
		wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_name' => $slug,
			'post_title' => 'Article de démonstration ' . $i, 'post_content' => 'Texte de démonstration pour les références.']);
	}
}

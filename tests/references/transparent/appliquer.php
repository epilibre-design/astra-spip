<?php
/**
 * Référence de l'en-tête transparent : état P1 (page « Notre jardin », menu de pages), puis les
 * options de valeurs/<variante>.json ; la page a l'en-tête transparent (méta de page `enabled`),
 * sauf pour la variante « base ».
 * Usage : VARIANTE=<variante> wp --path=$WP eval-file tests/references/transparent/appliquer.php
 */
require dirname(__DIR__) . '/wp/commun.php';
ref_etat_initial();
ref_menu_pages('primary');
ref_menu_pages('mobile_menu');
$ids = ref_pages();
$variante = getenv('VARIANTE') ?: 'base';
if ($variante !== 'base') {
	update_post_meta($ids['notre-jardin'], 'theme-transparent-header-meta', 'enabled');
	ref_reglages(json_decode(file_get_contents(__DIR__ . "/valeurs/$variante.json"), true));
}

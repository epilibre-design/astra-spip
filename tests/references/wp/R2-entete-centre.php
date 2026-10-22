<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_reglages([
	'header-desktop-items' => [
		'popup'   => ['popup_content' => ['mobile-menu']],
		'above'   => ['above_left' => [], 'above_left_center' => [], 'above_center' => [], 'above_right_center' => [], 'above_right' => []],
		'primary' => ['primary_left' => ['menu-1'], 'primary_left_center' => [], 'primary_center' => ['logo'], 'primary_right_center' => [], 'primary_right' => ['account', 'search']],
		'below'   => ['below_left' => ['widget-1'], 'below_left_center' => [], 'below_center' => [], 'below_right_center' => [], 'below_right' => ['html-2']],
	],
	'header-mobile-items' => [
		'popup'   => ['popup_content' => ['mobile-menu', 'search']],
		'above'   => ['above_left' => [], 'above_center' => [], 'above_right' => []],
		'primary' => ['primary_left' => ['mobile-trigger'], 'primary_center' => ['logo'], 'primary_right' => ['account']],
		'below'   => ['below_left' => [], 'below_center' => [], 'below_right' => []],
	],
	'mobile-header-type' => 'off-canvas',
	'header-html-2' => '<p>Livraison gratuite dès 50 €</p>',
]);
ref_widget_texte('header-widget-1', 'Horaires', 'Lundi–vendredi, 9 h – 18 h');

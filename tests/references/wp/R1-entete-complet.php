<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_menu('Secondaire', 'secondary_menu');
ref_reglages([
	'header-desktop-items' => [
		'popup'   => ['popup_content' => ['mobile-menu']],
		'above'   => ['above_left' => ['html-1'], 'above_left_center' => [], 'above_center' => [], 'above_right_center' => [], 'above_right' => ['social-icons-1']],
		'primary' => ['primary_left' => ['logo'], 'primary_left_center' => [], 'primary_center' => [], 'primary_right_center' => [], 'primary_right' => ['menu-1', 'search', 'button-1']],
		'below'   => ['below_left' => [], 'below_left_center' => [], 'below_center' => ['menu-2'], 'below_right_center' => [], 'below_right' => []],
	],
	'header-mobile-items' => [
		'popup'   => ['popup_content' => ['mobile-menu']],
		'above'   => ['above_left' => [], 'above_center' => ['html-1'], 'above_right' => []],
		'primary' => ['primary_left' => ['logo'], 'primary_center' => [], 'primary_right' => ['search', 'mobile-trigger']],
		'below'   => ['below_left' => [], 'below_center' => [], 'below_right' => []],
	],
	'mobile-header-type' => 'dropdown',
	'header-html-1' => '<p>Ouvert du lundi au vendredi</p>',
	'header-button1-text' => 'Nous contacter',
	'header-button1-link-option' => ['url' => 'http://127.0.0.1:8401/', 'new_tab' => false, 'link_rel' => ''],
]);

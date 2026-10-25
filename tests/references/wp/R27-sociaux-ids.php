<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
$ordinateur = astra_get_option('header-desktop-items');
$ordinateur['above']['above_right'] = ['social-icons-1'];
$mobile = astra_get_option('header-mobile-items');
$mobile['above']['above_center'] = ['social-icons-1'];
$items = ['items' => [
	['id' => 'email', 'enabled' => true, 'source' => 'icon', 'url' => 'MAILTO:nom@example.test', 'color' => '#ea4335', 'background' => 'transparent', 'icon' => 'email', 'label' => 'E-mail'],
	['id' => 'google_reviews', 'enabled' => true, 'source' => 'icon', 'url' => 'https://example.test/avis', 'color' => '#dc4e41', 'background' => 'transparent', 'icon' => 'google_reviews', 'label' => 'Google Reviews'],
]];
ref_reglages([
	'header-desktop-items' => $ordinateur,
	'header-mobile-items' => $mobile,
	'header-social-icons-1' => $items,
	'footer-social-icons-1' => $items,
	'footer-desktop-items' => ['above' => [], 'primary' => [], 'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]],
	'hbb-footer-column' => '2',
	'hbb-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
]);

<?php
// Icônes sociales du pied avec le réseau Google Reviews, activé avec la couleur de marque du
// sélecteur d'Astra, comme dans Garden Maintenance.
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
$element = function (string $id, string $libelle, string $couleur, string $url) {
	return ['id' => $id, 'enabled' => true, 'source' => 'icon', 'url' => $url, 'color' => $couleur, 'background' => 'transparent', 'icon' => $id, 'label' => $libelle];
};
ref_reglages([
	'footer-desktop-items' => [
		'above'   => ['above_1' => [], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
		'primary' => ['primary_1' => [], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_5' => []],
		'below'   => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
	],
	'hbb-footer-column' => '2',
	'hbb-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
	'footer-social-icons-1' => ['items' => [
		$element('facebook', 'Facebook', '#557dbc', 'https://example.org/facebook'),
		$element('google_reviews', 'Google Reviews', '#dc4e41', 'https://example.org/avis'),
	]],
]);

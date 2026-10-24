<?php
// Marges des quinze options des rangées, de l'en-tête entier et du pied entier : valeurs distinctes
// par appareil, unités mêlées, côtés en partie vides, marges extérieures négatives (valeurs :
// styles/valeurs/marges-toutes.json). Rangées haute et basse de l'en-tête remplies comme dans R1,
// rangée haute du pied remplie d'un texte.
putenv('GROUPE=marges-toutes');
require dirname(__DIR__) . '/styles/appliquer.php';
ref_menu('Secondaire', 'secondary_menu');
ref_reglages([
	'header-desktop-items' => [
		'popup' => ['popup_content' => ['mobile-menu']],
		'above' => ['above_left' => ['html-1'], 'above_left_center' => [], 'above_center' => [], 'above_right_center' => [], 'above_right' => ['social-icons-1']],
		'primary' => ['primary_left' => ['logo'], 'primary_left_center' => [], 'primary_center' => [], 'primary_right_center' => [], 'primary_right' => ['menu-1', 'button-1']],
		'below' => ['below_left' => [], 'below_left_center' => [], 'below_center' => ['menu-2'], 'below_right_center' => [], 'below_right' => []],
	],
	'header-mobile-items' => [
		'popup' => ['popup_content' => ['mobile-menu']],
		'above' => ['above_left' => [], 'above_center' => ['html-1'], 'above_right' => []],
		'primary' => ['primary_left' => ['logo'], 'primary_center' => [], 'primary_right' => ['mobile-trigger']],
		'below' => ['below_left' => [], 'below_center' => [], 'below_right' => []],
	],
	'footer-desktop-items' => [
		'above' => ['above_1' => ['html-1'], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
		'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4'], 'primary_5' => []],
		'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
	],
	'hba-footer-column' => '1',
	'hba-footer-layout' => ['desktop' => 'full', 'tablet' => 'full', 'mobile' => 'full'],
	'header-html-1' => '<p>Ouvert du lundi au vendredi</p>',
	'footer-html-1' => '<p>Un site éditorial propulsé par SPIP</p>',
]);

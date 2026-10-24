<?php
// Conditions d'émission : marges réglées pour les quinze options (valeurs :
// styles/valeurs/marges-rangees-vides.json, avec une tablette sans unité), rangée haute de
// l'en-tête remplie sur mobile seulement, rangée basse de l'en-tête vide, rangée haute du pied
// vide, rangée principale du pied remplie en zone 6 seulement : aucune règle pour les rangées
// vides (zone 6 comprise), règles pour la rangée remplie sur mobile, l'en-tête entier et le pied
// entier.
putenv('GROUPE=marges-rangees-vides');
require dirname(__DIR__) . '/styles/appliquer.php';
ref_reglages([
	'header-mobile-items' => [
		'popup' => ['popup_content' => ['mobile-menu']],
		'above' => ['above_left' => [], 'above_center' => ['html-1'], 'above_right' => []],
		'primary' => ['primary_left' => ['logo'], 'primary_center' => [], 'primary_right' => ['mobile-trigger']],
		'below' => ['below_left' => [], 'below_center' => [], 'below_right' => []],
	],
	'footer-desktop-items' => [
		'above' => ['above_1' => [], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
		'primary' => ['primary_1' => [], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_5' => [], 'primary_6' => ['widget-1']],
		'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
	],
	'hb-footer-column' => '6',
	'hb-footer-layout' => ['desktop' => '6-equal', 'tablet' => 'full', 'mobile' => 'full'],
	'header-html-1' => '<p>Ouvert du lundi au vendredi</p>',
]);

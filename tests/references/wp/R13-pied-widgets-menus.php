<?php
// Deux widgets « Menu de navigation » dans le pied, comme les widgets 2 et 3 de Garden
// Maintenance : menu Principal (Jardinage > Potager, Actualités) et menu Pied.
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_menu('Pied', 'footer_menu');
ref_reglages([
	'footer-desktop-items' => [
		'above'   => ['above_1' => [], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
		'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => [], 'primary_5' => []],
		'below'   => ['below_1' => ['copyright'], 'below_2' => [], 'below_3' => [], 'below_4' => [], 'below_5' => []],
	],
	'hb-footer-column' => '3',
	'hb-footer-layout' => ['desktop' => '3-equal', 'tablet' => '3-equal', 'mobile' => 'full'],
]);
ref_widget_texte('footer-widget-1', 'Colonne 1', 'Texte de la colonne 1');
ref_widget_menu('footer-widget-2', 'Liens rapides', 'Principal');
ref_widget_menu('footer-widget-3', 'Nos services', 'Pied');

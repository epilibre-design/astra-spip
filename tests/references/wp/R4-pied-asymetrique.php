<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_menu('Pied', 'footer_menu');
ref_reglages([
	'footer-desktop-items' => [
		'above'   => ['above_1' => [], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
		'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['html-1', 'social-icons-1'], 'primary_3' => ['menu'], 'primary_4' => [], 'primary_5' => []],
		'below'   => ['below_1' => ['copyright'], 'below_2' => ['html-2'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
	],
	'hb-footer-column' => '3',
	'hb-footer-layout' => ['desktop' => '3-cwide', 'tablet' => '3-cwide', 'mobile' => 'full'],
	'hbb-footer-column' => '2',
	'hbb-footer-layout' => ['desktop' => '2-lheavy', 'tablet' => '2-lheavy', 'mobile' => 'full'],
	'footer-menu-layout' => ['desktop' => 'vertical', 'tablet' => 'vertical', 'mobile' => 'vertical'],
	'footer-html-1' => '<p>Suivez-nous</p>',
	'footer-html-2' => '<p>Mentions légales</p>',
]);
ref_widget_texte('footer-widget-1', 'À propos', 'Quelques mots sur le site.');

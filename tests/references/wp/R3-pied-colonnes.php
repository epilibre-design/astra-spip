<?php
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_menu('Pied', 'footer_menu');
ref_reglages([
	'footer-desktop-items' => [
		'above'   => ['above_1' => ['html-1'], 'above_2' => ['social-icons-1'], 'above_3' => [], 'above_4' => [], 'above_5' => []],
		'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4'], 'primary_5' => []],
		'below'   => ['below_1' => ['copyright'], 'below_2' => ['menu'], 'below_3' => [], 'below_4' => [], 'below_5' => []],
	],
	'hba-footer-column' => '2',
	'hba-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
	'hb-footer-column' => '4',
	'hb-footer-layout' => ['desktop' => '4-equal', 'tablet' => '4-equal', 'mobile' => 'full'],
	'hbb-footer-column' => '2',
	'hbb-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
	'footer-html-1' => '<p>Un site éditorial propulsé par SPIP</p>',
]);
foreach ([1, 2, 3, 4] as $i) {
	ref_widget_texte('footer-widget-' . $i, 'Colonne ' . $i, 'Texte de la colonne ' . $i);
}

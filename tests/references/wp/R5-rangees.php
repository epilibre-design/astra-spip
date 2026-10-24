<?php
// Réglages des rangées de l'en-tête (hauteur, fond, séparateur de la rangée principale) et
// séparateurs des rangées du pied (rangée principale sans filet, rangées haute et basse colorées).
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
$fond = function (string $couleur) {
	$f = ['background-color' => '', 'background-image' => '', 'background-repeat' => 'repeat', 'background-position' => 'center center', 'background-size' => 'auto', 'background-attachment' => 'scroll', 'overlay-type' => '', 'overlay-color' => '', 'overlay-gradient' => ''];
	return ['desktop' => ['background-color' => $couleur] + $f, 'tablet' => $f, 'mobile' => $f];
};
ref_reglages([
	'hb-header-height' => ['desktop' => 100, 'tablet' => '', 'mobile' => ''],
	'hb-header-bg-obj-responsive' => $fond('#e8f5e9'),
	'hb-header-main-sep' => 0,
	'footer-desktop-items' => [
		'above'   => ['above_1' => ['html-1'], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => []],
		'primary' => ['primary_1' => ['html-2'], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_5' => []],
		'below'   => ['below_1' => ['copyright'], 'below_2' => [], 'below_3' => [], 'below_4' => [], 'below_5' => []],
	],
	'footer-html-1' => '<p>Rangée haute</p>',
	'footer-html-2' => '<p>Rangée principale</p>',
	'hba-footer-separator' => 2,
	'hba-footer-top-border-color' => '#c0392b',
	'hb-footer-main-sep' => 0,
	'hbb-footer-top-border-color' => 'var(--ast-global-color-4)',
]);

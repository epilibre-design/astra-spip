<?php
// Réglages des rangées de l'en-tête (hauteur, fond, séparateur de la rangée principale).
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
]);

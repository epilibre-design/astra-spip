<?php
/**
 * Référence des styles du Customizer : état R0, éléments placés comme dans Garden Maintenance,
 * puis les options du groupe (valeurs/<groupe>.json ; groupe « base » : aucune option).
 * Usage : GROUPE=<groupe> wp --path=$WP eval-file tests/references/styles/appliquer.php
 */
require dirname(__DIR__) . '/wp/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
$zones = function (array $noms, array $remplies) {
	return array_merge(array_fill_keys($noms, []), $remplies);
};
$entete = ['left', 'left_center', 'center', 'right_center', 'right'];
$reglages = [
	'header-desktop-items' => [
		'popup' => ['popup_content' => ['mobile-menu']],
		'above' => $zones(array_map(function ($z) { return "above_$z"; }, $entete), []),
		'primary' => $zones(array_map(function ($z) { return "primary_$z"; }, $entete), ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'button-1']]),
		'below' => $zones(array_map(function ($z) { return "below_$z"; }, $entete), []),
	],
	'footer-desktop-items' => [
		'above' => $zones(['above_1', 'above_2', 'above_3', 'above_4', 'above_5'], []),
		'primary' => $zones(['primary_1', 'primary_2', 'primary_3', 'primary_4', 'primary_5'], ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']]),
		'below' => $zones(['below_1', 'below_2', 'below_3', 'below_4', 'below_5'], ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]),
	],
	'hb-footer-column' => '4', 'hb-footer-layout' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'],
	'hbb-footer-column' => '2', 'hbb-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
];
$groupe = getenv('GROUPE') ?: 'base';
if ($groupe !== 'base') {
	$reglages += json_decode(file_get_contents(__DIR__ . "/valeurs/$groupe.json"), true);
}
ref_reglages($reglages);

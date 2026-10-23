<?php
/**
 * Référence des styles du Customizer : état R0, éléments placés comme dans Garden Maintenance
 * (logo, menu et bouton dans l'en-tête ; quatre widgets texte, copyright et icônes sociales dans le
 * pied), puis les options du groupe (valeurs/<groupe>.json ; « base » : aucune option ;
 * « combinee » : toutes les options, plus les polices du texte et des titres).
 * Usage : GROUPE=<groupe> wp --path=$WP eval-file tests/references/styles/appliquer.php
 * Les polices locales d'Astra SPIP sont servies par le WordPress (ref_polices_locales()).
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
	'header-button1-text' => 'Nous contacter',
	'header-button1-link-option' => ['url' => 'http://127.0.0.1:8401/', 'new_tab' => false, 'link_rel' => ''],
];
$groupe = getenv('GROUPE') ?: 'base';
$groupes = $groupe === 'combinee' ? ['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'] : ($groupe === 'base' ? [] : [$groupe]);
foreach ($groupes as $g) {
	$reglages += json_decode(file_get_contents(__DIR__ . "/valeurs/$g.json"), true);
}
if ($groupe === 'combinee') {
	$reglages['body-font-family'] = "'Raleway', sans-serif";
	$reglages['headings-font-family'] = "'Poppins', sans-serif";
}
ref_reglages($reglages);
foreach ([1, 2, 3, 4] as $i) {
	ref_widget_texte('footer-widget-' . $i, 'Colonne ' . $i, 'Texte de la colonne ' . $i);
}
ref_polices_locales();

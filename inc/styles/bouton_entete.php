<?php
/**
 * Astra pour SPIP — bouton de l'en-tête (inc/builder/type/base/dynamic-css/button/
 * class-astra-button-component-dynamic-css.php d'Astra 4.11.0, règles ordinateur).
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_bouton_entete(array $s, array $hfb): array {
	$p = 'header-button1';
	if (!astra_styles_pose($s, 'bouton-entete') || !astra_styles_element_place('button-1', $hfb, 'entete')) {
		return [];
	}
	$sel = '.ast-header-button-1';
	$extras = astra_styles_extras($s["$p-font-extras"] ?? []);
	$graisse = astra_styles_ou($s, "$p-font-weight");
	$bordure = $s["$p-border-size"] ?? '';
	// Astra : 40 px par défaut ; une fois l'option enregistrée, ses valeurs, même vides.
	$rayons = $s["$p-border-radius-fields"] ?? ['desktop' => ['top' => 40, 'right' => 40, 'bottom' => 40, 'left' => 40], 'desktop-unit' => 'px'];
	$rayon = [];
	foreach (['top', 'right', 'bottom', 'left'] as $cote) {
		$rayon[$cote] = astra_styles_cote($rayons, $cote);
	}
	$regles = [
		['', $sel . '[data-section*="section-hb-button-"] .ast-builder-button-wrap .ast-custom-button', [
			'font-family' => astra_styles_famille($s["$p-font-family"] ?? ''),
			'font-weight' => $graisse === 'inherit' ? '' : $graisse,
			'font-size' => astra_styles_taille_police($s["$p-font-size"] ?? ''),
		] + $extras],
		['', "$sel .ast-custom-button", [
			'color' => astra_styles_ou($s, "$p-text-color"), 'background' => astra_styles_ou($s, "$p-back-color"),
			'border-color' => astra_styles_ou($s, "$p-border-color"),
			'border-top-width' => astra_styles_cote($bordure, 'top'), 'border-bottom-width' => astra_styles_cote($bordure, 'bottom'),
			'border-left-width' => astra_styles_cote($bordure, 'left'), 'border-right-width' => astra_styles_cote($bordure, 'right'),
			'border-top-left-radius' => $rayon['top'], 'border-top-right-radius' => $rayon['right'],
			'border-bottom-right-radius' => $rayon['bottom'], 'border-bottom-left-radius' => $rayon['left'],
		]],
		['', "$sel .ast-custom-button:hover", [
			'color' => astra_styles_ou($s, "$p-text-h-color"), 'background' => astra_styles_ou($s, "$p-back-h-color"),
			'border-color' => astra_styles_ou($s, "$p-border-h-color"),
		]],
	];
	// Neutralisation : rayons enregistrés vides, même en partie, Astra n'émet plus les 40 px par
	// défaut que css/astra-hfb.css contient ; ces coins prennent alors le rayon du bouton global
	// (0 sans réglage).
	$vides = $rayon;
	foreach ($rayon as $cote => $v) {
		if ($v === '') {
			$rayon[$cote] = astra_styles_cote($s['button-radius-fields'] ?? '', $cote) ?: '0';
		}
	}
	if (isset($s["$p-border-radius-fields"]) && in_array('', $vides, true)) {
		$regles[] = ['', "$sel .ast-custom-button", [
			'border-top-left-radius' => $rayon['top'], 'border-top-right-radius' => $rayon['right'],
			'border-bottom-right-radius' => $rayon['bottom'], 'border-bottom-left-radius' => $rayon['left'],
		]];
	}
	return $regles;
}

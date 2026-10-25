<?php
/**
 * Astra pour SPIP — bouton du menu mobile (inc/builder/type/header/mobile-trigger/dynamic-css/
 * dynamic.css.php d'Astra 4.13.8, règles ordinateur).
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_menu_mobile(array $s, array $hfb): array {
	if (!astra_styles_pose($s, 'menu-mobile') || !astra_styles_element_place('mobile-trigger', $hfb, 'entete')) {
		return [];
	}
	$sel = '[data-section="section-header-mobile-trigger"] .ast-button-wrap';
	// theme-color d'Astra SPIP : première couleur de la palette.
	$theme = 'var(--ast-global-color-0)';
	$style = astra_styles_ou($s, 'mobile-header-toggle-btn-style', 'minimal');
	$icone = astra_styles_ou($s, 'mobile-header-toggle-btn-color', $style === 'fill' ? '#ffffff' : $theme);
	$fond = astra_styles_ou($s, 'mobile-header-toggle-btn-bg-color', $theme);
	$taille = astra_styles_ou($s, 'mobile-header-toggle-icon-size', 20) . 'px';
	$regles = [
		['', "$sel .mobile-menu-toggle-icon .ast-mobile-svg", ['width' => $taille, 'height' => $taille, 'fill' => $icone]],
		['', "$sel .mobile-menu-wrap .mobile-menu", ['color' => $icone]],
	];
	$rayons = $s['mobile-header-toggle-border-radius-fields'] ?? '';
	$rayon = [
		'border-top-left-radius' => astra_styles_cote($rayons, 'top'), 'border-top-right-radius' => astra_styles_cote($rayons, 'right'),
		'border-bottom-right-radius' => astra_styles_cote($rayons, 'bottom'), 'border-bottom-left-radius' => astra_styles_cote($rayons, 'left'),
	];
	if ($style === 'minimal') {
		$regles[] = ['', "$sel .ast-mobile-menu-trigger-minimal", ['color' => $icone, 'border' => 'none', 'background' => 'transparent']];
	} elseif ($style === 'fill') {
		$regles[] = ['', "$sel .ast-mobile-menu-trigger-fill", ['color' => $icone, 'border' => 'none', 'background' => $fond] + $rayon];
	} else {
		// Épaisseurs : défaut d'Astra (1 px) sans option ; une fois l'option enregistrée, 0 pour un côté absent.
		$bordure = $s['mobile-header-toggle-btn-border-size'] ?? ['top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1];
		$epaisseur = function (string $cote) use ($bordure) {
			$v = $bordure[$cote] ?? 0;
			return $v === '' ? '' : $v . 'px';
		};
		$regles[] = ['', "$sel .ast-mobile-menu-trigger-outline", [
			'background' => 'transparent', 'color' => $icone,
			'border-top-width' => $epaisseur('top'), 'border-bottom-width' => $epaisseur('bottom'),
			'border-right-width' => $epaisseur('right'), 'border-left-width' => $epaisseur('left'),
			'border-style' => 'solid', 'border-color' => astra_styles_ou($s, 'mobile-header-toggle-border-color', $fond),
		] + $rayon];
	}
	return $regles;
}

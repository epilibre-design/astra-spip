<?php
/**
 * Astra pour SPIP — alignements du copyright (inc/builder/type/footer/copyright/dynamic-css/
 * dynamic.css.php) et des icônes sociales du pied (inc/builder/type/base/dynamic-css/social/
 * class-astra-social-component-dynamic-css.php) d'Astra 4.13.8, sur les trois appareils.
 *
 * @license GPL-3.0-or-later
 */

/**
 * Options d'alignement : option => [élément du pied, sélecteur d'Astra, alignement sans règle
 * d'Astra]. Les icônes sociales restent centrées par une règle fixe de css/astra-hfb.css
 * ([data-section^="section-fb-social-icons-"] .footer-social-inner-wrap) ; le copyright hérite.
 */
const ASTRA_STYLES_ALIGNEMENTS = [
	'footer-copyright-alignment' => ['copyright', '.ast-footer-copyright', 'inherit'],
	'footer-social-1-alignment' => ['social-icons-1', '[data-section="section-fb-social-icons-1"] .footer-social-inner-wrap', 'center'],
];

function astra_styles_alignements(array $s, array $hfb): array {
	$regles = [];
	foreach (ASTRA_STYLES_ALIGNEMENTS as $option => [$element, $sel, $sans]) {
		if (!isset($s[$option]) || !astra_styles_element_place($element, $hfb, 'pied')) {
			continue;
		}
		$v = $s[$option];
		[$o, $t, $m] = [$v['desktop'] ?? '', $v['tablet'] ?? '', $v['mobile'] ?? ''];
		// Astra émet la règle de chaque appareil qui a une valeur : sans media query pour
		// l'ordinateur, puis 921 et 544 px. Neutralisation : css/astra-dynamic.css et
		// css/astra-hfb.css centrent sur les trois appareils ; un appareil sans valeur prend celle
		// que la cascade d'Astra lui donne (la tablette celle de l'ordinateur, le mobile celle de la
		// tablette, sinon de l'ordinateur), à défaut celle qui s'applique sans règle d'Astra.
		$regles[] = ['', $sel, ['text-align' => $o ?: $sans]];
		$regles[] = ['(max-width:921px)', $sel, ['text-align' => $t ?: ($o ?: $sans)]];
		$regles[] = ['(max-width:544px)', $sel, ['text-align' => $m ?: ($t ?: ($o ?: $sans))]];
	}
	return $regles;
}

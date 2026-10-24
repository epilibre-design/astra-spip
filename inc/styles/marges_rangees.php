<?php
/**
 * Astra pour SPIP — marges des rangées du builder, de l'en-tête entier et du pied entier
 * (Astra_Extended_Base_Dynamic_CSS::prepare_advanced_margin_padding_css() de
 * inc/class-astra-extended-base-dynamic-css.php, et section-header-builder-layout-margin de
 * inc/builder/type/header/primary-header/dynamic-css/dynamic.css.php d'Astra 4.11.0), sur les
 * trois appareils.
 *
 * @license GPL-3.0-or-later
 */

/**
 * Sections, dans l'ordre d'émission d'Astra : section => [sélecteur, rangée dont dépend
 * l'émission]. Astra n'émet le CSS d'une rangée de l'en-tête que si elle est remplie sur
 * ordinateur ou sur mobile (Astra_Builder_Helper::is_row_empty(), vrai pour une rangée remplie),
 * celui d'une rangée du pied que si une de ses zones 1 à 5 est remplie (is_footer_row_empty()) ;
 * la marge de l'en-tête entier suit la rangée principale, celle du pied entier est toujours émise.
 */
const ASTRA_STYLES_MARGES_RANGEES = [
	'section-below-footer-builder' => ['.site-below-footer-wrap[data-section="section-below-footer-builder"]', 'pied:below'],
	'section-above-footer-builder' => ['.site-above-footer-wrap[data-section="section-above-footer-builder"]', 'pied:above'],
	'section-footer-builder-layout' => ['.ast-hfb-header .site-footer', ''],
	'section-primary-footer-builder' => ['.site-primary-footer-wrap[data-section="section-primary-footer-builder"]', 'pied:primary'],
	'section-primary-header-builder' => ['.ast-desktop .ast-primary-header-bar.main-header-bar, .ast-header-break-point #masthead .ast-primary-header-bar.main-header-bar', 'entete:primary'],
	'section-header-builder-layout' => ['.ast-hfb-header .site-header', 'entete:primary'],
	'section-above-header-builder' => ['.ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar', 'entete:above'],
	'section-below-header-builder' => ['.ast-below-header-bar.ast-below-header, .ast-header-break-point .ast-below-header-bar.ast-below-header', 'entete:below'],
];

function astra_styles_marges_rangees(array $s, array $hfb): array {
	$n = astra_hfb_normaliser($hfb);
	$regles = [];
	foreach (ASTRA_STYLES_MARGES_RANGEES as $section => [$sel, $rangee]) {
		if (!isset($s["$section-padding"]) && !isset($s["$section-margin"])) {
			continue;
		}
		if ($rangee !== '') {
			[$builder, $r] = explode(':', $rangee);
			$remplie = $builder === 'pied'
				? astra_hfb_pied_rangee_remplie($n['pied'], $r)
				: astra_styles_entete_rangee_remplie($n['entete_ordinateur'], $r) || astra_styles_entete_rangee_remplie($n['entete_mobile'], $r);
			if (!$remplie) {
				continue;
			}
		}
		foreach (['' => 'desktop', '(max-width:921px)' => 'tablet', '(max-width:544px)' => 'mobile'] as $media => $appareil) {
			$d = [];
			foreach (['padding', 'margin'] as $type) {
				foreach (['top', 'bottom', 'left', 'right'] as $cote) {
					$d["$type-$cote"] = astra_styles_espacement($s["$section-$type"] ?? null, $cote, $appareil);
				}
			}
			$regles[] = [$media, $sel, $d];
		}
	}
	return $regles;
}

/** Vrai si une zone de la rangée d'un en-tête normalisé (ordinateur ou mobile) a un élément. */
function astra_styles_entete_rangee_remplie(array $entete, string $rangee): bool {
	foreach ($entete[$rangee] ?? [] as $ids) {
		if ($ids) {
			return true;
		}
	}
	return false;
}

/**
 * Un côté d'une option « côtés responsifs » pour un appareil, comme astra_responsive_spacing() :
 * vide sans valeur ou sans unité enregistrée pour cet appareil ; 0 donne 0px.
 */
function astra_styles_espacement($valeur, string $cote, string $appareil): string {
	if (!is_array($valeur) || !isset($valeur[$appareil][$cote], $valeur["$appareil-unit"])) {
		return '';
	}
	$v = $valeur[$appareil][$cote];
	return $v === '' ? '' : $v . $valeur["$appareil-unit"];
}

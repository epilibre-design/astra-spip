<?php
/**
 * Astra pour SPIP — styles du bouton global (inc/class-astra-dynamic-css.php d'Astra 4.11.0,
 * « Button Styling ») : couleurs, survol, bordure, rayon, marges, police.
 *
 * Astra émet toujours ces règles ; css/astra-dynamic.css en contient la version par défaut. Ici,
 * elles sont réémises, avec les valeurs réglées, dès qu'une option du bouton est posée.
 *
 * @license GPL-3.0-or-later
 */

const ASTRA_STYLES_SEL_CONTOUR_SURVOL_BLOC = '.wp-block-button.is-style-outline .wp-block-button__link:hover,.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link:focus,.wp-block-buttons .wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color):hover,.wp-block-buttons .wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color):hover';
const ASTRA_STYLES_SEL_CONTOUR_SURVOL = '.wp-block-button.is-style-outline .wp-block-button__link:hover,.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link:focus,.wp-block-buttons .wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color):hover,.wp-block-buttons .wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color):hover,.ast-outline-button:hover,.ast-outline-button:focus,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button:hover,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button:focus';
const ASTRA_STYLES_SEL_BLOC_LIEN = '.wp-block-button .wp-block-button__link';
const ASTRA_STYLES_SEL_BLOC_LIEN_SURVOL = '.wp-block-button .wp-block-button__link:hover,.wp-block-button .wp-block-button__link:focus';
const ASTRA_STYLES_SEL_BLOCS = '.wp-block-button .wp-block-button__link,.wp-block-search .wp-block-search__button,body .wp-block-file .wp-block-file__button';
const ASTRA_STYLES_SEL_BOUTONS = '.menu-toggle,button,.ast-button,.ast-custom-button,.button,input#submit,input[type="button"],input[type="submit"],input[type="reset"],#comments .submit,.search .search-submit,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button,body .wp-block-file .wp-block-file__button,.search .search-submit,.woocommerce-js a.button,.woocommerce button.button,.woocommerce .woocommerce-message a.button,.woocommerce #respond input#submit.alt,.woocommerce input.button.alt,.woocommerce input.button,.woocommerce input.button:disabled,.woocommerce input.button:disabled[disabled],.woocommerce input.button:disabled:hover,.woocommerce input.button:disabled[disabled]:hover,.woocommerce #respond input#submit,.woocommerce button.button.alt.disabled,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link,.wc-block-grid__product-onsale,[CLASS*="wc-block"] button,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping),.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout,.woocommerce button.button.alt.disabled.wc-variation-selection-needed,[CLASS*="wc-block"] .wc-block-components-button';
const ASTRA_STYLES_SEL_BOUTONS_SURVOL = 'button:focus,.menu-toggle:hover,button:hover,.ast-button:hover,.ast-custom-button:hover .button:hover,.ast-custom-button:hover ,input[type=reset]:hover,input[type=reset]:focus,input#submit:hover,input#submit:focus,input[type="button"]:hover,input[type="button"]:focus,input[type="submit"]:hover,input[type="submit"]:focus,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button:hover,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button:focus,body .wp-block-file .wp-block-file__button:hover,body .wp-block-file .wp-block-file__button:focus,.woocommerce-js a.button:hover,.woocommerce button.button:hover,.woocommerce .woocommerce-message a.button:hover,.woocommerce #respond input#submit:hover,.woocommerce #respond input#submit.alt:hover,.woocommerce input.button.alt:hover,.woocommerce input.button:hover,.woocommerce button.button.alt.disabled:hover,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link:hover,[CLASS*="wc-block"] button:hover,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping):hover,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout:hover,.woocommerce button.button.alt.disabled.wc-variation-selection-needed:hover,[CLASS*="wc-block"] .wc-block-components-button:hover,[CLASS*="wc-block"] .wc-block-components-button:focus';
const ASTRA_STYLES_SEL_RECHERCHE = 'form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button.has-icon';

function astra_styles_bouton(array $s): array {
	if (!astra_styles_pose($s, 'bouton')) {
		return [];
	}
	$couleur = astra_styles_ou($s, 'button-color', '#ffffff');
	$survol = astra_styles_ou($s, 'button-h-color', '#ffffff');
	$fond = astra_styles_ou($s, 'button-bg-color', 'var(--ast-global-color-0)');
	$fond_survol = astra_styles_ou($s, 'button-bg-h-color', 'var(--ast-global-color-1)');
	$famille = astra_styles_famille($s['font-family-button'] ?? '', 'inherit');
	$graisse = astra_styles_ou($s, 'font-weight-button', '500');
	$taille = astra_styles_taille_police($s['font-size-button'] ?? '', 16);
	$extras = astra_styles_extras($s['font-extras-button'] ?? []);
	$interligne = $extras['line-height'] !== '' ? $extras['line-height'] : '1em';
	$marge = [];
	foreach (['top' => 15, 'right' => 30, 'bottom' => 15, 'left' => 30] as $cote => $defaut) {
		$marge[$cote] = astra_styles_cote($s['theme-button-padding'] ?? '', $cote, $defaut);
	}
	$rayon = [];
	foreach (['top', 'right', 'bottom', 'left'] as $cote) {
		$rayon[$cote] = astra_styles_cote($s['button-radius-fields'] ?? '', $cote);
	}
	$bordure = $s['theme-button-border-group-border-size'] ?? null;
	$epaisseur = function (string $cote) use ($bordure): string {
		return astra_styles_cote($bordure ?? '', $cote) ?: '0';
	};
	$survols = ['color' => $survol, 'background-color' => $fond_survol, 'border-color' => $fond_survol];
	$rayons = [
		'border-top-left-radius' => $rayon['top'], 'border-top-right-radius' => $rayon['right'],
		'border-bottom-right-radius' => $rayon['bottom'], 'border-bottom-left-radius' => $rayon['left'],
	];
	$marges = [
		'padding-top' => $marge['top'], 'padding-right' => $marge['right'],
		'padding-bottom' => $marge['bottom'], 'padding-left' => $marge['left'],
	];
	$epaisseurs = [
		'border-style' => 'solid', 'border-top-width' => $epaisseur('top'), 'border-right-width' => $epaisseur('right'),
		'border-left-width' => $epaisseur('left'), 'border-bottom-width' => $epaisseur('bottom'),
	];
	$regles = [
		['', ASTRA_STYLES_SEL_CONTOUR_SURVOL_BLOC, $survols],
		['', ASTRA_STYLES_SEL_CONTOUR_SURVOL, $survols],
		['', ASTRA_STYLES_SEL_BLOC_LIEN, ['color' => $couleur]],
		['', ASTRA_STYLES_SEL_BLOC_LIEN_SURVOL, $survols],
		['', ASTRA_STYLES_SEL_BLOCS, ($bordure !== null ? $epaisseurs : []) + [
			'border-color' => $fond, 'background-color' => $fond, 'color' => $couleur,
			'font-family' => $famille, 'font-weight' => $graisse, 'line-height' => $interligne,
			'text-transform' => $extras['text-transform'], 'letter-spacing' => $extras['letter-spacing'], 'font-size' => $taille,
		] + $rayons + $marges],
		['', ASTRA_STYLES_SEL_BOUTONS, $epaisseurs + [
			'color' => $couleur, 'border-color' => $fond, 'background-color' => $fond,
		] + $marges + [
			'font-family' => $famille, 'font-weight' => $graisse, 'font-size' => $taille, 'line-height' => $interligne,
			'text-transform' => $extras['text-transform'], 'letter-spacing' => $extras['letter-spacing'],
		] + $rayons],
		['', ASTRA_STYLES_SEL_BOUTONS_SURVOL, $survols],
		['', ASTRA_STYLES_SEL_RECHERCHE, [
			'padding-top' => "calc({$marge['top']} - 3px)", 'padding-right' => "calc({$marge['right']} - 3px)",
			'padding-bottom' => "calc({$marge['bottom']} - 3px)", 'padding-left' => "calc({$marge['left']} - 3px)",
		]],
	];
	// Neutralisation : marges réglées pour l'ordinateur seul, Astra n'émet plus ses marges tablette
	// et mobile par défaut (14/28, 12/24), présentes dans css/astra-dynamic.css : celles de
	// l'ordinateur s'appliquent alors à tous les appareils.
	$padding = $s['theme-button-padding'] ?? [];
	if (astra_styles_renseigne(astra_styles_ordi($padding))) {
		foreach (['tablet' => '(max-width:921px)', 'mobile' => '(max-width:544px)'] as $app => $media) {
			if (!astra_styles_renseigne($padding[$app] ?? '')) {
				$regles[] = [$media, ASTRA_STYLES_SEL_BLOCS, $marges];
				$regles[] = [$media, ASTRA_STYLES_SEL_BOUTONS, $marges];
			}
		}
	}
	return $regles;
}

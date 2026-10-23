<?php
/**
 * Astra pour SPIP — icônes sociales (inc/builder/type/base/dynamic-css/social/
 * class-astra-social-component-dynamic-css.php d'Astra 4.11.0, règles ordinateur).
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_sociaux(array $s, array $hfb): array {
	$regles = [];
	foreach (['header' => 'entete', 'footer' => 'pied'] as $b => $builder) {
		$p = "$b-social-1";
		if (!astra_styles_pose($s, 'sociaux', "$p-") || !astra_styles_element_place('social-icons-1', $hfb, $builder)) {
			continue;
		}
		$sel = ".ast-$b-social-1-wrap";
		$espace = astra_styles_ou($s, "$p-space");
		$marge = $espace !== '' ? ((float) $espace / 2) . 'px' : '';
		$taille = astra_styles_ou($s, "$p-size", 18) . 'px';
		$couleur = astra_styles_ou($s, "$p-color");
		$survol = astra_styles_ou($s, "$p-h-color");
		$regles[] = ['', "$sel .ast-builder-social-element,$sel .social-show-label-true .ast-builder-social-element", ['margin-left' => $marge, 'margin-right' => $marge]];
		$regles[] = ['', "$sel .ast-builder-social-element svg", ['width' => $taille, 'height' => $taille]];
		if (($s["$p-color-type"] ?? 'custom') === 'custom') {
			array_push($regles,
				['', "$sel .ast-social-color-type-custom svg", ['fill' => $couleur]],
				['', "$sel .ast-social-color-type-custom .ast-builder-social-element:hover", ['color' => $survol]],
				['', "$sel .ast-social-color-type-custom .ast-builder-social-element:hover svg", ['fill' => $survol]],
				['', "$sel .ast-social-color-type-custom .social-item-label", ['color' => $couleur]]
			);
		}
		$regles[] = ['', "$sel .ast-builder-social-element:hover .social-item-label", ['color' => $survol]];
	}
	return $regles;
}

<?php
/**
 * Astra pour SPIP — pied de page : widgets (inc/builder/type/base/dynamic-css/widget/
 * class-astra-widget-component-dynamic-css.php) et copyright (inc/builder/type/footer/copyright/
 * dynamic-css/dynamic.css.php) d'Astra 4.13.8, règles ordinateur.
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_pied(array $s, array $hfb): array {
	$regles = [];
	for ($n = 1; $n <= 4; $n++) {
		$p = "footer-widget-$n";
		if (!astra_styles_pose($s, 'pied', "$p-") || !astra_styles_element_place("widget-$n", $hfb, 'pied')) {
			continue;
		}
		$sel = '.footer-widget-area[data-section="sidebar-widgets-footer-widget-' . $n . '"]';
		$titres = ["$sel .widget-title"];
		for ($h = 1; $h <= 6; $h++) {
			array_push($titres, "$sel h$h", "$sel .widget-area h$h");
		}
		array_push($regles,
			['', "$sel.footer-widget-area-inner", [
				'color' => astra_styles_ou($s, "$p-color"), 'font-size' => astra_styles_taille_police($s["$p-content-font-size"] ?? ''),
			]],
			['', "$sel.footer-widget-area-inner a", ['color' => astra_styles_ou($s, "$p-link-color")]],
			['', "$sel.footer-widget-area-inner a:hover", ['color' => astra_styles_ou($s, "$p-link-h-color")]],
			['', implode(',', $titres), [
				'color' => astra_styles_ou($s, "$p-title-color"), 'font-size' => astra_styles_taille_police($s["$p-font-size"] ?? ''),
			]]
		);
	}
	$copyright = ['footer-copyright-color' => 1, 'font-size-section-footer-copyright' => 1];
	if (array_intersect_key($s, $copyright) && astra_styles_element_place('copyright', $hfb, 'pied')) {
		array_push($regles,
			['', '.ast-footer-copyright.site-footer-focus-item', ['color' => astra_styles_ou($s, 'footer-copyright-color', 'var(--ast-global-color-3)')]],
			['', '.ast-footer-copyright.site-footer-focus-item', ['font-size' => astra_styles_taille_police($s['font-size-section-footer-copyright'] ?? '', 16)]]
		);
	}
	return $regles;
}

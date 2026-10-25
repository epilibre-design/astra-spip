<?php
/**
 * Seuil d'en-tête et repli sans JavaScript.
 * @license GPL-3.0-or-later
 */

/** Source : Astra 4.13.8 astra_header_break_point(), distinct du seuil tablette. */
function astra_responsive_seuil_entete(): int {
	return 921;
}

/** Source : Astra_Enqueue_Scripts::set_header_break_point_early(), Astra 4.13.8. */
function astra_responsive_script_precoce(int $seuil): string {
	$borne = number_format(abs($seuil) + 0.99, 2, '.', '');
	return "(function(){if(window.matchMedia('(max-width:{$borne}px)').matches){document.body.classList.add('ast-header-break-point');document.body.classList.remove('ast-desktop');}})();";
}

/** Menus placés dans l'en-tête mobile, dans l'ordre visuel, sans doublon d'élément. */
function astra_responsive_menus_sans_js(array $hfb): array {
	$menus = [];
	foreach (['above', 'primary', 'below', 'popup'] as $rangee) {
		foreach ($hfb['entete_mobile'][$rangee] ?? [] as $elements) {
			foreach ($elements as $id) {
				if (!in_array($id, ['menu-1', 'menu-2', 'mobile-menu'], true) || isset($menus[$id])) {
					continue;
				}
				$numero = $id === 'menu-2' ? 2 : 1;
				$reglages = $hfb['elements']['entete']['menu-' . $numero];
				$menus[$id] = [
					'id' => $id, 'numero' => $numero,
					'source' => $reglages['source'], 'id_source' => (int) $reglages['id'],
					'accueil' => $numero === 1 ? 'oui' : '',
				];
			}
		}
	}
	return array_values($menus);
}

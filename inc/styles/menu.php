<?php
/**
 * Astra pour SPIP — menus de l'en-tête (inc/builder/type/header/menu/dynamic-css/dynamic.css.php
 * d'Astra 4.13.8, règles ordinateur) : couleurs normale, au survol et de la page courante, fonds,
 * sous-menus, police.
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_menu(array $s, array $hfb): array {
	$regles = [];
	foreach ([1, 2] as $n) {
		$p = "header-menu$n";
		if (!astra_styles_pose($s, 'menu', "$p-") || !astra_styles_element_place("menu-$n", $hfb, 'entete')) {
			continue;
		}
		$sel = ".ast-builder-menu-$n";
		$extras = astra_styles_extras($s["$p-font-extras"] ?? []);
		$famille = astra_styles_ou($s, "$p-font-family", 'inherit');
		$couleur = astra_styles_ou($s, "$p-color-responsive", 'var(--ast-global-color-3)');
		$survol = astra_styles_ou($s, "$p-h-color-responsive", 'var(--ast-global-color-1)');
		$actif = astra_styles_ou($s, "$p-a-color-responsive", 'var(--ast-global-color-1)');
		$bordure = $s["$p-submenu-border"] ?? ['top' => 2, 'bottom' => 0, 'left' => 0, 'right' => 0];
		// Astra : un côté vide vaut 0 (empty()), le défaut ne joue que si l'option est absente.
		$cote = function (string $c) use ($bordure): string {
			return (int) ($bordure[$c] ?? 0) . 'px';
		};
		$haut = (int) ($bordure['top'] ?? 0);
		array_push($regles,
			['', $sel, [
				'font-family' => $famille === 'inherit' ? 'inherit' : astra_styles_famille($famille),
				'font-weight' => astra_styles_ou($s, "$p-font-weight", 'inherit'),
				'text-transform' => $extras['text-transform'],
			]],
			['', "$sel .menu-item > .menu-link", [
				'line-height' => $extras['line-height'], 'font-size' => astra_styles_taille_police($s["$p-font-size"] ?? ''),
				'color' => $couleur, 'text-decoration' => $extras['text-decoration'], 'letter-spacing' => $extras['letter-spacing'],
			]],
			['', "$sel .menu-item > .ast-menu-toggle", ['color' => $couleur]],
			['', "$sel .menu-link:hover,$sel .main-header-menu > .menu-item:hover > .menu-link,$sel .inline-on-mobile .ast-menu-toggle:hover,$sel .inline-on-mobile .main-header-menu > .menu-item:hover > .ast-menu-toggle", [
				'color' => $survol, 'background' => astra_styles_ou($s, "$p-h-bg-color-responsive"),
			]],
			['', "$sel .ast-menu-toggle:hover,$sel .main-header-menu > .menu-item:hover > .ast-menu-toggle", ['color' => $survol]],
			['', "$sel .menu-item.current-menu-item > .menu-link,$sel .inline-on-mobile .menu-item.current-menu-item > .ast-menu-toggle,$sel .current-menu-ancestor > .menu-link", [
				'color' => $actif, 'background' => astra_styles_ou($s, "$p-a-bg-color-responsive"),
			]],
			['', "$sel .menu-item.current-menu-item > .ast-menu-toggle", ['color' => $actif]],
			['', "$sel .sub-menu,$sel .inline-on-mobile .sub-menu", [
				'border-top-width' => $cote('top'), 'border-bottom-width' => $cote('bottom'),
				'border-right-width' => $cote('right'), 'border-left-width' => $cote('left'),
				'border-color' => astra_styles_ou($s, "$p-submenu-b-color", 'var(--ast-global-color-0)'), 'border-style' => 'solid',
			]],
			// Sous-menus imbriqués et zone de survol décalés de la bordure haute (décalage haut : 0).
			['', "$sel .sub-menu .sub-menu", ['top' => (-1 * $haut) . 'px']],
			['', ".ast-desktop $sel .main-header-menu > .menu-item > .sub-menu:before,.ast-desktop $sel .main-header-menu > .menu-item > .astra-full-megamenu-wrapper:before", [
				'height' => "calc( 0px + {$haut}px + 5px )",
			]],
			['', "$sel .main-header-menu,$sel .main-header-menu .sub-menu", astra_styles_fond($s["$p-bg-obj-responsive"] ?? [])]
		);
	}
	return $regles;
}

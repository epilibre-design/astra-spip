<?php
/**
 * Astra pour SPIP — identité du site (inc/class-astra-dynamic-css.php d'Astra 4.13.8) : couleurs
 * et tailles du titre et du slogan, affichage, largeur du logo.
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_identite(array $s): array {
	if (!astra_styles_pose($s, 'identite') && !astra_identite_responsive_pose($s)) {
		return [];
	}
	$visible = function (string $option, bool $defaut) use ($s): string {
		$v = astra_styles_ordi($s[$option] ?? '');
		return ($v === '' || $v === null ? $defaut : (bool) $v) ? 'block' : 'none';
	};
	$titre = $visible('display-site-title-responsive', true);
	$slogan = $visible('display-site-tagline-responsive', false);
	$logo = astra_styles_ou($s, 'ast-header-responsive-logo-width');
	$largeur = $logo !== '' ? $logo . 'px' : '';
	$regles = [
		['', '.ast-site-identity .site-title a', ['color' => astra_styles_ou($s, 'header-color-site-title', 'var(--ast-global-color-2)')]],
		['', '.ast-site-identity .site-title a:hover', ['color' => astra_styles_ou($s, 'header-color-h-site-title')]],
		['', '.ast-site-identity .site-description', ['color' => astra_styles_ou($s, 'header-color-site-tagline')]],
		['', '.site-title', ['font-size' => astra_identite_responsive_pose(['font-size-site-title' => $s['font-size-site-title'] ?? []]) ? (astra_identite_taille_appareil($s['font-size-site-title'], 'desktop') ?: 'inherit') : astra_styles_taille_police($s['font-size-site-title'] ?? '', 26), 'display' => $titre]],
		['', 'header .custom-logo-link img', ['max-width' => $largeur, 'width' => $largeur]],
		['', '.astra-logo-svg', ['width' => $largeur]],
		['', '.site-header .site-description', ['font-size' => astra_identite_responsive_pose(['font-size-site-tagline' => $s['font-size-site-tagline'] ?? []]) ? (astra_identite_taille_appareil($s['font-size-site-tagline'], 'desktop') ?: 'inherit') : astra_styles_taille_police($s['font-size-site-tagline'] ?? '', 15), 'display' => $slogan]],
	];
	// Astra : tailles puis affichage, largeur du SVG et images, tablette avant mobile.
	foreach (['tablet' => '(max-width:921px)', 'mobile' => '(max-width:544px)'] as $appareil => $media) {
		$regles[] = [$media, '.site-title', [
			'font-size' => astra_identite_taille_appareil($s['font-size-site-title'] ?? [], $appareil),
			'display' => astra_identite_visible_appareil($s['display-site-title-responsive'] ?? [], $appareil, true) ? 'block' : 'none',
		]];
		$regles[] = [$media, '.site-header .site-description', [
			'font-size' => astra_identite_taille_appareil($s['font-size-site-tagline'] ?? [], $appareil),
			'display' => astra_identite_visible_appareil($s['display-site-tagline-responsive'] ?? [], $appareil, false) ? 'block' : 'none',
		]];
		$v = $s['ast-header-responsive-logo-width'][$appareil] ?? '';
		$l = $v !== '' ? $v . 'px' : '';
		if ($appareil === 'tablet') {
			$regles[] = [$media, '.astra-logo-svg', ['width' => $l]];
			$regles[] = [$media, 'header .custom-logo-link img,.ast-header-break-point .site-logo-img .custom-mobile-logo-link img', ['max-width' => $l, 'width' => $l]];
		} else {
			$regles[] = [$media, 'header .custom-logo-link img,.ast-header-break-point .site-branding img,.ast-header-break-point .custom-logo-link img', ['max-width' => $l, 'width' => $l]];
			$regles[] = [$media, '.astra-logo-svg', ['width' => $l]];
			$regles[] = [$media, '.ast-header-break-point .site-logo-img .custom-mobile-logo-link img', ['max-width' => $l]];
		}
	}
	return $regles;
}

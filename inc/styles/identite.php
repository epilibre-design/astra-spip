<?php
/**
 * Astra pour SPIP — identité du site (inc/class-astra-dynamic-css.php d'Astra 4.13.8) : couleurs
 * et tailles du titre et du slogan, affichage, largeur du logo.
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_identite(array $s): array {
	if (!astra_styles_pose($s, 'identite')) {
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
		['', '.site-title', ['font-size' => astra_styles_taille_police($s['font-size-site-title'] ?? '', 26), 'display' => $titre]],
		['', 'header .custom-logo-link img', ['max-width' => $largeur, 'width' => $largeur]],
		['', '.astra-logo-svg', ['width' => $largeur]],
		['', '.site-header .site-description', ['font-size' => astra_styles_taille_police($s['font-size-site-tagline'] ?? '', 15), 'display' => $slogan]],
	];
	// Seule la valeur ordinateur est exploitée : l'affichage vaut pour tous les appareils.
	foreach (['(max-width:921px)', '(max-width:544px)'] as $media) {
		$regles[] = [$media, '.site-title', ['display' => $titre]];
		$regles[] = [$media, '.site-header .site-description', ['display' => $slogan]];
	}
	return $regles;
}

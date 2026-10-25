<?php
/**
 * Identité responsive, portée d'Astra 4.13.8 : inc/class-astra-dynamic-css.php,
 * Astra_Dynamic_CSS::return_output(), et inc/core/common-functions.php,
 * astra_responsive_font()/astra_get_font_css_value(). Profil de base 16 px.
 * @license GPL-3.0-or-later
 */
function astra_identite_responsive_pose(array $styles): bool {
	foreach (['ast-header-responsive-logo-width', 'font-size-site-title', 'font-size-site-tagline', 'display-site-title-responsive', 'display-site-tagline-responsive'] as $option) {
		foreach (['tablet', 'mobile'] as $appareil) {
			if (isset($styles[$option]) && is_array($styles[$option]) && array_key_exists($appareil, $styles[$option]) && $styles[$option][$appareil] !== '' && $styles[$option][$appareil] !== null) {
				return true;
			}
		}
	}
	return false;
}

/** Défaut propre à l'appareil, partagé par le HTML et le CSS ; faux reste explicite. */
function astra_identite_visible_appareil(array $valeurs, string $appareil, bool $defaut): bool {
	return array_key_exists($appareil, $valeurs) && $valeurs[$appareil] !== '' && $valeurs[$appareil] !== null ? (bool) $valeurs[$appareil] : $defaut;
}

/** Valeur amont ; le nombre zéro et la chaîne zéro sont distincts dans Astra 4.13.8. */
function astra_identite_taille_appareil(array $valeurs, string $appareil): string {
	$v = $valeurs[$appareil] ?? '';
	if ($v === '' || $v === null || $v === 0 || $v === 0.0) {
		return '';
	}
	$unite = $valeurs[$appareil . '-unit'] ?? 'px';
	if ($unite !== 'px') {
		return $v . $unite;
	}
	return (int) $v . 'px;font-size:' . ((int) $v / ASTRA_STYLES_TAILLE_TEXTE) . 'rem';
}

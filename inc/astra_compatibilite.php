<?php
/**
 * Astra pour SPIP — drapeaux de compatibilité d'Astra 4.11.0 (méta astra/compatibilite,
 * docs/superpowers/specs/2026-10-23-compatibilite-reglages-design.md).
 *
 * Un site installé avec une ancienne version d'Astra garde des drapeaux dans astra-settings ; les
 * Starter Templates les exportent dans site.json. Ils ne s'éditent pas dans le Customizer : ils
 * viennent d'un import (lot de Garden, clé config.astra.compatibilite).
 *
 * @license GPL-3.0-or-later
 */

/**
 * Drapeaux acceptés : submenu-below-header (booléen, Astra_Dynamic_CSS::astra_submenu_below_header_fix())
 * et can-support-widget-and-editor-fonts (présence seule, support_font_css_to_widget_and_in_editor()
 * teste isset). Toute autre clé est retirée, comme une option de style inconnue.
 */
function astra_compatibilite_normaliser($valeurs): array {
	$r = [];
	$v = is_array($valeurs) ? $valeurs : [];
	if (array_key_exists('submenu-below-header', $v) && (is_bool($v['submenu-below-header']) || in_array($v['submenu-below-header'], [0, 1, '0', '1'], true))) {
		$r['submenu-below-header'] = (bool) $v['submenu-below-header'];
	}
	if (array_key_exists('can-support-widget-and-editor-fonts', $v) && $v['can-support-widget-and-editor-fonts'] !== null) {
		$r['can-support-widget-and-editor-fonts'] = true;
	}
	return $r;
}

/** Vrai sauf si submenu-below-header est posé à faux (astra_submenu_below_header_fix()). */
function astra_compatibilite_sous_menu_fix(array $c): bool {
	return ($c['submenu-below-header'] ?? true) !== false;
}

/** Vrai si can-support-widget-and-editor-fonts est posé (graisses des titres d'avant Astra 3.6.0). */
function astra_compatibilite_polices_anciennes(array $c): bool {
	return isset($c['can-support-widget-and-editor-fonts']);
}

/** Feuille des règles qui dépendent de submenu-below-header, extraite du rendu d'Astra. */
function astra_compatibilite_feuille_sous_menu(array $c): string {
	return astra_compatibilite_sous_menu_fix($c) ? 'css/compat/sous-menu-fix.css' : 'css/compat/sous-menu-ancien.css';
}

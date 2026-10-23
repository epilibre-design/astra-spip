<?php
/**
 * Astra pour SPIP — installation et mises à jour de la configuration.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Mises à jour. Un site antérieur au schéma 1.0.0 n'a pas de version enregistrée : SPIP exécute
 * alors `create`, qui migre aussi.
 */
function astra_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [
		'create' => [['astra_migrer_identite']],
		'1.0.0' => [['astra_migrer_identite']],
	];
	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function astra_vider_tables($nom_meta_base_version) {
	effacer_meta($nom_meta_base_version);
}

/**
 * Titre et slogan : afficher_titre_site et afficher_slogan deviennent les options d'Astra
 * display-site-title-responsive et display-site-tagline-responsive (méta astra/styles), sans
 * changement de rendu. Le slogan, masqué jusqu'ici par css/astra-dynamic.css quel que soit
 * afficher_slogan, reste masqué (défaut d'Astra) ; seul un titre masqué s'écrit.
 */
function astra_migrer_identite(): void {
	include_spip('inc/config');
	if (lire_config('astra/afficher_titre_site') === 'non') {
		$styles = lire_config('astra/styles');
		$styles = is_array($styles) ? $styles : [];
		if (!isset($styles['display-site-title-responsive'])) {
			$styles['display-site-title-responsive'] = ['desktop' => false, 'tablet' => false, 'mobile' => false];
			ecrire_config('astra/styles', $styles);
		}
	}
	effacer_config('astra/afficher_titre_site');
	effacer_config('astra/afficher_slogan');
}

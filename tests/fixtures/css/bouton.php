<?php
// Aller-retour de la seule option de rayon, sur les deux sites privés d'import.
if (PHP_SAPI !== 'cli') { exit; }
$regler = getenv('CSS_BOUTON_ACTION') === 'regler';
$rayon = json_decode(file_get_contents(dirname(__DIR__,2) . '/references/styles/valeurs/bouton-rayon.json'), true)['button-radius-fields'];
if (defined('ABSPATH')) {
	if ($regler) {
		$c = get_option('astra-settings', []);
		update_option('astra_preuve_styles_avant', $c);
		$c['button-radius-fields'] = $rayon;
		update_option('astra-settings', $c);
	} else {
		$c = get_option('astra_preuve_styles_avant', null);
		if ($c !== null) { update_option('astra-settings', $c); delete_option('astra_preuve_styles_avant'); }
	}
} elseif (defined('_ECRIRE_INC_VERSION')) {
	include_spip('inc/config');
	if ($regler) {
		ecrire_meta('astra_preuve_styles_avant', serialize(lire_config('astra/styles', null)));
		ecrire_config('astra/styles', ['button-radius-fields'=>$rayon]);
	} elseif (isset($GLOBALS['meta']['astra_preuve_styles_avant'])) {
		$c = unserialize($GLOBALS['meta']['astra_preuve_styles_avant'], ['allowed_classes'=>false]);
		if ($c === null) { effacer_config('astra/styles'); } else { ecrire_config('astra/styles', $c); }
		effacer_meta('astra_preuve_styles_avant');
	}
} else { throw new RuntimeException('Charger dans WordPress ou SPIP'); }

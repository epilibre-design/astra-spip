<?php
/**
 * Astra pour SPIP — formulaire des styles du Customizer (méta astra/styles).
 *
 * Les options identifiées par le registre sont saisies sur les trois appareils ; les autres
 * gardent leur valeur ordinateur et les appareils importés non exposés.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/astra_styles_saisie');

function formulaires_configurer_astra_styles_charger_dist() {
	$styles = lire_config('astra/styles');
	$styles = is_array($styles) ? $styles : [];
	// protege_champ() du CVT transforme false en ''. Les choix booléens doivent
	// donc être préparés en '0'/'1', sans réécrire les valeurs stockées.
	foreach (astra_styles_registre() as $option => $definition) {
		if ($definition['type'] !== 'booleen' || !array_key_exists($option, $styles)) {
			continue;
		}
		if ($definition['responsive'] && is_array($styles[$option])) {
			foreach ($styles[$option] as &$valeur) {
				if (is_bool($valeur)) { $valeur = $valeur ? '1' : '0'; }
			}
			unset($valeur);
		} elseif (is_bool($styles[$option])) {
			$styles[$option] = $styles[$option] ? '1' : '0';
		}
	}
	return ['s' => $styles];
}

function formulaires_configurer_astra_styles_verifier_dist() {
	$erreurs = [];
	foreach (astra_styles_saisie_refusees(astra_styles_saisie_postee()) as $option) {
		$erreurs['s_' . $option] = _T('astra:styles_valeur_refusee');
	}
	if ($erreurs) {
		$erreurs['message_erreur'] = _T('astra:styles_erreurs_saisie');
	}
	return $erreurs;
}

function formulaires_configurer_astra_styles_traiter_dist() {
	$styles = lire_config('astra/styles');
	$styles = astra_styles_saisie_fusionner(is_array($styles) ? $styles : [], astra_styles_saisie_postee());
	ecrire_config('astra/styles', astra_styles_normaliser($styles));

	// Les styles figurent sur toutes les pages.
	include_spip('inc/invalideur');
	suivre_invalideur('1');

	return ['message_ok' => _T('config_info_enregistree'), 'editable' => true];
}

/** Champs postés : s[<option>]. */
function astra_styles_saisie_postee(): array {
	$s = _request('s');
	return is_array($s) ? $s : [];
}

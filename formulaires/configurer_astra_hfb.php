<?php

/**
 * Formulaire de configuration du builder d'en-tête et de pied de page
 *
 * @plugin Astra
 * @license GNU/GPL v3+
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');
include_spip('inc/astra_hfb_saisie');

/**
 * Champs du formulaire : emplacement et ordre de chaque élément, réglages des rangées,
 * mode du menu mobile, réglages des éléments.
 */
function formulaires_configurer_astra_hfb_charger_dist() {
	return astra_hfb_saisie_depuis_config(astra_hfb_normaliser(lire_config('astra/hfb', [])));
}

function formulaires_configurer_astra_hfb_verifier_dist() {
	$erreurs = [];
	foreach (astra_hfb_verifier_saisie(astra_hfb_saisie_postee()) as $champ => $code) {
		$erreurs[$champ] = _T('astra:hfb_erreur_' . $code);
	}
	if ($erreurs) {
		$erreurs['message_erreur'] = _T('astra:hfb_erreurs_saisie');
	}
	return $erreurs;
}

function formulaires_configurer_astra_hfb_traiter_dist() {
	$saisie = astra_hfb_saisie_postee();
	$config = [];
	foreach (astra_hfb_builders() as $builder => $cle) {
		$config[$cle] = astra_hfb_placements_depuis_saisie($saisie['place'][$builder] ?? [], $saisie['ordre'][$builder] ?? [], $builder);
	}
	$config['rangees'] = astra_hfb_rangees_depuis_saisie($saisie['rangees']);
	$config['menu_mobile'] = $saisie['menu_mobile'];
	$config['elements'] = astra_hfb_elements_depuis_saisie($saisie['elements']);
	ecrire_config('astra/hfb', $config);

	// L'en-tête et le pied de page figurent sur toutes les pages.
	include_spip('inc/invalideur');
	suivre_invalideur('1');

	return ['message_ok' => _T('config_info_enregistree'), 'editable' => true];
}

/**
 * Champs postés, sous la forme attendue par les fonctions de inc/astra_hfb_saisie.
 */
function astra_hfb_saisie_postee(): array {
	$tableau = function ($nom) {
		$valeur = _request($nom);
		return is_array($valeur) ? $valeur : [];
	};
	return [
		'place' => $tableau('place'),
		'ordre' => $tableau('ordre'),
		'rangees' => $tableau('rangees'),
		'menu_mobile' => (string) _request('menu_mobile'),
		'elements' => $tableau('elements'),
	];
}

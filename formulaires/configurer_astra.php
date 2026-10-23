<?php
/**
 * Astra pour SPIP — vérification du formulaire de configuration du thème.
 * Chargement et enregistrement : automatiques (formulaire configurer_, méta astra).
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_astra_verifier_dist() {
	$erreurs = [];
	$type = _request('accueil_type');
	if ($type !== null && !in_array($type, ['articles', 'page'], true)) {
		$erreurs['accueil_type'] = _T('astra:cfg_accueil_type_erreur');
	}
	if ($type === 'page') {
		include_spip('inc/astra_pages');
		$id = (int) _request('accueil_page');
		if (!astra_est_page_unique($id) || sql_getfetsel('statut', 'spip_articles', 'id_article=' . $id) !== 'publie') {
			$erreurs['accueil_page'] = _T('astra:cfg_accueil_page_erreur');
		}
	}
	return $erreurs;
}

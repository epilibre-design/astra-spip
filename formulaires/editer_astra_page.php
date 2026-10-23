<?php
/**
 * Astra pour SPIP — réglages d'affichage d'une page unique (titre, disposition).
 *
 * Nommé editer_ et non configurer_ : SPIP impose l'autorisation « configurer » à tout
 * formulaire configurer_* (ecrire/inc/cvt_configurer.php). Ici, ce sont les droits de
 * modification de la page qui comptent.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/astra_pages');
include_spip('inc/autoriser');

function formulaires_editer_astra_page_charger_dist($id_article) {
	$id_article = (int) $id_article;
	if (!astra_est_page_unique($id_article)) {
		return false;
	}
	$r = astra_page_reglages($id_article);
	return [
		'affichage_titre' => $r['titre'],
		'disposition' => $r['disposition'],
		'dispositions' => astra_page_dispositions(),
		'editable' => autoriser('modifier', 'article', $id_article),
	];
}

function formulaires_editer_astra_page_verifier_dist($id_article) {
	$erreurs = [];
	if (!in_array(_request('affichage_titre'), ['afficher', 'masquer'], true)) {
		$erreurs['affichage_titre'] = _T('astra:page_erreur_choix');
	}
	if (!in_array(_request('disposition'), astra_page_dispositions(), true)) {
		$erreurs['disposition'] = _T('astra:page_erreur_choix');
	}
	return $erreurs;
}

function formulaires_editer_astra_page_traiter_dist($id_article) {
	$id_article = (int) $id_article;
	if (!astra_est_page_unique($id_article) || !autoriser('modifier', 'article', $id_article)) {
		return ['message_erreur' => _T('astra:page_erreur_droits')];
	}
	include_spip('inc/config');
	ecrire_config('astra/pages/' . $id_article, astra_page_normaliser([
		'titre' => _request('affichage_titre'),
		'disposition' => _request('disposition'),
	]));
	include_spip('inc/invalideur');
	suivre_invalideur("id='article/$id_article'");
	return ['message_ok' => _T('astra:page_reglages_enregistres'), 'editable' => true];
}

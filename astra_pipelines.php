<?php
/**
 * Astra pour SPIP — pipelines.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Gabarit de page pour les pages uniques (plugin Pages).
 *
 * Priorité : variante article=<page>.html choisie par Pages, puis page_unique.html du site,
 * puis celui du plugin (find_in_path). On n'agit que si le squelette retenu est encore
 * article.html : une variante déjà choisie n'est jamais remplacée, et si ce pipeline passe
 * avant celui de Pages, Pages remplace de toute façon le résultat par sa variante.
 */
function astra_styliser($flux) {
	if (
		test_espace_prive()
		|| ($flux['args']['fond'] ?? '') !== 'article'
		|| !defined('_DIR_PLUGIN_PAGES')
		|| basename((string) $flux['data']) !== 'article'
	) {
		return $flux;
	}
	include_spip('inc/astra_pages');
	if (!astra_est_page_unique((int) ($flux['args']['contexte']['id_article'] ?? 0))) {
		return $flux;
	}
	$ext = $flux['args']['ext'];
	if ($f = find_in_path("page_unique.$ext")) {
		$flux['data'] = substr($f, 0, -strlen(".$ext"));
	}
	return $flux;
}

/** Réglages d'affichage sur la fiche d'une page unique dans l'espace privé. */
function astra_affiche_milieu($flux) {
	$e = trouver_objet_exec($flux['args']['exec'] ?? '');
	$id = (int) ($flux['args']['id_article'] ?? 0);
	if ($e && empty($e['edition']) && ($e['type'] ?? '') === 'article' && $id) {
		include_spip('inc/astra_pages');
		if (astra_est_page_unique($id)) {
			$flux['data'] .= recuperer_fond('prive/squelettes/inclure/astra-page', ['id_article' => $id]);
		}
	}
	return $flux;
}

/** Réglages d'une page supprimée : effacés avec elle. */
function astra_trig_supprimer_objets_lies($objets) {
	include_spip('inc/config');
	foreach ((array) $objets as $o) {
		if (($o['type'] ?? '') === 'article' && !empty($o['id'])) {
			effacer_config('astra/pages/' . (int) $o['id']);
		}
	}
	return $objets;
}

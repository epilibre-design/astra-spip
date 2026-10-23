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

/**
 * Réglages d'une page supprimée : effacés avec elle.
 *
 * trig_supprimer_objets_lies signale aussi chaque lien retiré par objet_dissocier() (un auteur
 * dissocié d'une page envoie type = article, id = la page) : seuls les réglages d'un article
 * qui n'existe plus sont effacés.
 */
function astra_trig_supprimer_objets_lies($objets) {
	include_spip('inc/config');
	foreach ((array) $objets as $o) {
		$id = (int) ($o['id'] ?? 0);
		if (($o['type'] ?? '') === 'article' && $id && !sql_countsel('spip_articles', 'id_article=' . $id)) {
			effacer_config('astra/pages/' . $id);
		}
	}
	return $objets;
}

/**
 * Réglages des pages disparues : la tâche optimiser efface les articles à la poubelle par
 * sql_delete, sans trig_supprimer_objets_lies. Ajoute le nombre de réglages effacés.
 */
function astra_optimiser_base_disparus($flux) {
	include_spip('inc/config');
	$ids = array_map('intval', array_keys((array) lire_config('astra/pages', [])));
	$existants = $ids ? array_map('intval', array_column(sql_allfetsel('id_article', 'spip_articles', sql_in('id_article', $ids)), 'id_article')) : [];
	foreach (array_diff($ids, $existants) as $id) {
		effacer_config('astra/pages/' . $id);
		$flux['data']++;
	}
	return $flux;
}

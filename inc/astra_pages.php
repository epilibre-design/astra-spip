<?php
/**
 * Astra pour SPIP — pages uniques (plugin Pages) : réglages par page, dispositions,
 * arbre des pages pour les menus, page d'accueil statique.
 *
 * @license GPL-3.0-or-later
 */

/** Réglages d'une page sans configuration propre. */
function astra_page_reglages_defaut(): array {
	return ['titre' => 'afficher', 'disposition' => 'defaut'];
}

/** Dispositions proposées pour une page. */
function astra_page_dispositions(): array {
	return ['defaut', 'normale', 'etroite', 'pleine'];
}

/** Réglages valides d'une page : clés connues seulement, valeurs inconnues remplacées par le défaut. */
function astra_page_normaliser($reglages): array {
	$reglages = is_array($reglages) ? $reglages : [];
	$r = astra_page_reglages_defaut();
	if (in_array($reglages['titre'] ?? '', ['afficher', 'masquer'], true)) {
		$r['titre'] = $reglages['titre'];
	}
	if (in_array($reglages['disposition'] ?? '', astra_page_dispositions(), true)) {
		$r['disposition'] = $reglages['disposition'];
	}
	return $r;
}

/** Disposition appliquée : celle des pages d'Astra par défaut (normal-width-container → plain-container). */
function astra_page_disposition_effective(string $disposition): string {
	return $disposition === 'defaut' ? 'normale' : $disposition;
}

/** Classes du body d'Astra pour une disposition (inc/markup-extras.php d'Astra 4.11.0). */
function astra_page_classes_disposition(string $disposition): array {
	$classes = ['normale' => 'ast-plain-container', 'etroite' => 'ast-narrow-container', 'pleine' => 'ast-page-builder-template'];
	return [$classes[astra_page_disposition_effective($disposition)] ?? 'ast-plain-container'];
}

/**
 * Classes du body d'une page, comme Astra sur WordPress (référence : tests/references/sorties/P*).
 * Les pages n'ont pas de barre latérale par défaut dans Astra ; `page-parent` marque une page
 * qui a des sous-pages.
 */
function astra_page_classes_body(int $id, array $reglages, bool $accueil, bool $parente = false): string {
	$classes = $accueil ? ['home'] : [];
	array_push($classes, 'page-template-default', 'page', 'page-id-' . $id);
	if ($parente) {
		$classes[] = 'page-parent';
	}
	$classes[] = 'ast-desktop';
	$classes = array_merge($classes, astra_page_classes_disposition($reglages['disposition']));
	array_push($classes, 'ast-no-sidebar', 'astra-' . (defined('_ASTRA_VERSION_THEME') ? _ASTRA_VERSION_THEME : '4.11.0'), 'ast-single-post', 'ast-inherit-site-logo-transparent', 'ast-hfb-header');
	if ($reglages['titre'] === 'afficher') {
		$classes[] = 'ast-normal-title-enabled';
	}
	return implode(' ', $classes);
}

/** Contexte transmis par le gabarit de page aux inclusions (spec, A4). */
function astra_page_contexte(int $id_article, array $reglages, bool $accueil): array {
	return [
		'type_page' => $accueil ? 'sommaire' : 'page',
		'id_article' => $id_article,
		'titre' => $reglages['titre'],
		'disposition' => astra_page_disposition_effective($reglages['disposition']),
	];
}

/** Vrai si l'article est une page unique (hors rubrique). */
function astra_est_page_unique(int $id_article): bool {
	return $id_article > 0 && (int) sql_getfetsel('id_rubrique', 'spip_articles', 'id_article=' . $id_article) === -1;
}

/** Réglages effectifs d'une page (méta astra/pages/<id>). */
function astra_page_reglages(int $id_article): array {
	include_spip('inc/config');
	return astra_page_normaliser(lire_config('astra/pages/' . $id_article));
}

/** Vrai si la page a au moins une sous-page publiée (liens a2a de type sous_page). */
function astra_page_a_sous_pages(int $id_article): bool {
	if (!test_plugin_actif('a2a')) {
		return false;
	}
	return (bool) sql_countsel(
		'spip_articles_lies AS l INNER JOIN spip_articles AS a ON a.id_article = l.id_article_lie',
		['l.id_article=' . $id_article, "l.type_liaison='sous_page'", "a.statut='publie'", 'a.id_rubrique=-1']
	);
}

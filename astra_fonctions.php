<?php

/**
 * Fonctions des squelettes du thème Astra
 *
 * @plugin Astra
 * @license GNU/GPL v3+
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Version du thème Astra d'origine dont le rendu est reproduit */
define('_ASTRA_VERSION_THEME', '4.11.0');

include_once __DIR__ . '/inc/astra_hfb.php';

/**
 * Configuration du builder d'en-tête et de pied de page, normalisée.
 */
function astra_hfb_config(): array {
	include_spip('inc/config');
	return astra_hfb_normaliser(lire_config('astra/hfb', []));
}

/**
 * Rangées à afficher pour un contexte du builder : entete_ordinateur, entete_mobile ou pied.
 */
function astra_hfb_rangees(string $contexte): array {
	$config = astra_hfb_config();
	if ($contexte === 'pied') {
		return astra_hfb_calculer_pied($config['pied'], $config['rangees']);
	}
	if ($contexte === 'entete_ordinateur' || $contexte === 'entete_mobile') {
		return astra_hfb_calculer_entete($config[$contexte]);
	}
	return [];
}

/**
 * Éléments du panneau mobile (vide s'il ne doit pas être affiché).
 */
function astra_hfb_panneau(): array {
	return astra_hfb_calculer_panneau(astra_hfb_config()['entete_mobile']);
}

/**
 * Une rangée d'un contexte du builder (voir astra_hfb_rangees()), ou [] si elle n'est pas affichée.
 */
function astra_hfb_rangee(string $rangee, string $contexte): array {
	foreach (astra_hfb_rangees($contexte) as $r) {
		if ($r['rangee'] === $rangee) {
			return $r;
		}
	}
	return [];
}

/**
 * Réglages d'un élément du builder : groupe 'entete' ou 'pied', identifiant de l'élément.
 */
function astra_hfb_reglages(string $groupe, string $id): array {
	$elements = astra_hfb_config()['elements'];
	return isset($elements[$groupe][$id]) && is_array($elements[$groupe][$id]) ? $elements[$groupe][$id] : [];
}

/**
 * Blocs valides d'une zone de widgets, dans l'ordre configuré.
 */
function astra_hfb_blocs_element(string $groupe, string $id): array {
	$blocs = astra_hfb_reglages($groupe, $id)['blocs'] ?? [];
	return array_values(array_intersect(is_array($blocs) ? $blocs : [], astra_hfb_blocs()));
}

/**
 * Bouton d'appel à l'action prêt à afficher : libellé, lien, cible, style.
 */
function astra_hfb_bouton(string $groupe, string $id): array {
	$r = astra_hfb_reglages($groupe, $id);
	$libelle = trim((string) ($r['libelle'] ?? ''));
	$lien = trim((string) ($r['lien'] ?? ''));
	return [
		'libelle' => $libelle !== '' ? $libelle : _T('astra:bouton_defaut'),
		'lien' => $lien !== '' ? $lien : url_de_base(),
		'cible' => ($r['nouvel_onglet'] ?? 'non') === 'oui' ? '_blank' : '_self',
		'contour' => ($r['style'] ?? 'plein') === 'contour',
	];
}

/**
 * Mode du menu mobile : 'dropdown' ou 'off-canvas'.
 */
function astra_hfb_menu_mobile(): string {
	return astra_hfb_config()['menu_mobile'];
}

/**
 * Icônes sociales d'un élément, prêtes à afficher : réseau, adresse, libellé, couleur, SVG.
 */
function astra_hfb_icones(string $groupe, string $id): array {
	include_once __DIR__ . '/inc/astra_icones.php';
	$reseaux = astra_hfb_reseaux();
	$svg = astra_icones_sociales();
	$icones = [];
	foreach (astra_hfb_reglages($groupe, $id)['reseaux'] ?? [] as $item) {
		$reseau = is_array($item) ? ($item['reseau'] ?? '') : '';
		if (!isset($reseaux[$reseau])) {
			continue;
		}
		$url = (string) ($item['url'] ?? '');
		if ($reseau === 'email' && $url !== '' && strpos($url, 'mailto:') !== 0) {
			$url = 'mailto:' . $url;
		}
		$icones[] = ['reseau' => $reseau, 'url' => $url, 'libelle' => $reseaux[$reseau][0], 'couleur' => $reseaux[$reseau][1], 'svg' => $svg[$reseau]];
	}
	return $icones;
}

/**
 * Valeurs par défaut de la configuration, alignées sur celles du Customizer d'Astra
 *
 * @return array<string, string>
 */
function astra_config_defaut(): array {
	return [
		// Palette globale Astra (--ast-global-color-0 … 8)
		'couleur_0' => '#046bd2',
		'couleur_1' => '#045cb4',
		'couleur_2' => '#1e293b',
		'couleur_3' => '#334155',
		'couleur_4' => '#ffffff',
		'couleur_5' => '#f0f5fa',
		'couleur_6' => '#111111',
		'couleur_7' => '#d1d5db',
		'couleur_8' => '#111111',
		'largeur_conteneur' => '1200',
		'largeur_etroite' => '750',
		'police_texte' => '',
		'police_titres' => '',
		// right-sidebar | left-sidebar | no-sidebar
		'disposition_listes' => 'right-sidebar',
		// narrow | right-sidebar | left-sidebar | no-sidebar
		'disposition_articles' => 'narrow',
		'afficher_slogan' => 'oui',
		'afficher_titre_site' => 'oui',
		'menu_accueil' => 'non',
		'articles_par_page' => '10',
		'afficher_extrait' => 'oui',
		'afficher_auteurs' => 'oui',
		'bouton_haut' => 'oui',
		'texte_pied' => '',
	];
}

/**
 * Lire une valeur de configuration du thème, avec repli sur la valeur par défaut
 *
 * Usage dans un squelette : `[(#VAL{couleur_0}|astra_conf)]`
 */
function astra_conf(string $cle): string {
	include_spip('inc/config');
	$valeur = lire_config('astra/' . $cle);
	if ($valeur === null || $valeur === '') {
		$defaut = astra_config_defaut();
		return $defaut[$cle] ?? '';
	}
	return is_array($valeur) ? implode(',', $valeur) : (string) $valeur;
}

/**
 * Disposition (barre latérale) appliquée à un type de page
 *
 * @param string $type Type de page : sommaire, rubrique, article, recherche, mot, auteur, 404…
 * @return string right-sidebar | left-sidebar | no-sidebar
 */
function astra_disposition(string $type): string {
	if ($type === '404') {
		return 'no-sidebar';
	}
	$disposition = ($type === 'article')
		? astra_conf('disposition_articles')
		: astra_conf('disposition_listes');
	if (!in_array($disposition, ['right-sidebar', 'left-sidebar', 'no-sidebar'], true)) {
		$disposition = 'no-sidebar';
	}
	return $disposition;
}

/**
 * Vrai si la barre latérale doit être produite à la position demandée
 *
 * @param string $type Type de page
 * @param string $position left-sidebar | right-sidebar
 */
function astra_barre_a(string $type, string $position): bool {
	return astra_disposition($type) === $position;
}

/**
 * Classes CSS du <body>, reproduisant celles posées par Astra sur WordPress
 *
 * @param string $type Type de page
 * @param int|string $id Identifiant de l'objet affiché (article, rubrique…)
 */
function astra_body_class(string $type, $id = 0): string {
	$id = (int) $id;
	$disposition = astra_disposition($type);
	$etroit = ($type === 'article' && astra_conf('disposition_articles') === 'narrow');

	$classes = [];
	switch ($type) {
		case 'sommaire':
			$classes = ['home', 'blog'];
			break;
		case 'rubrique':
			$classes = ['archive', 'category', 'category-' . $id];
			break;
		case 'mot':
			$classes = ['archive', 'tag', 'tag-' . $id];
			break;
		case 'auteur':
			$classes = ['archive', 'author', 'author-' . $id];
			break;
		case 'recherche':
			$classes = ['search', 'search-results'];
			break;
		case 'article':
			$classes = ['single', 'single-post', 'postid-' . $id, 'single-format-standard'];
			break;
		case '404':
			$classes = ['error404'];
			break;
		default:
			$classes = ['page', 'page-' . $type];
	}

	$classes[] = 'ast-desktop';
	if ($etroit) {
		$classes[] = 'ast-narrow-container';
	}
	$classes[] = 'ast-separate-container';
	// Astra ajoute ast-two-container sur les listes sans barre et les articles avec barre
	if (
		($type === 'article' && $disposition !== 'no-sidebar')
		|| ($type !== 'article' && $disposition === 'no-sidebar')
	) {
		$classes[] = 'ast-two-container';
	}
	$classes[] = 'ast-' . $disposition;
	$classes[] = 'astra-' . _ASTRA_VERSION_THEME;
	if ($type === 'article') {
		$classes[] = 'ast-blog-single-style-1';
		$classes[] = 'ast-single-post';
	}
	$classes[] = 'ast-inherit-site-logo-transparent';
	$classes[] = 'ast-hfb-header';
	if ($type === 'article') {
		$classes[] = 'ast-normal-title-enabled';
	}

	return implode(' ', $classes);
}

/**
 * Normaliser une couleur saisie (#rgb ou #rrggbb), avec repli
 */
function astra_couleur(string $couleur, string $defaut = '#000000'): string {
	$couleur = trim($couleur);
	if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $couleur)) {
		return strtolower($couleur);
	}
	return $defaut;
}

/**
 * Nettoyer une pile de polices CSS saisie par l'administrateur
 */
function astra_police(string $police): string {
	return trim(preg_replace('/[^\w\s,\'"-]/u', '', $police));
}

/**
 * Largeur en pixels bornée (entier, minimum imposé)
 */
function astra_largeur($largeur, int $minimum = 300): int {
	return max($minimum, min(3000, (int) $largeur));
}

/**
 * Texte de pied de page personnalisé (raccourcis SPIP), vide si non configuré
 *
 * Les marqueurs [annee] et [site] sont remplacés par l'année courante et le nom du site.
 */
function astra_texte_pied($nom_site): string {
	$texte = astra_conf('texte_pied');
	if (trim($texte) === '') {
		return '';
	}
	include_spip('inc/texte');
	$texte = str_replace(['[annee]', '[site]'], [date('Y'), textebrut($nom_site)], $texte);
	return propre($texte);
}

/**
 * Mention de copyright par défaut du pied de page
 */
function astra_copyright($nom_site): string {
	return _T('astra:copyright', ['annee' => date('Y'), 'site' => textebrut($nom_site)]);
}

/**
 * Extrait d'article à la manière de WordPress : 55 mots suivis de « […] »
 */
function astra_extrait($texte, int $mots = 55): string {
	$texte = trim(preg_replace(['/\[\d+\]/u', '/\s+/u'], ['', ' '], textebrut($texte)));
	$texte = preg_replace('/\s*(\(\.\.\.\)|\[(…|&hellip;|\.\.\.)\])\s*$/u', '', $texte);
	$liste = preg_split('/ /u', $texte, -1, PREG_SPLIT_NO_EMPTY);
	if (count($liste) > $mots) {
		return implode(' ', array_slice($liste, 0, $mots)) . ' [&hellip;]';
	}
	return $texte;
}

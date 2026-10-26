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
define('_ASTRA_VERSION_THEME', '4.13.8');

include_once __DIR__ . '/inc/astra_hfb.php';
include_once __DIR__ . '/inc/astra_hfb_saisie.php';
include_once __DIR__ . '/inc/astra_pages.php';
include_once __DIR__ . '/inc/astra_styles_saisie.php';
include_once __DIR__ . '/inc/astra_entete_transparent.php';
include_once __DIR__ . '/inc/astra_compatibilite.php';
include_once __DIR__ . '/inc/astra_responsive.php';
include_once __DIR__ . '/inc/astra_menus.php';
include_once __DIR__ . '/inc/astra_liens.php';
include_once __DIR__ . '/inc/astra_gravatar.php';

/** Réglages effectifs d'une page unique : `#ID_ARTICLE|astra_page_reglages`. */
function filtre_astra_page_reglages_dist($id_article): array {
	return astra_page_reglages((int) $id_article);
}

/** Contexte des inclusions du gabarit de page : `#ID_ARTICLE|astra_page_contexte{#ENV{accueil}}`. */
function filtre_astra_page_contexte_dist($id_article, $accueil = ''): array {
	return astra_page_contexte((int) $id_article, astra_page_reglages((int) $id_article), $accueil === 'oui');
}

/** Vrai si l'article a au moins un commentaire publié (CSS des commentaires d'Astra). */
function astra_a_commentaires($id_article): bool {
	return (bool) sql_countsel('spip_forum', ['id_objet=' . (int) $id_article, "objet='article'", "statut='publie'"]);
}

/** Arbre des pages pour la source de menu « pages » : `#VAL|astra_pages_arbre`. */
function filtre_astra_pages_arbre_dist($x = ''): array {
	return astra_pages_arbre();
}

/** Identifiant de la page d'accueil statique, 0 si l'accueil liste les articles. */
function astra_accueil_page($x = ''): int {
	return astra_conf('accueil_type') === 'page' ? (int) astra_conf('accueil_page') : 0;
}

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
		'lien' => astra_url_lien($lien !== '' ? $lien : url_de_base()),
		'cible' => ($r['nouvel_onglet'] ?? 'non') === 'oui' ? '_blank' : '_self',
		'contour' => ($r['style'] ?? 'plein') === 'contour',
	];
}

/**
 * CSS des réglages de rangées du builder (vide avec les réglages par défaut).
 */
function astra_hfb_css(): string {
	return astra_hfb_css_rangees(astra_hfb_config()['rangees']);
}

/**
 * @font-face des polices locales choisies (police du texte, des titres, du bouton, des menus, du
 * bouton de l'en-tête) : vide pour des piles système.
 */
function astra_polices_inline(): string {
	include_spip('inc/config');
	$styles = lire_config('astra/styles');
	$styles = is_array($styles) ? $styles : [];
	$piles = [astra_conf('police_texte'), astra_conf('police_titres')];
	foreach (['font-family-button', 'header-menu1-font-family', 'header-menu2-font-family', 'header-button1-font-family'] as $option) {
		$piles[] = $styles[$option] ?? '';
	}
	return astra_polices_css($piles, function (string $chemin) {
		return url_absolue(timestamp(find_in_path($chemin)));
	});
}

/** Drapeaux de compatibilité (méta astra/compatibilite), normalisés. */
function astra_compatibilite(): array {
	include_spip('inc/config');
	return astra_compatibilite_normaliser(lire_config('astra/compatibilite'));
}

/** Feuille des règles de submenu-below-header : `#VAL|astra_compatibilite_sous_menu|find_in_path`. */
function astra_compatibilite_sous_menu($x = ''): string {
	return astra_compatibilite_feuille_sous_menu(astra_compatibilite());
}

/**
 * CSS des styles du Customizer (méta astra/styles) et des drapeaux de compatibilité qui changent
 * le CSS (astra/compatibilite) : vide sans réglage.
 */
function astra_styles_inline(): string {
	include_spip('inc/config');
	$styles = lire_config('astra/styles');
	$hfb = lire_config('astra/hfb');
	return astra_styles_css(is_array($styles) ? $styles : [], is_array($hfb) ? $hfb : [], astra_compatibilite());
}

/**
 * En-tête transparent de la page (astra_entete_transparent_actif()) : $type et $id comme pour
 * astra_body_class() ; une page unique suit son réglage, même en accueil statique ; l'accueil qui
 * liste les derniers articles a son exclusion propre.
 */
function astra_entete_transparent(string $type, $id = 0, $accueil = ''): bool {
	include_spip('inc/config');
	$styles = lire_config('astra/styles');
	$styles = is_array($styles) ? $styles : [];
	if ($type === 'page') {
		return astra_entete_transparent_actif($styles, 'page', astra_page_reglages((int) $id)['transparent']);
	}
	return astra_entete_transparent_actif($styles, $type === 'sommaire' ? 'accueil_articles' : $type);
}

/**
 * CSS de l'en-tête transparent, vide si la page ne l'a pas :
 * `#ENV{contexte}|astra_entete_transparent_inline{#ENV{id},#ENV{accueil}}`.
 */
function astra_entete_transparent_inline($type, $id = 0, $accueil = ''): string {
	if (!astra_entete_transparent((string) $type, $id, $accueil)) {
		return '';
	}
	$styles = lire_config('astra/styles');
	$hfb = lire_config('astra/hfb');
	return astra_entete_transparent_css(is_array($styles) ? $styles : [], is_array($hfb) ? $hfb : []);
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
		$url = astra_lien_social($reseau, (string) ($item['url'] ?? ''));
		$icones[] = ['reseau' => $reseau, 'url' => astra_url_lien($url), 'libelle' => $reseaux[$reseau][0], 'couleur' => $reseaux[$reseau][1], 'svg' => $svg[$reseau]];
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
		'menu_accueil' => 'non',
		'articles_par_page' => '10',
		'afficher_extrait' => 'oui',
		'afficher_auteurs' => 'oui',
		'bouton_haut' => 'oui',
		'texte_pied' => '',
		// articles | page (page unique choisie dans accueil_page)
		'accueil_type' => 'articles',
		'accueil_page' => '',
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
 * @param string $type Type de page ; `page` : page unique
 * @param int|string $id Identifiant de l'objet affiché (article, rubrique…)
 * @param string $accueil `oui` pour une page unique affichée en accueil statique
 */
function astra_body_class(string $type, $id = 0, $accueil = ''): string {
	$id = (int) $id;
	if ($type === 'page') {
		return astra_page_classes_body($id, astra_page_reglages($id), $accueil === 'oui', astra_page_a_sous_pages($id), astra_entete_transparent('page', $id, $accueil));
	}
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
	if (astra_entete_transparent($type, $id)) {
		$classes[] = 'ast-theme-transparent-header';
	}
	$classes[] = 'ast-hfb-header';
	if ($type === 'article') {
		$classes[] = 'ast-normal-title-enabled';
	}

	return implode(' ', $classes);
}

/**
 * Valeur ordinateur normalisée d'une option des styles, sinon son défaut d'Astra.
 * `#VAL{mobile-header-toggle-btn-style}|astra_style_option`
 */
function astra_style_option(string $option) {
	include_spip('inc/config');
	$v = astra_styles_normaliser([$option => lire_config('astra/styles/' . $option)])[$option] ?? '';
	$v = astra_styles_ordi($v);
	return $v === '' || $v === null ? astra_styles_registre()[$option]['defaut'] : $v;
}

/**
 * Titre ou slogan imprimé : visible sur au moins un appareil, comme astra_logo() d'Astra.
 * `#VAL{display-site-title-responsive}|astra_identite_visible`
 */
function astra_identite_visible(string $option): bool {
	include_spip('inc/config');
	$defaut = astra_styles_registre()[$option]['defaut'];
	$v = astra_styles_normaliser([$option => lire_config('astra/styles/' . $option)])[$option] ?? [];
	foreach (ASTRA_STYLES_APPAREILS as $appareil) {
		if (array_key_exists($appareil, $v) && $v[$appareil] !== '' ? $v[$appareil] : $defaut[$appareil]) {
			return true;
		}
	}
	return false;
}

/**
 * Logo du site réduit : à la largeur ordinateur de ast-header-responsive-logo-width si elle est
 * réglée, sinon à 60 px de haut.
 */
function astra_logo_reduire($img): string {
	include_spip('inc/config');
	include_spip('inc/filtres_images_mini');
	$styles = astra_styles_normaliser(['ast-header-responsive-logo-width' => lire_config('astra/styles/ast-header-responsive-logo-width')]);
	$largeurs = $styles['ast-header-responsive-logo-width'] ?? [];
	$largeur = (int) ($largeurs['desktop'] ?? 0);
	$bureau = $largeur > 0 ? image_reduire($img, $largeur, 0) : image_reduire($img, 0, 60);
	$affichee = (int) extraire_attribut($bureau, 'width');
	$necessaire = astra_identite_largeur_source($largeurs, $affichee);
	if ($necessaire <= $affichee) {
		return $bureau;
	}
	// Une tablette peut demander plus de pixels. Le filtre SPIP ne dépasse pas la
	// définition de l'original ; le cadre et les attributs du bureau sont conservés.
	$ressource = image_reduire($img, $necessaire, 0);
	$source = extraire_attribut($ressource, 'src');
	return $source ? inserer_attribut($bureau, 'src', $source) : $bureau;
}

/**
 * Normaliser une couleur saisie (#rgb, #rrggbb, rgb() ou rgba() comme la palette d'Astra), avec repli
 */
function astra_couleur(string $couleur, string $defaut = '#000000'): string {
	$couleur = trim($couleur);
	if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $couleur)) {
		return strtolower($couleur);
	}
	if (strpos($couleur, 'rgb') === 0 && astra_styles_couleur_valide($couleur)) {
		return $couleur;
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

/**
 * Libellé d'un élément du builder dans le formulaire de configuration.
 */
function astra_hfb_libelle_element(string $id): string {
	return _T('astra:hfb_element_' . str_replace('-', '_', $id));
}

/**
 * Libellé d'une zone du builder : « Rangée principale, droite », « Rangée basse, colonne 2 »…
 */
function astra_hfb_libelle_zone(string $zone, string $builder = ''): string {
	if ($zone === 'popup_content') {
		return _T('astra:hfb_zone_popup');
	}
	[$rangee, $position] = explode('_', $zone, 2);
	$position = ctype_digit($position)
		? _T('astra:hfb_zone_colonne', ['n' => $position])
		: _T('astra:hfb_zone_' . $position);
	return _T('astra:hfb_rangee_' . $rangee) . ', ' . $position;
}

/**
 * Sources proposées pour un menu : valeur du champ source_id => libellé.
 */
function astra_hfb_sources_menu(): array {
	include_spip('base/abstract_sql');
	include_spip('inc/filtres');
	$sources = ['racine' => _T('astra:hfb_source_racine')];
	foreach (sql_allfetsel('id_menu, titre', 'spip_menus', 'id_menus_entree=0', '', 'titre') as $menu) {
		$sources['menus:' . $menu['id_menu']] = _T('astra:hfb_source_menu_libre', ['titre' => supprimer_numero(typo($menu['titre']))]);
	}
	foreach (sql_allfetsel('id_rubrique, titre', 'spip_rubriques', '', '', 'titre') as $r) {
		$sources['rubrique:' . $r['id_rubrique']] = _T('astra:hfb_source_rubrique', ['titre' => supprimer_numero(typo($r['titre']))]);
	}
	if (test_plugin_actif('mots')) {
		foreach (sql_allfetsel('id_groupe, titre', 'spip_groupes_mots', '', '', 'titre') as $g) {
			$sources['groupe_mots:' . $g['id_groupe']] = _T('astra:hfb_source_groupe_mots', ['titre' => supprimer_numero(typo($g['titre']))]);
		}
	}
	if (test_plugin_actif('pages')) {
		$sources['pages'] = _T('astra:hfb_source_pages');
	}
	return $sources;
}


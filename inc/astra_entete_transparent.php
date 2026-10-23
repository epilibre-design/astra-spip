<?php
/**
 * Astra pour SPIP — en-tête transparent (inc/addons/transparent-header/ d'Astra 4.11.0).
 *
 * Fonctions pures : règle d'activation (Astra_Ext_Transparent_Header_Markup::is_transparent_header())
 * et CSS du module (classes/dynamic-css/dynamic.css.php, et la partie « Transparent Header -
 * Component header specific CSS compatibility » d'inc/class-astra-dynamic-css.php). Le CSS de
 * header-sections-dynamic.css.php ne concerne que l'en-tête sans builder : il n'est pas repris.
 *
 * @license GPL-3.0-or-later
 */

require_once __DIR__ . '/astra_styles.php';
require_once __DIR__ . '/astra_hfb.php';

/** Points de rupture d'Astra (astra_get_tablet_breakpoint(), astra_get_mobile_breakpoint()). */
const ASTRA_TRANSPARENT_TABLETTE = 921;
const ASTRA_TRANSPARENT_MOBILE = 544;

/**
 * Vrai si la page a l'en-tête transparent. $contexte : page (page unique, y compris en accueil
 * statique), article, accueil_articles (accueil qui liste les derniers articles), rubrique, mot,
 * auteur, recherche, 404. $reglage_page : réglage de la page unique (defaut, active, desactive).
 */
function astra_entete_transparent_actif(array $s, string $contexte, string $reglage_page = 'defaut'): bool {
	$s = astra_styles_normaliser($s);
	$vrai = function (string $option, $defaut = 0) use ($s): bool {
		return (string) ($s[$option] ?? $defaut) === '1' || ($s[$option] ?? null) === true;
	};
	// Anciennes options : 404, recherche et archives retombent sur transparent-header-disable-archive.
	$exclue = function (string $option) use ($s, $vrai): bool {
		return isset($s[$option]) && $s[$option] !== '' ? $vrai($option) : $vrai('transparent-header-disable-archive', 1);
	};
	$actif = $vrai('transparent-header-enable');
	if ($actif) {
		switch ($contexte) {
			case '404':
				$actif = !$exclue('transparent-header-disable-404-page');
				break;
			case 'recherche':
				$actif = !$exclue('transparent-header-disable-search-page');
				break;
			case 'rubrique':
			case 'mot':
			case 'auteur':
				$actif = !$exclue('transparent-header-disable-archive-pages');
				break;
			case 'accueil_articles':
				$actif = !$vrai('transparent-header-disable-latest-posts-index', 1);
				break;
			case 'page':
				$actif = !$vrai('transparent-header-disable-page');
				break;
			case 'article':
				$actif = !$vrai('transparent-header-disable-posts');
				break;
		}
	}
	if ($reglage_page === 'active') {
		return true;
	}
	if ($reglage_page === 'desactive') {
		return false;
	}
	return $actif;
}

/**
 * Dernière rangée remplie de l'en-tête, comme astra_get_transparent_header_last_active_row() :
 * sélecteur de sa barre, vide si l'en-tête de cet appareil n'a aucune rangée remplie.
 */
function astra_entete_transparent_derniere_rangee(array $hfb, string $appareil): string {
	$n = astra_hfb_normaliser($hfb);
	$rangees = $appareil === 'desktop' ? $n['entete_ordinateur'] : $n['entete_mobile'];
	$prefixe = $appareil === 'desktop' ? '.ast-theme-transparent-header #ast-desktop-header > ' : '.ast-theme-transparent-header.ast-header-break-point #ast-mobile-header > ';
	$selecteur = '';
	foreach (['above', 'primary', 'below'] as $rangee) {
		$remplie = false;
		foreach ($rangees[$rangee] ?? [] as $ids) {
			$remplie = $remplie || !empty($ids);
		}
		if ($remplie) {
			$selecteur = $rangee === 'primary' ? $prefixe . '.ast-main-header-wrap > .main-header-bar' : $prefixe . '.ast-' . $rangee . '-header-wrap > .ast-' . $rangee . '-header';
		}
	}
	return $selecteur;
}

/**
 * CSS de l'en-tête transparent, pour une page où il est actif : règles du module d'Astra dans son
 * ordre et ses media queries, puis la neutralisation de la marge de #primary qu'Astra n'émet pas
 * sur une page à en-tête transparent (css/astra-dynamic.css la contient).
 */
function astra_entete_transparent_css(array $styles, array $hfb): string {
	$s = astra_styles_normaliser($styles);
	$t = ASTRA_TRANSPARENT_TABLETTE;
	$m = ASTRA_TRANSPARENT_MOBILE;
	$min = "(min-width:{$t}px)";
	$max_t = "(max-width:{$t}px)";
	$max_m = "(max-width:{$m}px)";
	$v = function (string $option, string $appareil) use ($s): string {
		$x = $s[$option][$appareil] ?? '';
		return is_string($x) ? $x : '';
	};
	// Fonds : la tablette reprend l'ordinateur, le mobile la tablette (astra_get_prop() avec repli).
	$fond = function (string $option) use ($v): array {
		$o = $v($option, 'desktop');
		$t = $v($option, 'tablet') !== '' ? $v($option, 'tablet') : $o;
		$m = $v($option, 'mobile') !== '' ? $v($option, 'mobile') : ($t !== '' ? $t : $o);
		return ['desktop' => $o, 'tablet' => $t, 'mobile' => $m];
	};
	$fonds = ['' => $fond('transparent-header-bg-color-responsive'), 'hba' => $fond('hba-transparent-header-bg-color-responsive'), 'hbb' => $fond('hbb-transparent-header-bg-color-responsive')];
	$appareils = $s['transparent-header-on-devices'] ?? 'both';
	$regles = [];

	// Largeur du logo propre à l'en-tête transparent : défauts d'Astra (logo propre hors périmètre).
	foreach (['' => 150, $max_t => 120, '(max-width:543px)' => 100] as $media => $largeur) {
		$regles[] = [$media, '.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo .astra-logo-svg', ['width' => $largeur . 'px']];
		$regles[] = [$media, '.ast-theme-transparent-header #masthead .site-logo-img .transparent-custom-logo img', [' max-width' => $largeur . 'px', ' width' => $largeur . 'px']];
	}

	$base = [
		['.ast-theme-transparent-header #masthead', ['position' => 'absolute', 'left' => '0', 'right' => '0']],
		['.ast-theme-transparent-header .main-header-bar, .ast-theme-transparent-header.ast-header-break-point .main-header-bar', ['background' => 'none']],
		['body.elementor-editor-active.ast-theme-transparent-header #masthead, .fl-builder-edit .ast-theme-transparent-header #masthead, body.vc_editor.ast-theme-transparent-header #masthead, body.brz-ed.ast-theme-transparent-header #masthead', ['z-index' => '0']],
		['.ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .custom-mobile-logo-link', ['display' => 'none']],
		['.ast-header-break-point.ast-replace-site-logo-transparent.ast-theme-transparent-header .transparent-custom-logo', ['display' => 'inline-block']],
		['.ast-theme-transparent-header .ast-above-header, .ast-theme-transparent-header .ast-above-header.ast-above-header-bar', ['background-image' => 'none', 'background-color' => 'transparent']],
		['.ast-theme-transparent-header .ast-below-header, .ast-theme-transparent-header .ast-below-header.ast-below-header-bar', ['background-image' => 'none', 'background-color' => 'transparent']],
	];
	$couleurs = function (string $appareil) use ($v, $fonds): array {
		$titre = '.ast-theme-transparent-header .site-title a, .ast-theme-transparent-header .site-title a:focus, .ast-theme-transparent-header .site-title a:hover, .ast-theme-transparent-header .site-title a:visited';
		if ($appareil !== 'desktop') {
			$titre .= ', .ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a, .ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:hover, .ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:focus, .ast-theme-transparent-header .ast-builder-layout-element .ast-site-identity .site-title a:visited';
		}
		$bp = $appareil === 'desktop' ? '' : '.ast-header-break-point';
		$rangees = $appareil === 'desktop'
			? ['.ast-theme-transparent-header .ast-above-header, .ast-theme-transparent-header .ast-above-header.ast-above-header-bar', '.ast-theme-transparent-header .ast-below-header, .ast-theme-transparent-header .ast-below-header.ast-below-header-bar']
			: ['.ast-theme-transparent-header.ast-header-break-point .ast-above-header, .ast-theme-transparent-header.ast-header-break-point .ast-above-header-bar .main-header-menu', '.ast-theme-transparent-header.ast-header-break-point .ast-below-header, .ast-theme-transparent-header.ast-header-break-point .ast-below-header-bar .main-header-menu'];
		$menu_fond = $appareil === 'desktop'
			? '.ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .sub-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header.ast-header-break-point .ast-builder-menu .main-header-bar-wrap .main-header-menu, '
			: '.ast-theme-transparent-header.ast-header-break-point .ast-builder-menu .main-header-menu, .ast-theme-transparent-header.ast-header-break-point .ast-builder-menu  .main-header-menu .sub-menu, .ast-theme-transparent-header.ast-header-break-point .ast-builder-menu  .main-header-menu, .ast-theme-transparent-header.ast-header-break-point .ast-builder-menu .main-header-bar-wrap .main-header-menu, ';
		$menu_fond .= '.ast-flyout-menu-enable.ast-header-break-point.ast-theme-transparent-header .main-header-bar-navigation .site-navigation, .ast-fullscreen-menu-enable.ast-header-break-point.ast-theme-transparent-header .main-header-bar-navigation .site-navigation, .ast-flyout-above-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation-wrap .ast-above-header-navigation, .ast-flyout-below-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-below-header-navigation-wrap .ast-below-header-actual-nav, .ast-fullscreen-above-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation-wrap, .ast-fullscreen-below-menu-enable.ast-header-break-point.ast-theme-transparent-header .ast-below-header-navigation-wrap, .ast-theme-transparent-header .main-header-menu .menu-link';
		$sous_menu_fond = $appareil === 'desktop'
			? '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu'
			: '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu, .ast-theme-transparent-header.astra-hfb-header .ast-builder-menu [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.astra-hfb-header .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .ast-builder-menu .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header.astra-hfb-header .ast-builder-menu [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.astra-hfb-header .ast-builder-menu .main-header-bar-navigation [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu';
		$sous_menu = '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link';
		$sous_menu_survol = [
			'desktop' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link:hover,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .menu-item,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link',
			'tablet' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link:hover,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .menu-item,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link',
			'mobile' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link:hover,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .menu-item,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .menu-link,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.current-menu-item > .ast-menu-toggle,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item:hover > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-item.focus > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-item.focus > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-item:hover > .menu-link, .ast-header-break-point.ast-flyout-menu-enable.ast-header-break-point .main-header-bar-navigation .main-header-menu .menu-item .sub-menu .menu-link',
		][$appareil];
		$menu = [
			'desktop' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item > .menu-link, .ast-theme-transparent-header .ast-masthead-custom-menu-items, .ast-theme-transparent-header .ast-masthead-custom-menu-items a, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-above-header-navigation a, .ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation a, .ast-header-break-point.ast-theme-transparent-header .ast-above-header-navigation > ul.ast-above-header-menu > .menu-item-has-children:not(.current-menu-item) > .ast-menu-toggle, .ast-theme-transparent-header .ast-below-header-menu, .ast-theme-transparent-header .ast-below-header-menu a, .ast-header-break-point.ast-theme-transparent-header .ast-below-header-menu a, .ast-header-break-point.ast-theme-transparent-header .ast-below-header-menu, .ast-theme-transparent-header .main-header-menu .menu-link',
			'tablet' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .menu-item > .menu-link, .ast-theme-transparent-header .ast-masthead-custom-menu-items, .ast-theme-transparent-header .ast-masthead-custom-menu-items a,.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-link',
			'mobile' => '.ast-theme-transparent-header .ast-builder-menu .main-header-menu, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-link, .ast-theme-transparent-header .ast-masthead-custom-menu-items, .ast-theme-transparent-header .ast-masthead-custom-menu-items a, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-link',
		][$appareil];
		$menu_survol = '.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item:hover > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .ast-masthead-custom-menu-items a:hover, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .focus > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .focus > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-item > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-ancestor > .menu-link, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-item > .ast-menu-toggle, .ast-theme-transparent-header .ast-builder-menu .main-header-menu .current-menu-ancestor > .ast-menu-toggle, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-item > .menu-link, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-ancestor > .menu-link, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-item > .ast-menu-toggle, .ast-theme-transparent-header [CLASS*="ast-builder-menu-"] .main-header-menu .current-menu-ancestor > .ast-menu-toggle, .ast-theme-transparent-header .main-header-menu .menu-item:hover > .menu-link, .ast-theme-transparent-header .main-header-menu .current-menu-item > .menu-link, .ast-theme-transparent-header .main-header-menu .current-menu-ancestor > .menu-link';
		$texte = '.ast-theme-transparent-header div.ast-masthead-custom-menu-items, .ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget, .ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget-title, .ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element';
		$lien = '.ast-theme-transparent-header div.ast-masthead-custom-menu-items a, .ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget a';
		$lien_survol = '.ast-theme-transparent-header div.ast-masthead-custom-menu-items a:hover, .ast-theme-transparent-header div.ast-masthead-custom-menu-items .widget a:hover';
		if ($appareil === 'desktop') {
			$lien .= ', .ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element a';
			$lien_survol .= ', .ast-theme-transparent-header .site-header-section [CLASS*="ast-header-html-"] .ast-builder-html-element a:hover';
		}
		$titre_couleur = $v('transparent-header-color-site-title-responsive', $appareil);
		$r = [
			['.ast-theme-transparent-header .main-header-bar, .ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-menu, .ast-theme-transparent-header.ast-header-break-point .main-header-bar-wrap .main-header-bar, .ast-theme-transparent-header.ast-header-break-point .ast-mobile-header-wrap .main-header-bar', ['background-color' => $fonds[''][$appareil]]],
			['.ast-theme-transparent-header .main-header-bar .ast-search-menu-icon form', ['background-color' => $fonds[''][$appareil]]],
			[$rangees[0], ['background-color' => $fonds['hba'][$appareil]]],
			[$rangees[1], ['background-color' => $fonds['hbb'][$appareil]]],
			[$titre, ['color' => $titre_couleur]],
			['.ast-theme-transparent-header .site-header .site-title a:hover', ['color' => $v('transparent-header-color-h-site-title-responsive', $appareil)]],
			['.ast-theme-transparent-header .site-header .site-description', ['color' => $titre_couleur]],
			[$menu_fond, ['background-color' => $v('transparent-menu-bg-color-responsive', $appareil)]],
			[$sous_menu_fond, ['background-color' => $v('transparent-submenu-bg-color-responsive', $appareil)]],
			[$sous_menu, ['color' => $v('transparent-submenu-color-responsive', $appareil)]],
			[$sous_menu_survol, ['color' => $v('transparent-submenu-h-color-responsive', $appareil)]],
			[$menu, ['color' => $v('transparent-menu-color-responsive', $appareil)]],
			[$menu_survol, ['color' => $v('transparent-menu-h-color-responsive', $appareil)]],
			[$texte, ['color' => $v('transparent-content-section-text-color-responsive', $appareil)]],
			[$lien, ['color' => $v('transparent-content-section-link-color-responsive', $appareil)]],
			[$lien_survol, ['color' => $v('transparent-content-section-link-h-color-responsive', $appareil)]],
		];
		// astra_has_submenu_transperent_styling() : vrai sur une installation récente.
		if ($appareil === 'desktop') {
			$r[] = ['.ast-theme-transparent-header .ast-builder-menu .main-header-menu .menu-item .sub-menu .menu-link, .ast-theme-transparent-header .main-header-menu .menu-item .sub-menu .menu-link', ['background-color' => 'transparent']];
		}
		return $r;
	};
	$ajouter = function (string $media, array $bloc) use (&$regles) {
		foreach ($bloc as [$selecteur, $declarations]) {
			$regles[] = [$media, $selecteur, $declarations];
		}
	};

	if ($appareils === 'both' || $appareils === 'desktop') {
		$ajouter($min, $base);
		$ajouter($appareils === 'both' ? '' : '(min-width:' . ($t + 1) . 'px)', $couleurs('desktop'));
	}
	if ($appareils === 'mobile') {
		$regles[] = [$min, '.transparent-custom-logo', ['display' => 'none']];
		$regles[] = [$max_t, '.transparent-custom-logo', ['display' => 'block']];
		$regles[] = [$max_t, '.ast-transparent-desktop-logo', ['display' => 'none']];
	}
	if ($appareils === 'desktop') {
		$regles[] = [$max_t, '.transparent-custom-logo', ['display' => 'none']];
		$regles[] = [$min, '.ast-transparent-mobile-logo', ['display' => 'none']];
		$regles[] = ['(max-width:' . ($t - 1) . 'px)', '.ast-transparent-mobile-logo', ['display' => 'block']];
	}
	if ($appareils === 'both' || $appareils === 'mobile') {
		$ajouter($max_t, $base);
		$ajouter($max_t, $couleurs('tablet'));
		$ajouter($max_m, $couleurs('mobile'));
	}

	// Séparateur sous la dernière rangée remplie de l'en-tête.
	// Défaut : 0 sur une installation récente, vide si transparent-header-default-border est faux
	// (astra_get_transparent_header_default_value(), inc/core/builder/class-astra-builder-options.php).
	// Faux : false du site.json, ou « 0 » enregistré par le formulaire.
	$sans_bordure = in_array($s['transparent-header-default-border'] ?? true, [false, 0, '0'], true);
	$sep = $s['transparent-header-main-sep'] ?? ($sans_bordure ? '' : 0);
	$sep_css = $sep !== '' && $sep !== null
		? ['border-bottom-width' => $sep . 'px', 'border-bottom-style' => 'solid', 'border-bottom-color' => $s['transparent-header-main-sep-color'] ?? '']
		: ['border-bottom-style' => 'none'];
	$ordi = astra_entete_transparent_derniere_rangee($hfb, 'desktop');
	$mobile = astra_entete_transparent_derniere_rangee($hfb, 'mobile');
	if ($appareils === 'both') {
		$regles[] = ['', $ordi !== '' && $mobile !== '' ? $ordi . ', ' . $mobile : $ordi . $mobile, $sep_css];
	} elseif ($appareils === 'mobile') {
		$regles[] = [$max_t, $mobile, $sep_css];
	} else {
		$regles[] = [$min, $ordi, $sep_css];
	}

	// Bouton du menu mobile (inc/class-astra-dynamic-css.php), couleurs propres non reprises.
	if (astra_styles_element_place('mobile-trigger', $hfb, 'entete')) {
		$style = $s['mobile-header-toggle-btn-style'] ?? 'minimal';
		$bouton = '.ast-theme-transparent-header [data-section="section-header-mobile-trigger"] .ast-button-wrap';
		if ($style === 'fill') {
			$regles[] = ['', "$bouton .ast-mobile-menu-trigger-fill, $bouton .ast-mobile-menu-trigger-minimal", ['border' => 'none']];
		} elseif ($style === 'outline') {
			$regles[] = ['', "$bouton .ast-mobile-menu-trigger-outline", ['background' => 'transparent']];
		} else {
			$regles[] = ['', "$bouton .ast-mobile-menu-trigger-minimal", ['background' => 'transparent']];
		}
	}

	// Neutralisation : css/astra-dynamic.css donne 60 px à #primary au-delà de 1200 px, règle
	// qu'Astra n'émet pas quand l'en-tête est transparent ; la marge reste celle de la règle de base.
	$regles[] = ['(min-width:1200px)', '.ast-theme-transparent-header.ast-plain-container.ast-no-sidebar #primary', ['margin-top' => '0', 'margin-bottom' => '0']];

	return astra_styles_ecrire($regles);
}

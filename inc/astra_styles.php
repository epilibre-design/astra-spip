<?php
/**
 * Astra pour SPIP — styles du Customizer d'Astra 4.11.0 : registre des options, normalisation
 * commune, écriture du CSS (docs/superpowers/specs/2026-10-23-styles-customizer-design.md).
 *
 * Les options portent les noms d'Astra ; leurs structures (valeur par appareil, unités, côtés,
 * fonds, extras de police) sont conservées. Seule la valeur `desktop` est exploitée.
 *
 * @license GPL-3.0-or-later
 */

require_once __DIR__ . '/astra_polices.php';
require_once __DIR__ . '/astra_compatibilite.php';

/** Taille du texte d'Astra SPIP (px) : base de la conversion px → rem d'astra_get_font_css_value(). */
const ASTRA_STYLES_TAILLE_TEXTE = 16;

/** Appareils des options responsives d'Astra. */
const ASTRA_STYLES_APPAREILS = ['desktop', 'tablet', 'mobile'];

/**
 * Registre des options : groupe, fichier source d'Astra 4.11.0, type, structure responsive,
 * unités ou valeurs acceptées, défaut d'Astra (documentation et tests : jamais écrit).
 */
function astra_styles_registre(): array {
	static $r = null;
	if ($r !== null) {
		return $r;
	}
	$r = [];
	$ajouter = function (string $groupe, string $source, array $options) use (&$r) {
		foreach ($options as $option => $d) {
			$r[$option] = $d + ['groupe' => $groupe, 'source' => $source, 'responsive' => false, 'defaut' => ''];
		}
	};
	$couleur = ['type' => 'couleur'];
	$couleur_r = ['type' => 'couleur', 'responsive' => true];
	$taille_r = ['type' => 'taille', 'responsive' => true, 'unites' => ['px', 'em', 'rem', 'vw']];
	$cotes_r = ['type' => 'quatre-cotes', 'responsive' => true, 'unites' => ['px', 'em', '%']];
	$cotes = ['type' => 'quatre-cotes'];
	$nombre_r = ['type' => 'nombre', 'responsive' => true];

	// Graisses des titres.
	$titres = ['headings-font-weight' => ['type' => 'graisse', 'defaut' => '600']];
	for ($n = 1; $n <= 6; $n++) {
		$titres["font-weight-h$n"] = ['type' => 'graisse', 'defaut' => 'inherit'];
	}
	$ajouter('titres', 'inc/class-astra-dynamic-css.php', $titres);

	$menu = 'inc/builder/type/header/menu/dynamic-css/dynamic.css.php';
	foreach ([1, 2] as $n) {
		$ajouter('menu', $menu, [
			"header-menu$n-color-responsive" => $couleur_r,
			"header-menu$n-h-color-responsive" => $couleur_r,
			"header-menu$n-a-color-responsive" => $couleur_r,
			"header-menu$n-h-bg-color-responsive" => $couleur_r,
			"header-menu$n-a-bg-color-responsive" => $couleur_r,
			"header-menu$n-bg-obj-responsive" => ['type' => 'fond', 'responsive' => true],
			"header-menu$n-font-size" => $taille_r,
			"header-menu$n-font-weight" => ['type' => 'graisse', 'defaut' => 'inherit'],
			"header-menu$n-font-family" => ['type' => 'police', 'defaut' => 'inherit'],
			"header-menu$n-font-extras" => ['type' => 'extras-police'],
			"header-menu$n-submenu-b-color" => $couleur,
			"header-menu$n-submenu-border" => $cotes + ['defaut' => ['top' => 2, 'bottom' => 0, 'left' => 0, 'right' => 0]],
		]);
	}
	$ajouter('bouton', 'inc/class-astra-dynamic-css.php', [
		'button-color' => $couleur,
		'button-h-color' => $couleur,
		'button-bg-color' => $couleur,
		'button-bg-h-color' => $couleur,
		'button-radius-fields' => $cotes_r,
		'theme-button-padding' => $cotes_r + ['defaut' => ['desktop' => ['top' => 15, 'right' => 30, 'bottom' => 15, 'left' => 30]]],
		'theme-button-border-group-border-size' => $cotes,
		'font-family-button' => ['type' => 'police', 'defaut' => 'inherit'],
		'font-weight-button' => ['type' => 'graisse', 'defaut' => '500'],
		'font-size-button' => $taille_r + ['defaut' => ['desktop' => 16, 'desktop-unit' => 'px']],
		'font-extras-button' => ['type' => 'extras-police', 'defaut' => ['line-height' => 1, 'line-height-unit' => 'em']],
	]);
	$ajouter('bouton-entete', 'inc/builder/type/base/dynamic-css/button/class-astra-button-component-dynamic-css.php', [
		'header-button1-text-color' => $couleur_r,
		'header-button1-text-h-color' => $couleur_r,
		'header-button1-back-color' => $couleur_r,
		'header-button1-back-h-color' => $couleur_r,
		'header-button1-border-color' => $couleur_r,
		'header-button1-border-h-color' => $couleur_r,
		'header-button1-border-size' => $cotes,
		'header-button1-border-radius-fields' => $cotes_r + ['defaut' => ['desktop' => ['top' => 40, 'right' => 40, 'bottom' => 40, 'left' => 40]]],
		'header-button1-font-size' => $taille_r,
		'header-button1-font-family' => ['type' => 'police', 'defaut' => 'inherit'],
		'header-button1-font-weight' => ['type' => 'graisse', 'defaut' => 'inherit'],
		'header-button1-font-extras' => ['type' => 'extras-police'],
	]);
	$ajouter('bouton-entete', 'inc/class-astra-extended-base-dynamic-css.php', [
		'section-hb-button-1-padding' => $cotes_r + ['defaut' => ['desktop' => ['top' => 15, 'right' => 30, 'bottom' => 15, 'left' => 30]]],
	]);
	$ajouter('identite', 'inc/class-astra-dynamic-css.php', [
		'font-size-site-title' => $taille_r + ['defaut' => ['desktop' => 26, 'desktop-unit' => 'px']],
		'font-size-site-tagline' => $taille_r + ['defaut' => ['desktop' => 15, 'desktop-unit' => 'px']],
		'header-color-site-title' => $couleur + ['defaut' => 'var(--ast-global-color-2)'],
		'header-color-h-site-title' => $couleur,
		'header-color-site-tagline' => $couleur,
		'display-site-title-responsive' => ['type' => 'booleen', 'responsive' => true, 'defaut' => ['desktop' => 1, 'tablet' => 1, 'mobile' => 1]],
		'display-site-tagline-responsive' => ['type' => 'booleen', 'responsive' => true, 'defaut' => ['desktop' => 0, 'tablet' => 0, 'mobile' => 0]],
		'ast-header-responsive-logo-width' => $nombre_r,
	]);
	$widgets = 'inc/builder/type/base/dynamic-css/widget/class-astra-widget-component-dynamic-css.php';
	for ($n = 1; $n <= 4; $n++) {
		$ajouter('pied', $widgets, [
			"footer-widget-$n-title-color" => $couleur_r,
			"footer-widget-$n-color" => $couleur_r,
			"footer-widget-$n-link-color" => $couleur_r,
			"footer-widget-$n-link-h-color" => $couleur_r,
			"footer-widget-$n-font-size" => $taille_r,
			"footer-widget-$n-content-font-size" => $taille_r,
		]);
	}
	$ajouter('pied', 'inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php', [
		'footer-copyright-color' => $couleur + ['defaut' => 'var(--ast-global-color-3)'],
		'font-size-section-footer-copyright' => $taille_r + ['defaut' => ['desktop' => 16, 'desktop-unit' => 'px']],
	]);
	$sociaux = 'inc/builder/type/base/dynamic-css/social/class-astra-social-component-dynamic-css.php';
	foreach (['header', 'footer'] as $b) {
		$ajouter('sociaux', $sociaux, [
			"$b-social-1-color" => $couleur_r,
			"$b-social-1-h-color" => $couleur_r,
			"$b-social-1-size" => $nombre_r + ['defaut' => ['desktop' => 18]],
			"$b-social-1-space" => $nombre_r,
			"$b-social-1-color-type" => ['type' => 'enum', 'valeurs' => ['custom', 'official'], 'defaut' => 'custom'],
		]);
	}
	// Alignements du pied : les trois appareils s'appliquent (type alignement), contrairement aux
	// autres styles.
	$alignement = ['type' => 'alignement', 'responsive' => true, 'defaut' => ['desktop' => 'center', 'tablet' => 'center', 'mobile' => 'center']];
	$ajouter('alignements', 'inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php', ['footer-copyright-alignment' => $alignement]);
	$ajouter('alignements', $sociaux, ['footer-social-1-alignment' => $alignement]);
	// Marges des rangées du builder, de l'en-tête entier et du pied entier : les trois appareils
	// s'appliquent (appareils), les marges extérieures acceptent les nombres négatifs (signe).
	// Défaut : Astra_Builder_Helper::$default_responsive_spacing (côtés vides, unités px).
	$vides = ['top' => '', 'right' => '', 'bottom' => '', 'left' => ''];
	$marge = $cotes_r + ['appareils' => true, 'defaut' => ['desktop' => $vides, 'tablet' => $vides, 'mobile' => $vides, 'desktop-unit' => 'px', 'tablet-unit' => 'px', 'mobile-unit' => 'px']];
	$marges = [];
	foreach (['above-header', 'primary-header', 'below-header', 'above-footer', 'primary-footer', 'below-footer'] as $rangee) {
		$marges["section-$rangee-builder-padding"] = $marge;
		$marges["section-$rangee-builder-margin"] = $marge + ['signe' => true];
	}
	$marges['section-footer-builder-layout-padding'] = $marge;
	$marges['section-footer-builder-layout-margin'] = $marge + ['signe' => true];
	$ajouter('marges-rangees', 'inc/class-astra-extended-base-dynamic-css.php', $marges);
	$ajouter('marges-rangees', 'inc/builder/type/header/primary-header/dynamic-css/dynamic.css.php', [
		'section-header-builder-layout-margin' => $marge + ['signe' => true],
	]);
	$ajouter('menu-mobile', 'inc/builder/type/header/mobile-trigger/dynamic-css/dynamic.css.php', [
		'mobile-header-toggle-btn-style' => ['type' => 'enum', 'valeurs' => ['minimal', 'fill', 'outline'], 'defaut' => 'minimal'],
		'mobile-header-toggle-btn-color' => $couleur,
		'mobile-header-toggle-btn-bg-color' => $couleur,
		'mobile-header-toggle-border-color' => $couleur,
		'mobile-header-toggle-btn-border-size' => $cotes + ['defaut' => ['top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1]],
		'mobile-header-toggle-border-radius-fields' => $cotes_r,
		'mobile-header-toggle-icon-size' => ['type' => 'nombre', 'defaut' => 20],
	]);
	$booleen = ['type' => 'booleen'];
	$ajouter('entete-transparent', 'inc/addons/transparent-header/classes/dynamic-css/dynamic.css.php', [
		'transparent-header-enable' => $booleen + ['defaut' => 0],
		'transparent-header-disable-archive' => $booleen + ['defaut' => 1],
		'transparent-header-disable-404-page' => $booleen,
		'transparent-header-disable-search-page' => $booleen,
		'transparent-header-disable-archive-pages' => $booleen,
		'transparent-header-disable-index' => $booleen,
		'transparent-header-disable-latest-posts-index' => $booleen + ['defaut' => 1],
		'transparent-header-disable-page' => $booleen,
		'transparent-header-disable-posts' => $booleen,
		'transparent-header-on-devices' => ['type' => 'enum', 'valeurs' => ['desktop', 'mobile', 'both'], 'defaut' => 'both'],
		'transparent-header-bg-color-responsive' => $couleur_r,
		'hba-transparent-header-bg-color-responsive' => $couleur_r,
		'hbb-transparent-header-bg-color-responsive' => $couleur_r,
		'transparent-header-color-site-title-responsive' => $couleur_r,
		'transparent-header-color-h-site-title-responsive' => $couleur_r,
		'transparent-menu-color-responsive' => $couleur_r,
		'transparent-menu-h-color-responsive' => $couleur_r,
		'transparent-menu-bg-color-responsive' => $couleur_r,
		'transparent-submenu-color-responsive' => $couleur_r,
		'transparent-submenu-h-color-responsive' => $couleur_r,
		'transparent-submenu-bg-color-responsive' => $couleur_r,
		'transparent-content-section-text-color-responsive' => $couleur_r,
		'transparent-content-section-link-color-responsive' => $couleur_r,
		'transparent-content-section-link-h-color-responsive' => $couleur_r,
		'transparent-header-main-sep' => ['type' => 'nombre'],
		'transparent-header-main-sep-color' => $couleur,
		'transparent-header-default-border' => $booleen + ['defaut' => true],
	]);
	return $r;
}

/** Couleur acceptée : vide, hexadécimale, couleur de la palette, rgb() ou rgba(). */
function astra_styles_couleur_valide($v): bool {
	if (!is_string($v)) {
		return false;
	}
	if ($v === '' || preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $v) || preg_match('/^var\(--ast-global-color-[0-8]\)$/', $v)) {
		return true;
	}
	if (!preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(0|1|0?\.\d+|1\.0+)\s*)?\)$/', $v, $m)) {
		return false;
	}
	return max((int) $m[1], (int) $m[2], (int) $m[3]) <= 255 && (strpos($v, 'rgba') === 0) === isset($m[4]);
}

/** Nombre borné ou vide (tailles, côtés, largeurs). */
function astra_styles_nombre_valide($v, float $max = 1000): bool {
	return $v === '' || ((is_int($v) || is_float($v) || (is_string($v) && is_numeric($v))) && (float) $v >= 0 && (float) $v <= $max);
}

/** Champs autorisés d'un fond (`*-bg-obj-responsive`), par appareil. */
function astra_styles_champs_fond(): array {
	$positions = [''];
	foreach (['left', 'center', 'right'] as $x) {
		foreach (['top', 'center', 'bottom'] as $y) {
			$positions[] = "$x $y";
		}
	}
	return [
		'background-type' => ['', 'color', 'image', 'gradient'],
		'background-color' => 'couleur',
		'background-image' => 'image',
		'background-media' => 'entier',
		'background-repeat' => ['', 'repeat', 'no-repeat', 'repeat-x', 'repeat-y'],
		'background-position' => $positions,
		'background-size' => ['', 'auto', 'cover', 'contain'],
		'background-attachment' => ['', 'scroll', 'fixed', 'inherit'],
		'overlay-type' => ['', 'none', 'classic', 'gradient'],
		'overlay-color' => 'couleur',
		'overlay-opacity' => 'opacite',
		'overlay-gradient' => [''],
	];
}

/** Champs autorisés des extras de police (`*-font-extras`, astra_get_font_extras()). */
function astra_styles_champs_extras(): array {
	return [
		'line-height' => 'nombre',
		'line-height-unit' => ['', 'px', 'em'],
		'letter-spacing' => 'nombre-signe',
		'letter-spacing-unit' => ['', 'px', 'em'],
		'text-transform' => ['', 'none', 'capitalize', 'uppercase', 'lowercase'],
		'text-decoration' => ['', 'initial', 'none', 'underline', 'overline', 'line-through'],
	];
}

/** Garde les champs connus et valides d'un objet ; null si ce n'est pas un tableau. */
function astra_styles_filtrer_objet($v, array $champs): ?array {
	if (!is_array($v)) {
		return null;
	}
	$r = [];
	foreach ($champs as $champ => $regle) {
		if (!array_key_exists($champ, $v)) {
			continue;
		}
		$x = $v[$champ];
		$ok = is_array($regle) ? in_array($x, $regle, true) : (
			$regle === 'couleur' ? astra_styles_couleur_valide($x) : (
			$regle === 'nombre' ? astra_styles_nombre_valide($x) : (
			$regle === 'nombre-signe' ? ($x === '' || (is_numeric($x) && abs((float) $x) <= 100)) : (
			$regle === 'nombre-signe-1000' ? ($x === '' || ((is_int($x) || is_float($x) || (is_string($x) && is_numeric($x))) && abs((float) $x) <= 1000)) : (
			$regle === 'opacite' ? ($x === '' || (is_numeric($x) && (float) $x >= 0 && (float) $x <= 1)) : (
			$regle === 'entier' ? ($x === '' || (is_numeric($x) && (int) $x == $x)) : (
			$regle === 'image' ? (is_string($x) && ($x === '' || preg_match('#^IMG/[\w./-]+\.(png|jpe?g|gif|webp|svg)$#i', $x))) : false)))))));
		if ($ok) {
			$r[$champ] = $x;
		}
	}
	return $r;
}

/** Valeur d'un appareil (ou d'une option non responsive) selon le type ; null si invalide. */
function astra_styles_valider(array $d, $v) {
	switch ($d['type']) {
		case 'couleur':
			return astra_styles_couleur_valide($v) ? $v : null;
		case 'taille':
		case 'nombre':
			return astra_styles_nombre_valide($v) ? $v : null;
		case 'quatre-cotes':
			return astra_styles_filtrer_objet($v, array_fill_keys(['top', 'right', 'bottom', 'left'], empty($d['signe']) ? 'nombre' : 'nombre-signe-1000'));
		case 'police':
			return is_string($v) && ($v === '' || isset(astra_polices_locales()[$v]) || preg_match('/^[\w\s,\'"-]+$/u', $v)) ? $v : null;
		case 'graisse':
			return in_array((string) $v, ['', 'inherit', 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'], true) && !is_bool($v) ? $v : null;
		case 'enum':
			return in_array($v, $d['valeurs'], true) ? $v : null;
		case 'alignement':
			return in_array($v, ['', 'left', 'center', 'right'], true) ? $v : null;
		case 'booleen':
			return is_bool($v) || in_array($v, [0, 1, '0', '1', ''], true) ? $v : null;
		case 'fond':
			return astra_styles_filtrer_objet($v, astra_styles_champs_fond());
		case 'extras-police':
			return astra_styles_filtrer_objet($v, astra_styles_champs_extras());
	}
	return null;
}

/** Une option normalisée ; null si rien n'en reste. */
function astra_styles_normaliser_option(array $d, $v) {
	if (!$d['responsive']) {
		return astra_styles_valider($d, $v);
	}
	if (!is_array($v)) {
		return null;
	}
	// Ordre des clés d'origine conservé : aller-retour sans perte des structures d'Astra.
	$r = [];
	foreach ($v as $cle => $x) {
		if (in_array($cle, ASTRA_STYLES_APPAREILS, true)) {
			if (($x = astra_styles_valider($d, $x)) !== null) {
				$r[$cle] = $x;
			}
		} elseif (isset($d['unites']) && preg_match('/^(desktop|tablet|mobile)-unit$/', (string) $cle) && in_array($x, $d['unites'], true)) {
			$r[$cle] = $x;
		}
	}
	return $r ?: null;
}

/**
 * Normalisation commune (formulaire, seed de Garden, imports) : clés du registre seulement,
 * structures d'Astra conservées, valeur ou champ invalide retiré seul.
 */
function astra_styles_normaliser($valeurs): array {
	$registre = astra_styles_registre();
	$r = [];
	foreach (is_array($valeurs) ? $valeurs : [] as $cle => $v) {
		if (isset($registre[$cle]) && ($n = astra_styles_normaliser_option($registre[$cle], $v)) !== null) {
			$r[$cle] = $n;
		}
	}
	return $r;
}

/**
 * Valeur ordinateur d'une option (la valeur elle-même si elle n'est pas responsive). Une valeur
 * responsive sans clé desktop n'a pas de valeur ordinateur : Astra lit $valeur['desktop'] seul.
 */
function astra_styles_ordi($v) {
	if (!is_array($v)) {
		return $v;
	}
	if (array_key_exists('desktop', $v)) {
		return $v['desktop'];
	}
	foreach (['tablet', 'mobile', 'desktop-unit', 'tablet-unit', 'mobile-unit'] as $cle) {
		if (array_key_exists($cle, $v)) {
			return '';
		}
	}
	return $v;
}

/** Vrai si la valeur contient au moins une donnée non vide. */
function astra_styles_renseigne($v): bool {
	if (is_array($v)) {
		foreach ($v as $x) {
			if (astra_styles_renseigne($x)) {
				return true;
			}
		}
		return false;
	}
	return $v !== '' && $v !== null;
}

/** Vrai si une option du groupe (et du préfixe) a une valeur ordinateur renseignée. */
function astra_styles_pose(array $s, string $groupe, string $prefixe = ''): bool {
	foreach (astra_styles_registre() as $o => $d) {
		if ($d['groupe'] === $groupe && isset($s[$o]) && ($prefixe === '' || strpos($o, $prefixe) === 0)
			&& astra_styles_renseigne($d['responsive'] ? astra_styles_ordi($s[$o]) : $s[$o])) {
			return true;
		}
	}
	return false;
}

/** Vrai si l'élément est placé dans le builder (Astra_Builder_Helper::is_component_loaded()). */
function astra_styles_element_place(string $id, array $hfb, string $builder): bool {
	$n = astra_hfb_normaliser($hfb);
	$parties = $builder === 'pied' ? [$n['pied']] : [$n['entete_ordinateur'], $n['entete_mobile']];
	foreach ($parties as $rangees) {
		foreach ($rangees as $zones) {
			foreach ($zones as $ids) {
				if (in_array($id, $ids, true)) {
					return true;
				}
			}
		}
	}
	return false;
}

/** Valeur ordinateur renseignée, sinon le défaut. */
function astra_styles_ou(array $s, string $option, $defaut = '') {
	$v = astra_styles_ordi($s[$option] ?? '');
	return $v === '' || $v === null ? $defaut : $v;
}

/** Taille de police comme astra_get_font_css_value() : en px, doublée d'une valeur en rem. */
function astra_styles_taille_police($valeur, $defaut = ''): string {
	$v = astra_styles_ordi($valeur);
	$unite = is_array($valeur) ? ($valeur['desktop-unit'] ?? 'px') : 'px';
	if ($v === '' || $v === null || (float) $v == 0) {
		if ($defaut === '') {
			return '';
		}
		[$v, $unite] = [$defaut, 'px'];
	}
	if ($unite !== 'px') {
		return $v . $unite;
	}
	return (int) $v . 'px;font-size:' . ((int) $v / ASTRA_STYLES_TAILLE_TEXTE) . 'rem';
}

/** Un côté d'une option quatre-côtés (responsive : valeur ordinateur et son unité ; sinon px). */
function astra_styles_cote($valeur, string $cote, $defaut = ''): string {
	if (!is_array($valeur)) {
		return $defaut === '' ? '' : $defaut . 'px';
	}
	$responsive = array_key_exists('desktop', $valeur);
	$cotes = $responsive ? $valeur['desktop'] : $valeur;
	$v = is_array($cotes) ? ($cotes[$cote] ?? '') : '';
	if ($v === '' || $v === null) {
		return $defaut === '' ? '' : $defaut . 'px';
	}
	return $v . ($responsive ? ($valeur['desktop-unit'] ?? 'px') : 'px');
}

/** Famille de police comme astra_get_css_value(…, 'font') : `inherit` donne le défaut. */
function astra_styles_famille($valeur, string $defaut = ''): string {
	if ($valeur === '' || $valeur === 'inherit' || $valeur === null) {
		return $defaut;
	}
	$locale = astra_polices_locales()[$valeur] ?? null;
	return $locale ? $locale['pile'] : $valeur;
}

/** Extras de police comme astra_get_font_extras(). */
function astra_styles_extras($valeur): array {
	$e = is_array($valeur) ? $valeur : [];
	$val = function (string $champ, string $unite = '') use ($e): string {
		$x = $e[$champ] ?? '';
		return $x === '' || $x === null ? '' : $x . ($unite !== '' ? ($e[$unite] ?? '') : '');
	};
	return [
		'line-height' => $val('line-height', 'line-height-unit'),
		'text-transform' => $val('text-transform'),
		'letter-spacing' => $val('letter-spacing', 'letter-spacing-unit'),
		'text-decoration' => $val('text-decoration'),
	];
}

/** Fond comme astra_get_responsive_background_obj(…, 'desktop'), types `color` et vide. */
function astra_styles_fond($valeur): array {
	$f = astra_styles_ordi($valeur);
	if (!is_array($f)) {
		return [];
	}
	$type = $f['background-type'] ?? '';
	$couleur = $f['background-color'] ?? '';
	if ($type === 'color') {
		return ($f['background-image'] ?? '') === '' ? ['background-color' => $couleur, 'background-image' => 'none'] : [];
	}
	return $type === '' && $couleur !== '' ? ['background-color' => $couleur] : [];
}

/**
 * Écrit des règles [media, sélecteur, [propriété => valeur]] comme astra_parse_css() : valeur
 * vide retirée (0 gardé), règle sans déclaration retirée, media consécutives regroupées ; puis
 * espaces après les virgules retirés, comme Astra_Enqueue_Scripts::trim_css().
 */
function astra_styles_ecrire(array $regles): string {
	$css = '';
	$media = null;
	$bloc = '';
	foreach ($regles as [$m, $selecteur, $declarations]) {
		$d = '';
		foreach ($declarations as $propriete => $valeur) {
			if ($valeur !== '' && $valeur !== null) {
				$d .= $propriete . ':' . $valeur . ';';
			}
		}
		if ($d === '') {
			continue;
		}
		if ($m !== $media) {
			$css .= $media ? '@media ' . $media . '{' . $bloc . '}' : $bloc;
			[$media, $bloc] = [$m, ''];
		}
		$bloc .= $selecteur . '{' . $d . '}';
	}
	$css .= $media ? '@media ' . $media . '{' . $bloc . '}' : $bloc;
	return str_replace(', ', ',', $css);
}

/** Modules de groupe (inc/styles/<module>.php), dans l'ordre où Astra émet leur CSS. */
const ASTRA_STYLES_MODULES = ['titres', 'bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux', 'alignements', 'menu_mobile', 'marges_rangees'];

/**
 * CSS des styles du Customizer (vide sans réglage), dans l'ordre d'émission d'Astra. $compatibilite :
 * drapeaux d'astra/compatibilite, qui changent le CSS même sans option de style.
 */
function astra_styles_css(array $styles, array $hfb, array $compatibilite = []): string {
	$s = astra_styles_normaliser($styles);
	$c = astra_compatibilite_normaliser($compatibilite);
	if (!$s && !$c) {
		return '';
	}
	$regles = [];
	foreach (ASTRA_STYLES_MODULES as $module) {
		require_once __DIR__ . "/styles/$module.php";
		$regles = array_merge($regles, ('astra_styles_' . $module)($s, $hfb, $c));
	}
	return astra_styles_ecrire($regles);
}

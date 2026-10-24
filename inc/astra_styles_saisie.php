<?php
/**
 * Astra pour SPIP — saisie des styles du Customizer (formulaire configurer_astra_styles).
 *
 * Fonctions pures : sections du formulaire, champs prêts à afficher, fusion de la saisie
 * (valeur ordinateur seulement) dans les structures d'Astra enregistrées, valeurs refusées.
 *
 * @license GPL-3.0-or-later
 */

require_once __DIR__ . '/astra_styles.php';

/** Sections du formulaire : clé de langue (astra:styles_section_<clé>) => motif des options. */
function astra_styles_sections(): array {
	return [
		'titres' => '/^(headings-font-weight|font-weight-h[1-6])$/',
		'menu_1' => '/^header-menu1-/',
		'menu_2' => '/^header-menu2-/',
		'bouton' => '/^(button-|theme-button-|font-[a-z]+-button$)/',
		'bouton_entete' => '/^(header-button1-|section-hb-button-1-)/',
		'identite' => '/^(?!(hba-|hbb-)?transparent-).*(site-title|site-tagline|logo-width)/',
		'widget_1' => '/^footer-widget-1-/',
		'widget_2' => '/^footer-widget-2-/',
		'widget_3' => '/^footer-widget-3-/',
		'widget_4' => '/^footer-widget-4-/',
		'copyright' => '/copyright/',
		'sociaux_entete' => '/^header-social-1-/',
		'sociaux_pied' => '/^footer-social-1-/',
		'menu_mobile' => '/^mobile-header-toggle-/',
		'marges_rangees' => '/^section-((above|primary|below)-(header|footer)-builder|(header|footer)-builder-layout)-(padding|margin)$/',
		'entete_transparent' => '/^(hba-|hbb-)?transparent-/',
	];
}

/**
 * Vrai si les trois appareils d'une option sont saisis et appliqués (alignements du pied, marges
 * des rangées), au lieu de la seule valeur ordinateur.
 */
function astra_styles_trois_appareils(array $d): bool {
	return $d['type'] === 'alignement' || !empty($d['appareils']);
}

/** Clé de langue du libellé d'une option : les options d'éléments semblables la partagent. */
function astra_styles_libelle(string $option): string {
	$o = preg_replace(
		['/^header-menu\d-/', '/^footer-widget-\d-/', '/^(header-button1|section-hb-button-1)-/', '/^(header|footer)-social-1-/'],
		['menu-', 'widget-', 'bouton-entete-', 'social-'],
		$option
	);
	return 'astra:style_' . str_replace('-', '_', $o);
}

/**
 * Champs d'une section, prêts à afficher : option, type, responsive, libellé, valeur ordinateur,
 * unité, unités et valeurs acceptées. $styles : réglages enregistrés ou saisie postée.
 */
function astra_styles_champs($styles, string $section): array {
	$styles = is_array($styles) ? $styles : [];
	$motif = astra_styles_sections()[$section] ?? '/^$/';
	$champs = [];
	foreach (astra_styles_registre() as $option => $d) {
		if (!preg_match($motif, $option)) {
			continue;
		}
		$v = $styles[$option] ?? '';
		$valeur = $d['responsive'] ? (is_array($v) ? ($v['desktop'] ?? '') : '') : $v;
		$champs[] = [
			'option' => $option,
			'nom' => $d['responsive'] ? "s[$option][desktop]" : "s[$option]",
			'nom_unite' => "s[$option][desktop-unit]",
			'type' => $d['type'],
			'responsive' => $d['responsive'],
			'libelle' => astra_styles_libelle($option),
			// Booléens d'Astra (true, false, 1, 0) proposés comme « 1 » et « 0 ».
			'valeur' => is_bool($valeur) ? ($valeur ? '1' : '0') : $valeur,
			'unite' => is_array($v) ? ($v['desktop-unit'] ?? 'px') : 'px',
			'unites' => $d['unites'] ?? [],
			'valeurs' => $d['valeurs'] ?? [],
			// Marges extérieures : nombres négatifs acceptés.
			'signe' => !empty($d['signe']),
			// Alignements, marges des rangées : une valeur (et une unité) par appareil, toutes appliquées.
			'appareils' => astra_styles_trois_appareils($d) ? array_map(function ($a) use ($option, $v) {
				return [
					'appareil' => $a, 'nom' => "s[$option][$a]", 'valeur' => is_array($v) ? ($v[$a] ?? '') : '',
					// Une valeur importée sans unité reste inactive après enregistrement.
					'nom_unite' => "s[$option][$a-unit]",
					'unite' => is_array($v) ? ($v["$a-unit"] ?? (array_key_exists($a, $v) ? '' : 'px')) : 'px',
				];
			}, ASTRA_STYLES_APPAREILS) : [],
		];
	}
	return $champs;
}

/** Nom d'un sous-champ : `#VALEUR{nom}|astra_styles_nom{top}` → s[option][desktop][top]. */
function astra_styles_nom(string $nom, string $cle): string {
	return $nom . '[' . $cle . ']';
}

/**
 * Fusionne la saisie dans les réglages enregistrés : pour une option responsive, seules la valeur
 * `desktop` et son unité changent, `tablet` et `mobile` sont gardés (sauf les alignements et les
 * marges des rangées, saisis pour les trois appareils) ; les champs d'une structure
 * (côtés, fond, extras de police) absents de la saisie sont gardés. Une option jamais réglée et
 * laissée vide n'est pas enregistrée.
 */
function astra_styles_saisie_fusionner(array $existant, array $saisie): array {
	$r = $existant;
	foreach (astra_styles_registre() as $o => $d) {
		if (!array_key_exists($o, $saisie)) {
			continue;
		}
		$v = $saisie[$o];
		$avant = $existant[$o] ?? null;
		if ($d['responsive']) {
			$x = is_array($avant) ? $avant : [];
			$appareils = astra_styles_trois_appareils($d) ? ASTRA_STYLES_APPAREILS : ['desktop'];
			foreach (is_array($v) ? $v : [] as $cle => $val) {
				if (in_array($cle, $appareils, true) && is_array($val)) {
					$x[$cle] = array_merge(is_array($x[$cle] ?? null) ? $x[$cle] : [], $val);
				} elseif (in_array($cle, $appareils, true) || in_array(preg_replace('/-unit$/', '', (string) $cle), $appareils, true)) {
					$x[$cle] = $val;
				}
			}
			$v = $x;
		} elseif (is_array($v)) {
			$v = array_merge(is_array($avant) ? $avant : [], $v);
		}
		// Première saisie : gardée si une valeur saisie est renseignée (l'ordinateur seul est saisi,
		// sauf pour les alignements et les marges des rangées, saisis sur les trois appareils ; une
		// unité seule n'est pas une valeur).
		$saisi = !$d['responsive'] ? $v : (astra_styles_trois_appareils($d) ? array_intersect_key($v, array_flip(ASTRA_STYLES_APPAREILS)) : astra_styles_ordi($v));
		if ($avant === null && !astra_styles_renseigne($saisi)) {
			continue;
		}
		$r[$o] = $v;
	}
	return $r;
}

/** Feuilles d'une valeur : chemin => valeur scalaire. */
function astra_styles_feuilles($v, string $chemin = ''): array {
	if (!is_array($v)) {
		return [$chemin => $v];
	}
	$r = [];
	foreach ($v as $cle => $x) {
		$r += astra_styles_feuilles($x, $chemin . '/' . $cle);
	}
	return $r;
}

/** Options dont une valeur saisie non vide est refusée par la normalisation commune. */
function astra_styles_saisie_refusees(array $saisie): array {
	$refusees = [];
	foreach ($saisie as $o => $v) {
		$normalise = astra_styles_normaliser([$o => $v]);
		$gardees = astra_styles_feuilles($normalise[$o] ?? []);
		foreach (astra_styles_feuilles($v) as $chemin => $x) {
			if ($x !== '' && $x !== null && !array_key_exists($chemin, $gardees)) {
				$refusees[] = $o;
				break;
			}
		}
	}
	return $refusees;
}

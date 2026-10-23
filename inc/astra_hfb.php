<?php
/**
 * Astra pour SPIP — builder d'en-tête et de pied de page.
 *
 * Fonctions pures (sans dépendance à SPIP, PHP 7.4) : valeurs par défaut, registre des
 * éléments, normalisation de la configuration et calcul des rangées à afficher, selon la
 * logique d'Astra_Builder_Helper (Astra 4.11.0) et des gabarits
 * template-parts/header/builder/header-row.php et template-parts/footer/builder/footer-row.php.
 */

require_once __DIR__ . '/astra_styles.php';

const ASTRA_HFB_RANGEES = ['above', 'primary', 'below'];

/** Couleur par défaut des séparateurs du pied (hb-footer-main-sep-color, hbb-footer-top-border-color). */
const ASTRA_HFB_COULEUR_SEPARATEUR = 'var( --ast-global-color-subtle-background,--ast-global-color-7 )';
const ASTRA_HFB_ZONES = ['left', 'left_center', 'center', 'right_center', 'right'];
const ASTRA_HFB_ZONES_MOBILE = ['left', 'center', 'right'];
const ASTRA_HFB_COLONNES_MAX = 6;

function astra_hfb_defauts(): array {
	$entete = function (array $zones, array $remplies = []) {
		$rangees = [];
		foreach (ASTRA_HFB_RANGEES as $r) {
			foreach ($zones as $z) {
				$rangees[$r][$r . '_' . $z] = $remplies[$r . '_' . $z] ?? [];
			}
		}
		return $rangees;
	};
	$pied = [];
	foreach (ASTRA_HFB_RANGEES as $r) {
		for ($i = 1; $i <= ASTRA_HFB_COLONNES_MAX; $i++) {
			$pied[$r][$r . '_' . $i] = [];
		}
	}
	$pied['below']['below_1'] = ['copyright'];
	// Icônes par défaut d'Astra, sans adresse.
	$reseaux = [['reseau' => 'facebook', 'url' => ''], ['reseau' => 'twitter', 'url' => ''], ['reseau' => 'instagram', 'url' => '']];
	$widget = ['blocs' => [], 'titre' => '', 'texte' => '', 'menu' => ['source' => 'racine', 'id' => 0]];
	return [
		'entete_ordinateur' => $entete(ASTRA_HFB_ZONES, ['primary_left' => ['logo'], 'primary_right' => ['menu-1']]),
		'entete_mobile' => ['popup' => ['popup_content' => ['mobile-menu']]]
			+ $entete(ASTRA_HFB_ZONES_MOBILE, ['primary_left' => ['logo'], 'primary_right' => ['mobile-trigger']]),
		'pied' => $pied,
		'rangees' => [
			'entete_above' => ['hauteur' => 50, 'fond' => '', 'bordure' => 1],
			'entete_primary' => ['hauteur' => 80, 'fond' => '', 'bordure' => 1],
			'entete_below' => ['hauteur' => 60, 'fond' => '', 'bordure' => 1],
			// Séparateurs : défauts d'Astra 4.11.0 (hba-footer-separator absent, hb-footer-main-sep et
			// hbb-footer-separator à 1) ; couleur vide = couleur par défaut d'Astra.
			'pied_above' => ['colonnes' => 2, 'repartition' => '2-equal', 'hauteur' => 60, 'fond' => '', 'separateur' => '', 'couleur_separateur' => ''],
			'pied_primary' => ['colonnes' => 3, 'repartition' => '3-equal', 'hauteur' => '', 'fond' => '', 'separateur' => 1, 'couleur_separateur' => ''],
			'pied_below' => ['colonnes' => 1, 'repartition' => 'full', 'hauteur' => 60, 'fond' => '', 'separateur' => 1, 'couleur_separateur' => ''],
		],
		'menu_mobile' => 'dropdown',
		'elements' => [
			'entete' => [
				'menu-1' => ['source' => 'racine', 'id' => 0],
				'menu-2' => ['source' => 'racine', 'id' => 0],
				'html-1' => ['texte' => ''],
				'html-2' => ['texte' => ''],
				'button-1' => ['libelle' => '', 'lien' => '', 'nouvel_onglet' => 'non', 'style' => 'plein'],
				'social-icons-1' => ['reseaux' => $reseaux],
				'widget-1' => $widget,
				'widget-2' => $widget,
			],
			'pied' => [
				'menu' => ['source' => 'racine', 'id' => 0, 'disposition' => 'horizontal'],
				'html-1' => ['texte' => ''],
				'html-2' => ['texte' => ''],
				'social-icons-1' => ['reseaux' => $reseaux],
				'widget-1' => $widget,
				'widget-2' => $widget,
				'widget-3' => $widget,
				'widget-4' => $widget,
			],
		],
	];
}

/**
 * Blocs proposés dans les zones de widgets (squelettes inclure/widgets/<bloc>.html).
 */
function astra_hfb_blocs(): array {
	return ['recherche', 'sous_rubriques', 'articles_recents', 'forums_recents', 'mots_cles', 'texte', 'menu'];
}

/**
 * Réseaux sociaux proposés : libellé et couleur (couleurs du sélecteur d'Astra,
 * Mastodon absent d'Astra : couleur de la marque). google_reviews : identifiant et couleur
 * d'Astra (classe ast-google_reviews).
 */
function astra_hfb_reseaux(): array {
	return [
		'facebook' => ['Facebook', '#557dbc'],
		'twitter' => ['Twitter', '#7acdee'],
		'twitter-x' => ['X', '#000000'],
		'instagram' => ['Instagram', '#8a3ab9'],
		'linkedin' => ['LinkedIn', '#1c86c6'],
		'youtube' => ['YouTube', '#e96651'],
		'mastodon' => ['Mastodon', '#6364ff'],
		'bluesky' => ['Bluesky', '#1285fe'],
		'github' => ['GitHub', '#24292e'],
		'rss' => ['RSS', '#f09124'],
		'email' => ['E-mail', '#ea4335'],
		'google_reviews' => ['Google Reviews', '#dc4e41'],
	];
}

/**
 * Éléments autorisés et contextes où ils peuvent être placés
 * (ordinateur, mobile = rangées mobiles, panneau = panneau mobile, pied).
 */
function astra_hfb_registre(): array {
	$entete = ['ordinateur', 'mobile', 'panneau'];
	$partout = ['ordinateur', 'mobile', 'panneau', 'pied'];
	return [
		'logo' => $entete,
		'menu-1' => $entete,
		'menu-2' => $entete,
		'mobile-menu' => ['mobile', 'panneau'],
		'mobile-trigger' => ['mobile'],
		'html-1' => $partout,
		'html-2' => $partout,
		'button-1' => $entete,
		'search' => $entete,
		'social-icons-1' => $partout,
		'widget-1' => $partout,
		'widget-2' => $partout,
		'widget-3' => ['pied'],
		'widget-4' => ['pied'],
		'account' => $entete,
		'copyright' => ['pied'],
		'menu' => ['pied'],
	];
}

/**
 * Configuration complète : défauts appliqués selon les règles de fusion de la spécification,
 * puis placements filtrés par le registre.
 */
function astra_hfb_normaliser($config): array {
	$defauts = astra_hfb_defauts();
	$config = is_array($config) ? $config : [];
	$n = $defauts;

	foreach (['entete_ordinateur', 'entete_mobile', 'pied'] as $builder) {
		$source = isset($config[$builder]) && is_array($config[$builder]) ? $config[$builder] : [];
		foreach ($defauts[$builder] as $rangee => $zones) {
			$sr = isset($source[$rangee]) && is_array($source[$rangee]) ? $source[$rangee] : [];
			foreach ($zones as $zone => $defaut) {
				if (array_key_exists($zone, $sr)) {
					$n[$builder][$rangee][$zone] = is_array($sr[$zone]) ? array_values($sr[$zone]) : [];
				}
			}
		}
	}

	$source = isset($config['rangees']) && is_array($config['rangees']) ? $config['rangees'] : [];
	foreach ($defauts['rangees'] as $cle => $reglages) {
		if (isset($source[$cle]) && is_array($source[$cle])) {
			$n['rangees'][$cle] = array_replace($reglages, array_intersect_key($source[$cle], $reglages));
		}
	}

	$n['menu_mobile'] = in_array($config['menu_mobile'] ?? '', ['dropdown', 'off-canvas'], true) ? $config['menu_mobile'] : 'dropdown';

	$registre = astra_hfb_registre();
	foreach (['entete', 'pied'] as $groupe) {
		$source = isset($config['elements'][$groupe]) && is_array($config['elements'][$groupe]) ? $config['elements'][$groupe] : [];
		foreach ($source as $id => $reglages) {
			if (isset($registre[$id]) && is_array($reglages)) {
				$n['elements'][$groupe][$id] = array_replace($defauts['elements'][$groupe][$id] ?? [], $reglages);
			}
		}
	}

	return astra_hfb_filtrer($n);
}

/**
 * Retire les éléments inconnus, hors contexte, en double dans un même builder,
 * ou placés au-delà du nombre de colonnes d'une rangée du pied.
 */
function astra_hfb_filtrer(array $n): array {
	$registre = astra_hfb_registre();
	$vus = [];
	$garder = function ($ids, string $contexte, string $builder) use ($registre, &$vus) {
		$gardes = [];
		foreach ($ids as $id) {
			if (!is_string($id) || !isset($registre[$id]) || !in_array($contexte, $registre[$id], true) || isset($vus[$builder][$id])) {
				continue;
			}
			$vus[$builder][$id] = true;
			$gardes[] = $id;
		}
		return $gardes;
	};

	foreach (ASTRA_HFB_RANGEES as $r) {
		foreach ($n['entete_ordinateur'][$r] as $zone => $ids) {
			$n['entete_ordinateur'][$r][$zone] = $garder($ids, 'ordinateur', 'ordinateur');
		}
	}
	foreach (ASTRA_HFB_RANGEES as $r) {
		foreach ($n['entete_mobile'][$r] as $zone => $ids) {
			$n['entete_mobile'][$r][$zone] = $garder($ids, 'mobile', 'mobile');
		}
	}
	$n['entete_mobile']['popup']['popup_content'] = $garder($n['entete_mobile']['popup']['popup_content'], 'panneau', 'mobile');

	foreach (ASTRA_HFB_RANGEES as $r) {
		$nombre = astra_hfb_nombre_colonnes($n['rangees']['pied_' . $r]);
		for ($i = 1; $i <= ASTRA_HFB_COLONNES_MAX; $i++) {
			$zone = $r . '_' . $i;
			$n['pied'][$r][$zone] = $i <= $nombre ? $garder($n['pied'][$r][$zone], 'pied', 'pied') : [];
		}
	}
	return $n;
}

function astra_hfb_nombre_colonnes(array $reglages): int {
	return max(1, min(ASTRA_HFB_COLONNES_MAX, (int) ($reglages['colonnes'] ?? 1)));
}

/**
 * Décrit un élément : squelette à inclure, numéro et groupe de réglages
 * (menu-2 → inclure/hfb/menu, 2).
 */
function astra_hfb_element(string $id, string $groupe): array {
	if (preg_match('/^(.+?)-(\d+)$/', $id, $m)) {
		return ['id' => $id, 'fond' => 'inclure/hfb/' . $m[1], 'numero' => $m[2], 'groupe' => $groupe];
	}
	return ['id' => $id, 'fond' => 'inclure/hfb/' . $id, 'numero' => '', 'groupe' => $groupe];
}

function astra_hfb_decrire(array $ids, string $groupe): array {
	return array_map(function ($id) use ($groupe) {
		return astra_hfb_element($id, $groupe);
	}, $ids);
}

/**
 * Rangées non vides d'un en-tête normalisé (ordinateur ou mobile).
 */
function astra_hfb_calculer_entete(array $items): array {
	$resultat = [];
	foreach (ASTRA_HFB_RANGEES as $r) {
		$zones = [];
		foreach (ASTRA_HFB_ZONES as $z) {
			$zones[$z] = astra_hfb_decrire($items[$r][$r . '_' . $z] ?? [], 'entete');
		}
		$cotes = (bool) ($zones['left'] || $zones['left_center'] || $zones['right_center'] || $zones['right']);
		$centre = (bool) $zones['center'];
		if ($cotes || $centre) {
			$resultat[] = ['rangee' => $r, 'cotes' => $cotes, 'centre' => $centre, 'zones' => $zones];
		}
	}
	return $resultat;
}

/**
 * Éléments du panneau mobile, s'il doit être affiché : non vide et ouvert par un mobile-trigger.
 */
function astra_hfb_calculer_panneau(array $mobile): array {
	$bouton = false;
	foreach (ASTRA_HFB_RANGEES as $r) {
		foreach ($mobile[$r] ?? [] as $ids) {
			$bouton = $bouton || in_array('mobile-trigger', $ids, true);
		}
	}
	return $bouton ? astra_hfb_decrire($mobile['popup']['popup_content'] ?? [], 'entete') : [];
}

/**
 * Répartition d'une rangée du pied par appareil. Valeur simple : ordinateur et tablette, mobile
 * `full` (comportement de la version 1.0) ; structure d'Astra (`hb-footer-layout`) : une valeur
 * par appareil, `full` pour un appareil absent ou invalide, comme footer-row.php d'Astra 4.11.0.
 */
function astra_hfb_repartition_appareils($valeur): array {
	$valide = function ($v): string {
		return is_string($v) && preg_match('/^(full|[2-6]-[a-z]+)$/', $v) ? $v : 'full';
	};
	if (!is_array($valeur)) {
		$v = $valide($valeur);
		return ['desktop' => $v, 'tablet' => $v, 'mobile' => 'full'];
	}
	return [
		'desktop' => $valide($valeur['desktop'] ?? ''),
		'tablet' => $valide($valeur['tablet'] ?? ''),
		'mobile' => $valide($valeur['mobile'] ?? ''),
	];
}

/**
 * Rangées non vides d'un pied de page normalisé, avec leurs classes et leurs colonnes.
 */
function astra_hfb_calculer_pied(array $items, array $rangees): array {
	$resultat = [];
	foreach (ASTRA_HFB_RANGEES as $r) {
		$reglages = $rangees['pied_' . $r] ?? [];
		$nombre = astra_hfb_nombre_colonnes($reglages);
		$colonnes = [];
		$occupee = false;
		for ($i = 1; $i <= $nombre; $i++) {
			$colonnes[$i] = astra_hfb_decrire($items[$r][$r . '_' . $i] ?? [], 'pied');
			$occupee = $occupee || $colonnes[$i];
		}
		if (!$occupee) {
			continue;
		}
		$repartition = astra_hfb_repartition_appareils($reglages['repartition'] ?? '');
		$resultat[] = [
			'rangee' => $r,
			'classes' => implode(' ', [
				'site-' . $r . '-footer-wrap',
				'ast-builder-grid-row-container',
				'site-footer-focus-item',
				'ast-builder-grid-row-' . $repartition['desktop'],
				'ast-builder-grid-row-tablet-' . $repartition['tablet'],
				'ast-builder-grid-row-mobile-' . $repartition['mobile'],
				'ast-footer-row-stack',
				'ast-footer-row-tablet-stack',
				'ast-footer-row-mobile-stack',
			]),
			'colonnes' => $colonnes,
		];
	}
	return $resultat;
}

/**
 * CSS des réglages de rangées qui diffèrent des valeurs par défaut d'Astra
 * (hauteur minimale, couleur de fond, épaisseur de la bordure basse de l'en-tête, séparateur
 * des rangées du pied).
 */
function astra_hfb_css_rangees(array $rangees): string {
	$defauts = astra_hfb_defauts()['rangees'];
	$css = [];
	foreach ($defauts as $cle => $defaut) {
		$r = array_replace($defaut, $rangees[$cle] ?? []);
		[$partie, $rangee] = explode('_', $cle, 2);
		if ($partie === 'entete') {
			$barre = '.ast-' . $rangee . '-header-bar';
			$hauteur = '.ast-mobile-header-wrap ' . $barre . ', ' . $barre . ' .site-' . $rangee . '-header-wrap';
		} else {
			$barre = '.site-' . $rangee . '-footer-wrap[data-section="section-' . $rangee . '-footer-builder"]';
			$hauteur = $barre . ', ' . $barre . ' .ast-builder-grid-row';
		}
		if ((string) $r['hauteur'] !== (string) $defaut['hauteur'] && preg_match('/^\d{1,4}$/', (string) $r['hauteur'])) {
			$css[] = $hauteur . '{min-height:' . (int) $r['hauteur'] . 'px;}';
		}
		if ($r['fond'] !== $defaut['fond'] && preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', (string) $r['fond'])) {
			$css[] = $barre . ', .ast-header-break-point ' . $barre . '{background-color:' . $r['fond'] . ';}';
		}
		if (isset($defaut['bordure']) && (string) $r['bordure'] !== (string) $defaut['bordure'] && preg_match('/^\d{1,2}$/', (string) $r['bordure'])) {
			// Astra double le séparateur de la rangée principale pour l'en-tête mobile.
			$selecteur = $cle === 'entete_primary' ? '.ast-header-break-point ' . $barre . ', ' . $barre : $barre;
			$css[] = $selecteur . '{border-bottom-width:' . (int) $r['bordure'] . 'px;}';
		}
		if (array_key_exists('separateur', $defaut)) {
			$css = array_merge($css, astra_hfb_css_separateur($barre, $r, $defaut));
		}
	}
	return implode("\n", $css);
}

/**
 * Séparateur d'une rangée du pied (inc/builder/type/footer/*-footer/dynamic-css/dynamic.css.php
 * d'Astra 4.11.0) : à partir de 1 px, bordure haute de cette épaisseur et de cette couleur. Sans
 * séparateur alors qu'Astra en pose un par défaut (css/astra-hfb.css, css/astra-dynamic.css), la
 * bordure est ramenée aux valeurs initiales de CSS : celles d'une rangée sans règle de bordure.
 */
function astra_hfb_css_separateur(string $barre, array $r, array $defaut): array {
	// Champ vide : valeur d'Astra (explication du formulaire des rangées).
	$epaisseur = (string) $r['separateur'] !== '' ? (string) $r['separateur'] : (string) $defaut['separateur'];
	$couleur = (string) $r['couleur_separateur'];
	if (!preg_match('/^\d{0,2}$/', $epaisseur) || !astra_styles_couleur_valide($couleur)
		|| ($epaisseur === (string) $defaut['separateur'] && $couleur === (string) $defaut['couleur_separateur'])) {
		return [];
	}
	if ((int) $epaisseur >= 1) {
		$couleur = $couleur !== '' ? $couleur : ((int) $defaut['separateur'] >= 1 ? ASTRA_HFB_COULEUR_SEPARATEUR : '');
		return [$barre . '{border-style:solid;border-width:0px;border-top-width:' . (int) $epaisseur . 'px;' . ($couleur !== '' ? 'border-top-color:' . $couleur . ';' : '') . '}'];
	}
	return (int) $defaut['separateur'] >= 1 ? [$barre . '{border-style:none;border-width:medium;border-color:currentcolor;}'] : [];
}

<?php
/**
 * Astra pour SPIP — saisie de la configuration du builder.
 *
 * Fonctions pures (PHP 7.4) utilisées par le formulaire configurer_astra_hfb : zones
 * proposées, conversion entre la configuration et les champs du formulaire (un emplacement
 * et un ordre par élément), vérification de la saisie.
 */

require_once __DIR__ . '/astra_hfb.php';

/** Builders du formulaire et clé de configuration correspondante. */
function astra_hfb_builders(): array {
	return ['ordinateur' => 'entete_ordinateur', 'mobile' => 'entete_mobile', 'pied' => 'pied'];
}

/**
 * Zones proposées dans le formulaire pour un builder, dans l'ordre d'affichage.
 */
function astra_hfb_zones_saisie(string $builder): array {
	$zones = [];
	foreach (ASTRA_HFB_RANGEES as $r) {
		if ($builder === 'pied') {
			for ($i = 1; $i <= ASTRA_HFB_COLONNES_MAX; $i++) {
				$zones[] = $r . '_' . $i;
			}
		} else {
			foreach (ASTRA_HFB_ZONES_MOBILE as $z) {
				$zones[] = $r . '_' . $z;
			}
		}
	}
	if ($builder === 'mobile') {
		$zones[] = 'popup_content';
	}
	return $zones;
}

/** Rangée d'une zone (popup pour le panneau mobile). */
function astra_hfb_rangee_de_zone(string $zone): string {
	return $zone === 'popup_content' ? 'popup' : explode('_', $zone, 2)[0];
}

/** Contexte du registre correspondant à une zone d'un builder. */
function astra_hfb_contexte_de_zone(string $builder, string $zone): string {
	if ($builder === 'mobile') {
		return $zone === 'popup_content' ? 'panneau' : 'mobile';
	}
	return $builder;
}

/**
 * Éléments qui peuvent être placés dans un builder, dans l'ordre du registre.
 */
function astra_hfb_elements_saisie(string $builder): array {
	$contextes = $builder === 'mobile' ? ['mobile', 'panneau'] : [$builder];
	$ids = [];
	foreach (astra_hfb_registre() as $id => $autorises) {
		if (array_intersect($contextes, $autorises)) {
			$ids[] = $id;
		}
	}
	return $ids;
}

function astra_hfb_zone_autorisee(string $builder, string $id, string $zone): bool {
	$registre = astra_hfb_registre();
	return isset($registre[$id])
		&& in_array($zone, astra_hfb_zones_saisie($builder), true)
		&& in_array(astra_hfb_contexte_de_zone($builder, $zone), $registre[$id], true);
}

/**
 * Placements d'un builder (structure de la configuration, toutes zones explicites)
 * à partir des champs « emplacement » et « ordre » du formulaire.
 */
function astra_hfb_placements_depuis_saisie(array $place, array $ordre, string $builder): array {
	$items = astra_hfb_defauts()[astra_hfb_builders()[$builder]];
	array_walk_recursive($items, function (&$v) {
		$v = [];
	});
	foreach ($items as $r => $zones) {
		foreach ($zones as $z => $vide) {
			$items[$r][$z] = [];
		}
	}
	$rang = array_flip(array_keys(astra_hfb_registre()));
	$par_zone = [];
	foreach (astra_hfb_elements_saisie($builder) as $id) {
		$zone = (string) ($place[$id] ?? '');
		if ($zone !== '' && astra_hfb_zone_autorisee($builder, $id, $zone)) {
			$par_zone[$zone][] = [(int) ($ordre[$id] ?? 0), $rang[$id], $id];
		}
	}
	foreach ($par_zone as $zone => $liste) {
		sort($liste);
		$items[astra_hfb_rangee_de_zone($zone)][$zone] = array_column($liste, 2);
	}
	return $items;
}

/**
 * Champs du formulaire à partir d'une configuration normalisée.
 */
function astra_hfb_saisie_depuis_config(array $config): array {
	$saisie = ['place' => [], 'ordre' => []];
	foreach (astra_hfb_builders() as $builder => $cle) {
		foreach (astra_hfb_elements_saisie($builder) as $id) {
			$saisie['place'][$builder][$id] = '';
			$saisie['ordre'][$builder][$id] = '';
		}
		foreach ($config[$cle] as $zones) {
			foreach ($zones as $zone => $ids) {
				foreach (array_values($ids) as $i => $id) {
					if (isset($saisie['place'][$builder][$id])) {
						$saisie['place'][$builder][$id] = $zone;
						$saisie['ordre'][$builder][$id] = (string) ($i + 1);
					}
				}
			}
		}
	}
	$saisie['rangees'] = $config['rangees'];
	$saisie['menu_mobile'] = $config['menu_mobile'];
	$saisie['elements'] = astra_hfb_saisie_elements($config['elements']);
	return $saisie;
}

/** Valeurs des champs de réglages d'éléments à partir de la configuration. */
function astra_hfb_saisie_elements(array $elements): array {
	foreach ($elements as $groupe => $liste) {
		foreach ($liste as $id => $r) {
			if (isset($r['source'])) {
				$elements[$groupe][$id]['source_id'] = in_array($r['source'], ['rubrique', 'groupe_mots'], true)
					? $r['source'] . ':' . (int) $r['id'] : $r['source'];
			}
			if (isset($r['reseaux'])) {
				$champs = [];
				$choisis = array_column($r['reseaux'], 'url', 'reseau');
				foreach (astra_hfb_reseaux() as $reseau => $infos) {
					$champs[$reseau] = [
						'afficher' => array_key_exists($reseau, $choisis) ? 'oui' : '',
						'url' => (string) ($choisis[$reseau] ?? ''),
					];
				}
				$elements[$groupe][$id]['reseaux'] = $champs;
			}
		}
	}
	return $elements;
}

/** Réglages d'éléments à enregistrer à partir des champs du formulaire. */
function astra_hfb_elements_depuis_saisie(array $saisie): array {
	$defauts = astra_hfb_defauts()['elements'];
	$elements = [];
	foreach ($defauts as $groupe => $liste) {
		foreach ($liste as $id => $defaut) {
			$champs = isset($saisie[$groupe][$id]) && is_array($saisie[$groupe][$id]) ? $saisie[$groupe][$id] : [];
			$r = [];
			foreach ($defaut as $cle => $valeur) {
				if ($cle === 'source' || $cle === 'id') {
					continue;
				}
				if ($cle === 'reseaux') {
					$r['reseaux'] = [];
					foreach (astra_hfb_reseaux() as $reseau => $infos) {
						$c = $champs['reseaux'][$reseau] ?? [];
						if (($c['afficher'] ?? '') === 'oui') {
							$r['reseaux'][] = ['reseau' => $reseau, 'url' => trim((string) ($c['url'] ?? ''))];
						}
					}
				} elseif ($cle === 'blocs') {
					$choisis = isset($champs['blocs']) && is_array($champs['blocs']) ? $champs['blocs'] : [];
					$r['blocs'] = array_values(array_intersect(astra_hfb_blocs(), $choisis));
				} else {
					$r[$cle] = trim((string) ($champs[$cle] ?? $valeur));
				}
			}
			if (array_key_exists('source', $defaut)) {
				$source_id = (string) ($champs['source_id'] ?? 'racine');
				if (preg_match('/^(rubrique|groupe_mots):(\d+)$/', $source_id, $m)) {
					$r = ['source' => $m[1], 'id' => (int) $m[2]] + $r;
				} elseif ($source_id === 'pages') {
					$r = ['source' => 'pages', 'id' => 0] + $r;
				} else {
					$r = ['source' => 'racine', 'id' => 0] + $r;
				}
			}
			if (isset($defaut['disposition']) && !in_array($r['disposition'], ['horizontal', 'vertical'], true)) {
				$r['disposition'] = $defaut['disposition'];
			}
			if (isset($defaut['nouvel_onglet']) && $r['nouvel_onglet'] !== 'oui') {
				$r['nouvel_onglet'] = 'non';
			}
			if (isset($defaut['style']) && $r['style'] !== 'contour') {
				$r['style'] = 'plein';
			}
			$elements[$groupe][$id] = $r;
		}
	}
	return $elements;
}

/**
 * Vérifie les champs du formulaire. Renvoie les erreurs, indexées par nom de champ aplati
 * (place_<builder>_<id>, rangees_<rangee>_<réglage>).
 */
function astra_hfb_verifier_saisie(array $saisie): array {
	$erreurs = [];
	foreach (astra_hfb_builders() as $builder => $cle) {
		foreach (astra_hfb_elements_saisie($builder) as $id) {
			$zone = (string) ($saisie['place'][$builder][$id] ?? '');
			if ($zone !== '' && !astra_hfb_zone_autorisee($builder, $id, $zone)) {
				$erreurs['place_' . $builder . '_' . $id] = 'zone_interdite';
			}
		}
	}

	// Pied de page : colonne au-delà du nombre de colonnes de la rangée.
	foreach ($saisie['place']['pied'] ?? [] as $id => $zone) {
		if (preg_match('/^(above|primary|below)_(\d)$/', (string) $zone, $m)) {
			$colonnes = (int) ($saisie['rangees']['pied_' . $m[1]]['colonnes'] ?? 1);
			if ((int) $m[2] > $colonnes) {
				$erreurs['place_pied_' . $id] = 'colonne_absente';
			}
		}
	}

	// Panneau mobile non vide : un bouton de menu est nécessaire dans une rangée mobile.
	$mobile = $saisie['place']['mobile'] ?? [];
	$panneau = array_filter($mobile, function ($zone) {
		return $zone === 'popup_content';
	});
	$bouton = ($mobile['mobile-trigger'] ?? '') !== '' && ($mobile['mobile-trigger'] ?? '') !== 'popup_content';
	if ($panneau && !$bouton) {
		$erreurs['place_mobile_mobile-trigger'] = 'bouton_menu_requis';
	}

	foreach (astra_hfb_defauts()['rangees'] as $cle => $defaut) {
		$r = $saisie['rangees'][$cle] ?? [];
		if (!preg_match('/^\d{0,4}$/', (string) ($r['hauteur'] ?? ''))) {
			$erreurs['rangees_' . $cle . '_hauteur'] = 'nombre';
		}
		if (!preg_match('/^(#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?)?$/', (string) ($r['fond'] ?? ''))) {
			$erreurs['rangees_' . $cle . '_fond'] = 'couleur';
		}
		if (isset($defaut['bordure']) && !preg_match('/^\d{1,2}$/', (string) ($r['bordure'] ?? ''))) {
			$erreurs['rangees_' . $cle . '_bordure'] = 'nombre';
		}
		if (array_key_exists('separateur', $defaut)) {
			$epaisseur = (string) ($r['separateur'] ?? '');
			if (!preg_match('/^\d{0,2}$/', $epaisseur) || (int) $epaisseur > 20) {
				$erreurs['rangees_' . $cle . '_separateur'] = 'nombre';
			}
			if (!astra_styles_couleur_valide((string) ($r['couleur_separateur'] ?? ''))) {
				$erreurs['rangees_' . $cle . '_couleur_separateur'] = 'couleur';
			}
		}
		if (isset($defaut['colonnes'])) {
			$colonnes = (int) ($r['colonnes'] ?? 0);
			$repartition = $r['repartition'] ?? '';
			$appareils = is_array($repartition) ? ['desktop', 'tablet', 'mobile'] : ['desktop'];
			if ($colonnes < 1 || $colonnes > ASTRA_HFB_COLONNES_MAX) {
				$erreurs['rangees_' . $cle . '_colonnes'] = 'colonnes';
			} else {
				foreach ($appareils as $appareil) {
					$v = is_array($repartition) ? ($repartition[$appareil] ?? '') : $repartition;
					if (!in_array($v, astra_hfb_repartitions($colonnes, $appareil), true)) {
						$erreurs['rangees_' . $cle . '_repartition'] = 'repartition';
					}
				}
			}
		}
	}

	if (!in_array($saisie['menu_mobile'] ?? '', ['dropdown', 'off-canvas'], true)) {
		$erreurs['menu_mobile'] = 'choix';
	}
	return $erreurs;
}

/**
 * Répartitions des colonnes du pied proposées par Astra pour un nombre de colonnes et un appareil
 * (Astra_Builder_Helper, choix du contrôle ast-row-layout, Astra 4.11.0) : tablette et mobile
 * ajoutent `full` et, selon le nombre de colonnes, `2-equal`, `3-firstrow`, `3-lastrow`.
 * La tablette garde en plus les répartitions de l'ordinateur (`4-lheavy`, `4-rheavy`) : une valeur
 * simple de la version 1.2.0 s'y applique, et le formulaire doit pouvoir la reproposer.
 */
function astra_hfb_repartitions(int $colonnes, string $appareil = 'desktop'): array {
	$ordinateur = [
		1 => ['full'],
		2 => ['2-equal', '2-lheavy', '2-rheavy'],
		3 => ['3-equal', '3-cwide', '3-cheavy', '3-lheavy', '3-rheavy'],
		4 => ['4-equal', '4-lheavy', '4-rheavy'],
		5 => ['5-equal'],
		6 => ['6-equal'],
	];
	$petits = [
		1 => ['full'],
		2 => ['2-equal', '2-lheavy', '2-rheavy', 'full'],
		3 => ['3-equal', '3-cwide', '3-cheavy', '3-lheavy', '3-rheavy', '3-firstrow', '3-lastrow', 'full'],
		4 => ['4-equal', '2-equal', 'full'],
		5 => ['5-equal', 'full'],
		6 => ['6-equal', 'full'],
	];
	if (!in_array($appareil, ['desktop', 'tablet', 'mobile'], true)) {
		return [];
	}
	if ($appareil === 'tablet') {
		return array_values(array_unique(array_merge($petits[$colonnes] ?? [], $ordinateur[$colonnes] ?? [])));
	}
	return ($appareil === 'desktop' ? $ordinateur : $petits)[$colonnes] ?? [];
}

/**
 * Répartitions proposées pour un appareil, tous nombres de colonnes confondus, sans doublon et
 * dans l'ordre d'Astra : liste du sélecteur ; la vérification écarte celles qui ne vont pas avec
 * le nombre de colonnes choisi.
 */
function astra_hfb_repartitions_appareil(string $appareil): array {
	$toutes = [];
	for ($colonnes = 1; $colonnes <= ASTRA_HFB_COLONNES_MAX; $colonnes++) {
		$toutes = array_merge($toutes, astra_hfb_repartitions($colonnes, $appareil));
	}
	return array_values(array_unique($toutes));
}

/** Réglages de rangées à enregistrer à partir des champs du formulaire. */
function astra_hfb_rangees_depuis_saisie(array $saisie): array {
	$rangees = [];
	foreach (astra_hfb_defauts()['rangees'] as $cle => $defaut) {
		foreach ($defaut as $reglage => $valeur) {
			if ($reglage === 'repartition' && is_array($saisie[$cle][$reglage] ?? null)) {
				// Une répartition par appareil (structure d'Astra) ; une valeur simple reste simple.
				$r = $saisie[$cle][$reglage];
				$rangees[$cle][$reglage] = [
					'desktop' => trim((string) ($r['desktop'] ?? $valeur)),
					'tablet' => trim((string) ($r['tablet'] ?? $valeur)),
					'mobile' => trim((string) ($r['mobile'] ?? 'full')),
				];
				continue;
			}
			$v = trim((string) ($saisie[$cle][$reglage] ?? $valeur));
			if (in_array($reglage, ['colonnes', 'bordure'], true) || (in_array($reglage, ['hauteur', 'separateur'], true) && $v !== '')) {
				$v = (int) $v;
			}
			$rangees[$cle][$reglage] = $v;
		}
	}
	return $rangees;
}

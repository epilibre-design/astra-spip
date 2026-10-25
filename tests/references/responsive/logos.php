<?php
/** Logos locaux réservés aux bases privées de comparaison ; aucune image distante au rendu. */
function astra_reference_logo_spip(?string $nom): void {
	include_spip('inc/config');
	include_spip('inc/autoriser');
	$chercher = charger_fonction('chercher_logo', 'inc');
	$present = $chercher(0, 'id_syndic', 'on');
	$propre = lire_config('astra/reference_logo');
	if ($present && (!$propre || $present[0] !== $propre)) {
		throw new RuntimeException('Le logo existant n’appartient pas au pilote de références.');
	}
	if ($present) {
		include_spip('action/editer_logo');
		logo_supprimer('site', 0, 'on');
	}
	effacer_config('astra/reference_logo');
	if ($nom === null) { return; }
	$manifest = json_decode(file_get_contents(__DIR__ . '/images-identite.json'), true);
	if (!isset($manifest[$nom])) { throw new InvalidArgumentException('Logo de référence inconnu.'); }
	$source = getenv('ASTRA_IMAGES') . '/' . $nom . '.png';
	if (!is_file($source) || hash_file('sha256', $source) !== $manifest[$nom]['sha256']) {
		throw new RuntimeException('Logo de référence absent ou empreinte incorrecte.');
	}
	include_spip('action/editer_logo');
	$temp = tempnam(_DIR_TMP, 'logo-reference-');
	$copie = $temp . '.png';
	rename($temp, $copie);
	copy($source, $copie);
	autoriser_exception('joindredocument', 'site', 0);
	try { $erreur = logo_modifier('site', 0, 'on', $copie); }
	finally { autoriser_exception('joindredocument', 'site', 0, false); @unlink($copie); }
	if ($erreur !== '') { throw new RuntimeException($erreur); }
	$logo = $chercher(0, 'id_syndic', 'on');
	if (!$logo) { throw new RuntimeException('Logo non installé.'); }
	ecrire_config('astra/reference_logo', $logo[0]);
}

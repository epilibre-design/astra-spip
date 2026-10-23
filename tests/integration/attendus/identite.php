<?php
/**
 * Cas de la migration de l'identité (titre et slogan) : configuration d'avant la version 1.2.0.
 * Exécuté seul (php tests/integration/attendus/identite.php), écrit identite-<cas>.html avec les
 * squelettes présents : à lancer avant la migration pour figer le rendu de référence.
 */
const ASTRA_CAS_IDENTITE = [
	'slogan_oui' => ['afficher_slogan' => 'oui'],
	'slogan_non' => ['afficher_slogan' => 'non'],
	'titre_non' => ['afficher_titre_site' => 'non'],
	'aucune' => [],
];

function astra_cas_identite_appliquer(array $config): void {
	foreach (['afficher_slogan', 'afficher_titre_site'] as $cle) {
		isset($config[$cle]) ? ecrire_config("astra/$cle", $config[$cle]) : effacer_config("astra/$cle");
	}
	ecrire_meta('slogan_site', 'Jardiner au naturel');
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
	require dirname(__DIR__, 2) . '/bootstrap_integration.php';
	$slogan = $GLOBALS['meta']['slogan_site'] ?? '';
	foreach (ASTRA_CAS_IDENTITE as $cas => $config) {
		astra_cas_identite_appliquer($config);
		file_put_contents(__DIR__ . "/identite-$cas.html", recuperer_fond('inclure/identite', []));
	}
	ecrire_meta('slogan_site', $slogan);
	effacer_config('astra/afficher_slogan');
	effacer_config('astra/afficher_titre_site');
}

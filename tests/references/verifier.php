<?php
/** Usage : php verifier.php <WP_ROOT> <ARCHIVE_ZIP> <MANIFEST_JSON>. */
require_once __DIR__ . '/environnement.php';
try {
	if ($argc !== 4) {
		throw new RuntimeException('Usage : verifier.php <WP_ROOT> <ARCHIVE_ZIP> <MANIFEST_JSON>');
	}
	[$script, $racine, $archive, $manifeste] = $argv;
	$attendue = json_decode(file_get_contents($manifeste), true, 512, JSON_THROW_ON_ERROR);
	if (!is_file($archive) || hash_file('sha256', $archive) !== ($attendue['archive_sha256'] ?? null)) {
		throw new RuntimeException('Archive différente de la référence épinglée');
	}
	$zip = new ZipArchive();
	if ($zip->open($archive) !== true) {
		throw new RuntimeException('Archive illisible');
	}
	$actif = astra_reference_wp($racine, ['option', 'get', 'stylesheet']);
	$theme = rtrim($racine, '/') . '/wp-content/themes/astra';
	$style = is_file($theme . '/style.css') ? file_get_contents($theme . '/style.css') : '';
	preg_match('/^Version:\s*(\S+)/m', $style, $version);
	$reelle = [
		'wordpress' => astra_reference_wp($racine, ['core', 'version']),
		'astra' => $version[1] ?? '',
		'theme' => $actif,
		'archive_sha256' => hash_file('sha256', $archive),
		'integrite_sources' => astra_reference_sources($theme, $zip),
		'assets' => [],
	];
	foreach ($attendue['assets'] as $chemin => $empreinte) {
		$reelle['assets'][$chemin] = is_file($theme . '/' . $chemin) ? hash_file('sha256', $theme . '/' . $chemin) : null;
	}
	$zip->close();
	$erreurs = astra_reference_erreurs($attendue, $reelle);
	if ($erreurs) {
		throw new RuntimeException(implode("\n", $erreurs));
	}
	echo json_encode($reelle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
} catch (Throwable $erreur) {
	fwrite(STDERR, $erreur->getMessage() . "\n");
	exit(1);
}

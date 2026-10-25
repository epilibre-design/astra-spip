<?php
/** Garde des références WordPress : aucun chargement du thème ni changement d'option. */
function astra_reference_erreurs(array $attendue, array $reelle): array {
	$erreurs = [];
	foreach (['wordpress', 'astra', 'theme', 'archive_sha256', 'integrite_sources'] as $cle) {
		if (!array_key_exists($cle, $attendue) || !array_key_exists($cle, $reelle) || $attendue[$cle] !== $reelle[$cle]) {
			$erreurs[] = "Référence non conforme : $cle";
		}
	}
	foreach ($attendue['assets'] ?? [] as $chemin => $empreinte) {
		if (($reelle['assets'][$chemin] ?? null) !== $empreinte) {
			$erreurs[] = "Asset non conforme : $chemin";
		}
	}
	return $erreurs;
}

/** Exécuter wp-cli sans les hooks du thème ; le drop-in de base reste nécessaire. */
function astra_reference_wp(string $racine, array $arguments): string {
	$commande = array_merge(['wp', '--path=' . $racine, '--skip-themes', '--skip-plugins'], $arguments);
	$processus = proc_open($commande, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuyaux);
	if (!is_resource($processus)) {
		throw new RuntimeException('Impossible de lancer wp-cli');
	}
	fclose($tuyaux[0]);
	$sortie = stream_get_contents($tuyaux[1]);
	$erreur = stream_get_contents($tuyaux[2]);
	fclose($tuyaux[1]);
	fclose($tuyaux[2]);
	if (proc_close($processus) !== 0) {
		throw new RuntimeException('Lecture wp-cli impossible : ' . trim($erreur));
	}
	return trim($sortie);
}

/** Comparaison de tous les fichiers du thème à l'archive épinglée, fichiers ajoutés compris. */
function astra_reference_sources(string $theme, ZipArchive $zip): bool {
	$attendus = [];
	for ($i = 0; $i < $zip->numFiles; $i++) {
		$nom = $zip->getNameIndex($i);
		if (substr($nom, -1) === '/') {
			continue;
		}
		if (strpos($nom, 'astra/') !== 0 || strpos($nom, '..') !== false) {
			return false;
		}
		$relatif = substr($nom, 6);
		$attendus[$relatif] = true;
		$chemin = $theme . '/' . $relatif;
		if (is_link($chemin) || !is_file($chemin) || hash_file('sha256', $chemin) !== hash('sha256', $zip->getFromIndex($i))) {
			return false;
		}
	}
	if (!$attendus || !is_dir($theme)) {
		return false;
	}
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme, FilesystemIterator::SKIP_DOTS));
	foreach ($files as $file) {
		$relatif = substr($file->getPathname(), strlen($theme) + 1);
		if ($file->isLink() || !$file->isFile() || !isset($attendus[$relatif])) {
			return false;
		}
	}
	return true;
}

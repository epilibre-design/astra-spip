<?php
/**
 * Compare la structure de l'en-tête, du panneau mobile et du pied de page produits par SPIP
 * à celle des références WordPress (tests/references/sorties/Rn/accueil.html).
 *
 * Usage : SPIP_CLI=… php tests/comparaison/comparer_html.php <racine SPIP> <URL SPIP> [Rn …]
 * (variable REFERENCES : autre dossier de références que tests/references/sorties)
 *
 * Pour chaque configuration : application au site SPIP (appliquer.sh), puis comparaison du
 * squelette du builder : balises et classes des rangées, zones et éléments, jusqu'au premier
 * niveau à l'intérieur de chaque élément. Les contenus (textes, entrées de menu, widgets,
 * formulaires) diffèrent entre les deux sites et ne sont pas comparés. Code de sortie 1 en cas
 * de différence.
 */

if ($argc < 3) {
	fwrite(STDERR, "Usage : SPIP_CLI=… php comparer_html.php <racine SPIP> <URL SPIP> [Rn …]\n");
	exit(2);
}
[$script, $racine, $url] = $argv;
$ici = __DIR__;
require_once $ici . '/configurations.php';
$configurations = array_slice($argv, 3) ?: array_keys(astra_configurations_reference());

/** Racines d'éléments : on compare leur premier niveau, pas leur contenu. */
function est_element(DOMElement $e): bool {
	return (bool) preg_match('/(^| )(ast-builder-layout-element|footer-widget-area|header-widget-area)( |$)/', $e->getAttribute('class'));
}

function classes(DOMElement $e): string {
	$c = preg_split('/\s+/', trim($e->getAttribute('class')));
	$c = array_filter($c, function ($x) {
		return $x !== '' && !preg_match('/^(menu-item-\d+|current-menu-\w+)$/', $x);
	});
	sort($c);
	return implode('.', $c);
}

/** Lignes « profondeur balise.classes » du squelette d'un nœud. */
function squelette(DOMElement $e, int $profondeur = 0, int $niveaux = -1): array {
	if (in_array($e->tagName, ['script', 'style', 'svg', 'form'], true)) {
		return [];
	}
	$lignes = [str_repeat('  ', $profondeur) . $e->tagName . ($e->getAttribute('class') !== '' ? '.' . classes($e) : '') . ($e->getAttribute('id') && !preg_match('/^(menu-item|search-field|text-)/', $e->getAttribute('id')) ? '#' . $e->getAttribute('id') : '')];
	if ($niveaux === 0) {
		return $lignes;
	}
	$suite = $niveaux > 0 ? $niveaux - 1 : (est_element($e) ? 1 : -1);
	foreach ($e->childNodes as $enfant) {
		if ($enfant instanceof DOMElement) {
			$lignes = array_merge($lignes, squelette($enfant, $profondeur + 1, $suite));
		}
	}
	return $lignes;
}

function parties(string $html): array {
	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	$doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
	libxml_clear_errors();
	$x = new DOMXPath($doc);
	$resultat = [];
	foreach (['entete' => '//header[@id="masthead"]', 'panneau' => '//*[@id="ast-mobile-popup-wrapper"]', 'pied' => '//footer[@id="colophon"]'] as $nom => $chemin) {
		$n = $x->query($chemin)->item(0);
		$resultat[$nom] = $n ? squelette($n) : [];
	}
	return $resultat;
}

$echec = false;
foreach ($configurations as $conf) {
	$reference = (getenv('REFERENCES') ?: $ici . '/../references/sorties') . '/' . $conf . '/accueil.html';
	if (!is_file($reference)) {
		fwrite(STDERR, "$conf : référence absente ($reference), lancer tests/references/capturer.sh\n");
		$echec = true;
		continue;
	}
	passthru(escapeshellarg($ici . '/appliquer.sh') . ' ' . escapeshellarg($racine) . ' ' . escapeshellarg($conf) . ' > /dev/null', $code);
	if ($code) {
		$echec = true;
		continue;
	}
	$spip = parties((string) file_get_contents(rtrim($url, '/') . '/'));
	$wp = parties((string) file_get_contents($reference));
	foreach ($wp as $partie => $lignes) {
		if ($lignes === $spip[$partie]) {
			echo "$conf / $partie : identique (" . count($lignes) . " nœuds)\n";
			continue;
		}
		$echec = true;
		$a = tempnam(sys_get_temp_dir(), 'wp');
		$b = tempnam(sys_get_temp_dir(), 'spip');
		file_put_contents($a, implode("\n", $lignes) . "\n");
		file_put_contents($b, implode("\n", $spip[$partie]) . "\n");
		echo "$conf / $partie : DIFFÉRENT (- WordPress, + SPIP)\n";
		passthru('diff -u --label wordpress --label spip ' . escapeshellarg($a) . ' ' . escapeshellarg($b) . ' | head -60');
		unlink($a);
		unlink($b);
	}
}
passthru(escapeshellarg($ici . '/appliquer.sh') . ' ' . escapeshellarg($racine) . ' R0-defaut > /dev/null');
exit($echec ? 1 : 0);

<?php
/**
 * Compare la structure de l'en-tête, du panneau mobile et du pied de page produits par SPIP
 * à celle des références WordPress (tests/references/sorties/<conf>/accueil.html). Pour les
 * configurations de pages (clé page ou astra), compare aussi #content jusqu'à .entry-content
 * et les classes du body.
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
require_once $ici . '/normalisations.php';
// Par défaut, les configurations du builder et des pages : les références des styles (clé styles)
// ne changent pas la structure comparée ici, elles sont vérifiées par comparer_captures.sh.
$configurations = array_slice($argv, 3) ?: array_keys(array_filter(astra_configurations_reference(), function ($c) {
	return !isset($c['styles']);
}));

/** Chemin SPIP de la page comparée : page unique de la configuration, sinon l'accueil. */
function chemin_spip(string $racine, array $conf): string {
	if (empty($conf['page'])) {
		return '/';
	}
	$php = 'include_spip("inc/filtres"); echo generer_objet_url((int) sql_getfetsel("id_article", "spip_articles", ["id_rubrique=-1", "page=" . sql_quote(' . var_export($conf['page'], true) . ')]), "article", "", "", true);';
	$url = trim((string) shell_exec('cd ' . escapeshellarg($racine) . ' && ' . getenv('SPIP_CLI') . ' php:eval ' . escapeshellarg($php)));
	return '/' . ltrim(preg_replace('#^https?://[^/]+#', '', $url), '/');
}

/** Classes du body comparables : sans celles que WordPress seul produit. */
function classes_body(DOMXPath $x): array {
	$b = $x->query('//body')->item(0);
	$c = $b ? preg_split('/\s+/', trim($b->getAttribute('class'))) : [];
	$c = array_values(array_filter($c, function ($x) {
		return $x !== '' && !preg_match('/^(wp-|group-blog$|[a-z]+-template-default$)/', $x);
	}));
	$c = preg_replace('/^(page-id|postid)-\d+$/', '$1-N', $c);
	sort($c);
	return $c;
}

/** Racines d'éléments du builder : on compare leur premier niveau, pas leur contenu. */
function est_element(DOMElement $e): bool {
	return (bool) preg_match('/(^| )(ast-builder-layout-element|footer-widget-area|header-widget-area)( |$)/', $e->getAttribute('class'));
}

/** Contenu rédactionnel : la balise elle-même est comparée, pas ses enfants. */
function est_contenu(DOMElement $e): bool {
	return (bool) preg_match('/(^| )entry-content( |$)/', $e->getAttribute('class'));
}

function classes(DOMElement $e): string {
	$c = preg_split('/\s+/', trim($e->getAttribute('class')));
	$c = array_filter($c, function ($x) {
		return $x !== '' && !preg_match('/^(menu-item-\d+|current-menu-\w+)$/', $x);
	});
	// Identifiants d'objets : différents entre les deux sites.
	$c = preg_replace('/^(post|page-id|postid)-\d+$/', '$1-N', $c);
	sort($c);
	return implode('.', $c);
}

/** Lignes « profondeur balise.classes » du squelette d'un nœud. */
function squelette(DOMElement $e, int $profondeur = 0, int $niveaux = -1): array {
	if (in_array($e->tagName, ['script', 'style', 'svg', 'form'], true)) {
		return [];
	}
	$lignes = [str_repeat('  ', $profondeur) . $e->tagName . ($e->getAttribute('class') !== '' ? '.' . classes($e) : '') . ($e->getAttribute('id') && !preg_match('/^(menu-item|search-field|text-)/', $e->getAttribute('id')) ? '#' . preg_replace('/^post-\d+$/', 'post-N', $e->getAttribute('id')) : '')];
	if ($niveaux === 0) {
		return $lignes;
	}
	if (est_contenu($e)) {
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

function parties(string $html, bool $page): array {
	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	$doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
	libxml_clear_errors();
	$x = new DOMXPath($doc);
	astra_normaliser_navigation_sans_js($x);
	$chemins = ['entete' => '//header[@id="masthead"]', 'panneau' => '//*[@id="ast-mobile-popup-wrapper"]', 'pied' => '//footer[@id="colophon"]'];
	if ($page) {
		$chemins['contenu'] = '//*[@id="content"]';
	}
	$resultat = [];
	foreach ($chemins as $nom => $chemin) {
		$n = $x->query($chemin)->item(0);
		$resultat[$nom] = $n ? squelette($n) : [];
	}
	if ($page) {
		$resultat['body'] = classes_body($x);
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
	$conf_c = astra_configurations_reference()[$conf];
	$page = isset($conf_c['page']) || isset($conf_c['astra']);
	$spip = parties((string) file_get_contents(rtrim($url, '/') . chemin_spip($racine, $conf_c)), $page);
	$wp = parties((string) file_get_contents($reference), $page);
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

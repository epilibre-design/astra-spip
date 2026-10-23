<?php
/**
 * Médias des blocs couverture et média/texte : le document affiché par SPIP doit provenir de la
 * même image que dans WordPress (empreinte SHA-256 du fichier original).
 * Usage : SPIP_CLI=… php tests/comparaison/comparer_medias.php <racine SPIP importé> <racine WP_BLOCS>
 */
[$script, $spip, $wp] = $argv;
$cli = getenv('SPIP_CLI');
$wp_json = shell_exec('wp --path=' . escapeshellarg($wp) . ' eval ' . escapeshellarg('
	$p = get_post(get_option("wp2spip_blocs_page"));
	$r = [];
	foreach (parse_blocks($p->post_content) as $b) {
		if ($b["blockName"] === "core/cover") { $r[] = ["cover", get_attached_file($b["attrs"]["id"]), $p->ID]; }
		if ($b["blockName"] === "core/media-text") { $r[] = ["media-text", get_attached_file($b["attrs"]["mediaId"]), $p->ID]; }
	}
	echo json_encode($r);') . ' 2>/dev/null');
$attendus = json_decode((string) $wp_json, true) ?: [];
$id_wp = $attendus[0][2] ?? 0;
$spip_json = shell_exec('cd ' . escapeshellarg($spip) . ' && ' . $cli . ' php:eval ' . escapeshellarg('
	$a = sql_fetsel("id_article, texte", "spip_articles", "page=" . sql_quote("wordpress_page_' . (int) $id_wp . '"));
	preg_match_all("#<div class=\"wp-block-(cover|media-text)\">\s*(?:<div class=\"wp-block-media-text__media\">\s*)?<(?:doc|img)(\d+)#", $a["texte"], $m, PREG_SET_ORDER);
	$r = [];
	foreach ($m as $x) {
		$f = sql_getfetsel("fichier", "spip_documents", "id_document=" . (int) $x[2]);
		$r[] = [$x[1], _DIR_IMG . $f, (int) $x[2], $a["id_article"]];
	}
	echo json_encode($r);'));
$obtenus = json_decode((string) $spip_json, true) ?: [];
$echec = count($attendus) !== count($obtenus) || !$attendus;
foreach ($attendus as $i => [$bloc, $fichier]) {
	[$bloc_s, $fichier_s, $id_doc] = $obtenus[$i] ?? ['', '', 0];
	$ok = $bloc === $bloc_s && is_file($fichier) && is_file($spip . '/' . $fichier_s) && hash_file('sha256', $fichier) === hash_file('sha256', $spip . '/' . $fichier_s);
	echo ($ok ? 'ok    ' : 'ÉCART ') . "$bloc : document $id_doc\n";
	$echec = $echec || !$ok;
}
exit($echec ? 1 : 0);

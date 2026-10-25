<?php
/** Fournisseur local de preuves, sans média tiers ni accès sortant. */
$origine = getenv('OEMBED_FIXTURE_URL') ?: 'http://localhost:8428';
$chemin = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = $_GET['url'] ?? '';
parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $arguments);
$essai = preg_replace('/[^a-zA-Z0-9-]/', '', $arguments['essai'] ?? $_GET['essai'] ?? '');
if ($journal = getenv('OEMBED_FIXTURE_JOURNAL')) {
	file_put_contents($journal, json_encode(['methode'=>$_SERVER['REQUEST_METHOD'], 'chemin'=>$chemin, 'url_cible'=>parse_url($url, PHP_URL_PATH), 'essai'=>$essai], JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
}
if ($chemin === '/thumbnail.png') {
	header('Content-Type: image/png'); readfile(dirname(__DIR__,2) . '/references/blocs/images/media.png'); return;
}
if (preg_match('~^/player/(large|classique)$~', $chemin)) {
	header('Content-Type: text/html; charset=utf-8');
	echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>html,body{height:100%;margin:0}body{display:grid;place-items:center;background:#216e39;color:white;font:20px sans-serif}</style></head><body>Lecteur local de preuve</body></html>';return;
}
if ($chemin === '/inconnu') {
	header('Content-Type: text/html; charset=utf-8');
	echo '<!doctype html><html><head><title>Fournisseur inconnu</title></head><body>Aucun endpoint oEmbed déclaré.</body></html>';return;
}
if ($chemin === '/decouverte') {
	header('Content-Type: text/html; charset=utf-8');
	$endpoint = htmlspecialchars($origine . '/oembed?url=' . rawurlencode($origine . '/video/large?essai=' . $essai), ENT_QUOTES);
	echo '<!doctype html><html><head><link rel="alternate" type="application/json+oembed" href="' . $endpoint . '"></head><body>Page de découverte locale</body></html>';return;
}
if ($chemin === '/oembed') {
	header('Content-Type: application/json; charset=utf-8');
	$parties = parse_url($url);
	if (($parties['scheme'] ?? '') !== 'http' || ($parties['host'] ?? '') !== parse_url($origine, PHP_URL_HOST) || ($parties['port'] ?? 0) !== (int) parse_url($origine, PHP_URL_PORT)) { http_response_code(400); echo '{}';return; }
	$cas = basename($parties['path'] ?? '');
	if ($cas === 'erreur') { http_response_code(503);echo '{"error":"indisponible pour le test"}';return; }
	$fichier = ['large'=>'video-16-9.json', 'classique'=>'video-4-3.json', 'invalide'=>'invalid.json'][$cas] ?? '';
	if (!$fichier) { http_response_code(404);echo '{}';return; }
	echo str_replace(['@ORIGINE@','@ESSAI@'], [$origine,$essai], file_get_contents(__DIR__ . '/responses/' . $fichier));return;
}
http_response_code(404);echo 'Fixture inconnue';

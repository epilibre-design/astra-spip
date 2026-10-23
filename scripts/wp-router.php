<?php
// Routeur pour php -S : redirige les URL de dossiers sans barre finale (ex. /wp-admin), comme
// Apache, pour que les liens relatifs de wp-admin se résolvent ; sert index.php sinon.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = $_SERVER['DOCUMENT_ROOT'] . $path;
if ($path !== '/' && substr($path, -1) !== '/' && is_dir($file)) {
	$qs = $_SERVER['QUERY_STRING'] ?? '';
	header('Location: ' . $path . '/' . ($qs !== '' ? '?' . $qs : ''), true, 301);
	return true;
}
if ($path !== '/' && is_file($file)) {
	return false;
}
if (is_dir($file) && is_file(rtrim($file, '/') . '/index.php')) {
	return false;
}
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';

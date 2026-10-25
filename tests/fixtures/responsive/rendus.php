<?php
// CLI SPIP uniquement ; configurations supplémentaires de la preuve sans JavaScript.
if (PHP_SAPI !== 'cli' || !defined('_ECRIRE_INC_VERSION')) { exit; }
include_spip('inc/config');
include_spip('public/assembler');
require_once dirname(__DIR__, 3) . '/astra_fonctions.php';
$avant = lire_config('astra', []);
$sorties = [];
try {
	ecrire_config('astra/menu_accueil', 'non');
	$cases = [
		'pages' => ['elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]]],
		'deux' => [
			'entete_mobile' => ['above' => ['above_center' => ['menu-2']]],
			'elements' => ['entete' => ['menu-2' => ['source' => 'pages', 'id' => 0]]],
		],
		'vide' => ['entete_mobile' => ['popup' => ['popup_content' => []]]],
	];
	foreach ($cases as $nom => $hfb) {
		ecrire_config('astra/hfb', $hfb);
		$sorties[$nom] = recuperer_fond('sommaire');
	}
} finally {
	ecrire_config('astra', $avant);
}
echo json_encode($sorties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

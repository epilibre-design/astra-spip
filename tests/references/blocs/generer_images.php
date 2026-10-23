<?php
// Images de test des blocs : aplats et motifs générés (aucune photographie). Domaine public (CC0).
// Usage : php tests/references/blocs/generer_images.php
foreach (['couverture' => [64, 128, 96], 'media' => [180, 90, 40]] as $nom => [$r, $v, $b]) {
	$im = imagecreatetruecolor(800, 500);
	imagefill($im, 0, 0, imagecolorallocate($im, $r, $v, $b));
	$clair = imagecolorallocate($im, min(255, $r + 80), min(255, $v + 80), min(255, $b + 80));
	for ($x = 0; $x < 800; $x += 100) {
		imagefilledrectangle($im, $x, 0, $x + 40, 500, $clair);
	}
	imagepng($im, __DIR__ . "/images/$nom.png", 9);
}

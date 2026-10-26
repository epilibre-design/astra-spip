<?php
/** Image de test originale du projet Astra SPIP, GPL-3.0-or-later ; aucun portrait réel. */
$im=imagecreatetruecolor(100,100);$fond=imagecolorallocate($im,52,110,90);imagefill($im,0,0,$fond);
$clair=imagecolorallocate($im,226,239,219);imagefilledellipse($im,50,35,30,30,$clair);imagefilledrectangle($im,25,60,75,99,$clair);
imagejpeg($im,__DIR__.'/portrait.jpg',95);imagedestroy($im);

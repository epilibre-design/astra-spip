<?php
/** Cache déterministe du vrai Gravatar 3.0.0 ; fixture originale GPL-3.0-or-later. */
function astra_gravatar_cache_preparer(): array {
 $avant=$GLOBALS['meta']['gravatar_salt'] ?? null;
 ecrire_meta('gravatar_salt','astra-fixture-privee-gravatar-20261026','non');
 $cache=sous_repertoire(_DIR_VAR,'cache-gravatar');
 $image=$cache.sha1('avatar@example.org'.$GLOBALS['meta']['gravatar_salt']).'.jpg';
 $vide=$cache.sha1('sans-avatar@example.org'.$GLOBALS['meta']['gravatar_salt']).'.vide';
 $im=imagecreatetruecolor(100,100);$fond=imagecolorallocate($im,52,110,90);imagefill($im,0,0,$fond);$clair=imagecolorallocate($im,226,239,219);imagefilledellipse($im,50,35,30,30,$clair);imagefilledrectangle($im,25,60,75,99,$clair);imagejpeg($im,$image,95);imagedestroy($im);
 touch($vide);touch($cache.'gravatar.lock');
 return ['sel'=>$avant,'fichiers'=>[$image,$vide,$cache.'gravatar.lock'],'image'=>$image];
}
function astra_gravatar_cache_restaurer(array $etat): void {
 foreach($etat['fichiers'] as $f)@unlink($f);
 if($etat['sel']===null)effacer_meta('gravatar_salt');else ecrire_meta('gravatar_salt',$etat['sel'],'non');
}

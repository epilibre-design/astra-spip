<?php
/**
 * Intégration facultative de Gravatar 3.0.0 (GPL v3), API gravatar()/gravatar_balise_imgx2().
 * Le cache et la récupération restent entièrement ceux du plugin SPIP Gravatar.
 * Présentation : Astra 4.13.8, inc/blog/single-blog.php, avatar de commentaire de 50 px.
 * @license GPL-3.0-or-later
 */
function astra_commentaire_gravatar($email): string {
 if (!is_string($email) || !function_exists('test_plugin_actif') || !test_plugin_actif('gravatar')) {
  return '';
 }
 include_spip('inc/filtres');
 $email = trim($email);
 if ($email === '' || !email_valide($email)) {
  return '';
 }
 include_spip('gravatar_fonctions');
 if (!function_exists('gravatar') || !function_exists('gravatar_balise_imgx2')) {
  return '';
 }
 $image = gravatar($email, '404');
 if (!$image) {
  return '';
 }
 $html = gravatar_balise_imgx2($image, 50, '', 'avatar avatar-50 photo');
 if ($html === '') {
  return '';
 }
 // Le helper x2 produit des dimensions intrinsèques et parfois un style inline.
 // Le thème applique 50 px, puis 2.5 em sous 921 px : laisser sa cascade agir.
 foreach (['style'=>null, 'width'=>'50', 'height'=>'50', 'alt'=>'', 'aria-hidden'=>'true'] as $attribut=>$valeur) {
  $html = inserer_attribut($html, $attribut, $valeur, true, $attribut === 'style');
 }
 return $html;
}

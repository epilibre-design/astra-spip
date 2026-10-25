<?php
/** Sondes CLI de disposition ; images de test CC0, pas de contenu de modèle. */
if (PHP_SAPI !== 'cli') { exit; }
$cas = getenv('CSS_RARE_CAS');
$tests = dirname(__DIR__, 2);
if (defined('ABSPATH')) {
	$mu = WPMU_PLUGIN_DIR . '/astra-preuve-rtl.php';
	if (is_file($mu)) { unlink($mu); }
	if ($cas === 'nettoyer') {
		require $tests . '/references/wp/R0-defaut.php';
		return;
	}
	$ref = ['rtl' => 'R4-pied-asymetrique', 'page' => 'P1-page'][$cas] ?? 'R0-defaut';
	require $tests . '/references/wp/' . $ref . '.php';
	if ($cas === 'logo-vide') {
		$zones = ['above' => ['above_left'=>[], 'above_center'=>[], 'above_right'=>[]],
			'primary' => ['primary_left'=>['logo'], 'primary_center'=>[], 'primary_right'=>[]],
			'below' => ['below_left'=>[], 'below_center'=>[], 'below_right'=>[]], 'popup'=>['popup_content'=>[]]];
		$desktop = $zones;
		foreach (['above','primary','below'] as $r) { $desktop[$r][''.$r.'_left_center']=[]; $desktop[$r][''.$r.'_right_center']=[]; }
		ref_reglages(['header-desktop-items'=>$desktop, 'header-mobile-items'=>$zones]);
	}
	$logo = get_posts(['post_type'=>'attachment', 'post_status'=>'inherit', 'title'=>'Preuve logo sans menu Astra', 'numberposts'=>1]);
	if (!$logo) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam('logo.png');
		copy($tests . '/references/blocs/images/media.png', $tmp);
		$id = media_handle_sideload(['name'=>'logo.png', 'tmp_name'=>$tmp], 0, 'Preuve logo sans menu Astra');
		if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
	} else { $id = $logo[0]->ID; }
	set_theme_mod('custom_logo', (int) $id);
	// Même taille rendue que le plafond de 60 px du port pour ce PNG 800×500.
	// Cette sonde porte sur la visibilité sans menu, pas sur les options de taille non portées.
	ref_reglages(['footer-copyright-editor'=>'Preuve des dispositions', 'ast-header-responsive-logo-width'=>['desktop'=>96,'tablet'=>96,'mobile'=>96]]);
	if ($cas === 'rtl') {
		if (!is_dir(WPMU_PLUGIN_DIR)) { mkdir(WPMU_PLUGIN_DIR, 0775, true); }
		file_put_contents($mu, '<?php add_action("after_setup_theme", function () { $GLOBALS["wp_locale"]->text_direction = "rtl"; if (isset($GLOBALS["wp_styles"])) { $GLOBALS["wp_styles"]->text_direction = "rtl"; } });');
	}
	return;
}
if (!defined('_ECRIRE_INC_VERSION')) { throw new RuntimeException('Charger avec wp eval-file ou spip php:eval'); }
include_spip('inc/config'); include_spip('public/assembler'); include_spip('action/editer_logo');
require_once dirname(__DIR__, 3) . '/astra_fonctions.php';
require_once $tests . '/comparaison/configurations.php';
$chercher = charger_fonction('chercher_logo', 'inc');
$logo = $chercher(0, 'id_syndic', 'on');
$marque = (int) ($GLOBALS['meta']['astra_preuve_logo_id'] ?? 0);
if ($cas === 'nettoyer') {
	if ($logo && $marque && (int) ($logo[5]['id_document'] ?? 0) === $marque) { logo_supprimer('site', 0, 'on'); }
	effacer_meta('astra_preuve_logo_id');
	return;
}
if ($logo && !$marque) { throw new RuntimeException('Un logo préalable existe : utiliser un site de test sans logo'); }
if (!$logo) {
	$tmp = tempnam(_DIR_TMP, 'astra-logo-');
	copy($tests . '/references/blocs/images/media.png', $tmp);
	$visiteur = $GLOBALS['visiteur_session'] ?? [];
	try {
		$GLOBALS['visiteur_session'] = sql_fetsel('id_auteur,statut,webmestre', 'spip_auteurs', 'webmestre="oui" AND statut="0minirezo"');
		if (!$GLOBALS['visiteur_session']) { throw new RuntimeException('Administrateur de test absent'); }
		$erreur = logo_modifier('site', 0, 'on', ['tmp_name'=>$tmp, 'name'=>'logo.png', 'error'=>0]);
		if ($erreur) { throw new RuntimeException($erreur); }
	} finally { $GLOBALS['visiteur_session'] = $visiteur; if (is_file($tmp)) { unlink($tmp); } }
	$logo = $chercher(0, 'id_syndic', 'on');
	if (!$logo) { throw new RuntimeException('Logo de preuve absent après ajout'); }
	ecrire_meta('astra_preuve_logo_id', (string) $logo[5]['id_document']);
}
$avant = lire_config('astra', []);
$langue = $GLOBALS['spip_lang'] ?? 'fr';
try {
	foreach (['accueil_type','accueil_page','styles','compatibilite','pages'] as $cle) { effacer_config('astra/' . $cle); }
	$c = astra_configurations_reference()[$cas === 'rtl' ? 'R4-pied-asymetrique' : 'R0-defaut'];
	$hfb = astra_configuration_hfb($c);
	if ($cas === 'logo-vide') {
		$hfb = ['entete_ordinateur'=>['primary'=>['primary_left'=>['logo'],'primary_center'=>[],'primary_right'=>[]]],
			'entete_mobile'=>['primary'=>['primary_left'=>['logo'],'primary_center'=>[],'primary_right'=>[]], 'popup'=>['popup_content'=>[]]]];
	}
	ecrire_config('astra/hfb', $hfb);
	ecrire_config('astra/texte_pied', 'Preuve des dispositions');
	$lang = $cas === 'rtl' ? 'ar' : 'fr'; changer_langue($lang);
	$fond = 'sommaire'; $env = ['lang'=>$lang];
	if ($cas === 'page') {
		$fond = 'article'; $env['id_article']=(int) sql_getfetsel('id_article','spip_articles','page="notre_jardin"');
	}
	if ($cas === 'archive') {
		$fond = 'rubrique'; $env['id_rubrique']=(int) sql_getfetsel('id_rubrique','spip_rubriques','titre LIKE ' . sql_quote('%Jardinage'));
	}
	echo recuperer_fond($fond, $env);
} finally {
	ecrire_config('astra', $avant); changer_langue($langue);
}

<?php
/** Références indépendantes de l'identité Astra 4.13.8 ; données partagées avec SPIP. */
function ref_identite_responsive(string $variante): void {
	putenv('GROUPE=base');
	require dirname(__DIR__) . '/styles/appliquer.php';
	$cas = json_decode(file_get_contents(dirname(__DIR__) . '/responsive/identite.json'), true)[$variante];
	$styles = $cas['styles'];
	foreach (['display-site-title-responsive' => true, 'display-site-tagline-responsive' => false] as $o => $defaut) {
		foreach (['desktop', 'tablet', 'mobile'] as $a) {
			if (!isset($styles[$o][$a]) || $styles[$o][$a] === '') {
				$styles[$o][$a] = $defaut;
			}
		}
	}
	foreach (['font-size-site-title', 'font-size-site-tagline'] as $o) {
		if (isset($styles[$o]) && !array_filter(array_intersect_key($styles[$o], array_flip(['desktop','tablet','mobile'])), function ($v) { return $v !== '' && $v !== null; })) {
			unset($styles[$o]); // Contrat SPIP : tableau entièrement vide = absence de réglage.
		}
	}
	if (isset($styles['ast-header-responsive-logo-width'])) {
		$styles['ast-header-responsive-logo-width'] += ['desktop' => '', 'tablet' => '', 'mobile' => ''];
	}
	$styles['font-size-body'] = ['desktop' => 16, 'tablet' => '', 'mobile' => '', 'desktop-unit' => 'px', 'tablet-unit' => 'px', 'mobile-unit' => 'px'];
	$styles['header-mobile-items'] = [
		'popup' => ['popup_content' => ['mobile-menu']],
		'above' => ['above_left' => [], 'above_center' => [], 'above_right' => []],
		'primary' => ['primary_left' => ['logo'], 'primary_center' => [], 'primary_right' => ['mobile-trigger']],
		'below' => ['below_left' => [], 'below_center' => [], 'below_right' => []],
	];
	ref_reglages($styles);
	if ($cas['transparent']) {
		$ids = ref_pages();
		update_post_meta($ids['notre-jardin'], 'theme-transparent-header-meta', 'enabled');
	}
	update_option('blogdescription', $cas['meta']['slogan_site']);
	$images = getenv('ASTRA_IMAGES');
	if (!$images) { throw new RuntimeException('ASTRA_IMAGES requis pour les logos locaux.'); }
	$source = "$images/{$cas['logo']}.png";
	$cle = 'astra_reference_logo_' . hash_file('sha256', $source);
	$id = (int) get_option($cle);
	if (!$id || !get_post($id)) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$copie = wp_tempnam(basename($source));
		copy($source, $copie);
		$id = media_handle_sideload(['name' => basename($source), 'tmp_name' => $copie], 0);
		if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
		update_option($cle, $id);
	}
	set_theme_mod('custom_logo', $id);
}

<?php
/** Alignements d'Astra 4.13.8 : fichier amont footer/widget/dynamic-css/dynamic.css.php. */
function ref_widgets_alignements(string $variante): void {
	putenv('GROUPE=base');
	require dirname(__DIR__) . '/styles/appliquer.php';
	$cas = json_decode(file_get_contents(dirname(__DIR__) . '/responsive/widgets.json'), true)[$variante];
	$styles = $cas['styles'];
	if ($cas['direction'] === 'rtl') {
		// Ce mu-plugin appartient exclusivement au site de référence jetable.
		file_put_contents(WPMU_PLUGIN_DIR . '/astra-reference-rtl.php', <<<'PHP'
<?php
add_action('init', function () { $GLOBALS['wp_locale']->text_direction = 'rtl'; });
PHP
		);
	} else { @unlink(WPMU_PLUGIN_DIR . '/astra-reference-rtl.php'); }

	// Une option complètement vide signifie absence de nouveau réglage dans le port.
	foreach ($styles as $o => $v) {
		if (!array_filter($v, function ($x) { return $x !== '' && $x !== null; })) { unset($styles[$o]); }
	}
	$h = get_option('astra-settings');
	$p = $h['footer-desktop-items'];
	if ($cas['placement'] === 'absent' || $cas['placement'] === 'entete') {
		$p['primary']['primary_1'] = [];
	}
	if ($cas['placement'] === 'entete') {
		$h['header-desktop-items']['above']['above_left'] = ['widget-1'];
		$styles['header-desktop-items'] = $h['header-desktop-items'];
	}
	if ($cas['placement'] === 'deplace') { $p['primary']['primary_1'] = []; $p['below']['below_1'] = ['widget-1']; }
	if ($cas['placement'] === 'zone6') {
		$p['primary'] = array_fill_keys(['primary_1','primary_2','primary_3','primary_4','primary_5'], []);
		$p['primary']['primary_6'] = ['widget-1'];
		$styles['hb-footer-column'] = '6';
	}
	$styles['footer-desktop-items'] = $p;
	ref_reglages($styles);
	if ($cas['blocs'] === 'texte-aligne') {
		$zones = get_option('sidebars_widgets');
		$zones['footer-widget-1'] = $zones['footer-widget-2'] = [];
		update_option('sidebars_widgets', $zones);
		ref_widget_texte('footer-widget-1', 'Colonne 1', '<p class="reference-aligne" style="text-align:right">Bloc explicitement aligné</p>');
		ref_widget_menu('footer-widget-2', 'Colonne 2', 'Principal');
	}
}

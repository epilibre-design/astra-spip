<?php
/** Sortie déterministe des générateurs purs, à comparer sous PHP 7.4 et 8.4. */
require_once dirname(__DIR__, 2) . '/inc/astra_hfb.php';
require_once dirname(__DIR__, 2) . '/inc/astra_entete_transparent.php';
require_once dirname(__DIR__) . '/comparaison/configurations.php';
foreach (astra_configurations_reference() as $nom => $conf) {
	$hfb = astra_hfb_normaliser(astra_configuration_hfb($conf));
	echo "/* $nom */\n", astra_hfb_css_rangees($hfb['rangees']), "\n",
		astra_styles_css($conf['styles'] ?? [], $hfb, $conf['compatibilite'] ?? []), "\n",
		astra_entete_transparent_css($conf['styles'] ?? [], $hfb), "\n";
}

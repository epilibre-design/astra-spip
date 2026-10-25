<?php
require __DIR__ . '/R1-entete-complet.php';
$s = get_option('astra-settings');
$s['header-mobile-items']['above']['above_center'] = ['menu-2'];
ref_reglages(['header-mobile-items' => $s['header-mobile-items']]);

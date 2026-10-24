<?php
// Drapeau de compatibilité submenu-below-header à faux (site.json de Garden Maintenance) :
// Astra_Dynamic_CSS::astra_submenu_below_header_fix() renvoie faux.
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
ref_reglages(['submenu-below-header' => false]);

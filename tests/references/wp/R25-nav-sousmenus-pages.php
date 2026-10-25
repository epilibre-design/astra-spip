<?php
require __DIR__ . '/R25-nav-sousmenus.php';
foreach (['primary', 'secondary_menu', 'mobile_menu'] as $emplacement) {
	ref_menu_pages($emplacement);
}

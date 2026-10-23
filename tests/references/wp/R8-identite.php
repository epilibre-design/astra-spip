<?php
// Styles du Customizer, valeurs de Garden Maintenance : Identité du site (titre, slogan affiché, logo).
putenv('GROUPE=identite');
update_option('blogdescription', 'Un site propulsé par SPIP');
require dirname(__DIR__) . '/styles/appliquer.php';

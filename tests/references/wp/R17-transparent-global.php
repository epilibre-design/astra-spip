<?php
// En-tête transparent sur tout le site (réglage global), avec les exclusions par défaut d'Astra :
// la page de la catégorie Jardinage, archive, garde l'en-tête normal.
require __DIR__ . '/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
$valeurs = json_decode(file_get_contents(dirname(__DIR__) . '/transparent/valeurs/garden.json'), true);
ref_reglages(['transparent-header-enable' => 1] + $valeurs);

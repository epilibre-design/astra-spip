<?php
// Charge le SPIP 4.4 de test installé par scripts/install-spip-test.sh.
$spipRoot = dirname(__DIR__) . '/vendor/spip/spip';
chdir($spipRoot);
// SPIP 4.4 démarre son noyau (SpipLeague) par l'autoloader de Composer.
require_once $spipRoot . '/vendor/autoload.php';
require_once $spipRoot . '/ecrire/inc_version.php';
include_spip('inc/plugin');
actualise_plugins_actifs();
include_spip('inc/config');
include_spip('public/assembler');
include_once _DIR_PLUGINS . 'astra/astra_fonctions.php';

<?php
// En-tête transparent activé sur la page « Notre jardin » (méta de page), réglage global désactivé,
// réglages de l'en-tête transparent de Garden Maintenance (transparent/valeurs/garden.json).
putenv('VARIANTE=garden');
require dirname(__DIR__) . '/transparent/appliquer.php';

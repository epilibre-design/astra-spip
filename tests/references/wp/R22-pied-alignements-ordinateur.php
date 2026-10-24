<?php
// Alignements réglés pour l'ordinateur seul : tablette et mobile prennent la valeur de
// l'ordinateur, comme dans Astra. Valeurs : styles/valeurs/alignements-ordinateur.json.
putenv('GROUPE=alignements-ordinateur');
require dirname(__DIR__) . '/styles/appliquer.php';

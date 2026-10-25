<?php
/** Options des seuls sites privés de preuve ; aucun relâchement du validateur SPIP. */
$origine = getenv('OEMBED_FIXTURE_URL') ?: 'http://localhost:8428';
$GLOBALS['oembed_providers']['#^' . preg_quote($origine, '#') . '/video/(large|classique|erreur|invalide)(?:\?.*)?$#'] = $origine . '/oembed';

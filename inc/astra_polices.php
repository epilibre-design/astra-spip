<?php
/**
 * Astra pour SPIP — polices locales (spec des styles du Customizer, « Polices locales ») :
 * registre fermé des polices fournies en woff2 dans fonts/, et leurs @font-face. Aucun service
 * tiers n'est appelé.
 *
 * @license GPL-3.0-or-later
 */

/**
 * Polices locales : nom, pile CSS (telle qu'Astra l'enregistre), fichiers [graisse, style, chemin],
 * licence, source et version. Fichiers « latin » de Fontsource 5.1.0.
 */
function astra_polices_locales(): array {
	return [
		'raleway' => [
			'nom' => 'Raleway',
			'pile' => "'Raleway', sans-serif",
			'fichiers' => [
				['400', 'normal', 'fonts/raleway/raleway-latin-400-normal.woff2'],
				['400', 'italic', 'fonts/raleway/raleway-latin-400-italic.woff2'],
				['700', 'normal', 'fonts/raleway/raleway-latin-700-normal.woff2'],
			],
			'licence' => 'OFL-1.1',
			'source' => 'https://cdn.jsdelivr.net/npm/@fontsource/raleway@5.1.0/',
			'version' => '5.1.0',
		],
		'poppins' => [
			'nom' => 'Poppins',
			'pile' => "'Poppins', sans-serif",
			'fichiers' => [
				['500', 'normal', 'fonts/poppins/poppins-latin-500-normal.woff2'],
				['600', 'normal', 'fonts/poppins/poppins-latin-600-normal.woff2'],
			],
			'licence' => 'OFL-1.1',
			'source' => 'https://cdn.jsdelivr.net/npm/@fontsource/poppins@5.1.0/',
			'version' => '5.1.0',
		],
	];
}

/** Police locale désignée par la première famille d'une pile ('Raleway', sans-serif → raleway). */
function astra_polices_detecter(string $pile): ?string {
	$premiere = strtolower(trim(explode(',', $pile)[0], " \t'\""));
	foreach (astra_polices_locales() as $cle => $police) {
		if ($premiere === strtolower($police['nom']) || $premiere === $cle) {
			return $cle;
		}
	}
	return null;
}

/**
 * @font-face des polices locales présentes dans les piles choisies, dans les graisses fournies ;
 * rien pour une pile système. $url donne l'adresse publique d'un fichier du plugin.
 */
function astra_polices_css(array $piles, callable $url): string {
	$css = '';
	$vues = [];
	foreach ($piles as $pile) {
		$cle = is_string($pile) ? astra_polices_detecter($pile) : null;
		if ($cle === null || isset($vues[$cle])) {
			continue;
		}
		$vues[$cle] = true;
		$police = astra_polices_locales()[$cle];
		foreach ($police['fichiers'] as [$graisse, $style, $chemin]) {
			$css .= "@font-face{font-family:'{$police['nom']}';font-style:$style;font-weight:$graisse;font-display:swap;src:url(" . $url($chemin) . ") format('woff2');}";
		}
	}
	return $css;
}

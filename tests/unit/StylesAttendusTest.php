<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';

/**
 * Équivalence avec WordPress : pour chaque groupe, les règles produites contiennent toutes les
 * règles qu'Astra 4.11.0 ajoute ou modifie (tests/references/styles/attendus/<groupe>.css,
 * capturées par capturer.sh), et leurs autres règles sont soit identiques à des règles du CSS
 * de base (attendus/base.css), soit des neutralisations déclarées (<groupe>.neutralisations.css).
 */
final class StylesAttendusTest extends TestCase {

	/** Éléments placés comme dans la référence (appliquer.php). */
	private const HFB = [
		'entete_ordinateur' => ['primary' => ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'button-1']]],
		'pied' => [
			'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']],
			'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']],
		],
		'rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => '4-equal'], 'pied_below' => ['colonnes' => 2, 'repartition' => '2-equal']],
	];

	/** Règles d'un CSS, au format de regles.py : « [@media …] sélecteur{déclarations} ». */
	public static function regles(string $css): array {
		preg_match_all('/@media[^{]*\{|[^{}]+\{[^{}]*\}|\}/', $css, $m);
		$out = [];
		$ctx = '';
		foreach ($m[0] as $t) {
			$t = trim($t);
			if (strpos($t, '@media') === 0) {
				$ctx = trim(substr($t, 6, -1));
				continue;
			}
			if ($t === '}') {
				$ctx = '';
				continue;
			}
			[$sel, $dec] = explode('{', $t, 2);
			$out[] = ($ctx !== '' ? "[@media $ctx] " : '') . trim($sel) . '{' . trim(rtrim($dec, '}')) . '}';
		}
		return $out;
	}

	private static function lignes(string $fichier): array {
		return is_file($fichier) ? array_values(array_filter(array_map('trim', file($fichier)))) : [];
	}

	/** Groupes dont le module est écrit (ASTRA_STYLES_MODULES), par nom de fichier de référence. */
	public static function groupes(): array {
		$groupes = [];
		foreach (['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'] as $g) {
			if (in_array(str_replace('-', '_', $g), ASTRA_STYLES_MODULES, true)) {
				$groupes[$g] = [$g];
			}
		}
		return $groupes;
	}

	#[DataProvider('groupes')]
	public function testEquivalenceAvecWordPress(string $groupe): void {
		$dir = dirname(__DIR__) . '/references/styles';
		$valeurs = json_decode(file_get_contents("$dir/valeurs/$groupe.json"), true);
		$nous = self::regles(astra_styles_css($valeurs, self::HFB));
		$attendues = self::lignes("$dir/attendus/$groupe.css");
		$this->assertNotEmpty($attendues);
		$this->assertSame([], array_values(array_diff($attendues, $nous)), 'règles d\'Astra manquantes');
		$base = array_flip(self::regles(file_get_contents("$dir/attendus/base.css")));
		$neutres = array_flip(self::lignes("$dir/attendus/$groupe.neutralisations.css"));
		$autres = array_filter(array_diff($nous, $attendues), function ($r) use ($base, $neutres) {
			return !isset($base[$r]) && !isset($neutres[$r]);
		});
		$this->assertSame([], array_values($autres), 'règles ni attendues, ni de base, ni neutralisations');
	}

	public function testGroupeAbsentSansElementPlace(): void {
		// Astra n'émet le CSS d'un élément du builder que s'il est placé (is_component_loaded()).
		$valeurs = json_decode(file_get_contents(dirname(__DIR__) . '/references/styles/valeurs/menu.json'), true);
		$sans_menu = ['entete_ordinateur' => ['primary' => ['primary_right' => []]]];
		$this->assertSame('', astra_styles_css($valeurs, $sans_menu));
		$this->assertNotSame('', astra_styles_css($valeurs, []), 'menu-1 est placé par défaut (astra_hfb_defauts)');
	}
}

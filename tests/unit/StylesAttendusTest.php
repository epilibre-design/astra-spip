<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';

/**
 * Équivalence avec WordPress : pour chaque groupe, les règles produites contiennent toutes les
 * règles qu'Astra 4.13.8 ajoute ou modifie (tests/references/styles/attendus/<groupe>.css,
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

	/** Virgules de niveau zéro, hors fonctions CSS, attributs, chaînes et échappements. */
	public static function selecteurs(string $texte): array {
		$resultat = [];
		$debut = $parentheses = $crochets = 0;
		$guillemet = null;
		$echappe = false;
		for ($i = 0, $n = strlen($texte); $i < $n; $i++) {
			$c = $texte[$i];
			if ($echappe) { $echappe = false; continue; }
			if ($c === chr(92)) { $echappe = true; continue; }
			if ($guillemet !== null) {
				if ($c === $guillemet) { $guillemet = null; }
				continue;
			}
			if ($c === '"' || $c === "'") { $guillemet = $c; }
			elseif ($c === '(') { $parentheses++; }
			elseif ($c === ')') { $parentheses--; }
			elseif ($c === '[') { $crochets++; }
			elseif ($c === ']') { $crochets--; }
			elseif ($c === ',' && $parentheses === 0 && $crochets === 0) {
				$resultat[] = trim(substr($texte, $debut, $i - $debut));
				$debut = $i + 1;
			}
		}
		$resultat[] = trim(substr($texte, $debut));
		return array_values(array_filter($resultat, 'strlen'));
	}

	private static function lignes(string $fichier): array {
		return is_file($fichier) ? array_values(array_filter(array_map('trim', file($fichier)))) : [];
	}

	/** Groupes dont le module est écrit (ASTRA_STYLES_MODULES), par nom de fichier de référence. */
	public static function groupes(): array {
		$groupes = [];
		foreach (['menu', 'bouton', 'bouton-rayon', 'bouton-entete', 'identite', 'pied', 'sociaux', 'menu-mobile'] as $g) {
			if (in_array(($g === 'bouton-rayon' ? 'bouton' : str_replace('-', '_', $g)), ASTRA_STYLES_MODULES, true)) {
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
		$autres = array_filter(array_diff($nous, $attendues), function ($r) use ($base, $neutres, $attendues, $groupe) {
			if (in_array($groupe, ['bouton','bouton-rayon'], true) && self::repriseImportee($r, array_merge($attendues, array_keys($base), array_keys($neutres)))) { return false; }
			return !isset($base[$r]) && !isset($neutres[$r]);
		});
		$this->assertSame([], array_values($autres), 'règles ni attendues, ni de base, ni neutralisations');
	}

	/** N'accepte une adaptation SPIP que si média et déclarations viennent d'une règle cible vérifiée. */
	private static function repriseImportee(string $regle, array $references): bool {
		$aliases = [
			'.wp-block-button :is(a,.wp-block-button__link)' => ['.wp-block-button .wp-block-button__link'],
			'.wp-block-button :is(a,.wp-block-button__link):hover,.wp-block-button :is(a,.wp-block-button__link):focus' => ['.wp-block-button .wp-block-button__link:hover','.wp-block-button .wp-block-button__link:focus'],
			'.ast-single-post .entry-content .wp-block-button :is(a,.wp-block-button__link)' => ['.ast-single-post .entry-content .wp-block-button .wp-block-button__link'],
		];
		if (!preg_match('/^(\[@media [^\]]+\] )?(.*?)\{(.*)\}$/', $regle, $a) || !isset($aliases[$a[2]])) { return false; }
		foreach ($references as $source) {
			if (preg_match('/^(\[@media [^\]]+\] )?(.*?)\{(.*)\}$/', $source, $n)
				&& $a[1] === $n[1] && $a[3] === $n[3]
				&& !array_diff($aliases[$a[2]], self::selecteurs($n[2]))) { return true; }
		}
		return false;
	}

	public function testUneRepriseImporteeDoitGarderLesValeursEtLeMediaDeLaSource(): void {
		$alias = '.wp-block-button :is(a,.wp-block-button__link){color:red;}';
		$this->assertTrue(self::repriseImportee($alias, ['.wp-block-button .wp-block-button__link{color:red;}']));
		$this->assertFalse(self::repriseImportee($alias, ['.wp-block-button .wp-block-button__link{color:blue;}']));
		$this->assertFalse(self::repriseImportee($alias, ['[@media (max-width:921px)] .wp-block-button .wp-block-button__link{color:red;}']));
		$this->assertFalse(self::repriseImportee('.autre{color:red;}', ['.wp-block-button .wp-block-button__link{color:red;}']));
	}

	public function testGroupeAbsentSansElementPlace(): void {
		// Astra n'émet le CSS d'un élément du builder que s'il est placé (is_component_loaded()).
		$valeurs = json_decode(file_get_contents(dirname(__DIR__) . '/references/styles/valeurs/menu.json'), true);
		$sans_menu = ['entete_ordinateur' => ['primary' => ['primary_right' => []]]];
		$this->assertSame('', astra_styles_css($valeurs, $sans_menu));
		$this->assertNotSame('', astra_styles_css($valeurs, []), 'menu-1 est placé par défaut (astra_hfb_defauts)');
	}
}

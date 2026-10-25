<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';
require_once dirname(__DIR__, 2) . '/inc/styles/titres.php';
require_once __DIR__ . '/StylesAttendusTest.php';

/**
 * Graisses des titres (groupe titres, drapeau can-support-widget-and-editor-fonts). Astra modifie
 * des règles figées : l'équivalence porte sur la graisse que calcule chaque titre, par la cascade,
 * entre WordPress (css/main.min.css puis les règles de graisse capturées, attendus/<variante>.css)
 * et SPIP (css/main.min.css, css/astra-dynamic.css, puis le CSS produit).
 */
final class StylesTitresTest extends TestCase {

	private const DIR = __DIR__ . '/../references/styles';

	/** Titres comparés : sélecteurs qui les visent => spécificité (règles sans media seulement). */
	private static function elements(): array {
		$e = [];
		for ($n = 1; $n <= 6; $n++) {
			$e["h$n"] = ["h$n" => 1];
			$e[".entry-content h$n"] = ["h$n" => 1, ".entry-content h$n" => 11, ".entry-content :where(h$n)" => 10, '.entry-content :where(h1,h2,h3,h4,h5,h6)' => 10];
			$e["h$n.widget-title"] = ["h$n" => 1, '.widget-title' => 10, "h$n.widget-title" => 11];
		}
		$e['.entry-content h2.wp-block-heading'] = ['h2' => 1, '.entry-content h2' => 11, '.entry-content :where(h2)' => 10, '.entry-content :where(h1,h2,h3,h4,h5,h6)' => 10, '.wp-block-heading' => 10];
		$e['.site-title'] = ['.site-title' => 10];
		$e['.site-title a'] = ['.site-title a' => 11];
		return $e;
	}

	/** Graisse calculée de chaque titre : spécificité la plus forte, puis dernière règle. */
	private static function graisses(string $css): array {
		$r = [];
		foreach (self::elements() as $element => $selecteurs) {
			$retenue = [-1, ''];
			foreach (StylesAttendusTest::regles($css) as $regle) {
				if ($regle[0] === '[' || !preg_match('/font-weight:([^;}]+)/', $regle, $m)) {
					continue;
				}
				foreach (StylesAttendusTest::selecteurs(explode('{', $regle, 2)[0]) as $sel) {
					$sel = preg_replace('/\s+/', ' ', trim($sel));
					if (isset($selecteurs[$sel]) && $selecteurs[$sel] >= $retenue[0]) {
						$retenue = [$selecteurs[$sel], trim($m[1])];
					}
				}
			}
			// Aucune règle : la graisse est héritée.
			$r[$element] = $retenue[1] === '' ? 'inherit' : $retenue[1];
		}
		return $r;
	}

	public function testSpecificiteWhereNulle(): void {
		$css = '.entry-content :where(h2){font-weight:600;}.wp-block-heading{font-weight:400;}';
		$this->assertSame('400', self::graisses($css)['.entry-content h2.wp-block-heading'] ?? null);
		$this->assertSame('600', self::graisses('.entry-content h2{font-weight:600;}.wp-block-heading{font-weight:400;}')['.entry-content h2.wp-block-heading'] ?? null);
	}

	public function testUnBlocPeutSurchargerLaGraisseDuTitre(): void {
		$css = astra_styles_css(['font-weight-h2' => '700'], [], []);
		$this->assertSame('400', self::graisses($css . '.wp-block-heading{font-weight:400;}')['.entry-content h2.wp-block-heading']);
	}

	public static function variantes(): array {
		$v = ['titres', 'titres-drapeau', 'drapeau-polices', 'titres-vide', 'titres-vide-drapeau', 'titres-generale-vide', 'titres-generale-heritee'];
		return array_combine($v, array_map(function ($x) { return [$x]; }, $v));
	}

	#[DataProvider('variantes')]
	public function testGraissesCommeWordPress(string $variante): void {
		$valeurs = json_decode(file_get_contents(self::DIR . "/valeurs/$variante.json"), true);
		$main = file_get_contents(dirname(__DIR__, 2) . '/css/main.min.css');
		$wordpress = self::graisses($main . file_get_contents(self::DIR . "/attendus/$variante.css"));
		$spip = self::graisses($main . file_get_contents(dirname(__DIR__, 2) . '/css/astra-dynamic.css') . astra_styles_css($valeurs, [], $valeurs));
		$this->assertSame($wordpress, $spip);
	}

	public function testSansReglageRienNEstEmis(): void {
		$this->assertSame([], astra_styles_titres([], [], []));
		// Valeurs par défaut d'Astra posées explicitement : rien ne diffère du CSS figé.
		$this->assertSame([], astra_styles_titres(['headings-font-weight' => '600', 'font-weight-h1' => 'inherit'], [], []));
	}

	public function testValeursDeGarden(): void {
		// Drapeau posé, niveaux à inherit, titres à 600 : seule la neutralisation des titres des widgets.
		$s = ['headings-font-weight' => '600'];
		for ($n = 1; $n <= 6; $n++) {
			$s["font-weight-h$n"] = 'inherit';
		}
		$css = astra_styles_css($s, [], ['can-support-widget-and-editor-fonts' => false]);
		$this->assertSame('h1.widget-title,h2.widget-title,h3.widget-title{font-weight:400;}', $css);
		// Drapeau seul, sans aucune option de style : même CSS.
		$this->assertSame($css, astra_styles_css([], [], ['can-support-widget-and-editor-fonts' => false]));
	}

	public function testNiveauEgalAuCssFigeSousUneAutreGraisse(): void {
		// headings-font-weight à 700, h1 à 600 : la règle du h1 suit la règle générale, sinon le h1
		// calculerait 700. Avec et sans le drapeau.
		foreach ([[], ['can-support-widget-and-editor-fonts' => true]] as $c) {
			$css = astra_styles_css(['headings-font-weight' => '700', 'font-weight-h1' => '600'], [], $c);
			$generale = strpos($css, '.site-title a{font-weight:700;}');
			$this->assertNotFalse($generale);
			$this->assertGreaterThan($generale, strpos($css, 'h1,.entry-content :where(h1){font-weight:600;}'));
		}
	}

	public function testValeurVideDistincteDeLOptionAbsente(): void {
		// Graisse du h2 vide (importée) : Astra n'émet ni la règle du niveau ni celle du titre de
		// widget ; le h2 prend la graisse des titres, h2.widget-title celle de .widget-title (400).
		$css = astra_styles_css(['headings-font-weight' => '700', 'font-weight-h2' => ''], [], []);
		$this->assertStringContainsString('h2.widget-title{font-weight:400;}', $css);
		$this->assertStringNotContainsString('h2,.entry-content :where(h2){', $css);
		// Option absente : défaut d'Astra (inherit), titre de widget à la graisse des titres.
		$this->assertStringContainsString('h2.widget-title{font-weight:700;}', astra_styles_css(['headings-font-weight' => '700'], [], []));
	}

	public function testGraisseDesTitresVideOuHeritee(): void {
		// inherit (défaut d'avant Astra 4.0) et vide sont gardés : Astra n'émet pas la règle générale.
		$this->assertSame(['headings-font-weight' => 'inherit', 'font-weight-h2' => ''], astra_styles_normaliser(['headings-font-weight' => 'inherit', 'font-weight-h2' => '']));
		// Neutralisation par les graisses de css/main.min.css.
		$attendu = 'h1,h2,h3,h4,h5,h6,.entry-content :where(h1,h2,h3,h4,h5,h6){font-weight:inherit;}'
			. '.site-title{font-weight:400;}.site-title a{font-weight:inherit;}';
		$this->assertStringStartsWith($attendu, astra_styles_css(['headings-font-weight' => ''], [], []));
		$this->assertStringStartsWith($attendu, astra_styles_css(['headings-font-weight' => 'inherit'], [], []));
	}
}

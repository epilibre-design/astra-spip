<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';

/** Registre, normalisation commune et sécurité des styles du Customizer. */
final class StylesTest extends TestCase {

	private static function valeurs(string $groupe): array {
		return json_decode(file_get_contents(dirname(__DIR__) . "/references/styles/valeurs/$groupe.json"), true);
	}

	public function testRegistreComplet(): void {
		foreach (astra_styles_registre() as $option => $d) {
			foreach (['groupe', 'source', 'type', 'responsive'] as $champ) {
				$this->assertArrayHasKey($champ, $d, "$option : $champ");
			}
			$this->assertArrayHasKey('defaut', $d, $option);
			if (in_array($d['type'], ['taille'], true)) {
				$this->assertNotEmpty($d['unites'] ?? [], "$option : unités");
			}
			if ($d['type'] === 'enum') {
				$this->assertNotEmpty($d['valeurs'] ?? [], "$option : valeurs");
			}
		}
	}

	/** Aller-retour sans perte des structures d'origine d'Astra (valeurs du site.json de Garden). */
	public function testStructuresDAstraConservees(): void {
		foreach (['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'] as $groupe) {
			$v = array_intersect_key(self::valeurs($groupe), astra_styles_registre());
			$this->assertSame($v, astra_styles_normaliser($v), $groupe);
		}
	}

	public function testChampInvalideRetireSeul(): void {
		$v = [
			'header-menu1-bg-obj-responsive' => [
				'desktop' => ['background-color' => '#fff', 'background-repeat' => 'nimporte', 'champ-inconnu' => 1],
				'tablet' => ['background-color' => 'url(x)'],
			],
			'font-size-button' => ['desktop' => '14', 'desktop-unit' => 'pt', 'tablet' => '-3', 'mobile' => '12'],
			'button-radius-fields' => ['desktop' => ['top' => 8, 'right' => 'x', 'autre' => 2], 'desktop-unit' => 'px'],
			'font-extras-button' => ['text-transform' => 'capitalize', 'text-decoration' => 'blink'],
			'footer-social-1-color-type' => 'arc-en-ciel',
		];
		$this->assertSame([
			'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => '#fff'], 'tablet' => []],
			'font-size-button' => ['desktop' => '14', 'mobile' => '12'],
			'button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'px'],
			'font-extras-button' => ['text-transform' => 'capitalize'],
		], astra_styles_normaliser($v));
	}

	public function testValeursTabletteEtMobileConservees(): void {
		$v = ['header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222', 'mobile' => 'var(--ast-global-color-3)']];
		$this->assertSame($v, astra_styles_normaliser($v));
	}

	public function testCouleurs(): void {
		foreach (['', '#abc', '#A1B2C3', 'var(--ast-global-color-8)', 'rgba(64,191,79,0.15)', 'rgb(0, 0, 0)', 'rgba(255,255,255,1)'] as $c) {
			$this->assertTrue(astra_styles_couleur_valide($c), $c);
		}
		foreach (['red', '#abcd', 'var(--x)', 'rgba(300,0,0,1)', 'rgb(0,0,0,1)', 'rgba(0,0,0)', '#fff;}body{', 'expression(1)'] as $c) {
			$this->assertFalse(astra_styles_couleur_valide($c), $c);
		}
	}

	public function testTaillePoliceCommeAstra(): void {
		$this->assertSame('14px;font-size:0.875rem', astra_styles_taille_police(['desktop' => '14', 'desktop-unit' => 'px']));
		$this->assertSame('1.2em', astra_styles_taille_police(['desktop' => '1.2', 'desktop-unit' => 'em']));
		$this->assertSame('', astra_styles_taille_police(['desktop' => '']));
		$this->assertSame('16px;font-size:1rem', astra_styles_taille_police('', 16));
	}
}

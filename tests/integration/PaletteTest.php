<?php

use PHPUnit\Framework\TestCase;

/** Neuvième couleur de la palette : rgb() et rgba() acceptés, comme global-color-palette d'Astra. */
final class PaletteTest extends TestCase {

	public static function setUpBeforeClass(): void {
		include_spip('formulaires/configurer_astra');
	}

	protected function tearDown(): void {
		effacer_config('astra/couleur_8');
		set_request('couleur_8', null);
	}

	public function testCouleurRgbaDansLaPalette(): void {
		ecrire_config('astra/couleur_8', 'rgba(64,191,79,0.15)');
		$this->assertStringContainsString('--ast-global-color-8:rgba(64,191,79,0.15);', recuperer_fond('inclure/head-vars', []));
	}

	/** Repli d'astra_couleur() dans head-vars.html, inchangé. */
	public function testCouleurInvalideRemplaceeParLeRepli(): void {
		ecrire_config('astra/couleur_8', 'rgba(300,0,0,1)');
		$this->assertStringContainsString('--ast-global-color-8:#000000;', recuperer_fond('inclure/head-vars', []));
	}

	public function testVerifierRefuseUneCouleurInvalide(): void {
		set_request('couleur_8', 'vert');
		$this->assertArrayHasKey('couleur_8', formulaires_configurer_astra_verifier_dist());
		set_request('couleur_8', 'rgba(64, 191, 79, 0.15)');
		$this->assertArrayNotHasKey('couleur_8', formulaires_configurer_astra_verifier_dist());
	}

	public function testChampTexteSeulementPourLaNeuvieme(): void {
		$html = recuperer_fond('formulaires/configurer_astra', ['editable' => true]);
		$this->assertMatchesRegularExpression('/<input type="text"[^>]*name="couleur_8"/', $html);
		$this->assertMatchesRegularExpression('/<input type="color"[^>]*name="couleur_0"/', $html);
	}
}

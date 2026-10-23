<?php

use PHPUnit\Framework\TestCase;

/** Formulaire configurer_astra_styles : charger, vérifier, traiter, rendu des champs. */
final class StylesFormulaireTest extends TestCase {

	public static function setUpBeforeClass(): void {
		include_spip('formulaires/configurer_astra_styles');
	}

	protected function tearDown(): void {
		effacer_config('astra/styles');
		set_request('s', null);
	}

	public function testChargerDonneLesReglages(): void {
		ecrire_config('astra/styles', ['button-color' => '#123456']);
		$this->assertSame(['s' => ['button-color' => '#123456']], formulaires_configurer_astra_styles_charger_dist());
	}

	public function testVerifierSignaleLesValeursRefusees(): void {
		set_request('s', ['button-color' => 'rouge', 'button-h-color' => '#ffffff']);
		$erreurs = formulaires_configurer_astra_styles_verifier_dist();
		$this->assertArrayHasKey('s_button-color', $erreurs);
		$this->assertArrayNotHasKey('s_button-h-color', $erreurs);
		$this->assertArrayHasKey('message_erreur', $erreurs);
	}

	public function testTraiterGardeTabletteEtMobile(): void {
		ecrire_config('astra/styles', [
			'header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222', 'mobile' => '#333333'],
		]);
		set_request('s', [
			'header-menu1-color-responsive' => ['desktop' => 'var(--ast-global-color-0)'],
			'button-color' => '',
		]);
		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertSame([
			'header-menu1-color-responsive' => ['desktop' => 'var(--ast-global-color-0)', 'tablet' => '#222222', 'mobile' => '#333333'],
		], lire_config('astra/styles'));
	}

	public function testRenduDesChamps(): void {
		$html = recuperer_fond('formulaires/configurer_astra_styles', ['editable' => true, 's' => ['button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'em']]]);
		$this->assertSame(12, substr_count($html, '<fieldset'));
		$this->assertStringContainsString('name="s[header-menu1-color-responsive][desktop]"', $html);
		$this->assertStringContainsString('name="s[button-radius-fields][desktop][top]" value="8"', $html);
		$this->assertMatchesRegularExpression('~name="s\[button-radius-fields\]\[desktop-unit\]".*?<option value="em" selected="selected">~s', $html);
		$this->assertStringContainsString('name="s[header-menu1-bg-obj-responsive][desktop][background-color]"', $html);
		$this->assertStringContainsString('list="astra-styles-palette"', $html);
	}
}

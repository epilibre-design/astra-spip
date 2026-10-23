<?php

use PHPUnit\Framework\TestCase;

/** Styles du Customizer dans l'en-tête HTML : bloc émis seulement avec un réglage, après le builder. */
final class StylesRenduTest extends TestCase {

	protected function tearDown(): void {
		effacer_config('astra/styles');
		effacer_config('astra/hfb');
	}

	public function testRienSansReglage(): void {
		effacer_config('astra/styles');
		$html = recuperer_fond('inclure/head-vars', []);
		$this->assertStringNotContainsString('astra-styles-inline-css', $html);
		$this->assertStringNotContainsString('@font-face', $html);
	}

	public function testReglageInvalideSansBloc(): void {
		ecrire_config('astra/styles', ['button-color' => 'red;}body{display:none']);
		$this->assertStringNotContainsString('astra-styles-inline-css', recuperer_fond('inclure/head-vars', []));
	}

	public function testBlocApresLeBuilder(): void {
		ecrire_config('astra/hfb', ['rangees' => ['entete_primary' => ['hauteur' => 100]]]);
		ecrire_config('astra/styles', ['button-color' => '#123456']);
		$html = recuperer_fond('inclure/head-vars', []);
		$builder = strpos($html, 'id="astra-hfb-inline-css"');
		$styles = strpos($html, 'id="astra-styles-inline-css"');
		$this->assertNotFalse($builder);
		$this->assertNotFalse($styles);
		$this->assertGreaterThan($builder, $styles);
		$this->assertStringContainsString('.wp-block-button .wp-block-button__link{color:#123456;}', $html);
	}
}

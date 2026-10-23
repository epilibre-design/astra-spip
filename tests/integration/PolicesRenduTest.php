<?php

use PHPUnit\Framework\TestCase;

/** Polices locales : @font-face émis pour une police fournie, rien pour une pile système. */
final class PolicesRenduTest extends TestCase {

	protected function tearDown(): void {
		effacer_config('astra/police_texte');
		effacer_config('astra/styles');
	}

	public function testPoliceLocaleChargee(): void {
		ecrire_config('astra/police_texte', "'Raleway', sans-serif");
		ecrire_config('astra/styles', ['font-family-button' => "'Poppins', sans-serif"]);
		$html = recuperer_fond('inclure/head-vars', []);
		$this->assertSame(5, substr_count($html, '@font-face'));
		preg_match('~src:url\(([^)?]+)[^)]*\) format~', $html, $m);
		$this->assertStringEndsWith('fonts/raleway/raleway-latin-400-normal.woff2', $m[1]);
		$this->assertFileExists(_DIR_PLUGINS . 'astra/fonts/raleway/raleway-latin-400-normal.woff2');
		$this->assertLessThan(strpos($html, 'astra-theme-css-inline-css'), strpos($html, 'astra-polices-inline-css'));
	}

	public function testPileSystemeSansFontFace(): void {
		ecrire_config('astra/police_texte', 'Georgia, serif');
		$html = recuperer_fond('inclure/head-vars', []);
		$this->assertStringNotContainsString('@font-face', $html);
		$this->assertStringNotContainsString('astra-polices-inline-css', $html);
	}

	public function testFormulaireProposeLesPolicesLocales(): void {
		$html = recuperer_fond('formulaires/configurer_astra', ['editable' => true]);
		$this->assertStringContainsString('<option value="&#039;Raleway&#039;, sans-serif">Raleway</option>', $html);
		$this->assertStringContainsString('list="astra-polices-locales"', $html);
	}
}

<?php

use PHPUnit\Framework\TestCase;

final class PluginActifTest extends TestCase {

	public function testPluginAstraActif(): void {
		$this->assertTrue(test_plugin_actif('astra'));
	}

	public function testEnTeteParDefautInchange(): void {
		effacer_config('astra/hfb');
		$html = recuperer_fond('inclure/header', ['type_page' => 'sommaire']);
		$this->assertStringContainsString('site-primary-header-wrap', $html);
		$this->assertStringContainsString('ast-builder-menu-1', $html);
		$this->assertStringNotContainsString('ast-above-header-wrap', $html);
	}
	public function testClasseVersionTheme(): void {
		$ancien = lire_config('astra/compatibilite', null);
		try {
			ecrire_config('astra/compatibilite', ['theme-auto-version' => '4.11.0']);
			$html = recuperer_fond('404', ['type_page' => '404']);
			$dom = new DOMDocument();
			@$dom->loadHTML($html);
			$classes = preg_split('/\s+/', $dom->getElementsByTagName('body')->item(0)->getAttribute('class'));
			$this->assertSame(1, count(array_keys($classes, 'astra-4.13.8', true)));
			$this->assertNotContains('astra-4.11.0', $classes);
			$this->assertStringContainsString('astra-4.13.8', astra_page_classes_body(7, astra_page_reglages_defaut(), false));
		} finally {
			if ($ancien === null) { effacer_config('astra/compatibilite'); }
			else { ecrire_config('astra/compatibilite', $ancien); }
		}
	}

}

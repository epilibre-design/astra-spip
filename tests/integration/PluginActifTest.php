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
}

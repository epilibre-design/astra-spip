<?php

use PHPUnit\Framework\TestCase;

final class HfbConfigurationTest extends TestCase {

	protected function tearDown(): void {
		effacer_config('astra/hfb');
	}

	public function testConfigurationEnregistreeEstRelueEtNormalisee(): void {
		ecrire_config('astra/hfb', [
			'entete_ordinateur' => ['above' => ['above_center' => ['html-1', 'copyright']]],
			'elements' => ['entete' => ['html-1' => ['texte' => 'Bonjour']]],
		]);
		$config = astra_hfb_config();
		$this->assertSame(['html-1'], $config['entete_ordinateur']['above']['above_center']);
		$this->assertSame(['texte' => 'Bonjour'], $config['elements']['entete']['html-1']);
		$this->assertSame(['above', 'primary'], array_column(astra_hfb_rangees('entete_ordinateur'), 'rangee'));
	}

	public function testChangementDeConfigurationPrisEnCompteDansLeMemeProcessus(): void {
		$this->assertSame(['primary'], array_column(astra_hfb_rangees('entete_ordinateur'), 'rangee'));
		ecrire_config('astra/hfb', ['entete_ordinateur' => ['below' => ['below_center' => ['search']]]]);
		$this->assertSame(['primary', 'below'], array_column(astra_hfb_rangees('entete_ordinateur'), 'rangee'));
	}

	public function testListeVideEnregistreeRetireLeMenu(): void {
		ecrire_config('astra/hfb', ['entete_ordinateur' => ['primary' => ['primary_right' => []]]]);
		$rangees = astra_hfb_rangees('entete_ordinateur');
		$this->assertSame([], $rangees[0]['zones']['right']);
	}

	public function testPanneauMobileSuitLaConfiguration(): void {
		$this->assertSame(['mobile-menu'], array_column(astra_hfb_panneau(), 'id'));
		ecrire_config('astra/hfb', ['entete_mobile' => ['popup' => ['popup_content' => ['search', 'mobile-menu']]]]);
		$this->assertSame(['search', 'mobile-menu'], array_column(astra_hfb_panneau(), 'id'));
	}
}

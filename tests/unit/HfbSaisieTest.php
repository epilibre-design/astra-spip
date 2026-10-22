<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_hfb_saisie.php';

final class HfbSaisieTest extends TestCase {

	public function testZonesProposees(): void {
		$this->assertSame(['above_left', 'above_center', 'above_right', 'primary_left', 'primary_center', 'primary_right', 'below_left', 'below_center', 'below_right'], astra_hfb_zones_saisie('ordinateur'));
		$this->assertSame('popup_content', array_slice(astra_hfb_zones_saisie('mobile'), -1)[0]);
		$this->assertCount(18, astra_hfb_zones_saisie('pied'));
	}

	public function testElementsParBuilder(): void {
		$this->assertNotContains('mobile-trigger', astra_hfb_elements_saisie('ordinateur'));
		$this->assertContains('mobile-trigger', astra_hfb_elements_saisie('mobile'));
		$this->assertContains('mobile-menu', astra_hfb_elements_saisie('mobile'));
		$this->assertSame(['html-1', 'html-2', 'social-icons-1', 'widget-1', 'widget-2', 'widget-3', 'widget-4', 'copyright', 'menu'], astra_hfb_elements_saisie('pied'));
	}

	public function testZoneAutorisee(): void {
		$this->assertTrue(astra_hfb_zone_autorisee('mobile', 'mobile-menu', 'popup_content'));
		$this->assertFalse(astra_hfb_zone_autorisee('mobile', 'mobile-trigger', 'popup_content'));
		$this->assertFalse(astra_hfb_zone_autorisee('ordinateur', 'copyright', 'primary_left'));
		$this->assertFalse(astra_hfb_zone_autorisee('ordinateur', 'logo', 'popup_content'));
		$this->assertFalse(astra_hfb_zone_autorisee('pied', 'copyright', 'below_7'));
	}

	public function testAllerRetourAvecLaConfigurationParDefaut(): void {
		$config = astra_hfb_normaliser([]);
		$saisie = astra_hfb_saisie_depuis_config($config);
		$this->assertSame('primary_left', $saisie['place']['ordinateur']['logo']);
		$this->assertSame('primary_right', $saisie['place']['ordinateur']['menu-1']);
		$this->assertSame('', $saisie['place']['ordinateur']['search']);
		$this->assertSame('popup_content', $saisie['place']['mobile']['mobile-menu']);
		$this->assertSame('below_1', $saisie['place']['pied']['copyright']);
		foreach (['ordinateur' => 'entete_ordinateur', 'mobile' => 'entete_mobile', 'pied' => 'pied'] as $builder => $cle) {
			$this->assertSame($config[$cle], astra_hfb_placements_depuis_saisie($saisie['place'][$builder], $saisie['ordre'][$builder], $builder), $builder);
		}
	}

	public function testPlacementsOrdonnesEtZonesExplicites(): void {
		$items = astra_hfb_placements_depuis_saisie(
			['menu-1' => 'primary_right', 'search' => 'primary_right', 'button-1' => 'primary_right', 'logo' => 'primary_left', 'html-1' => 'nulle_part'],
			['menu-1' => '2', 'search' => '1', 'button-1' => '2'],
			'ordinateur'
		);
		$this->assertSame(['search', 'menu-1', 'button-1'], $items['primary']['primary_right']);
		$this->assertSame([], $items['above']['above_left']);
		$this->assertArrayHasKey('below_right_center', $items['below']);
		$this->assertNotContains('html-1', array_merge(...array_values(array_map(function ($r) { return array_merge(...array_values($r)); }, $items))));
	}

	public function testVerificationDesPlacements(): void {
		$saisie = astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]));
		$saisie['place']['ordinateur']['logo'] = 'popup_content';
		$saisie['place']['ordinateur']['copyright'] = 'primary_left';
		$saisie['place']['pied']['html-1'] = 'below_3';
		$saisie['place']['mobile']['mobile-trigger'] = '';
		$erreurs = astra_hfb_verifier_saisie($saisie);
		$this->assertArrayHasKey('place_ordinateur_logo', $erreurs);
		$this->assertArrayNotHasKey('place_ordinateur_copyright', $erreurs, 'Élément sans champ dans ce builder : ignoré');
		$this->assertNotContains('copyright', astra_hfb_placements_depuis_saisie($saisie['place']['ordinateur'], $saisie['ordre']['ordinateur'], 'ordinateur')['primary']['primary_left']);
		$this->assertArrayHasKey('place_pied_html-1', $erreurs);
		$this->assertArrayHasKey('place_mobile_mobile-trigger', $erreurs);
	}

	public function testVerificationDesRangees(): void {
		$saisie = astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]));
		$saisie['rangees']['pied_primary']['colonnes'] = '4';
		$saisie['rangees']['pied_primary']['repartition'] = '3-equal';
		$saisie['rangees']['entete_above']['hauteur'] = 'grand';
		$saisie['rangees']['entete_below']['fond'] = 'red';
		$erreurs = astra_hfb_verifier_saisie($saisie);
		$this->assertArrayHasKey('rangees_pied_primary_repartition', $erreurs);
		$this->assertArrayHasKey('rangees_entete_above_hauteur', $erreurs);
		$this->assertArrayHasKey('rangees_entete_below_fond', $erreurs);
		$this->assertSame([], astra_hfb_verifier_saisie(astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]))));
	}

	public function testReglagesDElementsAllerRetour(): void {
		$config = astra_hfb_normaliser(['elements' => [
			'entete' => ['menu-2' => ['source' => 'rubrique', 'id' => 4], 'social-icons-1' => ['reseaux' => [['reseau' => 'mastodon', 'url' => 'https://m.example/@a']]]],
			'pied' => ['widget-1' => ['blocs' => ['texte', 'recherche'], 'titre' => 'T', 'texte' => 'X']],
		]]);
		$saisie = astra_hfb_saisie_depuis_config($config);
		$this->assertSame('rubrique:4', $saisie['elements']['entete']['menu-2']['source_id']);
		$this->assertSame('oui', $saisie['elements']['entete']['social-icons-1']['reseaux']['mastodon']['afficher']);
		$this->assertSame('', $saisie['elements']['entete']['social-icons-1']['reseaux']['facebook']['afficher']);
		$elements = astra_hfb_elements_depuis_saisie($saisie['elements']);
		$this->assertSame(['source' => 'rubrique', 'id' => 4], $elements['entete']['menu-2']);
		$this->assertSame([['reseau' => 'mastodon', 'url' => 'https://m.example/@a']], $elements['entete']['social-icons-1']['reseaux']);
		$this->assertSame(['recherche', 'texte'], $elements['pied']['widget-1']['blocs'], 'Blocs dans l\'ordre du registre');
		$this->assertSame('horizontal', $elements['pied']['menu']['disposition']);
	}

	public function testSourceDeMenuInvalideRevientALaRacine(): void {
		$saisie = astra_hfb_saisie_depuis_config(astra_hfb_normaliser([]));
		$saisie['elements']['entete']['menu-1']['source_id'] = 'sql:1';
		$this->assertSame(['source' => 'racine', 'id' => 0], astra_hfb_elements_depuis_saisie($saisie['elements'])['entete']['menu-1']);
	}

	public function testRangeesDepuisSaisie(): void {
		$r = astra_hfb_rangees_depuis_saisie(['entete_above' => ['hauteur' => ' 42 ', 'fond' => '#abc', 'bordure' => '0'], 'pied_primary' => ['colonnes' => '4', 'repartition' => '4-equal', 'hauteur' => '']]);
		$this->assertSame(['hauteur' => 42, 'fond' => '#abc', 'bordure' => 0], $r['entete_above']);
		$this->assertSame(['colonnes' => 4, 'repartition' => '4-equal', 'hauteur' => '', 'fond' => ''], $r['pied_primary']);
		$this->assertSame(astra_hfb_defauts()['rangees']['pied_below'], $r['pied_below']);
	}
}

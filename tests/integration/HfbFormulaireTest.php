<?php

use PHPUnit\Framework\TestCase;

/**
 * Formulaire configurer_astra_hfb : charger, vérifier, traiter.
 */
final class HfbFormulaireTest extends TestCase {

	public static function setUpBeforeClass(): void {
		include_spip('formulaires/configurer_astra_hfb');
	}

	protected function tearDown(): void {
		effacer_config('astra/hfb');
		foreach (['place', 'ordre', 'rangees', 'menu_mobile', 'elements'] as $champ) {
			set_request($champ, null);
		}
	}

	private function poster(array $valeurs): void {
		foreach ($valeurs as $champ => $valeur) {
			set_request($champ, $valeur);
		}
	}

	public function testChargerDonneLesEmplacementsActuels(): void {
		$valeurs = formulaires_configurer_astra_hfb_charger_dist();
		$this->assertSame('primary_left', $valeurs['place']['ordinateur']['logo']);
		$this->assertSame('popup_content', $valeurs['place']['mobile']['mobile-menu']);
		$this->assertSame('below_1', $valeurs['place']['pied']['copyright']);
		$this->assertSame('dropdown', $valeurs['menu_mobile']);
	}

	public function testVerifierSignaleLesErreurs(): void {
		$valeurs = formulaires_configurer_astra_hfb_charger_dist();
		$valeurs['place']['ordinateur']['logo'] = 'popup_content';
		$valeurs['place']['pied']['html-1'] = 'primary_5';
		$valeurs['place']['mobile']['mobile-trigger'] = '';
		$valeurs['rangees']['pied_below']['repartition'] = '2-equal';
		$this->poster($valeurs);
		$erreurs = formulaires_configurer_astra_hfb_verifier_dist();
		$this->assertArrayHasKey('message_erreur', $erreurs);
		foreach (['place_ordinateur_logo', 'place_pied_html-1', 'place_mobile_mobile-trigger', 'rangees_pied_below_repartition'] as $champ) {
			$this->assertArrayHasKey($champ, $erreurs, $champ);
			$this->assertStringNotContainsString('hfb_erreur', $erreurs[$champ], 'Message traduit');
		}
	}

	public function testTraiterEnregistreLaConfiguration(): void {
		$valeurs = formulaires_configurer_astra_hfb_charger_dist();
		$valeurs['place']['ordinateur']['menu-1'] = 'primary_left';
		$valeurs['ordre']['ordinateur']['menu-1'] = '2';
		$valeurs['place']['ordinateur']['search'] = 'primary_left';
		$valeurs['ordre']['ordinateur']['search'] = '1';
		$valeurs['place']['pied']['copyright'] = '';
		$valeurs['place']['pied']['html-1'] = 'primary_2';
		$valeurs['rangees']['pied_primary']['colonnes'] = '2';
		$valeurs['rangees']['pied_primary']['repartition'] = '2-lheavy';
		$valeurs['menu_mobile'] = 'off-canvas';
		$valeurs['elements']['pied']['html-1']['texte'] = 'Bonjour';
		$valeurs['elements']['entete']['menu-2']['source_id'] = 'rubrique:1';
		$this->poster($valeurs);
		$this->assertSame([], formulaires_configurer_astra_hfb_verifier_dist());
		$retour = formulaires_configurer_astra_hfb_traiter_dist();
		$this->assertArrayHasKey('message_ok', $retour);

		$enregistre = lire_config('astra/hfb');
		$this->assertSame(['logo', 'search', 'menu-1'], $enregistre['entete_ordinateur']['primary']['primary_left']);
		$this->assertSame([], $enregistre['entete_ordinateur']['primary']['primary_right'], 'Zone vide enregistrée explicitement');
		$this->assertSame([], $enregistre['pied']['below']['below_1']);
		$this->assertSame('off-canvas', $enregistre['menu_mobile']);
		$this->assertSame(2, $enregistre['rangees']['pied_primary']['colonnes']);

		$config = astra_hfb_config();
		$this->assertSame(['primary'], array_column(astra_hfb_rangees('pied'), 'rangee'), 'Copyright retiré : rangée basse masquée');
		$this->assertSame('Bonjour', $config['elements']['pied']['html-1']['texte']);
		$this->assertSame(['source' => 'rubrique', 'id' => 1], $config['elements']['entete']['menu-2']);
	}
}

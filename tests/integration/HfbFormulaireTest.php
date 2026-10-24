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

	public function testRepartitionParAppareil(): void {
		$valeurs = formulaires_configurer_astra_hfb_charger_dist();
		$valeurs['place']['pied']['html-1'] = 'primary_1';
		$valeurs['rangees']['pied_primary']['colonnes'] = '4';
		$valeurs['rangees']['pied_primary']['repartition'] = ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'];
		$this->poster($valeurs);
		$this->assertSame([], formulaires_configurer_astra_hfb_verifier_dist());
		formulaires_configurer_astra_hfb_traiter_dist();
		$this->assertSame(['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'], lire_config('astra/hfb/rangees/pied_primary/repartition'));
		$pied = recuperer_fond('inclure/footer', ['type_page' => 'sommaire']);
		$this->assertStringContainsString('ast-builder-grid-row-4-equal ast-builder-grid-row-tablet-full ast-builder-grid-row-mobile-full', $pied);
	}

	public function testRenduDesSelecteursDeRepartition(): void {
		ecrire_config('astra/hfb', ['rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => ['desktop' => '4-equal', 'tablet' => '2-equal', 'mobile' => 'full']]]]);
		$html = recuperer_fond('formulaires/configurer_astra_hfb', ['editable' => true] + formulaires_configurer_astra_hfb_charger_dist());
		$x = HfbRenduTest::xpath($html);
		$choisi = function (string $appareil) use ($x): string {
			$n = $x->query('//select[@name="rangees[pied_primary][repartition][' . $appareil . ']"]/option[@selected]');
			return $n->length === 1 ? $n->item(0)->getAttribute('value') : '';
		};
		$this->assertSame('4-equal', $choisi('desktop'));
		$this->assertSame('2-equal', $choisi('tablet'));
		$this->assertSame('full', $choisi('mobile'));
		ecrire_config('astra/hfb', ['rangees' => ['pied_below' => ['colonnes' => 1, 'repartition' => 'full']]]);
		$x = HfbRenduTest::xpath(recuperer_fond('formulaires/configurer_astra_hfb', ['editable' => true] + formulaires_configurer_astra_hfb_charger_dist()));
		$n = $x->query('//select[@name="rangees[pied_above][repartition][mobile]"]/option[@selected]');
		$this->assertSame('full', $n->item(0)->getAttribute('value'), 'Valeur simple : mobile en pleine largeur');
	}

	public function testAllerRetourDesRepartitionsLourdesAQuatreColonnes(): void {
		// Valeur simple de la version 1.2.0 : elle vaut pour l'ordinateur et la tablette. Le
		// formulaire doit la reproposer telle quelle, et l'enregistrement ne rien changer au rendu.
		$classes = function (): string {
			preg_match('/ast-builder-grid-row-4-[a-z]+ ast-builder-grid-row-tablet-[a-z0-9-]+ ast-builder-grid-row-mobile-[a-z0-9-]+/', recuperer_fond('inclure/footer', ['type_page' => 'sommaire']), $m);
			return $m[0] ?? '';
		};
		foreach (['4-lheavy', '4-rheavy'] as $repartition) {
			effacer_config('astra/hfb');
			ecrire_config('astra/hfb', [
				'pied' => ['primary' => ['primary_1' => ['html-1']]],
				'rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => $repartition]],
			]);
			$avant = $classes();
			$this->assertSame("ast-builder-grid-row-$repartition ast-builder-grid-row-tablet-$repartition ast-builder-grid-row-mobile-full", $avant);
			$valeurs = formulaires_configurer_astra_hfb_charger_dist();
			$x = HfbRenduTest::xpath(recuperer_fond('formulaires/configurer_astra_hfb', ['editable' => true] + $valeurs));
			$postee = [];
			foreach (['desktop', 'tablet', 'mobile'] as $appareil) {
				$n = $x->query('//select[@name="rangees[pied_primary][repartition][' . $appareil . ']"]/option[@selected]');
				$postee[$appareil] = $n->length === 1 ? $n->item(0)->getAttribute('value') : '';
			}
			$valeurs['rangees']['pied_primary']['repartition'] = $postee;
			$this->poster($valeurs);
			$this->assertSame([], formulaires_configurer_astra_hfb_verifier_dist(), $repartition);
			formulaires_configurer_astra_hfb_traiter_dist();
			$this->assertSame($avant, $classes(), $repartition);
		}
	}
}

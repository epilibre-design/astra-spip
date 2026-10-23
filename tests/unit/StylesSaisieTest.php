<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles_saisie.php';

/** Saisie des styles : sections, libellés, fusion sans perte des valeurs tablette et mobile. */
final class StylesSaisieTest extends TestCase {

	public function testChaqueOptionDansUneSeuleSection(): void {
		foreach (array_keys(astra_styles_registre()) as $option) {
			$sections = array_filter(astra_styles_sections(), function ($motif) use ($option) {
				return preg_match($motif, $option);
			});
			$this->assertCount(1, $sections, $option);
		}
	}

	public function testLibellesTraduits(): void {
		$fr = include dirname(__DIR__, 2) . '/lang/astra_fr.php';
		$en = include dirname(__DIR__, 2) . '/lang/astra_en.php';
		foreach (array_keys(astra_styles_registre()) as $option) {
			$cle = substr(astra_styles_libelle($option), strlen('astra:'));
			$this->assertArrayHasKey($cle, $fr, $option);
			$this->assertArrayHasKey($cle, $en, $option);
		}
		foreach (array_keys(astra_styles_sections()) as $section) {
			$this->assertArrayHasKey("styles_section_$section", $fr);
			$this->assertArrayHasKey("styles_section_$section", $en);
		}
	}

	public function testChampsDUneSection(): void {
		$styles = ['header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222']];
		$champs = astra_styles_champs($styles, 'menu_1');
		$this->assertSame('header-menu1-color-responsive', $champs[0]['option']);
		$this->assertSame('#111111', $champs[0]['valeur']);
		$this->assertSame('astra:style_menu_color_responsive', $champs[0]['libelle']);
		$this->assertCount(12, $champs);
		$this->assertSame('s[header-menu1-color-responsive][desktop]', $champs[0]['nom']);
		$identite = array_column(astra_styles_champs(['display-site-title-responsive' => ['desktop' => false]], 'identite'), 'valeur', 'option');
		$this->assertSame('0', $identite['display-site-title-responsive']);
	}

	public function testFusionGardeTabletteEtMobile(): void {
		$existant = [
			'header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222', 'mobile' => '#333333'],
			'font-size-button' => ['desktop' => '14', 'tablet' => '13', 'desktop-unit' => 'px', 'tablet-unit' => 'px'],
			'button-radius-fields' => ['desktop' => ['top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8], 'tablet' => ['top' => 4], 'desktop-unit' => 'px'],
			'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => '', 'background-repeat' => 'repeat'], 'mobile' => ['background-color' => '#000']],
		];
		$saisie = [
			'header-menu1-color-responsive' => ['desktop' => '#444444'],
			'font-size-button' => ['desktop' => '1.2', 'desktop-unit' => 'em'],
			'button-radius-fields' => ['desktop' => ['top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10']],
			'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => '#ffffff']],
			'button-color' => '',
		];
		$this->assertSame([
			'header-menu1-color-responsive' => ['desktop' => '#444444', 'tablet' => '#222222', 'mobile' => '#333333'],
			'font-size-button' => ['desktop' => '1.2', 'tablet' => '13', 'desktop-unit' => 'em', 'tablet-unit' => 'px'],
			'button-radius-fields' => ['desktop' => ['top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10'], 'tablet' => ['top' => 4], 'desktop-unit' => 'px'],
			'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => '#ffffff', 'background-repeat' => 'repeat'], 'mobile' => ['background-color' => '#000']],
		], astra_styles_saisie_fusionner($existant, $saisie));
	}

	public function testOptionVideEffaceeSeulementSiEnregistree(): void {
		$this->assertSame(['button-color' => ''], astra_styles_saisie_fusionner(['button-color' => '#123456'], ['button-color' => '']));
		$this->assertSame([], astra_styles_saisie_fusionner([], ['button-color' => '', 'header-menu1-color-responsive' => ['desktop' => '']]));
	}

	public function testValeursRefusees(): void {
		$this->assertSame(
			['button-color', 'font-size-button'],
			astra_styles_saisie_refusees([
				'button-color' => 'rouge',
				'button-h-color' => '',
				'font-size-button' => ['desktop' => '14', 'desktop-unit' => 'pt'],
				'header-menu1-color-responsive' => ['desktop' => 'var(--ast-global-color-3)'],
			])
		);
	}
}

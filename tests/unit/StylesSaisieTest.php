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

	public function testPremiereSaisieDUnAlignementMobileSeul(): void {
		// Alignement saisi pour le mobile seul : enregistré dès la première saisie.
		$saisie = ['footer-copyright-alignment' => ['desktop' => '', 'tablet' => '', 'mobile' => 'right']];
		$this->assertSame($saisie, astra_styles_saisie_fusionner([], $saisie));
		$this->assertSame([], astra_styles_saisie_fusionner([], ['footer-copyright-alignment' => ['desktop' => '', 'tablet' => '', 'mobile' => '']]));
	}

	public function testMargesDesRangeesSaisiesSurLesTroisAppareils(): void {
		// Marges des rangées : côtés et unités des trois appareils saisis ; côtés absents de la
		// saisie gardés.
		$existant = ['section-primary-footer-builder-padding' => ['desktop' => ['top' => '80', 'right' => ''], 'tablet' => ['top' => '80'], 'desktop-unit' => 'px', 'tablet-unit' => 'px']];
		$saisie = ['section-primary-footer-builder-padding' => [
			'desktop' => ['top' => '70'], 'desktop-unit' => 'px',
			'tablet' => ['right' => '2'], 'tablet-unit' => 'em',
			'mobile' => ['top' => '60', 'right' => '30', 'bottom' => '30', 'left' => '30'], 'mobile-unit' => '%',
		]];
		$this->assertSame(['section-primary-footer-builder-padding' => [
			'desktop' => ['top' => '70', 'right' => ''], 'tablet' => ['top' => '80', 'right' => '2'], 'desktop-unit' => 'px', 'tablet-unit' => 'em',
			'mobile' => ['top' => '60', 'right' => '30', 'bottom' => '30', 'left' => '30'], 'mobile-unit' => '%',
		]], astra_styles_saisie_fusionner($existant, $saisie));
		// Première saisie : gardée dès qu'un côté d'un appareil est renseigné ; des unités seules ne
		// sont pas une valeur.
		$mobile = ['section-below-footer-builder-margin' => ['desktop' => ['top' => ''], 'desktop-unit' => 'px', 'mobile' => ['top' => '-5'], 'mobile-unit' => 'px']];
		$this->assertSame($mobile, astra_styles_saisie_fusionner([], $mobile));
		$this->assertSame([], astra_styles_saisie_fusionner([], ['section-below-footer-builder-margin' => ['desktop' => ['top' => ''], 'desktop-unit' => 'px', 'tablet-unit' => 'em']]));
	}

	public function testChampsDesMargesGardentLesUnitesAbsentes(): void {
		$option = 'section-primary-header-builder-padding';
		foreach (ASTRA_STYLES_APPAREILS as $appareil) {
			$champs = array_column(astra_styles_champs([$option => [$appareil => ['top' => '7']]], 'marges_rangees'), null, 'option');
			$unites = array_column($champs[$option]['appareils'], 'unite', 'appareil');
			$this->assertSame('', $unites[$appareil]);
		}
		$champs = array_column(astra_styles_champs([], 'marges_rangees'), null, 'option');
		$this->assertSame(['px', 'px', 'px'], array_column($champs[$option]['appareils'], 'unite'));
		$champs = array_column(astra_styles_champs([$option => ['tablet-unit' => 'em']], 'marges_rangees'), null, 'option');
		$this->assertSame('em', $champs[$option]['appareils'][1]['unite']);
	}

	public function testChampsDesMargesParAppareil(): void {
		$champs = astra_styles_champs(['section-header-builder-layout-margin' => ['mobile' => ['bottom' => '-4'], 'mobile-unit' => 'em']], 'marges_rangees');
		$this->assertCount(15, $champs);
		$marge = array_column($champs, null, 'option')['section-header-builder-layout-margin'];
		$this->assertTrue($marge['signe']);
		$this->assertSame(['desktop', 'tablet', 'mobile'], array_column($marge['appareils'], 'appareil'));
		$this->assertSame(['appareil' => 'mobile', 'nom' => 's[section-header-builder-layout-margin][mobile]', 'valeur' => ['bottom' => '-4'], 'nom_unite' => 's[section-header-builder-layout-margin][mobile-unit]', 'unite' => 'em'], $marge['appareils'][2]);
		$this->assertFalse(array_column($champs, null, 'option')['section-primary-footer-builder-padding']['signe']);
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
	public function testCinqChampsIdentiteSurTroisAppareils(): void {
		$champs = array_column(astra_styles_champs(['display-site-title-responsive'=>['tablet'=>false]], 'identite'), null, 'option');
		$n = 0;
		foreach (['ast-header-responsive-logo-width','font-size-site-title','font-size-site-tagline','display-site-title-responsive','display-site-tagline-responsive'] as $o) {
			$this->assertSame(['desktop','tablet','mobile'],array_column($champs[$o]['appareils'],'appareil'));
			$n += count($champs[$o]['appareils']);
		}
		$this->assertSame(15,$n);
		$this->assertSame('0',$champs['display-site-title-responsive']['appareils'][1]['valeur']);
		$this->assertSame('', $champs['font-size-site-title']['appareils'][1]['unite']);
		$this->assertSame([], $champs['header-color-site-title']['appareils']);
	}

	public function testPremiereSaisieIdentiteMobileSeule(): void {
		$v=['font-size-site-title'=>['mobile'=>'16','mobile-unit'=>'']];
		$this->assertSame(['font-size-site-title'=>['mobile'=>'16']],astra_styles_saisie_fusionner([],$v));
		$this->assertSame([],astra_styles_saisie_fusionner([],['font-size-site-title'=>['mobile'=>'','mobile-unit'=>'px']]));
		$avant=['font-size-site-title'=>['desktop'=>'20','tablet'=>'18','mobile'=>'16','desktop-unit'=>'px']];
		$r=astra_styles_saisie_fusionner($avant,['font-size-site-title'=>['mobile'=>'','mobile-unit'=>'']]);
		$this->assertSame('18',$r['font-size-site-title']['tablet']);
		$this->assertArrayNotHasKey('mobile-unit',$r['font-size-site-title']);
	}

	public function testPostIdentiqueGardeLeTypeDesValeursImportees(): void {
		$avant=['display-site-title-responsive'=>['desktop'=>true,'tablet'=>false,'mobile'=>0], 'font-size-site-title'=>['desktop'=>20,'tablet'=>18.5]];
		$post=['display-site-title-responsive'=>['desktop'=>'1','tablet'=>'0','mobile'=>'0'],'font-size-site-title'=>['desktop'=>'20','tablet'=>'18.5']];
		$this->assertSame($avant,astra_styles_saisie_fusionner($avant,$post));
	}

	public function testPostPartielIdentiteNeCreePasDeFeuillesVides(): void {
		$avant=['font-size-site-title'=>['tablet'=>18]];
		$post=['font-size-site-title'=>['desktop'=>'','desktop-unit'=>'','tablet'=>'18','tablet-unit'=>'','mobile'=>'','mobile-unit'=>'']];
		$this->assertSame($avant,astra_styles_saisie_fusionner($avant,$post));
	}

}

<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';
require_once __DIR__ . '/StylesAttendusTest.php';

final class StylesIdentiteResponsiveTest extends TestCase {
	public function testValeurParAppareilEtConversionDAstra(): void {
		$this->assertSame('18px;font-size:1.125rem', astra_identite_taille_appareil(['tablet'=>'18'], 'tablet'));
		$this->assertSame('', astra_identite_taille_appareil(['tablet'=>'18'], 'desktop'));
		$this->assertSame('80%', astra_identite_taille_appareil(['tablet'=>'80','tablet-unit'=>'%'], 'tablet'));
		$this->assertSame('', astra_identite_taille_appareil(['tablet'=>0], 'tablet'));
		$this->assertSame('', astra_identite_taille_appareil(['tablet'=>0.0], 'tablet'));
		$this->assertSame('0px;font-size:0rem', astra_identite_taille_appareil(['tablet'=>'0'], 'tablet'));
	}

	public function testFauxEtDefautPropresALAppareil(): void {
		foreach ([false, 0, '0'] as $faux) {
			$this->assertFalse(astra_identite_visible_appareil(['tablet'=>$faux], 'tablet', true));
		}
		$this->assertTrue(astra_identite_visible_appareil(['desktop'=>false], 'tablet', true));
		$this->assertFalse(astra_identite_visible_appareil(['desktop'=>true], 'mobile', false));
	}

	public function testUnSeulAppareilActiveLeGroupe(): void {
		$this->assertTrue(astra_identite_responsive_pose(['display-site-title-responsive'=>['mobile'=>false]]));
		$this->assertFalse(astra_identite_responsive_pose(['font-size-site-title'=>['mobile-unit'=>'px']]));
		$css=astra_styles_css(['font-size-site-title'=>['tablet'=>'18']], []);
		$this->assertStringContainsString('@media (max-width:921px){.site-title{font-size:18px;font-size:1.125rem;display:block;}', $css);
		$this->assertStringContainsString('.site-title{font-size:inherit;display:block;}', $css);
		$this->assertSame('', astra_styles_css([], []));
	}

	public function testUniteInvalideRetireLaValeurDuSeulAppareil(): void {
		$v=astra_styles_normaliser(['font-size-site-title'=>['desktop'=>'20','desktop-unit'=>'px','tablet'=>'18','tablet-unit'=>'pt','mobile'=>'80','mobile-unit'=>'%']]);
		$this->assertSame(['desktop'=>'20','desktop-unit'=>'px','mobile'=>'80','mobile-unit'=>'%'], $v['font-size-site-title']);
		$this->assertSame(['tablet'=>'18'], astra_styles_normaliser(['font-size-site-title'=>['tablet'=>'18','tablet-unit'=>'']])['font-size-site-title']);
		$this->assertArrayNotHasKey('tablet', astra_styles_normaliser(['font-size-site-title'=>['desktop'=>'20','tablet'=>INF]])['font-size-site-title']);
		$this->assertArrayNotHasKey('tablet', astra_styles_normaliser(['font-size-site-title'=>['desktop'=>'20','tablet'=>'18;}body{display:none']])['font-size-site-title']);
		$this->assertNotContains('%', astra_styles_registre()['font-size-button']['unites']);
	}

	public static function cas(): array {
		$r=[];
		foreach(json_decode(file_get_contents(dirname(__DIR__).'/references/responsive/identite.json'),true) as $nom=>$cas) $r[$nom]=[$nom,$cas['styles']];
		return $r;
	}

	private static function atomes(string $css): array {
		$out=[];
		foreach(StylesAttendusTest::regles($css) as $regle) {
			if(!preg_match('/^(\[@media[^\]]+\] )?(.*?)\{(.*)\}$/',$regle,$m)) continue;
			$sel=$m[2];
			$props=in_array($sel,['.site-title','.site-header .site-description'],true)?['font-size','display']:(strpos($sel,'.custom-logo-link img')!==false||strpos($sel,'.custom-mobile-logo-link img')!==false||strpos($sel,'.astra-logo-svg')!==false?['width','max-width']:[]);
			foreach(explode(';',$m[3]) as $d) if(in_array(explode(':',$d)[0],$props,true))$out[]=($m[1]??'').$sel.'{'.$d.';}';
		}
		return $out;
	}

	#[DataProvider('cas')]
	public function testTaillesContreWordPress(string $nom,array $styles): void {
		$attendu=file_get_contents(dirname(__DIR__).'/references/responsive/attendus-identite/'.$nom.'.css');
		// L'oracle est une liste de règles avec media préfixé, non une feuille imbriquée.
		$css='';
		foreach(explode("\n",trim($attendu)) as $l) {
			if(preg_match('/^\[@media (.*?)\] (.*)$/',$l,$m))$css.='@media '.$m[1].'{'.$m[2].'}';else $css.=$l;
		}
		$nous=self::atomes(file_get_contents(dirname(__DIR__,2).'/css/astra-dynamic.css').astra_styles_css($styles,[]));
		$this->assertSame([],array_values(array_diff(self::atomes($css),$nous)), $nom.' : déclaration amont absente');
	}
}

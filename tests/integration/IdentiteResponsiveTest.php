<?php
use PHPUnit\Framework\TestCase;

/** HTML d'identité et vraie image servie, sans simulacre de filtre SPIP. */
final class IdentiteResponsiveTest extends TestCase {
	private $styles;
	private string $slogan;
	private array $fichiers = [];
	private $imageProcess;
	protected function setUp(): void {
		$this->styles = lire_config('astra/styles');
		$this->imageProcess = $GLOBALS['meta']['image_process'] ?? null;
		ecrire_meta('image_process','gd2');
		$this->slogan = $GLOBALS['meta']['slogan_site'] ?? '';
	}
	protected function tearDown(): void {
		if ($this->styles === null) effacer_config('astra/styles'); else ecrire_config('astra/styles',$this->styles);
		ecrire_meta('slogan_site',$this->slogan);
		if ($this->imageProcess === null) effacer_meta('image_process'); else ecrire_meta('image_process',$this->imageProcess);
		foreach($this->fichiers as $f) @unlink($f);
	}
	private function image(string $taille): string {
		$dir=getenv('ASTRA_IMAGES');
		if(!$dir || !is_file($dir.'/logo-'.$taille.'.png')) $this->markTestSkipped('Préparer ASTRA_IMAGES selon le plan responsive.');
		$f=_DIR_TMP.'logo-identite-'.$taille.'.png';copy($dir.'/logo-'.$taille.'.png',$f);$this->fichiers[]=$f;
		[$w,$h]=getimagesize($f);
		return '<img src="'.$f.'" width="'.$w.'" height="'.$h.'" alt="Logo de test" />';
	}
	public function testSloganVisibleSeulementSurMobile(): void {
		ecrire_meta('slogan_site','Un jardin vivant');
		ecrire_config('astra/styles',['display-site-title-responsive'=>['desktop'=>false,'tablet'=>true,'mobile'=>false],'display-site-tagline-responsive'=>['desktop'=>false,'tablet'=>false,'mobile'=>true]]);
		$h=recuperer_fond('inclure/identite',[]);
		$this->assertSame(1,substr_count($h,'class="site-description"'));
		$this->assertSame(1,substr_count($h,'class="site-title"'));
		$css=astra_styles_inline();
		$this->assertStringContainsString('.site-header .site-description{font-size:15px;font-size:0.9375rem;display:none;}',$css);
		$this->assertStringContainsString('@media (max-width:544px){.site-title{display:none;}.site-header .site-description{display:block;}',$css);
	}
	public function testDeuxTextesMasquesPartout(): void {
		ecrire_config('astra/styles',['display-site-title-responsive'=>array_fill_keys(['desktop','tablet','mobile'],false),'display-site-tagline-responsive'=>array_fill_keys(['desktop','tablet','mobile'],false)]);
		$this->assertStringNotContainsString('ast-site-title-wrap',recuperer_fond('inclure/identite',[]));
	}
	public function testLogoGardeLaDefinitionEtLeCadreBureau(): void {
		$img=$this->image('200x100');
		ecrire_config('astra/styles',['ast-header-responsive-logo-width'=>['desktop'=>'32','tablet'=>'96','mobile'=>'48']]);
		$r=astra_logo_reduire($img);
		$this->assertSame('32',extraire_attribut($r,'width'));
		[$w]=getimagesize(extraire_attribut($r,'src'));
		$this->assertGreaterThanOrEqual(96,$w,'Le fichier ne doit pas être réduit à la seule largeur bureau.');
		$this->assertSame('Logo de test',extraire_attribut($r,'alt'));
	}
	public function testLogoSansBureauGardeSonRepli(): void {
		$img=$this->image('200x100');
		effacer_config('astra/styles');$ancien=astra_logo_reduire($img);
		$this->assertSame(image_reduire($img,0,60),$ancien);
		ecrire_config('astra/styles',['ast-header-responsive-logo-width'=>['tablet'=>'160','mobile'=>'48']]);
		$r=astra_logo_reduire($img);
		$this->assertSame(extraire_attribut($ancien,'width'),extraire_attribut($r,'width'));
		$this->assertSame(extraire_attribut($ancien,'height'),extraire_attribut($r,'height'));
		[$w]=getimagesize(extraire_attribut($r,'src'));$this->assertGreaterThanOrEqual(160,$w);
	}
	public function testPetiteSourceNestPasAgrandie(): void {
		$img=$this->image('40x20');
		ecrire_config('astra/styles',['ast-header-responsive-logo-width'=>['desktop'=>'32','tablet'=>'96']]);
		[$w]=getimagesize(extraire_attribut(astra_logo_reduire($img),'src'));$this->assertSame(40,$w);
	}
}

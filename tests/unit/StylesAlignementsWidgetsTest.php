<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';
require_once __DIR__ . '/StylesAttendusTest.php';
final class StylesAlignementsWidgetsTest extends TestCase {
 private static function hfb(): array { return ['pied'=>['primary'=>['primary_1'=>['widget-1'],'primary_2'=>['widget-2'],'primary_3'=>['widget-3'],'primary_4'=>['widget-4']]],'rangees'=>['pied_primary'=>['colonnes'=>4]]]; }
 private static function sel(int $n): string { return '.footer-widget-area[data-section="sidebar-widgets-footer-widget-'.$n.'"].footer-widget-area-inner'; }
 public function testAlignementSeulSansCouleur(): void {
  $v=['footer-widget-alignment-1'=>['desktop'=>'right','tablet'=>'','mobile'=>'']];
  astra_styles_css($v,self::hfb());
  $this->assertSame([['',self::sel(1),['text-align'=>'right']]],astra_styles_alignements_widgets($v,self::hfb()));
  $this->assertSame('',astra_styles_css([],[]));
 }
 public function testOptionPartielleNeutraliseLeLeftFige(): void {
  astra_styles_css(['footer-widget-alignment-1'=>['tablet'=>'center']],self::hfb());
  $this->assertSame([['',self::sel(1),['text-align'=>'inherit']],['(max-width:921px)',self::sel(1),['text-align'=>'center']]],astra_styles_alignements_widgets(['footer-widget-alignment-1'=>['tablet'=>'center']],self::hfb()));
  $this->assertSame([],astra_styles_alignements_widgets(['footer-widget-alignment-1'=>['desktop'=>'','mobile'=>'']],self::hfb()));
 }
 public function testConditionDePlacement(): void {
  astra_styles_css(['footer-widget-alignment-1'=>['desktop'=>'right']],self::hfb());
  $s=['footer-widget-alignment-1'=>['desktop'=>'right']];
  $this->assertSame([],astra_styles_alignements_widgets($s,[]));
  $this->assertSame([],astra_styles_alignements_widgets($s,['entete_ordinateur'=>['primary'=>['primary_left'=>['widget-1']]]]));
  foreach(['below_1','primary_6'] as $z) $this->assertNotEmpty(astra_styles_alignements_widgets($s,['pied'=>[explode('_',$z)[0]=>[$z=>['widget-1']]],'rangees'=>['pied_primary'=>['colonnes'=>6]]]));
 }
 public function testOrdreDesQuatreWidgets(): void {
  $s=[];foreach([4,2,1,3] as $n)$s['footer-widget-alignment-'.$n]=['desktop'=>'right'];
  $css=astra_styles_css($s,self::hfb());$dernier=-1;
  for($n=1;$n<=4;$n++){ $p=strpos($css,self::sel($n));$this->assertNotFalse($p);$this->assertGreaterThan($dernier,$p);$dernier=$p; }
  $this->assertStringNotContainsString(self::sel(1),astra_styles_css(['footer-widget-alignment-2'=>['desktop'=>'left']],self::hfb()));
 }
 public function testEnumRefuseeParAppareil(): void {
  $v=astra_styles_normaliser(['footer-widget-alignment-1'=>['desktop'=>'left','tablet'=>'start','mobile'=>'right;display:none']]);
  $this->assertSame(['desktop'=>'left'],$v['footer-widget-alignment-1']);
 }
 public static function cas():array { $r=[];foreach(json_decode(file_get_contents(dirname(__DIR__).'/references/responsive/widgets.json'),true) as $n=>$c)$r[$n]=[$n,$c];return $r; }
 #[DataProvider('cas')]
 public function testOracle(string $nom,array $cas): void {
  require_once dirname(__DIR__).'/comparaison/configurations.php';
  $c=astra_configurations_widgets_alignements()[$nom];
  $css=astra_styles_css($cas['styles'],astra_configuration_hfb($c));
  $regles=array_values(array_filter(StylesAttendusTest::regles($css),fn($r)=>strpos($r,'.footer-widget-area[')!==false));
  $attendu=array_values(array_filter(explode("\n",trim(file_get_contents(dirname(__DIR__).'/references/responsive/attendus-widgets/'.$nom.'.css')))));
  // Le CSS figé reste inchangé : seule sa règle left est neutralisée pour une option partielle.
  $this->assertIsArray($regles);
  $amont=array_values(array_filter($regles,fn($r)=>strpos($r,'text-align:inherit')===false));
  // Astra émet aussi ses défauts des widgets sans option ; ils restent dans le CSS figé.
  foreach($amont as $r)$this->assertContains($r,$attendu,$nom);
  foreach($cas['styles'] as $o=>$v){if(!array_filter($v,fn($x)=>$x!==''&&$x!==null))continue;preg_match('/-(\d)$/',$o,$m);if(in_array($cas['placement'],['absent','entete'],true)&&$m[1]==1)continue;
   foreach(['desktop'=>'','tablet'=>'[@media (max-width:921px)] ','mobile'=>'[@media (max-width:544px)] '] as $a=>$media)if(!empty($v[$a]))$this->assertContains($media.self::sel((int)$m[1]).'{text-align:'.$v[$a].';}',$regles);
  }
 }
}

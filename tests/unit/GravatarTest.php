<?php
use PHPUnit\Framework\TestCase;
final class GravatarTest extends TestCase {
 private function decision(string $cas):array {
  $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/fixtures/gravatar/decision.php').' '.escapeshellarg($cas);
  exec($cmd.' 2>&1',$sortie,$code);$this->assertSame(0,$code,implode("\n",$sortie));return json_decode(implode("\n",$sortie),true,512,JSON_THROW_ON_ERROR);
 }
 public function testInactifMalgreApiPresente():void {$r=$this->decision('inactif');$this->assertSame('',$r['html']);$this->assertSame([],$r['appels']);}
 public function testApiAbsente():void {$r=$this->decision('sans-api');$this->assertSame('',$r['html']);$this->assertCount(1,$r['appels']);}
 public function testAdresseInutilisableSansFournisseur():void {foreach(['vide','invalide','tableau'] as $c){$r=$this->decision($c);$this->assertSame('',$r['html']);$this->assertSame([],$r['appels']);}}
 public function testPasDAvatarDeRemplacement():void {$r=$this->decision('sans-image');$this->assertSame('',$r['html']);$this->assertSame(['gravatar','test@example.org','404'],$r['appels'][1]);$this->assertCount(2,$r['appels']);}
 public function testImageLocaleDecorativeNominale():void {$r=$this->decision('image');$this->assertSame(['image','local/cache-gravatar/opaque.jpg',50,'','avatar avatar-50 photo'],$r['appels'][2]);$this->assertStringContainsString('width="50"',$r['html']);$this->assertStringContainsString('height="50"',$r['html']);$this->assertStringContainsString('aria-hidden="true"',$r['html']);$this->assertStringNotContainsString('style=',$r['html']);$this->assertStringNotContainsString('example.org',$r['html']);}
}

<?php
use PHPUnit\Framework\TestCase;
/** Le vrai plugin et son cache natif, aucun fournisseur distant dans ces essais. */
final class GravatarRenduTest extends TestCase {
 private array $cache=[];
 private array $forums=[];
 protected function setUp():void {
  if(getenv('ASTRA_GRAVATAR_PREUVE')!=='1')$this->markTestSkipped('Utiliser le site privé avec le vrai plugin Gravatar, voir fixtures/gravatar/README.md.');
  require_once dirname(__DIR__).'/fixtures/gravatar/cache.php';
  $this->cache=astra_gravatar_cache_preparer();
 }
 protected function tearDown():void {foreach($this->forums as $id)sql_delete('spip_forum','id_forum='.$id);if($this->cache)astra_gravatar_cache_restaurer($this->cache);}
 private function forum(string $nom,string $email,int $article=1,int $parent=0):int {
  $id=(int)sql_insertq('spip_forum',['objet'=>'article','id_objet'=>$article,'statut'=>'publie','auteur'=>$nom,'email_auteur'=>$email,'texte'=>'Un commentaire de test.','titre'=>'Commentaire','id_parent'=>$parent,'date_heure'=>'2026-10-26 08:00:00']);
  sql_updateq('spip_forum',['id_thread'=>$parent?:$id],'id_forum='.$id);$this->forums[]=$id;return$id;
 }
 private function xpath(string $html):DOMXPath {$d=new DOMDocument();@$d->loadHTML('<?xml encoding="UTF-8">'.$html);return new DOMXPath($d);}
 public function testImageOuInitialeSelonActivation():void {
  $id=$this->forum('Élodie','avatar@example.org');$html=recuperer_fond('inclure/commentaire',['id_forum'=>$id,'profondeur'=>1]);$x=$this->xpath($html);
  $images=$x->query('//*[contains(@class,"ast-comment-avatar-wrap")]/img');
  if(test_plugin_actif('gravatar')){
   $this->assertSame(1,$images->length);$i=$images->item(0);$this->assertSame('avatar avatar-50 photo',$i->getAttribute('class'));$this->assertSame('',$i->getAttribute('alt'));$this->assertSame('true',$i->getAttribute('aria-hidden'));$this->assertSame('50',$i->getAttribute('width'));$this->assertFalse($i->hasAttribute('style'));$this->assertStringStartsWith('local/cache-gravatar/',$i->getAttribute('src'));$this->assertStringNotContainsString('ast-avatar-initiale',$html);
  }else{$this->assertSame(0,$images->length);$this->assertStringContainsString('<span class="avatar avatar-50 photo ast-avatar-initiale" aria-hidden="true">É</span>',$html);}
  $this->assertStringNotContainsString('avatar@example.org',$html);
 }
 public function testAbsenceImageEtNomVideConserventLeRepli():void {
  foreach([['Élodie','sans-avatar@example.org','É'],['','invalid','?'],['Alice','','A']] as [$nom,$email,$initiale]){
   $ctx=['id_forum'=>$this->forum($nom,$email),'profondeur'=>1];
   $html=recuperer_fond('inclure/commentaire',$ctx);
   $this->assertSame(recuperer_fond('tests/fixtures/gravatar/commentaire-avant',$ctx),$html,'HTML du repli strictement conservé');
   $this->assertStringContainsString('<span class="avatar avatar-50 photo ast-avatar-initiale" aria-hidden="true">'.$initiale.'</span>',$html);$this->assertSame(0,$this->xpath($html)->query('//*[contains(@class,"ast-comment-avatar-wrap")]/img')->length);
  }
 }
 public function testReponseEtPageUnique():void {
  $page=(int)sql_getfetsel('id_article','spip_articles',"page='notre_jardin'");$this->assertGreaterThan(0,$page);
  $avant=sql_getfetsel('accepter_forum','spip_articles','id_article='.$page);sql_updateq('spip_articles',['accepter_forum'=>'pos'],'id_article='.$page);
  try{
   $parent=$this->forum('Élodie','avatar@example.org',$page);$this->forum('Alice','avatar@example.org',$page,$parent);
   $html=recuperer_fond('page_unique',['id_article'=>$page]);$x=$this->xpath($html);
   $this->assertSame(2,$x->query('//*[contains(@class,"ast-comment-avatar-wrap")]')->length);
   $this->assertSame(test_plugin_actif('gravatar')?2:0,$x->query('//*[contains(@class,"ast-comment-avatar-wrap")]/img')->length);
   $this->assertSame(1,$x->query('//ol[@class="children"]')->length);
  }finally{sql_updateq('spip_articles',['accepter_forum'=>$avant],'id_article='.$page);}
 }
}

<?php
use PHPUnit\Framework\TestCase;

final class MenusRenduTest extends TestCase {
	public function testMenuLibreDansTousLesEmplacementsEtRepli(): void {
		$avant=lire_config('astra/hfb',null);
		$menu=(int)sql_insertq('spip_menus',['titre'=>'Menu été','identifiant'=>'astra-test-libre']);
		$article=(int)sql_getfetsel('id_article','spip_articles','statut="publie"');
		$parent=(int)sql_insertq('spip_menus_entrees',['id_menu'=>$menu,'rang'=>1,'type_entree'=>'lien','parametres'=>serialize(['titre'=>'Été libre','url'=>'https://example.test/ete?a=1&b=2','css'=>'personnalisee'])]);
		$sous=(int)sql_insertq('spip_menus',['id_menus_entree'=>$parent]);
		sql_insertq('spip_menus_entrees',['id_menu'=>$sous,'rang'=>1,'type_entree'=>'objet','parametres'=>serialize(['type_objet'=>'article','id_objet'=>$article,'titre'=>'Page publiée'])]);
		sql_insertq('spip_menus_entrees',['id_menu'=>$menu,'rang'=>2,'type_entree'=>'objet','parametres'=>serialize(['type_objet'=>'article','id_objet'=>9999999,'titre'=>'Objet absent'])]);
		try {
			$this->assertTrue(test_plugin_actif('menus'));
			$this->assertArrayHasKey('menus:'.$menu,astra_hfb_sources_menu());
			$this->assertArrayNotHasKey('menus:'.$sous,astra_hfb_sources_menu());
			$env=['source'=>'menus','id_source'=>$menu,'id_article'=>$article,'type_page'=>'article','niveaux'=>2,'accueil'=>'oui'];
			$html=recuperer_fond('inclure/hfb/menu-items',$env);
			$this->assertStringContainsString('Été libre',$html);
			$this->assertStringContainsString('Page publiée',$html);
			$this->assertStringNotContainsString('Objet absent',$html);
			$this->assertStringContainsString('current-menu-item',$html);
			$this->assertStringContainsString('current-menu-ancestor',$html);
			$this->assertStringContainsString('menu-item-has-children',$html);
			$this->assertStringContainsString('ast-menu-toggle',$html);
			$this->assertStringNotContainsString('menu-item-home',$html);
			$this->assertStringContainsString('personnalisee',$html);
			$sans=recuperer_fond('inclure/hfb/menu-liens-sans-js',$env);
			$this->assertStringContainsString('Page publiée',$sans);
			$this->assertStringNotContainsString('ast-menu-toggle',$sans);
			$this->assertStringNotContainsString('sub-menu',$sans);
			$source=['source'=>'menus','id'=>$menu];
			ecrire_config('astra/hfb',['elements'=>['entete'=>['menu-1'=>$source],'pied'=>['menu'=>$source,'widget-1'=>['menu'=>$source]]]]);
			foreach (['inclure/hfb/menu'=>['id'=>'menu-1','numero'=>1,'groupe'=>'entete','appareil'=>'ordinateur'],'inclure/widgets/menu'=>['numero'=>1,'menu'=>$source,'groupe'=>'pied']] as $fond=>$contexte) {
				$this->assertStringContainsString('Été libre',recuperer_fond($fond,$contexte+$env));
			}
			$this->assertStringContainsString('Été libre',recuperer_fond('inclure/hfb/menu',['id'=>'menu','groupe'=>'pied','appareil'=>'pied']+$env));
			$this->assertSame('',trim(recuperer_fond('inclure/hfb/menu-items',['source'=>'menus','id_source'=>9999999])));
		} finally {
			sql_delete('spip_menus_entrees','id_menu IN ('.$menu.','.$sous.')');
			sql_delete('spip_menus','id_menu IN ('.$menu.','.$sous.')');
			if($avant===null)effacer_config('astra/hfb');else ecrire_config('astra/hfb',$avant);
		}
	}

	public function testAccueilEtPageSpecialeOntUnEtatActifDansTousLesRendus(): void {
		$this->avecMenuPages(function($menu) {
			foreach (['sommaire'=>'Accueil libre','recherche'=>'Recherche libre','article'=>null] as $page=>$titre) {
				foreach ($this->rendusMenu($menu,$page) as $fond=>$html) {
					$x=$this->xpath($html);
					$actifs=$x->query('//a[@aria-current="page"]');
					$this->assertSame($titre===null?0:($fond==='header'?3:1),$actifs->length,$fond.' / '.$page);
					if ($titre!==null) {
						$this->assertSame($titre,$actifs->item(0)->textContent,$fond);
						if ($fond!=='repli' && $fond!=='header') {
							$this->assertStringContainsString('current-menu-item',$actifs->item(0)->parentNode->getAttribute('class'),$fond);
						}
					}
				}
			}
		});
	}

	public function testClassesDuMenuSurLesListesAstra(): void {
		$this->avecMenuPages(function($menu) {
			foreach ($this->rendusMenu($menu,'sommaire') as $fond=>$html) {
				$x=$this->xpath($html);
				$this->assertGreaterThan(0,$x->query('//ul[contains(concat(" ",@class," ")," choix-libre ") and contains(concat(" ",@class," ")," css-ete ")]')->length,$fond);
				$this->assertSame(0,$x->query('//ul/ul')->length,'Pas de liste racine imbriquée : '.$fond);
			}
		});
	}

	private function rendusMenu(int $menu,string $page): array {
		$env=['source'=>'menus','id_source'=>$menu,'type_page'=>$page];
		return [
			'entete'=>recuperer_fond('inclure/hfb/menu',['id'=>'menu-1','numero'=>1,'groupe'=>'entete','appareil'=>'ordinateur']+$env),
			'mobile'=>recuperer_fond('inclure/hfb/mobile-menu',$env),
			'pied'=>recuperer_fond('inclure/hfb/menu',['id'=>'menu','groupe'=>'pied']+$env),
			'widget'=>recuperer_fond('inclure/widgets/menu',['menu'=>['source'=>'menus','id'=>$menu],'numero'=>1,'groupe'=>'pied']+$env),
			'repli'=>recuperer_fond('inclure/hfb/menu-sans-js',$env),
			'header'=>recuperer_fond('inclure/header',$env),
		];
	}

	private function avecMenuPages(callable $verifier): void {
		$avant=lire_config('astra/hfb',null);
		$menu=(int)sql_insertq('spip_menus',['titre'=>'Menu pages','identifiant'=>'astra-pages-review','css'=>'choix-libre css-ete']);
		sql_insertq('spip_menus_entrees',['id_menu'=>$menu,'rang'=>1,'type_entree'=>'accueil','parametres'=>serialize(['titre'=>'Accueil libre'])]);
		sql_insertq('spip_menus_entrees',['id_menu'=>$menu,'rang'=>2,'type_entree'=>'page_speciale','parametres'=>serialize(['titre'=>'Recherche libre','nom'=>'recherche'])]);
		$source=['source'=>'menus','id'=>$menu];
		try {
			ecrire_config('astra/hfb',['elements'=>['entete'=>['menu-1'=>$source],'pied'=>['menu'=>$source]]]);
			$verifier($menu);
		} finally {
			sql_delete('spip_menus_entrees','id_menu='.$menu);
			sql_delete('spip_menus','id_menu='.$menu);
			if($avant===null)effacer_config('astra/hfb');else ecrire_config('astra/hfb',$avant);
		}
	}

	private function xpath(string $html): DOMXPath {
		$d=new DOMDocument();@$d->loadHTML('<?xml encoding="utf-8"?>'.$html);
		return new DOMXPath($d);
	}
}

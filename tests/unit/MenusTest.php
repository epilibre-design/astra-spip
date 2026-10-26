<?php
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__, 2) . '/inc/astra_hfb_saisie.php';
// Les tests unitaires n’amorcent pas le service de traduction de SPIP.
if (!function_exists('_T')) {
	function _T($cle) { return $cle; }
}

final class MenusTest extends TestCase {
	public function testConfigurationLibreSurvitAuxAllersRetours(): void {
		$menu=['source'=>'menus','id'=>17];
		$this->assertSame('menus:17',astra_hfb_source_id($menu));
		$this->assertSame($menu,astra_hfb_source_depuis_saisie('menus:17'));
	}
	public function testAdapteLesLiensEtSousMenusSansPerdreLesAttributs(): void {
		require_once dirname(__DIR__,2).'/inc/astra_menus.php';
		$html='<ul class="menu-items"><li class="item on personnalisee"><a href="/été?a=1&amp;b=2" target="_blank" rel="noopener">Été</a><ul><li><a href="/enfant" aria-current="page">Enfant</a></li></ul></li></ul>';
		$r=astra_menus_adapter($html);
		$d=new DOMDocument();@$d->loadHTML('<?xml encoding="utf-8"?><ul>'.$r.'</ul>');$x=new DOMXPath($d);
		$this->assertSame('Été',$x->query('//a')->item(0)->textContent);
		$this->assertSame('/été?a=1&b=2',urldecode($x->query('//a')->item(0)->getAttribute('href')));
		$this->assertSame('_blank',$x->query('//a')->item(0)->getAttribute('target'));
		$this->assertStringContainsString('personnalisee',$x->query('//li')->item(0)->getAttribute('class'));
		$this->assertSame(1,$x->query('//li[contains(@class,"menu-item-has-children")]/button[@aria-expanded="false"]')->length);
		$this->assertSame(1,$x->query('//li[contains(@class,"current-menu-item")]/a[@aria-current="page"]')->length);
		$this->assertSame(1,$x->query('//ul[contains(@class,"sub-menu")]')->length);
		$this->assertStringContainsString('current-menu-ancestor',$x->query('//li')->item(0)->getAttribute('class'));
		$widget=astra_menus_adapter($html,'oui');
		$this->assertStringNotContainsString('<button',$widget);
		$this->assertStringNotContainsString('tabindex',$widget);
		$this->assertSame('',astra_menus_adapter(''));
	}
	public function testParentSansLienPeutDeplierSesEnfants(): void {
		require_once dirname(__DIR__,2).'/inc/astra_menus.php';
		$r=astra_menus_adapter('<ul><li><span class="texte_libre">Titre</span><ul><li><a href="/suite">Suite</a></li></ul></li></ul>');
		$d=new DOMDocument();@$d->loadHTML('<ul>'.$r.'</ul>');$x=new DOMXPath($d);
		$this->assertSame(1,$x->query('//li[contains(@class,"menu-item-has-children")]/button')->length);
	}
}

<?php
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__) . '/comparaison/normalisations.php';
final class NavigationSansJsComparaisonTest extends TestCase {
	public function testSeulLeRepliDeclareEstRetireDuComparatifActif(): void {
		$d = new DOMDocument();
		@$d->loadHTML('<body><header><noscript><nav class="astra-navigation-sans-js"><ul><li><a href="/">Lien</a></li></ul></nav></noscript><noscript><p>Autre contenu</p></noscript><nav class="astra-navigation-sans-js">Actif inattendu</nav></header></body>');
		$x = new DOMXPath($d);
		astra_normaliser_navigation_sans_js($x);
		$this->assertSame(1, $x->query('//noscript')->length);
		$this->assertSame('Autre contenu', $x->query('//noscript')->item(0)->textContent);
		$this->assertSame(1, $x->query('//header/nav')->length, 'Ne masque pas un repli présent dans le DOM actif');
	}
}

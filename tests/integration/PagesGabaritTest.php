<?php

use PHPUnit\Framework\TestCase;

/** Gabarit de page (page_unique.html) et choix par le pipeline styliser. */
final class PagesGabaritTest extends TestCase {

	public static function setUpBeforeClass(): void {
		include_spip('inc/astra_pages');
	}

	protected function tearDown(): void {
		effacer_config('astra/pages');
	}

	public static function id(string $page): int {
		return (int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', 'page=' . sql_quote($page)]);
	}

	/** Page complète analysée telle quelle : HfbRenduTest::xpath() l'envelopperait dans un second body. */
	public static function page(int $id, array $contexte = []): DOMXPath {
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHTML('<?xml encoding="utf-8"?>' . recuperer_fond('article', ['id_article' => $id] + $contexte));
		libxml_clear_errors();
		return new DOMXPath($doc);
	}

	public function testStyliserChoisitLeGabaritDePage(): void {
		$styliser = charger_fonction('styliser', 'public');
		$this->assertStringEndsWith('page_unique', $styliser('article', ['id_article' => self::id('notre-jardin')])[0] ?? '');
		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'id_rubrique>0', '', 'id_article', '0,1');
		$this->assertStringEndsWith('/article', $styliser('article', ['id_article' => $article])[0] ?? '');
	}

	public function testBalisageDePage(): void {
		$id = self::id('notre-jardin');
		$x = self::page($id);
		$this->assertSame(1, $x->query('//article[@id="post-' . $id . '"][contains(@class, "type-page")][@itemtype="https://schema.org/CreativeWork"]')->length);
		$this->assertSame('Notre jardin', trim($x->query('//article//header[contains(@class, "entry-header")]/h1[@class="entry-title"]')->item(0)->textContent));
		$this->assertSame(1, $x->query('//div[contains(@class, "entry-content")][@data-ast-blocks-layout="true"]')->length);
		$this->assertSame(0, $x->query('//*[contains(@class, "entry-meta")]')->length);
		$this->assertSame(0, $x->query('//nav[contains(@class, "post-navigation")]')->length);
		$this->assertSame(0, $x->query('//*[@id="secondary"]')->length);
		$classes = explode(' ', $x->query('//body')->item(0)->getAttribute('class'));
		$this->assertContains('page-id-' . $id, $classes);
		$this->assertContains('page-parent', $classes);
		$this->assertContains('ast-plain-container', $classes);
		$this->assertSame('https://schema.org/WebPage', $x->query('//body')->item(0)->getAttribute('itemtype'));
	}

	public function testTitreMasqueEtDisposition(): void {
		$id = self::id('plein-ecran');
		ecrire_config('astra/pages/' . $id, ['titre' => 'masquer', 'disposition' => 'pleine']);
		$x = self::page($id);
		$this->assertSame(0, $x->query('//h1[@class="entry-title"]')->length, 'Titre non produit, comme dans Astra');
		$classes = explode(' ', $x->query('//body')->item(0)->getAttribute('class'));
		$this->assertContains('ast-page-builder-template', $classes);
		$this->assertNotContains('ast-normal-title-enabled', $classes);
		$this->assertNotContains('page-parent', $classes);
		ecrire_config('astra/pages/' . $id, ['titre' => 'afficher', 'disposition' => 'etroite']);
		$this->assertContains('ast-narrow-container', explode(' ', self::page($id)->query('//body')->item(0)->getAttribute('class')));
	}
}

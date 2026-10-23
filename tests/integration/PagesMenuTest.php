<?php

use PHPUnit\Framework\TestCase;

/** Menu de pages à deux niveaux (liens a2a sous_page). */
final class PagesMenuTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/HfbRenduTest.php';
		require_once __DIR__ . '/PagesGabaritTest.php';
		include_spip('inc/astra_pages');
	}

	protected function setUp(): void {
		ecrire_config('astra/hfb', ['elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]]]);
	}

	protected function tearDown(): void {
		effacer_config('astra/hfb');
		sql_updateq('spip_articles', ['statut' => 'publie'], 'id_article=' . PagesGabaritTest::id('notre_jardin'));
	}

	private function menu(int $id_article): DOMXPath {
		return HfbRenduTest::xpath(recuperer_fond('inclure/header', ['type_page' => 'page', 'id_article' => $id_article]));
	}

	public function testArbre(): void {
		$p = [];
		foreach (['mentions', 'notre_jardin', 'potager_collectif', 'verger', 'plein_ecran'] as $n) {
			$p[$n] = PagesGabaritTest::id($n);
		}
		$this->assertSame([
			['id' => $p['mentions'], 'enfants' => []],
			['id' => $p['notre_jardin'], 'enfants' => [$p['potager_collectif'], $p['verger']]],
			['id' => $p['plein_ecran'], 'enfants' => []],
		], astra_pages_arbre());
	}

	public function testSousMenuEtEtatActif(): void {
		$jardin = PagesGabaritTest::id('notre_jardin');
		$verger = PagesGabaritTest::id('verger');
		$x = $this->menu($verger);
		$d = '//*[@id="ast-desktop-header"]//ul[@id="ast-hf-menu-1"]';
		$parent = $d . '/li[contains(@class, "menu-item-article-' . $jardin . '")]';
		$this->assertSame(1, $x->query($parent . '[contains(@class, "menu-item-has-children")][contains(@class, "current-menu-ancestor")]')->length);
		$this->assertSame(2, $x->query($parent . '/ul[@class="sub-menu"]/li')->length);
		$this->assertSame(1, $x->query($parent . '/ul/li[contains(@class, "menu-item-article-' . $verger . '")][contains(@class, "current-menu-item")]/a[@aria-current="page"]')->length);
		$this->assertSame(0, $x->query($d . '/li[contains(@class, "menu-item-article-' . $verger . '")]')->length, 'Une sous-page n\'est pas au premier niveau');
	}

	public function testParenteDepubliee(): void {
		$jardin = PagesGabaritTest::id('notre_jardin');
		$verger = PagesGabaritTest::id('verger');
		sql_updateq('spip_articles', ['statut' => 'prop'], 'id_article=' . $jardin);
		$d = '//*[@id="ast-desktop-header"]//ul[@id="ast-hf-menu-1"]';
		$this->assertSame(1, $this->menu(0)->query($d . '/li[contains(@class, "menu-item-article-' . $verger . '")]')->length, 'L\'enfant remonte au premier niveau');
		sql_updateq('spip_articles', ['statut' => 'publie'], 'id_article=' . $jardin);
		$this->assertSame(0, $this->menu(0)->query($d . '/li[contains(@class, "menu-item-article-' . $verger . '")]')->length, 'Il revient en sous-menu');
	}

	public function testTableDesLiensAbsente(): void {
		// a2a actif sans sa table (installation incomplète) : sql_countsel renvoie alors la requête,
		// une chaîne non vide, qui ne doit pas faire passer chaque page pour une page parente.
		$mentions = PagesGabaritTest::id('mentions');
		$jardin = PagesGabaritTest::id('notre_jardin');
		sql_query('ALTER TABLE spip_articles_lies RENAME TO spip_articles_lies_cache');
		try {
			$this->assertFalse(astra_page_a_sous_pages($mentions));
			$this->assertFalse(astra_page_a_sous_pages($jardin));
			$this->assertSame([], array_merge(...array_column(astra_pages_arbre(), 'enfants')));
		} finally {
			sql_query('ALTER TABLE spip_articles_lies_cache RENAME TO spip_articles_lies');
		}
		$this->assertTrue(astra_page_a_sous_pages($jardin));
	}

	public function testMenuDuPiedSurUnNiveau(): void {
		ecrire_config('astra/hfb', [
			'pied' => ['below' => ['below_1' => ['menu']]],
			'elements' => ['pied' => ['menu' => ['source' => 'pages', 'id' => 0]]],
		]);
		$x = HfbRenduTest::xpath(recuperer_fond('inclure/footer', ['type_page' => 'page']));
		$this->assertSame(0, $x->query('//ul[@id="astra-footer-menu"]//ul')->length);
		$this->assertSame(3, $x->query('//ul[@id="astra-footer-menu"]/li')->length);
	}
}

<?php

use PHPUnit\Framework\TestCase;

/**
 * Menus alimentés par les pages uniques (plugin Pages, installé par scripts/install-spip-test.sh) :
 * la page affichée est marquée comme entrée courante dans les menus ordinateur, mobile et du pied.
 */
final class HfbPagesTest extends TestCase {

	protected function tearDown(): void {
		effacer_config('astra/hfb');
	}

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/HfbRenduTest.php';
	}

	public function testPageCouranteDansLesMenusDePages(): void {
		$this->assertTrue((bool) test_plugin_actif('pages'), 'Le plugin Pages doit être actif (scripts/install-spip-test.sh)');
		$id_page = (int) sql_getfetsel('id_article', 'spip_articles', 'id_rubrique=-1 AND page=' . sql_quote('mentions'));
		$this->assertGreaterThan(0, $id_page, 'Page unique « mentions » absente');

		ecrire_config('astra/hfb', [
			'pied' => ['below' => ['below_1' => ['copyright'], 'below_2' => ['menu']]],
			'rangees' => ['pied_below' => ['colonnes' => 2, 'repartition' => '2-equal']],
			'elements' => [
				'entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]],
				'pied' => ['menu' => ['source' => 'pages', 'id' => 0]],
			],
		]);
		$x = HfbRenduTest::xpath(recuperer_fond('article', ['id_article' => $id_page]));
		$entree = '//li[contains(concat(" ", @class, " "), " menu-item-article-' . $id_page . ' ")][contains(concat(" ", @class, " "), " current-menu-item ")]';
		$this->assertSame(1, $x->query('//*[@id="ast-desktop-header"]' . $entree)->length, 'Menu ordinateur');
		$this->assertSame(1, $x->query('//*[@id="ast-mobile-header"]' . $entree)->length, 'Menu mobile');
		$this->assertSame(1, $x->query('//footer[@id="colophon"]' . $entree)->length, 'Menu du pied');
		$this->assertSame(3, $x->query($entree . '/a[@aria-current="page"]')->length);

		$autre = (int) sql_getfetsel('id_article', 'spip_articles', 'id_rubrique>0', '', 'id_article', '0,1');
		$y = HfbRenduTest::xpath(recuperer_fond('article', ['id_article' => $autre]));
		$this->assertSame(0, $y->query($entree)->length, 'La page n\'est pas courante sur un autre article');
		$this->assertSame(3, $y->query('//li[contains(concat(" ", @class, " "), " menu-item-article-' . $id_page . ' ")]')->length);
	}
}

<?php

use PHPUnit\Framework\TestCase;

/** Environnement d'intégration des pages uniques (scripts/install-spip-test.sh). */
final class PagesEnvironnementTest extends TestCase {

	public function testPluginsActifs(): void {
		$this->assertTrue((bool) test_plugin_actif('pages'));
		$this->assertTrue((bool) test_plugin_actif('a2a'));
	}

	public function testPagesEtSousPages(): void {
		$ids = [];
		foreach (['mentions', 'notre_jardin', 'potager_collectif', 'verger', 'plein_ecran'] as $page) {
			$ids[$page] = (int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', "statut='publie'", 'page=' . sql_quote($page)]);
			$this->assertGreaterThan(0, $ids[$page], $page);
		}
		$liens = sql_allfetsel('id_article_lie', 'spip_articles_lies', ['id_article=' . $ids['notre_jardin'], "type_liaison='sous_page'"], '', 'rang');
		$this->assertSame([$ids['potager_collectif'], $ids['verger']], array_map('intval', array_column($liens, 'id_article_lie')));
	}
}

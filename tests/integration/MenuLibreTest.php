<?php

use PHPUnit\Framework\TestCase;

/** inclure/menu-libre : interface publique d’un menu libre de Menus dans le balisage d’Astra (docs/interface-gabarits.md). */
final class MenuLibreTest extends TestCase {

	public function testMenuLibreDansLeBalisageDuPied(): void {
		$menu = (int) sql_insertq('spip_menus', ['titre' => 'Liens utiles', 'identifiant' => 'astra-test-interface', 'css' => 'choix-libre']);
		foreach ([['Mentions', 'https://example.test/mentions'], ['Contact', 'https://example.test/contact']] as $i => [$titre, $url]) {
			sql_insertq('spip_menus_entrees', ['id_menu' => $menu, 'rang' => $i + 1, 'type_entree' => 'lien', 'parametres' => serialize(['titre' => $titre, 'url' => $url])]);
		}
		try {
			$x = HfbRenduTest::xpath(recuperer_fond('inclure/menu-libre', ['identifiant' => 'astra-test-interface']));
			$ul = '//body/div[@class="footer-nav-wrap"]/' . HfbRenduTest::classe('ast-nav-menu astra-footer-horizontal-menu choix-libre');
			$this->assertSame(1, $x->query($ul)->length);
			$this->assertSame(['Mentions', 'Contact'], array_map(fn($a) => trim($a->textContent), iterator_to_array($x->query($ul . '/li/a[contains(@class,"menu-link")]'))));
			$this->assertSame(0, $x->query('//nav')->length, 'la liste seule : le nav appartient à l’appelant');
			$vertical = HfbRenduTest::xpath(recuperer_fond('inclure/menu-libre', ['identifiant' => 'astra-test-interface', 'disposition' => 'vertical']));
			$this->assertSame(1, $vertical->query('//' . HfbRenduTest::classe('astra-footer-vertical-menu'))->length);
			$this->assertSame('', trim(recuperer_fond('inclure/menu-libre', ['identifiant' => 'menu-absent'])), 'menu inconnu : rien');
		} finally {
			sql_delete('spip_menus_entrees', 'id_menu=' . $menu);
			sql_delete('spip_menus', 'id_menu=' . $menu);
		}
	}
}

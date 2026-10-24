<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_compatibilite.php';

/**
 * Drapeaux de compatibilité d'Astra 4.11.0 (méta astra/compatibilite) : normalisation et choix des
 * règles qui en dépendent.
 */
final class CompatibiliteTest extends TestCase {

	public function testNormalisation(): void {
		$this->assertSame([], astra_compatibilite_normaliser(null));
		$this->assertSame([], astra_compatibilite_normaliser(['inconnu' => true, 'submenu-below-header' => 'non']));
		// Valeurs du site.json de Garden Maintenance : can-support-widget-and-editor-fonts vaut false,
		// mais Astra ne teste que sa présence.
		$this->assertSame(
			['submenu-below-header' => false, 'can-support-widget-and-editor-fonts' => true],
			astra_compatibilite_normaliser(['submenu-below-header' => false, 'can-support-widget-and-editor-fonts' => false])
		);
		$this->assertSame(['submenu-below-header' => true], astra_compatibilite_normaliser(['submenu-below-header' => '1']));
	}

	public function testFeuilleDuSousMenu(): void {
		// Sans réglage, ou drapeau vrai : règles d'une installation récente (rendu inchangé).
		$this->assertSame('css/compat/sous-menu-fix.css', astra_compatibilite_feuille_sous_menu([]));
		$this->assertSame('css/compat/sous-menu-fix.css', astra_compatibilite_feuille_sous_menu(['submenu-below-header' => true]));
		$this->assertSame('css/compat/sous-menu-ancien.css', astra_compatibilite_feuille_sous_menu(['submenu-below-header' => false]));
		foreach (['fix', 'ancien'] as $variante) {
			$this->assertFileExists(dirname(__DIR__, 2) . "/css/compat/sous-menu-$variante.css");
		}
	}

	public function testReglesDuSousMenuSortiesDuCssFige(): void {
		// Les règles qui dépendent du drapeau ne sont plus dans css/astra-dynamic.css.
		$fige = file_get_contents(dirname(__DIR__, 2) . '/css/astra-dynamic.css');
		$this->assertStringNotContainsString('.main-header-menu .menu-item, #astra-footer-menu .menu-item', $fige);
		$this->assertStringContainsString('.main-header-menu .menu-item, #astra-footer-menu .menu-item', file_get_contents(dirname(__DIR__, 2) . '/css/compat/sous-menu-fix.css'));
	}

	public function testPolicesAnciennes(): void {
		$this->assertFalse(astra_compatibilite_polices_anciennes([]));
		$this->assertTrue(astra_compatibilite_polices_anciennes(astra_compatibilite_normaliser(['can-support-widget-and-editor-fonts' => false])));
	}
}

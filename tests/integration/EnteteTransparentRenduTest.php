<?php

use PHPUnit\Framework\TestCase;

/**
 * En-tête transparent dans les gabarits : classe ast-theme-transparent-header du body et bloc
 * astra-transparent-inline-css du head, seulement sur les pages où il est actif.
 */
final class EnteteTransparentRenduTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/PagesGabaritTest.php';
	}

	protected function tearDown(): void {
		foreach (['styles', 'pages', 'accueil_type', 'accueil_page'] as $cle) {
			effacer_config('astra/' . $cle);
		}
	}

	/** [classe présente, bloc CSS présent] pour un rendu. */
	private function transparent(string $html): array {
		$x = PagesGabaritTest::analyser($html);
		$classes = explode(' ', $x->query('//body')->item(0)->getAttribute('class'));
		$style = $x->query('//style[@id="astra-transparent-inline-css"]');
		return [in_array('ast-theme-transparent-header', $classes, true), $style->length === 1 && strpos($style->item(0)->textContent, '#masthead{position:absolute;') !== false];
	}

	private function rubrique(): string {
		return recuperer_fond('rubrique', ['id_rubrique' => (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre=' . sql_quote('10. Jardinage'))]);
	}

	private function article(): string {
		return recuperer_fond('article', ['id_article' => (int) sql_getfetsel('id_article', 'spip_articles', 'titre=' . sql_quote('Article du potager'))]);
	}

	public function testRienSansReglage(): void {
		$page = PagesGabaritTest::id('notre_jardin');
		foreach ([recuperer_fond('article', ['id_article' => $page]), $this->article(), $this->rubrique(), recuperer_fond('sommaire', [])] as $html) {
			$this->assertSame([false, false], $this->transparent($html));
		}
	}

	public function testPageActivee(): void {
		$page = PagesGabaritTest::id('notre_jardin');
		ecrire_config('astra/pages/' . $page, ['transparent' => 'active']);
		$this->assertSame([true, true], $this->transparent(recuperer_fond('article', ['id_article' => $page])));
		$this->assertSame([false, false], $this->transparent(recuperer_fond('article', ['id_article' => PagesGabaritTest::id('mentions')])));
		// Accueil statique : réglage de la page choisie.
		ecrire_config('astra/accueil_type', 'page');
		ecrire_config('astra/accueil_page', $page);
		$this->assertSame([true, true], $this->transparent(recuperer_fond('sommaire', [])));
	}

	public function testReglageGlobalEtExclusions(): void {
		ecrire_config('astra/styles', ['transparent-header-enable' => 1]);
		$this->assertSame([true, true], $this->transparent($this->article()));
		$this->assertSame([true, true], $this->transparent(recuperer_fond('article', ['id_article' => PagesGabaritTest::id('mentions')])));
		// Exclusions par défaut d'Astra : archives et accueil des derniers articles.
		$this->assertSame([false, false], $this->transparent($this->rubrique()));
		$this->assertSame([false, false], $this->transparent(recuperer_fond('sommaire', [])));
		// Une page désactivée l'emporte sur le réglage global.
		ecrire_config('astra/pages/' . PagesGabaritTest::id('mentions'), ['transparent' => 'desactive']);
		$this->assertSame([false, false], $this->transparent(recuperer_fond('article', ['id_article' => PagesGabaritTest::id('mentions')])));
	}
}

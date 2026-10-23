<?php

use PHPUnit\Framework\TestCase;

/** Page d'accueil statique. */
final class PagesAccueilTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/PagesGabaritTest.php';
		include_spip('inc/astra_pages');
	}

	protected function tearDown(): void {
		effacer_config('astra/accueil_type');
		effacer_config('astra/accueil_page');
		sql_updateq('spip_articles', ['statut' => 'publie'], 'id_article=' . PagesGabaritTest::id('notre_jardin'));
		set_request('accueil_type', null);
		set_request('accueil_page', null);
	}

	private function accueil(): DOMXPath {
		return PagesGabaritTest::analyser(recuperer_fond('sommaire', []));
	}

	private function classes(DOMXPath $x): array {
		return explode(' ', $x->query('//body')->item(0)->getAttribute('class'));
	}

	public function testAccueilParDefaut(): void {
		$this->assertContains('blog', $this->classes($this->accueil()));
	}

	public function testAccueilStatique(): void {
		$id = PagesGabaritTest::id('notre_jardin');
		ecrire_config('astra/accueil_type', 'page');
		ecrire_config('astra/accueil_page', $id);
		$x = $this->accueil();
		$classes = $this->classes($x);
		$this->assertContains('home', $classes);
		$this->assertContains('page-id-' . $id, $classes);
		$this->assertSame('Notre jardin', trim($x->query('//h1[@class="entry-title"]')->item(0)->textContent));
		$this->assertSame(0, $x->query('//link[@rel="canonical"]')->length);
		$propre = PagesGabaritTest::page($id);
		$this->assertSame(1, $propre->query('//link[@rel="canonical"]')->length, 'URL canonique vers l\'accueil sur l\'adresse de la page');
	}

	public function testRepliSiLaPageNEstPlusPubliee(): void {
		$id = PagesGabaritTest::id('notre_jardin');
		ecrire_config('astra/accueil_type', 'page');
		ecrire_config('astra/accueil_page', $id);
		sql_updateq('spip_articles', ['statut' => 'prop'], 'id_article=' . $id);
		$this->assertContains('blog', $this->classes($this->accueil()));
	}

	public function testVerificationDuFormulaire(): void {
		include_spip('formulaires/configurer_astra');
		set_request('accueil_type', 'page');
		set_request('accueil_page', (string) PagesGabaritTest::id('notre_jardin'));
		$this->assertSame([], formulaires_configurer_astra_verifier_dist());
		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'id_rubrique>0', '', 'id_article', '0,1');
		set_request('accueil_page', (string) $article);
		$this->assertArrayHasKey('accueil_page', formulaires_configurer_astra_verifier_dist());
		set_request('accueil_page', '999999');
		$this->assertArrayHasKey('accueil_page', formulaires_configurer_astra_verifier_dist());
		set_request('accueil_type', 'autre');
		$this->assertArrayHasKey('accueil_type', formulaires_configurer_astra_verifier_dist());
	}
}

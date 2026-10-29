<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Éléments de l'enveloppe de page (docs/interface-gabarits.md) : page-debut et page-fin encadrent #page ;
 * masthead, panneau-lateral et colophon sont équilibrés ; header et footer les composent sans changer leur sortie.
 */
final class EnveloppeTest extends TestCase {

	private const CONTEXTE = ['type_page' => 'article', 'id_rubrique' => 1, 'id_secteur' => 1, 'id_article' => 1];

	protected function tearDown(): void {
		effacer_config('astra/hfb');
	}

	private static function rendu(string $fond): string {
		return recuperer_fond('inclure/' . $fond, self::CONTEXTE);
	}

	/** Différence entre ouvertures et fermetures de chaque élément : 0 si le fragment est équilibré. */
	private static function solde(string $html): array {
		$solde = [];
		foreach (['div', 'header', 'footer', 'nav', 'ul', 'li', 'button'] as $tag) {
			$n = preg_match_all("~<$tag\\b~", $html) - substr_count($html, "</$tag>");
			if ($n) {
				$solde[$tag] = $n;
			}
		}
		return $solde;
	}

	public static function modes(): array {
		return ['déroulant' => [null], 'latéral' => ['R25-nav-offcanvas']];
	}

	#[DataProvider('modes')]
	public function testCompatibiliteDeHeaderEtFooter(?string $reference): void {
		if ($reference) {
			require_once dirname(__DIR__) . '/comparaison/configurations.php';
			ecrire_config('astra/hfb', astra_configuration_hfb(astra_configurations_reference()[$reference]));
		}
		$this->assertSame(self::rendu('page-debut') . "\n" . self::rendu('masthead'), self::rendu('header'));
		$lateral = self::rendu('panneau-lateral');
		$this->assertSame(($lateral === '' ? '' : $lateral . "\n") . self::rendu('colophon') . "\n" . self::rendu('page-fin'), ltrim(self::rendu('footer')));
		foreach (['masthead', 'panneau-lateral', 'colophon'] as $fond) {
			$this->assertSame([], self::solde(self::rendu($fond)), $fond);
		}
		$this->assertSame([], self::solde(self::rendu('page-debut') . self::rendu('page-fin')), 'page-debut + page-fin');
		$this->assertSame(['div' => 1], self::solde(self::rendu('page-debut')), '#page ouvert');
	}

	public function testEnveloppeSansBuilder(): void {
		$html = self::rendu('page-debut') . '<header id="bloc">Fragment</header><main id="main">Contenu</main>' . self::rendu('page-fin');
		$x = HfbRenduTest::xpath($html);
		$this->assertSame(1, $x->query('//div[@id="page"]/header[@id="bloc"]')->length, 'région placée dans #page');
		$this->assertSame(0, $x->query('//*[@id="masthead" or @id="colophon"]')->length, 'aucun élément du builder');
		$this->assertSame(1, $x->query('//script[@id="astra-theme-js-js"]')->length, 'scripts du thème chargés');
		$this->assertSame(0, $x->query('//div[@id="page"]//script[@id="astra-theme-js-js"]')->length, 'scripts après #page');
	}
}

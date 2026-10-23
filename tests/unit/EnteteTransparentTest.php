<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_entete_transparent.php';
require_once __DIR__ . '/StylesAttendusTest.php';

/**
 * En-tête transparent : règle d'activation (is_transparent_header() d'Astra 4.11.0) et CSS,
 * comparé aux règles capturées sur WordPress (tests/references/transparent/attendus/).
 */
final class EnteteTransparentTest extends TestCase {

	private const DIR = __DIR__ . '/../references/transparent';

	public function testRienSansReglage(): void {
		foreach (['page', 'article', 'accueil_articles', 'rubrique', 'mot', 'auteur', 'recherche', '404'] as $contexte) {
			$this->assertFalse(astra_entete_transparent_actif([], $contexte), $contexte);
		}
	}

	public function testReglageDeLaPagePrioritaire(): void {
		$this->assertTrue(astra_entete_transparent_actif([], 'page', 'active'));
		$this->assertFalse(astra_entete_transparent_actif(['transparent-header-enable' => 1], 'page', 'desactive'));
		$this->assertTrue(astra_entete_transparent_actif(['transparent-header-enable' => 1, 'transparent-header-disable-page' => 1], 'page', 'active'));
	}

	public function testExclusionsDuReglageGlobal(): void {
		$global = ['transparent-header-enable' => 1];
		// Défauts d'Astra : archives (404, recherche, rubriques, mots, auteurs) et accueil des
		// derniers articles exclus ; pages et articles transparents.
		$this->assertTrue(astra_entete_transparent_actif($global, 'page'));
		$this->assertTrue(astra_entete_transparent_actif($global, 'article'));
		foreach (['accueil_articles', 'rubrique', 'mot', 'auteur', 'recherche', '404'] as $contexte) {
			$this->assertFalse(astra_entete_transparent_actif($global, $contexte), $contexte);
		}
		$sans_exclusion = $global + ['transparent-header-disable-archive' => 0, 'transparent-header-disable-latest-posts-index' => 0];
		foreach (['accueil_articles', 'rubrique', 'recherche', '404'] as $contexte) {
			$this->assertTrue(astra_entete_transparent_actif($sans_exclusion, $contexte), $contexte);
		}
		// Options séparées : elles l'emportent sur transparent-header-disable-archive.
		$this->assertTrue(astra_entete_transparent_actif($global + ['transparent-header-disable-404-page' => 0], '404'));
		$this->assertFalse(astra_entete_transparent_actif($sans_exclusion + ['transparent-header-disable-search-page' => 1], 'recherche'));
		$this->assertFalse(astra_entete_transparent_actif($sans_exclusion + ['transparent-header-disable-archive-pages' => 1], 'mot'));
		$this->assertFalse(astra_entete_transparent_actif($global + ['transparent-header-disable-page' => 1], 'page'));
		$this->assertFalse(astra_entete_transparent_actif($global + ['transparent-header-disable-posts' => 1], 'article'));
		// Valeur invalide : retirée par la normalisation, défaut d'Astra.
		$this->assertFalse(astra_entete_transparent_actif(['transparent-header-enable' => 'oui'], 'page'));
	}

	public static function variantes(): array {
		return ['garden' => ['garden'], 'ordinateur' => ['ordinateur'], 'mobile' => ['mobile'], 'couleurs' => ['couleurs']];
	}

	#[DataProvider('variantes')]
	public function testEquivalenceAvecWordPress(string $variante): void {
		$valeurs = json_decode(file_get_contents(self::DIR . "/valeurs/$variante.json"), true);
		$nous = StylesAttendusTest::regles(astra_entete_transparent_css($valeurs, []));
		$lignes = function (string $f): array {
			return array_values(array_filter(array_map('trim', file(self::DIR . "/attendus/$f"))));
		};
		$attendues = $lignes("$variante.css");
		$neutralisations = $lignes('neutralisations.css');
		$this->assertSame([], array_values(array_diff($attendues, $nous)), 'règles d\'Astra manquantes');
		$this->assertSame([], array_values(array_diff($nous, $attendues, $neutralisations)), 'règles ni attendues ni neutralisations');
		// Chaque règle qu'Astra n'émet plus sur une page transparente est une règle fixe de
		// css/astra-dynamic.css, défaite par une neutralisation.
		$fixes = StylesAttendusTest::regles(file_get_contents(dirname(__DIR__, 2) . '/css/astra-dynamic.css'));
		foreach ($lignes("$variante.retirees.css") as $retiree) {
			$this->assertContains($retiree, $fixes);
		}
		$this->assertCount(count($lignes("$variante.retirees.css")), $neutralisations);
	}

	public function testSeparateurParDefaut(): void {
		$sel = '.ast-theme-transparent-header #ast-desktop-header > .ast-main-header-wrap > .main-header-bar,.ast-theme-transparent-header.ast-header-break-point #ast-mobile-header > .ast-main-header-wrap > .main-header-bar';
		// Installation récente : 0 px ; drapeau transparent-header-default-border à faux : aucun.
		$this->assertStringContainsString($sel . '{border-bottom-width:0px;border-bottom-style:solid;}', astra_entete_transparent_css([], []));
		$this->assertStringContainsString($sel . '{border-bottom-style:none;}', astra_entete_transparent_css(['transparent-header-default-border' => false], []));
		$this->assertStringContainsString($sel . '{border-bottom-style:none;}', astra_entete_transparent_css(['transparent-header-default-border' => '0'], []));
		$this->assertStringContainsString($sel . '{border-bottom-width:0px;border-bottom-style:solid;}', astra_entete_transparent_css(['transparent-header-default-border' => '1'], []));
	}

	public function testDerniereRangeeRemplie(): void {
		$hfb = ['entete_ordinateur' => ['below' => ['below_center' => ['menu-2']]]];
		$this->assertSame('.ast-theme-transparent-header #ast-desktop-header > .ast-below-header-wrap > .ast-below-header', astra_entete_transparent_derniere_rangee($hfb, 'desktop'));
		$this->assertSame('.ast-theme-transparent-header.ast-header-break-point #ast-mobile-header > .ast-main-header-wrap > .main-header-bar', astra_entete_transparent_derniere_rangee($hfb, 'mobile'));
	}
}

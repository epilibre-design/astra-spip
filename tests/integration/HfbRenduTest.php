<?php

use PHPUnit\Framework\TestCase;

/**
 * Rendu de l'en-tête et du pied de page par les squelettes du builder,
 * pour des configurations transposées des références WordPress (tests/references).
 */
final class HfbRenduTest extends TestCase {

	protected function tearDown(): void {
		effacer_config('astra/hfb');
	}

	public static function setUpBeforeClass(): void {
		require_once dirname(__DIR__) . '/comparaison/configurations.php';
	}

	public static function configR1(): array {
		return astra_configurations_reference()['R1-entete-complet'];
	}

	public static function configR2(): array {
		return astra_configurations_reference()['R2-entete-centre'];
	}

	private function entete(?array $config): DOMXPath {
		effacer_config('astra/hfb');
		if ($config !== null) {
			ecrire_config('astra/hfb', $config);
		}
		$html = recuperer_fond('inclure/header', ['type_page' => 'sommaire']);
		return self::xpath($html);
	}

	public static function xpath(string $html): DOMXPath {
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>');
		libxml_clear_errors();
		return new DOMXPath($doc);
	}

	/** Expression XPath : élément portant toutes les classes données. */
	public static function classe(string $classes): string {
		$conditions = array_map(function ($c) {
			return "contains(concat(' ', normalize-space(@class), ' '), ' $c ')";
		}, preg_split('/\s+/', trim($classes)));
		return '*[' . implode(' and ', $conditions) . ']';
	}

	private function compter(DOMXPath $x, string $expression): int {
		return $x->query($expression)->length;
	}

	public function testEnTeteParDefaut(): void {
		$x = $this->entete(null);
		$d = '//*[@id="ast-desktop-header"]';
		$this->assertSame(1, $this->compter($x, $d . '[@data-toggle-type="dropdown"]'));
		$this->assertSame(0, $this->compter($x, $d . '//' . self::classe('ast-above-header-wrap')));
		$grille = $d . '//' . self::classe('site-primary-header-wrap') . '/' . self::classe('ast-builder-grid-row ast-builder-grid-row-has-sides ast-builder-grid-row-no-center');
		$this->assertSame(1, $this->compter($x, $grille));
		$this->assertSame(1, $this->compter($x, $grille . '/' . self::classe('site-header-primary-section-left') . '//' . self::classe('site-branding')));
		$this->assertSame(1, $this->compter($x, $grille . '/' . self::classe('site-header-primary-section-right') . '/' . self::classe('ast-builder-menu-1')));
		$this->assertSame(0, $this->compter($x, $d . '//' . self::classe('ast-grid-right-center-section')));
		$this->assertGreaterThan(0, $this->compter($x, $d . '//' . self::classe('ast-builder-menu-1') . '//ul[contains(@class, "main-header-menu")]/li'));
	}

	public function testEnTeteR1(): void {
		$x = $this->entete(self::configR1());
		$d = '//*[@id="ast-desktop-header"]';

		$haute = $d . '//' . self::classe('ast-above-header-wrap') . '//' . self::classe('site-above-header-wrap ast-builder-grid-row-container ast-container');
		$this->assertSame(1, $this->compter($x, $haute));
		$this->assertSame(1, $this->compter($x, $haute . '//' . self::classe('site-header-above-section-left') . '//' . self::classe('ast-header-html-1') . '//' . self::classe('ast-builder-html-element')));
		$this->assertSame(1, $this->compter($x, $haute . '//' . self::classe('site-header-above-section-right') . '//' . self::classe('ast-header-social-1-wrap ast-header-social-wrap')));
		$this->assertSame(3, $this->compter($x, $haute . '//' . self::classe('ast-builder-social-element header-social-item')));

		$droite = $d . '//' . self::classe('site-header-primary-section-right');
		$ordre = [];
		foreach ($x->query($droite . '/*') as $n) {
			$ordre[] = preg_match('/ast-builder-menu-1|ast-header-search|ast-header-button-1/', $n->getAttribute('class'), $m) ? $m[0] : $n->getAttribute('class');
		}
		$this->assertSame(['ast-builder-menu-1', 'ast-header-search', 'ast-header-button-1'], $ordre);
		$this->assertSame(1, $this->compter($x, $droite . '//' . self::classe('ast-header-button-1') . '//a[@class="ast-custom-button-link"][@href="https://example.org/contact"]'));
		$this->assertStringContainsString('Nous contacter', $x->query($droite . '//' . self::classe('ast-custom-button'))->item(0)->textContent);

		$basse = $d . '//' . self::classe('ast-below-header-wrap') . '//' . self::classe('ast-builder-grid-row ast-grid-center-col-layout-only ast-flex ast-grid-center-col-layout');
		$this->assertSame(1, $this->compter($x, $basse . '/' . self::classe('site-header-below-section-center') . '/' . self::classe('ast-builder-menu-2')));

		$texte = $x->query($haute . '//' . self::classe('ast-builder-html-element'))->item(0)->textContent;
		$this->assertStringContainsString('lundi', $texte);
		$this->assertSame(1, $this->compter($x, $haute . '//' . self::classe('ast-builder-html-element') . '//strong'), 'Le texte libre est traité par propre()');
	}

	public function testEnTeteR2(): void {
		$x = $this->entete(self::configR2());
		$d = '//*[@id="ast-desktop-header"]';
		$this->assertSame(1, $this->compter($x, $d . '[@data-toggle-type="off-canvas"]'));
		$grille = $d . '//' . self::classe('site-primary-header-wrap') . '/' . self::classe('ast-builder-grid-row ast-builder-grid-row-has-sides ast-grid-center-col-layout');
		$this->assertSame(1, $this->compter($x, $grille));
		$this->assertSame(1, $this->compter($x, $grille . '/' . self::classe('site-header-primary-section-left') . '/' . self::classe('site-header-primary-section-left-center ast-grid-left-center-section')));
		$this->assertSame(1, $this->compter($x, $grille . '/' . self::classe('site-header-primary-section-center ast-grid-section-center') . '//' . self::classe('site-branding')));
		$this->assertSame(1, $this->compter($x, $grille . '/' . self::classe('site-header-primary-section-right') . '/' . self::classe('site-header-primary-section-right-center ast-grid-right-center-section')));
		$this->assertSame(1, $this->compter($x, $grille . '//' . self::classe('ast-header-account') . '//' . self::classe('ast-header-account-wrap') . '//a'));
		$widget = $d . '//' . self::classe('site-header-below-section-left') . '/aside[contains(@class, "header-widget-area")]';
		$this->assertSame(1, $this->compter($x, $widget));
		$this->assertStringContainsString('Horaires', $x->query($widget . '//*[contains(@class, "widget-title")]')->item(0)->textContent);
		$this->assertSame(1, $this->compter($x, $d . '//' . self::classe('site-header-below-section-right') . '//' . self::classe('ast-header-html-2')));
	}

	public function testMenuAvecSousMenu(): void {
		$x = $this->entete(self::configR1());
		$menu = '//*[@id="ast-desktop-header"]//' . self::classe('ast-builder-menu-1') . '//ul[contains(@class, "main-header-menu")]';
		$this->assertGreaterThan(0, $this->compter($x, $menu . '/li[contains(@class, "menu-item-has-children")]/ul[@class="sub-menu"]/li/a'));
		$this->assertSame(1, $this->compter($x, '//*[@id="ast-desktop-header"]//' . self::classe('ast-builder-menu-2') . '//ul[@id="ast-hf-menu-2"]'));
	}
}

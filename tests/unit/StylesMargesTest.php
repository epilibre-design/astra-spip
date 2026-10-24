<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';
require_once __DIR__ . '/StylesAttendusTest.php';

/**
 * Marges des rangées du builder : règles égales, dans l'ordre, aux règles de marges qu'Astra 4.11.0
 * émet pour les références R24 (tests/references/styles/attendus/marges-*.css, extraites par
 * marges.py), avec le placement de chaque référence ; normalisation et conditions d'émission.
 */
final class StylesMargesTest extends TestCase {

	/** Placement des références des styles (tests/references/styles/appliquer.php). */
	private const STYLES = [
		'entete_ordinateur' => ['primary' => ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'button-1']]],
		'pied' => [
			'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']],
			'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']],
		],
		'rangees' => ['pied_primary' => ['colonnes' => 4], 'pied_below' => ['colonnes' => 2]],
	];

	private static function valeurs(string $variante): array {
		return json_decode(file_get_contents(dirname(__DIR__) . "/references/styles/valeurs/$variante.json"), true);
	}

	/** Variante => placement de la référence WordPress R24 correspondante (tests/references/wp). */
	public static function variantes(): array {
		$toutes = array_replace_recursive(self::STYLES, [
			'entete_ordinateur' => ['above' => ['above_left' => ['html-1'], 'above_right' => ['social-icons-1']], 'below' => ['below_center' => ['menu-2']]],
			'entete_mobile' => ['above' => ['above_center' => ['html-1']]],
			'pied' => ['above' => ['above_1' => ['html-1']]],
			'rangees' => ['pied_above' => ['colonnes' => 1]],
		]);
		$vides = self::STYLES;
		$vides['entete_mobile'] = ['above' => ['above_center' => ['html-1']]];
		$vides['pied']['primary'] = ['primary_1' => [], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_6' => ['widget-1']];
		$vides['rangees']['pied_primary'] = ['colonnes' => 6];
		return [
			'marges-pied' => ['marges-pied', self::STYLES],
			'marges-toutes' => ['marges-toutes', $toutes],
			'marges-rangees-vides' => ['marges-rangees-vides', $vides],
		];
	}

	#[DataProvider('variantes')]
	public function testReglesDAstraDansLOrdre(string $variante, array $hfb): void {
		// Astra n'applique pas trim_css() à ces règles : l'espace après la virgule des sélecteurs,
		// sans effet, est retirée comme le fait astra_styles_ecrire().
		$attendues = array_values(array_filter(array_map(function ($r) {
			return str_replace(', ', ',', trim($r));
		}, file(dirname(__DIR__) . "/references/styles/attendus/$variante.css"))));
		$this->assertNotEmpty($attendues);
		$this->assertSame($attendues, StylesAttendusTest::regles(astra_styles_css(self::valeurs($variante), $hfb)));
	}

	public function testConditionsDEmission(): void {
		$v = self::valeurs('marges-toutes');
		$haut = '.ast-above-header.ast-above-header-bar';
		$pied = '.ast-hfb-header .site-footer{';
		// Rangée haute de l'en-tête vide sur les deux appareils : rien pour elle ; pied entier toujours.
		$css = astra_styles_css($v, self::STYLES);
		$this->assertStringNotContainsString($haut, $css);
		$this->assertStringContainsString($pied, $css);
		// Remplie sur ordinateur seulement, ou sur mobile seulement : émise.
		$this->assertStringContainsString($haut, astra_styles_css($v, ['entete_ordinateur' => ['above' => ['above_right_center' => ['html-1']]]] + self::STYLES));
		$this->assertStringContainsString($haut, astra_styles_css($v, ['entete_mobile' => ['above' => ['above_left' => ['html-1']]]] + self::STYLES));
		// Rangée principale de l'en-tête vide sur les deux appareils : ni elle, ni l'en-tête entier.
		$sans = ['entete_ordinateur' => ['primary' => ['primary_left' => [], 'primary_right' => []]], 'entete_mobile' => ['primary' => ['primary_left' => [], 'primary_right' => []]]] + self::STYLES;
		$css = astra_styles_css($v, $sans);
		$this->assertStringNotContainsString('.ast-primary-header-bar.main-header-bar', $css);
		$this->assertStringNotContainsString('.ast-hfb-header .site-header', $css);
		$this->assertStringContainsString($pied, $css);
		// Rangée du pied remplie en zone 6 seulement : rien pour elle.
		$six = self::STYLES;
		$six['pied']['primary'] = ['primary_1' => [], 'primary_2' => [], 'primary_3' => [], 'primary_4' => [], 'primary_6' => ['widget-1']];
		$six['rangees']['pied_primary'] = ['colonnes' => 6];
		$this->assertStringNotContainsString('.site-primary-footer-wrap', astra_styles_css($v, $six));
		// Sans option de marge : rien.
		$this->assertSame('', astra_styles_css([], self::STYLES));
	}

	public function testCotesCommeAstraResponsiveSpacing(): void {
		$sel = '.site-below-footer-wrap[data-section="section-below-footer-builder"]';
		$css = astra_styles_css(['section-below-footer-builder-margin' => [
			'desktop' => ['top' => 0, 'right' => '', 'bottom' => '-10', 'left' => '1.5'], 'desktop-unit' => 'em',
			// Tablette sans unité enregistrée : rien n'est émis pour elle, comme dans Astra.
			'tablet' => ['top' => '7'],
			'mobile' => ['top' => '', 'right' => '', 'bottom' => '', 'left' => ''], 'mobile-unit' => 'px',
		]], self::STYLES);
		$this->assertSame($sel . '{margin-top:0em;margin-bottom:-10em;margin-left:1.5em;}', $css);
	}

	public function testNormalisation(): void {
		$v = [
			'section-primary-footer-builder-padding' => ['desktop' => ['top' => '-5', 'right' => '10', 'bottom' => '1200'], 'desktop-unit' => 'px', 'tablet-unit' => 'vh'],
			'section-primary-footer-builder-margin' => ['desktop' => ['top' => '-5', 'right' => 'x', 'bottom' => '-1200'], 'desktop-unit' => '%', 'tablet' => ['top' => '-0.5'], 'tablet-unit' => 'em'],
		];
		$this->assertSame([
			// Marge intérieure négative ou hors bornes refusée, unité inconnue refusée.
			'section-primary-footer-builder-padding' => ['desktop' => ['right' => '10'], 'desktop-unit' => 'px'],
			// Marge extérieure négative gardée (bornée à 1000 en valeur absolue).
			'section-primary-footer-builder-margin' => ['desktop' => ['top' => '-5'], 'desktop-unit' => '%', 'tablet' => ['top' => '-0.5'], 'tablet-unit' => 'em'],
		], astra_styles_normaliser($v));
		// Valeurs du modèle : conservées telles quelles.
		$garden = self::valeurs('marges-pied');
		$this->assertSame($garden, astra_styles_normaliser($garden));
		$this->assertSame(self::valeurs('marges-toutes'), astra_styles_normaliser(self::valeurs('marges-toutes')));
	}
}

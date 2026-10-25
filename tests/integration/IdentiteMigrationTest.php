<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/attendus/identite.php';

/**
 * Migration de afficher_titre_site et afficher_slogan vers les options d'Astra, sans changement de
 * rendu : le HTML de l'identité est celui de la version 1.1.0 (attendus/identite-<cas>.html),
 * hormis le slogan que css/astra-dynamic.css masquait et qu'Astra n'imprime pas quand il est
 * masqué partout (astra_logo()), et l'enveloppe du titre restée vide.
 */
final class IdentiteMigrationTest extends TestCase {

	private static string $slogan;

	public static function setUpBeforeClass(): void {
		include_spip('astra_administrations');
		self::$slogan = $GLOBALS['meta']['slogan_site'] ?? '';
	}

	protected function tearDown(): void {
		effacer_config('astra/styles');
		effacer_config('astra/afficher_slogan');
		effacer_config('astra/afficher_titre_site');
		ecrire_meta('slogan_site', self::$slogan);
	}

	public static function cas(): array {
		return array_map(function ($c) { return [$c]; }, array_combine(array_keys(ASTRA_CAS_IDENTITE), array_keys(ASTRA_CAS_IDENTITE)));
	}

	private static function visible(string $html): string {
		$html = preg_replace('~<p class="site-description".*?</p>~s', '', $html);
		$html = preg_replace('~<div class="ast-site-title-wrap">\s*</div>~', '', $html);
		return preg_replace('/\s+/', ' ', $html);
	}

	#[DataProvider('cas')]
	public function testRenduInchange(string $cas): void {
		astra_cas_identite_appliquer(ASTRA_CAS_IDENTITE[$cas]);
		astra_migrer_identite();
		$avant = file_get_contents(__DIR__ . "/attendus/identite-$cas.html");
		// La fixture historique garde localhost ; le domaine du site de test est configurable.
		$avant = str_replace('http://localhost/', rtrim($GLOBALS['meta']['adresse_site'], '/') . '/', $avant);
		$this->assertSame(self::visible($avant), self::visible(recuperer_fond('inclure/identite', [])));
		$this->assertStringNotContainsString('site-description', recuperer_fond('inclure/identite', []));
		$this->assertNull(lire_config('astra/afficher_slogan'));
		$this->assertNull(lire_config('astra/afficher_titre_site'));
	}

	#[DataProvider('cas')]
	public function testSeulLeTitreMasqueSEcrit(string $cas): void {
		astra_cas_identite_appliquer(ASTRA_CAS_IDENTITE[$cas]);
		astra_migrer_identite();
		$styles = lire_config('astra/styles');
		if ($cas === 'titre_non') {
			$this->assertSame(['display-site-title-responsive' => ['desktop' => false, 'tablet' => false, 'mobile' => false]], $styles);
		} else {
			$this->assertNull($styles);
		}
	}

	public function testSloganAfficheParLOptionDAstra(): void {
		ecrire_meta('slogan_site', 'Jardiner au naturel');
		ecrire_config('astra/styles', ['display-site-tagline-responsive' => ['desktop' => true, 'tablet' => true, 'mobile' => true]]);
		$this->assertStringContainsString('<p class="site-description" itemprop="description">Jardiner au naturel</p>', recuperer_fond('inclure/identite', []));
		$this->assertStringContainsString('.site-header .site-description{font-size:15px;font-size:0.9375rem;display:block;}', recuperer_fond('inclure/head-vars', []));
	}

	public function testLargeurDuLogo(): void {
		ecrire_config('astra/styles', ['ast-header-responsive-logo-width' => ['desktop' => '40']]);
		$this->assertStringContainsString('header .custom-logo-link img{max-width:40px;width:40px;}', recuperer_fond('inclure/head-vars', []));
	}
}

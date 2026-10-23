<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';

/** Registre, normalisation commune et sécurité des styles du Customizer. */
final class StylesTest extends TestCase {

	private static function valeurs(string $groupe): array {
		return json_decode(file_get_contents(dirname(__DIR__) . "/references/styles/valeurs/$groupe.json"), true);
	}

	public function testRegistreComplet(): void {
		foreach (astra_styles_registre() as $option => $d) {
			foreach (['groupe', 'source', 'type', 'responsive'] as $champ) {
				$this->assertArrayHasKey($champ, $d, "$option : $champ");
			}
			$this->assertArrayHasKey('defaut', $d, $option);
			if (in_array($d['type'], ['taille'], true)) {
				$this->assertNotEmpty($d['unites'] ?? [], "$option : unités");
			}
			if ($d['type'] === 'enum') {
				$this->assertNotEmpty($d['valeurs'] ?? [], "$option : valeurs");
			}
		}
	}

	/** Aller-retour sans perte des structures d'origine d'Astra (valeurs du site.json de Garden). */
	public function testStructuresDAstraConservees(): void {
		foreach (['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux', 'menu-mobile'] as $groupe) {
			$v = array_intersect_key(self::valeurs($groupe), astra_styles_registre());
			$this->assertSame($v, astra_styles_normaliser($v), $groupe);
		}
	}

	public function testChampInvalideRetireSeul(): void {
		$v = [
			'header-menu1-bg-obj-responsive' => [
				'desktop' => ['background-color' => '#fff', 'background-repeat' => 'nimporte', 'champ-inconnu' => 1],
				'tablet' => ['background-color' => 'url(x)'],
			],
			'font-size-button' => ['desktop' => '14', 'desktop-unit' => 'pt', 'tablet' => '-3', 'mobile' => '12'],
			'button-radius-fields' => ['desktop' => ['top' => 8, 'right' => 'x', 'autre' => 2], 'desktop-unit' => 'px'],
			'font-extras-button' => ['text-transform' => 'capitalize', 'text-decoration' => 'blink'],
			'footer-social-1-color-type' => 'arc-en-ciel',
		];
		$this->assertSame([
			'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => '#fff'], 'tablet' => []],
			'font-size-button' => ['desktop' => '14', 'mobile' => '12'],
			'button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'px'],
			'font-extras-button' => ['text-transform' => 'capitalize'],
		], astra_styles_normaliser($v));
	}

	public function testValeursTabletteEtMobileConservees(): void {
		$v = ['header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222', 'mobile' => 'var(--ast-global-color-3)']];
		$this->assertSame($v, astra_styles_normaliser($v));
	}

	public function testCouleurs(): void {
		foreach (['', '#abc', '#A1B2C3', 'var(--ast-global-color-8)', 'rgba(64,191,79,0.15)', 'rgb(0, 0, 0)', 'rgba(255,255,255,1)'] as $c) {
			$this->assertTrue(astra_styles_couleur_valide($c), $c);
		}
		foreach (['red', '#abcd', 'var(--x)', 'rgba(300,0,0,1)', 'rgb(0,0,0,1)', 'rgba(0,0,0)', '#fff;}body{', 'expression(1)'] as $c) {
			$this->assertFalse(astra_styles_couleur_valide($c), $c);
		}
	}

	public function testTaillePoliceCommeAstra(): void {
		$this->assertSame('14px;font-size:0.875rem', astra_styles_taille_police(['desktop' => '14', 'desktop-unit' => 'px']));
		$this->assertSame('1.2em', astra_styles_taille_police(['desktop' => '1.2', 'desktop-unit' => 'em']));
		$this->assertSame('', astra_styles_taille_police(['desktop' => '']));
		$this->assertSame('16px;font-size:1rem', astra_styles_taille_police('', 16));
	}

	public function testRienSansReglage(): void {
		$this->assertSame('', astra_styles_css([], []));
		$this->assertSame('', astra_styles_css(['inconnue' => 'x', 'button-color' => 'red;}'], []));
	}

	public function testInjectionRefusee(): void {
		$tentatives = [';}body{display:none', '</style><script>', 'url(javascript:x)', 'expression(alert(1))'];
		foreach ($tentatives as $t) {
			$v = [
				'button-color' => $t,
				'font-family-button' => $t,
				'font-size-button' => ['desktop' => $t],
				'button-radius-fields' => ['desktop' => ['top' => $t]],
				'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => $t, 'background-image' => $t]],
				'font-extras-button' => ['letter-spacing' => $t, 'text-transform' => $t],
				'footer-social-1-color-type' => $t,
			];
			$css = astra_styles_css($v, []);
			foreach (['}body', '<', 'url(', 'expression('] as $interdit) {
				$this->assertStringNotContainsString($interdit, $css, $t);
			}
		}
	}

	public function testEcritureCommeAstraParseCss(): void {
		$this->assertSame(
			'a,b{color:red;top:0;}@media (max-width:921px){c{x:1;}d{y:2;}}e{z:3;}',
			astra_styles_ecrire([
				['', 'a, b', ['color' => 'red', 'vide' => '', 'top' => '0']],
				['', 'f', ['vide' => '']],
				['(max-width:921px)', 'c', ['x' => '1']],
				['(max-width:921px)', 'd', ['y' => '2']],
				['', 'e', ['z' => '3']],
			])
		);
	}

	private const HFB_MENU_BOUTON = ['entete_ordinateur' => ['primary' => ['primary_right' => ['menu-1', 'button-1']]]];

	public function testValeurSansOrdinateurIgnoree(): void {
		// Astra lit la clé desktop seule : une valeur tablette ne s'applique pas sur ordinateur.
		$this->assertSame('', astra_styles_ordi(['tablet' => '18', 'desktop-unit' => 'px']));
		$this->assertSame('', astra_styles_taille_police(['tablet' => '18']));
		$this->assertSame(['top' => 1], astra_styles_ordi(['top' => 1]));
		$s = astra_styles_normaliser([
			'font-size-button' => ['tablet' => '18'],
			'font-size-site-title' => ['desktop' => 'invalide', 'tablet' => '18', 'desktop-unit' => 'px'],
			'button-color' => '#123456',
		]);
		$css = astra_styles_css($s, []);
		$this->assertStringNotContainsString('1px', $css);
		$this->assertStringContainsString('font-size:16px;font-size:1rem', $css);
	}

	public function testBordureSousMenuCommeAstra(): void {
		// Bordure enregistrée : un côté vide vaut 0 (empty() d'Astra), le haut décale les sous-menus imbriqués.
		$base = ['header-menu1-color-responsive' => ['desktop' => '#111111']];
		$css = astra_styles_css($base + ['header-menu1-submenu-border' => ['top' => 10, 'bottom' => '', 'left' => '', 'right' => '']], self::HFB_MENU_BOUTON);
		$this->assertStringContainsString('{border-top-width:10px;border-bottom-width:0px;border-right-width:0px;border-left-width:0px;', $css);
		$this->assertStringContainsString('.ast-builder-menu-1 .sub-menu .sub-menu{top:-10px;}', $css);
		$this->assertStringContainsString(':before{height:calc( 0px + 10px + 5px );}', $css);
		$css = astra_styles_css($base + ['header-menu1-submenu-border' => ['top' => '', 'bottom' => '', 'left' => '', 'right' => '']], self::HFB_MENU_BOUTON);
		$this->assertStringContainsString('{border-top-width:0px;', $css);
		$this->assertStringContainsString('.sub-menu .sub-menu{top:0px;}', $css);
		// Option absente : défaut d'Astra, 2 px en haut.
		$css = astra_styles_css($base, self::HFB_MENU_BOUTON);
		$this->assertStringContainsString('{border-top-width:2px;border-bottom-width:0px;', $css);
		$this->assertStringContainsString('.sub-menu .sub-menu{top:-2px;}', $css);
	}

	public function testRayonsBoutonEntetePartiels(): void {
		// Un seul coin réglé : les coins vides prennent le rayon du bouton global, pas les 40 px d'astra-hfb.css.
		$css = astra_styles_css([
			'header-button1-border-radius-fields' => ['desktop' => ['top' => 5, 'right' => '', 'bottom' => '', 'left' => ''], 'desktop-unit' => 'px'],
			'button-radius-fields' => ['desktop' => ['top' => 3, 'right' => 3, 'bottom' => 3, 'left' => 3], 'desktop-unit' => 'px'],
		], self::HFB_MENU_BOUTON);
		$this->assertStringContainsString('.ast-header-button-1 .ast-custom-button{border-top-left-radius:5px;border-top-right-radius:3px;border-bottom-right-radius:3px;border-bottom-left-radius:3px;}', $css);
	}

	public function testSoulignementBouton(): void {
		$css = astra_styles_css(['font-extras-button' => ['text-decoration' => 'underline']], []);
		// Blocs, boutons du thème, puis surcharge du soulignement des liens de contenu.
		$this->assertSame(3, substr_count($css, 'text-decoration:underline;'));
		$this->assertStringContainsString(',body .entry-content .wp-block-file .wp-block-file__button{text-decoration:underline;}', $css);
		$css = astra_styles_css(['button-color' => '#123456'], []);
		$this->assertStringContainsString(',body .entry-content .wp-block-file .wp-block-file__button{text-decoration:none;}', $css);
	}

	public function testBoutonDuMenuMobileCommeAstra(): void {
		$sel = '[data-section="section-header-mobile-trigger"] .ast-button-wrap ';
		// Plein : icône blanche par défaut, fond de la couleur du thème.
		$css = astra_styles_css(['mobile-header-toggle-btn-style' => 'fill'], []);
		$this->assertStringContainsString($sel . '.mobile-menu-toggle-icon .ast-mobile-svg{width:20px;height:20px;fill:#ffffff;}', $css);
		$this->assertStringContainsString($sel . '.ast-mobile-menu-trigger-fill{color:#ffffff;border:none;background:var(--ast-global-color-0);}', $css);
		// Minimal (défaut) : seule la couleur change.
		$css = astra_styles_css(['mobile-header-toggle-btn-color' => '#123456'], []);
		$this->assertStringContainsString($sel . '.ast-mobile-menu-trigger-minimal{color:#123456;border:none;background:transparent;}', $css);
		// Contour sans épaisseur enregistrée : 1 px, défaut d'Astra ; bordure de la couleur du fond.
		$css = astra_styles_css(['mobile-header-toggle-btn-style' => 'outline', 'mobile-header-toggle-btn-bg-color' => '#abcdef'], []);
		$this->assertStringContainsString('.ast-mobile-menu-trigger-outline{background:transparent;color:var(--ast-global-color-0);border-top-width:1px;border-bottom-width:1px;border-right-width:1px;border-left-width:1px;border-style:solid;border-color:#abcdef;}', $css);
		// Sans bouton de menu placé : rien.
		$this->assertSame('', astra_styles_css(['mobile-header-toggle-btn-style' => 'fill'], ['entete_mobile' => ['primary' => ['primary_right' => []]]]));
		$this->assertSame([], astra_styles_normaliser(['mobile-header-toggle-btn-style' => 'plein']));
	}
}

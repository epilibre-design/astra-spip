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
		$config = self::configR1();
		$config['elements']['entete']['html-1']['texte'] = 'Ouvert du {{lundi}} au vendredi';
		$x = $this->entete($config);
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

	private function pied(?array $config): DOMXPath {
		effacer_config('astra/hfb');
		if ($config !== null) {
			ecrire_config('astra/hfb', $config);
		}
		return self::xpath(recuperer_fond('inclure/footer', ['type_page' => 'sommaire']));
	}

	public function testEnTeteMobileR1EtPanneauDeroulant(): void {
		$x = $this->entete(self::configR1());
		$m = '//*[@id="ast-mobile-header"]';
		$this->assertSame(1, $this->compter($x, $m . '[@data-type="dropdown"]'));
		$haute = $m . '/' . self::classe('ast-above-header-wrap') . '/' . self::classe('ast-above-header-bar site-above-header-wrap ast-builder-grid-row-layout-default');
		$this->assertSame(1, $this->compter($x, $haute . '/' . self::classe('ast-builder-grid-row ast-grid-center-col-layout-only ast-grid-center-col-layout') . '/' . self::classe('site-header-above-section-center') . '//' . self::classe('ast-header-html-1')));
		$principale = $m . '/' . self::classe('ast-main-header-wrap main-header-bar-wrap') . '/' . self::classe('ast-primary-header-bar main-header-bar site-primary-header-wrap');
		$this->assertSame(1, $this->compter($x, $principale . '//' . self::classe('site-header-primary-section-right') . '//' . self::classe('ast-header-search')));
		$this->assertSame(1, $this->compter($x, $principale . '//' . self::classe('site-header-primary-section-right') . '//button[contains(@class, "menu-toggle")]'));
		$panneau = $m . '/' . self::classe('ast-mobile-header-content content-align-flex-start');
		$this->assertGreaterThan(0, $this->compter($x, $panneau . '/' . self::classe('ast-builder-menu-mobile') . '//ul[@id="ast-hf-mobile-menu"]/li'));
		$this->assertSame(0, $this->compter($this->pied(self::configR1()), '//*[@id="ast-mobile-popup-wrapper"]'));
	}

	public function testPanneauLateralR2DansLePied(): void {
		$x = $this->entete(self::configR2());
		$m = '//*[@id="ast-mobile-header"]';
		$this->assertSame(1, $this->compter($x, $m . '[@data-type="off-canvas"]'));
		$this->assertSame(0, $this->compter($x, $m . '//' . self::classe('ast-mobile-header-content')));
		$this->assertSame(1, $this->compter($x, $m . '//' . self::classe('ast-builder-grid-row ast-builder-grid-row-has-sides ast-grid-center-col-layout') . '/' . self::classe('site-header-primary-section-center') . '//' . self::classe('site-branding')));

		$p = $this->pied(self::configR2());
		$tiroir = '//*[@id="ast-mobile-popup-wrapper"]/*[@id="ast-mobile-popup"][contains(@class, "ast-mobile-popup-drawer")]';
		$this->assertSame(1, $this->compter($p, $tiroir . '/' . self::classe('ast-mobile-popup-overlay')));
		$this->assertSame(1, $this->compter($p, $tiroir . '/' . self::classe('ast-mobile-popup-inner') . '/' . self::classe('ast-mobile-popup-header') . '/button[@id="menu-toggle-close"]'));
		$contenu = $tiroir . '/' . self::classe('ast-mobile-popup-inner') . '/' . self::classe('ast-mobile-popup-content');
		$ordre = [];
		foreach ($p->query($contenu . '/*') as $n) {
			$ordre[] = preg_match('/ast-builder-menu-mobile|ast-header-search/', $n->getAttribute('class'), $mm) ? $mm[0] : $n->getAttribute('class');
		}
		$this->assertSame(['ast-builder-menu-mobile', 'ast-header-search'], $ordre);
		$this->assertSame(1, $this->compter($p, '//*[@id="ast-mobile-popup-wrapper"]/following-sibling::footer[@id="colophon"]'));
	}

	public function testAucunPanneauSansBoutonDeMenu(): void {
		$config = ['entete_mobile' => ['primary' => ['primary_right' => []]]];
		$this->assertSame(0, $this->compter($this->entete($config), '//' . self::classe('ast-mobile-header-content')));
		$config['menu_mobile'] = 'off-canvas';
		$this->assertSame(0, $this->compter($this->pied($config), '//*[@id="ast-mobile-popup-wrapper"]'));
	}

	public function testPiedParDefaut(): void {
		$p = $this->pied(null);
		$this->assertSame(1, $this->compter($p, '//footer[@id="colophon"]/*'));
		$basse = '//footer[@id="colophon"]/' . self::classe('site-below-footer-wrap ast-builder-grid-row-container ast-builder-grid-row-full ast-footer-row-stack');
		$this->assertSame(1, $this->compter($p, $basse . '/' . self::classe('ast-builder-grid-row-container-inner') . '/' . self::classe('ast-builder-footer-grid-columns site-below-footer-inner-wrap ast-builder-grid-row') . '/' . self::classe('site-footer-below-section-1 site-footer-section site-footer-section-1') . '/' . self::classe('ast-footer-copyright')));
	}

	public function testPiedR3(): void {
		$p = $this->pied(astra_configurations_reference()['R3-pied-colonnes']);
		$f = '//footer[@id="colophon"]';
		$this->assertSame(['site-above-footer-wrap', 'site-primary-footer-wrap', 'site-below-footer-wrap'], array_map(function ($n) {
			return explode(' ', $n->getAttribute('class'))[0];
		}, iterator_to_array($p->query($f . '/div'))));
		$haute = $f . '/' . self::classe('site-above-footer-wrap ast-builder-grid-row-2-equal ast-builder-grid-row-tablet-2-equal ast-builder-grid-row-mobile-full');
		$this->assertSame(1, $this->compter($p, $haute . '//' . self::classe('site-footer-above-section-1') . '/' . self::classe('footer-widget-area widget-area site-footer-focus-item ast-footer-html-1') . '//' . self::classe('ast-builder-html-element')));
		$this->assertSame(1, $this->compter($p, $haute . '//' . self::classe('site-footer-above-section-2') . '//' . self::classe('ast-footer-social-1-wrap ast-footer-social-wrap')));
		$this->assertSame(3, $this->compter($p, $haute . '//' . self::classe('ast-builder-social-element footer-social-item')));
		$principale = $f . '/' . self::classe('site-primary-footer-wrap ast-builder-grid-row-4-equal');
		$this->assertSame(4, $this->compter($p, $principale . '//' . self::classe('site-footer-section') . '/aside[contains(@class, "footer-widget-area-inner")]'));
		$this->assertStringContainsString('Colonne 4', $p->query($principale . '//' . self::classe('site-footer-primary-section-4') . '//*[contains(@class, "widget-title")]')->item(0)->textContent);
		$basse = $f . '/' . self::classe('site-below-footer-wrap ast-builder-grid-row-2-equal');
		$this->assertSame(1, $this->compter($p, $basse . '//' . self::classe('site-footer-below-section-1') . '/' . self::classe('ast-footer-copyright')));
		$menu = $basse . '//' . self::classe('site-footer-below-section-2') . '//' . self::classe('footer-nav-wrap') . '/ul[@id="astra-footer-menu"][contains(@class, "astra-footer-horizontal-menu")]';
		$this->assertGreaterThan(0, $this->compter($p, $menu . '/li'));
		$this->assertSame(0, $this->compter($p, $menu . '//ul[@class="sub-menu"]'), 'Le menu du pied n\'a qu\'un niveau');
	}

	public function testPiedR4(): void {
		$p = $this->pied(astra_configurations_reference()['R4-pied-asymetrique']);
		$f = '//footer[@id="colophon"]';
		$this->assertSame(0, $this->compter($p, $f . '/' . self::classe('site-above-footer-wrap')));
		$principale = $f . '/' . self::classe('site-primary-footer-wrap ast-builder-grid-row-3-cwide');
		$this->assertSame(3, $this->compter($p, $principale . '//' . self::classe('site-footer-section')));
		$ordre = [];
		foreach ($p->query($principale . '//' . self::classe('site-footer-primary-section-2') . '/*') as $n) {
			$ordre[] = preg_match('/ast-footer-html-1|section-fb-social-icons-1/', $n->getAttribute('class') . ' ' . $n->getAttribute('data-section'), $m) ? $m[0] : '?';
		}
		$this->assertSame(['ast-footer-html-1', 'section-fb-social-icons-1'], $ordre);
		$this->assertSame(1, $this->compter($p, $principale . '//ul[@id="astra-footer-menu"][contains(@class, "astra-footer-vertical-menu")]'));
		$this->assertSame(1, $this->compter($p, $f . '/' . self::classe('site-below-footer-wrap ast-builder-grid-row-2-lheavy') . '//' . self::classe('site-footer-below-section-2') . '/' . self::classe('ast-footer-html-2')));
	}

	public function testBlocMenuDansLesWidgetsDuPied(): void {
		// Balisage du widget nav_menu de WordPress sous Astra (référence R13-pied-widgets-menus) :
		// Écart validé à 4.13.8 : liste statique, flèche décorative, sans faux état fermé.
		// Titre, nav étiquetée et sous-pages conservés.
		$x = $this->pied(astra_configurations_reference()['R13-pied-widgets-menus']);
		$widget = '//' . self::classe('footer-widget-area') . '[@data-section="sidebar-widgets-footer-widget-2"]/section[@class="widget widget_nav_menu"]';
		$this->assertSame(1, $this->compter($x, $widget . '/h2[@class="widget-title"][.="Liens rapides"]'));
		$nav = $widget . '/nav[@aria-label="Liens rapides"]/ul[@class="menu"]';
		$this->assertSame(1, $this->compter($x, $nav), 'nav étiquetée et liste .menu');
		$parent = $nav . '/li[contains(@class, "menu-item-has-children")]';
		$this->assertSame(1, $this->compter($x, $parent . '/a[not(@aria-expanded)][@class="menu-link"]/span[@aria-hidden="true"][@class="ast-header-navigation-arrow"][not(@role)][not(@tabindex)][not(@aria-expanded)][not(*)]'));
		$this->assertSame(0, $this->compter($x, $widget . '//*[@aria-expanded or @role="application" or @tabindex or contains(@class, "dropdown-menu-toggle")]'));
		$this->assertGreaterThan(0, $this->compter($x, $parent . '/ul[@class="sub-menu"]/li/a[@class="menu-link"]'));
		$this->assertSame(0, $this->compter($x, $widget . '//button'), 'Pas de bouton de dépliage dans un widget');
		$this->assertSame(1, $this->compter($x, '//' . self::classe('footer-widget-area') . '[@data-section="sidebar-widgets-footer-widget-3"]/section[contains(@class, "widget_nav_menu")]'));
	}

	public function testCommandesDeSousMenusNommeesPourPagesEtRubriques(): void {
		foreach (['racine', 'pages'] as $source) {
			$c = self::configR1();
			foreach ([1,2] as $n) { $c['elements']['entete']['menu-' . $n] = ['source' => $source, 'id' => 0]; }
			$x = $this->entete($c);
			foreach ([1,2] as $n) {
				$selecteur = '//*[@id="ast-desktop-header"]//' . self::classe('ast-builder-menu-' . $n) . '//a/span[contains(@class,"dropdown-menu-toggle")]';
				$this->assertGreaterThan(0, $x->query($selecteur)->length, $source . ' menu ' . $n);
				foreach ($x->query($selecteur) as $commande) {
					$this->assertSame('button', $commande->getAttribute('role'));
					$this->assertSame('0', $commande->getAttribute('tabindex'));
					$this->assertSame('false', $commande->getAttribute('aria-expanded'));
					$this->assertSame('true', $commande->getAttribute('aria-haspopup'));
					$this->assertSame(_T('astra:deplier_menu'), $commande->getAttribute('aria-label'));
				}
			}
			foreach ($x->query('//button[contains(@class,"ast-menu-toggle")]') as $bouton) {
				$this->assertSame('true', $bouton->getAttribute('aria-haspopup'));
				$this->assertSame(_T('astra:deplier_menu'), $bouton->getAttribute('aria-label'));
			}
			$this->assertSame(0, $x->query('//a//button')->length, 'Aucun bouton natif dans un lien');
		}
	}

	public function testBlocMenuSansTitreNiSource(): void {
		$x = $this->pied([
			'pied' => ['primary' => ['primary_1' => ['widget-1']]],
			'elements' => ['pied' => ['widget-1' => ['blocs' => ['menu']]]],
		]);
		$widget = '//section[contains(@class, "widget_nav_menu")]';
		$this->assertSame(0, $this->compter($x, $widget . '/h2'), 'Pas de titre vide');
		$this->assertGreaterThan(1, $this->compter($x, $widget . '/nav[@aria-label="Menu"]/ul[@class="menu"]/li'), 'Rubriques racines par défaut');
	}

	public function testBlocMenuDeChaqueSource(): void {
		$liens = function (array $menu): array {
			$x = $this->pied([
				'pied' => ['primary' => ['primary_1' => ['widget-1']]],
				'elements' => ['pied' => ['widget-1' => ['blocs' => ['menu'], 'menu' => $menu]]],
			]);
			$textes = [];
			foreach ($x->query('//section[contains(@class, "widget_nav_menu")]/nav/ul[@class="menu"]/li/a') as $a) {
				$textes[] = trim($a->textContent);
			}
			return $textes;
		};
		$this->assertSame(['Jardinage', 'Actualités'], $liens(['source' => 'racine', 'id' => 0]));
		$jardinage = (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre=' . sql_quote('10. Jardinage'));
		$this->assertSame(['Potager'], $liens(['source' => 'rubrique', 'id' => $jardinage]));
		$this->assertContains('Notre jardin', $liens(['source' => 'pages', 'id' => 0]));
		// Groupe de mots-clés : articles liés à ses mots, créés pour le test.
		$groupe = sql_insertq('spip_groupes_mots', ['titre' => 'Services', 'tables_liees' => 'articles']);
		$mot = sql_insertq('spip_mots', ['titre' => 'Entretien', 'id_groupe' => $groupe, 'type' => 'Services']);
		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'titre=' . sql_quote('Article du potager'));
		sql_insertq('spip_mots_liens', ['id_mot' => $mot, 'id_objet' => $article, 'objet' => 'article']);
		try {
			$this->assertSame(['Article du potager'], $liens(['source' => 'groupe_mots', 'id' => $groupe]));
		} finally {
			sql_delete('spip_mots_liens', 'id_mot=' . (int) $mot);
			sql_delete('spip_mots', 'id_mot=' . (int) $mot);
			sql_delete('spip_groupes_mots', 'id_groupe=' . (int) $groupe);
		}
	}

	public function testIconeGoogleReviews(): void {
		// Référence R15-sociaux-google : lien, étiquette, couleur de marque du sélecteur d'Astra et SVG.
		$x = $this->pied(astra_configurations_reference()['R15-sociaux-google']);
		$lien = '//' . self::classe('ast-footer-social-1-wrap') . '//a[contains(@class, "ast-google_reviews")]';
		$this->assertSame(1, $this->compter($x, $lien . '[@href="https://example.org/avis"][@aria-label="Google Reviews"][contains(@style, "--color: #dc4e41")]/span/*[local-name()="svg"]'));
	}

	public function testMenuDEnTeteGardeSonBalisage(): void {
		$x = $this->entete(self::configR1());
		$this->assertGreaterThan(0, $this->compter($x, '//*[@id="ast-desktop-header"]//ul[contains(@class, "main-header-menu")]//li[contains(@class, "menu-item-has-children")]/button[@class="ast-menu-toggle"]'));
		$this->assertSame(0, $this->compter($x, '//*[@id="ast-desktop-header"]//ul[contains(@class, "main-header-menu")]//a[@aria-expanded]'));
	}
}

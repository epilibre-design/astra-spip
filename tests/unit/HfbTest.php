<?php

use PHPUnit\Framework\TestCase;

final class HfbTest extends TestCase {

	private static function ids(array $elements): array {
		return array_column($elements, 'id');
	}

	// Valeurs par défaut

	public function testEnTeteParDefautNeContientQueLaRangeePrincipale(): void {
		$c = astra_hfb_normaliser([]);
		$rangees = astra_hfb_calculer_entete($c['entete_ordinateur']);
		$this->assertSame(['primary'], array_column($rangees, 'rangee'));
		$this->assertTrue($rangees[0]['cotes']);
		$this->assertFalse($rangees[0]['centre']);
		$this->assertSame(['logo'], self::ids($rangees[0]['zones']['left']));
		$this->assertSame(['menu-1'], self::ids($rangees[0]['zones']['right']));
	}

	public function testConfigurationNonTableauDonneLesDefauts(): void {
		$this->assertSame(astra_hfb_normaliser([]), astra_hfb_normaliser(''));
		$this->assertSame(astra_hfb_normaliser([]), astra_hfb_normaliser(null));
	}

	// Règles de fusion

	public function testZoneAbsenteHeriteDuDefaut(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['above' => ['above_center' => ['html-1']]]]);
		$this->assertSame(['html-1'], $c['entete_ordinateur']['above']['above_center']);
		$this->assertSame(['logo'], $c['entete_ordinateur']['primary']['primary_left']);
		$this->assertSame(['menu-1'], $c['entete_ordinateur']['primary']['primary_right']);
	}

	public function testZoneExplicitementVideRetireLesElementsParDefaut(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['primary' => ['primary_right' => []]]]);
		$this->assertSame([], $c['entete_ordinateur']['primary']['primary_right']);
		$this->assertSame(['logo'], $c['entete_ordinateur']['primary']['primary_left']);
	}

	public function testReglagesDeRangeeHeritentCleParCle(): void {
		$c = astra_hfb_normaliser(['rangees' => ['entete_primary' => ['hauteur' => 100]]]);
		$this->assertSame(100, $c['rangees']['entete_primary']['hauteur']);
		$this->assertSame(1, $c['rangees']['entete_primary']['bordure']);
		$this->assertSame(50, $c['rangees']['entete_above']['hauteur']);
	}

	public function testReglagesDElementHeritentCleParCle(): void {
		$c = astra_hfb_normaliser(['elements' => ['entete' => ['menu-1' => ['id' => 3]]]]);
		$this->assertSame(['source' => 'racine', 'id' => 3], $c['elements']['entete']['menu-1']);
	}

	public function testReglagesParDefautDesElements(): void {
		$c = astra_hfb_normaliser([]);
		$this->assertSame(['facebook', 'twitter', 'instagram'], array_column($c['elements']['entete']['social-icons-1']['reseaux'], 'reseau'));
		$this->assertSame('horizontal', $c['elements']['pied']['menu']['disposition']);
		$this->assertSame('', $c['elements']['pied']['html-2']['texte']);
	}

	public function testListeDeReseauxRemplaceeEnBloc(): void {
		$c = astra_hfb_normaliser(['elements' => ['pied' => ['social-icons-1' => ['reseaux' => [['reseau' => 'mastodon', 'url' => 'https://m.example']]]]]]);
		$this->assertSame(['mastodon'], array_column($c['elements']['pied']['social-icons-1']['reseaux'], 'reseau'));
		$this->assertSame(['facebook', 'twitter', 'instagram'], array_column($c['elements']['entete']['social-icons-1']['reseaux'], 'reseau'));
	}

	public function testChaqueReseauAUneIcone(): void {
		require_once dirname(__DIR__, 2) . '/inc/astra_icones.php';
		$this->assertSame([], array_diff(array_keys(astra_hfb_reseaux()), array_keys(astra_icones_sociales())));
	}

	public function testMenuMobileInvalideRevientAuDeroulant(): void {
		$this->assertSame('off-canvas', astra_hfb_normaliser(['menu_mobile' => 'off-canvas'])['menu_mobile']);
		$this->assertSame('dropdown', astra_hfb_normaliser(['menu_mobile' => 'plein-ecran'])['menu_mobile']);
	}

	// Registre

	public function testElementsInconnusOuHorsContexteRetires(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['primary' => [
			'primary_right' => ['menu-99', 'unknown', 'copyright', 'widget-4', 'mobile-trigger', '../../config/connect', 'menu-1'],
		]]]);
		$this->assertSame(['menu-1'], $c['entete_ordinateur']['primary']['primary_right']);
	}

	public function testDoublonDansUnMemeBuilderRetire(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['primary' => [
			'primary_left' => ['menu-1', 'logo'],
			'primary_right' => ['menu-1', 'search'],
		]]]);
		$this->assertSame(['menu-1', 'logo'], $c['entete_ordinateur']['primary']['primary_left']);
		$this->assertSame(['search'], $c['entete_ordinateur']['primary']['primary_right']);
	}

	public function testMemeIdentifiantPermisDansDeuxBuildersDifferents(): void {
		$c = astra_hfb_normaliser([
			'entete_ordinateur' => ['above' => ['above_left' => ['html-1']]],
			'pied' => ['below' => ['below_1' => ['html-1', 'copyright']]],
		]);
		$this->assertSame(['html-1'], $c['entete_ordinateur']['above']['above_left']);
		$this->assertSame(['html-1', 'copyright'], $c['pied']['below']['below_1']);
	}

	public function testElementDecritSonSqueletteEtSonGroupe(): void {
		$this->assertSame(['id' => 'menu-2', 'fond' => 'inclure/hfb/menu', 'numero' => '2', 'groupe' => 'entete'], astra_hfb_element('menu-2', 'entete'));
		$this->assertSame(['id' => 'social-icons-1', 'fond' => 'inclure/hfb/social-icons', 'numero' => '1', 'groupe' => 'pied'], astra_hfb_element('social-icons-1', 'pied'));
		$this->assertSame(['id' => 'mobile-trigger', 'fond' => 'inclure/hfb/mobile-trigger', 'numero' => '', 'groupe' => 'entete'], astra_hfb_element('mobile-trigger', 'entete'));
	}

	// En-tête

	public function testRangeeAvecSeulementUnCentreNAPasDeCotes(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['above' => ['above_center' => ['html-1']]]]);
		$rangees = astra_hfb_calculer_entete($c['entete_ordinateur']);
		$this->assertSame(['above', 'primary'], array_column($rangees, 'rangee'));
		$this->assertFalse($rangees[0]['cotes']);
		$this->assertTrue($rangees[0]['centre']);
	}

	public function testZoneCentreGaucheDonneDesCotes(): void {
		$c = astra_hfb_normaliser(['entete_ordinateur' => ['below' => ['below_left_center' => ['html-2']]]]);
		$rangees = astra_hfb_calculer_entete($c['entete_ordinateur']);
		$this->assertTrue($rangees[1]['cotes']);
		$this->assertSame(['html-2'], self::ids($rangees[1]['zones']['left_center']));
	}

	// En-tête mobile et panneau

	public function testEnTeteMobileParDefaut(): void {
		$c = astra_hfb_normaliser([]);
		$rangees = astra_hfb_calculer_entete($c['entete_mobile']);
		$this->assertSame(['primary'], array_column($rangees, 'rangee'));
		$this->assertSame(['mobile-trigger'], self::ids($rangees[0]['zones']['right']));
		$this->assertSame([], $rangees[0]['zones']['left_center']);
		$this->assertSame(['mobile-menu'], self::ids(astra_hfb_calculer_panneau($c['entete_mobile'])));
	}

	public function testPanneauSansBoutonDeMenuNonAffiche(): void {
		$c = astra_hfb_normaliser(['entete_mobile' => ['primary' => ['primary_right' => []]]]);
		$this->assertSame([], astra_hfb_calculer_panneau($c['entete_mobile']));
	}

	public function testDoublonEntreRangeesMobilesEtPanneauRetire(): void {
		$c = astra_hfb_normaliser(['entete_mobile' => ['popup' => ['popup_content' => ['logo', 'mobile-menu', 'search', 'mobile-trigger']]]]);
		$this->assertSame(['mobile-menu', 'search'], $c['entete_mobile']['popup']['popup_content']);
	}

	public function testMobileTriggerInterditEnOrdinateurMaisPermisEnMobile(): void {
		$c = astra_hfb_normaliser([
			'entete_ordinateur' => ['primary' => ['primary_right' => ['mobile-trigger']]],
			'entete_mobile' => ['above' => ['above_center' => ['mobile-menu']]],
		]);
		$this->assertSame([], $c['entete_ordinateur']['primary']['primary_right']);
		$this->assertSame(['mobile-menu'], $c['entete_mobile']['above']['above_center']);
	}

	// Pied de page

	public function testPiedParDefautReproduitLeRenduActuel(): void {
		$c = astra_hfb_normaliser([]);
		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
		$this->assertSame(['below'], array_column($rangees, 'rangee'));
		$this->assertSame(
			'site-below-footer-wrap ast-builder-grid-row-container site-footer-focus-item ast-builder-grid-row-full ast-builder-grid-row-tablet-full ast-builder-grid-row-mobile-full ast-footer-row-stack ast-footer-row-tablet-stack ast-footer-row-mobile-stack',
			$rangees[0]['classes']
		);
		$this->assertSame([1], array_keys($rangees[0]['colonnes']));
		$this->assertSame(['copyright'], self::ids($rangees[0]['colonnes'][1]));
		$this->assertSame('pied', $rangees[0]['colonnes'][1][0]['groupe']);
	}

	public function testPiedQuatreColonnes(): void {
		$c = astra_hfb_normaliser([
			'pied' => ['primary' => ['primary_1' => ['widget-1'], 'primary_4' => ['widget-4']]],
			'rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => '4-equal']],
		]);
		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
		$this->assertSame(['primary', 'below'], array_column($rangees, 'rangee'));
		$this->assertStringContainsString('ast-builder-grid-row-4-equal ast-builder-grid-row-tablet-4-equal ast-builder-grid-row-mobile-full', $rangees[0]['classes']);
		$this->assertSame([1, 2, 3, 4], array_keys($rangees[0]['colonnes']));
		$this->assertSame([], $rangees[0]['colonnes'][2]);
	}

	public function testElementsAuDelaDuNombreDeColonnesRetiresEtRangeeMasquee(): void {
		$c = astra_hfb_normaliser([
			'pied' => ['above' => ['above_3' => ['html-1']]],
			'rangees' => ['pied_above' => ['colonnes' => 2]],
		]);
		$this->assertSame([], $c['pied']['above']['above_3']);
		$this->assertSame(['below'], array_column(astra_hfb_calculer_pied($c['pied'], $c['rangees']), 'rangee'));
	}

	public function testSixiemeColonneRendue(): void {
		$c = astra_hfb_normaliser([
			'pied' => ['primary' => ['primary_6' => ['widget-2']]],
			'rangees' => ['pied_primary' => ['colonnes' => 6, 'repartition' => '6-equal']],
		]);
		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
		$this->assertSame('primary', $rangees[0]['rangee']);
		$this->assertSame(['widget-2'], self::ids($rangees[0]['colonnes'][6]));
	}

	public function testRepartitionInvalideRemplaceeParFull(): void {
		$c = astra_hfb_normaliser(['rangees' => ['pied_below' => ['repartition' => '" onload="x']]]);
		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
		$this->assertStringContainsString('ast-builder-grid-row-full ', $rangees[0]['classes']);
	}

	public function testRepartitionParAppareil(): void {
		$c = astra_hfb_normaliser([
			'pied' => ['primary' => ['primary_1' => ['widget-1']]],
			'rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full']]],
		]);
		$rangees = astra_hfb_calculer_pied($c['pied'], $c['rangees']);
		$this->assertStringContainsString('ast-builder-grid-row-4-equal ast-builder-grid-row-tablet-full ast-builder-grid-row-mobile-full', $rangees[0]['classes']);
	}

	public function testRepartitionParAppareilIncompleteOuInvalide(): void {
		$this->assertSame(['desktop' => '2-equal', 'tablet' => 'full', 'mobile' => 'full'], astra_hfb_repartition_appareils(['desktop' => '2-equal', 'mobile' => '" x']));
		$this->assertSame(['desktop' => '3-cwide', 'tablet' => '3-cwide', 'mobile' => 'full'], astra_hfb_repartition_appareils('3-cwide'), 'Valeur simple : rendu de la version 1.0');
		$this->assertSame(['desktop' => 'full', 'tablet' => 'full', 'mobile' => 'full'], astra_hfb_repartition_appareils(null));
	}

	// CSS des rangées

	public function testCssDesRangeesVideParDefaut(): void {
		$this->assertSame('', astra_hfb_css_rangees(astra_hfb_normaliser([])['rangees']));
	}

	public function testCssDesRangeesModifiees(): void {
		$c = astra_hfb_normaliser(['rangees' => [
			'entete_above' => ['hauteur' => 40, 'fond' => '#112233', 'bordure' => 0],
			'pied_primary' => ['fond' => 'red;}body{display:none'],
		]]);
		$css = astra_hfb_css_rangees($c['rangees']);
		$this->assertStringContainsString('.ast-mobile-header-wrap .ast-above-header-bar, .ast-above-header-bar .site-above-header-wrap{min-height:40px;}', $css);
		$this->assertStringContainsString('.ast-above-header-bar, .ast-header-break-point .ast-above-header-bar{background-color:#112233;}', $css);
		$this->assertStringContainsString('.ast-above-header-bar{border-bottom-width:0px;}', $css);
		$this->assertStringNotContainsString('display:none', $css);
	}

	public function testBordureDeLaRangeePrincipaleSurMobile(): void {
		// Astra émet le séparateur de la rangée principale aussi sous .ast-header-break-point
		// (primary-header/dynamic-css/dynamic.css.php), plus spécifique que astra-dynamic.css.
		$c = astra_hfb_normaliser(['rangees' => ['entete_primary' => ['bordure' => 0]]]);
		$css = astra_hfb_css_rangees($c['rangees']);
		$this->assertStringContainsString('.ast-header-break-point .ast-primary-header-bar, .ast-primary-header-bar{border-bottom-width:0px;}', $css);
	}

	public function testSeparateursDuPied(): void {
		// Valeurs de Garden : pas de filet au-dessus de la rangée principale, filet de la couleur 4
		// au-dessus de la rangée basse ; rangée haute : 2 px, couleur par défaut d'Astra (aucune).
		$c = astra_hfb_normaliser(['rangees' => [
			'pied_primary' => ['separateur' => 0],
			'pied_below' => ['couleur_separateur' => 'var(--ast-global-color-4)'],
			'pied_above' => ['separateur' => 2],
		]]);
		$css = astra_hfb_css_rangees($c['rangees']);
		$this->assertStringContainsString('.site-primary-footer-wrap[data-section="section-primary-footer-builder"]{border-style:none;border-width:medium;border-color:currentcolor;}', $css);
		$this->assertStringContainsString('.site-below-footer-wrap[data-section="section-below-footer-builder"]{border-style:solid;border-width:0px;border-top-width:1px;border-top-color:var(--ast-global-color-4);}', $css);
		$this->assertStringContainsString('.site-above-footer-wrap[data-section="section-above-footer-builder"]{border-style:solid;border-width:0px;border-top-width:2px;}', $css);
	}

	public function testSeparateursParDefautOuInvalidesSansCss(): void {
		$c = astra_hfb_normaliser(['rangees' => [
			'pied_primary' => ['separateur' => '', 'couleur_separateur' => ''],
			'pied_above' => ['separateur' => 0],
			'pied_below' => ['couleur_separateur' => 'red;}body{display:none'],
		]]);
		$this->assertSame('', astra_hfb_css_rangees($c['rangees']), 'Épaisseur vide (défaut d\'Astra), rangée haute sans filet, couleur refusée');
		$c = astra_hfb_normaliser(['rangees' => ['pied_primary' => ['couleur_separateur' => '#123456']]]);
		$this->assertStringContainsString('{border-style:solid;border-width:0px;border-top-width:1px;border-top-color:#123456;}', astra_hfb_css_rangees($c['rangees']));
	}
}

<?php

use PHPUnit\Framework\TestCase;

/** Formulaire configurer_astra_styles : charger, vérifier, traiter, rendu des champs. */
final class StylesFormulaireTest extends TestCase {

	public static function setUpBeforeClass(): void {
		include_spip('formulaires/configurer_astra_styles');
		require_once dirname(__DIR__, 2) . '/inc/astra_entete_transparent.php';
	}

	protected function tearDown(): void {
		effacer_config('astra/styles');
		set_request('s', null);
	}

	public function testChargerDonneLesReglages(): void {
		ecrire_config('astra/styles', ['button-color' => '#123456']);
		$this->assertSame(['s' => ['button-color' => '#123456']], formulaires_configurer_astra_styles_charger_dist());
	}

	public function testVerifierSignaleLesValeursRefusees(): void {
		set_request('s', ['button-color' => 'rouge', 'button-h-color' => '#ffffff']);
		$erreurs = formulaires_configurer_astra_styles_verifier_dist();
		$this->assertArrayHasKey('s_button-color', $erreurs);
		$this->assertArrayNotHasKey('s_button-h-color', $erreurs);
		$this->assertArrayHasKey('message_erreur', $erreurs);
	}

	public function testTraiterGardeTabletteEtMobile(): void {
		ecrire_config('astra/styles', [
			'header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222', 'mobile' => '#333333'],
		]);
		set_request('s', [
			'header-menu1-color-responsive' => ['desktop' => 'var(--ast-global-color-0)'],
			'button-color' => '',
		]);
		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertSame([
			'header-menu1-color-responsive' => ['desktop' => 'var(--ast-global-color-0)', 'tablet' => '#222222', 'mobile' => '#333333'],
		], lire_config('astra/styles'));
	}

	public function testRenduDesChamps(): void {
		$html = recuperer_fond('formulaires/configurer_astra_styles', ['editable' => true, 's' => ['button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'em']]]);
		$this->assertSame(16, substr_count($html, '<fieldset'));
		$this->assertMatchesRegularExpression('~name="s\[mobile-header-toggle-btn-style\]".*?<option value="outline">Contour</option>~s', $html);
		$this->assertStringContainsString('name="s[header-menu1-color-responsive][desktop]"', $html);
		$this->assertStringContainsString('name="s[button-radius-fields][desktop][top]" value="8"', $html);
		$this->assertMatchesRegularExpression('~name="s\[button-radius-fields\]\[desktop-unit\]".*?<option value="em" selected="selected">~s', $html);
		$this->assertStringContainsString('name="s[header-menu1-bg-obj-responsive][desktop][background-color]"', $html);
		$this->assertStringContainsString('list="astra-styles-palette"', $html);
	}

	public function testExclusionRemiseAuReglageDAstra(): void {
		// Exclusion des archives levée puis remise au « Réglage d'Astra » : l'option enregistrée
		// devient vide, le défaut d'Astra (archives exclues) s'applique de nouveau.
		ecrire_config('astra/styles', ['transparent-header-enable' => '1', 'transparent-header-disable-archive' => '0']);
		$this->assertTrue(astra_entete_transparent_actif(lire_config('astra/styles'), 'rubrique'));
		$saisie = $this->saisieDuFormulaire();
		$this->assertSame('0', $saisie['transparent-header-disable-archive']);
		$saisie['transparent-header-disable-archive'] = '';
		set_request('s', $saisie);
		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertFalse(astra_entete_transparent_actif(lire_config('astra/styles'), 'rubrique'));
	}

	public function testAllerRetourDesReglagesDeCompatibilite(): void {
		// Valeurs de Garden : graisses des titres, marge du bouton de l'en-tête enregistrée vide,
		// alignements des trois appareils. Un enregistrement sans modification ne change pas le CSS.
		$dir = dirname(__DIR__) . '/references/styles/valeurs';
		$garden = ['headings-font-weight' => '600'];
		for ($n = 1; $n <= 6; $n++) {
			$garden["font-weight-h$n"] = 'inherit';
		}
		$garden += json_decode(file_get_contents("$dir/bouton-entete-marge.json"), true) + json_decode(file_get_contents("$dir/alignements.json"), true);
		$hfb = ['pied' => ['below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]], 'rangees' => ['pied_below' => ['colonnes' => 2]]];
		$c = ['can-support-widget-and-editor-fonts' => true];
		ecrire_config('astra/styles', $garden);
		$avant = astra_styles_css($garden, $hfb, $c);
		$this->assertStringContainsString('@media (max-width:544px){.ast-footer-copyright{text-align:center;}', $avant);
		$this->assertStringContainsString('.footer-social-inner-wrap{text-align:right;}', $avant);
		$saisie = $this->saisieDuFormulaire();
		$this->assertSame(['desktop' => 'left', 'tablet' => 'left', 'mobile' => 'center'], $saisie['footer-copyright-alignment']);
		set_request('s', $saisie);
		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertSame($avant, astra_styles_css(lire_config('astra/styles'), $hfb, $c));
		// Alignement mobile changé par le formulaire : enregistré.
		$saisie['footer-copyright-alignment']['mobile'] = 'right';
		set_request('s', $saisie);
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertSame('right', lire_config('astra/styles')['footer-copyright-alignment']['mobile']);
	}

	public function testAllerRetourDesMargesDesRangees(): void {
		// Valeurs de la référence R24-marges-toutes (trois appareils, unités mêlées, marges négatives) :
		// un enregistrement sans modification ne change pas le CSS ; les options jamais réglées ne
		// sont pas enregistrées.
		$valeurs = json_decode(file_get_contents(dirname(__DIR__) . '/references/styles/valeurs/marges-toutes.json'), true);
		unset($valeurs['section-above-footer-builder-margin']);
		$hfb = ['entete_mobile' => ['above' => ['above_center' => ['html-1']]], 'pied' => ['above' => ['above_1' => ['html-1']], 'below' => ['below_1' => ['copyright']]]];
		ecrire_config('astra/styles', $valeurs);
		$avant = astra_styles_css($valeurs, $hfb);
		$this->assertStringContainsString('margin-top:-5px;', $avant);
		$saisie = $this->saisieDuFormulaire();
		$this->assertSame(['top' => '60', 'right' => '30', 'bottom' => '30', 'left' => '30'], $saisie['section-primary-footer-builder-padding']['mobile']);
		set_request('s', $saisie);
		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertSame($avant, astra_styles_css(lire_config('astra/styles'), $hfb));
		$this->assertArrayNotHasKey('section-above-footer-builder-margin', lire_config('astra/styles'));
		// Marge tablette changée par le formulaire : enregistrée et émise.
		$saisie['section-above-footer-builder-padding']['tablet']['top'] = '25';
		set_request('s', $saisie);
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertStringContainsString('@media (max-width:921px){.site-above-footer-wrap[data-section="section-above-footer-builder"]{padding-top:25px;', astra_styles_css(lire_config('astra/styles'), $hfb));
	}

	public function testAllerRetourDesMargesSansUnite(): void {
		$valeurs = json_decode(file_get_contents(dirname(__DIR__) . '/references/styles/valeurs/marges-rangees-vides.json'), true);
		$option = 'section-primary-header-builder-padding';
		$hfb = ['entete_ordinateur' => ['primary' => ['primary_left' => ['logo']]]];
		ecrire_config('astra/styles', $valeurs);
		$avant = astra_styles_css($valeurs, $hfb);
		$saisie = $this->saisieDuFormulaire();
		set_request('s', $saisie);
		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertSame($avant, astra_styles_css(lire_config('astra/styles'), $hfb));
		$this->assertArrayNotHasKey('tablet-unit', lire_config('astra/styles')[$option]);
		$this->assertSame($valeurs[$option]['tablet'], lire_config('astra/styles')[$option]['tablet']);
		// Choisir explicitement une unité active les valeurs importées.
		$saisie[$option]['tablet-unit'] = 'px';
		set_request('s', $saisie);
		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$apres = astra_styles_css(lire_config('astra/styles'), $hfb);
		$this->assertNotSame($avant, $apres);
		$this->assertStringContainsString('padding-top:7px;padding-bottom:7px;padding-left:7px;padding-right:7px;', $apres);
	}

	/** Champs du formulaire rendu, postés tels quels (sélecteurs : option choisie, sinon la première). */
	private function saisieDuFormulaire(): array {
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHTML('<?xml encoding="utf-8"?>' . recuperer_fond('formulaires/configurer_astra_styles', ['editable' => true] + formulaires_configurer_astra_styles_charger_dist()));
		libxml_clear_errors();
		$x = new DOMXPath($doc);
		$champs = [];
		foreach ($x->query('//*[self::input or self::select][starts-with(@name, "s[")]') as $n) {
			if ($n->nodeName === 'select') {
				$choisie = $x->query('option[@selected]', $n);
				$valeur = ($choisie->length ? $choisie->item(0) : $x->query('option', $n)->item(0))->getAttribute('value');
			} else {
				$valeur = $n->getAttribute('value');
			}
			$champs[] = urlencode($n->getAttribute('name')) . '=' . urlencode($valeur);
		}
		parse_str(implode('&', $champs), $r);
		return $r['s'];
	}

	public function testAllerRetourDesReglagesDeLEnTeteTransparent(): void {
		// Valeurs de Garden, dont transparent-header-default-border à faux : un enregistrement sans
		// modification ne doit rien changer au CSS de l'en-tête transparent.
		$garden = json_decode(file_get_contents(dirname(__DIR__) . '/references/transparent/valeurs/garden.json'), true);
		ecrire_config('astra/styles', $garden);
		$avant = astra_entete_transparent_css($garden, []);
		$this->assertStringContainsString('{border-bottom-width:0px;border-bottom-style:solid;}', $avant);
		set_request('s', $this->saisieDuFormulaire());
		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertSame($avant, astra_entete_transparent_css(lire_config('astra/styles'), []));
		// Séparateur vide : le drapeau à faux supprime le séparateur, avant comme après l'enregistrement.
		unset($garden['transparent-header-main-sep']);
		ecrire_config('astra/styles', $garden);
		$this->assertStringContainsString('{border-bottom-style:none;}', astra_entete_transparent_css($garden, []));
		set_request('s', $this->saisieDuFormulaire());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertStringContainsString('{border-bottom-style:none;}', astra_entete_transparent_css(lire_config('astra/styles'), []));
	}
	public function testAllerRetourIdentiteParAppareil(): void {
		$avant=['font-size-site-title'=>['desktop'=>20,'tablet'=>'18','mobile'=>'16','desktop-unit'=>'px'], 'font-size-site-tagline'=>['desktop'=>'11','tablet'=>'12','mobile'=>'11'], 'ast-header-responsive-logo-width'=>['desktop'=>'40','tablet'=>'40','mobile'=>'32'], 'display-site-title-responsive'=>['desktop'=>true,'tablet'=>false,'mobile'=>1], 'display-site-tagline-responsive'=>['desktop'=>false,'tablet'=>true,'mobile'=>false]];
		ecrire_config('astra/styles',$avant);
		$css=astra_styles_css($avant,[]);
		$post=$this->saisieDuFormulaire();
		$this->assertArrayHasKey('tablet',$post['font-size-site-title']);
		$this->assertSame('18',$post['font-size-site-title']['tablet']);
		$this->assertSame('0',$post['display-site-title-responsive']['tablet']);
		set_request('s',$post);
		$this->assertSame([],formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertSame($avant,lire_config('astra/styles'));
		$this->assertSame($css,astra_styles_css(lire_config('astra/styles'),[]));
		$post['font-size-site-title']['tablet-unit']='pt';
		set_request('s',$post);
		$this->assertArrayHasKey('s_font-size-site-title',formulaires_configurer_astra_styles_verifier_dist());
	}

	public function testFauxSurvitALaProtectionDuFormulaireCvt(): void {
		include_spip('balise/formulaire_');
		ecrire_config('astra/styles',['display-site-title-responsive'=>['tablet'=>false]]);
		$c=formulaires_configurer_astra_styles_charger_dist();
		$protege=protege_champ($c['s']);
		$this->assertSame('0',$protege['display-site-title-responsive']['tablet']);
		$this->assertSame(false,lire_config('astra/styles/display-site-title-responsive/tablet'));
	}

}

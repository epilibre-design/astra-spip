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
		$this->assertSame(14, substr_count($html, '<fieldset'));
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
}

<?php
use PHPUnit\Framework\TestCase;

final class SocialRenduTest extends TestCase {
	public function testIdentifiantsUniquesMultiAppareils(): void {
		$avant = lire_config('astra/hfb', null);
		$langue = $GLOBALS['spip_lang'] ?? 'fr';
		$config = self::configuration();
		try {
			ecrire_config('astra/hfb', $config);
			foreach (['fr', 'en'] as $langueTest) {
				changer_langue($langueTest);
				$html = recuperer_fond('404', ['type_page' => '404', 'lang' => $langueTest]);
				$dom = new DOMDocument();
				$erreurs = libxml_use_internal_errors(true);
				$dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
				libxml_clear_errors();
				libxml_use_internal_errors($erreurs);
				$xpath = new DOMXPath($dom);
				$ids = [];
				foreach ($xpath->query('//*[@id]') as $node) {
					$id = $node->getAttribute('id');
					$this->assertArrayNotHasKey($id, $ids, $id);
					$ids[$id] = true;
				}
				$this->assertSame(0, $xpath->query('//title[@id="email" or @id="google_reviews"]')->length);
				foreach (['email' => 'E-mail', 'google_reviews' => 'Google Reviews'] as $reseau => $libelle) {
					$liens = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " ast-' . $reseau . ' ")]');
					$this->assertSame(3, $liens->length, $reseau . ' : ordinateur, mobile, pied');
					foreach ($liens as $lien) {
						$this->assertSame($libelle, $lien->getAttribute('aria-label'));
						if ($reseau === 'email') {
							$this->assertSame('MAILTO:nom@example.test', $lien->getAttribute('href'));
						}
					}
				}
				foreach ($xpath->query('//*[@aria-labelledby or @aria-describedby]') as $node) {
					foreach (['aria-labelledby', 'aria-describedby'] as $attribut) {
						foreach (preg_split('/\s+/', trim($node->getAttribute($attribut)), -1, PREG_SPLIT_NO_EMPTY) as $id) {
							$this->assertArrayHasKey($id, $ids, $id);
						}
					}
				}
			}
			$this->assertSame($config, lire_config('astra/hfb'), 'Le rendu ne modifie pas le stockage');
		} finally {
			changer_langue($langue);
			if ($avant === null) { effacer_config('astra/hfb'); } else { ecrire_config('astra/hfb', $avant); }
		}
	}

	public function testAllerRetourConserveLaCasseDuPrefixe(): void {
		include_spip('formulaires/configurer_astra_hfb');
		$avant = lire_config('astra/hfb', null);
		try {
			$config = self::configuration();
			ecrire_config('astra/hfb', $config);
			$champs = formulaires_configurer_astra_hfb_charger_dist();
			foreach (['place', 'ordre', 'rangees', 'menu_mobile', 'elements'] as $champ) {
				set_request($champ, $champs[$champ]);
			}
			$this->assertSame([], formulaires_configurer_astra_hfb_verifier_dist());
			formulaires_configurer_astra_hfb_traiter_dist();
			foreach (['entete', 'pied'] as $groupe) {
				$this->assertSame($config['elements'][$groupe]['social-icons-1']['reseaux'], lire_config("astra/hfb/elements/$groupe/social-icons-1/reseaux"));
			}
		} finally {
			foreach (['place', 'ordre', 'rangees', 'menu_mobile', 'elements'] as $champ) { set_request($champ, null); }
			if ($avant === null) { effacer_config('astra/hfb'); } else { ecrire_config('astra/hfb', $avant); }
		}
	}

	private static function configuration(): array {
		$reseaux = [['reseau' => 'email', 'url' => 'MAILTO:nom@example.test'], ['reseau' => 'google_reviews', 'url' => 'https://example.test/avis']];
		return astra_hfb_normaliser([
			'entete_ordinateur' => ['above' => ['above_right' => ['social-icons-1']]],
			'entete_mobile' => ['above' => ['above_center' => ['social-icons-1']]],
			'pied' => ['below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]],
			'rangees' => ['pied_below' => ['colonnes' => 2, 'repartition' => '2-equal']],
			'elements' => ['entete' => ['social-icons-1' => ['reseaux' => $reseaux]], 'pied' => ['social-icons-1' => ['reseaux' => $reseaux]]],
		]);
	}
}

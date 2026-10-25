<?php
use PHPUnit\Framework\TestCase;

final class LiensRenduTest extends TestCase {
	public function testProtocolesActifsRefusesSansModifierLeStockage(): void {
		$avant = lire_config('astra/hfb', null);
		try {
			foreach (['javascript:alert(1)', "java\tscript:alert(1)", 'jav&#x61;script:alert(1)', 'jav&amp;#x61;script:alert(1)', 'data:text/html,test', 'vbscript:msgbox(1)'] as $url) {
				$config = ['elements' => ['entete' => ['button-1' => ['libelle' => 'Test', 'lien' => $url],
					'social-icons-1' => ['reseaux' => [['reseau' => 'facebook', 'url' => $url]]]]]];
				ecrire_config('astra/hfb', $config);
				$this->assertSame('', astra_hfb_bouton('entete', 'button-1')['lien'], $url);
				$this->assertSame('', astra_hfb_icones('entete', 'social-icons-1')[0]['url'], $url);
				foreach (['button', 'social-icons'] as $fond) {
					$html = recuperer_fond('inclure/hfb/' . $fond, ['groupe' => 'entete', 'id' => $fond === 'button' ? 'button-1' : 'social-icons-1', 'numero' => 1]);
					$doc = new DOMDocument();
					@$doc->loadHTML($html);
					foreach ($doc->getElementsByTagName('a') as $a) { $this->assertSame('', $a->getAttribute('href')); }
				}
				$this->assertSame($config, lire_config('astra/hfb'));
			}
		} finally {
			if ($avant === null) { effacer_config('astra/hfb'); } else { ecrire_config('astra/hfb', $avant); }
		}
	}

	public function testLiensValidesConservesAuRendu(): void {
		foreach (['https://example.test/a?x=1&y=2', 'MAILTO:nom@example.test', 'tel:+33123456789', '#contact', '/contact', '?page=contact', 'contact.html', '//example.test/contact'] as $url) {
			$this->assertSame($url, astra_url_lien($url));
		}
	}
}

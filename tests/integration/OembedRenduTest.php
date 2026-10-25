<?php

use PHPUnit\Framework\TestCase;

/** Caractérisation du vrai import wp2spip ; préparation dans fixtures/oembed/README.md. */
final class OembedRenduTest extends TestCase {
	protected function setUp(): void {
		if (getenv('ASTRA_OEMBED_PREUVE') !== '1') {
			$this->markTestSkipped('Les deux sites privés oEmbed et leur fournisseur ne sont pas préparés.');
		}
		$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/spip.php';
	}

	public function testImportSansPluginEtTexteInchange(): void {
		if (test_plugin_actif('oembed')) {
			$this->markTestSkipped('Ce cas caractérise le site sans plugin.');
		}
		foreach (['large', 'classique', 'erreur', 'invalide', 'inconnu', 'temoin'] as $cas) {
			$article = sql_fetsel('id_article,texte', 'spip_articles', 'titre=' . sql_quote('Preuve oEmbed ' . $cas));
			$this->assertIsArray($article, 'L’import réel doit être préparé : ' . $cas);
			$avant = $article['texte'];
			$html = recuperer_fond('article', ['id_article' => $article['id_article']]);
			if ($cas === 'temoin') {
				$this->assertStringContainsString('Article témoin sans vidéo.', $html);
			} else {
				$this->assertStringContainsString('Légende locale ' . $cas, $html);
				$url = strtok($avant, "\n");
				$this->assertStringContainsString('href="' . htmlspecialchars($url, ENT_QUOTES) . '"', $html);
			}
			$this->assertStringNotContainsString('rwd-video-container', $html);
			$this->assertSame($avant, sql_getfetsel('texte', 'spip_articles', 'id_article=' . (int) $article['id_article']));
		}
	}

	public function testTraitementUniqueAvecOembed(): void {
		if (!test_plugin_actif('oembed')) {
			$this->markTestSkipped('Ce cas caractérise le site avec oEmbed 3.5.1.');
		}
		include_spip('inc/oembed');
		foreach (['large', 'classique'] as $cas) {
			$article = sql_fetsel('id_article,texte', 'spip_articles', 'titre=' . sql_quote('Preuve oEmbed ' . $cas));
			$this->assertIsArray($article);
			$avant = $article['texte'];
			$html = recuperer_fond('article', ['id_article' => $article['id_article']]);
			$this->assertSame(1, preg_match_all('/class="[^"]*\brwd-video-container\b/', $html));
			$this->assertStringContainsString('Légende locale ' . $cas, $html);
			$this->assertStringContainsString('oe-play-button', $html);
			$this->assertStringNotContainsString('ast-oembed-container', $html);
			// Le vrai résultat du plugin repasse par le traitement : aucune seconde enveloppe.
			$doc = new DOMDocument();
			@$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
			$xpath = new DOMXPath($doc);
			$embed = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " oembed_video ")]')->item(0);
			$this->assertNotNull($embed);
			$deux = propre('<html>' . $doc->saveHTML($embed) . '</html>', '');
			$this->assertSame(1, preg_match_all('/class="[^"]*\brwd-video-container\b/', $deux));
			$this->assertSame($avant, sql_getfetsel('texte', 'spip_articles', 'id_article=' . (int) $article['id_article']));
		}
	}

	public function testEchecsFournisseurEtValidateurDistinctDeLaDecouverte(): void {
		if (!test_plugin_actif('oembed')) {
			$this->markTestSkipped('Ce cas requiert le vrai plugin.');
		}
		include_spip('inc/oembed');
		include_spip('inc/distant');
		foreach (['erreur', 'invalide', 'inconnu'] as $cas) {
			$article = sql_fetsel('id_article,texte', 'spip_articles', 'titre=' . sql_quote('Preuve oEmbed ' . $cas));
			$this->assertIsArray($article);
			$html = recuperer_fond('article', ['id_article' => $article['id_article']]);
			$this->assertStringContainsString('Légende locale ' . $cas, $html);
			$this->assertStringContainsString('href="' . htmlspecialchars(strtok($article['texte'], "\n"), ENT_QUOTES) . '"', $html);
			$this->assertStringNotContainsString('rwd-video-container', $html);
			$this->assertSame($article['texte'], sql_getfetsel('texte', 'spip_articles', 'id_article=' . (int) $article['id_article']));
		}
		// Le validateur refuse l’URL ; cette version du plugin ne le passe pas au client HTTP.
		// La découverte atteint donc le serveur, qui ne déclare aucun endpoint pour ce cas.
		$url = 'http://127.0.0.1:8428/inconnu?essai=refus-securite-spip';
		$this->assertFalse(valider_url_distante($url));
		$this->assertFalse(oembed_detecter_lien($url));
	}
}

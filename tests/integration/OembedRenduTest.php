<?php

use PHPUnit\Framework\TestCase;

/** Caractérisation du vrai import wp2spip ; préparation dans fixtures/oembed/README.md. */
final class OembedRenduTest extends TestCase {
	protected function setUp(): void {
		if (getenv('ASTRA_OEMBED_PREUVE') !== '1') {
			$this->markTestSkipped('Les deux sites privés oEmbed et leur fournisseur ne sont pas préparés.');
		}
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
}

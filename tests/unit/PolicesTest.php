<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_polices.php';

/** Polices locales : registre, fichiers fournis, détection dans une pile, @font-face. */
final class PolicesTest extends TestCase {

	public function testFichiersFournis(): void {
		foreach (astra_polices_locales() as $cle => $police) {
			foreach (['nom', 'pile', 'fichiers', 'licence', 'source', 'version'] as $champ) {
				$this->assertArrayHasKey($champ, $police, "$cle : $champ");
			}
			foreach ($police['fichiers'] as [$graisse, $style, $chemin]) {
				$this->assertFileExists(dirname(__DIR__, 2) . '/' . $chemin);
				$this->assertMatchesRegularExpression('/^[1-9]00$/', $graisse);
				$this->assertContains($style, ['normal', 'italic']);
			}
		}
	}

	public function testDetection(): void {
		$this->assertSame('poppins', astra_polices_detecter("'Poppins', sans-serif"));
		$this->assertSame('raleway', astra_polices_detecter('Raleway'));
		$this->assertSame('raleway', astra_polices_detecter('raleway'));
		$this->assertNull(astra_polices_detecter('Georgia, serif'));
		$this->assertNull(astra_polices_detecter(''));
		$this->assertNull(astra_polices_detecter("Arial, 'Raleway'"));
	}

	public function testFontFace(): void {
		$url = function (string $chemin) { return "/plugins/astra/$chemin"; };
		$css = astra_polices_css(["'Poppins', sans-serif", 'Georgia, serif', "'Poppins', sans-serif", ''], $url);
		$this->assertSame(
			"@font-face{font-family:'Poppins';font-style:normal;font-weight:500;font-display:swap;src:url(/plugins/astra/fonts/poppins/poppins-latin-500-normal.woff2) format('woff2');}"
			. "@font-face{font-family:'Poppins';font-style:normal;font-weight:600;font-display:swap;src:url(/plugins/astra/fonts/poppins/poppins-latin-600-normal.woff2) format('woff2');}",
			$css
		);
		$this->assertSame('', astra_polices_css(['Georgia, serif', 'inherit'], $url));
	}
}

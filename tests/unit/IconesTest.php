<?php
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__, 2) . '/inc/astra_icones.php';

final class IconesTest extends TestCase {
	public function testPrefixeMailInsensibleALaCasse(): void {
		foreach ([
			['email', 'nom@example.test', 'mailto:nom@example.test'],
			['email', 'mailto:nom@example.test', 'mailto:nom@example.test'],
			['email', 'MAILTO:nom@example.test', 'MAILTO:nom@example.test'],
			['email', 'MaIlTo:nom@example.test', 'MaIlTo:nom@example.test'],
			['email', '', ''],
			['github', 'https://example.test/', 'https://example.test/'],
		] as [$reseau, $url, $attendu]) {
			$this->assertSame($attendu, astra_lien_social($reseau, $url));
		}
	}

	public function testCatalogueSansIdStatique(): void {
		$catalogue = astra_icones_sociales();
		$this->assertArrayNotHasKey('trip_advisor', $catalogue);
		$this->assertCount(12, $catalogue);
		foreach ($catalogue as $reseau => $svg) {
			$dom = new DOMDocument();
			$this->assertTrue($dom->loadXML($svg), $reseau);
			$this->assertSame(0, (new DOMXPath($dom))->query('//*[@id]')->length, $reseau);
		}
	}
}

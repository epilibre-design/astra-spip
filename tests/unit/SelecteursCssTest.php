<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/StylesAttendusTest.php';

final class SelecteursCssTest extends TestCase {
	public function testVirgulesInternesEtEchappements(): void {
		foreach ([
			['.a:where(.b,.c), .d', ['.a:where(.b,.c)', '.d']],
			['a[href=","], .d', ['a[href=","]', '.d']],
			['.entry-content :where(h1,h2)', ['.entry-content :where(h1,h2)']],
			['.a\\,b, .c', ['.a\\,b', '.c']],
			[':is(.a, :not(.b,.c)), .d', [':is(.a, :not(.b,.c))', '.d']],
		] as [$texte, $attendu]) {
			$this->assertSame($attendu, StylesAttendusTest::selecteurs($texte));
		}
	}
}

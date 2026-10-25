<?php
use PHPUnit\Framework\TestCase;

final class AssetsReferenceTest extends TestCase {
	public function testAssetsIdentiquesA4138(): void {
		$racine = dirname(__DIR__, 2);
		$this->assertSame('84aad614f8b95ebd3a4ac6f7633e77323066d31da87afe3dafc8c14cd3d0f8fc', hash_file('sha256', "$racine/css/main.min.css"));
		$this->assertSame('3b3a9a24246253beec04f3d2619082970c3aa861ca4ee34280662e3d4a509d20', hash_file('sha256', "$racine/js/frontend.min.js"));
	}
}

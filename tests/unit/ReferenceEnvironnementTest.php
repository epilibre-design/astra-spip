<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/references/environnement.php';

final class ReferenceEnvironnementTest extends TestCase {
	private function cible(): array {
		return json_decode(file_get_contents(dirname(__DIR__) . '/references/cible.json'), true, 512, JSON_THROW_ON_ERROR);
	}

	public function testAccepteLaReferenceControlee(): void {
		$attendue = $this->cible();
		$this->assertSame([], astra_reference_erreurs($attendue, $attendue));
	}

	public function testRejetteUneReferenceFausse(): void {
		$attendue = $this->cible();
		foreach (['wordpress' => '7.1.2', 'astra' => '4.11.0', 'theme' => 'twentytwentyfive',
			'archive_sha256' => str_repeat('0', 64), 'integrite_sources' => false] as $cle => $faux) {
			$reelle = $attendue;
			$reelle[$cle] = $faux;
			$this->assertNotEmpty(astra_reference_erreurs($attendue, $reelle), $cle);
		}
		$reelle = $attendue;
		$reelle['assets']['assets/js/minified/frontend.min.js'] = str_repeat('0', 64);
		$this->assertNotEmpty(astra_reference_erreurs($attendue, $reelle));
		$this->assertNotEmpty(astra_reference_erreurs($attendue, []));
	}

	public function testHistoriqueExpliciteSansAffaiblirLaCible(): void {
		$historique = json_decode(file_get_contents(dirname(__DIR__) . '/references/cible-4.11.0.json'), true, 512, JSON_THROW_ON_ERROR);
		$this->assertSame([], astra_reference_erreurs($historique, $historique));
		$this->assertNotEmpty(astra_reference_erreurs($this->cible(), $historique));
	}
	public function testDetecteUneSourcePhpAltereeEtUnFichierAjoute(): void {
		$racine = sys_get_temp_dir() . '/astra-reference-' . bin2hex(random_bytes(6));
		mkdir($racine);
		$zip = new ZipArchive();
		$archive = $racine . '.zip';
		$zip->open($archive, ZipArchive::CREATE);
		$zip->addFromString('astra/functions.php', '<?php // source officielle');
		$zip->close();
		$zip->open($archive);
		try {
			file_put_contents($racine . '/functions.php', '<?php // source officielle');
			$this->assertTrue(astra_reference_sources($racine, $zip));
			file_put_contents($racine . '/functions.php', '<?php // source modifiee');
			$this->assertFalse(astra_reference_sources($racine, $zip));
			file_put_contents($racine . '/functions.php', '<?php // source officielle');
			file_put_contents($racine . '/ajout.php', '<?php // ajout');
			$this->assertFalse(astra_reference_sources($racine, $zip));
		} finally {
			$zip->close();
			@unlink($racine . '/functions.php');
			@unlink($racine . '/ajout.php');
			rmdir($racine);
			unlink($archive);
		}
	}

}

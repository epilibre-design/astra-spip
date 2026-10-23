<?php

use PHPUnit\Framework\TestCase;

/** Priorité des squelettes : variante du plugin Pages, page_unique.html du site, puis d'Astra. */
final class PagesStyliserTest extends TestCase {

	private static array $crees = [];

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/PagesGabaritTest.php';
		include_spip('astra_pipelines');
		include_spip('pages_pipelines');
		// Le chemin de SPIP n'inclut squelettes/ que s'il existe : on le crée et on le déclare.
		if (!is_dir(_DIR_RACINE . 'squelettes')) {
			mkdir(_DIR_RACINE . 'squelettes');
		}
		$GLOBALS['dossier_squelettes'] = 'squelettes';
	}

	public static function tearDownAfterClass(): void {
		$GLOBALS['dossier_squelettes'] = '';
		@rmdir(_DIR_RACINE . 'squelettes');
		$GLOBALS['path_files'] = [];
	}

	protected function tearDown(): void {
		foreach (self::$crees as $f) {
			@unlink($f);
		}
		self::$crees = [];
		$GLOBALS['path_files'] = [];
	}

	private function creer(string $nom): string {
		$f = _DIR_RACINE . "squelettes/$nom.html";
		file_put_contents($f, "<p>$nom</p>");
		self::$crees[] = $f;
		// find_in_path garde ses résultats : vider le cache pour qu'il voie le nouveau fichier.
		$GLOBALS['path_files'] = [];
		return substr($f, 0, -5);
	}

	/** Flux du pipeline styliser tel que le reçoivent les deux plugins pour le fond article. */
	private function flux(int $id): array {
		return ['args' => ['fond' => 'article', 'ext' => 'html', 'contexte' => ['id_article' => $id], 'connect' => ''], 'data' => _DIR_PLUGINS . 'astra/article'];
	}

	public function testVarianteDePagesPrioritaireDansLesDeuxOrdres(): void {
		$id = PagesGabaritTest::id('notre_jardin');
		$variante = $this->creer('article=notre_jardin');
		$this->assertSame(realpath($variante . '.html'), realpath(pages_styliser(astra_styliser($this->flux($id)))['data'] . '.html'));
		$this->assertSame(realpath($variante . '.html'), realpath(astra_styliser(pages_styliser($this->flux($id)))['data'] . '.html'));
	}

	public function testGabaritDuSitePrioritaireSurCeluiDAstra(): void {
		$id = PagesGabaritTest::id('verger');
		$site = $this->creer('page_unique');
		$this->assertSame(realpath($site . '.html'), realpath(astra_styliser($this->flux($id))['data'] . '.html'));
	}

	public function testGabaritDAstraSansSurcharge(): void {
		$id = PagesGabaritTest::id('verger');
		$this->assertSame(realpath(_DIR_PLUGINS . 'astra/page_unique.html'), realpath(astra_styliser($this->flux($id))['data'] . '.html'));
	}
}

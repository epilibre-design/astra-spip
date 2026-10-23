<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_pages.php';

final class PagesTest extends TestCase {

	public function testNormaliser(): void {
		$this->assertSame(['titre' => 'afficher', 'disposition' => 'defaut'], astra_page_normaliser(null));
		$this->assertSame(['titre' => 'masquer', 'disposition' => 'pleine'], astra_page_normaliser(['titre' => 'masquer', 'disposition' => 'pleine', 'autre' => 1]));
		$this->assertSame(['titre' => 'afficher', 'disposition' => 'defaut'], astra_page_normaliser(['titre' => 'x', 'disposition' => 'y']));
	}

	public function testDispositions(): void {
		$this->assertSame('normale', astra_page_disposition_effective('defaut'));
		$this->assertSame('etroite', astra_page_disposition_effective('etroite'));
		$this->assertSame(['ast-plain-container'], astra_page_classes_disposition('normale'));
		$this->assertSame(['ast-narrow-container'], astra_page_classes_disposition('etroite'));
		$this->assertSame(['ast-page-builder-template'], astra_page_classes_disposition('pleine'));
	}

	public function testClassesBody(): void {
		$c = explode(' ', astra_page_classes_body(7, ['titre' => 'afficher', 'disposition' => 'defaut'], false));
		foreach (['page-template-default', 'page', 'page-id-7', 'ast-desktop', 'ast-plain-container', 'ast-no-sidebar', 'ast-single-post', 'ast-hfb-header', 'ast-normal-title-enabled'] as $attendue) {
			$this->assertContains($attendue, $c);
		}
		$this->assertNotContains('home', $c);
		$this->assertNotContains('page-parent', $c);
		$c = explode(' ', astra_page_classes_body(7, ['titre' => 'masquer', 'disposition' => 'pleine'], true, true));
		$this->assertContains('home', $c);
		$this->assertContains('page-parent', $c);
		$this->assertContains('ast-page-builder-template', $c);
		$this->assertNotContains('ast-plain-container', $c);
		$this->assertNotContains('ast-normal-title-enabled', $c);
	}

	public function testContexte(): void {
		$this->assertSame(
			['type_page' => 'page', 'id_article' => 7, 'titre' => 'afficher', 'disposition' => 'normale'],
			astra_page_contexte(7, ['titre' => 'afficher', 'disposition' => 'defaut'], false)
		);
		$this->assertSame('sommaire', astra_page_contexte(7, astra_page_reglages_defaut(), true)['type_page']);
	}
}

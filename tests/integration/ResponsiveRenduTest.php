<?php
use PHPUnit\Framework\TestCase;

final class ResponsiveRenduTest extends TestCase {
	public function testScriptUniqueAvantMastheadDansTousLesGabarits(): void {
		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'statut="publie" AND id_rubrique>0');
		$page = (int) sql_getfetsel('id_article', 'spip_articles', 'page="notre_jardin"');
		$rubrique = (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'statut="publie"');
		$groupe = sql_insertq('spip_groupes_mots', ['titre' => 'Groupe responsive']);
		$mot = sql_insertq('spip_mots', ['titre' => 'Mot responsive', 'id_groupe' => $groupe]);
		$auteur = (int) sql_insertq('spip_auteurs', ['nom' => 'Auteur responsive', 'statut' => '1comite']);
		sql_insertq('spip_auteurs_liens', ['id_auteur' => $auteur, 'objet' => 'article', 'id_objet' => $article, 'vu' => 'non']);
		$this->assertGreaterThan(0, $article);
		$this->assertGreaterThan(0, $page);
		$this->assertGreaterThan(0, $rubrique);
		$avant = lire_config('astra', []);
		try {
			ecrire_config('astra/accueil_type', 'articles');
			foreach ([
				'sommaire' => [], 'article' => ['id_article' => $article], 'page_unique' => ['id_article' => $page],
				'rubrique' => ['id_rubrique' => $rubrique], 'auteur' => ['id_auteur' => $auteur],
				'mot' => ['id_mot' => $mot], 'recherche' => ['recherche' => 'Jardinage'], '404' => [],
			] as $fond => $contexte) {
				$this->verifierPage(recuperer_fond($fond, $contexte), $fond);
			}
			ecrire_config('astra/accueil_type', 'page');
			ecrire_config('astra/accueil_page', $page);
			$this->verifierPage(recuperer_fond('sommaire'), 'accueil statique');
		} finally {
			ecrire_config('astra', $avant);
			sql_delete('spip_auteurs_liens', 'id_auteur=' . $auteur);
			sql_delete('spip_auteurs', 'id_auteur=' . $auteur);
			sql_delete('spip_mots', 'id_mot=' . (int) $mot);
			sql_delete('spip_groupes_mots', 'id_groupe=' . (int) $groupe);
		}
	}

	private function verifierPage(string $html, string $fond): void {
		$this->assertStringContainsString('<body', $html, $fond . ' produit une page');
		$this->assertSame(1, substr_count($html, 'id="astra-header-break-point-early-js"'), $fond);
		$this->assertLessThan(strpos($html, 'id="masthead"'), strpos($html, 'id="astra-header-break-point-early-js"'), $fond);
		$this->assertGreaterThan(strpos($html, '<body'), strpos($html, 'id="astra-header-break-point-early-js"'), $fond);
		$this->assertStringContainsString('"break_point":"921"', $html, $fond);
	}

	public function testDeuxMenusEtSourcePagesGardentDesLiensSimples(): void {
		$avant = lire_config('astra', []);
		try {
			ecrire_config('astra/menu_accueil', 'non');
			ecrire_config('astra/hfb', [
				'entete_mobile' => ['above' => ['above_center' => ['menu-2']]],
				'elements' => ['entete' => ['menu-2' => ['source' => 'pages', 'id' => 0]]],
			]);
			$html = recuperer_fond('inclure/hfb/menu-sans-js');
			$doc = new DOMDocument();
			libxml_use_internal_errors(true);
			$doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
			libxml_clear_errors();
			$x = new DOMXPath($doc);
			$this->assertSame(2, $x->query('//noscript/nav')->length);
			$this->assertSame(_T('astra:navigation_secondaire'), $x->query('//nav')->item(0)->getAttribute('aria-label'));
			$this->assertSame(_T('astra:navigation_principale'), $x->query('//nav')->item(1)->getAttribute('aria-label'));
			$this->assertGreaterThan(0, $x->query('//nav[1]//a[contains(., "Notre jardin")]')->length);
			$this->assertGreaterThan(0, $x->query('//nav//ul/ul | //nav//li/ul/li/a[contains(., "Potager")]')->length);
			$this->assertSame(0, $x->query('//nav//*[@id or @tabindex or @aria-expanded or @aria-haspopup] | //nav//button | //nav//details')->length);
			$this->assertStringNotContainsString('sub-menu', $html);
		} finally {
			ecrire_config('astra', $avant);
		}
	}

	public function testLesQuatreSourcesOntLesMemesDestinationsQueLeMenuInteractif(): void {
		$groupe = (int) sql_insertq('spip_groupes_mots', ['titre' => 'Source responsive']);
		$mot = (int) sql_insertq('spip_mots', ['titre' => 'Source responsive', 'id_groupe' => $groupe]);
		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'statut="publie" AND id_rubrique>0');
		sql_insertq('spip_mots_liens', ['id_mot' => $mot, 'objet' => 'article', 'id_objet' => $article]);
		$rubrique = (int) sql_getfetsel('id_parent', 'spip_rubriques', 'id_parent>0');
		$extraire = function ($html) {
			$d = new DOMDocument();
			libxml_use_internal_errors(true);
			$d->loadHTML('<?xml encoding="utf-8"?><ul>' . $html . '</ul>');
			libxml_clear_errors();
			$r = [];
			foreach ((new DOMXPath($d))->query('//a') as $a) { $r[] = $a->getAttribute('href'); }
			return $r;
		};
		try {
			foreach (['racine' => 0, 'rubrique' => $rubrique, 'groupe_mots' => $groupe, 'pages' => 0] as $source => $id) {
				$env = ['source' => $source, 'id_source' => $id, 'niveaux' => 2];
				$actifs = $extraire(recuperer_fond('inclure/hfb/menu-items', $env));
				$this->assertNotEmpty($actifs, $source . ' possède des liens');
				$this->assertSame($actifs, $extraire(recuperer_fond('inclure/hfb/menu-liens-sans-js', $env)), $source);
			}
		} finally {
			sql_delete('spip_mots_liens', 'id_mot=' . $mot);
			sql_delete('spip_mots', 'id_mot=' . $mot);
			sql_delete('spip_groupes_mots', 'id_groupe=' . $groupe);
		}
	}

	public function testAucunNavVideDansLeRepli(): void {
		$avant = lire_config('astra/hfb', null);
		try {
			ecrire_config('astra/hfb', ['elements' => ['entete' => ['menu-1' => ['source' => 'rubrique', 'id' => 999999999]]]]);
			$this->assertStringNotContainsString('<nav', recuperer_fond('inclure/hfb/menu-sans-js'));
			ecrire_config('astra/hfb', ['entete_mobile' => ['popup' => ['popup_content' => []]]]);
			$this->assertStringNotContainsString('<nav', recuperer_fond('inclure/hfb/menu-sans-js'));
		} finally {
			if ($avant === null) { effacer_config('astra/hfb'); } else { ecrire_config('astra/hfb', $avant); }
		}
	}
}

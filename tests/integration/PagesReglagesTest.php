<?php

use PHPUnit\Framework\TestCase;

/** Formulaire editer_astra_page : droits de l'article, pas ceux de la configuration du site. */
final class PagesReglagesTest extends TestCase {

	private static int $redacteur = 0;
	private static int $autre = 0;
	private static int $page = 0;

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/PagesGabaritTest.php';
		include_spip('formulaires/editer_astra_page');
		include_spip('inc/autoriser');
		$auteur = function ($login) {
			sql_delete('spip_auteurs', 'login=' . sql_quote($login));
			return (int) sql_insertq('spip_auteurs', ['nom' => $login, 'login' => $login, 'statut' => '1comite', 'webmestre' => 'non']);
		};
		self::$redacteur = $auteur('redacteur_page');
		self::$autre = $auteur('redacteur_autre');
		sql_delete('spip_articles', 'page=' . sql_quote('page_redacteur'));
		self::$page = (int) sql_insertq('spip_articles', ['titre' => 'Page du rédacteur', 'texte' => 'Texte.', 'id_rubrique' => -1, 'page' => 'page_redacteur', 'statut' => 'prop', 'lang' => 'fr', 'date' => date('Y-m-d H:i:s')]);
		sql_insertq('spip_auteurs_liens', ['id_auteur' => self::$redacteur, 'objet' => 'article', 'id_objet' => self::$page, 'vu' => 'non']);
	}

	public static function tearDownAfterClass(): void {
		sql_delete('spip_auteurs_liens', 'id_objet=' . self::$page . " AND objet='article'");
		sql_delete('spip_articles', 'id_article=' . self::$page);
		sql_delete('spip_auteurs', sql_in('id_auteur', [self::$redacteur, self::$autre]));
		effacer_config('astra/pages');
		$GLOBALS['visiteur_session'] = [];
	}

	private function connecter(int $id_auteur): void {
		$GLOBALS['visiteur_session'] = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . $id_auteur);
	}

	private function poster(string $titre, string $disposition): void {
		set_request('affichage_titre', $titre);
		set_request('disposition', $disposition);
	}

	public function testRedacteurAuteurPeutEnregistrer(): void {
		$this->connecter(self::$redacteur);
		$this->assertFalse(autoriser('configurer', '_astra'), 'Le rédacteur ne peut pas configurer le site');
		$valeurs = formulaires_editer_astra_page_charger_dist(self::$page);
		$this->assertTrue($valeurs['editable']);
		$this->poster('masquer', 'pleine');
		$this->assertSame([], formulaires_editer_astra_page_verifier_dist(self::$page));
		$this->assertArrayHasKey('message_ok', formulaires_editer_astra_page_traiter_dist(self::$page));
		$this->assertSame(['titre' => 'masquer', 'disposition' => 'pleine'], lire_config('astra/pages/' . self::$page));
	}

	public function testRedacteurNonAuteurRefuse(): void {
		$this->connecter(self::$autre);
		$this->assertFalse(formulaires_editer_astra_page_charger_dist(self::$page)['editable']);
		$this->poster('afficher', 'etroite');
		$this->assertArrayHasKey('message_erreur', formulaires_editer_astra_page_traiter_dist(self::$page));
		$this->assertNotSame(['titre' => 'afficher', 'disposition' => 'etroite'], lire_config('astra/pages/' . self::$page));
	}

	public function testArticleOrdinaireRefuse(): void {
		$this->connecter(self::$redacteur);
		$article = (int) sql_getfetsel('id_article', 'spip_articles', 'id_rubrique>0', '', 'id_article', '0,1');
		$this->assertFalse(formulaires_editer_astra_page_charger_dist($article));
	}

	public function testValeursInvalides(): void {
		$this->connecter(self::$redacteur);
		$this->poster('cache', 'immense');
		$erreurs = formulaires_editer_astra_page_verifier_dist(self::$page);
		$this->assertArrayHasKey('affichage_titre', $erreurs);
		$this->assertArrayHasKey('disposition', $erreurs);
	}

	public function testSuppressionDeLaPageEffaceSesReglages(): void {
		ecrire_config('astra/pages/' . self::$page, ['titre' => 'masquer', 'disposition' => 'pleine']);
		pipeline('trig_supprimer_objets_lies', [['type' => 'article', 'id' => self::$page]]);
		$this->assertNull(lire_config('astra/pages/' . self::$page));
	}
}

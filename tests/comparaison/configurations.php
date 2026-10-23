<?php
/**
 * Configurations du builder transposées des références WordPress (tests/references/wp/Rn-*.php, Pn-*.php),
 * utilisées par les tests d'intégration et les comparaisons SPIP/WordPress. Les textes sont ceux
 * des références, pour que les captures soient comparables.
 */

function astra_configurations_reference(): array {
	return [
		'R0-defaut' => [],
		'R1-entete-complet' => [
			'entete_ordinateur' => [
				'above' => ['above_left' => ['html-1'], 'above_right' => ['social-icons-1']],
				'primary' => ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'search', 'button-1']],
				'below' => ['below_center' => ['menu-2']],
			],
			'entete_mobile' => [
				'popup' => ['popup_content' => ['mobile-menu']],
				'above' => ['above_center' => ['html-1']],
				'primary' => ['primary_left' => ['logo'], 'primary_right' => ['search', 'mobile-trigger']],
			],
			'menu_mobile' => 'dropdown',
			'elements' => ['entete' => [
				'html-1' => ['texte' => 'Ouvert du lundi au vendredi'],
				'button-1' => ['libelle' => 'Nous contacter', 'lien' => 'https://example.org/contact'],
			]],
		],
		'R2-entete-centre' => [
			'entete_ordinateur' => [
				'primary' => ['primary_left' => ['menu-1'], 'primary_center' => ['logo'], 'primary_right' => ['account', 'search']],
				'below' => ['below_left' => ['widget-1'], 'below_right' => ['html-2']],
			],
			'entete_mobile' => [
				'popup' => ['popup_content' => ['mobile-menu', 'search']],
				'primary' => ['primary_left' => ['mobile-trigger'], 'primary_center' => ['logo'], 'primary_right' => ['account']],
			],
			'menu_mobile' => 'off-canvas',
			'elements' => ['entete' => [
				'html-2' => ['texte' => 'Livraison gratuite dès 50 €'],
				'widget-1' => ['blocs' => ['texte'], 'titre' => 'Horaires', 'texte' => 'Lundi–vendredi, 9 h – 18 h'],
			]],
		],
		'R3-pied-colonnes' => [
			'pied' => [
				'above' => ['above_1' => ['html-1'], 'above_2' => ['social-icons-1']],
				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']],
				'below' => ['below_1' => ['copyright'], 'below_2' => ['menu']],
			],
			'rangees' => [
				'pied_above' => ['colonnes' => 2, 'repartition' => '2-equal'],
				'pied_primary' => ['colonnes' => 4, 'repartition' => '4-equal'],
				'pied_below' => ['colonnes' => 2, 'repartition' => '2-equal'],
			],
			'elements' => ['pied' => [
				'html-1' => ['texte' => 'Un site éditorial propulsé par SPIP'],
				'widget-1' => ['blocs' => ['texte'], 'titre' => 'Colonne 1', 'texte' => 'Texte de la colonne 1'],
				'widget-2' => ['blocs' => ['texte'], 'titre' => 'Colonne 2', 'texte' => 'Texte de la colonne 2'],
				'widget-3' => ['blocs' => ['texte'], 'titre' => 'Colonne 3', 'texte' => 'Texte de la colonne 3'],
				'widget-4' => ['blocs' => ['texte'], 'titre' => 'Colonne 4', 'texte' => 'Texte de la colonne 4'],
			]],
		],
		'R4-pied-asymetrique' => [
			'pied' => [
				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['html-1', 'social-icons-1'], 'primary_3' => ['menu']],
				'below' => ['below_1' => ['copyright'], 'below_2' => ['html-2']],
			],
			'rangees' => [
				'pied_primary' => ['colonnes' => 3, 'repartition' => '3-cwide'],
				'pied_below' => ['colonnes' => 2, 'repartition' => '2-lheavy'],
			],
			'elements' => ['pied' => [
				'menu' => ['disposition' => 'vertical'],
				'html-1' => ['texte' => 'Suivez-nous'],
				'html-2' => ['texte' => 'Mentions légales'],
				'widget-1' => ['blocs' => ['texte'], 'titre' => 'À propos', 'texte' => 'Quelques mots sur le site.'],
			]],
		],
		'R5-rangees' => [
			'rangees' => ['entete_primary' => ['hauteur' => 100, 'fond' => '#e8f5e9', 'bordure' => 0]],
		],
		'P1-page' => [
			'page' => 'notre_jardin',
			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
		],
		'P2-accueil' => [
			'astra' => ['accueil_type' => 'page', 'accueil_page' => '@notre_jardin'],
			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
		],
		'P3-page-pleine' => [
			'page' => 'plein_ecran',
			'pages' => ['plein_ecran' => ['titre' => 'masquer', 'disposition' => 'pleine']],
			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
		],
	];
}

/** Partie « builder » d'une configuration : sans les clés propres aux comparaisons de pages. */
function astra_configuration_hfb(array $c): array {
	unset($c['page'], $c['astra'], $c['pages']);
	return $c;
}

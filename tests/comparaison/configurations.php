<?php
/**
 * Configurations du builder transposées des références WordPress (tests/references/wp/Rn-*.php, Pn-*.php),
 * utilisées par les tests d'intégration et les comparaisons SPIP/WordPress. Les textes sont ceux
 * des références, pour que les captures soient comparables.
 */

function astra_configurations_reference(): array {
	// Références des styles du Customizer (R6–R11) : placement de tests/references/styles/appliquer.php.
	$styles = function (array $groupes, array $autres = []) {
		return $autres + [
			'styles' => astra_configuration_styles($groupes),
			'entete_ordinateur' => ['primary' => ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'button-1']]],
			'pied' => [
				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']],
				'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']],
			],
			'rangees' => [
				'pied_primary' => ['colonnes' => 4, 'repartition' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full']],
				'pied_below' => ['colonnes' => 2, 'repartition' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full']],
			],
			'elements' => [
				'entete' => ['button-1' => ['libelle' => 'Nous contacter', 'lien' => 'https://example.org/contact']],
				'pied' => [
					'widget-1' => ['blocs' => ['texte'], 'titre' => 'Colonne 1', 'texte' => 'Texte de la colonne 1'],
					'widget-2' => ['blocs' => ['texte'], 'titre' => 'Colonne 2', 'texte' => 'Texte de la colonne 2'],
					'widget-3' => ['blocs' => ['texte'], 'titre' => 'Colonne 3', 'texte' => 'Texte de la colonne 3'],
					'widget-4' => ['blocs' => ['texte'], 'titre' => 'Colonne 4', 'texte' => 'Texte de la colonne 4'],
				],
			],
		];
	};
	$tous = ['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'];
	// Graisses des titres (R19, R20) : page « Notre jardin » avec les six niveaux de titres,
	// comme ref_texte_titres() côté WordPress.
	$texte = '';
	for ($n = 1; $n <= 6; $n++) {
		$texte .= "<h$n>Titre de niveau $n</h$n>\n";
	}
	$titres = ['page' => 'notre_jardin', 'textes' => ['notre_jardin' => $texte . '<p>Texte de la page.</p>']];
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
		'R12-pied-appareils' => [
			'pied' => [
				'above' => ['above_1' => ['html-1'], 'above_2' => ['social-icons-1']],
				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']],
				'below' => ['below_1' => ['copyright'], 'below_2' => ['menu']],
			],
			'rangees' => [
				'pied_above' => ['colonnes' => 2, 'repartition' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full']],
				'pied_primary' => ['colonnes' => 4, 'repartition' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full']],
				'pied_below' => ['colonnes' => 2, 'repartition' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full']],
			],
			'elements' => ['pied' => [
				'html-1' => ['texte' => 'Un site éditorial propulsé par SPIP'],
				'widget-1' => ['blocs' => ['texte'], 'titre' => 'Colonne 1', 'texte' => 'Texte de la colonne 1'],
				'widget-2' => ['blocs' => ['texte'], 'titre' => 'Colonne 2', 'texte' => 'Texte de la colonne 2'],
				'widget-3' => ['blocs' => ['texte'], 'titre' => 'Colonne 3', 'texte' => 'Texte de la colonne 3'],
				'widget-4' => ['blocs' => ['texte'], 'titre' => 'Colonne 4', 'texte' => 'Texte de la colonne 4'],
			]],
		],
		'R13-pied-widgets-menus' => [
			'pied' => [
				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3']],
			],
			'rangees' => ['pied_primary' => ['colonnes' => 3, 'repartition' => '3-equal']],
			'elements' => ['pied' => [
				'widget-1' => ['blocs' => ['texte'], 'titre' => 'Colonne 1', 'texte' => 'Texte de la colonne 1'],
				'widget-2' => ['blocs' => ['menu'], 'titre' => 'Liens rapides', 'menu' => ['source' => 'racine', 'id' => 0]],
				'widget-3' => ['blocs' => ['menu'], 'titre' => 'Nos services', 'menu' => ['source' => 'racine', 'id' => 0]],
			]],
		],
		'R15-sociaux-google' => [
			'pied' => ['below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]],
			'rangees' => ['pied_below' => ['colonnes' => 2, 'repartition' => '2-equal']],
			'elements' => ['pied' => ['social-icons-1' => ['reseaux' => [
				['reseau' => 'facebook', 'url' => 'https://example.org/facebook'],
				['reseau' => 'google_reviews', 'url' => 'https://example.org/avis'],
			]]]],
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
			'pied' => [
				'above' => ['above_1' => ['html-1']],
				'primary' => ['primary_1' => ['html-2']],
			],
			'rangees' => [
				'entete_primary' => ['hauteur' => 100, 'fond' => '#e8f5e9', 'bordure' => 0],
				'pied_above' => ['separateur' => 2, 'couleur_separateur' => '#c0392b'],
				'pied_primary' => ['separateur' => 0],
				'pied_below' => ['couleur_separateur' => 'var(--ast-global-color-4)'],
			],
			'elements' => ['pied' => [
				'html-1' => ['texte' => 'Rangée haute'],
				'html-2' => ['texte' => 'Rangée principale'],
			]],
		],
		'R6-menu' => $styles(['menu'], ['rubrique' => 'Jardinage']),
		'R7-bouton' => $styles(['bouton']),
		'R8-identite' => $styles(['identite'], ['meta' => ['slogan_site' => 'Un site propulsé par SPIP']]),
		'R9-pied-couleurs' => $styles(['pied']),
		'R10-sociaux' => $styles(['sociaux']),
		'R14-bouton-mobile' => $styles(['menu-mobile']),
		'R11-combinee' => $styles($tous, [
			'astra' => ['police_texte' => "'Raleway', sans-serif", 'police_titres' => "'Poppins', sans-serif"],
			'meta' => ['slogan_site' => 'Un site propulsé par SPIP'],
		]),
		'R16-transparent-page' => [
			'page' => 'notre_jardin',
			'pages' => ['notre_jardin' => ['transparent' => 'active']],
			'styles' => json_decode(file_get_contents(dirname(__DIR__) . '/references/transparent/valeurs/garden.json'), true),
			'elements' => ['entete' => ['menu-1' => ['source' => 'pages', 'id' => 0]]],
		],
		'R17-transparent-global' => [
			'rubrique' => 'Jardinage',
			'styles' => ['transparent-header-enable' => 1] + json_decode(file_get_contents(dirname(__DIR__) . '/references/transparent/valeurs/garden.json'), true),
		],
		'R18-compat-sous-menu' => [
			'compatibilite' => ['submenu-below-header' => false],
		],
		'R19-compat-polices-widgets' => $styles([], $titres + ['compatibilite' => ['can-support-widget-and-editor-fonts' => false]]),
		'R20-titres-graisses' => $styles(['titres'], $titres),
		'R20-titres-graisses-drapeau' => $styles(['titres'], $titres + ['compatibilite' => ['can-support-widget-and-editor-fonts' => false]]),
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

/** Partie « builder » d'une configuration : sans les clés propres aux comparaisons de pages et aux styles. */
function astra_configuration_hfb(array $c): array {
	unset($c['page'], $c['astra'], $c['pages'], $c['styles'], $c['rubrique'], $c['meta']);
	return $c;
}

/** Valeurs de groupes de styles (tests/references/styles/valeurs/<groupe>.json), réunies. */
function astra_configuration_styles(array $groupes): array {
	$valeurs = [];
	foreach ($groupes as $groupe) {
		$valeurs += json_decode(file_get_contents(dirname(__DIR__) . "/references/styles/valeurs/$groupe.json"), true);
	}
	return $valeurs;
}

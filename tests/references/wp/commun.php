<?php
// Fonctions partagées par les configurations de référence du builder (wp eval-file).
// Chaque configuration part de ref_etat_initial() : le résultat ne dépend pas
// des configurations appliquées auparavant. Les menus et catégories, créés au premier
// passage, sont réutilisés ensuite pour garder des identifiants stables.

function ref_etat_initial(): void {
	update_option('astra-settings', [
		'theme-auto-version' => '4.11.0',
		'site-sidebar-layout' => 'right-sidebar',
		'single-post-sidebar-layout' => 'default',
		'single-post-ast-content-layout' => 'default',
	]);
	remove_theme_mods();
	update_option('show_on_front', 'posts');
	update_option('page_on_front', 0);
	delete_option('widget_text');
	delete_option('widget_nav_menu');
	$base = json_decode(file_get_contents(__DIR__ . '/widgets-base.json'), true);
	foreach ($base['options'] as $option => $valeur) {
		update_option($option, $valeur);
	}
	update_option('sidebars_widgets', $base['sidebars_widgets']);
	ref_categories();
	@unlink(WPMU_PLUGIN_DIR . '/astra-polices-locales.php');
}

// Polices locales d'Astra SPIP servies par le WordPress de test, sans Google Fonts : fichiers
// copiés dans wp-content/astra-polices, @font-face d'astra_polices_css() ajouté par un mu-plugin.
function ref_polices_locales(): void {
	require_once dirname(__DIR__, 3) . '/inc/astra_polices.php';
	$dest = WP_CONTENT_DIR . '/astra-polices';
	foreach (astra_polices_locales() as $police) {
		foreach ($police['fichiers'] as [, , $chemin]) {
			wp_mkdir_p(dirname("$dest/$chemin"));
			copy(dirname(__DIR__, 3) . "/$chemin", "$dest/$chemin");
		}
	}
	$css = astra_polices_css(array_column(astra_polices_locales(), 'pile'), function (string $chemin) {
		return content_url("astra-polices/$chemin");
	});
	wp_mkdir_p(WPMU_PLUGIN_DIR);
	file_put_contents(WPMU_PLUGIN_DIR . '/astra-polices-locales.php', "<?php\n"
		. "// Écrit par tests/references/wp/commun.php (ref_polices_locales) : polices d'Astra SPIP, sans Google Fonts.\n"
		. "add_filter('astra_render_fonts', '__return_empty_array');\n"
		. "add_action('wp_head', function () { echo " . var_export('<style id="astra-polices-references">' . $css . '</style>', true) . "; }, 1);\n");
}

// Catégories utilisées par les menus : Jardinage (avec la sous-catégorie Potager) et Actualités.
function ref_categories(): array {
	$ids = [];
	foreach ([['jardinage', 'Jardinage', ''], ['potager', 'Potager', 'jardinage'], ['actualites', 'Actualités', '']] as [$slug, $nom, $parent]) {
		$terme = get_term_by('slug', $slug, 'category');
		if (!$terme) {
			$r = wp_insert_term($nom, 'category', ['slug' => $slug, 'parent' => $parent ? $ids[$parent] : 0]);
			$ids[$slug] = $r['term_id'];
		} else {
			$ids[$slug] = $terme->term_id;
		}
	}
	return $ids;
}

// Menu Jardinage > Potager, Actualités, affecté à un emplacement du thème.
// Un menu déjà créé est réutilisé tel quel : ses entrées gardent les mêmes identifiants
// (classes menu-item-N) d'une capture à l'autre.
function ref_menu(string $nom, string $emplacement): void {
	$cats = ref_categories();
	$menu = wp_get_nav_menu_object($nom);
	if ($menu) {
		$emplacements = get_theme_mod('nav_menu_locations', []);
		$emplacements[$emplacement] = $menu->term_id;
		set_theme_mod('nav_menu_locations', $emplacements);
		return;
	}
	$id = wp_create_nav_menu($nom);
	$ajouter = function (string $slug, int $parent = 0) use ($id, $cats) {
		return wp_update_nav_menu_item($id, 0, [
			'menu-item-title' => get_term($cats[$slug])->name,
			'menu-item-object' => 'category',
			'menu-item-object-id' => $cats[$slug],
			'menu-item-type' => 'taxonomy',
			'menu-item-parent-id' => $parent,
			'menu-item-status' => 'publish',
		]);
	};
	$jardinage = $ajouter('jardinage');
	$ajouter('potager', $jardinage);
	$ajouter('actualites');
	$emplacements = get_theme_mod('nav_menu_locations', []);
	$emplacements[$emplacement] = $id;
	set_theme_mod('nav_menu_locations', $emplacements);
}

function ref_reglages(array $reglages): void {
	$s = get_option('astra-settings', []);
	foreach ($reglages as $cle => $valeur) {
		$s[$cle] = $valeur;
	}
	update_option('astra-settings', $s);
}

function ref_widget_texte(string $zone, string $titre, string $texte): void {
	$widgets = get_option('widget_text', []);
	$n = max(array_merge([1], array_filter(array_keys($widgets), 'is_int'))) + 1;
	$widgets[$n] = ['title' => $titre, 'text' => $texte, 'filter' => true, 'visual' => true];
	update_option('widget_text', $widgets);
	$zones = get_option('sidebars_widgets', []);
	$zones[$zone][] = 'text-' . $n;
	update_option('sidebars_widgets', $zones);
}

// Widget « Menu de navigation » (nav_menu) affichant un menu créé par ref_menu().
function ref_widget_menu(string $zone, string $titre, string $menu): void {
	$widgets = get_option('widget_nav_menu', []);
	$n = max(array_merge([1], array_filter(array_keys($widgets), 'is_int'))) + 1;
	$widgets[$n] = ['title' => $titre, 'nav_menu' => wp_get_nav_menu_object($menu)->term_id];
	update_option('widget_nav_menu', $widgets);
	$zones = get_option('sidebars_widgets', []);
	$zones[$zone][] = 'nav_menu-' . $n;
	update_option('sidebars_widgets', $zones);
}

// Pages uniques des références P* : mêmes titres, textes et ordre que le SPIP d'intégration
// (scripts/install-spip-test.sh). Recréées à l'identique à chaque appel ; la page d'exemple
// de WordPress est supprimée pour que les menus de pages soient comparables.
function ref_pages(): array {
	if ($exemple = get_page_by_path('sample-page')) {
		wp_delete_post($exemple->ID, true);
	}
	$ids = [];
	foreach ([
		['mentions', 'Mentions légales', ''],
		['notre-jardin', 'Notre jardin', ''],
		['potager-collectif', 'Potager collectif', 'notre-jardin'],
		['verger', 'Verger', 'notre-jardin'],
		['plein-ecran', 'Plein écran', ''],
	] as $ordre => [$slug, $titre, $parent]) {
		$existante = get_page_by_path($parent ? "$parent/$slug" : $slug);
		$donnees = [
			'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $titre,
			'post_content' => 'Texte de la page.', 'comment_status' => 'closed', 'menu_order' => $ordre,
			'post_parent' => $parent ? $ids[$parent] : 0,
		];
		if ($existante) {
			$donnees['ID'] = $existante->ID;
		}
		$ids[$slug] = wp_insert_post($donnees);
		delete_post_meta($ids[$slug], 'site-post-title');
		delete_post_meta($ids[$slug], 'ast-site-content-layout');
		delete_post_meta($ids[$slug], 'theme-transparent-header-meta');
	}
	return $ids;
}

// Menu de pages à deux niveaux, dans l'ordre du menu SPIP (par titre ; sous-pages par rang).
function ref_menu_pages(string $emplacement): void {
	$ids = ref_pages();
	$menu = wp_get_nav_menu_object('Pages');
	if (!$menu) {
		$id = wp_create_nav_menu('Pages');
		$ajouter = function (string $slug, int $parent = 0) use ($id, $ids) {
			return wp_update_nav_menu_item($id, 0, [
				'menu-item-title' => get_the_title($ids[$slug]), 'menu-item-object' => 'page',
				'menu-item-object-id' => $ids[$slug], 'menu-item-type' => 'post_type',
				'menu-item-parent-id' => $parent, 'menu-item-status' => 'publish',
			]);
		};
		$ajouter('mentions');
		$jardin = $ajouter('notre-jardin');
		$ajouter('potager-collectif', $jardin);
		$ajouter('verger', $jardin);
		$ajouter('plein-ecran');
		$menu = wp_get_nav_menu_object('Pages');
	}
	$emplacements = get_theme_mod('nav_menu_locations', []);
	$emplacements[$emplacement] = $menu->term_id;
	set_theme_mod('nav_menu_locations', $emplacements);
}

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
	delete_option('widget_text');
	$base = json_decode(file_get_contents(__DIR__ . '/widgets-base.json'), true);
	foreach ($base['options'] as $option => $valeur) {
		update_option($option, $valeur);
	}
	update_option('sidebars_widgets', $base['sidebars_widgets']);
	ref_categories();
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

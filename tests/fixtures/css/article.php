<?php
// Fixture CLI uniquement, sans contenu tiers ; même entrée éditoriale pour les deux moteurs.
if (PHP_SAPI !== 'cli') { exit; }
$tests = dirname(__DIR__, 2);
$texte = file_get_contents($tests . '/references/base/contenu-cascade.html');
if (defined('ABSPATH')) {
	require $tests . '/references/wp/R26-css-cascade.php';
	$existant = get_page_by_path('astra-preuve-article-css', OBJECT, 'post');
	if (getenv('CSS_PREUVE_SUPPRIMER') === '1') {
		if ($existant) { wp_delete_post($existant->ID, true); }
		return;
	}
	$id = wp_insert_post(['ID' => $existant ? $existant->ID : 0, 'post_type' => 'post', 'post_status' => 'publish',
		'post_name' => 'astra-preuve-article-css', 'post_title' => 'Preuve CSS des marges', 'post_content' => $texte, 'comment_status' => 'closed']);
} elseif (defined('_ECRIRE_INC_VERSION')) {
	// Cette sonde CSS utilise le HTML rendu, sans délimiteurs de sérialisation Gutenberg.
	// Elle ne remplace pas les tests d'import réel du lot blocs.
	$texte = preg_replace('~<!--\s*/?wp:.*?-->~s', '', $texte);
	$titre = 'Preuve CSS des marges';
	$id = (int) sql_getfetsel('id_article', 'spip_articles', 'titre=' . sql_quote($titre) . ' AND id_rubrique>0');
	if (getenv('CSS_PREUVE_SUPPRIMER') === '1') {
		if ($id) { sql_delete('spip_articles', 'id_article=' . $id); }
		return;
	}
	$donnees = ['titre' => $titre, 'texte' => $texte, 'statut' => 'publie', 'date' => date('Y-m-d H:i:s'),
		'id_rubrique' => (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'statut="publie"'), 'lang' => 'fr', 'accepter_forum' => 'non'];
	if ($id) { sql_updateq('spip_articles', $donnees, 'id_article=' . $id); }
	else { $id = (int) sql_insertq('spip_articles', $donnees); }
} else {
	throw new RuntimeException('Charger la fixture avec wp eval-file ou spip php:eval');
}
echo (int) $id;

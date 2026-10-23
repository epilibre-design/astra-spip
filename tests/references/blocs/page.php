<?php
// Page « Démonstration des blocs » : un exemple de chaque bloc conservé par wp2spip, textes
// écrits pour les tests. Images importées depuis tests/references/blocs/images.
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$media = [];
foreach (['couverture', 'media'] as $nom) {
	$existant = get_posts(['post_type' => 'attachment', 'title' => $nom, 'post_status' => 'inherit', 'numberposts' => 1]);
	if ($existant) {
		$media[$nom] = $existant[0]->ID;
		continue;
	}
	$tmp = wp_tempnam($nom . '.png');
	copy(__DIR__ . "/images/$nom.png", $tmp);
	$media[$nom] = media_handle_sideload(['name' => "$nom.png", 'tmp_name' => $tmp], 0, $nom);
}
$url = function ($nom) use ($media) {
	return wp_get_attachment_url($media[$nom]);
};

$contenu = <<<HTML
<!-- wp:paragraph --><p>Paragraphe de démonstration.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Titre de section</h2><!-- /wp:heading -->
<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Premier</li><!-- /wp:list-item --><!-- wp:list-item --><li>Second</li><!-- /wp:list-item --></ul><!-- /wp:list -->
<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>Colonne 1</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>Colonne 2</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->
<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>A</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>B</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>C</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->
<!-- wp:group --><div class="wp-block-group"><!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>Groupe imbriqué</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->
<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>Une citation de démonstration.</p><!-- /wp:paragraph --><cite>Une autrice</cite></blockquote><!-- /wp:quote -->
<!-- wp:pullquote --><figure class="wp-block-pullquote"><blockquote><p>Une citation en exergue.</p><cite>Un auteur</cite></blockquote></figure><!-- /wp:pullquote -->
<!-- wp:table --><figure class="wp-block-table"><table><thead><tr><th>Colonne</th><th>Valeur</th></tr></thead><tbody><tr><td>Un</td><td>1</td></tr><tr><td>Deux</td><td>2</td></tr></tbody></table></figure><!-- /wp:table -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.org/">Premier bouton</a></div><!-- /wp:button --><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.org/2">Second bouton</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
<!-- wp:separator --><hr class="wp-block-separator has-alpha-channel-opacity"/><!-- /wp:separator -->
<!-- wp:cover {"url":"{$url('couverture')}","id":{$media['couverture']},"dimRatio":50} --><div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-{$media['couverture']}" alt="" src="{$url('couverture')}" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph --><p>Texte sur la couverture</p><!-- /wp:paragraph --></div></div><!-- /wp:cover -->
<!-- wp:media-text {"mediaId":{$media['media']},"mediaType":"image"} --><div class="wp-block-media-text is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="{$url('media')}" alt="" class="wp-image-{$media['media']} size-full"/></figure><div class="wp-block-media-text__content"><!-- wp:paragraph --><p>Texte à côté du média.</p><!-- /wp:paragraph --></div></div><!-- /wp:media-text -->
<!-- wp:code --><pre class="wp-block-code"><code>echo "code";</code></pre><!-- /wp:code -->
<!-- wp:verse --><pre class="wp-block-verse">Des vers
de démonstration</pre><!-- /wp:verse -->
HTML;

$page = get_page_by_path('demonstration-des-blocs');
$id = wp_insert_post(['ID' => $page ? $page->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'demonstration-des-blocs', 'post_title' => 'Démonstration des blocs', 'post_content' => $contenu, 'comment_status' => 'closed']);
update_option('wp2spip_blocs_page', $id);
echo "Page $id\n";

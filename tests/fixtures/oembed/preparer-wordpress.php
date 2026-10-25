<?php
/** Contenu de preuve local ; import réel par wp2spip avant toute comparaison SPIP. */
if (PHP_SAPI !== 'cli' || !defined('ABSPATH')) { exit; }
$origine = rtrim(getenv('OEMBED_FIXTURE_URL') ?: 'http://localhost:8428', '/');
if (wp_get_theme('astra')->get('Version') !== '4.13.8' || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['127.0.0.1', 'localhost'], true)) { throw new RuntimeException('Réservé au WordPress local Astra 4.13.8'); }
$mu = WPMU_PLUGIN_DIR . '/astra-preuve-oembed.php';
if (getenv('OEMBED_PREUVE_ACTION') === 'restaurer') {
	$avant = get_option('astra_preuve_oembed_avant', null);
	if ($avant !== null) { update_option('astra-settings', $avant); delete_option('astra_preuve_oembed_avant'); }
	if (is_file($mu)) { unlink($mu); }
	return;
}
$essai = getenv('OEMBED_PREUVE_ESSAI') ?: bin2hex(random_bytes(8));
if (!preg_match('/^[a-zA-Z0-9-]+$/', $essai)) { throw new RuntimeException('Identifiant de preuve invalide'); }
if (get_option('astra_preuve_oembed_avant', null) === null) { update_option('astra_preuve_oembed_avant', get_option('astra-settings', [])); }
$s = get_option('astra-settings', []);
$s['site-content-width'] = 640;
$s['site-sidebar-layout'] = 'no-sidebar';
$s['single-page-sidebar-layout'] = 'no-sidebar';
update_option('astra-settings', $s);
if (!is_dir(WPMU_PLUGIN_DIR)) { mkdir(WPMU_PLUGIN_DIR, 0775, true); }
$code = <<<'CODE'
<?php
// Fournisseur propre à ce site de test : seul son endpoint est autorisé par le client sûr.
$origine = @ORIGINE@;
$permis = static function ($url) use ($origine) {
 $a=wp_parse_url($url);$b=wp_parse_url($origine);
 return ($a['scheme']??'')===($b['scheme']??'')&&($a['host']??'')===($b['host']??'')&&($a['port']??0)===($b['port']??0)&&($a['path']??'')==='/oembed';
};
wp_oembed_add_provider('#^'.preg_quote($origine,'#').'/video/(large|classique|erreur|invalide)(?:\?.*)?$#',$origine.'/oembed',true);
add_filter('http_request_host_is_external',static function($externe,$host,$url) use($permis){return $permis($url)?true:$externe;},10,3);
add_filter('http_allowed_safe_ports',static function($ports,$host,$url) use($permis,$origine){if($permis($url))$ports[]=(int)wp_parse_url($origine,PHP_URL_PORT);return $ports;},10,3);
CODE;
file_put_contents($mu, str_replace('@ORIGINE@', var_export($origine,true), $code));
$template = file_get_contents(__DIR__ . '/bloc-wordpress.html');
$pages = [];
foreach (['large','classique','erreur','invalide','inconnu','temoin'] as $cas) {
	$url = $cas === 'inconnu' ? "$origine/inconnu?essai=$essai" : "$origine/video/$cas?essai=$essai";
	$legende = 'Légende locale ' . $cas;
	$contenu = $cas === 'temoin' ? '<!-- wp:paragraph --><p>Article témoin sans vidéo.</p><!-- /wp:paragraph -->'
		: str_replace(['@URL@','@RATIO@','@LEGENDE@'], [$url,$cas==='classique'?'wp-embed-aspect-4-3':'wp-embed-aspect-16-9',$legende], $template);
	$slug = 'astra-preuve-oembed-' . $cas;
	$existant = get_page_by_path($slug);
	$id = wp_insert_post(['ID'=>$existant?$existant->ID:0, 'post_type'=>'page','post_status'=>'publish','post_name'=>$slug,
		'post_title'=>'Preuve oEmbed '.$cas,'post_content'=>$contenu,'comment_status'=>'closed'], true);
	if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
	update_post_meta($id,'site-sidebar-layout','no-sidebar');
	$pages[$cas]=['id'=>$id,'slug'=>$slug,'url'=>$url,'legende'=>$legende,'source_sha256'=>hash('sha256',$contenu)];
}
$manifeste = ['essai'=>$essai,'origine'=>$origine,'pages'=>$pages];
update_option('astra_preuve_oembed_manifest', $manifeste);
echo json_encode($manifeste, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),"\n";

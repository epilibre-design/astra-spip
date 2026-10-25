<?php
/**
 * Schémas autorisés dans les liens du builder, sans mutation de la configuration.
 * Référence : WordPress 7.1.3, wp-includes/functions.php::wp_allowed_protocols(),
 * liste utilisée par esc_url() dans les composants Astra 4.13.8.
 * Adaptation SPIP : filtrage avant attribut_html(), qui échappe mais ne valide pas un protocole.
 * @license GNU/GPL v3+
 */
function astra_url_lien(string $url): string {
	$url = trim($url);
	// Reconnaître aussi un schéma masqué par des entités ou caractères de contrôle.
	$verification = preg_replace('/[\x00-\x20\x7f]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	if (preg_match('~^([^/?#]*):~', $verification, $m)) {
		$protocoles = ['http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn'];
		if (!in_array(strtolower($m[1]), $protocoles, true)) {
			return '';
		}
	}
	return $url;
}

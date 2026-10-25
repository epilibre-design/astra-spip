<?php
/**
 * Astra pour SPIP — graisses des titres (inc/class-astra-dynamic-css.php d'Astra 4.13.8 :
 * typographie des titres et compatibilité can-support-widget-and-editor-fonts).
 *
 * css/astra-dynamic.css fige la graisse 600 des titres, de chaque niveau et des titres des widgets
 * h1 à h3 ; seules les différences avec ce CSS figé sont émises, après lui.
 *
 * @license GPL-3.0-or-later
 */

/** Graisse figée par css/astra-dynamic.css (headings-font-weight par défaut). */
const ASTRA_TITRES_GRAISSE_FIGEE = '600';

/**
 * Graisse que calcule chaque niveau dans Astra. $c : drapeaux de compatibilité normalisés.
 * Retourne [graisse des titres, [n => graisse d'Astra pour le niveau n]]. Une option absente prend
 * le défaut d'Astra ; une valeur vide (importée) reste vide : Astra n'émet alors pas la propriété.
 */
function astra_titres_graisses(array $s, array $c): array {
	$h = (string) ($s['headings-font-weight'] ?? ASTRA_TITRES_GRAISSE_FIGEE);
	// Valeur de repli : inherit, ou normal avec le drapeau (support_font_css_to_widget_and_in_editor()).
	$repli = astra_compatibilite_polices_anciennes($c) ? 'normal' : 'inherit';
	$niveaux = [];
	for ($n = 1; $n <= 6; $n++) {
		$g = (string) ($s["font-weight-h$n"] ?? 'inherit');
		$niveaux[$n] = $g === $repli ? $h : $g;
	}
	return [$h, $niveaux];
}

function astra_styles_titres(array $s, array $hfb, array $c = []): array {
	[$h, $niveaux] = astra_titres_graisses($s, $c);
	$titres = [];
	for ($n = 1; $n <= 6; $n++) {
		array_push($titres, "h$n");
	}
	$titres[] = ".entry-content :where(h1,h2,h3,h4,h5,h6)";
	$regles = [];
	// Graisse des titres vide ou inherit (défaut des installations d'avant Astra 4.0) : la règle
	// générale n'a pas de graisse (astra_get_css_value()).
	$sans_generale = $h === '' || $h === 'inherit';
	if ($sans_generale) {
		// Neutralisation par les graisses de css/main.min.css : titres hérités, .site-title à 400.
		$regles[] = ['', implode(',', $titres), ['font-weight' => 'inherit']];
		$regles[] = ['', '.site-title', ['font-weight' => '400']];
		$regles[] = ['', '.site-title a', ['font-weight' => 'inherit']];
	} elseif ($h !== ASTRA_TITRES_GRAISSE_FIGEE) {
		$regles[] = ['', implode(',', $titres) . ',.site-title,.site-title a', ['font-weight' => $h]];
	}
	// Graisse des titres une fois la règle générale appliquée.
	$generale = $sans_generale ? 'inherit' : $h;
	// Chaque niveau calcule sa graisse, ou celle des titres quand Astra n'en émet pas (inherit ou
	// vide) : comparée à celle que le niveau a après la règle générale.
	foreach ($niveaux as $n => $g) {
		$t = $g === 'inherit' || $g === '' ? $generale : $g;
		if ($t !== $generale) {
			$regles[] = ['', "h$n,.entry-content :where(h$n)", ['font-weight' => $t]];
		}
	}
	if (!astra_compatibilite_polices_anciennes($c)) {
		for ($n = 1; $n <= 3; $n++) {
			// Graisse vide : Astra n'émet pas la règle, celle de .widget-title (400) s'applique.
			$w = $niveaux[$n] === '' ? '400' : $niveaux[$n];
			if ($w !== ASTRA_TITRES_GRAISSE_FIGEE) {
				$regles[] = ['', "h$n.widget-title", ['font-weight' => $w]];
			}
		}
	} else {
		// Neutralisation : avec le drapeau, Astra n'émet pas les graisses des titres des widgets ; la
		// graisse de .widget-title de css/main.min.css (400) s'applique.
		$regles[] = ['', 'h1.widget-title,h2.widget-title,h3.widget-title', ['font-weight' => '400']];
	}
	return $regles;
}

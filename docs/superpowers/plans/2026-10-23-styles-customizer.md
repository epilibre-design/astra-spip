# Styles du Customizer — plan de mise en œuvre

> **Pour les agents :** sous-compétence requise : superpowers:subagent-driven-development (recommandé) ou superpowers:executing-plans, tâche par tâche. Les étapes sont des cases à cocher (`- [ ]`).

**But :** reprendre dans Astra SPIP les réglages de style du Customizer d'Astra 4.11.0 (menus, bouton, bouton de l'en-tête, identité, pied, icônes sociales, neuvième couleur en `rgba()`, polices locales), avec un rendu identique à WordPress, mesuré et documenté ; version 1.2.0.

**Architecture :** une méta `astra/styles` porte les options d'Astra sous leurs noms et dans leurs structures d'origine. Un registre (`inc/astra_styles.php`) décrit chaque option ; une normalisation commune valide les valeurs ; un module par groupe (`inc/styles/<groupe>.php`) porte les règles ordinateur du fichier de CSS dynamique d'Astra, avec ses sélecteurs, son ordre et ses conditions d'émission. Le CSS est écrit comme `astra_parse_css()` et inséré dans `inclure/head-vars.html`. L'équivalence est prouvée par des attendus capturés dans WordPress (tests unitaires) et par des références visuelles R6–R11 (captures, styles calculés, polices).

**Pile technique :** PHP 7.4+ (plugin), SPIP 4.4, PHPUnit 11.5, WordPress de test avec Astra 4.11.0 et WP-CLI, Playwright (Chromium), Python 3 (outillage des attendus).

**Spec :** `docs/superpowers/specs/2026-10-23-styles-customizer-design.md` (validée par Gilles et Codex).

## Contraintes globales

- Rien ne change visuellement sans réglage : sans `astra/styles`, le HTML et le CSS produits sont identiques octet pour octet à ceux de la version 1.1.0, sauf les deux suppressions de balisage de l’identité décrites à la tâche 11 (slogan masqué et enveloppe vide). Aucun slogan masqué ne devient visible ; les captures et les styles calculés restent identiques à 1280, 768 et 390 px (vérifié aux tâches 3, 11 et 14).
- Avec un réglage, l'effet est celui d'Astra 4.11.0 : mêmes sélecteurs, même ordre, mêmes conditions d'émission ; fichier et fonction d'Astra cités en commentaire.
- Aucune chaîne libre dans une feuille de style : toute valeur passe par `astra_styles_normaliser()`.
- Valeurs responsives d'Astra conservées entières ; seule `desktop` est exploitée.
- Aucun service tiers (pas de Google Fonts).
- Le plugin reste compatible PHP 7.4 : pas de syntaxe PHP 8 dans `inc/`, `formulaires/`, `astra_*.php` (les tests PHPUnit peuvent utiliser les attributs PHP 8).
- Langue du code, des commentaires et des messages : français, comme le dépôt.
- Dépôt partagé avec Codex : `git add` fichier par fichier ; ne jamais indexer `.gitignore`, `.ignore`, `tests/references/__pycache__/*.pyc`, `docs/inventaires/2026-10-23-preparation-publication.md`.
- Messages de commit sans trailer (ni `Co-Authored-By`, ni `Claude-Session`).
- Tests d'intégration : le SPIP de test (`vendor/spip/spip`) doit avoir `plugins/astra` pointant vers l'arbre de travail testé. Dans un worktree : `cp -a <dépôt principal>/vendor vendor && ln -sfn "$PWD" vendor/spip/spip/plugins/astra`.
- Commandes : `vendor/bin/phpunit --testsuite unit [--filter …]` ; `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php [--filter …]`. Après une modification de squelette, vider `vendor/spip/spip/tmp/cache/skel/` si un rendu semble ancien.

## Vérification préalable du plan

Le code de ce plan a été écrit et exécuté dans un worktree jetable de `origin/main` (923359e) avant
rédaction :

- tests unitaires : 67 tests, 1015 assertions ; tests d'intégration : 69 tests, 232 assertions ;
- rendu de `inclure/head-vars`, `inclure/header`, `inclure/footer`, `sommaire` et `article` sans
  réglage : identique à `main` (horodatages des feuilles exceptés) ;
- `comparer_captures.sh` R0–R5 : inchangé (toutes les vues sous le seuil, R5 styles identiques à
  1280, 768 et 390 px) ; R6–R11 : toutes les vues sous le seuil (plus grand écart 0,35 %), styles
  calculés identiques aux trois largeurs, polices chargées des deux côtés ;
- `capturer.sh` relancé après la tâche 14 : attendus identiques ;
- `tests/e2e/formulaire_styles.cjs` : OK, valeurs tablette et mobile importées conservées.

## Fichiers

| Fichier | Rôle | Tâche |
|---|---|---|
| `tests/references/styles/` | Outillage et attendus WordPress des groupes | 1, 14 |
| `inc/astra_styles.php` | Registre, normalisation, aides, écriture, point d'entrée | 2, 3 |
| `inc/styles/{menu,bouton,bouton_entete,identite,pied,sociaux}.php` | Un module par groupe | 4–9 |
| `inc/astra_polices.php` | Registre des polices locales, `@font-face` | 2 (vide), 12 |
| `inc/astra_styles_saisie.php` | Sections, champs, fusion et refus de la saisie | 13 |
| `astra_fonctions.php` | Filtres des squelettes | 3, 10, 11, 12, 13 |
| `astra_administrations.php` | Mise à jour : migration du titre et du slogan | 11 |
| `inclure/head-vars.html`, `inclure/identite.html` | Insertion du CSS, identité | 3, 11, 12 |
| `formulaires/configurer_astra.{html,php}` | Neuvième couleur, polices, affichage | 10, 11, 12 |
| `formulaires/configurer_astra_styles.{html,php}`, `prive/squelettes/contenu/configurer_astra_styles.html` | Formulaire « Couleurs et typographie » | 13 |
| `fonts/`, `licenses/OFL-1.1-*.txt` | Polices Raleway et Poppins | 12, 15 |
| `tests/comparaison/*`, `tests/references/wp/*` | Références visuelles R6–R11 | 14 |
| `paquet.xml`, `lang/astra_{fr,en}.php`, `README.md`, `CREDITS.md`, `licenses/README.md`, `tests/references/README.md` | Déclarations, langues, documentation | 10–15 |

---

### Tâche 1 : outillage des attendus WordPress

Fige le CSS qu'Astra 4.11.0 produit pour chaque groupe avec les valeurs du modèle Garden
Maintenance. Les attendus sont versionnés : les tests unitaires n'ont pas besoin de WordPress.

**Fichiers :**
- Créer : `tests/references/styles/appliquer.php`, `capturer.sh`, `regles.py`
- Créer : `tests/references/styles/valeurs/{menu,bouton,bouton-entete,identite,pied,sociaux}.json`
- Créer : `tests/references/styles/attendus/{base,menu,bouton,bouton-entete,identite,pied,sociaux}.css`
- Créer : `tests/references/styles/attendus/{bouton,bouton-entete}.neutralisations.css`

**Interfaces :**
- Produit : `valeurs/<groupe>.json` (options d'Astra telles que dans le `site.json` du modèle) ; `attendus/<groupe>.css` (une règle par ligne, format `[@media …] sélecteur{déclarations}`) ; `attendus/base.css` (CSS dynamique sans option) ; neutralisations au même format que les attendus. Groupes : `menu`, `bouton`, `bouton-entete`, `identite`, `pied`, `sociaux`.

- [ ] **Étape 1 : écrire `appliquer.php`**

````php
<?php
/**
 * Référence des styles du Customizer : état R0, éléments placés comme dans Garden Maintenance,
 * puis les options du groupe (valeurs/<groupe>.json ; groupe « base » : aucune option).
 * Usage : GROUPE=<groupe> wp --path=$WP eval-file tests/references/styles/appliquer.php
 */
require dirname(__DIR__) . '/wp/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
$zones = function (array $noms, array $remplies) {
	return array_merge(array_fill_keys($noms, []), $remplies);
};
$entete = ['left', 'left_center', 'center', 'right_center', 'right'];
$reglages = [
	'header-desktop-items' => [
		'popup' => ['popup_content' => ['mobile-menu']],
		'above' => $zones(array_map(function ($z) { return "above_$z"; }, $entete), []),
		'primary' => $zones(array_map(function ($z) { return "primary_$z"; }, $entete), ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'button-1']]),
		'below' => $zones(array_map(function ($z) { return "below_$z"; }, $entete), []),
	],
	'footer-desktop-items' => [
		'above' => $zones(['above_1', 'above_2', 'above_3', 'above_4', 'above_5'], []),
		'primary' => $zones(['primary_1', 'primary_2', 'primary_3', 'primary_4', 'primary_5'], ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']]),
		'below' => $zones(['below_1', 'below_2', 'below_3', 'below_4', 'below_5'], ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]),
	],
	'hb-footer-column' => '4', 'hb-footer-layout' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'],
	'hbb-footer-column' => '2', 'hbb-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
];
$groupe = getenv('GROUPE') ?: 'base';
if ($groupe !== 'base') {
	$reglages += json_decode(file_get_contents(__DIR__ . "/valeurs/$groupe.json"), true);
}
ref_reglages($reglages);
````

- [ ] **Étape 2 : écrire `regles.py`**

````python
"""Règles d'un CSS ([media] sélecteur{déclarations}), et règles de B absentes de A.

Usage : python3 regles.py A.css B.css  → une règle par ligne, dans l'ordre de B.
"""
import re
import sys


def regles(css):
    out, ctx = [], ''
    for t in re.findall(r'@media[^{]*\{|[^{}]+\{[^{}]*\}|\}', css):
        t = t.strip()
        if t.startswith('@media'):
            ctx = t[len('@media'):-1].strip()
            continue
        if t == '}':
            ctx = ''
            continue
        sel, dec = t.split('{', 1)
        out.append((f'[@media {ctx}] ' if ctx else '') + sel.strip() + '{' + dec.rstrip('}').strip() + '}')
    return out


if __name__ == '__main__':
    a = set(regles(open(sys.argv[1], encoding='utf-8').read()))
    for r in regles(open(sys.argv[2], encoding='utf-8').read()):
        if r not in a:
            print(r)
````

- [ ] **Étape 3 : écrire `capturer.sh`** (puis `chmod +x`)

````bash
#!/usr/bin/env bash
# Capture le CSS dynamique de WordPress pour chaque groupe de styles et en tire les attendus.
# Usage : WP=<racine WordPress> [URL=http://127.0.0.1:8401] tests/references/styles/capturer.sh
# Écrit attendus/base.css (sans option) et attendus/<groupe>.css (règles ajoutées ou modifiées
# par les options du groupe, par rapport à base.css), puis remet le WordPress en état R0.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
URL=${URL:-http://127.0.0.1:8401}
: "${WP:?WP doit indiquer la racine du WordPress de test}"
css() {
	curl -sf "$URL/" | php -r '$h = stream_get_contents(STDIN); if (!preg_match("~<style id=\"astra-theme-css-inline-css\">(.*?)</style>~s", $h, $m)) { exit(1); } echo $m[1];'
}
for groupe in base menu bouton bouton-entete identite pied sociaux; do
	GROUPE=$groupe wp --path="$WP" eval-file "$ICI/appliquer.php"
	css > "$ICI/attendus/.$groupe.complet.css"
done
mv "$ICI/attendus/.base.complet.css" "$ICI/attendus/base.css"
for groupe in menu bouton bouton-entete identite pied sociaux; do
	python3 "$ICI/regles.py" "$ICI/attendus/base.css" "$ICI/attendus/.$groupe.complet.css" > "$ICI/attendus/$groupe.css"
	rm "$ICI/attendus/.$groupe.complet.css"
	echo "$groupe : $(wc -l < "$ICI/attendus/$groupe.css") règles"
done
wp --path="$WP" eval-file "$ICI/../wp/R0-defaut.php"
````

- [ ] **Étape 4 : écrire les valeurs des groupes** (relevées dans `astra-site-customizer-data` du modèle 189117, reprises telles quelles)

`valeurs/menu.json` :
````json
{
	"header-menu1-bg-obj-responsive": {
		"desktop": {
			"background-color": "",
			"background-image": "",
			"background-repeat": "repeat",
			"background-position": "center center",
			"background-size": "auto",
			"background-attachment": "scroll",
			"overlay-type": "",
			"overlay-color": "",
			"overlay-gradient": ""
		},
		"tablet": {
			"background-color": "",
			"background-image": "",
			"background-repeat": "",
			"background-position": "",
			"background-size": "",
			"background-attachment": "",
			"overlay-type": "",
			"overlay-color": "",
			"overlay-gradient": ""
		},
		"mobile": {
			"background-color": "",
			"background-image": "",
			"background-repeat": "",
			"background-position": "",
			"background-size": "",
			"background-attachment": "",
			"overlay-type": "",
			"overlay-color": "",
			"overlay-gradient": ""
		}
	},
	"header-menu1-color-responsive": {
		"desktop": "var(--ast-global-color-7)",
		"tablet": "",
		"mobile": ""
	},
	"header-menu1-h-bg-color-responsive": {
		"desktop": "",
		"tablet": "",
		"mobile": ""
	},
	"header-menu1-h-color-responsive": {
		"desktop": "var(--ast-global-color-0)",
		"tablet": "",
		"mobile": ""
	},
	"header-menu1-a-bg-color-responsive": {
		"desktop": "",
		"tablet": "",
		"mobile": ""
	},
	"header-menu1-a-color-responsive": {
		"desktop": "var(--ast-global-color-0)",
		"tablet": "",
		"mobile": ""
	},
	"header-menu1-font-size": {
		"desktop": "",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"header-menu1-font-weight": "inherit",
	"header-menu1-font-family": "inherit",
	"header-menu1-submenu-b-color": "#eaeaea"
}
````

`valeurs/bouton.json` :
````json
{
	"button-color": "var(--ast-global-color-4)",
	"button-h-color": "var(--ast-global-color-2)",
	"button-bg-color": "var(--ast-global-color-0)",
	"button-bg-h-color": "var(--ast-global-color-5)",
	"theme-button-padding": {
		"desktop": {
			"top": "17",
			"right": "26",
			"bottom": "17",
			"left": "26"
		},
		"tablet": {
			"top": "",
			"right": "",
			"bottom": "",
			"left": ""
		},
		"mobile": {
			"top": "",
			"right": "",
			"bottom": "",
			"left": ""
		},
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"button-radius-fields": {
		"desktop": {
			"top": 8,
			"right": 8,
			"bottom": 8,
			"left": 8
		},
		"tablet": {
			"top": "",
			"right": "",
			"bottom": "",
			"left": ""
		},
		"mobile": {
			"top": "",
			"right": "",
			"bottom": "",
			"left": ""
		},
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"theme-button-border-group-border-size": {
		"top": "0",
		"right": "0",
		"bottom": "0",
		"left": "0"
	},
	"font-weight-button": "500",
	"font-family-button": "'Poppins', sans-serif",
	"font-size-button": {
		"desktop": "14",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"font-extras-button": {
		"line-height": "1",
		"line-height-unit": "em",
		"letter-spacing": "0.5",
		"letter-spacing-unit": "px",
		"text-transform": "capitalize",
		"text-decoration": ""
	}
}
````

`valeurs/bouton-entete.json` :
````json
{
	"header-button1-font-family": "inherit",
	"header-button1-font-weight": "inherit",
	"header-button1-font-size": {
		"desktop": "",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"header-button1-text-color": {
		"desktop": "var(--ast-global-color-4)",
		"tablet": "",
		"mobile": ""
	},
	"header-button1-back-color": {
		"desktop": "var(--ast-global-color-0)",
		"tablet": "",
		"mobile": ""
	},
	"header-button1-text-h-color": {
		"desktop": "rgba(255,255,255,0.75)",
		"tablet": "",
		"mobile": ""
	},
	"header-button1-back-h-color": {
		"desktop": "var(--ast-global-color-0)",
		"tablet": "",
		"mobile": ""
	},
	"header-button1-border-size": {
		"top": "",
		"right": "",
		"bottom": "",
		"left": ""
	},
	"header-button1-border-color": {
		"desktop": "",
		"tablet": "",
		"mobile": ""
	},
	"header-button1-border-radius-fields": {
		"desktop": {
			"top": "",
			"right": "",
			"bottom": "",
			"left": ""
		},
		"tablet": {
			"top": "",
			"right": "",
			"bottom": "",
			"left": ""
		},
		"mobile": {
			"top": "",
			"right": "",
			"bottom": "",
			"left": ""
		},
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	}
}
````

`valeurs/identite.json` :
````json
{
	"ast-header-responsive-logo-width": {
		"desktop": "40",
		"tablet": "",
		"mobile": ""
	},
	"header-color-site-title": "var(--ast-global-color-2)",
	"header-color-h-site-title": "",
	"header-color-site-tagline": "var(--ast-global-color-3)",
	"display-site-title-responsive": {
		"desktop": 1,
		"tablet": 1,
		"mobile": 1
	},
	"display-site-tagline-responsive": {
		"desktop": true,
		"tablet": true,
		"mobile": true
	},
	"font-size-site-title": {
		"desktop": "20",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"font-size-site-tagline": {
		"desktop": "11",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	}
}
````

`valeurs/pied.json` :
````json
{
	"footer-widget-1-title-color": {
		"desktop": "var(--ast-global-color-4)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-1-color": {
		"desktop": "var(--ast-global-color-5)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-1-link-color": {
		"desktop": "",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-1-link-h-color": {
		"desktop": "var(--ast-global-color-5)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-1-font-size": {
		"desktop": "",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"footer-widget-1-content-font-size": {
		"desktop": "15",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"footer-widget-2-title-color": {
		"desktop": "var(--ast-global-color-4)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-2-color": {
		"desktop": "var(--ast-global-color-5)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-2-link-color": {
		"desktop": "var(--ast-global-color-5)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-2-link-h-color": {
		"desktop": "",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-2-font-size": {
		"desktop": "",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"footer-widget-2-content-font-size": {
		"desktop": "14",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"footer-widget-3-title-color": {
		"desktop": "var(--ast-global-color-4)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-3-color": {
		"desktop": "var(--ast-global-color-5)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-3-link-color": {
		"desktop": "var(--ast-global-color-5)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-3-link-h-color": {
		"desktop": "",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-3-font-size": {
		"desktop": "",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"footer-widget-3-content-font-size": {
		"desktop": "14",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"footer-widget-4-title-color": {
		"desktop": "var(--ast-global-color-4)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-4-color": {
		"desktop": "var(--ast-global-color-5)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-4-link-color": {
		"desktop": "var(--ast-global-color-5)",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-4-link-h-color": {
		"desktop": "",
		"tablet": "",
		"mobile": ""
	},
	"footer-widget-4-font-size": {
		"desktop": "15",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"footer-widget-4-content-font-size": {
		"desktop": "14",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	},
	"footer-copyright-color": "var(--ast-global-color-5)",
	"font-size-section-footer-copyright": {
		"desktop": "15",
		"tablet": "",
		"mobile": "",
		"desktop-unit": "px",
		"tablet-unit": "px",
		"mobile-unit": "px"
	}
}
````

`valeurs/sociaux.json` :
````json
{
	"footer-social-1-space": {
		"desktop": "20",
		"tablet": "",
		"mobile": ""
	},
	"footer-social-1-size": {
		"desktop": "16",
		"tablet": "",
		"mobile": ""
	},
	"footer-social-1-color": {
		"desktop": "var(--ast-global-color-0)"
	},
	"footer-social-1-h-color": {
		"desktop": "var(--ast-global-color-5)"
	},
	"footer-social-1-bg-color": "",
	"footer-social-1-bg-h-color": "",
	"footer-social-1-color-type": "custom"
}
````

- [ ] **Étape 5 : capturer les attendus**

Run : `WP=<racine du WordPress de test> tests/references/styles/capturer.sh` (WordPress servi sur `URL`, défaut `http://127.0.0.1:8401`)
Expected :
```text
menu : 7 règles
bouton : 8 règles
bouton-entete : 2 règles
identite : 7 règles
pied : 14 règles
sociaux : 7 règles
```
`attendus/base.css` fait 37 lignes. Contenu attendu des fichiers d'attendus :

`attendus/menu.css` :
````css
.ast-builder-menu-1 .menu-item > .menu-link{color:var(--ast-global-color-7);}
.ast-builder-menu-1 .menu-item > .ast-menu-toggle{color:var(--ast-global-color-7);}
.ast-builder-menu-1 .menu-item:hover > .menu-link,.ast-builder-menu-1 .inline-on-mobile .menu-item:hover > .ast-menu-toggle{color:var(--ast-global-color-0);}
.ast-builder-menu-1 .menu-item:hover > .ast-menu-toggle{color:var(--ast-global-color-0);}
.ast-builder-menu-1 .menu-item.current-menu-item > .menu-link,.ast-builder-menu-1 .inline-on-mobile .menu-item.current-menu-item > .ast-menu-toggle,.ast-builder-menu-1 .current-menu-ancestor > .menu-link{color:var(--ast-global-color-0);}
.ast-builder-menu-1 .menu-item.current-menu-item > .ast-menu-toggle{color:var(--ast-global-color-0);}
.ast-builder-menu-1 .sub-menu,.ast-builder-menu-1 .inline-on-mobile .sub-menu{border-top-width:2px;border-bottom-width:0px;border-right-width:0px;border-left-width:0px;border-color:#eaeaea;border-style:solid;}
````

`attendus/bouton.css` :
````css
.wp-block-button.is-style-outline .wp-block-button__link:hover,.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link:focus,.wp-block-buttons .wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color):hover,.wp-block-buttons .wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color):hover{color:var(--ast-global-color-2);background-color:var(--ast-global-color-5);border-color:var(--ast-global-color-5);}
.wp-block-button.is-style-outline .wp-block-button__link:hover,.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link:focus,.wp-block-buttons .wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color):hover,.wp-block-buttons .wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color):hover,.ast-outline-button:hover,.ast-outline-button:focus,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button:hover,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button:focus{color:var(--ast-global-color-2);background-color:var(--ast-global-color-5);border-color:var(--ast-global-color-5);}
.wp-block-button .wp-block-button__link{color:var(--ast-global-color-4);}
.wp-block-button .wp-block-button__link:hover,.wp-block-button .wp-block-button__link:focus{color:var(--ast-global-color-2);background-color:var(--ast-global-color-5);border-color:var(--ast-global-color-5);}
.wp-block-button .wp-block-button__link,.wp-block-search .wp-block-search__button,body .wp-block-file .wp-block-file__button{border-style:solid;border-top-width:0px;border-right-width:0px;border-left-width:0px;border-bottom-width:0px;border-color:var(--ast-global-color-0);background-color:var(--ast-global-color-0);color:var(--ast-global-color-4);font-family:'Poppins',sans-serif;font-weight:500;line-height:1em;text-transform:capitalize;letter-spacing:0.5px;font-size:14px;font-size:0.875rem;border-top-left-radius:8px;border-top-right-radius:8px;border-bottom-right-radius:8px;border-bottom-left-radius:8px;padding-top:17px;padding-right:26px;padding-bottom:17px;padding-left:26px;}
.menu-toggle,button,.ast-button,.ast-custom-button,.button,input#submit,input[type="button"],input[type="submit"],input[type="reset"],#comments .submit,.search .search-submit,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button,body .wp-block-file .wp-block-file__button,.search .search-submit,.woocommerce-js a.button,.woocommerce button.button,.woocommerce .woocommerce-message a.button,.woocommerce #respond input#submit.alt,.woocommerce input.button.alt,.woocommerce input.button,.woocommerce input.button:disabled,.woocommerce input.button:disabled[disabled],.woocommerce input.button:disabled:hover,.woocommerce input.button:disabled[disabled]:hover,.woocommerce #respond input#submit,.woocommerce button.button.alt.disabled,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link,.wc-block-grid__product-onsale,[CLASS*="wc-block"] button,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping),.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout,.woocommerce button.button.alt.disabled.wc-variation-selection-needed,[CLASS*="wc-block"] .wc-block-components-button{border-style:solid;border-top-width:0px;border-right-width:0px;border-left-width:0px;border-bottom-width:0px;color:var(--ast-global-color-4);border-color:var(--ast-global-color-0);background-color:var(--ast-global-color-0);padding-top:17px;padding-right:26px;padding-bottom:17px;padding-left:26px;font-family:'Poppins',sans-serif;font-weight:500;font-size:14px;font-size:0.875rem;line-height:1em;text-transform:capitalize;letter-spacing:0.5px;border-top-left-radius:8px;border-top-right-radius:8px;border-bottom-right-radius:8px;border-bottom-left-radius:8px;}
button:focus,.menu-toggle:hover,button:hover,.ast-button:hover,.ast-custom-button:hover .button:hover,.ast-custom-button:hover ,input[type=reset]:hover,input[type=reset]:focus,input#submit:hover,input#submit:focus,input[type="button"]:hover,input[type="button"]:focus,input[type="submit"]:hover,input[type="submit"]:focus,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button:hover,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button:focus,body .wp-block-file .wp-block-file__button:hover,body .wp-block-file .wp-block-file__button:focus,.woocommerce-js a.button:hover,.woocommerce button.button:hover,.woocommerce .woocommerce-message a.button:hover,.woocommerce #respond input#submit:hover,.woocommerce #respond input#submit.alt:hover,.woocommerce input.button.alt:hover,.woocommerce input.button:hover,.woocommerce button.button.alt.disabled:hover,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link:hover,[CLASS*="wc-block"] button:hover,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping):hover,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout:hover,.woocommerce button.button.alt.disabled.wc-variation-selection-needed:hover,[CLASS*="wc-block"] .wc-block-components-button:hover,[CLASS*="wc-block"] .wc-block-components-button:focus{color:var(--ast-global-color-2);background-color:var(--ast-global-color-5);border-color:var(--ast-global-color-5);}
form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button.has-icon{padding-top:calc(17px - 3px);padding-right:calc(26px - 3px);padding-bottom:calc(17px - 3px);padding-left:calc(26px - 3px);}
````

`attendus/bouton-entete.css` :
````css
.ast-header-button-1 .ast-custom-button{color:var(--ast-global-color-4);background:var(--ast-global-color-0);}
.ast-header-button-1 .ast-custom-button:hover{color:rgba(255,255,255,0.75);background:var(--ast-global-color-0);}
````

`attendus/identite.css` :
````css
.ast-site-identity .site-description{color:var(--ast-global-color-3);}
.site-title{font-size:20px;font-size:1.25rem;display:block;}
header .custom-logo-link img{max-width:40px;width:40px;}
.astra-logo-svg{width:40px;}
.site-header .site-description{font-size:11px;font-size:0.6875rem;display:block;}
[@media (max-width:921px)] .site-header .site-description{display:block;}
[@media (max-width:544px)] .site-header .site-description{display:block;}
````

`attendus/pied.css` :
````css
.ast-footer-copyright{color:var(--ast-global-color-5);}
.ast-footer-copyright{font-size:15px;font-size:0.9375rem;}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"].footer-widget-area-inner{color:var(--ast-global-color-5);font-size:15px;font-size:0.9375rem;}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"].footer-widget-area-inner a:hover{color:var(--ast-global-color-5);}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] .widget-title,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] h1,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] .widget-area h1,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] h2,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] .widget-area h2,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] h3,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] .widget-area h3,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] h4,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] .widget-area h4,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] h5,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] .widget-area h5,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] h6,.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] .widget-area h6{color:var(--ast-global-color-4);}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"].footer-widget-area-inner{color:var(--ast-global-color-5);font-size:14px;font-size:0.875rem;}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"].footer-widget-area-inner a{color:var(--ast-global-color-5);}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] .widget-title,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] h1,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] .widget-area h1,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] h2,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] .widget-area h2,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] h3,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] .widget-area h3,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] h4,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] .widget-area h4,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] h5,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] .widget-area h5,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] h6,.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"] .widget-area h6{color:var(--ast-global-color-4);}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"].footer-widget-area-inner{color:var(--ast-global-color-5);font-size:14px;font-size:0.875rem;}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"].footer-widget-area-inner a{color:var(--ast-global-color-5);}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] .widget-title,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] h1,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] .widget-area h1,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] h2,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] .widget-area h2,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] h3,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] .widget-area h3,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] h4,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] .widget-area h4,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] h5,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] .widget-area h5,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] h6,.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"] .widget-area h6{color:var(--ast-global-color-4);}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"].footer-widget-area-inner{color:var(--ast-global-color-5);font-size:14px;font-size:0.875rem;}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"].footer-widget-area-inner a{color:var(--ast-global-color-5);}
.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] .widget-title,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] h1,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] .widget-area h1,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] h2,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] .widget-area h2,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] h3,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] .widget-area h3,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] h4,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] .widget-area h4,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] h5,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] .widget-area h5,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] h6,.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"] .widget-area h6{color:var(--ast-global-color-4);font-size:15px;font-size:0.9375rem;}
````

`attendus/sociaux.css` :
````css
.ast-footer-social-1-wrap .ast-builder-social-element,.ast-footer-social-1-wrap .social-show-label-true .ast-builder-social-element{margin-left:10px;margin-right:10px;}
.ast-footer-social-1-wrap .ast-builder-social-element svg{width:16px;height:16px;}
.ast-footer-social-1-wrap .ast-social-color-type-custom svg{fill:var(--ast-global-color-0);}
.ast-footer-social-1-wrap .ast-social-color-type-custom .ast-builder-social-element:hover{color:var(--ast-global-color-5);}
.ast-footer-social-1-wrap .ast-social-color-type-custom .ast-builder-social-element:hover svg{fill:var(--ast-global-color-5);}
.ast-footer-social-1-wrap .ast-social-color-type-custom .social-item-label{color:var(--ast-global-color-0);}
.ast-footer-social-1-wrap .ast-builder-social-element:hover .social-item-label{color:var(--ast-global-color-5);}
````

`attendus/base.css` :
````css

:root{--ast-post-nav-space:0;--ast-container-default-xlg-padding:2.5em;--ast-container-default-lg-padding:2.5em;--ast-container-default-slg-padding:2em;--ast-container-default-md-padding:2.5em;--ast-container-default-sm-padding:2.5em;--ast-container-default-xs-padding:2.4em;--ast-container-default-xxs-padding:1.8em;--ast-code-block-background:#ECEFF3;--ast-comment-inputs-background:#F9FAFB;--ast-normal-container-width:1200px;--ast-narrow-container-width:750px;--ast-blog-title-font-weight:600;--ast-blog-meta-weight:600;--ast-global-color-primary:var(--ast-global-color-4);--ast-global-color-secondary:var(--ast-global-color-5);--ast-global-color-alternate-background:var(--ast-global-color-6);--ast-global-color-subtle-background:var(--ast-global-color-7);--ast-bg-style-guide:#F8FAFC;--ast-shadow-style-guide:0px 0px 4px 0 #00000057;--ast-global-dark-bg-style:#fff;--ast-global-dark-lfs:#fbfbfb;--ast-widget-bg-color:#fafafa;--ast-wc-container-head-bg-color:#fbfbfb;--ast-title-layout-bg:#eeeeee;--ast-search-border-color:#e7e7e7;--ast-lifter-hover-bg:#e6e6e6;--ast-gallery-block-color:#000;--srfm-color-input-label:var(--ast-global-color-2);}html{font-size:100%;}a{color:var(--ast-global-color-0);}a:hover,a:focus{color:var(--ast-global-color-1);}body,button,input,select,textarea,.ast-button,.ast-custom-button{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Oxygen-Sans,Ubuntu,Cantarell,Helvetica Neue,sans-serif;font-weight:400;font-size:16px;font-size:1rem;line-height:var(--ast-body-line-height,1.65);}blockquote{color:var(--ast-global-color-3);}h1,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content h4,h5,.entry-content h5,h6,.entry-content h6,.site-title,.site-title a{font-weight:600;}.ast-site-identity .site-title a{color:var(--ast-global-color-2);}.site-title{font-size:26px;font-size:1.625rem;display:block;}.site-header .site-description{font-size:15px;font-size:0.9375rem;display:none;}.entry-title{font-size:20px;font-size:1.25rem;}.ast-blog-single-element.ast-taxonomy-container a{font-size:14px;font-size:0.875rem;}.ast-blog-meta-container{font-size:13px;font-size:0.8125rem;}.archive .ast-article-post .ast-article-inner,.blog .ast-article-post .ast-article-inner,.archive .ast-article-post .ast-article-inner:hover,.blog .ast-article-post .ast-article-inner:hover{border-top-left-radius:6px;border-top-right-radius:6px;border-bottom-right-radius:6px;border-bottom-left-radius:6px;overflow:hidden;}h1,.entry-content h1{font-size:36px;font-size:2.25rem;font-weight:600;line-height:1.4em;}h2,.entry-content h2{font-size:30px;font-size:1.875rem;font-weight:600;line-height:1.3em;}h3,.entry-content h3{font-size:24px;font-size:1.5rem;font-weight:600;line-height:1.3em;}h4,.entry-content h4{font-size:20px;font-size:1.25rem;line-height:1.2em;font-weight:600;}h5,.entry-content h5{font-size:18px;font-size:1.125rem;line-height:1.2em;font-weight:600;}h6,.entry-content h6{font-size:16px;font-size:1rem;line-height:1.25em;font-weight:600;}::selection{background-color:var(--ast-global-color-0);color:#ffffff;}body,h1,.entry-title a,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content h4,h5,.entry-content h5,h6,.entry-content h6{color:var(--ast-global-color-3);}.tagcloud a:hover,.tagcloud a:focus,.tagcloud a.current-item{color:#ffffff;border-color:var(--ast-global-color-0);background-color:var(--ast-global-color-0);}input:focus,input[type="text"]:focus,input[type="email"]:focus,input[type="url"]:focus,input[type="password"]:focus,input[type="reset"]:focus,input[type="search"]:focus,textarea:focus{border-color:var(--ast-global-color-0);}input[type="radio"]:checked,input[type=reset],input[type="checkbox"]:checked,input[type="checkbox"]:hover:checked,input[type="checkbox"]:focus:checked,input[type=range]::-webkit-slider-thumb{border-color:var(--ast-global-color-0);background-color:var(--ast-global-color-0);box-shadow:none;}.site-footer a:hover + .post-count,.site-footer a:focus + .post-count{background:var(--ast-global-color-0);border-color:var(--ast-global-color-0);}.single .nav-links .nav-previous,.single .nav-links .nav-next{color:var(--ast-global-color-0);}.entry-meta,.entry-meta *{line-height:1.45;color:var(--ast-global-color-0);font-weight:600;}.entry-meta a:not(.ast-button):hover,.entry-meta a:not(.ast-button):hover *,.entry-meta a:not(.ast-button):focus,.entry-meta a:not(.ast-button):focus *,.page-links > .page-link,.page-links .page-link:hover,.post-navigation a:hover{color:var(--ast-global-color-1);}#cat option,.secondary .calendar_wrap thead a,.secondary .calendar_wrap thead a:visited{color:var(--ast-global-color-0);}.secondary .calendar_wrap #today,.ast-progress-val span{background:var(--ast-global-color-0);}.secondary a:hover + .post-count,.secondary a:focus + .post-count{background:var(--ast-global-color-0);border-color:var(--ast-global-color-0);}.calendar_wrap #today > a{color:#ffffff;}.page-links .page-link,.single .post-navigation a{color:var(--ast-global-color-3);}.ast-search-menu-icon .search-form button.search-submit{padding:0 4px;}.ast-search-menu-icon form.search-form{padding-right:0;}.ast-search-menu-icon.slide-search input.search-field{width:0;}.ast-header-search .ast-search-menu-icon.ast-dropdown-active .search-form,.ast-header-search .ast-search-menu-icon.ast-dropdown-active .search-field:focus{transition:all 0.2s;}.search-form input.search-field:focus{outline:none;}.ast-search-menu-icon .search-form button.search-submit:focus,.ast-theme-transparent-header .ast-header-search .ast-dropdown-active .ast-icon,.ast-theme-transparent-header .ast-inline-search .search-field:focus .ast-icon{color:var(--ast-global-color-1);}.ast-header-search .slide-search .search-form{border:2px solid var(--ast-global-color-0);}.ast-header-search .slide-search .search-field{background-color:(--ast-global-dark-bg-style);}.ast-archive-title{color:var(--ast-global-color-2);}.widget-title{font-size:22px;font-size:1.375rem;color:var(--ast-global-color-2);}#secondary,#secondary button,#secondary input,#secondary select,#secondary textarea{font-size:16px;font-size:1rem;}.ast-single-post .entry-content a,.ast-comment-content a:not(.ast-comment-edit-reply-wrap a){text-decoration:underline;}.ast-single-post .elementor-widget-button .elementor-button,.ast-single-post .entry-content .uagb-tab a,.ast-single-post .entry-content .uagb-ifb-cta a,.ast-single-post .entry-content .uabb-module-content a,.ast-single-post .entry-content .uagb-post-grid a,.ast-single-post .entry-content .uagb-timeline a,.ast-single-post .entry-content .uagb-toc__wrap a,.ast-single-post .entry-content .uagb-taxomony-box a,.ast-single-post .entry-content .woocommerce a,.entry-content .wp-block-latest-posts > li > a,.ast-single-post .entry-content .wp-block-file__button,a.ast-post-filter-single,.ast-single-post .ast-comment-content .comment-reply-link,.ast-single-post .ast-comment-content .comment-edit-link{text-decoration:none;}.ast-search-menu-icon.slide-search a:focus-visible:focus-visible,.astra-search-icon:focus-visible,#close:focus-visible,a:focus-visible,.ast-menu-toggle:focus-visible,.site .skip-link:focus-visible,.wp-block-loginout input:focus-visible,.wp-block-search.wp-block-search__button-inside .wp-block-search__inside-wrapper,.ast-header-navigation-arrow:focus-visible,.woocommerce .wc-proceed-to-checkout > .checkout-button:focus-visible,.woocommerce .woocommerce-MyAccount-navigation ul li a:focus-visible,.ast-orders-table__row .ast-orders-table__cell:focus-visible,.woocommerce .woocommerce-order-details .order-again > .button:focus-visible,.woocommerce .woocommerce-message a.button.wc-forward:focus-visible,.woocommerce #minus_qty:focus-visible,.woocommerce #plus_qty:focus-visible,a#ast-apply-coupon:focus-visible,.woocommerce .woocommerce-info a:focus-visible,.woocommerce .astra-shop-summary-wrap a:focus-visible,.woocommerce a.wc-forward:focus-visible,#ast-apply-coupon:focus-visible,.woocommerce-js .woocommerce-mini-cart-item a.remove:focus-visible,#close:focus-visible,.button.search-submit:focus-visible,#search_submit:focus,.normal-search:focus-visible,.ast-header-account-wrap:focus-visible,.woocommerce .ast-on-card-button.ast-quick-view-trigger:focus{outline-style:dotted;outline-color:inherit;outline-width:thin;}input:focus,input[type="text"]:focus,input[type="email"]:focus,input[type="url"]:focus,input[type="password"]:focus,input[type="reset"]:focus,input[type="search"]:focus,input[type="number"]:focus,textarea:focus,.wp-block-search__input:focus,[data-section="section-header-mobile-trigger"] .ast-button-wrap .ast-mobile-menu-trigger-minimal:focus,.ast-mobile-popup-drawer.active .menu-toggle-close:focus,.woocommerce-ordering select.orderby:focus,#ast-scroll-top:focus,#coupon_code:focus,.woocommerce-page #comment:focus,.woocommerce #reviews #respond input#submit:focus,.woocommerce a.add_to_cart_button:focus,.woocommerce .button.single_add_to_cart_button:focus,.woocommerce .woocommerce-cart-form button:focus,.woocommerce .woocommerce-cart-form__cart-item .quantity .qty:focus,.woocommerce .woocommerce-billing-fields .woocommerce-billing-fields__field-wrapper .woocommerce-input-wrapper > .input-text:focus,.woocommerce #order_comments:focus,.woocommerce #place_order:focus,.woocommerce .woocommerce-address-fields .woocommerce-address-fields__field-wrapper .woocommerce-input-wrapper > .input-text:focus,.woocommerce .woocommerce-MyAccount-content form button:focus,.woocommerce .woocommerce-MyAccount-content .woocommerce-EditAccountForm .woocommerce-form-row .woocommerce-Input.input-text:focus,.woocommerce .ast-woocommerce-container .woocommerce-pagination ul.page-numbers li a:focus,body #content .woocommerce form .form-row .select2-container--default .select2-selection--single:focus,#ast-coupon-code:focus,.woocommerce.woocommerce-js .quantity input[type=number]:focus,.woocommerce-js .woocommerce-mini-cart-item .quantity input[type=number]:focus,.woocommerce p#ast-coupon-trigger:focus{border-style:dotted;border-color:inherit;border-width:thin;}input{outline:none;}.ast-logo-title-inline .site-logo-img{padding-right:1em;}body .ast-oembed-container *{position:absolute;top:0;width:100%;height:100%;left:0;}body .wp-block-embed-pocket-casts .ast-oembed-container *{position:unset;}.ast-single-post-featured-section + article {margin-top: 2em;}.site-content .ast-single-post-featured-section img {width: 100%;overflow: hidden;object-fit: cover;}.ast-separate-container .site-content .ast-single-post-featured-section + article {margin-top: -80px;z-index: 9;position: relative;border-radius: 4px;}@media (min-width: 922px) {.ast-no-sidebar .site-content .ast-article-image-container--wide {margin-left: -120px;margin-right: -120px;max-width: unset;width: unset;}.ast-left-sidebar .site-content .ast-article-image-container--wide,.ast-right-sidebar .site-content .ast-article-image-container--wide {margin-left: -10px;margin-right: -10px;}.site-content .ast-article-image-container--full {margin-left: calc( -50vw + 50%);margin-right: calc( -50vw + 50%);max-width: 100vw;width: 100vw;}.ast-left-sidebar .site-content .ast-article-image-container--full,.ast-right-sidebar .site-content .ast-article-image-container--full {margin-left: -10px;margin-right: -10px;max-width: inherit;width: auto;}}.site > .ast-single-related-posts-container {margin-top: 0;}@media (min-width: 922px) {.ast-desktop .ast-container--narrow {max-width: var(--ast-narrow-container-width);margin: 0 auto;}}#secondary {margin: 4em 0 2.5em;word-break: break-word;line-height: 2;}#secondary li {margin-bottom: 0.75em;}#secondary li:last-child {margin-bottom: 0;}@media (max-width: 768px) {.js_active .ast-plain-container.ast-single-post #secondary {margin-top: 1.5em;}}.ast-separate-container.ast-two-container #secondary .widget {background-color: #fff;padding: 2em;margin-bottom: 2em;}@media (min-width: 993px) {.ast-left-sidebar #secondary {padding-right: 60px;}.ast-right-sidebar #secondary {padding-left: 60px;}}@media (max-width: 993px) {.ast-right-sidebar #secondary {padding-left: 30px;}.ast-left-sidebar #secondary {padding-right: 30px;}}@media (min-width: 993px) {.ast-page-builder-template.ast-left-sidebar #secondary {padding-left: 60px;}.ast-page-builder-template.ast-right-sidebar #secondary {padding-right: 60px;}}@media (max-width: 993px) {.ast-page-builder-template.ast-right-sidebar #secondary {padding-right: 30px;}.ast-page-builder-template.ast-left-sidebar #secondary {padding-left: 30px;}}input[type="text"],input[type="number"],input[type="email"],input[type="url"],input[type="password"],input[type="search"],input[type=reset],input[type=tel],input[type=date],select,textarea{font-size:16px;font-style:normal;font-weight:400;line-height:24px;width:100%;padding:12px 16px;border-radius:4px;box-shadow:0px 1px 2px 0px rgba(0,0,0,0.05);color:var(--ast-form-input-text,#475569);}input[type="text"],input[type="number"],input[type="email"],input[type="url"],input[type="password"],input[type="search"],input[type=reset],input[type=tel],input[type=date],select{height:40px;}input[type="date"]{border-width:1px;border-style:solid;border-color:var(--ast-border-color);background:var( --ast-global-color-secondary,--ast-global-color-5 );}input[type="text"]:focus,input[type="number"]:focus,input[type="email"]:focus,input[type="url"]:focus,input[type="password"]:focus,input[type="search"]:focus,input[type=reset]:focus,input[type="tel"]:focus,input[type="date"]:focus,select:focus,textarea:focus{border-color:#046BD2;box-shadow:none;outline:none;color:var(--ast-form-input-focus-text,#475569);}label,legend{color:#111827;font-size:14px;font-style:normal;font-weight:500;line-height:20px;}select{padding:6px 10px;}fieldset{padding:30px;border-radius:4px;}button,.ast-button,.button,input[type="button"],input[type="reset"],input[type="submit"]{border-radius:4px;box-shadow:0px 1px 2px 0px rgba(0,0,0,0.05);}:root{--ast-comment-inputs-background:#FFF;}::placeholder{color:var(--ast-form-field-color,#9CA3AF);}::-ms-input-placeholder{color:var(--ast-form-field-color,#9CA3AF);}@media (max-width:921.9px){#ast-desktop-header{display:none;}}@media (min-width:922px){#ast-mobile-header{display:none;}}.wp-block-buttons.aligncenter{justify-content:center;}.wp-block-button.is-style-outline .wp-block-button__link{border-color:var(--ast-global-color-0);}div.wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color),div.wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color){color:var(--ast-global-color-0);}.wp-block-button.is-style-outline .wp-block-button__link:hover,.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link:focus,.wp-block-buttons .wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color):hover,.wp-block-buttons .wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color):hover{color:#ffffff;background-color:var(--ast-global-color-1);border-color:var(--ast-global-color-1);}.post-page-numbers.current .page-link,.ast-pagination .page-numbers.current{color:#ffffff;border-color:var(--ast-global-color-0);background-color:var(--ast-global-color-0);}.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link.wp-element-button,.ast-outline-button,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button{border-color:var(--ast-global-color-0);border-top-width:2px;border-right-width:2px;border-bottom-width:2px;border-left-width:2px;font-family:inherit;font-weight:500;font-size:16px;font-size:1rem;line-height:1em;padding-top:13px;padding-right:30px;padding-bottom:13px;padding-left:30px;}.wp-block-buttons .wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color),.wp-block-buttons .wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color),.ast-outline-button{color:var(--ast-global-color-0);}.wp-block-button.is-style-outline .wp-block-button__link:hover,.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link:focus,.wp-block-buttons .wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color):hover,.wp-block-buttons .wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color):hover,.ast-outline-button:hover,.ast-outline-button:focus,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button:hover,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button:focus{color:#ffffff;background-color:var(--ast-global-color-1);border-color:var(--ast-global-color-1);}.ast-single-post .entry-content a.ast-outline-button,.ast-single-post .entry-content .is-style-outline>.wp-block-button__link{text-decoration:none;}.wp-block-button .wp-block-button__link.wp-element-button.is-style-outline:not(.has-background),.wp-block-button.is-style-outline>.wp-block-button__link.wp-element-button:not(.has-background),.ast-outline-button{background-color:transparent;}.uagb-buttons-repeater.ast-outline-button{border-radius:9999px;}@media (max-width:921px){.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link.wp-element-button,.ast-outline-button,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button{padding-top:12px;padding-right:28px;padding-bottom:12px;padding-left:28px;}}@media (max-width:544px){.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link.wp-element-button,.ast-outline-button,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button{padding-top:10px;padding-right:24px;padding-bottom:10px;padding-left:24px;}}.entry-content[data-ast-blocks-layout] > figure{margin-bottom:1em;}h1.widget-title{font-weight:600;}h2.widget-title{font-weight:600;}h3.widget-title{font-weight:600;}#page{display:flex;flex-direction:column;min-height:100vh;}.ast-404-layout-1 h1.page-title{color:var(--ast-global-color-2);}.single .post-navigation a{line-height:1em;height:inherit;}.error-404 .page-sub-title{font-size:1.5rem;font-weight:inherit;}.search .site-content .content-area .search-form{margin-bottom:0;}#page .site-content{flex-grow:1;}.widget{margin-bottom:1.25em;}#secondary li{line-height:1.5em;}#secondary .wp-block-group h2{margin-bottom:0.7em;}#secondary h2{font-size:1.7rem;}.ast-separate-container .ast-article-post,.ast-separate-container .ast-article-single,.ast-separate-container .comment-respond{padding:3em;}.ast-separate-container .ast-article-single .ast-article-single{padding:0;}.ast-article-single .wp-block-post-template-is-layout-grid{padding-left:0;}.ast-separate-container .comments-title,.ast-narrow-container .comments-title{padding:1.5em 2em;}.ast-page-builder-template .comment-form-textarea,.ast-comment-formwrap .ast-grid-common-col{padding:0;}.ast-comment-formwrap{padding:0;display:inline-flex;column-gap:20px;width:100%;margin-left:0;margin-right:0;}.comments-area textarea#comment:focus,.comments-area textarea#comment:active,.comments-area .ast-comment-formwrap input[type="text"]:focus,.comments-area .ast-comment-formwrap input[type="text"]:active {box-shadow:none;outline:none;}.archive.ast-page-builder-template .entry-header{margin-top:2em;}.ast-page-builder-template .ast-comment-formwrap{width:100%;}.entry-title{margin-bottom:0.6em;}.ast-archive-description p{font-size:inherit;font-weight:inherit;line-height:inherit;}.ast-separate-container .ast-comment-list li.depth-1,.hentry{margin-bottom:1.5em;}.site-content section.ast-archive-description{margin-bottom:2em;}@media (min-width:921px){.ast-left-sidebar.ast-page-builder-template #secondary,.archive.ast-right-sidebar.ast-page-builder-template .site-main{padding-left:20px;padding-right:20px;}}@media (max-width:544px){.ast-comment-formwrap.ast-row{column-gap:10px;display:inline-block;}#ast-commentform .ast-grid-common-col{position:relative;width:100%;}}@media (min-width:1201px){.ast-separate-container .ast-article-post,.ast-separate-container .ast-article-single,.ast-separate-container .ast-author-box,.ast-separate-container .ast-404-layout-1,.ast-separate-container .no-results{padding:3em;}}@media (max-width:921px){.ast-separate-container #primary,.ast-separate-container #secondary{padding:1.5em 0;}#primary,#secondary{padding:1.5em 0;margin:0;}.ast-left-sidebar #content > .ast-container{display:flex;flex-direction:column-reverse;width:100%;}}@media (max-width:921px){#secondary.secondary{padding-top:0;}.ast-separate-container.ast-right-sidebar #secondary{padding-left:1em;padding-right:1em;}.ast-separate-container.ast-two-container #secondary{padding-left:0;padding-right:0;}.ast-page-builder-template .entry-header #secondary,.ast-page-builder-template #secondary{margin-top:1.5em;}}@media (max-width:921px){.ast-right-sidebar #primary{padding-right:0;}.ast-page-builder-template.ast-left-sidebar #secondary,.ast-page-builder-template.ast-right-sidebar #secondary{padding-right:20px;padding-left:20px;}.ast-right-sidebar #secondary,.ast-left-sidebar #primary{padding-left:0;}.ast-left-sidebar #secondary{padding-right:0;}}@media (min-width:922px){.ast-separate-container.ast-right-sidebar #primary,.ast-separate-container.ast-left-sidebar #primary{border:0;}.search-no-results.ast-separate-container #primary{margin-bottom:4em;}}@media (min-width:922px){.ast-right-sidebar #primary{border-right:1px solid var(--ast-border-color);}.ast-left-sidebar #primary{border-left:1px solid var(--ast-border-color);}.ast-right-sidebar #secondary{border-left:1px solid var(--ast-border-color);margin-left:-1px;}.ast-left-sidebar #secondary{border-right:1px solid var(--ast-border-color);margin-right:-1px;}.ast-separate-container.ast-two-container.ast-right-sidebar #secondary{padding-left:30px;padding-right:0;}.ast-separate-container.ast-two-container.ast-left-sidebar #secondary{padding-right:30px;padding-left:0;}.ast-separate-container.ast-right-sidebar #secondary,.ast-separate-container.ast-left-sidebar #secondary{border:0;margin-left:auto;margin-right:auto;}.ast-separate-container.ast-two-container #secondary .widget:last-child{margin-bottom:0;}}.wp-block-button .wp-block-button__link{color:#ffffff;}.wp-block-button .wp-block-button__link:hover,.wp-block-button .wp-block-button__link:focus{color:#ffffff;background-color:var(--ast-global-color-1);border-color:var(--ast-global-color-1);}.wp-block-button .wp-block-button__link,.wp-block-search .wp-block-search__button,body .wp-block-file .wp-block-file__button{border-color:var(--ast-global-color-0);background-color:var(--ast-global-color-0);color:#ffffff;font-family:inherit;font-weight:500;line-height:1em;font-size:16px;font-size:1rem;padding-top:15px;padding-right:30px;padding-bottom:15px;padding-left:30px;}.ast-single-post .entry-content .wp-block-button .wp-block-button__link,.ast-single-post .entry-content .wp-block-search .wp-block-search__button,body .entry-content .wp-block-file .wp-block-file__button{text-decoration:none;}@media (max-width:921px){.wp-block-button .wp-block-button__link,.wp-block-search .wp-block-search__button,body .wp-block-file .wp-block-file__button{padding-top:14px;padding-right:28px;padding-bottom:14px;padding-left:28px;}}@media (max-width:544px){.wp-block-button .wp-block-button__link,.wp-block-search .wp-block-search__button,body .wp-block-file .wp-block-file__button{padding-top:12px;padding-right:24px;padding-bottom:12px;padding-left:24px;}}.menu-toggle,button,.ast-button,.ast-custom-button,.button,input#submit,input[type="button"],input[type="submit"],input[type="reset"],#comments .submit,.search .search-submit,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button,body .wp-block-file .wp-block-file__button,.search .search-submit,.woocommerce-js a.button,.woocommerce button.button,.woocommerce .woocommerce-message a.button,.woocommerce #respond input#submit.alt,.woocommerce input.button.alt,.woocommerce input.button,.woocommerce input.button:disabled,.woocommerce input.button:disabled[disabled],.woocommerce input.button:disabled:hover,.woocommerce input.button:disabled[disabled]:hover,.woocommerce #respond input#submit,.woocommerce button.button.alt.disabled,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link,.wc-block-grid__product-onsale,[CLASS*="wc-block"] button,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping),.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout,.woocommerce button.button.alt.disabled.wc-variation-selection-needed,[CLASS*="wc-block"] .wc-block-components-button{border-style:solid;border-top-width:0;border-right-width:0;border-left-width:0;border-bottom-width:0;color:#ffffff;border-color:var(--ast-global-color-0);background-color:var(--ast-global-color-0);padding-top:15px;padding-right:30px;padding-bottom:15px;padding-left:30px;font-family:inherit;font-weight:500;font-size:16px;font-size:1rem;line-height:1em;}button:focus,.menu-toggle:hover,button:hover,.ast-button:hover,.ast-custom-button:hover .button:hover,.ast-custom-button:hover ,input[type=reset]:hover,input[type=reset]:focus,input#submit:hover,input#submit:focus,input[type="button"]:hover,input[type="button"]:focus,input[type="submit"]:hover,input[type="submit"]:focus,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button:hover,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button:focus,body .wp-block-file .wp-block-file__button:hover,body .wp-block-file .wp-block-file__button:focus,.woocommerce-js a.button:hover,.woocommerce button.button:hover,.woocommerce .woocommerce-message a.button:hover,.woocommerce #respond input#submit:hover,.woocommerce #respond input#submit.alt:hover,.woocommerce input.button.alt:hover,.woocommerce input.button:hover,.woocommerce button.button.alt.disabled:hover,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link:hover,[CLASS*="wc-block"] button:hover,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping):hover,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout:hover,.woocommerce button.button.alt.disabled.wc-variation-selection-needed:hover,[CLASS*="wc-block"] .wc-block-components-button:hover,[CLASS*="wc-block"] .wc-block-components-button:focus{color:#ffffff;background-color:var(--ast-global-color-1);border-color:var(--ast-global-color-1);}form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button.has-icon{padding-top:calc(15px - 3px);padding-right:calc(30px - 3px);padding-bottom:calc(15px - 3px);padding-left:calc(30px - 3px);}@media (max-width:921px){.menu-toggle,button,.ast-button,.ast-custom-button,.button,input#submit,input[type="button"],input[type="submit"],input[type="reset"],#comments .submit,.search .search-submit,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button,body .wp-block-file .wp-block-file__button,.search .search-submit,.woocommerce-js a.button,.woocommerce button.button,.woocommerce .woocommerce-message a.button,.woocommerce #respond input#submit.alt,.woocommerce input.button.alt,.woocommerce input.button,.woocommerce input.button:disabled,.woocommerce input.button:disabled[disabled],.woocommerce input.button:disabled:hover,.woocommerce input.button:disabled[disabled]:hover,.woocommerce #respond input#submit,.woocommerce button.button.alt.disabled,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link,.wc-block-grid__product-onsale,[CLASS*="wc-block"] button,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping),.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout,.woocommerce button.button.alt.disabled.wc-variation-selection-needed,[CLASS*="wc-block"] .wc-block-components-button{padding-top:14px;padding-right:28px;padding-bottom:14px;padding-left:28px;}}@media (max-width:544px){.menu-toggle,button,.ast-button,.ast-custom-button,.button,input#submit,input[type="button"],input[type="submit"],input[type="reset"],#comments .submit,.search .search-submit,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button,body .wp-block-file .wp-block-file__button,.search .search-submit,.woocommerce-js a.button,.woocommerce button.button,.woocommerce .woocommerce-message a.button,.woocommerce #respond input#submit.alt,.woocommerce input.button.alt,.woocommerce input.button,.woocommerce input.button:disabled,.woocommerce input.button:disabled[disabled],.woocommerce input.button:disabled:hover,.woocommerce input.button:disabled[disabled]:hover,.woocommerce #respond input#submit,.woocommerce button.button.alt.disabled,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link,.wc-block-grid__product-onsale,[CLASS*="wc-block"] button,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping),.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout,.woocommerce button.button.alt.disabled.wc-variation-selection-needed,[CLASS*="wc-block"] .wc-block-components-button{padding-top:12px;padding-right:24px;padding-bottom:12px;padding-left:24px;}}@media (max-width:921px){.ast-mobile-header-stack .main-header-bar .ast-search-menu-icon{display:inline-block;}.ast-header-break-point.ast-header-custom-item-outside .ast-mobile-header-stack .main-header-bar .ast-search-icon{margin:0;}.ast-comment-avatar-wrap img{max-width:2.5em;}.ast-comment-meta{padding:0 1.8888em 1.3333em;}}@media (min-width:544px){.ast-container{max-width:100%;}}@media (max-width:544px){.ast-separate-container .ast-article-post,.ast-separate-container .ast-article-single,.ast-separate-container .comments-title,.ast-separate-container .ast-archive-description{padding:1.5em 1em;}.ast-separate-container #content .ast-container{padding-left:0.54em;padding-right:0.54em;}.ast-separate-container .ast-comment-list .bypostauthor{padding:.5em;}.ast-search-menu-icon.ast-dropdown-active .search-field{width:170px;}.ast-separate-container #secondary{padding-top:0;}.ast-separate-container.ast-two-container #secondary .widget{margin-bottom:1.5em;padding-left:1em;padding-right:1em;}} #ast-mobile-header .ast-site-header-cart-li a{pointer-events:none;}.ast-separate-container{background-color:var(--ast-global-color-5);}@media (max-width:921px){.site-title{display:block;}.site-header .site-description{display:none;}h1,.entry-content h1{font-size:30px;font-size:1.875rem;}h2,.entry-content h2{font-size:25px;font-size:1.5625rem;}h3,.entry-content h3{font-size:20px;font-size:1.25rem;}}@media (max-width:544px){.site-title{display:block;}.site-header .site-description{display:none;}h1,.entry-content h1{font-size:30px;font-size:1.875rem;}h2,.entry-content h2{font-size:25px;font-size:1.5625rem;}h3,.entry-content h3{font-size:20px;font-size:1.25rem;}}@media (max-width:921px){html{font-size:91.2%;}}@media (max-width:544px){html{font-size:91.2%;}}@media (min-width:922px){.ast-container{max-width:1240px;}}@media (min-width:922px){.site-content .ast-container{display:flex;}}@media (max-width:921px){.site-content .ast-container{flex-direction:column;}}.ast-blog-layout-4-grid .ast-article-post{width:33.33%;margin-bottom:2em;border-bottom:0;background-color:transparent;}.ast-blog-layout-4-grid .ast-article-inner .wp-post-image{width:100%;}.ast-article-inner{padding:1.5em;}.ast-blog-layout-4-grid .ast-row{display:flex;flex-wrap:wrap;flex-flow:row wrap;align-items:stretch;}.ast-separate-container .ast-blog-layout-4-grid .ast-article-post{padding:0 1em 0;}.ast-separate-container.ast-desktop .ast-blog-layout-4-grid .ast-row{margin-left:-1em;margin-right:-1em;}.ast-blog-layout-4-grid .ast-article-inner{box-shadow:0px 6px 15px -2px rgba(16,24,40,0.05);}.ast-separate-container .ast-blog-layout-4-grid .ast-article-inner,.ast-plain-container .ast-blog-layout-4-grid .ast-article-inner{height:100%;}.ast-row .blog-layout-4 .post-content,.blog-layout-4 .post-thumb{padding-left:0;padding-right:0;}.ast-article-post.remove-featured-img-padding .blog-layout-4 .post-content .ast-blog-featured-section:first-child .post-thumb-img-content{margin-top:-1.5em;}.ast-article-post.remove-featured-img-padding .blog-layout-4 .post-content .ast-blog-featured-section .post-thumb-img-content{margin-left:-1.5em;margin-right:-1.5em;}@media (max-width:921px){.ast-blog-layout-4-grid .ast-article-post{width:100%;}}.ast-blog-layout-6-grid .ast-blog-featured-section:before { content: ""; }.ast-article-post .post-thumb-img-content img{aspect-ratio:16/9;width:100%;}.ast-article-post .post-thumb-img-content{overflow:hidden;}.ast-article-post .post-thumb-img-content img{transform:scale(1);transition:transform .5s ease;}.ast-article-post:hover .post-thumb-img-content img{transform:scale(1.1);}.cat-links.badge a,.tags-links.badge a {padding: 4px 8px;border-radius: 3px;font-weight: 400;}.cat-links.underline a,.tags-links.underline a{text-decoration: underline;}@media (min-width:922px){.main-header-menu .sub-menu .menu-item.ast-left-align-sub-menu:hover > .sub-menu,.main-header-menu .sub-menu .menu-item.ast-left-align-sub-menu.focus > .sub-menu{margin-left:-0px;}}.entry-content li > p{margin-bottom:0;}.site .comments-area{padding-bottom:2em;margin-top:2em;}.wp-block-file {display: flex;align-items: center;flex-wrap: wrap;justify-content: space-between;}.wp-block-pullquote {border: none;}.wp-block-pullquote blockquote::before {content: "\201D";font-family: "Helvetica",sans-serif;display: flex;transform: rotate( 180deg );font-size: 6rem;font-style: normal;line-height: 1;font-weight: bold;align-items: center;justify-content: center;}.has-text-align-right > blockquote::before {justify-content: flex-start;}.has-text-align-left > blockquote::before {justify-content: flex-end;}figure.wp-block-pullquote.is-style-solid-color blockquote {max-width: 100%;text-align: inherit;}:root {--wp--custom--ast-default-block-top-padding: 3em;--wp--custom--ast-default-block-right-padding: 3em;--wp--custom--ast-default-block-bottom-padding: 3em;--wp--custom--ast-default-block-left-padding: 3em;--wp--custom--ast-container-width: 1200px;--wp--custom--ast-content-width-size: 1200px;--wp--custom--ast-wide-width-size: calc(1200px + var(--wp--custom--ast-default-block-left-padding) + var(--wp--custom--ast-default-block-right-padding));}.ast-narrow-container {--wp--custom--ast-content-width-size: 750px;--wp--custom--ast-wide-width-size: 750px;}@media(max-width: 921px) {:root {--wp--custom--ast-default-block-top-padding: 3em;--wp--custom--ast-default-block-right-padding: 2em;--wp--custom--ast-default-block-bottom-padding: 3em;--wp--custom--ast-default-block-left-padding: 2em;}}@media(max-width: 544px) {:root {--wp--custom--ast-default-block-top-padding: 3em;--wp--custom--ast-default-block-right-padding: 1.5em;--wp--custom--ast-default-block-bottom-padding: 3em;--wp--custom--ast-default-block-left-padding: 1.5em;}}.entry-content > .wp-block-group,.entry-content > .wp-block-cover,.entry-content > .wp-block-columns {padding-top: var(--wp--custom--ast-default-block-top-padding);padding-right: var(--wp--custom--ast-default-block-right-padding);padding-bottom: var(--wp--custom--ast-default-block-bottom-padding);padding-left: var(--wp--custom--ast-default-block-left-padding);}.ast-plain-container.ast-no-sidebar .entry-content > .alignfull,.ast-page-builder-template .ast-no-sidebar .entry-content > .alignfull {margin-left: calc( -50vw + 50%);margin-right: calc( -50vw + 50%);max-width: 100vw;width: 100vw;}.ast-plain-container.ast-no-sidebar .entry-content .alignfull .alignfull,.ast-page-builder-template.ast-no-sidebar .entry-content .alignfull .alignfull,.ast-plain-container.ast-no-sidebar .entry-content .alignfull .alignwide,.ast-page-builder-template.ast-no-sidebar .entry-content .alignfull .alignwide,.ast-plain-container.ast-no-sidebar .entry-content .alignwide .alignfull,.ast-page-builder-template.ast-no-sidebar .entry-content .alignwide .alignfull,.ast-plain-container.ast-no-sidebar .entry-content .alignwide .alignwide,.ast-page-builder-template.ast-no-sidebar .entry-content .alignwide .alignwide,.ast-plain-container.ast-no-sidebar .entry-content .wp-block-column .alignfull,.ast-page-builder-template.ast-no-sidebar .entry-content .wp-block-column .alignfull,.ast-plain-container.ast-no-sidebar .entry-content .wp-block-column .alignwide,.ast-page-builder-template.ast-no-sidebar .entry-content .wp-block-column .alignwide {margin-left: auto;margin-right: auto;width: 100%;}[data-ast-blocks-layout] .wp-block-separator:not(.is-style-dots) {height: 0;}[data-ast-blocks-layout] .wp-block-separator {margin: 20px auto;}[data-ast-blocks-layout] .wp-block-separator:not(.is-style-wide):not(.is-style-dots) {max-width: 100px;}[data-ast-blocks-layout] .wp-block-separator.has-background {padding: 0;}.entry-content[data-ast-blocks-layout] > * {max-width: var(--wp--custom--ast-content-width-size);margin-left: auto;margin-right: auto;}.entry-content[data-ast-blocks-layout] > .alignwide {max-width: var(--wp--custom--ast-wide-width-size);}.entry-content[data-ast-blocks-layout] .alignfull {max-width: none;}.entry-content .wp-block-columns {margin-bottom: 0;}blockquote {margin: 1.5em;border-color: rgba(0,0,0,0.05);}.wp-block-quote:not(.has-text-align-right):not(.has-text-align-center) {border-left: 5px solid rgba(0,0,0,0.05);}.has-text-align-right > blockquote,blockquote.has-text-align-right {border-right: 5px solid rgba(0,0,0,0.05);}.has-text-align-left > blockquote,blockquote.has-text-align-left {border-left: 5px solid rgba(0,0,0,0.05);}.wp-block-site-tagline,.wp-block-latest-posts .read-more {margin-top: 15px;}.wp-block-loginout p label {display: block;}.wp-block-loginout p:not(.login-remember):not(.login-submit) input {width: 100%;}.wp-block-loginout input:focus {border-color: transparent;}.wp-block-loginout input:focus {outline: thin dotted;}.entry-content .wp-block-media-text .wp-block-media-text__content {padding: 0 0 0 8%;}.entry-content .wp-block-media-text.has-media-on-the-right .wp-block-media-text__content {padding: 0 8% 0 0;}.entry-content .wp-block-media-text.has-background .wp-block-media-text__content {padding: 8%;}.entry-content .wp-block-cover:not([class*="background-color"]):not(.has-text-color.has-link-color) .wp-block-cover__inner-container,.entry-content .wp-block-cover:not([class*="background-color"]) .wp-block-cover-image-text,.entry-content .wp-block-cover:not([class*="background-color"]) .wp-block-cover-text,.entry-content .wp-block-cover-image:not([class*="background-color"]) .wp-block-cover__inner-container,.entry-content .wp-block-cover-image:not([class*="background-color"]) .wp-block-cover-image-text,.entry-content .wp-block-cover-image:not([class*="background-color"]) .wp-block-cover-text {color: var(--ast-global-color-primary,var(--ast-global-color-5));}.wp-block-loginout .login-remember input {width: 1.1rem;height: 1.1rem;margin: 0 5px 4px 0;vertical-align: middle;}.wp-block-latest-posts > li > *:first-child,.wp-block-latest-posts:not(.is-grid) > li:first-child {margin-top: 0;}.entry-content > .wp-block-buttons,.entry-content > .wp-block-uagb-buttons {margin-bottom: 1.5em;}.wp-block-search__inside-wrapper .wp-block-search__input {padding: 0 10px;color: var(--ast-global-color-3);background: var(--ast-global-color-primary,var(--ast-global-color-5));border-color: var(--ast-border-color);}.wp-block-latest-posts .read-more {margin-bottom: 1.5em;}.wp-block-search__no-button .wp-block-search__inside-wrapper .wp-block-search__input {padding-top: 5px;padding-bottom: 5px;}.wp-block-latest-posts .wp-block-latest-posts__post-date,.wp-block-latest-posts .wp-block-latest-posts__post-author {font-size: 1rem;}.wp-block-latest-posts > li > *,.wp-block-latest-posts:not(.is-grid) > li {margin-top: 12px;margin-bottom: 12px;}.ast-page-builder-template .entry-content[data-ast-blocks-layout] > *,.ast-page-builder-template .entry-content[data-ast-blocks-layout] > .alignfull:not(.wp-block-group):not(.uagb-is-root-container) > * {max-width: none;}.ast-page-builder-template .entry-content[data-ast-blocks-layout] > .alignwide:not(.uagb-is-root-container) > * {max-width: var(--wp--custom--ast-wide-width-size);}.ast-page-builder-template .entry-content[data-ast-blocks-layout] > .inherit-container-width > *,.ast-page-builder-template .entry-content[data-ast-blocks-layout] > *:not(.wp-block-group):not(.uagb-is-root-container) > *,.entry-content[data-ast-blocks-layout] > .wp-block-cover .wp-block-cover__inner-container {max-width: none ;margin-left: auto;margin-right: auto;}.entry-content[data-ast-blocks-layout] .wp-block-cover:not(.alignleft):not(.alignright) {width: auto;}@media(max-width: 1200px) {.ast-separate-container .entry-content > .alignfull,.ast-separate-container .entry-content[data-ast-blocks-layout] > .alignwide,.ast-plain-container .entry-content[data-ast-blocks-layout] > .alignwide,.ast-plain-container .entry-content .alignfull {margin-left: calc(-1 * min(var(--ast-container-default-xlg-padding),20px)) ;margin-right: calc(-1 * min(var(--ast-container-default-xlg-padding),20px));}}@media(min-width: 1201px) {.ast-separate-container .entry-content > .alignfull {margin-left: calc(-1 * var(--ast-container-default-xlg-padding) );margin-right: calc(-1 * var(--ast-container-default-xlg-padding) );}.ast-separate-container .entry-content[data-ast-blocks-layout] > .alignwide,.ast-plain-container .entry-content[data-ast-blocks-layout] > .alignwide {margin-left: auto;margin-right: auto;}}@media(min-width: 921px) {.ast-separate-container .entry-content .wp-block-group.alignwide:not(.inherit-container-width) > :where(:not(.alignleft):not(.alignright)),.ast-plain-container .entry-content .wp-block-group.alignwide:not(.inherit-container-width) > :where(:not(.alignleft):not(.alignright)) {max-width: calc( var(--wp--custom--ast-content-width-size) + 80px );}.ast-plain-container.ast-right-sidebar .entry-content[data-ast-blocks-layout] .alignfull,.ast-plain-container.ast-left-sidebar .entry-content[data-ast-blocks-layout] .alignfull {margin-left: -60px;margin-right: -60px;}}@media(min-width: 544px) {.entry-content > .alignleft {margin-right: 20px;}.entry-content > .alignright {margin-left: 20px;}}@media (max-width:544px){.wp-block-columns .wp-block-column:not(:last-child){margin-bottom:20px;}.wp-block-latest-posts{margin:0;}}@media( max-width: 600px ) {.entry-content .wp-block-media-text .wp-block-media-text__content,.entry-content .wp-block-media-text.has-media-on-the-right .wp-block-media-text__content {padding: 8% 0 0;}.entry-content .wp-block-media-text.has-background .wp-block-media-text__content {padding: 8%;}}.ast-page-builder-template .entry-header {padding-left: 0;}.ast-narrow-container .site-content .wp-block-uagb-image--align-full .wp-block-uagb-image__figure {max-width: 100%;margin-left: auto;margin-right: auto;}.entry-content ul,.entry-content ol {padding: revert;margin: revert;padding-left: 20px;}:root .has-ast-global-color-0-color{color:var(--ast-global-color-0);}:root .has-ast-global-color-0-background-color{background-color:var(--ast-global-color-0);}:root .wp-block-button .has-ast-global-color-0-color{color:var(--ast-global-color-0);}:root .wp-block-button .has-ast-global-color-0-background-color{background-color:var(--ast-global-color-0);}:root .has-ast-global-color-1-color{color:var(--ast-global-color-1);}:root .has-ast-global-color-1-background-color{background-color:var(--ast-global-color-1);}:root .wp-block-button .has-ast-global-color-1-color{color:var(--ast-global-color-1);}:root .wp-block-button .has-ast-global-color-1-background-color{background-color:var(--ast-global-color-1);}:root .has-ast-global-color-2-color{color:var(--ast-global-color-2);}:root .has-ast-global-color-2-background-color{background-color:var(--ast-global-color-2);}:root .wp-block-button .has-ast-global-color-2-color{color:var(--ast-global-color-2);}:root .wp-block-button .has-ast-global-color-2-background-color{background-color:var(--ast-global-color-2);}:root .has-ast-global-color-3-color{color:var(--ast-global-color-3);}:root .has-ast-global-color-3-background-color{background-color:var(--ast-global-color-3);}:root .wp-block-button .has-ast-global-color-3-color{color:var(--ast-global-color-3);}:root .wp-block-button .has-ast-global-color-3-background-color{background-color:var(--ast-global-color-3);}:root .has-ast-global-color-4-color{color:var(--ast-global-color-4);}:root .has-ast-global-color-4-background-color{background-color:var(--ast-global-color-4);}:root .wp-block-button .has-ast-global-color-4-color{color:var(--ast-global-color-4);}:root .wp-block-button .has-ast-global-color-4-background-color{background-color:var(--ast-global-color-4);}:root .has-ast-global-color-5-color{color:var(--ast-global-color-5);}:root .has-ast-global-color-5-background-color{background-color:var(--ast-global-color-5);}:root .wp-block-button .has-ast-global-color-5-color{color:var(--ast-global-color-5);}:root .wp-block-button .has-ast-global-color-5-background-color{background-color:var(--ast-global-color-5);}:root .has-ast-global-color-6-color{color:var(--ast-global-color-6);}:root .has-ast-global-color-6-background-color{background-color:var(--ast-global-color-6);}:root .wp-block-button .has-ast-global-color-6-color{color:var(--ast-global-color-6);}:root .wp-block-button .has-ast-global-color-6-background-color{background-color:var(--ast-global-color-6);}:root .has-ast-global-color-7-color{color:var(--ast-global-color-7);}:root .has-ast-global-color-7-background-color{background-color:var(--ast-global-color-7);}:root .wp-block-button .has-ast-global-color-7-color{color:var(--ast-global-color-7);}:root .wp-block-button .has-ast-global-color-7-background-color{background-color:var(--ast-global-color-7);}:root .has-ast-global-color-8-color{color:var(--ast-global-color-8);}:root .has-ast-global-color-8-background-color{background-color:var(--ast-global-color-8);}:root .wp-block-button .has-ast-global-color-8-color{color:var(--ast-global-color-8);}:root .wp-block-button .has-ast-global-color-8-background-color{background-color:var(--ast-global-color-8);}:root{--ast-global-color-0:#046bd2;--ast-global-color-1:#045cb4;--ast-global-color-2:#1e293b;--ast-global-color-3:#334155;--ast-global-color-4:#FFFFFF;--ast-global-color-5:#F0F5FA;--ast-global-color-6:#111111;--ast-global-color-7:#D1D5DB;--ast-global-color-8:#111111;}:root {--ast-border-color : var(--ast-global-color-7);}.ast-archive-entry-banner {-js-display: flex;display: flex;flex-direction: column;justify-content: center;text-align: center;position: relative;background: var(--ast-title-layout-bg);}.ast-archive-entry-banner[data-banner-width-type="custom"] {margin: 0 auto;width: 100%;}.ast-archive-entry-banner[data-banner-layout="layout-1"] {background: inherit;padding: 20px 0;text-align: left;}body.archive .ast-archive-description{max-width:1200px;width:100%;text-align:left;padding-top:3em;padding-right:3em;padding-bottom:3em;padding-left:3em;}body.archive .ast-archive-description .ast-archive-title,body.archive .ast-archive-description .ast-archive-title *{font-weight:600;font-size:32px;font-size:2rem;}body.archive .ast-archive-description > *:not(:last-child){margin-bottom:10px;}@media (max-width:921px){body.archive .ast-archive-description{text-align:left;}}@media (max-width:544px){body.archive .ast-archive-description{text-align:left;}}.ast-breadcrumbs .trail-browse,.ast-breadcrumbs .trail-items,.ast-breadcrumbs .trail-items li{display:inline-block;margin:0;padding:0;border:none;background:inherit;text-indent:0;text-decoration:none;}.ast-breadcrumbs .trail-browse{font-size:inherit;font-style:inherit;font-weight:inherit;color:inherit;}.ast-breadcrumbs .trail-items{list-style:none;}.trail-items li::after{padding:0 0.3em;content:"\00bb";}.trail-items li:last-of-type::after{display:none;}h1,.entry-content h1,h2,.entry-content h2,h3,.entry-content h3,h4,.entry-content h4,h5,.entry-content h5,h6,.entry-content h6{color:var(--ast-global-color-2);}.entry-title a{color:var(--ast-global-color-2);}@media (max-width:921px){.ast-builder-grid-row-container.ast-builder-grid-row-tablet-3-firstrow .ast-builder-grid-row > *:first-child,.ast-builder-grid-row-container.ast-builder-grid-row-tablet-3-lastrow .ast-builder-grid-row > *:last-child{grid-column:1 / -1;}}@media (max-width:544px){.ast-builder-grid-row-container.ast-builder-grid-row-mobile-3-firstrow .ast-builder-grid-row > *:first-child,.ast-builder-grid-row-container.ast-builder-grid-row-mobile-3-lastrow .ast-builder-grid-row > *:last-child{grid-column:1 / -1;}}.ast-builder-layout-element[data-section="title_tagline"]{display:flex;}@media (max-width:921px){.ast-header-break-point .ast-builder-layout-element[data-section="title_tagline"]{display:flex;}}@media (max-width:544px){.ast-header-break-point .ast-builder-layout-element[data-section="title_tagline"]{display:flex;}}[data-section*="section-hb-button-"] .menu-link{display:none;}.ast-header-button-1 .ast-custom-button{border-top-left-radius:40px;border-top-right-radius:40px;border-bottom-right-radius:40px;border-bottom-left-radius:40px;}.ast-header-button-1[data-section*="section-hb-button-"] .ast-builder-button-wrap .ast-custom-button{padding-top:15px;padding-bottom:15px;padding-left:30px;padding-right:30px;}.ast-header-button-1[data-section="section-hb-button-1"]{display:flex;}@media (max-width:921px){.ast-header-break-point .ast-header-button-1[data-section="section-hb-button-1"]{display:flex;}}@media (max-width:544px){.ast-header-break-point .ast-header-button-1[data-section="section-hb-button-1"]{display:flex;}}.ast-builder-menu-1{font-family:inherit;font-weight:inherit;}.ast-builder-menu-1 .menu-item > .menu-link{color:var(--ast-global-color-3);}.ast-builder-menu-1 .menu-item > .ast-menu-toggle{color:var(--ast-global-color-3);}.ast-builder-menu-1 .menu-item:hover > .menu-link,.ast-builder-menu-1 .inline-on-mobile .menu-item:hover > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-1 .menu-item:hover > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-1 .menu-item.current-menu-item > .menu-link,.ast-builder-menu-1 .inline-on-mobile .menu-item.current-menu-item > .ast-menu-toggle,.ast-builder-menu-1 .current-menu-ancestor > .menu-link{color:var(--ast-global-color-1);}.ast-builder-menu-1 .menu-item.current-menu-item > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-1 .sub-menu,.ast-builder-menu-1 .inline-on-mobile .sub-menu{border-top-width:2px;border-bottom-width:0px;border-right-width:0px;border-left-width:0px;border-color:var(--ast-global-color-0);border-style:solid;}.ast-builder-menu-1 .sub-menu .sub-menu{top:-2px;}.ast-builder-menu-1 .main-header-menu > .menu-item > .sub-menu,.ast-builder-menu-1 .main-header-menu > .menu-item > .astra-full-megamenu-wrapper{margin-top:0px;}.ast-desktop .ast-builder-menu-1 .main-header-menu > .menu-item > .sub-menu:before,.ast-desktop .ast-builder-menu-1 .main-header-menu > .menu-item > .astra-full-megamenu-wrapper:before{height:calc( 0px + 2px + 5px );}.ast-desktop .ast-builder-menu-1 .menu-item .sub-menu .menu-link{border-style:none;}@media (max-width:921px){.ast-header-break-point .ast-builder-menu-1 .menu-item.menu-item-has-children > .ast-menu-toggle{top:0;}.ast-builder-menu-1 .inline-on-mobile .menu-item.menu-item-has-children > .ast-menu-toggle{right:-15px;}.ast-builder-menu-1 .menu-item-has-children > .menu-link:after{content:unset;}.ast-builder-menu-1 .main-header-menu > .menu-item > .sub-menu,.ast-builder-menu-1 .main-header-menu > .menu-item > .astra-full-megamenu-wrapper{margin-top:0;}}@media (max-width:544px){.ast-header-break-point .ast-builder-menu-1 .menu-item.menu-item-has-children > .ast-menu-toggle{top:0;}.ast-builder-menu-1 .main-header-menu > .menu-item > .sub-menu,.ast-builder-menu-1 .main-header-menu > .menu-item > .astra-full-megamenu-wrapper{margin-top:0;}}.ast-builder-menu-1{display:flex;}@media (max-width:921px){.ast-header-break-point .ast-builder-menu-1{display:flex;}}@media (max-width:544px){.ast-header-break-point .ast-builder-menu-1{display:flex;}}.site-below-footer-wrap{padding-top:20px;padding-bottom:20px;}.site-below-footer-wrap[data-section="section-below-footer-builder"]{background-color:var(--ast-global-color-4 );min-height:60px;border-style:solid;border-width:0px;border-top-width:1px;border-top-color:var( --ast-global-color-subtle-background,--ast-global-color-7 );}.site-below-footer-wrap[data-section="section-below-footer-builder"] .ast-builder-grid-row{max-width:1200px;min-height:60px;margin-left:auto;margin-right:auto;}.site-below-footer-wrap[data-section="section-below-footer-builder"] .ast-builder-grid-row,.site-below-footer-wrap[data-section="section-below-footer-builder"] .site-footer-section{align-items:center;}.site-below-footer-wrap[data-section="section-below-footer-builder"].ast-footer-row-inline .site-footer-section{display:flex;margin-bottom:0;}.ast-builder-grid-row-2-equal .ast-builder-grid-row{grid-template-columns:repeat( 2,1fr );}@media (max-width:921px){.site-below-footer-wrap[data-section="section-below-footer-builder"].ast-footer-row-tablet-inline .site-footer-section{display:flex;margin-bottom:0;}.site-below-footer-wrap[data-section="section-below-footer-builder"].ast-footer-row-tablet-stack .site-footer-section{display:block;margin-bottom:10px;}.ast-builder-grid-row-container.ast-builder-grid-row-tablet-2-equal .ast-builder-grid-row{grid-template-columns:repeat( 2,1fr );}}@media (max-width:544px){.site-below-footer-wrap[data-section="section-below-footer-builder"].ast-footer-row-mobile-inline .site-footer-section{display:flex;margin-bottom:0;}.site-below-footer-wrap[data-section="section-below-footer-builder"].ast-footer-row-mobile-stack .site-footer-section{display:block;margin-bottom:10px;}.ast-builder-grid-row-container.ast-builder-grid-row-mobile-full .ast-builder-grid-row{grid-template-columns:1fr;}}.site-below-footer-wrap[data-section="section-below-footer-builder"]{display:grid;}@media (max-width:921px){.ast-header-break-point .site-below-footer-wrap[data-section="section-below-footer-builder"]{display:grid;}}@media (max-width:544px){.ast-header-break-point .site-below-footer-wrap[data-section="section-below-footer-builder"]{display:grid;}}.ast-footer-copyright{text-align:center;}.ast-footer-copyright {color:var(--ast-global-color-3);}@media (max-width:921px){.ast-footer-copyright{text-align:center;}}@media (max-width:544px){.ast-footer-copyright{text-align:center;}}.ast-footer-copyright {font-size:16px;font-size:1rem;}.ast-footer-copyright.ast-builder-layout-element{display:flex;}@media (max-width:921px){.ast-header-break-point .ast-footer-copyright.ast-builder-layout-element{display:flex;}}@media (max-width:544px){.ast-header-break-point .ast-footer-copyright.ast-builder-layout-element{display:flex;}}.ast-social-stack-desktop .ast-builder-social-element,.ast-social-stack-tablet .ast-builder-social-element,.ast-social-stack-mobile .ast-builder-social-element {margin-top: 6px;margin-bottom: 6px;}.social-show-label-true .ast-builder-social-element {width: auto;padding: 0 0.4em;}[data-section^="section-fb-social-icons-"] .footer-social-inner-wrap {text-align: center;}.ast-footer-social-wrap {width: 100%;}.ast-footer-social-wrap .ast-builder-social-element:first-child {margin-left: 0;}.ast-footer-social-wrap .ast-builder-social-element:last-child {margin-right: 0;}.ast-header-social-wrap .ast-builder-social-element:first-child {margin-left: 0;}.ast-header-social-wrap .ast-builder-social-element:last-child {margin-right: 0;}.ast-builder-social-element {line-height: 1;color: var(--ast-global-color-2);background: transparent;vertical-align: middle;transition: all 0.01s;margin-left: 6px;margin-right: 6px;justify-content: center;align-items: center;}.ast-builder-social-element .social-item-label {padding-left: 6px;}.ast-footer-social-1-wrap .ast-builder-social-element svg{width:18px;height:18px;}[data-section="section-fb-social-icons-1"] .footer-social-inner-wrap{text-align:center;}@media (max-width:921px){[data-section="section-fb-social-icons-1"] .footer-social-inner-wrap{text-align:center;}}@media (max-width:544px){[data-section="section-fb-social-icons-1"] .footer-social-inner-wrap{text-align:center;}}.ast-builder-layout-element[data-section="section-fb-social-icons-1"]{display:flex;}@media (max-width:921px){.ast-header-break-point .ast-builder-layout-element[data-section="section-fb-social-icons-1"]{display:flex;}}@media (max-width:544px){.ast-header-break-point .ast-builder-layout-element[data-section="section-fb-social-icons-1"]{display:flex;}}.site-primary-footer-wrap{padding-top:45px;padding-bottom:45px;}.site-primary-footer-wrap[data-section="section-primary-footer-builder"]{background-color:#f9f9f9;border-style:solid;border-width:0px;border-top-width:1px;border-top-color:var( --ast-global-color-subtle-background,--ast-global-color-7 );}.site-primary-footer-wrap[data-section="section-primary-footer-builder"] .ast-builder-grid-row{max-width:1200px;margin-left:auto;margin-right:auto;}.site-primary-footer-wrap[data-section="section-primary-footer-builder"] .ast-builder-grid-row,.site-primary-footer-wrap[data-section="section-primary-footer-builder"] .site-footer-section{align-items:flex-start;}.site-primary-footer-wrap[data-section="section-primary-footer-builder"].ast-footer-row-inline .site-footer-section{display:flex;margin-bottom:0;}.ast-builder-grid-row-4-equal .ast-builder-grid-row{grid-template-columns:repeat( 4,1fr );}@media (max-width:921px){.site-primary-footer-wrap[data-section="section-primary-footer-builder"].ast-footer-row-tablet-inline .site-footer-section{display:flex;margin-bottom:0;}.site-primary-footer-wrap[data-section="section-primary-footer-builder"].ast-footer-row-tablet-stack .site-footer-section{display:block;margin-bottom:10px;}.ast-builder-grid-row-container.ast-builder-grid-row-tablet-full .ast-builder-grid-row{grid-template-columns:1fr;}}@media (max-width:544px){.site-primary-footer-wrap[data-section="section-primary-footer-builder"].ast-footer-row-mobile-inline .site-footer-section{display:flex;margin-bottom:0;}.site-primary-footer-wrap[data-section="section-primary-footer-builder"].ast-footer-row-mobile-stack .site-footer-section{display:block;margin-bottom:10px;}.ast-builder-grid-row-container.ast-builder-grid-row-mobile-full .ast-builder-grid-row{grid-template-columns:1fr;}}.site-primary-footer-wrap[data-section="section-primary-footer-builder"]{display:grid;}@media (max-width:921px){.ast-header-break-point .site-primary-footer-wrap[data-section="section-primary-footer-builder"]{display:grid;}}@media (max-width:544px){.ast-header-break-point .site-primary-footer-wrap[data-section="section-primary-footer-builder"]{display:grid;}}.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"].footer-widget-area-inner{text-align:left;}.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"].footer-widget-area-inner{text-align:left;}.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"].footer-widget-area-inner{text-align:left;}.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"].footer-widget-area-inner{text-align:left;}.footer-widget-area.widget-area.site-footer-focus-item{width:auto;}.ast-footer-row-inline .footer-widget-area.widget-area.site-footer-focus-item{width:100%;}.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"]{display:block;}@media (max-width:921px){.ast-header-break-point .footer-widget-area[data-section="sidebar-widgets-footer-widget-1"]{display:block;}}@media (max-width:544px){.ast-header-break-point .footer-widget-area[data-section="sidebar-widgets-footer-widget-1"]{display:block;}}.footer-widget-area[data-section="sidebar-widgets-footer-widget-2"]{display:block;}@media (max-width:921px){.ast-header-break-point .footer-widget-area[data-section="sidebar-widgets-footer-widget-2"]{display:block;}}@media (max-width:544px){.ast-header-break-point .footer-widget-area[data-section="sidebar-widgets-footer-widget-2"]{display:block;}}.footer-widget-area[data-section="sidebar-widgets-footer-widget-3"]{display:block;}@media (max-width:921px){.ast-header-break-point .footer-widget-area[data-section="sidebar-widgets-footer-widget-3"]{display:block;}}@media (max-width:544px){.ast-header-break-point .footer-widget-area[data-section="sidebar-widgets-footer-widget-3"]{display:block;}}.footer-widget-area[data-section="sidebar-widgets-footer-widget-4"]{display:block;}@media (max-width:921px){.ast-header-break-point .footer-widget-area[data-section="sidebar-widgets-footer-widget-4"]{display:block;}}@media (max-width:544px){.ast-header-break-point .footer-widget-area[data-section="sidebar-widgets-footer-widget-4"]{display:block;}}.ast-header-break-point .main-header-bar{border-bottom-width:1px;}@media (min-width:922px){.main-header-bar{border-bottom-width:1px;}}@media (min-width:922px){#primary{width:70%;}#secondary{width:30%;}}.main-header-menu .menu-item, #astra-footer-menu .menu-item, .main-header-bar .ast-masthead-custom-menu-items{-js-display:flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;-moz-box-pack:center;-ms-flex-pack:center;justify-content:center;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;-moz-box-orient:vertical;-moz-box-direction:normal;-ms-flex-direction:column;flex-direction:column;}.main-header-menu > .menu-item > .menu-link, #astra-footer-menu > .menu-item > .menu-link{height:100%;-webkit-box-align:center;-webkit-align-items:center;-moz-box-align:center;-ms-flex-align:center;align-items:center;-js-display:flex;display:flex;}.ast-header-break-point .main-navigation ul .menu-item .menu-link .icon-arrow:first-of-type svg{top:.2em;margin-top:0px;margin-left:0px;width:.65em;transform:translate(0, -2px) rotateZ(270deg);}.ast-mobile-popup-content .ast-submenu-expanded > .ast-menu-toggle{transform:rotateX(180deg);overflow-y:auto;}@media (min-width:922px){.ast-builder-menu .main-navigation > ul > li:last-child a{margin-right:0;}}.ast-separate-container .ast-article-inner{background-color:var(--ast-global-color-4);}@media (max-width:921px){.ast-separate-container .ast-article-inner{background-color:var(--ast-global-color-4);}}@media (max-width:544px){.ast-separate-container .ast-article-inner{background-color:var(--ast-global-color-4);}}.ast-separate-container .ast-article-single:not(.ast-related-post), .woocommerce.ast-separate-container .ast-woocommerce-container, .ast-separate-container .error-404, .ast-separate-container .no-results, .single.ast-separate-container .site-main .ast-author-meta, .ast-separate-container .related-posts-title-wrapper,.ast-separate-container .comments-count-wrapper, .ast-box-layout.ast-plain-container .site-content,.ast-padded-layout.ast-plain-container .site-content, .ast-separate-container .ast-archive-description, .ast-separate-container .comments-area{background-color:var(--ast-global-color-4);}@media (max-width:921px){.ast-separate-container .ast-article-single:not(.ast-related-post), .woocommerce.ast-separate-container .ast-woocommerce-container, .ast-separate-container .error-404, .ast-separate-container .no-results, .single.ast-separate-container .site-main .ast-author-meta, .ast-separate-container .related-posts-title-wrapper,.ast-separate-container .comments-count-wrapper, .ast-box-layout.ast-plain-container .site-content,.ast-padded-layout.ast-plain-container .site-content, .ast-separate-container .ast-archive-description{background-color:var(--ast-global-color-4);}}@media (max-width:544px){.ast-separate-container .ast-article-single:not(.ast-related-post), .woocommerce.ast-separate-container .ast-woocommerce-container, .ast-separate-container .error-404, .ast-separate-container .no-results, .single.ast-separate-container .site-main .ast-author-meta, .ast-separate-container .related-posts-title-wrapper,.ast-separate-container .comments-count-wrapper, .ast-box-layout.ast-plain-container .site-content,.ast-padded-layout.ast-plain-container .site-content, .ast-separate-container .ast-archive-description{background-color:var(--ast-global-color-4);}}.ast-separate-container.ast-two-container #secondary .widget{background-color:var(--ast-global-color-4);}@media (max-width:921px){.ast-separate-container.ast-two-container #secondary .widget{background-color:var(--ast-global-color-4);}}@media (max-width:544px){.ast-separate-container.ast-two-container #secondary .widget{background-color:var(--ast-global-color-4);}}.ast-plain-container, .ast-page-builder-template{background-color:var(--ast-global-color-4);}@media (max-width:921px){.ast-plain-container, .ast-page-builder-template{background-color:var(--ast-global-color-4);}}@media (max-width:544px){.ast-plain-container, .ast-page-builder-template{background-color:var(--ast-global-color-4);}}
		#ast-scroll-top {
			display: none;
			position: fixed;
			text-align: center;
			cursor: pointer;
			z-index: 99;
			width: 2.1em;
			height: 2.1em;
			line-height: 2.1;
			color: #ffffff;
			border-radius: 2px;
			content: "";
			outline: inherit;
		}
		@media (min-width: 769px) {
			#ast-scroll-top {
				content: "769";
			}
		}
		#ast-scroll-top .ast-icon.icon-arrow svg {
			margin-left: 0px;
			vertical-align: middle;
			transform: translate(0, -20%) rotate(180deg);
			width: 1.6em;
		}
		.ast-scroll-to-top-right {
			right: 30px;
			bottom: 30px;
		}
		.ast-scroll-to-top-left {
			left: 30px;
			bottom: 30px;
		}
	#ast-scroll-top{background-color:var(--ast-global-color-0);font-size:15px;}@media (max-width:921px){#ast-scroll-top .ast-icon.icon-arrow svg{width:1em;}}.ast-mobile-header-content > *,.ast-desktop-header-content > * {padding: 10px 0;height: auto;}.ast-mobile-header-content > *:first-child,.ast-desktop-header-content > *:first-child {padding-top: 10px;}.ast-mobile-header-content > .ast-builder-menu,.ast-desktop-header-content > .ast-builder-menu {padding-top: 0;}.ast-mobile-header-content > *:last-child,.ast-desktop-header-content > *:last-child {padding-bottom: 0;}.ast-mobile-header-content .ast-search-menu-icon.ast-inline-search label,.ast-desktop-header-content .ast-search-menu-icon.ast-inline-search label {width: 100%;}.ast-desktop-header-content .main-header-bar-navigation .ast-submenu-expanded > .ast-menu-toggle::before {transform: rotateX(180deg);}#ast-desktop-header .ast-desktop-header-content,.ast-mobile-header-content .ast-search-icon,.ast-desktop-header-content .ast-search-icon,.ast-mobile-header-wrap .ast-mobile-header-content,.ast-main-header-nav-open.ast-popup-nav-open .ast-mobile-header-wrap .ast-mobile-header-content,.ast-main-header-nav-open.ast-popup-nav-open .ast-desktop-header-content {display: none;}.ast-main-header-nav-open.ast-header-break-point #ast-desktop-header .ast-desktop-header-content,.ast-main-header-nav-open.ast-header-break-point .ast-mobile-header-wrap .ast-mobile-header-content {display: block;}.ast-desktop .ast-desktop-header-content .astra-menu-animation-slide-up > .menu-item > .sub-menu,.ast-desktop .ast-desktop-header-content .astra-menu-animation-slide-up > .menu-item .menu-item > .sub-menu,.ast-desktop .ast-desktop-header-content .astra-menu-animation-slide-down > .menu-item > .sub-menu,.ast-desktop .ast-desktop-header-content .astra-menu-animation-slide-down > .menu-item .menu-item > .sub-menu,.ast-desktop .ast-desktop-header-content .astra-menu-animation-fade > .menu-item > .sub-menu,.ast-desktop .ast-desktop-header-content .astra-menu-animation-fade > .menu-item .menu-item > .sub-menu {opacity: 1;visibility: visible;}.ast-hfb-header.ast-default-menu-enable.ast-header-break-point .ast-mobile-header-wrap .ast-mobile-header-content .main-header-bar-navigation {width: unset;margin: unset;}.ast-mobile-header-content.content-align-flex-end .main-header-bar-navigation .menu-item-has-children > .ast-menu-toggle,.ast-desktop-header-content.content-align-flex-end .main-header-bar-navigation .menu-item-has-children > .ast-menu-toggle {left: calc( 20px - 0.907em);right: auto;}.ast-mobile-header-content .ast-search-menu-icon,.ast-mobile-header-content .ast-search-menu-icon.slide-search,.ast-desktop-header-content .ast-search-menu-icon,.ast-desktop-header-content .ast-search-menu-icon.slide-search {width: 100%;position: relative;display: block;right: auto;transform: none;}.ast-mobile-header-content .ast-search-menu-icon.slide-search .search-form,.ast-mobile-header-content .ast-search-menu-icon .search-form,.ast-desktop-header-content .ast-search-menu-icon.slide-search .search-form,.ast-desktop-header-content .ast-search-menu-icon .search-form {right: 0;visibility: visible;opacity: 1;position: relative;top: auto;transform: none;padding: 0;display: block;overflow: hidden;}.ast-mobile-header-content .ast-search-menu-icon.ast-inline-search .search-field,.ast-mobile-header-content .ast-search-menu-icon .search-field,.ast-desktop-header-content .ast-search-menu-icon.ast-inline-search .search-field,.ast-desktop-header-content .ast-search-menu-icon .search-field {width: 100%;padding-right: 5.5em;}.ast-mobile-header-content .ast-search-menu-icon .search-submit,.ast-desktop-header-content .ast-search-menu-icon .search-submit {display: block;position: absolute;height: 100%;top: 0;right: 0;padding: 0 1em;border-radius: 0;}.ast-hfb-header.ast-default-menu-enable.ast-header-break-point .ast-mobile-header-wrap .ast-mobile-header-content .main-header-bar-navigation ul .sub-menu .menu-link {padding-left: 30px;}.ast-hfb-header.ast-default-menu-enable.ast-header-break-point .ast-mobile-header-wrap .ast-mobile-header-content .main-header-bar-navigation .sub-menu .menu-item .menu-item .menu-link {padding-left: 40px;}.ast-mobile-popup-drawer.active .ast-mobile-popup-inner{background-color:#ffffff;;}.ast-mobile-header-wrap .ast-mobile-header-content, .ast-desktop-header-content{background-color:#ffffff;;}.ast-mobile-popup-content > *, .ast-mobile-header-content > *, .ast-desktop-popup-content > *, .ast-desktop-header-content > *{padding-top:0px;padding-bottom:0px;}.content-align-flex-start .ast-builder-layout-element{justify-content:flex-start;}.content-align-flex-start .main-header-menu{text-align:left;}.ast-desktop-header-content, .ast-mobile-header-content{position:absolute;width:100%;}.ast-mobile-popup-drawer.active .menu-toggle-close{color:#3a3a3a;}.ast-mobile-header-wrap .ast-primary-header-bar,.ast-primary-header-bar .site-primary-header-wrap{min-height:80px;}.ast-desktop .ast-primary-header-bar .main-header-menu > .menu-item{line-height:80px;}.ast-header-break-point #masthead .ast-mobile-header-wrap .ast-primary-header-bar,.ast-header-break-point #masthead .ast-mobile-header-wrap .ast-below-header-bar,.ast-header-break-point #masthead .ast-mobile-header-wrap .ast-above-header-bar{padding-left:20px;padding-right:20px;}.ast-header-break-point .ast-primary-header-bar{border-bottom-width:1px;border-bottom-color:var( --ast-global-color-subtle-background,--ast-global-color-7 );border-bottom-style:solid;}@media (min-width:922px){.ast-primary-header-bar{border-bottom-width:1px;border-bottom-color:var( --ast-global-color-subtle-background,--ast-global-color-7 );border-bottom-style:solid;}}.ast-primary-header-bar{background-color:var( --ast-global-color-primary,--ast-global-color-4 );}.ast-primary-header-bar{display:block;}@media (max-width:921px){.ast-header-break-point .ast-primary-header-bar{display:grid;}}@media (max-width:544px){.ast-header-break-point .ast-primary-header-bar{display:grid;}}[data-section="section-header-mobile-trigger"] .ast-button-wrap .ast-mobile-menu-trigger-minimal{color:var(--ast-global-color-0);border:none;background:transparent;}[data-section="section-header-mobile-trigger"] .ast-button-wrap .mobile-menu-toggle-icon .ast-mobile-svg{width:20px;height:20px;fill:var(--ast-global-color-0);}[data-section="section-header-mobile-trigger"] .ast-button-wrap .mobile-menu-wrap .mobile-menu{color:var(--ast-global-color-0);}.ast-builder-menu-mobile .main-navigation .main-header-menu .menu-item > .menu-link{color:var(--ast-global-color-3);}.ast-builder-menu-mobile .main-navigation .main-header-menu .menu-item > .ast-menu-toggle{color:var(--ast-global-color-3);}.ast-builder-menu-mobile .main-navigation .main-header-menu .menu-item:hover > .menu-link, .ast-builder-menu-mobile .main-navigation .inline-on-mobile .menu-item:hover > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-mobile .menu-item:hover > .menu-link, .ast-builder-menu-mobile .main-navigation .inline-on-mobile .menu-item:hover > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-mobile .main-navigation .menu-item:hover > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-mobile .main-navigation .menu-item.current-menu-item > .menu-link, .ast-builder-menu-mobile .main-navigation .inline-on-mobile .menu-item.current-menu-item > .ast-menu-toggle, .ast-builder-menu-mobile .main-navigation .menu-item.current-menu-ancestor > .menu-link, .ast-builder-menu-mobile .main-navigation .menu-item.current-menu-ancestor > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-mobile .main-navigation .menu-item.current-menu-item > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-mobile .main-navigation .menu-item.menu-item-has-children > .ast-menu-toggle{top:0;}.ast-builder-menu-mobile .main-navigation .menu-item-has-children > .menu-link:after{content:unset;}.ast-hfb-header .ast-builder-menu-mobile .main-header-menu, .ast-hfb-header .ast-builder-menu-mobile .main-navigation .menu-item .menu-link, .ast-hfb-header .ast-builder-menu-mobile .main-navigation .menu-item .sub-menu .menu-link{border-style:none;}.ast-builder-menu-mobile .main-navigation .menu-item.menu-item-has-children > .ast-menu-toggle{top:0;}@media (max-width:921px){.ast-builder-menu-mobile .main-navigation .main-header-menu .menu-item > .menu-link{color:var(--ast-global-color-3);}.ast-builder-menu-mobile .main-navigation .main-header-menu .menu-item > .ast-menu-toggle{color:var(--ast-global-color-3);}.ast-builder-menu-mobile .main-navigation .main-header-menu .menu-item:hover > .menu-link, .ast-builder-menu-mobile .main-navigation .inline-on-mobile .menu-item:hover > .ast-menu-toggle{color:var(--ast-global-color-1);background:var(--ast-global-color-5);}.ast-builder-menu-mobile .main-navigation .menu-item:hover > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-mobile .main-navigation .menu-item.current-menu-item > .menu-link, .ast-builder-menu-mobile .main-navigation .inline-on-mobile .menu-item.current-menu-item > .ast-menu-toggle, .ast-builder-menu-mobile .main-navigation .menu-item.current-menu-ancestor > .menu-link, .ast-builder-menu-mobile .main-navigation .menu-item.current-menu-ancestor > .ast-menu-toggle{color:var(--ast-global-color-1);background:var(--ast-global-color-5);}.ast-builder-menu-mobile .main-navigation .menu-item.current-menu-item > .ast-menu-toggle{color:var(--ast-global-color-1);}.ast-builder-menu-mobile .main-navigation .menu-item.menu-item-has-children > .ast-menu-toggle{top:0;}.ast-builder-menu-mobile .main-navigation .menu-item-has-children > .menu-link:after{content:unset;}.ast-builder-menu-mobile .main-navigation .main-header-menu , .ast-builder-menu-mobile .main-navigation .main-header-menu .menu-link, .ast-builder-menu-mobile .main-navigation .main-header-menu .sub-menu{background-color:var(--ast-global-color-4);}}@media (max-width:544px){.ast-builder-menu-mobile .main-navigation .menu-item.menu-item-has-children > .ast-menu-toggle{top:0;}}.ast-builder-menu-mobile .main-navigation{display:block;}@media (max-width:921px){.ast-header-break-point .ast-builder-menu-mobile .main-navigation{display:block;}}@media (max-width:544px){.ast-header-break-point .ast-builder-menu-mobile .main-navigation{display:block;}}
/*# sourceURL=astra-theme-css-inline-css */
````

- [ ] **Étape 6 : écrire les neutralisations déclarées**

Règles qu'Astra SPIP émet en plus, pour défaire une règle fixe de ses feuilles qu'Astra n'émet plus avec ce réglage.

`attendus/bouton.neutralisations.css` (marges réglées pour l'ordinateur seul : Astra n'émet plus ses marges tablette et mobile 14/28 et 12/24, que `css/astra-dynamic.css` contient) :
````css
[@media (max-width:921px)] .wp-block-button .wp-block-button__link,.wp-block-search .wp-block-search__button,body .wp-block-file .wp-block-file__button{padding-top:17px;padding-right:26px;padding-bottom:17px;padding-left:26px;}
[@media (max-width:921px)] .menu-toggle,button,.ast-button,.ast-custom-button,.button,input#submit,input[type="button"],input[type="submit"],input[type="reset"],#comments .submit,.search .search-submit,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button,body .wp-block-file .wp-block-file__button,.search .search-submit,.woocommerce-js a.button,.woocommerce button.button,.woocommerce .woocommerce-message a.button,.woocommerce #respond input#submit.alt,.woocommerce input.button.alt,.woocommerce input.button,.woocommerce input.button:disabled,.woocommerce input.button:disabled[disabled],.woocommerce input.button:disabled:hover,.woocommerce input.button:disabled[disabled]:hover,.woocommerce #respond input#submit,.woocommerce button.button.alt.disabled,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link,.wc-block-grid__product-onsale,[CLASS*="wc-block"] button,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping),.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout,.woocommerce button.button.alt.disabled.wc-variation-selection-needed,[CLASS*="wc-block"] .wc-block-components-button{padding-top:17px;padding-right:26px;padding-bottom:17px;padding-left:26px;}
[@media (max-width:544px)] .wp-block-button .wp-block-button__link,.wp-block-search .wp-block-search__button,body .wp-block-file .wp-block-file__button{padding-top:17px;padding-right:26px;padding-bottom:17px;padding-left:26px;}
[@media (max-width:544px)] .menu-toggle,button,.ast-button,.ast-custom-button,.button,input#submit,input[type="button"],input[type="submit"],input[type="reset"],#comments .submit,.search .search-submit,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button,body .wp-block-file .wp-block-file__button,.search .search-submit,.woocommerce-js a.button,.woocommerce button.button,.woocommerce .woocommerce-message a.button,.woocommerce #respond input#submit.alt,.woocommerce input.button.alt,.woocommerce input.button,.woocommerce input.button:disabled,.woocommerce input.button:disabled[disabled],.woocommerce input.button:disabled:hover,.woocommerce input.button:disabled[disabled]:hover,.woocommerce #respond input#submit,.woocommerce button.button.alt.disabled,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link,.wc-block-grid__product-onsale,[CLASS*="wc-block"] button,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping),.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout,.woocommerce button.button.alt.disabled.wc-variation-selection-needed,[CLASS*="wc-block"] .wc-block-components-button{padding-top:17px;padding-right:26px;padding-bottom:17px;padding-left:26px;}
````

`attendus/bouton-entete.neutralisations.css` (rayons enregistrés vides : Astra n'émet plus les 40 px de `css/astra-hfb.css` ; le bouton prend le rayon du bouton global, 0 sans réglage) :
````css
.ast-header-button-1 .ast-custom-button{border-top-left-radius:0;border-top-right-radius:0;border-bottom-right-radius:0;border-bottom-left-radius:0;}
````

- [ ] **Étape 7 : contrôle de l'outil de différence**

Run : `python3 tests/references/styles/regles.py tests/references/styles/attendus/base.css tests/references/styles/attendus/base.css | wc -l`
Expected : `0`

- [ ] **Étape 8 : commit**

```bash
git add tests/references/styles/appliquer.php tests/references/styles/capturer.sh tests/references/styles/regles.py
git add tests/references/styles/valeurs/menu.json tests/references/styles/valeurs/bouton.json tests/references/styles/valeurs/bouton-entete.json tests/references/styles/valeurs/identite.json tests/references/styles/valeurs/pied.json tests/references/styles/valeurs/sociaux.json
git add tests/references/styles/attendus/base.css tests/references/styles/attendus/menu.css tests/references/styles/attendus/bouton.css tests/references/styles/attendus/bouton-entete.css tests/references/styles/attendus/identite.css tests/references/styles/attendus/pied.css tests/references/styles/attendus/sociaux.css
git add tests/references/styles/attendus/bouton.neutralisations.css tests/references/styles/attendus/bouton-entete.neutralisations.css
git commit -m "Styles du Customizer : attendus capturés dans WordPress (valeurs de Garden Maintenance)"
```

---

### Tâche 2 : registre et normalisation commune

**Fichiers :**
- Créer : `inc/astra_styles.php` (sans l'écriture ni le point d'entrée, ajoutés à la tâche 3)
- Créer : `inc/astra_polices.php` (registre vide, rempli à la tâche 12)
- Test : `tests/unit/StylesTest.php`

**Interfaces :**
- Consomme : `astra_hfb_normaliser(array $config): array` (`inc/astra_hfb.php`, chargé par le bootstrap des tests unitaires et par `astra_fonctions.php`).
- Produit :
  - `astra_styles_registre(): array` — option ⇒ `['groupe', 'source', 'type', 'responsive', 'defaut', 'unites'?, 'valeurs'?]` ; groupes `menu`, `bouton`, `bouton-entete`, `identite`, `pied`, `sociaux` ; types `couleur`, `taille`, `quatre-cotes`, `nombre`, `police`, `graisse`, `enum`, `booleen`, `fond`, `extras-police` ;
  - `astra_styles_normaliser($valeurs): array` ; `astra_styles_couleur_valide($v): bool` ;
  - constantes `ASTRA_STYLES_TAILLE_TEXTE` (16), `ASTRA_STYLES_APPAREILS` ;
  - aides pour les modules : `astra_styles_ordi($v)`, `astra_styles_renseigne($v): bool`, `astra_styles_pose(array $s, string $groupe, string $prefixe = ''): bool`, `astra_styles_element_place(string $id, array $hfb, string $builder): bool` (`'entete'` ou `'pied'`), `astra_styles_ou(array $s, string $option, $defaut = '')`, `astra_styles_taille_police($valeur, $defaut = ''): string`, `astra_styles_cote($valeur, string $cote, $defaut = ''): string`, `astra_styles_famille($valeur, string $defaut = ''): string`, `astra_styles_extras($valeur): array`, `astra_styles_fond($valeur): array` ;
  - `astra_polices_locales(): array` (vide ici).

- [ ] **Étape 1 : écrire le test**

````php
<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';

/** Registre, normalisation commune et sécurité des styles du Customizer. */
final class StylesTest extends TestCase {

	private static function valeurs(string $groupe): array {
		return json_decode(file_get_contents(dirname(__DIR__) . "/references/styles/valeurs/$groupe.json"), true);
	}

	public function testRegistreComplet(): void {
		foreach (astra_styles_registre() as $option => $d) {
			foreach (['groupe', 'source', 'type', 'responsive'] as $champ) {
				$this->assertArrayHasKey($champ, $d, "$option : $champ");
			}
			$this->assertArrayHasKey('defaut', $d, $option);
			if (in_array($d['type'], ['taille'], true)) {
				$this->assertNotEmpty($d['unites'] ?? [], "$option : unités");
			}
			if ($d['type'] === 'enum') {
				$this->assertNotEmpty($d['valeurs'] ?? [], "$option : valeurs");
			}
		}
	}

	/** Aller-retour sans perte des structures d'origine d'Astra (valeurs du site.json de Garden). */
	public function testStructuresDAstraConservees(): void {
		foreach (['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'] as $groupe) {
			$v = array_intersect_key(self::valeurs($groupe), astra_styles_registre());
			$this->assertSame($v, astra_styles_normaliser($v), $groupe);
		}
	}

	public function testChampInvalideRetireSeul(): void {
		$v = [
			'header-menu1-bg-obj-responsive' => [
				'desktop' => ['background-color' => '#fff', 'background-repeat' => 'nimporte', 'champ-inconnu' => 1],
				'tablet' => ['background-color' => 'url(x)'],
			],
			'font-size-button' => ['desktop' => '14', 'desktop-unit' => 'pt', 'tablet' => '-3', 'mobile' => '12'],
			'button-radius-fields' => ['desktop' => ['top' => 8, 'right' => 'x', 'autre' => 2], 'desktop-unit' => 'px'],
			'font-extras-button' => ['text-transform' => 'capitalize', 'text-decoration' => 'blink'],
			'footer-social-1-color-type' => 'arc-en-ciel',
		];
		$this->assertSame([
			'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => '#fff'], 'tablet' => []],
			'font-size-button' => ['desktop' => '14', 'mobile' => '12'],
			'button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'px'],
			'font-extras-button' => ['text-transform' => 'capitalize'],
		], astra_styles_normaliser($v));
	}

	public function testValeursTabletteEtMobileConservees(): void {
		$v = ['header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222', 'mobile' => 'var(--ast-global-color-3)']];
		$this->assertSame($v, astra_styles_normaliser($v));
	}

	public function testCouleurs(): void {
		foreach (['', '#abc', '#A1B2C3', 'var(--ast-global-color-8)', 'rgba(64,191,79,0.15)', 'rgb(0, 0, 0)', 'rgba(255,255,255,1)'] as $c) {
			$this->assertTrue(astra_styles_couleur_valide($c), $c);
		}
		foreach (['red', '#abcd', 'var(--x)', 'rgba(300,0,0,1)', 'rgb(0,0,0,1)', 'rgba(0,0,0)', '#fff;}body{', 'expression(1)'] as $c) {
			$this->assertFalse(astra_styles_couleur_valide($c), $c);
		}
	}

	public function testTaillePoliceCommeAstra(): void {
		$this->assertSame('14px;font-size:0.875rem', astra_styles_taille_police(['desktop' => '14', 'desktop-unit' => 'px']));
		$this->assertSame('1.2em', astra_styles_taille_police(['desktop' => '1.2', 'desktop-unit' => 'em']));
		$this->assertSame('', astra_styles_taille_police(['desktop' => '']));
		$this->assertSame('16px;font-size:1rem', astra_styles_taille_police('', 16));
	}
}
````

- [ ] **Étape 2 : vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesTest`
Expected : erreur fatale, `inc/astra_styles.php` introuvable.

- [ ] **Étape 3 : écrire `inc/astra_polices.php`**

````php
<?php
/**
 * Astra pour SPIP — polices locales (spec des styles du Customizer, « Polices locales ») :
 * registre fermé des polices fournies en woff2 dans fonts/, et leurs @font-face.
 *
 * @license GPL-3.0-or-later
 */

/** Polices locales : nom, pile CSS, graisses fournies. Vide tant qu'aucune n'est fournie. */
function astra_polices_locales(): array {
	return [];
}
````

- [ ] **Étape 4 : écrire `inc/astra_styles.php`**

````php
<?php
/**
 * Astra pour SPIP — styles du Customizer d'Astra 4.11.0 : registre des options, normalisation
 * commune, écriture du CSS (docs/superpowers/specs/2026-10-23-styles-customizer-design.md).
 *
 * Les options portent les noms d'Astra ; leurs structures (valeur par appareil, unités, côtés,
 * fonds, extras de police) sont conservées. Seule la valeur `desktop` est exploitée.
 *
 * @license GPL-3.0-or-later
 */

require_once __DIR__ . '/astra_polices.php';

/** Taille du texte d'Astra SPIP (px) : base de la conversion px → rem d'astra_get_font_css_value(). */
const ASTRA_STYLES_TAILLE_TEXTE = 16;

/** Appareils des options responsives d'Astra. */
const ASTRA_STYLES_APPAREILS = ['desktop', 'tablet', 'mobile'];

/**
 * Registre des options : groupe, fichier source d'Astra 4.11.0, type, structure responsive,
 * unités ou valeurs acceptées, défaut d'Astra (documentation et tests : jamais écrit).
 */
function astra_styles_registre(): array {
	static $r = null;
	if ($r !== null) {
		return $r;
	}
	$r = [];
	$ajouter = function (string $groupe, string $source, array $options) use (&$r) {
		foreach ($options as $option => $d) {
			$r[$option] = $d + ['groupe' => $groupe, 'source' => $source, 'responsive' => false, 'defaut' => ''];
		}
	};
	$couleur = ['type' => 'couleur'];
	$couleur_r = ['type' => 'couleur', 'responsive' => true];
	$taille_r = ['type' => 'taille', 'responsive' => true, 'unites' => ['px', 'em', 'rem', 'vw']];
	$cotes_r = ['type' => 'quatre-cotes', 'responsive' => true, 'unites' => ['px', 'em', '%']];
	$cotes = ['type' => 'quatre-cotes'];
	$nombre_r = ['type' => 'nombre', 'responsive' => true];

	$menu = 'inc/builder/type/header/menu/dynamic-css/dynamic.css.php';
	foreach ([1, 2] as $n) {
		$ajouter('menu', $menu, [
			"header-menu$n-color-responsive" => $couleur_r,
			"header-menu$n-h-color-responsive" => $couleur_r,
			"header-menu$n-a-color-responsive" => $couleur_r,
			"header-menu$n-h-bg-color-responsive" => $couleur_r,
			"header-menu$n-a-bg-color-responsive" => $couleur_r,
			"header-menu$n-bg-obj-responsive" => ['type' => 'fond', 'responsive' => true],
			"header-menu$n-font-size" => $taille_r,
			"header-menu$n-font-weight" => ['type' => 'graisse', 'defaut' => 'inherit'],
			"header-menu$n-font-family" => ['type' => 'police', 'defaut' => 'inherit'],
			"header-menu$n-font-extras" => ['type' => 'extras-police'],
			"header-menu$n-submenu-b-color" => $couleur,
			"header-menu$n-submenu-border" => $cotes + ['defaut' => ['top' => 2, 'bottom' => 0, 'left' => 0, 'right' => 0]],
		]);
	}
	$ajouter('bouton', 'inc/class-astra-dynamic-css.php', [
		'button-color' => $couleur,
		'button-h-color' => $couleur,
		'button-bg-color' => $couleur,
		'button-bg-h-color' => $couleur,
		'button-radius-fields' => $cotes_r,
		'theme-button-padding' => $cotes_r + ['defaut' => ['desktop' => ['top' => 15, 'right' => 30, 'bottom' => 15, 'left' => 30]]],
		'theme-button-border-group-border-size' => $cotes,
		'font-family-button' => ['type' => 'police', 'defaut' => 'inherit'],
		'font-weight-button' => ['type' => 'graisse', 'defaut' => '500'],
		'font-size-button' => $taille_r + ['defaut' => ['desktop' => 16, 'desktop-unit' => 'px']],
		'font-extras-button' => ['type' => 'extras-police', 'defaut' => ['line-height' => 1, 'line-height-unit' => 'em']],
	]);
	$ajouter('bouton-entete', 'inc/builder/type/base/dynamic-css/button/class-astra-button-component-dynamic-css.php', [
		'header-button1-text-color' => $couleur_r,
		'header-button1-text-h-color' => $couleur_r,
		'header-button1-back-color' => $couleur_r,
		'header-button1-back-h-color' => $couleur_r,
		'header-button1-border-color' => $couleur_r,
		'header-button1-border-h-color' => $couleur_r,
		'header-button1-border-size' => $cotes,
		'header-button1-border-radius-fields' => $cotes_r + ['defaut' => ['desktop' => ['top' => 40, 'right' => 40, 'bottom' => 40, 'left' => 40]]],
		'header-button1-font-size' => $taille_r,
		'header-button1-font-family' => ['type' => 'police', 'defaut' => 'inherit'],
		'header-button1-font-weight' => ['type' => 'graisse', 'defaut' => 'inherit'],
		'header-button1-font-extras' => ['type' => 'extras-police'],
	]);
	$ajouter('identite', 'inc/class-astra-dynamic-css.php', [
		'font-size-site-title' => $taille_r + ['defaut' => ['desktop' => 26, 'desktop-unit' => 'px']],
		'font-size-site-tagline' => $taille_r + ['defaut' => ['desktop' => 15, 'desktop-unit' => 'px']],
		'header-color-site-title' => $couleur + ['defaut' => 'var(--ast-global-color-2)'],
		'header-color-h-site-title' => $couleur,
		'header-color-site-tagline' => $couleur,
		'display-site-title-responsive' => ['type' => 'booleen', 'responsive' => true, 'defaut' => ['desktop' => 1, 'tablet' => 1, 'mobile' => 1]],
		'display-site-tagline-responsive' => ['type' => 'booleen', 'responsive' => true, 'defaut' => ['desktop' => 0, 'tablet' => 0, 'mobile' => 0]],
		'ast-header-responsive-logo-width' => $nombre_r,
	]);
	$widgets = 'inc/builder/type/base/dynamic-css/widget/class-astra-widget-component-dynamic-css.php';
	for ($n = 1; $n <= 4; $n++) {
		$ajouter('pied', $widgets, [
			"footer-widget-$n-title-color" => $couleur_r,
			"footer-widget-$n-color" => $couleur_r,
			"footer-widget-$n-link-color" => $couleur_r,
			"footer-widget-$n-link-h-color" => $couleur_r,
			"footer-widget-$n-font-size" => $taille_r,
			"footer-widget-$n-content-font-size" => $taille_r,
		]);
	}
	$ajouter('pied', 'inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php', [
		'footer-copyright-color' => $couleur + ['defaut' => 'var(--ast-global-color-3)'],
		'font-size-section-footer-copyright' => $taille_r + ['defaut' => ['desktop' => 16, 'desktop-unit' => 'px']],
	]);
	$sociaux = 'inc/builder/type/base/dynamic-css/social/class-astra-social-component-dynamic-css.php';
	foreach (['header', 'footer'] as $b) {
		$ajouter('sociaux', $sociaux, [
			"$b-social-1-color" => $couleur_r,
			"$b-social-1-h-color" => $couleur_r,
			"$b-social-1-size" => $nombre_r + ['defaut' => ['desktop' => 18]],
			"$b-social-1-space" => $nombre_r,
			"$b-social-1-color-type" => ['type' => 'enum', 'valeurs' => ['custom', 'official'], 'defaut' => 'custom'],
		]);
	}
	return $r;
}

/** Couleur acceptée : vide, hexadécimale, couleur de la palette, rgb() ou rgba(). */
function astra_styles_couleur_valide($v): bool {
	if (!is_string($v)) {
		return false;
	}
	if ($v === '' || preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $v) || preg_match('/^var\(--ast-global-color-[0-8]\)$/', $v)) {
		return true;
	}
	if (!preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(0|1|0?\.\d+|1\.0+)\s*)?\)$/', $v, $m)) {
		return false;
	}
	return max((int) $m[1], (int) $m[2], (int) $m[3]) <= 255 && (strpos($v, 'rgba') === 0) === isset($m[4]);
}

/** Nombre borné ou vide (tailles, côtés, largeurs). */
function astra_styles_nombre_valide($v, float $max = 1000): bool {
	return $v === '' || ((is_int($v) || is_float($v) || (is_string($v) && is_numeric($v))) && (float) $v >= 0 && (float) $v <= $max);
}

/** Champs autorisés d'un fond (`*-bg-obj-responsive`), par appareil. */
function astra_styles_champs_fond(): array {
	$positions = [''];
	foreach (['left', 'center', 'right'] as $x) {
		foreach (['top', 'center', 'bottom'] as $y) {
			$positions[] = "$x $y";
		}
	}
	return [
		'background-type' => ['', 'color', 'image', 'gradient'],
		'background-color' => 'couleur',
		'background-image' => 'image',
		'background-media' => 'entier',
		'background-repeat' => ['', 'repeat', 'no-repeat', 'repeat-x', 'repeat-y'],
		'background-position' => $positions,
		'background-size' => ['', 'auto', 'cover', 'contain'],
		'background-attachment' => ['', 'scroll', 'fixed', 'inherit'],
		'overlay-type' => ['', 'none', 'classic', 'gradient'],
		'overlay-color' => 'couleur',
		'overlay-opacity' => 'opacite',
		'overlay-gradient' => [''],
	];
}

/** Champs autorisés des extras de police (`*-font-extras`, astra_get_font_extras()). */
function astra_styles_champs_extras(): array {
	return [
		'line-height' => 'nombre',
		'line-height-unit' => ['', 'px', 'em'],
		'letter-spacing' => 'nombre-signe',
		'letter-spacing-unit' => ['', 'px', 'em'],
		'text-transform' => ['', 'none', 'capitalize', 'uppercase', 'lowercase'],
		'text-decoration' => ['', 'initial', 'none', 'underline', 'overline', 'line-through'],
	];
}

/** Garde les champs connus et valides d'un objet ; null si ce n'est pas un tableau. */
function astra_styles_filtrer_objet($v, array $champs): ?array {
	if (!is_array($v)) {
		return null;
	}
	$r = [];
	foreach ($champs as $champ => $regle) {
		if (!array_key_exists($champ, $v)) {
			continue;
		}
		$x = $v[$champ];
		$ok = is_array($regle) ? in_array($x, $regle, true) : (
			$regle === 'couleur' ? astra_styles_couleur_valide($x) : (
			$regle === 'nombre' ? astra_styles_nombre_valide($x) : (
			$regle === 'nombre-signe' ? ($x === '' || (is_numeric($x) && abs((float) $x) <= 100)) : (
			$regle === 'opacite' ? ($x === '' || (is_numeric($x) && (float) $x >= 0 && (float) $x <= 1)) : (
			$regle === 'entier' ? ($x === '' || (is_numeric($x) && (int) $x == $x)) : (
			$regle === 'image' ? (is_string($x) && ($x === '' || preg_match('#^IMG/[\w./-]+\.(png|jpe?g|gif|webp|svg)$#i', $x))) : false))))));
		if ($ok) {
			$r[$champ] = $x;
		}
	}
	return $r;
}

/** Valeur d'un appareil (ou d'une option non responsive) selon le type ; null si invalide. */
function astra_styles_valider(array $d, $v) {
	switch ($d['type']) {
		case 'couleur':
			return astra_styles_couleur_valide($v) ? $v : null;
		case 'taille':
		case 'nombre':
			return astra_styles_nombre_valide($v) ? $v : null;
		case 'quatre-cotes':
			return astra_styles_filtrer_objet($v, array_fill_keys(['top', 'right', 'bottom', 'left'], 'nombre'));
		case 'police':
			return is_string($v) && ($v === '' || isset(astra_polices_locales()[$v]) || preg_match('/^[\w\s,\'"-]+$/u', $v)) ? $v : null;
		case 'graisse':
			return in_array((string) $v, ['', 'inherit', 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'], true) && !is_bool($v) ? $v : null;
		case 'enum':
			return in_array($v, $d['valeurs'], true) ? $v : null;
		case 'booleen':
			return is_bool($v) || in_array($v, [0, 1, '0', '1', ''], true) ? $v : null;
		case 'fond':
			return astra_styles_filtrer_objet($v, astra_styles_champs_fond());
		case 'extras-police':
			return astra_styles_filtrer_objet($v, astra_styles_champs_extras());
	}
	return null;
}

/** Une option normalisée ; null si rien n'en reste. */
function astra_styles_normaliser_option(array $d, $v) {
	if (!$d['responsive']) {
		return astra_styles_valider($d, $v);
	}
	if (!is_array($v)) {
		return null;
	}
	// Ordre des clés d'origine conservé : aller-retour sans perte des structures d'Astra.
	$r = [];
	foreach ($v as $cle => $x) {
		if (in_array($cle, ASTRA_STYLES_APPAREILS, true)) {
			if (($x = astra_styles_valider($d, $x)) !== null) {
				$r[$cle] = $x;
			}
		} elseif (isset($d['unites']) && preg_match('/^(desktop|tablet|mobile)-unit$/', (string) $cle) && in_array($x, $d['unites'], true)) {
			$r[$cle] = $x;
		}
	}
	return $r ?: null;
}

/**
 * Normalisation commune (formulaire, seed de Garden, imports) : clés du registre seulement,
 * structures d'Astra conservées, valeur ou champ invalide retiré seul.
 */
function astra_styles_normaliser($valeurs): array {
	$registre = astra_styles_registre();
	$r = [];
	foreach (is_array($valeurs) ? $valeurs : [] as $cle => $v) {
		if (isset($registre[$cle]) && ($n = astra_styles_normaliser_option($registre[$cle], $v)) !== null) {
			$r[$cle] = $n;
		}
	}
	return $r;
}

/** Valeur ordinateur d'une option (la valeur elle-même si elle n'est pas responsive). */
function astra_styles_ordi($v) {
	return is_array($v) && array_key_exists('desktop', $v) ? $v['desktop'] : $v;
}

/** Vrai si la valeur contient au moins une donnée non vide. */
function astra_styles_renseigne($v): bool {
	if (is_array($v)) {
		foreach ($v as $x) {
			if (astra_styles_renseigne($x)) {
				return true;
			}
		}
		return false;
	}
	return $v !== '' && $v !== null;
}

/** Vrai si une option du groupe (et du préfixe) a une valeur ordinateur renseignée. */
function astra_styles_pose(array $s, string $groupe, string $prefixe = ''): bool {
	foreach (astra_styles_registre() as $o => $d) {
		if ($d['groupe'] === $groupe && isset($s[$o]) && ($prefixe === '' || strpos($o, $prefixe) === 0)
			&& astra_styles_renseigne($d['responsive'] ? astra_styles_ordi($s[$o]) : $s[$o])) {
			return true;
		}
	}
	return false;
}

/** Vrai si l'élément est placé dans le builder (Astra_Builder_Helper::is_component_loaded()). */
function astra_styles_element_place(string $id, array $hfb, string $builder): bool {
	$n = astra_hfb_normaliser($hfb);
	$parties = $builder === 'pied' ? [$n['pied']] : [$n['entete_ordinateur'], $n['entete_mobile']];
	foreach ($parties as $rangees) {
		foreach ($rangees as $zones) {
			foreach ($zones as $ids) {
				if (in_array($id, $ids, true)) {
					return true;
				}
			}
		}
	}
	return false;
}

/** Valeur ordinateur renseignée, sinon le défaut. */
function astra_styles_ou(array $s, string $option, $defaut = '') {
	$v = astra_styles_ordi($s[$option] ?? '');
	return $v === '' || $v === null ? $defaut : $v;
}

/** Taille de police comme astra_get_font_css_value() : en px, doublée d'une valeur en rem. */
function astra_styles_taille_police($valeur, $defaut = ''): string {
	$v = astra_styles_ordi($valeur);
	$unite = is_array($valeur) ? ($valeur['desktop-unit'] ?? 'px') : 'px';
	if ($v === '' || $v === null || (float) $v == 0) {
		if ($defaut === '') {
			return '';
		}
		[$v, $unite] = [$defaut, 'px'];
	}
	if ($unite !== 'px') {
		return $v . $unite;
	}
	return (int) $v . 'px;font-size:' . ((int) $v / ASTRA_STYLES_TAILLE_TEXTE) . 'rem';
}

/** Un côté d'une option quatre-côtés (responsive : valeur ordinateur et son unité ; sinon px). */
function astra_styles_cote($valeur, string $cote, $defaut = ''): string {
	if (!is_array($valeur)) {
		return $defaut === '' ? '' : $defaut . 'px';
	}
	$responsive = array_key_exists('desktop', $valeur);
	$cotes = $responsive ? $valeur['desktop'] : $valeur;
	$v = is_array($cotes) ? ($cotes[$cote] ?? '') : '';
	if ($v === '' || $v === null) {
		return $defaut === '' ? '' : $defaut . 'px';
	}
	return $v . ($responsive ? ($valeur['desktop-unit'] ?? 'px') : 'px');
}

/** Famille de police comme astra_get_css_value(…, 'font') : `inherit` donne le défaut. */
function astra_styles_famille($valeur, string $defaut = ''): string {
	if ($valeur === '' || $valeur === 'inherit' || $valeur === null) {
		return $defaut;
	}
	$locale = astra_polices_locales()[$valeur] ?? null;
	return $locale ? $locale['pile'] : $valeur;
}

/** Extras de police comme astra_get_font_extras(). */
function astra_styles_extras($valeur): array {
	$e = is_array($valeur) ? $valeur : [];
	$val = function (string $champ, string $unite = '') use ($e): string {
		$x = $e[$champ] ?? '';
		return $x === '' || $x === null ? '' : $x . ($unite !== '' ? ($e[$unite] ?? '') : '');
	};
	return [
		'line-height' => $val('line-height', 'line-height-unit'),
		'text-transform' => $val('text-transform'),
		'letter-spacing' => $val('letter-spacing', 'letter-spacing-unit'),
		'text-decoration' => $val('text-decoration'),
	];
}

/** Fond comme astra_get_responsive_background_obj(…, 'desktop'), types `color` et vide. */
function astra_styles_fond($valeur): array {
	$f = astra_styles_ordi($valeur);
	if (!is_array($f)) {
		return [];
	}
	$type = $f['background-type'] ?? '';
	$couleur = $f['background-color'] ?? '';
	if ($type === 'color') {
		return ($f['background-image'] ?? '') === '' ? ['background-color' => $couleur, 'background-image' => 'none'] : [];
	}
	return $type === '' && $couleur !== '' ? ['background-color' => $couleur] : [];
}
````

- [ ] **Étape 5 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesTest`
Expected : `OK (6 tests, …)`.

- [ ] **Étape 6 : commit**

```bash
git add inc/astra_styles.php inc/astra_polices.php tests/unit/StylesTest.php
git commit -m "Styles du Customizer : registre des options d'Astra et normalisation commune"
```

---

### Tâche 3 : écriture du CSS et insertion dans la page

**Fichiers :**
- Modifier : `inc/astra_styles.php` (ajout en fin de fichier)
- Modifier : `astra_fonctions.php`, `inclure/head-vars.html`
- Test : `tests/unit/StylesTest.php` (trois méthodes), `tests/integration/StylesRenduTest.php`

**Interfaces :**
- Produit :
  - `astra_styles_ecrire(array $regles): string` — règles `[media, sélecteur, [propriété ⇒ valeur]]` ;
  - `const ASTRA_STYLES_MODULES` — modules écrits, dans l'ordre d'émission d'Astra ; chaque module `inc/styles/<module>.php` définit `astra_styles_<module>(array $s, array $hfb): array` (règles) ; vide à cette tâche, complété aux tâches 4–9 ;
  - `astra_styles_css(array $styles, array $hfb): string` ;
  - filtre `astra_styles_inline(): string` (méta `astra/styles` et `astra/hfb`) ;
  - bloc `<style id="astra-styles-inline-css">` après `astra-hfb-inline-css`, émis s'il n'est pas vide.

- [ ] **Étape 1 : ajouter les tests unitaires** (dans `StylesTest`, avant l'accolade finale)

````php
	public function testRienSansReglage(): void {
		$this->assertSame('', astra_styles_css([], []));
		$this->assertSame('', astra_styles_css(['inconnue' => 'x', 'button-color' => 'red;}'], []));
	}

	public function testInjectionRefusee(): void {
		$tentatives = [';}body{display:none', '</style><script>', 'url(javascript:x)', 'expression(alert(1))'];
		foreach ($tentatives as $t) {
			$v = [
				'button-color' => $t,
				'font-family-button' => $t,
				'font-size-button' => ['desktop' => $t],
				'button-radius-fields' => ['desktop' => ['top' => $t]],
				'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => $t, 'background-image' => $t]],
				'font-extras-button' => ['letter-spacing' => $t, 'text-transform' => $t],
				'footer-social-1-color-type' => $t,
			];
			$css = astra_styles_css($v, []);
			foreach (['}body', '<', 'url(', 'expression('] as $interdit) {
				$this->assertStringNotContainsString($interdit, $css, $t);
			}
		}
	}

	public function testEcritureCommeAstraParseCss(): void {
		$this->assertSame(
			'a,b{color:red;top:0;}@media (max-width:921px){c{x:1;}d{y:2;}}e{z:3;}',
			astra_styles_ecrire([
				['', 'a, b', ['color' => 'red', 'vide' => '', 'top' => '0']],
				['', 'f', ['vide' => '']],
				['(max-width:921px)', 'c', ['x' => '1']],
				['(max-width:921px)', 'd', ['y' => '2']],
				['', 'e', ['z' => '3']],
			])
		);
	}
````

- [ ] **Étape 2 : écrire le test d'intégration**

````php
<?php

use PHPUnit\Framework\TestCase;

/** Styles du Customizer dans l'en-tête HTML : bloc émis seulement avec un réglage, après le builder. */
final class StylesRenduTest extends TestCase {

	protected function tearDown(): void {
		effacer_config('astra/styles');
		effacer_config('astra/hfb');
	}

	public function testRienSansReglage(): void {
		effacer_config('astra/styles');
		$html = recuperer_fond('inclure/head-vars', []);
		$this->assertStringNotContainsString('astra-styles-inline-css', $html);
		$this->assertStringNotContainsString('@font-face', $html);
	}

	public function testReglageInvalideSansBloc(): void {
		ecrire_config('astra/styles', ['button-color' => 'red;}body{display:none']);
		$this->assertStringNotContainsString('astra-styles-inline-css', recuperer_fond('inclure/head-vars', []));
	}
}
````

- [ ] **Étape 3 : vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesTest`
Expected : `Call to undefined function astra_styles_css()`.

- [ ] **Étape 4 : ajouter l'écriture et le point d'entrée** à la fin de `inc/astra_styles.php`

````php
/**
 * Écrit des règles [media, sélecteur, [propriété => valeur]] comme astra_parse_css() : valeur
 * vide retirée (0 gardé), règle sans déclaration retirée, media consécutives regroupées ; puis
 * espaces après les virgules retirés, comme Astra_Enqueue_Scripts::trim_css().
 */
function astra_styles_ecrire(array $regles): string {
	$css = '';
	$media = null;
	$bloc = '';
	foreach ($regles as [$m, $selecteur, $declarations]) {
		$d = '';
		foreach ($declarations as $propriete => $valeur) {
			if ($valeur !== '' && $valeur !== null) {
				$d .= $propriete . ':' . $valeur . ';';
			}
		}
		if ($d === '') {
			continue;
		}
		if ($m !== $media) {
			$css .= $media ? '@media ' . $media . '{' . $bloc . '}' : $bloc;
			[$media, $bloc] = [$m, ''];
		}
		$bloc .= $selecteur . '{' . $d . '}';
	}
	$css .= $media ? '@media ' . $media . '{' . $bloc . '}' : $bloc;
	return str_replace(', ', ',', $css);
}

/** Modules de groupe (inc/styles/<module>.php), dans l'ordre où Astra émet leur CSS. */
const ASTRA_STYLES_MODULES = ['bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux'];

/** CSS des styles du Customizer (vide sans réglage), dans l'ordre d'émission d'Astra. */
function astra_styles_css(array $styles, array $hfb): string {
	$s = astra_styles_normaliser($styles);
	if (!$s) {
		return '';
	}
	$regles = [];
	foreach (ASTRA_STYLES_MODULES as $module) {
		require_once __DIR__ . "/styles/$module.php";
		$regles = array_merge($regles, ('astra_styles_' . $module)($s, $hfb));
	}
	return astra_styles_ecrire($regles);
}
````

À cette tâche, la constante est vide : `const ASTRA_STYLES_MODULES = [];`. Les tâches 4 à 9 la complètent ; sa valeur finale est celle ci-dessus.

- [ ] **Étape 5 : déclarer le filtre** dans `astra_fonctions.php`

Après `include_once __DIR__ . '/inc/astra_pages.php';` :
```php
include_once __DIR__ . '/inc/astra_styles.php';
```
Avant le bloc `/** … Mode du menu mobile … */` :
````php
/**
 * CSS des styles du Customizer (méta astra/styles) : vide sans réglage.
 */
function astra_styles_inline(): string {
	include_spip('inc/config');
	$styles = lire_config('astra/styles');
	$hfb = lire_config('astra/hfb');
	return astra_styles_css(is_array($styles) ? $styles : [], is_array($hfb) ? $hfb : []);
}
````

- [ ] **Étape 6 : insérer le bloc** dans `inclure/head-vars.html`

Remplacer la fin du fichier :
```html
[<style id="astra-hfb-inline-css">
(#VAL|astra_hfb_css)
</style>]
```
par :
```html
[<style id="astra-hfb-inline-css">
(#VAL|astra_hfb_css)
</style>][<style id="astra-styles-inline-css">
(#VAL|astra_styles_inline)
</style>]
```

- [ ] **Étape 7 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Expected : tout passe, dont `StylesTest` (9 tests) et `StylesRenduTest` (2 tests).

- [ ] **Étape 8 : critère 1, rendu inchangé sans réglage**

Comparer le rendu avec celui de `main` (dépôt principal, SPIP de test pointant sur `main`) :
```bash
for d in <dépôt principal> "$PWD"; do (cd "$d" && php -r 'require "tests/bootstrap_integration.php"; effacer_config("astra/styles"); foreach (["inclure/head-vars","inclure/header","inclure/footer","sommaire","article"] as $f) { echo "=== $f\n", recuperer_fond($f, ["type_page"=>"sommaire","id_article"=>1]); }' | sed -E 's/\?[0-9]{10}"/"/g' > "/tmp/rendu-$(basename "$d").html"); done
diff /tmp/rendu-<nom du dépôt principal>.html "/tmp/rendu-$(basename "$PWD").html" && echo IDENTIQUE
```
Expected : `IDENTIQUE`.

- [ ] **Étape 9 : commit**

```bash
git add inc/astra_styles.php astra_fonctions.php inclure/head-vars.html tests/unit/StylesTest.php tests/integration/StylesRenduTest.php
git commit -m "Styles du Customizer : écriture du CSS comme astra_parse_css, bloc astra-styles-inline-css"
```

---

### Tâche 4 : groupe menu

**Fichiers :**
- Créer : `inc/styles/menu.php`
- Modifier : `inc/astra_styles.php` (`ASTRA_STYLES_MODULES`)
- Test : `tests/unit/StylesAttendusTest.php`

**Interfaces :**
- Consomme : aides de la tâche 2, `ASTRA_STYLES_MODULES` (tâche 3).
- Produit : `astra_styles_menu(array $s, array $hfb): array` ; `StylesAttendusTest` (test d'équivalence de tous les groupes dont le module est déclaré).

- [ ] **Étape 1 : écrire le test d'équivalence**

````php
<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles.php';

/**
 * Équivalence avec WordPress : pour chaque groupe, les règles produites contiennent toutes les
 * règles qu'Astra 4.11.0 ajoute ou modifie (tests/references/styles/attendus/<groupe>.css,
 * capturées par capturer.sh), et leurs autres règles sont soit identiques à des règles du CSS
 * de base (attendus/base.css), soit des neutralisations déclarées (<groupe>.neutralisations.css).
 */
final class StylesAttendusTest extends TestCase {

	/** Éléments placés comme dans la référence (appliquer.php). */
	private const HFB = [
		'entete_ordinateur' => ['primary' => ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'button-1']]],
		'pied' => [
			'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']],
			'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']],
		],
		'rangees' => ['pied_primary' => ['colonnes' => 4, 'repartition' => '4-equal'], 'pied_below' => ['colonnes' => 2, 'repartition' => '2-equal']],
	];

	/** Règles d'un CSS, au format de regles.py : « [@media …] sélecteur{déclarations} ». */
	public static function regles(string $css): array {
		preg_match_all('/@media[^{]*\{|[^{}]+\{[^{}]*\}|\}/', $css, $m);
		$out = [];
		$ctx = '';
		foreach ($m[0] as $t) {
			$t = trim($t);
			if (strpos($t, '@media') === 0) {
				$ctx = trim(substr($t, 6, -1));
				continue;
			}
			if ($t === '}') {
				$ctx = '';
				continue;
			}
			[$sel, $dec] = explode('{', $t, 2);
			$out[] = ($ctx !== '' ? "[@media $ctx] " : '') . trim($sel) . '{' . trim(rtrim($dec, '}')) . '}';
		}
		return $out;
	}

	private static function lignes(string $fichier): array {
		return is_file($fichier) ? array_values(array_filter(array_map('trim', file($fichier)))) : [];
	}

	/** Groupes dont le module est écrit (ASTRA_STYLES_MODULES), par nom de fichier de référence. */
	public static function groupes(): array {
		$groupes = [];
		foreach (['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'] as $g) {
			if (in_array(str_replace('-', '_', $g), ASTRA_STYLES_MODULES, true)) {
				$groupes[$g] = [$g];
			}
		}
		return $groupes;
	}

	#[DataProvider('groupes')]
	public function testEquivalenceAvecWordPress(string $groupe): void {
		$dir = dirname(__DIR__) . '/references/styles';
		$valeurs = json_decode(file_get_contents("$dir/valeurs/$groupe.json"), true);
		$nous = self::regles(astra_styles_css($valeurs, self::HFB));
		$attendues = self::lignes("$dir/attendus/$groupe.css");
		$this->assertNotEmpty($attendues);
		$this->assertSame([], array_values(array_diff($attendues, $nous)), 'règles d\'Astra manquantes');
		$base = array_flip(self::regles(file_get_contents("$dir/attendus/base.css")));
		$neutres = array_flip(self::lignes("$dir/attendus/$groupe.neutralisations.css"));
		$autres = array_filter(array_diff($nous, $attendues), function ($r) use ($base, $neutres) {
			return !isset($base[$r]) && !isset($neutres[$r]);
		});
		$this->assertSame([], array_values($autres), 'règles ni attendues, ni de base, ni neutralisations');
	}

	public function testGroupeAbsentSansElementPlace(): void {
		// Astra n'émet le CSS d'un élément du builder que s'il est placé (is_component_loaded()).
		$valeurs = json_decode(file_get_contents(dirname(__DIR__) . '/references/styles/valeurs/menu.json'), true);
		$sans_menu = ['entete_ordinateur' => ['primary' => ['primary_right' => []]]];
		$this->assertSame('', astra_styles_css($valeurs, $sans_menu));
		$this->assertNotSame('', astra_styles_css($valeurs, []), 'menu-1 est placé par défaut (astra_hfb_defauts)');
	}
}
````

- [ ] **Étape 2 : déclarer le module et vérifier l'échec**

Dans `inc/astra_styles.php` : `const ASTRA_STYLES_MODULES = ['menu'];`
Run : `vendor/bin/phpunit --testsuite unit --filter StylesAttendusTest`
Expected : erreur, `inc/styles/menu.php` introuvable.

- [ ] **Étape 3 : écrire le module**

````php
<?php
/**
 * Astra pour SPIP — menus de l'en-tête (inc/builder/type/header/menu/dynamic-css/dynamic.css.php
 * d'Astra 4.11.0, règles ordinateur) : couleurs normale, au survol et de la page courante, fonds,
 * sous-menus, police.
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_menu(array $s, array $hfb): array {
	$regles = [];
	foreach ([1, 2] as $n) {
		$p = "header-menu$n";
		if (!astra_styles_pose($s, 'menu', "$p-") || !astra_styles_element_place("menu-$n", $hfb, 'entete')) {
			continue;
		}
		$sel = ".ast-builder-menu-$n";
		$extras = astra_styles_extras($s["$p-font-extras"] ?? []);
		$famille = astra_styles_ou($s, "$p-font-family", 'inherit');
		$couleur = astra_styles_ou($s, "$p-color-responsive", 'var(--ast-global-color-3)');
		$survol = astra_styles_ou($s, "$p-h-color-responsive", 'var(--ast-global-color-1)');
		$actif = astra_styles_ou($s, "$p-a-color-responsive", 'var(--ast-global-color-1)');
		$bordure = $s["$p-submenu-border"] ?? ['top' => 2, 'bottom' => 0, 'left' => 0, 'right' => 0];
		$cote = function (string $c) use ($bordure): string {
			return (int) ($bordure[$c] ?? 0) . 'px';
		};
		array_push($regles,
			['', $sel, [
				'font-family' => $famille === 'inherit' ? 'inherit' : astra_styles_famille($famille),
				'font-weight' => astra_styles_ou($s, "$p-font-weight", 'inherit'),
				'text-transform' => $extras['text-transform'],
			]],
			['', "$sel .menu-item > .menu-link", [
				'line-height' => $extras['line-height'], 'font-size' => astra_styles_taille_police($s["$p-font-size"] ?? ''),
				'color' => $couleur, 'text-decoration' => $extras['text-decoration'], 'letter-spacing' => $extras['letter-spacing'],
			]],
			['', "$sel .menu-item > .ast-menu-toggle", ['color' => $couleur]],
			['', "$sel .menu-item:hover > .menu-link,$sel .inline-on-mobile .menu-item:hover > .ast-menu-toggle", [
				'color' => $survol, 'background' => astra_styles_ou($s, "$p-h-bg-color-responsive"),
			]],
			['', "$sel .menu-item:hover > .ast-menu-toggle", ['color' => $survol]],
			['', "$sel .menu-item.current-menu-item > .menu-link,$sel .inline-on-mobile .menu-item.current-menu-item > .ast-menu-toggle,$sel .current-menu-ancestor > .menu-link", [
				'color' => $actif, 'background' => astra_styles_ou($s, "$p-a-bg-color-responsive"),
			]],
			['', "$sel .menu-item.current-menu-item > .ast-menu-toggle", ['color' => $actif]],
			['', "$sel .sub-menu,$sel .inline-on-mobile .sub-menu", [
				'border-top-width' => $cote('top'), 'border-bottom-width' => $cote('bottom'),
				'border-right-width' => $cote('right'), 'border-left-width' => $cote('left'),
				'border-color' => astra_styles_ou($s, "$p-submenu-b-color", 'var(--ast-global-color-0)'), 'border-style' => 'solid',
			]],
			['', "$sel .main-header-menu,$sel .main-header-menu .sub-menu", astra_styles_fond($s["$p-bg-obj-responsive"] ?? [])]
		);
	}
	return $regles;
}
````

- [ ] **Étape 4 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit`
Expected : tout passe ; `StylesAttendusTest` : équivalence `menu` et groupe absent sans élément placé.

- [ ] **Étape 5 : commit**

```bash
git add inc/styles/menu.php inc/astra_styles.php tests/unit/StylesAttendusTest.php
git commit -m "Styles du Customizer : menus de l'en-tête (couleurs, survol, page courante, sous-menus, fond)"
```

---

### Tâche 5 : groupe bouton (et sa neutralisation)

**Fichiers :**
- Créer : `inc/styles/bouton.php`
- Modifier : `inc/astra_styles.php` (`ASTRA_STYLES_MODULES`)
- Test : `tests/integration/StylesRenduTest.php` (une méthode)

**Interfaces :**
- Produit : `astra_styles_bouton(array $s): array` (appelé avec `$hfb` en plus, ignoré : Astra émet toujours ces règles). Sélecteurs du bouton global copiés à l'identique du CSS rendu par Astra (constantes `ASTRA_STYLES_SEL_*`).

- [ ] **Étape 1 : déclarer le module** — `const ASTRA_STYLES_MODULES = ['bouton', 'menu'];`

- [ ] **Étape 2 : ajouter le test d'intégration** (dans `StylesRenduTest`)

````php
	public function testBlocApresLeBuilder(): void {
		ecrire_config('astra/hfb', ['rangees' => ['entete_primary' => ['hauteur' => 100]]]);
		ecrire_config('astra/styles', ['button-color' => '#123456']);
		$html = recuperer_fond('inclure/head-vars', []);
		$builder = strpos($html, 'id="astra-hfb-inline-css"');
		$styles = strpos($html, 'id="astra-styles-inline-css"');
		$this->assertNotFalse($builder);
		$this->assertNotFalse($styles);
		$this->assertGreaterThan($builder, $styles);
		$this->assertStringContainsString('.wp-block-button .wp-block-button__link{color:#123456;}', $html);
	}
````

- [ ] **Étape 3 : vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesAttendusTest`
Expected : erreur, `inc/styles/bouton.php` introuvable.

- [ ] **Étape 4 : écrire le module**

````php
<?php
/**
 * Astra pour SPIP — styles du bouton global (inc/class-astra-dynamic-css.php d'Astra 4.11.0,
 * « Button Styling ») : couleurs, survol, bordure, rayon, marges, police.
 *
 * Astra émet toujours ces règles ; css/astra-dynamic.css en contient la version par défaut. Ici,
 * elles sont réémises, avec les valeurs réglées, dès qu'une option du bouton est posée.
 *
 * @license GPL-3.0-or-later
 */

const ASTRA_STYLES_SEL_CONTOUR_SURVOL_BLOC = '.wp-block-button.is-style-outline .wp-block-button__link:hover,.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link:focus,.wp-block-buttons .wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color):hover,.wp-block-buttons .wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color):hover';
const ASTRA_STYLES_SEL_CONTOUR_SURVOL = '.wp-block-button.is-style-outline .wp-block-button__link:hover,.wp-block-buttons .wp-block-button.is-style-outline .wp-block-button__link:focus,.wp-block-buttons .wp-block-button.is-style-outline > .wp-block-button__link:not(.has-text-color):hover,.wp-block-buttons .wp-block-button.wp-block-button__link.is-style-outline:not(.has-text-color):hover,.ast-outline-button:hover,.ast-outline-button:focus,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button:hover,.wp-block-uagb-buttons-child .uagb-buttons-repeater.ast-outline-button:focus';
const ASTRA_STYLES_SEL_BLOC_LIEN = '.wp-block-button .wp-block-button__link';
const ASTRA_STYLES_SEL_BLOC_LIEN_SURVOL = '.wp-block-button .wp-block-button__link:hover,.wp-block-button .wp-block-button__link:focus';
const ASTRA_STYLES_SEL_BLOCS = '.wp-block-button .wp-block-button__link,.wp-block-search .wp-block-search__button,body .wp-block-file .wp-block-file__button';
const ASTRA_STYLES_SEL_BOUTONS = '.menu-toggle,button,.ast-button,.ast-custom-button,.button,input#submit,input[type="button"],input[type="submit"],input[type="reset"],#comments .submit,.search .search-submit,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button,body .wp-block-file .wp-block-file__button,.search .search-submit,.woocommerce-js a.button,.woocommerce button.button,.woocommerce .woocommerce-message a.button,.woocommerce #respond input#submit.alt,.woocommerce input.button.alt,.woocommerce input.button,.woocommerce input.button:disabled,.woocommerce input.button:disabled[disabled],.woocommerce input.button:disabled:hover,.woocommerce input.button:disabled[disabled]:hover,.woocommerce #respond input#submit,.woocommerce button.button.alt.disabled,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link,.wc-block-grid__product-onsale,[CLASS*="wc-block"] button,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping),.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout,.woocommerce button.button.alt.disabled.wc-variation-selection-needed,[CLASS*="wc-block"] .wc-block-components-button';
const ASTRA_STYLES_SEL_BOUTONS_SURVOL = 'button:focus,.menu-toggle:hover,button:hover,.ast-button:hover,.ast-custom-button:hover .button:hover,.ast-custom-button:hover ,input[type=reset]:hover,input[type=reset]:focus,input#submit:hover,input#submit:focus,input[type="button"]:hover,input[type="button"]:focus,input[type="submit"]:hover,input[type="submit"]:focus,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button:hover,form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button:focus,body .wp-block-file .wp-block-file__button:hover,body .wp-block-file .wp-block-file__button:focus,.woocommerce-js a.button:hover,.woocommerce button.button:hover,.woocommerce .woocommerce-message a.button:hover,.woocommerce #respond input#submit:hover,.woocommerce #respond input#submit.alt:hover,.woocommerce input.button.alt:hover,.woocommerce input.button:hover,.woocommerce button.button.alt.disabled:hover,.wc-block-grid__products .wc-block-grid__product .wp-block-button__link:hover,[CLASS*="wc-block"] button:hover,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons .button:not(.checkout):not(.ast-continue-shopping):hover,.woocommerce-js .astra-cart-drawer .astra-cart-drawer-content .woocommerce-mini-cart__buttons a.checkout:hover,.woocommerce button.button.alt.disabled.wc-variation-selection-needed:hover,[CLASS*="wc-block"] .wc-block-components-button:hover,[CLASS*="wc-block"] .wc-block-components-button:focus';
const ASTRA_STYLES_SEL_RECHERCHE = 'form[CLASS*="wp-block-search__"].wp-block-search .wp-block-search__inside-wrapper .wp-block-search__button.has-icon';

function astra_styles_bouton(array $s): array {
	if (!astra_styles_pose($s, 'bouton')) {
		return [];
	}
	$couleur = astra_styles_ou($s, 'button-color', '#ffffff');
	$survol = astra_styles_ou($s, 'button-h-color', '#ffffff');
	$fond = astra_styles_ou($s, 'button-bg-color', 'var(--ast-global-color-0)');
	$fond_survol = astra_styles_ou($s, 'button-bg-h-color', 'var(--ast-global-color-1)');
	$famille = astra_styles_famille($s['font-family-button'] ?? '', 'inherit');
	$graisse = astra_styles_ou($s, 'font-weight-button', '500');
	$taille = astra_styles_taille_police($s['font-size-button'] ?? '', 16);
	$extras = astra_styles_extras($s['font-extras-button'] ?? []);
	$interligne = $extras['line-height'] !== '' ? $extras['line-height'] : '1em';
	$marge = [];
	foreach (['top' => 15, 'right' => 30, 'bottom' => 15, 'left' => 30] as $cote => $defaut) {
		$marge[$cote] = astra_styles_cote($s['theme-button-padding'] ?? '', $cote, $defaut);
	}
	$rayon = [];
	foreach (['top', 'right', 'bottom', 'left'] as $cote) {
		$rayon[$cote] = astra_styles_cote($s['button-radius-fields'] ?? '', $cote);
	}
	$bordure = $s['theme-button-border-group-border-size'] ?? null;
	$epaisseur = function (string $cote) use ($bordure): string {
		return astra_styles_cote($bordure ?? '', $cote) ?: '0';
	};
	$survols = ['color' => $survol, 'background-color' => $fond_survol, 'border-color' => $fond_survol];
	$rayons = [
		'border-top-left-radius' => $rayon['top'], 'border-top-right-radius' => $rayon['right'],
		'border-bottom-right-radius' => $rayon['bottom'], 'border-bottom-left-radius' => $rayon['left'],
	];
	$marges = [
		'padding-top' => $marge['top'], 'padding-right' => $marge['right'],
		'padding-bottom' => $marge['bottom'], 'padding-left' => $marge['left'],
	];
	$epaisseurs = [
		'border-style' => 'solid', 'border-top-width' => $epaisseur('top'), 'border-right-width' => $epaisseur('right'),
		'border-left-width' => $epaisseur('left'), 'border-bottom-width' => $epaisseur('bottom'),
	];
	$regles = [
		['', ASTRA_STYLES_SEL_CONTOUR_SURVOL_BLOC, $survols],
		['', ASTRA_STYLES_SEL_CONTOUR_SURVOL, $survols],
		['', ASTRA_STYLES_SEL_BLOC_LIEN, ['color' => $couleur]],
		['', ASTRA_STYLES_SEL_BLOC_LIEN_SURVOL, $survols],
		['', ASTRA_STYLES_SEL_BLOCS, ($bordure !== null ? $epaisseurs : []) + [
			'border-color' => $fond, 'background-color' => $fond, 'color' => $couleur,
			'font-family' => $famille, 'font-weight' => $graisse, 'line-height' => $interligne,
			'text-transform' => $extras['text-transform'], 'letter-spacing' => $extras['letter-spacing'], 'font-size' => $taille,
		] + $rayons + $marges],
		['', ASTRA_STYLES_SEL_BOUTONS, $epaisseurs + [
			'color' => $couleur, 'border-color' => $fond, 'background-color' => $fond,
		] + $marges + [
			'font-family' => $famille, 'font-weight' => $graisse, 'font-size' => $taille, 'line-height' => $interligne,
			'text-transform' => $extras['text-transform'], 'letter-spacing' => $extras['letter-spacing'],
		] + $rayons],
		['', ASTRA_STYLES_SEL_BOUTONS_SURVOL, $survols],
		['', ASTRA_STYLES_SEL_RECHERCHE, [
			'padding-top' => "calc({$marge['top']} - 3px)", 'padding-right' => "calc({$marge['right']} - 3px)",
			'padding-bottom' => "calc({$marge['bottom']} - 3px)", 'padding-left' => "calc({$marge['left']} - 3px)",
		]],
	];
	// Neutralisation : marges réglées pour l'ordinateur seul, Astra n'émet plus ses marges tablette
	// et mobile par défaut (14/28, 12/24), présentes dans css/astra-dynamic.css : celles de
	// l'ordinateur s'appliquent alors à tous les appareils.
	$padding = $s['theme-button-padding'] ?? [];
	if (astra_styles_renseigne(astra_styles_ordi($padding))) {
		foreach (['tablet' => '(max-width:921px)', 'mobile' => '(max-width:544px)'] as $app => $media) {
			if (!astra_styles_renseigne($padding[$app] ?? '')) {
				$regles[] = [$media, ASTRA_STYLES_SEL_BLOCS, $marges];
				$regles[] = [$media, ASTRA_STYLES_SEL_BOUTONS, $marges];
			}
		}
	}
	return $regles;
}
````

- [ ] **Étape 5 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter StylesRendu`
Expected : tout passe ; équivalence `bouton` (8 règles attendues ; marges tablette et mobile = neutralisations déclarées).

- [ ] **Étape 6 : commit**

```bash
git add inc/styles/bouton.php inc/astra_styles.php tests/integration/StylesRenduTest.php
git commit -m "Styles du Customizer : bouton global (couleurs, survol, bordure, rayon, marges, police)"
```

---

### Tâche 6 : groupe bouton de l'en-tête

**Fichiers :**
- Créer : `inc/styles/bouton_entete.php`
- Modifier : `inc/astra_styles.php` — `const ASTRA_STYLES_MODULES = ['bouton', 'menu', 'bouton_entete'];`

**Interfaces :**
- Produit : `astra_styles_bouton_entete(array $s, array $hfb): array` (règles seulement si `button-1` est placé).

- [ ] **Étape 1 : déclarer le module et vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesAttendusTest`
Expected : erreur, `inc/styles/bouton_entete.php` introuvable.

- [ ] **Étape 2 : écrire le module**

````php
<?php
/**
 * Astra pour SPIP — bouton de l'en-tête (inc/builder/type/base/dynamic-css/button/
 * class-astra-button-component-dynamic-css.php d'Astra 4.11.0, règles ordinateur).
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_bouton_entete(array $s, array $hfb): array {
	$p = 'header-button1';
	if (!astra_styles_pose($s, 'bouton-entete') || !astra_styles_element_place('button-1', $hfb, 'entete')) {
		return [];
	}
	$sel = '.ast-header-button-1';
	$extras = astra_styles_extras($s["$p-font-extras"] ?? []);
	$graisse = astra_styles_ou($s, "$p-font-weight");
	$bordure = $s["$p-border-size"] ?? '';
	// Astra : 40 px par défaut ; une fois l'option enregistrée, ses valeurs, même vides.
	$rayons = $s["$p-border-radius-fields"] ?? ['desktop' => ['top' => 40, 'right' => 40, 'bottom' => 40, 'left' => 40], 'desktop-unit' => 'px'];
	$rayon = [];
	foreach (['top', 'right', 'bottom', 'left'] as $cote) {
		$rayon[$cote] = astra_styles_cote($rayons, $cote);
	}
	$regles = [
		['', $sel . '[data-section*="section-hb-button-"] .ast-builder-button-wrap .ast-custom-button', [
			'font-family' => astra_styles_famille($s["$p-font-family"] ?? ''),
			'font-weight' => $graisse === 'inherit' ? '' : $graisse,
			'font-size' => astra_styles_taille_police($s["$p-font-size"] ?? ''),
		] + $extras],
		['', "$sel .ast-custom-button", [
			'color' => astra_styles_ou($s, "$p-text-color"), 'background' => astra_styles_ou($s, "$p-back-color"),
			'border-color' => astra_styles_ou($s, "$p-border-color"),
			'border-top-width' => astra_styles_cote($bordure, 'top'), 'border-bottom-width' => astra_styles_cote($bordure, 'bottom'),
			'border-left-width' => astra_styles_cote($bordure, 'left'), 'border-right-width' => astra_styles_cote($bordure, 'right'),
			'border-top-left-radius' => $rayon['top'], 'border-top-right-radius' => $rayon['right'],
			'border-bottom-right-radius' => $rayon['bottom'], 'border-bottom-left-radius' => $rayon['left'],
		]],
		['', "$sel .ast-custom-button:hover", [
			'color' => astra_styles_ou($s, "$p-text-h-color"), 'background' => astra_styles_ou($s, "$p-back-h-color"),
			'border-color' => astra_styles_ou($s, "$p-border-h-color"),
		]],
	];
	// Neutralisation : rayons enregistrés vides, Astra n'émet plus les 40 px par défaut que
	// css/astra-hfb.css contient ; le bouton prend alors le rayon du bouton global (0 sans réglage).
	foreach ($rayon as $cote => $v) {
		if ($v === '') {
			$rayon[$cote] = astra_styles_cote($s['button-radius-fields'] ?? '', $cote) ?: '0';
		}
	}
	if (isset($s["$p-border-radius-fields"]) && !astra_styles_renseigne(astra_styles_ordi($rayons))) {
		$regles[] = ['', "$sel .ast-custom-button", [
			'border-top-left-radius' => $rayon['top'], 'border-top-right-radius' => $rayon['right'],
			'border-bottom-right-radius' => $rayon['bottom'], 'border-bottom-left-radius' => $rayon['left'],
		]];
	}
	return $regles;
}
````

- [ ] **Étape 3 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit`
Expected : tout passe ; équivalence `bouton-entete` (rayons enregistrés vides → neutralisation déclarée).

- [ ] **Étape 4 : commit**

```bash
git add inc/styles/bouton_entete.php inc/astra_styles.php
git commit -m "Styles du Customizer : bouton de l'en-tête (couleurs, survol, bordure, rayon, police)"
```

---

### Tâche 7 : groupe identité (CSS)

Le balisage (titre et slogan imprimés selon les options d'Astra) vient à la tâche 11.

**Fichiers :**
- Créer : `inc/styles/identite.php`
- Modifier : `inc/astra_styles.php` — `const ASTRA_STYLES_MODULES = ['bouton', 'identite', 'menu', 'bouton_entete'];`

**Interfaces :**
- Produit : `astra_styles_identite(array $s): array`.

- [ ] **Étape 1 : déclarer le module et vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesAttendusTest`
Expected : erreur, `inc/styles/identite.php` introuvable.

- [ ] **Étape 2 : écrire le module**

````php
<?php
/**
 * Astra pour SPIP — identité du site (inc/class-astra-dynamic-css.php d'Astra 4.11.0) : couleurs
 * et tailles du titre et du slogan, affichage, largeur du logo.
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_identite(array $s): array {
	if (!astra_styles_pose($s, 'identite')) {
		return [];
	}
	$visible = function (string $option, bool $defaut) use ($s): string {
		$v = astra_styles_ordi($s[$option] ?? '');
		return ($v === '' || $v === null ? $defaut : (bool) $v) ? 'block' : 'none';
	};
	$titre = $visible('display-site-title-responsive', true);
	$slogan = $visible('display-site-tagline-responsive', false);
	$logo = astra_styles_ou($s, 'ast-header-responsive-logo-width');
	$largeur = $logo !== '' ? $logo . 'px' : '';
	$regles = [
		['', '.ast-site-identity .site-title a', ['color' => astra_styles_ou($s, 'header-color-site-title', 'var(--ast-global-color-2)')]],
		['', '.ast-site-identity .site-title a:hover', ['color' => astra_styles_ou($s, 'header-color-h-site-title')]],
		['', '.ast-site-identity .site-description', ['color' => astra_styles_ou($s, 'header-color-site-tagline')]],
		['', '.site-title', ['font-size' => astra_styles_taille_police($s['font-size-site-title'] ?? '', 26), 'display' => $titre]],
		['', 'header .custom-logo-link img', ['max-width' => $largeur, 'width' => $largeur]],
		['', '.astra-logo-svg', ['width' => $largeur]],
		['', '.site-header .site-description', ['font-size' => astra_styles_taille_police($s['font-size-site-tagline'] ?? '', 15), 'display' => $slogan]],
	];
	// Seule la valeur ordinateur est exploitée : l'affichage vaut pour tous les appareils.
	foreach (['(max-width:921px)', '(max-width:544px)'] as $media) {
		$regles[] = [$media, '.site-title', ['display' => $titre]];
		$regles[] = [$media, '.site-header .site-description', ['display' => $slogan]];
	}
	return $regles;
}
````

- [ ] **Étape 3 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit`
Expected : tout passe ; équivalence `identite`.

- [ ] **Étape 4 : commit**

```bash
git add inc/styles/identite.php inc/astra_styles.php
git commit -m "Styles du Customizer : identité du site (couleurs, tailles, affichage, largeur du logo)"
```

---

### Tâche 8 : groupe pied (widgets et copyright)

**Fichiers :**
- Créer : `inc/styles/pied.php`
- Modifier : `inc/astra_styles.php` — `const ASTRA_STYLES_MODULES = ['bouton', 'identite', 'menu', 'bouton_entete', 'pied'];`

**Interfaces :**
- Produit : `astra_styles_pied(array $s, array $hfb): array` (widget N seulement si `widget-N` est placé dans le pied ; copyright si `copyright` l'est).

- [ ] **Étape 1 : déclarer le module et vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesAttendusTest`
Expected : erreur, `inc/styles/pied.php` introuvable.

- [ ] **Étape 2 : écrire le module**

````php
<?php
/**
 * Astra pour SPIP — pied de page : widgets (inc/builder/type/base/dynamic-css/widget/
 * class-astra-widget-component-dynamic-css.php) et copyright (inc/builder/type/footer/copyright/
 * dynamic-css/dynamic.css.php) d'Astra 4.11.0, règles ordinateur.
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_pied(array $s, array $hfb): array {
	$regles = [];
	for ($n = 1; $n <= 4; $n++) {
		$p = "footer-widget-$n";
		if (!astra_styles_pose($s, 'pied', "$p-") || !astra_styles_element_place("widget-$n", $hfb, 'pied')) {
			continue;
		}
		$sel = '.footer-widget-area[data-section="sidebar-widgets-footer-widget-' . $n . '"]';
		$titres = ["$sel .widget-title"];
		for ($h = 1; $h <= 6; $h++) {
			array_push($titres, "$sel h$h", "$sel .widget-area h$h");
		}
		array_push($regles,
			['', "$sel.footer-widget-area-inner", [
				'color' => astra_styles_ou($s, "$p-color"), 'font-size' => astra_styles_taille_police($s["$p-content-font-size"] ?? ''),
			]],
			['', "$sel.footer-widget-area-inner a", ['color' => astra_styles_ou($s, "$p-link-color")]],
			['', "$sel.footer-widget-area-inner a:hover", ['color' => astra_styles_ou($s, "$p-link-h-color")]],
			['', implode(',', $titres), [
				'color' => astra_styles_ou($s, "$p-title-color"), 'font-size' => astra_styles_taille_police($s["$p-font-size"] ?? ''),
			]]
		);
	}
	$copyright = ['footer-copyright-color' => 1, 'font-size-section-footer-copyright' => 1];
	if (array_intersect_key($s, $copyright) && astra_styles_element_place('copyright', $hfb, 'pied')) {
		array_push($regles,
			['', '.ast-footer-copyright', ['color' => astra_styles_ou($s, 'footer-copyright-color', 'var(--ast-global-color-3)')]],
			['', '.ast-footer-copyright', ['font-size' => astra_styles_taille_police($s['font-size-section-footer-copyright'] ?? '', 16)]]
		);
	}
	return $regles;
}
````

- [ ] **Étape 3 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit`
Expected : tout passe ; équivalence `pied`.

- [ ] **Étape 4 : commit**

```bash
git add inc/styles/pied.php inc/astra_styles.php
git commit -m "Styles du Customizer : widgets et copyright du pied"
```

---

### Tâche 9 : groupe icônes sociales

**Fichiers :**
- Créer : `inc/styles/sociaux.php`
- Modifier : `inc/astra_styles.php` — `const ASTRA_STYLES_MODULES = ['bouton', 'identite', 'menu', 'bouton_entete', 'pied', 'sociaux'];` (valeur finale)

**Interfaces :**
- Produit : `astra_styles_sociaux(array $s, array $hfb): array` (en-tête et pied ; couleurs seulement si `-color-type` vaut `custom`).

- [ ] **Étape 1 : déclarer le module et vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesAttendusTest`
Expected : erreur, `inc/styles/sociaux.php` introuvable.

- [ ] **Étape 2 : écrire le module**

````php
<?php
/**
 * Astra pour SPIP — icônes sociales (inc/builder/type/base/dynamic-css/social/
 * class-astra-social-component-dynamic-css.php d'Astra 4.11.0, règles ordinateur).
 *
 * @license GPL-3.0-or-later
 */

function astra_styles_sociaux(array $s, array $hfb): array {
	$regles = [];
	foreach (['header' => 'entete', 'footer' => 'pied'] as $b => $builder) {
		$p = "$b-social-1";
		if (!astra_styles_pose($s, 'sociaux', "$p-") || !astra_styles_element_place('social-icons-1', $hfb, $builder)) {
			continue;
		}
		$sel = ".ast-$b-social-1-wrap";
		$espace = astra_styles_ou($s, "$p-space");
		$marge = $espace !== '' ? ((float) $espace / 2) . 'px' : '';
		$taille = astra_styles_ou($s, "$p-size", 18) . 'px';
		$couleur = astra_styles_ou($s, "$p-color");
		$survol = astra_styles_ou($s, "$p-h-color");
		$regles[] = ['', "$sel .ast-builder-social-element,$sel .social-show-label-true .ast-builder-social-element", ['margin-left' => $marge, 'margin-right' => $marge]];
		$regles[] = ['', "$sel .ast-builder-social-element svg", ['width' => $taille, 'height' => $taille]];
		if (($s["$p-color-type"] ?? 'custom') === 'custom') {
			array_push($regles,
				['', "$sel .ast-social-color-type-custom svg", ['fill' => $couleur]],
				['', "$sel .ast-social-color-type-custom .ast-builder-social-element:hover", ['color' => $survol]],
				['', "$sel .ast-social-color-type-custom .ast-builder-social-element:hover svg", ['fill' => $survol]],
				['', "$sel .ast-social-color-type-custom .social-item-label", ['color' => $couleur]]
			);
		}
		$regles[] = ['', "$sel .ast-builder-social-element:hover .social-item-label", ['color' => $survol]];
	}
	return $regles;
}
````

- [ ] **Étape 3 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Expected : tout passe ; `StylesAttendusTest` : six groupes équivalents.

- [ ] **Étape 4 : commit**

```bash
git add inc/styles/sociaux.php inc/astra_styles.php
git commit -m "Styles du Customizer : icônes sociales (taille, espacement, couleurs personnalisées)"
```

---

### Tâche 10 : neuvième couleur en rgba()

**Fichiers :**
- Modifier : `astra_fonctions.php` (`astra_couleur`), `formulaires/configurer_astra.php`, `formulaires/configurer_astra.html`, `lang/astra_fr.php`, `lang/astra_en.php`
- Test : `tests/integration/PaletteTest.php`

**Interfaces :**
- Consomme : `astra_styles_couleur_valide()` (tâche 2).
- Produit : `astra_couleur()` accepte `rgb()` et `rgba()` ; erreur `couleur_8` dans `formulaires_configurer_astra_verifier_dist()`.

- [ ] **Étape 1 : écrire le test**

````php
<?php

use PHPUnit\Framework\TestCase;

/** Neuvième couleur de la palette : rgb() et rgba() acceptés, comme global-color-palette d'Astra. */
final class PaletteTest extends TestCase {

	public static function setUpBeforeClass(): void {
		include_spip('formulaires/configurer_astra');
	}

	protected function tearDown(): void {
		effacer_config('astra/couleur_8');
		set_request('couleur_8', null);
	}

	public function testCouleurRgbaDansLaPalette(): void {
		ecrire_config('astra/couleur_8', 'rgba(64,191,79,0.15)');
		$this->assertStringContainsString('--ast-global-color-8:rgba(64,191,79,0.15);', recuperer_fond('inclure/head-vars', []));
	}

	/** Repli d'astra_couleur() dans head-vars.html, inchangé. */
	public function testCouleurInvalideRemplaceeParLeRepli(): void {
		ecrire_config('astra/couleur_8', 'rgba(300,0,0,1)');
		$this->assertStringContainsString('--ast-global-color-8:#000000;', recuperer_fond('inclure/head-vars', []));
	}

	public function testVerifierRefuseUneCouleurInvalide(): void {
		set_request('couleur_8', 'vert');
		$this->assertArrayHasKey('couleur_8', formulaires_configurer_astra_verifier_dist());
		set_request('couleur_8', 'rgba(64, 191, 79, 0.15)');
		$this->assertArrayNotHasKey('couleur_8', formulaires_configurer_astra_verifier_dist());
	}

	public function testChampTexteSeulementPourLaNeuvieme(): void {
		$html = recuperer_fond('formulaires/configurer_astra', ['editable' => true]);
		$this->assertMatchesRegularExpression('/<input type="text"[^>]*name="couleur_8"/', $html);
		$this->assertMatchesRegularExpression('/<input type="color"[^>]*name="couleur_0"/', $html);
	}
}
````

- [ ] **Étape 2 : vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter PaletteTest`
Expected : échecs de `testCouleurRgbaDansLaPalette`, `testVerifierRefuseUneCouleurInvalide`, `testChampTexteSeulementPourLaNeuvieme`.

- [ ] **Étape 3 : étendre `astra_couleur()`** dans `astra_fonctions.php` (remplace la fonction et son commentaire)

````php
/**
 * Normaliser une couleur saisie (#rgb, #rrggbb, rgb() ou rgba() comme la palette d'Astra), avec repli
 */
function astra_couleur(string $couleur, string $defaut = '#000000'): string {
	$couleur = trim($couleur);
	if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $couleur)) {
		return strtolower($couleur);
	}
	if (strpos($couleur, 'rgb') === 0 && astra_styles_couleur_valide($couleur)) {
		return $couleur;
	}
	return $defaut;
}
````

- [ ] **Étape 4 : vérifier la saisie** — `formulaires/configurer_astra.php` devient :

````php
<?php
/**
 * Astra pour SPIP — vérification du formulaire de configuration du thème.
 * Chargement et enregistrement : automatiques (formulaire configurer_, méta astra).
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_astra_verifier_dist() {
	$erreurs = [];
	$couleur = _request('couleur_8');
	if ($couleur !== null && astra_couleur((string) $couleur, '') === '') {
		$erreurs['couleur_8'] = _T('astra:cfg_couleur_8_erreur');
	}
	$type = _request('accueil_type');
	if ($type !== null && !in_array($type, ['articles', 'page'], true)) {
		$erreurs['accueil_type'] = _T('astra:cfg_accueil_type_erreur');
	}
	if ($type === 'page') {
		include_spip('inc/astra_pages');
		$id = (int) _request('accueil_page');
		if (!astra_est_page_unique($id) || sql_getfetsel('statut', 'spip_articles', 'id_article=' . $id) !== 'publie') {
			$erreurs['accueil_page'] = _T('astra:cfg_accueil_page_erreur');
		}
	}
	return $erreurs;
}
````

- [ ] **Étape 5 : champ texte pour la neuvième couleur** — dans `formulaires/configurer_astra.html`, la boucle `BOUCLE_couleurs` devient :

```html
				<BOUCLE_couleurs(DATA){enum 0,8}>
				<div class="editer editer_couleur_#VALEUR[ (#ENV*{erreurs/couleur_#VALEUR}|oui)erreur]">
					<label for="couleur_#VALEUR">[(#VAL{astra:cfg_couleur_}|concat{#VALEUR}|_T)]</label>
					[(#VALEUR|=={8}|oui)<p class="explication"><:astra:cfg_couleur_8_explication:></p>
					[<span class="erreur_message">(#ENV*{erreurs/couleur_8})</span>]]
					<input type="[(#VALEUR|=={8}|?{text,color})]" class="text" name="couleur_#VALEUR" id="couleur_#VALEUR" value="[(#ENV{couleur_#VALEUR}|sinon{#VAL{couleur_#VALEUR}|astra_conf}|astra_couleur)]" />
				</div>
				</BOUCLE_couleurs>
```

- [ ] **Étape 6 : chaînes de langue** — après `'cfg_couleur_8' => …,`

`lang/astra_fr.php` :
```php
	'cfg_couleur_8_erreur' => 'Couleur hexadécimale, <code>rgb()</code> ou <code>rgba()</code> attendue.',
	'cfg_couleur_8_explication' => 'Hexadécimale (<code>#40bf4f</code>) ou <code>rgba(64, 191, 79, 0.15)</code>, comme la palette d’Astra.',
```
`lang/astra_en.php` :
```php
	'cfg_couleur_8_erreur' => 'Expected a hexadecimal, <code>rgb()</code> or <code>rgba()</code> color.',
	'cfg_couleur_8_explication' => 'Hexadecimal (<code>#40bf4f</code>) or <code>rgba(64, 191, 79, 0.15)</code>, like Astra’s palette.',
```

- [ ] **Étape 7 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Expected : tout passe, dont `PaletteTest` (4 tests).

- [ ] **Étape 8 : commit**

```bash
git add astra_fonctions.php formulaires/configurer_astra.php formulaires/configurer_astra.html lang/astra_fr.php lang/astra_en.php tests/integration/PaletteTest.php
git commit -m "Palette : neuvième couleur en rgb() ou rgba(), comme global-color-palette d'Astra"
```

---

### Tâche 11 : migration du titre et du slogan, largeur du logo

`afficher_titre_site` et `afficher_slogan` deviennent `display-site-title-responsive` et
`display-site-tagline-responsive`, sans changement de rendu. Le slogan, masqué jusqu'ici par
`css/astra-dynamic.css` quel que soit `afficher_slogan`, reste masqué ; comme Astra
(`astra_logo()`), un slogan masqué sur tous les appareils n'est plus imprimé, et l'enveloppe
du titre n'est imprimée que si le titre ou le slogan l'est.

Ces deux suppressions sont l’unique exception au HTML identique octet pour octet. Pour le
contrôle de migration, appliquer uniquement ces suppressions au HTML attendu de la version
1.1.0, puis comparer strictement le résultat au HTML obtenu ; ne pas neutraliser les autres
nœuds ou les différences d’espacement de toute la page. Compléter la comparaison par des
captures et les styles calculés à 1280, 768 et 390 px (visibilité, dimensions et position du
logo, du titre et des éléments voisins), notamment quand le titre et le slogan sont tous deux
masqués. Une différence de mise en page reste un échec, même si le nœud supprimé était vide.
Ajouter un cas de migration répétée et un cas où des réglages explicites existent déjà dans
`astra/styles` : ils sont conservés, sans être écrasés par les anciennes clés.

**Fichiers :**
- Créer : `astra_administrations.php`, `tests/integration/attendus/identite.php`, `tests/integration/attendus/identite-{slogan_oui,slogan_non,titre_non,aucune}.html`
- Modifier : `paquet.xml`, `astra_fonctions.php`, `inclure/identite.html`, `formulaires/configurer_astra.html`, `lang/astra_fr.php`, `lang/astra_en.php`
- Test : `tests/integration/IdentiteMigrationTest.php`

**Interfaces :**
- Consomme : registre (`defaut` des options `display-*`), module identité (tâche 7).
- Produit : `astra_upgrade()`, `astra_vider_tables()`, `astra_migrer_identite(): void` ; filtres `astra_identite_visible(string $option): bool`, `astra_logo_reduire($img): string`.

- [ ] **Étape 1 : script des cas et rendu de référence**

````php
<?php
/**
 * Cas de la migration de l'identité (titre et slogan) : configuration d'avant la version 1.2.0.
 * Exécuté seul (php tests/integration/attendus/identite.php), écrit identite-<cas>.html avec les
 * squelettes présents : à lancer avant la migration pour figer le rendu de référence.
 */
const ASTRA_CAS_IDENTITE = [
	'slogan_oui' => ['afficher_slogan' => 'oui'],
	'slogan_non' => ['afficher_slogan' => 'non'],
	'titre_non' => ['afficher_titre_site' => 'non'],
	'aucune' => [],
];

function astra_cas_identite_appliquer(array $config): void {
	foreach (['afficher_slogan', 'afficher_titre_site'] as $cle) {
		isset($config[$cle]) ? ecrire_config("astra/$cle", $config[$cle]) : effacer_config("astra/$cle");
	}
	ecrire_meta('slogan_site', 'Jardiner au naturel');
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
	require dirname(__DIR__, 2) . '/bootstrap_integration.php';
	$slogan = $GLOBALS['meta']['slogan_site'] ?? '';
	foreach (ASTRA_CAS_IDENTITE as $cas => $config) {
		astra_cas_identite_appliquer($config);
		file_put_contents(__DIR__ . "/identite-$cas.html", recuperer_fond('inclure/identite', []));
	}
	ecrire_meta('slogan_site', $slogan);
	effacer_config('astra/afficher_slogan');
	effacer_config('astra/afficher_titre_site');
}
````

- [ ] **Étape 2 : figer le rendu de la version 1.1.0** (avant toute modification des squelettes)

Run : `php tests/integration/attendus/identite.php && ls tests/integration/attendus/`
Expected : `identite-aucune.html identite-slogan_non.html identite-slogan_oui.html identite-titre_non.html identite.php`. `identite-slogan_oui.html` contient `<p class="site-description" itemprop="description">Jardiner au naturel</p>` ; `identite-titre_non.html` ne contient pas `site-title`.

- [ ] **Étape 3 : écrire le test**

````php
<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/attendus/identite.php';

/**
 * Migration de afficher_titre_site et afficher_slogan vers les options d'Astra, sans changement de
 * rendu : le HTML de l'identité est celui de la version 1.1.0 (attendus/identite-<cas>.html),
 * hormis le slogan que css/astra-dynamic.css masquait et qu'Astra n'imprime pas quand il est
 * masqué partout (astra_logo()), et l'enveloppe du titre restée vide.
 */
final class IdentiteMigrationTest extends TestCase {

	private static string $slogan;

	public static function setUpBeforeClass(): void {
		include_spip('astra_administrations');
		self::$slogan = $GLOBALS['meta']['slogan_site'] ?? '';
	}

	protected function tearDown(): void {
		effacer_config('astra/styles');
		effacer_config('astra/afficher_slogan');
		effacer_config('astra/afficher_titre_site');
		ecrire_meta('slogan_site', self::$slogan);
	}

	public static function cas(): array {
		return array_map(function ($c) { return [$c]; }, array_combine(array_keys(ASTRA_CAS_IDENTITE), array_keys(ASTRA_CAS_IDENTITE)));
	}

	private static function attenduApresMigration(string $html): string {
		$html = preg_replace('~<p class="site-description".*?</p>~s', '', $html);
		$html = preg_replace('~<div class="ast-site-title-wrap">\s*</div>~', '', $html);
		return $html;
	}

	#[DataProvider('cas')]
	public function testRenduInchange(string $cas): void {
		astra_cas_identite_appliquer(ASTRA_CAS_IDENTITE[$cas]);
		astra_migrer_identite();
		$avant = file_get_contents(__DIR__ . "/attendus/identite-$cas.html");
		$this->assertSame(self::attenduApresMigration($avant), recuperer_fond('inclure/identite', []));
		$this->assertStringNotContainsString('site-description', recuperer_fond('inclure/identite', []));
		$this->assertNull(lire_config('astra/afficher_slogan'));
		$this->assertNull(lire_config('astra/afficher_titre_site'));
	}

	#[DataProvider('cas')]
	public function testSeulLeTitreMasqueSEcrit(string $cas): void {
		astra_cas_identite_appliquer(ASTRA_CAS_IDENTITE[$cas]);
		astra_migrer_identite();
		$styles = lire_config('astra/styles');
		if ($cas === 'titre_non') {
			$this->assertSame(['display-site-title-responsive' => ['desktop' => false, 'tablet' => false, 'mobile' => false]], $styles);
		} else {
			$this->assertNull($styles);
		}
	}

	public function testMigrationIdempotente(): void {
		astra_cas_identite_appliquer(ASTRA_CAS_IDENTITE['titre_non']);
		astra_migrer_identite();
		$styles = lire_config('astra/styles');
		$html = recuperer_fond('inclure/identite', []);
		astra_migrer_identite();
		$this->assertSame($styles, lire_config('astra/styles'));
		$this->assertSame($html, recuperer_fond('inclure/identite', []));
	}

	public function testMigrationPreserveLesReglagesExplicites(): void {
		astra_cas_identite_appliquer(ASTRA_CAS_IDENTITE['titre_non']);
		$styles = [
			'display-site-title-responsive' => ['desktop' => true, 'tablet' => false, 'mobile' => true],
			'display-site-tagline-responsive' => ['desktop' => true],
			'button-color' => '#123456',
		];
		ecrire_config('astra/styles', $styles);
		astra_migrer_identite();
		$this->assertSame($styles, lire_config('astra/styles'));
		$this->assertNull(lire_config('astra/afficher_slogan'));
		$this->assertNull(lire_config('astra/afficher_titre_site'));
	}

	public function testSloganAfficheParLOptionDAstra(): void {
		ecrire_meta('slogan_site', 'Jardiner au naturel');
		ecrire_config('astra/styles', ['display-site-tagline-responsive' => ['desktop' => true, 'tablet' => true, 'mobile' => true]]);
		$this->assertStringContainsString('<p class="site-description" itemprop="description">Jardiner au naturel</p>', recuperer_fond('inclure/identite', []));
		$this->assertStringContainsString('.site-header .site-description{font-size:15px;font-size:0.9375rem;display:block;}', recuperer_fond('inclure/head-vars', []));
	}

	public function testLargeurDuLogo(): void {
		ecrire_config('astra/styles', ['ast-header-responsive-logo-width' => ['desktop' => '40']]);
		$this->assertStringContainsString('header .custom-logo-link img{max-width:40px;width:40px;}', recuperer_fond('inclure/head-vars', []));
	}
}
````

- [ ] **Étape 4 : vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter IdentiteMigrationTest`
Expected : échec, `astra_administrations` introuvable ou `astra_migrer_identite()` non définie.

- [ ] **Étape 5 : écrire `astra_administrations.php`**

````php
<?php
/**
 * Astra pour SPIP — installation et mises à jour de la configuration.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Mises à jour. Un site antérieur au schéma 1.0.0 n'a pas de version enregistrée : SPIP exécute
 * alors `create`, qui migre aussi.
 */
function astra_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [
		'create' => [['astra_migrer_identite']],
		'1.0.0' => [['astra_migrer_identite']],
	];
	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function astra_vider_tables($nom_meta_base_version) {
	effacer_meta($nom_meta_base_version);
}

/**
 * Titre et slogan : afficher_titre_site et afficher_slogan deviennent les options d'Astra
 * display-site-title-responsive et display-site-tagline-responsive (méta astra/styles), sans
 * changement de rendu. Le slogan, masqué jusqu'ici par css/astra-dynamic.css quel que soit
 * afficher_slogan, reste masqué (défaut d'Astra) ; seul un titre masqué s'écrit.
 */
function astra_migrer_identite(): void {
	include_spip('inc/config');
	if (lire_config('astra/afficher_titre_site') === 'non') {
		$styles = lire_config('astra/styles');
		$styles = is_array($styles) ? $styles : [];
		if (!isset($styles['display-site-title-responsive'])) {
			$styles['display-site-title-responsive'] = ['desktop' => false, 'tablet' => false, 'mobile' => false];
			ecrire_config('astra/styles', $styles);
		}
	}
	effacer_config('astra/afficher_titre_site');
	effacer_config('astra/afficher_slogan');
}
````

- [ ] **Étape 6 : déclarer le schéma** — dans `paquet.xml`, après `version="1.1.0"` : `schema="1.0.0"`.

- [ ] **Étape 7 : filtres de l'identité** — dans `astra_fonctions.php`, retirer de `astra_config_defaut()` les lignes `'afficher_slogan' => 'oui',` et `'afficher_titre_site' => 'oui',` ; ajouter avant `astra_couleur()` :

````php
/**
 * Titre ou slogan imprimé : visible sur au moins un appareil, comme astra_logo() d'Astra.
 * `#VAL{display-site-title-responsive}|astra_identite_visible`
 */
function astra_identite_visible(string $option): bool {
	include_spip('inc/config');
	$defaut = astra_styles_registre()[$option]['defaut'];
	$v = astra_styles_normaliser([$option => lire_config('astra/styles/' . $option)])[$option] ?? [];
	foreach (ASTRA_STYLES_APPAREILS as $appareil) {
		if (array_key_exists($appareil, $v) && $v[$appareil] !== '' ? $v[$appareil] : $defaut[$appareil]) {
			return true;
		}
	}
	return false;
}

/**
 * Logo du site réduit : à la largeur ordinateur de ast-header-responsive-logo-width si elle est
 * réglée, sinon à 60 px de haut.
 */
function astra_logo_reduire($img): string {
	include_spip('inc/config');
	include_spip('inc/filtres_images_mini');
	$largeur = (int) (lire_config('astra/styles/ast-header-responsive-logo-width/desktop') ?? 0);
	return $largeur > 0 ? image_reduire($img, $largeur, 0) : image_reduire($img, 0, 60);
}
````

- [ ] **Étape 8 : réécrire `inclure/identite.html`** (blancs identiques à la version 1.1.0 sans réglage)

````html
[(#REM)

	Identité du site : logo, nom et slogan (élément « title_tagline » d'Astra, astra_logo()).
	Le nom et le slogan sont imprimés s'ils sont visibles sur au moins un appareil
	(display-site-title-responsive, display-site-tagline-responsive) ; le CSS de styles/identite
	règle leur affichage. Sans réglage, les blancs produits sont ceux de la version 1.1.0.

]#SET{titre,#VAL{display-site-title-responsive}|astra_identite_visible}#SET{slogan,#VAL{display-site-tagline-responsive}|astra_identite_visible}<div class="site-branding ast-site-identity" itemtype="https://schema.org/Organization" itemscope="itemscope">
	[<span class="site-logo-img"><a href="#URL_SITE_SPIP/" class="custom-logo-link" rel="home" itemprop="url">(#LOGO_SITE_SPIP|astra_logo_reduire|inserer_attribut{class,custom-logo}|inserer_attribut{itemprop,logo}|inserer_attribut{alt,#NOM_SITE_SPIP|textebrut})</a></span>]
	<BOUCLE_titre_slogan(CONDITION){si #GET{titre}|ou{#GET{slogan}}}><div class="ast-site-title-wrap">
		[(#GET{titre}|oui)
		<span class="site-title" itemprop="name">
			<a href="#URL_SITE_SPIP/" rel="home" itemprop="url">[(#NOM_SITE_SPIP|supprimer_numero)]</a>
		</span>]
		[(#GET{slogan}|ou{#GET{titre}}|oui)
		]<BOUCLE_slogan(CONDITION){si #GET{slogan}}><p class="site-description" itemprop="description">[(#SLOGAN_SITE_SPIP|textebrut)]</p></BOUCLE_slogan>
	</div></BOUCLE_titre_slogan>
</div>
<!-- .site-branding -->
````

- [ ] **Étape 9 : retirer les anciennes cases** — dans `formulaires/configurer_astra.html`, `{liste afficher_titre_site, afficher_slogan, menu_accueil, afficher_auteurs, afficher_extrait, bouton_haut}` devient `{liste menu_accueil, afficher_auteurs, afficher_extrait, bouton_haut}` ; retirer `cfg_afficher_slogan` et `cfg_afficher_titre_site` de `lang/astra_fr.php` et `lang/astra_en.php`.

- [ ] **Étape 10 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Expected : tout passe, dont `IdentiteMigrationTest` (12 tests).

- [ ] **Étape 11 : mise à jour réelle**

Run :
```bash
php -r 'require "tests/bootstrap_integration.php"; ecrire_config("astra/afficher_titre_site","non"); effacer_meta("astra_base_version"); include_spip("astra_administrations"); astra_upgrade("astra_base_version", "1.0.0"); echo $GLOBALS["meta"]["astra_base_version"], " ", json_encode(lire_config("astra/styles")), " ", var_export(lire_config("astra/afficher_titre_site"), true), "\n"; effacer_config("astra/styles"); effacer_meta("astra_base_version");' | grep -v '^MAJ'
```
Expected : `1.0.0 {"display-site-title-responsive":{"desktop":false,"tablet":false,"mobile":false}} NULL`

- [ ] **Étape 12 : rendu inchangé après migration** — refaire l’étape 8 de la tâche 3, en appliquant au HTML attendu uniquement les deux suppressions de balisage documentées ci-dessus ; toutes les autres différences restent des échecs. Exécuter aussi les comparaisons de migration en captures et styles calculés à 1280, 768 et 390 px pour les quatre configurations de `ASTRA_CAS_IDENTITE`. Expected : aucune autre différence HTML ou CSS, rendu visible inchangé ; migration idempotente et réglages explicites préservés.

- [ ] **Étape 13 : commit**

```bash
git add astra_administrations.php paquet.xml astra_fonctions.php inclure/identite.html formulaires/configurer_astra.html lang/astra_fr.php lang/astra_en.php
git add tests/integration/attendus/identite.php tests/integration/attendus/identite-slogan_oui.html tests/integration/attendus/identite-slogan_non.html tests/integration/attendus/identite-titre_non.html tests/integration/attendus/identite-aucune.html tests/integration/IdentiteMigrationTest.php
git commit -m "Identité : titre et slogan selon les options d'Astra, migration sans changement de rendu, largeur du logo"
```

---

### Tâche 12 : polices locales

**Fichiers :**
- Créer : `fonts/raleway/raleway-latin-{400-normal,400-italic,700-normal}.woff2`, `fonts/poppins/poppins-latin-{500-normal,600-normal}.woff2`
- Modifier : `inc/astra_polices.php`, `astra_fonctions.php`, `inclure/head-vars.html`, `formulaires/configurer_astra.html`, `lang/astra_fr.php`, `lang/astra_en.php`
- Test : `tests/unit/PolicesTest.php`, `tests/integration/PolicesRenduTest.php`

**Interfaces :**
- Produit : `astra_polices_locales(): array` (clé ⇒ `nom`, `pile`, `fichiers` [[graisse, style, chemin]], `licence`, `source`, `version`) ; `astra_polices_detecter(string $pile): ?string` (première famille de la pile) ; `astra_polices_css(array $piles, callable $url): string` ; filtre `astra_polices_inline(): string` ; bloc `<style id="astra-polices-inline-css">` avant `astra-theme-css-inline-css`, émis s'il n'est pas vide. Graisses fournies : Raleway 400, 400 italique, 700 (texte, `em`, `strong`) ; Poppins 500, 600 (bouton, titres).

- [ ] **Étape 1 : télécharger les fichiers** (Fontsource 5.1.0, sous-ensemble latin)

```bash
B=https://cdn.jsdelivr.net/npm/@fontsource
mkdir -p fonts/raleway fonts/poppins
for f in raleway/raleway-latin-400-normal raleway/raleway-latin-400-italic raleway/raleway-latin-700-normal poppins/poppins-latin-500-normal poppins/poppins-latin-600-normal; do
	curl -sfL -o "fonts/$f.woff2" "$B/${f%%/*}@5.1.0/files/${f#*/}.woff2"
done
sha256sum fonts/*/*.woff2
```
Expected :
```text
cd36de204aca2d5fa263a731f7c20009b5e3d754ba1f1e03c33e93a48f3e7446  fonts/poppins/poppins-latin-500-normal.woff2
f4e80d9dfd374d02989b87a27b5ed4cb78fbb177c27f1478e9a8b0afb7513149  fonts/poppins/poppins-latin-600-normal.woff2
29de03aaf7a0844578daef59eabdbd1cfe9257873765938cc51a3c9a3af843b4  fonts/raleway/raleway-latin-400-italic.woff2
3e44fb721d3be9376c6e5e946109067a04da84ae10b3f27a03ada7a3731e515c  fonts/raleway/raleway-latin-400-normal.woff2
109736135dc84f02f379825bd2b48998e17068eaf1f085df5f52e80537a4257d  fonts/raleway/raleway-latin-700-normal.woff2
```
Si une empreinte diffère, s'arrêter et le signaler (fichier changé chez Fontsource).

- [ ] **Étape 2 : écrire les tests**

`tests/unit/PolicesTest.php` :
````php
<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_polices.php';

/** Polices locales : registre, fichiers fournis, détection dans une pile, @font-face. */
final class PolicesTest extends TestCase {

	public function testFichiersFournis(): void {
		foreach (astra_polices_locales() as $cle => $police) {
			foreach (['nom', 'pile', 'fichiers', 'licence', 'source', 'version'] as $champ) {
				$this->assertArrayHasKey($champ, $police, "$cle : $champ");
			}
			foreach ($police['fichiers'] as [$graisse, $style, $chemin]) {
				$this->assertFileExists(dirname(__DIR__, 2) . '/' . $chemin);
				$this->assertMatchesRegularExpression('/^[1-9]00$/', $graisse);
				$this->assertContains($style, ['normal', 'italic']);
			}
		}
	}

	public function testDetection(): void {
		$this->assertSame('poppins', astra_polices_detecter("'Poppins', sans-serif"));
		$this->assertSame('raleway', astra_polices_detecter('Raleway'));
		$this->assertSame('raleway', astra_polices_detecter('raleway'));
		$this->assertNull(astra_polices_detecter('Georgia, serif'));
		$this->assertNull(astra_polices_detecter(''));
		$this->assertNull(astra_polices_detecter("Arial, 'Raleway'"));
	}

	public function testFontFace(): void {
		$url = function (string $chemin) { return "/plugins/astra/$chemin"; };
		$css = astra_polices_css(["'Poppins', sans-serif", 'Georgia, serif', "'Poppins', sans-serif", ''], $url);
		$this->assertSame(
			"@font-face{font-family:'Poppins';font-style:normal;font-weight:500;font-display:swap;src:url(/plugins/astra/fonts/poppins/poppins-latin-500-normal.woff2) format('woff2');}"
			. "@font-face{font-family:'Poppins';font-style:normal;font-weight:600;font-display:swap;src:url(/plugins/astra/fonts/poppins/poppins-latin-600-normal.woff2) format('woff2');}",
			$css
		);
		$this->assertSame('', astra_polices_css(['Georgia, serif', 'inherit'], $url));
	}
}
````

`tests/integration/PolicesRenduTest.php` :
````php
<?php

use PHPUnit\Framework\TestCase;

/** Polices locales : @font-face émis pour une police fournie, rien pour une pile système. */
final class PolicesRenduTest extends TestCase {

	protected function tearDown(): void {
		effacer_config('astra/police_texte');
		effacer_config('astra/styles');
	}

	public function testPoliceLocaleChargee(): void {
		ecrire_config('astra/police_texte', "'Raleway', sans-serif");
		ecrire_config('astra/styles', ['font-family-button' => "'Poppins', sans-serif"]);
		$html = recuperer_fond('inclure/head-vars', []);
		$this->assertSame(5, substr_count($html, '@font-face'));
		preg_match('~src:url\(([^)?]+)[^)]*\) format~', $html, $m);
		$this->assertStringEndsWith('fonts/raleway/raleway-latin-400-normal.woff2', $m[1]);
		$this->assertFileExists(_DIR_PLUGINS . 'astra/fonts/raleway/raleway-latin-400-normal.woff2');
		$this->assertLessThan(strpos($html, 'astra-theme-css-inline-css'), strpos($html, 'astra-polices-inline-css'));
	}

	public function testPileSystemeSansFontFace(): void {
		ecrire_config('astra/police_texte', 'Georgia, serif');
		$html = recuperer_fond('inclure/head-vars', []);
		$this->assertStringNotContainsString('@font-face', $html);
		$this->assertStringNotContainsString('astra-polices-inline-css', $html);
	}

	public function testFormulaireProposeLesPolicesLocales(): void {
		$html = recuperer_fond('formulaires/configurer_astra', ['editable' => true]);
		$this->assertStringContainsString('<option value="&#039;Raleway&#039;, sans-serif">Raleway</option>', $html);
		$this->assertStringContainsString('list="astra-polices-locales"', $html);
	}
}
````

- [ ] **Étape 3 : vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite unit --filter PolicesTest`
Expected : `Call to undefined function astra_polices_detecter()`.

- [ ] **Étape 4 : remplir le registre** — `inc/astra_polices.php` devient :

````php
<?php
/**
 * Astra pour SPIP — polices locales (spec des styles du Customizer, « Polices locales ») :
 * registre fermé des polices fournies en woff2 dans fonts/, et leurs @font-face. Aucun service
 * tiers n'est appelé.
 *
 * @license GPL-3.0-or-later
 */

/**
 * Polices locales : nom, pile CSS (telle qu'Astra l'enregistre), fichiers [graisse, style, chemin],
 * licence, source et version. Fichiers « latin » de Fontsource 5.1.0.
 */
function astra_polices_locales(): array {
	return [
		'raleway' => [
			'nom' => 'Raleway',
			'pile' => "'Raleway', sans-serif",
			'fichiers' => [
				['400', 'normal', 'fonts/raleway/raleway-latin-400-normal.woff2'],
				['400', 'italic', 'fonts/raleway/raleway-latin-400-italic.woff2'],
				['700', 'normal', 'fonts/raleway/raleway-latin-700-normal.woff2'],
			],
			'licence' => 'OFL-1.1',
			'source' => 'https://cdn.jsdelivr.net/npm/@fontsource/raleway@5.1.0/',
			'version' => '5.1.0',
		],
		'poppins' => [
			'nom' => 'Poppins',
			'pile' => "'Poppins', sans-serif",
			'fichiers' => [
				['500', 'normal', 'fonts/poppins/poppins-latin-500-normal.woff2'],
				['600', 'normal', 'fonts/poppins/poppins-latin-600-normal.woff2'],
			],
			'licence' => 'OFL-1.1',
			'source' => 'https://cdn.jsdelivr.net/npm/@fontsource/poppins@5.1.0/',
			'version' => '5.1.0',
		],
	];
}

/** Police locale désignée par la première famille d'une pile ('Raleway', sans-serif → raleway). */
function astra_polices_detecter(string $pile): ?string {
	$premiere = strtolower(trim(explode(',', $pile)[0], " \t'\""));
	foreach (astra_polices_locales() as $cle => $police) {
		if ($premiere === strtolower($police['nom']) || $premiere === $cle) {
			return $cle;
		}
	}
	return null;
}

/**
 * @font-face des polices locales présentes dans les piles choisies, dans les graisses fournies ;
 * rien pour une pile système. $url donne l'adresse publique d'un fichier du plugin.
 */
function astra_polices_css(array $piles, callable $url): string {
	$css = '';
	$vues = [];
	foreach ($piles as $pile) {
		$cle = is_string($pile) ? astra_polices_detecter($pile) : null;
		if ($cle === null || isset($vues[$cle])) {
			continue;
		}
		$vues[$cle] = true;
		$police = astra_polices_locales()[$cle];
		foreach ($police['fichiers'] as [$graisse, $style, $chemin]) {
			$css .= "@font-face{font-family:'{$police['nom']}';font-style:$style;font-weight:$graisse;font-display:swap;src:url(" . $url($chemin) . ") format('woff2');}";
		}
	}
	return $css;
}
````

- [ ] **Étape 5 : filtre des `@font-face`** — dans `astra_fonctions.php`, avant `astra_styles_inline()` :

````php
/**
 * @font-face des polices locales choisies (police du texte, des titres, du bouton, des menus, du
 * bouton de l'en-tête) : vide pour des piles système.
 */
function astra_polices_inline(): string {
	include_spip('inc/config');
	$styles = lire_config('astra/styles');
	$styles = is_array($styles) ? $styles : [];
	$piles = [astra_conf('police_texte'), astra_conf('police_titres')];
	foreach (['font-family-button', 'header-menu1-font-family', 'header-menu2-font-family', 'header-button1-font-family'] as $option) {
		$piles[] = $styles[$option] ?? '';
	}
	return astra_polices_css($piles, function (string $chemin) {
		return url_absolue(timestamp(find_in_path($chemin)));
	});
}
````

- [ ] **Étape 6 : bloc des polices** — `inclure/head-vars.html` devient :

````html
[(#REM)

	Variables CSS d'Astra pilotées par la configuration du thème
	(palette globale, largeurs de conteneur, polices)

][<style id="astra-polices-inline-css">
(#VAL|astra_polices_inline)
</style>]<style id="astra-theme-css-inline-css">
:root{<BOUCLE_couleurs(DATA){enum 0,8}>--ast-global-color-#VALEUR:[(#VAL{couleur_#VALEUR}|astra_conf|astra_couleur)];</BOUCLE_couleurs>
#SET{large,#VAL{largeur_conteneur}|astra_conf|astra_largeur{600}}--ast-normal-container-width:#GET{large}px;--wp--custom--ast-container-width:#GET{large}px;--wp--custom--ast-content-width-size:#GET{large}px;
--ast-narrow-container-width:[(#VAL{largeur_etroite}|astra_conf|astra_largeur{400})]px;}
[body,button,input,select,textarea,.ast-button,.ast-custom-button{font-family:(#VAL{police_texte}|astra_conf|astra_police);}]
[h1,h2,h3,h4,h5,h6,.entry-content :where(h1,h2,h3,h4,h5,h6),.site-title,.site-title a{font-family:(#VAL{police_titres}|astra_conf|astra_police);}]
</style>
[<style id="astra-hfb-inline-css">
(#VAL|astra_hfb_css)
</style>][<style id="astra-styles-inline-css">
(#VAL|astra_styles_inline)
</style>]
````

- [ ] **Étape 7 : formulaire du thème** — dans `formulaires/configurer_astra.html`, le champ `police_texte` devient :

```html
					<input type="text" class="text" name="police_texte" id="police_texte" value="[(#ENV{police_texte})]" placeholder="Georgia, serif" list="astra-polices-locales" />
					<datalist id="astra-polices-locales">
						<BOUCLE_polices(DATA){source tableau, #VAL|astra_polices_locales}><option value="[(#VALEUR{pile}|attribut_html)]">#VALEUR{nom}</option></BOUCLE_polices>
					</datalist>
```
et le champ `police_titres` reçoit `list="astra-polices-locales"`. Dans les fichiers de langue, `cfg_police_explication` devient :
- fr : `'Pile de polices CSS, par exemple <code>Georgia, serif</code>, ou une police fournie avec le thème (Raleway, Poppins), chargée depuis le site. Laisser vide pour la police système d’Astra.'`
- en : `'CSS font stack, e.g. <code>Georgia, serif</code>, or a font shipped with the theme (Raleway, Poppins), served by the site. Leave empty to use Astra’s system font.'`

- [ ] **Étape 8 : vérifier le succès**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Expected : tout passe, dont `PolicesTest` (3) et `PolicesRenduTest` (3). Puis refaire l'étape 8 de la tâche 3 : `IDENTIQUE`.

- [ ] **Étape 9 : commit**

```bash
git add fonts/raleway/raleway-latin-400-normal.woff2 fonts/raleway/raleway-latin-400-italic.woff2 fonts/raleway/raleway-latin-700-normal.woff2 fonts/poppins/poppins-latin-500-normal.woff2 fonts/poppins/poppins-latin-600-normal.woff2
git add inc/astra_polices.php astra_fonctions.php inclure/head-vars.html formulaires/configurer_astra.html lang/astra_fr.php lang/astra_en.php tests/unit/PolicesTest.php tests/integration/PolicesRenduTest.php
git commit -m "Polices locales : Raleway et Poppins servies par le site, @font-face des polices choisies"
```

La licence et les crédits des polices sont ajoutés à la tâche 15, avant la publication de la version.

---

### Tâche 13 : formulaire « Couleurs et typographie »

**Fichiers :**
- Créer : `inc/astra_styles_saisie.php`, `formulaires/configurer_astra_styles.php`, `formulaires/configurer_astra_styles.html`, `prive/squelettes/contenu/configurer_astra_styles.html`, `tests/e2e/formulaire_styles.cjs`
- Modifier : `astra_fonctions.php`, `paquet.xml`, `lang/astra_fr.php`, `lang/astra_en.php`
- Test : `tests/unit/StylesSaisieTest.php`, `tests/integration/StylesFormulaireTest.php`

**Interfaces :**
- Consomme : registre, normalisation (tâche 2), `astra_polices_locales()` (tâche 12).
- Produit : `astra_styles_sections(): array` (section ⇒ motif d'options) ; `astra_styles_libelle(string $option): string` (clé `astra:style_…`) ; `astra_styles_champs($styles, string $section): array` (champs : `option`, `nom`, `nom_unite`, `type`, `responsive`, `libelle`, `valeur`, `unite`, `unites`, `valeurs`) ; `astra_styles_nom(string $nom, string $cle): string` ; `astra_styles_saisie_fusionner(array $existant, array $saisie): array` ; `astra_styles_saisie_refusees(array $saisie): array` ; champs postés `s[<option>]`, `s[<option>][desktop]`, `s[<option>][desktop-unit]`.

- [ ] **Étape 1 : écrire le test unitaire**

````php
<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/astra_styles_saisie.php';

/** Saisie des styles : sections, libellés, fusion sans perte des valeurs tablette et mobile. */
final class StylesSaisieTest extends TestCase {

	public function testChaqueOptionDansUneSeuleSection(): void {
		foreach (array_keys(astra_styles_registre()) as $option) {
			$sections = array_filter(astra_styles_sections(), function ($motif) use ($option) {
				return preg_match($motif, $option);
			});
			$this->assertCount(1, $sections, $option);
		}
	}

	public function testLibellesTraduits(): void {
		$fr = include dirname(__DIR__, 2) . '/lang/astra_fr.php';
		$en = include dirname(__DIR__, 2) . '/lang/astra_en.php';
		foreach (array_keys(astra_styles_registre()) as $option) {
			$cle = substr(astra_styles_libelle($option), strlen('astra:'));
			$this->assertArrayHasKey($cle, $fr, $option);
			$this->assertArrayHasKey($cle, $en, $option);
		}
		foreach (array_keys(astra_styles_sections()) as $section) {
			$this->assertArrayHasKey("styles_section_$section", $fr);
			$this->assertArrayHasKey("styles_section_$section", $en);
		}
	}

	public function testChampsDUneSection(): void {
		$styles = ['header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222']];
		$champs = astra_styles_champs($styles, 'menu_1');
		$this->assertSame('header-menu1-color-responsive', $champs[0]['option']);
		$this->assertSame('#111111', $champs[0]['valeur']);
		$this->assertSame('astra:style_menu_color_responsive', $champs[0]['libelle']);
		$this->assertCount(12, $champs);
		$this->assertSame('s[header-menu1-color-responsive][desktop]', $champs[0]['nom']);
		$identite = array_column(astra_styles_champs(['display-site-title-responsive' => ['desktop' => false]], 'identite'), 'valeur', 'option');
		$this->assertSame('0', $identite['display-site-title-responsive']);
	}

	public function testFusionGardeTabletteEtMobile(): void {
		$existant = [
			'header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222', 'mobile' => '#333333'],
			'font-size-button' => ['desktop' => '14', 'tablet' => '13', 'desktop-unit' => 'px', 'tablet-unit' => 'px'],
			'button-radius-fields' => ['desktop' => ['top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8], 'tablet' => ['top' => 4], 'desktop-unit' => 'px'],
			'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => '', 'background-repeat' => 'repeat'], 'mobile' => ['background-color' => '#000']],
		];
		$saisie = [
			'header-menu1-color-responsive' => ['desktop' => '#444444'],
			'font-size-button' => ['desktop' => '1.2', 'desktop-unit' => 'em'],
			'button-radius-fields' => ['desktop' => ['top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10']],
			'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => '#ffffff']],
			'button-color' => '',
		];
		$this->assertSame([
			'header-menu1-color-responsive' => ['desktop' => '#444444', 'tablet' => '#222222', 'mobile' => '#333333'],
			'font-size-button' => ['desktop' => '1.2', 'tablet' => '13', 'desktop-unit' => 'em', 'tablet-unit' => 'px'],
			'button-radius-fields' => ['desktop' => ['top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10'], 'tablet' => ['top' => 4], 'desktop-unit' => 'px'],
			'header-menu1-bg-obj-responsive' => ['desktop' => ['background-color' => '#ffffff', 'background-repeat' => 'repeat'], 'mobile' => ['background-color' => '#000']],
		], astra_styles_saisie_fusionner($existant, $saisie));
	}

	public function testOptionVideEffaceeSeulementSiEnregistree(): void {
		$this->assertSame(['button-color' => ''], astra_styles_saisie_fusionner(['button-color' => '#123456'], ['button-color' => '']));
		$this->assertSame([], astra_styles_saisie_fusionner([], ['button-color' => '', 'header-menu1-color-responsive' => ['desktop' => '']]));
	}

	public function testValeursRefusees(): void {
		$this->assertSame(
			['button-color', 'font-size-button'],
			astra_styles_saisie_refusees([
				'button-color' => 'rouge',
				'button-h-color' => '',
				'font-size-button' => ['desktop' => '14', 'desktop-unit' => 'pt'],
				'header-menu1-color-responsive' => ['desktop' => 'var(--ast-global-color-3)'],
			])
		);
	}
}
````

- [ ] **Étape 2 : vérifier l'échec**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesSaisieTest`
Expected : erreur fatale, `inc/astra_styles_saisie.php` introuvable.

- [ ] **Étape 3 : écrire `inc/astra_styles_saisie.php`**

````php
<?php
/**
 * Astra pour SPIP — saisie des styles du Customizer (formulaire configurer_astra_styles).
 *
 * Fonctions pures : sections du formulaire, champs prêts à afficher, fusion de la saisie
 * (valeur ordinateur seulement) dans les structures d'Astra enregistrées, valeurs refusées.
 *
 * @license GPL-3.0-or-later
 */

require_once __DIR__ . '/astra_styles.php';

/** Sections du formulaire : clé de langue (astra:styles_section_<clé>) => motif des options. */
function astra_styles_sections(): array {
	return [
		'menu_1' => '/^header-menu1-/',
		'menu_2' => '/^header-menu2-/',
		'bouton' => '/^(button-|theme-button-|font-[a-z]+-button$)/',
		'bouton_entete' => '/^header-button1-/',
		'identite' => '/(site-title|site-tagline|logo-width)/',
		'widget_1' => '/^footer-widget-1-/',
		'widget_2' => '/^footer-widget-2-/',
		'widget_3' => '/^footer-widget-3-/',
		'widget_4' => '/^footer-widget-4-/',
		'copyright' => '/copyright/',
		'sociaux_entete' => '/^header-social-1-/',
		'sociaux_pied' => '/^footer-social-1-/',
	];
}

/** Clé de langue du libellé d'une option : les options d'éléments semblables la partagent. */
function astra_styles_libelle(string $option): string {
	$o = preg_replace(
		['/^header-menu\d-/', '/^footer-widget-\d-/', '/^header-button1-/', '/^(header|footer)-social-1-/'],
		['menu-', 'widget-', 'bouton-entete-', 'social-'],
		$option
	);
	return 'astra:style_' . str_replace('-', '_', $o);
}

/**
 * Champs d'une section, prêts à afficher : option, type, responsive, libellé, valeur ordinateur,
 * unité, unités et valeurs acceptées. $styles : réglages enregistrés ou saisie postée.
 */
function astra_styles_champs($styles, string $section): array {
	$styles = is_array($styles) ? $styles : [];
	$motif = astra_styles_sections()[$section] ?? '/^$/';
	$champs = [];
	foreach (astra_styles_registre() as $option => $d) {
		if (!preg_match($motif, $option)) {
			continue;
		}
		$v = $styles[$option] ?? '';
		$valeur = $d['responsive'] ? (is_array($v) ? ($v['desktop'] ?? '') : '') : $v;
		$champs[] = [
			'option' => $option,
			'nom' => $d['responsive'] ? "s[$option][desktop]" : "s[$option]",
			'nom_unite' => "s[$option][desktop-unit]",
			'type' => $d['type'],
			'responsive' => $d['responsive'],
			'libelle' => astra_styles_libelle($option),
			// Booléens d'Astra (true, false, 1, 0) proposés comme « 1 » et « 0 ».
			'valeur' => is_bool($valeur) ? ($valeur ? '1' : '0') : $valeur,
			'unite' => is_array($v) ? ($v['desktop-unit'] ?? 'px') : 'px',
			'unites' => $d['unites'] ?? [],
			'valeurs' => $d['valeurs'] ?? [],
		];
	}
	return $champs;
}

/** Nom d'un sous-champ : `#VALEUR{nom}|astra_styles_nom{top}` → s[option][desktop][top]. */
function astra_styles_nom(string $nom, string $cle): string {
	return $nom . '[' . $cle . ']';
}

/**
 * Fusionne la saisie dans les réglages enregistrés : pour une option responsive, seules la valeur
 * `desktop` et son unité changent, `tablet` et `mobile` sont gardés ; les champs d'une structure
 * (côtés, fond, extras de police) absents de la saisie sont gardés. Une option jamais réglée et
 * laissée vide n'est pas enregistrée.
 */
function astra_styles_saisie_fusionner(array $existant, array $saisie): array {
	$r = $existant;
	foreach (astra_styles_registre() as $o => $d) {
		if (!array_key_exists($o, $saisie)) {
			continue;
		}
		$v = $saisie[$o];
		$avant = $existant[$o] ?? null;
		if ($d['responsive']) {
			$x = is_array($avant) ? $avant : [];
			foreach (is_array($v) ? $v : [] as $cle => $val) {
				if ($cle === 'desktop' && is_array($val)) {
					$x[$cle] = array_merge(is_array($x[$cle] ?? null) ? $x[$cle] : [], $val);
				} elseif ($cle === 'desktop' || $cle === 'desktop-unit') {
					$x[$cle] = $val;
				}
			}
			$v = $x;
		} elseif (is_array($v)) {
			$v = array_merge(is_array($avant) ? $avant : [], $v);
		}
		if ($avant === null && !astra_styles_renseigne($d['responsive'] ? astra_styles_ordi($v) : $v)) {
			continue;
		}
		$r[$o] = $v;
	}
	return $r;
}

/** Feuilles d'une valeur : chemin => valeur scalaire. */
function astra_styles_feuilles($v, string $chemin = ''): array {
	if (!is_array($v)) {
		return [$chemin => $v];
	}
	$r = [];
	foreach ($v as $cle => $x) {
		$r += astra_styles_feuilles($x, $chemin . '/' . $cle);
	}
	return $r;
}

/** Options dont une valeur saisie non vide est refusée par la normalisation commune. */
function astra_styles_saisie_refusees(array $saisie): array {
	$refusees = [];
	foreach ($saisie as $o => $v) {
		$normalise = astra_styles_normaliser([$o => $v]);
		$gardees = astra_styles_feuilles($normalise[$o] ?? []);
		foreach (astra_styles_feuilles($v) as $chemin => $x) {
			if ($x !== '' && $x !== null && !array_key_exists($chemin, $gardees)) {
				$refusees[] = $o;
				break;
			}
		}
	}
	return $refusees;
}
````

- [ ] **Étape 4 : chaînes de langue** — dans la section `// S`, après `'sous_rubriques' => …,`

`lang/astra_fr.php` :
```php
	'style_ast_header_responsive_logo_width' => 'Largeur du logo (px)',
	'style_bouton_entete_back_color' => 'Couleur de fond',
	'style_bouton_entete_back_h_color' => 'Couleur de fond au survol',
	'style_bouton_entete_border_color' => 'Couleur de la bordure',
	'style_bouton_entete_border_h_color' => 'Couleur de la bordure au survol',
	'style_bouton_entete_border_radius_fields' => 'Rayon des coins',
	'style_bouton_entete_border_size' => 'Épaisseur de la bordure (px)',
	'style_bouton_entete_font_extras' => 'Interligne, espacement et casse',
	'style_bouton_entete_font_family' => 'Police',
	'style_bouton_entete_font_size' => 'Taille du texte',
	'style_bouton_entete_font_weight' => 'Graisse',
	'style_bouton_entete_text_color' => 'Couleur du texte',
	'style_bouton_entete_text_h_color' => 'Couleur du texte au survol',
	'style_button_bg_color' => 'Couleur de fond',
	'style_button_bg_h_color' => 'Couleur de fond au survol',
	'style_button_color' => 'Couleur du texte',
	'style_button_h_color' => 'Couleur du texte au survol',
	'style_button_radius_fields' => 'Rayon des coins',
	'style_display_site_tagline_responsive' => 'Afficher le slogan',
	'style_display_site_title_responsive' => 'Afficher le nom du site',
	'style_font_extras_button' => 'Interligne, espacement et casse',
	'style_font_family_button' => 'Police',
	'style_font_size_button' => 'Taille du texte',
	'style_font_size_section_footer_copyright' => 'Taille du texte',
	'style_font_size_site_tagline' => 'Taille du slogan',
	'style_font_size_site_title' => 'Taille du nom du site',
	'style_font_weight_button' => 'Graisse',
	'style_footer_copyright_color' => 'Couleur du texte',
	'style_header_color_h_site_title' => 'Couleur du nom du site au survol',
	'style_header_color_site_tagline' => 'Couleur du slogan',
	'style_header_color_site_title' => 'Couleur du nom du site',
	'style_menu_a_bg_color_responsive' => 'Couleur de fond de la page courante',
	'style_menu_a_color_responsive' => 'Couleur de la page courante',
	'style_menu_bg_obj_responsive' => 'Couleur de fond du menu',
	'style_menu_color_responsive' => 'Couleur des liens',
	'style_menu_font_extras' => 'Interligne, espacement et casse',
	'style_menu_font_family' => 'Police',
	'style_menu_font_size' => 'Taille du texte',
	'style_menu_font_weight' => 'Graisse',
	'style_menu_h_bg_color_responsive' => 'Couleur de fond au survol',
	'style_menu_h_color_responsive' => 'Couleur des liens au survol',
	'style_menu_submenu_b_color' => 'Couleur de la bordure des sous-menus',
	'style_menu_submenu_border' => 'Épaisseur de la bordure des sous-menus (px)',
	'style_social_color' => 'Couleur des icônes',
	'style_social_color_type' => 'Couleurs des icônes',
	'style_social_h_color' => 'Couleur des icônes au survol',
	'style_social_size' => 'Taille des icônes (px)',
	'style_social_space' => 'Espace entre les icônes (px)',
	'style_theme_button_border_group_border_size' => 'Épaisseur de la bordure (px)',
	'style_theme_button_padding' => 'Marges intérieures',
	'style_widget_color' => 'Couleur du texte',
	'style_widget_content_font_size' => 'Taille du texte',
	'style_widget_font_size' => 'Taille des titres',
	'style_widget_link_color' => 'Couleur des liens',
	'style_widget_link_h_color' => 'Couleur des liens au survol',
	'style_widget_title_color' => 'Couleur des titres',
	'styles_bas' => 'bas',
	'styles_casse' => 'Casse',
	'styles_couleur_explication' => 'Une couleur de la palette (<code>var(--ast-global-color-0)</code> à <code>8</code>), hexadécimale ou <code>rgba()</code>.',
	'styles_defaut' => 'Réglage d’Astra',
	'styles_droite' => 'droite',
	'styles_enum_custom' => 'Personnalisées',
	'styles_enum_official' => 'Couleurs des marques',
	'styles_erreurs_saisie' => 'Certaines valeurs sont refusées : corrigez-les ou videz-les.',
	'styles_espacement' => 'Espacement des lettres',
	'styles_explication' => 'Réglages du Customizer d’Astra, sous les mêmes noms. Un champ vide garde le réglage d’Astra.',
	'styles_gauche' => 'gauche',
	'styles_haut' => 'haut',
	'styles_interligne' => 'Interligne',
	'styles_masque' => 'Masqué',
	'styles_responsive' => 'Ordinateur. Les valeurs tablette et mobile sont conservées mais pas encore appliquées.',
	'styles_section_bouton' => 'Bouton',
	'styles_section_bouton_entete' => 'Bouton de l’en-tête',
	'styles_section_copyright' => 'Copyright',
	'styles_section_identite' => 'Identité du site',
	'styles_section_menu_1' => 'Menu principal',
	'styles_section_menu_2' => 'Menu secondaire',
	'styles_section_sociaux_entete' => 'Icônes sociales de l’en-tête',
	'styles_section_sociaux_pied' => 'Icônes sociales du pied de page',
	'styles_section_widget_1' => 'Widget 1 du pied de page',
	'styles_section_widget_2' => 'Widget 2 du pied de page',
	'styles_section_widget_3' => 'Widget 3 du pied de page',
	'styles_section_widget_4' => 'Widget 4 du pied de page',
	'styles_titre' => 'Couleurs et typographie',
	'styles_valeur_refusee' => 'Valeur refusée.',
	'styles_visible' => 'Visible',
```
`lang/astra_en.php` :
```php
	'style_ast_header_responsive_logo_width' => 'Logo width (px)',
	'style_bouton_entete_back_color' => 'Background color',
	'style_bouton_entete_back_h_color' => 'Background color on hover',
	'style_bouton_entete_border_color' => 'Border color',
	'style_bouton_entete_border_h_color' => 'Border color on hover',
	'style_bouton_entete_border_radius_fields' => 'Corner radius',
	'style_bouton_entete_border_size' => 'Border width (px)',
	'style_bouton_entete_font_extras' => 'Line height, spacing and case',
	'style_bouton_entete_font_family' => 'Font',
	'style_bouton_entete_font_size' => 'Font size',
	'style_bouton_entete_font_weight' => 'Font weight',
	'style_bouton_entete_text_color' => 'Text color',
	'style_bouton_entete_text_h_color' => 'Text color on hover',
	'style_button_bg_color' => 'Background color',
	'style_button_bg_h_color' => 'Background color on hover',
	'style_button_color' => 'Text color',
	'style_button_h_color' => 'Text color on hover',
	'style_button_radius_fields' => 'Corner radius',
	'style_display_site_tagline_responsive' => 'Show the tagline',
	'style_display_site_title_responsive' => 'Show the site title',
	'style_font_extras_button' => 'Line height, spacing and case',
	'style_font_family_button' => 'Font',
	'style_font_size_button' => 'Font size',
	'style_font_size_section_footer_copyright' => 'Font size',
	'style_font_size_site_tagline' => 'Tagline size',
	'style_font_size_site_title' => 'Site title size',
	'style_font_weight_button' => 'Font weight',
	'style_footer_copyright_color' => 'Text color',
	'style_header_color_h_site_title' => 'Site title color on hover',
	'style_header_color_site_tagline' => 'Tagline color',
	'style_header_color_site_title' => 'Site title color',
	'style_menu_a_bg_color_responsive' => 'Current page background color',
	'style_menu_a_color_responsive' => 'Current page color',
	'style_menu_bg_obj_responsive' => 'Menu background color',
	'style_menu_color_responsive' => 'Link color',
	'style_menu_font_extras' => 'Line height, spacing and case',
	'style_menu_font_family' => 'Font',
	'style_menu_font_size' => 'Font size',
	'style_menu_font_weight' => 'Font weight',
	'style_menu_h_bg_color_responsive' => 'Background color on hover',
	'style_menu_h_color_responsive' => 'Link color on hover',
	'style_menu_submenu_b_color' => 'Submenu border color',
	'style_menu_submenu_border' => 'Submenu border width (px)',
	'style_social_color' => 'Icon color',
	'style_social_color_type' => 'Icon colors',
	'style_social_h_color' => 'Icon color on hover',
	'style_social_size' => 'Icon size (px)',
	'style_social_space' => 'Space between icons (px)',
	'style_theme_button_border_group_border_size' => 'Border width (px)',
	'style_theme_button_padding' => 'Padding',
	'style_widget_color' => 'Text color',
	'style_widget_content_font_size' => 'Font size',
	'style_widget_font_size' => 'Title size',
	'style_widget_link_color' => 'Link color',
	'style_widget_link_h_color' => 'Link color on hover',
	'style_widget_title_color' => 'Title color',
	'styles_bas' => 'bottom',
	'styles_casse' => 'Case',
	'styles_couleur_explication' => 'A palette color (<code>var(--ast-global-color-0)</code> to <code>8</code>), hexadecimal or <code>rgba()</code>.',
	'styles_defaut' => 'Astra setting',
	'styles_droite' => 'right',
	'styles_enum_custom' => 'Custom',
	'styles_enum_official' => 'Brand colors',
	'styles_erreurs_saisie' => 'Some values were refused: correct or clear them.',
	'styles_espacement' => 'Letter spacing',
	'styles_explication' => 'Astra Customizer settings, under the same names. An empty field keeps Astra’s setting.',
	'styles_gauche' => 'left',
	'styles_haut' => 'top',
	'styles_interligne' => 'Line height',
	'styles_masque' => 'Hidden',
	'styles_responsive' => 'Desktop. Tablet and mobile values are kept but not applied yet.',
	'styles_section_bouton' => 'Button',
	'styles_section_bouton_entete' => 'Header button',
	'styles_section_copyright' => 'Copyright',
	'styles_section_identite' => 'Site identity',
	'styles_section_menu_1' => 'Primary menu',
	'styles_section_menu_2' => 'Secondary menu',
	'styles_section_sociaux_entete' => 'Header social icons',
	'styles_section_sociaux_pied' => 'Footer social icons',
	'styles_section_widget_1' => 'Footer widget 1',
	'styles_section_widget_2' => 'Footer widget 2',
	'styles_section_widget_3' => 'Footer widget 3',
	'styles_section_widget_4' => 'Footer widget 4',
	'styles_titre' => 'Colors and typography',
	'styles_valeur_refusee' => 'Value refused.',
	'styles_visible' => 'Visible',
```

- [ ] **Étape 5 : vérifier le succès du test unitaire**

Run : `vendor/bin/phpunit --testsuite unit --filter StylesSaisieTest`
Expected : `OK (6 tests, …)`.

- [ ] **Étape 6 : écrire le test d'intégration**

````php
<?php

use PHPUnit\Framework\TestCase;

/** Formulaire configurer_astra_styles : charger, vérifier, traiter, rendu des champs. */
final class StylesFormulaireTest extends TestCase {

	public static function setUpBeforeClass(): void {
		include_spip('formulaires/configurer_astra_styles');
	}

	protected function tearDown(): void {
		effacer_config('astra/styles');
		set_request('s', null);
	}

	public function testChargerDonneLesReglages(): void {
		ecrire_config('astra/styles', ['button-color' => '#123456']);
		$this->assertSame(['s' => ['button-color' => '#123456']], formulaires_configurer_astra_styles_charger_dist());
	}

	public function testVerifierSignaleLesValeursRefusees(): void {
		set_request('s', ['button-color' => 'rouge', 'button-h-color' => '#ffffff']);
		$erreurs = formulaires_configurer_astra_styles_verifier_dist();
		$this->assertArrayHasKey('s_button-color', $erreurs);
		$this->assertArrayNotHasKey('s_button-h-color', $erreurs);
		$this->assertArrayHasKey('message_erreur', $erreurs);
	}

	public function testTraiterGardeTabletteEtMobile(): void {
		ecrire_config('astra/styles', [
			'header-menu1-color-responsive' => ['desktop' => '#111111', 'tablet' => '#222222', 'mobile' => '#333333'],
		]);
		set_request('s', [
			'header-menu1-color-responsive' => ['desktop' => 'var(--ast-global-color-0)'],
			'button-color' => '',
		]);
		$this->assertSame([], formulaires_configurer_astra_styles_verifier_dist());
		formulaires_configurer_astra_styles_traiter_dist();
		$this->assertSame([
			'header-menu1-color-responsive' => ['desktop' => 'var(--ast-global-color-0)', 'tablet' => '#222222', 'mobile' => '#333333'],
		], lire_config('astra/styles'));
	}

	public function testRenduDesChamps(): void {
		$html = recuperer_fond('formulaires/configurer_astra_styles', ['editable' => true, 's' => ['button-radius-fields' => ['desktop' => ['top' => 8], 'desktop-unit' => 'em']]]);
		$this->assertSame(12, substr_count($html, '<fieldset'));
		$this->assertStringContainsString('name="s[header-menu1-color-responsive][desktop]"', $html);
		$this->assertStringContainsString('name="s[button-radius-fields][desktop][top]" value="8"', $html);
		$this->assertMatchesRegularExpression('~name="s\[button-radius-fields\]\[desktop-unit\]".*?<option value="em" selected="selected">~s', $html);
		$this->assertStringContainsString('name="s[header-menu1-bg-obj-responsive][desktop][background-color]"', $html);
		$this->assertStringContainsString('list="astra-styles-palette"', $html);
	}
}
````

- [ ] **Étape 7 : charger la saisie dans les squelettes** — dans `astra_fonctions.php`, `include_once __DIR__ . '/inc/astra_styles.php';` devient `include_once __DIR__ . '/inc/astra_styles_saisie.php';` (qui charge `astra_styles.php`).

- [ ] **Étape 8 : écrire le formulaire**

`formulaires/configurer_astra_styles.php` :
````php
<?php
/**
 * Astra pour SPIP — formulaire des styles du Customizer (méta astra/styles).
 *
 * Seule la valeur ordinateur des options responsives est saisie ; les valeurs tablette et mobile
 * enregistrées (seed, import) sont gardées.
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/astra_styles_saisie');

function formulaires_configurer_astra_styles_charger_dist() {
	$styles = lire_config('astra/styles');
	return ['s' => is_array($styles) ? $styles : []];
}

function formulaires_configurer_astra_styles_verifier_dist() {
	$erreurs = [];
	foreach (astra_styles_saisie_refusees(astra_styles_saisie_postee()) as $option) {
		$erreurs['s_' . $option] = _T('astra:styles_valeur_refusee');
	}
	if ($erreurs) {
		$erreurs['message_erreur'] = _T('astra:styles_erreurs_saisie');
	}
	return $erreurs;
}

function formulaires_configurer_astra_styles_traiter_dist() {
	$styles = lire_config('astra/styles');
	$styles = astra_styles_saisie_fusionner(is_array($styles) ? $styles : [], astra_styles_saisie_postee());
	ecrire_config('astra/styles', astra_styles_normaliser($styles));

	// Les styles figurent sur toutes les pages.
	include_spip('inc/invalideur');
	suivre_invalideur('1');

	return ['message_ok' => _T('config_info_enregistree'), 'editable' => true];
}

/** Champs postés : s[<option>]. */
function astra_styles_saisie_postee(): array {
	$s = _request('s');
	return is_array($s) ? $s : [];
}
````

`formulaires/configurer_astra_styles.html` :
````html
[(#REM)

	Styles du Customizer d'Astra (formulaire CVT explicite : formulaires/configurer_astra_styles.php).
	Une section par élément ; champs générés depuis le registre (inc/astra_styles_saisie.php).

]<div class="formulaire_spip formulaire_configurer formulaire_#FORM">
	[<p class="reponse_formulaire reponse_formulaire_ok" role="status">(#ENV*{message_ok})</p>]
	[<p class="reponse_formulaire reponse_formulaire_erreur" role="alert">(#ENV*{message_erreur})</p>]
	<BOUCLE_editable(CONDITION){si #ENV{editable}}>
	<form method="post" action="#ENV{action}"><div>
		#ACTION_FORMULAIRE
		<style>
			.astra-styles-cotes input, .astra-styles-unite input { max-width: 6em; }
		</style>
		<p class="explication"><:astra:styles_explication:></p>
		<datalist id="astra-styles-palette">
			<BOUCLE_palette(DATA){enum 0,8}><option value="var(--ast-global-color-#VALEUR)">[(#VAL{astra:cfg_couleur_}|concat{#VALEUR}|_T)]</option></BOUCLE_palette>
		</datalist>
		<datalist id="astra-polices-locales">
			<BOUCLE_polices(DATA){source tableau, #VAL|astra_polices_locales}><option value="[(#VALEUR{pile}|attribut_html)]">#VALEUR{nom}</option></BOUCLE_polices>
		</datalist>

		<BOUCLE_sections(DATA){source tableau, #VAL|astra_styles_sections}>
		<fieldset class="astra-styles-section astra-styles-#CLE">
			<legend>[(#VAL{astra:styles_section_}|concat{#CLE}|_T)]</legend>
			<div class="editer-groupe">
			<BOUCLE_champs(DATA){source tableau, #ENV{s}|astra_styles_champs{#_sections:CLE}}>
			#SET{c,#VALEUR}
			#SET{erreur,#ENV*{erreurs}|table_valeur{#VAL{s_}|concat{#GET{c}|table_valeur{option}}}}
			<div class="editer editer_s_[(#GET{c}|table_valeur{option})][ (#GET{erreur}|oui)erreur]">
				<label for="s_[(#GET{c}|table_valeur{option})]">[(#GET{c}|table_valeur{libelle}|_T)]</label>
				[(#GET{c}|table_valeur{responsive}|oui)<p class="explication"><:astra:styles_responsive:></p>]
				[<span class="erreur_message">(#GET{erreur})</span>]
				<BOUCLE_couleur(CONDITION){si #GET{c}|table_valeur{type}|=={couleur}}>
				<input type="text" class="text" id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom})]" value="[(#GET{c}|table_valeur{valeur})]" list="astra-styles-palette" />
				</BOUCLE_couleur>
				<BOUCLE_police(CONDITION){si #GET{c}|table_valeur{type}|=={police}}>
				<input type="text" class="text" id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom})]" value="[(#GET{c}|table_valeur{valeur})]" list="astra-polices-locales" />
				</BOUCLE_police>
				<BOUCLE_nombre(CONDITION){si #GET{c}|table_valeur{type}|=={nombre}}>
				<input type="number" min="0" step="any" class="text" id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom})]" value="[(#GET{c}|table_valeur{valeur})]" />
				</BOUCLE_nombre>
				<BOUCLE_taille(CONDITION){si #GET{c}|table_valeur{type}|=={taille}}>
				<span class="astra-styles-unite">
					<input type="number" min="0" step="any" class="text" id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom})]" value="[(#GET{c}|table_valeur{valeur})]" />
					<select name="[(#GET{c}|table_valeur{nom_unite})]" aria-label="unité">
						<BOUCLE_unites(DATA){source tableau, #GET{c}|table_valeur{unites}}><option value="#VALEUR"[(#VALEUR|=={#GET{c}|table_valeur{unite}}|oui)selected="selected"]>#VALEUR</option></BOUCLE_unites>
					</select>
				</span>
				</BOUCLE_taille>
				<BOUCLE_graisse(CONDITION){si #GET{c}|table_valeur{type}|=={graisse}}>
				<select id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom})]">
					<option value=""><:astra:styles_defaut:></option>
					<BOUCLE_graisses(DATA){liste inherit, normal, bold, 100, 200, 300, 400, 500, 600, 700, 800, 900}><option value="#VALEUR"[(#VALEUR|=={#GET{c}|table_valeur{valeur}}|oui)selected="selected"]>#VALEUR</option></BOUCLE_graisses>
				</select>
				</BOUCLE_graisse>
				<BOUCLE_enum(CONDITION){si #GET{c}|table_valeur{type}|=={enum}}>
				<select id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom})]">
					<option value=""><:astra:styles_defaut:></option>
					<BOUCLE_valeurs(DATA){source tableau, #GET{c}|table_valeur{valeurs}}><option value="#VALEUR"[(#VALEUR|=={#GET{c}|table_valeur{valeur}}|oui)selected="selected"]>[(#VAL{astra:styles_enum_}|concat{#VALEUR}|_T)]</option></BOUCLE_valeurs>
				</select>
				</BOUCLE_enum>
				<BOUCLE_booleen(CONDITION){si #GET{c}|table_valeur{type}|=={booleen}}>
				#SET{v,#GET{c}|table_valeur{valeur}|strval}
				<select id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom})]">
					<option value=""><:astra:styles_defaut:></option>
					<option value="1"[(#GET{v}|=={1}|oui)selected="selected"]><:astra:styles_visible:></option>
					<option value="0"[(#GET{v}|=={0}|oui)selected="selected"]><:astra:styles_masque:></option>
				</select>
				</BOUCLE_booleen>
				<BOUCLE_cotes(CONDITION){si #GET{c}|table_valeur{type}|=={quatre-cotes}}>
				<span class="astra-styles-cotes" id="s_[(#GET{c}|table_valeur{option})]">
					<BOUCLE_cote(DATA){liste top, right, bottom, left}>
					#SET{etiquette,#ARRAY{top,haut,right,droite,bottom,bas,left,gauche}|table_valeur{#VALEUR}}
					<input type="number" min="0" step="any" class="text" name="[(#GET{c}|table_valeur{nom}|astra_styles_nom{#VALEUR})]" value="[(#GET{c}|table_valeur{valeur}|table_valeur{#VALEUR})]" aria-label="[(#VAL{astra:styles_}|concat{#GET{etiquette}}|_T)]" placeholder="[(#VAL{astra:styles_}|concat{#GET{etiquette}}|_T)]" />
					</BOUCLE_cote>
					<BOUCLE_unite_cotes(CONDITION){si #GET{c}|table_valeur{responsive}}>
					<select name="[(#GET{c}|table_valeur{nom_unite})]" aria-label="unité">
						<BOUCLE_unites_cotes(DATA){source tableau, #GET{c}|table_valeur{unites}}><option value="#VALEUR"[(#VALEUR|=={#GET{c}|table_valeur{unite}}|oui)selected="selected"]>#VALEUR</option></BOUCLE_unites_cotes>
					</select>
					</BOUCLE_unite_cotes>
				</span>
				</BOUCLE_cotes>
				<BOUCLE_fond(CONDITION){si #GET{c}|table_valeur{type}|=={fond}}>
				<input type="text" class="text" id="s_[(#GET{c}|table_valeur{option})]" name="[(#GET{c}|table_valeur{nom}|astra_styles_nom{background-color})]" value="[(#GET{c}|table_valeur{valeur}|table_valeur{background-color})]" list="astra-styles-palette" />
				</BOUCLE_fond>
				<BOUCLE_extras(CONDITION){si #GET{c}|table_valeur{type}|=={extras-police}}>
				#SET{v,#GET{c}|table_valeur{valeur}}
				<span class="astra-styles-unite" id="s_[(#GET{c}|table_valeur{option})]">
					<label><:astra:styles_interligne:>
					<input type="number" min="0" step="any" class="text" name="[(#GET{c}|table_valeur{nom}|astra_styles_nom{line-height})]" value="[(#GET{v}|table_valeur{line-height})]" /></label>
					<select name="[(#GET{c}|table_valeur{nom}|astra_styles_nom{line-height-unit})]" aria-label="unité">
						<BOUCLE_unites_interligne(DATA){source tableau, #ARRAY{0,"",1,px,2,em}}><option value="#VALEUR"[(#VALEUR|=={#GET{v}|table_valeur{line-height-unit}}|oui)selected="selected"]>#VALEUR</option></BOUCLE_unites_interligne>
					</select>
					<label><:astra:styles_espacement:>
					<input type="number" step="any" class="text" name="[(#GET{c}|table_valeur{nom}|astra_styles_nom{letter-spacing})]" value="[(#GET{v}|table_valeur{letter-spacing})]" /></label>
					<select name="[(#GET{c}|table_valeur{nom}|astra_styles_nom{letter-spacing-unit})]" aria-label="unité">
						<BOUCLE_unites_espacement(DATA){source tableau, #ARRAY{0,"",1,px,2,em}}><option value="#VALEUR"[(#VALEUR|=={#GET{v}|table_valeur{letter-spacing-unit}}|oui)selected="selected"]>#VALEUR</option></BOUCLE_unites_espacement>
					</select>
					<label><:astra:styles_casse:>
					<select name="[(#GET{c}|table_valeur{nom}|astra_styles_nom{text-transform})]">
						<BOUCLE_casses(DATA){source tableau, #ARRAY{0,"",1,none,2,capitalize,3,uppercase,4,lowercase}}><option value="#VALEUR"[(#VALEUR|=={#GET{v}|table_valeur{text-transform}}|oui)selected="selected"]>#VALEUR</option></BOUCLE_casses>
					</select></label>
				</span>
				</BOUCLE_extras>
			</div>
			</BOUCLE_champs>
			</div>
		</fieldset>
		</BOUCLE_sections>

		<p class="boutons"><input type="submit" class="submit btn" value="<:bouton_enregistrer:>" /></p>
	</div></form>
	</BOUCLE_editable>
</div>
````

`prive/squelettes/contenu/configurer_astra_styles.html` :
````html
[(#AUTORISER{configurer,_astra}|sinon_interdire_acces)]
<h1 class="grostitre"><:astra:styles_titre:></h1>
<div class="ajax">
	#FORMULAIRE_CONFIGURER_ASTRA_STYLES
</div>
````

Dans `paquet.xml`, après le menu `configurer_astra_hfb` :
```xml
	<menu nom="configurer_astra_styles" titre="astra:styles_titre" parent="menu_squelette" icone="images/astra-xx.svg" action="configurer_astra_styles" />
```

- [ ] **Étape 9 : vérifier le succès**

Run : `rm -f vendor/spip/spip/tmp/cache/skel/*.php; vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Expected : tout passe, dont `StylesFormulaireTest` (4 tests).

- [ ] **Étape 10 : test de bout en bout**

````js
// Test de bout en bout du formulaire « Couleurs et typographie » (styles du Customizer).
// Usage : URL=http://127.0.0.1:8402 LOGIN=admin MDP=… NODE_PATH=$PW/node_modules node tests/e2e/formulaire_styles.cjs
// Se connecte à l'espace privé, règle la couleur du menu, celle du bouton et l'affichage du
// slogan, enregistre, vérifie le refus d'une valeur invalide, puis le site public. Modifie la
// configuration du site : à lancer sur un site de test.
const { chromium } = require('playwright');

const URL = (process.env.URL || 'http://127.0.0.1:8402').replace(/\/$/, '');
// SPIP sert toujours le cache aux robots, dont HeadlessChrome : se présenter comme un navigateur ordinaire.
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
const FORM = '.formulaire_configurer_astra_styles';
const echecs = [];
const verifier = (condition, message) => { if (!condition) echecs.push(message); };

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 }, userAgent: AGENT });
  page.on('pageerror', e => echecs.push('erreur JavaScript : ' + e.message));

  await page.goto(URL + '/spip.php?page=login&url=ecrire%2F%3Fexec%3Dconfigurer_astra_styles', { waitUntil: 'load' });
  await page.fill('#var_login', process.env.LOGIN || 'admin');
  await page.fill('input[type=password]', process.env.MDP || '');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('.formulaire_login [type=submit], form [type=submit]')]);
  if (!page.url().includes('exec=configurer_astra_styles')) {
    await page.goto(URL + '/ecrire/?exec=configurer_astra_styles', { waitUntil: 'load' });
  }
  verifier(await page.locator(`${FORM} form`).count() === 1, 'formulaire absent de la page de configuration');

  await page.fill('input[name="s[header-menu1-color-responsive][desktop]"]', 'var(--ast-global-color-0)');
  await page.fill('input[name="s[button-color]"]', '#123456');
  await page.selectOption('select[name="s[display-site-tagline-responsive][desktop]"]', '1');
  await page.click(`${FORM} .boutons [type=submit]`);
  await page.locator(`${FORM} .reponse_formulaire_ok`).waitFor({ timeout: 10000 }).catch(() => {});
  verifier(await page.locator(`${FORM} .reponse_formulaire_ok`).count() === 1, 'pas de message de confirmation après enregistrement');
  verifier(await page.inputValue('input[name="s[button-color]"]') === '#123456', 'couleur du bouton non conservée dans le formulaire');

  // Valeur refusée : signalée sur son champ, rien n'est enregistré.
  await page.fill('input[name="s[button-h-color]"]', 'rouge');
  await page.click(`${FORM} .boutons [type=submit]`);
  await page.locator(`${FORM} .reponse_formulaire_erreur`).waitFor({ timeout: 10000 }).catch(() => {});
  verifier(await page.locator(`${FORM} .editer_s_button-h-color.erreur .erreur_message`).count() === 1, 'valeur refusée non signalée sur son champ');

  const publique = await browser.newPage({ viewport: { width: 1280, height: 900 }, userAgent: AGENT });
  await publique.goto(URL + '/', { waitUntil: 'load' });
  const css = await publique.locator('#astra-styles-inline-css').textContent().catch(() => '');
  verifier(css.includes('.wp-block-button .wp-block-button__link{color:#123456;}'), 'règle du bouton absente du site public');
  verifier(!css.includes('rouge'), 'valeur refusée présente dans le CSS public');
  const lien = publique.locator('.ast-builder-menu-1 .menu-item > .menu-link').first();
  const couleur0 = await publique.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--ast-global-color-0').trim());
  const couleurLien = await lien.evaluate(e => getComputedStyle(e).color);
  const attendue = await publique.evaluate((c) => { const d = document.createElement('div'); d.style.color = c; document.body.appendChild(d); const r = getComputedStyle(d).color; d.remove(); return r; }, couleur0);
  verifier(couleurLien === attendue, `couleur du menu ${couleurLien}, attendue ${attendue}`);
  verifier(await publique.locator('.site-header .site-description').first().isVisible(), 'slogan non affiché');
  await browser.close();

  if (echecs.length) {
    console.error(echecs.join('\n'));
    process.exit(1);
  }
  console.log('Formulaire des styles : OK');
})();
````

Préparer le SPIP de test (valeurs tablette et mobile comme après un import, slogan), le servir, lancer le test, relire la méta, puis nettoyer :
```bash
php -r 'require "tests/bootstrap_integration.php"; ecrire_config("astra/styles", ["header-menu1-color-responsive" => ["desktop" => "#111111", "tablet" => "#222222", "mobile" => "#333333"]]); ecrire_meta("slogan_site", "Jardiner au naturel");'
(cd vendor/spip/spip && php -S 127.0.0.1:8460 >/dev/null 2>&1 & echo $! > /tmp/astra-e2e.pid)
URL=http://127.0.0.1:8460 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire_styles.cjs
php -r 'require "tests/bootstrap_integration.php"; echo json_encode(lire_config("astra/styles")), "\n"; effacer_config("astra/styles"); ecrire_meta("slogan_site", "");'
kill "$(cat /tmp/astra-e2e.pid)"
```
Expected : `Formulaire des styles : OK`, puis
`{"header-menu1-color-responsive":{"desktop":"var(--ast-global-color-0)","tablet":"#222222","mobile":"#333333"},"button-color":"#123456","display-site-tagline-responsive":{"desktop":"1"}}`

- [ ] **Étape 11 : commit**

```bash
git add inc/astra_styles_saisie.php formulaires/configurer_astra_styles.php formulaires/configurer_astra_styles.html prive/squelettes/contenu/configurer_astra_styles.html
git add astra_fonctions.php paquet.xml lang/astra_fr.php lang/astra_en.php tests/unit/StylesSaisieTest.php tests/integration/StylesFormulaireTest.php tests/e2e/formulaire_styles.cjs
git commit -m "Formulaire « Couleurs et typographie » : champs générés depuis le registre, tablette et mobile conservés"
```

---

### Tâche 14 : références visuelles R6–R11

Une référence par groupe, plus R11 (toutes les options, polices du texte et des titres) ;
comparaison des captures, des styles calculés (dont survol, focus, page courante, sous-menu
ouvert) à 1280, 768 et 390 px, et des polices chargées.

**Fichiers :**
- Modifier : `tests/references/styles/appliquer.php`, `tests/references/wp/commun.php`, `tests/comparaison/configurations.php`, `tests/comparaison/appliquer.sh`, `tests/comparaison/comparer_captures.sh`, `tests/comparaison/comparer_captures.cjs`, `tests/comparaison/comparer_html.php`
- Créer : `tests/references/wp/R{6-menu,7-bouton,8-identite,9-pied-couleurs,10-sociaux,11-combinee}.{php,json}`

**Interfaces :**
- Consomme : `astra_polices_locales()`, `astra_polices_css()` (tâche 12) ; valeurs de la tâche 1.
- Produit : clés de configuration `styles` (écrite dans `astra/styles`), `rubrique` (page d'une rubrique, par son titre sans numéro), `meta` (métas du site, dont `slogan_site`) ; dans le JSON de référence, `styles[].survol`, `styles[].focus` et `polices`.

- [ ] **Étape 1 : placement et polices côté WordPress** — `tests/references/styles/appliquer.php` devient :

````php
<?php
/**
 * Référence des styles du Customizer : état R0, éléments placés comme dans Garden Maintenance
 * (logo, menu et bouton dans l'en-tête ; quatre widgets texte, copyright et icônes sociales dans le
 * pied), puis les options du groupe (valeurs/<groupe>.json ; « base » : aucune option ;
 * « combinee » : toutes les options, plus les polices du texte et des titres).
 * Usage : GROUPE=<groupe> wp --path=$WP eval-file tests/references/styles/appliquer.php
 * Les polices locales d'Astra SPIP sont servies par le WordPress (ref_polices_locales()).
 */
require dirname(__DIR__) . '/wp/commun.php';
ref_etat_initial();
ref_menu('Principal', 'primary');
ref_menu('Principal', 'mobile_menu');
$zones = function (array $noms, array $remplies) {
	return array_merge(array_fill_keys($noms, []), $remplies);
};
$entete = ['left', 'left_center', 'center', 'right_center', 'right'];
$reglages = [
	'header-desktop-items' => [
		'popup' => ['popup_content' => ['mobile-menu']],
		'above' => $zones(array_map(function ($z) { return "above_$z"; }, $entete), []),
		'primary' => $zones(array_map(function ($z) { return "primary_$z"; }, $entete), ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'button-1']]),
		'below' => $zones(array_map(function ($z) { return "below_$z"; }, $entete), []),
	],
	'footer-desktop-items' => [
		'above' => $zones(['above_1', 'above_2', 'above_3', 'above_4', 'above_5'], []),
		'primary' => $zones(['primary_1', 'primary_2', 'primary_3', 'primary_4', 'primary_5'], ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']]),
		'below' => $zones(['below_1', 'below_2', 'below_3', 'below_4', 'below_5'], ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']]),
	],
	'hb-footer-column' => '4', 'hb-footer-layout' => ['desktop' => '4-equal', 'tablet' => 'full', 'mobile' => 'full'],
	'hbb-footer-column' => '2', 'hbb-footer-layout' => ['desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full'],
	'header-button1-text' => 'Nous contacter',
	'header-button1-link-option' => ['url' => 'http://127.0.0.1:8401/', 'new_tab' => false, 'link_rel' => ''],
];
$groupe = getenv('GROUPE') ?: 'base';
$groupes = $groupe === 'combinee' ? ['menu', 'bouton', 'bouton-entete', 'identite', 'pied', 'sociaux'] : ($groupe === 'base' ? [] : [$groupe]);
foreach ($groupes as $g) {
	$reglages += json_decode(file_get_contents(__DIR__ . "/valeurs/$g.json"), true);
}
if ($groupe === 'combinee') {
	$reglages['body-font-family'] = "'Raleway', sans-serif";
	$reglages['headings-font-family'] = "'Poppins', sans-serif";
}
ref_reglages($reglages);
foreach ([1, 2, 3, 4] as $i) {
	ref_widget_texte('footer-widget-' . $i, 'Colonne ' . $i, 'Texte de la colonne ' . $i);
}
ref_polices_locales();
````

Dans `tests/references/wp/commun.php`, à la fin de `ref_etat_initial()`, ajouter `@unlink(WPMU_PLUGIN_DIR . '/astra-polices-locales.php');`, puis après la fonction :

````php
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
````

- [ ] **Étape 2 : scripts WordPress des références**

`tests/references/wp/R6-menu.php` :
````php
<?php
// Styles du Customizer, valeurs de Garden Maintenance : Couleurs et police du menu principal.
putenv('GROUPE=menu');
require dirname(__DIR__) . '/styles/appliquer.php';
````
`R7-bouton.php` :
````php
<?php
// Styles du Customizer, valeurs de Garden Maintenance : Bouton global.
putenv('GROUPE=bouton');
require dirname(__DIR__) . '/styles/appliquer.php';
````
`R8-identite.php` :
````php
<?php
// Styles du Customizer, valeurs de Garden Maintenance : Identité du site (titre, slogan affiché, logo).
putenv('GROUPE=identite');
update_option('blogdescription', 'Un site propulsé par SPIP');
require dirname(__DIR__) . '/styles/appliquer.php';
````
`R9-pied-couleurs.php` :
````php
<?php
// Styles du Customizer, valeurs de Garden Maintenance : Couleurs et tailles des widgets et du copyright.
putenv('GROUPE=pied');
require dirname(__DIR__) . '/styles/appliquer.php';
````
`R10-sociaux.php` :
````php
<?php
// Styles du Customizer, valeurs de Garden Maintenance : Icônes sociales du pied.
putenv('GROUPE=sociaux');
require dirname(__DIR__) . '/styles/appliquer.php';
````
`R11-combinee.php` :
````php
<?php
// Styles du Customizer, valeurs de Garden Maintenance : Tous les groupes et les polices du texte et des titres (cascade).
putenv('GROUPE=combinee');
require dirname(__DIR__) . '/styles/appliquer.php';
````

- [ ] **Étape 3 : JSON des références**

`R6-menu.json` :
````json
{
	"chemin": "/?category_name=jardinage",
	"ordinateur": [
		".site-primary-header-wrap .ast-builder-menu-1",
		".site-primary-header-wrap .ast-header-button-1",
		".site-primary-footer-wrap .footer-widget-area",
		".site-below-footer-wrap .ast-footer-copyright",
		".site-below-footer-wrap .ast-footer-social-wrap"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		".ast-builder-menu-1 .sub-menu"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	],
	"styles": [
		{
			"selecteur": ".ast-builder-menu-1 .main-header-menu > .menu-item:last-child > .menu-link",
			"proprietes": [
				"color",
				"font-size",
				"font-weight",
				"font-family"
			]
		},
		{
			"selecteur": ".ast-builder-menu-1 .main-header-menu > .menu-item:last-child > .menu-link",
			"survol": ".ast-builder-menu-1 .main-header-menu > .menu-item:last-child > .menu-link",
			"proprietes": [
				"color",
				"background-color"
			]
		},
		{
			"selecteur": ".ast-builder-menu-1 .current-menu-item > .menu-link",
			"proprietes": [
				"color",
				"background-color"
			]
		},
		{
			"selecteur": ".ast-builder-menu-1 .sub-menu",
			"survol": ".ast-builder-menu-1 .menu-item-has-children",
			"proprietes": [
				"border-top-width",
				"border-top-color",
				"border-top-style",
				"visibility"
			]
		},
		{
			"selecteur": ".ast-builder-menu-1 .main-header-menu",
			"proprietes": [
				"background-color"
			]
		}
	]
}
````
`R7-bouton.json` :
````json
{
	"ordinateur": [
		".site-primary-header-wrap .ast-builder-menu-1",
		".site-primary-header-wrap .ast-header-button-1",
		".site-primary-footer-wrap .footer-widget-area",
		".site-below-footer-wrap .ast-footer-copyright",
		".site-below-footer-wrap .ast-footer-social-wrap"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		".ast-builder-menu-1 .sub-menu"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	],
	"styles": [
		{
			"selecteur": ".ast-header-button-1 .ast-custom-button",
			"proprietes": [
				"color",
				"background-color",
				"font-family",
				"font-size",
				"font-weight",
				"line-height",
				"letter-spacing",
				"text-transform",
				"padding-top",
				"padding-left",
				"border-top-left-radius",
				"border-top-width"
			]
		},
		{
			"selecteur": ".ast-header-button-1 .ast-custom-button",
			"survol": ".ast-header-button-1 .ast-custom-button",
			"proprietes": [
				"color",
				"background-color"
			]
		},
		{
			"selecteur": ".ast-header-button-1 .ast-custom-button",
			"focus": ".ast-header-button-1 .ast-custom-button",
			"proprietes": [
				"color",
				"background-color"
			]
		}
	],
	"polices": [
		"500 14px Poppins"
	]
}
````
`R8-identite.json` :
````json
{
	"ordinateur": [
		".site-primary-header-wrap .ast-builder-menu-1",
		".site-primary-header-wrap .ast-header-button-1",
		".site-primary-footer-wrap .footer-widget-area",
		".site-below-footer-wrap .ast-footer-copyright",
		".site-below-footer-wrap .ast-footer-social-wrap"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		".ast-builder-menu-1 .sub-menu"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	],
	"styles": [
		{
			"selecteur": ".site-title",
			"proprietes": [
				"font-size",
				"display"
			]
		},
		{
			"selecteur": ".site-title a",
			"proprietes": [
				"color"
			]
		},
		{
			"selecteur": ".site-header .site-description",
			"proprietes": [
				"font-size",
				"display",
				"color"
			]
		}
	]
}
````
`R9-pied-couleurs.json` :
````json
{
	"ordinateur": [
		".site-primary-header-wrap .ast-builder-menu-1",
		".site-primary-header-wrap .ast-header-button-1",
		".site-primary-footer-wrap .footer-widget-area",
		".site-below-footer-wrap .ast-footer-copyright",
		".site-below-footer-wrap .ast-footer-social-wrap"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		".ast-builder-menu-1 .sub-menu"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	],
	"styles": [
		{
			"selecteur": ".footer-widget-area[data-section=\"sidebar-widgets-footer-widget-1\"] .widget-title",
			"proprietes": [
				"color",
				"font-size"
			]
		},
		{
			"selecteur": ".footer-widget-area[data-section=\"sidebar-widgets-footer-widget-1\"].footer-widget-area-inner",
			"proprietes": [
				"color",
				"font-size"
			]
		},
		{
			"selecteur": ".footer-widget-area[data-section=\"sidebar-widgets-footer-widget-4\"] .widget-title",
			"proprietes": [
				"color",
				"font-size"
			]
		},
		{
			"selecteur": ".ast-footer-copyright",
			"proprietes": [
				"color",
				"font-size"
			]
		}
	]
}
````
`R10-sociaux.json` :
````json
{
	"ordinateur": [
		".site-primary-header-wrap .ast-builder-menu-1",
		".site-primary-header-wrap .ast-header-button-1",
		".site-primary-footer-wrap .footer-widget-area",
		".site-below-footer-wrap .ast-footer-copyright",
		".site-below-footer-wrap .ast-footer-social-wrap"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		".ast-builder-menu-1 .sub-menu"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	],
	"styles": [
		{
			"selecteur": ".ast-footer-social-1-wrap .ast-builder-social-element",
			"proprietes": [
				"margin-left",
				"margin-right"
			]
		},
		{
			"selecteur": ".ast-footer-social-1-wrap .ast-builder-social-element svg",
			"proprietes": [
				"width",
				"height",
				"fill"
			]
		},
		{
			"selecteur": ".ast-footer-social-1-wrap .ast-builder-social-element svg",
			"survol": ".ast-footer-social-1-wrap .ast-builder-social-element",
			"proprietes": [
				"fill"
			]
		}
	]
}
````
`R11-combinee.json` :
````json
{
	"ordinateur": [
		".site-primary-header-wrap .ast-builder-menu-1",
		".site-primary-header-wrap .ast-header-button-1",
		".site-primary-footer-wrap .footer-widget-area",
		".site-below-footer-wrap .ast-footer-copyright",
		".site-below-footer-wrap .ast-footer-social-wrap"
	],
	"mobile": [
		".ast-mobile-header-wrap .menu-toggle"
	],
	"presents": [
		".ast-builder-menu-1 .sub-menu"
	],
	"menuMobile": "dropdown",
	"menuOuvert": [
		".ast-mobile-header-content .ast-builder-menu-mobile .sub-menu"
	],
	"styles": [
		{
			"selecteur": ".ast-builder-menu-1 .main-header-menu > .menu-item:last-child > .menu-link",
			"proprietes": [
				"color",
				"font-size",
				"font-weight",
				"font-family"
			]
		},
		{
			"selecteur": ".ast-builder-menu-1 .main-header-menu > .menu-item:last-child > .menu-link",
			"survol": ".ast-builder-menu-1 .main-header-menu > .menu-item:last-child > .menu-link",
			"proprietes": [
				"color",
				"background-color"
			]
		},
		{
			"selecteur": ".ast-builder-menu-1 .current-menu-item > .menu-link",
			"proprietes": [
				"color",
				"background-color"
			]
		},
		{
			"selecteur": ".ast-builder-menu-1 .sub-menu",
			"survol": ".ast-builder-menu-1 .menu-item-has-children",
			"proprietes": [
				"border-top-width",
				"border-top-color",
				"border-top-style",
				"visibility"
			]
		},
		{
			"selecteur": ".ast-builder-menu-1 .main-header-menu",
			"proprietes": [
				"background-color"
			]
		},
		{
			"selecteur": ".ast-header-button-1 .ast-custom-button",
			"proprietes": [
				"color",
				"background-color",
				"font-family",
				"font-size",
				"font-weight",
				"line-height",
				"letter-spacing",
				"text-transform",
				"padding-top",
				"padding-left",
				"border-top-left-radius",
				"border-top-width"
			]
		},
		{
			"selecteur": ".ast-header-button-1 .ast-custom-button",
			"survol": ".ast-header-button-1 .ast-custom-button",
			"proprietes": [
				"color",
				"background-color"
			]
		},
		{
			"selecteur": ".ast-header-button-1 .ast-custom-button",
			"focus": ".ast-header-button-1 .ast-custom-button",
			"proprietes": [
				"color",
				"background-color"
			]
		},
		{
			"selecteur": ".site-title",
			"proprietes": [
				"font-size",
				"display"
			]
		},
		{
			"selecteur": ".site-title a",
			"proprietes": [
				"color"
			]
		},
		{
			"selecteur": ".site-header .site-description",
			"proprietes": [
				"font-size",
				"display",
				"color"
			]
		},
		{
			"selecteur": ".footer-widget-area[data-section=\"sidebar-widgets-footer-widget-1\"] .widget-title",
			"proprietes": [
				"color",
				"font-size"
			]
		},
		{
			"selecteur": ".footer-widget-area[data-section=\"sidebar-widgets-footer-widget-1\"].footer-widget-area-inner",
			"proprietes": [
				"color",
				"font-size"
			]
		},
		{
			"selecteur": ".footer-widget-area[data-section=\"sidebar-widgets-footer-widget-4\"] .widget-title",
			"proprietes": [
				"color",
				"font-size"
			]
		},
		{
			"selecteur": ".ast-footer-copyright",
			"proprietes": [
				"color",
				"font-size"
			]
		},
		{
			"selecteur": ".ast-footer-social-1-wrap .ast-builder-social-element",
			"proprietes": [
				"margin-left",
				"margin-right"
			]
		},
		{
			"selecteur": ".ast-footer-social-1-wrap .ast-builder-social-element svg",
			"proprietes": [
				"width",
				"height",
				"fill"
			]
		},
		{
			"selecteur": ".ast-footer-social-1-wrap .ast-builder-social-element svg",
			"survol": ".ast-footer-social-1-wrap .ast-builder-social-element",
			"proprietes": [
				"fill"
			]
		},
		{
			"selecteur": "body",
			"proprietes": [
				"font-family"
			]
		}
	],
	"polices": [
		"400 16px Raleway",
		"500 14px Poppins"
	]
}
````

- [ ] **Étape 4 : configurations SPIP** — `tests/comparaison/configurations.php` devient :

````php
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
				'pied_primary' => ['colonnes' => 4, 'repartition' => '4-equal'],
				'pied_below' => ['colonnes' => 2, 'repartition' => '2-equal'],
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
		'R6-menu' => $styles(['menu'], ['rubrique' => 'Jardinage']),
		'R7-bouton' => $styles(['bouton']),
		'R8-identite' => $styles(['identite'], ['meta' => ['slogan_site' => 'Un site propulsé par SPIP']]),
		'R9-pied-couleurs' => $styles(['pied']),
		'R10-sociaux' => $styles(['sociaux']),
		'R11-combinee' => $styles($tous, [
			'astra' => ['police_texte' => "'Raleway', sans-serif", 'police_titres' => "'Poppins', sans-serif"],
			'meta' => ['slogan_site' => 'Un site propulsé par SPIP'],
		]),
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
````

- [ ] **Étape 5 : application côté SPIP** — `tests/comparaison/appliquer.sh` devient :

````bash
#!/usr/bin/env bash
# Usage : SPIP_CLI=… tests/comparaison/appliquer.sh <racine du site SPIP> <configuration>
# Écrit une configuration transposée (configurations.php) dans astra/hfb, avec les réglages
# d'accueil et de pages (astra/accueil_*, astra/pages), les styles (astra/styles), les polices
# (astra/police_*) et le slogan (méta slogan_site), et vide le cache.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
: "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
cd "$1"
$SPIP_CLI php:eval "
include_spip('inc/config');
require_once '$ICI/configurations.php';
\$c = astra_configurations_reference();
if (!isset(\$c['$2'])) { echo 'configuration-inconnue'; return; }
\$conf = \$c['$2'];
\$id = function (\$identifiant) {
	return (int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', 'page=' . sql_quote(\$identifiant)]);
};
foreach (['accueil_type', 'accueil_page', 'pages', 'styles', 'police_texte', 'police_titres'] as \$cle) {
	effacer_config('astra/' . \$cle);
}
ecrire_meta('slogan_site', '');
foreach (\$conf['meta'] ?? [] as \$meta => \$valeur) {
	ecrire_meta(\$meta, \$valeur);
}
if (!empty(\$conf['styles'])) {
	ecrire_config('astra/styles', \$conf['styles']);
}
foreach (\$conf['astra'] ?? [] as \$cle => \$valeur) {
	ecrire_config('astra/' . \$cle, is_string(\$valeur) && \$valeur[0] === '@' ? \$id(substr(\$valeur, 1)) : \$valeur);
}
foreach (\$conf['pages'] ?? [] as \$identifiant => \$reglages) {
	ecrire_config('astra/pages/' . \$id(\$identifiant), \$reglages);
}
\$hfb = astra_configuration_hfb(\$conf);
\$hfb ? ecrire_config('astra/hfb', \$hfb) : effacer_config('astra/hfb');
echo 'configuration-appliquee';
" | grep -q '^configuration-appliquee$' || { echo "Configuration $2 non appliquée" >&2; exit 1; }
$SPIP_CLI cache:vider > /dev/null
echo "$2 appliquée"
````

- [ ] **Étape 6 : comparateur** — `tests/comparaison/comparer_captures.sh` devient :

````bash
#!/usr/bin/env bash
# Comparaison visuelle SPIP / WordPress des configurations de référence.
# Usage : WP=… PW=… SPIP_CLI=… tests/comparaison/comparer_captures.sh <racine SPIP> <URL SPIP> [Rn|Pn …]
# Les configurations Pn (pages uniques) comparent en plus le contenu (#primary).
# Le site SPIP doit avoir les contenus du SPIP de test (scripts/install-spip-test.sh) : site « Mon site »,
# rubriques Jardinage > Potager et Actualités, comme les références WordPress. Pour chaque
# configuration : application au WordPress de test et au site SPIP, captures et comparaison au pixel.
set -euo pipefail
ICI=$(cd "$(dirname "$0")" && pwd)
RACINE=$1
URL_SPIP=$2
shift 2
URL_WP=${URL_WP:-http://127.0.0.1:8401}
SORTIES=${SORTIES:-$ICI/sorties}
: "${WP:?WP doit indiquer la racine du WordPress de test}"
: "${PW:?PW doit indiquer le dossier contenant node_modules/playwright et pixelmatch}"
: "${SPIP_CLI:?SPIP_CLI doit désigner spip-cli}"
CONFS=("$@")
[ ${#CONFS[@]} -gt 0 ] || CONFS=(R0-defaut R1-entete-complet R2-entete-centre R3-pied-colonnes R4-pied-asymetrique R5-rangees R6-menu R7-bouton R8-identite R9-pied-couleurs R10-sociaux R11-combinee)

# Copyright identique à celui d'Astra dans WordPress.
(cd "$RACINE" && $SPIP_CLI php:eval 'include_spip("inc/config"); ecrire_config("astra/texte_pied", "Copyright © [annee] [site] | Powered by [Thème WordPress Astra->https://wpastra.com]");' > /dev/null)

code=0
for conf in "${CONFS[@]}"; do
	echo "== $conf"
	wp --path="$WP" eval-file "$ICI/../references/wp/$conf.php"
	"$ICI/appliquer.sh" "$RACINE" "$conf" > /dev/null
	menu=$(php -r 'require $argv[1]; echo astra_configurations_reference()[$argv[2]]["menu_mobile"] ?? "dropdown";' "$ICI/configurations.php" "$conf")
	chemin_wp=$(php -r '$j = json_decode(file_get_contents($argv[1]), true); echo $j["chemin"] ?? "/";' "$ICI/../references/wp/$conf.json")
	identifiant=$(php -r 'require $argv[1]; echo astra_configurations_reference()[$argv[2]]["page"] ?? "";' "$ICI/configurations.php" "$conf")
	rubrique=$(php -r 'require $argv[1]; echo astra_configurations_reference()[$argv[2]]["rubrique"] ?? "";' "$ICI/configurations.php" "$conf")
	chemin_spip=/
	contenu=
	if [ -n "$identifiant" ]; then
		chemin_spip=/$( (cd "$RACINE" && $SPIP_CLI php:eval "include_spip('inc/filtres'); echo generer_objet_url((int) sql_getfetsel('id_article', 'spip_articles', ['id_rubrique=-1', 'page=' . sql_quote('$identifiant')]), 'article', '', '', true);") | sed -E 's#^https?://[^/]+/?##')
	fi
	# Page d'une rubrique (titre sans son numéro) : page courante du menu, comme la catégorie côté WordPress.
	if [ -n "$rubrique" ]; then
		chemin_spip=/$( (cd "$RACINE" && $SPIP_CLI php:eval "include_spip('inc/filtres'); echo generer_objet_url((int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre LIKE ' . sql_quote('%$rubrique')), 'rubrique', '', '', true);") | sed -E 's#^https?://[^/]+/?##')
	fi
	case $conf in P*) contenu=contenu ;; esac
	NODE_PATH="$PW/node_modules" node "$ICI/comparer_captures.cjs" "$URL_WP$chemin_wp" "$URL_SPIP$chemin_spip" "$SORTIES/$conf" "$menu" "$contenu" "$ICI/../references/wp/$conf.json" || code=1
done

wp --path="$WP" eval-file "$ICI/../references/wp/R0-defaut.php"
"$ICI/appliquer.sh" "$RACINE" R0-defaut > /dev/null
(cd "$RACINE" && $SPIP_CLI php:eval 'include_spip("inc/config"); effacer_config("astra/texte_pied");' > /dev/null)
exit $code
````

`tests/comparaison/comparer_captures.cjs` devient :

````js
// Compare au pixel près l'en-tête, le menu mobile ouvert et le pied de page de deux sites.
// Usage : NODE_PATH=$PW/node_modules node comparer_captures.cjs <URL WordPress> <URL SPIP> <dossier> <menu mobile : dropdown|off-canvas> [contenu] [référence .json]
// Pour chaque vue, enregistre wordpress-<vue>.png, spip-<vue>.png et diff-<vue>.png dans <dossier>
// et affiche la proportion de pixels différents. Code 1 si une vue dépasse SEUIL (défaut 0,01)
// ou si les dimensions diffèrent de plus de 2 px.
// Si la référence JSON a une liste « styles » ([{selecteur, proprietes, survol?, focus?}]), compare
// aussi, à 1280, 768 et 390 px, les styles calculés du premier élément correspondant : un écart d'un
// pixel (une bordure) reste sous le seuil des captures. « survol » (ou « focus ») désigne l'élément
// à survoler (ou à focaliser) avant la lecture : état :hover, :focus, sous-menu ouvert.
// Si elle a une liste « polices » (descripteurs CSS, « 500 14px Poppins »), vérifie des deux côtés
// que leur fichier est chargé et qu'aucune police n'est demandée à Google Fonts.
// Les captures attendent document.fonts.ready.
const fs = require('fs');
const { chromium } = require('playwright');
const { PNG } = require('pngjs');
const pixelmatch = require('pixelmatch');

const [urlWp, urlSpip, dossier, menuMobile, contenu, reference] = process.argv.slice(2);
const REFERENCE = reference ? JSON.parse(fs.readFileSync(reference, 'utf8')) : {};
const STYLES = REFERENCE.styles || [];
const POLICES = REFERENCE.polices || [];
const SEUIL = parseFloat(process.env.SEUIL || '0.01');
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
// Pas d'animation ni de curseur clignotant : captures stables.
const CSS_STABLE = '*,*::before,*::after{transition:none!important;animation:none!important;caret-color:transparent!important}';

const VUES = [
  { nom: 'entete-ordinateur', largeur: 1280, selecteur: '#masthead' },
  { nom: 'pied-ordinateur', largeur: 1280, selecteur: '#colophon' },
  { nom: 'entete-mobile', largeur: 390, selecteur: '#masthead' },
  { nom: 'pied-mobile', largeur: 390, selecteur: '#colophon' },
  { nom: 'menu-mobile', largeur: 390, selecteur: menuMobile === 'off-canvas' ? '#ast-mobile-popup .ast-mobile-popup-inner' : '#masthead', ouvrir: true },
];
if (contenu === 'contenu') {
  VUES.push({ nom: 'contenu-ordinateur', largeur: 1280, selecteur: '#primary' });
  VUES.push({ nom: 'contenu-mobile', largeur: 390, selecteur: '#primary' });
}

async function capturer(browser, url, vue, fichier) {
  const page = await browser.newPage({ viewport: { width: vue.largeur, height: 900 }, userAgent: AGENT });
  await page.goto(url, { waitUntil: 'load' });
  await page.addStyleTag({ content: CSS_STABLE });
  await page.evaluate(() => document.fonts.ready);
  if (vue.ouvrir) {
    await page.locator('.ast-mobile-header-wrap .menu-toggle').first().click();
    await page.waitForTimeout(300);
  }
  await page.locator(vue.selecteur).first().screenshot({ path: fichier });
  await page.close();
}

async function styles(browser, url, largeur) {
  const page = await browser.newPage({ viewport: { width: largeur, height: 900 }, userAgent: AGENT });
  await page.goto(url, { waitUntil: 'load' });
  await page.addStyleTag({ content: CSS_STABLE });
  await page.evaluate(() => document.fonts.ready);
  const valeurs = [];
  for (const { selecteur, proprietes, survol, focus } of STYLES) {
    const etat = survol ? ` (survol ${survol})` : (focus ? ` (focus ${focus})` : '');
    await page.mouse.move(0, 0);
    if (survol && await page.locator(survol).first().isVisible().catch(() => false)) await page.locator(survol).first().hover();
    if (focus && await page.locator(focus).count()) await page.locator(focus).first().focus();
    const lues = await page.evaluate(([s, ps]) => {
      const e = document.querySelector(s);
      const c = e && getComputedStyle(e);
      return ps.map((p) => (c ? c.getPropertyValue(p) : '(absent)'));
    }, [selecteur, proprietes]);
    proprietes.forEach((p, i) => valeurs.push(`${selecteur}${etat} { ${p}: ${lues[i]} }`));
    if (focus) await page.evaluate(() => document.activeElement && document.activeElement.blur());
  }
  await page.close();
  return valeurs;
}

async function polices(browser, url) {
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 }, userAgent: AGENT });
  const google = [];
  page.on('request', (r) => { if (/fonts\.(googleapis|gstatic)\.com/.test(r.url())) google.push(r.url()); });
  await page.goto(url, { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  // Chargée : un @font-face de cette famille et de cette graisse a fini de charger son fichier
  // (document.fonts.check() seul est vrai aussi quand aucune police de ce nom n'est déclarée).
  const absentes = await page.evaluate((liste) => liste.filter((p) => {
    const [graisse, , ...famille] = p.split(' ');
    const nom = famille.join(' ');
    return !document.fonts.check(p) || ![...document.fonts].some((f) => f.family.replace(/["']/g, '') === nom && String(f.weight) === graisse && f.status === 'loaded');
  }), POLICES);
  await page.close();
  return { absentes, google };
}

(async () => {
  fs.mkdirSync(dossier, { recursive: true });
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  let echec = false;
  for (const vue of VUES) {
    const fWp = `${dossier}/wordpress-${vue.nom}.png`;
    const fSpip = `${dossier}/spip-${vue.nom}.png`;
    await capturer(browser, urlWp, vue, fWp);
    await capturer(browser, urlSpip, vue, fSpip);
    const a = PNG.sync.read(fs.readFileSync(fWp));
    const b = PNG.sync.read(fs.readFileSync(fSpip));
    // Meilleur alignement vertical à ±2 px près : la position sous-pixel d'un élément dépend de la
    // hauteur du contenu qui le précède, différente entre les deux sites.
    const largeur = Math.min(a.width, b.width);
    let meilleur = null;
    for (let dy = -2; dy <= 2; dy++) {
      const hauteur = Math.min(a.height - Math.max(0, dy), b.height - Math.max(0, -dy));
      if (hauteur <= 0) continue;
      const ra = new PNG({ width: largeur, height: hauteur });
      const rb = new PNG({ width: largeur, height: hauteur });
      PNG.bitblt(a, ra, 0, Math.max(0, dy), largeur, hauteur, 0, 0);
      PNG.bitblt(b, rb, 0, Math.max(0, -dy), largeur, hauteur, 0, 0);
      const diff = new PNG({ width: largeur, height: hauteur });
      const n = pixelmatch(ra.data, rb.data, diff.data, largeur, hauteur, { threshold: 0.1 });
      if (!meilleur || n / (largeur * hauteur) < meilleur.part) meilleur = { part: n / (largeur * hauteur), diff, dy };
    }
    fs.writeFileSync(`${dossier}/diff-${vue.nom}.png`, PNG.sync.write(meilleur.diff));
    const part = meilleur.part;
    const tailles = Math.abs(a.width - b.width) > 2 || Math.abs(a.height - b.height) > 2;
    const ko = part > SEUIL || tailles;
    echec = echec || ko;
    console.log(`${ko ? 'ÉCART' : 'ok   '} ${vue.nom} : ${(part * 100).toFixed(2)} % de pixels différents`
      + (tailles ? ` ; dimensions WordPress ${a.width}×${a.height}, SPIP ${b.width}×${b.height}` : ''));
  }
  for (const [nom, url] of POLICES.length ? [['WordPress', urlWp], ['SPIP', urlSpip]] : []) {
    const { absentes, google } = await polices(browser, url);
    const ko = absentes.length > 0 || google.length > 0;
    echec = echec || ko;
    console.log(`${ko ? 'ÉCART' : 'ok   '} polices ${nom} : ${POLICES.length} attendues`
      + (absentes.length ? ` ; non chargées : ${absentes.join(', ')}` : '') + (google.length ? ` ; Google Fonts : ${google.join(', ')}` : ''));
  }
  for (const largeur of STYLES.length ? [1280, 768, 390] : []) {
    const wp = await styles(browser, urlWp, largeur);
    const spip = await styles(browser, urlSpip, largeur);
    const ecarts = wp.map((v, i) => (v === spip[i] ? null : `WordPress ${v} ; SPIP ${spip[i]}`)).filter(Boolean);
    echec = echec || ecarts.length > 0;
    console.log(`${ecarts.length ? 'ÉCART' : 'ok   '} styles-${largeur} : ${wp.length} valeurs` + (ecarts.length ? '\n      ' + ecarts.join('\n      ') : ''));
  }
  await browser.close();
  process.exit(echec ? 1 : 0);
})();
````

Dans `tests/comparaison/comparer_html.php`, la ligne `$configurations = array_slice($argv, 3) ?: array_keys(astra_configurations_reference());` devient :
```php
// Par défaut, les configurations du builder et des pages : les références des styles (clé styles)
// ne changent pas la structure comparée ici, elles sont vérifiées par comparer_captures.sh.
$configurations = array_slice($argv, 3) ?: array_keys(array_filter(astra_configurations_reference(), function ($c) {
	return !isset($c['styles']);
}));
```

- [ ] **Étape 7 : syntaxe**

Run : `node --check tests/comparaison/comparer_captures.cjs && php -l tests/comparaison/configurations.php && php -l tests/comparaison/comparer_html.php`
Expected : aucune erreur.

- [ ] **Étape 8 : comparer R0–R11**

SPIP de test servi (`cd vendor/spip/spip && php -S 127.0.0.1:8460`), WordPress de test servi sur 8401 :
```bash
WP=<racine WordPress> PW=<dossier playwright> SPIP_CLI="php <spip-cli MR 91>/bin/spip" tests/comparaison/comparer_captures.sh vendor/spip/spip http://127.0.0.1:8460
```
Expected : aucune ligne `ÉCART` ; R5–R11 : `styles-1280`, `styles-768`, `styles-390` ok ; R7 et R11 : `polices WordPress` et `polices SPIP` ok. Relevé de vérification du plan : R0, R1, R2, R5 à 0,00 % ; plus grand écart 0,35 % (R11, pied sur ordinateur). Tout écart au-dessus du seuil ou dans les styles calculés est documenté (référence, propriété, valeurs, cause) dans `tests/references/README.md` à la tâche 15 avant de poursuivre.

- [ ] **Étape 9 : attendus inchangés**

Run : `WP=<racine WordPress> tests/references/styles/capturer.sh && git status --short tests/references/styles/attendus`
Expected : les mêmes nombres de règles qu'à la tâche 1, aucun fichier modifié.

- [ ] **Étape 10 : structure inchangée**

Run : `WP=… PW=… SPIP_CLI=… php tests/comparaison/comparer_html.php vendor/spip/spip http://127.0.0.1:8460`
Expected : R0–R5 et P1–P3 sans différence (les références R6–R11 sont exclues par défaut).

- [ ] **Étape 11 : commit**

```bash
git add tests/references/styles/appliquer.php tests/references/wp/commun.php tests/comparaison/configurations.php tests/comparaison/appliquer.sh tests/comparaison/comparer_captures.sh tests/comparaison/comparer_captures.cjs tests/comparaison/comparer_html.php
git add tests/references/wp/R6-menu.php tests/references/wp/R6-menu.json tests/references/wp/R7-bouton.php tests/references/wp/R7-bouton.json tests/references/wp/R8-identite.php tests/references/wp/R8-identite.json
git add tests/references/wp/R9-pied-couleurs.php tests/references/wp/R9-pied-couleurs.json tests/references/wp/R10-sociaux.php tests/references/wp/R10-sociaux.json tests/references/wp/R11-combinee.php tests/references/wp/R11-combinee.json
git commit -m "Références R6–R11 des styles : survol, focus, page courante, 768 px, polices chargées des deux côtés"
```

---

### Tâche 15 : version 1.2.0, licences, documentation

**Fichiers :**
- Créer : `licenses/OFL-1.1-raleway.txt`, `licenses/OFL-1.1-poppins.txt`
- Modifier : `paquet.xml`, `CREDITS.md`, `licenses/README.md`, `README.md`, `tests/references/README.md`

- [ ] **Étape 1 : licences des polices** (copie sans modification du `LICENSE` de chaque paquet)

```bash
curl -sfL -o licenses/OFL-1.1-raleway.txt https://cdn.jsdelivr.net/npm/@fontsource/raleway@5.1.0/LICENSE
curl -sfL -o licenses/OFL-1.1-poppins.txt https://cdn.jsdelivr.net/npm/@fontsource/poppins@5.1.0/LICENSE
sha256sum licenses/OFL-1.1-*.txt; head -1 licenses/OFL-1.1-raleway.txt
```
Expected :
```text
8503c30a4d7e09c4c09015fee42f829f18acbe6330755e9930ba50ea44eaa157  licenses/OFL-1.1-poppins.txt
c62d0f4b78825cbf2d99ac7ea3dd163f645210efa032243731184c9f86a6eab6  licenses/OFL-1.1-raleway.txt
Copyright 2010 The Raleway Project Authors (impallari@gmail.com), with Reserved Font Name "Raleway".
```

- [ ] **Étape 2 : `paquet.xml`** — `version="1.1.0"` devient `version="1.2.0"` ; après le crédit IcoMoon :

```xml
	<credit lien="https://github.com/impallari/Raleway">Police Raleway : The Raleway Project Authors (SIL OFL 1.1, nom réservé « Raleway »), fichiers Fontsource 5.1.0</credit>
	<credit lien="https://github.com/itfoundry/Poppins">Police Poppins : The Poppins Project Authors (SIL OFL 1.1), fichiers Fontsource 5.1.0</credit>
```

- [ ] **Étape 3 : documentation** — appliquer le correctif suivant (`git apply`) :

````diff
diff --git a/CREDITS.md b/CREDITS.md
index 83ec151..8dd7e9b 100644
--- a/CREDITS.md
+++ b/CREDITS.md
@@ -29,6 +29,8 @@ et le texte de la GPL v2 dans [licenses/GPL-2.0-or-later.txt](licenses/GPL-2.0-o
 | `inc/dynamic-css/comments-flex.php` | `css/astra-commentaires.css` | Règle d'espacement des listes ; chargement conditionné aux commentaires dans SPIP |
 | Gabarits `header.php`, `footer.php`, `page.php`, `single.php`, `archive.php`, `search.php`, `404.php`, `searchform.php`, `template-parts/` et composants du builder | Squelettes à la racine, `inclure/`, `inclure/hfb/`, `inclure/widgets/`, `formulaires/recherche.html`, `modeles/pagination_astra.html` | Reproduction et adaptation du balisage, des classes, des attributs et de la structure ; les requêtes et appels WordPress sont remplacés par les boucles, balises et formulaires SPIP |
 | Options, palette et organisation du builder | `astra_fonctions.php`, `inc/astra_hfb.php`, formulaires de configuration | Transposition des valeurs par défaut et des conventions Astra ; interface et stockage adaptés à SPIP |
+| Styles du Customizer : `inc/class-astra-dynamic-css.php` (bouton, identité), `inc/builder/type/header/menu/dynamic-css/dynamic.css.php`, `inc/builder/type/base/dynamic-css/{button,widget,social}/class-astra-*-component-dynamic-css.php`, `inc/builder/type/footer/copyright/dynamic-css/dynamic.css.php` | `inc/astra_styles.php`, `inc/styles/*.php` | Transposition des règles ordinateur : sélecteurs (ceux du bouton global copiés à l'identique), ordre, conditions d'émission et valeurs par défaut ; écriture comme `astra_parse_css()` et `trim_css()` |
+| CSS dynamique rendu par WordPress avec les valeurs du modèle Garden Maintenance | `tests/references/styles/attendus/*.css` | Extraits de la sortie d'Astra 4.11.0 (GPL v2 ou ultérieure), capturés par `tests/references/styles/capturer.sh` pour les tests d'équivalence |
 | Règles Astra appliquées aux contenus | `css/astra-spip.css` | Adaptations aux éléments produits par SPIP ; les reprises de règles WordPress/Astra sont signalées dans les commentaires |
 
 Notices de paternité amont conservées :
@@ -164,8 +166,9 @@ attribuer tous les SVG d'Astra à IcoMoon.
 - `images/astra-xx.svg` est le pictogramme du plugin, distinct de la capture
   promotionnelle `screenshot.jpg` du thème WordPress. Aucune source tierce n'est
   indiquée dans ce fichier ; il n'est pas attribué ici à un jeu d'icônes amont.
-- Les choix de polices sont des piles CSS : aucun fichier de fonte de texte ni
-  chargeur Google Fonts n'est fourni par le thème SPIP dans l'état examiné.
+- Les polices du texte, des titres, du bouton et des menus sont des piles CSS ; deux
+  polices sont fournies en fichiers locaux (section « Polices locales »). Aucun chargeur
+  Google Fonts n'est fourni ni appelé.
 - **SPIP**, **Pages** et **a2a** sont des dépendances ou intégrations, pas du code
   embarqué dans le thème. PHPUnit et les outils de comparaison sont des dépendances
   de développement ; leurs distributions conservent leurs propres licences.
@@ -178,6 +181,32 @@ Webfonts Loader, la capture promotionnelle et ces illustrations ne sont pas
 embarqués dans Astra-spip : leur présence dans le thème WordPress ne signifie pas
 qu'ils sont tous redistribués par le portage.
 
+## Polices locales
+
+Fichiers woff2 du sous-ensemble « latin » publiés par [Fontsource](https://fontsource.org/)
+(paquets npm `@fontsource/raleway` et `@fontsource/poppins`, version **5.1.0**, déclarés
+`OFL-1.1`), téléchargés le 23 octobre 2026 depuis `https://cdn.jsdelivr.net/npm/@fontsource/<police>@5.1.0/files/`
+et redistribués sans modification. Le texte de licence de chaque paquet, avec la mention de
+copyright de la police, est conservé dans `licenses/`.
+
+| Fichier | Police, graisse, style | SHA-256 |
+| --- | --- | --- |
+| `fonts/raleway/raleway-latin-400-normal.woff2` | Raleway 400 | `3e44fb721d3be9376c6e5e946109067a04da84ae10b3f27a03ada7a3731e515c` |
+| `fonts/raleway/raleway-latin-400-italic.woff2` | Raleway 400 italique | `29de03aaf7a0844578daef59eabdbd1cfe9257873765938cc51a3c9a3af843b4` |
+| `fonts/raleway/raleway-latin-700-normal.woff2` | Raleway 700 | `109736135dc84f02f379825bd2b48998e17068eaf1f085df5f52e80537a4257d` |
+| `fonts/poppins/poppins-latin-500-normal.woff2` | Poppins 500 | `cd36de204aca2d5fa263a731f7c20009b5e3d754ba1f1e03c33e93a48f3e7446` |
+| `fonts/poppins/poppins-latin-600-normal.woff2` | Poppins 600 | `f4e80d9dfd374d02989b87a27b5ed4cb78fbb177c27f1478e9a8b0afb7513149` |
+
+- **Raleway** : Copyright 2010 The Raleway Project Authors (impallari@gmail.com), avec le
+  nom réservé (*Reserved Font Name*) « Raleway » ; SIL OFL 1.1,
+  [licenses/OFL-1.1-raleway.txt](licenses/OFL-1.1-raleway.txt). L'OFL interdit d'employer un
+  nom réservé pour une version modifiée. Les fichiers sont les sous-ensembles produits par
+  Fontsource et diffusés par lui sous ce nom ; Astra-spip les reprend tels quels. Savoir si un
+  sous-ensemble est une version modifiée au sens de l'OFL est une question signalée ici, non
+  tranchée.
+- **Poppins** : Copyright 2020 The Poppins Project Authors (https://github.com/itfoundry/Poppins),
+  sans nom réservé ; SIL OFL 1.1, [licenses/OFL-1.1-poppins.txt](licenses/OFL-1.1-poppins.txt).
+
 ## Vérification et entretien
 
 L'archive officielle téléchargée pour ce relevé a pour empreinte SHA-256 :
diff --git a/README.md b/README.md
index 5d93374..82560c4 100644
--- a/README.md
+++ b/README.md
@@ -126,8 +126,10 @@ avec leur effet et la propriété vérifiée, dans
 - Largeur du conteneur et du conteneur étroit.
 - Disposition de l'accueil et des listes : barre latérale à droite, à gauche, ou aucune.
 - Disposition des articles : conteneur étroit (valeur par défaut d'Astra), barre latérale à droite ou à gauche, pleine largeur.
-- Polices du texte et des titres, nombre d'articles par page.
-- Affichage du nom du site, du slogan, des auteurs, des extraits, du lien « Accueil » et du bouton de retour en haut.
+- Polices du texte et des titres (pile CSS, ou Raleway et Poppins, fournies avec le thème et servies par le site), nombre d'articles par page.
+- Affichage des auteurs, des extraits, du lien « Accueil » et du bouton de retour en haut.
+- Neuvième couleur de la palette : hexadécimale ou `rgba()`, comme dans Astra.
+- **Couleurs et typographie** (Configuration › Squelettes) : réglages de style du Customizer d'Astra, sous leurs noms d'options (`header-menu1-color-responsive`, `button-radius-fields`…) — menus, bouton, bouton de l'en-tête, identité du site (couleurs, tailles, affichage du nom et du slogan, largeur du logo), widgets et copyright du pied, icônes sociales. Les valeurs d'un `site.json` de modèle Astra se reprennent telles quelles ; seule la valeur ordinateur est appliquée, les valeurs tablette et mobile sont conservées. Sans réglage, rien ne change.
 - Texte du pied de page (raccourcis SPIP, marqueurs `[annee]` et `[site]`).
 
 ## Tests
@@ -151,6 +153,7 @@ références, un WordPress de test et le SPIP de test servi par un serveur web :
     URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire.cjs
     RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
     URL=http://127.0.0.1:8404 NODE_PATH=$PW/node_modules node tests/e2e/tableaux.cjs
+    URL=http://127.0.0.1:8404 LOGIN=admin MDP=adminadmin NODE_PATH=$PW/node_modules node tests/e2e/formulaire_styles.cjs
 
 - `comparer_html.php` compare l'ossature HTML de l'en-tête, du panneau mobile et du pied de
   page à celle de WordPress, pour les six configurations de référence du builder (R0–R5) et
@@ -163,7 +166,11 @@ références, un WordPress de test et le SPIP de test servi par un serveur web :
   images et leurs différences sont enregistrées dans `tests/comparaison/sorties/`. Une référence
   peut aussi lister des styles calculés à comparer (`styles` dans `tests/references/wp/<conf>.json`) :
   R5 vérifie ainsi la hauteur, le fond et le séparateur des rangées de l'en-tête, qu'un filet
-  d'un pixel laisserait sous le seuil des captures ;
+  d'un pixel laisserait sous le seuil des captures. Les références R6–R11 (styles du Customizer,
+  valeurs de Garden Maintenance) comparent aussi les styles calculés au survol et au focus, à
+  1280, 768 et 390 px, et vérifient que les polices locales sont chargées des deux côtés ;
+- `tests/e2e/formulaire_styles.cjs` vérifie le formulaire « Couleurs et typographie » de bout en
+  bout (enregistrement, valeur refusée, site public) ;
 - `tests/e2e/formulaire.cjs` vérifie le formulaire de configuration de bout en bout ;
 - `tests/e2e/tableaux.cjs` vérifie que les tableaux SPIP du texte sont stylés, et pas ceux d'un
   bloc tableau importé (`figure.wp-block-table`) ;
diff --git a/licenses/README.md b/licenses/README.md
index 46608bd..cf32612 100644
--- a/licenses/README.md
+++ b/licenses/README.md
@@ -12,6 +12,8 @@ dans [LICENSE](../LICENSE).
 | `fontawesome-5.15.4-LICENSE.txt` | [Notice originale Font Awesome Free 5.15.4](https://github.com/FortAwesome/Font-Awesome/blob/5.15.4/LICENSE.txt), par Fonticons, Inc. ; les parties relatives aux fontes et au code sont conservées avec le document original mais ces composants ne sont pas embarqués |
 | `wordpress-7.1.3-NOTICE.txt` | Notice initiale de [`license.txt` de WordPress 7.1.3](https://github.com/WordPress/WordPress/blob/7.1.3/license.txt), avant les notices des composants incorporés ; le corps de GPL v2 figure dans le fichier suivant |
 | `GPL-2.0-or-later.txt` | [Texte SPDX GPL-2.0-or-later](https://github.com/spdx/license-list-data/blob/main/text/GPL-2.0-or-later.txt) ; [licence publiée par la FSF](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html) |
+| `OFL-1.1-raleway.txt` | `LICENSE` du paquet [`@fontsource/raleway@5.1.0`](https://cdn.jsdelivr.net/npm/@fontsource/raleway@5.1.0/LICENSE), copié sans modification : copyright de Raleway et texte de la SIL OFL 1.1 |
+| `OFL-1.1-poppins.txt` | `LICENSE` du paquet [`@fontsource/poppins@5.1.0`](https://cdn.jsdelivr.net/npm/@fontsource/poppins@5.1.0/LICENSE), copié sans modification : copyright de Poppins et texte de la SIL OFL 1.1 |
 | `CC-BY-4.0.txt` | [Texte SPDX CC-BY-4.0](https://github.com/spdx/license-list-data/blob/main/text/CC-BY-4.0.txt) ; [texte publié par Creative Commons](https://creativecommons.org/licenses/by/4.0/legalcode) |
 
 Les crédits d'Astra concernant des ressources non embarquées sont conservés dans
diff --git a/tests/references/README.md b/tests/references/README.md
index d111217..151c8f0 100644
--- a/tests/references/README.md
+++ b/tests/references/README.md
@@ -67,6 +67,12 @@ Vérifié le 2026-10-22 : HTML et CSS identiques.
 | R3-pied-colonnes | Pied : rangée haute 2 colonnes (texte, icônes), principale 4 widgets (`4-equal`), basse copyright et menu (`2-equal`) |
 | R4-pied-asymetrique | Pied : rangée principale `3-cwide` (widget ; texte et icônes ; menu vertical), basse `2-lheavy` (copyright, texte 2) |
 | R5-rangees | Disposition par défaut ; rangée principale de l'en-tête : hauteur 100 px, fond `#e8f5e9`, sans séparateur (`hb-header-main-sep` à 0) ; styles calculés comparés (`styles`). Hors des configurations lues par `extraire_css.py` |
+| R6-menu | Styles du Customizer (`tests/references/styles/appliquer.php`, groupe `menu`) : logo, menu et bouton dans l'en-tête, quatre widgets texte, copyright et icônes sociales dans le pied ; couleurs et sous-menus du menu principal, sur la page de la catégorie Jardinage (page courante) |
+| R7-bouton | Même placement ; bouton global (couleurs, survol, rayon, marges, police Poppins 500, taille, casse, espacement) |
+| R8-identite | Même placement ; nom du site et slogan (affiché), tailles et couleurs, largeur du logo |
+| R9-pied-couleurs | Même placement ; couleurs et tailles des quatre widgets et du copyright |
+| R10-sociaux | Même placement ; taille, espacement et couleurs des icônes sociales du pied |
+| R11-combinee | Toutes les options précédentes, police du texte Raleway et des titres Poppins (cascade de plusieurs réglages sur le même bouton et le même menu) |
 | P1-page | Page « Notre jardin » (`/?pagename=notre-jardin`), menu de pages à deux niveaux (`ref_menu_pages()`) |
 | P2-accueil | Même menu, page « Notre jardin » en page d'accueil statique (`/`) |
 | P3-page-pleine | Page « Plein écran », titre désactivé, conteneur `full-width-container` |
@@ -78,6 +84,39 @@ SPIP d'intégration (`scripts/install-spip-test.sh`). Classes du `body` relevée
 - P2-accueil : les mêmes, précédées de `home`
 - P3-page-pleine : `wp-singular page-template-default page page-id-24 wp-theme-astra ast-desktop ast-page-builder-template ast-no-sidebar astra-4.11.0 group-blog ast-single-post ast-inherit-site-logo-transparent ast-hfb-header` (ni `ast-normal-title-enabled`, ni `page-parent`)
 
+Les références R6–R11 reprennent les valeurs du modèle Garden Maintenance
+(`tests/references/styles/valeurs/<groupe>.json`) ; elles ne sont pas lues par
+`extraire_css.py` ni par `comparer_html.php` (structure inchangée), mais par
+`comparer_captures.sh` : captures, styles calculés (dont `survol` et `focus`) à 1280, 768 et
+390 px, polices chargées (`polices`). Le WordPress de test sert alors les polices d'Astra SPIP
+par un mu-plugin écrit par `ref_polices_locales()` (`commun.php`), sans Google Fonts ;
+`ref_etat_initial()` le retire.
+
+## Styles du Customizer : attendus
+
+`tests/references/styles/` fixe le CSS qu'Astra 4.11.0 produit pour chaque groupe de styles :
+
+- `appliquer.php` : état R0, placement de Garden, options du groupe (`GROUPE=<groupe>`) ;
+- `capturer.sh` : `WP=… tests/references/styles/capturer.sh` capture le CSS dynamique sans option
+  (`attendus/base.css`) puis avec les options de chaque groupe, et écrit dans
+  `attendus/<groupe>.css` les règles ajoutées ou modifiées (`regles.py`) ; il remet R0 ensuite ;
+- `attendus/<groupe>.neutralisations.css` : règles qu'Astra SPIP émet en plus pour défaire une
+  règle fixe de ses feuilles qu'Astra n'émet plus avec ce réglage (marges tablette et mobile
+  du bouton ; rayon de 40 px du bouton de l'en-tête quand ses rayons sont enregistrés vides).
+
+`tests/unit/StylesAttendusTest.php` vérifie que la sortie d'`astra_styles_css()` contient toutes
+les règles attendues et que ses autres règles sont des règles de `base.css` ou des
+neutralisations déclarées. Après une montée de version d'Astra, relancer `capturer.sh` et relire
+le diff des attendus.
+
+Relevé du 23 octobre 2026 (`comparer_captures.sh`, R0–R11) : toutes les vues sous le seuil,
+styles calculés identiques aux trois largeurs, polices chargées des deux côtés ; plus grand
+écart : 0,35 % (R11, pied sur ordinateur).
+
+Non couvert dans cette version (valeurs conservées, aucun CSS) : valeurs tablette et mobile ;
+fonds de type `image` ou `gradient` ; fond, fond au survol et rayon des icônes sociales
+(`*-social-1-bg-color`, `-bg-h-color`, `-radius-fields`), absents des valeurs du modèle.
+
 ## Relevés pour le portage
 
 ### Répartitions des colonnes du pied
````

- [ ] **Étape 4 : vérification finale**

Run : `vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php && git diff --check`
Expected : tout passe, aucune erreur d'espaces.

- [ ] **Étape 5 : commit**

```bash
git add licenses/OFL-1.1-raleway.txt licenses/OFL-1.1-poppins.txt paquet.xml CREDITS.md licenses/README.md README.md tests/references/README.md
git commit -m "Version 1.2.0 : styles du Customizer, polices locales (licences OFL), références R6–R11"
```

Ensuite : pousser la branche et ouvrir la PR avec l'accord de Gilles ; l'étiquette `v1.2.0` se pose
après la fusion. Le lot de Garden qui pose `astra/styles` exigera Astra SPIP 1.2.0 (plan à part,
dans garden-spip).

<?php
/**
 * Adaptation du rendu natif de Menus aux composants de navigation Astra.
 * @license GPL-3.0-or-later
 */

/**
 * Retourne les entrées de la liste racine, sans changer les destinations ni les types d’entrées.
 * $widget : oui = liste statique, sans-js = HTML natif sans commandes ajoutées.
 * La logique éditoriale et les autorisations restent celles de Menus.
 */
function astra_menus_adapter(string $html, ?string $widget = ''): string {
	if (trim($html) === '') {
		return '';
	}
	$d = new DOMDocument();
	$avant = libxml_use_internal_errors(true);
	$d->loadHTML('<?xml encoding="utf-8"?><div id="astra-menu-rendu">' . $html . '</div>', LIBXML_NONET);
	libxml_clear_errors();
	libxml_use_internal_errors($avant);
	$x = new DOMXPath($d);
	$racine = $x->query('//*[@id="astra-menu-rendu"]/ul')->item(0);
	if (!$racine) {
		return '';
	}
	$classe = static function (DOMElement $e, string $c): void {
		$e->setAttribute('class', trim($e->getAttribute('class') . ' ' . $c));
	};
	foreach ($x->query('.//li', $racine) as $li) {
		if ($widget === 'sans-js') {
			continue;
		}
		$classe($li, 'menu-item');
		$a = $x->query('./a', $li)->item(0);
		if ($a) {
			$classe($a, 'menu-link');
		}
		if ($a && $a->getAttribute('aria-current') === 'page') {
			$classe($li, 'current-menu-item');
			foreach ($x->query('ancestor::li', $li) as $parent) {
				$classe($parent, 'current-menu-ancestor');
			}
		}
		if (!$x->query('./ul', $li)->length) {
			continue;
		}
		$classe($li, 'menu-item-has-children');
		$fleche = $d->createElement('span');
		$fleche->setAttribute('class', 'ast-header-navigation-arrow');
		$fleche->setAttribute('aria-hidden', 'true');
		if ($widget === 'oui') {
			if ($a) {
				$a->appendChild($fleche);
			}
		} else {
			if (!$a) {
				$classe($li, 'astra-menu-sans-lien');
			}
			// Même commande que les sources historiques, sans cible imbriquée dans le lien.
			$bouton = $d->createElement('button');
			$bouton->setAttribute('class', 'ast-menu-toggle');
			$bouton->setAttribute('type', 'button');
			$bouton->setAttribute('aria-expanded', 'false');
			$bouton->setAttribute('aria-haspopup', 'true');
			$bouton->setAttribute('aria-label', _T('astra:deplier_menu'));
			$svg = $d->createDocumentFragment();
			$svg->appendXML('<svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" width="26" height="16.043" viewBox="57 35.171 26 16.043"><path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" /></svg>');
			$fleche->appendChild($svg);
			if ($a) {
				$a->appendChild($fleche->cloneNode(true));
			}
			$bouton->appendChild($fleche);
			$li->insertBefore($bouton, $x->query('./ul', $li)->item(0));
		}
	}
	foreach ($x->query('.//ul', $racine) as $ul) {
		if ($widget !== 'sans-js') {
			$classe($ul, 'sub-menu');
		}
	}
	$r = '';
	foreach ($racine->childNodes as $enfant) {
		$r .= $d->saveHTML($enfant);
	}
	return $r;
}

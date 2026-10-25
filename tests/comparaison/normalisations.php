<?php
/** Repli déclaré, inactif lorsque les scripts fonctionnent, qualifié séparément sans JS. */
function astra_normaliser_navigation_sans_js(DOMXPath $x): void {
	foreach ($x->query('//noscript[count(*)=1 and nav[@class="astra-navigation-sans-js"]]') as $n) {
		$n->parentNode->removeChild($n);
	}
}

/** Classe JS retirée du widget statique par décision validée ; uniquement sur la référence WP. */
function astra_normaliser_widgets_statiques(DOMXPath $x): void {
	$widgets = '//*[contains(concat(" ", normalize-space(@class), " "), " widget_nav_menu ")]';
	foreach ($x->query($widgets . '//span[not(*) and contains(concat(" ", normalize-space(@class), " "), " ast-header-navigation-arrow ")]') as $fleche) {
		$classes = preg_split('/\s+/', trim($fleche->getAttribute('class')));
		$fleche->setAttribute('class', implode(' ', array_values(array_diff($classes, ['dropdown-menu-toggle']))));
	}
}

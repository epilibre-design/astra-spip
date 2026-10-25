<?php
/** Repli déclaré, inactif lorsque les scripts fonctionnent, qualifié séparément sans JS. */
function astra_normaliser_navigation_sans_js(DOMXPath $x): void {
	foreach ($x->query('//noscript[count(*)=1 and nav[@class="astra-navigation-sans-js"]]') as $n) {
		$n->parentNode->removeChild($n);
	}
}

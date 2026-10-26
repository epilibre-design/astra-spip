<?php
/**
 * Alignements des widgets flex : Astra 4.13.8,
 * inc/builder/type/footer/widget/dynamic-css/dynamic.css.php, astra_fb_widget_dynamic_css().
 * @license GPL-3.0-or-later
 */
function astra_styles_alignements_widgets(array $s, array $hfb): array {
 $regles = [];
 for ($n = 1; $n <= 4; $n++) {
  $v = $s["footer-widget-alignment-$n"] ?? [];
  if (!array_filter($v, static function ($x) { return $x !== '' && $x !== null; })
   || !astra_styles_element_place("widget-$n", $hfb, 'pied')) {
   continue;
  }
  $sel = '.footer-widget-area[data-section="sidebar-widgets-footer-widget-' . $n . '"].footer-widget-area-inner';
  // Le CSS figé contient left. Sans valeur ordinateur, Astra laisse l'élément hériter.
  $regles[] = ['', $sel, ['text-align' => ($v['desktop'] ?? '') ?: 'inherit']];
  foreach (['tablet' => '(max-width:921px)', 'mobile' => '(max-width:544px)'] as $a => $media) {
   if (($v[$a] ?? '') !== '') {
    $regles[] = [$media, $sel, ['text-align' => $v[$a]]];
   }
  }
 }
 return $regles;
}

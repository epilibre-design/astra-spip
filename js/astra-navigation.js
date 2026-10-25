/**
 * Adaptation SPIP de navigation, distincte du bundle Astra 4.13.8 intact.
 * Répare le retour au déclencheur et le piège de focus après réouverture.
 * Source du comportement comparé : assets/js/unminified/frontend.js (Astra 4.13.8).
 * GPL-3.0-or-later — Astra SPIP.
 */
(function () {
 'use strict';
 const popup = document.getElementById('ast-mobile-popup');
 const masthead = document.getElementById('masthead');
 if (!popup || !masthead) return;
 const visible = element => {
  if (!element || !element.isConnected) return false;
  const style = window.getComputedStyle(element);
  return element.getClientRects().length > 0 && element.offsetWidth > 0 && element.offsetHeight > 0 && style.visibility !== 'hidden' && style.display !== 'none';
 };
 let declencheur = null;
 let ouvert = false;
 const actif = () => popup.classList.contains('active') && visible(popup);
 function restaurerFocus() {
  const commandes = [...masthead.querySelectorAll('.main-header-menu-toggle')];
  const liens = [...masthead.querySelectorAll('.custom-logo-link, .site-title a')];
  const cible = visible(declencheur) ? declencheur : commandes.find(visible) || liens.find(visible);
  if (cible) {
   cible.focus({preventScroll: true});
  } else {
   if (!masthead.hasAttribute('tabindex')) {
    masthead.setAttribute('tabindex', '-1');
    masthead.addEventListener('blur', () => masthead.removeAttribute('tabindex'), {once: true});
   }
   masthead.focus({preventScroll: true});
  }
 }
 // Le bundle assure seul l'ouverture et la fermeture ; ne pas doubler sa bascule.
 document.addEventListener('click', event => {
  const commande = event.target.closest('.main-header-menu-toggle');
  if (commande && masthead.contains(commande) && visible(commande)) declencheur = commande;
 }, true);
 function synchroniser() {
  if (actif()) ouvert = true;
  else if (ouvert) {
   ouvert = false;
   restaurerFocus();
  }
 }
 const observation = new MutationObserver(synchroniser);
 observation.observe(popup, {attributes: true, attributeFilter: ['class', 'style']});
 observation.observe(document.body, {attributes: true, attributeFilter: ['class']});
 observation.observe(document.documentElement, {attributes: true, attributeFilter: ['class']});
 window.addEventListener('resize', () => window.requestAnimationFrame(synchroniser));
 // L'amont retire son écouteur à la fermeture et ne le repose pas à la réouverture.
 // Si son premier piège a déjà traité Tab, defaultPrevented évite une seconde action.
 document.addEventListener('keydown', event => {
  if (event.key !== 'Tab' || event.defaultPrevented || !actif()) return;
  const commandes = [...popup.querySelectorAll('button,a[href],input,select,textarea,[tabindex]')]
   .filter(element => element.tabIndex >= 0 && !element.disabled && !element.closest('[inert]') && visible(element));
  if (!commandes.length) return;
  const premier = commandes[0], dernier = commandes[commandes.length - 1];
  if (event.shiftKey && document.activeElement === premier) {
   event.preventDefault(); dernier.focus();
  } else if ((!event.shiftKey && document.activeElement === dernier) || !popup.contains(document.activeElement)) {
   event.preventDefault(); premier.focus();
  }
 });
})();

// Listes statiques des widgets : Entrée suit le lien, sans lui inventer un état replié.
// Le gestionnaire global amont pose sinon aria-expanded sur ce parent non dépliant.
(function () {
 'use strict';
 document.addEventListener('keydown', event => {
  if (event.key !== 'Enter') return;
  const lien = event.target.closest('.widget_nav_menu li.menu-item-has-children > a.menu-link');
  if (lien && event.target === lien) event.stopPropagation();
  // Pas de preventDefault : l'activation native du lien garde sa destination.
 }, true);
})();

// Boutons natifs des sous-menus : la classe portée par le bundle fait foi.
(function () {
 'use strict';
 function initialiser() {
  // navigation_accessibility() prend le premier bouton de l'en-tête pour son
  // déclencheur global. Un sous-menu placé avant ce déclencheur n'en est pas un.
  for (const id of ['ast-desktop-header', 'ast-mobile-header']) {
   const entete = document.getElementById(id);
   if (!entete || !entete.querySelector('.navigation-accessibility')) continue;
   const premier = entete.getElementsByTagName('button')[0];
   if (premier && premier.classList.contains('ast-menu-toggle')) premier.onclick = null;
  }
  const observation = new MutationObserver(records => {
   for (const record of records) synchroniser(record.target);
  });
  function synchroniser(parent) {
   const bouton = parent.querySelector(':scope > button.ast-menu-toggle');
   if (!bouton) return;
   bouton.setAttribute('aria-expanded', String(parent.classList.contains('ast-submenu-expanded')));
   // AstraToggleSubMenu utilise event.target, qui peut être le SVG lors d'un clic.
   for (const enfant of bouton.querySelectorAll('[aria-expanded]')) enfant.removeAttribute('aria-expanded');
  }
  for (const parent of document.querySelectorAll('#masthead li.menu-item-has-children, #ast-mobile-popup li.menu-item-has-children')) {
   if (!parent.querySelector(':scope > button.ast-menu-toggle')) continue;
   synchroniser(parent);
   observation.observe(parent, {attributes: true, attributeFilter: ['class']});
  }
 }
 if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialiser, {once: true});
 else initialiser();
 document.addEventListener('focusout', event => {
  const parent = event.target.closest('li.ast-submenu-expanded');
  if (!parent || !parent.closest('#masthead, #ast-mobile-popup') || parent.contains(event.relatedTarget)) return;
  parent.classList.remove('ast-submenu-expanded');
  const sousmenu = parent.querySelector(':scope > .sub-menu');
  if (sousmenu) sousmenu.style.display = 'none';
 });
})();

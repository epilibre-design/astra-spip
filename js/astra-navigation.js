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

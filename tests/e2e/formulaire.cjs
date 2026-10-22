// Test de bout en bout du formulaire « En-tête et pied de page ».
// Usage : URL=http://127.0.0.1:8402 LOGIN=admin MDP=… NODE_PATH=$PW/node_modules node tests/e2e/formulaire.cjs
// Se connecte à l'espace privé, place la recherche et un bouton dans l'en-tête, enregistre,
// puis vérifie le site public. Modifie la configuration du site : à lancer sur un site de test.
const { chromium } = require('playwright');

const URL = (process.env.URL || 'http://127.0.0.1:8402').replace(/\/$/, '');
// SPIP sert toujours le cache aux robots, dont HeadlessChrome : se présenter comme un navigateur ordinaire.
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
const echecs = [];
const verifier = (condition, message) => { if (!condition) echecs.push(message); };

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 }, userAgent: AGENT });
  page.on('pageerror', e => echecs.push('erreur JavaScript : ' + e.message));

  await page.goto(URL + '/spip.php?page=login&url=ecrire%2F%3Fexec%3Dconfigurer_astra_hfb', { waitUntil: 'load' });
  await page.fill('#var_login', process.env.LOGIN || 'admin');
  await page.fill('input[type=password]', process.env.MDP || '');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('.formulaire_login [type=submit], form [type=submit]')]);
  if (!page.url().includes('exec=configurer_astra_hfb')) {
    await page.goto(URL + '/ecrire/?exec=configurer_astra_hfb', { waitUntil: 'load' });
  }
  verifier(await page.locator('.formulaire_configurer_astra_hfb form').count() === 1, 'formulaire absent de la page de configuration');

  await page.selectOption('select[name="place[ordinateur][search]"]', 'primary_right');
  await page.fill('input[name="ordre[ordinateur][search]"]', '2');
  await page.selectOption('select[name="place[ordinateur][button-1]"]', 'primary_right');
  await page.fill('input[name="ordre[ordinateur][button-1]"]', '3');
  await page.fill('input[name="elements[entete][button-1][libelle]"]', 'Contact e2e');
  await page.click('.formulaire_configurer_astra_hfb .boutons [type=submit]');
  await page.locator('.formulaire_configurer_astra_hfb .reponse_formulaire_ok').waitFor({ timeout: 10000 }).catch(() => {});
  verifier(await page.locator('.formulaire_configurer_astra_hfb .reponse_formulaire_ok').count() === 1, 'pas de message de confirmation après enregistrement');
  verifier(await page.inputValue('input[name="elements[entete][button-1][libelle]"]') === 'Contact e2e', 'libellé non conservé dans le formulaire');

  // Erreur de saisie : panneau mobile sans bouton de menu.
  await page.selectOption('select[name="place[mobile][mobile-trigger]"]', '');
  await page.click('.formulaire_configurer_astra_hfb .boutons [type=submit]');
  await page.locator('.formulaire_configurer_astra_hfb .reponse_formulaire_erreur').waitFor({ timeout: 10000 }).catch(() => {});
  verifier(await page.locator('tr.erreur:has(select[name="place[mobile][mobile-trigger]"]) .erreur_message').count() === 1, 'erreur du bouton de menu mobile non affichée');

  const publique = await browser.newPage({ viewport: { width: 1280, height: 900 }, userAgent: AGENT });
  await publique.goto(URL + '/', { waitUntil: 'load' });
  const droite = publique.locator('#ast-desktop-header .site-header-primary-section-right');
  verifier(await droite.locator('.ast-header-search').isVisible(), 'recherche absente du site public');
  const bouton = droite.locator('.ast-header-button-1 .ast-custom-button');
  verifier(await bouton.count() === 1 && (await bouton.textContent()).trim() === 'Contact e2e', 'bouton absent ou libellé incorrect sur le site public');
  await browser.close();

  if (echecs.length) {
    console.error(echecs.join('\n'));
    process.exit(1);
  }
  console.log('Formulaire : OK');
})();

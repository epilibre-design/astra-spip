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
  // Au premier passage, la compilation des squelettes peut faire interrompre la navigation
  // (ERR_ABORTED) : une seconde tentative suffit.
  for (let essai = 0; essai < 2 && !page.url().includes('exec=configurer_astra_styles'); essai++) {
    await page.goto(URL + '/ecrire/?exec=configurer_astra_styles', { waitUntil: 'load' }).catch(() => page.waitForLoadState('load'));
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

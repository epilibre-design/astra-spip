// Usage : NODE_PATH=$PW/node_modules node capture.cjs <url> <dossier> <attendu.json>
// Captures ordinateur (1280 px) et mobile (390 px), menu mobile ouvert.
// Sort en erreur (code 1) si : erreur JavaScript, sélecteur attendu non visible ou absent,
// menu mobile attendu qui ne s'ouvre pas.
const fs = require('fs');
const { chromium } = require('playwright');
// SPIP sert toujours le cache aux robots, dont HeadlessChrome : se présenter comme un navigateur ordinaire.
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
(async () => {
  const [url, dossier, fichierAttendu] = process.argv.slice(2);
  const attendu = JSON.parse(fs.readFileSync(fichierAttendu, 'utf8'));
  const echecs = [];
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  for (const [nom, largeur] of [['ordinateur', 1280], ['mobile', 390]]) {
    const page = await browser.newPage({ viewport: { width: largeur, height: 900 }, userAgent: AGENT });
    page.on('pageerror', e => echecs.push(`${nom} : erreur JavaScript : ${e.message}`));
    await page.goto(url, { waitUntil: 'load', timeout: 90000 });
    await page.screenshot({ path: `${dossier}/${nom}.png`, fullPage: true });
    for (const sel of attendu[nom] || []) {
      if (!(await page.locator(sel).first().isVisible())) echecs.push(`${nom} : non visible : ${sel}`);
    }
    if (nom === 'ordinateur') {
      for (const sel of attendu.presents || []) {
        if ((await page.locator(sel).count()) === 0) echecs.push(`absent du DOM : ${sel}`);
      }
    }
    if (nom === 'mobile' && attendu.menuMobile) {
      const bouton = page.locator('.ast-mobile-header-wrap .menu-toggle').first();
      if (!(await bouton.isVisible())) {
        echecs.push('mobile : bouton du menu non visible');
      } else {
        await bouton.click();
        const panneau = attendu.menuMobile === 'off-canvas'
          ? page.locator('#ast-mobile-popup.active, .ast-mobile-popup-drawer.active').first()
          : page.locator('.ast-mobile-header-content').first();
        try {
          await panneau.waitFor({ state: 'visible', timeout: 3000 });
          await page.waitForTimeout(800); // fin de l'animation d'ouverture
          for (const sel of attendu.menuOuvert || []) {
            if ((await page.locator(sel).count()) === 0) echecs.push(`mobile : absent du menu ouvert : ${sel}`);
          }
        } catch (e) {
          echecs.push(`mobile : le menu ${attendu.menuMobile} ne s'ouvre pas`);
        }
        await page.screenshot({ path: `${dossier}/mobile-menu.png`, fullPage: false });
      }
    }
    await page.close();
  }
  await browser.close();
  if (echecs.length) {
    console.error(echecs.join('\n'));
    process.exit(1);
  }
})();

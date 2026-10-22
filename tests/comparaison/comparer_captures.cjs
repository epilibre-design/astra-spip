// Compare au pixel près l'en-tête, le menu mobile ouvert et le pied de page de deux sites.
// Usage : NODE_PATH=$PW/node_modules node comparer_captures.cjs <URL WordPress> <URL SPIP> <dossier> <menu mobile : dropdown|off-canvas>
// Pour chaque vue, enregistre wordpress-<vue>.png, spip-<vue>.png et diff-<vue>.png dans <dossier>
// et affiche la proportion de pixels différents. Code 1 si une vue dépasse SEUIL (défaut 0,01)
// ou si les dimensions diffèrent de plus de 2 px.
const fs = require('fs');
const { chromium } = require('playwright');
const { PNG } = require('pngjs');
const pixelmatch = require('pixelmatch');

const [urlWp, urlSpip, dossier, menuMobile] = process.argv.slice(2);
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

async function capturer(browser, url, vue, fichier) {
  const page = await browser.newPage({ viewport: { width: vue.largeur, height: 900 }, userAgent: AGENT });
  await page.goto(url, { waitUntil: 'load' });
  await page.addStyleTag({ content: CSS_STABLE });
  if (vue.ouvrir) {
    await page.locator('.ast-mobile-header-wrap .menu-toggle').first().click();
    await page.waitForTimeout(300);
  }
  await page.locator(vue.selecteur).first().screenshot({ path: fichier });
  await page.close();
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
  await browser.close();
  process.exit(echec ? 1 : 0);
})();

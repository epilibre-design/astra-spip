// Compare au pixel près l'en-tête, le menu mobile ouvert et le pied de page de deux sites.
// Usage : NODE_PATH=$PW/node_modules node comparer_captures.cjs <URL WordPress> <URL SPIP> <dossier> <menu mobile : dropdown|off-canvas> [contenu] [référence .json]
// Pour chaque vue, enregistre wordpress-<vue>.png, spip-<vue>.png et diff-<vue>.png dans <dossier>
// et affiche la proportion de pixels différents. Code 1 si une vue dépasse SEUIL (défaut 0,01)
// ou si les dimensions diffèrent de plus de 2 px.
// Si la référence JSON a une liste « styles » ([{selecteur, proprietes, survol?, focus?}]), compare
// aussi, à 1280, 768 et 390 px, les styles calculés du premier élément correspondant : un écart d'un
// pixel (une bordure) reste sous le seuil des captures. « survol » (ou « focus ») désigne l'élément
// à survoler (ou à focaliser) avant la lecture : état :hover, :focus, sous-menu ouvert.
// Si elle a une liste « polices » (descripteurs CSS, « 500 14px Poppins »), vérifie des deux côtés
// que leur fichier est chargé et qu'aucune police n'est demandée à Google Fonts.
// Les captures attendent document.fonts.ready.
const fs = require('fs');
const { chromium } = require('playwright');
const { PNG } = require('pngjs');
const pixelmatch = require('pixelmatch');

const [urlWp, urlSpip, dossier, menuMobile, contenu, reference] = process.argv.slice(2);
const REFERENCE = reference ? JSON.parse(fs.readFileSync(reference, 'utf8')) : {};
const STYLES = REFERENCE.styles || [];
const POLICES = REFERENCE.polices || [];
const SEUIL = parseFloat(process.env.SEUIL || '0.01');
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
// Pas d'animation ni de curseur clignotant : captures stables.
const { cssStabilisation, stabiliserComposant } = require('./stabilisation.cjs');
const CSS_STABLE = cssStabilisation('');

const VUES = [
  { nom: 'entete-ordinateur', largeur: 1280, selecteur: '#masthead' },
  { nom: 'pied-ordinateur', largeur: 1280, selecteur: '#colophon' },
  { nom: 'entete-mobile', largeur: 390, selecteur: '#masthead' },
  { nom: 'pied-mobile', largeur: 390, selecteur: '#colophon' },
  { nom: 'menu-mobile', largeur: 390, selecteur: menuMobile === 'off-canvas' ? '#ast-mobile-popup .ast-mobile-popup-inner' : '#masthead', ouvrir: true },
];
if (contenu === 'contenu') {
  VUES.push({ nom: 'contenu-ordinateur', largeur: 1280, selecteur: '#primary' });
  VUES.push({ nom: 'contenu-mobile', largeur: 390, selecteur: '#primary' });
}

async function capturer(browser, url, vue, fichier) {
  const page = await browser.newPage({ viewport: { width: vue.largeur, height: 900 }, userAgent: AGENT });
  await page.goto(url, { waitUntil: 'load' });
  await stabiliserComposant(page, vue.selecteur);
  await page.evaluate(() => document.fonts.ready);
  if (vue.ouvrir) {
    await page.locator('.ast-mobile-header-wrap .menu-toggle').first().click();
    await page.waitForTimeout(300);
  }
  await page.locator(vue.selecteur).first().screenshot({ path: fichier });
  await page.close();
}

async function styles(browser, url, largeur) {
  const page = await browser.newPage({ viewport: { width: largeur, height: 900 }, userAgent: AGENT });
  await page.goto(url, { waitUntil: 'load' });
  await page.addStyleTag({ content: CSS_STABLE });
  await page.evaluate(() => document.fonts.ready);
  const valeurs = [];
  for (const { selecteur, proprietes, survol, focus } of STYLES) {
    const etat = survol ? ` (survol ${survol})` : (focus ? ` (focus ${focus})` : '');
    await page.mouse.move(0, 0);
    if (survol && await page.locator(survol).first().isVisible().catch(() => false)) await page.locator(survol).first().hover();
    if (focus && await page.locator(focus).count()) await page.locator(focus).first().focus();
    const lues = await page.evaluate(([s, ps]) => {
      const e = document.querySelector(s);
      const c = e && getComputedStyle(e);
      return ps.map((p) => (c ? c.getPropertyValue(p) : '(absent)'));
    }, [selecteur, proprietes]);
    proprietes.forEach((p, i) => valeurs.push(`${selecteur}${etat} { ${p}: ${lues[i]} }`));
    if (focus) await page.evaluate(() => document.activeElement && document.activeElement.blur());
  }
  await page.close();
  return valeurs;
}

async function polices(browser, url) {
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 }, userAgent: AGENT });
  const google = [];
  page.on('request', (r) => { if (/fonts\.(googleapis|gstatic)\.com/.test(r.url())) google.push(r.url()); });
  await page.goto(url, { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  // Chargée : un @font-face de cette famille et de cette graisse a fini de charger son fichier
  // (document.fonts.check() seul est vrai aussi quand aucune police de ce nom n'est déclarée).
  const absentes = await page.evaluate((liste) => liste.filter((p) => {
    const [graisse, , ...famille] = p.split(' ');
    const nom = famille.join(' ');
    return !document.fonts.check(p) || ![...document.fonts].some((f) => f.family.replace(/["']/g, '') === nom && String(f.weight) === graisse && f.status === 'loaded');
  }), POLICES);
  await page.close();
  return { absentes, google };
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
  for (const [nom, url] of POLICES.length ? [['WordPress', urlWp], ['SPIP', urlSpip]] : []) {
    const { absentes, google } = await polices(browser, url);
    const ko = absentes.length > 0 || google.length > 0;
    echec = echec || ko;
    console.log(`${ko ? 'ÉCART' : 'ok   '} polices ${nom} : ${POLICES.length} attendues`
      + (absentes.length ? ` ; non chargées : ${absentes.join(', ')}` : '') + (google.length ? ` ; Google Fonts : ${google.join(', ')}` : ''));
  }
  for (const largeur of STYLES.length ? [1280, 768, 390] : []) {
    const wp = await styles(browser, urlWp, largeur);
    const spip = await styles(browser, urlSpip, largeur);
    const ecarts = wp.map((v, i) => (v === spip[i] ? null : `WordPress ${v} ; SPIP ${spip[i]}`)).filter(Boolean);
    echec = echec || ecarts.length > 0;
    console.log(`${ecarts.length ? 'ÉCART' : 'ok   '} styles-${largeur} : ${wp.length} valeurs` + (ecarts.length ? '\n      ' + ecarts.join('\n      ') : ''));
  }
  await browser.close();
  process.exit(echec ? 1 : 0);
})();

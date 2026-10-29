// Titre d'entrée hors de header.entry-header (classe ast-titre-entree, docs/interface-gabarits.md) :
// mêmes styles calculés que le titre d'un article d'Astra, à 1280 et 390 px.
// Usage : URL=<SPIP de test> NODE_PATH=$PW/node_modules node tests/e2e/titre_entree.cjs
const { chromium } = require('playwright');

const URL = process.env.URL || 'http://127.0.0.1:8404';
const PROPRIETES = ['font-size', 'font-weight', 'line-height', 'font-family', 'color', 'margin-bottom'];

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.CHROMIUM || process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  const echecs = [];
  for (const largeur of [1280, 390]) {
    const page = await browser.newPage({ viewport: { width: largeur, height: 900 } });
    await page.goto(URL + '/spip.php?article1', { waitUntil: 'load' });
    const r = await page.evaluate((props) => {
      const reference = document.querySelector('header.entry-header h1.entry-title');
      const article = reference.closest('article');
      article.insertAdjacentHTML('beforebegin', `<div class="wp-block-group"><h1 id="t-bloc" class="entry-title ast-titre-entree">${reference.textContent}</h1></div><h1 id="t-sans" class="entry-title">${reference.textContent}</h1>`);
      const styles = (e) => Object.fromEntries(props.map(p => [p, getComputedStyle(e).getPropertyValue(p)]));
      return { reference: styles(reference), bloc: styles(document.querySelector('#t-bloc')), sans: styles(document.querySelector('#t-sans')) };
    }, PROPRIETES);
    await page.close();
    for (const p of PROPRIETES) {
      if (p !== 'margin-bottom' && r.bloc[p] !== r.reference[p]) echecs.push(`${largeur} px, ${p} : ${r.bloc[p]} au lieu de ${r.reference[p]}`);
    }
    if (r.sans['font-size'] === r.reference['font-size']) echecs.push(`${largeur} px : le témoin sans la classe a déjà la taille du titre`);
    console.log(largeur, JSON.stringify(r));
  }
  await browser.close();
  console.log(echecs.length ? echecs.join('\n') : 'Titre d’entrée : styles identiques au titre d’article.');
  process.exitCode = echecs.length ? 1 : 0;
})();

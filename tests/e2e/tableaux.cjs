// Tableaux SPIP : stylés par css/astra-spip.css dans le texte (enfant direct de .entry-content
// ou dans un autre conteneur), mais pas dans un bloc tableau importé (figure.wp-block-table).
// Usage : URL=<SPIP de test> NODE_PATH=$PW/node_modules node tests/e2e/tableaux.cjs
const { chromium } = require('playwright');

const URL = process.env.URL || 'http://127.0.0.1:8404';
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
const TABLE = '<table class="table spip"><thead><tr class="row_first"><th>A</th></tr></thead><tbody><tr class="row_odd odd"><td>1</td></tr></tbody></table>';

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  const page = await browser.newPage({ userAgent: AGENT });
  await page.goto(URL + '/spip.php?article1', { waitUntil: 'load' });
  const r = await page.evaluate((table) => {
    const contenu = document.querySelector('.entry-content');
    contenu.insertAdjacentHTML('beforeend', `<div id="t-direct">${table}</div>`);
    // Enfant direct, comme #TEXTE le produit
    const direct = document.querySelector('#t-direct table');
    contenu.appendChild(direct);
    contenu.insertAdjacentHTML('beforeend', `<div id="t-conteneur" class="wp-block-group">${table}</div><figure id="t-bloc" class="wp-block-table">${table}</figure>`);
    const styles = (t) => {
      const th = getComputedStyle(t.querySelector('thead th'));
      const td = getComputedStyle(t.querySelector('td'));
      return { fond: th.backgroundColor, padding: td.paddingLeft };
    };
    return { direct: styles(direct), conteneur: styles(document.querySelector('#t-conteneur table')), bloc: styles(document.querySelector('#t-bloc table')) };
  }, TABLE);
  await browser.close();
  const transparent = 'rgba(0, 0, 0, 0)';
  const echecs = [];
  for (const cas of ['direct', 'conteneur']) {
    if (r[cas].fond === transparent || r[cas].padding !== '12px') echecs.push(`${cas} : ${JSON.stringify(r[cas])}, attendu fond de l'en-tête et padding 12px`);
  }
  if (r.bloc.fond !== transparent || r.bloc.padding === '12px') echecs.push(`bloc tableau : ${JSON.stringify(r.bloc)}, attendu les styles de wp-block-table`);
  console.log(echecs.length ? 'Tableaux : ÉCHEC\n' + echecs.join('\n') : 'Tableaux : OK');
  process.exit(echecs.length ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });

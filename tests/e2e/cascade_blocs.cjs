// Cascade du CSS des blocs : les propriétés calculées sur le HTML importé doivent être les mêmes
// avec les feuilles d'origine de WordPress et avec css/astra-blocs.css.
// Usage : WP=<racine WordPress> PAGE=<page capturée> NODE_PATH=$PW/node_modules node tests/e2e/cascade_blocs.cjs
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const RACINE = path.join(__dirname, '..', '..');
const WP = process.env.WP;
const BLOCS = ['paragraph', 'heading', 'list', 'quote', 'pullquote', 'code', 'preformatted', 'verse',
  'separator', 'table', 'columns', 'column', 'group', 'buttons', 'button', 'cover', 'media-text'];
const PROPRIETES = ['display', 'flex-wrap', 'flex-basis', 'flex-grow', 'align-items', 'gap', 'column-gap', 'row-gap',
  'margin-top', 'margin-bottom', 'padding-top', 'padding-left', 'border-top-width', 'border-left-width',
  'font-size', 'font-style', 'text-align', 'width', 'min-height', 'position', 'box-sizing'];

const lire = f => fs.readFileSync(f, 'utf8');
const dynamique = lire(path.join(RACINE, 'css/astra-dynamic.css'));
const page = lire(process.env.PAGE);
const globales = (page.match(/<style id=['"]global-styles-inline-css['"][^>]*>([\s\S]*?)<\/style>/) || [, ''])[1];
const origine = [lire(path.join(WP, 'wp-includes/css/dist/block-library/common.css'))]
  .concat(BLOCS.map(b => path.join(WP, 'wp-includes/blocks', b, 'style.css')).filter(fs.existsSync).map(lire))
  .concat([globales]).join('\n');
const genere = lire(path.join(RACINE, 'css/astra-blocs.css'));
const html = lire(path.join(RACINE, 'tests/references/blocs/importe.html'));

async function mesurer(browser, css, largeur) {
  const p = await browser.newPage({ viewport: { width: largeur, height: 900 } });
  await p.setContent(`<!doctype html><html><head><style>${dynamique}</style><style>${css}</style></head><body>${html}</body></html>`);
  const valeurs = await p.evaluate(props => [...document.querySelectorAll('.entry-content *')].map((e, i) => {
    const s = getComputedStyle(e);
    return { i, nom: e.tagName.toLowerCase() + (e.className ? '.' + String(e.className).split(' ').join('.') : ''), v: props.map(x => s.getPropertyValue(x)) };
  }), PROPRIETES);
  await p.close();
  return valeurs;
}

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  const ecarts = [];
  for (const largeur of [1280, 600]) {
    const a = await mesurer(browser, origine, largeur);
    const b = await mesurer(browser, genere, largeur);
    a.forEach((e, k) => e.v.forEach((v, j) => {
      if (v !== b[k].v[j]) ecarts.push(`${largeur} px, ${e.nom} : ${PROPRIETES[j]} = ${v} (WordPress) / ${b[k].v[j]} (généré)`);
    }));
  }
  await browser.close();
  if (ecarts.length) {
    console.error(ecarts.join('\n'));
    process.exit(1);
  }
  console.log('Cascade des blocs : OK');
})();

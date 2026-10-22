// Rendu des répartitions de colonnes du pied et de la largeur de ses rangées.
// Usage : RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
// Pour chaque répartition proposée par le formulaire : configuration de la rangée principale du
// pied, puis mesure de la grille dans Chromium. Ordinateur et tablette : colonnes dans les
// proportions d'Astra ; mobile : une seule colonne. Vérifie aussi qu'avec un conteneur de 1600 px
// les trois rangées du pied suivent cette largeur. Modifie la configuration du site : site de test.
const { execFileSync } = require('child_process');
const { chromium } = require('playwright');

const RACINE = process.env.RACINE || 'vendor/spip/spip';
const URL = (process.env.URL || 'http://127.0.0.1:8404').replace(/\/$/, '');
const SPIP_CLI = (process.env.SPIP_CLI || '').split(' ').filter(Boolean);
if (!SPIP_CLI.length) { console.error('SPIP_CLI doit désigner spip-cli'); process.exit(2); }
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';

// Astra_Builder_Helper::$grid_size_mapping, pour les répartitions proposées (astra_hfb_repartitions).
const REPARTITIONS = {
  full: [1], '2-equal': [1, 1], '2-lheavy': [2, 1], '2-rheavy': [1, 2],
  '3-equal': [1, 1, 1], '3-cwide': [1, 3, 1], '3-cheavy': [1, 2, 1], '3-lheavy': [2, 1, 1], '3-rheavy': [1, 1, 2],
  '4-equal': [1, 1, 1, 1], '4-lheavy': [2, 1, 1, 1], '4-rheavy': [1, 1, 1, 2],
  '5-equal': [1, 1, 1, 1, 1], '6-equal': [1, 1, 1, 1, 1, 1],
};
const APPAREILS = [
  { nom: 'ordinateur', largeur: 1280, mobile: false },
  { nom: 'tablette', largeur: 800, mobile: false },
  { nom: 'mobile', largeur: 390, mobile: true },
];
const echecs = [];

function configurer(php) {
  execFileSync(SPIP_CLI[0], [...SPIP_CLI.slice(1), 'php:eval', 'include_spip("inc/config");' + php], { cwd: RACINE });
  execFileSync(SPIP_CLI[0], [...SPIP_CLI.slice(1), 'cache:vider'], { cwd: RACINE });
}

function piedPrincipal(repartition, colonnes, autres = {}) {
  const config = {
    pied: { ...autres, primary: { primary_1: ['html-1'] } },
    rangees: { pied_primary: { colonnes, repartition } },
    elements: { pied: { 'html-1': { texte: 'Colonne 1' } } },
  };
  return 'ecrire_config("astra/hfb", json_decode(' + JSON.stringify(JSON.stringify(config)) + ', true));';
}

async function colonnes(browser, largeur, selecteur) {
  const page = await browser.newPage({ viewport: { width: largeur, height: 900 }, userAgent: AGENT });
  await page.goto(URL + '/', { waitUntil: 'load' });
  const valeur = await page.locator(selecteur).first().evaluate(e => getComputedStyle(e).gridTemplateColumns);
  await page.close();
  return valeur.split(' ').map(parseFloat);
}

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  try {
    for (const [repartition, fr] of Object.entries(REPARTITIONS)) {
      configurer(piedPrincipal(repartition, fr.length));
      for (const appareil of APPAREILS) {
        const px = await colonnes(browser, appareil.largeur, '.site-primary-footer-wrap .ast-builder-grid-row');
        const attendu = appareil.mobile ? [1] : fr;
        const proportions = px.length === attendu.length && px.every((w, i) => Math.abs(w / px[0] - attendu[i] / attendu[0]) < 0.02);
        if (!proportions) echecs.push(`${repartition} / ${appareil.nom} : colonnes ${px.map(Math.round).join(' ')} px, proportions attendues ${attendu.join(':')}`);
      }
    }

    // Largeur des rangées du pied : variable de largeur du conteneur, pas 1200 px.
    configurer('ecrire_config("astra/largeur_conteneur", "1600");' + piedPrincipal('3-equal', 3, { above: { above_1: ['copyright'] }, below: { below_1: ['html-2'] } }));
    const page = await browser.newPage({ viewport: { width: 1800, height: 900 }, userAgent: AGENT });
    await page.goto(URL + '/', { waitUntil: 'load' });
    for (const rangee of ['above', 'primary', 'below']) {
      const largeur = await page.locator(`.site-${rangee}-footer-wrap .ast-builder-grid-row`).first().evaluate(e => e.getBoundingClientRect().width).catch(() => 0);
      if (Math.abs(largeur - 1600) > 1) echecs.push(`rangée ${rangee} du pied : ${largeur} px de large avec un conteneur de 1600 px`);
    }
    await page.close();
  } finally {
    await browser.close();
    configurer('effacer_config("astra/hfb"); effacer_config("astra/largeur_conteneur");');
  }

  if (echecs.length) {
    console.error(echecs.join('\n'));
    process.exit(1);
  }
  console.log(`Répartitions : OK (${Object.keys(REPARTITIONS).length} répartitions × ${APPAREILS.length} appareils, largeur des rangées)`);
})();

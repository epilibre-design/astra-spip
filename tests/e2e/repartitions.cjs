// Rendu des répartitions de colonnes du pied et de la largeur de ses rangées.
// Usage : RACINE=vendor/spip/spip URL=http://127.0.0.1:8404 SPIP_CLI=… NODE_PATH=$PW/node_modules node tests/e2e/repartitions.cjs
// Pour chaque répartition proposée par le formulaire : configuration de la rangée principale du
// pied, puis mesure de la grille dans Chromium. Ordinateur et tablette : colonnes dans les
// proportions d'Astra ; mobile : une seule colonne. Puis, pour chaque répartition tablette et
// mobile proposée (structure par appareil), mesure à 800 et 390 px (répartitions de l'ordinateur
// gardées sur tablette : à 800 px seulement) ; `3-firstrow` et `3-lastrow`
// font en plus déborder la première ou la dernière colonne sur toute la largeur. Vérifie aussi
// qu'avec un conteneur de 1600 px les trois rangées du pied suivent cette largeur. Modifie la
// configuration du site : site de test.
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
// Répartitions tablette et mobile d'Astra par nombre de colonnes (astra_hfb_repartitions).
const PETITS = {
  2: ['2-equal', '2-lheavy', '2-rheavy', 'full'],
  3: ['3-equal', '3-cwide', '3-cheavy', '3-lheavy', '3-rheavy', '3-firstrow', '3-lastrow', 'full'],
  4: ['4-equal', '2-equal', 'full'],
  5: ['5-equal', 'full'],
  6: ['6-equal', 'full'],
};
// Tablette seulement : répartitions de l'ordinateur gardées pour les valeurs simples de la 1.2.0.
const TABLETTE = { 4: ['4-lheavy', '4-rheavy'] };
const DEBORDE = { '3-firstrow': 'first', '3-lastrow': 'last' };
const ORDINATEUR = { 2: '2-equal', 3: '3-equal', 4: '4-equal', 5: '5-equal', 6: '6-equal' };
const APPAREILS = [
  { nom: 'ordinateur', largeur: 1280, mobile: false },
  { nom: 'tablette', largeur: 800, mobile: false },
  { nom: 'mobile', largeur: 390, mobile: true },
];
const echecs = [];
let petites = 0;

function configurer(php) {
  execFileSync(SPIP_CLI[0], [...SPIP_CLI.slice(1), 'php:eval', 'include_spip("inc/config");' + php], { cwd: RACINE });
  execFileSync(SPIP_CLI[0], [...SPIP_CLI.slice(1), 'cache:vider'], { cwd: RACINE });
}

function piedPrincipal(repartition, colonnes, autres = {}) {
  const zones = { primary_1: ['html-1'] };
  if (colonnes === 3) Object.assign(zones, { primary_2: ['menu'], primary_3: ['social-icons-1'] });
  const config = {
    pied: { ...autres, primary: zones },
    rangees: { pied_primary: { colonnes, repartition } },
    elements: { pied: { 'html-1': { texte: 'Colonne 1' } } },
  };
  return 'ecrire_config("astra/hfb", json_decode(' + JSON.stringify(JSON.stringify(config)) + ', true));';
}

async function colonnes(browser, largeur, selecteur, deborde = '') {
  const page = await browser.newPage({ viewport: { width: largeur, height: 900 }, userAgent: AGENT });
  await page.goto(URL + '/', { waitUntil: 'load' });
  const grille = page.locator(selecteur).first();
  const valeur = await grille.evaluate(e => getComputedStyle(e).gridTemplateColumns);
  // Colonne qui déborde : sa largeur est celle de la grille.
  const pleine = deborde ? await grille.evaluate((e, d) => {
    const enfant = d === 'first' ? e.firstElementChild : e.lastElementChild;
    return Math.abs(enfant.getBoundingClientRect().width - e.clientWidth + parseFloat(getComputedStyle(e).paddingLeft) + parseFloat(getComputedStyle(e).paddingRight)) < 2;
  }, deborde) : true;
  await page.close();
  return { px: valeur.split(' ').map(parseFloat), pleine };
}

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  try {
    for (const [repartition, fr] of Object.entries(REPARTITIONS)) {
      configurer(piedPrincipal(repartition, fr.length));
      for (const appareil of APPAREILS) {
        const { px } = await colonnes(browser, appareil.largeur, '.site-primary-footer-wrap .ast-builder-grid-row');
        const attendu = appareil.mobile ? [1] : fr;
        const proportions = px.length === attendu.length && px.every((w, i) => Math.abs(w / px[0] - attendu[i] / attendu[0]) < 0.02);
        if (!proportions) echecs.push(`${repartition} / ${appareil.nom} : colonnes ${px.map(Math.round).join(' ')} px, proportions attendues ${attendu.join(':')}`);
      }
    }

    // Répartitions par appareil : la même valeur sur tablette et mobile, l'ordinateur à part.
    for (const [nombre, liste] of Object.entries(PETITS)) {
      for (const repartition of liste) {
        const n = Number(nombre);
        configurer(piedPrincipal({ desktop: ORDINATEUR[n], tablet: repartition, mobile: repartition }, n));
        for (const appareil of APPAREILS.slice(1)) {
          const { px, pleine } = await colonnes(browser, appareil.largeur, '.site-primary-footer-wrap .ast-builder-grid-row', DEBORDE[repartition] || '');
          const attendu = DEBORDE[repartition] ? [1, 1] : REPARTITIONS[repartition];
          const proportions = px.length === attendu.length && px.every((w, i) => Math.abs(w / px[0] - attendu[i] / attendu[0]) < 0.02);
          if (!proportions || !pleine) echecs.push(`${n} colonnes, ${repartition} / ${appareil.nom} : colonnes ${px.map(Math.round).join(' ')} px, proportions attendues ${attendu.join(':')}${pleine ? '' : ', colonne débordante trop étroite'}`);
        }
        const { px } = await colonnes(browser, 1280, '.site-primary-footer-wrap .ast-builder-grid-row');
        if (px.length !== n) echecs.push(`${n} colonnes, ${repartition} : ${px.length} colonnes sur ordinateur`);
        petites++;
      }
    }
    for (const [nombre, liste] of Object.entries(TABLETTE)) {
      for (const repartition of liste) {
        const n = Number(nombre);
        configurer(piedPrincipal({ desktop: ORDINATEUR[n], tablet: repartition, mobile: 'full' }, n));
        const { px } = await colonnes(browser, 800, '.site-primary-footer-wrap .ast-builder-grid-row');
        const attendu = REPARTITIONS[repartition];
        const proportions = px.length === attendu.length && px.every((w, i) => Math.abs(w / px[0] - attendu[i] / attendu[0]) < 0.02);
        if (!proportions) echecs.push(`${n} colonnes, ${repartition} / tablette : colonnes ${px.map(Math.round).join(' ')} px, proportions attendues ${attendu.join(':')}`);
        petites++;
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
  console.log(`Répartitions : OK (${Object.keys(REPARTITIONS).length} répartitions × ${APPAREILS.length} appareils, ${petites} répartitions tablette et mobile, largeur des rangées)`);
})();

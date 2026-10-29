// RECETTE-28 : rendu des routes d'Astra avant et après une modification, sans autre moteur actif.
//   node avant_apres.cjs capturer <URL> <dossier> <configuration>   HTML normalisé et captures (1280 et 390 px)
//   node avant_apres.cjs comparer <dossier avant> <dossier après>   écarts de HTML et de pixels
// Le HTML est comparé en entier après retrait des seuls horodatages de fichiers (|timestamp) ; les
// captures pixel par pixel, dans Chromium, sans dépendance supplémentaire.
const fs = require('fs'), path = require('path'), { execFileSync } = require('child_process');
const { chromium } = require('playwright');
const ROUTES = {
  sommaire: '/', article: '/spip.php?article1', rubrique: '/spip.php?rubrique1', page_unique: '/spip.php?article4',
  sous_page: '/spip.php?article6', recherche: '/spip.php?page=recherche&recherche=jardin', erreur_404: '/spip.php?article99999',
};
// auteur.html et mot.html ne sont pas couverts : l'environnement de test n'a ni auteur publié ni mot-clé.
const LARGEURS = [1280, 390];
// Seules variations d'un calcul à l'autre : horodatages des fichiers et jeton chiffré des blocs ajax.
const normaliser = (html) => html.replace(/(\.(?:css|js|png|svg|woff2?|jpe?g))\?\d+/g, '$1').replace(/data-ajax-env='[^']*'/g, "data-ajax-env=''");

async function capturer(url, dossier, conf) {
  const sortie = path.join(dossier, conf); fs.mkdirSync(sortie, {recursive: true});
  const browser = await chromium.launch({headless: true, executablePath: process.env.CHROMIUM || undefined});
  try {
    for (const [nom, route] of Object.entries(ROUTES)) {
      // curl : SPIP sous php -S envoie une ligne d'état « Http/1.0 404 » que fetch refuse.
      const r = execFileSync('curl', ['-s', '-w', '\n%{http_code}', url + route], {encoding: 'utf8', maxBuffer: 1 << 26});
      const k = r.lastIndexOf('\n');
      fs.writeFileSync(path.join(sortie, `${nom}.html`), `${r.slice(k + 1)}\n` + normaliser(r.slice(0, k)));
      for (const largeur of LARGEURS) {
        const page = await browser.newPage({viewport: {width: largeur, height: 900}});
        await page.goto(url + route, {waitUntil: 'load'});
        await page.addStyleTag({content: '*,*::before,*::after{transition:none!important;animation:none!important;caret-color:transparent!important}'});
        await page.waitForTimeout(150);
        fs.writeFileSync(path.join(sortie, `${nom}-${largeur}.png`), await page.screenshot({fullPage: true}));
        await page.close();
      }
    }
  } finally { await browser.close(); }
}

async function comparer(avant, apres) {
  const ecarts = [], espaces = [];
  const browser = await chromium.launch({headless: true, executablePath: process.env.CHROMIUM || undefined});
  const page = await browser.newPage();
  try {
    for (const conf of fs.readdirSync(avant).sort()) {
      for (const fichier of fs.readdirSync(path.join(avant, conf)).sort()) {
        const a = path.join(avant, conf, fichier), b = path.join(apres, conf, fichier);
        if (!fs.existsSync(b)) { ecarts.push(`${conf}/${fichier} : absent après`); continue; }
        if (fichier.endsWith('.html')) {
          const [ta, tb] = [a, b].map(f => fs.readFileSync(f, 'utf8'));
          if (ta === tb) continue;
          // Espaces entre balises seulement : sans effet sur la structure, signalé à part.
          const compacter = (t) => t.replace(/>\s+</g, '><').replace(/\s+/g, ' ');
          if (compacter(ta) === compacter(tb)) { espaces.push(`${conf}/${fichier}`); continue; }
          const la = ta.split('\n'), lb = tb.split('\n');
          const i = la.findIndex((l, k) => l !== lb[k]);
          if (i >= 0 || la.length !== lb.length) {
            const k = i >= 0 ? i : Math.min(la.length, lb.length);
            ecarts.push(`${conf}/${fichier} : ligne ${k + 1}\n  avant : ${(la[k] ?? '').trim().slice(0, 200)}\n  après : ${(lb[k] ?? '').trim().slice(0, 200)}`);
          }
        } else {
          const [da, db] = [a, b].map(f => 'data:image/png;base64,' + fs.readFileSync(f).toString('base64'));
          const d = await page.evaluate(async ([da, db]) => {
            const charger = (src) => new Promise(ok => { const i = new Image(); i.onload = () => ok(i); i.src = src; });
            const [ia, ib] = await Promise.all([charger(da), charger(db)]);
            if (ia.width !== ib.width || ia.height !== ib.height) return {taille: [ia.width, ia.height, ib.width, ib.height]};
            const pixels = (img) => { const c = document.createElement('canvas'); c.width = img.width; c.height = img.height;
              const x = c.getContext('2d'); x.drawImage(img, 0, 0); return x.getImageData(0, 0, img.width, img.height).data; };
            const [pa, pb] = [pixels(ia), pixels(ib)]; let n = 0;
            for (let k = 0; k < pa.length; k += 4) if (pa[k] !== pb[k] || pa[k + 1] !== pb[k + 1] || pa[k + 2] !== pb[k + 2]) n++;
            return {differents: n, total: pa.length / 4};
          }, [da, db]);
          if (d.taille || d.differents) ecarts.push(`${conf}/${fichier} : ${JSON.stringify(d)}`);
        }
      }
    }
  } finally { await browser.close(); }
  console.log(ecarts.length ? ecarts.join('\n') : 'Aucun écart.');
  if (espaces.length) console.log(`Espaces entre balises seulement (${espaces.length}) : ${espaces.join(', ')}`);
  const n = fs.readdirSync(avant).reduce((s, c) => s + fs.readdirSync(path.join(avant, c)).length, 0);
  console.log(`${n} fichiers comparés, ${ecarts.length} écart(s)`);
  process.exitCode = ecarts.length ? 1 : 0;
}

const [mode, ...args] = process.argv.slice(2);
(mode === 'capturer' ? capturer(...args) : mode === 'comparer' ? comparer(...args) : Promise.reject(new Error('mode : capturer | comparer')))
  .catch(e => { console.error(e); process.exitCode = 2; });

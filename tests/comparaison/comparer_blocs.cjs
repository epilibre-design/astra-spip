// Compare chaque bloc de la page de démonstration dans WordPress et dans le SPIP importé.
// Usage : NODE_PATH=$PW/node_modules node comparer_blocs.cjs <URL page WordPress> <URL page SPIP> <dossier>
// Blocs appariés dans l'ordre : enfants directs de .entry-content. Les blocs que wp2spip convertit
// en typographie SPIP (paragraphe, intertitre, liste, séparateur, code, vers) n'ont plus de classe
// wp-block-* : ils sont nommés par leur balise et comparés aussi.
// Seuil 1 % ; une perte caractérisée (tests/references/blocs/pertes.json) masque sa zone et
// vérifie sa propriété à la place. Une perte de masque :scope (bloc converti par wp2spip en
// typographie SPIP) n'est pas comparée en image : seule sa propriété est vérifiée.
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const { PNG } = require('pngjs');
const pixelmatch = require('pixelmatch');

const [urlWp, urlSpip, dossier] = process.argv.slice(2);
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
const SEUIL = 0.01;
const pertes = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'references', 'blocs', 'pertes.json'), 'utf8'));

async function blocs(browser, url, largeur, prefixe, masques) {
  const page = await browser.newPage({ viewport: { width: largeur, height: 900 }, userAgent: AGENT });
  await page.goto(url, { waitUntil: 'load' });
  // Sans animations ; bouton de retour en haut (position fixe) masqué : il recouvrirait les blocs.
  await page.addStyleTag({ content: '*,*::before,*::after{transition:none!important;animation:none!important}#ast-scroll-top{display:none!important}' });
  const elements = await page.locator('.entry-content > :not(script, style)').all();
  const resultat = [];
  for (const [i, e] of elements.entries()) {
    const classes = ((await e.getAttribute('class')) || '').split(' ');
    const nom = classes.find(c => c.startsWith('wp-block-')) || await e.evaluate(x => x.tagName.toLowerCase());
    for (const m of masques.filter(p => p.bloc === nom && p.masque !== ':scope')) {
      await e.locator(m.masque).evaluateAll(n => n.forEach(x => { x.style.visibility = 'hidden'; }));
    }
    const fichier = `${dossier}/${prefixe}-${largeur}-${i}-${nom}.png`;
    await e.screenshot({ path: fichier });
    resultat.push({ nom, fichier });
  }
  const verifs = [];
  for (const p of masques) {
    const v = await page.locator(`.entry-content > ${p.selecteur || '.' + p.bloc} ${p.cible || ''}`.trim()).first().evaluate((x, prop) => getComputedStyle(x).getPropertyValue(prop), p.propriete).catch(() => null);
    verifs.push({ p, v });
  }
  await page.close();
  return { resultat, verifs };
}

(async () => {
  fs.mkdirSync(dossier, { recursive: true });
  const browser = await chromium.launch({ executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome' });
  let echec = false;
  for (const largeur of [1280, 390]) {
    const wp = await blocs(browser, urlWp, largeur, 'wordpress', pertes);
    const spip = await blocs(browser, urlSpip, largeur, 'spip', pertes);
    if (wp.resultat.length !== spip.resultat.length) {
      console.log(`ÉCART ${largeur} px : nombres de blocs différents\n  WordPress : ${wp.resultat.map(b => b.nom).join(' ')}\n  SPIP      : ${spip.resultat.map(b => b.nom).join(' ')}`);
      echec = true;
      continue;
    }
    wp.resultat.forEach((b, i) => {
      const s = spip.resultat[i];
      const noms = b.nom === s.nom ? b.nom : `${b.nom} / ${s.nom}`;
      if (pertes.some(p => p.bloc === b.nom && p.masque === ':scope')) {
        console.log(`perte ${largeur} px ${i} ${noms} : converti, non comparé en image`);
        return;
      }
      const a = PNG.sync.read(fs.readFileSync(b.fichier));
      const c = PNG.sync.read(fs.readFileSync(s.fichier));
      // Meilleur alignement vertical à ±2 px près : la position sous-pixel d'un bloc dépend de la
      // hauteur du contenu qui le précède, différente entre les deux sites.
      const l = Math.min(a.width, c.width);
      let meilleur = null;
      for (let dy = -2; dy <= 2; dy++) {
        const h = Math.min(a.height - Math.max(0, dy), c.height - Math.max(0, -dy));
        if (h <= 0) continue;
        const ra = new PNG({ width: l, height: h });
        const rc = new PNG({ width: l, height: h });
        PNG.bitblt(a, ra, 0, Math.max(0, dy), l, h, 0, 0);
        PNG.bitblt(c, rc, 0, Math.max(0, -dy), l, h, 0, 0);
        const diff = new PNG({ width: l, height: h });
        const n = pixelmatch(ra.data, rc.data, diff.data, l, h, { threshold: 0.1 }) / (l * h);
        if (!meilleur || n < meilleur.part) meilleur = { part: n, diff };
      }
      const part = meilleur.part;
      fs.writeFileSync(`${dossier}/diff-${largeur}-${i}-${b.nom}.png`, PNG.sync.write(meilleur.diff));
      const tailles = Math.abs(a.width - c.width) > 2 || Math.abs(a.height - c.height) > 2;
      const ko = part > SEUIL || tailles;
      echec = echec || ko;
      console.log(`${ko ? 'ÉCART' : 'ok   '} ${largeur} px ${i} ${noms} : ${(part * 100).toFixed(2)} %${tailles ? ` ; ${a.width}×${a.height} / ${c.width}×${c.height}` : ''}`);
    });
    for (const { p, v } of spip.verifs) {
      const ok = v === p.attendu;
      echec = echec || !ok;
      console.log(`${ok ? 'ok   ' : 'ÉCART'} ${largeur} px perte ${p.bloc} ${p.perdu} : ${p.propriete} = ${v} (attendu ${p.attendu})`);
    }
  }
  await browser.close();
  process.exit(echec ? 1 : 0);
})();

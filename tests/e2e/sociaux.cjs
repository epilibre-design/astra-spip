// NODE_PATH=… node tests/e2e/sociaux.cjs <URL> <SORTIES> ; URL_WP facultative pour comparaison.
const fs = require('node:fs');
const assert = require('node:assert/strict');
const {chromium} = require('playwright');
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';

(async () => {
  const [url, sorties] = process.argv.slice(2);
  assert(url && sorties, 'Usage : sociaux.cjs <URL> <SORTIES>');
  fs.mkdirSync(sorties, {recursive:true});
  const browser = await chromium.launch({executablePath:process.env.HOME+'/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome'});
  const rapports = {};
  try {
    for (const [nom, adresse] of [['spip', url], ...(process.env.URL_WP ? [['wordpress',process.env.URL_WP]] : [])]) {
      rapports[nom] = [];
      for (const largeur of [1280,768,390]) {
        const page = await browser.newPage({viewport:{width:largeur,height:900},userAgent:AGENT});
        const erreurs = [];
        page.on('pageerror',e=>erreurs.push(e.message));
        await page.goto(adresse,{waitUntil:'load'});
        const donnees = await page.evaluate(() => {
          const ids = [...document.querySelectorAll('[id]')].map(e=>e.id);
          const collisions = ids.filter((id,i)=>ids.indexOf(id)!==i);
          const references = [...document.querySelectorAll('[aria-labelledby],[aria-describedby]')].flatMap(e =>
            ['aria-labelledby','aria-describedby'].flatMap(a=>(e.getAttribute(a)||'').split(/\s+/).filter(Boolean)));
          const icones = [...document.querySelectorAll('a.ast-email,a.ast-google_reviews')].map(e => {
            const svg=e.querySelector('svg'), style=getComputedStyle(svg), rect=e.getBoundingClientRect();
            return {reseau:e.classList.contains('ast-email')?'email':'google_reviews',nom:e.getAttribute('aria-label'),href:e.getAttribute('href'),
              contexte:e.closest('#ast-desktop-header')?'ordinateur':e.closest('#ast-mobile-header')?'mobile':'pied',
              visible:rect.width>0&&rect.height>0,width:style.width,height:style.height,fill:style.fill};
          });
          return {collisions,referencesAbsentes:references.filter(id=>ids.filter(x=>x===id).length!==1),titresFixes:document.querySelectorAll('title#email,title#google_reviews').length,icones};
        });
        assert.deepEqual(erreurs,[],nom+' erreurs JS');
        assert.deepEqual(donnees.collisions,[],nom+' IDs');
        assert.deepEqual(donnees.referencesAbsentes,[],nom+' références ARIA');
        assert.equal(donnees.titresFixes,0);
        for(const reseau of ['email','google_reviews']) {
          const icones=donnees.icones.filter(e=>e.reseau===reseau);
          assert.equal(icones.length,3,nom+' '+reseau);
          for(const icone of icones) {
            assert.equal(icone.nom,reseau==='email'?'E-mail':'Google Reviews');
            if(reseau==='email') assert.match(icone.href,/^mailto:nom@example\.test$/i);
          }
        }
        const survols=[];
        for(const reseau of ['email','google_reviews']) {
          const lien=page.locator('#colophon a.ast-'+reseau);
          await lien.hover();
          survols.push(await lien.locator('svg').evaluate(e=>getComputedStyle(e).fill));
        }
        rapports[nom].push({largeur,...donnees,survols});
        await page.screenshot({path:`${sorties}/${nom}-${largeur}.png`,fullPage:true});
        await page.close();
      }
    }
    fs.writeFileSync(`${sorties}/mesures.json`,JSON.stringify(rapports,null,2)+'\n');
    // Le schéma mailto est insensible à la casse ; conserver les valeurs brutes dans le rapport.
    const comparer = vues => vues.map(v=>({...v,icones:v.icones.map(i=>({...i,href:i.href.replace(/^mailto:/i,'mailto:')}))}));
    if(rapports.wordpress) assert.deepEqual(comparer(rapports.spip),comparer(rapports.wordpress),'Rendu social WordPress/SPIP');
    console.log('R27 : IDs, noms et e-mails valides aux trois largeurs'+(rapports.wordpress?' ; styles et survol identiques à WordPress.':'.'));
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});

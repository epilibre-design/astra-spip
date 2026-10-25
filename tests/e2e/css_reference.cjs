// NODE_PATH=… node css_reference.cjs <URL> <PROFIL> <SORTIES> ; URL_WP facultative.
const fs=require('node:fs');
const assert=require('node:assert/strict');
const {chromium}=require('playwright');
const {cssStabilisation}=require('../comparaison/stabilisation.cjs');
const AGENT='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
(async()=>{
  const [url,profil,sorties]=process.argv.slice(2);
  assert(url&&sorties&&['cascade','replis','responsive','blocs'].includes(profil),'Usage : css_reference.cjs <URL> <cascade|replis|responsive|blocs> <SORTIES>');
  fs.mkdirSync(sorties,{recursive:true});
  const browser=await chromium.launch({executablePath:process.env.HOME+'/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome'});
  const resultats={};
  try{
    if(profil==='responsive'){await require('./lib/responsive.cjs')(browser,url,sorties,process.env.URL_WP);return;}
    for(const[nom,adresse] of [['spip',url],...(process.env.URL_WP?[['wordpress',process.env.URL_WP]]:[])]){
      resultats[nom]=[];
      for(const largeur of [1280,768,390]){
        const page=await browser.newPage({viewport:{width:largeur,height:900},userAgent:AGENT});
        const erreurs=[];
        page.on('pageerror',e=>erreurs.push(e.message));
        await page.goto(adresse,{waitUntil:'load'});
        await page.addStyleTag({content:cssStabilisation('')});
        let valeurs;
        if(profil==='cascade'){
          valeurs=await page.evaluate(()=>({code:getComputedStyle(document.querySelector('.astra-preuve-code code')).fontSize,
            kbd:getComputedStyle(document.querySelector('.astra-preuve-code kbd')).fontSize,
            variable:getComputedStyle(document.querySelector('.astra-preuve-code var')).fontSize,
            titre:getComputedStyle(document.querySelector('.astra-preuve-titre')).fontWeight}));
          assert.deepEqual(valeurs,{code:'24px',kbd:'24px',variable:'24px',titre:'400'},nom+' cascade');
          if(await page.locator('body.single-post').count()){
            valeurs.marges=await page.evaluate(()=>{
              const h=document.querySelector('.astra-preuve-titre'),p=h.nextElementSibling,s=getComputedStyle(h),suivant=getComputedStyle(p);
              return {taille:parseFloat(s.fontSize),haut:parseFloat(s.marginTop),bas:parseFloat(s.marginBottom),suivant:parseFloat(suivant.marginBottom),distance:p.getBoundingClientRect().top-h.getBoundingClientRect().bottom};
            });
            assert(Math.abs(valeurs.marges.bas-(valeurs.marges.taille*.3+10))<.1,nom+' marge du titre après la règle générique');
          }
          valeurs.codeTitre=await page.evaluate(()=>{const h=document.querySelector('.astra-preuve-titre'),c=document.createElement('code');c.textContent='code';h.append(c);const mesure={code:getComputedStyle(c).fontSize,titre:getComputedStyle(h).fontSize};c.remove();return mesure});
          assert.equal(valeurs.codeTitre.code,valeurs.codeTitre.titre,nom+' code dans un titre');
          // Fixture technique à trois niveaux : n'ajoute pas cette profondeur au builder.
          await page.evaluate(()=>{
            const menu=document.createElement('div');menu.className='ast-builder-menu-1 astra-preuve-menu';
            menu.innerHTML='<style>.astra-preuve-menu{width:320px}.astra-preuve-menu ul,.astra-preuve-menu li{display:block!important;position:static!important;opacity:1!important;visibility:visible!important}.astra-preuve-menu a{display:block}</style><ul class="main-header-menu"><li class="menu-item menu-item-has-children"><a class="menu-link" data-niveau="1" href="#">Premier</a><ul class="sub-menu"><li class="menu-item menu-item-has-children"><a class="menu-link" data-niveau="2" href="#">Deuxième</a><ul class="sub-menu"><li class="menu-item"><a class="menu-link" data-niveau="3" href="#">Troisième</a></li></ul></li></ul></li></ul>';
            document.querySelector('.entry-content').append(menu);
          });
          await page.locator('.astra-preuve-menu [data-niveau="3"]').hover();
          valeurs.survols=await page.locator('.astra-preuve-menu a').evaluateAll(liens=>liens.map(a=>({couleur:getComputedStyle(a).color,fond:getComputedStyle(a).backgroundColor})));
          assert.deepEqual(valeurs.survols,[{couleur:'rgb(0, 255, 0)',fond:'rgb(17, 34, 51)'},{couleur:'rgb(17, 17, 17)',fond:'rgba(0, 0, 0, 0)'},{couleur:'rgb(0, 255, 0)',fond:'rgb(17, 34, 51)'}],nom+' parent intermédiaire non survolé');
        }else if(profil==='replis'){
          valeurs=await page.evaluate(()=>{
            const champ=document.createElement('input');champ.type='date';document.querySelector('.entry-content').append(champ);
            const racine=document.documentElement,ancien=racine.style.cssText;
            const avant=getComputedStyle(champ).backgroundColor;
            racine.style.setProperty('--ast-global-color-secondary','initial');
            racine.style.setProperty('--ast-global-color-5','#123456');
            const repli=getComputedStyle(champ).backgroundColor;
            racine.style.cssText=ancien;
            const apres=getComputedStyle(champ).backgroundColor;champ.remove();
            return {avant,repli,apres};
          });
          assert.equal(valeurs.repli,'rgb(18, 52, 86)',nom+' repli champ date');
          assert.equal(valeurs.apres,valeurs.avant,nom+' palette restaurée');
          valeurs.etiquettes=await page.evaluate(()=>{const f=document.createElement('fieldset');f.innerHTML='<legend>Légende</legend><label>Champ</label><span>Couleur palette</span>';document.querySelector('.entry-content').append(f);f.lastElementChild.style.color='var(--ast-global-color-2)';const couleurs=[...f.children].map(e=>getComputedStyle(e).color);f.remove();return couleurs});
          assert.equal(valeurs.etiquettes[0],valeurs.etiquettes[2],nom+' legend palette');
          assert.equal(valeurs.etiquettes[1],valeurs.etiquettes[2],nom+' label palette');
        }else if(profil==='blocs'){
          valeurs=await page.evaluate(()=>{
            const lien=document.querySelector('.wp-block-button > a'),s=getComputedStyle(lien);
            const colonnes=[...document.querySelectorAll('.entry-content .wp-block-columns')].map(e=>{const s=getComputedStyle(e);return {colonnes:e.children.length,display:s.display,gap:s.gap,width:e.getBoundingClientRect().width,enfants:[...e.children].map(c=>({width:c.getBoundingClientRect().width,display:getComputedStyle(c).display}))}});
            return {rayon:s.borderRadius,ombre:s.boxShadow,taille:s.fontSize,haut:s.paddingTop,gauche:s.paddingLeft,colonnes};
          });
          assert.equal(valeurs.rayon,process.env.ASTRA_CSS_RAYON_BOUTON||'4px',nom+' rayon du bouton importé');
          assert.equal(valeurs.ombre,'rgba(0, 0, 0, 0.05) 0px 1px 2px 0px',nom+' ombre du bouton importé');
          assert(valeurs.colonnes.length>=2,nom+' deux et trois colonnes réellement importées');
          // Sondes CSS natives, distinctes de l'import dont le comparateur garde les quatre pertes.
          // wp2spip retire alignwide/full : ces classes ne sont pas prétendues conservées.
          valeurs.sondes=await page.evaluate(()=>{
            const contenu=document.querySelector('.entry-content'),modele=contenu.querySelector('.wp-block-columns'),mesures=[];
            for(const alignement of['normal','wide','full']){
              const e=modele.cloneNode(true);if(alignement!=='normal')e.classList.add('align'+alignement);contenu.append(e);
              const r=e.getBoundingClientRect(),s=getComputedStyle(e);mesures.push({alignement,width:r.width,marginLeft:s.marginLeft,marginRight:s.marginRight,gap:s.gap});e.remove();
            }
            const h=document.createElement('h2');h.className='wp-block-heading';h.style.fontWeight='400';h.innerHTML='Titre avec <code>code</code>';contenu.append(h);
            const titre={poids:getComputedStyle(h).fontWeight,taille:getComputedStyle(h).fontSize,code:getComputedStyle(h.firstElementChild).fontSize};h.remove();
            return{origine:'sondes CSS natives après mesure de l’import, sans écriture éditoriale',colonnes:mesures,titre};
          });
          assert.equal(valeurs.sondes.titre.poids,'400',nom+' priorité de la graisse du bloc');
          assert.equal(valeurs.sondes.titre.code,valeurs.sondes.titre.taille,nom+' code dans titre natif');
        }else{
          throw new Error('Profil '+profil+' : assertions à fournir dans sa tâche avant de l’exécuter');
        }
        assert.deepEqual(erreurs,[],nom+' pageerror');
        resultats[nom].push({largeur,...valeurs});
        await page.screenshot({path:`${sorties}/${nom}-${largeur}.png`,fullPage:true});
        await page.close();
      }
    }
    fs.writeFileSync(`${sorties}/mesures.json`,JSON.stringify(resultats,null,2)+'\n');
    if(resultats.wordpress)assert.deepEqual(resultats.spip,resultats.wordpress,'Propriétés CSS WordPress/SPIP');
    if(profil==='blocs'&&process.env.ASTRA_CSS_RARES)await require('./lib/css-rares.cjs')(browser,process.env.ASTRA_CSS_RARES,sorties+'/rares');
    console.log(profil+' : assertions et comparaison CSS réussies aux trois largeurs.');
  }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});

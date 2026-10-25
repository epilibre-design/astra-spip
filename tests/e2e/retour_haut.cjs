// Vérifie le composant fixé à la fenêtre, séparément des captures du pied.
const fs=require('node:fs');const assert=require('node:assert/strict');const{chromium}=require('playwright');
const AGENT='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
(async()=>{
 const[url,sorties]=process.argv.slice(2);assert(url&&sorties,'Usage : retour_haut.cjs <URL> <SORTIES>');fs.mkdirSync(sorties,{recursive:true});
 const b=await chromium.launch({executablePath:process.env.HOME+'/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome'});const resultats={};
 try{
  for(const[nom,adresse]of[['spip',url],...(process.env.URL_WP?[['wordpress',process.env.URL_WP]]:[])]){
   resultats[nom]=[];
   for(const largeur of[1280,768,390]){
    const p=await b.newPage({viewport:{width:largeur,height:900},userAgent:AGENT,reducedMotion:'reduce'});const erreurs=[];p.on('pageerror',e=>erreurs.push(e.message));
    await p.goto(adresse,{waitUntil:'load'});
    await p.evaluate(()=>{const espace=document.createElement('div');espace.style.minHeight='2500px';document.querySelector('#colophon').before(espace);window.scrollTo(0,0);});
    const bouton=p.locator('#ast-scroll-top');
    await p.waitForTimeout(100);assert.equal(await bouton.isVisible(),false,nom+' initial');
    await p.evaluate(()=>window.scrollTo(0,500));await bouton.waitFor({state:'visible'});
    const style=await bouton.evaluate(e=>{const r=e.getBoundingClientRect(),s=getComputedStyle(e);return{width:r.width,height:r.height,background:s.backgroundColor,tabindex:e.tabIndex};});
    assert(style.width>0&&style.height>0);assert.equal(style.tabindex,0);
    await bouton.click();await p.waitForFunction(()=>window.scrollY<=1);
    assert.deepEqual(erreurs,[],nom+' pageerror');resultats[nom].push({largeur,...style,retour:await p.evaluate(()=>window.scrollY)});
    await p.close();
   }
  }
  fs.writeFileSync(`${sorties}/mesures.json`,JSON.stringify(resultats,null,2)+'\n');
  if(resultats.wordpress)assert.deepEqual(resultats.spip,resultats.wordpress,'Bouton retour en haut WordPress/SPIP');
  console.log('Retour en haut : apparition au défilement, dimensions et action conformes aux trois largeurs.');
 }finally{await b.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});

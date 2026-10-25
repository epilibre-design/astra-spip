const fs=require('node:fs'),assert=require('node:assert/strict');
const AGENT='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
module.exports=async function rares(browser,fichier,sorties){
 const fixtures=JSON.parse(fs.readFileSync(fichier,'utf8')),mesures={};
 fs.mkdirSync(sorties,{recursive:true});
 for(const[cas,rendus]of Object.entries(fixtures)){
  mesures[cas]={};
  for(const[nom,fichierHtml]of Object.entries(rendus)){
   mesures[cas][nom]=[];
   for(const largeur of[1280,768,390]){
    const p=await browser.newPage({viewport:{width:largeur,height:900},userAgent:AGENT}),erreurs=[];p.on('pageerror',e=>erreurs.push(e.message));
    const html=fs.readFileSync(fichierHtml,'utf8'),accueil=html.match(/<a\b[^>]*\bhref=["'](https?:\/\/[^"']+)/i);
    assert(accueil,nom+' URL du site dans son rendu');
    const url=new URL('?astra-preuve-rares='+cas,accueil[1]).href;
    try{
     await p.route(url,r=>r.fulfill({status:200,contentType:'text/html; charset=utf-8',body:html}));
     await p.goto(url,{waitUntil:'load'});
     assert.equal(await p.locator('html').evaluate(e=>getComputedStyle(e).direction),cas==='rtl'?'rtl':'ltr',nom+' direction '+cas);
     let valeurs;
     if(cas==='logo-vide'){
      const logo=p.locator(`${largeur<922?'#ast-mobile-header':'#ast-desktop-header'} .custom-logo-link img`);
      assert(await logo.isVisible(),nom+' logo visible sans menu');
      assert.equal(await p.locator('#masthead nav,#masthead .main-header-menu-toggle').count(),0,nom+' aucun menu ni déclencheur');
      valeurs=await logo.evaluate(e=>({width:e.getBoundingClientRect().width,height:e.getBoundingClientRect().height,charge:e.complete&&e.naturalWidth>0}));
      assert(valeurs.charge,nom+' image servie');
     }else if(cas==='rtl'){
      valeurs=await p.evaluate(()=>{
       const f=document.querySelector('#colophon'),r=f.getBoundingClientRect();
       return{direction:getComputedStyle(f).direction,colonnes:[...f.querySelectorAll('.site-footer-section')].map(e=>{const s=getComputedStyle(e),b=e.getBoundingClientRect();return{x:b.x-r.x,width:b.width,height:b.height,textAlign:s.textAlign,direction:s.direction}})};
      });
     }else{
      valeurs=await p.evaluate(()=>{
       const e=document.querySelector('#primary'),s=getComputedStyle(e),conteneur=e.closest('.ast-container'),c=getComputedStyle(conteneur),description=e.querySelector('.ast-archive-description');
       return {marginTop:s.marginTop,marginBottom:s.marginBottom,paddingTop:s.paddingTop,paddingBottom:s.paddingBottom,conteneurTop:c.paddingTop,conteneurBottom:c.paddingBottom,
        description:description?{haut:getComputedStyle(description).paddingTop,bas:getComputedStyle(description).paddingBottom}:null};
      });
     }
     assert.deepEqual(erreurs,[],nom+' pageerror '+cas);mesures[cas][nom].push({largeur,...valeurs});
     await p.screenshot({path:`${sorties}/${nom}-${cas}-${largeur}.png`,fullPage:true});
    }finally{await p.close()}
   }
  }
 }
 fs.writeFileSync(`${sorties}/mesures.json`,JSON.stringify(mesures,null,2)+'\n');
 for(const[cas,rs]of Object.entries(mesures))assert.deepEqual(rs.spip,rs.wordpress,'Disposition WordPress/SPIP '+cas);
 console.log('Dispositions rares : logo sans menu, pied RTL et espacements page/archive conformes.');
};

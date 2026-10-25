const fs=require('node:fs');
const assert=require('node:assert/strict');
const AGENT='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
const {cssStabilisation}=require('../../comparaison/stabilisation.cjs');

async function etat(page){
 return page.evaluate(()=>{
  const visible=e=>{const r=e.getBoundingClientRect(),s=getComputedStyle(e);return r.width>0&&r.height>0&&s.visibility!=='hidden'&&s.display!=='none'};
  return {innerWidth,clientWidth:document.documentElement.clientWidth,mobile:document.body.classList.contains('ast-header-break-point'),desktop:document.body.classList.contains('ast-desktop'),entetes:[...document.querySelectorAll('#ast-desktop-header,#ast-mobile-header')].filter(visible).map(e=>e.id)};
 });
}

module.exports=async function responsive(browser,url,sorties,urlWp){
 const mesures={};
 for(const[nom,adresse]of[['spip',url],...(urlWp?[['wordpress',urlWp]]:[])]){
  mesures[nom]={premierePeinture:[],sansJavascript:[],avecJavascript:[]};
  for(const largeur of[921,922,1280,768,390])for(const scrollbar of[false,true]){
   const context=await browser.newContext({viewport:{width:largeur,height:900},userAgent:AGENT});
   const page=await context.newPage(),erreurs=[];page.on('pageerror',e=>erreurs.push(e.message));
   let liberer;const attente=new Promise(r=>liberer=r);let bloque=false;
   try{
    await page.route('**/frontend.min.js*',async route=>{bloque=true;await attente;await route.continue()});
    const reponse=await page.goto(adresse,{waitUntil:'commit'});
    assert(reponse.ok(),nom+' HTTP');
    await page.locator('#masthead').waitFor({state:'attached'});
    // Le bundle classique de pied reste en attente : le DOM de l'en-tête peut déjà être peint.
    await page.waitForFunction(()=>document.querySelector('script[src*="frontend.min.js"]'));
    assert(bloque,nom+' bundle effectivement intercepté');
    await page.addStyleTag({content:`html{overflow-y:${scrollbar?'scroll':'hidden'}!important}`});
    const avant=await etat(page),mobile=await page.evaluate(()=>matchMedia('(max-width:921.99px)').matches);
    assert.equal(avant.mobile,mobile,nom+' classe avant le bundle, '+largeur);
    assert.equal(avant.desktop,!mobile,nom+' retrait classe ordinateur');
    assert.deepEqual(avant.entetes,[mobile?'ast-mobile-header':'ast-desktop-header'],nom+' en-tête initial');
    // Playwright attend document.fonts.ready, qui attend ici le script bloqué.
    // CDP capture précisément cet état avant load, sans déplacer la barrière du test.
    const cdp=await context.newCDPSession(page);
    const image=await cdp.send('Page.captureScreenshot',{format:'png'});
    fs.writeFileSync(`${sorties}/${nom}-premier-${largeur}-${scrollbar}.png`,Buffer.from(image.data,'base64'));
    await cdp.detach();
    liberer();await page.waitForLoadState('load');
    const apres=await etat(page);
    assert.deepEqual(apres,avant,nom+' pas de bascule au chargement du bundle');
    assert.equal(await page.locator('.astra-navigation-sans-js').count(),0,nom+' aucun repli dans le DOM actif');
    assert.deepEqual(erreurs,[],nom+' pageerror');
    mesures[nom].premierePeinture.push({largeur,scrollbar,avant,apres});
   }finally{liberer();await context.close()}
  }
  for(const largeur of[1280,768,390]){
   const page=await browser.newPage({viewport:{width:largeur,height:900},userAgent:AGENT,javaScriptEnabled:false});
   try{
    await page.goto(adresse,{waitUntil:'load'});
    const nav=page.locator('.astra-navigation-sans-js:visible');
    const liens=await nav.locator(':scope > ul > li > a').allTextContents();
    const visibles=await page.locator('#masthead a.menu-link:visible').allTextContents();
    if(nom==='spip'&&largeur<922){
     assert.equal(await nav.count(),1,'R2 un menu de repli');
     assert.deepEqual(liens.map(t=>t.trim()),['Jardinage','Actualités']);
     const enfant=nav.getByRole('link',{name:'Potager',exact:true});assert(await enfant.isVisible());
     const cible=await enfant.getAttribute('href');assert(cible&&!cible.startsWith('#'),'Enfant navigable');
     assert.equal(await nav.locator('[id],.sub-menu,button,[tabindex],[aria-expanded],[aria-haspopup],details').count(),0,'Liens simples');
    }else{
     assert.equal(await nav.count(),0);
     assert.equal(visibles.length,largeur<922?0:2,nom+' liens interactifs visibles sans JS');
    }
    mesures[nom].sansJavascript.push({largeur,replis:liens.map(t=>t.trim()),interactifs:visibles.map(t=>t.trim())});
    await page.screenshot({path:`${sorties}/${nom}-sans-js-${largeur}.png`});
   }finally{await page.close()}
   const active=await browser.newPage({viewport:{width:largeur,height:900},userAgent:AGENT});
   try{
    await active.goto(adresse,{waitUntil:'load'});await active.addStyleTag({content:cssStabilisation('')});
    assert.equal(await active.locator('.astra-navigation-sans-js').count(),0);
    const r=await active.locator('#masthead').boundingBox();
    mesures[nom].avecJavascript.push({largeur,width:r.width,height:r.height});
   }finally{await active.close()}
  }
 }
 // Rendus SPIP produits par les vrais gabarits, configuration restaurée côté CLI.
 assert(process.env.ASTRA_RESPONSIVE_RENDUS,'Fichier de rendus supplémentaires requis');
 const fixtures=JSON.parse(fs.readFileSync(process.env.ASTRA_RESPONSIVE_RENDUS,'utf8'));
 mesures.fixtures=[];
 for(const[nom,html]of Object.entries(fixtures))for(const largeur of[1280,768,390]){
  const p=await browser.newPage({viewport:{width:largeur,height:900},javaScriptEnabled:false,userAgent:AGENT});
  try{
   await p.route(url,route=>route.fulfill({status:200,contentType:'text/html; charset=utf-8',body:html}));
   await p.goto(url,{waitUntil:'load'});
   const nav=p.locator('.astra-navigation-sans-js:visible');
   const compte=await nav.count();
   assert.equal(compte,largeur>=922||nom==='vide'?0:nom==='deux'?2:1,nom+' nav visibles '+largeur);
   if(compte){
    assert.equal(await nav.locator('ul').count()>0,true);
    assert.equal(await nav.locator('[id],button,[tabindex],[aria-expanded],[aria-haspopup],.sub-menu').count(),0);
    for(let i=0;i<compte;i++)assert(await nav.nth(i).locator('a').count()>0,'Nav non vide');
    assert(await nav.getByRole('link',{name:'Notre jardin',exact:true}).isVisible(),'Source pages');
   }
   mesures.fixtures.push({nom,largeur,compte,liens:await nav.locator('a').allTextContents()});
  }finally{await p.close()}
 }
 // La différence sans JS est intentionnelle ; les deux rendus actifs gardent la même géométrie.
 fs.writeFileSync(`${sorties}/mesures.json`,JSON.stringify({...mesures,fractionnaire:'Non reproduit : viewport CSS entier ; cas 921,95 px complémentaire.'},null,2)+'\n');
 if(mesures.wordpress){
  assert.deepEqual(mesures.spip.premierePeinture,mesures.wordpress.premierePeinture,'Première peinture WordPress/SPIP, y compris borne 922');
  assert.deepEqual(mesures.spip.avecJavascript,mesures.wordpress.avecJavascript,'Géométrie active WordPress/SPIP');
 }
 // Même réponse mise en cache, consommée dans les deux ordres : choix côté navigateur.
 const contexte=await browser.newContext({userAgent:AGENT}),p=await contexte.newPage();
 try{
  let htmlPrecedent;
  for(const largeur of[1280,390,1280]){
   await p.setViewportSize({width:largeur,height:900});
   const r=await p.goto(url,{waitUntil:'load'}),html=await r.text();
   if(htmlPrecedent!==undefined)assert.equal(html,htmlPrecedent,'HTML indépendant du terminal');
   htmlPrecedent=html;assert.equal((await etat(p)).mobile,largeur<922);
  }
 }finally{await contexte.close()}
 console.log('Responsive : classes avant le bundle, seuils 921/922, repli sans JS et HTML partagé conformes.');
};

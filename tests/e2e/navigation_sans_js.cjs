const {chromium}=require('playwright');
const assert=require('node:assert/strict'),fs=require('node:fs');
(async()=>{
 const browser=await chromium.launch(),mesures=[];
 try {
  for(const js of [false,true]) for(const largeur of [320,390,768,921,922,923,1280]) {
   const page=await browser.newPage({viewport:{width:largeur,height:900},javaScriptEnabled:js});
   const erreurs=[];page.on('pageerror',e=>erreurs.push(e.message));
   await page.goto(process.env.URL,{waitUntil:'networkidle'});
   const nav=page.locator('.astra-navigation-sans-js:visible');
   if(!js && largeur<=922){
    assert.equal(await nav.count(),1,`menu de secours visible à ${largeur}`);
    assert(await nav.locator('a').count()>0);
    const menu=await nav.boundingBox(),contenu=await page.locator('#content').boundingBox();
    assert(menu.y+menu.height<=contenu.y+1,`menu hors du contenu à ${largeur}: ${JSON.stringify({menu,contenu})}`);
    for(const a of await nav.locator('a').all())assert(await a.isVisible());
   }else assert.equal(await nav.count(),0);
   assert(await page.locator('#masthead a:visible').count()>0,`identité visible ${largeur}`);
   assert(await page.locator('#masthead').evaluate(e=>e.getBoundingClientRect().right<=innerWidth+1),`débordement en-tête ${largeur}`);
   assert.deepEqual(erreurs,[]);
   mesures.push({js,largeur,replis:await nav.count()});await page.close();
  }
  if(process.env.SORTIE)fs.writeFileSync(process.env.SORTIE,JSON.stringify(mesures,null,2)+'\n');
  console.log(`Navigation : ${mesures.length} configurations conformes.`);
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});

// Mesures indépendantes du générateur PHP ; même sonde pour WordPress et SPIP.
const fs = require('fs');
const {chromium} = require('playwright');
(async () => {
 const [url, sortie] = process.argv.slice(2);
 const b = await chromium.launch();
 try {
  const r = {};
  for (const width of (process.env.ASTRA_LARGEURS||'1280,768,390').split(',').map(Number)) {
   const p = await b.newPage({viewport:{width,height:900},userAgent:'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36'});
   await p.goto(url,{waitUntil:'load'});await p.evaluate(()=>document.fonts.ready);
   await p.waitForTimeout(200);
   r[width]=await p.evaluate(()=>{
    const out={};
    for(const [n,sel,props] of [['titre','.site-title',['font-size','display']],['slogan','.site-header .site-description',['font-size','display','color']],['logo','header .custom-logo-link img',['width','height']],['entete','#masthead',['height']]]){
     const es=[...document.querySelectorAll(sel)],e=es.find(x=>x.getBoundingClientRect().height>0)||es[0];
     out[n]=e?Object.fromEntries(props.map(k=>[k,getComputedStyle(e).getPropertyValue(k)])):null;
     if(n==='logo'&&e)Object.assign(out[n],{naturalWidth:e.naturalWidth,naturalHeight:e.naturalHeight});
    }return out;
   });await p.close();
  }
  fs.writeFileSync(sortie,JSON.stringify(r,null,2)+'\n');
 } finally {await b.close();}
})();

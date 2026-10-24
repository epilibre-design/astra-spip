// Usage : NODE_PATH=... node verifier_sans_javascript.cjs <URL_WP_REFERENCE> <SORTIE_JSON>
const fs=require('fs');const {chromium}=require('playwright');
const [url, sortie]=process.argv.slice(2);
if (!url || !sortie) throw new Error('URL et sortie JSON requises');
(async()=>{const b=await chromium.launch();const out={};
for(const width of [1280,768,390]){
 const context=await b.newContext({javaScriptEnabled:false,viewport:{width,height:900}});const p=await context.newPage();
 await p.goto(url,{waitUntil:'load'});
 const r=await p.evaluate(()=>({body:document.body.className,desktop:getComputedStyle(document.querySelector('#ast-desktop-header')).display,mobile:getComputedStyle(document.querySelector('#ast-mobile-header')).display,liensMenuVisibles:[...document.querySelectorAll('.main-header-menu .menu-link,.ast-mobile-popup-drawer .menu-link')].filter(e=>e.checkVisibility({checkVisibilityCSS:true})).length,panneau:getComputedStyle(document.querySelector('#ast-mobile-popup')).visibility}));
 if (!r.body.split(/\s+/).includes('astra-4.13.8')) throw new Error('Cette sonde exige Astra 4.13.8 actif');
 out[width]=r;await context.close();
}await b.close();fs.writeFileSync(sortie,JSON.stringify(out,null,2));console.log(JSON.stringify(out,null,2));})();

const fs = require('fs');
const {chromium} = require('playwright');
// Usage : node verifier_css.cjs <astra-4.11.0> <astra-4.13.8> <astra-spip>
const [ancien, recent, port] = process.argv.slice(2);
if (!ancien || !recent || !port) throw new Error('Trois répertoires de sources sont requis');
const path = require('path');
(async()=>{
 const browser=await chromium.launch();const page=await browser.newPage();const results={};
 for(const version of ['4.11.0','4.13.8']){
  const css=fs.readFileSync(path.join(version==='4.11.0'?ancien:recent,'assets/css/minified/main.min.css'),'utf8');
  await page.setContent(`<style>${css}</style><p style="font-size:24px"><code id="code">exemple</code></p>`);
  results['code-'+version]=await page.locator('#code').evaluate(e=>getComputedStyle(e).fontSize);
 }
 const main=fs.readFileSync(path.join(port,'css/main.min.css'),'utf8');
 const dynamic=fs.readFileSync(path.join(port,'css/astra-dynamic.css'),'utf8');
 for(const fixed of [false,true]){
  // Démonstration en mémoire du repli ; aucune modification des sources.
  const css=fixed?dynamic.replace(/var\(\s*(--[\w-]+)\s*,\s*(--[\w-]+)\s*\)/g,'var($1, var($2))'):dynamic;
  await page.setContent(`<html style="--ast-global-color-secondary:initial;--ast-global-color-5:#123456"><style>${main}\n${css}</style><body><input id="input" type="date"></body></html>`);
  results['fallback-'+(fixed?'corrige-en-memoire':'port-actuel')]=await page.locator('#input').evaluate(e=>getComputedStyle(e).backgroundColor);
 }
 console.log(JSON.stringify(results,null,2));
 if(results['code-4.11.0']!=='15px'||results['code-4.13.8']!=='24px'||results['fallback-port-actuel']!=='rgba(0, 0, 0, 0)'||results['fallback-corrige-en-memoire']!=='rgb(18, 52, 86)')process.exitCode=1;
 await browser.close();
})();

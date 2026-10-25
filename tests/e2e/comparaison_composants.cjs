// Test du comparateur : un bouton fixé à la fenêtre ne fait pas partie du pied du builder.
const assert=require('node:assert/strict');
const fs=require('node:fs');
const os=require('node:os');
const path=require('node:path');
const {chromium}=require('playwright');
const{PNG}=require('pngjs');
const{cssStabilisation,stabiliserComposant}=require('../comparaison/stabilisation.cjs');
(async()=>{
 const b=await chromium.launch({executablePath:process.env.HOME+'/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome'});
 const dossier=fs.mkdtempSync(path.join(os.tmpdir(),'astra-composants-'));
 try{
  const fichiers=[];
  for(const hauteur of [0.25,3000.75]){
   const p=await b.newPage({viewport:{width:1280,height:900}});
   await p.setContent(`<style>body{margin:0}#avant{height:${hauteur}px}#colophon{height:300px;background:#eee}#ast-scroll-top{position:fixed;right:30px;bottom:30px;width:32px;height:32px;background:blue}</style><div id="avant"></div><footer id="colophon">Pied de référence</footer><div id="ast-scroll-top"></div>`);
   await stabiliserComposant(p,'#colophon');
   const fichier=path.join(dossier,hauteur+'.png');
   await p.locator('#colophon').screenshot({path:fichier});fichiers.push(fichier);
   assert.equal(await p.locator('#colophon').evaluate(e=>e.getBoundingClientRect().height),300);
   await p.close();
  }
  const images=fichiers.map(f=>PNG.sync.read(fs.readFileSync(f)));
  assert.equal(images[0].width,images[1].width);assert.equal(images[0].height,images[1].height);
  assert.equal(images[0].data.equals(images[1].data),true,'Même pied, hauteurs de contenu différentes');
  assert(!cssStabilisation('#masthead').includes('#ast-scroll-top'),'Le masque est limité à la capture du pied');
  console.log('Comparateur : pied identique sans inclure le bouton fixe, géométrie conservée.');
 }finally{await b.close();fs.rmSync(dossier,{recursive:true,force:true});}
})().catch(e=>{console.error(e);process.exitCode=1});

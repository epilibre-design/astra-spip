// NODE_PATH=… node navigation.cjs <URL> <PROFIL> <SORTIES> ; URL_WP facultative.
const fs=require('node:fs'),assert=require('node:assert/strict');
const {chromium}=require('playwright');
const {cssStabilisation}=require('../comparaison/stabilisation.cjs');
const AGENT='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
const focusVisible=page=>page.evaluate(()=>{const e=document.activeElement,r=e.getBoundingClientRect(),s=getComputedStyle(e);return{balise:e.tagName,id:e.id,visible:r.width>0&&r.height>0&&s.visibility!=='hidden',dansPanneau:!!e.closest('#ast-mobile-popup'),texte:e.textContent.trim().slice(0,90)}});
async function offcanvas(page,largeur,check){
 const popup=page.locator('#ast-mobile-popup'),trigger=page.locator('#ast-mobile-header .main-header-menu-toggle').first(),close=page.locator('#menu-toggle-close');
 check(!await popup.isVisible(),'panneau fermé invisible');
 if(largeur===1280){check(!await trigger.isVisible(),'aucune commande mobile ordinateur');return{ordinateur:true}}
 await page.addStyleTag({content:'.ast-mobile-popup-inner{padding:24px!important}'});
 const etats=[];
 for(const fermeture of['bouton','echap','voile']){
  await trigger.click();await popup.waitFor({state:'visible'});
  check(await trigger.getAttribute('aria-expanded')==='true','commande ouverte '+fermeture);
  check(await close.evaluate(e=>e===document.activeElement),'focus initial fermeture '+fermeture);
  const controles=await page.locator('#ast-mobile-popup button,#ast-mobile-popup [href],#ast-mobile-popup input,#ast-mobile-popup [tabindex]').evaluateAll(es=>es.filter(e=>e.tabIndex>=0&&e.offsetWidth>0&&e.offsetHeight>0&&getComputedStyle(e).visibility!=='hidden').map(e=>{if(!e.dataset.preuveFocus)e.dataset.preuveFocus='nav-'+Math.random().toString(36).slice(2);return e.dataset.preuveFocus}));
  assert(controles.length>=4,'Commandes, liens et recherche sont présents');
  const premier=page.locator(`[data-preuve-focus="${controles[0]}"]`),dernier=page.locator(`[data-preuve-focus="${controles.at(-1)}"]`);
  await premier.focus();await page.keyboard.press('Shift+Tab');check(await dernier.evaluate(e=>e===document.activeElement),'boucle Maj+Tab '+fermeture);
  await dernier.focus();await page.keyboard.press('Tab');check(await premier.evaluate(e=>e===document.activeElement),'boucle Tab '+fermeture);
  const champ=page.locator('#ast-mobile-popup input[type="search"]:visible');await champ.fill('potager');check(await champ.inputValue()==='potager','recherche saisissable');
  const taille=await close.boundingBox();check(taille.width>=24&&taille.height>=24,'fermeture non rétrécie');
  if(fermeture==='bouton')await close.click();
  if(fermeture==='echap')await page.keyboard.press('Escape');
  if(fermeture==='voile')await page.locator('.ast-mobile-popup-overlay').click({position:{x:5,y:100}});
  await popup.waitFor({state:'hidden'});
  check(await trigger.getAttribute('aria-expanded')==='false','commande fermée '+fermeture);
  check(await trigger.evaluate(e=>e===document.activeElement),'retour au déclencheur '+fermeture);
  const focus=await focusVisible(page);check(focus.visible&&!focus.dansPanneau,'focus visible après '+fermeture);etats.push({fermeture,focus,tailleFermeture:{width:taille.width,height:taille.height}});
 }
 // Cas réellement possible avec le registre : passage mobile vers ordinateur, panneau ouvert.
 await trigger.click();await popup.waitFor({state:'visible'});await page.setViewportSize({width:1280,height:900});await page.waitForTimeout(150);
 const apres=await focusVisible(page);check(apres.visible&&!apres.dansPanneau,'focus visible après redimensionnement');
 check(await page.locator('#masthead').evaluate(e=>e===document.activeElement||e.contains(document.activeElement)),'retour logique dans en-tête après redimensionnement');
 check(!await popup.isVisible(),'panneau fermé après passage ordinateur');
 if(process.env.ASTRA_NAV_SANS_LIEN==='1' && await page.locator('#masthead .main-header-menu-toggle:visible').count()===0){
  check(apres.id==='masthead','repli sans logo sur masthead');
  check(await page.locator('#masthead').getAttribute('tabindex')==='-1','tabindex provisoire');
  await page.keyboard.press('Tab');
  check(await page.locator('#masthead').getAttribute('tabindex')===null,'tabindex retiré au blur');
 }
 await page.setViewportSize({width:largeur,height:900});await page.waitForTimeout(100);
 await trigger.click();check(await popup.isVisible(),'une seule ouverture après redimensionnement');
 const first=page.locator('#menu-toggle-close');await first.focus();await page.keyboard.press('Shift+Tab');
 check(await page.locator('#ast-mobile-popup').evaluate(e=>e.contains(document.activeElement)),'piège conservé après redimensionnement');
 await close.click();
 return{etats,redimensionnement:apres};
}
(async()=>{
 const[url,profil,sorties]=process.argv.slice(2);assert(url&&sorties&&['offcanvas','sousmenus','widgets','vide','evitement'].includes(profil),'Usage navigation.cjs URL PROFIL SORTIES');fs.mkdirSync(sorties,{recursive:true});
 const b=await chromium.launch({executablePath:process.env.HOME+'/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome'}),mesures={};let echecs=0;
 try{
  for(const[nom,adresse]of[...(process.env.URL_WP?[['wordpress',process.env.URL_WP]]:[]),['spip',url]]){
   mesures[nom]=[];
   for(const largeur of[1280,768,390]){
    const p=await b.newPage({viewport:{width:largeur,height:900},userAgent:AGENT});const erreurs=[],defauts=[];p.on('pageerror',e=>erreurs.push(e.message));
    if(process.env.ASTRA_NAV_COMMANDE_MASQUEE==='1')await p.addInitScript(()=>document.addEventListener('DOMContentLoaded',()=>{const h=document.querySelector('#masthead'),d=document.createElement('div');d.hidden=true;d.innerHTML='<div class="ast-button-wrap"><button class="menu-toggle" id="preuve-commande-masquee">Masqué</button></div>';h.prepend(d)}));
    if(process.env.ASTRA_NAV_SANS_LIEN==='1')await p.addInitScript(()=>document.addEventListener('DOMContentLoaded',()=>document.querySelectorAll('#masthead .custom-logo-link,#masthead .site-title a').forEach(e=>e.remove())));
    const check=(valeur,message)=>{if(!valeur){defauts.push(message);if(nom==='spip')echecs++}};
    try{
     await p.goto(adresse,{waitUntil:'load'});await p.addStyleTag({content:cssStabilisation('')});
     let resultat;
     if(profil==='offcanvas')resultat=await offcanvas(p,largeur,check);
     else if(profil==='vide'){
      check(await p.locator('#ast-mobile-popup,.main-header-menu-toggle').count()===0,'aucun panneau ni déclencheur');resultat={focus:await focusVisible(p)};
     }else resultat=await require('./lib/navigation-semantique.cjs')[profil](p,largeur,check);
     assert.deepEqual(erreurs,[],nom+' pageerror');mesures[nom].push({largeur,...resultat,defauts});
     await p.screenshot({path:`${sorties}/${nom}-${largeur}.png`,fullPage:true});
    }finally{await p.close()}
   }
  }
  fs.writeFileSync(`${sorties}/mesures.json`,JSON.stringify(mesures,null,2)+'\n');
  for(const[nom,rs]of Object.entries(mesures))console.log(nom,rs.flatMap(r=>r.defauts.map(d=>r.largeur+' '+d)));
  assert.equal(echecs,0,'Défauts de navigation SPIP');console.log(profil+' : parcours SPIP conformes, comportement cible enregistré.');
 }finally{await b.close()}
})().catch(e=>{console.error(e);process.exitCode=1});

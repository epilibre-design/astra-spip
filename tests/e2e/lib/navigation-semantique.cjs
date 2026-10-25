const assert=require('node:assert/strict');
async function sousmenus(page,largeur,check){
 if(largeur<922)await page.locator('#ast-mobile-header .main-header-menu-toggle').click();
 const zone=largeur<922?'#ast-mobile-header':'#ast-desktop-header';
 const commandes=page.locator(zone+' .dropdown-menu-toggle:visible,'+zone+' button.ast-menu-toggle:visible');
 assert(await commandes.count()>0,'Un parent de menu dépliant est présent');
 const mesures=[];
 for(let i=0;i<await commandes.count();i++){
  const commande=commandes.nth(i),parent=commande.locator('xpath=ancestor::li[1]'),sous=parent.locator(':scope > .sub-menu');
  const natif=await commande.evaluate(e=>e.tagName==='BUTTON');
  const cible=natif?parent:sous,classe=natif?'ast-submenu-expanded':'toggled-on';
  check(natif||await commande.getAttribute('role')==='button','rôle commande '+i);
  check(!!((await commande.getAttribute('aria-label'))||(await commande.textContent()).trim()),'nom commande '+i);
  check(await commande.getAttribute('aria-haspopup')==='true','sous-menu annoncé '+i);
  await commande.focus();
  const defilement=await page.evaluate(()=>scrollY);
  const etats=[];
  for(const[touche,attendu]of[['Enter',true],['Space',false]]){
   await cible.evaluate(e=>{e._preuve=[];e._observation=new MutationObserver(rs=>e._preuve.push(...rs.map(r=>r.oldValue)));e._observation.observe(e,{attributes:true,attributeFilter:['class'],attributeOldValue:true})});
   const avant=await cible.evaluate((e,c)=>e.classList.contains(c),classe);
   await commande.press(touche);await page.waitForTimeout(35);
   const etat=await cible.evaluate((e,{avant,classe})=>{e._observation.disconnect();const states=[avant,...e._preuve.slice(1).map(s=>(s||'').split(/\s+/).includes(classe)),e.classList.contains(classe)];return{ouvert:e.classList.contains(classe),bascules:states.slice(1).filter((s,i)=>s!==states[i]).length}},{avant,classe});
   check(etat.ouvert===attendu,'état après '+touche+' commande '+i);
   check(etat.bascules===1,'une seule bascule '+touche+' commande '+i);
   check(await commande.getAttribute('aria-expanded')===String(attendu),'aria synchronisée '+touche+' commande '+i);
   etats.push({touche,...etat});
  }
  check(await page.evaluate(()=>scrollY)===defilement,'Espace sans défilement '+i);
  if(natif){
   for(const attendu of[true,false]){
    await commande.locator('svg').click();
    check(await parent.evaluate(e=>e.classList.contains('ast-submenu-expanded'))===attendu,'clic icône état '+i);
    check(await commande.getAttribute('aria-expanded')===String(attendu),'clic icône aria '+i);
   }
  }
  await commande.press('Enter');await page.waitForTimeout(35);
  const autre=page.locator('#masthead .site-title a:visible').first();
  await autre.focus();await page.waitForTimeout(35);
  check(!await cible.evaluate((e,c)=>e.classList.contains(c),classe),'fermeture au départ du focus '+i);
  check(await commande.getAttribute('aria-expanded')==='false','aria au départ du focus '+i);
  mesures.push({commande:i,etats});
 }
 const ids=await page.locator('[id]').evaluateAll(es=>{const ids=es.map(e=>e.id);return ids.filter((id,i)=>ids.indexOf(id)!==i)});
 check(ids.length===0,'ID uniques');return{commandes:mesures};
}
async function widgets(page,largeur,check){
 const widgets=page.locator('.widget_nav_menu');assert(await widgets.count()>=1,'Widget menu présent');
 const parents=widgets.locator('li.menu-item-has-children');assert(await parents.count()>0,'Widget avec sous-page');
 const mesures=[];
 for(let i=0;i<await parents.count();i++){
  const parent=parents.nth(i),lien=parent.locator(':scope > a'),enfants=parent.locator(':scope > .sub-menu a');
  check(await enfants.first().isVisible(),'enfants widget visibles '+i);
  check(await parent.locator(':scope > a [tabindex],:scope > a [role="button"],:scope > a .dropdown-menu-toggle').count()===0,'flèche décorative '+i);
  check(await parent.locator('[aria-expanded]').count()===0,'aucun faux état fermé '+i);
  await lien.scrollIntoViewIfNeeded();await lien.focus();
  await page.keyboard.press('Tab');
  check(await enfants.evaluateAll(es=>es.includes(document.activeElement)),'enfant accessible par Tab '+i);
  await lien.focus();
  // Bloque seulement la navigation de cette activation pour observer les attributs après keydown.
  await lien.evaluate(e=>e.addEventListener('click',event=>event.preventDefault(),{once:true}));
  await lien.press('Enter');await page.waitForTimeout(35);
  check(await parent.locator('[aria-expanded]').count()===0,'pas de faux état ajouté par le JS amont '+i);
  check(await enfants.first().isVisible(),'liste toujours visible après Entrée '+i);
  mesures.push({enfants:await enfants.allTextContents(),titre:await parent.locator('xpath=ancestor::*[contains(@class,"widget_nav_menu")][1]').locator('.widget-title').allTextContents()});
 }
 // Un clic sur le parent garde sa fonction de lien et navigue réellement.
 const lien=parents.first().locator(':scope > a'),destination=new URL(await lien.getAttribute('href'),page.url()).href;
 await Promise.all([page.waitForURL(destination),lien.click()]);check(page.url()===destination,'lien parent navigable');
 return{widgets:mesures};
}
async function evitement(page,largeur,check){
 const lien=page.locator('a.skip-link');assert.equal(await lien.count(),1);assert.equal(await lien.getAttribute('href'),'#content');
 await lien.focus();check(await lien.isVisible(),'lien évitement visible au focus');
 const rectangle=await lien.boundingBox();check(rectangle.x>=0&&rectangle.y>=0,'lien évitement dans la fenêtre');
 await lien.press('Enter');
 check(await page.locator('#content').count()===1,'cible unique');
 const focus=await page.evaluate(()=>({id:document.activeElement.id,hash:location.hash}));
 check(focus.id==='content'||focus.hash==='#content','accès au contenu');
 return{focus};
}
module.exports={sousmenus,widgets,evitement};

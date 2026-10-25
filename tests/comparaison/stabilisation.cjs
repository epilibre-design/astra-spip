// Normalisation des seules captures de composants ; aucun style produit n'est modifié.
const ANIMATIONS='*,*::before,*::after{transition:none!important;animation:none!important;caret-color:transparent!important}';
function cssStabilisation(selecteur){
 // Ce bouton appartient à la fenêtre et peut chevaucher le pied selon la hauteur du contenu.
 // Son rendu et son action sont vérifiés séparément par tests/e2e/retour_haut.cjs.
 return ANIMATIONS+(selecteur==='#colophon'?'#ast-scroll-top{visibility:hidden!important}':'');
}
module.exports={cssStabilisation};

async function stabiliserComposant(page,selecteur){
 await page.addStyleTag({content:cssStabilisation(selecteur)});
 if(selecteur==='#colophon'){
  await page.locator(selecteur).scrollIntoViewIfNeeded();
  await page.locator(selecteur).evaluate(e=>{
   // L'origine dépend de la hauteur du contenu précédent ; conserver les positions relatives.
   if(getComputedStyle(e).transform!=='none')return;
   const r=e.getBoundingClientRect(),d=window.devicePixelRatio||1;
   const decalage=Math.round(r.y*d)/d-r.y;
   const ancienne=e.style.marginTop;
   e.style.marginTop=`calc(${getComputedStyle(e).marginTop} + ${decalage}px)`;
   const apres=e.getBoundingClientRect();
   if(apres.width!==r.width||apres.height!==r.height)e.style.marginTop=ancienne;
  });
 }
}
module.exports.stabiliserComposant=stabiliserComposant;

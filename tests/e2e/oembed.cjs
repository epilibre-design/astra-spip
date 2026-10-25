// NODE_PATH=… node oembed.cjs URL_ARTICLE PROFIL SORTIES ; URL_WP facultative.
const fs=require('node:fs');
const assert=require('node:assert/strict');
const {chromium}=require('playwright');
const AGENT='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
(async()=>{
 const[url,profil,sorties]=process.argv.slice(2);
 assert(url&&sorties&&['sans-plugin','avec-plugin','erreurs','deja-enveloppe'].includes(profil),'Usage : oembed.cjs URL_ARTICLE PROFIL SORTIES');
 fs.mkdirSync(sorties,{recursive:true});
 const browser=await chromium.launch({executablePath:process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH||process.env.HOME+'/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome'});
 const mesures={profil,sites:{}};
 try{
  for(const[nom,adresse]of [['spip',url],...(process.env.URL_WP?[['wordpress',process.env.URL_WP]]:[])]){
   mesures.sites[nom]=[];
   for(const largeur of[1280,768,390]){
    const page=await browser.newPage({viewport:{width:largeur,height:900},userAgent:AGENT});
    const erreurs=[];page.on('pageerror',e=>erreurs.push(e.message));
    await page.goto(adresse,{waitUntil:'networkidle'});
    const contenu=page.locator('.entry-content');
    await assert.doesNotReject(()=>contenu.waitFor());
    const texte=await contenu.innerText();
    const temoin=texte.includes('Article témoin sans vidéo.');
    if(!temoin)assert.match(texte,/Légende locale (large|classique|erreur|invalide|inconnu)/);
    const lecteur=!temoin&&['avec-plugin','deja-enveloppe'].includes(profil);
    if(lecteur){
     if(nom==='spip'){
      assert.equal(await contenu.locator('.rwd-video-container').count(),1,'Un seul conteneur responsive');
      assert.equal(await contenu.locator('.ast-oembed-container').count(),0,'Aucune enveloppe Astra ajoutée');
      const bouton=contenu.locator('.oe-play-button button');
      if(await bouton.count())await bouton.click();
     }
     await contenu.locator('iframe').waitFor();
     assert.equal(await contenu.locator('iframe').count(),1,'Une seule iframe après lecture');
    }else{
     assert.equal(await contenu.locator('iframe,.rwd-video-container').count(),0);
     if(!temoin){const liens=await contenu.locator('a[href*="localhost:8428/"]').count();assert.equal(liens,1,'URL de repli utilisable');}
    }
    const geometrie=await contenu.evaluate((e,lecteur)=>{
     const rect=e.getBoundingClientRect(),s=getComputedStyle(e);
     const disponible=rect.width-parseFloat(s.paddingLeft)-parseFloat(s.paddingRight);
     const boite=x=>{const r=x.getBoundingClientRect();return {largeur:r.width,hauteur:r.height}};
     const iframe=e.querySelector('iframe');
     const conteneur=e.querySelector('.rwd-video-container,.wp-block-embed__wrapper');
     return {disponible,debordement:document.documentElement.scrollWidth>innerWidth+1,
      iframe:lecteur?boite(iframe):null,conteneur:lecteur?boite(conteneur):null};
    },lecteur);
    assert.equal(geometrie.debordement,false,'Aucun débordement horizontal');
    if(lecteur){
     const ratio=texte.includes('Légende locale classique')?.75:.5625;
     for(const type of['iframe','conteneur']){
      const b=geometrie[type];assert(b.largeur>0&&b.largeur<=geometrie.disponible+1,nom+' largeur bornée');
      assert(Math.abs(b.hauteur-b.largeur*ratio)<=1,nom+' '+type+' ratio : '+JSON.stringify(b));
     }
    }
    assert.deepEqual(erreurs,[],'Aucune exception JavaScript');
    await contenu.screenshot({path:`${sorties}/${nom}-${largeur}.png`});
    mesures.sites[nom].push({largeur,temoin,geometrie,erreurs});await page.close();
   }
  }
  if(mesures.sites.wordpress)for(let i=0;i<3;i++)for(const type of['iframe','conteneur']){
   const a=mesures.sites.spip[i].geometrie[type],b=mesures.sites.wordpress[i].geometrie[type];
   assert(a&&b,'Comparaison réservée aux lecteurs');
   assert(Math.abs(a.largeur-b.largeur)<=1&&Math.abs(a.hauteur-b.hauteur)<=1,'Dimensions équivalentes à WordPress');
  }
 }finally{fs.writeFileSync(sorties+'/mesures.json',JSON.stringify(mesures,null,2)+'\n');await browser.close();}
 console.log('oEmbed '+profil+' : trois largeurs conformes.');
})().catch(e=>{console.error(e);process.exitCode=1});

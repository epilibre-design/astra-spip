// Image locale identique, vrai plugin/cache et dimensions d'Astra 4.13.8.
const{chromium}=require('playwright'),fs=require('fs'),path=require('path'),cp=require('child_process'),assert=require('assert/strict');
const{PNG}=require('pngjs'),pixelmatch=require('pixelmatch');
const[wpUrl,spipUrl,out]=process.argv.slice(2),env=process.env;
const root=path.resolve(__dirname,'../..');fs.mkdirSync(out,{recursive:true});
function spip(code){const[b,...a]=env.SPIP_CLI.split(/\s+/);return cp.execFileSync(b,[...a,'php:eval',code],{cwd:env.SPIP_ROOT,env,encoding:'utf8'}).trim()}
const etat=spip('require "'+root+'/tests/fixtures/gravatar/spip.php";echo base64_encode(serialize(astra_gravatar_preparer_site()));');
{const[b,...a]=env.SPIP_CLI.split(/\s+/);cp.execFileSync(b,[...a,'cache:vider'],{cwd:env.SPIP_ROOT,env,stdio:'pipe'})}
const ids=JSON.parse(spip('$e=unserialize(base64_decode("'+etat+'"));echo json_encode(["article"=>$e["article"],"page"=>$e["page"]]);'));
(async()=>{const b=await chromium.launch(),r={mesures:{},reseau:[],erreurs:[]};
try{
 if(env.ASTRA_GRAVATAR_MODE==='sans-plugin'){
  r.mode='sans-plugin';
  for(const width of[1280,768,390])for(const[type,id]of[['article',ids.article],['page',ids.page]]){
   const p=await b.newPage({viewport:{width,height:900},userAgent:'Mozilla/5.0 Chrome/140.0 Safari/537.36'});await p.goto(spipUrl+'/spip.php?page=article&id_article='+id,{waitUntil:'load'});
   assert.equal(await p.locator('.ast-comment-avatar-wrap img').count(),0);assert.equal(await p.locator('.ast-avatar-initiale').count(),2);assert.deepEqual(await p.locator('.ast-avatar-initiale').allTextContents(),['É','A']);assert.equal(await p.locator('.ast-avatar-gravatar').count(),0);
   r.mesures[type+'-'+width]=await p.locator('.ast-avatar-initiale').evaluateAll(ns=>ns.map(e=>({texte:e.textContent,largeur:e.getBoundingClientRect().width,hauteur:e.getBoundingClientRect().height})));
   await p.locator('.ast-avatar-initiale').first().screenshot({path:path.join(out,type+'-'+width+'.png')});await p.close();
  }
  fs.writeFileSync(path.join(out,'bilan.json'),JSON.stringify(r,null,2)+'\n');console.log('Gravatar absent : repli article/page, trois largeurs OK');return;
 }
 for(const width of[1280,768,390]){
  const images=[];r.mesures[width]={};
  for(const[nom,url]of[['wordpress',wpUrl],['spip',spipUrl+'/spip.php?page=article&id_article='+ids.article]]){
   const p=await b.newPage({viewport:{width,height:900},userAgent:'Mozilla/5.0 Chrome/140.0 Safari/537.36'});
   p.on('request',q=>{if(!q.url().startsWith('data:')&&new URL(q.url()).origin!==new URL(url).origin)r.reseau.push(q.url())});
   await p.goto(url,{waitUntil:'load'});await p.evaluate(()=>document.fonts.ready);
   const sel=nom==='wordpress'&&env.WP_COMMENT?'#comment-'+env.WP_COMMENT+' .ast-comment-avatar-wrap img':'.ast-comment-avatar-wrap img';
   const img=p.locator(sel).first();await img.waitFor();assert.equal(await img.getAttribute('alt'),'');assert(!/example.org|gravatar.com/.test(await img.getAttribute('src')));
   const reponse=await p.request.get(new URL(await img.getAttribute('src'),url).href);const octets=await reponse.body();fs.writeFileSync(path.join(out,nom+'-source.jpg'),octets);
   const v=await img.evaluate(e=>{const c=getComputedStyle(e),box=e.getBoundingClientRect(),wrap=e.closest('.ast-comment-avatar-wrap'),nom=e.closest('.ast-comment-info').querySelector('.fn');return{largeur:box.width,hauteur:box.height,rayon:c.borderRadius,bordure:c.borderWidth,couleurBordure:c.borderColor,fond:c.backgroundColor,rendu:c.imageRendering,fit:c.objectFit,filtre:c.filter,opacite:c.opacity,ombre:c.boxShadow,marge:getComputedStyle(wrap).marginRight,ecart:nom.getBoundingClientRect().left-box.right}});v.source_sha256=require('crypto').createHash('sha256').update(octets).digest('hex');r.mesures[width][nom]=v;fs.writeFileSync(path.join(out,nom+'-'+width+'.html'),await img.evaluate(e=>e.closest('.ast-comment-info').outerHTML));
   // Stabiliser seulement l’origine sous-pixel du recadrage, après les mesures géométriques.
   await img.scrollIntoViewIfNeeded();const cadre=await img.evaluate(e=>{const r=e.getBoundingClientRect();e.style.position='relative';e.style.left=(Math.round(r.x)-r.x)+'px';e.style.top=(Math.round(r.y)-r.y)+'px';return{avant:{x:r.x,y:r.y,width:r.width,height:r.height},apres:JSON.parse(JSON.stringify(e.getBoundingClientRect()))}});
   fs.writeFileSync(path.join(out,nom+'-'+width+'.contexte.json'),JSON.stringify(await img.evaluate(e=>({info:e.closest('.ast-comment-info').getBoundingClientRect().width,primary:document.querySelector('#primary').getBoundingClientRect().width,src:e.src,naturalWidth:e.naturalWidth,srcset:e.srcset})),null,2));
   fs.writeFileSync(path.join(out,nom+'-'+width+'.cadre.json'),JSON.stringify(cadre,null,2));
   const f=path.join(out,nom+'-'+width+'.png');await img.screenshot({path:f});images.push(PNG.sync.read(fs.readFileSync(f)));await p.close();
  }
  const[a,c]=Object.values(r.mesures[width]);for(const k of Object.keys(a)){if(typeof a[k]==='number'){if(Math.abs(a[k]-c[k])>.05)r.erreurs.push(width+'/'+k+': '+a[k]+' != '+c[k])}else if(a[k]!==c[k])r.erreurs.push(width+'/'+k+': '+a[k]+' != '+c[k])}
  if(images[0].width!==images[1].width||images[0].height!==images[1].height)r.erreurs.push(width+': dimensions de capture différentes');else{const part=pixelmatch(images[0].data,images[1].data,null,images[0].width,images[0].height,{threshold:.1})/(images[0].width*images[0].height);r.mesures[width].pixels=part;if(part>.01)r.erreurs.push(width+': image différente '+part)}
 }
 const p=await b.newPage();await p.goto(spipUrl+'/spip.php?page=article&id_article='+ids.page);assert.equal(await p.locator('.ast-comment-avatar-wrap img').count(),2);assert.equal(await p.locator('ol.children .ast-comment-avatar-wrap img').count(),1);await p.close();
 if(r.reseau.length)r.erreurs.push('requêtes externes : '+r.reseau.join(', '));
 fs.writeFileSync(path.join(out,'bilan.json'),JSON.stringify(r,null,2)+'\n');assert.deepEqual(r.erreurs,[]);console.log('Gravatar : dimensions, images et page unique OK');
}finally{spip('require "'+root+'/tests/fixtures/gravatar/spip.php";astra_gravatar_restaurer_site(unserialize(base64_decode("'+etat+'")));');await b.close()}
})().catch(e=>{console.error(e);process.exitCode=1});

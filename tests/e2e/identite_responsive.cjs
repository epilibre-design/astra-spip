// R29 : comparaison indépendante des rendus WP/SPIP et de la définition d'image.
const fs=require('fs'),path=require('path'),cp=require('child_process'),assert=require('assert/strict');
const {chromium}=require('playwright');
const root=path.resolve(__dirname,'../..');
const [urlWp,urlSpip,out]=process.argv.slice(2);
const cases=JSON.parse(fs.readFileSync(path.join(root,'tests/references/responsive/identite.json')));
const env=process.env;
function spip(code){const [bin,...args]=env.SPIP_CLI.split(/\s+/);return cp.execFileSync(bin,[...args,'php:eval',code],{cwd:env.SPIP_ROOT,env,encoding:'utf8'}).trim();}
const source=path.join(root,'tests/references/responsive/sonder-identite.cjs');
fs.mkdirSync(out,{recursive:true});
(async()=>{
 const erreurs=[];const resultats={};
 const avant=spip('echo json_encode($GLOBALS["meta"]["image_process"] ?? null);');
 try{
  spip('ecrire_meta("image_process","gd2");');
  for(const [nom,cas] of Object.entries(cases).filter(([n])=>!env.CAS_R29||env.CAS_R29.split(',').includes(n))){
   cp.execFileSync('wp',['--path='+env.WP,'eval-file',path.join(root,'tests/references/wp',nom+'.php')],{env,stdio:'pipe'});
   cp.execFileSync('bash',[path.join(root,'tests/comparaison/appliquer.sh'),env.SPIP_ROOT,nom],{env,stdio:'pipe'});
   let cheminWp='/',cheminSpip='/';
   if(cas.transparent){cheminWp='/?pagename=notre-jardin';cheminSpip='/'+spip('include_spip("inc/filtres"); echo generer_objet_url((int)sql_getfetsel("id_article","spip_articles","page=".sql_quote("notre_jardin")),"article","","",true);').replace(/^https?:\/\/[^/]+\/?/,'');}
   const fichiers=['wp','spip'].map(n=>path.join(out,nom+'.'+n+'.json'));
   for(const [i,u] of [urlWp+cheminWp,urlSpip+cheminSpip].entries())cp.execFileSync(process.execPath,[source,u,fichiers[i]],{env,stdio:'pipe'});
   const [wp,s]=fichiers.map(f=>JSON.parse(fs.readFileSync(f)));resultats[nom]={wordpress:wp,spip:s};
   for(const largeur of Object.keys(wp))for(const nomEl of ['titre','slogan','logo','entete']){
    if(!wp[largeur][nomEl]||!s[largeur][nomEl]){if(wp[largeur][nomEl]!==s[largeur][nomEl])erreurs.push(`${nom}/${largeur}/${nomEl}: présence différente`);continue;}
    for(const [prop,v] of Object.entries(wp[largeur][nomEl])){
     if(prop.startsWith('natural'))continue;
     const a=s[largeur][nomEl][prop];
     const proche=typeof a==='string'&&/^-?[\d.]+px$/.test(a)&&/^-?[\d.]+px$/.test(v)&&Math.abs(parseFloat(a)-parseFloat(v))<=0.05;
     if(a!==v&&!proche)erreurs.push(`${nom}/${largeur}/${nomEl}/${prop}: ${a} != ${v}`);
    }
   }
   if(nom.endsWith('-logo-large'))assert(s['768'].logo.naturalWidth>=96,'fichier insuffisant sur tablette');
   if(nom.endsWith('-logo-petit'))assert.equal(s['768'].logo.naturalWidth,40,'la source ne doit pas être agrandie');
   const b=await chromium.launch();
   try{
    for(const width of (env.ASTRA_LARGEURS||'1280,768,390').split(',').map(Number))for(const javaScriptEnabled of [true,false]){
     const p=await b.newPage({viewport:{width,height:900},javaScriptEnabled,userAgent:'Mozilla/5.0 (X11; Linux x86_64) Chrome/140.0 Safari/537.36'});await p.goto(urlSpip+cheminSpip,{waitUntil:'load'});
     for(const [o,sel,def]of [['display-site-title-responsive','.site-title',true],['display-site-tagline-responsive','.site-description',false]]){
      const a=width>921?'desktop':width>544?'tablet':'mobile';const vals=cas.styles[o]||{};const voulu=vals[a]===undefined||vals[a]===''?def:!!Number(vals[a]);
      const nodes=await p.locator(sel).all();let visible=false;for(const node of nodes)visible||=await node.isVisible();
      // Une police zéro est invisible géométriquement sans être masquée par display.
      if(!nom.includes('-zero-')&&(javaScriptEnabled||[1280,768,390].includes(width))&&visible!==voulu)erreurs.push(`${nom}/${width}/JS=${javaScriptEnabled}: ${o} ${visible} != ${voulu}`);
     }
     if(cas.transparent&&!await p.locator('body.ast-theme-transparent-header').count())erreurs.push(nom+': transparence non activée');
     await p.close();
    }
   }finally{await b.close();}
   console.log(nom+': mesuré');
  }
 }finally{
  cp.execFileSync('wp',['--path='+env.WP,'eval-file',path.join(root,'tests/references/wp/R0-defaut.php')],{env,stdio:'pipe'});
  cp.execFileSync('bash',[path.join(root,'tests/comparaison/appliquer.sh'),env.SPIP_ROOT,'R0-defaut'],{env,stdio:'pipe'});
  spip(avant==='null'?'effacer_meta("image_process");':'ecrire_meta("image_process",'+avant+');');
 }
 fs.writeFileSync(path.join(out,'bilan.json'),JSON.stringify({cas:resultats,erreurs},null,2)+'\n');
 if(erreurs.length){console.error(erreurs.join('\n'));process.exitCode=1;}else console.log('Identité responsive : OK');
})().catch(e=>{console.error(e);process.exitCode=1;});

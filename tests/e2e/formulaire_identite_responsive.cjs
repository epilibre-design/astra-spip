// CVT réel : quinze valeurs, unités implicites, refus et suppression sans perte.
const {chromium}=require('playwright');
const cp=require('child_process'),assert=require('assert/strict');
const env=process.env,URL=env.URL.replace(/\/$/,'');
function spip(code){const [bin,...args]=env.SPIP_CLI.split(/\s+/);return cp.execFileSync(bin,[...args,'php:eval',code],{cwd:env.SPIP_ROOT,env,encoding:'utf8'}).trim();}
(async()=>{
 const avant=spip('include_spip("inc/config"); echo base64_encode(serialize([lire_config("astra/styles"),$GLOBALS["meta"]["slogan_site"]??""]));');
 const b=await chromium.launch();
 try{
  spip('include_spip("inc/config"); ecrire_config("astra/styles",["button-color"=>"#123456","font-size-site-title"=>["desktop"=>20,"tablet"=>18,"mobile"=>16,"desktop-unit"=>"px"],"display-site-title-responsive"=>["desktop"=>true,"tablet"=>false,"mobile"=>true]]); ecrire_meta("slogan_site","Un jardin vivant");');
  const p=await b.newPage({userAgent:'Mozilla/5.0 (X11; Linux x86_64) Chrome/140.0 Safari/537.36'});
  await p.goto(URL+'/spip.php?page=login&url=ecrire%2F%3Fexec%3Dconfigurer_astra_styles',{waitUntil:'load'});
  await p.fill('#var_login',env.LOGIN||'admin');await p.fill('input[type=password]',env.MDP||'');
  await Promise.all([p.waitForNavigation({waitUntil:'load'}),p.click('.formulaire_login [type=submit],form [type=submit]')]);
  if(!p.url().includes('exec=configurer_astra_styles'))await p.goto(URL+'/ecrire/?exec=configurer_astra_styles');
  const form='.formulaire_configurer_astra_styles';await p.locator(form+' form').waitFor();
  const vals={'ast-header-responsive-logo-width':['40','48','32'],'font-size-site-title':['20','18','16'],'font-size-site-tagline':['11','12','11'],'display-site-title-responsive':['1','0','1'],'display-site-tagline-responsive':['0','1','0']};
  const appareils=['desktop','tablet','mobile'];
  for(const [o,v]of Object.entries(vals))for(let i=0;i<3;i++){
   const sel=`[name="s[${o}][${appareils[i]}]"]`;
   if(o.startsWith('display-'))await p.selectOption(sel,v[i]);else await p.fill(sel,v[i]);
  }
  for(const o of ['font-size-site-title','font-size-site-tagline'])for(const a of appareils)await p.selectOption(`[name="s[${o}][${a}-unit]"]`,a==='tablet'?'':'px');
  await p.click(form+' .boutons [type=submit]');await p.locator(form+' .reponse_formulaire_ok').waitFor();
  await p.goto(URL+'/ecrire/?exec=configurer_astra_styles',{waitUntil:'load'});
  for(const [o,v]of Object.entries(vals))for(let i=0;i<3;i++)assert.equal(await p.inputValue(`[name="s[${o}][${appareils[i]}]"]`),v[i],o+'/'+appareils[i]);
  assert.equal(spip('include_spip("inc/config"); echo json_encode(lire_config("astra/styles/font-size-site-title/tablet"));'),'18','type entier importé conservé');
  assert.equal(spip('include_spip("inc/config"); echo json_encode(lire_config("astra/styles/font-size-site-title/tablet-unit"));'),'null','unité implicite reste absente');
  const sauvegarde=spip('include_spip("inc/config"); echo base64_encode(serialize(lire_config("astra/styles")));');
  const select='select[name="s[font-size-site-title][tablet-unit]"]';
  await p.locator(select).evaluate(s=>{s.appendChild(new Option('pt','pt'));s.value='pt';});
  await p.click(form+' .boutons [type=submit]');await p.locator(form+' .reponse_formulaire_erreur').waitFor();
  assert.equal(spip('include_spip("inc/config"); echo base64_encode(serialize(lire_config("astra/styles")));'),sauvegarde,'aucune écriture après refus');
  await p.selectOption(select,'');await p.fill('input[name="s[font-size-site-title][mobile]"]','');
  await p.click(form+' .boutons [type=submit]');await p.locator(form+' .reponse_formulaire_ok').waitFor();
  assert.equal(spip('include_spip("inc/config"); echo lire_config("astra/styles/button-color");'),'#123456');
  assert.equal(spip('include_spip("inc/config"); echo lire_config("astra/styles/font-size-site-title/tablet");'),'18');
  for(const [width,titre,slogan]of [[1280,true,false],[768,false,true],[390,true,false]]){
   const q=await b.newPage({viewport:{width,height:900},userAgent:'Mozilla/5.0 (X11; Linux x86_64) Chrome/140.0 Safari/537.36'});await q.goto(URL,{waitUntil:'load'});
   for(const [sel,want]of [['.site-title',titre],['.site-description',slogan]]){let visible=false;for(const n of await q.locator(sel).all())visible||=await n.isVisible();assert.equal(visible,want,sel+'/'+width);}
   await q.close();
  }
  console.log('Formulaire identité responsive : OK');
 }finally{
  spip('include_spip("inc/config"); [$s,$slogan]=unserialize(base64_decode("'+avant+'")); if($s===null)effacer_config("astra/styles");else ecrire_config("astra/styles",$s);ecrire_meta("slogan_site",$slogan);');
  await b.close();
 }
})().catch(e=>{console.error(e);process.exitCode=1;});

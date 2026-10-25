const{chromium}=require('playwright');const cp=require('child_process'),assert=require('assert/strict');
const env=process.env,URL=env.URL.replace(/\/$/,'');
function spip(code){const[b,...a]=env.SPIP_CLI.split(/\s+/);return cp.execFileSync(b,[...a,'php:eval',code],{cwd:env.SPIP_ROOT,env,encoding:'utf8'}).trim()}
(async()=>{
 const avant=spip('include_spip("inc/config");echo base64_encode(serialize([lire_config("astra/styles"),lire_config("astra/hfb")]));');const b=await chromium.launch();
 try{
  spip('include_spip("inc/config"); require "'+require('path').resolve(__dirname,'../comparaison/configurations.php')+'";$c=astra_configurations_widgets_alignements()["R30-widgets-alignements"];ecrire_config("astra/hfb",astra_configuration_hfb($c));ecrire_config("astra/styles",["button-color"=>"#123456"]);');
  const p=await b.newPage();await p.goto(URL+'/spip.php?page=login&url=ecrire%2F%3Fexec%3Dconfigurer_astra_styles');await p.fill('#var_login',env.LOGIN||'admin');await p.fill('input[type=password]',env.MDP||'');await Promise.all([p.waitForNavigation(),p.click('.formulaire_login [type=submit],form [type=submit]')]);await p.goto(URL+'/ecrire/?exec=configurer_astra_styles');
  const form='.formulaire_configurer_astra_styles',app=['desktop','tablet','mobile'],vals=['left','center','right'];
  const field=(n,a)=>`select[name="s[footer-widget-alignment-${n}][${a}]"]`;
  for(let n=1;n<=4;n++)for(let i=0;i<3;i++){assert.match(await p.locator(field(n,app[i])+' option[value=""]').textContent(),/Sans réglage/);await p.selectOption(field(n,app[i]),vals[i]);}
  await p.click(form+' .boutons [type=submit]');await p.locator(form+' .reponse_formulaire_ok').waitFor();await p.goto(URL+'/ecrire/?exec=configurer_astra_styles');
  for(let n=1;n<=4;n++)for(let i=0;i<3;i++)assert.equal(await p.inputValue(field(n,app[i])),vals[i]);
  const saved=spip('include_spip("inc/config");echo base64_encode(serialize(lire_config("astra/styles")));');
  for(const bad of ['diagonal','center;}body{display:none']){
   await p.locator(field(1,'mobile')).evaluate((s,v)=>{s.appendChild(new Option(v,v));s.value=v},bad);await p.click(form+' .boutons [type=submit]');await p.locator(form+' .reponse_formulaire_erreur').waitFor();assert.equal(spip('include_spip("inc/config");echo base64_encode(serialize(lire_config("astra/styles")));'),saved);
  }
  await p.selectOption(field(1,'mobile'),'');await p.click(form+' .boutons [type=submit]');await p.locator(form+' .reponse_formulaire_ok').waitFor();
  assert.equal(spip('include_spip("inc/config");echo lire_config("astra/styles/button-color");'),'#123456');
  for(const[width,align]of [[1280,'left'],[768,'center'],[390,'center']]){const q=await b.newPage({viewport:{width,height:900}});await q.goto(URL);assert.equal(await q.locator('.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"].footer-widget-area-inner').evaluate(e=>getComputedStyle(e).textAlign),align);await q.close()}
  console.log('Formulaire alignements widgets : OK');
 }finally{spip('include_spip("inc/config");[$s,$h]=unserialize(base64_decode("'+avant+'"));foreach(["styles"=>$s,"hfb"=>$h]as$k=>$v){if($v===null)effacer_config("astra/".$k);else ecrire_config("astra/".$k,$v);}');await b.close()}
})().catch(e=>{console.error(e);process.exitCode=1});

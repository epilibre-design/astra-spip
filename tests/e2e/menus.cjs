const {chromium}=require('playwright'),assert=require('node:assert/strict'),cp=require('node:child_process');
const cli=process.env.SPIP_CLI.split(/\s+/);
function spip(code){return cp.execFileSync(cli[0],[...cli.slice(1),'php:eval',code],{cwd:process.env.SPIP_ROOT,encoding:'utf8'}).trim()}
(async()=>{
 const avant=spip('include_spip("inc/config"); echo base64_encode(serialize(lire_config("astra/hfb",null)));');
 const ids=JSON.parse(spip(`include_spip("inc/config");
 $m=sql_insertq("spip_menus",["titre"=>"Navigation libre test","identifiant"=>"astra-e2e","css"=>"e2e-menu-css"]);
 sql_insertq("spip_menus_entrees",["id_menu"=>$m,"rang"=>0,"type_entree"=>"accueil","parametres"=>serialize(["titre"=>"Accueil libre"])]);
 $e=sql_insertq("spip_menus_entrees",["id_menu"=>$m,"type_entree"=>"lien","parametres"=>serialize(["titre"=>"Parent libre","url"=>"https://example.test/parent"])]);
 $s=sql_insertq("spip_menus",["id_menus_entree"=>$e]);
 sql_insertq("spip_menus_entrees",["id_menu"=>$s,"type_entree"=>"lien","parametres"=>serialize(["titre"=>"Enfant libre","url"=>"https://example.test/enfant"])]);
 $e2=sql_insertq("spip_menus_entrees",["id_menu"=>$m,"rang"=>2,"type_entree"=>"texte_libre","parametres"=>serialize(["contenu"=>"Sans lien"])]);
 $t=sql_insertq("spip_menus",["id_menus_entree"=>$e2]);
 sql_insertq("spip_menus_entrees",["id_menu"=>$t,"type_entree"=>"lien","parametres"=>serialize(["titre"=>"Enfant sans lien","url"=>"https://example.test/autre"])]);
 $source=["source"=>"menus","id"=>$m];
 ecrire_config("astra/hfb",["menu_mobile"=>"dropdown","pied"=>["primary"=>["primary_1"=>["menu"],"primary_2"=>["widget-1"]]],"elements"=>["entete"=>["menu-1"=>$source],"pied"=>["menu"=>$source,"widget-1"=>["blocs"=>["menu"],"menu"=>$source]]]]);
 include_spip("inc/invalideur");suivre_invalideur("1");echo json_encode([$m,$s,$t]);`));
 const b=await chromium.launch();
 try{
  for(const mode of ['dropdown','off-canvas']) {
  spip(`include_spip("inc/config");ecrire_config("astra/hfb/menu_mobile","${mode}");include_spip("inc/invalideur");suivre_invalideur("1");`);
  for(const js of [false,true])for(const width of [390,922,1280]){
   const p=await b.newPage({javaScriptEnabled:js,viewport:{width,height:900}});
   const erreurs=[];p.on('pageerror',e=>erreurs.push(e.message));
   await p.goto(process.env.URL+'?var_mode=recalcul',{waitUntil:'networkidle'});
   if(!js && width<=922){
    const nav=p.locator('.astra-navigation-sans-js:visible');
    assert(await nav.getByRole('link',{name:'Enfant libre',exact:true}).isVisible());
    assert.equal(await nav.locator('button,[aria-expanded]').count(),0);
    await p.keyboard.press('Tab');
   }else if(js && await p.locator('#ast-mobile-header').isVisible()){
    const toggle=p.locator('#ast-mobile-header .main-header-menu-toggle');
    await toggle.focus();await p.keyboard.press('Enter');
    const nav=p.locator(mode==='dropdown'?'#ast-mobile-header .ast-mobile-header-content':'#ast-mobile-popup');
    const commande=nav.locator('li:has(> a[href="https://example.test/parent"]) > .ast-menu-toggle');
    await commande.focus();await p.keyboard.press('Space');
    assert.equal(await commande.getAttribute('aria-expanded'),'true');
    assert(await nav.getByRole('link',{name:'Enfant libre',exact:true}).isVisible());
    const sans=nav.locator('.astra-menu-sans-lien > button');
    await sans.focus();await p.keyboard.press('Space');
    assert.equal(await sans.getAttribute('aria-expanded'),'true');
    assert(await nav.getByRole('link',{name:'Enfant sans lien',exact:true}).isVisible());
   }else if(js){
    const parent=p.locator('#ast-desktop-header').getByRole('link',{name:'Parent libre',exact:true});
    await parent.hover();
    await p.locator('#ast-desktop-header').getByRole('link',{name:'Enfant libre',exact:true}).waitFor({state:'visible'});
    const sans=p.locator('#ast-desktop-header .astra-menu-sans-lien > button');
    await sans.focus();await p.keyboard.press('Enter');
    assert.equal(await sans.getAttribute('aria-expanded'),'true');
    await p.locator('#ast-desktop-header').getByRole('link',{name:'Enfant sans lien',exact:true}).waitFor({state:'visible'});
    await p.keyboard.press('Escape');assert.equal(await sans.getAttribute('aria-expanded'),'false');
   }
   assert(await p.locator('#footer-site-navigation').getByRole('link',{name:'Parent libre',exact:true}).isVisible());
   assert(await p.locator('#footer-site-navigation').getByRole('link',{name:'Enfant libre',exact:true}).isVisible());
   assert.equal(await p.locator('#footer-site-navigation button').count(),0);
   assert(await p.locator('.widget_nav_menu').getByRole('link',{name:'Enfant libre',exact:true}).isVisible());
   for(const liste of await p.locator('#ast-hf-menu-1,#ast-hf-mobile-menu,#astra-footer-menu,.widget_nav_menu ul.menu,.astra-liens-sans-js').all()) {
    assert(await liste.evaluate(e=>e.classList.contains('e2e-menu-css')),'Classe Menus sur la liste Astra');
   }
   const accueil=p.locator('#astra-footer-menu').getByRole('link',{name:'Accueil libre',exact:true});
   assert.equal(await accueil.getAttribute('aria-current'),'page');
   assert(await accueil.evaluate(e=>e.parentElement.classList.contains('current-menu-item')));
   assert.deepEqual(erreurs,[]);await p.close();
  }
  }
  console.log('Menus : 12 configurations (déroulant et panneau latéral), sous-menu mobile au clavier, survol ordinateur, repli et widgets conformes.');
 }finally{
  await b.close();
  spip(`include_spip("inc/config");$a=unserialize(base64_decode("${avant}"));if($a===null)effacer_config("astra/hfb");else ecrire_config("astra/hfb",$a);sql_delete("spip_menus_entrees","id_menu IN (${ids.join(',')})");sql_delete("spip_menus","id_menu IN (${ids.join(',')})");include_spip("inc/invalideur");suivre_invalideur("1");`);
 }
})().catch(e=>{console.error(e);process.exitCode=1});

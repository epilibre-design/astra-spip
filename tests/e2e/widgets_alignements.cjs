// R30 : chaque alignement est comparé sur l'élément flex lui-même, absences comprises.
const fs=require('fs'),path=require('path'),cp=require('child_process');
const root=path.resolve(__dirname,'../..'),env=process.env;
const [wpUrl,spipUrl,out]=process.argv.slice(2);
const cas=JSON.parse(fs.readFileSync(path.join(root,'tests/references/responsive/widgets.json')));
fs.mkdirSync(out,{recursive:true});const bilan={cas:{},erreurs:[]};
try{
 for(const[nom,c]of Object.entries(cas)){
  cp.execFileSync('wp',['--path='+env.WP,'eval-file',path.join(root,'tests/references/wp',nom+'.php')],{env,stdio:'pipe'});
  cp.execFileSync('bash',[path.join(root,'tests/comparaison/appliquer.sh'),env.SPIP_ROOT,nom],{env,stdio:'pipe'});
  const mesures=[];
  for(const[label,url]of [['wordpress',wpUrl],['spip',spipUrl]]){
   const f=path.join(out,nom+'.'+label+'.json');cp.execFileSync(process.execPath,[path.join(root,'tests/references/responsive/sonder-widgets.cjs'),url,f],{env,stdio:'pipe'});mesures.push(JSON.parse(fs.readFileSync(f)));
  }
  bilan.cas[nom]={wordpress:mesures[0],spip:mesures[1]};
  for(const width of Object.keys(mesures[0])){
   const[a,b]=mesures.map(m=>m[width]);
   for(const k of Object.keys(a))if(JSON.stringify(a[k])!==JSON.stringify(b[k]))bilan.erreurs.push(`${nom}/${width}/${k}: ${JSON.stringify(b[k])} != ${JSON.stringify(a[k])}`);
   if(a.direction!==c.direction||b.direction!==c.direction)bilan.erreurs.push(`${nom}: direction non appliquée`);
  }
  console.log(nom+': mesuré');
 }
}finally{
 fs.rmSync(path.join(env.WP,'wp-content/mu-plugins/astra-reference-rtl.php'),{force:true});
 cp.execFileSync('wp',['--path='+env.WP,'eval-file',path.join(root,'tests/references/wp/R0-defaut.php')],{env,stdio:'pipe'});
 cp.execFileSync('bash',[path.join(root,'tests/comparaison/appliquer.sh'),env.SPIP_ROOT,'R0-defaut'],{env,stdio:'pipe'});
 fs.writeFileSync(path.join(out,'bilan.json'),JSON.stringify(bilan,null,2)+'\n');
}
if(bilan.erreurs.length){console.error(bilan.erreurs.join('\n'));process.exitCode=1;}else console.log('Widgets alignements : OK');

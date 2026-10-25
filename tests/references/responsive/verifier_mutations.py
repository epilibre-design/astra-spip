# Mutations de qualification sur copie temporaire, sans modifier la production.
import subprocess,tarfile,io,tempfile,json,os
from pathlib import Path
root=Path.cwd();d=Path(tempfile.mkdtemp(prefix='astra-mutations-',dir=os.environ.get('TMPDIR')))
blob=subprocess.check_output(['git','archive','HEAD']);tarfile.open(fileobj=io.BytesIO(blob)).extractall(d,filter='data');(d/'vendor').symlink_to(root/'vendor',target_is_directory=True)
# Test du contrat de définition, indépendant du rééchantillonnage GD couvert en intégration.
p=d/'tests/unit/StylesIdentiteResponsiveTest.php';s=p.read_text();i=s.rfind('}');s=s[:i]+'''public function testDefinitionSourceTousAppareils(): void {$this->assertSame(96,astra_identite_largeur_source(['desktop'=>40,'tablet'=>96,'mobile'=>48],40));}'''+s[i:];p.write_text(s)
mods=[
('bool-mobile','inc/astra_identite_responsive.php','(bool) $valeurs[$appareil]','!(bool) $valeurs[$appareil]','StylesIdentiteResponsiveTest'),
('lecture-desktop','inc/astra_identite_responsive.php',"$v = $valeurs[$appareil] ?? '';","$v = $valeurs['tablet'] ?? '';",'StylesIdentiteResponsiveTest'),
('unite-inconnue','inc/astra_styles.php',"['px', 'em', 'rem', 'vw', '%']","['px', 'em', 'rem', 'vw', '%', 'pt']",'StylesIdentiteResponsiveTest'),
('source-bureau','inc/astra_identite_responsive.php','return $maximum;','return $largeur_affichee;','StylesIdentiteResponsiveTest'),
('widget-index','inc/styles/alignements_widgets.php',". $n . '",". ($n + 1) . '",'StylesAlignementsWidgetsTest'),
('widget-placement','inc/styles/alignements_widgets.php','|| !astra_styles_element_place', '|| false && !astra_styles_element_place','StylesAlignementsWidgetsTest'),
('widget-neutralisation','inc/styles/alignements_widgets.php',"?: 'inherit'","?: 'left'",'StylesAlignementsWidgetsTest'),
('widget-mobile-force','inc/styles/alignements_widgets.php',"'text-align' => $v[$a]","'text-align' => $a === 'mobile' ? 'center' : $v[$a]",'StylesAlignementsWidgetsTest'),
('widget-descendant','inc/styles/alignements_widgets.php','].footer-widget-area-inner','] .footer-widget-area-inner','StylesAlignementsWidgetsTest')]
r=[]
for nom,file,a,b,filtre in mods:
 p=d/file;orig=p.read_text()
 if a not in orig:
  r.append({'mutation':nom,'etat':'motif absent'});continue
 p.write_text(orig.replace(a,b,1))
 z=subprocess.run([str(root/'vendor/bin/phpunit'),'--testsuite','unit','--filter',filtre],cwd=d,stdout=subprocess.PIPE,stderr=subprocess.STDOUT,text=True)
 (Path(os.environ['ASTRA_PREUVES'])/'qualification'/('mutation-'+nom+'.log')).write_text(z.stdout)
 p.write_text(orig);r.append({'mutation':nom,'detectee':z.returncode!=0,'code':z.returncode})
z=subprocess.run([str(root/'vendor/bin/phpunit'),'--testsuite','unit'],cwd=d,stdout=subprocess.PIPE,stderr=subprocess.STDOUT,text=True);r.append({'temoin_restaure':z.returncode==0,'code':z.returncode})
(Path(os.environ['ASTRA_PREUVES'])/'qualification/mutations.json').write_text(json.dumps(r,indent=2)+'\n');print(json.dumps(r,indent=2));print(d)

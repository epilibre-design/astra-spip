<?php
// Processus isolé : doubles des seules frontières SPIP et API optionnelle, pas de réseau.
$cas=$argv[1];$appels=[];
function test_plugin_actif($nom){global $cas;return $cas!=='inactif';}
function include_spip($f){global $appels;if($f==='gravatar_fonctions')$appels[]=['include',$f];}
function email_valide($v){return filter_var($v,FILTER_VALIDATE_EMAIL);}
function inserer_attribut($html,$attribut,$valeur){
 $html=preg_replace('/\s+'.preg_quote($attribut,'/').'="[^"]*"/','',$html);
 return preg_replace('/\s*\/?>$/',($valeur===null?'':' '.$attribut.'="'.htmlspecialchars((string)$valeur,ENT_QUOTES).'"').' />',$html);
}
if($cas!=='sans-api'){
 function gravatar($email,$defaut){global$appels,$cas;$appels[]=['gravatar',$email,$defaut];return $cas==='sans-image'?'':'local/cache-gravatar/opaque.jpg';}
 function gravatar_balise_imgx2($src,$size,$alt,$class){global$appels;$appels[]=['image',$src,$size,$alt,$class];return '<img src="'.$src.'" width="100" height="100" style="width:50px;height:auto;" alt="" class="'.$class.'" />';}
}
require dirname(__DIR__,3).'/inc/astra_gravatar.php';
$adresse=['vide'=>'','invalide'=>'<script>@','tableau'=>[]][$cas]??'test@example.org';
$html=astra_commentaire_gravatar($adresse);echo json_encode(['html'=>$html,'appels'=>$appels]);

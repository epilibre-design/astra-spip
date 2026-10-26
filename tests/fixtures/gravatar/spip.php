<?php
/** Préparation du seul site SPIP privé de preuve ; état sérialisé pour restauration. */
function astra_gravatar_preparer_site(): array {
 include_spip('inc/config');require_once __DIR__.'/cache.php';
 $cache=astra_gravatar_cache_preparer();$ids=[];
 $article=(int)sql_insertq('spip_articles',['titre'=>'Preuve des avatars','texte'=>'Texte de la preuve.','id_rubrique'=>1,'statut'=>'publie','accepter_forum'=>'pos','date'=>'2026-10-26 08:00:00']);
 $page=(int)sql_getfetsel('id_article','spip_articles',"page='notre_jardin'");
 $accepter=sql_getfetsel('accepter_forum','spip_articles','id_article='.$page);sql_updateq('spip_articles',['accepter_forum'=>'pos'],'id_article='.$page);
 foreach([$article,$page] as $a){$parent=0;foreach(['Élodie','Alice'] as $nom){$id=(int)sql_insertq('spip_forum',['objet'=>'article','id_objet'=>$a,'statut'=>'publie','auteur'=>$nom,'email_auteur'=>'avatar@example.org','texte'=>'Un commentaire de test.','titre'=>'Commentaire','id_parent'=>$parent,'date_heure'=>'2026-10-26 08:00:00']);sql_updateq('spip_forum',['id_thread'=>$parent?:$id],'id_forum='.$id);$ids[]=$id;$parent=$id;}}
 return ['cache'=>$cache,'article'=>$article,'page'=>$page,'accepter'=>$accepter,'forums'=>$ids];
}
function astra_gravatar_restaurer_site(array $etat):void {
 require_once __DIR__.'/cache.php';foreach($etat['forums'] as $id)sql_delete('spip_forum','id_forum='.(int)$id);sql_delete('spip_articles','id_article='.(int)$etat['article']);sql_updateq('spip_articles',['accepter_forum'=>$etat['accepter']],'id_article='.(int)$etat['page']);astra_gravatar_cache_restaurer($etat['cache']);
}

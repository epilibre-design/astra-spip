<?php
// Pilote du WordPress isolé, jamais du site de référence partagé.
require dirname(__DIR__,2).'/references/wp/commun.php';
ref_etat_initial();
ref_reglages(['single-post-ast-content-layout'=>'narrow-width-container','single-post-sidebar-layout'=>'no-sidebar','narrow-container-max-width'=>750]);
// Métadonnées affichées identiques au fixture SPIP : isoler la géométrie de l’avatar.
update_option('date_format', 'j F Y');
update_option('time_format', 'H\\hi\\m\\i\\n');
copy(__DIR__.'/portrait.jpg',WP_CONTENT_DIR.'/uploads/astra-gravatar-fixture.jpg');
file_put_contents(WPMU_PLUGIN_DIR.'/astra-gravatar-fixture.php', <<<'MU'
<?php
add_filter('get_avatar_data', function ($args) {
 $args['url']=content_url('/uploads/astra-gravatar-fixture.jpg');$args['found_avatar']=true;return$args;
});
MU
);
$id=wp_insert_comment(['comment_post_ID'=>1,'comment_author'=>'Élodie','comment_author_email'=>'avatar@example.org','comment_content'=>'Un commentaire de test.','comment_date'=>'2026-10-26 08:00:00','comment_approved'=>1]);
echo json_encode(['commentaire'=>$id,'page'=>'/?p=1']);

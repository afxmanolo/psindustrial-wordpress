<?php
/** Local integration tests; creates only the authorized slider seed and disposable slide fixture. */
if ( PHP_SAPI !== 'cli' ) { exit; }
$_SERVER += ['SERVER_NAME'=>'localhost','HTTP_HOST'=>'localhost','REQUEST_URI'=>'/'];
require dirname(__DIR__,4).'/wp-load.php';
use PSIndustrial\Core\{Slides,SlidesMigration};
if(wp_get_environment_type()!=='local'||DB_NAME!=='psindustrial_wp_dev'){exit(1);}
wp_set_current_user(get_users(['role'=>'administrator','number'=>1])[0]->ID);
$checks=[];$check=static function($ok,$test)use(&$checks){$checks[]=['test'=>$test,'passed'=>(bool)$ok];};
$check(post_type_exists('psi_slide'),'CPT registered');$type=get_post_type_object('psi_slide');
$check(!$type->publicly_queryable && !$type->has_archive && $type->show_ui,'Admin-only CPT without public routes');
$check($type->cap->edit_posts==='manage_options','Administrator required');
$check(post_type_supports('psi_slide','thumbnail') && post_type_supports('psi_slide','page-attributes'),'Native image and ordering supports');
global $wpdb;$unrelated=$wpdb->get_results("SELECT ID,post_title,post_content,post_status FROM {$wpdb->posts} WHERE post_type IN ('psi_producto','page') ORDER BY ID",ARRAY_A);
$was=(int)get_option('psi_slides_migration_version',0);SlidesMigration::run();
$check((int)get_option('psi_slides_migration_version')===1,'Initial migration completed');
$created=get_option('psi_slides_migration_created');$check(count($created)===4,'Four seed identities preserved');
$slides=Slides::published();$check(count($slides)===4,'Four published slides');
foreach(SlidesMigration::seeds() as $key=>$seed){$id=$created[$key];$post=get_post($id);$check($post->post_title===$seed['title'] && get_post_meta($id,'_psi_slide_eyebrow',true)===$seed['eyebrow'],'Seed copy preserved: '.$key);$check((int)$post->menu_order===$seed['order'],'Seed order preserved: '.$key);$check(hash_file('sha256',get_attached_file(get_post_thumbnail_id($id)))===hash_file('sha256',get_theme_root().'/psindustrial/'.$seed['file']),'Seed image exact bytes: '.$key);}
$count=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");SlidesMigration::run();$check($count===(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}"),'Second migration creates no objects');
$first=reset($created);$original=get_post($first);$oldTitle=$original->post_title;
wp_update_post(['ID'=>$first,'post_title'=>'Edición humana de prueba']);
$force=static fn()=>0;add_filter('pre_option_psi_slides_migration_version',$force);
try{SlidesMigration::run();$check(get_post($first)->post_title==='Edición humana de prueba','Even interrupted migration retry preserves human edit');}finally{remove_filter('pre_option_psi_slides_migration_version',$force);wp_update_post(['ID'=>$first,'post_title'=>$oldTitle]);}
$fixture=wp_insert_post(['post_type'=>'psi_slide','post_status'=>'draft','post_title'=>'Fixture slider QA','menu_order'=>-1]);
$render=static function($rows){ob_start();get_template_part('template-parts/home-slider',null,['slides'=>$rows]);return ob_get_clean();};
try{
 $check(!in_array($fixture,array_column(Slides::published(),'id'),true),'Draft excluded');
 wp_update_post(['ID'=>$fixture,'post_status'=>'publish']);$rows=Slides::published();$check($rows[0]['id']===$fixture,'menu_order controls visual order');
 $_POST=['psi_slide_nonce'=>wp_create_nonce('psi_save_slide'),'psi_slide'=>['eyebrow'=>'<b>Introducción</b>','button_label'=>'Ver detalle','button_url'=>'https://example.org/destino']];Slides::save($fixture);$row=Slides::published()[0];
 $check($row['eyebrow']==='Introducción' && $row['button_label']==='Ver detalle' && $row['button_url']==='https://example.org/destino','Save sanitizes text and persists CTA');
 $html=$render([$row]);$check(str_contains($html,'Fixture slider QA') && str_contains($html,'Ver detalle') && str_contains($html,'https://example.org/destino'),'Visible title/text/button/URL rendered');
 $check(!str_contains($html,'<img'),'Missing image uses existing blue background without broken image');
 set_post_thumbnail($fixture,get_post_thumbnail_id($first));$withImage=Slides::published()[0];
 $check($withImage['image_id']===get_post_thumbnail_id($first) && str_contains($render([$withImage]),'srcset='),'Native featured image selection renders responsive attachment');
 delete_post_thumbnail($fixture);
 $row['button_url']='';$check(!str_contains($render([$row]),'class="home-hero-cta"'),'Empty URL hides CTA');$row['button_url']='https://example.org';$row['button_label']='';$check(!str_contains($render([$row]),'class="home-hero-cta"'),'Empty label hides CTA');
 $check($render([])==='','Zero slides outputs no carousel');
 $empty=static fn($pre,$q)=>$q->get('post_type')==='psi_slide'?[]:$pre;add_filter('posts_pre_query',$empty,10,2);try{$check(Slides::frontend()===[],'After migration zero published slides do not resurrect hardcoded content');}finally{remove_filter('posts_pre_query',$empty,10);}
 $_POST['psi_slide_nonce']='bad';$_POST['psi_slide']['button_label']='SHOULD NOT SAVE';Slides::save($fixture);$check(get_post_meta($fixture,'_psi_slide_button_label',true)==='Ver detalle','Invalid nonce rejected');
 wp_set_current_user(0);$_POST['psi_slide_nonce']=wp_create_nonce('psi_save_slide');Slides::save($fixture);$check(get_post_meta($fixture,'_psi_slide_button_label',true)==='Ver detalle','Unauthorized save rejected');
 $check(Slides::sanitize_url('javascript:alert(1)')==='' && Slides::sanitize_url('//evil.example')==='' && Slides::sanitize_url('/productos/')==='/productos/','URL allowlist rejects executable/protocol-relative values');
}finally{$_POST=[];wp_set_current_user(get_users(['role'=>'administrator','number'=>1])[0]->ID);wp_delete_post($fixture,true);}
$req=new WP_REST_Request('GET','/wp/v2/psi_slide');$req->set_param('context','edit');$check(rest_do_request($req)->get_status()===200,'Administrator REST works');wp_set_current_user(0);$check(rest_do_request($req)->get_status()===401,'Anonymous REST editing denied');
$response=wp_remote_get(home_url('/'));$html=wp_remote_retrieve_body($response);$check(wp_remote_retrieve_response_code($response)===200,'Home HTTP 200');
$check(str_contains($html,'/uploads/') && substr_count($html,'class="home-slide"')===4,'Home renders four Media Library slides');
$dom=new DOMDocument();@$dom->loadHTML($html);$x=new DOMXPath($dom);$check($x->query('//footer//img[contains(@src,"logo-black-big")]')->length===1 && $x->query('//footer//img[contains(@src,"logo-white")]')->length===0,'Footer uses existing institutional color logo');
$check($x->query('//header//img[contains(@src,"logo-black-big")]')->length===1,'Header logo unchanged');
$check(!preg_match('/Fatal error|Warning:|Deprecated:/',$html),'No PHP diagnostics in HTTP');
$check($unrelated===$wpdb->get_results("SELECT ID,post_title,post_content,post_status FROM {$wpdb->posts} WHERE post_type IN ('psi_producto','page') ORDER BY ID",ARRAY_A),'Unrelated products and Pages unchanged');
$failed=array_values(array_filter($checks,static fn($r)=>!$r['passed']));echo wp_json_encode(['passed'=>count($checks)-count($failed),'failed'=>count($failed),'failures'=>$failed],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);exit($failed?1:0);

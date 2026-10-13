<?php
/** Authorized Home images integration; temporary editorial changes restored in finally. */
if ( PHP_SAPI !== 'cli' ) { exit; }
$_SERVER += array('SERVER_NAME'=>'localhost','HTTP_HOST'=>'localhost','REQUEST_URI'=>'/');
require dirname(__DIR__,4).'/wp-load.php';
use PSIndustrial\Core\{HomeCategoryImage as Image,HomeImagesMigration as Migration};
use PSIndustrial\Core\Migration\{Identity,Storage};
use function PSIndustrial\Theme\{home_category_sections,header_solutions_navigation,catalog_navigation};
if (wp_get_environment_type()!=='local'||DB_NAME!=='psindustrial_wp_dev') { exit(1); }
$admin=get_users(array('role'=>'administrator','number'=>1))[0]->ID; wp_set_current_user($admin);
$checks=array(); $check=static function($ok,$label)use(&$checks){$checks[]=array('test'=>$label,'passed'=>(bool)$ok);};
global $wpdb;
$unrelated=$wpdb->get_results("SELECT ID,post_title,post_content,post_status FROM {$wpdb->posts} WHERE post_type IN ('psi_producto','page','psi_slide') ORDER BY ID",ARRAY_A);
$before=array(); $nav=header_solutions_navigation(); $footer=catalog_navigation(); $sidebar=catalog_navigation(true);
foreach(Migration::FILES as $key=>$file) {
 $e=array('entity_key'=>$key,'target_type'=>'psi_categoria'); $id=Identity::find($e); $state=get_term_meta($id,'_psi_import_state',true);
 $e+=array('source_hash'=>$state['source_hash'],'decision_hash'=>$state['decision_hash']);
 $before[$key]=array($e,$id,Identity::snapshot($e,$id),Identity::prediction($e),$state);
}
$report=Migration::run(); $check((int)get_option(Migration::MARKER)===1&&count($report)===7,'Seven roots initialized');
// Replay only this migration's options; inject missing identity without changing any term.
$savedVersion=get_option(Migration::MARKER); $savedReport=get_option(Migration::REPORT);
$missing=static fn($terms,$tax,$args)=>($args['meta_value']??'')==='"category:1"'?array():$terms;
try {
 delete_option(Migration::MARKER); delete_option(Migration::REPORT); add_filter('get_terms',$missing,99,3);
 $partial=Migration::run(); $check(!get_option(Migration::MARKER)&&$partial['category:1']==='missing_or_not_root','Partial failure never marks version complete');
 remove_filter('get_terms',$missing,99);
 $resumed=Migration::run(); $check((int)get_option(Migration::MARKER)===1&&!array_diff($resumed,array('preserved')),'Retry preserves existing human selections without import');
} finally { remove_filter('get_terms',$missing,99); update_option(Migration::MARKER,$savedVersion,false); update_option(Migration::REPORT,$savedReport,false); }
$images=array();
foreach(Migration::FILES as $key=>$file) {
 [$e,$id,$snapshot,$prediction,$state]=$before[$key]; $image=(int)get_term_meta($id,Image::META,true); $images[]=$image;
 $check($image>0&&hash_file('sha256',get_attached_file($image))===hash_file('sha256',get_theme_root().'/psindustrial/assets/images/home/'.$file),'Original bytes preserved '.$key);
 $check(Identity::snapshot($e,$id)===$snapshot&&Identity::prediction($e)===$prediction&&get_term_meta($id,'_psi_import_state',true)===$state,'No identity drift or stored hash change '.$key);
}
$count=wp_count_posts('attachment')->inherit; Migration::run(); $check(wp_count_posts('attachment')->inherit===$count,'Second execution does not duplicate media');
$check(\PSIndustrial\Core\Media::referenced($images[0]),'Home relationship included in existing media deletion protection');
$method=new ReflectionMethod(Migration::class,'image'); $check($method->invoke(null,Migration::FILES['category:1'])===$images[0]&&wp_count_posts('attachment')->inherit===$count,'Hash match reuses existing attachment');
$sections=home_category_sections(); $check(array_column($sections,'legacy_id')===array(1,3,4,5,6,2,7),'Home order unchanged');
foreach($sections as $i=>$s) { $check($s['image_id']===(int)get_term_meta($s['term_id'],Image::META,true)&&$s['reverse']===($i%2===1)&&$s['narrow']===in_array($s['legacy_id'],array(4,6),true)&&$s['pattern']===($i%2===1||$i===6),'Home meta and presentation flags '.$i); }
$check($nav===header_solutions_navigation()&&$footer===catalog_navigation()&&$sidebar===catalog_navigation(true),'Navigation unchanged');
$check(isset(get_registered_meta_keys('term','psi_categoria')[Image::META]),'Native term meta registered');
$fixture=wp_insert_term('Home image QA '.wp_generate_uuid4(),'psi_categoria')['term_id'];
$e=array('target_type'=>'psi_categoria','entity_key'=>'test:home-image:'.$fixture,'source_hash'=>'test-source','decision_hash'=>'test-decision');
try {
 update_term_meta($fixture,'_psi_source_keys',array($e['entity_key'])); update_term_meta($fixture,'_psi_image_id',0);
 $base=Identity::snapshot($e,$fixture); $hash=Storage::hash($base);
 Identity::set($e,$fixture,'_psi_import_state',array('target_hash'=>$hash,'source_hash'=>$e['source_hash'],'decision_hash'=>$e['decision_hash']));
 $_POST=array('psi_home_image_nonce'=>wp_create_nonce('psi_home_image'),'psi_home_image'=>(string)$images[0]); Image::save($fixture);
 $check((int)get_term_meta($fixture,Image::META,true)===$images[0],'Admin selection persists attachment ID');
 $check(Identity::snapshot($e,$fixture)===$base&&Storage::hash(Identity::snapshot($e,$fixture))===$hash&&Identity::prediction($e)==='UNCHANGED','Home image excluded from snapshot/hash; no CONFLICT');
 update_term_meta($fixture,Image::META,$images[1]); $check(Storage::hash(Identity::snapshot($e,$fixture))===$hash,'Changing Home image does not change hash');
 update_term_meta($fixture,'_psi_image_id',$images[0]); $check(Storage::hash(Identity::snapshot($e,$fixture))!==$hash&&Identity::prediction($e)==='CONFLICT','Original _psi_image_id remains identity content');
 update_term_meta($fixture,'_psi_image_id',0); update_term_meta($fixture,'_psi_h1','Content change'); $check(Storage::hash(Identity::snapshot($e,$fixture))!==$hash,'Real content meta remains protected'); delete_term_meta($fixture,'_psi_h1');
 ob_start(); Image::control($fixture); $html=ob_get_clean(); $check(str_contains($html,'Imagen para página de inicio')&&str_contains($html,'data-kind="single"')&&str_contains($html,'value="'.$images[1].'"'),'Native preview control receives persisted ID');
 $_POST['psi_home_image']='0'; Image::save($fixture); $check(metadata_exists('term',$fixture,Image::META)&&(int)get_term_meta($fixture,Image::META,true)===0,'Removal persists explicit zero');
 $pdf=get_posts(array('post_type'=>'attachment','post_mime_type'=>'application/pdf','numberposts'=>1,'fields'=>'ids'))[0];
 update_term_meta($fixture,Image::META,$pdf); $check((int)get_term_meta($fixture,Image::META,true)===0,'Metadata API rejects PDF');
 $req=new WP_REST_Request('POST','/wp/v2/psi_categoria/'.$fixture); $req->set_param('meta',array(Image::META=>$pdf)); $check(rest_do_request($req)->get_status()===400,'REST rejects non-image');
 $req->set_param('meta',array(Image::META=>$images[0])); $check(rest_do_request($req)->get_status()===200&&(int)get_term_meta($fixture,Image::META,true)===$images[0],'REST accepts valid image');
 $_POST['psi_home_image_nonce']='bad'; $_POST['psi_home_image']='0'; Image::save($fixture); $check((int)get_term_meta($fixture,Image::META,true)===$images[0],'Invalid nonce rejected');
 wp_set_current_user(0); $_POST['psi_home_image_nonce']=wp_create_nonce('psi_home_image'); Image::save($fixture); $check((int)get_term_meta($fixture,Image::META,true)===$images[0],'Unauthorized save denied');
} finally { $_POST=array(); wp_set_current_user($admin); wp_delete_term($fixture,'psi_categoria'); }
$id=$sections[0]['term_id']; $original=(int)get_term_meta($id,Image::META,true);
try {
 update_term_meta($id,Image::META,$images[1]); Migration::run(); $check(home_category_sections()[0]['image_id']===$images[1],'Human edit consumed by Home, not overwritten');
 $body=wp_remote_retrieve_body(wp_remote_get(home_url('/'))); $dom=new DOMDocument(); @$dom->loadHTML($body); $x=new DOMXPath($dom); $node=$x->query('//div[contains(@class,"home-family-image")]//img')->item(0); $check($node&&$node->getAttribute('src')===wp_get_attachment_image_url($images[1],'full'),'Changed image rendered in HTTP Home');
 update_term_meta($id,Image::META,0); Migration::run(); $check(home_category_sections()[0]['image_id']===0,'Human removal not recreated');
 $body=wp_remote_retrieve_body(wp_remote_get(home_url('/'))); @$dom->loadHTML($body); $x=new DOMXPath($dom); $check($x->query('//div[contains(@class,"home-family-image")]')->length===6&&!preg_match('/Warning:|Fatal error/',$body),'Missing image omitted safely without fallback');
} finally { update_term_meta($id,Image::META,$original); }
$check($unrelated===$wpdb->get_results("SELECT ID,post_title,post_content,post_status FROM {$wpdb->posts} WHERE post_type IN ('psi_producto','page','psi_slide') ORDER BY ID",ARRAY_A),'Products, Pages and slides unchanged');
$source=file_get_contents(dirname(__DIR__).'/includes/HomeImagesMigration.php');
$check(!preg_match('#C:[/\\\\]|/legacy|/docs|importer-reports#',$source)&&str_contains($source,'Identity::find')&&str_contains($source,'get_theme_root()'),'Portable migration paths and entity resolution');
$fail=array_values(array_filter($checks,static fn($c)=>!$c['passed'])); echo wp_json_encode(array('passed'=>count($checks)-count($fail),'failed'=>count($fail),'failures'=>$fail),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE); exit($fail?1:0);

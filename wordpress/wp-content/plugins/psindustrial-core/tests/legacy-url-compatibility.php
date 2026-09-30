<?php
/** Local read-only URL regression; publication fixtures live only in object cache. */
if (PHP_SAPI !== 'cli') { exit; }
$_SERVER += ['SERVER_NAME'=>'localhost','HTTP_HOST'=>'localhost','REQUEST_URI'=>'/'];
require dirname(__DIR__,4).'/wp-load.php';
use PSIndustrial\Core\LegacyUrls;
use PSIndustrial\Core\Migration\Identity;
if (wp_get_environment_type() !== 'local' || DB_NAME !== 'psindustrial_wp_dev') { exit(1); }
$checks=[]; $check=static function($ok,$name) use (&$checks){$checks[]=['test'=>$name,'passed'=>(bool)$ok];};
$map=require dirname(__DIR__).'/data/legacy-url-map.php';
$destination=new ReflectionMethod(LegacyUrls::class,'destination');
global $wpdb;
$before=$wpdb->get_results("SELECT ID,post_status,post_name FROM {$wpdb->posts} ORDER BY ID",ARRAY_A);
$id=Identity::find(['target_type'=>'psi_producto','entity_key'=>'sql:productos:122']);
$check($id>0,'Historical ID 122 has one reconciled target');
$original=get_post($id); $fixture=clone $original; $fixture->post_status='publish'; wp_cache_set($id,$fixture,'posts');
try {
 $canonical=get_permalink($fixture);
 $check(str_replace(untrailingslashit(home_url()),'',$canonical)==='/producto/puerta-holandesa/','Holandesa canonical exact path excluding installation base');
 foreach(['/122/producto/puerta-holandesa/','/122/producto/irrelevant-slug/','/puerta-holandesa.php','/puerta-holandesa.php?utm_source=test'] as $path){$check(LegacyUrls::resolve($path,true)===$canonical,'Canonical WordPress permalink: '.$path);}
 $check($destination->invoke(null,['wp_type'=>'psi_producto','wp_id'=>$id])===$canonical,'wp_id branch uses canonical permalink');
 $check(LegacyUrls::resolve($canonical,false)===null && LegacyUrls::resolve($canonical,true)===null,'Canonical URL is never redirected again');
 $fixture->post_type='page'; wp_cache_set($id,clone $fixture,'posts');
 $check($destination->invoke(null,['wp_type'=>'psi_producto','wp_id'=>$id])===null,'Wrong post type fails closed');
 $fixture->post_type='psi_producto';
 foreach(['trash','draft','private','pending'] as $status){$fixture->post_status=$status;wp_cache_set($id,clone $fixture,'posts');$check($destination->invoke(null,['wp_type'=>'psi_producto','wp_id'=>$id])===null,'Non-public target rejected: '.$status);}
 $fixture->post_status='publish';wp_cache_set($id,clone $fixture,'posts');
 $invalid=static fn()=> 'javascript:alert(1)';add_filter('post_type_link',$invalid);$check($destination->invoke(null,['wp_type'=>'psi_producto','wp_id'=>$id])===null,'Invalid permalink rejected');remove_filter('post_type_link',$invalid);
} finally {wp_cache_set($id,$original,'posts');}
$check($destination->invoke(null,['wp_type'=>'psi_producto','wp_id'=>PHP_INT_MAX])===null,'Missing post fails closed');
$check(LegacyUrls::resolve('/999999999/producto/nope/',true)===null,'Unknown historical ID fails closed');
$check(LegacyUrls::resolve('/__psi_unknown_legacy__.php?x=1',true)===null,'Unknown PHP with query remains unmapped');
foreach($map as $file=>$rule){
 if(in_array($rule['wp_type'],['psi_categoria','psi_marca'],true)){
  $tid=Identity::find(['target_type'=>$rule['wp_type'],'entity_key'=>$rule['entity_key']]);
  $expected=$tid && PSIndustrial\Core\TermPolicy::is_public($tid)?get_term_link($tid,$rule['wp_type']):null;
  $check(LegacyUrls::resolve('/'.$file,true)===$expected,'Taxonomy API preserved: '.$file);
 }
 if($rule['wp_type']==='psi_producto' && isset($rule['entity_key'])){
  $target=Identity::find(['target_type'=>'psi_producto','entity_key'=>$rule['entity_key']]);$saved=get_post($target);$check($saved && $saved->post_type==='psi_producto','New mapping has exact identity: '.$file);
  if($saved){$published=clone $saved;$published->post_status='publish';wp_cache_set($target,$published,'posts');try{$check(LegacyUrls::resolve('/'.$file,true)===get_permalink($published),'New mapping canonical when public: '.$file);}finally{wp_cache_set($target,$saved,'posts');}}
 }
 if($rule['wp_type']==='psi_producto' && isset($rule['wp_id'])){
  $p=get_post($rule['wp_id']);$expected=$p && $p->post_type==='psi_producto' && $p->post_status==='publish'?get_permalink($p):null;
  $check(LegacyUrls::resolve('/'.$file,true)===$expected,'Existing product mapping: '.$file);
 }
}
foreach(['nosotros','contacto','soluciones','marcas'] as $slug){$p=get_page_by_path($slug);$check(LegacyUrls::resolve('/'.$slug.'.php',true)===($p && $p->post_status==='publish'?get_permalink($p):null),'Institutional page preserved: '.$slug);}
$root=dirname(ABSPATH);$f=fopen($root.'/docs/seo/legacy-root-coverage.csv','r');$head=fgetcsv($f,0,',','"','');$count=0; $audited=[];
while(($row=fgetcsv($f,0,',','"',''))!==false){$r=array_combine($head,$row);$count++;$audited[]=basename($r['legacy_path']);if(in_array($r['classification_before'],['COVERED','MISSING_WITH_DEMONSTRATED_TARGET'],true)){$check(isset($map[basename($r['legacy_path'])]),'Coverage: '.$r['legacy_path']);}}
fclose($f);foreach(glob($root.'/legacy/public/*.php') as $file){$check(in_array(basename($file),$audited,true),'Real root file accounted for: '.basename($file));}$check($count===199,'Audited root corpus accounted for');
$base=home_url('/');$res=wp_remote_get($base.'puerta-seccional-de-acero-thermacore-594-uso-medio.php?utm_source=qa',['redirection'=>0]);
$check(wp_remote_retrieve_response_code($res)===301,'Public product HTTP 301');
$check(wp_remote_retrieve_header($res,'location')===add_query_arg('utm_source','qa',get_permalink(1371)),'HTTP canonical destination with query retained');
$res=wp_remote_get($base.'__psi_unknown_legacy__.php?x=1',['redirection'=>0]);$check(wp_remote_retrieve_response_code($res)===404,'Unknown PHP actual HTTP 404');
$check($before===$wpdb->get_results("SELECT ID,post_status,post_name FROM {$wpdb->posts} ORDER BY ID",ARRAY_A),'No stored post changes');
$failed=array_filter($checks,static fn($c)=>!$c['passed']);echo wp_json_encode(['passed'=>count($checks)-count($failed),'failed'=>count($failed),'failures'=>array_values($failed)],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);exit($failed?1:0);

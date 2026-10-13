<?php
namespace PSIndustrial\Core;
use PSIndustrial\Core\Migration\Identity;
defined('ABSPATH') || exit;

/** Independent initialization; never updates imported term content or its identity state. */
final class HomeImagesMigration {
 public const VERSION=1;
 public const MARKER='psi_home_images_migration_version';
 public const REPORT='psi_home_images_migration_report';
 public const FILES=array(
  'category:1'=>'industrial-home-670.webp',
  'category:3'=>'equipos-accesorios-anden-carga-670.webp',
  'category:4'=>'salida-emergencia-150.webp',
  'category:5'=>'PuertasIncendios-960.webp',
  'category:6'=>'puerta-metalica-hospital-196.webp',
  'category:2'=>'home-comercial-600.webp',
  'category:7'=>'residencial-2-600.webp',
 );
 public static function boot(): void {
  add_action('admin_init',array(self::class,'run'),30);
  add_action('admin_notices',static function() {
   if(current_user_can('manage_options') && get_option(self::REPORT) && (int)get_option(self::MARKER,0)<self::VERSION) {
    echo '<div class="notice notice-warning"><p>'.esc_html__('No se completó la preparación de las imágenes de Inicio. Se reintentará al abrir administración; revise los términos o archivos pendientes.','psindustrial-core').'</p></div>';
   }
  });
 }
 public static function run(): array {
  $report=(array)get_option(self::REPORT,array());
  if(!current_user_can('manage_options')||(int)get_option(self::MARKER,0)>=self::VERSION) { return $report; }
  $lock='psi_home_images_migration_lock';
  if((int)get_option($lock,0) && time()-(int)get_option($lock)>300) { delete_option($lock); }
  if(!add_option($lock,time(),'',false)) { return $report; }
  try {
   foreach(self::FILES as $key=>$file) {
    // A completed entry also preserves later human deletion during partial retries.
    if(in_array($report[$key]??'',array('applied','preserved'),true)) { continue; }
    try {
     $id=Identity::find(array('target_type'=>'psi_categoria','entity_key'=>$key));
     $term=$id?get_term($id,'psi_categoria'):null;
     if(!$term||is_wp_error($term)||(int)$term->parent!==0) { $report[$key]='missing_or_not_root'; }
     elseif(metadata_exists('term',$id,HomeCategoryImage::META)) { $report[$key]='preserved'; }
     else {
      $image=self::image($file);
      $ok=update_term_meta($id,HomeCategoryImage::META,$image);
      $report[$key]=!is_wp_error($ok)&&(int)get_term_meta($id,HomeCategoryImage::META,true)===$image?'applied':'metadata_error';
     }
    } catch(\Throwable $error) { $report[$key]='error:'.$error->getMessage(); }
    update_option(self::REPORT,$report,false);
   }
   if(count(array_intersect_key($report,self::FILES))===7 && !array_diff(array_intersect_key($report,self::FILES),array('applied','preserved'))) { update_option(self::MARKER,self::VERSION,false); }
  } finally { delete_option($lock); }
  return $report;
 }
 private static function image(string $file): int {
  $source=get_theme_root().'/psindustrial/assets/images/home/'.$file;
  if(!Media::file_valid($source,'image/webp')) { throw new \RuntimeException('invalid_source_image'); }
  $hash=hash_file('sha256',$source);
  foreach(get_posts(array('post_type'=>'attachment','post_status'=>'inherit','post_mime_type'=>'image/webp','numberposts'=>-1,'fields'=>'ids')) as $id) {
   $path=get_attached_file($id);
   if($path&&Media::valid($id,'image')&&hash_file('sha256',$path)===$hash) { return (int)$id; }
  }
  require_once ABSPATH.'wp-admin/includes/file.php'; require_once ABSPATH.'wp-admin/includes/media.php'; require_once ABSPATH.'wp-admin/includes/image.php';
  $temp=wp_tempnam($file);
  if(!$temp||!copy($source,$temp)) { if($temp) { wp_delete_file($temp); } throw new \RuntimeException('image_copy_failed'); }
  try { $id=media_handle_sideload(array('name'=>$file,'tmp_name'=>$temp),0); }
  finally { if(is_file($temp)) { wp_delete_file($temp); } }
  if(is_wp_error($id)||!Media::valid((int)$id,'image')||hash_file('sha256',get_attached_file($id))!==$hash) { throw new \RuntimeException('image_import_failed'); }
  return (int)$id;
 }
}

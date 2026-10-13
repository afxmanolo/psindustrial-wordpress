<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/** Home-only editorial image. The category's imported _psi_image_id stays untouched. */
final class HomeCategoryImage {
 public const META = '_psi_category_home_image_id';
 public static function boot(): void {
  add_action( 'init', static function() {
   register_term_meta( 'psi_categoria', self::META, array(
    'type'=>'integer', 'single'=>true, 'default'=>0,
    'show_in_rest'=>array('schema'=>array('type'=>'integer','minimum'=>0)),
    'auth_callback'=>static fn()=>current_user_can(get_taxonomy('psi_categoria')->cap->edit_terms),
   ) );
  } );
  add_action( 'psi_categoria_add_form_fields', static function() { echo '<div class="form-field">'; self::control(0); echo '</div>'; } );
  add_action( 'psi_categoria_edit_form_fields', static function($term) { echo '<tr class="form-field"><th scope="row">'.esc_html__('Imagen para página de inicio','psindustrial-core').'</th><td>'; self::control($term->term_id); echo '</td></tr>'; } );
  add_action( 'created_psi_categoria', array(self::class,'save') );
  add_action( 'edited_psi_categoria', array(self::class,'save') );
  foreach(array('add_term_metadata','update_term_metadata') as $hook) { add_filter($hook,static fn($check,$id,$key,$value)=>self::META===$key&&!self::valid($value)?false:$check,10,4); }
  add_filter('rest_request_before_callbacks',static function($result,$handler,$request) {
   if(null!==$result || !in_array($request->get_method(),array('POST','PUT','PATCH'),true) || !preg_match('#^/wp/v2/psi_categoria(?:/[0-9]+)?$#',$request->get_route())) { return $result; }
   $meta=(array)$request->get_param('meta');
   return array_key_exists(self::META,$meta)&&!self::valid($meta[self::META]) ? new \WP_Error('psi_home_image',__('Seleccione una imagen válida para Inicio.','psindustrial-core'),array('status'=>400)) : $result;
  },10,3);
 }
 public static function valid(mixed $value): bool {
  return (is_int($value)||(is_string($value)&&ctype_digit($value))) && (int)$value>=0 && (0===(int)$value||Media::valid((int)$value,'image'));
 }
 public static function control(int $id): void {
  wp_nonce_field('psi_home_image','psi_home_image_nonce');
  echo '<p>'.esc_html__('Imagen para página de inicio','psindustrial-core').'</p>';
  ProductEditor::media_control('psi_home_image',$id?(int)get_term_meta($id,self::META,true):0,'single');
  echo '<p class="description">'.esc_html__('Se muestra sólo en las categorías principales de Inicio. Quitarla no elimina el archivo ni modifica la imagen general de la categoría.','psindustrial-core').'</p>';
 }
 public static function save(int $id): void {
  $term=get_term($id,'psi_categoria'); $nonce=$_POST['psi_home_image_nonce']??null;
  if(!$term||is_wp_error($term)||!current_user_can(get_taxonomy('psi_categoria')->cap->edit_terms)||!is_string($nonce)||!wp_verify_nonce($nonce,'psi_home_image')||!isset($_POST['psi_home_image'])) { return; }
  $value=wp_unslash($_POST['psi_home_image']);
  if(!self::valid($value)) { ProductEditor::error(__('Seleccione una imagen válida para Inicio.','psindustrial-core')); return; }
  // Explicit zero is an editorial removal, including before initialization.
  update_term_meta($id,self::META,(int)$value);
 }
}

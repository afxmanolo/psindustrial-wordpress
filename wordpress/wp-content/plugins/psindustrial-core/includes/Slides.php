<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/** Native, administrator-managed Home slides; no public single/archive routes. */
final class Slides {
 public static function boot(): void {
  add_action( 'init', array( self::class, 'register' ) );
  add_action( 'add_meta_boxes_psi_slide', static function() { add_meta_box( 'psi-slide-copy', __( 'Texto y botón', 'psindustrial-core' ), array( self::class, 'fields' ), 'psi_slide' ); } );
  add_action( 'save_post_psi_slide', array( self::class, 'save' ) );
  add_filter( 'enter_title_here', static fn( $label, $post ) => $post->post_type === 'psi_slide' ? __( 'Texto principal del slide', 'psindustrial-core' ) : $label, 10, 2 );
 }
 public static function register(): void {
  $caps = array_fill_keys( array( 'edit_post','read_post','delete_post','edit_posts','edit_others_posts','publish_posts','read_private_posts','delete_posts','delete_private_posts','delete_published_posts','delete_others_posts','edit_private_posts','edit_published_posts','create_posts','read' ), 'manage_options' );
  register_post_type( 'psi_slide', array(
   'labels' => array( 'name' => __( 'Slides de Inicio', 'psindustrial-core' ), 'singular_name' => __( 'Slide', 'psindustrial-core' ), 'add_new_item' => __( 'Añadir slide', 'psindustrial-core' ), 'edit_item' => __( 'Editar slide', 'psindustrial-core' ), 'all_items' => __( 'Todos los slides', 'psindustrial-core' ), 'featured_image' => __( 'Imagen del slide', 'psindustrial-core' ), 'set_featured_image' => __( 'Elegir imagen', 'psindustrial-core' ) ),
   'public' => false, 'show_ui' => true, 'show_in_rest' => true, 'publicly_queryable' => false, 'exclude_from_search' => true,
   'rewrite' => false, 'has_archive' => false, 'query_var' => false, 'menu_icon' => 'dashicons-images-alt2',
   'supports' => array( 'title','thumbnail','page-attributes','revisions','custom-fields' ), 'map_meta_cap' => false, 'capabilities' => $caps,
  ) );
  foreach ( array( '_psi_slide_eyebrow', '_psi_slide_button_label', '_psi_slide_button_url' ) as $key ) {
   register_post_meta( 'psi_slide', $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'revisions_enabled' => true,
    'sanitize_callback' => $key === '_psi_slide_button_url' ? array( self::class, 'sanitize_url' ) : 'sanitize_text_field',
    'auth_callback' => static fn() => current_user_can( 'manage_options' ),
   ) );
  }
 }
 public static function sanitize_url( mixed $value ): string {
  if ( ! is_string( $value ) ) { return ''; }
  $value = trim( $value );
  if ( str_starts_with( $value, '/' ) && ! str_starts_with( $value, '//' ) && ! str_contains( $value, '\\' ) ) { return esc_url_raw( $value ); }
  return in_array( strtolower( (string) wp_parse_url( $value, PHP_URL_SCHEME ) ), array( 'http','https' ), true ) && wp_parse_url( $value, PHP_URL_HOST ) ? esc_url_raw( $value, array( 'http','https' ) ) : '';
 }
 public static function fields( \WP_Post $post ): void {
  wp_nonce_field( 'psi_save_slide', 'psi_slide_nonce' );
  foreach ( array( 'eyebrow' => __( 'Texto introductorio', 'psindustrial-core' ), 'button_label' => __( 'Texto del botón', 'psindustrial-core' ), 'button_url' => __( 'Enlace del botón', 'psindustrial-core' ) ) as $field => $label ) {
   echo '<p><label for="psi-slide-' . esc_attr( $field ) . '">' . esc_html( $label ) . '</label><input class="widefat" id="psi-slide-' . esc_attr( $field ) . '" name="psi_slide[' . esc_attr( $field ) . ']" type="text" value="' . esc_attr( get_post_meta( $post->ID, '_psi_slide_' . $field, true ) ) . '"></p>';
  }
  echo '<p>' . esc_html__( 'El enlace puede ser https://… o una ruta desde el inicio del sitio, por ejemplo /productos/. Sin texto o enlace no se muestra el botón. Use Imagen del slide y Atributos → Orden; los números menores aparecen primero.', 'psindustrial-core' ) . '</p>';
 }
 public static function save( int $id ): void {
  if ( wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) || ! current_user_can( 'edit_post', $id ) ) { return; }
  $nonce = $_POST['psi_slide_nonce'] ?? null;
  if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'psi_save_slide' ) || ! is_array( $_POST['psi_slide'] ?? null ) ) { return; }
  foreach ( array( 'eyebrow','button_label','button_url' ) as $field ) {
   if ( isset( $_POST['psi_slide'][ $field ] ) && is_string( $_POST['psi_slide'][ $field ] ) ) { update_post_meta( $id, '_psi_slide_' . $field, wp_unslash( $_POST['psi_slide'][ $field ] ) ); }
  }
 }
 public static function published(): array {
  $result = array();
  foreach ( get_posts( array( 'post_type' => 'psi_slide', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) ) ) as $post ) {
   $url = self::sanitize_url( get_post_meta( $post->ID, '_psi_slide_button_url', true ) );
   $result[] = array( 'id' => $post->ID, 'title' => $post->post_title, 'eyebrow' => get_post_meta( $post->ID, '_psi_slide_eyebrow', true ), 'image_id' => get_post_thumbnail_id( $post ), 'button_label' => get_post_meta( $post->ID, '_psi_slide_button_label', true ), 'button_url' => str_starts_with( $url, '/' ) ? home_url( $url ) : $url );
  }
  return $result;
 }
 /** Only before initialization, never resurrect content an administrator has removed/unpublished. */
 public static function frontend(): array {
  $slides = self::published();
  if ( $slides || (int) get_option( 'psi_slides_migration_version', 0 ) >= SlidesMigration::VERSION || get_posts( array( 'post_type'=>'psi_slide', 'post_status'=>array('publish','draft','pending','private','future','trash'), 'numberposts'=>1 ) ) ) { return $slides; }
  return array_values( array_map( static fn( $seed ) => array_merge( $seed, array( 'image_id'=>0, 'fallback_image'=>$seed['image_key'], 'button_url'=>get_post_type_archive_link( 'psi_producto' ) ?: '' ) ), SlidesMigration::seeds() ) );
 }
}

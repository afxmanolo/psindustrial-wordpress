<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/** One-time conversion of existing theme hero data, unrelated to the legacy importer. */
final class SlidesMigration {
 public const VERSION = 1;
 public static function boot(): void {
  add_action( 'admin_init', array( self::class, 'run' ), 25 );
  add_action( 'admin_notices', static function() {
   if ( current_user_can( 'manage_options' ) && get_option( 'psi_slides_migration_error' ) ) { echo '<div class="notice notice-error"><p>' . esc_html__( 'No se completó la preparación de los slides. Se reintentará al abrir administración. Detalle:', 'psindustrial-core' ) . ' ' . esc_html( get_option( 'psi_slides_migration_error' ) ) . '</p></div>'; }
  } );
 }
 public static function seeds(): array { return require dirname( PSINDUSTRIAL_CORE_FILE ) . '/data/home-slide-seed.php'; }
 public static function run(): void {
  if ( ! current_user_can( 'manage_options' ) || (int) get_option( 'psi_slides_migration_version', 0 ) >= self::VERSION ) { return; }
  $lock = (int) get_option( 'psi_slides_migration_lock', 0 );
  if ( $lock && time() - $lock > 300 ) { delete_option( 'psi_slides_migration_lock' ); }
  if ( ! add_option( 'psi_slides_migration_lock', time(), '', false ) ) { return; }
  try {
   $done = (array) get_option( 'psi_slides_migration_created', array() );
   foreach ( self::seeds() as $key => $seed ) {
    // Completion journal is also a tombstone: deleting an already-created slide never recreates it.
    if ( isset( $done[ $key ] ) ) { continue; }
    $existing = get_posts( array( 'post_type'=>'psi_slide', 'post_status'=>array('publish','draft','pending','private','future','trash'), 'numberposts'=>1, 'meta_key'=>'_psi_slide_seed', 'meta_value'=>$key ) );
    if ( $existing ) { $done[ $key ] = $existing[0]->ID; update_option( 'psi_slides_migration_created', $done, false ); continue; }
    $image = self::image( $seed['file'] );
    $archive = get_post_type_archive_link( 'psi_producto' );
    $url = $archive ? substr( $archive, strlen( untrailingslashit( home_url() ) ) ) : '';
    $id = wp_insert_post( array( 'post_type'=>'psi_slide', 'post_status'=>'publish', 'post_title'=>$seed['title'], 'menu_order'=>$seed['order'], 'meta_input'=>array( '_psi_slide_seed'=>$key, '_thumbnail_id'=>$image, '_psi_slide_eyebrow'=>$seed['eyebrow'], '_psi_slide_button_label'=>$seed['button_label'], '_psi_slide_button_url'=>$url ) ), true );
    if ( is_wp_error( $id ) || ! $id ) { throw new \RuntimeException( 'slide_insert_failed' ); }
    $done[ $key ] = $id; update_option( 'psi_slides_migration_created', $done, false );
   }
   update_option( 'psi_slides_migration_version', self::VERSION, false ); delete_option( 'psi_slides_migration_error' );
  } catch ( \Throwable $error ) { update_option( 'psi_slides_migration_error', $error->getMessage(), false ); }
  finally { delete_option( 'psi_slides_migration_lock' ); }
 }
 private static function image( string $file ): int {
  $source = get_theme_root() . '/psindustrial/' . $file;
  if ( ! is_file( $source ) || ! Media::file_valid( $source, 'image/webp' ) ) { throw new \RuntimeException( 'slide_source_image_missing_or_invalid' ); }
  $hash = hash_file( 'sha256', $source );
  foreach ( get_posts( array( 'post_type'=>'attachment', 'post_status'=>'inherit', 'post_mime_type'=>'image/webp', 'numberposts'=>-1, 'fields'=>'ids' ) ) as $id ) {
   $path = get_attached_file( $id );
   if ( $path && is_file( $path ) && hash_file( 'sha256', $path ) === $hash ) { return $id; }
  }
  require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
  $temp = wp_tempnam( basename( $source ) );
  if ( ! $temp || ! copy( $source, $temp ) ) { if ( $temp ) { wp_delete_file( $temp ); } throw new \RuntimeException( 'slide_image_copy_failed' ); }
  try { $id = media_handle_sideload( array( 'name'=>basename($source), 'tmp_name'=>$temp ), 0 ); }
  finally { if ( is_file( $temp ) ) { wp_delete_file( $temp ); } }
  if ( is_wp_error( $id ) ) { throw new \RuntimeException( 'slide_image_upload_failed' ); }
  return $id;
 }
}

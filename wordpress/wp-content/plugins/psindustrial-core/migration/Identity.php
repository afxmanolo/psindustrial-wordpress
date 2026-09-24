<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

final class Identity {
 public static function boot(): void {
  add_action( 'init', static function() {
   foreach ( array( '_psi_import_identity', '_psi_import_state', '_psi_import_origin' ) as $key ) {
    $args = array( 'type' => 'object', 'single' => true, 'show_in_rest' => false, 'auth_callback' => static fn() => current_user_can( 'psi_manage_migration' ) );
    foreach ( array( 'psi_producto','page','attachment' ) as $type ) { register_post_meta( $type, $key, $args ); }
    foreach ( array( 'psi_categoria','psi_marca' ) as $tax ) { register_term_meta( $tax, $key, $args ); }
   }
  } );
  foreach ( array( 'post','term' ) as $type ) { foreach ( array( 'add','update','delete' ) as $op ) {
   add_filter( $op . '_' . $type . '_metadata', static function( $check, $id, $key ) { return str_starts_with( $key, '_psi_import_' ) && ! current_user_can( 'psi_manage_migration' ) ? false : $check; }, 10, 3 );
  } }
 }
 /**
  * Editorial/presentational metadata: legitimately changed by a human after import, on the
  * project's own terms, never by re-running the importer -- must never make
  * prediction() report CONFLICT for an object nothing has actually mutated the CONTENT of.
  * Verified empirically (2026-09-23, docs/implementation/full-local-import/ note on
  * BrandsMigration), never assumed: both _psi_public_state and _psi_brand_home_order had
  * to be excluded together before a live snapshot matched its recorded target_hash again --
  * neither alone was sufficient, so both are named here, explicitly.
  *
  * _psi_category_menu_order (added alongside CategoriesMigration) is the same kind of value
  * for psi_categoria that _psi_brand_home_order is for psi_marca -- a presentation-order
  * integer, never written by the importer, never part of any target_hash recorded before this
  * key existed, so excluding it here changes nothing for any past hash; it only has to be
  * listed before CategoriesMigration ever writes it, which it is.
  *
  * _psi_public_state applies to BOTH taxonomies already (TermPolicy gates psi_categoria and
  * psi_marca identically), so CategoriesMigration reuses this same entry -- no separate key
  * needed for categories.
  *
  * Deliberately narrow and explicit, never a blanket "_psi_* is presentational" rule: every
  * OTHER _psi_* key stays part of identity, on purpose. In particular _psi_logo_id is never
  * added here -- which image actually represents a term is CONTENT, not presentation
  * (exactly like a product's _psi_datasheets/gallery), so a human changing it must keep
  * producing CONFLICT, the same protection every other content field already gets.
  */
 private const EDITORIAL_META_KEYS = array( '_psi_public_state', '_psi_brand_home_order', '_psi_category_menu_order' );
 public static function term( array $e ): bool { return in_array( $e['target_type'], array( 'psi_categoria','psi_marca' ), true ); }
 public static function get( array $e, int $id, string $key ): mixed { return self::term( $e ) ? get_term_meta( $id, $key, true ) : get_post_meta( $id, $key, true ); }
 public static function set( array $e, int $id, string $key, mixed $value ): void {
  $ok = self::term( $e ) ? update_term_meta( $id, $key, $value ) : update_post_meta( $id, $key, $value );
  if ( is_wp_error( $ok ) || ( false === $ok && self::get( $e, $id, $key ) !== $value ) ) { throw new \RuntimeException( 'METADATA_WRITE_REJECTED:' . $key ); }
 }
 public static function token( array $e ): string { return substr( Storage::hash( $e['entity_key'] ), 0, 32 ); }
 public static function ledger( array $e ): string { return 'identity-' . self::token( $e ) . '.json'; }
 public static function find( array $e ): int {
  // Serialized array matching includes string delimiters, not a substring of another source key.
  $query = array( 'meta_key' => '_psi_source_keys', 'meta_value' => '"' . $e['entity_key'] . '"', 'meta_compare' => 'LIKE' );
  if ( self::term( $e ) ) { $ids = get_terms( $query + array( 'taxonomy' => $e['target_type'], 'hide_empty' => false, 'psi_include_review' => true, 'fields' => 'ids' ) ); }
  else { $ids = get_posts( $query + array( 'post_type' => $e['target_type'], 'post_status' => array( 'draft','pending','publish','private','future','inherit','trash' ), 'numberposts' => 3, 'fields' => 'ids' ) ); }
  if ( is_wp_error( $ids ) || count( $ids ) > 1 ) { throw new \RuntimeException( 'AMBIGUOUS_IDENTITY' ); }
  return $ids ? (int) $ids[0] : 0;
 }
 public static function snapshot( array $e, int $id ): array {
  if ( self::term( $e ) ) {
   $o = get_term( $id, $e['target_type'] ); if ( ! $o || is_wp_error( $o ) ) { throw new \RuntimeException( 'TARGET_MISSING' ); }
   $value = array( 'name' => $o->name, 'slug' => $o->slug, 'description' => $o->description, 'parent' => (int) $o->parent ); $meta = get_term_meta( $id );
  } else {
   $o = get_post( $id ); if ( ! $o ) { throw new \RuntimeException( 'TARGET_MISSING' ); }
   $value = array_intersect_key( $o->to_array(), array_flip( array( 'post_title','post_content','post_excerpt','post_status','post_name','post_parent','menu_order','post_mime_type' ) ) );
   $meta = get_post_meta( $id );
   if ( 'psi_producto' === $e['target_type'] ) { foreach ( array( 'psi_categoria','psi_marca' ) as $tax ) { $ids = wp_get_object_terms( $id, $tax, array( 'fields' => 'ids' ) ); sort( $ids ); $value[ $tax ] = $ids; } }
   if ( 'attachment' === $e['target_type'] ) { $file = get_attached_file( $id ); $value['file_hash'] = $file && is_file( $file ) ? hash_file( 'sha256', $file ) : 'MISSING'; }
  }
  $value['meta'] = array();
  foreach ( $meta as $key => $values ) { if ( ! str_starts_with( $key, '_psi_import_' ) && ! in_array( $key, self::EDITORIAL_META_KEYS, true ) && ( str_starts_with( $key, '_psi_' ) || in_array( $key, array( '_thumbnail_id','_wp_attachment_image_alt','_wp_attached_file' ), true ) ) ) { $value['meta'][ $key ] = array_map( 'maybe_unserialize', $values ); } }
  ksort( $value['meta'] ); return $value;
 }
 public static function prediction( array $e ): string {
  $id = self::find( $e ); $ledger = Storage::read( self::ledger( $e ) );
  if ( ! $id ) {
   if ( $ledger ) { return 'CONFLICT'; }
   $slug = $e['data']['slug'] ?? '';
   if ( $slug ) {
    $collision = self::term( $e ) ? get_term_by( 'slug', $slug, $e['target_type'] ) : get_page_by_path( $slug, OBJECT, $e['target_type'] );
    if ( $collision ) { return 'CONFLICT'; }
   }
   return 'CREATE';
  }
  $state = self::get( $e, $id, '_psi_import_state' );
  if ( ! is_array( $state ) || empty( $state['target_hash'] ) || ! hash_equals( $state['target_hash'], Storage::hash( self::snapshot( $e, $id ) ) ) ) { return 'CONFLICT'; }
  return ( $state['source_hash'] === $e['source_hash'] && $state['decision_hash'] === $e['decision_hash'] ) ? 'UNCHANGED' : ( 'attachment' === $e['target_type'] ? 'CONFLICT' : 'UPDATE' );
 }
}

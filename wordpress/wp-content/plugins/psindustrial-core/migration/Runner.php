<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

final class Runner {
 public static function batch( string $run, string $confirmation, int $limit = 10 ): array {
  Storage::guard();
  if ( 'IMPORTAR SUBSET EN BORRADOR' !== $confirmation ) { throw new \RuntimeException( 'EXPLICIT_CONFIRMATION_REQUIRED' ); }
  return Storage::locked( static function() use ( $run, $limit ): array {
   $plan = Storage::read( 'run-' . $run . '.json' );
   if ( ! $plan || ! in_array( $plan['status'], array( 'VALIDATED','RUNNING','COMPLETE' ), true ) || 'subset' !== $plan['scope'] || ! hash_equals( $plan['environment_id'], Storage::hash( array( home_url(), DB_NAME ) ) ) ) { throw new \RuntimeException( 'VALID_LOCAL_SUBSET_PLAN_REQUIRED' ); }
   if ( count( array_filter( $plan['entries'], static fn( $e ) => ! in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) ) > 25 ) { throw new \RuntimeException( 'SUBSET_LIMIT_EXCEEDED' ); }
   if ( 'COMPLETE' === $plan['status'] ) { return $plan; }
   if ( Planner::VERSION !== $plan['transform_version'] ) { throw new \RuntimeException( 'TRANSFORM_CHANGED_REPLAN' ); }
   if ( ! hash_equals( $plan['plan_hash'], Planner::digest( $plan ) ) ) { throw new \RuntimeException( 'PLAN_INTEGRITY_FAILED' ); }
   if ( ! hash_equals( $plan['decisions_hash'], Storage::hash( Planner::decisions() ) ) ) { throw new \RuntimeException( 'DECISIONS_CHANGED_REPLAN' ); }
   foreach ( $plan['sources'] as $relative => $hash ) { if ( ! hash_equals( $hash, hash_file( 'sha256', Sources::safe( Storage::project(), $relative ) ) ) ) { throw new \RuntimeException( 'SOURCE_CHANGED_REPLAN' ); } }
   $start = microtime( true ); $done = 0; $media = 0; $plan['status'] = 'RUNNING'; $plan['mode'] = 'EXECUTE_SUBSET';
   while ( $plan['cursor'] < count( $plan['entries'] ) && $done < min( 25, max( 1, $limit ) ) && $media < 5 && microtime( true ) - $start < 5 ) {
    $e = $plan['entries'][ $plan['cursor'] ]; $result = $e; unset( $result['row'], $result['data'], $result['decision'] );
    $result['run_id'] = $run; $result['environment_id'] = $plan['environment_id']; $result['migrated_at'] = null; $result['migration_date'] = null;
    try {
     if ( in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) { $result['status'] = 'SKIP' === $e['action'] ? 'SKIPPED' : 'BLOCKED'; $result['result'] = $e['action']; }
     else {
      if ( isset( $e['source_file_hash'] ) && ! hash_equals( $e['source_file_hash'], hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $e['legacy_file'] ) ) ) ) { throw new \RuntimeException( 'PHP_SOURCE_CHANGED_REPLAN' ); }
      if ( 'media' === $e['source_type'] ) {
       foreach ( array_merge( array( $e['legacy_file'] ), $e['binary_aliases'] ?? array() ) as $path ) {
        if ( ! hash_equals( $e['data']['sha256'], hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $path ) ) ) ) { throw new \RuntimeException( 'MEDIA_CHANGED_REPLAN' ); }
       }
      }
      foreach ( $e['dependencies'] as $dep ) {
       $dependency = array_values( array_filter( $plan['entries'], static fn( $v ) => $v['entity_key'] === $dep ) )[0] ?? null;
       if ( ! $dependency || ! Identity::find( $dependency ) || ! in_array( Identity::prediction( $dependency ), array( 'UNCHANGED','UPDATE' ), true ) ) { throw new \RuntimeException( 'DEPENDENCY_NOT_APPLIED:' . $dep ); }
      }
      $prediction = Identity::prediction( $e );
      if ( 'CONFLICT' === $prediction ) { $result['status'] = 'CONFLICT'; $result['result'] = 'REVIEW'; $result['notes'] = 'Destino editado, identidad parcial o versión binaria cambiada: conservar y resolver.'; }
      else {
       $id = 'UNCHANGED' === $prediction ? Identity::find( $e ) : self::apply( $e, $plan );
       $result['wordpress_id'] = $id; $result['status'] = 'UNCHANGED' === $prediction ? 'UNCHANGED' : 'APPLIED'; $result['result'] = $prediction;
       $state = Identity::get( $e, $id, '_psi_import_state' );
       $result['target_hash'] = $state['target_hash']; $result['last_applied_source_hash'] = $state['source_hash']; $result['migrated_at'] = $state['migrated_at']; $result['migration_date'] = $state['migrated_at'];
      }
     }
    } catch ( \Throwable $error ) { $result['status'] = 'FAILED'; $result['result'] = 'ERROR'; $result['notes'] = self::safe_error( $error->getMessage() ); }
    $plan['results'][] = $result; ++$plan['cursor']; ++$done; if ( 'media' === $e['source_type'] ) { ++$media; }
    Storage::log( $run, $e['entity_key'], $e['action'], $result['result'], $result['notes'] );
    Storage::write( 'run-' . $run . '.json', $plan );
    update_option( 'psi_import_run_' . $run, array( 'cursor' => $plan['cursor'], 'total' => count( $plan['entries'] ), 'status' => $plan['status'], 'updated_at' => gmdate( 'c' ) ), false );
   }
   if ( $plan['cursor'] === count( $plan['entries'] ) ) { $plan['status'] = 'COMPLETE'; }
   Storage::write( 'run-' . $run . '.json', $plan );
   update_option( 'psi_import_run_' . $run, array( 'cursor' => $plan['cursor'], 'total' => count( $plan['entries'] ), 'status' => $plan['status'], 'updated_at' => gmdate( 'c' ) ), false );
   return $plan;
  } );
 }
 public static function safe_error( string $message ): string { return preg_match( '/^[A-Z0-9_: .-]+$/D', $message ) ? $message : 'OBJECT_OPERATION_FAILED'; }
 public static function dependency( string $key, array $plan ): int {
  if ( ! $key ) { return 0; }
  foreach ( $plan['entries'] as $e ) { if ( $key === $e['entity_key'] ) { $id = Identity::find( $e ); if ( $id ) { return $id; } } }
  throw new \RuntimeException( 'DEPENDENCY_NOT_FOUND' );
 }
 private static function apply( array $e, array $plan ): int {
  Storage::guard(); $id = Identity::find( $e ); $creating = ! $id; $data = $e['data'];
  if ( isset( $e['source_file_hash'] ) && ! hash_equals( $e['source_file_hash'], hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $e['legacy_file'] ) ) ) ) { throw new \RuntimeException( 'PHP_SOURCE_CHANGED_REPLAN' ); }
  $journal = array( 'entity_key' => $e['entity_key'], 'run_id' => $plan['run_id'], 'status' => 'INTENT', 'wordpress_id' => $id, 'created' => $creating, 'before' => $id ? Identity::snapshot( $e, $id ) : array(), 'at' => gmdate( 'c' ) );
  Storage::write( Identity::ledger( $e ), $journal );
  do_action( 'psi_import_boundary', 'intent', $e );
  if ( Identity::term( $e ) ) {
   $args = array( 'slug' => $data['slug'], 'description' => $data['description'], 'parent' => self::dependency( $data['parent'], $plan ) );
   $result = $id ? wp_update_term( $id, $e['target_type'], $args + array( 'name' => $data['name'] ) ) : wp_insert_term( $data['name'], $e['target_type'], $args );
   if ( is_wp_error( $result ) ) { throw new \RuntimeException( 'TERM_WRITE_FAILED' ); } $id = (int) $result['term_id'];
  } elseif ( 'attachment' === $e['target_type'] ) {
   $id = self::media( $e );
  } else {
   $args = array( 'post_type' => $e['target_type'], 'post_title' => $data['name'], 'post_name' => $data['slug'], 'post_content' => $data['content'], 'post_status' => 'draft' );
   if ( $id ) { $args['ID'] = $id; }
   $result = wp_insert_post( wp_slash( $args ), true );
   if ( is_wp_error( $result ) || ! $result ) { throw new \RuntimeException( 'POST_WRITE_FAILED' ); } $id = (int) $result;
  }
  // Persist ID before relationships; a crash before here remains INTENT and blocks blind reinsertion.
  $journal['wordpress_id'] = $id; $journal['status'] = 'OBJECT_CREATED'; $journal['initial_hash'] = Storage::hash( Identity::snapshot( $e, $id ) ); Storage::write( Identity::ledger( $e ), $journal );
  do_action( 'psi_import_boundary', 'object', $e );
  $keys = array_values( array_unique( array_merge( $e['source_keys'], $e['decision']['source_keys'] ?? array() ) ) );
  Identity::set( $e, $id, '_psi_source_keys', $keys );
  Identity::set( $e, $id, '_psi_import_identity', array( 'entity_key' => $e['entity_key'], 'created_by_run' => $creating ? $plan['run_id'] : ( Identity::get( $e, $id, '_psi_import_identity' )['created_by_run'] ?? '' ) ) );
  Identity::set( $e, $id, '_psi_import_origin', array( 'legacy_id' => $e['legacy_id'], 'file' => $e['legacy_file'], 'url' => $e['legacy_url'], 'source_hash' => $e['source_hash'], 'binary_aliases' => $e['binary_aliases'] ?? array(), 'seo_evidence' => $e['seo_evidence'] ?? array() ) );
  if ( Identity::term( $e ) ) {
   Identity::set( $e, $id, '_psi_public_state', 'review' );
   if ( $data['image'] ) { Identity::set( $e, $id, 'psi_marca' === $e['target_type'] ? '_psi_logo_id' : '_psi_image_id', self::dependency( $data['image'], $plan ) ); }
  } elseif ( 'attachment' !== $e['target_type'] ) {
   Identity::set( $e, $id, '_psi_review_state', 'pending' );
   if ( 'psi_producto' === $e['target_type'] ) {
    $categories = array_map( static fn( $key ) => self::dependency( $key, $plan ), $data['categories'] );
    foreach ( array( 'psi_categoria' => $categories, 'psi_marca' => $data['brand'] ? array( self::dependency( $data['brand'], $plan ) ) : array() ) as $tax => $ids ) { if ( is_wp_error( wp_set_object_terms( $id, $ids, $tax, false ) ) ) { throw new \RuntimeException( 'RELATION_WRITE_FAILED' ); } }
    $images = array_values( array_unique( array_map( static fn( $key ) => self::dependency( $key, $plan ), $data['images'] ) ) );
    Identity::set( $e, $id, '_psi_gallery_ids', array() );
    if ( $images ) { if ( false === set_post_thumbnail( $id, $images[0] ) && (int) get_post_thumbnail_id( $id ) !== $images[0] ) { throw new \RuntimeException( 'THUMBNAIL_WRITE_FAILED' ); } }
    else { delete_post_thumbnail( $id ); }
    Identity::set( $e, $id, '_psi_gallery_ids', array_slice( $images, 1 ) );
    $pdfs = array(); foreach ( $data['pdfs'] as $key ) { $pdfs[] = array( 'attachment_id' => self::dependency( $key, $plan ), 'label' => basename( substr( $key, 6 ) ), 'language' => '' ); }
    Identity::set( $e, $id, '_psi_datasheets', $pdfs ); Identity::set( $e, $id, '_psi_videos', $data['videos'] );
   }
  }
  if ( 'attachment' !== $e['target_type'] ) { Identity::set( $e, $id, '_psi_source_hash', $e['source_hash'] ); }
  if ( 'product' === $e['source_type'] && ! empty( $e['legacy_date'] ) ) { Identity::set( $e, $id, '_psi_legacy_date', $e['legacy_date'] ); }
  do_action( 'psi_import_boundary', 'relations', $e );
  $state = array( 'source_hash' => $e['source_hash'], 'decision_hash' => $e['decision_hash'], 'last_applied_source_hash' => $e['source_hash'], 'target_hash' => Storage::hash( Identity::snapshot( $e, $id ) ), 'run_id' => $plan['run_id'], 'migrated_at' => gmdate( 'c' ) );
  Identity::set( $e, $id, '_psi_import_state', $state );
  $journal['status'] = 'APPLIED'; $journal['state'] = $state; Storage::write( Identity::ledger( $e ), $journal );
  return $id;
 }
 /** Controlled cleanup of creations only. Updates use the private before-snapshot and manual review. */
 public static function rollback_created( string $run, string $confirmation ): array {
  if ( 'RETIRAR CREACIONES SIN CAMBIOS' !== $confirmation ) { throw new \RuntimeException( 'EXPLICIT_CONFIRMATION_REQUIRED' ); }
  return Storage::locked( static function() use ( $run ): array {
   $plan = Storage::read( 'run-' . $run . '.json' ); $results = array();
   if ( ! $plan || 'subset' !== $plan['scope'] ) { throw new \RuntimeException( 'SUBSET_REQUIRED' ); }
   foreach ( array_reverse( $plan['entries'] ) as $e ) {
    $journal = Storage::read( Identity::ledger( $e ) );
    if ( ! $journal || $journal['run_id'] !== $run || ! $journal['created'] ) { continue; }
    $id = (int) $journal['wordpress_id'];
    if ( ! $id ) { $results[ $e['entity_key'] ] = 'REVIEW_INTENT_WITHOUT_ID'; continue; }
    $expected = $journal['state']['target_hash'] ?? $journal['initial_hash'] ?? '';
    if ( ! $expected || ! hash_equals( $expected, Storage::hash( Identity::snapshot( $e, $id ) ) ) ) { $results[ $e['entity_key'] ] = 'CONFLICT'; continue; }
    if ( Identity::term( $e ) ) {
     $objects = get_objects_in_term( $id, $e['target_type'] ); $children = get_terms( array( 'taxonomy' => $e['target_type'], 'parent' => $id, 'hide_empty' => false, 'psi_include_review' => true, 'fields' => 'ids' ) );
     if ( $objects || $children || 'public' === get_term_meta( $id, '_psi_public_state', true ) ) { $results[ $e['entity_key'] ] = 'REVIEW_REFERENCED'; continue; }
     $ok = wp_delete_term( $id, $e['target_type'] );
    } else {
     if ( 'attachment' !== $e['target_type'] && 'draft' !== get_post_status( $id ) ) { $results[ $e['entity_key'] ] = 'REVIEW_NOT_DRAFT'; continue; }
     $ok = 'attachment' === $e['target_type'] ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
    }
    if ( $ok && ! is_wp_error( $ok ) ) { wp_delete_file( Storage::path( Identity::ledger( $e ) ) ); $results[ $e['entity_key'] ] = 'REMOVED'; } else { $results[ $e['entity_key'] ] = 'REVIEW_DELETE_BLOCKED'; }
   }
   Storage::write( 'rollback-' . $run . '.json', $results ); return $results;
  } );
 }
 private static function media( array $e ): int {
  $d = $e['data']; $source = Storage::path( $d['package_asset'] );
  if ( ! hash_equals( $d['sha256'], hash_file( 'sha256', $source ) ) || ! \PSIndustrial\Core\Media::file_valid( $source, $d['mime'] ) ) { throw new \RuntimeException( 'MEDIA_CHANGED_OR_UNSAFE' ); }
  require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
  $ext = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf' )[ $d['mime'] ];
  $name = sanitize_file_name( pathinfo( $d['name'], PATHINFO_FILENAME ) ) . '.' . $ext;
  $tmp = wp_tempnam( $name, Storage::root() );
  try {
   if ( ! copy( $source, $tmp ) ) { throw new \RuntimeException( 'MEDIA_COPY_FAILED' ); }
   $id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, null, array( 'post_title' => sanitize_text_field( $d['name'] ) ) );
   if ( is_wp_error( $id ) ) { throw new \RuntimeException( 'MEDIA_SIDELOAD_FAILED' ); }
   Identity::set( $e, $id, '_psi_content_sha256', $d['sha256'] ); Identity::set( $e, $id, '_psi_original_name', $d['name'] ); return $id;
  } finally { if ( is_file( $tmp ) ) { wp_delete_file( $tmp ); } }
 }
}

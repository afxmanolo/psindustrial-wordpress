<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

/** One-off completion of the eleven documented PDF-B partial posts. No creation/recovery engine. */
final class PartialPdfRepair {
 public const RUN = '9c492018-cc99-4548-ac37-aed5d73688c1';
 public const IDS = array( 5 => 1360, 6 => 1361, 7 => 1367, 17 => 1354, 18 => 1355, 19 => 1356, 22 => 1357, 24 => 1358, 44 => 1359, 77 => 1372, 95 => 1373 );

 /** Gather current machine evidence without changing content. */
 public static function evidence( string $key, array $fresh ): array {
  Storage::guard();
  $run = Storage::read( 'run-' . self::RUN . '.json' );
  $sealed = array_column( $run['entries'], null, 'entity_key' )[ $key ] ?? array();
  $e = array_column( $fresh['entries'], null, 'entity_key' )[ $key ] ?? array();
  $result = array_column( $run['results'], null, 'entity_key' )[ $key ] ?? array();
  $id = $e ? Identity::find( $e ) : 0;
  $facts = array( 'key' => $key, 'id' => $id, 'expected_id' => self::IDS[ (int) substr( $key, 14 ) ] ?? 0,
   'entry' => $e, 'sealed' => $sealed, 'result' => $result,
   'run_sealed' => hash_equals( $run['plan_hash'], Storage::hash( array_intersect_key( $run, array_flip( array( 'manifest_version','transform_version','run_id','scope','parent_run_id','recovery_reason','environment_id','created_at','entries' ) ) ) ) ) );
  if ( ! $e || ! $id ) { return $facts; }
  $facts['ledger'] = Storage::read( Identity::ledger( $e ) );
  $facts['identity'] = get_post_meta( $id, '_psi_import_identity', true );
  $facts['origin'] = get_post_meta( $id, '_psi_import_origin', true );
  $facts['state'] = get_post_meta( $id, '_psi_import_state', true );
  $facts['prediction'] = Identity::prediction( $e );
  $facts['snapshot'] = Identity::snapshot( $e, $id );
  $initial = $facts['snapshot']; $initial['meta'] = array(); $initial['psi_categoria'] = array(); $initial['psi_marca'] = array();
  $facts['initial_hash'] = Storage::hash( $initial );
  $post = get_post( $id );
  $facts['unedited'] = $post->post_modified === $post->post_date && $post->post_modified_gmt === $post->post_date_gmt && ! wp_get_post_revisions( $id );
  $facts['actual_pdfs'] = get_post_meta( $id, '_psi_datasheets', true );
  $facts['expected_pdfs'] = array(); $facts['paths'] = array(); $facts['pdf_checks'] = array();
  $byKey = array_column( $fresh['entries'], null, 'entity_key' );
  foreach ( $sealed['data']['pdfs'] ?? array() as $pdfKey ) {
   $dep = $byKey[ $pdfKey ] ?? array(); $att = $dep ? Identity::find( $dep ) : 0;
   $path = substr( $pdfKey, 6 ); $file = $att ? get_attached_file( $att ) : false;
   $hash = $file && is_file( $file ) ? hash_file( 'sha256', $file ) : '';
   $groupB = 'exception' === ( $dep['pdf_approval_type'] ?? '' );
   $facts['pdf_checks'][] = array( 'id' => $att, 'expected_id' => (int) ( Storage::read( Identity::ledger( $dep ) )['wordpress_id'] ?? 0 ),
    'valid' => $att && 'attachment' === get_post_type( $att ) && 'application/pdf' === get_post_mime_type( $att ) && 'trash' !== get_post_status( $att ),
    'hash_matches' => $hash && hash_equals( $dep['data']['sha256'] ?? '', $hash ),
    'approved' => $groupB ? PdfApprovals::isApprovedFalsePositive( $path, $hash ) : \PSIndustrial\Core\Media::valid( $att, 'pdf' ), 'group_b' => $groupB );
   $facts['expected_pdfs'][] = array( 'attachment_id' => $att, 'label' => basename( $path ), 'language' => '' ); $facts['paths'][ $att ] = $path;
  }
  $expected = $initial;
  $expected['psi_categoria'] = array_map( static fn( $k ) => Runner::dependency( $k, $fresh ), $sealed['data']['categories'] ); sort( $expected['psi_categoria'] );
  $expected['psi_marca'] = $sealed['data']['brand'] ? array( Runner::dependency( $sealed['data']['brand'], $fresh ) ) : array();
  $images = array_values( array_unique( array_map( static fn( $k ) => Runner::dependency( $k, $fresh ), $sealed['data']['images'] ) ) );
  $keys = array_values( array_unique( array_merge( $sealed['source_keys'], $sealed['decision']['source_keys'] ?? array() ) ) );
  $expected['meta'] = array( '_psi_gallery_ids' => array( array_slice( $images, 1 ) ), '_psi_review_state' => array( 'pending' ), '_psi_source_keys' => array( $keys ) );
  if ( $images ) { $expected['meta']['_thumbnail_id'] = array( (string) $images[0] ); }
  ksort( $expected['meta'] );
  $facts['expected_partial'] = $expected;
  return $facts;
 }

 /** Pure predicate used by tests; execution always gathers its own evidence. */
 public static function eligibility( array $f ): string {
  if ( ! preg_match( '/^sql:productos:[0-9]+$/D', $f['key'] ?? '' ) || ! ( $f['expected_id'] ?? 0 ) || $f['id'] !== $f['expected_id'] || ! ( $f['run_sealed'] ?? false ) ) { return 'IDENTITY_MISMATCH'; }
  $e = $f['entry']; $s = $f['sealed']; $j = $f['ledger'];
  if ( ! in_array( $e['action'], array( 'MIGRATE','MERGE','CREATE_FROM_STATIC' ), true ) || 'CONFLICT' !== ( $f['result']['status'] ?? '' ) ) { return 'EXCLUDED_ACTION_OR_RESULT'; }
  foreach ( array( 'entity_key','source_hash','decision_hash','action' ) as $k ) { if ( ( $e[ $k ] ?? null ) !== ( $s[ $k ] ?? null ) ) { return 'SOURCE_OR_DECISION_CHANGED'; } }
  if ( ( $j['entity_key'] ?? '' ) !== $f['key'] || ( $j['wordpress_id'] ?? 0 ) !== $f['id'] || ( $j['run_id'] ?? '' ) !== self::RUN || empty( $j['created'] ) || ( $f['identity']['entity_key'] ?? '' ) !== $f['key'] || ( $f['identity']['created_by_run'] ?? '' ) !== self::RUN || ( $f['origin']['source_hash'] ?? '' ) !== $e['source_hash'] ) { return 'NOT_OWN_PARTIAL_ATTEMPT'; }
  if ( empty( $f['pdf_checks'] ) || ! in_array( true, array_column( $f['pdf_checks'], 'group_b' ), true ) ) { return 'GROUP_B_REQUIRED'; }
  foreach ( $f['pdf_checks'] as $p ) { if ( ! $p['id'] || $p['id'] !== $p['expected_id'] || ! $p['valid'] || ! $p['hash_matches'] || ! $p['approved'] ) { return 'PDF_EVIDENCE_FAILED'; } }
  if ( 'APPLIED' === $j['status'] && 'UNCHANGED' === $f['prediction'] && $f['actual_pdfs'] === $f['expected_pdfs'] ) { return 'UNCHANGED'; }
  if ( 'OBJECT_CREATED' !== $j['status'] || ! empty( $f['state'] ) || ! $f['unedited'] || ! hash_equals( $j['initial_hash'] ?? '', $f['initial_hash'] ) || $f['snapshot'] !== $f['expected_partial'] || ! empty( $f['actual_pdfs'] ) || ! empty( $s['data']['videos'] ) ) { return 'DESTINATION_CHANGED_OR_NOT_DATASHEET_ONLY'; }
  return 'REPAIRABLE';
 }

 public static function repair( string $key, array $fresh, string $backupName ): string {
  Storage::guard();
  return Storage::locked( static function() use ( $key, $fresh, $backupName ): string {
   $backup = Storage::read( $backupName );
   if ( empty( $backup['restore_verified'] ) ) { throw new \RuntimeException( 'VERIFIED_BACKUP_REQUIRED' ); }
   foreach ( Runner::verify_backup_integrity( $backup ) as $check ) { if ( ! $check['passed'] ) { throw new \RuntimeException( 'BACKUP_INTEGRITY_FAILED' ); } }
   $f = self::evidence( $key, $fresh ); $verdict = self::eligibility( $f );
   if ( 'UNCHANGED' === $verdict ) { return $verdict; }
   if ( 'REPAIRABLE' !== $verdict ) { throw new \RuntimeException( $verdict ); }
   $e = $f['entry']; $id = $f['id'];
   if ( strtotime( $backup['created_at'] ) <= strtotime( $f['ledger']['at'] ) || ! hash_equals( $backup['private_evidence_manifest'][ Identity::ledger( $e ) ] ?? '', hash_file( 'sha256', Storage::path( Identity::ledger( $e ) ) ) ) ) { throw new \RuntimeException( 'CURRENT_PARTIAL_JOURNAL_BACKUP_REQUIRED' ); }
   // Preserve the pre-repair journal; never recreate or update the post itself.
   Storage::write( 'partial-pdf-before-' . $id . '.json', $f );
   Runner::write_approved_datasheets( $e, $id, $f['expected_pdfs'], $f['paths'] );
   Identity::set( $e, $id, '_psi_source_hash', $e['source_hash'] );
   if ( ! empty( $e['legacy_date'] ) ) { Identity::set( $e, $id, '_psi_legacy_date', $e['legacy_date'] ); }
   $state = array( 'source_hash' => $e['source_hash'], 'decision_hash' => $e['decision_hash'], 'last_applied_source_hash' => $e['source_hash'], 'target_hash' => Storage::hash( Identity::snapshot( $e, $id ) ), 'run_id' => self::RUN, 'migrated_at' => gmdate( 'c' ) );
   Identity::set( $e, $id, '_psi_import_state', $state );
   $journal = $f['ledger']; $journal['status'] = 'APPLIED'; $journal['state'] = $state; $journal['repair'] = array( 'reason' => 'COMPLETE_PDF_B_RELATION', 'backup' => $backupName, 'at' => gmdate( 'c' ) );
   Storage::write( Identity::ledger( $e ), $journal );
   Storage::log( self::RUN, $key, 'COMPLETE_PDF_B_RELATION', 'REPAIRED' );
   return 'REPAIRED';
  } );
 }
}

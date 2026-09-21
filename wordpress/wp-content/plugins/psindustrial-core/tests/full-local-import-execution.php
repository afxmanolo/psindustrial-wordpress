<?php
/** Runner::batch_full_local_resolved_only() tests. Every mutation in this file targets a
 * synthetic, isolated, 'test:'-prefixed entity created and torn down by this file itself --
 * never the real catalogue, never the 15 real subset objects, never the real current FULL
 * DRY RUN plan. Mirrors the exact isolated-fixture technique tests/importer.php already uses
 * and has proven for the subset path, applied here to scope='full' and the new method. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,Planner,Identity,Runner};
(static function(): void {
 $checks = array(); $cleanupPostIds = array(); $cleanupLedgers = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $throws = static function( callable $fn, string $label ) use ( $assert ): void { $caught = false; try { $fn(); } catch ( Throwable $e ) { $caught = true; } $assert( $caught, $label ); };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };
 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();

  // A real, valid 'full' plan supplies the outer shape (sources fingerprints, decisions_hash,
  // environment_id) that preflight/execution legitimately check -- entries are then replaced
  // entirely with small synthetic fixtures. Built once, read-only, never itself executed.
  $realFull = Planner::build( 'full' );
  $baseEntry = array_values( array_filter( $realFull['entries'], static fn( $e ) => 'php:nosotros.php' === $e['entity_key'] ) )[0];

  /** Builds an isolated single- or multi-entry 'full' scope plan from $entryOverrides (each
   *  override array is shallow-merged onto a fresh clone of $baseEntry). Never touches or
   *  reuses $realFull's own run_id/entries/results. */
  $makeFull = static function( array $entrySpecs ) use ( $realFull, $baseEntry, &$cleanupLedgers ): array {
   $entries = array();
   foreach ( $entrySpecs as $spec ) {
    $e = $baseEntry;
    $e['entity_key'] = $spec['key']; $e['source_key'] = $spec['key']; $e['source_keys'] = $spec['source_keys'] ?? array( $spec['key'] );
    $e['action'] = $spec['action'] ?? 'MIGRATE';
    $e['data']['name'] = $spec['name'] ?? ( 'PSI full-local test ' . $spec['key'] );
    $e['data']['slug'] = sanitize_title( $spec['key'] );
    $e['source_hash'] = $spec['source_hash'] ?? Storage::hash( array( $spec['key'], $spec['name'] ?? '' ) );
    $e['dependencies'] = $spec['dependencies'] ?? array();
    if ( isset( $spec['decision'] ) ) { $e['decision'] = $spec['decision']; }
    $entries[] = $e;
    $cleanupLedgers[] = Identity::ledger( $e );
   }
   $plan = $realFull;
   $plan['run_id'] = wp_generate_uuid4(); $plan['scope'] = 'full'; $plan['status'] = 'VALIDATED'; $plan['mode'] = 'DRY_RUN';
   $plan['cursor'] = 0; $plan['results'] = array(); $plan['entries'] = $entries; unset( $plan['backup'] );
   $plan['plan_hash'] = Planner::digest( $plan );
   Storage::write( 'run-' . $plan['run_id'] . '.json', $plan );
   return $plan;
  };
  $id = static fn( array $entry ) => Identity::find( $entry );

  // ============================================================== frases: ninguna autoriza el scope de la otra
  $fixture = $makeFull( array( array( 'key' => 'test:' . wp_generate_uuid4() ) ) );
  $throws( static fn() => Runner::batch_full_local_resolved_only( $fixture['run_id'], '' ), 'Full-local execution requires explicit confirmation' );
  $throws( static fn() => Runner::batch_full_local_resolved_only( $fixture['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ), 'The SUBSET phrase never authorizes a full-scope plan' );
  // A DISTINCT run_id/file -- reusing $fixture's own would overwrite the very file the
  // "should succeed" call further down still needs to read back correctly.
  $subsetShaped = $fixture; $subsetShaped['run_id'] = wp_generate_uuid4(); $subsetShaped['scope'] = 'subset'; $subsetShaped['plan_hash'] = Planner::digest( $subsetShaped ); Storage::write( 'run-' . $subsetShaped['run_id'] . '.json', $subsetShaped );
  $throws( static fn() => Runner::batch( $subsetShaped['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' ), 'The FULL phrase never authorizes execution via the subset method either' );
  $throws( static fn() => Runner::batch_full_local_resolved_only( $fixture['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' . ' ' ), 'Confirmation phrase must match exactly (trailing space rejected)' );

  // ============================================================== full local valida -> ALLOW; backup automatico en la primera llamada
  $assert( ! is_file( Storage::path( 'backup-' . $fixture['run_id'] . '.json' ) ), 'Sanity: no backup exists yet for this fresh run_id' );
  $done = Runner::batch_full_local_resolved_only( $fixture['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' );
  $assert( is_file( Storage::path( 'backup-' . $fixture['run_id'] . '.json' ) ), 'Database backup taken automatically before the first mutation' );
  $assert( is_dir( Storage::root() . '/backup-uploads-' . $fixture['run_id'] ), 'Uploads backup taken automatically before the first mutation' );
  $assert( 'APPLIED' === $done['results'][0]['status'], 'CREATE-eligible entry (MIGRATE action, brand-new identity) executes successfully' );
  $tid1 = $id( $fixture['entries'][0] ); $cleanupPostIds[] = $tid1;
  $assert( $tid1 > 0, 'Synthetic fixture created via real WordPress APIs' );
  $assert( 'draft' === get_post_status( $tid1 ), 'Full-local execution never publishes -- created as draft' );

  // ============================================================== REVIEW row -> cero mutacion
  $reviewKey = 'test:' . wp_generate_uuid4();
  $mixed = $makeFull( array(
   array( 'key' => 'test:' . wp_generate_uuid4(), 'name' => 'CREATE sibling' ),
   array( 'key' => $reviewKey, 'action' => 'REVIEW', 'name' => 'Never created' ),
  ) );
  $done = Runner::batch_full_local_resolved_only( $mixed['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' );
  while ( 'COMPLETE' !== $done['status'] ) { $done = Runner::batch_full_local_resolved_only( $mixed['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' ); }
  $reviewEntry = array_values( array_filter( $mixed['entries'], static fn( $e ) => $reviewKey === $e['entity_key'] ) )[0];
  $assert( 0 === Identity::find( $reviewEntry ), 'A REVIEW-action entry never creates a post/term/attachment, even inside an otherwise-executed full-local batch' );
  $reviewResult = array_values( array_filter( $done['results'], static fn( $r ) => $reviewKey === $r['entity_key'] ) )[0];
  $assert( 'BLOCKED' === $reviewResult['status'] && 'REVIEW' === $reviewResult['result'], 'REVIEW row is walked over (cursor advances, logged) but never applied -- its own action never silently becomes SKIP or CREATE' );
  $createSibling = $mixed['entries'][0]; $cleanupPostIds[] = $id( $createSibling );
  $assert( $id( $createSibling ) > 0, 'The CREATE sibling in the SAME batch still executed normally alongside the preserved REVIEW row' );

  // ============================================================== SKIP row -> cero mutacion
  $skipKey = 'test:' . wp_generate_uuid4();
  $skipPlan = $makeFull( array( array( 'key' => $skipKey, 'action' => 'SKIP', 'name' => 'Never created either' ) ) );
  $done = Runner::batch_full_local_resolved_only( $skipPlan['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' );
  $assert( 'SKIPPED' === $done['results'][0]['status'] && 0 === Identity::find( $skipPlan['entries'][0] ), 'SKIP row never creates anything' );

  global $wpdb;
  $optionsHash = static fn() => Storage::hash( $wpdb->get_results( "SELECT option_name,option_value FROM {$wpdb->options} ORDER BY option_name", ARRAY_A ) );
  $postCount = static fn() => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" );

  // ============================================================== MERGE row -> ejecutable (misma tecnica sintetica que el ensayo subset)
  $mergeKey = 'test:' . wp_generate_uuid4(); $aliasKey = 'test:' . wp_generate_uuid4();
  $mergePlan = $makeFull( array( array( 'key' => $mergeKey, 'action' => 'MERGE', 'decision' => array( 'source_keys' => array( $mergeKey, $aliasKey ) ) ) ) );
  $done = Runner::batch_full_local_resolved_only( $mergePlan['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' );
  $mergedLookup = $mergePlan['entries'][0]; $mergedLookup['entity_key'] = $aliasKey;
  $mtid = Identity::find( $mergedLookup ); $cleanupPostIds[] = $mtid;
  $assert( $mtid > 0 && $mtid === $done['results'][0]['wordpress_id'], 'MERGE row executes via the identical mechanism as subset -- one object, multiple source identities' );

  // ============================================================== ejecucion interrumpida -> resumable; segunda ejecucion -> idempotente
  $keys = array(); for ( $i = 0; $i < 5; ++$i ) { $keys[] = array( 'key' => 'test:' . wp_generate_uuid4(), 'name' => 'Resumable ' . $i ); }
  $resumable = $makeFull( $keys );
  $done = Runner::batch_full_local_resolved_only( $resumable['run_id'], 'IMPORTAR FULL LOCAL RESUELTO', 2 ); // limit=2: forces at least 3 calls for 5 entries.
  $assert( 2 === $done['cursor'] && 'RUNNING' === $done['status'], 'First call processes exactly `limit` entries and persists status=RUNNING, cursor=2' );
  $assert( is_file( Storage::path( 'run-' . $resumable['run_id'] . '.json' ) ), 'Snapshot persisted mid-run' );
  $reread = Storage::read( 'run-' . $resumable['run_id'] . '.json' );
  $assert( 2 === $reread['cursor'] && 'RUNNING' === $reread['status'], 'Persisted snapshot reflects the interruption point exactly' );
  while ( 'COMPLETE' !== $done['status'] ) { $done = Runner::batch_full_local_resolved_only( $resumable['run_id'], 'IMPORTAR FULL LOCAL RESUELTO', 2 ); }
  $assert( 5 === $done['cursor'], 'Resumed execution reaches the end without needing a fresh plan or losing progress' );
  $createdIds = array_map( $id, $resumable['entries'] );
  foreach ( $createdIds as $cid ) { $cleanupPostIds[] = $cid; }
  $assert( 5 === count( array_unique( $createdIds ) ) && ! in_array( 0, $createdIds, true ), 'Interrupted-then-resumed batch created exactly 5 distinct objects, never duplicating the first 2' );
  // Idempotent second execution: re-invoking on the now-COMPLETE plan is a pure no-op.
  $beforeIdempotent = array( $postCount(), $optionsHash() );
  $again = Runner::batch_full_local_resolved_only( $resumable['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' );
  $assert( 'COMPLETE' === $again['status'] && 5 === $again['cursor'], 'Second call on an already-COMPLETE run short-circuits, unchanged' );
  $assert( $beforeIdempotent === array( $postCount(), $optionsHash() ), 'Second execution on a completed run creates no posts and touches no options -- true no-op' );

  // ============================================================== fuente cambiada -> conflicto, no sobrescribe
  $srcChangeKey = 'test:' . wp_generate_uuid4();
  $original = $makeFull( array( array( 'key' => $srcChangeKey, 'name' => 'Original content' ) ) );
  Runner::batch_full_local_resolved_only( $original['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' );
  $ctid = $id( $original['entries'][0] ); $cleanupPostIds[] = $ctid;
  $changed = $makeFull( array( array( 'key' => $srcChangeKey, 'name' => 'Original content', 'source_hash' => str_repeat( 'a', 64 ) ) ) );
  $done = Runner::batch_full_local_resolved_only( $changed['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' );
  $assert( 'UPDATE' === $done['results'][0]['result'] && $ctid === $done['results'][0]['wordpress_id'], 'A changed source_hash against an unmodified destination updates in place -- never a duplicate object' );

  // ============================================================== destino editado por humano -> conflicto, no sobrescribe
  wp_update_post( array( 'ID' => $ctid, 'post_excerpt' => 'Human editorial change (full-local test)' ) );
  $retryPlan = $makeFull( array( array( 'key' => $srcChangeKey, 'name' => 'Original content', 'source_hash' => str_repeat( 'b', 64 ) ) ) );
  $done = Runner::batch_full_local_resolved_only( $retryPlan['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' );
  $assert( 'CONFLICT' === $done['results'][0]['status'], 'A human-edited destination blocks automatic overwrite -- marked CONFLICT, never silently applied' );
  $assert( 'Human editorial change (full-local test)' === get_post( $ctid )->post_excerpt, 'The human edit survives the conflicting execution attempt untouched' );

  // ============================================================== fatal vs. entity-level: fatal aborta todo el lote; entity-level continua
  $entityLevelKey = 'test:' . wp_generate_uuid4();
  $entityLevelPlan = $makeFull( array( array( 'key' => $entityLevelKey ) ) );
  $hook = static function( $at, $e ) use ( $entityLevelKey ) { if ( 'intent' === $at && $e['entity_key'] === $entityLevelKey ) { throw new RuntimeException( 'INJECTED_ENTITY_LEVEL_FAILURE' ); } };
  add_action( 'psi_import_boundary', $hook, 10, 2 );
  try { $done = Runner::batch_full_local_resolved_only( $entityLevelPlan['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' ); } finally { remove_action( 'psi_import_boundary', $hook, 10 ); }
  $assert( 'FAILED' === $done['results'][0]['status'] && 'COMPLETE' === $done['status'], 'An unrecognised (entity-level) injected failure is caught, marked FAILED, and the batch still completes -- never aborts the whole run' );
  $cleanupLedgers[] = Identity::ledger( $entityLevelPlan['entries'][0] );

  $fatalKey = 'test:' . wp_generate_uuid4();
  $fatalPlan = $makeFull( array(
   array( 'key' => 'test:' . wp_generate_uuid4(), 'name' => 'Before the fatal entry' ),
   array( 'key' => $fatalKey, 'name' => 'The fatal entry' ),
   array( 'key' => 'test:' . wp_generate_uuid4(), 'name' => 'After the fatal entry -- must never be attempted' ),
  ) );
  $fatalHook = static function( $at, $e ) use ( $fatalKey ) { if ( 'intent' === $at && $e['entity_key'] === $fatalKey ) { throw new RuntimeException( 'PERMISSION_DENIED' ); } };
  add_action( 'psi_import_boundary', $fatalHook, 10, 2 );
  $fatalThrew = false;
  try { Runner::batch_full_local_resolved_only( $fatalPlan['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' ); }
  catch ( \Throwable $e ) { $fatalThrew = 'PERMISSION_DENIED' === $e->getMessage(); }
  finally { remove_action( 'psi_import_boundary', $fatalHook, 10 ); }
  $assert( $fatalThrew, 'A FATAL_ERROR_CODES-listed failure propagates out of batch_full_local_resolved_only() instead of being swallowed' );
  $afterFatal = Storage::read( 'run-' . $fatalPlan['run_id'] . '.json' );
  $assert( 1 === $afterFatal['cursor'], 'Cursor stopped exactly at the entry BEFORE the fatal one -- the fatal entry itself and everything after it were never attempted' );
  $assert( array() === $afterFatal['results'] || 1 === count( $afterFatal['results'] ), 'At most the one successful entry before the fatal one has a result recorded' );
  $cleanupPostIds[] = $id( $fatalPlan['entries'][0] ); $cleanupLedgers[] = Identity::ledger( $fatalPlan['entries'][0] );
  $assert( 0 === $id( $fatalPlan['entries'][1] ) && 0 === $id( $fatalPlan['entries'][2] ), 'Neither the fatal entry nor anything scheduled after it was created' );

  // ============================================================== rollback (solo fixtures, nunca los 15 objetos reales del subset)
  $rollbackKey = 'test:' . wp_generate_uuid4();
  $rollbackPlan = $makeFull( array( array( 'key' => $rollbackKey ) ) );
  Runner::batch_full_local_resolved_only( $rollbackPlan['run_id'], 'IMPORTAR FULL LOCAL RESUELTO' );
  $rtid = $id( $rollbackPlan['entries'][0] );
  $assert( $rtid > 0, 'Rollback fixture created successfully before being rolled back' );
  $rolled = Runner::rollback_created( $rollbackPlan['run_id'], 'RETIRAR CREACIONES SIN CAMBIOS' );
  $assert( array( 'REMOVED' ) === array_values( $rolled ), 'rollback_created() now accepts a full-scope run via the exact same mechanism as subset' );
  $assert( ! get_post( $rtid ), 'The rolled-back synthetic fixture no longer exists' );
  // The real 15 subset-rehearsal objects (and their real entity keys) are never referenced
  // anywhere in this file -- this is a structural guarantee (only 'test:*' keys were ever
  // constructed above), asserted explicitly here as documentation of that boundary.
  $assert( ! str_contains( wp_json_encode( array_keys( $rolled ) ), 'sql:productos:' ) && ! str_contains( wp_json_encode( array_keys( $rolled ) ), 'category:' ), 'Rollback touched only synthetic test: keys, never a real catalogue entity key' );

  $export( 'full-local-import-execution-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
 } catch ( Throwable $error ) {
  $export( 'full-local-import-execution-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) );
  fwrite( STDERR, $error->getMessage() . "\n" );
  foreach ( $cleanupPostIds as $pid ) { if ( $pid ) { wp_delete_post( $pid, true ); } }
  foreach ( $cleanupLedgers as $l ) { try { wp_delete_file( Storage::path( $l ) ); } catch ( \Throwable $e ) {} }
  exit( 1 );
 }
 foreach ( $cleanupPostIds as $pid ) { if ( $pid ) { wp_delete_post( $pid, true ); } }
 foreach ( $cleanupLedgers as $l ) { try { wp_delete_file( Storage::path( $l ) ); } catch ( \Throwable $e ) {} }
})();

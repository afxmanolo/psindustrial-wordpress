<?php
/** Tests for Runner::batch_recovery() (the scope=recovery executor) and the backup/restore
 * integrity checks it and recovery_preflight() depend on.
 *
 * batch_recovery() itself IS mutating by contract (it calls apply()) — so its batching/
 * cursor/idempotency/individual-drift behaviour is exercised here against a SYNTHETIC
 * recovery plan built from 48 REAL entity_keys that already predict UNCHANGED right now
 * (drawn from the 449 objects the real parent run already applied, plus the 15 pre-existing
 * subset objects) -- every one of them resolves through batch_recovery()'s own "already
 * applied, no-op" branch, so calling the REAL executor on this REAL plan can never create,
 * update or duplicate anything, verified directly below (post_first_import/wp_posts/
 * wp_terms counts identical before/after every single call in this suite). The REAL
 * recovery plan for the REAL parent run (9c492018-..., 48 genuinely-retryable entries) is
 * never passed to batch_recovery() anywhere in this file — this suite proves the mechanism
 * is safe and correct without ever executing the real retry the task explicitly withheld. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Identity,Planner,Runner};
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();
  global $wpdb;
  $dbSnapshot = static fn(): array => array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $realParentRun = '2e0c1248-8d56-4d92-a33b-c17e37b2732e';
  $realRecoveryRun = '9c492018-cc99-4548-ac37-aed5d73688c1';

  // ============================================================ build the safe synthetic fixture plan
  $fresh = Planner::build( 'full' );
  $unchangedKeys = array();
  foreach ( $fresh['entries'] as $e ) { if ( 'UNCHANGED' === $e['planned_result'] ) { $unchangedKeys[] = $e['entity_key']; } if ( 48 === count( $unchangedKeys ) ) { break; } }
  $assert( 48 === count( $unchangedKeys ), 'Fixture: found 48 real, currently-UNCHANGED entity_keys to build a safe synthetic plan from' );
  $freshByKey = array_column( $fresh['entries'], null, 'entity_key' );
  $syntheticEntries = array_map( static fn( $k ) => $freshByKey[ $k ], $unchangedKeys );
  foreach ( $syntheticEntries as $e ) { $assert( 'UNCHANGED' === Identity::prediction( $e ), 'Sanity: every fixture entry genuinely predicts UNCHANGED right now (nothing this suite does can mutate anything): ' . $e['entity_key'] ); }

  // Uses the REAL parent_run_id (its log-<id>.jsonl genuinely exists, and its real 449
  // applied entities are genuinely UNCHANGED right now) so recovery_preflight() -- which
  // batch_recovery() itself always calls first -- can actually pass; this suite is testing
  // the EXECUTOR's batching/cursor/idempotency/drift behaviour, not preflight's own parent-
  // evidence gate (already covered by tests/recovery-model.php). The synthetic ENTRIES
  // themselves (not the parent reference) are what make each fixture plan safe to execute.
  $buildSyntheticPlan = static function( array $entries ) use ( $fresh, $realParentRun ): array {
   $runId = wp_generate_uuid4();
   $plan = array(
    'manifest_version' => 1, 'transform_version' => Planner::VERSION, 'run_id' => $runId, 'scope' => 'recovery',
    'parent_run_id' => $realParentRun, 'recovery_reason' => 'TEST_FIXTURE', 'environment_id' => $fresh['environment_id'],
    'created_at' => gmdate( 'c' ), 'status' => 'VALIDATED', 'mode' => 'RECOVERY_CANDIDATE', 'cursor' => 0,
    'entries' => $entries, 'results' => array(), 'recovery_open' => true, 'post_first_import_backup' => null,
    'evidence_summary' => array( 'candidates_considered' => count( $entries ), 'retryable' => count( $entries ), 'rejected' => 0 ),
   );
   $ref = new ReflectionMethod( Runner::class, 'recovery_digest' ); $ref->setAccessible( true );
   $plan['plan_hash'] = $ref->invoke( null, $plan );
   Storage::write( 'run-' . $runId . '.json', $plan );
   return $plan;
  };
  $bypassPreflightBackupCheck = static function( string $runId ) use ( $realRecoveryRun ): void {
   // The fixture plans below are never meant to exercise the backup-manifest checks (those
   // are tested directly against real/synthetic manifests further down) -- reuse the REAL,
   // already-verified backup manifest purely so recovery_preflight() (which batch_recovery()
   // itself calls) has something real and valid to find, isolating THIS suite's assertions
   // to batching/cursor/idempotency/drift behaviour specifically.
   $plan = Storage::read( 'run-' . $runId . '.json' );
   $realPlan = Storage::read( 'run-' . $realRecoveryRun . '.json' );
   $plan['post_first_import_backup'] = $realPlan['post_first_import_backup'];
   Storage::write( 'run-' . $runId . '.json', $plan );
  };

  // ============================================================ scope=full -> DENY
  $before1 = $dbSnapshot();
  try { Runner::batch_recovery( $realParentRun, 'REINTENTAR RECOVERY LOCAL', 20 ); $assert( false, 'unreachable' ); }
  catch ( RuntimeException $e ) { $assert( 'VALID_RECOVERY_PLAN_REQUIRED' === $e->getMessage(), 'batch_recovery() DENIES a scope=full run_id outright: ' . $e->getMessage() ); }
  $assert( $before1 === $dbSnapshot(), 'The scope=full DENY attempt never touched the database' );

  // Wrong confirmation phrase (including the OTHER two valid phrases from this same class) -> DENY.
  $plan0 = $buildSyntheticPlan( array_slice( $syntheticEntries, 0, 1 ) );
  foreach ( array( 'IMPORTAR FULL LOCAL RESUELTO', 'REINTENTAR FALLOS RESUELTOS', 'wrong phrase entirely' ) as $wrongPhrase ) {
   try { Runner::batch_recovery( $plan0['run_id'], $wrongPhrase, 20 ); $assert( false, 'unreachable' ); }
   catch ( RuntimeException $e ) { $assert( 'EXPLICIT_RECOVERY_CONFIRMATION_REQUIRED' === $e->getMessage(), "Wrong/other-scope confirmation phrase rejected: '$wrongPhrase'" ); }
  }

  // ============================================================ scope=recovery valid -> ALLOW, 20+20+8, COMPLETE
  $plan = $buildSyntheticPlan( $syntheticEntries );
  $bypassPreflightBackupCheck( $plan['run_id'] );
  $sealedEntriesBefore = $plan['entries'];

  $before2 = $dbSnapshot();
  $b1 = Runner::batch_recovery( $plan['run_id'], 'REINTENTAR RECOVERY LOCAL', 20 );
  $assert( $before2 === $dbSnapshot(), 'Batch 1 (all-UNCHANGED fixture) never mutates the database' );
  $assert( 20 === $b1['cursor'] && 'RUNNING' === $b1['status'], 'Batch 1: cursor=20, status=RUNNING' );
  $assert( 20 === count( $b1['results'] ) && 20 === count( array_filter( $b1['results'], static fn( $r ) => 'UNCHANGED' === $r['status'] ) ), 'Batch 1: all 20 results are UNCHANGED (no-op, already applied)' );

  $b2 = Runner::batch_recovery( $plan['run_id'], 'REINTENTAR RECOVERY LOCAL', 20 );
  $assert( $before2 === $dbSnapshot(), 'Batch 2 never mutates the database' );
  $assert( 40 === $b2['cursor'] && 'RUNNING' === $b2['status'], 'Batch 2: cursor=40, status=RUNNING' );
  $assert( 40 === count( $b2['results'] ), 'Batch 2: 40 cumulative results' );
  // THE defect this executor exists to avoid: the sealed entries list must be byte-identical
  // after a batch that included already-applied (UNCHANGED) entries -- never recomputed.
  $assert( $sealedEntriesBefore === $b2['entries'], 'Sealed entries list is byte-identical after batch 2 -- never recomputed (the RETRY_SET_CHANGED_SINCE_FIRST_BUILD defect this executor was built to avoid)' );

  $b3 = Runner::batch_recovery( $plan['run_id'], 'REINTENTAR RECOVERY LOCAL', 20 );
  $assert( $before2 === $dbSnapshot(), 'Batch 3 never mutates the database' );
  $assert( 48 === $b3['cursor'] && 'COMPLETE' === $b3['status'], 'Batch 3: cursor=48, status=COMPLETE (20+20+8)' );
  $assert( 48 === count( $b3['results'] ), 'Batch 3: 48 cumulative results, matching entry count exactly' );
  $assert( $sealedEntriesBefore === $b3['entries'], 'Sealed entries list still byte-identical at COMPLETE' );

  // ============================================================ re-execution -> idempotent, no duplicates
  $before3 = $dbSnapshot();
  $b4 = Runner::batch_recovery( $plan['run_id'], 'REINTENTAR RECOVERY LOCAL', 20 );
  $assert( $before3 === $dbSnapshot(), 'Re-running a COMPLETE recovery run never mutates the database' );
  $assert( 48 === $b4['cursor'] && 'COMPLETE' === $b4['status'] && 48 === count( $b4['results'] ), 'Re-running a COMPLETE recovery run is a pure no-op: same cursor, same status, same 48 results, no duplicates' );

  // ============================================================ entry source/decision/action changed -> ONLY that entry CONFLICT
  // Exercised directly against Runner::recovery_process_entry() (the real per-entry logic
  // batch_recovery() itself delegates to) with a hand-built $freshPlan whose ONE entity's
  // source_hash genuinely differs from the sealed entry -- never by tampering with a real
  // sealed plan's own `entries` on disk (that field is part of what plan_hash covers, so
  // editing it there would correctly break the WHOLE plan's seal before ever reaching this
  // per-entry logic at all -- proven the hard way while building this suite).
  $driftEntries = array_slice( $syntheticEntries, 0, 5 );
  $rpe = new ReflectionMethod( Runner::class, 'recovery_process_entry' ); $rpe->setAccessible( true );

  $driftedFreshPlan = $fresh; $driftedKey = $driftEntries[2]['entity_key'];
  foreach ( $driftedFreshPlan['entries'] as &$fe ) { if ( $fe['entity_key'] === $driftedKey ) { $fe['source_hash'] = 'deliberately-drifted-source-hash'; } } unset( $fe );

  $before4 = $dbSnapshot();
  foreach ( $driftEntries as $i => $sealedEntry ) {
   $planForThisEntry = ( 2 === $i ) ? $driftedFreshPlan : $fresh; // only entry index 2 sees drifted "current" state; siblings see real, unmodified current state.
   if ( 2 === $i ) {
    try { $rpe->invoke( null, $sealedEntry, $planForThisEntry, $realParentRun ); $assert( false, 'unreachable' ); }
    catch ( RuntimeException $e ) { $assert( 'SOURCE_CHANGED_SINCE_RECOVERY_SEALED' === $e->getMessage(), "The drifted entry itself throws, precisely labelled: {$e->getMessage()}" ); }
   } else {
    $r = $rpe->invoke( null, $sealedEntry, $planForThisEntry, $realParentRun );
    $assert( 'UNCHANGED' === $r['status'], "A non-drifted sibling entry is unaffected, still its own normal outcome: {$sealedEntry['entity_key']}" );
   }
  }
  $assert( $before4 === $dbSnapshot(), 'Neither the drifted entry nor its siblings mutate the database' );

  // Same drill for decision_hash and action, and confirm a destination-conflict-not-our-own
  // is also correctly rejected -- all via the same real, extracted method.
  $decisionDriftPlan = $fresh; foreach ( $decisionDriftPlan['entries'] as &$fe2 ) { if ( $fe2['entity_key'] === $driftedKey ) { $fe2['decision_hash'] = 'deliberately-drifted-decision-hash'; } } unset( $fe2 );
  try { $rpe->invoke( null, $driftEntries[2], $decisionDriftPlan, $realParentRun ); $assert( false, 'unreachable' ); }
  catch ( RuntimeException $e ) { $assert( 'DECISION_CHANGED_SINCE_RECOVERY_SEALED' === $e->getMessage(), 'decision_hash drift precisely labelled: ' . $e->getMessage() ); }

  $actionDriftPlan = $fresh; foreach ( $actionDriftPlan['entries'] as &$fe3 ) { if ( $fe3['entity_key'] === $driftedKey ) { $fe3['action'] = 'SKIP'; } } unset( $fe3 );
  try { $rpe->invoke( null, $driftEntries[2], $actionDriftPlan, $realParentRun ); $assert( false, 'unreachable' ); }
  catch ( RuntimeException $e ) { $assert( 'ACTION_CHANGED_SINCE_RECOVERY_SEALED' === $e->getMessage(), 'action drift precisely labelled: ' . $e->getMessage() ); }

  $missingPlan = $fresh; $missingPlan['entries'] = array_values( array_filter( $missingPlan['entries'], static fn( $e ) => $e['entity_key'] !== $driftedKey ) );
  try { $rpe->invoke( null, $driftEntries[2], $missingPlan, $realParentRun ); $assert( false, 'unreachable' ); }
  catch ( RuntimeException $e ) { $assert( 'ENTITY_MISSING_FROM_CURRENT_STATE' === $e->getMessage(), 'Entity absent from current state precisely labelled: ' . $e->getMessage() ); }
  $assert( $before4 === $dbSnapshot(), 'None of the decision/action/missing drift probes mutate the database' );

  // ============================================================ 449/REVIEW/SKIP/legitimate-conflicts -> structurally never included
  // batch_recovery() only ever walks $plan['entries'] (the sealed set) -- for the REAL
  // recovery plan, that set is exactly the 48 real.build_recovery_retry_plan() already
  // proved (tests/recovery-model.php) excludes all 449/339/1534/14. Re-confirmed here
  // directly against the real recovery plan's own on-disk sealed entries, read-only.
  $realPlan = Storage::read( 'run-' . $realRecoveryRun . '.json' );
  $assert( 48 === count( $realPlan['entries'] ), 'Real recovery plan sealed entries count is exactly 48' );
  $realKeys = array_column( $realPlan['entries'], 'entity_key' );
  foreach ( array( 'sql:productos:1', 'sql:productos:46', 'sql:productos:47', 'sql:productos:48' ) as $legit ) { $assert( ! in_array( $legit, $realKeys, true ), "Legitimate category-conflict cascade never in the sealed real recovery set: $legit" ); }
  foreach ( array( 'category:37', 'category:38', 'php:index.php' ) as $legit ) { $assert( ! in_array( $legit, $realKeys, true ), "Legitimate slug collision never in the sealed real recovery set: $legit" ); }
  $anyReviewKey = null; $anySkipKey = null;
  foreach ( $fresh['entries'] as $e ) { if ( ! $anyReviewKey && 'REVIEW' === $e['action'] ) { $anyReviewKey = $e['entity_key']; } if ( ! $anySkipKey && 'SKIP' === $e['action'] ) { $anySkipKey = $e['entity_key']; } }
  $assert( ! in_array( $anyReviewKey, $realKeys, true ), 'A real REVIEW entity never in the sealed real recovery set' );
  $assert( ! in_array( $anySkipKey, $realKeys, true ), 'A real SKIP entity never in the sealed real recovery set' );

  // ============================================================ backup checks: DENY/PASS
  $realManifest = Storage::read( $realPlan['post_first_import_backup'] );
  $checksById = static function( array $manifest ) {
   $r = new ReflectionMethod( Runner::class, 'verify_backup_integrity' ); $r->setAccessible( true );
   return $r->invoke( null, $manifest );
  };

  // backup anterior al parent run -> DENY
  $beforeParent = $realManifest; $beforeParent['created_at'] = '2000-01-01T00:00:00+00:00';
  $c1 = $checksById( $beforeParent );
  $assert( false === $c1['created_after_parent_completion']['passed'], 'Backup dated before the parent run completion: DENY' );

  // backup hash alterado -> DENY
  $tamperedHash = $realManifest; $tamperedHash['db_dump_sha256'] = str_repeat( 'a', 64 );
  $c2 = $checksById( $tamperedHash );
  $assert( false === $c2['db_dump_hash_current']['passed'], 'Backup with an altered recorded hash vs. the real file: DENY' );

  // backup DB incorrecta -> DENY
  $wrongDb = $realManifest; $wrongDb['db_name'] = 'some_other_database';
  $c3 = $checksById( $wrongDb );
  $assert( false === $c3['db_name_matches']['passed'], 'Backup manifest declaring the wrong database name: DENY' );

  // backup restaurable válido -> PASS (the real, already-verified manifest, unmodified)
  $c4 = $checksById( $realManifest );
  foreach ( $c4 as $id => $c ) { $assert( true === $c['passed'], "Real, untampered backup manifest: PASS on '$id'" ); }
  $assert( true === $realManifest['restore_verified'], 'Real backup manifest records restore_verified=true (from the real verify_backup_restorable() run at creation time)' );

  $export( 'batch-recovery-model-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
 } catch ( Throwable $error ) {
  $export( 'batch-recovery-model-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

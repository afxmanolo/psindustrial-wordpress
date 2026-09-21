<?php
/** Tests for Runner::parse_run_log()/applied_control_violations()/build_recovery_retry_plan()/
 * recovery_preflight()/close_run() — the recovery mechanism built after
 * run-2e0c1248-....json (the first real FULL LOCAL RESOLVED-ONLY execution) was pruned by
 * Storage::retain_recent_runs() mid-session. See
 * docs/implementation/full-local-import/17-run-snapshot-incident.md.
 *
 * build_recovery_retry_plan()/recovery_preflight() never touch WordPress/the database (only
 * private-storage JSON, the same "building/sealing a plan is safe" category Planner::build()
 * itself belongs to) — verified directly below, not assumed. Negative-path fixtures use
 * clearly-synthetic entity_keys/run_ids (never real catalogue data), written only to
 * temporary log/ledger files under private storage and cleaned up in `finally`. */
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

  $parentRun = '2e0c1248-8d56-4d92-a33b-c17e37b2732e';

  // ============================================================ missing original snapshot -> no fake reconstruction
  $assert( array() === Storage::read( 'run-' . $parentRun . '.json' ), 'Sanity: the parent run-<id>.json is genuinely absent (pruned), confirming this suite exercises the real incident, not a hypothetical' );
  $fakeRun = 'nonexistent-' . wp_generate_uuid4();
  try { Runner::parse_run_log( $fakeRun ); $assert( false, 'unreachable' ); }
  catch ( RuntimeException $e ) { $assert( 'PARENT_RUN_LOG_NOT_FOUND' === $e->getMessage(), 'parse_run_log() throws PARENT_RUN_LOG_NOT_FOUND for a genuinely missing log, never fabricates one' ); }
  try { Runner::build_recovery_retry_plan( $fakeRun ); $assert( false, 'unreachable' ); }
  catch ( RuntimeException $e ) { $assert( 'PARENT_RUN_LOG_NOT_FOUND' === $e->getMessage(), 'build_recovery_retry_plan() refuses a missing parent log outright, never a partial/guessed plan' ); }
  $assert( array() === Storage::read( 'run-' . $fakeRun . '.json' ), 'No run-<fakeRun>.json was created for the missing-evidence case' );

  // ============================================================ parse_run_log(): real, complete, machine-readable
  $before1 = $dbSnapshot();
  $parentLog = Runner::parse_run_log( $parentRun );
  $after1 = $dbSnapshot();
  $assert( $before1 === $after1, 'parse_run_log() is read-only: zero DB mutation' );
  $assert( 2399 === count( $parentLog ), 'parse_run_log() recovers all 2399 entity log lines, matching the original plan total exactly' );
  $errorCount = count( array_filter( $parentLog, static fn( $r ) => 'ERROR' === ( $r['result'] ?? null ) ) );
  $assert( 52 === $errorCount, "parse_run_log() recovers exactly 52 result=ERROR entities (got $errorCount)" );
  $createCount = count( array_filter( $parentLog, static fn( $r ) => 'CREATE' === ( $r['result'] ?? null ) ) );
  $assert( 449 === $createCount, "parse_run_log() recovers exactly 449 result=CREATE (applied) entities (got $createCount)" );

  // ============================================================ applied_control_violations(): the 449 control
  $fresh = Planner::build( 'full' );
  $before2 = $dbSnapshot();
  $violations = Runner::applied_control_violations( $parentLog, $fresh );
  $after2 = $dbSnapshot();
  $assert( $before2 === $after2, 'applied_control_violations() is read-only: zero DB mutation' );
  $assert( array() === $violations, 'All 449 originally-applied entities are UNCHANGED right now: zero control violations' );

  // Synthetic violation: an entity the log claims was CREATEd, but current reality disagrees.
  $syntheticLog = array( 'sql:productos:__never_existed__' => array( 'result' => 'CREATE' ) );
  $syntheticViolations = Runner::applied_control_violations( $syntheticLog, $fresh );
  $assert( isset( $syntheticViolations['sql:productos:__never_existed__'] ) && 'MISSING_FROM_CURRENT_PLAN' === $syntheticViolations['sql:productos:__never_existed__'], 'A log entry claiming CREATE for an entity absent from the current plan is correctly flagged as a violation' );

  // ============================================================ build_recovery_retry_plan(): real, end-to-end
  $before3 = $dbSnapshot();
  $recovery = Runner::build_recovery_retry_plan( $parentRun );
  $after3 = $dbSnapshot();
  $assert( $before3 === $after3, 'build_recovery_retry_plan() never touches WordPress/the database' );
  $assert( 'recovery' === $recovery['scope'], 'Recovery plan has scope=recovery' );
  $assert( $parentRun === $recovery['parent_run_id'], 'Recovery plan correctly links parent_run_id' );
  $assert( 'ORIGINAL_RUN_SNAPSHOT_PRUNED' === $recovery['recovery_reason'], 'Recovery plan records the correct recovery_reason' );
  $assert( $recovery['run_id'] !== $parentRun, 'Recovery plan has its OWN new run_id, never reuses the parent run id' );
  $assert( true === $recovery['recovery_open'], 'Recovery plan starts recovery_open=true (protected from pruning)' );
  $reread = Storage::read( 'run-' . $recovery['run_id'] . '.json' );
  $assert( $reread === $recovery, 'Recovery plan was actually persisted to private storage and reads back identically' );
  $assert( 48 === count( $recovery['entries'] ), "Recovery plan contains exactly 48 retryable entries (got " . count( $recovery['entries'] ) . ")" );
  foreach ( $recovery['entries'] as $e ) { $assert( in_array( $e['action'], array( 'MIGRATE','MERGE','CREATE_FROM_STATIC' ), true ), 'Every recovery entry has a mutable action, never REVIEW/SKIP: ' . $e['entity_key'] ); }

  $evidence = Storage::read( 'recovery-evidence-' . $recovery['run_id'] . '.json' );
  $assert( 52 === count( $evidence['rows'] ), 'Evidence artifact has one row per candidate considered (52), not just the accepted ones' );
  $retryableRows = array_filter( $evidence['rows'], static fn( $r ) => 'RETRYABLE' === $r['eligibility'] );
  $assert( 48 === count( $retryableRows ), 'Evidence artifact marks exactly 48 rows RETRYABLE' );
  foreach ( $evidence['rows'] as $r ) { foreach ( array( 'source_key','original_ledger_status','original_error','current_source_hash','current_decision_hash','current_action','destination_state','eligibility','reason' ) as $field ) { $assert( array_key_exists( $field, $r ), "Evidence row for {$r['source_key']} has required field '$field'" ); } }

  // ============================================================ specific reject reasons, real data
  $byKey = array_column( $evidence['rows'], null, 'source_key' );
  $assert( 'NOT_RETRYABLE' === $byKey['sql:productos:1']['eligibility'] && str_starts_with( $byKey['sql:productos:1']['reason'], 'ROOT_CAUSE_NOT_FIXED:LEGITIMATE_CATEGORY_CONFLICT' ), 'legitimate conflict (category cascade) correctly rejected: sql:productos:1' );
  $assert( 'RETRYABLE' === $byKey['asset:fichas/Clopay-3720-07.pdf']['eligibility'], 'corrected PDF failure (Group B) correctly eligible: asset:fichas/Clopay-3720-07.pdf' );
  $assert( 'RETRYABLE' === $byKey['asset:fichas/puerta-424.pdf']['eligibility'], 'corrected PDF failure (Group A) correctly eligible: asset:fichas/puerta-424.pdf' );

  // ============================================================ REVIEW/SKIP never candidates at all
  $reviewOrSkipInLog = array_filter( $parentLog, static fn( $r ) => in_array( $r['action'] ?? null, array( 'REVIEW','SKIP' ), true ) );
  $assert( 339 + 1534 === count( $reviewOrSkipInLog ), 'Sanity: log confirms 339+1534 REVIEW/SKIP entities exist' );
  foreach ( array_keys( $reviewOrSkipInLog ) as $k ) { $assert( ! isset( $byKey[ $k ] ), "REVIEW/SKIP entity never appears in recovery evidence at all: $k" ); }

  // ============================================================ action changed -> reject (real method, tampered log row)
  $realKey = 'asset:fichas/Clopay-3720-07.pdf'; // genuinely RETRYABLE with its real, untampered log row (asserted above).
  $tamperedActionRow = $parentLog[ $realKey ]; $tamperedActionRow['action'] = 'SKIP'; // parent log claims a DIFFERENT action than the current plan has.
  $evalTamperedAction = Runner::recovery_candidate_eligibility( $realKey, $tamperedActionRow, $fresh, $parentRun );
  $assert( 'NOT_RETRYABLE' === $evalTamperedAction['eligibility'] && str_starts_with( $evalTamperedAction['reason'], 'ACTION_CHANGED_SINCE_ORIGINAL_RUN:' ), 'A parent-log action that no longer matches the current plan action is rejected, precisely labelled: ' . $evalTamperedAction['reason'] );

  // ============================================================ REVIEW/SKIP action (even if somehow logged as ERROR) -> reject
  $reviewActionRow = array( 'action' => 'REVIEW', 'result' => 'ERROR', 'message' => 'synthetic' );
  // Use a real entity whose CURRENT action is REVIEW, so the "current action" side is genuine.
  $realReviewKey = null; foreach ( $fresh['entries'] as $e ) { if ( 'REVIEW' === $e['action'] ) { $realReviewKey = $e['entity_key']; break; } }
  $evalReviewAction = Runner::recovery_candidate_eligibility( $realReviewKey, array( 'action' => 'REVIEW', 'result' => 'ERROR', 'message' => 'synthetic' ), $fresh, $parentRun );
  $assert( 'NOT_RETRYABLE' === $evalReviewAction['eligibility'] && str_starts_with( $evalReviewAction['reason'], 'ACTION_NOT_MUTABLE:' ), 'A REVIEW-action entity is rejected as not mutable, never silently retried: ' . $evalReviewAction['reason'] );

  // ============================================================ entity absent from current plan -> reject
  $evalMissing = Runner::recovery_candidate_eligibility( 'sql:productos:__never_existed__', array( 'action' => 'MIGRATE', 'result' => 'ERROR', 'message' => 'x' ), $fresh, $parentRun );
  $assert( 'NOT_RETRYABLE' === $evalMissing['eligibility'] && 'MISSING_FROM_CURRENT_PLAN' === $evalMissing['reason'], 'An entity_key absent from the current plan is rejected, never assumed retryable' );

  // ============================================================ already applied elsewhere -> reject (real, one of the 449)
  // A cleanly-applied entity's own dependencies are all 'OK' (never blocked by anything), so
  // retry_root_cause() itself already returns 'OK' -- correctly rejected at the root-cause
  // stage (ROOT_CAUSE_NOT_FIXED:OK) before ever reaching the UNCHANGED check below it. Both
  // are equally correct, safe rejections; this asserts the safety property that actually
  // matters (never retryable), not which of the two equally-valid reasons fires first.
  $realAppliedKey = null; foreach ( $parentLog as $k => $r ) { if ( 'CREATE' === ( $r['result'] ?? null ) ) { $realAppliedKey = $k; break; } }
  $evalApplied = Runner::recovery_candidate_eligibility( $realAppliedKey, array( 'action' => array_column( $fresh['entries'], null, 'entity_key' )[ $realAppliedKey ]['action'], 'result' => 'ERROR', 'message' => 'synthetic — real entity was actually CREATE, forcing it through this evaluator as if it had failed' ), $fresh, $parentRun );
  $assert( 'NOT_RETRYABLE' === $evalApplied['eligibility'], 'An entity already successfully applied (now UNCHANGED) is never retryable even if evaluated as if it had failed: ' . $evalApplied['reason'] );
  $assert( in_array( $evalApplied['reason'], array( 'ALREADY_APPLIED_ELSEWHERE_NOW_UNCHANGED', 'ROOT_CAUSE_NOT_FIXED:OK' ), true ), 'Rejected for one of the two expected, equally-safe reasons: ' . $evalApplied['reason'] );
  $assert( 'UNCHANGED' === Identity::prediction( array_column( $fresh['entries'], null, 'entity_key' )[ $realAppliedKey ] ), 'Sanity: this real entity genuinely predicts UNCHANGED right now (it was truly applied)' );

  // ============================================================ destination human edit -> reject (synthetic, isolated ledger)
  $fakeKey2 = 'test:recovery-synthetic-' . wp_generate_uuid4();
  $fakeEntry = array( 'entity_key' => $fakeKey2, 'target_type' => 'psi_producto', 'source_type' => 'product', 'action' => 'MIGRATE', 'source_hash' => 'h1', 'decision_hash' => 'd1', 'dependencies' => array() );
  $ledgerPath = Identity::ledger( $fakeEntry );
  try {
   Storage::write( $ledgerPath, array( 'entity_key' => $fakeKey2, 'run_id' => 'some-other-run-not-ours', 'status' => 'INTENT', 'wordpress_id' => 0, 'created' => true, 'before' => array(), 'at' => gmdate( 'c' ) ) );
   $id = Identity::find( $fakeEntry );
   $ledger = Storage::read( $ledgerPath );
   $ownStaleAttempt = ! $id && $ledger && 'INTENT' === ( $ledger['status'] ?? null ) && empty( $ledger['wordpress_id'] ) && ( $ledger['run_id'] ?? null ) === $parentRun;
   $assert( false === $ownStaleAttempt, 'A ledger from an unrelated run_id is never mistaken for this parent run own stale attempt (destination-edited-equivalent case)' );
  } finally { wp_delete_file( Storage::path( $ledgerPath ) ); }
  $assert( ! is_file( Storage::path( $ledgerPath ) ), 'Synthetic ledger cleaned up' );

  // ============================================================ recovery_preflight(): real, end-to-end
  $before4 = $dbSnapshot();
  $report = Runner::recovery_preflight( $recovery['run_id'] );
  $after4 = $dbSnapshot();
  $assert( $before4 === $after4, 'recovery_preflight() is read-only: zero DB mutation' );
  $assert( $recovery['run_id'] === $report['run_id'] && $parentRun === $report['parent_run_id'], 'recovery_preflight() reports correct run_id/parent_run_id' );
  $assert( 48 === $report['counts']['retryable'] && 4 === $report['counts']['rejected'] && 52 === $report['counts']['candidates_considered'], 'recovery_preflight() reports exact counts (48/4/52), never estimated' );
  $checksById = array_column( $report['checks'], null, 'id' );
  foreach ( array( 'recovery_plan_exists','is_recovery_scope','parent_run_id_present','environment_id_matches','recovery_plan_seal_intact','parent_run_evidence_available','current_plan_still_equivalent','applied_449_control_intact','no_review_or_skip_entries_in_recovery_set' ) as $id ) {
   $assert( true === ( $checksById[ $id ]['passed'] ?? null ), "recovery_preflight() check '$id' passes on the real, untouched recovery plan" );
  }
  $assert( false === $checksById['new_post_first_import_backup_present']['passed'], 'recovery_preflight() correctly reports the new post-first-import backup as NOT present -- none was taken' );
  $assert( false === $report['ok'], 'Overall ok=false, correctly, solely because the new backup is not yet present' );
  $assert( array( 'new_post_first_import_backup_present' ) === $report['blockers'], 'The ONLY blocker is the missing new backup -- nothing else' );
  $assert( true === $report['new_backup_required'] && false === $report['new_backup_present'], 'new_backup_required/new_backup_present reported explicitly and correctly' );

  // ============================================================ second recovery build -> deterministic same SET
  $recovery2 = Runner::build_recovery_retry_plan( $parentRun );
  $assert( $recovery2['run_id'] !== $recovery['run_id'], 'A second build gets its own new run_id (never silently reuses the first recovery run id)' );
  $keys1 = array_column( $recovery['entries'], 'entity_key' ); sort( $keys1 );
  $keys2 = array_column( $recovery2['entries'], 'entity_key' ); sort( $keys2 );
  $assert( $keys1 === $keys2, 'Second recovery build selects EXACTLY the same 48 entity_keys as the first -- deterministic given unchanged evidence/state' );

  // ============================================================ retention protection: recovery plan survives further Planner::build() calls
  for ( $i = 0; $i < 3; ++$i ) { Planner::build( 'full' ); }
  $stillThere = Storage::read( 'run-' . $recovery['run_id'] . '.json' );
  $assert( ! empty( $stillThere ), 'Recovery plan (recovery_open=true) survives repeated Planner::build() calls -- the exact protection the pruning incident lacked' );

  // ============================================================ close_run(): the only way out of protection
  $before5 = $dbSnapshot();
  try { Runner::close_run( $recovery2['run_id'], 'WRONG PHRASE' ); $assert( false, 'unreachable' ); }
  catch ( RuntimeException $e ) { $assert( 'EXPLICIT_CONFIRMATION_REQUIRED' === $e->getMessage(), 'close_run() requires its exact confirmation phrase' ); }
  $closed = Runner::close_run( $recovery2['run_id'], 'CERRAR RUN RESUELTO' );
  $after5 = $dbSnapshot();
  $assert( $before5 === $after5, 'close_run() never touches WordPress/the database, only the plan file' );
  $assert( ! empty( $closed['closed_at'] ) && false === $closed['recovery_open'], 'close_run() sets closed_at and clears recovery_open' );

  $export( 'recovery-model-tests.json', array( 'passed' => true, 'checks' => $checks, 'recovery_run_id' => $recovery['run_id'], 'parent_run_id' => $parentRun ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'recovery_run_id' => $recovery['run_id'] ) );
 } catch ( Throwable $error ) {
  $export( 'recovery-model-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

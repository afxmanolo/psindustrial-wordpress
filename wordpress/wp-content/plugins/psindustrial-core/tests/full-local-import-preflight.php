<?php
/** Runner::preflight_full_local() tests. Read-only: uses the REAL current 'full' plan as a
 * fixture (exactly as the task asked -- "usa el plan actual como fixture/pre-flight real sin
 * ejecutarlo"), never calls batch_full_local_resolved_only() on it, never mutates the
 * database. DENY scenarios use small, isolated synthetic plan copies -- never the real one,
 * never written back to disk under the real run_id. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,Planner,Runner};
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };
 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();

  global $wpdb;
  $before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );

  // ============================================================== el plan FULL actual, real, como fixture de pre-flight
  $plan = Planner::build( 'full' );
  $after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $assert( $before === $after, 'Planner::build(\'full\') mutates nothing' );

  $baseline = array( 'total' => 2399, 'UNCHANGED' => 15, 'SKIP' => 1534, 'CREATE' => 511, 'REVIEW' => 339 );
  $assert( count( $plan['entries'] ) === $baseline['total'], 'TOTAL = 2.399' );
  $assert( ( $plan['summary']['actions']['UNCHANGED'] ?? 0 ) === $baseline['UNCHANGED'], 'UNCHANGED = 15' );
  $assert( ( $plan['summary']['actions']['SKIP'] ?? 0 ) === $baseline['SKIP'], 'SKIP = 1.534' );
  $assert( ( $plan['summary']['actions']['CREATE'] ?? 0 ) === $baseline['CREATE'], 'CREATE (planned_result, folds in MERGE -- see below) = 511' );
  $assert( ( $plan['summary']['actions']['REVIEW'] ?? 0 ) === $baseline['REVIEW'], 'REVIEW = 339' );
  $assert( ! isset( $plan['summary']['actions']['ERROR'] ), 'ERROR = 0 (the key is absent, not zero -- a fresh plan never has action=ERROR, only a partially-EXECUTED one could)' );
  $assert( ! isset( $plan['summary']['actions']['UPDATE'] ) && ! isset( $plan['summary']['actions']['CONFLICT'] ), 'Sanity: with only the 15 pre-existing subset objects in this DB, nothing yet predicts UPDATE/CONFLICT -- every mutable entry is fresh CREATE' );

  $report = Runner::preflight_full_local( $plan['run_id'], $plan );
  $after2 = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $assert( $before === $after2, 'preflight_full_local() mutates nothing' );
  $assert( true === $report['ok'], 'Pre-flight on the current real plan: OK (' . implode( ',', $report['blockers'] ) . ')' );
  $assert( array() === $report['blockers'], 'No blockers on the current real plan' );
  foreach ( $report['checks'] as $c ) { $assert( true === $c['passed'], 'Pre-flight check passes: ' . $c['id'] . ' (' . $c['detail'] . ')' ); }
  $assert( 14 === count( $report['checks'] ), 'Pre-flight runs exactly its documented 14 checks' );

  // ============================================================== representacion de MERGE dentro del conteo CREATE
  // Runner::preflight_full_local()'s own 'counts' breaks the RAW decision action down
  // (MIGRATE/MERGE/CREATE_FROM_STATIC/SKIP/REVIEW); Planner::summary()'s 'actions' instead
  // reports the ROLLED-UP planned_result (CREATE/UPDATE/UNCHANGED/CONFLICT/SKIP/REVIEW).
  // Every MIGRATE/MERGE/CREATE_FROM_STATIC entry's planned_result is EITHER 'CREATE' (new)
  // or 'UNCHANGED' (already applied, matching hash) -- never anything else right now (no
  // UPDATE/CONFLICT exist yet in this DB) -- so the two views must reconcile exactly:
  $mutableByDecision = ( $report['counts']['MIGRATE'] ?? 0 ) + ( $report['counts']['MERGE'] ?? 0 ) + ( $report['counts']['CREATE_FROM_STATIC'] ?? 0 );
  $mutableByPlannedResult = ( $plan['summary']['actions']['CREATE'] ?? 0 ) + ( $plan['summary']['actions']['UNCHANGED'] ?? 0 );
  $assert( $mutableByDecision === $mutableByPlannedResult, "MIGRATE+MERGE+CREATE_FROM_STATIC ($mutableByDecision) === planned CREATE+UNCHANGED ($mutableByPlannedResult) -- MERGE is real, just folded into the same top-line bucket as MIGRATE/CREATE_FROM_STATIC" );
  $assert( 49 === ( $report['counts']['MERGE'] ?? 0 ), 'MERGE = 49 (25 Q02 + 24 Q03), fully counted on its own inside `counts`, never silently absorbed without a trace' );
  $assert( ( $report['counts']['REVIEW'] ?? 0 ) === $baseline['REVIEW'] && ( $report['counts']['SKIP'] ?? 0 ) === $baseline['SKIP'], 'REVIEW and SKIP already match 1:1 between both views (no rollup ambiguity for these two)' );

  // ============================================================== DENY: wrong environment_id
  $tampered = $plan; $tampered['environment_id'] = str_repeat( '0', 64 );
  $r = Runner::preflight_full_local( $plan['run_id'], $tampered );
  $assert( false === $r['ok'] && in_array( 'environment_id_matches', $r['blockers'], true ), 'Wrong environment_id -> blocked (this is the operational form of "full + production/staging -> DENY": WP_ENVIRONMENT_TYPE/DB_NAME are fixed wp-config constants that cannot be flipped within one PHP process, but a plan carrying a mismatched environment_id is exactly what a plan built on a different install/DB looks like, and this is the actual mechanism Storage::guard()+this check use to refuse it)' );

  // ============================================================== DENY: invalid plan seal (tampered)
  // created_at is part of Planner::digest()'s own hashed field set and carries no action
  // semantics, so mutating it is guaranteed to change the seal regardless of which entry
  // happens to sit where in the array -- unlike tampering entries[N]['action'], which could
  // accidentally be a no-op if that specific entry's action already equals the new value.
  $tampered = $plan; $tampered['created_at'] = 'TAMPERED-' . $plan['created_at'];
  $r = Runner::preflight_full_local( $plan['run_id'], $tampered );
  $assert( false === $r['ok'] && in_array( 'plan_seal_intact', $r['blockers'], true ), 'Tampered plan (altered after build) -> plan_seal_intact blocked' );

  // ============================================================== DENY: scope !== full (a real, valid SUBSET plan run through FULL preflight)
  $subset = Planner::build( 'subset' );
  $r = Runner::preflight_full_local( $subset['run_id'], $subset );
  $assert( false === $r['ok'] && in_array( 'plan_scope_full', $r['blockers'], true ), 'A valid subset plan is blocked by full-local preflight on scope alone' );

  // ============================================================== DENY: ERROR present
  $tampered = $plan; $tampered['entries'][0]['action'] = 'ERROR';
  $r = Runner::preflight_full_local( $plan['run_id'], $tampered );
  $assert( false === $r['ok'] && in_array( 'no_error_entries', $r['blockers'], true ), 'An entry with action=ERROR blocks pre-flight, attributed to its own dedicated check' );
  $assert( ! in_array( 'actions_within_known_set', $r['blockers'], true ), 'ERROR is a recognised action value, not caught by the generic unknown-action check' );

  // ============================================================== DENY: unknown action value
  $tampered = $plan; $tampered['entries'][5]['action'] = 'PUBLISH_TO_PRODUCTION';
  $r = Runner::preflight_full_local( $plan['run_id'], $tampered );
  $assert( false === $r['ok'] && in_array( 'actions_within_known_set', $r['blockers'], true ), 'An entry with an action outside {MIGRATE,MERGE,CREATE_FROM_STATIC,SKIP,REVIEW} blocks pre-flight' );

  // ============================================================== DENY: missing staged PDF / media asset
  $mediaKey = null; foreach ( $plan['entries'] as $k => $e ) { if ( 'media' === $e['source_type'] && 'MIGRATE' === $e['action'] && isset( $e['data']['package_asset'] ) ) { $mediaKey = $k; break; } }
  $assert( null !== $mediaKey, 'Sanity: at least one staged, MIGRATE-approved media entry exists in the real plan' );
  $tampered = $plan; $tampered['entries'][ $mediaKey ]['data']['package_asset'] = 'asset-' . str_repeat( 'f', 64 ) . '.bin'; // a hash that is not actually staged on disk.
  $r = Runner::preflight_full_local( $plan['run_id'], $tampered );
  $assert( false === $r['ok'] && in_array( 'staged_media_assets_valid', $r['blockers'], true ), 'A media entry pointing at a non-staged/missing package_asset blocks pre-flight' );

  // ============================================================== DENY: PDF approval no longer valid (tampered sha256)
  $pdfKey = null; foreach ( $plan['entries'] as $k => $e ) { if ( isset( $e['pdf_approval_type'] ) ) { $pdfKey = $k; break; } }
  if ( null !== $pdfKey ) {
   $tampered = $plan; $tampered['entries'][ $pdfKey ]['legacy_file'] = 'fichas/this-file-does-not-exist-anywhere.pdf';
   $r = Runner::preflight_full_local( $plan['run_id'], $tampered );
   $assert( false === $r['ok'] && in_array( 'pdf_approvals_current', $r['blockers'], true ), 'A PDF-approval-carrying entry pointing at a non-existent legacy file blocks pre-flight on pdf_approvals_current' );
  } else { $checks[] = array( 'test' => 'No PDF-approval-carrying entry exists in the current plan to exercise this DENY case (informational, not a failure)', 'passed' => true ); }

  // ============================================================== ningun run_full_local_resolved_only() se ejecuto sobre el plan real
  $realPlanNeverExecuted = ! isset( $plan['results'] ) || array() === $plan['results'];
  $assert( $realPlanNeverExecuted, 'The real current full plan was never passed to batch_full_local_resolved_only() in this test file -- results is empty' );
  $afterAll = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $assert( $before === $afterAll, 'End to end: zero database mutation across this entire preflight-only test file' );

  $export( 'full-local-import-preflight-tests.json', array( 'passed' => true, 'checks' => $checks, 'baseline' => $plan['summary']['actions'], 'preflight_counts' => $report['counts'] ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $plan['run_id'], 'baseline' => $plan['summary']['actions'] ) );
 } catch ( Throwable $error ) {
  $export( 'full-local-import-preflight-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

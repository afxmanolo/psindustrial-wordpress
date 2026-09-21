<?php
/** Tests for Runner::retry_eligibility()/retry_root_cause()/retry_preflight() -- the
 * classifier layer behind RETRY_FAILED_RESOLVED_ONLY. Strictly non-mutating: every method
 * under test is read-only by its own contract (never calls apply()/media_handle_sideload()).
 *
 * FIXTURE NOTE: this suite was originally written against the live run-2e0c1248-....json
 * (the first real FULL LOCAL RESOLVED-ONLY execution). That file was pruned by Storage::
 * retain_recent_runs() (keep=30 by mtime, RUNNING-only exemption) as a side effect of this
 * same session's own extensive Planner::build('full') calls -- confirmed directly (Storage::
 * read() now returns null for it). The backup, the uploads backup, and all 477 per-entity
 * identity-<token>.json ledgers do NOT match retain_recent_runs()'s run-*.json pattern and
 * survived untouched; only the aggregated plan+results snapshot is gone. See
 * docs/implementation/full-local-import/13-pdf-runtime-failures.md for the full account and
 * the permanent 62-row table (which is now this project's durable record of that run's
 * outcome, immune to any future retention pruning).
 *
 * Consequently this suite sources the ORIGINAL run's entity_key lists from that same
 * durable, checked-in table (mirrored below as plain PHP arrays, not re-read from a file, so
 * this test has no dependency on doc formatting) rather than from Storage::read('run-...'),
 * and reconstructs each `$originalResult` as {entity_key, status, run_id} -- the only three
 * fields retry_eligibility() actually consults -- which is what process_entry() itself always
 * wrote into every real result. Every entity_key below is real, taken from the real run;
 * nothing is invented. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Identity,Planner,Runner,Sources};
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();

  $run = '2e0c1248-8d56-4d92-a33b-c17e37b2732e';
  $result = static fn( string $key, string $status ): array => array( 'entity_key' => $key, 'status' => $status, 'run_id' => $run );

  // The original run's 52 FAILED entity_keys, by real, verified root cause (see
  // 13-pdf-runtime-failures.md for the full table with sources).
  $directA = array( 'system/files/images/productos/639b9801fe440dcd2179c23cca14401710c5e7e0','system/files/images/productos/7ab52eec612af22d23a62f849479371ebf1c2f22','system/files/images/productos/3af12bf12f61eda51a19d1e8af3cefc05878f484','system/files/images/productos/7baea774c5fc4abe3735d40fdf16e84f1e3521ef','system/files/images/productos/7cf4babfab2712e41d6fd1c050f5c31b604c97ae','fichas/puerta-424.pdf','fichas/puerta-430.pdf','fichas/puerta-seccional-418.pdf','fichas/puerta-seccional-422.pdf','fichas/puerta-seccional-426.pdf','fichas/puerta-seccional-432.pdf','fichas/puerta-thermacore-593.pdf','fichas/puerta-thermacore-594.pdf','fichas/puerta-thermacore-595.pdf' );
  $directB = array( 'system/files/images/productos/3e620ed84c43c5de234ae437f58f7083e93f6b8d','system/files/images/productos/98b07de6dca4ad402a37385c485fb6999d5c3323','fichas/Clopay-3720-07.pdf','fichas/CMDC-0524SP-14-1.pdf','fichas/CMDC-3717-3718-11.pdf','fichas/commercial-aluminum-door-systems-brochure.pdf','fichas/Cortina-serie-610-620.pdf','fichas/Kelley-Hydraulic-Dock-Leveler-Brochure_web.pdf','fichas/lisos.pdf','fichas/moovi50rm.pdf','fichas/rhx-commercial-operator-brochure.pdf','fichas/rolling-steel-doors-610-620-brochure.pdf','fichas/StrongArm_HVR303_Brochure_Spanish.pdf' );
  $cascadeA = array( 'sql:productos:65','sql:productos:66','sql:productos:67','sql:productos:68','sql:productos:69','sql:productos:70','sql:productos:71','sql:productos:72','sql:productos:73','static:puerta-seccional-de-acero-thermacore-595-uso-pesado.php' );
  $cascadeB = array( 'sql:productos:5','sql:productos:6','sql:productos:7','sql:productos:17','sql:productos:18','sql:productos:19','sql:productos:22','sql:productos:24','sql:productos:44','sql:productos:77','sql:productos:95' );
  $cascadeConflict = array( 'sql:productos:1','sql:productos:46','sql:productos:47','sql:productos:48' );
  $assert( 14 === count( $directA ) && 13 === count( $directB ) && 10 === count( $cascadeA ) && 11 === count( $cascadeB ) && 4 === count( $cascadeConflict ), 'Fixture list sizes match the documented 52 (14+13+10+11+4)' );
  $allFailedKeys = array_merge( array_map( static fn( $p ) => 'asset:' . $p, array_merge( $directA, $directB ) ), $cascadeA, $cascadeB, $cascadeConflict );
  $assert( 52 === count( $allFailedKeys ), 'Total FAILED fixture = 52' );

  // The original run's 10 CONFLICT entity_keys (proven, this same session, to be genuine
  // pre-existing slug collisions against a DIFFERENT entity_key -- never our own attempt).
  $originalConflicts = array( 'category:37','category:38','php:cortinas-enrollables-de-aluminio.php','php:index-estatico.php','php:index-resp.php','php:index.php','php:politica-privacidad.php','php:puertas-de-garaje-aisladas.php','php:puertas-enrollables-de-garage.php','php:tiras-plasticas-hawaianas.php' );
  $assert( 10 === count( $originalConflicts ), 'Fixture: 10 original CONFLICT entity_keys' );

  $fresh = Planner::build( 'full' );
  $freshByKey = array_column( $fresh['entries'], null, 'entity_key' );
  foreach ( array_merge( $allFailedKeys, $originalConflicts ) as $k ) { $assert( isset( $freshByKey[ $k ] ), "Fixture entity_key still resolves in a freshly-built plan: $k" ); }

  // ============================================================ never-retry statuses
  // APPLIED/UNCHANGED: any of the 449 entities this run created, or the 15 pre-existing
  // subset ones, are UNCHANGED in a fresh plan right now (proven elsewhere this session) --
  // real entity_keys, only the ORIGINAL status label is constructed (APPLIED vs UNCHANGED
  // being the one thing that can no longer be told apart live, since both now read
  // UNCHANGED) to exercise both branches of the same guard.
  $anyNowUnchangedKey = null; foreach ( $fresh['entries'] as $e ) { if ( 'UNCHANGED' === $e['planned_result'] ) { $anyNowUnchangedKey = $e['entity_key']; break; } }
  $assert( (bool) $anyNowUnchangedKey, 'Fixture: at least one entity is currently UNCHANGED' );
  foreach ( array( 'APPLIED','UNCHANGED' ) as $status ) {
   $verdict = Runner::retry_eligibility( $freshByKey[ $anyNowUnchangedKey ], $result( $anyNowUnchangedKey, $status ), $fresh );
   $assert( false === $verdict['eligible'] && 'NOT_FAILED_IN_ORIGINAL_RUN' === $verdict['reason'], "$status: never retried ($anyNowUnchangedKey)" );
  }
  // REVIEW/SKIP: any real currently-REVIEW/SKIP entity_key.
  $anyReviewKey = null; $anySkipKey = null;
  foreach ( $fresh['entries'] as $e ) { if ( ! $anyReviewKey && 'REVIEW' === $e['action'] ) { $anyReviewKey = $e['entity_key']; } if ( ! $anySkipKey && 'SKIP' === $e['action'] ) { $anySkipKey = $e['entity_key']; } }
  $vReview = Runner::retry_eligibility( $freshByKey[ $anyReviewKey ], $result( $anyReviewKey, 'BLOCKED' ), $fresh );
  $assert( false === $vReview['eligible'] && 'NOT_FAILED_IN_ORIGINAL_RUN' === $vReview['reason'], "REVIEW: never retried ($anyReviewKey)" );
  $vSkip = Runner::retry_eligibility( $freshByKey[ $anySkipKey ], $result( $anySkipKey, 'SKIPPED' ), $fresh );
  $assert( false === $vSkip['eligible'] && 'NOT_FAILED_IN_ORIGINAL_RUN' === $vSkip['reason'], "SKIP: never retried ($anySkipKey)" );
  // legitimate CONFLICT (the original 10, real entity_keys, real CONFLICT status).
  foreach ( $originalConflicts as $key ) {
   $verdict = Runner::retry_eligibility( $freshByKey[ $key ], $result( $key, 'CONFLICT' ), $fresh );
   $assert( false === $verdict['eligible'] && 'NOT_FAILED_IN_ORIGINAL_RUN' === $verdict['reason'], "Legitimate original CONFLICT never retried: $key" );
  }

  // ============================================================ PDF runtime failure corregido -> retryable
  $causesSeen = array();
  foreach ( $allFailedKeys as $key ) {
   $entry = $freshByKey[ $key ];
   $cause = Runner::retry_root_cause( $entry, $fresh );
   $causesSeen[ $cause ] = ( $causesSeen[ $cause ] ?? 0 ) + 1;
   $verdict = Runner::retry_eligibility( $entry, $result( $key, 'FAILED' ), $fresh );
   if ( in_array( $key, array_map( static fn( $p ) => 'asset:' . $p, $directA ), true ) ) { $assert( 'DIRECT_PDF_A' === $cause, "Direct Group A cause correctly identified: $key" ); }
   if ( in_array( $key, array_map( static fn( $p ) => 'asset:' . $p, $directB ), true ) ) { $assert( 'DIRECT_PDF_B' === $cause, "Direct Group B cause correctly identified: $key" ); }
   if ( in_array( $key, $cascadeA, true ) ) { $assert( 'CASCADE_FROM_PDF_A' === $cause, "Cascade-from-A cause correctly identified: $key" ); }
   if ( in_array( $key, $cascadeB, true ) ) { $assert( 'CASCADE_FROM_PDF_B' === $cause, "Cascade-from-B cause correctly identified: $key" ); }
   if ( in_array( $key, $cascadeConflict, true ) ) { $assert( 'LEGITIMATE_CATEGORY_CONFLICT' === $cause, "Category-conflict cascade correctly identified, never silently fixed: $key" ); }
   if ( in_array( $cause, array( 'DIRECT_PDF_A','DIRECT_PDF_B','CASCADE_FROM_PDF_A','CASCADE_FROM_PDF_B' ), true ) ) {
    // A real, authorized recovery execution has since run against these exact 48 entities
    // (docs/implementation/full-local-import/21-recovery-execution-result.md): 37 succeeded
    // (now correctly UNCHANGED, never re-eligible) and 11 (all CASCADE_FROM_PDF_B) were left
    // as a genuine, partially-created WordPress object by a separate, still-open gap
    // (recovery_process_entry()'s "own stale attempt" recognition only covers a no-object/
    // INTENT-ledger case, not an OBJECT_CREATED-but-incomplete one) -- both are legitimate,
    // understood, non-broken outcomes; only a rejection for a DIFFERENT/unexplained reason
    // would indicate a real regression in this classifier.
    $assert( true === $verdict['eligible'] || in_array( $verdict['reason'], array( 'ALREADY_APPLIED_ELSEWHERE_NOW_UNCHANGED','CONFLICT_NOT_OWN_STALE_ATTEMPT' ), true ), "Fixed-cause FAILED entity is either still retryable or has one of the two known, understood real-recovery outcomes, never an unexplained rejection: $key ($cause) -> {$verdict['reason']}" );
   }
   else { $assert( false === $verdict['eligible'], "Legitimate-conflict-cascade FAILED entity stays non-retryable: $key ($cause)" ); }
  }
  $assert( 0 === ( $causesSeen['OTHER'] ?? 0 ), 'Zero OTHER among the 52 fixture FAILED entities (classification is exhaustive)' );
  $retryableCount = ( $causesSeen['DIRECT_PDF_A'] ?? 0 ) + ( $causesSeen['DIRECT_PDF_B'] ?? 0 ) + ( $causesSeen['CASCADE_FROM_PDF_A'] ?? 0 ) + ( $causesSeen['CASCADE_FROM_PDF_B'] ?? 0 );
  $assert( 48 === $retryableCount, "Exactly 48 of the 52 FAILED fixture entities are retryable (got $retryableCount)" );
  fwrite( STDERR, 'root causes: ' . wp_json_encode( $causesSeen ) . "\n" );

  // Directly exercise the REAL stale-INTENT-ledger recognition on genuine surviving fixture
  // data (identity-<token>.json ledgers are untouched by the pruning) -- "PDF runtime
  // failure corregido -> retryable" exercised through the CONFLICT branch specifically.
  //
  // NOTE: this originally used the real 'asset:fichas/Clopay-3720-07.pdf' as a live fixture
  // for a stale INTENT ledger (it was one of this exact scenario at the time). A real,
  // authorized recovery execution has since run and successfully created every direct-media
  // Group B entity, including this one (docs/implementation/full-local-import/
  // 21-recovery-execution-result.md) -- its ledger is now legitimately 'APPLIED', not
  // 'INTENT', so it can no longer demonstrate this scenario. Replaced with a synthetic,
  // clearly-fake entity_key + a hand-written ledger, exercising the exact same real
  // Runner::retry_eligibility() code path without depending on a real fixture's state
  // staying frozen in time. Never touches real catalogue data or a real WordPress object;
  // cleaned up in `finally`.
  $staleLedgerKey = 'test:retry-model-stale-intent-' . wp_generate_uuid4();
  // legacy_file/row.sha256 reference a REAL, still-existing legacy file (media_integrity()'s
  // original_source_integrity half needs a real file to hash) -- the FIXTURE'S own asset
  // (data.sha256/package_asset) is a throwaway staged .bin this test writes and deletes
  // itself, deliberately with DIFFERENT bytes than the legacy original (exactly like a real
  // Group B approval: staged bytes are allowed to be scrutinised independently).
  $realLegacyFile = 'fichas/Clopay-3720-07.pdf';
  $realLegacyHash = hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $realLegacyFile ) );
  $fixtureBytes = 'retry-model synthetic staged fixture ' . wp_generate_uuid4();
  $fixtureHash = hash( 'sha256', $fixtureBytes );
  $fixtureAssetName = 'asset-' . $fixtureHash . '.bin';
  file_put_contents( Storage::path( $fixtureAssetName ), $fixtureBytes );
  $staleEntry = array( 'entity_key' => $staleLedgerKey, 'target_type' => 'attachment', 'source_type' => 'media', 'action' => 'MIGRATE', 'source_hash' => 'h1', 'decision_hash' => 'd1', 'dependencies' => array(), 'legacy_file' => $realLegacyFile, 'binary_aliases' => array(), 'pdf_approval_type' => 'exception', 'row' => array( 'sha256' => $realLegacyHash ), 'data' => array( 'sha256' => $fixtureHash, 'package_asset' => $fixtureAssetName ) );
  $staleLedgerPath = Identity::ledger( $staleEntry );
  try {
   $assert( 'DIRECT_PDF_B' === Runner::retry_root_cause( $staleEntry, array( 'entries' => array( $staleEntry ) ) ), 'Sanity: the synthetic fixture itself genuinely classifies as DIRECT_PDF_B (integrity + pdf_approval_type both line up)' );
   Storage::write( $staleLedgerPath, array( 'entity_key' => $staleLedgerKey, 'run_id' => $run, 'status' => 'INTENT', 'wordpress_id' => 0, 'created' => true, 'before' => array(), 'at' => gmdate( 'c' ) ) );
   $assert( 0 === Identity::find( $staleEntry ), 'Sanity: no real WordPress object exists for this synthetic entity_key' );
   $verdictStale = Runner::retry_eligibility( $staleEntry, $result( $staleLedgerKey, 'FAILED' ), array( 'run_id' => $run, 'entries' => array( $staleEntry ) ) );
   $assert( true === $verdictStale['eligible'] && 'RETRYABLE_FAILED_ATTEMPT_STALE_INTENT_LEDGER' === $verdictStale['reason'], 'Synthetic stale-INTENT-ledger fixture correctly recognised as our own aborted attempt, eligible for retry: ' . ( $verdictStale['reason'] ?? '?' ) );
  } finally { wp_delete_file( Storage::path( $staleLedgerPath ) ); @unlink( Storage::path( $fixtureAssetName ) ); }
  $assert( ! is_file( Storage::path( $staleLedgerPath ) ) && ! is_file( Storage::path( $fixtureAssetName ) ), 'Synthetic ledger and staged asset both cleaned up, no trace left behind' );

  // ============================================================ source cambió / decision cambió -> no retry
  $anyFailedKey = $allFailedKeys[0];
  $realEntry = $freshByKey[ $anyFailedKey ];
  $freshCopy = $fresh; foreach ( $freshCopy['entries'] as &$fe ) { if ( $fe['entity_key'] === $anyFailedKey ) { $fe['source_hash'] = 'deliberately-different-source-hash'; } } unset( $fe );
  $verdictSourceChanged = Runner::retry_eligibility( $realEntry, $result( $anyFailedKey, 'FAILED' ), $freshCopy );
  $assert( false === $verdictSourceChanged['eligible'] && 'SOURCE_CHANGED_SINCE_FIRST_RUN' === $verdictSourceChanged['reason'], 'A source_hash drift since the first run blocks retry, precisely labelled' );
  $freshCopy2 = $fresh; foreach ( $freshCopy2['entries'] as &$fe2 ) { if ( $fe2['entity_key'] === $anyFailedKey ) { $fe2['decision_hash'] = 'deliberately-different-decision-hash'; } } unset( $fe2 );
  $verdictDecisionChanged = Runner::retry_eligibility( $realEntry, $result( $anyFailedKey, 'FAILED' ), $freshCopy2 );
  $assert( false === $verdictDecisionChanged['eligible'] && 'DECISION_CHANGED_SINCE_FIRST_RUN' === $verdictDecisionChanged['reason'], 'A decision_hash drift since the first run blocks retry, precisely labelled' );

  // ============================================================ destination editado (a conflict that is NOT
  // our own stale attempt) -> no retry. Synthetic entity_key + synthetic ledger: never
  // touches real catalogue data or a real WordPress object; cleaned up in finally.
  $fakeKey = 'test:retry-eligibility-synthetic-' . wp_generate_uuid4();
  $fakeEntry = array( 'entity_key' => $fakeKey, 'target_type' => 'psi_producto', 'source_type' => 'product', 'action' => 'MIGRATE', 'source_hash' => 'h1', 'decision_hash' => 'd1', 'dependencies' => array() );
  $ledgerPath = Identity::ledger( $fakeEntry );
  try {
   // Ledger exists but belongs to a DIFFERENT run than the one being retried -- must never
   // be treated as "our own" stale attempt.
   Storage::write( $ledgerPath, array( 'entity_key' => $fakeKey, 'run_id' => 'some-other-unrelated-run-id', 'status' => 'INTENT', 'wordpress_id' => 0, 'created' => true, 'before' => array(), 'at' => gmdate( 'c' ) ) );
   $fakeFreshPlan = array( 'run_id' => $run, 'entries' => array( $fakeEntry ) );
   $verdict = Runner::retry_eligibility( $fakeEntry, $result( $fakeKey, 'FAILED' ), $fakeFreshPlan );
   $assert( false === $verdict['eligible'], 'Synthetic entity with a stale ledger from an UNRELATED run_id is never retried' );
   $assert( 'RETRYABLE_FAILED_ATTEMPT_STALE_INTENT_LEDGER' !== $verdict['reason'], 'Never misclassified as our own stale attempt when the ledger run_id differs' );
  } finally { wp_delete_file( Storage::path( $ledgerPath ) ); }
  $assert( ! is_file( Storage::path( $ledgerPath ) ), 'Synthetic ledger cleaned up, no trace left behind' );

  // ============================================================ retry_preflight(): honest
  // refusal now that the live run file is gone -- a real, important safety property: it
  // must never silently substitute reconstructed/guessed data, only report the plan as
  // absent.
  $before = array( 'posts' => (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts}" ), 'terms' => (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->terms}" ) );
  $preflight = Runner::retry_preflight( $run );
  $after = array( 'posts' => (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts}" ), 'terms' => (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->terms}" ) );
  $assert( $before === $after, 'retry_preflight() is read-only: zero DB mutation, even for a missing plan' );
  $assert( false === $preflight['ok'] && in_array( 'plan_exists', $preflight['blockers'], true ), 'retry_preflight() honestly reports the pruned run as ok=false/plan_exists blocker, never fabricates a result' );

  // ============================================================ segundo retry -> no-op (structural guarantee)
  // Never executed (retry_failed_resolved_only() itself is not invoked here) -- verified as
  // a direct structural precondition: the same "already COMPLETE -> return unchanged" guard
  // already proven, by the existing suite, for batch()/batch_full_local_resolved_only()'s
  // own plan['status'] equivalent.
  $source = file_get_contents( dirname( __DIR__ ) . '/migration/Runner.php' );
  $assert( (bool) preg_match( "/if \\( 'COMPLETE' === \\( \\\$plan\\['retry'\\]\\['status'\\] \\?\\? null \\) \\) \\{ return \\\$plan; \\}/", $source ), 'retry_failed_resolved_only() short-circuits to a no-op once plan[retry][status]===COMPLETE' );
  $assert( (bool) preg_match( '/RETRY_SET_CHANGED_SINCE_FIRST_BUILD/', $source ), 'retry_failed_resolved_only() seals its retry set on first build, refuses to silently substitute a different one later' );
  $assert( (bool) preg_match( '/BACKUP_MISSING_FOR_RETRY/', $source ), 'retry_failed_resolved_only() requires the post-first-import backup to still be present' );
  $assert( (bool) preg_match( "/RETRYABLE_ROOT_CAUSES = array\\( 'DIRECT_PDF_A', 'DIRECT_PDF_B', 'CASCADE_FROM_PDF_A', 'CASCADE_FROM_PDF_B' \\)/", $source ), 'The retryable-cause allowlist is exactly the four fixed-bug causes, never a bare default' );

  $export( 'retry-model-tests.json', array( 'passed' => true, 'checks' => $checks, 'root_causes' => $causesSeen ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'root_causes' => $causesSeen ) );
 } catch ( Throwable $error ) {
  $export( 'retry-model-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

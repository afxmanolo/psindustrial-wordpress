<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

final class Runner {
 /** Distinct from the subset phrase by construction -- never accepted by batch() (scope
  *  check would still refuse a 'full' plan even if it were), never accepted by
  *  batch_full_local_resolved_only() for a 'subset' plan. Neither phrase authorizes the
  *  other's scope; see migration/Runner.php's own checks below, not this string alone. */
 private const FULL_CONFIRMATION = 'IMPORTAR FULL LOCAL RESUELTO';
 /** Distinct from both other phrases by construction -- retry_failed_resolved_only() never
  *  accepts either of them, and neither of them is ever accepted by retry_failed_resolved_only()'s
  *  own check below. A retry is a materially different, smaller-blast-radius operation
  *  (only the pre-identified retry set, never a full cursor walk) and gets its own explicit
  *  human confirmation rather than reusing a phrase approved for a different scope. */
 private const RETRY_CONFIRMATION = 'REINTENTAR FALLOS RESUELTOS';
 /** Defensive sanity ceiling only -- not a tuning knob. The real limit on any single HTTP
  *  request is $limit/time-budget below; this just refuses to even attempt a plan whose
  *  mutable-entry count is wildly outside anything this project's actual catalogue could
  *  ever produce, the same spirit as batch()'s own SUBSET_LIMIT_EXCEEDED. */
 private const MAX_FULL_MUTABLE = 5000;
 /** Infra/environment-level failures abort the WHOLE batch immediately (re-thrown by
  *  batch_full_local_resolved_only(), never by batch(), which is intentionally left with its
  *  original catch-everything-as-FAILED behaviour to avoid any change to the already-proven
  *  subset path). Everything else -- a bad hash, a missing dependency, a WordPress write
  *  rejection, any injected test failure -- stays entity-level, exactly as this class has
  *  always treated it. Deliberately an ALLOWLIST, not a denylist: an unrecognised message
  *  defaults to entity-level, so a legitimate but previously-unseen per-entity failure can
  *  never accidentally abort an entire full run. */
 private const FATAL_ERROR_CODES = array(
  'PERMISSION_DENIED', 'LOCAL_DATABASE_REQUIRED', 'PRIVATE_STORAGE_UNAVAILABLE',
  'PRIVATE_STORAGE_INSIDE_WEBROOT', 'JOURNAL_WRITE_FAILED', 'PACKAGE_ASSET_CORRUPT',
  'PACKAGE_ASSET_COPY_FAILED', 'PACKAGE_ASSET_HASH_INVALID', 'WRITER_BUSY',
  'WRITER_LEASE_ACTIVE', 'SYMLINK_REJECTED', 'INVALID_JOURNAL_NAME', 'INVALID_RUN_ID',
 );

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
    $e = $plan['entries'][ $plan['cursor'] ];
    try { $result = self::process_entry( $e, $plan, $run ); }
    catch ( \Throwable $error ) {
     $result = $e; unset( $result['row'], $result['data'], $result['decision'] );
     $result['run_id'] = $run; $result['environment_id'] = $plan['environment_id']; $result['migrated_at'] = null; $result['migration_date'] = null;
     $result['status'] = 'FAILED'; $result['result'] = 'ERROR'; $result['notes'] = self::safe_error( $error->getMessage() );
    }
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

 /**
  * FULL_LOCAL_RESOLVED_ONLY execution. Processes every non-REVIEW, non-ERROR entry of an
  * already-validated 'full' plan (MIGRATE/MERGE/CREATE_FROM_STATIC/SKIP -- the same action
  * vocabulary Planner/EditorialDecisions/Policy already produce; nothing here decides what
  * an entity's action IS, only whether it gets applied) via the EXACT SAME self::apply()/
  * Identity::prediction() mechanism batch() already uses for subset -- no editorial logic,
  * no second identity/merge/conflict algorithm. REVIEW and any entry whose action is outside
  * the known set are never even attempted; a REVIEW row's own action can never silently
  * become SKIP or CREATE here, it is walked over (for cursor/audit completeness, exactly
  * like batch() already does for subset) and marked BLOCKED, nothing more.
  *
  * Distinct, stronger gate than batch(): its own confirmation phrase (never the subset one),
  * requires scope==='full' (batch() requires 'subset' -- neither accepts the other's plan),
  * runs preflight_full_local() first and aborts before touching anything if it fails,
  * requires a private database+uploads backup for this exact run_id to already exist (taken
  * automatically on the first call for a given run, verified -- never silently skipped -- on
  * every resumed call), and classifies a small allowlist of infra-level failures as fatal
  * (abort the whole batch) rather than per-entity, on top of everything batch() already
  * checks (plan seal, transform version, decisions hash, source hashes, environment_id,
  * Storage::guard()'s local-environment/DB/capability gate).
  */
 public static function batch_full_local_resolved_only( string $run, string $confirmation, int $limit = 50 ): array {
  Storage::guard();
  if ( self::FULL_CONFIRMATION !== $confirmation ) { throw new \RuntimeException( 'EXPLICIT_FULL_CONFIRMATION_REQUIRED' ); }
  return Storage::locked( static function() use ( $run, $limit ): array {
   $plan = Storage::read( 'run-' . $run . '.json' );
   if ( ! $plan || ! in_array( $plan['status'], array( 'VALIDATED','RUNNING','COMPLETE' ), true ) || 'full' !== $plan['scope'] || ! hash_equals( $plan['environment_id'], Storage::hash( array( home_url(), DB_NAME ) ) ) ) { throw new \RuntimeException( 'VALID_LOCAL_FULL_PLAN_REQUIRED' ); }
   if ( 'COMPLETE' === $plan['status'] ) { return $plan; }
   $mutableCount = count( array_filter( $plan['entries'], static fn( $e ) => ! in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) );
   if ( $mutableCount > self::MAX_FULL_MUTABLE ) { throw new \RuntimeException( 'FULL_MUTABLE_SANITY_CEILING_EXCEEDED' ); }
   $report = self::preflight_full_local( $run, $plan );
   if ( ! $report['ok'] ) { throw new \RuntimeException( 'PREFLIGHT_FAILED:' . implode( ',', $report['blockers'] ) ); }
   // Backup is REQUIRED before the first mutation of this run, never optional and never
   // re-taken on a resumed call: cursor===0 and status still 'VALIDATED' (never yet RUNNING)
   // is the one-time signal "nothing of this run has executed yet". A resumed call (cursor>0
   // or status already RUNNING/COMPLETE) trusts the backup already recorded on the plan
   // itself -- verified present, never silently re-taken or silently skipped.
   if ( 0 === $plan['cursor'] && 'VALIDATED' === $plan['status'] ) {
    $plan['backup'] = array( 'database' => Storage::backup_database( $run ), 'uploads' => Storage::backup_uploads( $run ), 'taken_at' => gmdate( 'c' ) );
    Storage::write( 'run-' . $run . '.json', $plan );
   } elseif ( empty( $plan['backup']['database'] ?? null ) ) {
    throw new \RuntimeException( 'BACKUP_MISSING_FOR_IN_PROGRESS_RUN' );
   }
   $start = microtime( true ); $done = 0; $media = 0; $plan['status'] = 'RUNNING'; $plan['mode'] = 'EXECUTE_FULL_LOCAL_RESOLVED_ONLY';
   while ( $plan['cursor'] < count( $plan['entries'] ) && $done < min( self::MAX_FULL_MUTABLE, max( 1, $limit ) ) && $media < 20 && microtime( true ) - $start < 20 ) {
    $e = $plan['entries'][ $plan['cursor'] ];
    try { $result = self::process_entry( $e, $plan, $run ); }
    catch ( \Throwable $error ) {
     if ( self::is_fatal( $error ) ) {
      $plan['status'] = 'RUNNING'; Storage::write( 'run-' . $run . '.json', $plan );
      update_option( 'psi_import_run_' . $run, array( 'cursor' => $plan['cursor'], 'total' => count( $plan['entries'] ), 'status' => $plan['status'], 'updated_at' => gmdate( 'c' ), 'fatal' => $error->getMessage() ), false );
      throw $error; // whole batch aborts; cursor stays exactly where it was, nothing marked for this entry.
     }
     $result = $e; unset( $result['row'], $result['data'], $result['decision'] );
     $result['run_id'] = $run; $result['environment_id'] = $plan['environment_id']; $result['migrated_at'] = null; $result['migration_date'] = null;
     $result['status'] = 'FAILED'; $result['result'] = 'ERROR'; $result['notes'] = self::safe_error( $error->getMessage() );
    }
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

 /**
  * Read-only. Never mutates anything, never requires the confirmation phrase -- safe to call
  * at any time to inspect whether a plan WOULD be allowed to execute, including against a
  * plan nobody intends to run yet (see docs/implementation/full-local-import/03-preflight.md
  * for this exact use as a standing report against the current FULL DRY RUN). Every check a
  * throwing call would perform anyway (plan seal, transform version, decisions hash, source
  * hashes) is re-verified here non-fatally, plus the checks unique to full-scope execution:
  * every referenced legacy source file still exists, every staged media asset's bytes still
  * match what was staged, and every PDF security approval this plan depends on still
  * resolves against the file's current bytes. Storage::guard()'s own environment/DB/
  * capability gate is NOT re-implemented here as a soft check -- Storage::read() below
  * already throws through it unconditionally, exactly like every other method in this class;
  * this function only ever runs at all once that has already passed.
  * @param array|null $plan Optional pre-loaded plan (avoids a second read when called from
  *        batch_full_local_resolved_only() itself).
  */
 public static function preflight_full_local( string $run, ?array $plan = null ): array {
  $plan ??= Storage::read( 'run-' . $run . '.json' );
  $checks = array(); $blockers = array();
  $add = static function( string $id, bool $passed, string $detail = '' ) use ( &$checks, &$blockers ): void {
   $checks[] = array( 'id' => $id, 'passed' => $passed, 'detail' => $detail );
   if ( ! $passed ) { $blockers[] = $id; }
  };

  $add( 'plan_exists', (bool) $plan, $plan ? '' : 'No run-' . $run . '.json found.' );
  if ( ! $plan ) { return array( 'run_id' => $run, 'ok' => false, 'checks' => $checks, 'blockers' => $blockers, 'counts' => array() ); }

  $scopeOk = 'full' === ( $plan['scope'] ?? null );
  $add( 'plan_scope_full', $scopeOk, $scopeOk ? '' : 'scope=' . ( $plan['scope'] ?? 'null' ) . ' (expected full).' );
  $statusOk = in_array( $plan['status'] ?? null, array( 'VALIDATED','RUNNING','COMPLETE' ), true );
  $add( 'plan_status_valid', $statusOk, $statusOk ? '' : 'status=' . ( $plan['status'] ?? 'null' ) . ' (expected VALIDATED, RUNNING or COMPLETE).' );
  $envOk = isset( $plan['environment_id'] ) && hash_equals( $plan['environment_id'], Storage::hash( array( home_url(), DB_NAME ) ) );
  $add( 'environment_id_matches', $envOk, $envOk ? '' : 'Plan was built for a different WordPress install/DB.' );
  $versionOk = Planner::VERSION === ( $plan['transform_version'] ?? null );
  $add( 'transform_version_matches', $versionOk, $versionOk ? '' : 'Planner::VERSION=' . Planner::VERSION . ' plan=' . ( $plan['transform_version'] ?? 'null' ) . '.' );
  $sealOk = false;
  try { $sealOk = hash_equals( $plan['plan_hash'] ?? '', Planner::digest( $plan ) ); } catch ( \Throwable $e ) { $sealOk = false; }
  $add( 'plan_seal_intact', $sealOk, $sealOk ? '' : 'plan_hash does not match Planner::digest(plan) -- the plan file was altered after being built.' );
  $decisionsOk = false;
  try { $decisionsOk = hash_equals( $plan['decisions_hash'] ?? '', Storage::hash( Planner::decisions() ) ); } catch ( \Throwable $e ) { $decisionsOk = false; }
  $add( 'manual_decisions_unchanged', $decisionsOk, $decisionsOk ? '' : 'subset-decisions.json changed since this plan was built.' );

  $sourcesOk = true; $sourceDetail = '';
  foreach ( $plan['sources'] ?? array() as $relative => $hash ) {
   try { if ( ! hash_equals( $hash, hash_file( 'sha256', Sources::safe( Storage::project(), $relative ) ) ) ) { $sourcesOk = false; $sourceDetail = $relative; break; } }
   catch ( \Throwable $e ) { $sourcesOk = false; $sourceDetail = $relative . ' (' . $e->getMessage() . ')'; break; }
  }
  $add( 'source_hashes_current', $sourcesOk, $sourceDetail ? "Changed or missing: $sourceDetail" : '' );

  $entries = $plan['entries'] ?? array();
  // ERROR is a recognised value -- a fresh plan should never legitimately carry it (it is
  // only ever assigned at EXECUTION time, never by Planner) -- but it must be caught by its
  // OWN dedicated, correctly-attributed no_error_entries gate below, not fall through into
  // the generic "this action string is unrecognised" bucket, which would still correctly
  // block execution but with a misleading diagnostic.
  $knownActions = array( 'MIGRATE','MERGE','CREATE_FROM_STATIC','SKIP','REVIEW','ERROR' );
  $unknownAction = null; $errorEntries = 0;
  foreach ( $entries as $e ) {
   if ( ! in_array( $e['action'], $knownActions, true ) ) { $unknownAction = $e['entity_key'] . '=' . $e['action']; break; }
   if ( 'ERROR' === $e['action'] ) { ++$errorEntries; }
  }
  $add( 'actions_within_known_set', null === $unknownAction, (string) $unknownAction );
  $add( 'no_error_entries', 0 === $errorEntries, "$errorEntries entr(y/ies) already carry action=ERROR." );

  $counts = array( 'total' => count( $entries ) );
  foreach ( $entries as $e ) { $counts[ $e['action'] ] = ( $counts[ $e['action'] ] ?? 0 ) + 1; }
  $reviewCount = $counts['REVIEW'] ?? 0;
  $add( 'review_counted', true, "$reviewCount REVIEW entries will be preserved untouched (walked over, never applied)." );

  $missingSource = null; $missingAsset = null; $staleApproval = null;
  foreach ( $entries as $e ) {
   if ( in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) { continue; }
   $file = $e['legacy_file'] ?? '';
   if ( 'media' !== $e['source_type'] && '' !== $file && 'UNKNOWN' !== $file ) {
    try { Sources::safe( Storage::project() . '/legacy/public', $file ); } catch ( \Throwable $err ) { $missingSource = $e['entity_key']; break; }
   }
   if ( 'media' === $e['source_type'] ) {
    $asset = $e['data']['package_asset'] ?? null; $sha = $e['data']['sha256'] ?? null;
    if ( $asset && $sha ) {
     $path = null;
     try { $path = Storage::path( $asset ); } catch ( \Throwable $err ) { $missingAsset = $e['entity_key']; break; }
     if ( ! is_file( $path ) || ! hash_equals( $sha, hash_file( 'sha256', $path ) ) ) { $missingAsset = $e['entity_key']; break; }
    }
    if ( isset( $e['pdf_approval_type'] ) ) {
     $original = $e['legacy_file'] ?? ''; $origSha = null;
     try { $origSha = hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $original ) ); } catch ( \Throwable $err ) { $staleApproval = $e['entity_key']; break; }
     if ( null === PdfApprovals::resolve( $original, $origSha ) ) { $staleApproval = $e['entity_key']; break; }
    }
   }
  }
  $add( 'legacy_source_files_exist', null === $missingSource, (string) $missingSource );
  $add( 'staged_media_assets_valid', null === $missingAsset, (string) $missingAsset );
  $add( 'pdf_approvals_current', null === $staleApproval, (string) $staleApproval );

  $ok = empty( $blockers );
  return array(
   'run_id' => $run, 'ok' => $ok, 'checks' => $checks, 'blockers' => $blockers, 'counts' => $counts,
   'environment' => array( 'type' => wp_get_environment_type(), 'db_name' => DB_NAME, 'db_host' => DB_HOST ),
  );
 }

 /** Core per-entry decision: SKIP/REVIEW never call apply(); anything else resolves via
  *  Identity::prediction() (CREATE/UPDATE/UNCHANGED/CONFLICT) exactly like every other
  *  scope. No try/catch here on purpose -- each caller (batch()/batch_full_local_
  *  resolved_only()) decides its own fault tolerance around this call; extracted verbatim
  *  from what was batch()'s own loop body so subset's behaviour is unchanged byte-for-byte. */
 private static function process_entry( array $e, array $plan, string $run ): array {
  $result = $e; unset( $result['row'], $result['data'], $result['decision'] );
  $result['run_id'] = $run; $result['environment_id'] = $plan['environment_id']; $result['migrated_at'] = null; $result['migration_date'] = null;
  if ( in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) { $result['status'] = 'SKIP' === $e['action'] ? 'SKIPPED' : 'BLOCKED'; $result['result'] = $e['action']; return $result; }
  if ( isset( $e['source_file_hash'] ) && ! hash_equals( $e['source_file_hash'], hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $e['legacy_file'] ) ) ) ) { throw new \RuntimeException( 'PHP_SOURCE_CHANGED_REPLAN' ); }
  if ( 'media' === $e['source_type'] ) {
   $integrity = self::media_integrity( $e );
   if ( ! $integrity['original_source_integrity'] || ! $integrity['staged_artifact_integrity'] ) { throw new \RuntimeException( 'MEDIA_CHANGED_REPLAN' ); }
  }
  foreach ( $e['dependencies'] as $dep ) {
   $dependency = array_values( array_filter( $plan['entries'], static fn( $v ) => $v['entity_key'] === $dep ) )[0] ?? null;
   if ( ! $dependency || ! Identity::find( $dependency ) || ! in_array( Identity::prediction( $dependency ), array( 'UNCHANGED','UPDATE' ), true ) ) { throw new \RuntimeException( 'DEPENDENCY_NOT_APPLIED:' . $dep ); }
  }
  $prediction = Identity::prediction( $e );
  if ( 'CONFLICT' === $prediction ) { $result['status'] = 'CONFLICT'; $result['result'] = 'REVIEW'; $result['notes'] = 'Destino editado, identidad parcial o versión binaria cambiada: conservar y resolver.'; return $result; }
  $id = 'UNCHANGED' === $prediction ? Identity::find( $e ) : self::apply( $e, $plan );
  $result['wordpress_id'] = $id; $result['status'] = 'UNCHANGED' === $prediction ? 'UNCHANGED' : 'APPLIED'; $result['result'] = $prediction;
  $state = Identity::get( $e, $id, '_psi_import_state' );
  $result['target_hash'] = $state['target_hash']; $result['last_applied_source_hash'] = $state['source_hash']; $result['migrated_at'] = $state['migrated_at']; $result['migration_date'] = $state['migrated_at'];
  return $result;
 }
 private static function is_fatal( \Throwable $error ): bool { return in_array( $error->getMessage(), self::FATAL_ERROR_CODES, true ); }
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
   // Scope-agnostic below this line -- it only ever reads each entity's own journal and
   // reverses a clean creation; widened from subset-only so a full-local run's clean
   // creations can use the exact same, already-proven mechanism (never a second
   // implementation). Still never touches an UPDATE'd or CONFLICT'd object -- see this
   // method's own docblock -- and a large full-scope rollback is a deliberate, rare,
   // human-invoked recovery action, not a routine one, so no separate batching is added here.
   if ( ! $plan || ! in_array( $plan['scope'], array( 'subset','full' ), true ) ) { throw new \RuntimeException( 'SUBSET_OR_FULL_REQUIRED' ); }
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

 /**
  * Root causes recognised as "fixed" and therefore eligible for retry. Deliberately a
  * closed allowlist, never inferred from the FAILED message string alone (safe_error() may
  * have already masked it) -- retry_root_cause() below always re-derives the real cause
  * from the entity's own dependency graph and the two fixed checks, exactly like the
  * post-import classification that produced this same set of names.
  */
 private const RETRYABLE_ROOT_CAUSES = array( 'DIRECT_PDF_A', 'DIRECT_PDF_B', 'CASCADE_FROM_PDF_A', 'CASCADE_FROM_PDF_B' );

 /**
  * Read-only. Re-derives WHY a FAILED entity failed -- never by pattern-matching the
  * stored, possibly-safe_error()-masked notes string.
  *
  * For a direct media entry: re-runs media_integrity() (any genuine CURRENT drift, on
  * either half, is a fresh problem -- 'OTHER', never assumed retryable). Once both halves
  * pass -- which both fixes now guarantee for anything either bug used to block -- WHICH
  * bug (if either) originally blocked it is read from pdf_approval_type, a STABLE, plan-time
  * fact set once by Planner::build() ('sanitized'=Group A, 'exception'=Group B, absent for
  * an ordinary asset never gated by either approval path). It is deliberately never
  * re-derived from media_is_valid()/file_valid() here: after the fix, an ordinary,
  * never-blocked asset (a ordinary product photo, say) passes those exactly the same way an
  * approved Group B exception does, so re-deriving from them would misclassify every
  * ordinary dependency as a fixed bug.
  *
  * Everything else is classified by walking its own declared `dependencies` and asking the
  * same question recursively one level down (a cascade's cause is its dependency's cause).
  * Returns one of RETRYABLE_ROOT_CAUSES, 'LEGITIMATE_CATEGORY_CONFLICT',
  * 'LEGITIMATE_SLUG_COLLISION', 'OK' (this entity/dependency was never a problem at all),
  * or 'OTHER' (never silently defaults an unrecognised shape to retryable).
  */
 public static function retry_root_cause( array $entry, array $plan ): string {
  if ( 'media' === $entry['source_type'] ) {
   try { $integrity = self::media_integrity( $entry ); } catch ( \Throwable $e ) { return 'OTHER'; }
   if ( ! $integrity['original_source_integrity'] || ! $integrity['staged_artifact_integrity'] ) { return 'OTHER'; } // genuine current drift on either half -- not either historical bug, needs a fresh look.
   $type = $entry['pdf_approval_type'] ?? null;
   if ( 'sanitized' === $type ) { return 'DIRECT_PDF_A'; }
   if ( 'exception' === $type ) { return 'DIRECT_PDF_B'; }
   return 'OK'; // no PDF approval involved at all -- an ordinary asset, never blocked by either bug.
  }
  $hitsA = false; $hitsB = false; $hitsLegitimate = false; $hitsOther = false;
  foreach ( $entry['dependencies'] ?? array() as $dep ) {
   $depEntry = null;
   foreach ( $plan['entries'] as $e ) { if ( $e['entity_key'] === $dep ) { $depEntry = $e; break; } }
   if ( ! $depEntry ) { $hitsOther = true; continue; }
   $depCause = in_array( $depEntry['source_type'], array( 'media' ), true ) ? self::retry_root_cause( $depEntry, $plan ) : ( 'CONFLICT' === Identity::prediction( $depEntry ) ? 'LEGITIMATE_SLUG_COLLISION' : 'OK' );
   match ( true ) {
    'DIRECT_PDF_A' === $depCause => $hitsA = true,
    'DIRECT_PDF_B' === $depCause => $hitsB = true,
    'LEGITIMATE_SLUG_COLLISION' === $depCause => $hitsLegitimate = true,
    'OK' === $depCause => null,
    default => $hitsOther = true,
   };
  }
  if ( $hitsOther || ( $hitsLegitimate && ( $hitsA || $hitsB ) ) ) { return 'OTHER'; } // mixed/unknown cause -- never silently classified as safe.
  if ( $hitsLegitimate ) { return 'LEGITIMATE_CATEGORY_CONFLICT'; }
  if ( $hitsA ) { return 'CASCADE_FROM_PDF_A'; }
  if ( $hitsB ) { return 'CASCADE_FROM_PDF_B'; }
  return 'OK'; // none of this entity's own dependencies carry a recognised cause -- it was never actually blocked by anything this classifier understands.
 }

 /**
  * Read-only. Whether ONE originally-FAILED entity is safe to retry right now, re-checking
  * every condition fresh against current state -- never trusting the original run's stored
  * result. `$freshPlan` must be a plan just built by Planner::build('full') (the caller
  * builds it once and reuses it across every entity, never per-entity, for a consistent
  * snapshot). Returns ['eligible'=>bool, 'reason'=>string, 'fresh_entry'=>array|null].
  */
 public static function retry_eligibility( array $originalEntry, array $originalResult, array $freshPlan ): array {
  if ( 'FAILED' !== ( $originalResult['status'] ?? null ) ) { return array( 'eligible' => false, 'reason' => 'NOT_FAILED_IN_ORIGINAL_RUN', 'fresh_entry' => null ); }
  $key = $originalEntry['entity_key'];
  $freshEntry = null;
  foreach ( $freshPlan['entries'] as $e ) { if ( $e['entity_key'] === $key ) { $freshEntry = $e; break; } }
  if ( ! $freshEntry ) { return array( 'eligible' => false, 'reason' => 'MISSING_FROM_FRESH_PLAN', 'fresh_entry' => null ); }
  if ( ! in_array( $freshEntry['action'], array( 'MIGRATE','MERGE','CREATE_FROM_STATIC' ), true ) ) { return array( 'eligible' => false, 'reason' => 'FRESH_ACTION_NOT_MUTABLE:' . $freshEntry['action'], 'fresh_entry' => $freshEntry ); }
  if ( $freshEntry['source_hash'] !== $originalEntry['source_hash'] ) { return array( 'eligible' => false, 'reason' => 'SOURCE_CHANGED_SINCE_FIRST_RUN', 'fresh_entry' => $freshEntry ); }
  if ( $freshEntry['decision_hash'] !== $originalEntry['decision_hash'] ) { return array( 'eligible' => false, 'reason' => 'DECISION_CHANGED_SINCE_FIRST_RUN', 'fresh_entry' => $freshEntry ); }
  $cause = self::retry_root_cause( $freshEntry, $freshPlan );
  if ( ! in_array( $cause, self::RETRYABLE_ROOT_CAUSES, true ) ) { return array( 'eligible' => false, 'reason' => 'ROOT_CAUSE_NOT_FIXED:' . $cause, 'fresh_entry' => $freshEntry ); }
  $prediction = Identity::prediction( $freshEntry );
  if ( 'UNCHANGED' === $prediction ) { return array( 'eligible' => false, 'reason' => 'ALREADY_APPLIED_ELSEWHERE_NOW_UNCHANGED', 'fresh_entry' => $freshEntry ); }
  if ( 'CONFLICT' === $prediction ) {
   // Distinguish THIS run's own aborted, never-completed attempt (safe -- apply() will
   // simply overwrite its stale INTENT journal with a fresh one, exactly as any first
   // attempt would) from a genuinely new or pre-existing conflict (never safe to retry
   // blindly). Never trusts the ledger's own claim alone: also re-confirms no WordPress
   // object actually exists.
   $id = Identity::find( $freshEntry );
   $ledger = Storage::read( Identity::ledger( $freshEntry ) );
   // Compared against the ORIGINAL run this FAILED result actually came from
   // ($originalResult['run_id'], set by process_entry() on every result) -- never
   // $freshPlan['run_id'], which is a brand-new, unrelated id from THIS rebuild and would
   // never match any real ledger, silently rejecting every genuinely-retryable stale attempt.
   $ownStaleAttempt = ! $id && $ledger && 'INTENT' === ( $ledger['status'] ?? null ) && empty( $ledger['wordpress_id'] ) && ( $ledger['run_id'] ?? null ) === ( $originalResult['run_id'] ?? null );
   if ( ! $ownStaleAttempt ) { return array( 'eligible' => false, 'reason' => 'CONFLICT_NOT_OWN_STALE_ATTEMPT', 'fresh_entry' => $freshEntry ); }
   return array( 'eligible' => true, 'reason' => 'RETRYABLE_FAILED_ATTEMPT_STALE_INTENT_LEDGER', 'fresh_entry' => $freshEntry );
  }
  return array( 'eligible' => true, 'reason' => 'CLEAN_RETRY_' . $prediction, 'fresh_entry' => $freshEntry );
 }

 /**
  * Read-only. Never mutates anything -- safe to call at any time, including long before a
  * retry is authorized. Reuses preflight_full_local()'s own 14 checks in full (a retry is
  * meaningless if the underlying plan/environment/backup/source/PDF-approval state it
  * would build on is not itself sound) and adds the retry-specific ones: the original run
  * must be COMPLETE, and the exact retry set is computed and reported by exact count, never
  * estimated.
  */
 public static function retry_preflight( string $run ): array {
  $plan = Storage::read( 'run-' . $run . '.json' );
  $base = self::preflight_full_local( $run, $plan );
  $checks = $base['checks']; $blockers = $base['blockers'];
  $add = static function( string $id, bool $passed, string $detail = '' ) use ( &$checks, &$blockers ): void {
   $checks[] = array( 'id' => $id, 'passed' => $passed, 'detail' => $detail );
   if ( ! $passed ) { $blockers[] = $id; }
  };
  if ( ! $plan ) { return array( 'run_id' => $run, 'ok' => false, 'checks' => $checks, 'blockers' => $blockers, 'counts' => array() ); }

  $completeOk = 'COMPLETE' === ( $plan['status'] ?? null );
  $add( 'original_run_complete', $completeOk, $completeOk ? '' : 'status=' . ( $plan['status'] ?? 'null' ) . ' (retry requires a COMPLETE first run).' );
  $backupOk = ! empty( $plan['backup']['database'] ?? null ) && is_file( Storage::path( $plan['backup']['database'] ) );
  $add( 'post_first_import_backup_available', $backupOk, $backupOk ? '' : 'No verifiable backup-<run>.json on disk for this run.' );
  $alreadyRetried = 'COMPLETE' === ( $plan['retry']['status'] ?? null );
  $add( 'retry_not_already_complete', ! $alreadyRetried, $alreadyRetried ? 'A retry for this run already completed; see plan[retry].' : '' );

  $counts = array( 'retryable' => 0, 'non_retryable_conflict' => 0, 'already_applied' => 0, 'review' => 0, 'skip' => 0, 'other_excluded' => 0 );
  $retrySet = array(); $excluded = array();

  if ( $completeOk ) {
   $fresh = Planner::build( 'full' );
   foreach ( $plan['results'] as $r ) {
    if ( 'BLOCKED' === $r['status'] ) { ++$counts['review']; continue; }
    if ( 'SKIPPED' === $r['status'] ) { ++$counts['skip']; continue; }
    if ( 'UNCHANGED' === $r['status'] || 'APPLIED' === $r['status'] ) { ++$counts['already_applied']; continue; }
    if ( 'FAILED' !== $r['status'] ) { continue; } // CONFLICT (the original 10): counted separately below, never in this per-result loop's buckets.
    $originalEntry = null;
    foreach ( $plan['entries'] as $e ) { if ( $e['entity_key'] === $r['entity_key'] ) { $originalEntry = $e; break; } }
    $verdict = self::retry_eligibility( $originalEntry, $r, $fresh );
    if ( $verdict['eligible'] ) { ++$counts['retryable']; $retrySet[] = $r['entity_key']; }
    else {
     $reasonPrefix = strtok( $verdict['reason'], ':' );
     if ( in_array( $reasonPrefix, array( 'CONFLICT_NOT_OWN_STALE_ATTEMPT','ROOT_CAUSE_NOT_FIXED' ), true ) ) { ++$counts['non_retryable_conflict']; }
     else { ++$counts['other_excluded']; }
     $excluded[ $r['entity_key'] ] = $verdict['reason'];
    }
   }
   foreach ( $plan['results'] as $r ) { if ( 'CONFLICT' === $r['status'] ) { ++$counts['non_retryable_conflict']; } } // the original 10 -- proven legitimate slug collisions this same session, never retried.
  }

  $ok = empty( $blockers );
  return array(
   'run_id' => $run, 'ok' => $ok, 'checks' => $checks, 'blockers' => $blockers, 'counts' => $counts,
   'retry_set' => $retrySet, 'excluded' => $excluded, 'environment' => $base['environment'] ?? array(),
  );
 }

 /**
  * Mutating. Requires its OWN, distinct confirmation phrase -- never RETRY_CONFIRMATION
  * accepted by the other two batch entry points, and vice versa. Operates ONLY on the exact
  * retry set retry_preflight() computes (rebuilt fresh here, under the same lock, never
  * trusted from a stale caller-supplied list) -- never a cursor walk over the whole plan.
  * Never rewrites the original run's own `results`/`cursor`/`status` (that history is
  * immutable once written); progress lives entirely under the new `plan['retry']` key,
  * exactly like `plan['backup']` was added without disturbing anything else on the plan.
  * Processes direct media causes before cascades that depend on them (a cascade's own
  * dependency check in process_entry()/apply() requires its dependency to already resolve
  * to UNCHANGED/UPDATE) by sorting DIRECT_* causes first within the retry set.
  */
 public static function retry_failed_resolved_only( string $run, string $confirmation, int $limit = 25 ): array {
  Storage::guard();
  if ( self::RETRY_CONFIRMATION !== $confirmation ) { throw new \RuntimeException( 'EXPLICIT_RETRY_CONFIRMATION_REQUIRED' ); }
  return Storage::locked( static function() use ( $run, $limit ): array {
   $plan = Storage::read( 'run-' . $run . '.json' );
   if ( ! $plan || 'full' !== ( $plan['scope'] ?? null ) || 'COMPLETE' !== ( $plan['status'] ?? null ) || ! hash_equals( $plan['environment_id'], Storage::hash( array( home_url(), DB_NAME ) ) ) ) { throw new \RuntimeException( 'VALID_COMPLETE_FULL_PLAN_REQUIRED' ); }
   if ( empty( $plan['backup']['database'] ?? null ) || ! is_file( Storage::path( $plan['backup']['database'] ) ) ) { throw new \RuntimeException( 'BACKUP_MISSING_FOR_RETRY' ); }
   if ( 'COMPLETE' === ( $plan['retry']['status'] ?? null ) ) { return $plan; }

   $report = self::retry_preflight( $run );
   if ( ! $report['ok'] ) { throw new \RuntimeException( 'RETRY_PREFLIGHT_FAILED:' . implode( ',', $report['blockers'] ) ); }

   $fresh = Planner::build( 'full' );
   // apply()'s journal/_psi_import_state attribution must point at the ORIGINAL run being
   // retried, never at Planner::build()'s own fresh, ephemeral run_id -- entries/dependency
   // resolution still come entirely from $fresh (current reality), only the run identity
   // written into each retried object's own audit trail is corrected here.
   $fresh['run_id'] = $run;
   $freshByKey = array_column( $fresh['entries'], null, 'entity_key' );
   $order = array( 'DIRECT_PDF_A' => 0, 'DIRECT_PDF_B' => 0, 'CASCADE_FROM_PDF_A' => 1, 'CASCADE_FROM_PDF_B' => 1 );
   $set = $report['retry_set'];
   usort( $set, static function( $a, $b ) use ( $freshByKey, $fresh, $order ): int {
    return ( $order[ self::retry_root_cause( $freshByKey[ $a ], $fresh ) ] ?? 2 ) <=> ( $order[ self::retry_root_cause( $freshByKey[ $b ], $fresh ) ] ?? 2 );
   } );

   $retry = $plan['retry'] ?? array( 'retry_id' => wp_generate_uuid4(), 'based_on_plan_hash' => $plan['plan_hash'], 'created_at' => gmdate( 'c' ), 'set' => $set, 'cursor' => 0, 'status' => 'RUNNING', 'results' => array() );
   if ( ( $retry['set'] ?? array() ) !== $set ) { throw new \RuntimeException( 'RETRY_SET_CHANGED_SINCE_FIRST_BUILD' ); } // sealed on first call, exactly like the plan's own entries.
   $retry['status'] = 'RUNNING';

   $start = microtime( true ); $done = 0; $media = 0;
   while ( $retry['cursor'] < count( $retry['set'] ) && $done < min( 100, max( 1, $limit ) ) && $media < 20 && microtime( true ) - $start < 20 ) {
    $key = $retry['set'][ $retry['cursor'] ];
    $entry = $freshByKey[ $key ];
    try {
     // Re-verify eligibility one more time, immediately before mutating -- state may have
     // shifted between preflight and this exact entity's turn within the same run (an
     // earlier retried dependency in THIS pass, or, defensively, anything else).
     $originalResult = null; foreach ( $plan['results'] as $r ) { if ( $r['entity_key'] === $key ) { $originalResult = $r; break; } }
     $originalEntry = null; foreach ( $plan['entries'] as $e ) { if ( $e['entity_key'] === $key ) { $originalEntry = $e; break; } }
     $verdict = self::retry_eligibility( $originalEntry, $originalResult, $fresh );
     if ( ! $verdict['eligible'] ) { throw new \RuntimeException( 'NO_LONGER_ELIGIBLE:' . $verdict['reason'] ); }
     $id = self::apply( $entry, $fresh );
     $result = array( 'entity_key' => $key, 'status' => 'APPLIED', 'wordpress_id' => $id, 'retried_at' => gmdate( 'c' ) );
    } catch ( \Throwable $error ) {
     if ( self::is_fatal( $error ) ) { $retry['status'] = 'RUNNING'; $plan['retry'] = $retry; Storage::write( 'run-' . $run . '.json', $plan ); throw $error; }
     $result = array( 'entity_key' => $key, 'status' => 'FAILED', 'wordpress_id' => 0, 'notes' => self::safe_error( $error->getMessage() ), 'retried_at' => gmdate( 'c' ) );
    }
    $retry['results'][] = $result; ++$retry['cursor']; ++$done; if ( 'media' === $entry['source_type'] ) { ++$media; }
    Storage::log( $run, $key, 'RETRY', $result['status'], $result['notes'] ?? '' );
    $plan['retry'] = $retry; Storage::write( 'run-' . $run . '.json', $plan );
   }
   if ( $retry['cursor'] === count( $retry['set'] ) ) { $retry['status'] = 'COMPLETE'; }
   $plan['retry'] = $retry; Storage::write( 'run-' . $run . '.json', $plan );
   return $plan;
  } );
 }

 /**
  * ============================================================================
  * RECOVERY — for when a parent run's OWN run-<id>.json snapshot no longer exists (pruned,
  * lost, or otherwise unavailable), so retry_preflight()/retry_failed_resolved_only() above
  * (which read plan['results'] from that exact file) cannot operate on it at all. Never
  * reconstructs or fabricates that missing file — see
  * docs/implementation/full-local-import/17-run-snapshot-incident.md for the incident this
  * responds to. Builds a NEW, independently-sealed plan instead, evidenced exclusively by
  * what survives: log-<parentRun>.jsonl (parse_run_log(), never pruned — a different
  * filename shape entirely), per-entity identity-<token>.json ledgers, and a freshly-built
  * CURRENT full plan. Markdown/CSV documentation is read by humans, never by this code.
  * ============================================================================
  */
 private const RECOVERY_REASON_SNAPSHOT_PRUNED = 'ORIGINAL_RUN_SNAPSHOT_PRUNED';

 /**
  * Read-only. Parses log-<run>.jsonl — the per-entity, append-only audit trail Storage::log()
  * writes for EVERY entity batch()/batch_full_local_resolved_only() processes, success or
  * not (never only the ones that succeeded): one JSON line per entity, `{run_id, timestamp,
  * entity, action, result, message}`. Unlike run-<id>.json (a single aggregated snapshot,
  * the thing Storage::retain_recent_runs() prunes), this filename never matches that
  * pattern and is never touched by it. Throws PARENT_RUN_LOG_NOT_FOUND rather than
  * returning an empty/partial result if the log itself is also gone — recovery must never
  * silently proceed on zero evidence.
  * @return array<string,array{run_id:string,timestamp:string,entity:string,action:string,result:string,message:string}> keyed by entity_key.
  */
 public static function parse_run_log( string $run ): array {
  if ( ! preg_match( '/^[a-zA-Z0-9-]+$/D', $run ) ) { throw new \RuntimeException( 'INVALID_RUN_ID' ); }
  $path = Storage::path( 'log-' . $run . '.jsonl' );
  if ( ! is_file( $path ) ) { throw new \RuntimeException( 'PARENT_RUN_LOG_NOT_FOUND' ); }
  $handle = fopen( $path, 'r' );
  if ( ! $handle ) { throw new \RuntimeException( 'PARENT_RUN_LOG_UNREADABLE' ); }
  $byKey = array();
  try {
   while ( false !== ( $line = fgets( $handle ) ) ) {
    $line = trim( $line ); if ( '' === $line ) { continue; }
    $row = json_decode( $line, true );
    if ( ! is_array( $row ) || ! isset( $row['entity'] ) ) { continue; } // tolerate one malformed line, never abort the whole parse.
    $byKey[ $row['entity'] ] = $row; // append-only log; last line for a key wins (defensive — entities are normally logged exactly once per run).
   }
  } finally { fclose( $handle ); }
  return $byKey;
 }

 private static function recovery_digest( array $plan ): string {
  return Storage::hash( array_intersect_key( $plan, array_flip( array( 'manifest_version','transform_version','run_id','scope','parent_run_id','recovery_reason','environment_id','created_at','entries' ) ) ) );
 }

 /**
  * Read-only. The control proving recovery never silently touches what the parent run
  * already successfully applied: every entity_key the parent log recorded with
  * `result=CREATE` (i.e. actually APPLIED — see process_entry(), which sets
  * `$result['result'] = $prediction` and `$prediction` is only ever 'CREATE' for a brand
  * new object, as every one of these was) must resolve to `Identity::prediction()===
  * UNCHANGED` against a freshly-built current plan. Returns the list of any that do NOT —
  * empty means the control passed. Never partial: checks every applied entity_key the log
  * contains, not a sample.
  */
 public static function applied_control_violations( array $parentLog, array $freshPlan ): array {
  $freshByKey = array_column( $freshPlan['entries'], null, 'entity_key' );
  $violations = array();
  foreach ( $parentLog as $key => $row ) {
   if ( 'CREATE' !== ( $row['result'] ?? null ) ) { continue; }
   $entry = $freshByKey[ $key ] ?? null;
   if ( ! $entry ) { $violations[ $key ] = 'MISSING_FROM_CURRENT_PLAN'; continue; }
   $prediction = Identity::prediction( $entry );
   if ( 'UNCHANGED' !== $prediction ) { $violations[ $key ] = 'UNEXPECTED_' . $prediction; }
  }
  return $violations;
 }

 /**
  * Mutating (private storage only — never touches WordPress/the database, the same
  * "building/sealing a plan is safe" category Planner::build() itself already belongs to).
  * Locked for extra safety since this does a multi-step read-decide-write sequence a
  * concurrent real execution could otherwise interleave with.
  *
  * Candidate source is EXCLUSIVELY: parse_run_log($parentRun) (which entity_keys the parent
  * run actually recorded `result=ERROR` for — never inferred from anything else) plus a
  * freshly-built current full plan (current entries/dependencies/hashes) plus each
  * candidate's own surviving identity ledger. Every candidate must independently prove ALL
  * of: same source_key (it IS the key), current action identical to what the parent log
  * recorded (never silently different — an editorial/classification change since the
  * parent run is disqualifying, not something to paper over), action still mutable
  * (never REVIEW/SKIP), root cause in the fixed-bug allowlist (retry_root_cause() — real
  * cascade/legitimate-conflict/OTHER classification, never approximate), destination not
  * already applied elsewhere (UNCHANGED excluded) and not a genuine conflict distinct from
  * this exact parent run's own aborted attempt (Identity::prediction()+ledger cross-check,
  * exactly like retry_eligibility() above — recovery never invents a looser rule for the
  * same question). Anything that cannot prove every one of these is recorded as
  * NOT_RETRYABLE with its specific reason, never silently dropped.
  *
  * Produces and seals a NEW, independent plan (`scope=recovery`, fresh `run_id`, own
  * `plan_hash`) linked to its parent via `parent_run_id`/`recovery_reason` — never
  * resurrects or overwrites run-<parentRun>.json, which stays absent. `recovery_open=true`
  * so Storage::run_protected() shields this new plan from the exact same pruning that
  * caused the incident it exists to recover from, until close_run() explicitly closes it.
  * Also writes a dedicated machine-readable evidence artifact
  * (`recovery-evidence-<recoveryRunId>.json`) — one row per candidate considered, whether
  * accepted or rejected, with its full reasoning; documentation may reflect this, never
  * substitute for it.
  */
 /**
  * Read-only. The equivalence gate for ONE recovery candidate — extracted from
  * build_recovery_retry_plan() so every rejection path (action changed, not mutable, root
  * cause not a fixed bug, already applied, genuine conflict) is independently testable with
  * synthetic fixtures, not only reachable through a full real recovery build. `$logRow` is
  * the candidate's own line from parse_run_log($parentRun) (never anything else — this
  * method takes it as a parameter precisely so a test can hand it a deliberately-altered
  * copy without touching any real file). Returns the full evidence row shape used by
  * recovery-evidence-<id>.json; `eligibility` is 'RETRYABLE' only when every check passed.
  */
 public static function recovery_candidate_eligibility( string $key, array $logRow, array $freshPlan, string $parentRun ): array {
  $row = array(
   'source_key' => $key, 'original_ledger_status' => null, 'original_error' => $logRow['message'] ?? '',
   'current_source_hash' => null, 'current_decision_hash' => null, 'current_action' => null,
   'destination_state' => null, 'eligibility' => 'NOT_RETRYABLE', 'reason' => 'UNKNOWN',
  );
  $freshByKey = array_column( $freshPlan['entries'], null, 'entity_key' );
  $freshEntry = $freshByKey[ $key ] ?? null;
  if ( ! $freshEntry ) { $row['reason'] = 'MISSING_FROM_CURRENT_PLAN'; return $row; }
  $row['current_source_hash'] = $freshEntry['source_hash']; $row['current_decision_hash'] = $freshEntry['decision_hash']; $row['current_action'] = $freshEntry['action'];

  if ( ( $logRow['action'] ?? null ) !== $freshEntry['action'] ) { $row['reason'] = 'ACTION_CHANGED_SINCE_ORIGINAL_RUN:' . ( $logRow['action'] ?? 'null' ) . '->' . $freshEntry['action']; return $row; }
  if ( ! in_array( $freshEntry['action'], array( 'MIGRATE','MERGE','CREATE_FROM_STATIC' ), true ) ) { $row['reason'] = 'ACTION_NOT_MUTABLE:' . $freshEntry['action']; return $row; }

  $ledger = Storage::read( Identity::ledger( $freshEntry ) );
  $row['original_ledger_status'] = $ledger['status'] ?? null;

  $cause = self::retry_root_cause( $freshEntry, $freshPlan );
  if ( ! in_array( $cause, self::RETRYABLE_ROOT_CAUSES, true ) ) { $row['reason'] = 'ROOT_CAUSE_NOT_FIXED:' . $cause; return $row; }

  $prediction = Identity::prediction( $freshEntry );
  $row['destination_state'] = $prediction;
  if ( 'UNCHANGED' === $prediction ) { $row['reason'] = 'ALREADY_APPLIED_ELSEWHERE_NOW_UNCHANGED'; return $row; }
  if ( 'CONFLICT' === $prediction ) {
   $id = Identity::find( $freshEntry );
   $ownStaleAttempt = ! $id && $ledger && 'INTENT' === ( $ledger['status'] ?? null ) && empty( $ledger['wordpress_id'] ) && ( $ledger['run_id'] ?? null ) === $parentRun;
   if ( ! $ownStaleAttempt ) { $row['reason'] = 'CONFLICT_NOT_OWN_STALE_ATTEMPT'; return $row; }
  }

  $row['eligibility'] = 'RETRYABLE'; $row['reason'] = 'root_cause=' . $cause;
  return $row;
 }

 public static function build_recovery_retry_plan( string $parentRun ): array {
  Storage::guard();
  return Storage::locked( static function() use ( $parentRun ): array {
   $parentLog = self::parse_run_log( $parentRun ); // throws PARENT_RUN_LOG_NOT_FOUND — never fabricated.
   $fresh = Planner::build( 'full' );

   $controlViolations = self::applied_control_violations( $parentLog, $fresh );
   if ( $controlViolations ) { throw new \RuntimeException( 'APPLIED_CONTROL_VIOLATED:' . implode( ',', array_keys( $controlViolations ) ) ); }

   $failedKeys = array();
   foreach ( $parentLog as $key => $row ) { if ( 'ERROR' === ( $row['result'] ?? null ) ) { $failedKeys[] = $key; } }
   sort( $failedKeys ); // deterministic order, independent of jsonl append order.

   $freshByKey = array_column( $fresh['entries'], null, 'entity_key' );
   $entries = array(); $evidence = array();
   foreach ( $failedKeys as $key ) {
    $evalRow = self::recovery_candidate_eligibility( $key, $parentLog[ $key ], $fresh, $parentRun );
    $evidence[] = $evalRow;
    if ( 'RETRYABLE' === $evalRow['eligibility'] ) { $entries[] = $freshByKey[ $key ]; }
   }

   $recoveryRunId = wp_generate_uuid4();
   $plan = array(
    'manifest_version' => 1, 'transform_version' => Planner::VERSION,
    'run_id' => $recoveryRunId, 'scope' => 'recovery',
    'parent_run_id' => $parentRun, 'recovery_reason' => self::RECOVERY_REASON_SNAPSHOT_PRUNED,
    'environment_id' => $fresh['environment_id'], 'created_at' => gmdate( 'c' ),
    'status' => 'VALIDATED', 'mode' => 'RECOVERY_CANDIDATE', 'cursor' => 0,
    'entries' => $entries, 'results' => array(), 'recovery_open' => true,
    'post_first_import_backup' => null,
    'evidence_summary' => array( 'candidates_considered' => count( $failedKeys ), 'retryable' => count( $entries ), 'rejected' => count( $failedKeys ) - count( $entries ) ),
   );
   $plan['plan_hash'] = self::recovery_digest( $plan );
   Storage::write( 'run-' . $recoveryRunId . '.json', $plan );
   Storage::write( 'recovery-evidence-' . $recoveryRunId . '.json', array( 'parent_run_id' => $parentRun, 'recovery_run_id' => $recoveryRunId, 'generated_at' => gmdate( 'c' ), 'rows' => $evidence ) );
   return $plan;
  } );
 }

 /**
  * Read-only, never mutates anything — safe to call at any time. Reports exactly what
  * 18-recovery-preflight.md's checklist demands: whether recovery is possible, exact
  * retryable/rejected counts, blockers, whether parent-run evidence is available, whether
  * the candidate set is still equivalent to current reality, and whether the NEW
  * post-first-import backup real execution would require is present yet (it is never taken
  * by this method — see build_recovery_retry_plan()'s own docblock and
  * 19-recovery-preflight.md for why that stays a separate, explicit, human-authorized step).
  */
 public static function recovery_preflight( string $recoveryRun ): array {
  $plan = Storage::read( 'run-' . $recoveryRun . '.json' );
  $checks = array(); $blockers = array();
  $add = static function( string $id, bool $passed, string $detail = '' ) use ( &$checks, &$blockers ): void {
   $checks[] = array( 'id' => $id, 'passed' => $passed, 'detail' => $detail );
   if ( ! $passed ) { $blockers[] = $id; }
  };
  $add( 'recovery_plan_exists', (bool) $plan, $plan ? '' : "No run-$recoveryRun.json found." );
  if ( ! $plan ) { return array( 'run_id' => $recoveryRun, 'ok' => false, 'checks' => $checks, 'blockers' => $blockers, 'counts' => array() ); }

  $add( 'is_recovery_scope', 'recovery' === ( $plan['scope'] ?? null ), 'scope=' . ( $plan['scope'] ?? 'null' ) );
  $add( 'parent_run_id_present', ! empty( $plan['parent_run_id'] ), '' );
  $envOk = isset( $plan['environment_id'] ) && hash_equals( $plan['environment_id'], Storage::hash( array( home_url(), DB_NAME ) ) );
  $add( 'environment_id_matches', $envOk, $envOk ? '' : 'Plan was built for a different WordPress install/DB.' );

  $sealOk = false;
  try { $sealOk = hash_equals( $plan['plan_hash'] ?? '', self::recovery_digest( $plan ) ); } catch ( \Throwable $e ) { $sealOk = false; }
  $add( 'recovery_plan_seal_intact', $sealOk, '' );

  $parentLog = null;
  try { $parentLog = self::parse_run_log( $plan['parent_run_id'] ?? '' ); } catch ( \Throwable $e ) {}
  $add( 'parent_run_evidence_available', null !== $parentLog, null !== $parentLog ? '' : 'log-' . ( $plan['parent_run_id'] ?? '?' ) . '.jsonl not found — parent evidence gone.' );

  $fresh = Planner::build( 'full' );
  $freshByKey = array_column( $fresh['entries'], null, 'entity_key' );
  $stillEquivalent = true; $drift = array();
  foreach ( $plan['entries'] as $e ) {
   $now = $freshByKey[ $e['entity_key'] ] ?? null;
   if ( ! $now || $now['source_hash'] !== $e['source_hash'] || $now['decision_hash'] !== $e['decision_hash'] || $now['action'] !== $e['action'] ) { $stillEquivalent = false; $drift[] = $e['entity_key']; }
  }
  $add( 'current_plan_still_equivalent', $stillEquivalent, $stillEquivalent ? '' : 'Drifted since recovery plan was built: ' . implode( ',', array_slice( $drift, 0, 10 ) ) );

  $controlViolations = $parentLog ? self::applied_control_violations( $parentLog, $fresh ) : array( '(parent log unavailable)' => 'CANNOT_VERIFY' );
  $add( 'applied_449_control_intact', empty( $controlViolations ), $controlViolations ? ( count( $controlViolations ) . ' violation(s): ' . implode( ',', array_slice( array_keys( $controlViolations ), 0, 10 ) ) ) : '' );

  $reviewSkipUntouched = true;
  foreach ( $plan['entries'] as $e ) { if ( in_array( $e['action'], array( 'REVIEW','SKIP' ), true ) ) { $reviewSkipUntouched = false; break; } }
  $add( 'no_review_or_skip_entries_in_recovery_set', $reviewSkipUntouched, '' );

  $backupOk = ! empty( $plan['post_first_import_backup'] ?? null ) && is_file( Storage::path( $plan['post_first_import_backup'] ) );
  $add( 'new_post_first_import_backup_present', $backupOk, $backupOk ? '' : 'A NEW backup taken AFTER the 449 already-applied objects is required before real execution; none recorded on this recovery plan yet.' );

  $ok = empty( $blockers );
  return array(
   'run_id' => $recoveryRun, 'parent_run_id' => $plan['parent_run_id'] ?? null, 'ok' => $ok,
   'checks' => $checks, 'blockers' => $blockers,
   'counts' => array( 'retryable' => count( $plan['entries'] ), 'rejected' => $plan['evidence_summary']['rejected'] ?? null, 'candidates_considered' => $plan['evidence_summary']['candidates_considered'] ?? null ),
   'new_backup_required' => true, 'new_backup_present' => $backupOk,
  );
 }

 /**
  * Mutating (private storage only). The ONLY way a protected run stops being protected
  * (Storage::run_protected()) — explicit, human-authorized, never inferred from current
  * state on its own. Intended for once a recovery's retryables are all resolved and any
  * remaining conflicts have been explicitly transferred to REVIEW/editorial handling
  * outside this mechanism — this method itself does not verify that judgement call, only
  * records that a human made it.
  */
 public static function close_run( string $run, string $confirmation ): array {
  if ( 'CERRAR RUN RESUELTO' !== $confirmation ) { throw new \RuntimeException( 'EXPLICIT_CONFIRMATION_REQUIRED' ); }
  Storage::guard();
  return Storage::locked( static function() use ( $run ): array {
   $plan = Storage::read( 'run-' . $run . '.json' );
   if ( ! $plan ) { throw new \RuntimeException( 'RUN_NOT_FOUND' ); }
   $plan['closed_at'] = gmdate( 'c' ); $plan['recovery_open'] = false;
   Storage::write( 'run-' . $run . '.json', $plan );
   return $plan;
  } );
 }

 /**
  * Pure, side-effect-free predicate for the two DISTINCT integrity checks process_entry()
  * enforces on every media entry before ever reaching apply()/media_handle_sideload() --
  * kept separate, exactly like media_is_valid() below, so both halves stay independently
  * testable and neither can silently stand in for the other.
  *
  * $e['data']['sha256'] is the STAGED hash: Planner::build() overwrites it with the
  * sanitized substitute's hash for a Group A PdfApprovals approval, so comparing it against
  * the legacy original is wrong by construction for exactly those approvals -- that
  * substitution was this check's original bug.
  *
  * original_source_integrity: every legacy path (primary + binary aliases) on
  * /legacy/public must still match $e['row']['sha256'], the ORIGINAL hash Planner recorded
  * from media-master.csv and already verified once against this same path at plan-build
  * time (Planner::build()'s own MEDIA_VALIDATION_OR_HASH / BINARY_ALIAS_NOT_IDENTICAL
  * checks). Never compared against the staged/sanitized hash.
  *
  * staged_artifact_integrity: the artifact that will actually be uploaded -- the sanitized
  * substitute for a Group A approval, or the original bytes for everything else -- must
  * still match ITS OWN staged hash in private storage. A Group A substitute legitimately
  * differs from the legacy original; that is by design, never drift, and is never checked
  * against the original hash above. The legacy original itself is never imported for a
  * Group A approval -- only ever read here, to confirm it has not silently drifted.
  * @return array{original_source_integrity:bool,staged_artifact_integrity:bool}
  */
 private static function media_integrity( array $e ): array {
  $originalOk = true;
  foreach ( array_merge( array( $e['legacy_file'] ), $e['binary_aliases'] ?? array() ) as $path ) {
   if ( ! hash_equals( $e['row']['sha256'], hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $path ) ) ) ) { $originalOk = false; break; }
  }
  $stagedOk = hash_equals( $e['data']['sha256'], hash_file( 'sha256', Storage::path( $e['data']['package_asset'] ) ) );
  return array( 'original_source_integrity' => $originalOk, 'staged_artifact_integrity' => $stagedOk );
 }
 /**
  * Execution-time content validation. Kept separate from media() (which has the
  * side-effecting sideload call) purely so it stays a small, pure, independently
  * testable predicate — never so it can be called before the hash-drift check below.
  *
  * Second condition is an explicit, hash-gated authorization check, never a relaxation
  * of the first: Media::file_valid() is called first and unmodified; the PdfApprovals
  * fallback only ever returns true for the exact legacy_path+sha256 pairs a human
  * approved in pdf-approvals.json (Group B), and only for application/pdf. Every other
  * file — approved-or-not, PDF-or-not — sees exactly the prior behaviour.
  */
 private static function media_is_valid( string $source, string $mime, string $legacyPath ): bool {
  if ( \PSIndustrial\Core\Media::file_valid( $source, $mime ) ) { return true; }
  return 'application/pdf' === $mime && PdfApprovals::isApprovedFalsePositive( $legacyPath, hash_file( 'sha256', $source ) );
 }
 /**
  * Narrowly-scoped override for the SECOND, independent validation WordPress itself runs
  * during media_handle_sideload() (Media::upload(), on wp_handle_sideload_prefilter) — the
  * generic, always-on filter that protects every upload/sideload in the whole install,
  * including the admin UI, and knows nothing about PdfApprovals' Group B exceptions.
  *
  * Never disables or relaxes Media::file_valid()/Media::upload() itself, never whitelists by
  * filename: re-verifies, from scratch, on every single invocation --
  *  (a) this is the EXACT temp file Runner::media() just created for THIS entity ($tmp),
  *      never any other file mid-upload elsewhere in the request;
  *  (b) Media::upload() actually rejected it (nothing to override otherwise);
  *  (c) the source bytes still match the hash Runner::media() already re-verified above;
  *  (d) it is a PDF with an exact-hash, path-matched PdfApprovals Group B exception --
  *      never Group A (a sanitized substitute already passes Media::file_valid() alone; see
  *      media_is_valid()'s own docblock), never an unapproved file, never a real threat.
  * Only then clears the rejection; otherwise returns $file untouched, including any other
  * error Media::upload() itself set.
  */
 private static function approved_sideload_override( array $file, string $tmp, string $source, array $d ): array {
  if ( ( $file['tmp_name'] ?? '' ) !== $tmp || empty( $file['error'] ) ) { return $file; }
  if ( ! hash_equals( $d['sha256'], hash_file( 'sha256', $source ) ) ) { return $file; }
  if ( 'application/pdf' !== $d['mime'] || ! PdfApprovals::isApprovedFalsePositive( $d['path'] ?? '', hash_file( 'sha256', $source ) ) ) { return $file; }
  $file['error'] = '';
  return $file;
 }
 private static function media( array $e ): int {
  $d = $e['data']; $source = Storage::path( $d['package_asset'] );
  if ( ! hash_equals( $d['sha256'], hash_file( 'sha256', $source ) ) || ! self::media_is_valid( $source, $d['mime'], $d['path'] ?? '' ) ) { throw new \RuntimeException( 'MEDIA_CHANGED_OR_UNSAFE' ); }
  require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
  $ext = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf' )[ $d['mime'] ];
  $name = sanitize_file_name( pathinfo( $d['name'], PATHINFO_FILENAME ) ) . '.' . $ext;
  $tmp = wp_tempnam( $name, Storage::root() );
  try {
   if ( ! copy( $source, $tmp ) ) { throw new \RuntimeException( 'MEDIA_COPY_FAILED' ); }
   // Installed only around this one sideload call, for this one temp file, and removed
   // immediately after in `finally` regardless of outcome -- never affects any other upload,
   // concurrent or later, on this or any other request. Priority 20 (after Media::upload()'s
   // default 10) so it only ever runs AFTER and reacts to a rejection Media::upload() itself
   // already produced; it never runs first and never hides Media::upload() from anything.
   $override = static fn( array $file ): array => self::approved_sideload_override( $file, $tmp, $source, $d );
   add_filter( 'wp_handle_sideload_prefilter', $override, 20 );
   try {
    $id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, null, array( 'post_title' => sanitize_text_field( $d['name'] ) ) );
   } finally {
    remove_filter( 'wp_handle_sideload_prefilter', $override, 20 );
   }
   if ( is_wp_error( $id ) ) { throw new \RuntimeException( 'MEDIA_SIDELOAD_FAILED' ); }
   Identity::set( $e, $id, '_psi_content_sha256', $d['sha256'] ); Identity::set( $e, $id, '_psi_original_name', $d['name'] ); return $id;
  } finally { if ( is_file( $tmp ) ) { wp_delete_file( $tmp ); } }
 }
}

<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

final class Runner {
 /** Distinct from the subset phrase by construction -- never accepted by batch() (scope
  *  check would still refuse a 'full' plan even if it were), never accepted by
  *  batch_full_local_resolved_only() for a 'subset' plan. Neither phrase authorizes the
  *  other's scope; see migration/Runner.php's own checks below, not this string alone. */
 private const FULL_CONFIRMATION = 'IMPORTAR FULL LOCAL RESUELTO';
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
   foreach ( array_merge( array( $e['legacy_file'] ), $e['binary_aliases'] ?? array() ) as $path ) {
    if ( ! hash_equals( $e['data']['sha256'], hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $path ) ) ) ) { throw new \RuntimeException( 'MEDIA_CHANGED_REPLAN' ); }
   }
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
 private static function media( array $e ): int {
  $d = $e['data']; $source = Storage::path( $d['package_asset'] );
  if ( ! hash_equals( $d['sha256'], hash_file( 'sha256', $source ) ) || ! self::media_is_valid( $source, $d['mime'], $d['path'] ?? '' ) ) { throw new \RuntimeException( 'MEDIA_CHANGED_OR_UNSAFE' ); }
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

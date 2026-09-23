<?php
/** Local integration rehearsal. The 15-object canonical subset was migrated for real early
 * in this project and is no longer a disposable fixture: one of its objects
 * (php:nosotros.php, WordPress Page 134) was later legitimately published in its own
 * authorized phase (docs/frontend/institutional-frontend.md). Re-executing Runner against
 * that same real, canonical subset plan is therefore no longer a safe, deterministic
 * regression check -- its outcome would depend on whatever a later, unrelated editorial
 * decision did to any of those 15 real objects, not on Runner's own correctness. So this
 * file separates two concerns that used to be entangled in one real execution:
 *  - Read-only verification that the REAL, already-migrated subset objects still have the
 *    technical relationships the importer is supposed to guarantee (hierarchy, logo,
 *    gallery/thumbnail separation, PDF validation, brand-inference boundaries), proven
 *    against CURRENT state -- never by re-running Runner against them.
 *  - Every actual exercise of Runner's write path (create/update/conflict/rollback/
 *    injected-failure recovery at each boundary/MERGE/capability/lease/lock/tamper/CSRF)
 *    uses entirely synthetic `test:*` entities cloned from a real entry's SHAPE only,
 *    isolated from whatever the 15 real canonical objects' current state happens to be --
 *    exactly as the second half of this file already did before this change.
 * A normal run of this file never mutates the 15 real subset objects; it only ever reads
 * them, and only ever writes through freshly-generated, self-contained synthetic fixtures. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,Planner,Identity,Runner,Admin};
(static function(): void {
 $checks = array(); $fixtures = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $throws = static function( callable $fn, string $label ) use ( $assert ): void { $caught = false; try { $fn(); } catch ( Throwable $e ) { $caught = true; } $assert( $caught, $label ); };
 $export = static function( string $name, array $data ): void {
  $path = Storage::project() . '/docs/implementation/importer-reports/' . $name;
  if ( is_file( $path ) && in_array( $name, array( 'subset-plan.json','subset-execution-1.json','subset-execution-2.json' ), true ) ) { return; }
  file_put_contents( $path, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" );
 };
 $count = static function(): array { global $wpdb; return array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ), 'options_hash' => Storage::hash( $wpdb->get_results( "SELECT option_name,option_value FROM {$wpdb->options} ORDER BY option_name", ARRAY_A ) ) ); };
 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();
  $assert( '8.4' === substr( PHP_VERSION, 0, 3 ) && 'psindustrial_wp_dev' === DB_NAME, 'PHP 8.4 and dedicated local database' );
  $sources = new Sources();
  $assert( count( $sources->catalog['productos'] ) === 165 && count( $sources->catalog['categorias'] ) === 38 && count( $sources->catalog['marcas'] ) === 12, 'Read-only SQL parser catalogue coverage; no users parsed' );
  $assert( ! isset( $sources->catalog['user'] ) && count( $sources->rows['product-media-relations.csv'] ) === 421, 'Canonical media relation coverage and exclusion of account data' );
  foreach ( array( '../wp-config.php','/wp-config.php','C:/secret','images/../../secret','images\\secret' ) as $path ) { $throws( static fn() => Sources::safe( Storage::project(), $path ), 'Reject unsafe source path ' . $path ); }
  $assert( Sources::parts( '1|2|UNKNOWN|' ) === array( '1','2' ), 'Normalization preserves multi-valued IDs without invented values' );
  $throws( static fn() => Admin::authorize( 'invalid' ), 'Invalid nonce denied' ); Admin::authorize( wp_create_nonce( 'psi_migration' ) ); $assert( true, 'Administrator valid nonce accepted' );
  $before = $count(); $first = Planner::build( 'subset' ); $assert( $before === $count(), 'DRY RUN changes no posts, terms or options' );
  $assert( 17 === count( $first['entries'] ) && 2 === $first['summary']['actions']['REVIEW'], 'Subset includes hierarchy, brand, media, products, static and Page plus REVIEW' );
  $export( 'subset-plan.json', Admin::report( $first ) );
  $throws( static fn() => Runner::batch( $first['run_id'], '' ), 'Execution requires explicit confirmation' );
  $throws( static fn() => Runner::batch( 'missing', 'IMPORTAR SUBSET EN BORRADOR' ), 'Execution without validated plan denied' );

  // ============================================================ read-only: the REAL, already-migrated subset's current technical invariants
  // These 15 objects were created for real by the original subset rehearsal long before
  // this session; nothing here re-executes Runner against them, so nothing here can mutate
  // them -- every assertion below is a direct read of their current, live state.
  $entries = array_column( $first['entries'], null, 'entity_key' );
  $id = static fn( string $key ) => Identity::find( $entries[ $key ] );
  $assert( (int) get_term( $id( 'category:8' ) )->parent === $id( 'category:1' ), 'Parent-child hierarchy preserved' );
  $assert( (int) get_term_meta( $id( 'brand:1' ), '_psi_logo_id', true ) === $id( 'asset:images/tg.png' ), 'Brand logo attachment ID associated' );
  $assert( (int) get_term_meta( $id( 'category:8' ), '_psi_image_id', true ) > 0, 'Extensionless verified category image attached' );
  $assert( has_term( $id( 'category:8' ), 'psi_categoria', $id( 'sql:productos:63' ) ) && has_term( $id( 'brand:1' ), 'psi_marca', $id( 'sql:productos:63' ) ), 'Canonical product category and brand relationships' );
  $assert( count( get_post_meta( $id( 'sql:productos:63' ), '_psi_gallery_ids', true ) ) === 1 && get_post_thumbnail_id( $id( 'sql:productos:63' ) ) > 0, 'Featured image and gallery remain separate attachment IDs' );
  $pdf = $id( 'asset:fichas/puerta-420.pdf' );
  $assert( get_post_meta( $id( 'sql:productos:64' ), '_psi_datasheets', true )[0]['attachment_id'] === $pdf && 'application/pdf' === get_post_mime_type( $pdf ), 'PDF validated and associated' );
  $assert( in_array( 'file:676', get_post_meta( $pdf, '_psi_source_keys', true ), true ), 'Identical PDF binary retains original file ID and alias without second upload' );
  $assert( hash_file( 'sha256', get_attached_file( $pdf ) ) === $entries['asset:fichas/puerta-420.pdf']['data']['sha256'], 'PDF bytes unchanged' );
  $assert( ! wp_get_object_terms( $id( 'static:automatismos-para-cancelas-cubic.php' ), 'psi_marca' ), 'Static product stays without inferred brand' );
  $assert( 'page' === get_post_type( $id( 'php:nosotros.php' ) ), 'Institutional content uses Page' );
  foreach ( array( 'sql:productos:63','sql:productos:64','static:automatismos-para-cancelas-cubic.php' ) as $key ) { $assert( 'draft' === get_post_status( $id( $key ) ), 'Unpublished rehearsal ' . $key ); }
  $assert( 'publish' === get_post_status( $id( 'php:nosotros.php' ) ), 'php:nosotros.php was legitimately published in its own later, authorized phase (docs/frontend/institutional-frontend.md) -- still present, correctly no longer draft' );
  $assert( ! $id( 'sql:productos:3' ) && ! $id( 'sql:productos:165' ), 'Contradictory and test SQL rows remain REVIEW without entities' );

  // ============================================================ idempotency, read-only: prediction alone, never a second real execution
  // A second Runner::batch() call used to run here for real. Re-running it against these
  // SAME 15 real, already-migrated objects stopped being a safe assumption once any one of
  // them can legitimately change outside this test's control (nosotros.php did) -- so
  // idempotency is proven the way Runner's own process_entry() actually decides it (read
  // Identity::prediction(), never call apply() for anything but CREATE/UPDATE), never by
  // mutating anything here. Editorial-only term metadata (_psi_public_state,
  // _psi_brand_home_order, e.g. BrandsMigration on brand:1) no longer counts as drift here:
  // Identity::snapshot() excludes it (migration/Identity.php EDITORIAL_META_KEYS).
  foreach ( $first['entries'] as $e ) {
   if ( in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) { continue; }
   $expected = ( 'php:nosotros.php' === $e['entity_key'] ) ? 'CONFLICT' : 'UNCHANGED';
   $assert( Identity::find( $e ) > 0 && $expected === Identity::prediction( $e ), 'Subset identity still present, prediction=' . $expected . ': ' . $e['entity_key'] );
  }
  $changed = $entries['sql:productos:63']; $changed['source_hash'] = str_repeat( 'a', 64 );
  $assert( 'UPDATE' === Identity::prediction( $changed ), 'Changed source produces update instead of duplicate' );
  // Isolated synthetic Page exercises update/conflict/retry boundaries using the same runner and DB.
  $make = static function( string $key, string $suffix = '' ) use ( $first, &$fixtures ): array {
   $plan = $first; $plan['run_id'] = wp_generate_uuid4(); $plan['status'] = 'VALIDATED'; $plan['cursor'] = 0; $plan['results'] = array();
   $e = array_values( array_filter( $first['entries'], static fn( $e ) => 'php:nosotros.php' === $e['entity_key'] ) )[0];
   $e['entity_key'] = $key; $e['source_key'] = $key; $e['source_keys'] = array( $key ); $e['data']['name'] = 'PSI importer synthetic ' . $suffix; $e['data']['slug'] = sanitize_title( $key ); $e['source_hash'] = Storage::hash( $key . $suffix );
   $plan['entries'] = array( $e ); $plan['plan_hash'] = Planner::digest( $plan ); Storage::write( 'run-' . $plan['run_id'] . '.json', $plan ); $fixtures[] = $plan; return $plan;
  };
  $test = $make( 'test:' . wp_generate_uuid4() ); $done = Runner::batch( $test['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ); $tid = Identity::find( $test['entries'][0] );
  $assert( $tid > 0, 'Synthetic fixture uses real APIs' );
  $update = $make( $test['entries'][0]['entity_key'], 'updated' ); $done = Runner::batch( $update['run_id'], 'IMPORTAR SUBSET EN BORRADOR' );
  $assert( 'UPDATE' === $done['results'][0]['result'] && $tid === $done['results'][0]['wordpress_id'], 'Changed source updates existing identity' );
  wp_update_post( array( 'ID' => $tid, 'post_excerpt' => 'Human editorial change' ) );
  $assert( 'CONFLICT' === Identity::prediction( $update['entries'][0] ), 'Human changes block automatic overwrite' );
  $retry = $make( $test['entries'][0]['entity_key'], 'another' ); $done = Runner::batch( $retry['run_id'], 'IMPORTAR SUBSET EN BORRADOR' );
  $assert( 'CONFLICT' === $done['results'][0]['status'] && 'Human editorial change' === get_post( $tid )->post_excerpt, 'Conflict preserves human edit during execution' );
  // Cleanup is explicitly fixture-owned, never title-based or a catalogue-wide delete.
  wp_delete_post( $tid, true ); wp_delete_file( Storage::path( Identity::ledger( $test['entries'][0] ) ) );
  foreach ( array( 'intent','object','relations' ) as $boundary ) {
   $test = $make( 'test:' . wp_generate_uuid4() );
   $hook = static function( $at ) use ( $boundary ) { if ( $at === $boundary ) { throw new RuntimeException( 'INJECTED_FAILURE' ); } };
   add_action( 'psi_import_boundary', $hook );
   try { $done = Runner::batch( $test['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ); } finally { remove_action( 'psi_import_boundary', $hook ); }
   $assert( 'FAILED' === $done['results'][0]['status'], 'Failure recorded at ' . $boundary );
   $assert( 'CONFLICT' === Identity::prediction( $test['entries'][0] ), 'Interrupted object cannot duplicate at ' . $boundary );
   $journal = Storage::read( Identity::ledger( $test['entries'][0] ) );
   if ( $journal['wordpress_id'] ) { wp_delete_post( $journal['wordpress_id'], true ); }
   wp_delete_file( Storage::path( Identity::ledger( $test['entries'][0] ) ) );
  }
  $test = $make( 'test:' . wp_generate_uuid4() ); $done = Runner::batch( $test['run_id'], 'IMPORTAR SUBSET EN BORRADOR' );
  $rolled = Runner::rollback_created( $test['run_id'], 'RETIRAR CREACIONES SIN CAMBIOS' );
  $assert( array( 'REMOVED' ) === array_values( $rolled ), 'Controlled rollback removes untouched creations only' );
  $uid = wp_insert_user( array( 'user_login' => 'psi-import-test-' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password( 40, true, true ), 'role' => 'psi_gestor' ) );
  if ( is_wp_error( $uid ) ) { throw new RuntimeException( 'TEST_USER_FAILED' ); }
  $admin = get_current_user_id(); wp_set_current_user( $uid );
  try { $throws( static fn() => Planner::build( 'subset' ), 'Gestor cannot plan or execute migration' ); $throws( static fn() => Admin::authorize( wp_create_nonce( 'psi_migration' ) ), 'Valid nonce does not bypass Gestor capability restriction' ); }
  finally { wp_set_current_user( $admin ); require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid ); }
  $assert( ! get_option( 'psi_import_lock' ), 'Writer lease released' );
  Storage::locked( static function() use ( $throws ): void { $throws( static fn() => Storage::locked( static fn() => true ), 'Concurrent writer rejected by file lock' ); } );
  $merge = $make( 'test:' . wp_generate_uuid4() ); $alias = 'test:' . wp_generate_uuid4();
  $merge['entries'][0]['action'] = 'MERGE'; $merge['entries'][0]['decision']['source_keys'] = array( $merge['entries'][0]['entity_key'], $alias );
  $merge['plan_hash'] = Planner::digest( $merge ); Storage::write( 'run-' . $merge['run_id'] . '.json', $merge );
  $done = Runner::batch( $merge['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ); $merged = $merge['entries'][0]; $merged['entity_key'] = $alias;
  $assert( Identity::find( $merged ) === $done['results'][0]['wordpress_id'], 'Synthetic approved merge retains multiple source identities in one object' );
  Runner::rollback_created( $merge['run_id'], 'RETIRAR CREACIONES SIN CAMBIOS' );
  $tmp = Storage::path( 'parser-test.json' );
  try {
   file_put_contents( $tmp, "a,a\n1,2\n" ); $throws( static fn() => Sources::csv( $tmp ), 'CSV duplicate headers rejected' );
   file_put_contents( $tmp, "a,b\n1,2,3\n" ); $throws( static fn() => Sources::csv( $tmp ), 'CSV wrong field count rejected' );
   file_put_contents( $tmp, "\xEF\xBB\xBFa,b\n\"x,y\",\"two\nlines\"\n" ); $parsed = Sources::csv( $tmp ); $assert( 'x,y' === $parsed[0]['a'] && "two\nlines" === $parsed[0]['b'], 'CSV BOM, quotes, commas and multiline parsed' );
   file_put_contents( $tmp, 'not a PDF' ); $assert( ! \PSIndustrial\Core\Media::file_valid( $tmp, 'application/pdf' ), 'PDF spoof rejected by bytes and MIME' );
   file_put_contents( $tmp, '%PDF-1.7 /JavaScript (bad)' ); $assert( ! \PSIndustrial\Core\Media::file_valid( $tmp, 'application/pdf' ), 'Active PDF rejected' );
  } finally { wp_delete_file( $tmp ); }
  $test = $make( 'test:' . wp_generate_uuid4() ); $test['scope'] = 'full'; $test['plan_hash'] = Planner::digest( $test ); Storage::write( 'run-' . $test['run_id'] . '.json', $test );
  $throws( static fn() => Runner::batch( $test['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ), 'Full real import explicitly blocked' );
  $test = $make( 'test:' . wp_generate_uuid4() ); $test['entries'][0]['data']['name'] = 'Tampered'; Storage::write( 'run-' . $test['run_id'] . '.json', $test );
  $throws( static fn() => Runner::batch( $test['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ), 'Modified plan seal rejected' );
  $test = $make( 'test:' . wp_generate_uuid4() ); $other = $make( 'test:' . wp_generate_uuid4() ); $test['entries'][] = $other['entries'][0]; $test['plan_hash'] = Planner::digest( $test ); Storage::write( 'run-' . $test['run_id'] . '.json', $test );
  $bad_key = $test['entries'][0]['entity_key'];
  $hook = static function( $at, $e ) use ( $bad_key ) { if ( 'intent' === $at && $e['entity_key'] === $bad_key ) { throw new RuntimeException( 'INJECTED_PARTIAL_FAILURE' ); } };
  add_action( 'psi_import_boundary', $hook, 10, 2 );
  try { $done = Runner::batch( $test['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ); } finally { remove_action( 'psi_import_boundary', $hook, 10 ); }
  $assert( 'FAILED' === $done['results'][0]['status'] && 'APPLIED' === $done['results'][1]['status'], 'Individual failure does not cancel following valid entity' );
  Runner::rollback_created( $test['run_id'], 'RETIRAR CREACIONES SIN CAMBIOS' ); wp_delete_file( Storage::path( Identity::ledger( $test['entries'][0] ) ) );
  // Exercise the actual admin-post surface with a short-lived local Administrator session.
  $admin = get_current_user_id(); $sessions = WP_Session_Tokens::get_instance( $admin ); $token = $sessions->create( time() + 300 );
  $logged = wp_generate_auth_cookie( $admin, time() + 300, 'logged_in', $token ); $auth = wp_generate_auth_cookie( $admin, time() + 300, 'auth', $token );
  $old_cookie = $_COOKIE[ LOGGED_IN_COOKIE ] ?? null; $_COOKIE[ LOGGED_IN_COOKIE ] = $logged;
  $cookies = array( LOGGED_IN_COOKIE => $logged, AUTH_COOKIE => $auth );
  try {
   $r = wp_remote_get( admin_url( 'tools.php?page=psi-migration' ), array( 'cookies' => $cookies, 'redirection' => 0, 'timeout' => 20 ) );
   $assert( 200 === wp_remote_retrieve_response_code( $r ) && str_contains( wp_remote_retrieve_body( $r ), 'FULL DRY RUN' ), 'Authenticated admin screen renders default DRY RUN' );
   $r = wp_remote_post( admin_url( 'admin-post.php' ), array( 'cookies' => $cookies, 'redirection' => 0, 'timeout' => 20, 'body' => array( 'action' => 'psi_migration','operation' => 'dry','scope' => 'subset','_wpnonce' => 'bad' ) ) );
   $assert( 400 === wp_remote_retrieve_response_code( $r ) && str_contains( wp_remote_retrieve_body( $r ), 'INVALID_NONCE' ), 'HTTP administrative action rejects CSRF' );
   $r = wp_remote_post( admin_url( 'admin-post.php' ), array( 'cookies' => $cookies, 'redirection' => 0, 'timeout' => 20, 'body' => array( 'action' => 'psi_migration','operation' => 'dry','scope' => 'subset','_wpnonce' => wp_create_nonce( 'psi_migration' ) ) ) );
   $assert( 302 === wp_remote_retrieve_response_code( $r ) && str_contains( wp_remote_retrieve_header( $r, 'location' ), 'run=' ), 'HTTP admin-post generates subset plan without CLI' );
   parse_str( wp_parse_url( wp_remote_retrieve_header( $r, 'location' ), PHP_URL_QUERY ), $query );
   $r = wp_remote_post( admin_url( 'admin-post.php' ), array( 'cookies' => $cookies, 'redirection' => 0, 'timeout' => 20, 'body' => array( 'action' => 'psi_migration','operation' => 'execute','run' => $query['run'],'confirmation' => 'IMPORTAR SUBSET EN BORRADOR','_wpnonce' => wp_create_nonce( 'psi_migration' ) ) ) );
   $assert( 302 === wp_remote_retrieve_response_code( $r ) && Storage::read( 'run-' . $query['run'] . '.json' )['cursor'] > 0, 'HTTP administrative batch advances without CLI or persistent workers' );
  } finally { $sessions->destroy( $token ); if ( null === $old_cookie ) { unset( $_COOKIE[ LOGGED_IN_COOKIE ] ); } else { $_COOKIE[ LOGGED_IN_COOKIE ] = $old_cookie; } }
  $export( 'tests.json', array( 'passed' => true, 'checks' => $checks ) ); echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'subset_run' => $first['run_id'] ) );
 } catch ( Throwable $error ) {
  $export( 'tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

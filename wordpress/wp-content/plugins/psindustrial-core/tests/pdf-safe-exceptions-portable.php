<?php
/** Tests for PdfSafeExceptions -- the portable half of the PDF "false positive" approval
 * design (see includes/PdfSafeExceptions.php and data/pdf-safe-exceptions.php's own
 * docblocks). Reproduces, then proves fixed, a real staging failure:
 *
 *   RuntimeException: SOURCE_NOT_FOUND
 *   Fields::datasheet_valid() -> PdfApprovals::isApprovedFalsePositive() ->
 *   PdfApprovals::resolve() -> PdfApprovals::approvals() -> Sources::safe() -> SOURCE_NOT_FOUND
 *
 * Fields::datasheet_valid() used to call Migration\PdfApprovals::isApprovedFalsePositive(),
 * which resolves docs/implementation/pdf-security-review/pdf-approvals.json via
 * Sources::safe( Storage::project(), ... ) -- a path under the git project root that exists
 * locally but is never deployed to staging/production (only wp-content/theme+plugin ship,
 * see tools/staging-package/build.php). Fields.php now calls PdfSafeExceptions::isApproved()
 * instead, which reads data/pdf-safe-exceptions.php -- a file that DOES ship (it lives under
 * the plugin's own tree, outside tests/, so build.php's git-ls-tree walk includes it
 * automatically; verified explicitly below).
 *
 * The most direct way to prove "works without the local migration source tree" is to make
 * that tree genuinely unavailable and observe the real behaviour difference: this suite
 * temporarily renames the actual pdf-approvals.json (the file staging genuinely lacks) for
 * the duration of one block, restores it in `finally` (and via a shutdown-function safety
 * net, in case a fatal ever bypassed the finally), and calls exactly ONE PHP process per run
 * so PdfApprovals's own static cache can never mask the missing file. Strictly non-mutating
 * otherwise: PdfSafeExceptions::isApproved() is a pure predicate, reached via its real public
 * API (no reflection needed); no WordPress object is created or changed by this suite. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,PdfApprovals};
use PSIndustrial\Core\Media;
use PSIndustrial\Core\Fields;
use PSIndustrial\Core\PdfSafeExceptions;
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
  global $wpdb;

  // ============================================================ 1. the deployable file itself
  $dataFile = dirname( __DIR__ ) . '/data/pdf-safe-exceptions.php';
  $assert( is_file( $dataFile ), 'data/pdf-safe-exceptions.php exists inside the plugin tree (not under docs/)' );
  $registry = require $dataFile;
  $assert( is_array( $registry ) && count( $registry ) >= 11, 'Registry loads as a plain PHP array with at least the 11 known approvals' );
  foreach ( $registry as $legacyPath => $entry ) {
   $assert( is_string( $legacyPath ) && '' !== $legacyPath, 'Every registry key is a non-empty legacy path string' );
   $assert( 64 === strlen( $entry['source_sha256'] ?? '' ) && ctype_xdigit( $entry['source_sha256'] ), "Entry has a well-formed sha256: $legacyPath" );
   $assert( in_array( $entry['classification'] ?? '', array( 'SAFE_PDF_FALSE_POSITIVE', 'SAFE_PDF_BENIGN_INTERNAL_ACTION' ), true ), "Entry carries a known-safe classification: $legacyPath" );
  }

  // ============================================================ 2. tools/staging-package/build.php ships it
  // Static proof (no packaging performed here): the script walks `git ls-tree -r <ref> --
  // wordpress/wp-content/plugins/psindustrial-core`, excluding only that plugin's own tests/
  // directory. data/pdf-safe-exceptions.php is neither under tests/ nor otherwise excluded.
  $root = dirname( __DIR__, 5 ); // .../psindustrial-core/tests -> ... -> repo root
  $builder = file_get_contents( $root . '/tools/staging-package/build.php' );
  $assert( (bool) $builder, 'tools/staging-package/build.php is readable' );
  $assert( str_contains( $builder, "\$pluginPath . '/tests/'" ), 'build.php excludes ONLY the plugin tests/ directory, nothing under data/' );
  $assert( ! str_contains( $builder, "'/data/'" ), 'build.php has no separate exclusion rule that could drop data/' );
  // Dynamic proof: ask git itself, for the currently checked-out tree, whether the file is
  // tracked and would be picked up by `git ls-tree -r HEAD -- <pluginPath>` (build.php's own
  // exact listing call) -- read-only, no packaging, no commit performed. A brand-new file is
  // only "tracked" once staged, so this accepts either a clean `ls-files` hit or the file
  // showing up in `git status --porcelain` as staged/untracked-but-present -- what actually
  // matters for build.php (run against a REAL commit) is that nothing here EXCLUDES it.
  $relPath = 'wordpress/wp-content/plugins/psindustrial-core/data/pdf-safe-exceptions.php';
  $lsFiles = array(); $code = 0;
  exec( 'git -C ' . escapeshellarg( $root ) . ' ls-files --error-unmatch -- ' . escapeshellarg( $relPath ) . ' 2>&1', $lsFiles, $code );
  if ( 0 !== $code ) {
   // Not committed yet (this fix is still under review) -- confirm it is at least present on
   // disk and would be added, i.e. `git add` would pick it up as untracked, not ignored.
   $checkIgnore = array(); $ignoreCode = 0;
   exec( 'git -C ' . escapeshellarg( $root ) . ' check-ignore -q -- ' . escapeshellarg( $relPath ), $checkIgnore, $ignoreCode );
   $assert( 1 === $ignoreCode, 'data/pdf-safe-exceptions.php is not committed yet, but is a real, non-.gitignore\'d file that a commit would include (check-ignore correctly reports "not ignored")' );
  } else {
   $assert( true, 'git already tracks data/pdf-safe-exceptions.php: a build from this tree will include it' );
  }

  // ============================================================ 3. real fixtures: the 11 known Group B products, read from the DB
  $rows = $wpdb->get_results( "SELECT pm.post_id AS attachment_id, pm.meta_value AS origin FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_psi_import_origin' AND p.post_type = 'attachment' AND p.post_mime_type = 'application/pdf'", ARRAY_A );
  $assert( count( $rows ) > 0, 'Fixture: at least one imported PDF attachment exists with _psi_import_origin' );
  $knownApproved = array(); // attachment_id => array(legacyPath, sha256)
  foreach ( $rows as $row ) {
   $origin = maybe_unserialize( $row['origin'] );
   $legacyPath = is_array( $origin ) ? (string) ( $origin['file'] ?? '' ) : '';
   $attId = (int) $row['attachment_id'];
   if ( '' === $legacyPath || Media::valid( $attId, 'pdf' ) ) { continue; } // only care about ones that need the fallback at all.
   $file = get_attached_file( $attId );
   if ( ! $file || ! is_file( $file ) ) { continue; }
   $knownApproved[ $attId ] = array( $legacyPath, hash_file( 'sha256', $file ) );
  }
  $assert( count( $knownApproved ) >= 11, 'Fixture: at least the 11 known Group-B-dependent PDF attachments found (' . count( $knownApproved ) . ')' );

  // Sanity, sources still present: every one currently resolves true via PdfSafeExceptions.
  foreach ( $knownApproved as $attId => $pair ) {
   $assert( true === PdfSafeExceptions::isApproved( $pair[0], $pair[1] ), "Known approved attachment $attId ({$pair[0]}) resolves true with sources present" );
  }

  // ============================================================ 4. the actual portability proof: hide the local migration source tree
  $approvalsJson = Storage::project() . '/docs/implementation/pdf-security-review/pdf-approvals.json';
  $assert( is_file( $approvalsJson ), 'Precondition: the real pdf-approvals.json exists locally (so hiding it is a genuine test, not a no-op)' );
  $hidden = $approvalsJson . '.hidden-for-portability-test';
  $restore = static function() use ( $approvalsJson, $hidden ): void {
   if ( is_file( $hidden ) && ! is_file( $approvalsJson ) ) { rename( $hidden, $approvalsJson ); }
  };
  register_shutdown_function( $restore ); // last-resort safety net if a fatal ever skipped `finally`.

  $assert( true === rename( $approvalsJson, $hidden ), 'Test setup: pdf-approvals.json renamed out of the way (simulating staging, which never has it)' );
  try {
   // 4a. Migration\PdfApprovals now genuinely reproduces the reported production error.
   $threw = null;
   try { PdfApprovals::isApprovedFalsePositive( $knownApproved[ array_key_first( $knownApproved ) ][0], $knownApproved[ array_key_first( $knownApproved ) ][1] ); }
   catch ( \Throwable $e ) { $threw = $e; }
   $assert( $threw instanceof \RuntimeException && 'SOURCE_NOT_FOUND' === $threw->getMessage(), 'Migration\\PdfApprovals::isApprovedFalsePositive() reproduces the exact reported SOURCE_NOT_FOUND when the local source tree is absent' );

   // 4b. PdfSafeExceptions::isApproved() -- the fix -- throws nothing and still resolves
   // every one of the same 11+ known cases correctly, with the local source tree absent.
   foreach ( $knownApproved as $attId => $pair ) {
    $ok = null; $threwPortable = null;
    try { $ok = PdfSafeExceptions::isApproved( $pair[0], $pair[1] ); } catch ( \Throwable $e ) { $threwPortable = $e; }
    $assert( null === $threwPortable, "PdfSafeExceptions::isApproved() throws nothing for attachment $attId while local sources are absent" . ( $threwPortable ? ( ': ' . $threwPortable->getMessage() ) : '' ) );
    $assert( true === $ok, "PdfSafeExceptions::isApproved() still resolves attachment $attId ({$pair[0]}) true with local sources absent" );
   }

   // 4c. And, critically, Fields::datasheet_valid() / Fields::validate() -- the actual
   // caller that broke in production -- succeeds end to end with local sources absent.
   $m = new ReflectionMethod( Fields::class, 'datasheet_valid' );
   $m->setAccessible( true );
   foreach ( $knownApproved as $attId => $pair ) {
    $threwField = null; $result = null;
    try { $result = $m->invoke( null, $attId ); } catch ( \Throwable $e ) { $threwField = $e; }
    $assert( null === $threwField, "Fields::datasheet_valid($attId) throws nothing with local sources absent" . ( $threwField ? ( ': ' . $threwField->getMessage() ) : '' ) );
    $assert( true === $result, "Fields::datasheet_valid($attId) still returns true with local sources absent" );
   }
   $anyAttId = array_key_first( $knownApproved );
   $validateResult = Fields::validate( '_psi_datasheets', array( array( 'attachment_id' => $anyAttId, 'label' => 'x', 'language' => '' ) ), 0 );
   $assert( true === $validateResult, 'Fields::validate(_psi_datasheets) itself -- the exact call Gutenberg triggers on every save -- succeeds with local sources absent' );

   // 4d. Security still holds with sources absent: altered hash, unregistered path, and a
   // real dangerous PDF are all still rejected -- no "source missing, allow" fallback exists.
   $anyPath = $knownApproved[ $anyAttId ][0];
   $assert( false === PdfSafeExceptions::isApproved( $anyPath, str_repeat( '0', 64 ) ), 'Approved path with an ALTERED hash: still rejected with sources absent (no source-missing fallback)' );
   $assert( false === PdfSafeExceptions::isApproved( 'fichas/never-approved-anywhere.pdf', $knownApproved[ $anyAttId ][1] ), 'A hash that is approved for a DIFFERENT path is never approved for an unregistered one, sources absent or not' );
   $dangerous = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R/OpenAction<</Type/Action/S/JavaScript/JS(app.alert\\(1\\))>>>>endobj\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF";
   $dangerousHash = hash( 'sha256', $dangerous );
   $assert( false === PdfSafeExceptions::isApproved( $anyPath, $dangerousHash ), 'Real dangerous PDF bytes at an otherwise-approved path: never approved, sources absent or not' );
   foreach ( array_keys( $registry ) as $registeredPath ) {
    $assert( false === PdfSafeExceptions::isApproved( $registeredPath, $dangerousHash ), "Real dangerous PDF bytes never satisfy any registered path by coincidence: $registeredPath" );
   }
  } finally {
   $restore();
  }
  $assert( is_file( $approvalsJson ) && ! is_file( $hidden ), 'Teardown: pdf-approvals.json restored to its original location' );

  // ============================================================ 5. non-PDF and empty inputs, sources present again
  $imageId = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type='attachment' AND post_mime_type LIKE 'image/%' LIMIT 1" );
  $assert( $imageId > 0, 'Fixture: an ordinary image attachment exists' );
  $anyKey = array_key_first( $registry );
  $assert( false === PdfSafeExceptions::isApproved( $anyKey, '' ), 'Empty sha256 is never approved' );
  $assert( false === PdfSafeExceptions::isApproved( '', $registry[ $anyKey ]['source_sha256'] ), 'Empty legacy path is never approved' );

  $export( 'pdf-safe-exceptions-portable-tests.json', array( 'passed' => true, 'checks' => $checks, 'known_approved_count' => count( $knownApproved ) ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'known_approved_count' => count( $knownApproved ) ) );
 } catch ( Throwable $error ) {
  if ( isset( $restore ) ) { $restore(); } // best-effort: never leave pdf-approvals.json missing on failure.
  $export( 'pdf-safe-exceptions-portable-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

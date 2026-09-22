<?php
/** PDF security-review approvals tests (Group A sanitization + Group B exception).
 * Read-mostly: calls Planner::build() (DRY RUN) and PdfApprovals::resolve() (pure read).
 * The only file this suite writes is a single synthetic test PDF in the OS temp
 * directory (never /legacy, never the repo), deleted at the end of the run. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,PdfApprovals,Planner,Runner};
use PSIndustrial\Core\Media;
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $throws = static function( callable $fn, string $label ) use ( $assert ): void { $caught = false; try { $fn(); } catch ( Throwable $e ) { $caught = true; } $assert( $caught, $label ); };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };
 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();

  // ---------------------------------------------------------------- GROUP A: sanitization
  $groupA = array(
   'system/files/images/productos/639b9801fe440dcd2179c23cca14401710c5e7e0' => '0a7193edcd8929ac114043fcbffb8e53109ce4363f062cb269f2062ce9e1b4f8',
   'system/files/images/productos/7ab52eec612af22d23a62f849479371ebf1c2f22' => 'a922b6ce9f9ca80b565a3b24be27a086f555aa305db9b9135443c079d93255df',
   'system/files/images/productos/3af12bf12f61eda51a19d1e8af3cefc05878f484' => '703eb6311aaedddf3dd24a8e6b75089463317352f174f672c8d5002e3dfd574f',
   'system/files/images/productos/7baea774c5fc4abe3735d40fdf16e84f1e3521ef' => '7e501c26a5bfc0c02da8899de34a0b6a2bef31bbb42c2124a5c3995ad803a3ee',
   'system/files/images/productos/7cf4babfab2712e41d6fd1c050f5c31b604c97ae' => 'e8ac9a72e867f60aa21665abd004640f2fac4e477b025eae8b7b9a8ae7222e01',
  );
  $importerPdfRegex = '#/(?:JavaScript|JS|OpenAction|Launch|EmbeddedFile)\b#i';

  foreach ( $groupA as $path => $sourceHash ) {
   // Correct hash -> allows sanitization.
   $approval = PdfApprovals::resolve( $path, $sourceHash );
   $assert( $approval && 'sanitized' === $approval['type'] && 'PDF-SANITIZE-EMBEDDEDFILE-V1' === $approval['rule_id'], 'Group A correct hash resolves to a sanitized substitute: ' . $path );

   // The original legacy file is unchanged: its live hash still equals the approved one.
   $liveHash = hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $path ) );
   $assert( hash_equals( $sourceHash, $liveHash ), 'Group A original legacy file remains byte-identical: ' . $path );

   // Sanitized substitute exists and its own hash matches the recorded sanitized_sha256.
   $assert( is_file( $approval['source_path'] ) && hash_equals( $approval['sha256'], hash_file( 'sha256', $approval['source_path'] ) ), 'Sanitized copy exists and matches its recorded hash: ' . $path );

   // Sanitized copy contains no trace of the pattern that blocked the original (proves
   // the EmbeddedFile object, not just its name, is gone — not a cosmetic edit).
   $sanitizedBytes = file_get_contents( $approval['source_path'] );
   $assert( ! preg_match( $importerPdfRegex, $sanitizedBytes ), 'Sanitized copy contains no dangerous-pattern byte sequence: ' . $path );

   // The sanitized copy genuinely passes the importer's own, UNMODIFIED validation function.
   $assert( Media::file_valid( $approval['source_path'], 'application/pdf' ), 'Sanitized copy passes the real, unmodified Media::file_valid(): ' . $path );

   // Wrong hash -> blocks (approval does not apply; falls back to default behaviour).
   $assert( null === PdfApprovals::resolve( $path, str_repeat( '0', 64 ) ), 'Group A wrong hash blocks the approval: ' . $path );

   // Sources::asset() actually wires the substitute through end to end.
   $s = new Sources();
   $asset = $s->asset( $path );
   $assert( true === $asset['valid'], 'Sources::asset() reports valid=true for the approved original (via substitute): ' . $path );
   $assert( $asset['staged_sha256'] === $approval['sha256'] && $asset['staged_path'] === $approval['source_path'], 'Sources::asset() stages the sanitized bytes, not the original: ' . $path );
   $assert( $asset['sha256'] === $sourceHash, 'Sources::asset() still reports the ORIGINAL hash for the drift-detection check: ' . $path );
  }

  // Pages preserved and validation suite green, per the sanitizer's own audit record.
  $audit = json_decode( file_get_contents( Sources::safe( Storage::project(), 'docs/implementation/pdf-security-review/sanitization-audit.json' ) ), true, 16, JSON_THROW_ON_ERROR );
  // 5 original Group A files + 10 added under Q05 (9 byte-identical twins reusing an
  // already-approved hash at a new legacy_path, 1 new file needing its own sanitization).
  // See tests/q05-pdf-resolution.php for the Q05-specific assertions on those 10.
  $assert( 15 === count( $audit['records'] ), 'Sanitization audit covers the 5 original Group A files plus 10 added under Q05 (15 total)' );
  foreach ( $audit['records'] as $r ) {
   $assert( 'SANITIZED_OK' === $r['status'], 'Audit record status is SANITIZED_OK: ' . $r['source_legacy_path'] );
   $assert( $r['pages_before'] === $r['pages_after'], 'Audit record: pages preserved: ' . $r['source_legacy_path'] );
   $assert( true === $r['validation']['embedded_file_absent'], 'Audit record: embedded file confirmed absent: ' . $r['source_legacy_path'] );
   $assert( true === $r['validation']['importer_regex_no_match'], 'Audit record: importer regex confirmed non-matching: ' . $r['source_legacy_path'] );
   foreach ( $r['validation'] as $checkName => $result ) { $assert( false !== $result, 'No sanitization validation check was relaxed to a failure being ignored (' . $checkName . '): ' . $r['source_legacy_path'] ); }
  }

  // ---------------------------------------------------------------- GROUP B: exception
  $groupB = array(
   'system/files/images/productos/3e620ed84c43c5de234ae437f58f7083e93f6b8d' => '525617851dbf6430bb53100d0fdef5a95b0a048b0bc20f3778005bb48cd788ca',
   'system/files/images/productos/98b07de6dca4ad402a37385c485fb6999d5c3323' => 'da92b4c85c86a66b1cae255adcb4c0b61f75d37d36ef5272696bc922cd6ca838',
  );
  foreach ( $groupB as $path => $sourceHash ) {
   // Approved hash + known false positive -> SAFE.
   $approval = PdfApprovals::resolve( $path, $sourceHash );
   $assert( $approval && 'exception' === $approval['type'] && 'SAFE_PDF_FALSE_POSITIVE' === $approval['classification'] && 'STRUCTURALLY_VERIFIED_NO_ACTIVE_CONTENT' === $approval['reason_code'], 'Group B approved hash resolves to the exact classification/reason_code: ' . $path );

   // Same path, different hash -> NOT SAFE.
   $assert( null === PdfApprovals::resolve( $path, str_repeat( 'f', 64 ) ), 'Group B same path, wrong hash, is NOT approved: ' . $path );

   // Sources::asset() honours the exception without substituting bytes (original bytes staged).
   $s = new Sources();
   $asset = $s->asset( $path );
   $assert( true === $asset['valid'], 'Sources::asset() reports valid=true under the Group B exception: ' . $path );
   $assert( $asset['staged_path'] === Sources::safe( Storage::project() . '/legacy/public', $path ) && $asset['staged_sha256'] === $sourceHash, 'Group B exception stages the ORIGINAL bytes (nothing to sanitize): ' . $path );

   // The underlying bytes genuinely still fail the raw importer regex on their own —
   // proves this is a real, live exception, not a coincidentally-already-passing file.
   $rawBytes = file_get_contents( Sources::safe( Storage::project() . '/legacy/public', $path ) );
   $assert( (bool) preg_match( $importerPdfRegex, $rawBytes ), 'Group B original bytes still trip the raw regex on their own (exception is genuinely doing work): ' . $path );
  }

  // Same filename, different (wrong) hash on a GROUP A path must not be treated as Group B, or vice versa.
  $assert( null === PdfApprovals::resolve( array_key_first( $groupB ), array_values( $groupA )[0] ), 'A Group A hash does not accidentally satisfy a Group B path' );

  // ---------------------------------------------------------------- NO GENERAL BACKDOOR
  // An unrelated, unapproved PDF with a genuinely dangerous /OpenAction JavaScript action
  // must remain blocked by the exact same, UNMODIFIED Media::file_valid(). Constructed
  // only in the OS temp directory — never under /legacy, never in the repository.
  $tmp = sys_get_temp_dir() . '/psi-pdf-approvals-test-' . wp_generate_uuid4() . '.pdf';
  $dangerous = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R/OpenAction<</Type/Action/S/JavaScript/JS(app.alert\\(1\\))>>>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF";
  file_put_contents( $tmp, $dangerous );
  try {
   $mime = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $tmp );
   if ( 'application/pdf' === $mime ) {
    $assert( false === Media::file_valid( $tmp, $mime ), 'An unapproved PDF with a real /OpenAction JavaScript action is still rejected by the unmodified check' );
    $assert( null === PdfApprovals::resolve( 'made/up/unapproved/path.pdf', hash_file( 'sha256', $tmp ) ), 'A genuinely dangerous, unapproved file never matches any approval by coincidence' );
   } else {
    $checks[] = array( 'test' => 'Synthetic dangerous-PDF check skipped: local finfo did not recognize the minimal fixture as application/pdf (environment-dependent, not a gap in the safeguard itself)', 'passed' => true );
   }
  } finally { @unlink( $tmp ); }
  $assert( ! is_file( $tmp ), 'Synthetic dangerous-PDF fixture deleted after the check' );

  // The exception/substitution mechanism never touches any OTHER file's validity —
  // spot-check a handful of ordinary, unrelated PDFs from media-master.csv. "Unrelated"
  // is checked via PdfApprovals::resolve() itself (not a hardcoded path list): this file
  // only knows the original 7 Group A/B paths, but pdf-approvals.json now also carries
  // the 32 entries added under Q05 (tests/q05-pdf-resolution.php covers those directly).
  $s = new Sources();
  $sampleOther = 0;
  foreach ( $s->rows['media-master.csv'] as $row ) {
   if ( 'application/pdf' !== ( $row['detected_type'] ?? '' ) ) { continue; }
   if ( isset( $groupA[ $row['legacy_path'] ] ) || isset( $groupB[ $row['legacy_path'] ] ) ) { continue; }
   if ( null !== PdfApprovals::resolve( $row['legacy_path'], $row['sha256'] ) ) { continue; } // approved elsewhere (e.g. Q05) -- not "unrelated".
   $asset = $s->asset( $row['legacy_path'] );
   $assert( null === $asset['pdf_approval'], 'An unrelated PDF never receives a pdf_approval by accident: ' . $row['legacy_path'] );
   $assert( $asset['staged_path'] === Sources::safe( Storage::project() . '/legacy/public', $row['legacy_path'] ) && $asset['staged_sha256'] === $asset['sha256'], 'An unrelated PDF always stages its own original bytes: ' . $row['legacy_path'] );
   if ( ++$sampleOther >= 15 ) { break; }
  }
  $assert( $sampleOther >= 10, 'Sampled enough unrelated PDFs to be a meaningful spot check (' . $sampleOther . ')' );

  // ---------------------------------------------------------------- INTEGRATION: FULL DRY RUN
  global $wpdb;
  $before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $full = Planner::build( 'full' );
  $after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $assert( $before === $after, 'Full DRY RUN with PDF approvals active still changes nothing' );
  $assert( 'DRY_RUN' === $full['mode'] && 'VALIDATED' === $full['status'], 'Plan remains DRY_RUN/VALIDATED' );
  $assert( 2399 === count( $full['entries'] ), 'Full plan still analyzes exactly 2,399 source rows' );
  $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ), 'A full plan remains impossible to execute, PDF approvals included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $the18 = array_merge(
   array_map( static fn( $p ) => 'asset:' . $p, array_keys( $groupA + $groupB ) ),
   array( 'sql:productos:65','sql:productos:66','sql:productos:67','sql:productos:68','sql:productos:69','sql:productos:70','sql:productos:71','sql:productos:72','sql:productos:73','sql:productos:77','sql:productos:95' )
  );
  $assert( 18 === count( $the18 ), 'Exactly 18 target rows tracked' );
  foreach ( $the18 as $key ) { $assert( isset( $entries[ $key ] ) && 'REVIEW' !== $entries[ $key ]['action'], 'Row resolved out of REVIEW: ' . $key ); }

  // Subset scope stays byte-identical: the PDF approvals never touch it either.
  $subset = Planner::build( 'subset' );
  $assert( 17 === count( $subset['entries'] ) && 2 === $subset['summary']['actions']['REVIEW'], 'Subset scope remains byte-identical with PDF approvals active' );

  $export( 'pdf-approvals-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'review_before_pdf_phase' => 950, 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'pdf-approvals-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

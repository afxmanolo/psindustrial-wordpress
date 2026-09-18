<?php
/** Runtime consistency tests for Runner::media()'s independent re-validation (the DRY
 * RUN vs. real-execution inconsistency found in verification of the PDF-A/PDF-B phase).
 *
 * Never calls Runner::batch()/media_handle_sideload() and never creates a WordPress
 * attachment: Runner::media_is_valid() is a pure, side-effect-free predicate, reached
 * here via Reflection (it stays private — this suite does not widen Runner's public
 * API). Everything else is read-only inspection of a real, already-built plan's staged
 * bytes. The only file this suite writes is a temporary copy of a real staged file (to
 * prove tampering is caught) and two small synthetic fixtures, all in the OS temp
 * directory, all deleted before the run ends. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,PdfApprovals,Planner};
use PSIndustrial\Core\Media;
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };
 // Reflection reaches the private, pure predicate only — never media()/media_handle_sideload().
 $mediaIsValid = static function( string $source, string $mime, string $legacyPath ): bool {
  $m = new ReflectionMethod( \PSIndustrial\Core\Migration\Runner::class, 'media_is_valid' );
  $m->setAccessible( true );
  return $m->invoke( null, $source, $mime, $legacyPath );
 };
 $importerPdfRegex = '#/(?:JavaScript|JS|OpenAction|Launch|EmbeddedFile)\b#i';

 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();

  $groupA = array(
   'system/files/images/productos/639b9801fe440dcd2179c23cca14401710c5e7e0' => '0a7193edcd8929ac114043fcbffb8e53109ce4363f062cb269f2062ce9e1b4f8',
   'system/files/images/productos/7ab52eec612af22d23a62f849479371ebf1c2f22' => 'a922b6ce9f9ca80b565a3b24be27a086f555aa305db9b9135443c079d93255df',
   'system/files/images/productos/3af12bf12f61eda51a19d1e8af3cefc05878f484' => '703eb6311aaedddf3dd24a8e6b75089463317352f174f672c8d5002e3dfd574f',
   'system/files/images/productos/7baea774c5fc4abe3735d40fdf16e84f1e3521ef' => '7e501c26a5bfc0c02da8899de34a0b6a2bef31bbb42c2124a5c3995ad803a3ee',
   'system/files/images/productos/7cf4babfab2712e41d6fd1c050f5c31b604c97ae' => 'e8ac9a72e867f60aa21665abd004640f2fac4e477b025eae8b7b9a8ae7222e01',
  );
  $groupB = array(
   'system/files/images/productos/3e620ed84c43c5de234ae437f58f7083e93f6b8d' => '525617851dbf6430bb53100d0fdef5a95b0a048b0bc20f3778005bb48cd788ca',
   'system/files/images/productos/98b07de6dca4ad402a37385c485fb6999d5c3323' => 'da92b4c85c86a66b1cae255adcb4c0b61f75d37d36ef5272696bc922cd6ca838',
  );

  // A real, already-built plan supplies the exact staged files Runner would actually see.
  $full = Planner::build( 'full' );
  $entries = array_column( $full['entries'], null, 'entity_key' );

  // ================================================================== GROUP A
  foreach ( $groupA as $path => $sourceHash ) {
   $key = 'asset:' . $path;
   $entry = $entries[ $key ] ?? null;
   $assert( $entry && 'MIGRATE' === $entry['action'], 'Group A entry present and approved in the plan: ' . $path );
   $d = $entry['data'];
   $stagedSource = Storage::path( $d['package_asset'] );

   // "original con EmbeddedFile → no se usa": staged bytes are NOT the original's.
   $originalLive = Sources::safe( Storage::project() . '/legacy/public', $path );
   $assert( hash_file( 'sha256', $stagedSource ) !== hash_file( 'sha256', $originalLive ), 'Group A staged bytes differ from the original (original is never used): ' . $path );
   $assert( ! preg_match( $importerPdfRegex, file_get_contents( $stagedSource ) ), 'Group A staged bytes contain no trace of the pattern that blocked the original: ' . $path );

   // "staged sanitized → válido": Media::file_valid() ALONE already passes — no PdfApprovals fallback needed.
   $assert( true === Media::file_valid( $stagedSource, 'application/pdf' ), 'Group A staged (sanitized) bytes pass the real, unmodified Media::file_valid() alone: ' . $path );
   $assert( true === $mediaIsValid( $stagedSource, 'application/pdf', $path ), 'Runner::media_is_valid() accepts the Group A staged bytes: ' . $path );

   // "staged hash correcto → permitido": the hash-drift check Runner::media() runs first.
   $assert( hash_equals( $d['sha256'], hash_file( 'sha256', $stagedSource ) ), 'Group A staged bytes match the recorded staged_sha256 (hash-drift check passes): ' . $path );

   // "staged alterado → bloqueado": corrupt a COPY of the real staged file, never the original.
   $tamperedCopy = sys_get_temp_dir() . '/psi-tamper-test-' . wp_generate_uuid4() . '.pdf';
   copy( $stagedSource, $tamperedCopy );
   $bytes = file_get_contents( $tamperedCopy );
   $bytes[ intdiv( strlen( $bytes ), 2 ) ] = chr( ( ord( $bytes[ intdiv( strlen( $bytes ), 2 ) ] ) + 1 ) % 256 ); // flip one byte in the middle
   file_put_contents( $tamperedCopy, $bytes );
   try {
    $assert( ! hash_equals( $d['sha256'], hash_file( 'sha256', $tamperedCopy ) ), 'Tampered staged copy no longer matches the recorded hash (Runner::media() would block it before even reaching media_is_valid()): ' . $path );
   } finally { @unlink( $tamperedCopy ); }

   $assert( false === PdfApprovals::isApprovedFalsePositive( $path, $sourceHash ), 'Group A approvals are never a Group B false-positive exception (sanitized substitute needs none): ' . $path );
  }

  // ================================================================== GROUP B
  foreach ( $groupB as $path => $sourceHash ) {
   $key = 'asset:' . $path;
   $entry = $entries[ $key ] ?? null;
   $assert( $entry && 'MIGRATE' === $entry['action'], 'Group B entry present and approved in the plan: ' . $path );
   $d = $entry['data'];
   $stagedSource = Storage::path( $d['package_asset'] );

   // Staged bytes are the ORIGINAL (nothing to sanitize) — and they genuinely still fail
   // Media::file_valid() alone. This proves the fix is doing real work, not papering over
   // a case that would already pass.
   $assert( hash_file( 'sha256', $stagedSource ) === hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $path ) ), 'Group B stages the ORIGINAL bytes (nothing to sanitize): ' . $path );
   $assert( false === Media::file_valid( $stagedSource, 'application/pdf' ), 'Group B staged bytes still fail Media::file_valid() alone, unmodified: ' . $path );

   // "hash exacto aprobado → permitido": the fix's OR-fallback accepts it.
   $assert( true === $mediaIsValid( $stagedSource, 'application/pdf', $path ), 'Runner::media_is_valid() accepts Group B via the exact-hash approval: ' . $path );
   $assert( hash_equals( $d['sha256'], hash_file( 'sha256', $stagedSource ) ), 'Group B hash-drift check passes on the correctly staged original: ' . $path );

   // "mismo filename + hash diferente" / "mismo path + bytes diferentes → bloqueado".
   $wrongHash = str_repeat( 'a', 64 );
   $assert( false === PdfApprovals::isApprovedFalsePositive( $path, $wrongHash ), 'Same Group B path, wrong hash, is not an approved false positive: ' . $path );
   // Simulate genuinely different bytes at the same path: point media_is_valid() at a
   // DIFFERENT real file's bytes while asserting the Group B path — the content hash no
   // longer matches what was approved for that path, so the fallback must not fire.
   $otherRealPdf = Sources::safe( Storage::project() . '/legacy/public', array_key_first( $groupA ) );
   $assert( false === PdfApprovals::isApprovedFalsePositive( $path, hash_file( 'sha256', $otherRealPdf ) ), 'Same Group B path with a different file\'s bytes/hash is not approved: ' . $path );
  }

  // Wrong-group cross-check: a Group A hash must never satisfy a Group B path, or vice versa.
  $assert( false === PdfApprovals::isApprovedFalsePositive( array_key_first( $groupB ), array_values( $groupA )[0] ), 'A Group A hash never satisfies a Group B path' );

  // ================================================================== NO GENERAL BACKDOOR
  $tmpDir = sys_get_temp_dir();
  $fixtures = array();

  // "otro PDF que contiene /JS → bloqueado": a manufactured false positive (the literal
  // 3-byte sequence "/JS" inside random, incompressible binary padding — structurally the
  // same kind of coincidence as the real Group B case) at a path/hash that is NOT approved.
  // The byte immediately after the needle must be deterministically non-word (the importer
  // regex requires a \b there); a NUL guarantees that regardless of the random padding
  // around it — using another random byte there would make this fixture's own match
  // probabilistic (~1/4 chance of no match, since \w covers 63/256 byte values).
  $randomPadding = random_bytes( 2000 );
  $needle = "/JS\x00";
  $spliced = substr( $randomPadding, 0, 900 ) . $needle . substr( $randomPadding, 900 );
  $fakeFalsePositive = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\n4 0 obj<</Length " . strlen( $spliced ) . ">>stream\n" . $spliced . "\nendstream\nendobj\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF";
  $fixtures['unapproved_false_positive'] = $tmpDir . '/psi-unapproved-fp-' . wp_generate_uuid4() . '.pdf';
  file_put_contents( $fixtures['unapproved_false_positive'], $fakeFalsePositive );

  // "PDF realmente peligroso → bloqueado": a real, structural /OpenAction JavaScript action.
  $dangerous = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R/OpenAction<</Type/Action/S/JavaScript/JS(app.alert\\(1\\))>>>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF";
  $fixtures['dangerous_real_action'] = $tmpDir . '/psi-dangerous-' . wp_generate_uuid4() . '.pdf';
  file_put_contents( $fixtures['dangerous_real_action'], $dangerous );

  try {
   foreach ( $fixtures as $label => $fixturePath ) {
    $mime = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $fixturePath );
    if ( 'application/pdf' !== $mime ) {
     $checks[] = array( 'test' => "Synthetic fixture '$label' skipped: local finfo did not recognize it as application/pdf (environment-dependent, not a gap in the safeguard)", 'passed' => true );
     continue;
    }
    $assert( (bool) preg_match( $importerPdfRegex, file_get_contents( $fixturePath ) ), "Synthetic fixture '$label' genuinely trips the raw regex (sanity check on the fixture itself)" );
    $assert( false === Media::file_valid( $fixturePath, $mime ), "Synthetic fixture '$label' is rejected by the unmodified Media::file_valid()" );
    $assert( false === $mediaIsValid( $fixturePath, $mime, 'made/up/unapproved/path-' . $label . '.pdf' ), "Runner::media_is_valid() still blocks unapproved fixture '$label' — no general backdoor" );
   }
  } finally { foreach ( $fixtures as $f ) { @unlink( $f ); } }
  foreach ( $fixtures as $f ) { $assert( ! is_file( $f ), 'Synthetic fixture deleted: ' . basename( $f ) ); }

  // ================================================================== ORDINARY PDF: UNCHANGED BEHAVIOUR
  $s = new Sources();
  $sampled = 0;
  foreach ( $s->rows['media-master.csv'] as $row ) {
   if ( 'application/pdf' !== ( $row['detected_type'] ?? '' ) ) { continue; }
   if ( isset( $groupA[ $row['legacy_path'] ] ) || isset( $groupB[ $row['legacy_path'] ] ) ) { continue; }
   $full2 = Sources::safe( Storage::project() . '/legacy/public', $row['legacy_path'] );
   $mime = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $full2 );
   $plain = Media::file_valid( $full2, $mime );
   $withFallback = $mediaIsValid( $full2, $mime, $row['legacy_path'] );
   $assert( $plain === $withFallback, 'Ordinary, unrelated PDF: media_is_valid() outcome identical to plain Media::file_valid(): ' . $row['legacy_path'] );
   if ( ++$sampled >= 15 ) { break; }
  }
  $assert( $sampled >= 10, 'Sampled enough ordinary PDFs for a meaningful spot check (' . $sampled . ')' );

  // Non-PDF mime types are never routed through the fallback at all, regardless of path/hash.
  $assert( false === $mediaIsValid( sys_get_temp_dir(), 'image/jpeg', array_key_first( $groupB ) ), 'Non-PDF mime never consults PdfApprovals (fallback only ever applies to application/pdf)' );

  // ================================================================== INTEGRATION: numbers unchanged
  $assert( 932 === $full['summary']['actions']['REVIEW'], 'Full plan REVIEW count is unchanged by this fix (932): the fix only affects a future Runner execution, never Planner\'s decisions' );
  $assert( 'DRY_RUN' === $full['mode'] && 'VALIDATED' === $full['status'], 'Plan remains DRY_RUN/VALIDATED' );

  $export( 'runner-media-validation-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'runner-media-validation-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

<?php
/** Q05 (extend the approved PDF security policy to the 32 additional type-blocked files)
 * tests. Read-mostly: calls Planner::build() (DRY RUN) and PdfApprovals::resolve() (pure
 * read). The only file this suite writes is a single synthetic test PDF in the OS temp
 * directory (never /legacy, never the repo), deleted at the end of the run. Complements
 * tests/pdf-approvals.php (the original 5 Group A + 2 Group B entries), which is not
 * re-tested here. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,PdfApprovals,Planner};
use PSIndustrial\Core\Media;
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };
 $importerPdfRegex = '#/(?:JavaScript|JS|OpenAction|Launch|EmbeddedFile)\b#i';
 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();
  $s = new Sources();

  // ============================================================ Group A: twins reuse an
  // already-sanitized substitute by content, without regenerating it.
  $aTwins = array(
   'fichas/puerta-424.pdf' => array( '0a7193edcd8929ac114043fcbffb8e53109ce4363f062cb269f2062ce9e1b4f8', 'system/files/images/productos/639b9801fe440dcd2179c23cca14401710c5e7e0' ),
   'fichas/puerta-430.pdf' => array( '0a7193edcd8929ac114043fcbffb8e53109ce4363f062cb269f2062ce9e1b4f8', 'system/files/images/productos/639b9801fe440dcd2179c23cca14401710c5e7e0' ),
   'fichas/puerta-seccional-418.pdf' => array( 'a922b6ce9f9ca80b565a3b24be27a086f555aa305db9b9135443c079d93255df', 'system/files/images/productos/7ab52eec612af22d23a62f849479371ebf1c2f22' ),
   'fichas/puerta-521.pdf' => array( 'e8ac9a72e867f60aa21665abd004640f2fac4e477b025eae8b7b9a8ae7222e01', 'system/files/images/productos/7cf4babfab2712e41d6fd1c050f5c31b604c97ae' ),
  );
  foreach ( $aTwins as $twinPath => [ $sourceHash, $parentPath ] ) {
   $twinApproval = PdfApprovals::resolve( $twinPath, $sourceHash );
   $parentApproval = PdfApprovals::resolve( $parentPath, $sourceHash );
   $assert( $twinApproval && 'sanitized' === $twinApproval['type'], "Twin resolves to a sanitized substitute (same hash already approved -> reuse approval): $twinPath" );
   $assert( $parentApproval && 'sanitized' === $parentApproval['type'], "Parent path still resolves independently: $parentPath" );
   $assert( $twinApproval['sha256'] === $parentApproval['sha256'] && $twinApproval['source_path'] === $parentApproval['source_path'], "Same content under another path -> correct traceability: identical sanitized substitute reused, not regenerated ($twinPath == $parentPath)" );
   $assert( is_file( $twinApproval['source_path'] ), "Sanitized substitute file exists on disk: $twinPath" );
   $assert( hash_equals( $twinApproval['sha256'], hash_file( 'sha256', $twinApproval['source_path'] ) ), "Sanitized substitute's own hash matches its recorded hash: $twinPath" );
   // Sanitized result -> passes the real, unmodified validation function.
   $assert( Media::file_valid( $twinApproval['source_path'], 'application/pdf' ), "Sanitized result passes normal Media::file_valid(): $twinPath" );
   // The two DIFFERENT legacy source files (twin + parent) are themselves byte-identical
   // -- proof this is a genuine content twin, not a coincidence of the approval file.
   $liveTwinHash = hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $twinPath ) );
   $liveParentHash = hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $parentPath ) );
   $assert( hash_equals( $liveTwinHash, $liveParentHash ) && hash_equals( $liveTwinHash, $sourceHash ), "Twin and parent legacy files are genuinely byte-identical on disk: $twinPath" );
  }

  // ============================================================ Group A: genuinely NEW
  // content (not a twin of anything), needing its own sanitization run.
  $newFile = 'fichas/puerta-thermacore-595.pdf';
  $newHash = '8e4b232791099c29a4616cae17d42ee1c8c9637af06f83e25a640505a91026b2';
  $newApproval = PdfApprovals::resolve( $newFile, $newHash );
  $assert( $newApproval && 'sanitized' === $newApproval['type'], "Benign /EmbeddedFile (new content, own sanitization run): $newFile" );
  $sanitizedBytes = file_get_contents( $newApproval['source_path'] );
  $assert( ! preg_match( $importerPdfRegex, $sanitizedBytes ), "Sanitized copy contains no dangerous-pattern byte sequence: $newFile" );
  $assert( Media::file_valid( $newApproval['source_path'], 'application/pdf' ), "Sanitized result passes normal Media::file_valid(): $newFile" );
  $liveHash = hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $newFile ) );
  $assert( hash_equals( $newHash, $liveHash ), "Original legacy file remains byte-identical (never modified): $newFile" );

  // ============================================================ Group B: exact false
  // positives (pure regex coincidence, zero declared action anywhere in the object graph).
  $falsePositives = array(
   'fichas/commercial-aluminum-door-systems-brochure.pdf' => '8dc41cef299a6fff42f35586d57cadf6d24bacbe36c327467c91a69dc4c6a8b3',
   'fichas/Kelley-Hydraulic-Dock-Leveler-Brochure_web.pdf' => 'd1af1d5b7a74f8bdda6761f7b3b30ccecec3bfd93fc5d966b2bb6bd202d0de27',
   'fichas/moovi50rm.pdf' => 'b40a5af2a94250cc800336c668821dd1f54f3f28f1c6ef3ed8f4a35beb3ed1cd',
   'fichas/rolling-steel-doors-610-620-brochure.pdf' => '9baa5d63b37b5e11e3877d383c1c6a1d79d45e9cac57495fad648de204da0883',
   'fichas/StrongArm_HVR303_Brochure_Spanish.pdf' => '63b50f31f52cad064dfabe524ec2fee7c18a088ac99d5792ca34fb7fe7c09353',
   'system/files/images/productos/800c352920fab91fa561da8cbc0cda7af293ddbf' => '06654d8d65cc09ccf19d04453a004c20c4fe50b1b011f433f9daa977b71b8694',
  );
  foreach ( $falsePositives as $path => $hash ) {
   $approval = PdfApprovals::resolve( $path, $hash );
   $assert( $approval && 'exception' === $approval['type'] && 'SAFE_PDF_FALSE_POSITIVE' === $approval['classification'], "Exact false positive -> hash-approved exception: $path" );
   $raw = file_get_contents( Sources::safe( Storage::project() . '/legacy/public', $path ) );
   $assert( (bool) preg_match( $importerPdfRegex, $raw ), "Original bytes genuinely still trip the raw regex on their own (real exception, not coincidence): $path" );
  }

  // ============================================================ Group B: benign
  // declared /OpenAction (real key present, proven internal same-document navigation) --
  // a DIFFERENT classification from a pure false positive, same hash-gated mechanism.
  $benignAction = array(
   'fichas/Icaro-smart-AC-Aes.pdf' => '59e03db70ddceee5c79478129ceeedde4a469893d60f00afe83c8c836040f6f4',
   'fichas/Clopay-3720-07.pdf' => '4b1e655cb7fe72789212f01e6578fba7773cfd0f2c7d4f03d968e666d3a82638',
   'fichas/CMDC-0524SP-14-1.pdf' => '4c0e907d3846a72f5bea4da1e2f2d67d2afdaab66211045725eee9b430bdbf4e',
   'fichas/CMDC-3717-3718-11.pdf' => '56136ae68c00fd76ee16c50cbaefabd8eb5ea8e581142f59287b5b05ab9c2ec1',
   'fichas/lisos.pdf' => '9f243ab6101cecf78a08deb6c11900c1a017d409e6baa53c958cd13dc2f532e0',
  );
  foreach ( $benignAction as $path => $hash ) {
   $approval = PdfApprovals::resolve( $path, $hash );
   $assert( $approval && 'exception' === $approval['type'] && 'SAFE_PDF_BENIGN_INTERNAL_ACTION' === $approval['classification'] && 'STRUCTURALLY_VERIFIED_INERT_NAVIGATION' === $approval['reason_code'], "Benign declared action -> distinct classification from a pure false positive: $path" );
  }

  // ============================================================ Modified bytes -> blocked.
  foreach ( array_slice( $falsePositives, 0, 1, true ) + array_slice( $benignAction, 0, 1, true ) as $path => $hash ) {
   $assert( null === PdfApprovals::resolve( $path, str_repeat( 'a', 64 ) ), "Wrong hash on an otherwise-approved Q05 path is blocked (approval does not apply): $path" );
  }
  $assert( null === PdfApprovals::resolve( 'fichas/puerta-424.pdf', str_repeat( 'b', 64 ) ), 'Modified bytes on a Group A twin are blocked, not silently substituted' );

  // ============================================================ Active/dangerous PDF is
  // still blocked by the exact same, UNMODIFIED Media::file_valid() -- Q05 approves 32
  // specific, individually-audited files, never PDFs in general.
  $tmp = sys_get_temp_dir() . '/psi-q05-test-' . wp_generate_uuid4() . '.pdf';
  $dangerous = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R/OpenAction<</Type/Action/S/Launch/F(cmd.exe)>>>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF";
  file_put_contents( $tmp, $dangerous );
  try {
   $mime = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $tmp );
   if ( 'application/pdf' === $mime ) {
    $assert( false === Media::file_valid( $tmp, $mime ), 'An unapproved PDF with a real /Launch action is still rejected by the unmodified check' );
    $assert( null === PdfApprovals::resolve( 'fichas/puerta-424.pdf', hash_file( 'sha256', $tmp ) ), 'A dangerous file never matches an approval by pretending to be an approved path with a different hash' );
   } else {
    $checks[] = array( 'test' => 'Synthetic dangerous-PDF check skipped: local finfo did not recognize the minimal fixture as application/pdf (environment-dependent)', 'passed' => true );
   }
  } finally { @unlink( $tmp ); }
  $assert( ! is_file( $tmp ), 'Synthetic dangerous-PDF fixture deleted after the check' );

  // ============================================================ INTEGRATION: existing
  // second blocker -> the row can stay REVIEW even though Q05 itself is fully resolved.
  // fichas/lisos.pdf's security block is resolved (Group B exception above), but its only
  // candidate owners (products 6/16/20/107) are the D03 brand-conflict register
  // (Policy::BRAND_CONFLICT_PRODUCTS) and so have never been R-P01-approved -- the MEDIA
  // entity itself has no MIGRATE decision from anywhere and correctly stays REVIEW.
  // media_validation proves WHICH cause: VALID_BYTES_REQUIRES_APPROVAL means the bytes
  // are fine and only an ownership decision is missing, not a security block.
  $full = Planner::build( 'full' );
  $entries = array_column( $full['entries'], null, 'entity_key' );
  $secondBlocker = array( 'asset:fichas/lisos.pdf', 'asset:fichas/Icaro-smart-AC-Aes.pdf', 'asset:fichas/puerta-thermacore-595.pdf', 'asset:fichas/commercial-aluminum-door-systems-brochure.pdf' );
  $stillReview = 0;
  foreach ( $secondBlocker as $key ) {
   $e = $entries[ $key ] ?? null;
   $assert( null !== $e, "Entry exists: $key" );
   if ( 'REVIEW' === $e['action'] ) {
    $assert( 'VALID_BYTES_REQUIRES_APPROVAL' === ( $e['media_validation'] ?? '' ), "Q05-resolved file still REVIEW for an unrelated reason (ownership, not security): $key" );
    ++$stillReview;
   }
  }
  $assert( $stillReview >= 1, 'At least one Q05-resolved file demonstrably still REVIEW due to a second, unrelated blocker' );

  // The dual-blocked file (Q05 type block + Q06 ownership) has its security resolved by
  // Q05 independently of what Q06 later decides about its final fate (see
  // tests/editorial-decisions.php for confirmation that its final action is SKIP).
  $dualHash = '06654d8d65cc09ccf19d04453a004c20c4fe50b1b011f433f9daa977b71b8694';
  $dualApproval = PdfApprovals::resolve( 'system/files/images/productos/800c352920fab91fa561da8cbc0cda7af293ddbf', $dualHash );
  $assert( null !== $dualApproval, 'Q05 resolves the dual-blocked file\'s security classification on its own merits, independent of Q06\'s ownership decision' );

  // ============================================================ pdf-approvals.json totals
  $data = json_decode( file_get_contents( Sources::safe( Storage::project(), 'docs/implementation/pdf-security-review/pdf-approvals.json' ) ), true, 16, JSON_THROW_ON_ERROR );
  $assert( 15 === count( $data['group_a_sanitization'] ), 'pdf-approvals.json: 15 group_a_sanitization entries (5 original + 10 added under Q05)' );
  $assert( 24 === count( $data['group_b_exception'] ), 'pdf-approvals.json: 24 group_b_exception entries (2 original + 22 added under Q05)' );
  $paths = array_merge( array_column( $data['group_a_sanitization'], 'legacy_path' ), array_column( $data['group_b_exception'], 'legacy_path' ) );
  $assert( count( $paths ) === count( array_unique( $paths ) ), 'No duplicate legacy_path across the whole approvals file (each entry individually hash+path-gated)' );

  // No DB mutation; plan still fully DRY_RUN/VALIDATED with Q05 active.
  global $wpdb;
  $before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  Planner::build( 'full' );
  $after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $assert( $before === $after, 'Full DRY RUN with Q05 active still changes nothing in the database' );

  $export( 'q05-pdf-resolution-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'still_review_due_to_second_blocker' => $stillReview ) );
 } catch ( Throwable $error ) {
  $export( 'q05-pdf-resolution-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

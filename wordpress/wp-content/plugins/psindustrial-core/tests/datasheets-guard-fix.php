<?php
/** Tests for Runner::approved_datasheets_override() — the fix for the gap discovered
 * during the first real recovery execution: Fields::guard() validates `_psi_datasheets`
 * via Media::valid() with no PdfApprovals awareness, so an already-legitimate Group B PDF
 * attachment still fails the write. See
 * docs/implementation/full-local-import/21-recovery-execution-result.md.
 *
 * Strictly non-mutating: approved_datasheets_override() is a pure predicate (no writes of
 * its own), reached via Reflection since it stays private. Uses REAL, already-existing
 * attachment ids from this same install (Group A/B PDFs created by the real recovery
 * execution, plus an ordinary image) as fixtures — read-only inspection only, no new
 * attachment created, no postmeta written. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,PdfApprovals,Planner};
use PSIndustrial\Core\Media;
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();
  global $wpdb;

  $call = static function( ...$args ) {
   $m = new ReflectionMethod( \PSIndustrial\Core\Migration\Runner::class, 'approved_datasheets_override' );
   $m->setAccessible( true );
   return $m->invokeArgs( null, $args );
  };

  // Locate REAL fixtures: a Group B attachment already created by the recovery run, a
  // Group A attachment, and an ordinary product image -- all read-only lookups.
  $fresh = Planner::build( 'full' );
  $groupBRow = $wpdb->get_row( "SELECT p.ID, pm.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_psi_import_origin' WHERE p.post_type='attachment' AND pm.meta_value LIKE '%commercial-aluminum-door-systems-brochure.pdf%'", ARRAY_A );
  $assert( (bool) $groupBRow, 'Fixture: the real Group B attachment (commercial-aluminum-door-systems-brochure.pdf) exists' );
  $groupBId = (int) $groupBRow['ID'];
  $groupBLegacyPath = 'fichas/commercial-aluminum-door-systems-brochure.pdf';
  $assert( false === Media::valid( $groupBId, 'pdf' ), 'Sanity: this Group B attachment still fails Media::valid() alone (the bug this fix addresses)' );

  $groupARow = $wpdb->get_row( "SELECT p.ID, pm.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_psi_import_origin' WHERE p.post_type='attachment' AND pm.meta_value LIKE '%puerta-424.pdf%'", ARRAY_A );
  $assert( (bool) $groupARow, 'Fixture: a real Group A (sanitized) attachment exists' );
  $groupAId = (int) $groupARow['ID'];
  $assert( true === Media::valid( $groupAId, 'pdf' ), 'Sanity: the Group A attachment already passes Media::valid() alone (sanitized bytes)' );

  $imageId = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type='attachment' AND post_mime_type LIKE 'image/%' LIMIT 1" );
  $assert( $imageId > 0, 'Fixture: an ordinary image attachment exists' );

  $postId = 1360; // a post id that need not exist -- the override only compares it for identity, never loads the post.

  // ============================================================ Grupo B exact hash + approval -> PASS
  $value = array( array( 'attachment_id' => $groupBId, 'label' => 'x', 'language' => '' ) );
  $paths = array( $groupBId => $groupBLegacyPath );
  $result = $call( false, $postId, '_psi_datasheets', $value, $postId, $paths );
  $assert( null === $result, 'Group B exact attachment + exact legacy path + approval: override returns null to perform the native write' );

  // ============================================================ mismo attachment con hash distinto -> BLOCK
  // Simulate "the hash no longer matches" by pointing the legacy path at a DIFFERENT
  // (unapproved) path for the SAME attachment id -- PdfApprovals will not match path+hash together.
  $wrongPaths = array( $groupBId => 'fichas/this-path-was-never-approved-for-this-attachment.pdf' );
  $resultWrongPath = $call( false, $postId, '_psi_datasheets', $value, $postId, $wrongPaths );
  $assert( false === $resultWrongPath, 'Same attachment, path that does not match its own approval: BLOCK (returns original $check, false)' );

  // ============================================================ PDF no aprobado -> BLOCK
  // A real, ordinary image attachment used as if it were a PDF datasheet: Media::valid()
  // fails (not a PDF at all) and it is never a PdfApprovals entry for any path.
  $unapprovedValue = array( array( 'attachment_id' => $imageId, 'label' => 'x', 'language' => '' ) );
  $unapprovedPaths = array( $imageId => 'fichas/not-actually-approved.pdf' );
  $resultUnapproved = $call( false, $postId, '_psi_datasheets', $unapprovedValue, $postId, $unapprovedPaths );
  $assert( false === $resultUnapproved, 'An attachment with no real PdfApprovals entry for that path: BLOCK' );

  // ============================================================ JavaScript real -> BLOCK
  // Confirm directly against PdfApprovals: real dangerous bytes are never an approved
  // exception for any path (mirrors the same check already proven in pdf-runtime-fix.php).
  $dangerous = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R/OpenAction<</Type/Action/S/JavaScript/JS(app.alert\\(1\\))>>>>endobj\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF";
  $dangerousHash = hash( 'sha256', $dangerous );
  $assert( false === PdfApprovals::isApprovedFalsePositive( $groupBLegacyPath, $dangerousHash ), 'Real dangerous PDF bytes are never an approved exception for the Group B path' );
  // (Not re-exercised through the override directly: doing so would require an attachment
  // whose ON-DISK bytes are genuinely dangerous, which this suite never creates -- the
  // PdfApprovals-level proof above is the meaningful, safe equivalent.)

  // ============================================================ PDF Grupo A -> sigue su ruta sanitized normal
  $groupAValue = array( array( 'attachment_id' => $groupAId, 'label' => 'x', 'language' => '' ) );
  // Even with NO legacy path context at all, Group A passes -- because it already validates
  // on its own merits (Media::valid()===true) and the override never needs to consult
  // PdfApprovals for it at all.
  $resultGroupA = $call( false, $postId, '_psi_datasheets', $groupAValue, $postId, array() );
  $assert( null === $resultGroupA, 'Group A (sanitized) attachment: override returns null to perform the native write purely via Media::valid(), never touches PdfApprovals' );

  // ============================================================ media ordinaria -> comportamiento sin cambios
  // Wrong object id: never touched, regardless of content.
  $resultWrongObject = $call( false, $postId + 1, '_psi_datasheets', $value, $postId, $paths );
  $assert( false === $resultWrongObject, 'A different post id (any other write in flight): completely untouched, original $check returned' );
  // Wrong meta key: never touched.
  $resultWrongKey = $call( false, $postId, '_psi_gallery_ids', $value, $postId, $paths );
  $assert( false === $resultWrongKey, 'A different meta key (e.g. _psi_gallery_ids): completely untouched' );
  // $check already not-false (something else already decided): never touched.
  $resultAlreadyTrue = $call( true, $postId, '_psi_datasheets', $value, $postId, $paths );
  $assert( true === $resultAlreadyTrue, '$check already true (nothing rejected): returned as-is, never re-evaluated' );

  // ============================================================ Fields::guard() itself: untouched, still the real, unmodified gate
  $source = file_get_contents( dirname( __DIR__ ) . '/includes/Fields.php' );
  $assert( str_contains( $source, "Media::valid( (int) \$item['attachment_id'], 'pdf' )" ), 'Fields::validate() still calls the real, unmodified Media::valid() for _psi_datasheets -- never relaxed' );
  $assert( ! str_contains( $source, 'PdfApprovals' ), 'Fields.php itself never references PdfApprovals -- the override lives entirely in Runner.php, scoped to the migration path only' );

  // ============================================================ integration: plan reproducibility unchanged
  $fullAgain = Planner::build( 'full' );
  $assert( $fullAgain['summary']['actions']['REVIEW'] === $fresh['summary']['actions']['REVIEW'], 'Full plan REVIEW count stable/reproducible with this fix active' );

  $export( 'datasheets-guard-fix-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
 } catch ( Throwable $error ) {
  $export( 'datasheets-guard-fix-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

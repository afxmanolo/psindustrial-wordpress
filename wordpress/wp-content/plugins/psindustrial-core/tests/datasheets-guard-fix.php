<?php
/** Tests for Runner::approved_datasheets_override() (the one-time import-write fix) AND
 * Fields::datasheet_valid() (the runtime-edit fix for the SAME gap, closing the "future
 * separate correction" flagged in docs/implementation/full-local-import/
 * 21-recovery-execution-result.md): Fields::guard()/Fields::validate_rest() validated
 * `_psi_datasheets` via Media::valid() alone, with no PdfApprovals awareness, so an
 * already-legitimate Group B PDF attachment failed every subsequent admin/REST save, not
 * only the one-time import write (reported live against sql:productos:77, WordPress post
 * 1372, "Operador de puerta comercial RHX ®").
 *
 * Strictly non-mutating: approved_datasheets_override() and datasheet_valid() are both pure
 * predicates (no writes of their own), reached via Reflection since they stay private. Uses
 * REAL, already-existing attachment ids from this same install (Group A/B PDFs created by
 * the real recovery execution, plus an ordinary image) as fixtures — read-only inspection
 * only, no new attachment created, no postmeta written. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,PdfApprovals,Planner};
use PSIndustrial\Core\Media;
use PSIndustrial\Core\Fields;
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

  // ============================================================ Fields::datasheet_valid(): the runtime-edit fix for the SAME gap
  // Closes the gap left open by design when approved_datasheets_override() was added (see
  // this file's own docblock): Fields.php now has its OWN narrowly-scoped PdfApprovals
  // fallback for `_psi_datasheets`, sourced ONLY from the attachment's own recorded
  // `_psi_import_origin` (never a caller-supplied path, never guessed), so an approved
  // Group B attachment stays valid on every later admin/REST save -- not only at the
  // moment of import. Media::valid() itself is never touched (reconfirmed below).
  $datasheetValid = static function( int $attachmentId ) {
   $m = new ReflectionMethod( \PSIndustrial\Core\Fields::class, 'datasheet_valid' );
   $m->setAccessible( true );
   return $m->invoke( null, $attachmentId );
  };
  $assert( true === $datasheetValid( $groupBId ), 'Fields::datasheet_valid(): the real Group B attachment is valid via its own recorded legacy path + approval' );
  $assert( true === Fields::validate( '_psi_datasheets', array( array( 'attachment_id' => $groupBId, 'label' => 'x', 'language' => '' ) ), $postId ), 'Fields::validate() itself now accepts this real Group B datasheet -- the exact write Gutenberg performs on every save' );
  $assert( true === $datasheetValid( $groupAId ), 'Fields::datasheet_valid(): Group A (sanitized) attachment stays valid, purely via Media::valid(), never touches PdfApprovals' );
  $assert( false === $datasheetValid( $imageId ), 'Fields::datasheet_valid(): an ordinary non-PDF attachment stays rejected -- wrong mime, no approval, never approved by proximity' );
  $assert( false === PdfApprovals::isApprovedFalsePositive( $groupBLegacyPath, $dangerousHash ), 'Real dangerous PDF bytes are never an approved exception for the Group B path -- datasheet_valid() relies on this same exact-hash-gated predicate, never a looser rule' );

  // Fields::guard() itself: still the real gate, only its per-item PDF predicate changed.
  $source = file_get_contents( dirname( __DIR__ ) . '/includes/Fields.php' );
  $assert( str_contains( $source, 'self::datasheet_valid(' ), 'Fields::validate() routes _psi_datasheets through datasheet_valid(), never a bare, unaware Media::valid() call' );
  $assert( str_contains( $source, 'Media::valid( $attachment_id' ), 'datasheet_valid() itself still checks the real, unmodified Media::valid() first -- never skips it' );

  // Media::valid() itself remains completely unmodified: still a pure content-integrity
  // predicate, with no PdfApprovals awareness baked into the general function every OTHER
  // caller (Runner.php, PartialPdfRepair.php) also relies on.
  $mediaSource = file_get_contents( dirname( __DIR__ ) . '/includes/Media.php' );
  $assert( ! str_contains( $mediaSource, 'PdfApprovals' ), 'Media::valid() itself is never touched -- it stays a pure content-integrity predicate, with no PdfApprovals awareness of its own' );
  $assert( false === Media::valid( $groupBId, 'pdf' ), 'Re-confirmed: Media::valid() alone still returns false for this exact attachment -- only Fields::datasheet_valid() layers the approval on top' );

  // ============================================================ integration: plan reproducibility unchanged
  $fullAgain = Planner::build( 'full' );
  $assert( $fullAgain['summary']['actions']['REVIEW'] === $fresh['summary']['actions']['REVIEW'], 'Full plan REVIEW count stable/reproducible with this fix active' );

  $export( 'datasheets-guard-fix-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
 } catch ( Throwable $error ) {
  $export( 'datasheets-guard-fix-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

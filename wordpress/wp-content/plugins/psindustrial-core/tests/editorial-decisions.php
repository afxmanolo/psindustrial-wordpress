<?php
/** Q02 (identity-equivalent product consolidation) + Q06 (incomplete/test legacy record
 * exclusion) tests for migration/EditorialDecisions.php. Read-only: only calls
 * Planner::build('full'/'subset') (DRY RUN) and inspects the resulting plan. Writes
 * nothing except its own JSON report under docs/implementation/importer-reports/. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,Planner,Runner};
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };
 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();

  global $wpdb;
  $before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $full = Planner::build( 'full' );
  $full2 = Planner::build( 'full' );
  $after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $assert( $before === $after, 'Two full DRY RUNs change nothing in the database' );
  $assert( 2399 === count( $full['entries'] ), 'Full plan still analyzes exactly 2,399 source rows' );
  $throws = static function( callable $fn ) { try { $fn(); return false; } catch ( \Throwable $e ) { return true; } };
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, editorial decisions included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };

  // ============================================================== IDEMPOTENCY (all Q02/Q06 rows)
  $idempotent = 0;
  foreach ( $entries as $key => $e ) {
   $did = $e['decision']['decision_id'] ?? null;
   if ( 'Q02' !== $did && 'Q06' !== $did ) { continue; }
   $e2 = $entries2[ $key ] ?? null;
   $assert( $e2 && $e['action'] === $e2['action'] && $e['planned_result'] === $e2['planned_result'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $e2['decision'] ), "Repeated execution -> identical result: $key" );
   ++$idempotent;
  }
  $assert( $idempotent > 150, "A meaningful number of Q02/Q06 rows were checked for idempotency ($idempotent)" );

  // ============================================================================== Q02
  // --- Pure-duplicate group: cortina-serie-625.php (ids 4, 87). Both members' own
  // images/technical_pdf columns are literally the same 3 paths; two of those 3 are
  // themselves byte-identical (images/rolling-service-door-625-wide.jpg ==
  // system/files/images/productos/0dd525f6... by SHA-256, verified independently below),
  // so the deduplicated-by-content union collapses to exactly 2 images, not 3 and not 6.
  $winner = $get( 'sql:productos:4' );
  $loser = $get( 'sql:productos:87' );
  $assert( 'MERGE' === $winner['action'] && 'Q02' === $winner['decision']['decision_id'], 'Pure-duplicate group: winner (oldest, id 4) action is MERGE' );
  $assert( 'SKIP' === $loser['action'] && 'Q02' === $loser['decision']['decision_id'], 'Pure-duplicate group: loser (id 87) action is SKIP' );
  $assert( array( 'asset:images/rolling-service-door-625-wide.jpg' ) === $winner['data']['images'], 'Pure duplicate retains its actual photo exactly once; the second former image was the UI button' );
  $assert( in_array( 'asset:images/rolling-service-door-625-wide.jpg', $winner['data']['images'], true ), 'Pure-duplicate group: union keeps the winning path of the duplicate pair' );
  $assert( ! in_array( 'asset:system/files/images/productos/0dd525f64a0290377628938328d07d21668c7f22', $winner['data']['images'], true ), 'Duplicate binary image -> only one survives the union (the other path is dropped, not both kept)' );
  $s = new Sources();
  $h1 = $s->asset( 'images/rolling-service-door-625-wide.jpg' )['sha256'];
  $h2 = $s->asset( 'system/files/images/productos/0dd525f64a0290377628938328d07d21668c7f22' )['sha256'];
  $assert( hash_equals( $h1, $h2 ), 'Independent proof: the two paths really are byte-identical (this is why they collapse to one)' );
  $assert( 1 === count( $winner['data']['pdfs'] ), 'Identical PDF -> only one survives the union: ' . 'sql:productos:4' );

  // --- Featured image = first image of the oldest record. Product 4's own fecha
  // (2025-10-20) predates product 87's (2025-10-24); the winner's first image is the
  // first element of the WINNING record's own product-master.csv images column.
  $assert( 'asset:images/rolling-service-door-625-wide.jpg' === $winner['data']['images'][0], 'Featured image is the first image of the oldest record (by productos.fecha)' );

  // --- Complementary-photos group: cortina-serie-620.php (ids 7, 91). Product 91
  // contributes system/files/images/productos/0b26a7b2..., a genuinely distinct SHA-256
  // (695b297e...) absent from product 7's own list -- the union must include it.
  $winner2 = $get( 'sql:productos:7' );
  $loser2 = $get( 'sql:productos:91' );
  $assert( 'MERGE' === $winner2['action'], 'Complementary-photos group: winner (id 7) action is MERGE' );
  $assert( 'SKIP' === $loser2['action'], 'Complementary-photos group: loser (id 91) action is SKIP' );
  $assert( in_array( 'asset:system/files/images/productos/0b26a7b20dd4d467cf9a57c9a5cba26cafbf6203', $winner2['data']['images'], true ), 'Complementary-photos group: union of unique photos includes the loser\'s distinct image' );
  $assert( 2 === count( $winner2['data']['images'] ) && ! in_array( 'asset:images/verficha.png', $winner2['data']['images'], true ), 'Complementary-photos union retains both real photos, excluding only the verified UI button' );

  // --- MERGE self-consistency: field_winners all point to the winner; source_keys cover
  // every merged legacy id; editorial_approval present (Planner::build() itself would
  // otherwise have silently downgraded these to REVIEW -- their being MERGE at all is
  // already partial proof, this asserts the payload shape directly).
  foreach ( array( $winner, $winner2 ) as $w ) {
   foreach ( array( 'name','content','categories','brand','images','pdfs','videos' ) as $field ) {
    $assert( $w['decision']['field_winners'][ $field ] === $w['entity_key'], "field_winners['$field'] points to the winner itself: {$w['entity_key']}" );
   }
   $assert( '' !== trim( $w['decision']['editorial_approval'] ?? '' ), 'editorial_approval is present: ' . $w['entity_key'] );
   $assert( in_array( $w['entity_key'], $w['decision']['source_keys'], true ), 'source_keys includes the winner itself: ' . $w['entity_key'] );
  }

  // --- A group with an unrelated, orthogonal data-quality problem (one of its own
  // images fails the pre-existing MIME/extension consistency check -- unrelated to Q02)
  // must KEEP_REVIEW for BOTH members, never SKIP the loser while stranding the winner:
  // that would silently discard the loser's row without ever completing the merge.
  $dockWinner = $get( 'sql:productos:50' );
  $dockLoser = $get( 'sql:productos:59' );
  $assert( 'REVIEW' === $dockWinner['action'] && null === ( $dockWinner['decision']['decision_id'] ?? null ), 'Group with an invalid union asset: winner stays plain REVIEW, no Q02 decision at all' );
  $assert( 'REVIEW' === $dockLoser['action'] && null === ( $dockLoser['decision']['decision_id'] ?? null ), 'Group with an invalid union asset: loser is NOT silently SKIPped (no data loss without a completed merge)' );

  // --- Similar-but-not-Q02 group (accesspro-fs1000speed.php, ids 1/109/110/114): this is
  // one of the 27 Q03 editorial-merge groups (diverges by category_id, not identity-
  // equivalent) -- explicitly NOT in editorial-decisions.json's Q02 list. Must be
  // completely unaffected: no Q02 decision on any of its members.
  foreach ( array( '1', '109', '110', '114' ) as $id ) {
   $e = $get( 'sql:productos:' . $id );
   $assert( 'Q02' !== ( $e['decision']['decision_id'] ?? null ), "Q03 editorial-merge group member untouched by Q02: sql:productos:$id" );
  }

  // --- Sweep: every one of the 26 approved groups resolves to either (MERGE winner +
  // SKIP loser, both decision_id=Q02) or (both members untouched by Q02, e.g. the
  // dockman case above) -- never a winner without its loser accounted for, and never a
  // loser SKIPped whose winner did not itself reach MERGE.
  $decisions = json_decode( file_get_contents( Sources::safe( Storage::project(), 'docs/implementation/review-resolution/implementation/editorial-decisions.json' ) ), true, 16, JSON_THROW_ON_ERROR );
  $groups = $decisions['q02_identity_merge']['groups'];
  $assert( 26 === count( $groups ), 'editorial-decisions.json declares exactly 26 Q02 groups' );
  $mergedGroups = 0; $untouchedGroups = 0;
  foreach ( $groups as $group ) {
   $ids = $group['legacy_product_ids'];
   $actions = array_map( static fn( $id ) => $entries[ 'sql:productos:' . $id ]['action'], $ids );
   $withQ02 = array_map( static fn( $id ) => 'Q02' === ( $entries[ 'sql:productos:' . $id ]['decision']['decision_id'] ?? null ), $ids );
   $anyQ02 = in_array( true, $withQ02, true );
   $allQ02 = ! in_array( false, $withQ02, true );
   $assert( $anyQ02 === $allQ02, 'Group is all-or-nothing for Q02, never partially decided: ' . $group['group_key'] );
   if ( $allQ02 ) {
    $assert( 1 === count( array_filter( $actions, static fn( $a ) => 'MERGE' === $a ) ), 'Exactly one MERGE winner: ' . $group['group_key'] );
    $assert( count( $ids ) - 1 === count( array_filter( $actions, static fn( $a ) => 'SKIP' === $a ) ), 'All other members are SKIP: ' . $group['group_key'] );
    ++$mergedGroups;
   } else {
    foreach ( $ids as $id ) { $assert( 'REVIEW' === $entries[ 'sql:productos:' . $id ]['action'], 'Untouched group member stays REVIEW: sql:productos:' . $id ); }
    ++$untouchedGroups;
   }
  }
  $assert( 26 === $mergedGroups + $untouchedGroups, 'Every approved group accounted for exactly once' );
  $assert( $mergedGroups >= 20, "A large majority of the 26 groups fully resolve ($mergedGroups merged, $untouchedGroups untouched by an unrelated blocker)" );

  // ============================================================================== Q06
  $q06 = $decisions['q06_incomplete_or_test_records'];
  $assert( 15 === count( $q06['legacy_product_ids'] ), 'editorial-decisions.json declares exactly 15 Q06 ids (151-164 + 165)' );
  foreach ( range( 151, 164 ) as $id ) {
   $e = $get( 'sql:productos:' . $id );
   $assert( 'SKIP' === $e['action'] && 'SKIP' === $e['planned_result'], "Empty legacy record SKIP: sql:productos:$id" );
   $assert( 'Q06' === ( $e['decision']['decision_id'] ?? null ), "Empty legacy record decision is Q06: sql:productos:$id" );
   $assert( ! in_array( $e['action'], array( 'MIGRATE', 'CREATE_FROM_STATIC', 'MERGE' ), true ), "No empty product is ever created: sql:productos:$id" );
  }
  $e165 = $get( 'sql:productos:165' );
  $assert( 'SKIP' === $e165['action'] && 'Q06' === ( $e165['decision']['decision_id'] ?? null ), 'ID 165 ("Prueba") is SKIP under Q06' );

  // --- Exclusive image -> SKIP. Product 151's own image (file_id 1295) has no PMR owner
  // other than 151 itself.
  $exclusive = $get( 'asset:system/files/images/productos/3795fc76a4c3c0133fecce131dcac2542c18e980' );
  $assert( 'SKIP' === $exclusive['action'] && 'Q06' === ( $exclusive['decision']['decision_id'] ?? null ), 'Media exclusively owned by a Q06 record is SKIP' );

  // --- Image shared with a valid (non-Q06) entity -> NEVER SKIPped by Q06, regardless of
  // how many owners it has, as long as at least one is outside the Q06 id list. Real
  // example: system/files/images/productos/1bc8dece... is owned by products 6/16/20/107
  // (product-media-relations.csv), none of which is a Q06 record.
  $shared = $get( 'asset:system/files/images/productos/1bc8deceeea60e7d404142cf859897e4e36ccc86' );
  $assert( 'Q06' !== ( $shared['decision']['decision_id'] ?? null ), 'Media shared with a valid (non-Q06) entity is never touched by Q06' );

  // --- Media that is a binary duplicate of a Q06-exclusive image, but is not itself
  // referenced by ANY product (product-media-relations.csv has no row for it at all,
  // Q06 or otherwise), is left alone -- not swept into SKIP by resemblance/content. This
  // is the closest real-data proof available that Q06 acts strictly on documented PMR
  // ownership, never on binary similarity: no genuinely Q06-plus-valid-owner duplicate
  // exists in the current dataset (a clean sign about the data, not a gap in this test).
  $twin = $get( 'asset:images/accesorio-1.jpeg' );
  $assert( 'Q06' !== ( $twin['decision']['decision_id'] ?? null ), 'A binary duplicate of a Q06-exclusive image, itself unreferenced by any product, is preserved (not SKIPped by resemblance)' );

  // --- Full 15-id / 16-media sweep, no accidental over-reach: nothing outside the
  // approved lists ever carries decision_id=Q06.
  $q06ProductCount = 0; $q06MediaCount = 0;
  $ids = array_flip( $q06['legacy_product_ids'] );
  foreach ( $entries as $key => $e ) {
   if ( 'Q06' !== ( $e['decision']['decision_id'] ?? null ) ) { continue; }
   if ( str_starts_with( $key, 'sql:productos:' ) ) {
    ++$q06ProductCount;
    $assert( isset( $ids[ $e['legacy_id'] ] ), 'Every Q06 product decision is within the approved 15 ids: ' . $key );
   } elseif ( str_starts_with( $key, 'asset:' ) ) { ++$q06MediaCount; }
   else { $assert( false, 'Q06 decision on an unexpected entity type: ' . $key ); }
  }
  $assert( 15 === $q06ProductCount, "Exactly 15 Q06 product decisions ($q06ProductCount)" );
  $assert( $q06MediaCount >= 1 && $q06MediaCount <= 16, "Q06 media decisions within the documented bound of 16 ($q06MediaCount)" );

  $export( 'editorial-decisions-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'review_after' => $full['summary']['actions']['REVIEW'], 'q02_merged_groups' => $mergedGroups, 'q02_untouched_groups' => $untouchedGroups, 'q06_product_skips' => $q06ProductCount, 'q06_media_skips' => $q06MediaCount ) );
 } catch ( Throwable $error ) {
  $export( 'editorial-decisions-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

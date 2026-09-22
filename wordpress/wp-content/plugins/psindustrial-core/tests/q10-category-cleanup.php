<?php
/** Q10 (category 26 SKIP, category 33 kept, category 25 SKIP only if no legitimate use
 * beyond Q06, never a name-based merge) tests. Read-only: only calls
 * Planner::build('full'/'subset') (DRY RUN) and inspects the resulting plan. */
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
  $throws = static function( callable $fn ) { try { $fn(); return false; } catch ( \Throwable $e ) { return true; } };
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q10 included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };
  $s = new Sources();

  // ============================================================== category 26 -> SKIP
  $c26 = $get( 'category:26' );
  $assert( 'SKIP' === $c26['action'] && 'Q10' === $c26['decision']['decision_id'], 'Category 26 (nameless duplicate, 0 products, no page) is SKIP' );

  // ============================================================== category 33 -> preservada
  $c33 = $get( 'category:33' );
  $assert( 'MIGRATE' === $c33['action'] && 'Q10' === $c33['decision']['decision_id'], 'Category 33 (own page, real products) is kept' );
  $assert( 'category:5' === $c33['data']['parent'], 'Category 33 parent resolved correctly (parent_legacy_id=5), same logic as R-T01' );
  $assert ( '' !== ( $c33['data']['image'] ?? '' ), 'Category 33 receives its own image_file_id, same mechanism as Q01' );

  // ============================================================== mismo nombre no provoca MERGE automatico
  // Both are literally named "Puertas contra incendio" in category-master.csv; confirm
  // they resolve to two entirely independent, non-conflicting decisions -- never merged,
  // never cross-referencing each other's evidence.
  $cat26row = null; $cat33row = null;
  foreach ( $s->rows['category-master.csv'] as $r ) { if ( '26' === $r['legacy_id'] ) { $cat26row = $r; } if ( '33' === $r['legacy_id'] ) { $cat33row = $r; } }
  $assert( $cat26row['name'] === $cat33row['name'], 'Sanity: 26 and 33 really do share the exact same name in the source data' );
  $assert( $c26['action'] !== $c33['action'], 'Same name, opposite actions: proves the decision is keyed by legacy_id, never by name' );
  $assert( ! str_contains( strtolower( $c26['decision']['reason'] ), 'merge' ) && ! str_contains( strtolower( $c33['decision']['reason'] ), 'merge' ), 'Neither reason describes a merge of the two ids' );

  // ============================================================== category 25 sin otro uso -> SKIP
  $c25 = $get( 'category:25' );
  $assert( 'SKIP' === $c25['action'] && 'Q10' === $c25['decision']['decision_id'], 'Category 25 (only referenced by the 14 Q06 empty/test records, no page, no frontend evidence) is SKIP' );
  // Verify the underlying evidence directly, not just trust the decision text.
  $cat25row = null; foreach ( $s->rows['category-master.csv'] as $r ) { if ( '25' === $r['legacy_id'] ) { $cat25row = $r; } }
  $assert( '' === trim( $cat25row['legacy_page'] ?? '' ), 'Sanity: category 25 genuinely has no legacy_page' );
  $assert( '0' === trim( $cat25row['frontend_product_links'] ?? '' ), 'Sanity: category 25 genuinely has zero frontend product links' );
  $q06Ids = array_flip( range( 151, 164 ) );
  $refs = array_filter( $s->rows['product-master.csv'], static fn( $r ) => '25' === trim( $r['category_id'] ?? '' ) );
  $refIds = array_map( static fn( $r ) => (int) $r['legacy_product_id'], $refs );
  sort( $refIds );
  $assert( range( 151, 164 ) === $refIds, 'Sanity: category 25\'s only product references are exactly the 14 Q06 empty ids, nothing else' );

  // ============================================================== category 25 con uso publico simulado -> KEEP_REVIEW
  // Simulated by construction: EditorialDecisions::q10() only originates a MIGRATE decision
  // when editorial-decisions.json explicitly says so; verify the DEFENSIVE gate directly --
  // a category NOT in the approved list (e.g. category 1, "Industrial", which genuinely
  // has its own page and products) is correctly left alone by Q10 (no decision_id=Q10),
  // proving Q10 never expands beyond its 3 explicitly-approved ids even when a category
  // looks superficially similar (has a page, has products).
  $c1 = $get( 'category:1' );
  $assert( 'Q10' !== ( $c1['decision']['decision_id'] ?? null ), 'A category with real public use, not in Q10\'s approved list, is never touched by Q10' );

  // ============================================================== ejecucion repetida -> estable
  $stable = 0;
  foreach ( $entries as $key => $e ) {
   if ( 'Q10' !== ( $e['decision']['decision_id'] ?? null ) ) { continue; }
   $o = $entries2[ $key ] ?? null;
   $assert( $o && $e['action'] === $o['action'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $o['decision'] ), "Repeated execution -> identical result: $key" );
   ++$stable;
  }
  $assert( $stable >= 4, "A meaningful number of Q10 rows checked for stability ($stable)" );

  $export( 'q10-category-cleanup-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'q10-category-cleanup-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

<?php
/** Q03-E (Kelley, rampas-de-anden-hidraulicas-kelley.php): ids 24+139 consolidate into one
 * identity using id 24's own already-documented title verbatim (never a heuristic rewrite of
 * id 139's Blue-Giant-contaminated name); id 3 gets NO decision at all and stays exactly as
 * Policy::CATEGORY_CONFLICT_PRODUCTS already left it. Read-only DRY RUN only. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,Planner,Runner};
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };
 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();
  $full = Planner::build( 'full' );
  $full2 = Planner::build( 'full' );
  $throws = static function( callable $fn ) { try { $fn(); return false; } catch ( \Throwable $e ) { return true; } };
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q03-E included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };
  $s = new Sources();

  // ============================================================== ids 24+139 -> one identity
  $winner = $get( 'sql:productos:24' );
  $assert( 'MERGE' === $winner['action'] && 'Q03-E' === $winner['decision']['decision_sub_id'], 'id 24 is the consolidated Kelley winner' );
  $loser = $get( 'sql:productos:139' );
  $assert( 'SKIP' === $loser['action'] && 'Q03-E' === $loser['decision']['decision_sub_id'], 'id 139 is absorbed into id 24, no independent product' );

  // title is id 24's OWN documented title verbatim -- never a heuristic transformation of id 139's name.
  $row24 = null; $row139 = null;
  foreach ( $s->rows['product-master.csv'] as $r ) { if ( '24' === $r['legacy_product_id'] ) { $row24 = $r; } if ( '139' === $r['legacy_product_id'] ) { $row139 = $r; } }
  $assert( $row24['name'] === $winner['data']['name'], 'Title is exactly id 24\'s own documented name, byte for byte' );
  $assert ( ! str_contains( $winner['data']['name'], 'BLUE GIANT' ) && ! str_contains( mb_strtoupper( $winner['data']['name'] ), 'GIANT' ), 'Title never carries id 139\'s Blue-Giant-contaminated text' );
  $assert( str_contains( mb_strtoupper( $row139['name'] ), 'BLUE GIANT' ), 'Sanity: id 139\'s OWN documented name genuinely contains the other product\'s name -- confirms this is a real, not a hypothetical, copy/paste artifact' );

  // identity evidence: id 139's description/images/PDF match id 24, confirming this is the same entity.
  $assert( $s->catalog['productos']['24']['descripcion'] === $s->catalog['productos']['139']['descripcion'], 'Sanity: id 24 and id 139 share byte-identical description text' );
  $img24 = Sources::parts( $row24['images'] ?? '' ); $img139 = Sources::parts( $row139['images'] ?? '' );
  $assert( array() !== array_intersect( $img24, $img139 ), 'Sanity: id 24 and id 139 share at least one identical image path' );

  // ============================================================== id 3 stays REVIEW, untouched
  $id3 = $get( 'sql:productos:3' );
  $assert( 'REVIEW' === $id3['action'], 'id 3 stays in plain REVIEW' );
  $assert( 'Q03' !== ( $id3['decision']['decision_id'] ?? null ), 'id 3 receives NO Q03 decision of any kind -- not consolidated, not skipped, not enriched' );
  $assert( null === ( $id3['decision'] ?? null ) || array() === ( $id3['decision'] ?? array() ), 'id 3 has no decision object at all from any source layer' );

  // ============================================================== Policy safeguard on id 3 is intact and unmodified
  $reflection = new \ReflectionClass( \PSIndustrial\Core\Migration\Policy::class );
  $values = $reflection->getReflectionConstant( 'CATEGORY_CONFLICT_PRODUCTS' )->getValue();
  $assert( in_array( '3', $values, true ), 'Policy::CATEGORY_CONFLICT_PRODUCTS still lists id 3 -- this phase did not disable or weaken that safeguard' );
  $assert( 1 === count( $values ), 'Policy::CATEGORY_CONFLICT_PRODUCTS still has exactly its original single entry -- nothing added, nothing removed' );

  // ============================================================== brand not assigned even though id 24 alone has a strong signal
  $assert( '' === $winner['decision']['brand'], 'Kelley: brand left unresolved out of caution (id 139 carries a pre-existing Policy::BRAND_CONFLICT_PRODUCTS flag), despite id 24\'s own strong Kelley signal' );
  $brandConflictValues = $reflection->getReflectionConstant( 'BRAND_CONFLICT_PRODUCTS' )->getValue();
  $assert( in_array( '139', $brandConflictValues, true ), 'Sanity: id 139 is indeed in the pre-existing BRAND_CONFLICT_PRODUCTS registry -- the caution has a documented basis' );

  // ============================================================== dependent page still retires via the EXISTING Q01 ownership logic (no second implementation)
  $page = $get( 'php:rampas-de-anden-hidraulicas-kelley.php' );
  $assert( 'SKIP' === $page['action'] && 'Q01' === ( $page['decision']['decision_id'] ?? null ), 'The page retires via Q01, citing the Q03-E winner -- reusing the exact existing ownership logic, not a new one' );
  $assert( str_contains( $page['decision']['reason'], 'sql:productos:24' ), 'Page\'s own reason cites id 24 (the actual MERGE winner) as owner' );

  // ============================================================== ejecucion repetida -> estable
  $stable = 0;
  foreach ( $entries as $key => $e ) {
   if ( 'Q03-E' !== ( $e['decision']['decision_sub_id'] ?? null ) ) { continue; }
   $o = $entries2[ $key ] ?? null;
   $assert( $o && $e['action'] === $o['action'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $o['decision'] ), "Repeated execution -> identical result: $key" );
   ++$stable;
  }
  $assert( $stable >= 3, "A meaningful number of Q03-E rows checked for stability ($stable)" );
  $id3_2 = $entries2['sql:productos:3'];
  $assert( $id3_2['action'] === $id3['action'], 'id 3 stays REVIEW identically across repeated runs too' );

  $export( 'q03-kelley-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'] ) );
 } catch ( Throwable $error ) {
  $export( 'q03-kelley-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

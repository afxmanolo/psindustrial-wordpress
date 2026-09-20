<?php
/** Q13 (a binary-identical second legacy path never becomes a second attachment, but is
 * preserved as an auditable alias) tests. Read-only: only calls
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
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q13 included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };
  $s = new Sources();

  // The one known case from the earlier ensayo: fichas/puerta-420.pdf (winner, MIGRATE)
  // declares system/files/images/productos/9856266836... as its binary_alias.
  $winnerKey = 'asset:fichas/puerta-420.pdf';
  $aliasPath = 'system/files/images/productos/9856266836c012c90673224e9379a1a7f4b32389';
  $aliasKey = 'asset:' . $aliasPath;

  // ============================================================== same SHA-256 -> un attachment logico
  $winner = $get( $winnerKey );
  $alias = $get( $aliasKey );
  $assert( 'MIGRATE' === $winner['action'], 'Winner (fichas/puerta-420.pdf) reaches MIGRATE as the sole logical attachment' );
  $assert( 'SKIP' === $alias['action'] && 'Q13' === $alias['decision']['decision_id'], 'Second legacy path (binary-identical) is SKIP, never a second attachment' );
  $winnerHash = $s->asset( 'fichas/puerta-420.pdf' )['sha256'];
  $aliasHash = $s->asset( $aliasPath )['sha256'];
  $assert( hash_equals( $winnerHash, $aliasHash ), 'Independent proof: the two legacy paths really are byte-identical (SHA-256)' );

  // ============================================================== segunda legacy_path preservada como alias
  $assert( in_array( $aliasPath, $winner['binary_aliases'] ?? array(), true ), 'The alias path is recorded on the winning entity\'s own binary_aliases (auditable trail)' );
  $assert( str_contains( $alias['decision']['reason'], 'fichas/puerta-420.pdf' ) || str_contains( $alias['decision']['reason'], $winnerKey ), 'The alias\'s own SKIP reason cites which entity it is an alias of' );

  // ============================================================== mismo filename con bytes diferentes -> NO dedupe
  // Prove this is keyed by hash-verified alias declarations, not by any name heuristic: an
  // arbitrary OTHER media path with a similar-looking name but no binary_aliases
  // relationship anywhere gets no Q13 decision at all.
  $unrelated = null;
  foreach ( $s->rows['media-master.csv'] as $r ) {
   if ( 'system/files/images/productos/9856266836c012c90673224e9379a1a7f4b32389' === $r['legacy_path'] ) { continue; }
   if ( str_starts_with( $r['legacy_path'], 'system/files/images/productos/' ) && 'application/pdf' === $r['detected_type'] ) { $unrelated = $r['legacy_path']; break; }
  }
  $assert( null !== $unrelated, 'Sanity: found an unrelated PDF path to use as a negative control' );
  $unrelatedEntity = $get( 'asset:' . $unrelated );
  $assert( 'Q13' !== ( $unrelatedEntity['decision']['decision_id'] ?? null ), 'An unrelated media path (no declared binary_alias relationship) never receives a Q13 decision' );

  // ============================================================== ejecucion repetida -> estable
  $stable = 0;
  foreach ( $entries as $key => $e ) {
   if ( 'Q13' !== ( $e['decision']['decision_id'] ?? null ) ) { continue; }
   $o = $entries2[ $key ] ?? null;
   $assert( $o && $e['action'] === $o['action'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $o['decision'] ), "Repeated execution -> identical result: $key" );
   ++$stable;
  }
  $assert( $stable >= 1, "At least the one known Q13 row checked for stability (got $stable)" );

  $export( 'q13-media-alias-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'q13-media-alias-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

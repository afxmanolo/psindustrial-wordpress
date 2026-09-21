<?php
/** Q03-B (Modern Steel), Q03-C (Lift Master Mod H) and Q03-D (Thermospan Modelo 150) tests:
 * identity consolidates, brand stays explicitly unresolved (Q09), and for D specifically the
 * description field conflict is preserved, never synthesized. Read-only DRY RUN only. */
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
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q03-B/C/D included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };

  // ============================================================== Q03-B: one product, no brand
  $modernSteel = $get( 'sql:productos:6' );
  $assert( 'MERGE' === $modernSteel['action'] && 'Q03-B' === $modernSteel['decision']['decision_sub_id'], 'coleccion-modern-steel.php: id 6 is the single consolidated product' );
  $assert( '' === $modernSteel['decision']['brand'], 'Modern Steel: brand is explicitly empty, never Overhead Door/Wayne Dalton/Clopay' );
  $assert( 'BRAND_UNRESOLVED_Q09' === $modernSteel['decision']['field_provenance']['brand_status'], 'Modern Steel: field_provenance records BRAND_UNRESOLVED_Q09' );
  $msWinners = 0; $msLosers = 0;
  foreach ( array( '6', '16', '20', '107' ) as $id ) {
   $e = $get( 'sql:productos:' . $id );
   if ( 'MERGE' === $e['action'] ) { ++$msWinners; } elseif ( 'SKIP' === $e['action'] ) { ++$msLosers; }
   $assert( 'REVIEW' !== $e['action'], "Modern Steel id $id is not stranded in plain REVIEW (the product is not duplicated just because the brand is unknown)" );
  }
  $assert( 1 === $msWinners && 3 === $msLosers, "Modern Steel: exactly one winner, three absorbed siblings (winners=$msWinners losers=$msLosers)" );
  // sanity: the 3-way brand conflict genuinely exists in the source data (never invented as a pretext).
  $s = new Sources();
  $msBrands = array();
  foreach ( $s->rows['product-master.csv'] as $r ) { if ( in_array( $r['legacy_product_id'], array( '6', '16', '20', '107' ), true ) && '' !== trim( $r['brand_id'] ?? '' ) && '0' !== $r['brand_id'] ) { $msBrands[ $r['brand_id'] ] = true; } }
  $assert( count( $msBrands ) >= 3, 'Sanity: Modern Steel genuinely has 3+ distinct brand_id values across its members' );

  // ============================================================== Q03-C: one product, no brand
  $liftMaster = $get( 'sql:productos:43' );
  $assert( 'MERGE' === $liftMaster['action'] && 'Q03-C' === $liftMaster['decision']['decision_sub_id'], 'lift-master-mod-h.php: id 43 is the single consolidated product' );
  $assert( '' === $liftMaster['decision']['brand'], 'LiftMaster: brand is explicitly empty, never LiftMaster nor Blue Giant' );
  $assert( 'BRAND_UNRESOLVED_Q09' === $liftMaster['decision']['field_provenance']['brand_status'], 'LiftMaster: field_provenance records BRAND_UNRESOLVED_Q09' );
  $lmLoser = $get( 'sql:productos:81' );
  $assert( 'SKIP' === $lmLoser['action'], 'id 81 absorbed into id 43, no independent product' );
  // evidence of both attributions is preserved (never erased): brand_id points to LiftMaster;
  // the dependent page's own title still says Blue Giant -- both remain readable from source.
  $page = null; foreach ( $s->rows['content-master.csv'] as $r ) { if ( 'lift-master-mod-h.php' === $r['legacy_file'] ) { $page = $r; } }
  $assert( null !== $page && str_contains( $page['title'] ?? '', 'Blue Giant' ), 'Sanity: the page\'s own title evidence (Blue Giant) is untouched, still readable from content-master.csv' );

  // ============================================================== Q03-D: one identity, no brand, content conflict preserved
  $thermospan = $get( 'sql:productos:14' );
  $assert( 'MERGE' === $thermospan['action'] && 'Q03-D' === $thermospan['decision']['decision_sub_id'], 'puertas-seccionales-de-acero-aisladas-thermospan-modelo-150.php: id 14 is the single consolidated product' );
  $assert( '' === $thermospan['decision']['brand'], 'Thermospan: brand is explicitly empty, never Wayne Dalton nor Clopay' );
  $assert( 'BRAND_UNRESOLVED_Q09' === $thermospan['decision']['field_provenance']['brand_status'], 'Thermospan: field_provenance records BRAND_UNRESOLVED_Q09' );
  $tsProv = $thermospan['decision']['field_provenance']['description'];
  $assert( 'CONFLICT' === $tsProv['status'], 'Thermospan: description genuinely differs between id 14 and id 57 -> CONFLICT, not silently picked' );
  $assert( null === $tsProv['source_id'], 'Thermospan: CONFLICT never claims a single winning description source' );
  $reasonLower = mb_strtolower( $thermospan['decision']['reason'] );
  $assert( str_contains( $reasonLower, 'conflict' ) || str_contains( $reasonLower, 'descripcion=conflict' ), 'Thermospan: the decision\'s own reason text records the description conflict' );
  $assert( 'MERGE' === $thermospan['action'], 'Thermospan: identity STILL consolidates despite the field conflict -- the two are separate axes' );
  $tsLoser = $get( 'sql:productos:57' );
  $assert( 'SKIP' === $tsLoser['action'], 'id 57 absorbed into id 14, no independent product' );
  $desc14 = ( new Sources() )->catalog['productos']['14']['descripcion'];
  $desc57 = ( new Sources() )->catalog['productos']['57']['descripcion'];
  $assert( $desc14 !== $desc57, 'Sanity: the two descriptions genuinely differ in the source data' );
  $assert ( '' !== trim( $desc14 ) && '' !== trim( $desc57 ), 'Sanity: both are non-empty (a real conflict, not an empty-vs-populated case)' );

  // ============================================================== ejecucion repetida -> estable, los 3
  $stable = 0;
  foreach ( array( 'Q03-B', 'Q03-C', 'Q03-D' ) as $sub ) {
   foreach ( $entries as $key => $e ) {
    if ( $sub !== ( $e['decision']['decision_sub_id'] ?? null ) ) { continue; }
    $o = $entries2[ $key ] ?? null;
    $assert( $o && $e['action'] === $o['action'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $o['decision'] ), "Repeated execution -> identical result: $key" );
    ++$stable;
   }
  }
  $assert( $stable >= 16, "A meaningful number of Q03-B/C/D rows checked for stability ($stable)" );

  $export( 'q03-brand-unresolved-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'] ) );
 } catch ( Throwable $error ) {
  $export( 'q03-brand-unresolved-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

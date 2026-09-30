<?php
/** Q03-A (7 documented name-variant groups, no category ever in dispute) tests. Covers the
 * exact 7 groups only -- spaces, case, typo, descriptive prefix, registered mark, extra
 * model text, unconfirmed-brand-suffix -- and proves the normalization is NOT a general
 * rule applied to any other "similar-looking" product. Read-only DRY RUN only. */
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
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q03-A included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };

  // ============================================================== spaces ("511/521" vs "511 / 521")
  $aluminio = $get( 'sql:productos:5' );
  $assert( 'MERGE' === $aluminio['action'] && 'Q03-A' === $aluminio['decision']['decision_sub_id'], 'cortina-en-aluminio-serie-511-521.php: id 5 consolidates' );
  $lost = $get( 'sql:productos:88' );
  $assert( 'SKIP' === $lost['action'], 'id 88 (spacing variant) absorbed, no independent product' );
  $assert( 'Cortina en aluminio serie 511/521' === $aluminio['data']['name'], 'Title is the natural winner\'s own name, unmodified' );

  // ============================================================== case ("Puerta rÃ¡pida apilable" vs "RÃPIDA APILABLE")
  $apilable = $get( 'sql:productos:39' );
  $assert( 'MERGE' === $apilable['action'], 'rapida-apilable.php: id 39 (proper case, more complete) consolidates' );
  $assert( str_starts_with( $apilable['data']['name'], 'Puerta' ), 'Title keeps the proper-case, more complete documented form, never the all-caps abbreviation' );

  // ============================================================== typo ("autorreparabale" vs "autorreparable")
  $rolli = $get( 'sql:productos:105' );
  $assert( 'MERGE' === $rolli['action'], 'rolli-zip.php: id 105 (corrected spelling) consolidates' );
  $assert( str_ends_with( $rolli['data']['name'], 'autorreparable' ), 'Title uses the id that ALREADY carries the correct spelling in its own legacy record, never a rewrite' );
  $rolliLoser = $get( 'sql:productos:40' );
  $assert( 'SKIP' === $rolliLoser['action'], 'id 40 (the typo) is the absorbed sibling, not the winner' );

  // ============================================================== descriptive prefix ("Thermacore serie 592" vs "Puerta seccional de acero Thermacore serie 592")
  $thermacore = $get( 'sql:productos:62' );
  $assert( 'MERGE' === $thermacore['action'], 'puerta-seccional-de-acero-thermacore-serie-592.php: id 62 (fuller documented title) consolidates' );
  $assert( str_starts_with( $thermacore['data']['name'], 'Puerta seccional' ), 'Title is the fuller, already-documented form' );

  // ============================================================== registered mark ("Fast-Seal Â®" vs "Fast-SealÂ® High Performance Door")
  $fastSeal = $get( 'sql:productos:101' );
  $assert( 'MERGE' === $fastSeal['action'], 'fast-seal-high-performance-door.php: id 101 (fuller documented title) consolidates' );
  $assert( str_contains( $fastSeal['data']['name'], 'High Performance Door' ), 'Title keeps the fuller documented form' );

  // ============================================================== extra model text ("...3717 1 3/4\"" vs "...3717 1 3/4â€³, 3720 /3724 / 3722")
  $energy = $get( 'sql:productos:17' );
  $assert( 'MERGE' === $energy['action'], 'energy-series...: id 17 (documents 3 more model numbers) consolidates' );
  $assert( str_contains( $energy['data']['name'], '3720' ) && str_contains( $energy['data']['name'], '3724' ) && str_contains( $energy['data']['name'], '3722' ), 'Title keeps the fuller documented form; the extra model numbers are copied from an EXISTING legacy record, never invented' );
  $s = new Sources();
  $assert( $s->catalog['productos']['2']['descripcion'] === $s->catalog['productos']['17']['descripcion'], 'Sanity: description is IDENTICAL between the two -- no content is lost by preferring the fuller title' );

  // ============================================================== brand suffix not confirmed in TITLE (Q03-A7)
  $labio = $get( 'sql:productos:51' );
  $assert( 'MERGE' === $labio['action'], 'labio-de-elevacion-mecanico-dockman.php: id 51 (neutral title) consolidates' );
  $row51 = null; $row143 = null;
  foreach ( ( new Sources() )->rows['product-master.csv'] as $r ) { if ( '51' === $r['legacy_product_id'] ) { $row51 = $r; } if ( '143' === $r['legacy_product_id'] ) { $row143 = $r; } }
  $assert( $row51['name'] === $labio['data']['name'], 'Title is exactly id 51\'s own documented (neutral) name, byte for byte' );
  $assert( ! str_contains( mb_strtolower( $labio['data']['name'] ) , 'dockman' ), 'Title is the NEUTRAL form -- "Dockman" is never appended to the free-text title' );
  $assert( str_contains( mb_strtolower( $row143['name'] ), 'dockman' ), 'Sanity: it is specifically id 143\'s OWN documented name that mentions Dockman, confirming the title choice is deliberate, not incidental' );
  $assert( 'brand:10' === $labio['decision']['brand'], 'Brand IS assigned via taxonomy (Dockman, brand_id=10, CONFIRMED on the winning record) -- the restriction was only about the TITLE text, never the taxonomy' );
  $labioLoser = $get( 'sql:productos:143' );
  $assert( 'SKIP' === $labioLoser['action'], 'id 143 (title mentions Dockman) is the absorbed sibling' );

  // ============================================================== the rule is NOT extended to other, merely similar-looking products
  // Any product whose canonical_candidate_group is NOT one of the 7 approved Q03-A group
  // keys must never receive a Q03-A decision, even if its own name differs cosmetically too.
  $aGroupKeys = array_column( json_decode( file_get_contents( Storage::project() . '/docs/implementation/review-resolution/implementation/editorial-decisions.json' ), true )['q03_group_consolidation']['name_consolidation']['groups'], 'group_key' );
  $offList = 0;
  foreach ( $s->rows['product-master.csv'] as $r ) {
   if ( in_array( $r['canonical_candidate_group'] ?? '', $aGroupKeys, true ) ) { continue; }
   $e = $entries[ 'sql:productos:' . $r['legacy_product_id'] ] ?? null;
   if ( $e ) { $assert( 'Q03-A' !== ( $e['decision']['decision_sub_id'] ?? null ), 'Product outside the 7 approved groups never receives a Q03-A decision: ' . $r['legacy_product_id'] ); ++$offList; }
  }
  $assert( $offList > 100, "Checked a large number of off-list products ($offList), none touched by Q03-A" );

  // ============================================================== ejecucion repetida -> estable
  $stable = 0;
  foreach ( $entries as $key => $e ) {
   if ( 'Q03-A' !== ( $e['decision']['decision_sub_id'] ?? null ) ) { continue; }
   $o = $entries2[ $key ] ?? null;
   $assert( $o && $e['action'] === $o['action'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $o['decision'] ), "Repeated execution -> identical result: $key" );
   ++$stable;
  }
  $assert( $stable >= 25, "A meaningful number of Q03-A rows checked for stability ($stable)" );

  // exactly 7 winners, 7 losers.
  $winners = 0; $losers = 0;
  foreach ( $entries as $e ) {
   if ( 'Q03-A' !== ( $e['decision']['decision_sub_id'] ?? null ) ) { continue; }
   if ( 'MERGE' === $e['action'] ) { ++$winners; } elseif ( 'SKIP' === $e['action'] ) { ++$losers; }
  }
  $assert( 7 === $winners && 7 === $losers, "Exactly 7 winners and 7 losers across the 7 Q03-A groups (winners=$winners losers=$losers)" );

  $export( 'q03-name-consolidation-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'] ) );
 } catch ( Throwable $error ) {
  $export( 'q03-name-consolidation-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

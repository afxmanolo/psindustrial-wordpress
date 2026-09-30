<?php
/** Q04 (the 31+1 static-product-supplement.csv fichas become real psi_producto drafts,
 * without category/brand, without becoming a second entity alongside their equivalent
 * Page) tests. Read-only: only calls Planner::build('full'/'subset') (DRY RUN) and
 * inspects the resulting plan. Writes nothing except its own JSON report under
 * docs/implementation/importer-reports/. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,Planner,Runner,Identity};
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
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q04 included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };

  // ============================================================== static_product -> psi_producto draft
  $e = $get( 'static:1500-revolving-door.php' );
  $assert( 'CREATE_FROM_STATIC' === $e['action'] && 'Q04' === $e['decision']['decision_id'], '1500-revolving-door.php approved as CREATE_FROM_STATIC under Q04' );
  $assert( 'psi_producto' === $e['target_type'], 'Target type is psi_producto, not a Page' );
  $assert( Identity::find( $e ) > 0 && 'psi_producto' === get_post_type( Identity::find( $e ) ) && 'UNCHANGED' === Identity::prediction( $e ), 'Q04 identity exists as a product, with unchanged imported snapshot and no recreation' );

  // ============================================================== no category / no brand automatica
  // Excludes the one static_product already migrated in the earlier ensayo (a MANUAL,
  // pre-Q04 decision -- carries no decision_id at all -- correctly untouched by Q04's
  // rule via precedence; it is a different, already-approved decision, not a Q04 one).
  foreach ( $entries as $key => $e2 ) {
   if ( 'Q04' !== ( $e2['decision']['decision_id'] ?? null ) || 'CREATE_FROM_STATIC' !== $e2['action'] ) { continue; }
   $assert( array() === $e2['decision']['categories'], "No category is ever auto-assigned to a Q04 product: $key" );
   $assert( '' === $e2['decision']['brand'], "No brand is ever auto-assigned to a Q04 product: $key" );
   $assert( array() === $e2['data']['categories'] && '' === $e2['data']['brand'], "Planner's own data payload also carries no category/brand: $key" );
  }

  // ============================================================== contenido preservado
  $assert( '' !== trim( wp_strip_all_tags( $e['data']['content'] ) ), 'Static product content is preserved (non-empty extracted body)' );
  $assert( 'Puerta Holandesa' === $e['data']['name'], 'Static product name is preserved verbatim from static-product-supplement.csv' );

  // ============================================================== media preservada
  $assert( in_array( 'asset:images/revolving.jpg', $e['data']['images'], true ), 'Static product media is preserved (its own images column)' );
  $img = $get( 'asset:images/revolving.jpg' );
  $assert( 'MIGRATE' === $img['action'] && 'Q04' === $img['decision']['decision_id'], "Static product's own media independently approved" );

  // ============================================================== Page namespace equivalente -> no Page
  $page = $get( 'php:1500-revolving-door.php' );
  $assert( 'SKIP' === $page['action'] && 'Q04' === $page['decision']['decision_id'], 'The equivalent php: PRODUCT_PAGE entry is SKIP, never a Page' );

  // ============================================================== PRODUCT_PAGE equivalente -> no segundo producto
  // Double-enumeration check across the whole static-product-supplement.csv set: no
  // physical file becomes both a static_product CREATE and a php: page CREATE/MIGRATE.
  $doubleEnum = 0; $staticCount = 0;
  $s = new Sources();
  foreach ( $s->rows['static-product-supplement.csv'] as $r ) {
   $file = $r['legacy_php'];
   $staticEntry = $entries[ 'static:' . $file ] ?? null;
   $pageEntry = $entries[ 'php:' . $file ] ?? null;
   if ( ! $staticEntry || 'CREATE_FROM_STATIC' !== $staticEntry['action'] ) { continue; }
   ++$staticCount;
   if ( $pageEntry && in_array( $pageEntry['action'], array( 'MIGRATE', 'CREATE_FROM_STATIC' ), true ) ) { ++$doubleEnum; }
  }
  $assert( 0 === $doubleEnum, "No double enumeration across the full static-product-supplement.csv set ($staticCount resolved, 0 double-created)" );
  $assert( $staticCount >= 25, "A large majority of the 32 static_product rows resolve (>=25, got $staticCount)" );

  // ============================================================== legacy URL mapping -> unico
  // Exactly one target (the static product) claims this legacy_url's content; the php:
  // entry retains its own legacy_url for the future redirect phase, still pointing at the
  // SAME physical file, never a second, independent URL.
  $assert( $e['legacy_url'] === $page['legacy_url'], 'Static product and its retired page share the exact same legacy_url (one physical file, one canonical destination)' );

  // ============================================================== ejecucion repetida -> estable
  $stable = 0;
  foreach ( $entries as $key => $e2 ) {
   if ( 'Q04' !== ( $e2['decision']['decision_id'] ?? null ) ) { continue; }
   $o = $entries2[ $key ] ?? null;
   $assert( $o && $e2['action'] === $o['action'] && wp_json_encode( $e2['decision'] ) === wp_json_encode( $o['decision'] ), "Repeated execution -> identical result: $key" );
   ++$stable;
  }
  $assert( $stable > 50, "A meaningful number of Q04 rows checked for stability ($stable)" );

  // ============================================================== parecido semantico con producto SQL -> no merge automatico
  // static-product-supplement.csv rows have NO legacy_product_id and no related SQL row by
  // design (that's precisely why they are Q04 candidates, not Q02/R-P01 candidates); a
  // static product entity's key namespace ('static:') is entirely disjoint from
  // 'sql:productos:', so it structurally cannot collide with or be merged into any SQL
  // product regardless of name similarity.
  $assert( '' === trim( ( array_values( array_filter( $s->rows['static-product-supplement.csv'], static fn( $r ) => '1500-revolving-door.php' === $r['legacy_php'] ) )[0]['legacy_product_id'] ?? '' ) ), 'Sanity: static-product-supplement.csv rows carry no legacy_product_id (no SQL row to accidentally merge with)' );

  // ============================================================== Q03 group intacto
  // static-product-supplement.csv is entirely disjoint from any canonical_candidate_group
  // used by Q02/Q03 (it has no legacy_product_id at all); spot check confirms no overlap.
  $staticFiles = array_column( $s->rows['static-product-supplement.csv'], 'legacy_php' );
  $assert( ! in_array( 'accesspro-fs1000speed.php', $staticFiles, true ), 'Sanity: a known Q03 group file is not also a static_product candidate' );

  // ============================================================== missing OPTIONAL media never blocks the entity
  // Updated by Q05 -> Q07/Q10/Q11/Q13 phase: Q11 explicitly separates "this one image is
  // genuinely missing" (images/dura.jpg, images/magic.jpg — see
  // tests/q11-missing-media.php) from "this product cannot proceed". Before Q11 existed,
  // media_union() had no way to drop a single known-missing path and refused the whole
  // union, so these two legitimately stayed REVIEW; now they correctly proceed as drafts
  // with an empty gallery, never a fabricated replacement.
  foreach ( array( 'dura-glide-20003000-puerta.php', 'magic-fuerza-del-operador.php' ) as $file ) {
   $e2 = $get( 'static:' . $file );
   $assert( 'CREATE_FROM_STATIC' === $e2['action'] && 'Q04' === ( $e2['decision']['decision_id'] ?? null ), "Static product with only a documented missing-media reference proceeds as a draft: $file" );
   $assert( array() === $e2['data']['images'], "Its gallery excludes only the missing image, never fabricates a replacement: $file" );
   $missing = $get( 'missing:images/' . ( str_starts_with( $file, 'dura' ) ? 'dura' : 'magic' ) . '.jpg' );
   $assert( 'REVIEW' === $missing['action'] && 'Q11' === $missing['decision']['decision_id'], "The missing reference itself stays separately, explicitly tracked as REVIEW: $file" );
  }

  $export( 'q04-static-products-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'static_resolved' => $staticCount, 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'q04-static-products-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

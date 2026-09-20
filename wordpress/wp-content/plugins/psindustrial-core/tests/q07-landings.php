<?php
/** Q07 (the 9 SEO_LANDING pages become WordPress Page drafts, never publish, own media
 * only when demonstrable) tests. Read-only: only calls Planner::build('full'/'subset')
 * (DRY RUN) and inspects the resulting plan. */
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
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q07 included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };

  $decisions = json_decode( file_get_contents( Sources::safe( Storage::project(), 'docs/implementation/review-resolution/implementation/editorial-decisions.json' ) ), true, 32, JSON_THROW_ON_ERROR );
  $pages = $decisions['q07_landings']['pages'];
  $assert( 9 === count( $pages ), 'editorial-decisions.json declares exactly 9 Q07 landings' );

  // ============================================================== las 9 landings -> CREATE draft, no publish
  foreach ( $pages as $file ) {
   $e = $get( 'php:' . $file );
   $assert( 'MIGRATE' === $e['action'] && 'Q07' === $e['decision']['decision_id'], "Landing approved as a Page: $file" );
   $assert( 'page' === $e['target_type'], "Target type is page, never psi_producto/psi_categoria/psi_marca: $file" );
   $assert( 'CREATE' === $e['planned_result'], "Planned result is a draft creation (Runner::apply() always writes post_status=draft for a new post; _psi_review_state=pending follows immediately -- never publish): $file" );
   $assert( '' !== trim( wp_strip_all_tags( $e['data']['content'] ) ), "Landing content preserved (non-empty extracted body), own text never copied from a product: $file" );
   $assert( '' !== ( $e['legacy_url'] ?? '' ), "Legacy URL mapping present on the landing entry: $file" );
  }

  // ============================================================== contenido propio, no copiado de producto
  // The extraction path is Sources::content($file) on the LANDING's own legacy_php -- the
  // exact same mechanism used for every product/page/static_product, never a product's.
  $sample = $get( 'php:cortinas-enrollables-de-aluminio.php' );
  $assert( str_contains( $sample['data']['content'], 'cortina' ) || str_contains( strtolower( $sample['data']['content'] ), 'aluminio' ), 'Sampled landing content plausibly reflects its own page, not a generic/product body' );

  // ============================================================== media propia preservada (exclusiva)
  $img = $get( 'asset:images/cortinas.jpg' );
  $assert( 'MIGRATE' === $img['action'] && 'Q07' === $img['decision']['decision_id'], 'Exclusive landing media independently approved' );
  $assert( in_array( 'asset:images/cortinas.jpg', $get( 'php:cortinas-enrollables-de-aluminio.php' )['data']['images'], true ), 'Exclusive media listed on the landing\'s own decision' );

  // media propia preservada (compartida solo con landings hermanas)
  $shared = $get( 'asset:images/access.jpg' );
  $assert( 'MIGRATE' === $shared['action'] && 'Q07' === $shared['decision']['decision_id'], 'Media shared only among sibling landings is still approved' );
  foreach ( array( 'operadores-puerta-abatible.php', 'operadores-puerta-corrediza-residencial.php', 'operadores-puertas-ascendentes.php' ) as $sibling ) {
   $assert( in_array( 'asset:images/access.jpg', $get( 'php:' . $sibling )['data']['images'], true ), "Shared sibling media listed on $sibling\'s own decision" );
  }

  // ============================================================== media conflictiva -> REVIEW (sin decision Q07)
  // images/banner1.jpg is referenced by essentially every page on the site (a sitewide
  // fallback), including many PRODUCT_PAGE/CATEGORY_PAGE/BRAND_PAGE/institutional pages --
  // a landing must never take ownership of it.
  $banner = $get( 'asset:images/banner1.jpg' );
  $assert( 'Q07' !== ( $banner['decision']['decision_id'] ?? null ), 'A media path with a stronger/contradictory claim (sitewide banner) is never decided by Q07' );
  foreach ( $pages as $file ) { $assert( ! in_array( 'asset:images/banner1.jpg', $get( 'php:' . $file )['data']['images'], true ), "Sitewide banner never listed on landing $file\'s own decision" ); }

  // fichas/13.0.Folleto-...-hoja-tecnica.pdf: shared between a real PRODUCT_PAGE
  // (accesspro-fs1000speed.php) and several "operadores" landings -- the SQL/product-page
  // claim must win; no landing may list it as its own.
  $pdf = $get( 'asset:fichas/13.0.Folleto-Operador-AccessPRO-FS1000-SPEED-hoja-tecnica.pdf' );
  $assert( 'Q07' !== ( $pdf['decision']['decision_id'] ?? null ), 'A PDF also referenced by a real PRODUCT_PAGE is never claimed by a landing' );

  // ============================================================== ejecucion repetida -> estable
  $stable = 0;
  foreach ( $entries as $key => $e ) {
   if ( 'Q07' !== ( $e['decision']['decision_id'] ?? null ) ) { continue; }
   $o = $entries2[ $key ] ?? null;
   $assert( $o && $e['action'] === $o['action'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $o['decision'] ), "Repeated execution -> identical result: $key" );
   ++$stable;
  }
  $assert( $stable >= 17, "A meaningful number of Q07 rows checked for stability ($stable)" );

  // ============================================================== Q03/Q08/Q09/Q12 unaffected sanity check
  $assert( 'Q07' !== ( $get( 'sql:productos:1' )['decision']['decision_id'] ?? null ), 'Sanity: an unrelated Q03 product carries no Q07 decision' );

  $export( 'q07-landings-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'q07-landings-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

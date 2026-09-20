<?php
/** Q01 (page content belongs to its canonical product/category/brand; no separate Page;
 * media reassignment where the current model has a legitimate field) tests. Read-only:
 * only calls Planner::build('full'/'subset') (DRY RUN) and inspects the resulting plan.
 * Writes nothing except its own JSON report under docs/implementation/importer-reports/. */
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
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q01 included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };

  // ============================================================== Same execution, repeated -> stable
  $q01Count = 0;
  foreach ( $entries as $key => $e ) {
   if ( 'Q01' !== ( $e['decision']['decision_id'] ?? null ) ) { continue; }
   $e2 = $entries2[ $key ] ?? null;
   $assert( $e2 && $e['action'] === $e2['action'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $e2['decision'] ), "Repeated execution -> identical mapping/ownership: $key" );
   ++$q01Count;
  }
  $assert( $q01Count > 100, "A meaningful number of Q01 rows were checked for stability ($q01Count)" );

  // ============================================================== page -> product (unambiguous)
  // puerta-seccional-de-acero-sin-aislamiento-424-uso-pesado.php is the ONLY (single
  // related_product_id) page for product 65, an R-P01 singleton. No Page is created; the
  // product's decision is enriched with the page's own richer images/pdfs.
  $page = $get( 'php:puerta-seccional-de-acero-sin-aislamiento-424-uso-pesado.php' );
  $product = $get( 'sql:productos:65' );
  $assert( 'SKIP' === $page['action'] && 'Q01' === $page['decision']['decision_id'], 'page -> product: no separate Page is planned' );
  $assert( 'MIGRATE' === $product['action'] && 'Q01' === $product['decision']['decision_id'], 'page -> product: content belongs to the product (unambiguous)' );
  $assert( in_array( 'asset:fichas/puerta-424.pdf', $product['data']['pdfs'], true ), 'page -> product: page-sourced PDF reaches the product gallery' );
  $assert( count( $product['data']['images'] ) >= 3, 'page -> product: page-sourced images (beyond PMR-only) reach the product gallery' );
  $assert( array( 'category:8' ) === $product['data']['categories'], 'page -> product: category unchanged from the underlying R-P01 policy decision' );

  // ============================================================== page -> category (unambiguous)
  $catPage = $get( 'php:puertas-seccionales-industriales.php' );
  $cat = $get( 'category:8' );
  $assert( 'SKIP' === $catPage['action'] && 'Q01' === $catPage['decision']['decision_id'], 'page -> category: no separate Page is planned' );
  // category:8 happens to also carry a manual (higher-precedence) decision from the earlier
  // ensayo, so its FINAL decision_id is not Q01 here -- confirming precedence is respected,
  // not that Q01 failed to compute one (a second, un-shadowed category proves the positive case below).
  $assert( 'MIGRATE' === $cat['action'], 'page -> category: category itself resolves' );

  // A category NOT already covered by a manual decision: content-supported field (image)
  // transfers from category-master.csv's own image_file_id.
  $catPage2 = $get( 'php:fraccionamientos-y-condominios.php' );
  $cat2 = $get( 'category:9' );
  $assert( 'SKIP' === $catPage2['action'] && 'Q01' === $catPage2['decision']['decision_id'], 'page -> category (unshadowed): no separate Page is planned' );
  $assert( 'MIGRATE' === $cat2['action'] && 'Q01' === $cat2['decision']['decision_id'], 'page -> category (unshadowed): content belongs to the category' );
  $assert( 'asset:system/files/images/categorias/9022847e37c72e9a2de76dcdf7c2e22b08cf6cb0' === $cat2['data']['image'], 'page -> category: supported field (image) transfers from image_file_id' );
  $assert( ! array_key_exists( 'images', $cat2['data'] ), 'unsupported field: a category never receives a gallery field the term model does not have (Fields.php/TermEditor.php define only a single image slot)' );

  // ============================================================== page -> brand (unambiguous)
  $brandPage = $get( 'php:clopay.php' );
  $brand = $get( 'brand:3' );
  $assert( 'SKIP' === $brandPage['action'] && 'Q01' === $brandPage['decision']['decision_id'], 'page -> brand: no separate Page is planned' );
  $assert( 'MIGRATE' === $brand['action'] && 'Q01' === $brand['decision']['decision_id'], 'page -> brand: content belongs to the brand' );
  $assert( 'asset:images/clopay.png' === $brand['data']['image'], 'page -> brand: supported field (logo) transfers from brand-master.csv:logo' );

  // ============================================================== media owned only by a page -> canonical entity
  $s = new Sources();
  $imgAsset = $get( 'asset:system/files/images/categorias/9022847e37c72e9a2de76dcdf7c2e22b08cf6cb0' );
  $assert( 'MIGRATE' === $imgAsset['action'] && 'Q01' === $imgAsset['decision']['decision_id'], 'media owned only by a page becomes an approved migration of its own' );

  // ============================================================== duplicate binary media -> not duplicated
  // product 65's page lists images/industrial-brown-sectional-steel-model-424-432.jpg,
  // images/t424.png, images/verficha.png -- verficha.png is the sitewide generic "ver
  // ficha" icon, reused verbatim across many pages; it must appear at most once.
  $seen = array_count_values( $product['data']['images'] );
  foreach ( $seen as $path => $count ) { $assert( 1 === $count, "No path appears twice in a product's own image list: $path" ); }
  // And by CONTENT, not just by path: hash every listed image, confirm no two share a hash.
  $hashes = array();
  foreach ( $product['data']['images'] as $key ) {
   $path = substr( $key, 6 );
   $hash = $s->asset( $path )['sha256'];
   $assert( ! isset( $hashes[ $hash ] ), "Duplicate binary media is not duplicated in the gallery (path=$path, hash=$hash)" );
   $hashes[ $hash ] = $path;
  }

  // ============================================================== owner ambiguo -> KEEP_REVIEW
  // A Q03 editorial-merge group (4 related_product_ids, NOT a Q02 identity group): which
  // of the 4 is canonical is not this phase's call.
  $ambiguousProductPage = $get( 'php:accesspro-fs1000speed.php' );
  $assert( 'REVIEW' === $ambiguousProductPage['action'] && null === ( $ambiguousProductPage['decision']['decision_id'] ?? null ), 'Q03 multi-product page (ambiguous owner) is left untouched, KEEP_REVIEW' );
  // A category page shared by two categories (27 and 28): no single-owner target.
  $ambiguousCatPage = $get( 'php:puertas-peatonales-estandar-y-reforzada.php' );
  $assert( 'REVIEW' === $ambiguousCatPage['action'] && null === ( $ambiguousCatPage['decision']['decision_id'] ?? null ), 'A page shared by two categories is left untouched, KEEP_REVIEW' );
  // Category pages with NO documented single-term owner (marcas.php, soluciones.php) and
  // the D06-conflict category (33, via contra-incendio.php) are equally untouched.
  foreach ( array( 'php:marcas.php', 'php:soluciones.php', 'php:contra-incendio.php' ) as $k ) {
   $e = $get( $k );
   $assert( 'REVIEW' === $e['action'] && null === ( $e['decision']['decision_id'] ?? null ), "No documented/approved single owner -> KEEP_REVIEW: $k" );
  }

  // ============================================================== Q03 groups remain intact
  // None of the 27 Q03 editorial-merge groups' member products carry a Q01/Q02 decision.
  $editorial = json_decode( file_get_contents( Sources::safe( Storage::project(), 'docs/implementation/review-resolution/implementation/editorial-decisions.json' ) ), true, 16, JSON_THROW_ON_ERROR );
  $q02Groups = array_column( $editorial['q02_identity_merge']['groups'], 'group_key' );
  $q03Sample = array( '1', '109', '110', '114' ); // accesspro-fs1000speed.php, confirmed Q03 (not in Q02's 26).
  $assert( ! in_array( 'accesspro-fs1000speed.php', $q02Groups, true ), 'Sanity: the Q03 sample group is genuinely not one of the 26 Q02 groups' );
  foreach ( $q03Sample as $id ) {
   $e = $get( 'sql:productos:' . $id );
   $assert( ! in_array( $e['decision']['decision_id'] ?? null, array( 'Q01', 'Q02' ), true ), "Q03 group member carries no Q01/Q02 decision: sql:productos:$id" );
  }

  // ============================================================== legacy URL mapping evidence
  // Every Q01-resolved page keeps its own legacy_url on its (SKIPped) entry -- the raw
  // material the future URL/redirect phase needs; this phase does not decide the target
  // permalink, only records which canonical entity now owns the content.
  foreach ( array( $page, $catPage2, $brandPage ) as $e ) {
   $assert( '' !== ( $e['legacy_url'] ?? '' ), 'Q01-resolved page entry retains its legacy_url for future URL mapping: ' . $e['entity_key'] );
  }

  // ============================================================== cross-dependency: Q05 unlocked by Q01
  // fichas/puerta-424.pdf was one of the 32 Q05 entries, still REVIEW after Q05+Q02+Q06
  // alone (its only owner, product 65, was not yet enriched) -- now MIGRATE via Q01.
  $q05File = $get( 'asset:fichas/puerta-424.pdf' );
  $assert( 'MIGRATE' === $q05File['action'], 'A Q05-approved PDF additionally unlocked by Q01 ownership resolution reaches MIGRATE' );

  $export( 'q01-content-ownership-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'q01-content-ownership-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

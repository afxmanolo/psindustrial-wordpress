<?php
/** Read-only header/Home order regression. Cache fixtures never persist metadata. */
if ( PHP_SAPI !== 'cli' ) { exit; }
$_SERVER += array( 'SERVER_NAME' => 'localhost', 'HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/' );
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || 'psindustrial_wp_dev' !== DB_NAME ) { exit( 1 ); }
use function PSIndustrial\Theme\{header_solutions_navigation,home_category_sections,catalog_navigation};
$checks = array(); $check = static function( $ok, $label ) use ( &$checks ) { $checks[] = array( 'test' => $label, 'passed' => (bool) $ok ); };
$snapshot = static function() { global $wpdb; return hash( 'sha256', serialize( array( $wpdb->get_results( "SELECT * FROM {$wpdb->terms} ORDER BY term_id" ), $wpdb->get_results( "SELECT * FROM {$wpdb->term_taxonomy} ORDER BY term_taxonomy_id" ), $wpdb->get_results( "SELECT * FROM {$wpdb->termmeta} ORDER BY meta_id" ) ) ) ); };
$before = $snapshot(); $home = home_category_sections(); $nav = header_solutions_navigation(); $footer = catalog_navigation(); $sidebar = catalog_navigation( true );
$check( count( $nav ) === 7, 'Exactly seven roots' );
$check( array_column( $home, 'legacy_id' ) === array( 1,3,4,5,6,2,7 ), 'Home order unchanged' );
$check( array_column( $nav, 'term_id' ) === array_column( $home, 'term_id' ), 'Header follows Home order' );
foreach ( $nav as $item ) { $term = get_term( $item['term_id'] ); $check( $term->taxonomy === 'psi_categoria' && (int) $term->parent === 0 && $item['label'] === $term->name && $item['url'] === get_term_link( $term ), 'Real root/name/link: ' . $term->name ); }
$check( ! in_array( get_post_type_archive_link( 'psi_producto' ), array_column( $nav, 'url' ), true ), 'No Productos archive entry' );
$id = $nav[0]['term_id']; $meta = get_term_meta( $id );
try {
 $changed = $meta; $changed['_psi_category_home_order'] = array( '999' ); wp_cache_set( $id, $changed, 'term_meta' );
 $sorted = header_solutions_navigation();
 $check( end( $sorted )['term_id'] === $id, 'Header dynamically follows editorial Home order' );
 $check( array_column( $sorted, 'term_id' ) === array_column( home_category_sections(), 'term_id' ), 'Header and Home stay aligned after editorial change' );
 $check( $footer === catalog_navigation() && $sidebar === catalog_navigation( true ), 'Home order cannot reorder footer or sidebar' );
 $changed = $meta; $changed['_psi_category_menu_order'] = array( '999' ); wp_cache_set( $id, $changed, 'term_meta' );
 $check( $nav === header_solutions_navigation(), 'Header independent of category menu order' );
} finally { wp_cache_set( $id, $meta, 'term_meta' ); }
$html = wp_remote_retrieve_body( wp_remote_get( home_url( '/' ) ) ); $dom = new DOMDocument(); @$dom->loadHTML( '<?xml encoding="UTF-8">' . $html ); $x = new DOMXPath( $dom );
$links = $x->query( '//header//ul[contains(@class,"sub-menu")]/li/a' );
$check( $links->length === 7, 'HTTP dropdown exactly seven links, no children or Products' );
$urls = array(); foreach ( $links as $link ) { $urls[] = $link->getAttribute( 'href' ); }
$check( $urls === array_column( $nav, 'url' ), 'HTTP dropdown exact real order' );
$urls = array(); foreach ( $x->query( '//footer//ul[@class="catalog-footer-links"]/li/a' ) as $link ) { $urls[] = $link->getAttribute( 'href' ); }
$check( $urls === array_column( $footer, 'url' ), 'Footer retains complete original navigation' );
$check( $before === $snapshot(), 'Names/slugs/hierarchy/metadata unchanged in database' );
$fail = array_filter( $checks, static fn( $c ) => ! $c['passed'] );
echo wp_json_encode( array( 'passed' => count($checks)-count($fail), 'failed' => count($fail), 'items' => array_column($nav,'label'), 'checks' => $checks ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ); exit( $fail ? 1 : 0 );

<?php
/** Read-only local integration tests. Optional argv[1]: private HTML preview directory. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || 'psindustrial_wp_dev' !== DB_NAME ) { exit( 1 ); }

use function PSIndustrial\Theme\product_images;
use function PSIndustrial\Theme\product_image;
use function PSIndustrial\Theme\product_datasheets;

$checks = array();
$check = static function( bool $ok, string $name ) use ( &$checks ): void {
	$checks[] = array( 'test' => $name, 'passed' => $ok );
};
$snapshot = static function(): string {
	global $wpdb;
	$values = array();
	foreach ( array( 'posts', 'postmeta', 'terms', 'termmeta', 'term_taxonomy', 'term_relationships' ) as $table ) {
		// Table names come exclusively from this fixed WordPress table allowlist.
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->$table}", ARRAY_A );
		$rows = array_map( 'serialize', $rows ); sort( $rows ); $values[ $table ] = hash( 'sha256', serialize( $rows ) );
	}
	return hash( 'sha256', serialize( $values ) );
};
$before = $snapshot();
$administrator_id = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID;
wp_set_current_user( $administrator_id ); // Local read-only previews may render existing drafts.
show_admin_bar( false );
// WordPress normally renders one document per request; these private previews render several.
add_action( 'get_header', static function() { if ( did_action( 'get_header' ) > 1 ) { include get_theme_file_path( 'header.php' ); } } );
add_action( 'get_footer', static function() { if ( did_action( 'get_footer' ) > 1 ) { include get_theme_file_path( 'footer.php' ); } } );
$preview = $argv[1] ?? '';
if ( $preview && ! is_dir( $preview ) ) { mkdir( $preview, 0700, true ); }
$render = static function( WP_Query $query, string $template ): string {
	$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = $query;
	wp_styles()->done = array(); wp_scripts()->done = array();
	ob_start(); include get_theme_file_path( $template ); return ob_get_clean();
};
$dom = static function( string $html ): DOMXPath {
	$d = new DOMDocument(); $previous = libxml_use_internal_errors( true );
	$d->loadHTML( '<?xml encoding="UTF-8">' . $html ); libxml_clear_errors(); libxml_use_internal_errors( $previous );
	return new DOMXPath( $d );
};
// Public fallback must never advertise a review-only taxonomy route.
$public_navigation = \PSIndustrial\Theme\catalog_navigation( true );
$check( $public_navigation[0]['url'] === get_post_type_archive_link( 'psi_producto' ), 'Navigation archive uses native archive permalink' );
$allowed_urls = array( get_post_type_archive_link( 'psi_producto' ) );
foreach ( get_terms( array( 'taxonomy' => array( 'psi_categoria', 'psi_marca' ), 'hide_empty' => false ) ) as $term ) {
    if ( 'public' === get_term_meta( $term->term_id, '_psi_public_state', true ) ) { $allowed_urls[] = get_term_link( $term ); }
}
$check( ! array_diff( array_column( $public_navigation, 'url' ), $allowed_urls ), 'Only real public taxonomy/archive destinations; REVIEW excluded' );
$samples = array( 1371 => 'published / Group A / no brand', 1360 => 'Group B / one PDF / one image', 1354 => 'gallery / multiple PDFs', 1359 => 'multiple assigned categories', 133 => 'Q04 / no PDF / no brand', 1171 => 'no images / no PDF', 1110 => 'six images' );
foreach ( $samples as $id => $purpose ) {
	$q = new WP_Query( array( 'post_type' => 'psi_producto', 'post_status' => 'any', 'p' => $id ) );
	$html = $render( $q, 'single-psi_producto.php' ); $x = $dom( $html );
	$check( $q->post_count === 1 && $x->query( '//h1' )->length === 1, "$id: product and unique H1" );
    $check( $x->query( '//aside//a[@href="#" or @href=""] | //nav//a[@href="#" or @href=""]' )->length === 0, "$id: sidebar and menu have no placeholder links" );
    $check( $x->query( '//nav//ul[contains(@class,"sub-menu")]//a' )->length >= 1, "$id: dropdown contains server-rendered links without JS" );
    $check( $x->query( '//aside//li/span[@aria-disabled="true"]' )->length === 0, "$id: every sidebar entry is a link" );
	$images = product_images( $id ); $pdfs = product_datasheets( $id );
	$check( $x->query( '//a[contains(@class,"product-gallery-item")]' )->length === count( $images ), "$id: gallery count" );
	$buttons = $x->query( '//a[@class="datasheet-button"]' );
	$check( $buttons->length === count( $pdfs ), "$id: PDF count" );
	foreach ( $buttons as $i => $button ) {
		$check( $button->getAttribute( 'href' ) === $pdfs[ $i ]['url'] && $button->getAttribute( 'target' ) === '_blank' && $button->getAttribute( 'rel' ) === 'noopener', "$id: actual PDF destination $i" );
		$check( $x->query( './/img[@alt="Ver ficha técnica"]', $button )->item( 0 )?->getAttribute( 'src' ) === get_theme_file_uri( 'assets/images/ver-ficha-tecnica.png' ), "$id: theme UI button $i" );
	}
	if ( 1 === count( $pdfs ) ) { $check( $x->query( '//a[@class="datasheet-button"]/span[not(@class="screen-reader-text")]' )->length === 0, "$id: single PDF without filename" ); }
	$check( $x->query( '//a[not(*) and not(normalize-space())]' )->length === 0, "$id: no empty focusable links" );
	$check( ! str_contains( $html, 'Warning:' ) && ! str_contains( $html, 'Fatal error:' ), "$id: no PHP error output" );
	if ( $preview ) { file_put_contents( $preview . "/product-$id.html", $html ); }
}
$menu_args = (object) array( 'theme_location' => 'primary' );
$menu_fixture = array(
    (object) array( 'url' => '#', 'type' => 'custom' ),
    (object) array( 'url' => get_permalink( 1371 ), 'type' => 'post_type', 'object_id' => 1371 ),
    (object) array( 'url' => get_permalink( 1360 ), 'type' => 'post_type', 'object_id' => 1360 ),
);
$filtered_menu = apply_filters( 'wp_nav_menu_objects', $menu_fixture, $menu_args );
$check( count( $filtered_menu ) === 1 && $filtered_menu[0]->object_id === 1371, 'Native assigned menu omits placeholder and draft destinations without changing menu data' );
$check( get_post_status( 1371 ) === 'publish', 'Human publication remains publish' );
$check( ! product_image( 1013 ), 'Known UI image excluded at presentation boundary' );
$image = product_images( 1371 )[0];
$pdf = (int) get_post_meta( 1371, '_psi_datasheets', true )[0]['attachment_id'];
$override = static function( $value, $object, $key ) use ( $image, $pdf ) {
	if ( 1371 === $object && '_psi_gallery_ids' === $key ) { return array( array( $image, $image, $pdf, 1013, 99999999 ) ); }
	return $value;
};
add_filter( 'get_post_metadata', $override, 20, 3 );
$check( product_images( 1371 ) === array( $image ), 'Duplicate IDs, PDF, UI image and invalid ID excluded without DB writes' );
remove_filter( 'get_post_metadata', $override, 20 );
$pdf_override = static function( $value, $object, $key ) use ( $image, $pdf ) {
	if ( 1371 === $object && '_psi_datasheets' === $key ) {
		return array( array_map( static fn( $id ) => array( 'attachment_id' => $id, 'label' => 'Ficha' ), array( $pdf, $pdf, $image, 1013, 99999999 ) ) );
	}
	return $value;
};
add_filter( 'get_post_metadata', $pdf_override, 20, 3 );
$check( count( product_datasheets( 1371 ) ) === 1, 'PDF presentation rejects image, UI, unknown attachment and duplicate ID' );
remove_filter( 'get_post_metadata', $pdf_override, 20 );
$video_override = static function( $value, $object, $key ) {
	if ( 1371 === $object && '_psi_videos' === $key ) {
		return array( array( array( 'provider' => 'youtube', 'video_id' => 'abcdefghijk', 'title' => 'Video de prueba' ), array( 'provider' => 'youtube', 'video_id' => '<script>', 'title' => 'Invalid' ), array( 'provider' => 'unknown', 'video_id' => 'abcdefghijk', 'title' => 'Invalid provider' ) ) );
	}
	return $value;
};
add_filter( 'get_post_metadata', $video_override, 20, 3 );
$video_html = $render( new WP_Query( array( 'post_type' => 'psi_producto', 'post_status' => 'any', 'p' => 1371 ) ), 'single-psi_producto.php' );
$check( $dom( $video_html )->query( '//iframe' )->length === 1 && str_contains( $video_html, 'https://www.youtube-nocookie.com/embed/abcdefghijk' ), 'Only approved video provider and valid ID rendered (in-memory fixture)' );
remove_filter( 'get_post_metadata', $video_override, 20 );

foreach ( array( 1371, 1360, 1354 ) as $id ) {
	foreach ( product_datasheets( $id ) as $sheet ) {
		$r = wp_remote_head( $sheet['url'] );
		$check( ! is_wp_error( $r ) && 200 === wp_remote_retrieve_response_code( $r ) && str_contains( wp_remote_retrieve_header( $r, 'content-type' ), 'application/pdf' ), "$id: real PDF HTTP 200 and PDF MIME" );
	}
}

// Test existing relations as they will render after an editor approves terms.
// The in-memory filter is confined to this CLI process and never persists approval.
wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
$cats = wp_get_object_terms( 1359, 'psi_categoria' );
$seccionales = wp_get_object_terms( 1371, 'psi_categoria' );
$brands = wp_get_object_terms( 1360, 'psi_marca' );
wp_set_current_user( $administrator_id );
$ids = array_merge( wp_list_pluck( $cats, 'term_id' ), wp_list_pluck( $seccionales, 'term_id' ), wp_list_pluck( $brands, 'term_id' ) );
$public = static function( $v, $id, $key ) use ( $ids ) { return '_psi_public_state' === $key && in_array( $id, $ids, true ) ? array( 'public' ) : $v; };
add_filter( 'get_term_metadata', $public, 20, 3 );
foreach ( array( 1359, 1360 ) as $id ) {
	$html = $render( new WP_Query( array( 'post_type' => 'psi_producto', 'post_status' => 'any', 'p' => $id ) ), 'single-psi_producto.php' );
	$x = $dom( $html );
	$check( $x->query( '//div[@class="product-terms"]/ul/li' )->length >= ( 1359 === $id ? 3 : 1 ), "$id: approved existing taxonomy relationships render" );
	if ( $preview ) { file_put_contents( $preview . "/terms-$id.html", $html ); }
}
foreach ( array( $seccionales[0], $brands[0] ) as $term ) {
	$q = new WP_Query( array( 'post_type' => 'psi_producto', 'post_status' => 'any', 'posts_per_page' => 6, 'tax_query' => array( array( 'taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => $term->term_id, 'include_children' => false ) ) ) );
	$q->is_tax = true; $q->is_archive = true; $q->is_post_type_archive = false; $q->queried_object = $term; $q->queried_object_id = $term->term_id;
	$html = $render( $q, 'taxonomy-' . $term->taxonomy . '.php' );
    $x = $dom( $html );
    $active = $x->query( '//aside//li[@class="is-active"]/a[@aria-current="page"]' );
    $check( $active->length === 1 && $active->item( 0 )->getAttribute( 'href' ) === get_term_link( $term ), $term->taxonomy . ': exact active sidebar destination uses native term URL' );
    $navigation = \PSIndustrial\Theme\catalog_navigation( true );
    $check( in_array( get_term_link( $term ), array_column( $navigation, 'url' ), true ), $term->taxonomy . ': approved destination automatically available without stored menu changes' );
	$check( $q->post_count > 0 && $dom( $html )->query( '//article[@class="product-card"]' )->length === $q->post_count, $term->taxonomy . ': actual related products use shared cards' );
	foreach ( $q->posts as $item ) { $check( has_term( $term->term_id, $term->taxonomy, $item->ID ), $term->taxonomy . ': product belongs to queried term ' . $item->ID ); }
	if ( $preview ) { file_put_contents( $preview . '/' . $term->taxonomy . '.html', $html ); }
}
remove_filter( 'get_term_metadata', $public, 20 );
foreach ( array( $cats[0], $brands[0] ) as $term ) {
	$r = wp_remote_get( get_term_link( $term ), array( 'redirection' => 0 ) );
	$expected = \PSIndustrial\Core\TermPolicy::is_public( $term->term_id ) ? 200 : 404;
	$check( ! is_wp_error( $r ) && wp_remote_retrieve_response_code( $r ) === $expected, $term->taxonomy . ': public HTTP respects existing approval policy' );
}
$q = new WP_Query( array( 'post_type' => 'psi_producto', 'post_status' => 'any', 'posts_per_page' => 12 ) );
$q->is_post_type_archive = true;
$html = $render( $q, 'archive-psi_producto.php' );
$check( $dom( $html )->query( '//article[@class="product-card"]' )->length === 12, 'Catalog grid with 12 real products (private draft preview)' );
if ( $preview ) { file_put_contents( $preview . '/catalog.html', $html ); }
$response = wp_remote_get( get_post_type_archive_link( 'psi_producto' ) );
$check( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ), 'Public archive HTTP 200' );
$check( str_contains( wp_remote_retrieve_body( $response ), get_permalink( 1371 ) ) && ! str_contains( wp_remote_retrieve_body( $response ), get_permalink( 1360 ) ), 'Public archive includes published product, excludes draft' );
$home_response = wp_remote_get( home_url( '/' ) );
$check( ! is_wp_error( $home_response ) && ! str_contains( wp_remote_retrieve_body( $home_response ), 'assets/css/catalog.css' ), 'Catalog visual system does not alter Home' );
$check( $before === $snapshot(), 'All posts, metadata and taxonomy tables unchanged' );
if ( $preview ) {
	// Same-origin static assets keep self-hosted fonts usable in loopback previews.
	// These files stay outside WordPress and do not publish the draft posts.
	$assets = get_theme_file_path( 'assets' );
	foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $assets, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
		if ( ! $file->isFile() ) { continue; }
		$relative = substr( $file->getPathname(), strlen( $assets ) + 1 );
		$target = $preview . '/theme/assets/' . $relative;
		if ( ! is_dir( dirname( $target ) ) ) { mkdir( dirname( $target ), 0700, true ); }
		copy( $file->getPathname(), $target );
	}
	foreach ( glob( $preview . '/*.html' ) as $file ) {
		file_put_contents( $file, str_replace( get_theme_file_uri( 'assets/' ), '/theme/assets/', file_get_contents( $file ) ) );
	}
}
$failed = array_filter( $checks, static fn( $r ) => ! $r['passed'] );
echo wp_json_encode( array( 'passed' => count( $checks ) - count( $failed ), 'failed' => count( $failed ), 'samples' => $samples, 'checks' => $checks ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
exit( $failed ? 1 : 0 );

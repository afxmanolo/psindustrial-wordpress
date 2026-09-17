<?php
/** Local-only integration smoke suite. Creates and removes its own synthetic fixtures. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || 'psindustrial_wp_dev' !== DB_NAME ) {
	fwrite( STDERR, "This suite requires the dedicated local development database.\n" ); exit( 1 );
}
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
use PSIndustrial\Core\Fields;
use PSIndustrial\Core\ProductEditor;
use PSIndustrial\Core\TermEditor;
use PSIndustrial\Core\Settings;
$posts = $terms = $users = $files = $checks = array();
$failed = false;
$assert = static function( bool $condition, string $name ) use ( &$checks ): void {
	$checks[] = array( 'test' => $name, 'passed' => $condition );
	if ( ! $condition ) { throw new RuntimeException( $name ); }
};
$request = static function( string $method, string $route, array $params = array() ): WP_REST_Response {
	$r = new WP_REST_Request( $method, $route );
	foreach ( $params as $key => $value ) { $r->set_param( $key, $value ); }
	return rest_do_request( $r );
};
$http = static function( string $url, int $status, string $marker = '' ) use ( $assert ): void {
	$r = wp_remote_get( $url, array( 'timeout' => 20, 'redirection' => 0 ) );
	$assert( ! is_wp_error( $r ) && $status === wp_remote_retrieve_response_code( $r ), 'HTTP ' . $status . ' ' . $url );
	if ( $marker ) { $assert( str_contains( wp_remote_retrieve_body( $r ), $marker ), 'Template marker ' . $marker ); }
};
try {
	$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
	wp_set_current_user( $admin->ID );
	$assert( 'psindustrial' === get_stylesheet() && class_exists( Fields::class ), 'Active theme and plugin' );
	$assert( post_type_exists( 'psi_producto' ) && get_taxonomy( 'psi_categoria' )->hierarchical && ! get_taxonomy( 'psi_marca' )->hierarchical, 'CPT and taxonomy contracts' );
	$assert( current_user_can( 'publish_psi_productos' ) && current_user_can( 'psi_manage_brands' ), 'Administrator capabilities' );
	$prefix = 'psi-smoke-' . wp_generate_password( 8, false );
	foreach ( array( 'psi_categoria', 'psi_marca', 'psi_marca', 'psi_categoria' ) as $i => $tax ) {
		$t = wp_insert_term( $prefix . '-' . $i, $tax );
		$assert( ! is_wp_error( $t ), 'Create synthetic term ' . $i );
		$terms[] = array( (int) $t['term_id'], $tax );
	}
	$category = $terms[0][0]; $brand = $terms[1][0]; $brand2 = $terms[2][0];
	$r = $request( 'POST', '/wp/v2/psi_producto', array( 'title' => $prefix, 'content' => 'Contenido sintético de prueba.', 'status' => 'publish', 'psi_categoria' => array( $category ), 'psi_marca' => array( $brand ) ) );
	$assert( 201 === $r->get_status(), 'REST create product and relations' );
	$id = (int) $r->get_data()['id']; $posts[] = $id;
	$fixture_page_id = wp_insert_post( array( 'post_title' => $prefix . '-page', 'post_type' => 'page', 'post_content' => 'Página sintética.', 'post_status' => 'publish' ), true );
	$assert( ! is_wp_error( $fixture_page_id ), 'Create synthetic Page' ); $posts[] = $fixture_page_id;
	$png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6lS8AAAAASUVORK5CYII=' );
	foreach ( array( array( '.png', 'image/png', $png ), array( '.pdf', 'application/pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF" ) ) as $fixture ) {
		$upload = wp_upload_bits( $prefix . $fixture[0], null, $fixture[2] );
		$assert( ! $upload['error'], 'Create synthetic media ' . $fixture[0] ); $files[] = $upload['file'];
		$aid = wp_insert_attachment( array( 'post_title' => $prefix, 'post_mime_type' => $fixture[1], 'post_status' => 'inherit' ), $upload['file'] );
		$posts[] = $aid;
		if ( '.png' === $fixture[0] ) { $image = $aid; } else { $pdf = $aid; }
	}
	$meta = array( '_psi_h1' => 'Encabezado sintético', '_psi_primary_category_id' => $category, '_psi_gallery_ids' => array( $image ), '_psi_datasheets' => array( array( 'attachment_id' => $pdf, 'label' => 'Ficha sintética', 'language' => 'es' ) ), '_psi_videos' => array( array( 'provider' => 'youtube', 'video_id' => 'abcdefghijk', 'title' => 'Video sintético' ) ) );
	$r = $request( 'POST', '/wp/v2/psi_producto/' . $id, array( 'meta' => $meta, 'featured_media' => $image ) );
	$assert( 200 === $r->get_status(), 'REST persist all product fields and featured image' );
	foreach ( $meta as $key => $value ) {
		$stored = get_post_meta( $id, $key, true );
		$assert( $value === ( is_int( $value ) ? (int) $stored : $stored ), 'Round trip ' . $key );
	}
	$r = $request( 'POST', '/wp/v2/psi_marca/' . $brand, array( 'meta' => array( '_psi_logo_id' => $image ) ) );
	$assert( 200 === $r->get_status() && $image === (int) get_term_meta( $brand, '_psi_logo_id', true ), 'Brand logo REST round trip' );
	$r = $request( 'POST', '/wp/v2/psi_marca/' . $brand, array( 'meta' => array( '_psi_logo_id' => $pdf ) ) );
	$assert( 400 === $r->get_status(), 'Reject PDF as logo' );
	$r = $request( 'POST', '/wp/v2/psi_producto/' . $id, array( 'meta' => array( '_psi_datasheets' => array( array( 'attachment_id' => $image, 'label' => 'invalid' ) ) ) ) );
	$assert( 400 === $r->get_status(), 'Reject image as technical PDF' );
	$r = $request( 'POST', '/wp/v2/psi_producto/' . $id, array( 'meta' => array( '_psi_primary_category_id' => $terms[3][0] ) ) );
	$assert( 400 === $r->get_status(), 'Reject unassigned primary category' );
	$r = $request( 'POST', '/wp/v2/psi_producto/' . $id, array( 'psi_marca' => array( $brand, $brand2 ) ) );
	$assert( 400 === $r->get_status(), 'Reject multiple brands via REST' );
	wp_set_object_terms( $id, array( $brand, $brand2 ), 'psi_marca' );
	$assert( array( $brand ) === wp_get_object_terms( $id, 'psi_marca', array( 'fields' => 'ids' ) ), 'Restore original brand on invalid native write' );
	$_POST = array( 'psi_product_nonce' => 'invalid', 'psi_fields' => array( '_psi_h1' => 'bad' ) ); ProductEditor::save( $id );
	$assert( 'Encabezado sintético' === get_post_meta( $id, '_psi_h1', true ), 'Reject invalid metabox nonce' );
	$_POST = array( 'psi_product_nonce' => wp_create_nonce( 'psi_save_product' ), 'psi_fields' => array( '_psi_h1' => '<b>Seguro</b>' ) ); ProductEditor::save( $id ); $_POST = array();
	$assert( 'Seguro' === get_post_meta( $id, '_psi_h1', true ) && array( $image ) === get_post_meta( $id, '_psi_gallery_ids', true ), 'Sanitize metabox and preserve absent fields' );
	$assert( is_wp_error( ProductEditor::parse_videos( 'https://evil.invalid/watch?v=abcdefghijk' ) ), 'Reject unsupported video host' );
	$assert( 1 === count( ProductEditor::parse_videos( 'https://youtu.be/abcdefghijk' ) ), 'Parse approved YouTube URL' );
	$assert( Settings::get() === Settings::sanitize( array( 'contact_email' => 'not-an-email' ) ), 'Reject invalid contact setting without mutation' );
	foreach ( array( 'front-page.php', 'page.php', 'single-psi_producto.php', 'archive-psi_producto.php', 'taxonomy-psi_categoria.php', 'taxonomy-psi_marca.php', '404.php' ) as $template ) {
		$assert( '' !== locate_template( $template ), 'Locate ' . $template );
	}
	$http( home_url( '/' ), 200, 'class="home ' );
	$http( get_permalink( $fixture_page_id ), 200, 'page-id-' . $fixture_page_id );
	$http( get_permalink( $id ), 200, 'single-psi_producto' );
	$http( get_post_type_archive_link( 'psi_producto' ), 200, 'post-type-archive-psi_producto' );
	$http( get_term_link( $category, 'psi_categoria' ), 200, 'tax-psi_categoria' );
	$http( get_term_link( $brand, 'psi_marca' ), 200, 'tax-psi_marca' );
	$http( home_url( '/' . $prefix . '-missing/' ), 404, 'error404' );
	$http( wp_get_attachment_url( $pdf ), 200 );
	$http( home_url( '/.local/debug.log' ), 403 );
	wp_set_current_user( 0 );
	$r = $request( 'POST', '/wp/v2/psi_producto/' . $id, array( 'title' => 'forbidden' ) );
	$assert( in_array( $r->get_status(), array( 401, 403 ), true ), 'Anonymous cannot edit product' );
	$uid = wp_insert_user( array( 'user_login' => $prefix, 'user_pass' => wp_generate_password( 32 ), 'role' => 'subscriber' ) );
	$assert( ! is_wp_error( $uid ), 'Create temporary subscriber' ); $users[] = $uid; wp_set_current_user( $uid );
	$r = $request( 'POST', '/wp/v2/psi_producto/' . $id, array( 'meta' => array( '_psi_h1' => 'forbidden' ) ) );
	$assert( 403 === $r->get_status(), 'Subscriber cannot edit product metadata' );
	$r = $request( 'POST', '/wp/v2/psi_marca/' . $brand, array( 'meta' => array( '_psi_logo_id' => 0 ) ) );
	$assert( 403 === $r->get_status(), 'Subscriber cannot edit brand logo' );
} catch ( Throwable $e ) {
	$failed = true; $checks[] = array( 'failure' => $e->getMessage() );
} finally {
	$_POST = array(); wp_set_current_user( $admin->ID ?? 0 );
	foreach ( array_reverse( $posts ) as $pid ) { 'attachment' === get_post_type( $pid ) ? wp_delete_attachment( $pid, true ) : wp_delete_post( $pid, true ); }
	foreach ( $terms as [ $tid, $tax ] ) { wp_delete_term( $tid, $tax ); }
	foreach ( $users as $uid ) { wp_delete_user( $uid ); }
	foreach ( $files as $file ) { if ( is_file( $file ) ) { wp_delete_file( $file ); } }
	delete_transient( 'psi_field_error_' . ( $admin->ID ?? 0 ) );
}
echo wp_json_encode( array( 'passed' => ! $failed, 'checks' => $checks ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
exit( $failed ? 1 : 0 );

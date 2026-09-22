<?php
/** Read-only institutional previews and form validation tests. No email transport. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || 'psindustrial_wp_dev' !== DB_NAME ) { exit( 1 ); }
use PSIndustrial\Core\Contact;
$checks = array();
$check = static function( bool $ok, string $name ) use ( &$checks ) { $checks[] = array( 'test' => $name, 'passed' => $ok ); };
$snapshot = static function() {
 global $wpdb; $hashes = array();
 foreach ( array( 'posts', 'postmeta', 'terms', 'termmeta', 'term_taxonomy', 'term_relationships', 'options' ) as $table ) {
  $rows = array_map( 'serialize', $wpdb->get_results( "SELECT * FROM {$wpdb->$table}", ARRAY_A ) ); sort( $rows ); $hashes[] = hash( 'sha256', serialize( $rows ) );
 }
 return hash( 'sha256', serialize( $hashes ) );
};
$before = $snapshot(); $sent = 0;
$check( 'publish' === get_post_status( 134 ), 'Authorized Nosotros Page published' );
$check( 'publish' === get_page_by_path( 'contacto' )?->post_status, 'Contacto Page published' );
$check( 'draft' === get_page_by_path( 'politica-privacidad' )?->post_status, 'Privacy remains draft' );
$check( '' === \PSIndustrial\Theme\institutional_privacy_url(), 'Draft privacy has no public navigation URL' );
foreach ( array( 'nosotros', 'contacto' ) as $slug ) {
 $url = get_permalink( get_page_by_path( $slug ) );
 $response = wp_remote_get( $url ); $body = wp_remote_retrieve_body( $response );
 $check( 200 === wp_remote_retrieve_response_code( $response ), $slug . ': real HTTP 200' );
 $check( str_contains( $body, esc_url( get_permalink( 134 ) ) ) && str_contains( $body, esc_url( get_permalink( get_page_by_path( 'contacto' ) ) ) ), $slug . ': native institutional navigation URLs' );
 $check( ! preg_match( '/4771763046|4791071234|wa\.me|api\.whatsapp|Teléfono pendiente/u', $body ), $slug . ': no ambiguous phone, WhatsApp or phone placeholder' );
}
add_filter( 'pre_wp_mail', static function() use ( &$sent ) { ++$sent; return false; } );
$response = wp_remote_post( get_permalink( get_page_by_path( 'contacto' ) ), array( 'body' => array( 'psi_contact_name' => 'Prueba', 'psi_contact_email' => 'example@example.org', 'psi_contact_message' => '', 'psi_contact_website' => '', '_wpnonce' => 'invalid', 'started' => '0', 'signature' => 'invalid' ) ) );
$check( 200 === wp_remote_retrieve_response_code( $response ), 'HTTP POST names do not collide with WordPress routing' );
$check( str_contains( wp_remote_retrieve_body( $response ), 'El formulario caducó' ), 'HTTP invalid nonce yields safe visible feedback before delivery' );
$valid = array( 'name' => 'Prueba', 'email' => 'example@example.org', 'message' => 'Consulta', 'website' => '', '_wpnonce' => wp_create_nonce( 'psi_contact' ), 'started' => (string) ( time() - 5 ), 'signature' => Contact::stamp( time() - 5 ) );
$clean = Contact::validate( $valid );
$check( is_array( $clean ) && $clean['email'] === 'example@example.org', 'Valid legacy name/email/message accepted' );
$check( is_array( Contact::validate( array_replace( $valid, array( 'message' => '' ) ) ) ), 'Message optional as legacy' );
foreach ( array( 'nonce' => array( '_wpnonce' => 'bad' ), 'email' => array( 'email' => 'invalid' ), 'injection' => array( 'email' => "x@example.org\r\nBcc: x@example.org" ), 'name-header' => array( 'name' => "Name\nHeader" ), 'required' => array( 'name' => '' ), 'array' => array( 'email' => array() ), 'honeypot' => array( 'website' => 'spam' ), 'signature' => array( 'signature' => 'fake' ), 'long' => array( 'message' => str_repeat( 'x', 5001 ) ), 'fast' => array( 'started' => (string) time(), 'signature' => Contact::stamp( time() ) ), 'expired' => array( 'started' => (string) ( time() - 4000 ), 'signature' => Contact::stamp( time() - 4000 ) ) ) as $name => $overrides ) {
 $check( is_wp_error( Contact::validate( array_replace( $valid, $overrides ) ) ), 'Reject ' . $name );
}
$sanitized = Contact::validate( array_replace( $valid, array( 'name' => '<b>Name</b>', 'message' => '<b>Message</b>' ) ) );
$check( $sanitized['name'] === 'Name' && $sanitized['message'] === 'Message', 'Plain-text sanitization' );
$check( is_wp_error( Contact::deliver( $clean ) ) && 0 === $sent, 'Local delivery disabled before wp_mail; zero actual mail' );
// Core's bundled in-process cache isolates rate counters from the database.
$old_cache = wp_using_ext_object_cache(); wp_using_ext_object_cache( true );
for ( $i = 0; $i < 5; ++$i ) { $check( Contact::allow_attempt( 'institutional-test-only' ), 'Rate limit allows attempt ' . ( $i + 1 ) ); }
$check( ! Contact::allow_attempt( 'institutional-test-only' ), 'Rate limit blocks sixth attempt' );
wp_using_ext_object_cache( $old_cache );
show_admin_bar( false );
add_action( 'get_header', static function() { if ( did_action( 'get_header' ) > 1 ) { include get_theme_file_path( 'header.php' ); } } );
add_action( 'get_footer', static function() { if ( did_action( 'get_footer' ) > 1 ) { include get_theme_file_path( 'footer.php' ); } } );
$preview = $argv[1] ?? ''; if ( $preview && ! is_dir( $preview ) ) { mkdir( $preview, 0700, true ); }
foreach ( array( 'nosotros' => 'Nosotros', 'contacto' => 'Contacto', 'politica-privacidad' => 'Política de privacidad' ) as $slug => $title ) {
 $page = get_page_by_path( $slug );
 // Missing Pages represented only in process for template previews, never persisted.
 $post = $page ?: new WP_Post( (object) array( 'ID' => 0, 'post_type' => 'page', 'post_name' => $slug, 'post_title' => $title, 'post_status' => 'draft', 'post_content' => '', 'post_parent' => 0, 'post_author' => 0, 'post_date' => '2026-09-21 00:00:00', 'filter' => 'raw' ) );
 $q = new WP_Query(); $q->is_page = true; $q->is_singular = true; $q->queried_object = $post; $q->queried_object_id = $post->ID; $q->posts = array( $post ); $q->post_count = 1;
 $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = $q; $GLOBALS['post'] = $post; setup_postdata( $post );
 wp_styles()->done = array(); wp_scripts()->done = array();
 ob_start(); include get_theme_file_path( 'page-' . $slug . '.php' ); $html = ob_get_clean();
 $d = new DOMDocument(); $old = libxml_use_internal_errors( true ); $d->loadHTML( '<?xml encoding="UTF-8">' . $html ); libxml_clear_errors(); libxml_use_internal_errors( $old ); $x = new DOMXPath( $d );
 $check( 1 === $x->query( '//h1' )->length && 1 === $x->query( '//main' )->length, $slug . ': unique H1 and main' );
 $check( str_contains( $html, 'institutional.css' ) && str_contains( $html, 'psi-has-hero' ), $slug . ': scoped styles and integrated header' );
 $check( 0 === $x->query( '//a[@href="#" or @href=""]' )->length, $slug . ': no placeholder anchors' );
 $check( ! str_contains( $html, 'Warning:' ) && ! str_contains( $html, 'Fatal error:' ), $slug . ': no PHP diagnostics' );
 $check( 1 === $x->query( '//nav[@id="site-navigation"]' )->length && 1 === $x->query( '//footer' )->length, $slug . ': shared global navigation/footer' );
 if ( 'contacto' === $slug ) {
  $check( 0 === $x->query( '//form//input[@name="name"]' )->length && 1 === $x->query( '//input[@name="psi_contact_name"]' )->length, 'Form uses prefixed names, never WordPress query vars' );
  $check( 1 === $x->query( '//input[@name="_wpnonce"]' )->length && 1 === $x->query( '//input[@name="signature"]' )->length, 'Form nonce and signed timing token' );
  $check( 3 === $x->query( '//label[@for="psi-name" or @for="psi-email" or @for="psi-message"]' )->length, 'Accessible field labels' );
  $check( 1 === $x->query( '//address' )->length && 0 === $x->query( '//iframe' )->length, 'Verified address; no invented map or third-party embed' );
  $check( 2 === $x->query( '//main//a[starts-with(@href,"mailto:")]' )->length, 'Two source-confirmed contact emails' );
  $check( 0 === $x->query( '//main//a[starts-with(@href,"tel:")]' )->length, 'Contradictory phone not selected automatically' );
 }
 if ( 'politica-privacidad' === $slug ) { $check( str_contains( $html, 'LEGAL_CONTENT_PENDING' ) && ! str_contains( $html, 'NOMBRE EMPRESA' ) && ! str_contains( $html, 'dermafest' ), 'Legal gaps flagged, no fabricated or unrelated policy' ); }
 if ( 'nosotros' === $slug ) { $check( ! str_contains( $html, '8k+' ) && ! str_contains( $html, '9k+' ), 'Commented-out metrics not resurrected from imported markup' ); }
 if ( $preview ) { file_put_contents( $preview . '/' . $slug . '.html', $html ); }
}
$check( $sent === 0, 'No real or mocked mail calls during tests' );
$check( get_post_status( 1371 ) === 'publish', 'Protected product remains published' );
$check( $before === $snapshot(), 'All content, taxonomies and options unchanged' );
if ( $preview ) {
 $source = get_theme_file_path( 'assets' );
 foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ) ) as $file ) { if ( ! $file->isFile() ) { continue; } $target = $preview . '/theme/assets/' . substr( $file->getPathname(), strlen( $source ) + 1 ); if ( ! is_dir( dirname( $target ) ) ) { mkdir( dirname( $target ), 0700, true ); } copy( $file->getPathname(), $target ); }
 foreach ( glob( $preview . '/*.html' ) as $file ) { file_put_contents( $file, str_replace( get_theme_file_uri( 'assets/' ), '/theme/assets/', file_get_contents( $file ) ) ); }
}
$failed = array_filter( $checks, static fn( $c ) => ! $c['passed'] );
echo wp_json_encode( array( 'passed' => count( $checks ) - count( $failed ), 'failed' => count( $failed ), 'checks' => $checks ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
exit( $failed ? 1 : 0 );

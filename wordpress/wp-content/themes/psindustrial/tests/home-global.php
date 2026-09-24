<?php
/** Read-only integration checks for the global shell and historical Home. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || 'psindustrial_wp_dev' !== DB_NAME ) { exit( 1 ); }
$checks = array();
$check = static function( bool $ok, string $name ) use ( &$checks ) { $checks[] = array( 'test' => $name, 'passed' => $ok ); };
$snapshot = static function() {
 global $wpdb; $tables = array();
 foreach ( array( 'posts', 'postmeta', 'terms', 'termmeta', 'term_taxonomy', 'term_relationships' ) as $table ) {
  $rows = array_map( 'serialize', $wpdb->get_results( "SELECT * FROM {$wpdb->$table}", ARRAY_A ) ); sort( $rows ); $tables[] = hash( 'sha256', serialize( $rows ) );
 }
 return hash( 'sha256', serialize( $tables ) );
};
$before = $snapshot();
$response = wp_remote_get( home_url( '/' ) );
$check( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ), 'Home HTTP 200' );
$html = wp_remote_retrieve_body( $response );
$document = new DOMDocument(); $old = libxml_use_internal_errors( true ); $document->loadHTML( '<?xml encoding="UTF-8">' . $html ); libxml_clear_errors(); libxml_use_internal_errors( $old ); $x = new DOMXPath( $document );
foreach ( array( 'header', 'main', 'footer', 'h1' ) as $tag ) { $check( 1 === $x->query( '//' . $tag )->length, 'Unique landmark/heading: ' . $tag ); }
$check( 4 === $x->query( '//div[@class="home-slide"]' )->length, 'Four verified legacy slides' );
$check( 4 === $x->query( '//button[@class="home-slide-dot"]' )->length, 'Native slider controls available for enhancement' );
$check( 1 === $x->query( '//button[@class="home-slider-pause"]' )->length, 'Pause control' );
// Brand logos became real psi_marca term links (2026-09-23, dynamic-brands, already approved/
// merged to develop/validated on staging): li > a > img, not the old bare li > img this file
// originally checked. Verifies something stronger than "some <a> now exists": every logo is
// still present, each is wrapped in exactly one link, and that link is specifically a real,
// resolvable psi_marca term's own get_term_link() -- never a placeholder, never some other
// destination.
$check( 12 === $x->query( '//ul[@class="home-brand-grid"]/li/a/img' )->length, 'Twelve historical brand logos are still present' );
$brandGridAnchors = $x->query( '//ul[@class="home-brand-grid"]/li/a' );
$check( 12 === $brandGridAnchors->length, 'Each logo is wrapped in exactly one link' );
$allBrandLinksValid = true;
foreach ( $brandGridAnchors as $anchor ) {
	$href = $anchor->getAttribute( 'href' );
	$slug = $href ? basename( untrailingslashit( (string) wp_parse_url( $href, PHP_URL_PATH ) ) ) : '';
	$term = $slug ? get_term_by( 'slug', $slug, 'psi_marca' ) : false;
	if ( ! $term || untrailingslashit( get_term_link( $term ) ) !== untrailingslashit( $href ) ) { $allBrandLinksValid = false; }
}
$check( $allBrandLinksValid, 'Every brand logo href is a real, resolvable psi_marca term\'s own get_term_link()' );
$check( 0 === $x->query( '//ul[@class="home-brand-grid"]//a[@href="#" or @href=""]' )->length, 'No placeholder href="#" among brand logo links' );
$check( 7 === $x->query( '//section[contains(concat(" ",@class," ")," home-family ")]' )->length, 'Seven legacy commercial families' );
$check( 3 === $x->query( '//section[contains(@class,"is-reversed")]' )->length, 'Alternating family composition preserved' );
$check( str_contains( $html, 'Conoce nuestros Productos' ) && str_contains( $html, 'Acerca de nosotros' ), 'Verified Home content, no invented marketing sections' );
// Header dropdown (.sub-menu), footer link list (.catalog-footer-links) and .home-family
// "+ label" lines/titles all render real psi_categoria term names since earlier this project
// (dynamic-categories-menu, then this session's Home category sections). Between those two
// sessions the real terms briefly carried known mojibake encoding artifacts (e.g. "Puertas
// rÃ¡pidas"), which needed this check scoped away from those three areas for a time --
// MojibakeContentMigration (2026-09-24) has since corrected every one of them at the source,
// so the check applies to the WHOLE page again, no exclusion needed. If new taxonomy content
// is ever imported with the same historical corruption, this check will correctly catch it.
$check( ! str_contains( $html, 'Ã' ) && ! str_contains( $html, "\xef\xbf\xbd" ), 'No mojibake anywhere on the historical Home page' );
$check( 0 === $x->query( '//a[@href="#" or @href=""]' )->length, 'No empty or placeholder URLs' );
$check( 1 === $x->query( '//img[@fetchpriority="high" and @loading="eager"]' )->length, 'Only first hero is LCP priority, never lazy' );
$check( 0 === $x->query( '//img[not(@width) or not(@height) or not(@alt)]' )->length, 'All images have dimensions and alt attributes' );
$check( 24 === $x->query( '//main//img[@srcset and @sizes]' )->length, 'Home artwork has responsive source metadata' );
$assets_ok = true;
foreach ( $x->query( '//img' ) as $image ) {
 $relative = str_replace( get_theme_file_uri() . '/', '', $image->getAttribute( 'src' ) );
 if ( ! is_file( get_theme_file_path( $relative ) ) ) { $assets_ok = false; }
}
$check( $assets_ok, 'All rendered theme images exist' );
foreach ( $x->query( '//a[@class="home-hero-cta"]' ) as $link ) { $check( $link->getAttribute( 'href' ) === get_post_type_archive_link( 'psi_producto' ), 'Hero CTA uses real WP archive' ); }
$check( str_contains( $html, 'catalog-site-header' ) && str_contains( $html, 'catalog-site-footer' ), 'Approved shell reused globally' );
$check( str_contains( $html, '¡Platícanos de tu proyecto!' ), 'Red footer CTA retained' );
$check( str_contains( $html, 'assets/css/global.css' ) && str_contains( $html, 'assets/css/home.css' ) && ! str_contains( $html, 'assets/css/catalog.css' ), 'Home loads shared + Home CSS only' );
$archive = wp_remote_retrieve_body( wp_remote_get( get_post_type_archive_link( 'psi_producto' ) ) );
$check( str_contains( $archive, 'assets/css/global.css' ) && str_contains( $archive, 'assets/css/catalog.css' ) && ! str_contains( $archive, 'assets/js/home.js' ), 'Catalog does not load slider or Home styles' );
$check( ! str_contains( $html, 'vendors.min.js' ) && ! str_contains( $html, 'swiper.js' ) && ! str_contains( $html, 'recaptcha/api.js' ), 'No legacy framework or tracking dependencies copied' );
// Render the existing draft Page in memory; never publish or mutate it.
$page = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => 1, 'post__not_in' => array_filter( array_map( static fn( $slug ) => get_page_by_path( $slug )?->ID, array( 'nosotros', 'contacto', 'politica-privacidad' ) ) ) ) )[0];
$q = new WP_Query( array( 'page_id' => $page->ID, 'post_status' => 'any' ) );
$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = $q;
ob_start(); include get_theme_file_path( 'page.php' ); $page_html = ob_get_clean();
$check( str_contains( $page_html, 'catalog-site-header' ) && str_contains( $page_html, 'catalog-site-footer' ), 'Existing Page uses global shell without rebuilding its content' );
$check( str_contains( $page_html, 'psi-site' ) && ! str_contains( $page_html, 'psi-has-hero' ), 'Non-hero Page has readable standalone header' );
$check( ! str_contains( $html . $page_html, 'Warning:' ) && ! str_contains( $html . $page_html, 'Fatal error:' ), 'No PHP diagnostics' );
$check( 'publish' === get_post_status( 1371 ), 'Human publication untouched' );
$check( $before === $snapshot(), 'All content and taxonomy tables unchanged' );
$failed = array_filter( $checks, static fn( $c ) => ! $c['passed'] );
echo wp_json_encode( array( 'passed' => count( $checks ) - count( $failed ), 'failed' => count( $failed ), 'checks' => $checks ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
exit( $failed ? 1 : 0 );

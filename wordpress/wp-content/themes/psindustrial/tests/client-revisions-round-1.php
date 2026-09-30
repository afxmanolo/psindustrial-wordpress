<?php
/** Local regression suite. Only the two authorized editorial migrations persist changes. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\{Settings,HomeOrderMigration,InstitutionalContentMigration};
use PSIndustrial\Core\Migration\{Identity,Storage};
if ( wp_get_environment_type() !== 'local' || DB_NAME !== 'psindustrial_wp_dev' ) { exit( 1 ); }
wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
$checks = array();
$check = static function( bool $ok, string $label ) use ( &$checks ) { $checks[] = array( 'test' => $label, 'passed' => $ok ); };
$nav = \PSIndustrial\Theme\catalog_navigation(); $before = array();
foreach ( HomeOrderMigration::ORDER as $legacy ) {
 $e = array( 'entity_key' => 'category:' . $legacy, 'target_type' => 'psi_categoria' ); $id = Identity::find( $e );
 $state = get_term_meta( $id, '_psi_import_state', true );
 $e['source_hash'] = $state['source_hash']; $e['decision_hash'] = $state['decision_hash'];
 $before[] = array( $e, $id, Storage::hash( Identity::snapshot( $e, $id ) ), Identity::prediction( $e ), get_term_meta( $id, '_psi_public_state', true ), get_term_meta( $id, '_psi_category_menu_order', true ) );
}
HomeOrderMigration::run();
foreach ( $before as [ $e, $id, $hash, $prediction, $visibility, $menu ] ) {
 $check( $hash === Storage::hash( Identity::snapshot( $e, $id ) ), $e['entity_key'] . ' Home migration preserves identity hash' );
 $check( $prediction === Identity::prediction( $e ), $e['entity_key'] . ' no new CONFLICT' );
 $check( $visibility === get_term_meta( $id, '_psi_public_state', true ) && $menu === get_term_meta( $id, '_psi_category_menu_order', true ), $e['entity_key'] . ' no publication or navigation-order changes' );
}
$report = HomeOrderMigration::run(); $check( $report === HomeOrderMigration::run(), 'Home migration idempotent' );
$check( $nav === \PSIndustrial\Theme\catalog_navigation(), 'Header/footer/sidebar common navigation unchanged' );
$sections = \PSIndustrial\Theme\home_category_sections();
$check( array_column( $sections, 'legacy_id' ) === HomeOrderMigration::ORDER, 'Home exact order 1,3,4,5,6,2,7' );
$check( 3 === count( array_filter( array_column( $sections, 'reverse' ) ) ), 'Exactly three reverse sections' );
foreach ( $sections as $i => $s ) { $check( $s['reverse'] === ( $i % 2 === 1 ) && $s['pattern'] === ( $i % 2 === 1 || $i === 6 ) && $s['narrow'] === in_array( $s['legacy_id'], array( 4,6 ), true ), 'Position alternation and identity-bound narrow: ' . $i ); }
// Exercise the real snapshot code with metadata cache overrides only; no baseline is written.
[ $e, $id ] = $before[0]; $meta = get_term_meta( $id ); $hash = Storage::hash( Identity::snapshot( $e, $id ) );
try {
 foreach ( array( '_psi_category_home_order', '_psi_category_menu_order' ) as $key ) {
  $changed = $meta; $changed[ $key ] = array( '999' ); wp_cache_set( $id, $changed, 'term_meta' );
  $check( $hash === Storage::hash( Identity::snapshot( $e, $id ) ), $key . ' changes excluded from identity' );
  $check( Identity::prediction( $e ) === $before[0][3], $key . ' prediction unchanged after changing value' );
 }
 $changed = $meta; $changed['_psi_source_hash'] = array( str_repeat( 'a', 64 ) ); wp_cache_set( $id, $changed, 'term_meta' );
 $check( $hash !== Storage::hash( Identity::snapshot( $e, $id ) ), 'Real identity metadata still changes hash' );
 $check( 'CONFLICT' === Identity::prediction( $e ), 'Real identity change is still a CONFLICT' );
} finally { wp_cache_set( $id, $meta, 'term_meta' ); }
$recipient = Settings::get()['mail_recipient']; InstitutionalContentMigration::public_emails();
$check( $recipient === Settings::get()['mail_recipient'], 'Internal recipient untouched' );
$values = Settings::get(); InstitutionalContentMigration::public_emails();
$check( $values === Settings::get(), 'Email migration idempotent' );
foreach ( array( '', 'contacto@contacto.com', 'administracion@puertasyserviciosindustriales.com' ) as $old ) {
 $v = InstitutionalContentMigration::public_email_values( array( 'contact_email' => $old, 'mail_recipient' => 'private@example.org' ) );
 $check( $v['contact_email'] === 'overheaddoor@hotmail.com' && $v['contact_email_secondary'] === 'vicenteaguilarleon@gmail.com' && $v['mail_recipient'] === 'private@example.org', 'Known previous email value migrated: ' . $old );
}
$custom = array( 'contact_email' => 'owner@example.org', 'contact_email_secondary' => 'other@example.org', 'mail_recipient' => 'private@example.org' );
$check( $custom === InstitutionalContentMigration::public_email_values( $custom ), 'Administrator custom emails preserved' );
$fixture = static fn() => array( 'contact_email' => 'First@example.org', 'contact_email_secondary' => 'first@example.org' );
add_filter( 'pre_option_psi_site_settings', $fixture ); $check( Settings::public_emails() === array( 'First@example.org' ), 'Public emails deduplicate case-insensitively' ); remove_filter( 'pre_option_psi_site_settings', $fixture );
$fixture = static fn() => array( 'contact_email' => 'invalid', 'contact_email_secondary' => '' );
add_filter( 'pre_option_psi_site_settings', $fixture ); $check( Settings::public_emails() === array(), 'Invalid and empty public emails omitted' ); remove_filter( 'pre_option_psi_site_settings', $fixture );
$fixture = static fn() => array( 'contact_email' => 'first@example.org', 'contact_email_secondary' => 'second@example.org' );
add_filter( 'pre_option_psi_site_settings', $fixture ); $check( Settings::public_emails() === array( 'first@example.org', 'second@example.org' ), 'Public email order is primary then secondary' ); remove_filter( 'pre_option_psi_site_settings', $fixture );
$check( count( Settings::public_emails() ) === 2, 'Both public emails available in order' );
$brands = get_terms( array( 'taxonomy' => 'psi_marca', 'hide_empty' => false ) );
$brand = current( array_filter( $brands, static fn( $t ) => (int) get_term_meta( $t->term_id, '_psi_logo_id', true ) > 0 ) );
$logo = (int) get_term_meta( $brand->term_id, '_psi_logo_id', true );
$image = wp_get_attachment_image_src( $logo, 'medium' );
$check( str_contains( \PSIndustrial\Theme\brand_logo( $brand ), esc_url( $image[0] ) ) && ! str_contains( \PSIndustrial\Theme\brand_logo( $brand ), 'size-thumbnail' ), 'Shared brand helper uses uncropped medium image' );
$product = get_posts( array( 'post_type' => 'psi_producto', 'post_status' => 'publish', 'numberposts' => 1 ) )[0];
$render = static function() use ( $product ) { ob_start(); get_template_part( 'template-parts/components/product-card', null, array( 'product_id' => $product->ID ) ); return ob_get_clean(); };
$terms = static fn( $existing, $id, $tax ) => $tax === 'psi_marca' ? array( $brand ) : $existing;
add_filter( 'get_the_terms', $terms, 99, 3 );
$check( str_contains( $render(), 'product-brand-logo' ) && str_contains( $render(), esc_html( $brand->name ) ), 'Card brand with logo and name' );
$check( ! str_contains( $render(), 'product-card-categories' ), 'Brand line replaces category line' );
$old_query = $GLOBALS['wp_query']; $q = new WP_Query(); $q->is_tax = true; $q->queried_object = $brand; $q->queried_object_id = $brand->term_id; $GLOBALS['wp_query'] = $q;
$check( str_contains( $render(), esc_html( $brand->name ) ), 'Card brand retained on its own archive' ); $GLOBALS['wp_query'] = $old_query;
$no_logo = static fn( $v, $id, $key ) => $key === '_psi_logo_id' ? 0 : $v;
add_filter( 'get_term_metadata', $no_logo, 99, 3 ); $html = $render();
$check( ! str_contains( $html, 'product-brand-logo' ) && str_contains( $html, esc_html( $brand->name ) ), 'Brand without logo renders only name' ); remove_filter( 'get_term_metadata', $no_logo, 99 ); remove_filter( 'get_the_terms', $terms, 99 );
$no_brand = static fn( $existing, $id, $tax ) => $tax === 'psi_marca' ? array() : $existing;
add_filter( 'get_the_terms', $no_brand, 99, 3 ); $check( ! str_contains( $render(), 'product-card-brand' ), 'No brand produces no placeholder line' ); remove_filter( 'get_the_terms', $no_brand, 99 );
$response = wp_remote_get( home_url( '/' ) ); $html = wp_remote_retrieve_body( $response );
$dom = new DOMDocument(); @$dom->loadHTML( $html ); $x = new DOMXPath( $dom );
$check( 1 === $x->query( '//header//a[contains(@class,"catalog-logo")]/img[contains(@src,"logo-black-big")]' )->length && 0 === $x->query( '//header//img[contains(@src,"logo-white")]' )->length, 'One color header logo, no white header asset' );
$check( 1 === $x->query( '//footer//img[contains(@src,"logo-white")]' )->length, 'Footer white logo preserved' );
$check( 2 === $x->query( '//div[@class="catalog-footer-cta"]//a[starts-with(@href,"mailto:")]' )->length && 1 === $x->query( '//div[@class="catalog-footer-cta"]//i[contains(@class,"icon-mail")]' )->length, 'CTA two email links and local mail icon' );
$check( 4 === $x->query( '//footer//a[starts-with(@href,"mailto:")]' )->length, 'Both emails in CTA and footer contacts' );
$contact = file_get_contents( get_theme_file_path( 'page-contacto.php' ) );
$check( str_contains( $contact, 'Settings::public_emails()' ) && ! str_contains( $contact, 'overheaddoor@' ) && ! str_contains( $contact, 'vicenteaguilarleon@' ), 'Contacto has no literal public emails' );
$check( str_contains( file_get_contents( WP_PLUGIN_DIR . '/psindustrial-core/includes/Contact.php' ), "Settings::get()['mail_recipient']" ), 'Form still delivers to private recipient' );
$check( 200 === wp_remote_retrieve_response_code( wp_remote_get( rest_url( 'wp/v2/psi_categoria' ) ) ), 'Category REST remains functional' );
$failed = array_filter( $checks, static fn( $c ) => ! $c['passed'] );
echo wp_json_encode( array( 'passed' => count( $checks ) - count( $failed ), 'failed' => count( $failed ), 'checks' => $checks ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
exit( $failed ? 1 : 0 );

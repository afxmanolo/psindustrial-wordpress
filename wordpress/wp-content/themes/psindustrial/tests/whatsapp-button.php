<?php
/** Tests for the global floating WhatsApp button (inc/whatsapp.php + Settings::whatsapp_url()).
 * Reversibly toggles this environment's real psi_site_settings (save/restore in finally,
 * same pattern already used by legacy-url-redirects.php/institutional-content-migration.php)
 * -- never a second, hand-simulated data source. Real HTTP requests against real rendered
 * pages throughout, never a hand-built HTML fixture. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/template.php'; // add_settings_error(), called by Settings::sanitize() on its own validation-failure paths (deliberately exercised below), same as any real wp-admin request would already have loaded.
use PSIndustrial\Core\Settings;
(static function(): void {
	$checks = array();
	$assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
	$export = static function( string $name, array $data ): void { file_put_contents( dirname( __DIR__, 5 ) . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

	$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0] ?? null;
	$realSettings = Settings::get();

	$fetch = static function( string $path ): string {
		$r = wp_remote_get( home_url( $path ), array( 'timeout' => 10 ) );
		return is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r );
	};
	$hasButton = static function( string $html ): bool { return (bool) preg_match( '#<a class="psi-whatsapp-button"[^>]*>#', $html, $m ) ? true : false; };
	$buttonTag = static function( string $html ): string { preg_match( '#<a class="psi-whatsapp-button"[^>]*>#', $html, $m ); return $m[0] ?? ''; };

	try {
		wp_set_current_user( $admin->ID );
		global $wpdb;
		$before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );

		// ============================================================ 2/13/14/15: disabled (this environment's real, current state) -- absent everywhere, nothing else broken
		$assert( false === (bool) $realSettings['whatsapp_enabled'], 'Fixture: whatsapp_enabled is genuinely disabled on this environment right now' );
		$assert( '' === Settings::whatsapp_url(), 'whatsapp_url() is empty while disabled' );
		foreach ( array( '/' => 'Home', '/nosotros/' => 'Nosotros', '/contacto/' => 'Contacto', '/soluciones/' => 'Soluciones', '/marcas/' => 'Marcas' ) as $path => $label ) {
			$html = $fetch( $path );
			$assert( '' !== $html, "Fixture: $label fetched successfully" );
			$assert( ! $hasButton( $html ), "Disabled: no button on $label (item 2)" );
			$assert( str_contains( $html, 'catalog-site-footer' ) || str_contains( $html, 'catalog-footer' ), "Disabled: $label footer still renders normally, not broken by this feature (items 13/14/15)" );
		}
		$contactoHtml = $fetch( '/contacto/' );
		$assert( str_contains( $contactoHtml, 'psi_contact_name' ) && str_contains( $contactoHtml, 'psi_contact_email' ), 'Disabled: Contacto form fields still present, unaffected (item 14)' );
		$homeHtml = $fetch( '/' );
		$assert( str_contains( $homeHtml, 'home-slider' ) && str_contains( $homeHtml, 'home-brand-grid' ), 'Disabled: Home slider/brands still present, unaffected (item 15)' );

		// ============================================================ 3: enabled + empty number -> absent
		// Settings::sanitize() itself already refuses to ever SAVE this combination (its own
		// line "if ($clean['whatsapp_enabled'] && '' === $clean['whatsapp_number'])" rejects
		// it, matching the admin form's own "Indique un número antes de activar WhatsApp"
		// message) -- a real, independent guard, not something this feature needs to
		// duplicate. This test instead proves defense in depth: even if that combination
		// somehow reached the option some other way (bypassing sanitize(), exactly like the
		// normalization proof below does), the render-side check in whatsapp_url() still
		// independently refuses to build a link with nothing after wa.me/.
		try {
			update_option( 'psi_site_settings', array_merge( $realSettings, array( 'whatsapp_enabled' => true, 'whatsapp_number' => '' ) ) );
			$assert( true === Settings::get()['whatsapp_enabled'] && '' === Settings::get()['whatsapp_number'], 'Fixture: enabled with empty number actually stored this way' );
			$assert( '' === Settings::whatsapp_url(), 'Enabled but empty number: whatsapp_url() still empty (item 3)' );
			$assert( ! $hasButton( $fetch( '/' ) ), 'Enabled but empty number: no button rendered on Home (item 3)' );
		} finally {
			update_option( 'psi_site_settings', Settings::sanitize( $realSettings ) );
		}

		// ============================================================ 1/6/7/8/9/12: enabled + valid number -> visible everywhere, correct link/attributes
		try {
			update_option( 'psi_site_settings', Settings::sanitize( array_merge( $realSettings, array( 'whatsapp_enabled' => true, 'whatsapp_number' => '+524771234567', 'whatsapp_message' => 'Hola, quiero información' ) ) ) );
			$expectedHref = 'https://wa.me/524771234567?text=' . rawurlencode( 'Hola, quiero información' );
			$assert( Settings::whatsapp_url() === $expectedHref, 'whatsapp_url() builds the exact expected wa.me link with encoded message (items 5/6)' );

			$sampleProduct = get_posts( array( 'post_type' => 'psi_producto', 'post_status' => 'publish', 'numberposts' => 1 ) )[0] ?? null;
			$sampleBrand = get_terms( array( 'taxonomy' => 'psi_marca', 'hide_empty' => false, 'number' => 1 ) )[0] ?? null;
			$targets = array(
				'/' => 'Home', '/nosotros/' => 'Nosotros', '/contacto/' => 'Contacto', '/soluciones/' => 'Soluciones', '/marcas/' => 'Marcas',
				'/categoria/industrial/' => 'Category archive',
			);
			if ( $sampleBrand ) { $targets[ untrailingslashit( (string) wp_parse_url( get_term_link( $sampleBrand ), PHP_URL_PATH ) ) . '/' ] = 'Brand archive'; }
			if ( $sampleProduct ) { $targets[ untrailingslashit( (string) wp_parse_url( get_permalink( $sampleProduct ), PHP_URL_PATH ) ) . '/' ] = 'Single product'; }
			$targets[ untrailingslashit( (string) wp_parse_url( get_post_type_archive_link( 'psi_producto' ), PHP_URL_PATH ) ) . '/' ] = 'Product archive';

			$assert( count( $targets ) >= 9, 'Fixture: at least the 9 page types named in the task are covered (item 12)' );
			foreach ( $targets as $path => $label ) {
				$html = $fetch( $path );
				$assert( '' !== $html, "Fixture: $label ($path) fetched successfully" );
				$tag = $buttonTag( $html );
				$assert( '' !== $tag, "Enabled: button renders globally on $label (item 12)" );
				$assert( str_contains( $tag, 'href="' . esc_url( $expectedHref ) . '"' ), "Enabled: correct href on $label" );
				$assert( str_contains( $tag, 'target="_blank"' ), "target=_blank present on $label (item 7)" );
				$assert( str_contains( $tag, 'rel="noopener noreferrer"' ), "rel=noopener noreferrer present on $label (item 8)" );
				$assert( str_contains( $tag, 'aria-label="Contactar por WhatsApp"' ), "aria-label present on $label (item 9)" );
			}
		} finally {
			update_option( 'psi_site_settings', Settings::sanitize( $realSettings ) );
		}

		// ============================================================ 4: normalization strips spaces/+/dashes/parentheses/anything non-numeric
		// Deliberately bypasses Settings::sanitize()'s own strict save-time format (a direct
		// update_option(), exactly the "value reached the option some other way" case
		// whatsapp_url()'s own docblock says it defends against) -- proves the URL builder
		// itself normalizes robustly, never assuming the stored value is already clean.
		try {
			update_option( 'psi_site_settings', array_merge( $realSettings, array( 'whatsapp_enabled' => true, 'whatsapp_number' => '+52 477 123-4567', 'whatsapp_message' => '' ) ) );
			$assert( 'https://wa.me/524771234567' === Settings::whatsapp_url(), 'Loosely-formatted "+52 477 123-4567" normalizes to 524771234567 (item 4)' );
			update_option( 'psi_site_settings', array_merge( $realSettings, array( 'whatsapp_enabled' => true, 'whatsapp_number' => '(52) 477-123-4567', 'whatsapp_message' => '' ) ) );
			$assert( 'https://wa.me/524771234567' === Settings::whatsapp_url(), 'Parentheses/dashes also stripped: "(52) 477-123-4567" -> 524771234567 (item 4)' );
			update_option( 'psi_site_settings', array_merge( $realSettings, array( 'whatsapp_enabled' => true, 'whatsapp_number' => '', 'whatsapp_message' => '' ) ) );
			$assert( '' === Settings::whatsapp_url(), 'Fully non-numeric-stripped-to-empty number still yields no link, never wa.me/ with nothing after it' );
		} finally {
			update_option( 'psi_site_settings', Settings::sanitize( $realSettings ) );
		}

		// ============================================================ 5: message absent -> no ?text= at all (not ?text= empty)
		try {
			update_option( 'psi_site_settings', Settings::sanitize( array_merge( $realSettings, array( 'whatsapp_enabled' => true, 'whatsapp_number' => '+524771234567', 'whatsapp_message' => '' ) ) ) );
			$assert( 'https://wa.me/524771234567' === Settings::whatsapp_url(), 'No message configured: plain wa.me link, no trailing ?text=' );
		} finally {
			update_option( 'psi_site_settings', Settings::sanitize( $realSettings ) );
		}

		// ============================================================ 10/11: structural -- no hardcoded number/message, single Settings source
		$whatsappSource = file_get_contents( __DIR__ . '/../inc/whatsapp.php' );
		$assert( ! preg_match( '/\+?52\d{9,10}/', $whatsappSource ), 'Structural: inc/whatsapp.php contains no hardcoded phone number (item 10)' );
		$assert( str_contains( $whatsappSource, 'Settings::whatsapp_url()' ), 'Structural: the button is built exclusively from Settings::whatsapp_url() (item 11)' );
		$assert( ! str_contains( $whatsappSource, 'get_option(' ), 'Structural: no second, direct read of psi_site_settings bypassing Settings (item 11)' );
		foreach ( glob( __DIR__ . '/../*.php' ) as $templateFile ) {
			$assert( ! str_contains( file_get_contents( $templateFile ), 'psi-whatsapp-button' ), 'Structural: ' . basename( $templateFile ) . ' does not duplicate the button markup (single wp_footer hook, never copied into templates)' );
		}

		$assert( Settings::get() === $realSettings, 'Real settings end this suite exactly as they started it' );
		$after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
		$assert( $before === $after, 'No posts/terms created or removed by this suite' );

		$export( 'whatsapp-button-tests.json', array( 'passed' => true, 'checks' => $checks ) );
		echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
	} catch ( Throwable $error ) {
		if ( isset( $realSettings ) ) { update_option( 'psi_site_settings', Settings::sanitize( $realSettings ) ); }
		$export( 'whatsapp-button-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) );
		fwrite( STDERR, $error->getMessage() . "\n" );
		exit( 1 );
	}
})();

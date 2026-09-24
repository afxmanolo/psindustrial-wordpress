<?php
/** Tests for InstitutionalContentMigration -- the portable replacement for the ad hoc local
 * scripts that originally created the Soluciones/Marcas Pages (hardcoded post IDs 2048/2049)
 * and corrected psi_site_settings (feature/legacy-contact-solutions, 2026-09-24). Proves the
 * migration creates-if-missing, never duplicates, never depends on a specific post ID, never
 * silently overwrites a legitimate existing setting, correctly retries the one
 * capability-gated field, and is idempotent end to end -- all against real WordPress objects
 * (a disposable fixture page for the create/reuse proof, a temporary psi_gestor user for the
 * capability proof, this environment's real psi_site_settings reversibly toggled for the
 * placeholder-migration proofs), never a hand-rolled simulation of any of it. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
use PSIndustrial\Core\InstitutionalContentMigration;
use PSIndustrial\Core\Settings;
use PSIndustrial\Core\Migration\Storage;
(static function(): void {
	$checks = array();
	$assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
	$export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

	$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0] ?? null;
	$fixtureUserId = 0;
	$fixturePostIds = array();

	try {
		global $wpdb;
		$before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
		$realSettings = Settings::get();

		$resolvePage = new ReflectionMethod( InstitutionalContentMigration::class, 'resolve_page' );
		$resolvePage->setAccessible( true );
		$migrateSettings = new ReflectionMethod( InstitutionalContentMigration::class, 'migrate_settings' );
		$migrateSettings->setAccessible( true );

		wp_set_current_user( $admin->ID );

		// ============================================================ 1/2: creates if missing, never duplicates
		// A disposable fixture slug, not the real 'soluciones' -- the real Pages already exist
		// on this environment from the original ad hoc work, so proving "create if missing"
		// against them directly is impossible without destroying real, already-approved
		// content. resolve_page() is generic/slug-agnostic code; proving it here proves it for
		// 'soluciones'/'marcas' too, which are exercised for real (not synthetically) below.
		$fixtureSlug = 'psi-institutional-migration-test-fixture-' . strtolower( wp_generate_password( 8, false ) );
		$fixtureSpec = array( 'title' => 'Test Fixture', 'excerpt' => 'Disposable fixture for InstitutionalContentMigration tests, never publicly linked.' );
		$first = $resolvePage->invoke( null, $fixtureSlug, $fixtureSpec );
		$fixturePostIds[] = $first['post_id'];
		$assert( 'created' === $first['status'] && $first['post_id'] > 0, 'A Page missing at its path is created' );
		$assert( 'publish' === get_post_status( $first['post_id'] ), 'The created Page is genuinely published (Editorial guard satisfied via meta_input, not bypassed)' );
		$assert( 'approved' === get_post_meta( $first['post_id'], '_psi_review_state', true ), 'The created Page carries _psi_review_state=approved, the same guard every other publish respects' );
		$assert( get_post( $first['post_id'] )->post_title === $fixtureSpec['title'] && '' !== trim( get_post( $first['post_id'] )->post_excerpt ), 'Title and a real non-empty excerpt were set' );

		$second = $resolvePage->invoke( null, $fixtureSlug, $fixtureSpec );
		$assert( 'reused' === $second['status'] && $second['post_id'] === $first['post_id'], 'A second resolution of the same slug reuses the existing Page, never creates a duplicate' );
		$dupeCount = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='page' AND post_name=%s", $fixtureSlug ) );
		$assert( 1 === $dupeCount, 'Exactly one Page exists at that path in the database, not two' );

		// ============================================================ 5: physical IDs can differ; nothing depends on a specific one
		$fixtureSlug2 = 'psi-institutional-migration-test-fixture-' . strtolower( wp_generate_password( 8, false ) );
		$third = $resolvePage->invoke( null, $fixtureSlug2, $fixtureSpec );
		$fixturePostIds[] = $third['post_id'];
		$assert( 'created' === $third['status'] && $third['post_id'] !== $first['post_id'], 'Two independently-created Pages get two different, environment-assigned post IDs -- neither is fixed/expected' );
		$realSoluciones = get_page_by_path( 'soluciones' );
		$assert( $realSoluciones && (int) $realSoluciones->ID !== $first['post_id'] && (int) $realSoluciones->ID !== $third['post_id'], 'Sanity: the real Soluciones Page (this environment: ID ' . ( $realSoluciones->ID ?? 0 ) . ') is a third, independent ID from both fixtures -- proves IDs are read, never assumed' );

		// ============================================================ 3/4: same mechanism, exercised for real against 'soluciones' and 'marcas'
		foreach ( array( 'soluciones', 'marcas' ) as $realSlug ) {
			$page = get_page_by_path( $realSlug );
			$assert( (bool) $page, "Fixture: the real '$realSlug' Page already exists on this environment (created 2026-09-24, before this portability fix)" );
			$resolved = $resolvePage->invoke( null, $realSlug, array( 'title' => ucfirst( $realSlug ), 'excerpt' => 'unused when reused' ) );
			$assert( 'reused' === $resolved['status'] && $resolved['post_id'] === $page->ID, "resolve_page() correctly recognizes the real, already-existing '$realSlug' Page and reuses it rather than creating a second one" );
		}

		// ============================================================ 6: get_permalink() resolves whatever this environment's real Page is
		$solucionesUrl = \PSIndustrial\Theme\published_page_url( 'soluciones' );
		$assert( $solucionesUrl === get_permalink( get_page_by_path( 'soluciones' )->ID ), "published_page_url('soluciones') matches this environment's real, current permalink" );
		foreach ( array( 'inc/soluciones.php', 'page-nosotros.php', 'front-page.php', 'template-parts/site/catalog-header.php' ) as $themeFile ) {
			$source = file_get_contents( dirname( __DIR__, 3 ) . '/themes/psindustrial/' . $themeFile );
			$assert( ! preg_match( '/\b2048\b|\b2049\b/', $source ), "Structural: $themeFile never hardcodes the local post IDs 2048/2049" );
			$assert( ! preg_match( '/get_permalink\(\s*20(48|49)\s*\)/', $source ), "Structural: $themeFile never calls get_permalink() with a literal id -- always published_page_url()/get_term_link()" );
		}

		// ============================================================ 7: legacy redirects do not depend on 2048/2049
		$map = require dirname( __DIR__ ) . '/data/legacy-url-map.php';
		foreach ( array( 'soluciones.php' => 'soluciones', 'marcas.php' => 'marcas' ) as $file => $expectedPath ) {
			$assert( isset( $map[ $file ] ), "Fixture: a rule exists for $file" );
			$assert( ! array_key_exists( 'wp_id', $map[ $file ] ), "Structural: the $file rule carries no wp_id at all" );
			$assert( ( $map[ $file ]['page_path'] ?? null ) === $expectedPath, "Structural: it carries page_path='$expectedPath' instead" );
		}
		// Behavioural, not just structural: destination() (private) handed a rule with a
		// deliberately wrong wp_id sitting next to the correct page_path must still resolve
		// correctly -- the exact same proof legacy-url-redirects.php already applies to
		// entity_key-based taxonomy rules, extended here to page_path.
		$destinationRef = new ReflectionMethod( \PSIndustrial\Core\LegacyUrls::class, 'destination' );
		$destinationRef->setAccessible( true );
		$expectedSolucionesUrl = get_permalink( get_page_by_path( 'soluciones' )->ID );
		$spoofed = $destinationRef->invoke( null, array( 'wp_type' => 'page', 'page_path' => 'soluciones', 'wp_id' => 999999999 ) );
		$assert( untrailingslashit( $spoofed ) === untrailingslashit( $expectedSolucionesUrl ), 'destination() ignores a present-but-wrong wp_id for a page_path rule and still resolves correctly' );
		$httpSoluciones = wp_remote_get( home_url( '/soluciones.php' ), array( 'redirection' => 0, 'timeout' => 10 ) );
		$assert( 301 === wp_remote_retrieve_response_code( $httpSoluciones ) && untrailingslashit( wp_remote_retrieve_header( $httpSoluciones, 'location' ) ) === untrailingslashit( home_url( '/soluciones/' ) ), 'Real HTTP: /soluciones.php still 301s to the real current Page, resolved by path' );
		$httpMarcas = wp_remote_get( home_url( '/marcas.php' ), array( 'redirection' => 0, 'timeout' => 10 ) );
		$assert( 301 === wp_remote_retrieve_response_code( $httpMarcas ) && untrailingslashit( wp_remote_retrieve_header( $httpMarcas, 'location' ) ) === untrailingslashit( home_url( '/marcas/' ) ), 'Real HTTP: /marcas.php still 301s to the real current Page, resolved by path' );

		// ============================================================ 8/9: settings placeholders migrate; legitimate existing values do not get overwritten
		try {
			update_option( 'psi_site_settings', Settings::sanitize( array_merge( $realSettings, array(
				'contact_address' => '',
				'contact_phone'   => '4774103773',
				'contact_email'   => 'contacto@contacto.com',
				'mail_recipient'  => 'ya-establecido-legitimamente@example.com', // a legitimate, non-placeholder, non-target value an admin could plausibly have set
			) ) ) );
			$statuses = $migrateSettings->invoke( null );
			$assert( 'migrated' === $statuses['contact_address'], 'Empty contact_address is migrated (item 8)' );
			$assert( 'migrated' === $statuses['contact_phone'], 'Known placeholder contact_phone (4774103773) is migrated (item 8)' );
			$assert( 'migrated' === $statuses['contact_email'], 'Known placeholder contact_email (contacto@contacto.com) is migrated (item 8)' );
			$assert( 'skipped_existing' === $statuses['mail_recipient'], 'A legitimate, already-set mail_recipient is left alone, not silently overwritten (item 9)' );
			$after = Settings::get();
			$assert( 'Blvd. Estrella #323 local 5-A, Fracc. Estrella, C.P. 36566, Irapuato, Gto.' === $after['contact_address'], 'The migrated contact_address actually holds the client-confirmed value' );
			$assert( 'ya-establecido-legitimamente@example.com' === $after['mail_recipient'], 'The untouched mail_recipient still holds the legitimate value it had before this run, not the target and not empty' );
		} finally {
			update_option( 'psi_site_settings', Settings::sanitize( $realSettings ) );
		}

		// ============================================================ capability gating: mail_recipient alone waits for a real Administrator
		try {
			update_option( 'psi_site_settings', Settings::sanitize( array_merge( $realSettings, array( 'contact_phone' => '4774103773', 'contact_email' => 'contacto@contacto.com', 'mail_recipient' => '' ) ) ) );
			$fixtureUserId = wp_insert_user( array( 'user_login' => 'psi-migration-test-gestor-' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password(), 'role' => 'psi_gestor' ) );
			$assert( is_int( $fixtureUserId ) && $fixtureUserId > 0, 'Fixture: a temporary psi_gestor user was created for this proof only' );
			wp_set_current_user( $fixtureUserId );
			$assert( ! current_user_can( 'manage_options' ), 'Fixture: psi_gestor genuinely lacks manage_options' );
			$statuses = $migrateSettings->invoke( null );
			$assert( 'pending_capability' === $statuses['mail_recipient'], 'mail_recipient is not migrated by a visitor without manage_options -- reported pending_capability, not silently skipped or forced' );
			$assert( 'migrated' === $statuses['contact_phone'] && 'migrated' === $statuses['contact_email'], 'The other placeholder fields still migrate under psi_gestor -- the capability gate is scoped to mail_recipient alone' );
			$assert( '' === Settings::get()['mail_recipient'], 'mail_recipient genuinely was not written to the option' );
			wp_set_current_user( $admin->ID );
			$statuses = $migrateSettings->invoke( null );
			$assert( 'migrated' === $statuses['mail_recipient'], 'The same still-empty field is picked up and migrated as soon as a real Administrator is the one running it -- retried, not stuck' );
		} finally {
			update_option( 'psi_site_settings', Settings::sanitize( $realSettings ) );
			if ( $fixtureUserId ) { wp_delete_user( $fixtureUserId ); $fixtureUserId = 0; }
			wp_set_current_user( $admin->ID );
		}

		// ============================================================ 10: second execution of the real, public run() = no changes
		$assert( Settings::get() === $realSettings, 'Sanity: real settings are restored to exactly their pre-test value before exercising run() for real' );
		$firstRun = InstitutionalContentMigration::run();
		$secondRun = InstitutionalContentMigration::run();
		$assert( $firstRun === $secondRun, 'Running the real migration twice in a row produces an identical report -- the second run changes nothing' );
		foreach ( $firstRun['settings'] as $field => $status ) { $assert( in_array( $status, array( 'already_set', 'skipped_existing' ), true ), "Against this environment's already-correct real settings, $field resolves to already_set/skipped_existing, never migrated again" ); }
		foreach ( $firstRun['pages'] as $slug => $page ) { $assert( 'reused' === $page['status'], "Against this environment's already-existing real Pages, $slug resolves to reused, never created again" ); }
		$assert( (int) get_option( 'psi_institutional_content_migration_version', 0 ) >= InstitutionalContentMigration::VERSION, 'Version gate is set once every field/page is resolved' );

		// ============================================================ 11: portable -- no Storage::guard(), no Planner, no environment-type gate
		$migrationSource = file_get_contents( __DIR__ . '/../includes/InstitutionalContentMigration.php' );
		$assert( ! str_contains( $migrationSource, 'Storage::guard' ), 'Structural: never gated by Storage::guard() (local-dev-only by design)' );
		$assert( ! str_contains( $migrationSource, 'Planner::' ) && ! str_contains( $migrationSource, 'Runner::' ), 'Structural: never depends on the legacy importer machinery' );
		$assert( ! str_contains( $migrationSource, 'wp_get_environment_type' ), 'Structural: never gated by environment type -- runs the same on local/staging/production' );
		$assert( str_contains( $migrationSource, "add_action( 'admin_init'" ), 'Structural: registered on admin_init, a real authenticated admin-area request' );

		// ============================================================ 12: Contacto/footer continue reading from Settings (not reverted to literals)
		$contactoSource = file_get_contents( dirname( __DIR__, 3 ) . '/themes/psindustrial/page-contacto.php' );
		$footerSource = file_get_contents( dirname( __DIR__, 3 ) . '/themes/psindustrial/template-parts/site/catalog-footer.php' );
		$assert( str_contains( $contactoSource, 'Settings::get()' ), 'Structural: page-contacto.php still sources contact data from Settings::get()' );
		$assert( str_contains( $footerSource, 'Settings::get()' ), 'Structural: catalog-footer.php still sources contact data from Settings::get()' );

		// ============================================================ cleanup + integrity
		foreach ( $fixturePostIds as $id ) { wp_delete_post( $id, true ); }
		$fixturePostIds = array();
		$assert( Settings::get() === $realSettings, 'Real settings end this suite exactly as they started it' );
		$after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
		$assert( $before === $after, 'No net posts/terms remain from this suite -- fixture pages and the fixture user were fully cleaned up' );

		$export( 'institutional-content-migration-tests.json', array( 'passed' => true, 'checks' => $checks ) );
		echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
	} catch ( Throwable $error ) {
		foreach ( $fixturePostIds as $id ) { wp_delete_post( $id, true ); }
		if ( $fixtureUserId ) { wp_delete_user( $fixtureUserId ); }
		if ( isset( $realSettings ) ) { update_option( 'psi_site_settings', Settings::sanitize( $realSettings ) ); }
		wp_set_current_user( 0 );
		$export( 'institutional-content-migration-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) );
		fwrite( STDERR, $error->getMessage() . "\n" );
		exit( 1 );
	}
})();

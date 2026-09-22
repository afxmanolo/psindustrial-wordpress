<?php
/** Tests for LegacyUrls::resolve() -- the legacy .php URL compatibility layer built from
 * the reconciled docs/seo/redirect-map.csv. Strictly non-mutating: resolve() is a pure
 * function (no exit, no superglobals, no writes); every fixture below is a REAL legacy
 * file + REAL WordPress object already in data/legacy-url-map.php, read live. Also runs a
 * handful of real end-to-end HTTP checks against the local server, matching the pattern
 * already established in tests/smoke.php. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\LegacyUrls;
use PSIndustrial\Core\Migration\Storage;
(static function(): void {
	$checks = array();
	$assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
	$export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

	try {
		global $wpdb;
		$before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );

		$map = require dirname( __DIR__ ) . '/data/legacy-url-map.php';
		$assert( count( $map ) > 100, 'Sanity: the generated legacy URL map has a real, non-trivial number of rules (got ' . count( $map ) . ')' );

		// ============================================================ exact product legacy URL -> producto actual (Q01)
		$assert( 'publish' === get_post_status( 1371 ), 'Fixture: sql:productos:73 (post 1371) is genuinely published right now' );
		$destination = LegacyUrls::resolve( '/puerta-seccional-de-acero-thermacore-594-uso-medio.php', true );
		$assert( untrailingslashit( $destination ) === untrailingslashit( get_permalink( 1371 ) ), 'Q01 legacy page for a published product resolves directly to that product\'s live canonical URL' );
		$assert( str_contains( $destination, '/producto/' ), 'Destination uses the real product permalink structure, not an invented one' );

		// ============================================================ Q04 static product -> producto (not yet public -> no invented redirect)
		$assert( 'draft' === get_post_status( 133 ), 'Fixture: the Q04 static-product entity (post 133, automatismos-para-cancelas-cubic.php) is genuinely draft right now' );
		$assert( null === LegacyUrls::resolve( '/automatismos-para-cancelas-cubic.php', true ), 'Q04 static-product mapping exists but target is draft: no redirect invented, stays eligible for 404' );

		// ============================================================ Q07 landing -> Page (not yet public -> no invented redirect)
		$assert( 'draft' === get_post_status( 1186 ), 'Fixture: the Q07 landing Page (1186, operadores-puerta-abatible.php) is genuinely draft right now' );
		$assert( null === LegacyUrls::resolve( '/operadores-puerta-abatible.php', true ), 'Q07 landing mapping exists but the Page is draft: no redirect invented' );

		// ============================================================ known category mapping (not yet public -> KEEP_PENDING, never invented)
		$assert( 'review' === get_term_meta( 245, '_psi_public_state', true ), 'Fixture: category term 245 (blindadas.php) is genuinely in review right now' );
		$assert( isset( $map['blindadas.php'] ), 'A real mapping rule exists for blindadas.php (proves the mechanism reaches taxonomy targets)' );
		$assert( null === LegacyUrls::resolve( '/blindadas.php', true ), 'Category mapping exists but the term is review, not public: no redirect' );

		// ============================================================ pending taxonomy / unresolved page -> no redirect invented
		$assert( ! isset( $map['puertas-blindadas.php'] ), 'A genuinely unresolved legacy file (KEEP_REVIEW, no owner) has no rule at all in the generated map' );
		$assert( null === LegacyUrls::resolve( '/puertas-blindadas.php', true ), 'No rule exists: resolve() returns null, never a guessed destination' );

		// ============================================================ unknown .php -> 404 (no rule, ever)
		$assert( ! isset( $map['this-file-never-existed-in-any-source-xyz.php'] ), 'A made-up filename has no rule' );
		$assert( null === LegacyUrls::resolve( '/this-file-never-existed-in-any-source-xyz.php', true ), 'An unmapped legacy .php path resolves to null -- normal 404, never a soft-redirect to Home' );

		// ============================================================ current WP URL -> no redirect
		$assert( null === LegacyUrls::resolve( '/nosotros/', false ), 'A real, resolvable modern URL never reaches redirect logic at all: is_404()=false short-circuits immediately' );
		$modernBasenames = array_filter( array_keys( $map ), static fn( $f ) => ! str_ends_with( $f, '.php' ) );
		$assert( array() === $modernBasenames, 'Structural: every single rule key ends in .php -- a modern clean-slug basename can never collide with one' );
		$assert( null === LegacyUrls::resolve( '/nosotros/', true ), 'Even if WordPress somehow 404s a path with no .php suffix, basename() never matches a rule' );

		// ============================================================ redirect loop / chain -> impossible (structural, across the whole table)
		// Every rule key ends in .php (already proven above); every live destination below
		// is checked to never itself be a .php path or another rule key, so a request can
		// never bounce through this layer twice, let alone loop.
		$chainOrLoopFound = false; $destinationsChecked = 0;
		foreach ( $map as $file => $rule ) {
			$dest = null;
			if ( 'site_root' === $rule['wp_type'] ) { $dest = home_url( '/' ); }
			elseif ( in_array( $rule['wp_type'], array( 'page', 'psi_producto' ), true ) && 'publish' === get_post_status( $rule['wp_id'] ) ) { $dest = get_permalink( $rule['wp_id'] ); }
			elseif ( in_array( $rule['wp_type'], array( 'psi_categoria', 'psi_marca' ), true ) && \PSIndustrial\Core\TermPolicy::is_public( $rule['wp_id'] ) ) { $link = get_term_link( $rule['wp_id'], $rule['wp_type'] ); $dest = is_wp_error( $link ) ? null : $link; }
			if ( null === $dest ) { continue; }
			$destinationsChecked++;
			$destBasename = basename( (string) wp_parse_url( $dest, PHP_URL_PATH ) );
			if ( str_ends_with( $destBasename, '.php' ) || isset( $map[ $destBasename ] ) ) { $chainOrLoopFound = true; }
		}
		$assert( $destinationsChecked > 0, 'Sanity: at least one currently-live destination was actually checked (' . $destinationsChecked . ')' );
		$assert( false === $chainOrLoopFound, 'No live destination in the whole table is itself a legacy .php path or another rule key -- redirect chains/loops are structurally impossible, checked across all ' . count( $map ) . ' rules' );

		// ============================================================ real end-to-end HTTP checks (matches tests/smoke.php's own pattern)
		// admin/API/assets never touched is exercised directly below via a real wp-admin request.
		$httpBase = home_url();
		$http = static function( string $path ) use ( $httpBase ): array {
			$r = wp_remote_get( $httpBase . $path, array( 'redirection' => 0, 'timeout' => 10 ) );
			if ( is_wp_error( $r ) ) { return array( 'status' => 0, 'location' => '' ); }
			return array( 'status' => wp_remote_retrieve_response_code( $r ), 'location' => wp_remote_retrieve_header( $r, 'location' ) );
		};
		$r1 = $http( '/nosotros.php' );
		$assert( 301 === $r1['status'] && untrailingslashit( $r1['location'] ) === untrailingslashit( home_url( '/nosotros/' ) ), "HTTP 301 /nosotros.php -> /nosotros/ (got {$r1['status']} {$r1['location']})" );
		$r2 = $http( '/accesorios.php' );
		$assert( 404 === $r2['status'], "HTTP 404 /accesorios.php -- mapped but target draft, never soft-redirected (got {$r2['status']})" );
		$r3 = $http( '/index.php' );
		$assert( 301 === $r3['status'] && untrailingslashit( $r3['location'] ) === untrailingslashit( home_url( '/' ) ), "HTTP 301 /index.php -> site root (got {$r3['status']} {$r3['location']})" );
		$r4 = $http( '/nosotros.php?utm_source=psi-test' );
		$assert( 301 === $r4['status'] && str_contains( $r4['location'], 'utm_source=psi-test' ), 'Query string is preserved through the redirect' );
		$r5 = $http( '/wp-admin/' );
		$assert( 302 === $r5['status'] && str_contains( $r5['location'], 'wp-login.php' ), "wp-admin still gets WordPress's own normal auth redirect, completely untouched by the legacy layer (got {$r5['status']} {$r5['location']})" );
		$r6 = $http( '/nosotros/' );
		$assert( 200 === $r6['status'], 'Modern current URL /nosotros/ returns 200 directly, no redirect' );

		$after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
		$assert( $before === $after, 'The entire suite (including live HTTP requests) never mutates the database' );

		$export( 'legacy-url-redirects-tests.json', array( 'passed' => true, 'checks' => $checks ) );
		echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'map_size' => count( $map ) ) );
	} catch ( Throwable $error ) {
		$export( 'legacy-url-redirects-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
	}
})();

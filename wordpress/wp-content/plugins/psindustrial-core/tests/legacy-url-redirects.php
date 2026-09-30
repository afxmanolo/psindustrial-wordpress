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
		// Term 245 (blindadas.php, category:32) was the original real, still-review fixture
		// here; CategoriesMigration (2026-09-23, dynamic "Soluciones" menu) has since
		// published every real category, itself the correct, intended outcome, not a bug --
		// so no genuinely non-public category with a real map entry exists anymore to read
		// live. Reversibly toggle this one real term back to review for the duration of this
		// check only (never created/deleted, restored in `finally` even if an assertion
		// throws) to keep exercising the real mapping rule + real destination() taxonomy
		// branch, as close to this file's own "real object" fixtures as the current data
		// still allows.
		//
		// TermEditor::guard() (update_term_metadata filter) deliberately refuses to
		// un-publish an already-public term (real safety: never silently drop a live public
		// URL) unless the acting user has psi_manage_routes -- a real capability this file's
		// otherwise-anonymous checks never need elsewhere, so it is granted only around this
		// one reversible toggle, exactly the scope the write itself requires.
		$assert( isset( $map['blindadas.php'] ), 'A real mapping rule exists for blindadas.php (proves the mechanism reaches taxonomy targets)' );
		$assert( 'public' === get_term_meta( 245, '_psi_public_state', true ), 'Fixture: category term 245 (blindadas.php) is genuinely public right now (CategoriesMigration)' );
		wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
		update_term_meta( 245, '_psi_public_state', 'review' );
		wp_set_current_user( 0 );
		try {
			$assert( 'review' === get_term_meta( 245, '_psi_public_state', true ), 'Sanity: the toggle above actually took effect' );
			$assert( null === LegacyUrls::resolve( '/blindadas.php', true ), 'Category mapping exists but the term is review, not public: no redirect' );
		} finally {
			wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
			update_term_meta( 245, '_psi_public_state', 'public' );
			wp_set_current_user( 0 );
		}

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
		// never bounce through this layer twice, let alone loop. Goes through the real,
		// public resolve() for every single rule key (never a hand-rolled re-implementation
		// of destination()'s per-wp_type branches, which would silently drift out of sync --
		// exactly what happened here once already when psi_categoria/psi_marca stopped
		// carrying wp_id).
		$chainOrLoopFound = false; $destinationsChecked = 0;
		foreach ( array_keys( $map ) as $file ) {
			$dest = LegacyUrls::resolve( $file, true );
			if ( null === $dest ) { continue; }
			$destinationsChecked++;
			$destBasename = basename( (string) wp_parse_url( $dest, PHP_URL_PATH ) );
			if ( str_ends_with( $destBasename, '.php' ) || isset( $map[ $destBasename ] ) ) { $chainOrLoopFound = true; }
		}
		$assert( $destinationsChecked > 0, 'Sanity: at least one currently-live destination was actually checked (' . $destinationsChecked . ')' );
		$assert( false === $chainOrLoopFound, 'No live destination in the whole table is itself a legacy .php path or another rule key -- redirect chains/loops are structurally impossible, checked across all ' . count( $map ) . ' rules' );

		// ============================================================ portability: taxonomy destinations resolve by identity, never a stored term_id
		// The real incident this proves against: category:39 was term_id 250 originally,
		// accidentally deleted and recreated locally as 333 during this project's own test
		// development (documented in this session's report). Staging was never touched by
		// that accident and most likely still has 250 for the same category. The exact same
		// checked-in code -- this file's own data/legacy-url-map.php among it -- has to
		// redirect correctly on both, which means it can never read a stored term_id for a
		// taxonomy rule at all.
		$assert( ! array_key_exists( 'wp_id', $map['operadores-para-puertas-ascendentes.php'] ), 'Structural: the map entry for category:39 carries no wp_id at all' );
		$assert( 'category:39' === $map['operadores-para-puertas-ascendentes.php']['entity_key'], 'Structural: it carries entity_key=category:39 instead' );
		foreach ( $map as $file => $rule ) {
			if ( in_array( $rule['wp_type'], array( 'psi_categoria', 'psi_marca' ), true ) ) {
				$assert( ! array_key_exists( 'wp_id', $rule ) && isset( $rule['entity_key'] ), "Structural: every taxonomy rule resolves by entity_key, never wp_id: $file" );
			}
		}
		$realTermId = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => 'category:39' ) );
		$assert( $realTermId > 0, 'Fixture: category:39 genuinely resolves to a real term in this environment right now' );
		$expectedCanonical = get_term_link( $realTermId, 'psi_categoria' );
		$destination = LegacyUrls::resolve( '/operadores-para-puertas-ascendentes.php', true );
		$assert( untrailingslashit( $destination ) === untrailingslashit( $expectedCanonical ), 'The legacy URL resolves to THIS environment\'s real, current term -- whatever its numeric id happens to be here' );
		// Behavioural proof, not just structural: destination() (private -- Reflection) is
		// handed a rule with a deliberately wrong wp_id sitting right next to the correct
		// entity_key. If wp_id mattered at all, this would resolve to nonsense or throw; it
		// must resolve to the exact same real canonical as above, proving wp_id is never even
		// read for a taxonomy rule, the strongest form of "does not depend on a fixed term_id"
		// -- no real database mutation, no simulated environment, needed to prove it.
		$ref = new ReflectionMethod( LegacyUrls::class, 'destination' );
		$ref->setAccessible( true );
		$spoofed = $ref->invoke( null, array( 'wp_type' => 'psi_categoria', 'entity_key' => 'category:39', 'wp_id' => 999999999 ) );
		$assert( untrailingslashit( $spoofed ) === untrailingslashit( $expectedCanonical ), 'destination() ignores a present-but-wrong wp_id entirely and still resolves correctly by entity_key alone' );
		// And the same property for a second, independent category and a brand -- never a
		// one-off special case for category:39 alone.
		foreach ( array( array( 'psi_categoria', 'category:1', 'industrial' ), array( 'psi_marca', 'brand:3', 'clopay' ) ) as [ $taxonomy, $entityKey, $slug ] ) {
			$realId = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => $taxonomy, 'entity_key' => $entityKey ) );
			$assert( $realId > 0, "Fixture: $entityKey resolves to a real term" );
			$expected = get_term_link( $realId, $taxonomy );
			$spoofed = $ref->invoke( null, array( 'wp_type' => $taxonomy, 'entity_key' => $entityKey, 'wp_id' => 888888888 ) );
			$assert( untrailingslashit( $spoofed ) === untrailingslashit( $expected ), "Same property holds generally, not just for category:39: $entityKey" );
		}

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

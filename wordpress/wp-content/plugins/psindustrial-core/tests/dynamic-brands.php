<?php
/** Tests for the dynamic Home "Marcas" section (home_brands()), the BrandsMigration data
 * migration, and the /productos/marca/{id}/{slug}/ legacy pattern in LegacyUrls. Strictly
 * read-only against real data: the 12 historical psi_marca terms, their real _psi_logo_id
 * attachments and real product relationships -- no synthetic brand fixtures, since the
 * whole point is verifying the REAL 12 terms this session's migration touched. Also runs
 * real end-to-end HTTP checks, matching tests/legacy-url-redirects.php's own pattern. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\{BrandsMigration, LegacyUrls};
use PSIndustrial\Core\Migration\Storage;
(static function(): void {
	$checks = array();
	$assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
	$export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

	try {
		global $wpdb;
		$before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );

		// ============================================================ BrandsMigration: idempotent, correct
		$firstRun = BrandsMigration::run(); // already applied earlier in this session -- expect a clean no-op.
		$assert( array() === $firstRun, 'BrandsMigration::run() is a no-op once already applied (idempotent)' );
		$historicalOrder = array( 'Overhead Door' => 1, 'Wayne Dalton' => 2, 'Clopay' => 3, 'Blue Giant' => 4, 'Kelley' => 5, 'Doorlock' => 6, 'Rytec' => 7, 'Infraca Quality Doors' => 8, 'GLG Porter Industriali' => 9, 'Dockman' => 10, 'LiftMaster' => 11, 'BFT' => 12 );
		$terms = get_terms( array( 'taxonomy' => 'psi_marca', 'hide_empty' => false ) );
		$assert( 12 === count( $terms ), 'Exactly the 12 historical brand terms exist' );
		foreach ( $terms as $t ) {
			$assert( isset( $historicalOrder[ $t->name ] ), 'Term is one of the 12 historical brands: ' . $t->name );
			$assert( 'public' === get_term_meta( $t->term_id, '_psi_public_state', true ), 'Migration set this brand public: ' . $t->name );
			$assert( (int) get_term_meta( $t->term_id, '_psi_brand_home_order', true ) === $historicalOrder[ $t->name ], 'Migration set the correct historical home order for: ' . $t->name );
		}

		// ============================================================ 1/2/3: Home is dynamic, no hardcoded 12, empty brands included
		$homeSource = file_get_contents( dirname( __DIR__ ) . '/../../themes/psindustrial/inc/home-data.php' );
		$assert( ! str_contains( $homeSource, "'name' => 'Overhead Door'" ), 'home-data.php no longer hardcodes the static brand list' );
		$frontPageSource = file_get_contents( dirname( __DIR__ ) . '/../../themes/psindustrial/front-page.php' );
		$assert( str_contains( $frontPageSource, 'home_brands()' ), 'front-page.php sources brands from home_brands(), not a static array' );

		$brands = \PSIndustrial\Theme\home_brands();
		$assert( 12 === count( $brands ), 'home_brands() returns all 12 (hide_empty=false honored): got ' . count( $brands ) );
		$names = array_column( $brands, 'name' );
		$assert( in_array( 'LiftMaster', $names, true ), 'LiftMaster (0 products, any status) still appears: hide_empty=false is real, not just a product-count check' );
		$orderedNames = array_keys( $historicalOrder );
		$returnedInOrder = array_values( array_intersect( $orderedNames, $names ) );
		$assert( $orderedNames === $returnedInOrder, 'home_brands() preserves the exact historical order, not alphabetical/term_id order' );

		// ============================================================ 4/5/10: every logo links to its real term archive, resolves, no href="#"
		foreach ( $brands as $brand ) {
			$term = get_term_by( 'name', $brand['name'], 'psi_marca' );
			$assert( untrailingslashit( $brand['link'] ) === untrailingslashit( get_term_link( $term ) ), 'Brand link is the real get_term_link() for: ' . $brand['name'] );
			$assert( '#' !== $brand['link'] && ! str_ends_with( $brand['link'], '/#' ), 'Brand link is never a placeholder href=#: ' . $brand['name'] );
			$assetPath = get_theme_file_path( 'assets/images/home/' . pathinfo( $brand['image'], PATHINFO_FILENAME ) . '-500.webp' );
			$assert( is_file( $assetPath ), 'Logo asset for ' . $brand['name'] . ' actually resolves to a real file on disk: ' . $assetPath );
		}

		// ============================================================ 6/7: archive works with and without products
		$clopay = get_term_by( 'slug', 'clopay', 'psi_marca' );
		$liftmaster = get_term_by( 'slug', 'liftmaster', 'psi_marca' );
		$assert( $clopay && $liftmaster, 'Fixture terms resolve: Clopay (has products) and LiftMaster (has none)' );
		// $target accepts either a bare path ('/foo/') or a full URL already carrying
		// home_url()'s own prefix (e.g. straight from get_term_link()) -- never double-prefixes.
		$http = static function( string $target ): array {
			$url = str_starts_with( $target, 'http' ) ? $target : home_url( $target );
			$r = wp_remote_get( $url, array( 'redirection' => 0, 'timeout' => 10 ) );
			if ( is_wp_error( $r ) ) { return array( 'status' => 0, 'location' => '', 'body' => '' ); }
			return array( 'status' => wp_remote_retrieve_response_code( $r ), 'location' => wp_remote_retrieve_header( $r, 'location' ), 'body' => wp_remote_retrieve_body( $r ) );
		};
		$rClopay = $http( get_term_link( $clopay ) );
		$assert( 200 === $rClopay['status'], 'Archive for a brand with real product relationships renders 200 (got ' . $rClopay['status'] . ')' );
		$rLift = $http( get_term_link( $liftmaster ) );
		$assert( 200 === $rLift['status'], 'Archive for LiftMaster (zero products) renders 200, never 404 for being empty (got ' . $rLift['status'] . ')' );
		$assert( str_contains( $rLift['body'], 'Todavía no hay productos publicados' ), 'Empty brand archive shows the real "no products published" message' );

		// ============================================================ 8: Clopay legacy pattern -> correct canonical, 301
		$rLegacyClopay = $http( '/productos/marca/3/clopay/' );
		$assert( 301 === $rLegacyClopay['status'] && untrailingslashit( $rLegacyClopay['location'] ) === untrailingslashit( get_term_link( $clopay ) ), 'Legacy /productos/marca/3/clopay/ -> 301 -> real Clopay canonical (got ' . $rLegacyClopay['status'] . ' ' . $rLegacyClopay['location'] . ')' );
		$rLegacyBadSlug = $http( '/productos/marca/3/this-slug-was-never-real/' );
		$assert( 301 === $rLegacyBadSlug['status'] && untrailingslashit( $rLegacyBadSlug['location'] ) === untrailingslashit( get_term_link( $clopay ) ), 'The slug segment is decorative, exactly like the original legacy rewrite (id 3 alone resolves Clopay) -- never validated against the current slug' );
		$rLegacyPhp = $http( '/clopay.php' );
		$assert( 301 === $rLegacyPhp['status'] && untrailingslashit( $rLegacyPhp['location'] ) === untrailingslashit( get_term_link( $clopay ) ), 'The pre-existing .php legacy mapping for Clopay still resolves to the same real canonical' );

		// ============================================================ 9: all 12 brand legacy mappings, no loops/chains
		$legacyIdByName = array( 'Overhead Door' => 1, 'Wayne Dalton' => 2, 'Clopay' => 3, 'Blue Giant' => 4, 'Kelley' => 5, 'Doorlock' => 6, 'Rytec' => 7, 'Infraca Quality Doors' => 8, 'GLG Porter Industriali' => 9, 'Dockman' => 10, 'LiftMaster' => 11, 'BFT' => 12 );
		$phpFileByName = array( 'Overhead Door' => 'overhead-door.php', 'Wayne Dalton' => 'wayne-dalton.php', 'Clopay' => 'clopay.php', 'Blue Giant' => 'blue-giant.php', 'Kelley' => 'kelley.php', 'Doorlock' => 'doorlock.php', 'Rytec' => 'rytec.php', 'Infraca Quality Doors' => 'infraca-quality-doors.php', 'GLG Porter Industriali' => 'glg-porte-industriali.php', 'Dockman' => 'dockman.php', 'LiftMaster' => 'lift-master.php', 'BFT' => 'bft.php' );
		foreach ( $legacyIdByName as $name => $legacyId ) {
			$term = get_term_by( 'name', $name, 'psi_marca' );
			$canonical = untrailingslashit( get_term_link( $term ) );
			$destPattern = LegacyUrls::resolve( "/productos/marca/{$legacyId}/anything/", true );
			$assert( untrailingslashit( (string) $destPattern ) === $canonical, "Pattern mapping resolves to the real canonical for $name" );
			$destPhp = LegacyUrls::resolve( '/' . $phpFileByName[ $name ], true );
			$assert( untrailingslashit( (string) $destPhp ) === $canonical, "Existing .php mapping resolves to the SAME real canonical for $name (no divergence between the two legacy schemes)" );
			// No chain/loop: re-resolving the canonical destination's own path must never
			// itself produce a further redirect.
			$destBasename = basename( (string) wp_parse_url( $canonical, PHP_URL_PATH ) );
			$assert( null === LegacyUrls::resolve( '/' . $destBasename, true ), "Canonical destination for $name is not itself a redirect source (no chain): /$destBasename" );
		}
		$assert( null === LegacyUrls::resolve( '/productos/marca/9999/nonexistent/', true ), 'An unknown legacy brand id resolves to null, never an invented destination' );
		$assert( null === LegacyUrls::resolve( '/productos/marca/abc/clopay/', true ), 'A non-numeric id never matches the pattern' );
		$assert( null === LegacyUrls::resolve( '/productos/marca/3/clopay/extra/', true ), 'Extra path segments never match the pattern' );

		// ============================================================ 11: /legacy untouched (read-only proof: the exact rewrite rule this whole feature is built from)
		$htaccess = file_get_contents( Storage::project() . '/legacy/public/.htaccess' );
		$assert( str_contains( $htaccess, 'productos/marca/([\d]+)/(.*)/$' ), '/legacy/public/.htaccess still contains the original brand rewrite rule this test derived its pattern from -- read-only, never modified' );

		$after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
		$assert( $before === $after, 'This entire suite (including live HTTP requests and the migration re-run) creates no posts/terms -- the only writes ever made were the one-time BrandsMigration run, already applied before this suite started' );

		$export( 'dynamic-brands-tests.json', array( 'passed' => true, 'checks' => $checks ) );
		echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'brand_count' => count( $brands ) ) );
	} catch ( Throwable $error ) {
		$export( 'dynamic-brands-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
	}
})();

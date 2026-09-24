<?php
/** Tests for the dynamic Home "Conoce nuestros Productos" category sections
 * (\PSIndustrial\Theme\home_category_sections()) and CategoriesMigration's SIBLING_ORDER.
 * Strictly read-only against real data: the 7 real, public root categories and their 27 real
 * direct children -- no synthetic fixtures, since the whole point is verifying the REAL
 * hierarchy. Also runs real end-to-end HTTP checks against the Home page and re-verifies the
 * "Soluciones" dropdown/archives/legacy redirects/Identity are unaffected by writing
 * _psi_category_menu_order to children as well as roots (same meta key, disjoint value
 * ranges per parent list -- this test proves that sharing never cross-contaminates). */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\CategoriesMigration;
use PSIndustrial\Core\Migration\{Storage, Identity, Planner};
(static function(): void {
	$checks = array();
	$assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
	$export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

	try {
		wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
		Storage::guard();
		global $wpdb;
		$before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );

		// ============================================================ CategoriesMigration v2: idempotent, correct
		$rerun = CategoriesMigration::run();
		$assert( array() === $rerun, 'CategoriesMigration::run() is a no-op once already applied (idempotent)' );
		$assert( 2 === CategoriesMigration::VERSION, 'Fixture: this is the v2 migration (adds SIBLING_ORDER on top of v1\'s public-state + root order)' );
		$ref = new ReflectionClass( CategoriesMigration::class );
		$siblingOrder = $ref->getConstant( 'SIBLING_ORDER' );
		$assert( 27 === count( $siblingOrder ), 'Fixture: exactly 27 real children carry an explicit sibling order (36 approved legacy_ids - 7 roots - 2 without a real term, 37/38)' );

		// ============================================================ 1/2/3/4/5: real parent, real direct children, real links, empty ones included
		$sections = \PSIndustrial\Theme\home_category_sections();
		$assert( 7 === count( $sections ), 'All 7 real, public root categories produce a Home section' );
		// Keyed by legacy_id (always ASCII-safe), not section title text (one real title has
		// a known encoding artifact) -- home_category_sections() iterates home_data()'s
		// sections in legacy_id 1..7 order, so position i here is always legacy_id i+1,
		// exactly like the order assertions below already rely on.
		$expectedChildCountsByPosition = array( 7, 4, 5, 4, 3, 2, 2 ); // audited counts for legacy_id 1..7
		foreach ( $sections as $i => $section ) {
			$parent = get_term_by( 'name', $section['title'], 'psi_categoria' );
			$assert( (bool) $parent, "Section title is a real psi_categoria term: {$section['title']}" );
			$assert( untrailingslashit( $section['link'] ) === untrailingslashit( get_term_link( $parent ) ), "Section resolves its real parent term: {$section['title']}" );
			$realChildren = get_terms( array( 'taxonomy' => 'psi_categoria', 'parent' => $parent->term_id, 'hide_empty' => false ) );
			$assert( count( $realChildren ) === count( $section['children'] ), "Section lists exactly the parent's real direct children, no more/fewer: {$section['title']} (got " . count( $section['children'] ) . ', expected ' . count( $realChildren ) . ')' );
			$assert( $expectedChildCountsByPosition[ $i ] === count( $section['children'] ), "Child count matches the audited real hierarchy (legacy_id " . ( $i + 1 ) . '): ' . count( $section['children'] ) );
			foreach ( $section['children'] as $child ) {
				$childTerm = get_term_by( 'name', $child['label'], 'psi_categoria' );
				$assert( (bool) $childTerm && (int) $childTerm->parent === $parent->term_id, "Child is a real, direct child of this exact parent: {$child['label']} of {$section['title']}" );
				$assert( untrailingslashit( $child['link'] ) === untrailingslashit( get_term_link( $childTerm ) ), "Child link is the real get_term_link(): {$child['label']}" );
			}
		}
		// A real, currently zero-product child (Puertas Seccionales has products; pick one
		// that structurally cannot -- any child of a section whose own term has 0 direct
		// associations) still appears: Puerta Holandesa (category:29) has 0 associated
		// products and was the one link legacy disabled entirely, yet it appears here.
		$holandesa = array_values( array_filter( $sections[3]['children'], static fn( $c ) => 'Puerta Holandesa' === $c['label'] ) );
		$assert( 1 === count( $holandesa ), 'A child with zero products (Puerta Holandesa, disabled in legacy) still appears -- hide_empty=false is real, not a product-count check' );

		// ============================================================ 6: "Ver todos" links to the parent, never the global catalog
		$catalogArchive = untrailingslashit( get_post_type_archive_link( 'psi_producto' ) );
		foreach ( $sections as $section ) {
			$assert( untrailingslashit( $section['link'] ) !== $catalogArchive, "Ver todos never falls back to the global catalog: {$section['title']}" );
		}

		// ============================================================ 7: no href="#" anywhere
		foreach ( $sections as $section ) {
			$assert( '#' !== $section['link'], "Parent link is never a placeholder: {$section['title']}" );
			foreach ( $section['children'] as $child ) { $assert( '#' !== $child['link'], "Child link is never a placeholder: {$child['label']}" ); }
		}

		// ============================================================ 8: order matches the real legacy/public/index-estatico.php evidence
		// Expected labels are the REAL, live term names for each legacy_id in the audited
		// legacy order (never a hardcoded literal copy): the source data has known encoding
		// artifacts on accented characters (e.g. category:17's real name renders as "Puertas
		// rÃ¡pidas", not "Puertas rápidas") -- comparing real name to real name is what proves
		// the ORDER property here, without an unrelated encoding question tripping a literal-
		// string comparison up, exactly like tests/dynamic-categories-menu.php's own fix for
		// the identical class of issue.
		$namesByLegacyId = static function( array $legacyIds ): array {
			return array_map( static fn( $lid ) => get_term( Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => "category:$lid" ) ) )->name, $legacyIds );
		};
		$assert( $namesByLegacyId( array( 8, 11, 12, 13, 14, 17, 16 ) ) === array_column( $sections[0]['children'], 'label' ), 'Industrial children are in the exact legacy Home order' );
		$assert( $namesByLegacyId( array( 9, 18, 19, 20 ) ) === array_column( $sections[1]['children'], 'label' ), 'Comercial children are in the exact legacy Home order' );
		$assert( $namesByLegacyId( array( 27, 28, 30, 29 ) ) === array_column( $sections[3]['children'], 'label' ), 'Salida de Emergencia children: the 2 grouped legacy links split into their real categories in reading order, then the legacy-disabled Holandesa last' );
		$assert( $namesByLegacyId( array( 33, 31, 32 ) ) === array_column( $sections[4]['children'], 'label' ), 'Contra Incendio children are in the exact legacy Home order' );
		$assert( $namesByLegacyId( array( 36, 39 ) ) === array_column( $sections[6]['children'], 'label' ), 'Residenciales children: real order preserved with 37/38 (no WordPress term) correctly absent, never invented' );
		$assert( array( 1, 2, 3, 4, 5, 6, 7 ) === array_map( static fn( $lid ) => (int) get_term_meta( Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => "category:$lid" ) ), '_psi_category_menu_order', true ), range( 1, 7 ) ), 'Root order values are untouched by writing sibling order to children (shared meta key, no cross-contamination)' );

		// ============================================================ 9: the "Soluciones" dropdown/footer are not broken by this change
		$nav = \PSIndustrial\Theme\catalog_navigation();
		$navRoots = array_values( array_filter( $nav, static fn( $i ) => in_array( $i['label'], array_column( $sections, 'title' ), true ) ) );
		$assert( 7 === count( $navRoots ) && array_column( $sections, 'title' ) === array_column( $navRoots, 'label' ), 'catalog_navigation() (header/footer) still shows exactly the 7 roots, in the same order, unaffected by children now also carrying _psi_category_menu_order' );

		// ============================================================ 10: archives still work (spot check: a section child + the parent)
		$http = static function( string $url ): int {
			$r = wp_remote_get( $url, array( 'redirection' => 0, 'timeout' => 10 ) );
			return is_wp_error( $r ) ? 0 : wp_remote_retrieve_response_code( $r );
		};
		$assert( 200 === $http( $sections[0]['link'] ), 'Parent archive still renders 200: ' . $sections[0]['title'] );
		$assert( 200 === $http( $holandesa[0]['link'] ), 'Zero-product child archive still renders 200: Puerta Holandesa' );

		// ============================================================ 11: legacy redirects for these same categories still pass
		$assert( 301 === $http( home_url( '/industrial.php' ) ), 'Legacy industrial.php still 301s (LegacyUrls untouched by this task)' );

		// ============================================================ 12: Identity stays green for every section parent and child
		$fresh = Planner::build( 'full' );
		$byKey = array_column( $fresh['entries'], null, 'entity_key' );
		foreach ( array_merge( range( 1, 7 ), array_keys( $siblingOrder ) ) as $legacyId ) {
			$key = "category:$legacyId";
			$assert( isset( $byKey[ $key ] ), "Fixture: $key resolves in a freshly-built plan" );
			$assert( 'UNCHANGED' === Identity::prediction( $byKey[ $key ] ), "Section category still predicts UNCHANGED after gaining a sibling order, not CONFLICT: $key" );
		}

		// ============================================================ Home page itself renders correctly end-to-end
		$home = wp_remote_get( home_url( '/' ), array( 'timeout' => 10 ) );
		$assert( ! is_wp_error( $home ) && 200 === wp_remote_retrieve_response_code( $home ), 'Home page renders 200' );
		$homeBody = wp_remote_retrieve_body( $home );
		$homeDocument = new DOMDocument();
		$libxmlPrevious = libxml_use_internal_errors( true );
		$homeDocument->loadHTML( '<?xml encoding="UTF-8">' . $homeBody );
		libxml_clear_errors(); libxml_use_internal_errors( $libxmlPrevious );
		$homeXPath = new DOMXPath( $homeDocument );
		$assert( 7 === $homeXPath->query( '//section[contains(concat(" ",@class," ")," home-family ")]' )->length, 'Home page HTML still contains all 7 family sections' );
		$assert( str_contains( $homeBody, esc_url( $sections[0]['link'] ) ), 'Rendered Home HTML actually contains the real, resolved parent link' );

		$after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
		$assert( $before === $after, 'This entire suite (including live HTTP requests and the migration re-run) creates no posts/terms' );

		$export( 'dynamic-home-categories-tests.json', array( 'passed' => true, 'checks' => $checks ) );
		echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'sections' => count( $sections ) ) );
	} catch ( Throwable $error ) {
		$export( 'dynamic-home-categories-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
	}
})();

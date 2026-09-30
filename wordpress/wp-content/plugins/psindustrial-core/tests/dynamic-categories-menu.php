<?php
/** Tests for the dynamic "Soluciones" navigation (catalog_navigation()), the
 * CategoriesMigration data migration, and the two legacy category URL patterns
 * (/productos/categoria/{id}/{slug}/ and /{id}/categoria/{slug}/) in LegacyUrls. Strictly
 * read-only against real data: the 34 real, evidence-confirmed psi_categoria terms this
 * session's migration touched, their real product relationships and real hierarchy -- no
 * synthetic category fixtures for the positive cases, since the whole point is verifying the
 * REAL terms. One small, cleaned-up synthetic term is used only for the negative TermPolicy
 * case (13), which needs a genuinely non-public term and none of the 34 approved ones still
 * qualifies. Also runs real end-to-end HTTP checks, matching tests/dynamic-brands.php and
 * tests/legacy-url-redirects.php's own pattern. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\{CategoriesMigration, LegacyUrls, TermPolicy};
use PSIndustrial\Core\Migration\{Storage, Identity, Planner};
(static function(): void {
	$checks = array();
	$assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
	$export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

	// A real category term (250, "Operadores para puertas ascendentes") was once destroyed by
	// this file's own cleanup: a $termId variable reused both as a foreach loop variable (real
	// terms, read-only) and as the synthetic fixture's id meant that when an unrelated
	// assertion threw first, the catch block found $termId still holding the LAST REAL TERM ID
	// the loop had touched and deleted it. Recovered (Runner::apply(), the same real code path
	// the importer itself uses) and documented in this session's final report.
	//
	// The rename to $syntheticTermId (declared here, before the try block, the ONLY variable
	// any cleanup below ever acts on) fixed THAT specific bug. This second, independent guard
	// fixes the CLASS of bug: no cleanup here ever calls wp_delete_term() on a bare integer's
	// say-so again. FIXTURE_SLUG_PREFIX and FIXTURE_META_KEY are the two pieces of evidence
	// every synthetic term created below must carry BEFORE it may be deleted -- a real
	// category can never have both (real slugs come from legacy Spanish names; the meta key is
	// never written by CategoriesMigration, the importer, or any editorial UI) -- so even a
	// future variable-collision bug re-pointing $syntheticTermId at a real term_id would hit
	// this check and fail loudly instead of silently deleting something real.
	$FIXTURE_SLUG_PREFIX = 'zzz-test-';
	$FIXTURE_META_KEY = '_psi_test_fixture';
	$safeDeleteTestTerm = static function( ?int $termId, string $taxonomy ) use ( $FIXTURE_SLUG_PREFIX, $FIXTURE_META_KEY ): void {
		if ( ! $termId ) { return; }
		$term = get_term( $termId, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) { return; } // already gone -- nothing to do, never an error.
		if ( ! str_starts_with( $term->slug, $FIXTURE_SLUG_PREFIX ) || ! get_term_meta( $termId, $FIXTURE_META_KEY, true ) ) {
			throw new RuntimeException( "REFUSING_TO_DELETE_NON_FIXTURE_TERM:$termId (taxonomy=$taxonomy, slug={$term->slug}) -- missing the fixture slug prefix and/or the fixture meta marker; this guard exists because a real term was destroyed here once by accident" );
		}
		wp_delete_term( $termId, $taxonomy );
	};

	$syntheticTermId = null;
	try {
		wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
		Storage::guard();
		global $wpdb;
		$before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );

		// ============================================================ safety: the delete guard refuses a real term, proven BEFORE it is ever relied on below
		// Exactly the failure mode of the original incident: a real category's term_id ends up
		// in the variable a cleanup path deletes from. Calling the real guard against a real,
		// live category term (never deleted -- the guard throws before wp_delete_term() is
		// ever reached) proves it, rather than assuming it from reading the code.
		$industrialTermId = get_term_by( 'slug', 'industrial', 'psi_categoria' )->term_id;
		$guardThrew = false;
		try { $safeDeleteTestTerm( $industrialTermId, 'psi_categoria' ); }
		catch ( RuntimeException $e ) { $guardThrew = str_starts_with( $e->getMessage(), 'REFUSING_TO_DELETE_NON_FIXTURE_TERM:' ); }
		$assert( $guardThrew, 'safeDeleteTestTerm() refuses a real category term (no fixture slug/meta) instead of deleting it' );
		$assert( null !== get_term( $industrialTermId, 'psi_categoria' ) && ! is_wp_error( get_term( $industrialTermId, 'psi_categoria' ) ), 'Sanity: Industrial genuinely still exists after that refused call' );
		// Half-evidence (fixture slug alone, no fixture meta) must also be refused -- proves
		// the guard requires BOTH signals, never either alone.
		$halfFixture = wp_insert_term( $FIXTURE_SLUG_PREFIX . 'half-evidence-' . wp_generate_uuid4(), 'psi_categoria' );
		$halfFixtureId = (int) $halfFixture['term_id'];
		$guardThrew = false;
		try { $safeDeleteTestTerm( $halfFixtureId, 'psi_categoria' ); }
		catch ( RuntimeException $e ) { $guardThrew = str_starts_with( $e->getMessage(), 'REFUSING_TO_DELETE_NON_FIXTURE_TERM:' ); }
		$assert( $guardThrew, 'safeDeleteTestTerm() refuses a term with the fixture slug prefix but no fixture meta marker' );
		update_term_meta( $halfFixtureId, $FIXTURE_META_KEY, true );
		$safeDeleteTestTerm( $halfFixtureId, 'psi_categoria' ); // now carries both signals -- this call is expected to actually delete it.
		$deletedTerm = get_term( $halfFixtureId, 'psi_categoria' );
		$assert( ! $deletedTerm || is_wp_error( $deletedTerm ), 'Once both signals are present, safeDeleteTestTerm() does delete -- this fixture cleans itself up' );

		// ============================================================ CategoriesMigration: idempotent, correct
		$rerun = CategoriesMigration::run();
		$assert( array() === $rerun, 'CategoriesMigration::run() is a no-op once already applied (idempotent)' );
		$ref = new ReflectionClass( CategoriesMigration::class );
		$approved = $ref->getConstant( 'APPROVED_LEGACY_IDS' );
		$menuOrder = $ref->getConstant( 'MENU_ORDER' );
		$assert( 36 === count( $approved ), 'Fixture: exactly 36 approved legacy_ids (38 category-master.csv rows minus the 2 UNKNOWN-confidence ones, 25 and 26)' );
		$assert( 7 === count( $menuOrder ), 'Fixture: exactly 7 top-level roots carry an explicit menu order' );

		$termByLegacyId = array();
		foreach ( $approved as $legacyId => $name ) {
			$foundTermId = Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => 'category:' . $legacyId ) );
			if ( $foundTermId ) { $termByLegacyId[ $legacyId ] = $foundTermId; }
		}
		$assert( 34 === count( $termByLegacyId ), 'Fixture: 34 of the 36 approved legacy_ids have a real, already-imported WordPress term today (37/38 do not yet -- a real, pre-existing slug collision against 11/12, unrelated to this migration, never invented or fixed here)' );
		foreach ( $termByLegacyId as $legacyId => $termId ) {
			$assert( 'public' === get_term_meta( $termId, '_psi_public_state', true ), "Migration published category:$legacyId (term $termId)" );
		}
		foreach ( $menuOrder as $legacyId => $order ) {
			if ( ! isset( $termByLegacyId[ $legacyId ] ) ) { continue; }
			$assert( (int) get_term_meta( $termByLegacyId[ $legacyId ], '_psi_category_menu_order', true ) === $order, "Migration set the correct legacy header.php order for category:$legacyId" );
		}

		// ============================================================ 13: Identity stays green -- publishing is editorial, not identity drift
		$fresh = Planner::build( 'full' );
		$byKey = array_column( $fresh['entries'], null, 'entity_key' );
		foreach ( $termByLegacyId as $legacyId => $termId ) {
			$key = "category:$legacyId";
			$assert( isset( $byKey[ $key ] ), "Fixture: $key resolves in a freshly-built plan" );
			$assert( 'UNCHANGED' === Identity::prediction( $byKey[ $key ] ), "Newly-published category still predicts UNCHANGED, not CONFLICT: $key" );
		}
		// The one real, pre-existing exception: category:37/38 collide in slug with the
		// already-existing category:11/12 (same legacy name, different parent) -- a genuine,
		// evidence-based CONFLICT (get_term_by('slug', ...) collision on CREATE), present
		// before this session touched anything, and out of scope to resolve here (would mean
		// reopening the importer's identity/merge decisions).
		foreach ( array( 37, 38 ) as $legacyId ) {
			$assert( 'CONFLICT' === Identity::prediction( $byKey[ "category:$legacyId" ] ), "Pre-existing slug collision correctly still CONFLICT, never silently hidden: category:$legacyId" );
		}

		// ============================================================ 1/2/3: dynamic, not hardcoded, hide_empty=false
		$navSource = file_get_contents( dirname( __DIR__ ) . '/../../themes/psindustrial/inc/catalog-navigation.php' );
		$assert( ! str_contains( $navSource, "'industrial', 'comercial'" ), 'catalog_navigation() no longer hardcodes the category slug order array' );
		$assert( str_contains( $navSource, "get_terms(" ) && str_contains( $navSource, "'hide_empty' => false" ), 'catalog_navigation() still queries with hide_empty=false' );
		$assert( str_contains( $navSource, '_psi_category_menu_order' ), 'catalog_navigation() sources order from term meta, not a literal array' );

		$nav = \PSIndustrial\Theme\catalog_navigation();
		$categoryItems = array();
		foreach ( $nav as $item ) { $term = get_term_by( 'name', $item['label'], 'psi_categoria' ); if ( $term ) { $categoryItems[] = $item; } }
		// Built from the real, live term names for legacy_id 1..7 (never a hardcoded literal
		// copy of those names): several carried known mojibake encoding artifacts on accented
		// characters (e.g. category:5's name rendered as "ExplosiÃ³n" until
		// MojibakeContentMigration corrected it, 2026-09-24) -- a byte-identical DB round-trip
		// on both sides is what actually proves the ORDER property this asserts, staying
		// correct regardless of whether the underlying text is ever corrupted again, without a
		// literal-string comparison depending on that unrelated question either way.
		$expectedRootNames = array_map( static fn( $legacyId ) => get_term( $termByLegacyId[ $legacyId ] )->name, array( 1, 2, 3, 4, 5, 6, 7 ) );
		$assert( $expectedRootNames === array_column( $categoryItems, 'label' ), 'catalog_navigation() top-level categories appear in the exact legacy header.php order (legacy_id 1..7)' );

		// ============================================================ 4: a real zero-product category still appears
		$zeroProductRoot = get_term_by( 'slug', 'industrial', 'psi_categoria' );
		$assert( 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d", get_term( $zeroProductRoot )->term_taxonomy_id ) ), 'Fixture: Industrial genuinely has zero associated products (published or not) right now' );
		$assert( in_array( 'Industrial', array_column( $categoryItems, 'label' ), true ), 'A category with zero associated products still appears in catalog_navigation() -- hide_empty=false is real' );

		// ============================================================ 5/6: every link is a real term link, never a placeholder
		foreach ( $categoryItems as $item ) {
			$term = get_term_by( 'name', $item['label'], 'psi_categoria' );
			$assert( untrailingslashit( $item['url'] ) === untrailingslashit( get_term_link( $term ) ), 'Link is the real get_term_link() for: ' . $item['label'] );
			$assert( '#' !== $item['url'] && ! str_ends_with( $item['url'], '/#' ), 'Link is never a placeholder href=#: ' . $item['label'] );
		}

		$http = static function( string $target ): array {
			$url = str_starts_with( $target, 'http' ) ? $target : home_url( $target );
			$r = wp_remote_get( $url, array( 'redirection' => 0, 'timeout' => 10 ) );
			if ( is_wp_error( $r ) ) { return array( 'status' => 0, 'location' => '', 'body' => '' ); }
			return array( 'status' => wp_remote_retrieve_response_code( $r ), 'location' => wp_remote_retrieve_header( $r, 'location' ), 'body' => wp_remote_retrieve_body( $r ) );
		};

		// ============================================================ 7: hierarchy is preserved, never flattened
		$comercial = get_term_by( 'slug', 'comercial', 'psi_categoria' );
		$estacionamientos = get_term_by( 'slug', 'estacionamientos', 'psi_categoria' );
		$assert( $comercial && $estacionamientos, 'Fixture terms resolve: Comercial (root) and Estacionamientos (its real child)' );
		$assert( (int) $estacionamientos->parent === $comercial->term_id, 'Estacionamientos is genuinely a child of Comercial in real WordPress term data (not asserted, read)' );
		$assert( ! in_array( 'Estacionamientos', array_column( $nav, 'label' ), true ), 'catalog_navigation() (header mode) shows only top-level roots -- matches the legacy header.php dropdown exactly (7 flat items, no nesting), never arbitrarily flattens the taxonomy by also listing children there' );
		$rEstacionamientos = $http( get_term_link( $estacionamientos ) );
		$assert( 200 === $rEstacionamientos['status'], 'Child category archive renders 200 (got ' . $rEstacionamientos['status'] . ')' );
		// catalog_navigation(true)'s child-inclusion is conditioned on is_tax($taxonomy,
		// $term->term_id) -- true only while WordPress is actually rendering that specific
		// term's own page (catalog-sidebar.php's real, pre-existing use) -- so proving a real
		// child is reachable needs the real global query state a page load sets up, not a bare
		// out-of-request call, which would have is_tax() false throughout and make every child
		// look skipped regardless. Set up directly, exactly what WordPress itself does when
		// serving that page (verified against the real 200 response above), so this exercises
		// the same is_tax() every real visitor's page view relies on.
		global $wp_query;
		$savedQuery = $wp_query;
		$wp_query = new WP_Query( array( 'taxonomy' => 'psi_categoria', 'term' => $estacionamientos->slug ) );
		$assert( is_tax( 'psi_categoria', $estacionamientos->term_id ), 'Fixture: simulated query state genuinely reports is_tax() for Estacionamientos' );
		$onOwnPageNav = \PSIndustrial\Theme\catalog_navigation( true );
		$wp_query = $savedQuery;
		$assert( in_array( 'Estacionamientos', array_column( $onOwnPageNav, 'label' ), true ), 'catalog_navigation(all_categories=true) includes the real child while its own page is being rendered -- reachable via real WordPress structure, never hardcoded' );

		// ============================================================ 8/9: archives render correctly, with and without products
		$puertasSeccionales = get_term_by( 'slug', 'puertas-seccionales', 'psi_categoria' );
		$rWithProducts = $http( get_term_link( $puertasSeccionales ) );
		$assert( 200 === $rWithProducts['status'], 'Archive for a category with a real published product renders 200 (got ' . $rWithProducts['status'] . ')' );
		$assert( str_contains( $rWithProducts['body'], 'product-grid' ) || str_contains( $rWithProducts['body'], 'product-card' ), 'Archive with products renders the real product grid, not the empty-state message' );
		$rEmpty = $http( get_term_link( $zeroProductRoot ) );
		$assert( 200 === $rEmpty['status'], 'Archive for a category with zero products renders 200, never 404 for being empty (got ' . $rEmpty['status'] . ')' );
		$assert( str_contains( $rEmpty['body'], 'Todavía no hay productos publicados' ), 'Empty category archive shows the real, shared "no products published" message' );

		// ============================================================ 10/11: legacy URLs redirect 301 directly, no chains
		$rPhp = $http( '/industrial.php' );
		$assert( 301 === $rPhp['status'] && untrailingslashit( $rPhp['location'] ) === untrailingslashit( get_term_link( $zeroProductRoot ) ), 'Legacy industrial.php -> 301 -> real Industrial canonical (got ' . $rPhp['status'] . ' ' . $rPhp['location'] . ')' );
		$rNumericA = $http( '/productos/categoria/1/industrial/' );
		$assert( 301 === $rNumericA['status'] && untrailingslashit( $rNumericA['location'] ) === untrailingslashit( get_term_link( $zeroProductRoot ) ), 'Legacy /productos/categoria/1/industrial/ -> 301 -> real Industrial canonical (got ' . $rNumericA['status'] . ' ' . $rNumericA['location'] . ')' );
		$rNumericB = $http( '/1/categoria/industrial/' );
		$assert( 301 === $rNumericB['status'] && untrailingslashit( $rNumericB['location'] ) === untrailingslashit( get_term_link( $zeroProductRoot ) ), 'Legacy /1/categoria/industrial/ -> 301 -> real Industrial canonical (got ' . $rNumericB['status'] . ' ' . $rNumericB['location'] . ')' );
		$rBadSlug = $http( '/productos/categoria/1/this-slug-was-never-real/' );
		$assert( 301 === $rBadSlug['status'] && untrailingslashit( $rBadSlug['location'] ) === untrailingslashit( get_term_link( $zeroProductRoot ) ), 'The slug segment is decorative, exactly like the original legacy rewrite -- never validated against the current slug' );
		foreach ( array( $rPhp, $rNumericA, $rNumericB ) as $r ) {
			$destBasename = basename( (string) wp_parse_url( $r['location'], PHP_URL_PATH ) );
			$assert( null === LegacyUrls::resolve( '/' . $destBasename, true ), 'Canonical destination is not itself a redirect source (no chain): /' . $destBasename );
		}
		$assert( null === LegacyUrls::resolve( '/productos/categoria/9999/nonexistent/', true ), 'An unknown legacy category id resolves to null, never an invented destination' );
		$assert( null === LegacyUrls::resolve( '/productos/categoria/25/bumpers-semaforos-y-cepillos-para-rampa-niveladora/', true ), 'category:25 (UNKNOWN confidence, no WordPress term) resolves to null -- never invented' );
		$assert( null === LegacyUrls::resolve( '/productos/1/2/algo/', true ), 'The combined CategoriaId+MarcaId pattern is intentionally never handled -- a single term canonical cannot represent both, so no destination is guessed' );

		// ============================================================ 12: TermPolicy still protects a genuinely non-public term
		$synthetic = wp_insert_term( $FIXTURE_SLUG_PREFIX . 'categories-menu-termpolicy-' . wp_generate_uuid4(), 'psi_categoria' );
		$assert( ! is_wp_error( $synthetic ), 'Synthetic fixture term created' );
		$syntheticTermId = (int) $synthetic['term_id'];
		update_term_meta( $syntheticTermId, $FIXTURE_META_KEY, true );
		$assert( str_starts_with( get_term( $syntheticTermId )->slug, $FIXTURE_SLUG_PREFIX ) && get_term_meta( $syntheticTermId, $FIXTURE_META_KEY, true ), 'Sanity: the fixture carries both pieces of evidence safeDeleteTestTerm() will require before it may be deleted' );
		try {
			// Deliberately left at get_term_meta()'s real default (never set to 'public') --
			// exactly the state every OTHER not-yet-approved psi_categoria term is still in.
			$assert( ! TermPolicy::is_public( $syntheticTermId ), 'A term nothing has published is correctly NOT public' );
			$rSynthetic = $http( get_term_link( $syntheticTermId, 'psi_categoria' ) );
			$assert( 404 === $rSynthetic['status'], 'A non-public category archive still 404s (got ' . $rSynthetic['status'] . ') -- TermPolicy::template_redirect guard is untouched' );
			$navAfter = \PSIndustrial\Theme\catalog_navigation( true );
			$syntheticName = get_term( $syntheticTermId )->name;
			$assert( ! in_array( $syntheticName, array_column( $navAfter, 'label' ), true ), 'A non-public term never appears in catalog_navigation(), including all_categories mode' );
			// current_user_can()+CLI would normally bypass the public filter (see
			// IdentityHashRebaseline/CategoriesMigration docblocks) -- explicitly simulate an
			// anonymous, non-admin request instead, the real condition TermPolicy protects.
			wp_set_current_user( 0 );
			$anonTerms = get_terms( array( 'taxonomy' => 'psi_categoria', 'hide_empty' => false, 'include' => array( $syntheticTermId ) ) );
			wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
			$assert( is_array( $anonTerms ) && array() === $anonTerms, 'An anonymous get_terms() call never returns a non-public term' );
		} finally {
			$safeDeleteTestTerm( $syntheticTermId, 'psi_categoria' );
		}

		$after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
		$assert( $before === $after, 'This entire suite (including live HTTP requests and the migration re-run) creates/deletes only its own synthetic fixture term -- net zero posts/terms' );

		$export( 'dynamic-categories-menu-tests.json', array( 'passed' => true, 'checks' => $checks ) );
		echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
	} catch ( Throwable $error ) {
		$safeDeleteTestTerm( $syntheticTermId, 'psi_categoria' );
		$export( 'dynamic-categories-menu-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
	}
})();

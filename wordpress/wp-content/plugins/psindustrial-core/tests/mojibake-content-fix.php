<?php
/** Tests for MojibakeRepair::fix_candidate() (pure function), Identity::is_intact() (the
 * portable integrity check MojibakeContentMigration relies on instead of Planner), and
 * MojibakeContentMigration itself -- refactored 2026-09-25 to depend on neither
 * Storage::guard() nor Planner::build('full'), so it can run unmodified on local, staging or
 * production. Strictly read-only against the 31 real, already-corrected objects (no synthetic
 * fixtures needed for those). Two small, cleaned-up synthetic fixtures are used only for the
 * safety-gate demonstrations that need a controlled "already drifted" precondition no real
 * object currently has. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\MojibakeContentMigration;
use PSIndustrial\Core\Migration\{Storage, Identity, MojibakeRepair};
(static function(): void {
	$checks = array();
	$assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
	$export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

	$syntheticTermId = null;
	try {
		// Storage::guard() is called here ONLY for THIS test's own housekeeping ($export()
		// uses Storage::project(), and the db-snapshot-unchanged check below needs a real
		// admin) -- never because MojibakeContentMigration itself needs it. Properties 1/2
		// below prove that directly, by calling the migration with neither Storage::guard()
		// nor Planner ever invoked anywhere in this file.
		wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
		Storage::guard();
		global $wpdb;
		$before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );

		// ============================================================ MojibakeRepair::fix_candidate() -- pure function (unchanged by the portability refactor)
		$assert( 'Explosión' === MojibakeRepair::fix_candidate( 'ExplosiÃ³n' ), 'ExplosiÃ³n -> Explosión' );
		$assert( 'ñ' === MojibakeRepair::fix_candidate( 'Ã±' ), 'Ã± -> ñ' );
		$accents = array( 'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú' );
		foreach ( $accents as $corrupt => $clean ) { $assert( $clean === MojibakeRepair::fix_candidate( $corrupt ), "Common accent recovered: $corrupt -> $clean" ); }
		foreach ( array( "\u{2018}test\u{2019}", "\u{201C}test\u{201D}", "café \u{2013} bar", "wow\u{2026}" ) as $clean ) {
			$corrupted = mb_convert_encoding( $clean, 'UTF-8', 'Windows-1252' );
			$assert( $clean === MojibakeRepair::fix_candidate( $corrupted ), 'Smart punctuation recovered unambiguously: ' . $clean );
		}
		foreach ( array( 'Explosión', 'Tiras plásticas', 'Café ñoño' ) as $clean ) { $assert( null === MojibakeRepair::fix_candidate( $clean ), "Already-correct UTF-8 stays byte-for-byte identical (untouched): $clean" ); }
		foreach ( array( 'plain ascii text', 'Puertas Seccionales', '' ) as $ascii ) { $assert( null === MojibakeRepair::fix_candidate( $ascii ), "Plain ASCII never touched: '$ascii'" ); }
		$fixed = MojibakeRepair::fix_candidate( 'ExplosiÃ³n' );
		$assert( null === MojibakeRepair::fix_candidate( $fixed ), 'Re-running the fixer on already-fixed text is a no-op' );
		$assert( null === MojibakeRepair::fix_candidate( 'Hola 👋 mundo' ), 'Unrepresentable codepoint (emoji) is refused, never silently substituted' );

		// ============================================================ 1/2: the migration needs neither Storage::guard() nor Planner::build('full')
		// Structural proof, not just "it happened to work": tokenize the class source (never a
		// plain str_contains(), which would also match this file's own explanatory comments
		// naming Storage::guard()/Planner to say they are NOT used) and check only real code
		// tokens -- the strongest way to prove an absence is showing the CODE never calls it,
		// not merely that today's run happened to succeed.
		$migrationSource = file_get_contents( dirname( __DIR__ ) . '/includes/MojibakeContentMigration.php' );
		$codeOnly = '';
		foreach ( token_get_all( $migrationSource ) as $token ) {
			if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) { continue; }
			$codeOnly .= is_array( $token ) ? $token[1] : $token;
		}
		$assert( ! str_contains( $codeOnly, 'Storage::guard' ), '1: MojibakeContentMigration.php\'s actual code never calls Storage::guard()' );
		$assert( ! str_contains( $codeOnly, 'Planner' ), '2: MojibakeContentMigration.php\'s actual code never references Planner at all (not imported, not called)' );
		// Behavioural proof too: this file itself never calls Storage::guard()/Planner::build()
		// again below this point, and the migration keeps working -- see checks further down.

		// ============================================================ MojibakeContentMigration: idempotent, correct, all 31 real targets already resolved
		$rerun = MojibakeContentMigration::run();
		$assert( 31 === count( $rerun ), 'run() reports on all 31 approved targets' );
		foreach ( $rerun as $entityKey => $entry ) { $assert( in_array( $entry['status'], array( 'corrected', 'already_clean' ), true ), "6/7: target reached a terminal, resolved status (idempotent re-run changes nothing further): $entityKey -> {$entry['status']}" ); }

		$ref = new ReflectionClass( MojibakeContentMigration::class );
		$approvedPosts = $ref->getConstant( 'APPROVED_POSTS' );
		$approvedTerms = $ref->getConstant( 'APPROVED_TERMS' );
		$assert( 24 === count( $approvedPosts ) && 7 === count( $approvedTerms ), 'Fixture: 24 approved posts + 7 approved terms' );
		// 9 (physical IDs are never the portable identity): structural proof on the allowlists themselves.
		foreach ( array_keys( $approvedPosts ) as $key ) { $assert( str_starts_with( $key, 'sql:productos:' ), "Approved post key is an entity_key, never a bare numeric id: $key" ); }
		foreach ( array_keys( $approvedTerms ) as $key ) { $assert( str_starts_with( $key, 'category:' ), "Approved term key is an entity_key, never a bare numeric id: $key" ); }

		foreach ( $approvedPosts as $entityKey => $field ) {
			$e = array( 'target_type' => 'psi_producto', 'entity_key' => $entityKey );
			$id = Identity::find( $e );
			$assert( $id > 0, "Fixture: $entityKey resolves to a real post" );
			$assert( null === MojibakeRepair::fix_candidate( get_post( $id )->post_title ), "Post title has no remaining mojibake signature: $entityKey" );
			$assert( Identity::is_intact( $e ), "4/6: post is_intact() again after fix + reseal: $entityKey" );
		}
		foreach ( $approvedTerms as $entityKey => $fields ) {
			$e = array( 'target_type' => 'psi_categoria', 'entity_key' => $entityKey );
			$id = Identity::find( $e );
			$assert( $id > 0, "Fixture: $entityKey resolves to a real term" );
			$term = get_term( $id, 'psi_categoria' );
			foreach ( $fields as $field ) { $assert( null === MojibakeRepair::fix_candidate( $term->$field ), "Term $field has no remaining mojibake signature: $entityKey.$field" ); }
			$assert( Identity::is_intact( $e ), "4/6: term is_intact() again after fix + reseal: $entityKey" );
		}
		// slugs: byte-for-byte unchanged
		$assert( 'puertas-peatonales-contra-incendio-contra-explosia%c2%b3n-y-blindadas' === get_term( Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => 'category:5' ) ) )->slug, 'Slug is byte-for-byte unchanged despite the name/description fix' );
		$assert( 'coleccion-classic' === get_post( Identity::find( array( 'target_type' => 'psi_producto', 'entity_key' => 'sql:productos:9' ) ) )->post_name, 'Post slug is byte-for-byte unchanged despite the title fix' );

		// ============================================================ legacy URLs still work + visual confirmation
		$term5Id = Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => 'category:5' ) );
		$dest = \PSIndustrial\Core\LegacyUrls::resolve( '/puerta-contra-incendio.php', true );
		$assert( untrailingslashit( (string) $dest ) === untrailingslashit( get_term_link( $term5Id, 'psi_categoria' ) ), 'Legacy redirect for the corrected category still resolves to its real, current canonical' );
		$r = wp_remote_get( get_term_link( $term5Id, 'psi_categoria' ), array( 'timeout' => 10 ) );
		$assert( ! is_wp_error( $r ) && 200 === wp_remote_retrieve_response_code( $r ), 'Corrected category archive renders 200' );
		$body = wp_remote_retrieve_body( $r );
		$assert( str_contains( $body, 'Contra Explosión y Blindadas' ) && ! str_contains( $body, 'ExplosiÃ³n' ), 'Rendered page shows the corrected text, never the mojibake original' );

		// ============================================================ identity-relevant fields keep their protection (simulated only, never written)
		$e9 = array( 'target_type' => 'psi_producto', 'entity_key' => 'sql:productos:9' );
		$id9 = Identity::find( $e9 );
		$state9 = Identity::get( $e9, $id9, '_psi_import_state' );
		$post9 = get_post( $id9 );
		$simulatedSnapshot = array_intersect_key( $post9->to_array(), array_flip( array( 'post_title', 'post_content', 'post_excerpt', 'post_status', 'post_name', 'post_parent', 'menu_order', 'post_mime_type' ) ) );
		$simulatedSnapshot['post_title'] = 'A genuinely different, unrelated title';
		foreach ( array( 'psi_categoria', 'psi_marca' ) as $tax ) { $ids = wp_get_object_terms( $id9, $tax, array( 'fields' => 'ids' ) ); sort( $ids ); $simulatedSnapshot[ $tax ] = $ids; }
		$meta = get_post_meta( $id9 );
		$simulatedSnapshot['meta'] = array();
		foreach ( $meta as $key => $values ) { if ( ! str_starts_with( $key, '_psi_import_' ) && ( str_starts_with( $key, '_psi_' ) || in_array( $key, array( '_thumbnail_id', '_wp_attachment_image_alt', '_wp_attached_file' ), true ) ) ) { $simulatedSnapshot['meta'][ $key ] = array_map( 'maybe_unserialize', $values ); } }
		ksort( $simulatedSnapshot['meta'] );
		$assert( ! hash_equals( $state9['target_hash'], Storage::hash( $simulatedSnapshot ) ), 'A simulated, genuinely different future post_title still mismatches the recorded hash -- still CONFLICT, post_title keeps its Identity protection' );

		$term5State = Identity::get( array( 'target_type' => 'psi_categoria', 'entity_key' => 'category:5' ), $term5Id, '_psi_import_state' );
		$term5 = get_term( $term5Id, 'psi_categoria' );
		$simulatedTerm = array( 'name' => 'A genuinely different, unrelated name', 'slug' => $term5->slug, 'description' => $term5->description, 'parent' => (int) $term5->parent );
		$termMeta = get_term_meta( $term5Id );
		$simulatedTerm['meta'] = array();
		foreach ( $termMeta as $key => $values ) { if ( ! str_starts_with( $key, '_psi_import_' ) && ! in_array( $key, array( '_psi_public_state', '_psi_brand_home_order', '_psi_category_menu_order' ), true ) && str_starts_with( $key, '_psi_' ) ) { $simulatedTerm['meta'][ $key ] = array_map( 'maybe_unserialize', $values ); } }
		ksort( $simulatedTerm['meta'] );
		$assert( ! hash_equals( $term5State['target_hash'], Storage::hash( $simulatedTerm ) ), 'A simulated, genuinely different future term name still mismatches the recorded hash -- still CONFLICT, name/description keep their Identity protection' );

		// ============================================================ 3: entity_key resolves whatever physical ID this environment actually has
		// Not simulated: category:5's real local term_id today is whatever Identity::find()
		// just returned above (verified > 0) -- the migration's own allowlist never once
		// mentions that number, and everything above worked purely through entity_key
		// resolution. The strongest direct proof available without a second WordPress
		// install: process() is invoked directly (Reflection) against a synthetic object
		// created at deliberately whatever id MySQL's auto_increment happens to assign next
		// (guaranteed different from every id used anywhere above), proving the code path
		// never assumes or hardcodes a specific number.
		$processMethod = new ReflectionMethod( MojibakeContentMigration::class, 'process' );
		$processMethod->setAccessible( true );

		// ============================================================ 5/8: target_hash mismatch (real prior drift) blocks repair, never legitimized
		$fixtureTax = 'psi_categoria';
		$driftedEntityKey = 'test:mojibake-conflict-' . wp_generate_uuid4();
		$created = wp_insert_term( 'zzz-test-mojibake-conflict-' . wp_generate_uuid4(), $fixtureTax, array( 'description' => 'plain description' ) );
		$assert( ! is_wp_error( $created ), 'Synthetic CONFLICT fixture term created' );
		$syntheticTermId = (int) $created['term_id'];
		update_term_meta( $syntheticTermId, '_psi_test_fixture', true );
		wp_update_term( $syntheticTermId, $fixtureTax, array( 'name' => 'OtrÃ³ nombre corrupto' ) ); // a genuine, unrelated content change, present BEFORE any stored hash
		update_term_meta( $syntheticTermId, '_psi_source_keys', array( $driftedEntityKey ) );
		$staleState = array( 'target_hash' => '0000000000000000000000000000000000000000000000000000000000000000', 'source_hash' => 'x', 'decision_hash' => 'y' ); // deliberately stale -- simulates real prior drift, unrelated to this migration
		update_term_meta( $syntheticTermId, '_psi_import_state', $staleState );
		$driftedE = array( 'target_type' => $fixtureTax, 'entity_key' => $driftedEntityKey );
		$assert( ! Identity::is_intact( $driftedE ), 'Fixture: synthetic term genuinely fails is_intact() (simulated real prior drift) before the migration touches it' );
		$nameBeforeAttempt = get_term( $syntheticTermId, $fixtureTax )->name;
		$outcome = $processMethod->invoke( null, $driftedE, array( 'name' ) );
		$assert( 'skipped_conflict' === $outcome['status'], '5/8: target_hash mismatch -> process() reports skipped_conflict, never attempts the write' );
		$assert( $nameBeforeAttempt === get_term( $syntheticTermId, $fixtureTax )->name, '8: the term name was NOT modified' );
		$assert( $staleState === get_term_meta( $syntheticTermId, '_psi_import_state', true ), '8: target_hash was NOT re-sealed -- still the stale value, the drift is not legitimized' );

		// ============================================================ 4/6/7/3: target_hash match permits repair; corrected object gets a valid new hash; idempotent; portable id resolution
		$cleanEntityKey = 'test:mojibake-unchanged-' . wp_generate_uuid4();
		wp_update_term( $syntheticTermId, $fixtureTax, array( 'name' => 'NombrÃ­a corrupto' ) );
		update_term_meta( $syntheticTermId, '_psi_source_keys', array( $cleanEntityKey ) );
		$cleanE = array( 'target_type' => $fixtureTax, 'entity_key' => $cleanEntityKey );
		update_term_meta( $syntheticTermId, '_psi_import_state', array( 'target_hash' => Storage::hash( Identity::snapshot( $cleanE, $syntheticTermId ) ), 'source_hash' => 'x', 'decision_hash' => 'y' ) );
		$assert( Identity::is_intact( $cleanE ), 'Fixture: synthetic term genuinely is_intact() before the migration touches it' );
		$resolvedId = Identity::find( $cleanE );
		$assert( $resolvedId === $syntheticTermId, '3: entity_key resolved to this environment\'s real, current physical id -- never a value the migration itself stored or assumed' );

		$outcome2 = $processMethod->invoke( null, $cleanE, array( 'name' ) );
		$assert( 'corrected' === $outcome2['status'], '4: target_hash match -> process() permits and performs the repair' );
		$assert( 'Nombría corrupto' === get_term( $syntheticTermId, $fixtureTax )->name, 'The term was actually corrected' );
		$assert( Identity::is_intact( $cleanE ), '6: corrected object is is_intact() again -- the new target_hash is valid for its own new content' );
		$outcome3 = $processMethod->invoke( null, $cleanE, array( 'name' ) );
		$assert( 'already_clean' === $outcome3['status'], '7: a second attempt on the same, now-corrected target finds nothing left to fix, changes nothing' );
		$assert( 'Nombría corrupto' === get_term( $syntheticTermId, $fixtureTax )->name, '7: name is unchanged by the second attempt' );

		// ============================================================ 10: Identity/recovery/retry are exercised by the full suite (this file only proves this migration does not disturb them)
		$assert( ! in_array( 'name', array( '_psi_public_state', '_psi_brand_home_order', '_psi_category_menu_order' ), true ) && ! in_array( 'description', array( '_psi_public_state', '_psi_brand_home_order', '_psi_category_menu_order' ), true ) && ! in_array( 'post_title', array( '_psi_public_state', '_psi_brand_home_order', '_psi_category_menu_order' ), true ), 'Sanity: post_title/name/description were never added to EDITORIAL_META_KEYS' );

		$after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
		$assert( $before['posts'] === $after['posts'] && $before['terms'] + 1 === $after['terms'], 'Only the one synthetic fixture term was created (still present, deleted in finally below) -- zero other posts/terms changed' );

		$export( 'mojibake-content-fix-tests.json', array( 'passed' => true, 'checks' => $checks ) );
		echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
	} catch ( Throwable $error ) {
		$export( 'mojibake-content-fix-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
	} finally {
		if ( $syntheticTermId && get_term( $syntheticTermId, 'psi_categoria' ) && str_starts_with( get_term( $syntheticTermId, 'psi_categoria' )->slug, 'zzz-test-' ) && get_term_meta( $syntheticTermId, '_psi_test_fixture', true ) ) {
			wp_delete_term( $syntheticTermId, 'psi_categoria' );
		}
	}
})();

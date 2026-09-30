<?php
/** Tests for the 2026-09-23 Identity::snapshot() fix and its companion
 * IdentityHashRebaseline migration -- the architectural separation of IDENTITY/importer-
 * owned immutable state from EDITORIAL/post-import mutable state for psi_categoria/psi_marca
 * terms (migration/Identity.php EDITORIAL_META_KEYS: _psi_public_state,
 * _psi_brand_home_order). Empirically motivated: reverting either key alone on the real
 * brand:2 (Wayne Dalton) term did NOT restore its recorded target_hash; only reverting both
 * together did (see includes/IdentityHashRebaseline.php's docblock) -- so both are excluded,
 * named explicitly, never inferred from a blanket "_psi_* is presentational" rule.
 *
 * Proves four properties, each either from real data (read-only) or a synthetic, cleaned-up
 * fixture (never real catalogue data):
 *  1. A term whose ONLY changes since import are within EDITORIAL_META_KEYS predicts
 *     UNCHANGED, not CONFLICT (real: all 12 psi_marca brand terms).
 *  2. EDITORIAL_META_KEYS are structurally absent from Identity::snapshot()'s hashed meta,
 *     even when genuinely set to a non-default value on a real term (real: brand:2).
 *  3. A genuinely identity-relevant field (name, _psi_logo_id) remains part of the hash, and
 *     changing it still produces a mismatch against the recorded target_hash -- i.e. still
 *     CONFLICT. The fix is narrow, not a blanket weakening (real: brand:2, simulated only).
 *  4. IdentityHashRebaseline never launders MIXED drift: a term with both an editorial change
 *     and a genuine non-editorial change is left exactly alone, target_hash untouched
 *     (synthetic fixture, created and deleted in this test only).
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Identity,Planner};
use PSIndustrial\Core\IdentityHashRebaseline;
(static function(): void {
	$checks = array();
	$assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
	$export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

	try {
		wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
		Storage::guard();
		global $wpdb;
		$before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );

		// ============================================================ migration already applied earlier, idempotent
		$rerun = IdentityHashRebaseline::run();
		$assert( array() === $rerun, 'IdentityHashRebaseline::run() is a no-op once already applied (idempotent)' );
		$assert( (int) get_option( 'psi_identity_hash_rebaseline_version', 0 ) >= IdentityHashRebaseline::VERSION, 'Migration version recorded' );

		// ============================================================ 1: real brand terms genuinely predict UNCHANGED, not CONFLICT
		$fresh = Planner::build( 'full' );
		$byKey = array_column( $fresh['entries'], null, 'entity_key' );
		for ( $legacyId = 1; $legacyId <= 12; $legacyId++ ) {
			$key = "brand:$legacyId";
			$assert( isset( $byKey[ $key ] ), "Fixture: $key resolves in a freshly-built plan" );
			$assert( 'UNCHANGED' === Identity::prediction( $byKey[ $key ] ), "Editorial-only brand term predicts UNCHANGED, not CONFLICT: $key" );
		}

		// ============================================================ 2/3: brand:2 (Wayne Dalton) -- structural proof
		$e2 = $byKey['brand:2'];
		$id2 = Identity::find( $e2 );
		$assert( 'public' === get_term_meta( $id2, '_psi_public_state', true ), 'Fixture: brand:2 is genuinely, currently public (a real editorial change, not a hypothetical)' );
		$homeOrder = get_term_meta( $id2, '_psi_brand_home_order', true );
		$assert( '' !== $homeOrder && null !== $homeOrder, 'Fixture: brand:2 genuinely has a _psi_brand_home_order value set (a real editorial change, not a hypothetical)' );

		$snap2 = Identity::snapshot( $e2, $id2 );
		$assert( ! array_key_exists( '_psi_public_state', $snap2['meta'] ), 'EDITORIAL: _psi_public_state is structurally absent from the hashed meta, despite being genuinely set to a non-default value' );
		$assert( ! array_key_exists( '_psi_brand_home_order', $snap2['meta'] ), 'EDITORIAL: _psi_brand_home_order is structurally absent from the hashed meta, despite being genuinely set' );
		$logoId = (int) get_term_meta( $id2, '_psi_logo_id', true );
		$assert( $logoId > 0 && array_key_exists( '_psi_logo_id', $snap2['meta'] ) && (int) $snap2['meta']['_psi_logo_id'][0] === $logoId, 'IDENTITY: _psi_logo_id (real brand content, never editorial) remains part of the hashed meta' );

		$state2 = Identity::get( $e2, $id2, '_psi_import_state' );
		$recordedHash2 = $state2['target_hash'];
		$assert( hash_equals( $recordedHash2, Storage::hash( $snap2 ) ), 'Sanity: brand:2 live snapshot matches its recorded target_hash right now (rebaselined correctly)' );

		// Simulated only -- never written. Reconstructs Identity::snapshot()'s CURRENT (fixed)
		// algorithm with exactly one additional field changed on top of brand:2's real live
		// state, to prove that field remains identity-relevant going forward.
		$rebuild = static function( array $valueOverrides, array $metaOverrides ) use ( $e2, $id2 ): array {
			$o = get_term( $id2, $e2['target_type'] );
			$value = array( 'name' => $o->name, 'slug' => $o->slug, 'description' => $o->description, 'parent' => (int) $o->parent );
			foreach ( $valueOverrides as $k => $v ) { $value[ $k ] = $v; }
			$meta = get_term_meta( $id2 );
			foreach ( $metaOverrides as $k => $v ) { $meta[ $k ] = array( $v ); }
			$value['meta'] = array();
			foreach ( $meta as $key => $values ) {
				if ( ! str_starts_with( $key, '_psi_import_' ) && ! in_array( $key, array( '_psi_public_state', '_psi_brand_home_order' ), true ) && str_starts_with( $key, '_psi_' ) ) {
					$value['meta'][ $key ] = array_map( 'maybe_unserialize', $values );
				}
			}
			ksort( $value['meta'] );
			return $value;
		};
		$nameChanged = Storage::hash( $rebuild( array( 'name' => 'Wayne Dalton TAMPERED' ), array() ) );
		$assert( ! hash_equals( $recordedHash2, $nameChanged ), 'IDENTITY: a simulated name change still mismatches the recorded hash -- still CONFLICT, never silently tolerated' );
		$logoChanged = Storage::hash( $rebuild( array(), array( '_psi_logo_id' => $logoId + 9999 ) ) );
		$assert( ! hash_equals( $recordedHash2, $logoChanged ), 'IDENTITY: a simulated _psi_logo_id change still mismatches the recorded hash -- still CONFLICT, never silently tolerated' );
		$publicStateReverted = Storage::hash( $rebuild( array(), array( '_psi_public_state' => 'review' ) ) );
		$assert( hash_equals( $recordedHash2, $publicStateReverted ), 'EDITORIAL: forcing _psi_public_state back to review changes nothing -- confirms it is genuinely excluded, not just coincidentally absent' );

		// ============================================================ 4: mixed drift is never laundered (synthetic fixture, cleaned up)
		$testTax = 'psi_categoria';
		$created = wp_insert_term( 'zzz-test-identity-rebaseline-safety-' . wp_generate_uuid4(), $testTax, array( 'description' => 'original description' ) );
		$assert( ! is_wp_error( $created ), 'Synthetic fixture term created' );
		$termId = (int) $created['term_id'];
		try {
			$term = get_term( $termId, $testTax );
			$recordedHash = Storage::hash( array( 'name' => $term->name, 'slug' => $term->slug, 'description' => 'original description', 'parent' => 0, 'meta' => array( '_psi_public_state' => array( 'review' ) ) ) );
			update_term_meta( $termId, '_psi_import_state', array( 'target_hash' => $recordedHash, 'source_hash' => 'x', 'decision_hash' => 'y' ) );

			// Mixed drift: an editorial change (public_state) AND a genuine, non-editorial
			// change (description) -- both after the "recorded" snapshot above.
			update_term_meta( $termId, '_psi_public_state', 'public' );
			wp_update_term( $termId, $testTax, array( 'description' => 'GENUINELY CHANGED description' ) );

			$ref = new ReflectionMethod( IdentityHashRebaseline::class, 'rebaseline_term' );
			$ref->setAccessible( true );
			$result = $ref->invoke( null, $testTax, $termId );
			$assert( null === $result, 'Mixed drift (editorial + genuine content change) is correctly left untouched by rebaseline_term(), never laundered' );

			$stateAfter = get_term_meta( $termId, '_psi_import_state', true );
			$assert( hash_equals( $recordedHash, $stateAfter['target_hash'] ), 'target_hash is provably unchanged when non-editorial drift is also present' );

			// Contrast: pure editorial-only drift on the SAME term (revert the description,
			// keep public_state changed) IS safely rebaselined.
			wp_update_term( $termId, $testTax, array( 'description' => 'original description' ) );
			$result2 = $ref->invoke( null, $testTax, $termId );
			$assert( is_string( $result2 ), 'Pure editorial-only drift (once the non-editorial field is back to its recorded value) IS rebaselined' );
			$stateAfter2 = get_term_meta( $termId, '_psi_import_state', true );
			$assert( ! hash_equals( $recordedHash, $stateAfter2['target_hash'] ), 'target_hash was actually updated for the pure-editorial-drift case' );
		} finally {
			wp_delete_term( $termId, $testTax );
		}

		$after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
		$assert( $before === $after, 'This suite creates/deletes only its own synthetic fixture term -- net zero posts/terms' );

		$export( 'identity-editorial-metadata-tests.json', array( 'passed' => true, 'checks' => $checks ) );
		echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
	} catch ( Throwable $error ) {
		if ( isset( $termId, $testTax ) && get_term( $termId, $testTax ) ) { wp_delete_term( $termId, $testTax ); }
		$export( 'identity-editorial-metadata-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
	}
})();

<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

use PSIndustrial\Core\Migration\{Identity, Storage};

/**
 * One-time, idempotent correction made necessary by the 2026-09-23 fix to
 * Identity::snapshot(): psi_categoria/psi_marca term snapshots now exclude
 * Identity::EDITORIAL_META_KEYS (_psi_public_state, _psi_brand_home_order) from the
 * hashed structure, so that a human's editorial change (publishing a term, setting its
 * Home order) stops being reported as CONFLICT the way a real content edit correctly is.
 *
 * Excluding a key from a hash changes the hash. Every term's _psi_import_state.target_hash
 * already on file was computed against the OLD structure (editorial keys included) --
 * against the NEW structure it will never match again, even for a term nothing has ever
 * touched. This migration re-stamps target_hash under the new structure, but ONLY where
 * it can prove the term's non-editorial fields are unchanged since import: it rebuilds the
 * exact state the importer originally wrote (migration/Runner.php:306 always creates a term
 * with _psi_public_state='review'; _psi_brand_home_order is never written by the importer,
 * only by BrandsMigration, so its creation-time state is always "absent") and hashes that
 * under the OLD (pre-fix) structure. A match proves every OTHER field -- name, slug,
 * description, parent, every other _psi_* meta key -- is identical to what was imported, so
 * the only possible drift is inside the two now-editorial keys, and re-stamping is safe. A
 * mismatch proves some non-editorial field genuinely changed since import; target_hash is
 * left untouched and the term correctly keeps reporting CONFLICT. This migration never
 * launders a real conflict, and never writes anything for a term that never went through
 * the importer (scoped to terms carrying _psi_import_state).
 */
final class IdentityHashRebaseline {
	public const VERSION = 1;

	public static function boot(): void {
		// admin_init, not init: unlike BrandsMigration this writes _psi_import_state, which
		// Identity::boot()'s metadata filter rejects unless the acting user already has
		// psi_manage_migration (migration/Identity.php:15) -- protecting that field from being
		// forged by an unprivileged write is the whole point of the filter, so this migration
		// must run inside a real, already-authenticated wp-admin request (exactly the trust
		// level the importer's own writes already require) rather than on the front end, where
		// no such user would ever be present to satisfy the check.
		add_action( 'admin_init', array( self::class, 'run' ) );
	}

	/** @return string[] human-readable log of terms actually rebaselined (empty if none/already applied). */
	public static function run(): array {
		if ( (int) get_option( 'psi_identity_hash_rebaseline_version', 0 ) >= self::VERSION ) { return array(); }
		$changes = array();
		foreach ( array( 'psi_categoria', 'psi_marca' ) as $taxonomy ) {
			$termIds = get_terms( array(
				'taxonomy'           => $taxonomy,
				'hide_empty'         => false,
				'psi_include_review' => true,
				'fields'             => 'ids',
				'meta_key'           => '_psi_import_state',
			) );
			if ( is_wp_error( $termIds ) ) { continue; }
			foreach ( $termIds as $termId ) {
				$result = self::rebaseline_term( $taxonomy, (int) $termId );
				if ( $result ) { $changes[] = $result; }
			}
		}
		update_option( 'psi_identity_hash_rebaseline_version', self::VERSION, false );
		if ( $changes ) { error_log( 'psindustrial-core IdentityHashRebaseline v' . self::VERSION . ': ' . implode( '; ', $changes ) ); }
		return $changes;
	}

	private static function rebaseline_term( string $taxonomy, int $termId ): ?string {
		$e     = array( 'target_type' => $taxonomy );
		$state = Identity::get( $e, $termId, '_psi_import_state' );
		if ( ! is_array( $state ) || empty( $state['target_hash'] ) ) { return null; }

		$reconstructedCreationHash = Storage::hash( self::creation_time_snapshot( $taxonomy, $termId ) );
		if ( ! hash_equals( $state['target_hash'], $reconstructedCreationHash ) ) {
			// A non-editorial field also drifted since import (or drifted instead) -- leave
			// target_hash untouched; the term correctly keeps reporting CONFLICT.
			return null;
		}

		$newHash = Storage::hash( Identity::snapshot( $e, $termId ) );
		if ( hash_equals( $newHash, $state['target_hash'] ) ) { return null; } // nothing to change

		$state['target_hash'] = $newHash;
		Identity::set( $e, $termId, '_psi_import_state', $state );
		return "term $termId ($taxonomy): target_hash rebaselined for editorial-excluded structure";
	}

	/**
	 * The term exactly as the importer originally left it, regardless of any editorial change
	 * made since -- i.e. Identity::snapshot()'s pre-2026-09-23 algorithm (editorial keys still
	 * part of the hashed meta), evaluated against the term's current non-editorial fields but
	 * with _psi_public_state forced to 'review' and _psi_brand_home_order removed, the two
	 * values migration/Runner.php guarantees for every term at creation. Frozen here for this
	 * one-time comparison only; never used for anything else, and never how prediction() works
	 * going forward -- that is Identity::snapshot()'s job now.
	 */
	private static function creation_time_snapshot( string $taxonomy, int $id ): array {
		$o = get_term( $id, $taxonomy );
		if ( ! $o || is_wp_error( $o ) ) { throw new \RuntimeException( 'TARGET_MISSING' ); }
		$value = array( 'name' => $o->name, 'slug' => $o->slug, 'description' => $o->description, 'parent' => (int) $o->parent );
		$meta  = get_term_meta( $id );
		$meta['_psi_public_state'] = array( 'review' );
		unset( $meta['_psi_brand_home_order'] );
		$value['meta'] = array();
		foreach ( $meta as $key => $values ) {
			if ( ! str_starts_with( $key, '_psi_import_' ) && str_starts_with( $key, '_psi_' ) ) {
				$value['meta'][ $key ] = array_map( 'maybe_unserialize', $values );
			}
		}
		ksort( $value['meta'] );
		return $value;
	}
}

<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

use PSIndustrial\Core\Migration\{Identity, MojibakeRepair, Storage};

/**
 * Small, versioned, idempotent, Identity-safe correction of legacy mojibake (UTF-8 text once
 * misread as Windows-1252 and re-encoded, e.g. "ExplosiÃ³n" -> "Explosión") in imported content
 * -- approved 2026-09-24 after a full read-only audit (32 candidates, 0 ambiguous, 0
 * hardcoded-in-code, charset already utf8mb4 throughout).
 *
 * PORTABLE BY DESIGN (2026-09-25 refactor): the corrupted content exists locally today and
 * will exist on staging, then production, once each imports it -- this must run correctly,
 * unmodified, on any of them. It depends on nothing environment-specific:
 *  - never Storage::guard() (local-dev-only by design -- see Storage::guard() itself; this
 *    migration was originally gated by it for Planner::build('full'), which the refactor
 *    below removes the need for entirely, not works around);
 *  - never Planner::build('full') or any Sources method / legacy source file;
 *  - never a hardcoded post_id/term_id -- every target is named by entity_key
 *    (APPROVED_POSTS/APPROVED_TERMS below) and resolved at runtime via Identity::find(),
 *    exactly like BrandsMigration/CategoriesMigration already do for their own content.
 * The only environment-specific values are numeric ids, and those are never stored anywhere
 * in this file -- resolved fresh, per environment, every run.
 *
 * SAFETY (the actual point of this migration, not incidental to it): correcting content and
 * then blindly re-sealing _psi_import_state.target_hash would legitimize ANY prior drift on
 * that object, mojibake-related or not -- exactly what this must never do. So for every
 * target, in order:
 *  1. resolve the real object via Identity::find() (portable, entity_key-based);
 *  2. require Identity::is_intact() -- the object's current content hashes to exactly the
 *     target_hash the importer last recorded, proof nothing has drifted, not merely "some
 *     fresh hash happens to match now" (is_intact() is the same target_hash comparison
 *     prediction()'s CONFLICT branch already used, factored into migration/Identity.php
 *     itself so this file reuses it rather than re-implementing any hashing);
 *  3. if it is not intact -- skip, never modify, never re-seal, record status=skipped_conflict;
 *  4. only then: compute MojibakeRepair::fix_candidate() fresh (never trust a stale audit
 *     snapshot), write it via the real WordPress API (wp_update_post()/wp_update_term()),
 *     recompute Identity::snapshot() against the now-corrected object, and store that as the
 *     new target_hash -- the exact same "write content, then hash what you actually wrote"
 *     sequence Runner::apply() itself already uses for every field it ever sets. The new hash
 *     therefore certifies only this one known, controlled, audited transformation, never
 *     arbitrary drift.
 *
 * "Primary" entity_key matters for MERGEd products: a merged post's _psi_source_keys lists
 * every contributing SQL row, but only one (the one action=MERGE built from, never one of the
 * action=SKIP losers) is the row Runner::apply() actually used for post_title -- APPROVED_POSTS
 * below names that one specifically, verified per-row against the real, local
 * Planner::build('full')/decision.field_winners['name'] once, by hand, before this file was
 * written (audit tooling, not something this migration itself needs at runtime).
 *
 * post_title and term name/description are never added to Identity::EDITORIAL_META_KEYS --
 * they are real content, not presentation, and must keep triggering CONFLICT for any future
 * unrelated change, exactly like every other content field.
 *
 * RETRY MODEL: status is tracked per entity_key (psi_mojibake_content_migration_report), not
 * as one all-or-nothing version gate. A target already 'corrected' or 'already_clean' is never
 * revisited. A target left 'skipped_conflict'/'missing'/'error' is retried on every future
 * admin_init until it resolves -- a human fixing the underlying conflict needs nothing more
 * than the next admin page load for that target to be picked up, without ever re-touching the
 * targets already corrected. Once every approved target reaches a terminal, resolved status,
 * psi_mojibake_content_migration_version is set and short-circuits all future runs.
 */
final class MojibakeContentMigration {
	public const VERSION = 1;

	/** entity_key => post_title (the only field approved for posts). Always target_type
	 * psi_producto. Each key is the single action!==SKIP plan entry for that post (the MERGE
	 * winner where applicable), never an arbitrary pick among a merged post's several source
	 * keys -- verified by hand against decision.field_winners['name'] during the audit. */
	private const APPROVED_POSTS = array(
		'sql:productos:9' => 'title',
		'sql:productos:13' => 'title',
		'sql:productos:14' => 'title',
		'sql:productos:23' => 'title',
		'sql:productos:37' => 'title',
		'sql:productos:38' => 'title',
		'sql:productos:39' => 'title',
		'sql:productos:41' => 'title',
		'sql:productos:51' => 'title',
		'sql:productos:52' => 'title',
		'sql:productos:53' => 'title',
		'sql:productos:56' => 'title',
		'sql:productos:78' => 'title',
		'sql:productos:85' => 'title',
		'sql:productos:101' => 'title',
		'sql:productos:105' => 'title',
		'sql:productos:146' => 'title',
		'sql:productos:148' => 'title',
		'sql:productos:17' => 'title',
		'sql:productos:19' => 'title',
		'sql:productos:22' => 'title',
		'sql:productos:24' => 'title',
		'sql:productos:6' => 'title',
		'sql:productos:77' => 'title',
	);

	/** entity_key => fields approved for that term. Always target_type psi_categoria. Only
	 * category:5 has both name and description affected; the other six are name-only. Never
	 * slug -- not in scope, not touched. */
	private const APPROVED_TERMS = array(
		'category:5' => array( 'name', 'description' ),
		'category:16' => array( 'name' ),
		'category:17' => array( 'name' ),
		'category:21' => array( 'name' ),
		'category:24' => array( 'name' ),
		'category:27' => array( 'name' ),
		'category:28' => array( 'name' ),
	);

	private const REPORT_OPTION = 'psi_mojibake_content_migration_report';
	private const VERSION_OPTION = 'psi_mojibake_content_migration_version';
	/** Statuses that mean "done, never revisit". Everything else is retried on the next run. */
	private const TERMINAL_STATUSES = array( 'corrected', 'already_clean' );

	public static function boot(): void {
		// admin_init: writing _psi_import_state requires psi_manage_migration (Identity's own
		// metadata filter), a real authenticated admin context -- same reasoning as
		// IdentityHashRebaseline/CategoriesMigration/BrandsMigration. No Storage::guard() and
		// no local-environment requirement beyond that: this runs the same way on staging and
		// production once they carry this content.
		add_action( 'admin_init', array( self::class, 'run' ) );
	}

	/** @return array<string, array{status: string, at: string}> current report, keyed by entity_key. */
	public static function run(): array {
		$report = get_option( self::REPORT_OPTION, array() );
		if ( (int) get_option( self::VERSION_OPTION, 0 ) >= self::VERSION ) { return $report; } // fast path: every approved target already reached a terminal status

		foreach ( self::APPROVED_POSTS as $entityKey => $field ) {
			if ( in_array( $report[ $entityKey ]['status'] ?? null, self::TERMINAL_STATUSES, true ) ) { continue; } // never re-touch an already-resolved target
			$report[ $entityKey ] = self::process( array( 'target_type' => 'psi_producto', 'entity_key' => $entityKey ), array( 'post_' . $field ) ); // 'title' -> post_title, the real WP_Post property
		}
		foreach ( self::APPROVED_TERMS as $entityKey => $fields ) {
			if ( in_array( $report[ $entityKey ]['status'] ?? null, self::TERMINAL_STATUSES, true ) ) { continue; }
			$report[ $entityKey ] = self::process( array( 'target_type' => 'psi_categoria', 'entity_key' => $entityKey ), $fields ); // 'name'/'description', already the real WP_Term properties
		}

		update_option( self::REPORT_OPTION, $report, false );
		$allResolved = true;
		foreach ( array_merge( array_keys( self::APPROVED_POSTS ), array_keys( self::APPROVED_TERMS ) ) as $entityKey ) {
			if ( ! in_array( $report[ $entityKey ]['status'] ?? null, self::TERMINAL_STATUSES, true ) ) { $allResolved = false; break; }
		}
		if ( $allResolved ) { update_option( self::VERSION_OPTION, self::VERSION, false ); }
		return $report;
	}

	/**
	 * $properties are real public properties to read/write on the resolved object: 'post_title'
	 * for a post, 'name'/'description' for a term -- exactly the array keys
	 * wp_update_post()/wp_update_term() themselves expect, so no further translation happens
	 * between reading and writing.
	 * @return array{status: string, at: string}
	 */
	private static function process( array $e, array $properties ): array {
		try {
			$id = Identity::find( $e );
			if ( ! $id ) { return array( 'status' => 'missing', 'at' => gmdate( 'c' ) ); }
			if ( ! Identity::is_intact( $e ) ) { return array( 'status' => 'skipped_conflict', 'at' => gmdate( 'c' ) ); }

			$isTerm = Identity::term( $e );
			$object = $isTerm ? get_term( $id, $e['target_type'] ) : get_post( $id );
			$updates = array();
			foreach ( $properties as $property ) {
				$after = MojibakeRepair::fix_candidate( $object->$property );
				if ( null !== $after ) { $updates[ $property ] = $after; }
			}
			if ( ! $updates ) { return array( 'status' => 'already_clean', 'at' => gmdate( 'c' ) ); }

			if ( $isTerm ) {
				$result = wp_update_term( $id, $e['target_type'], wp_slash( $updates ) );
				if ( is_wp_error( $result ) ) { throw new \RuntimeException( 'TERM_UPDATE_FAILED' ); }
			} else {
				$result = wp_update_post( array_merge( array( 'ID' => $id ), wp_slash( $updates ) ), true );
				if ( is_wp_error( $result ) || ! $result ) { throw new \RuntimeException( 'POST_UPDATE_FAILED' ); }
			}
			// Write content, then hash exactly what was written -- Runner::apply()'s own
			// sequence for every field it sets, never a guess about what "should" be there.
			$state = Identity::get( $e, $id, '_psi_import_state' );
			$state['target_hash'] = Storage::hash( Identity::snapshot( $e, $id ) );
			Identity::set( $e, $id, '_psi_import_state', $state );
			return array( 'status' => 'corrected', 'at' => gmdate( 'c' ) );
		} catch ( \Throwable $error ) {
			return array( 'status' => 'error', 'at' => gmdate( 'c' ), 'message' => substr( $error->getMessage(), 0, 100 ) );
		}
	}
}

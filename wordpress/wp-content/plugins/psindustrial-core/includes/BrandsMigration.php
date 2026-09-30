<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/**
 * Small, idempotent, versioned data migration for the 12 historical psi_marca brand terms
 * used by the Home "Marcas" section (docs/legacy-analysis: overhead-door, wayne-dalton,
 * clopay, blue-giant, kelley, doorlock, rytec, infraca-quality-doors, glg-porte-industriali,
 * dockman, lift-master, bft -- brand-master.csv legacy_id 1-12, confirmed against the
 * static order this migration replaces in inc/home-data.php).
 *
 * Sets only two things, both re-checked before writing (never blind overwrite):
 *  - _psi_brand_home_order: the exact historical Home order, so it never depends on
 *    get_terms()'s accidental alphabetical ordering.
 *  - _psi_public_state=public for these 12 specific, evidence-confirmed terms only --
 *    required for their legacy URLs/archive to stop 404ing (TermPolicy gates visibility on
 *    this, never on product count) and implied directly by the "brand archive must never
 *    404 for being empty" requirement this migration exists to satisfy.
 *
 * Never invents a term (skips silently if Identity::find() can't resolve one), never
 * touches _psi_logo_id (already correctly populated for all 12 -- verified, not migrated
 * here), never touches any other taxonomy, product, or category. Runs on plain `init`, no
 * Storage::guard()/local-only gate -- ordinary content data, safe to deploy to staging and
 * production exactly like any other code change.
 */
final class BrandsMigration {
	public const VERSION = 1;

	/** brand-master.csv legacy_id => historical Home order (identical sequence; kept as two
	 * separate concepts since a future edit to one must never silently move the other). */
	private const HOME_ORDER = array(
		1 => 1,   // Overhead Door
		2 => 2,   // Wayne Dalton
		3 => 3,   // Clopay
		4 => 4,   // Blue Giant
		5 => 5,   // Kelley
		6 => 6,   // Doorlock
		7 => 7,   // Rytec
		8 => 8,   // Infraca Quality Doors
		9 => 9,   // GLG Porter Industriali
		10 => 10, // Dockman
		11 => 11, // LiftMaster
		12 => 12, // BFT
	);

	public static function boot(): void {
		// Priority 20: strictly after Content::register() (default priority 10) registers
		// the psi_marca taxonomy itself -- Identity::find()/get_term_meta() need it to
		// already exist.
		add_action( 'init', array( self::class, 'run' ), 20 );
	}

	/** @return string[] human-readable log of changes actually made (empty if none/already applied). */
	public static function run(): array {
		if ( (int) get_option( 'psi_brands_home_migration_version', 0 ) >= self::VERSION ) { return array(); }
		$changes = array();
		foreach ( self::HOME_ORDER as $legacyId => $order ) {
			$termId = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => 'psi_marca', 'entity_key' => 'brand:' . $legacyId ) );
			if ( ! $termId ) { continue; } // term genuinely absent locally -- never invented here.
			if ( (int) get_term_meta( $termId, '_psi_brand_home_order', true ) !== $order ) {
				update_term_meta( $termId, '_psi_brand_home_order', $order );
				$changes[] = "term $termId (brand:$legacyId): _psi_brand_home_order -> $order";
			}
			if ( 'public' !== get_term_meta( $termId, '_psi_public_state', true ) ) {
				update_term_meta( $termId, '_psi_public_state', 'public' );
				$changes[] = "term $termId (brand:$legacyId): _psi_public_state -> public";
			}
		}
		update_option( 'psi_brands_home_migration_version', self::VERSION, false );
		if ( $changes ) { error_log( 'psindustrial-core BrandsMigration v' . self::VERSION . ': ' . implode( '; ', $changes ) ); }
		return $changes;
	}
}

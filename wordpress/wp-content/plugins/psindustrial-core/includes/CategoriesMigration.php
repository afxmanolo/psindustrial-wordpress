<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/**
 * Small, idempotent, versioned data migration for the psi_categoria terms behind the
 * "Soluciones" header/footer navigation and catalog sidebar (docs/migration/category-master.csv
 * legacy_id 1-39, minus the reserved-but-unused 15).
 *
 * Publishes exactly the 36 legacy_ids category-master.csv records as STRONG_INFERENCE /
 * CONFIRMED (APPROVED_LEGACY_IDS below) -- explicitly NOT legacy_id 25 ("Bumpers, semaforos y
 * cepillos para rampa niveladora") or 26 ("Puertas contra incendio", parent 0), both
 * UNKNOWN-confidence, no legacy .php page, and 26 explicitly flagged in category-master.csv as
 * a same-name conflict against category 33 that must not be merged -- both still need a human
 * decision (docs/migration/manual-decisions-required.md), not something this migration should
 * ever imply approval for. Identity::find() silently skips any legacy_id without a real,
 * already-imported WordPress term (currently 25, 26, 37, 38 -- 37/38 ARE evidence-approved and
 * simply have not been imported yet, most likely because they collide in name with categories
 * 11/12 under a different parent; if/when the importer resolves and creates them, this
 * migration will publish them automatically on its next run, the same forward-safe behaviour
 * BrandsMigration already has for a brand term that does not exist locally yet) -- never
 * invents a term.
 *
 * Sets only two things, both re-checked before writing (never blind overwrite):
 *  - _psi_public_state=public for the approved set -- required for their legacy URLs/archive
 *    to stop 404ing (TermPolicy gates visibility on this, never on product count) and implied
 *    directly by the "every real category must be public and navigable, even with zero
 *    products" requirement this migration exists to satisfy.
 *  - _psi_category_menu_order on the 7 top-level roots only (MENU_ORDER below) -- the exact
 *    order legacy/public/header.php's "Soluciones" dropdown used (Industrial, Comercial,
 *    Equipos y Accesorios para Anden de Carga, Puertas peatonales de Salida de Emergencia,
 *    Puertas peatonales contra Incendio/Contra Explosión/Blindadas, Puertas peatonales para
 *    Hospitales, Residencial), a flat, non-nested 7-item list with no children and no "Marcas"
 *    entry (commented out there too) -- there is no legacy evidence of a per-child submenu
 *    order, so children are deliberately left unordered here rather than inventing one; they
 *    already fall back sensibly in catalog_navigation()'s sort.
 *
 * Never touches slugs, names, parent/child relationships, product/category relationships, or
 * any other taxonomy. Runs on plain `init`, no Storage::guard()/local-only gate -- ordinary
 * content data, safe to deploy to staging and production exactly like BrandsMigration.
 */
final class CategoriesMigration {
	public const VERSION = 1;

	/** category-master.csv legacy_id => name, STRONG_INFERENCE/CONFIRMED rows only. */
	private const APPROVED_LEGACY_IDS = array(
		1  => 'Industrial',
		2  => 'Comercial',
		3  => 'Equipos y Accesorios para Anden de Carga',
		4  => 'Puertas peatonales de Salida de Emergencia',
		5  => 'Puertas peatonales contra Incendio, Contra Explosión y Blindadas',
		6  => 'Puertas peatonales para Hospitales',
		7  => 'Residenciales',
		8  => 'Puertas Seccionales',
		9  => 'Fraccionamientos y Condominios',
		10 => 'Rampa Niveladora',
		11 => 'Operadores para puerta corrediza (Industrial)',
		12 => 'Operadores para puerta abatible (Industrial)',
		13 => 'Operadores para puertas seccionales y cortinas enrollables',
		14 => 'Cortinas enrollables',
		16 => 'Tiras plásticas',
		17 => 'Puertas rápidas',
		18 => 'Estacionamientos',
		19 => 'Centros comerciales y hoteles',
		20 => 'Accesorios y dispositivos de control de acceso y seguridad',
		21 => 'Labio de elevación para anden de carga',
		22 => 'Retenedores de vehiculos',
		23 => 'Sellos para Anden',
		24 => 'Semaforos y señalizaciones para anden',
		27 => 'Puerta estándar',
		28 => 'Puerta estándar reforzada',
		29 => 'Puerta Holandesa',
		30 => 'Puerta y fijos Louver',
		31 => 'Puerta contra explosion',
		32 => 'Puertas blindadas',
		33 => 'Puertas contra incendio',
		34 => 'Puertas contra rayos x',
		35 => 'Puerta para hospital',
		36 => 'Puertas residenciales',
		37 => 'Operadores para puerta corrediza (Residencial)',
		38 => 'Operadores para puerta abatible (Residencial)',
		39 => 'Operadores para puertas ascendentes',
	);

	/** legacy_id => legacy/public/header.php "Soluciones" dropdown position (top-level roots only). */
	private const MENU_ORDER = array( 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7 );

	public static function boot(): void {
		// admin_init, not init: every one of these 36 terms starts out _psi_public_state=review
		// (Runner.php always creates a term that way), and TermPolicy::public_args() hides
		// non-public psi_categoria/psi_marca terms from get_terms() -- which Identity::find()
		// is built on -- unless is_admin() is true or the acting user already has
		// psi_manage_categories. On plain `init`, neither is reliably true yet, so
		// Identity::find() cannot SEE the very review-state terms this migration exists to
		// publish: every lookup silently returns 0, the loop does nothing, and (because "found
		// nothing to change" and "found nothing to look at" are indistinguishable from inside
		// the loop) it still marks itself version-complete -- a silent no-op, caught here before
		// ever landing on init. admin_init runs inside a real wp-admin request, where
		// is_admin() alone bypasses that filter regardless of capability, exactly like
		// IdentityHashRebaseline's identical fix for the same underlying visibility gate.
		add_action( 'admin_init', array( self::class, 'run' ) );
	}

	/** @return string[] human-readable log of changes actually made (empty if none/already applied). */
	public static function run(): array {
		if ( (int) get_option( 'psi_categories_public_migration_version', 0 ) >= self::VERSION ) { return array(); }
		$changes = array();
		foreach ( self::APPROVED_LEGACY_IDS as $legacyId => $name ) {
			$termId = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => 'category:' . $legacyId ) );
			if ( ! $termId ) { continue; } // term genuinely absent locally -- never invented here.
			if ( 'public' !== get_term_meta( $termId, '_psi_public_state', true ) ) {
				update_term_meta( $termId, '_psi_public_state', 'public' );
				$changes[] = "term $termId (category:$legacyId, $name): _psi_public_state -> public";
			}
			if ( isset( self::MENU_ORDER[ $legacyId ] ) && (int) get_term_meta( $termId, '_psi_category_menu_order', true ) !== self::MENU_ORDER[ $legacyId ] ) {
				update_term_meta( $termId, '_psi_category_menu_order', self::MENU_ORDER[ $legacyId ] );
				$changes[] = "term $termId (category:$legacyId, $name): _psi_category_menu_order -> " . self::MENU_ORDER[ $legacyId ];
			}
		}
		update_option( 'psi_categories_public_migration_version', self::VERSION, false );
		if ( $changes ) { error_log( 'psindustrial-core CategoriesMigration v' . self::VERSION . ': ' . implode( '; ', $changes ) ); }
		return $changes;
	}
}

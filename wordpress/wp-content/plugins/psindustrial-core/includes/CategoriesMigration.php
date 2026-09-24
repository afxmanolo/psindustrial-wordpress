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
 *  - _psi_category_menu_order on the 7 top-level roots (MENU_ORDER) -- the exact order
 *    legacy/public/header.php's "Soluciones" dropdown used (Industrial, Comercial, Equipos y
 *    Accesorios para Anden de Carga, Puertas peatonales de Salida de Emergencia, Puertas
 *    peatonales contra Incendio/Contra Explosión/Blindadas, Puertas peatonales para
 *    Hospitales, Residencial) -- and on their 27 real direct children (SIBLING_ORDER), the
 *    exact order legacy/public/index-estatico.php's Home "+ label" lists used within each
 *    parent's own section. Both use the SAME meta key: sorting is always scoped to one list
 *    at a time (all 7 roots together, or one parent's own children together), so a root's
 *    value and a child's value are never compared against each other and a shared 1..N range
 *    per list is fine. Two children (29 Puerta Holandesa, 35 Puerta para hospital) have no
 *    direct visible-Home order evidence -- 29's own legacy link was HTML-commented-out
 *    (disabled, never shown) and 35 was never listed as its own Home line at all -- both are
 *    placed last within their parent, documented per-entry below, never invented as tied for
 *    a position that had real evidence.
 *
 * Never touches slugs, names, parent/child relationships, product/category relationships, or
 * any other taxonomy. Runs on plain `init`, no Storage::guard()/local-only gate -- ordinary
 * content data, safe to deploy to staging and production exactly like BrandsMigration.
 */
final class CategoriesMigration {
	public const VERSION = 2;

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

	/**
	 * legacy_id => position within its own parent's Home "+ label" list, read directly from
	 * legacy/public/index-estatico.php's real <a href> order (never home-data.php's own labels
	 * array, which is presentation copy this migration must not be the source of truth for).
	 * legacy_id 25/26/37/38 never appear here: none has a real WordPress term (see
	 * APPROVED_LEGACY_IDS), so there is nothing to order.
	 */
	private const SIBLING_ORDER = array(
		// Industrial (parent legacy_id 1): Puertas Seccionales, Operadores para puerta
		// corrediza, Operadores para puerta abatible, Operadores para puertas seccionales y
		// cortinas enrollables, Cortinas enrollables, Puertas rápidas, Tiras plásticas.
		8 => 1, 11 => 2, 12 => 3, 13 => 4, 14 => 5, 17 => 6, 16 => 7,
		// Comercial (parent 2): Fraccionamientos y Condominios, Estacionamientos, Centros
		// comerciales y hoteles, Accesorios y dispositivos de control de acceso y seguridad.
		9 => 1, 18 => 2, 19 => 3, 20 => 4,
		// Equipos y Accesorios para Anden de Carga (parent 3): Rampa Niveladora, Labio de
		// elevación para anden de carga, Retenedores de vehiculos, Sellos para Anden,
		// Semaforos y señalizaciones para anden -- legacy's 5th link text read "Bumpers,
		// semaforos y cepillos para rampa niveladora" (category 25's name, which has no real
		// term) but its href was semaforos-senalizaciones-para-anden.php (category 24) --  a
		// pre-existing legacy mislabel, not evidence that 25 belongs here.
		10 => 1, 21 => 2, 22 => 3, 23 => 4, 24 => 5,
		// Puertas peatonales de Salida de Emergencia (parent 4): legacy showed 2 links
		// grouping 4 real categories -- "Puertas peatonales estandar y reforzada" (one shared
		// page for 27 then 28, in that reading order) and "Puertas tipo Louver y holandesa"
		// (30's own page, actively linked). 29 (Holandesa) had its OWN link present in the
		// HTML but wrapped in an HTML comment (disabled, never rendered) -- real evidence it
		// existed and was de-prioritized, not zero evidence, so it sorts last rather than
		// arbitrarily among the active three.
		27 => 1, 28 => 2, 30 => 3, 29 => 4,
		// Puertas peatonales contra Incendio, Contra Explosión y Blindadas (parent 5): legacy
		// order was Contra incendio, Contra explosión, Blindada.
		33 => 1, 31 => 2, 32 => 3,
		// Puertas peatonales para Hospitales (parent 6): legacy showed only "Puertas
		// peatonales rayos X" (34) as its own Home line. 35 ("Puerta para hospital") is a real
		// psi_categoria child (category-master.csv notes it was actually a product page in the
		// legacy SQL hierarchy, not a separate category landing) with no Home line of its own
		// at all -- no position evidence, so it sorts last, after the one line that did exist.
		34 => 1, 35 => 2,
		// Residenciales (parent 7): legacy order was Puertas residenciales, [37: no term],
		// [38: no term], Operadores para puertas ascendentes -- preserving 36 and 39's real
		// relative order.
		36 => 1, 39 => 2,
	);

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
		$order = self::MENU_ORDER + self::SIBLING_ORDER; // disjoint key ranges (1-7 vs 8-39) -- never a collision.
		foreach ( self::APPROVED_LEGACY_IDS as $legacyId => $name ) {
			$termId = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => 'category:' . $legacyId ) );
			if ( ! $termId ) { continue; } // term genuinely absent locally -- never invented here.
			if ( 'public' !== get_term_meta( $termId, '_psi_public_state', true ) ) {
				update_term_meta( $termId, '_psi_public_state', 'public' );
				$changes[] = "term $termId (category:$legacyId, $name): _psi_public_state -> public";
			}
			if ( isset( $order[ $legacyId ] ) && (int) get_term_meta( $termId, '_psi_category_menu_order', true ) !== $order[ $legacyId ] ) {
				update_term_meta( $termId, '_psi_category_menu_order', $order[ $legacyId ] );
				$changes[] = "term $termId (category:$legacyId, $name): _psi_category_menu_order -> " . $order[ $legacyId ];
			}
		}
		update_option( 'psi_categories_public_migration_version', self::VERSION, false );
		if ( $changes ) { error_log( 'psindustrial-core CategoriesMigration v' . self::VERSION . ': ' . implode( '; ', $changes ) ); }
		return $changes;
	}
}

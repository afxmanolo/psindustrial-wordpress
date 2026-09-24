<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/**
 * Portable, idempotent creation/resolution of the Soluciones and Marcas institutional Pages,
 * plus a safe, non-destructive correction of psi_site_settings' contact fields
 * (feature/legacy-contact-solutions, 2026-09-24). Both were originally done locally via
 * one-off scripts that hardcoded this environment's own post IDs (2048/2049) and wrote
 * directly to the option -- this project's CI/CD ships theme+psindustrial-core code only,
 * never the local database, so neither change would ever reach staging/production on its
 * own. This migration is what actually reaches them, and it never assumes a specific post ID
 * or a specific prior option value exists anywhere.
 *
 * PAGES: resolved by path (get_page_by_path()), never a stored post_id -- the same portable-
 * identity principle CategoriesMigration/BrandsMigration/LegacyUrls already apply to taxonomy
 * terms (see LegacyUrls' own docblock for the term_id incident that motivated it there).
 * Created only if genuinely absent at that path; an existing Page there (whatever its status)
 * is reused as-is and reported, never overwritten, never duplicated. Publishing goes through
 * Editorial::native() exactly as a human editor would: post_title, a real non-empty excerpt
 * and _psi_review_state=approved are supplied together in the one wp_insert_post() call (via
 * meta_input, read by Editorial::native() from $postarr before the post exists), never a
 * guard bypass and never a separate silent-draft-then-force-republish step.
 *
 * SETTINGS: GLOBAL contact data only -- address, phone, the CTA/footer email and the private
 * form-delivery recipient. The two additional emails shown on the Contacto page itself
 * (overheaddoor@hotmail.com, vicenteaguilarleon@gmail.com) are page-specific literals in
 * page-contacto.php, not part of this or any other "single source of truth", and this
 * migration never touches them -- see docs/frontend/institutional-frontend.md for that
 * distinction spelled out. A field is only ever written when its CURRENT value is empty or
 * matches a known historical placeholder (KNOWN_PLACEHOLDERS below); a legitimate value an
 * administrator already entered on that environment is left alone and reported
 * skipped_existing, never silently replaced. mail_recipient additionally requires the
 * visiting user to actually hold manage_options -- Settings::sanitize() itself already
 * silently discards that one field otherwise (Settings.php's own design, not something this
 * migration works around) -- so on an environment first visited by a non-Administrator (e.g.
 * psi_gestor, who can still trigger the other three fields) this one field alone is retried on
 * every future admin_init until a real Administrator loads any wp-admin screen.
 *
 * Runs on admin_init (a real authenticated admin-area request -- capability only means
 * anything in that context, the same reasoning MojibakeContentMigration already documents for
 * itself). Never gated by the local-dev-only Storage guard, never Planner, never a hardcoded
 * local id. Report tracked
 * per field/page in psi_institutional_content_migration_report for observability; the version
 * gate (psi_institutional_content_migration_version) is only set once every field has reached
 * a resolved status (migrated/already_set/skipped_existing) and every page is created/reused --
 * short-circuiting all future runs once true, exactly like MojibakeContentMigration.
 */
final class InstitutionalContentMigration {
	public const VERSION = 1;

	/** field => the specific stale value known to already be sitting in psi_site_settings on
	 * at least one real environment (this local one) -- never touched again once corrected, but
	 * never assumed to be the only reason a field could be empty/unset either (empty always
	 * qualifies too, independent of this list). */
	private const KNOWN_PLACEHOLDERS = array(
		'contact_phone' => '4774103773',
		'contact_email' => 'contacto@contacto.com',
	);

	/** field => client-confirmed value (2026-09-24 staging confirmation; see
	 * docs/frontend/contact-data-audit.md's "Resolución" section for the evidence trail). */
	private const TARGET_SETTINGS = array(
		'contact_address' => 'Blvd. Estrella #323 local 5-A, Fracc. Estrella, C.P. 36566, Irapuato, Gto.',
		'contact_phone'   => '479 107 12 34',
		'contact_email'   => 'administracion@puertasyserviciosindustriales.com',
		'mail_recipient'  => 'administracion@puertasyserviciosindustriales.com',
	);

	/** slug => Page fields. Content is intentionally minimal/factual and never publicly
	 * rendered -- both page-soluciones.php/page-marcas.php are fully custom templates that
	 * never call the_content(), exactly like page-nosotros.php/page-contacto.php already. */
	private const PAGES = array(
		'soluciones' => array(
			'title'   => 'Soluciones',
			'excerpt' => 'Landing de soluciones: tres familias -- Industrial, Residencial y Marcas -- cada una enlazando a su destino real.',
		),
		'marcas' => array(
			'title'   => 'Marcas',
			'excerpt' => 'Listado completo de las marcas psi_marca con las que trabajamos, cada logo enlazando a su archivo real.',
		),
	);

	private const REPORT_OPTION = 'psi_institutional_content_migration_report';
	private const VERSION_OPTION = 'psi_institutional_content_migration_version';

	public static function boot(): void {
		add_action( 'admin_init', array( self::class, 'run' ) );
	}

	/** @return array{settings: array<string,string>, pages: array<string,array>} current report. */
	public static function run(): array {
		if ( (int) get_option( self::VERSION_OPTION, 0 ) >= self::VERSION ) { return get_option( self::REPORT_OPTION, array() ); }

		$report = array( 'settings' => self::migrate_settings(), 'pages' => array() );
		foreach ( self::PAGES as $slug => $spec ) { $report['pages'][ $slug ] = self::resolve_page( $slug, $spec ); }

		update_option( self::REPORT_OPTION, $report, false );
		if ( self::all_resolved( $report ) ) { update_option( self::VERSION_OPTION, self::VERSION, false ); }
		return $report;
	}

	private static function all_resolved( array $report ): bool {
		foreach ( $report['settings'] as $status ) { if ( 'pending_capability' === $status ) { return false; } }
		foreach ( $report['pages'] as $page ) { if ( 'error' === $page['status'] ) { return false; } }
		return true;
	}

	/** @return array<string,string> field => migrated|already_set|skipped_existing|pending_capability */
	private static function migrate_settings(): array {
		$current = Settings::get();
		$toWrite = array();
		$statuses = array();
		foreach ( self::TARGET_SETTINGS as $field => $target ) {
			$value = $current[ $field ] ?? '';
			if ( $value === $target ) { $statuses[ $field ] = 'already_set'; continue; }
			$isPlaceholder = '' === $value || ( self::KNOWN_PLACEHOLDERS[ $field ] ?? null ) === $value;
			if ( ! $isPlaceholder ) { $statuses[ $field ] = 'skipped_existing'; continue; }
			if ( 'mail_recipient' === $field && ! current_user_can( 'manage_options' ) ) { $statuses[ $field ] = 'pending_capability'; continue; }
			$toWrite[ $field ] = $target;
			$statuses[ $field ] = 'migrated';
		}
		if ( $toWrite ) {
			update_option( 'psi_site_settings', Settings::sanitize( array_merge( $current, $toWrite ) ) );
			// Trust only what was actually persisted, never what was attempted: Settings'
			// own capability guard (pre_update_option_psi_site_settings) can still discard
			// mail_recipient here even after the pre-check above, in principle -- the same
			// "write, then verify what you actually wrote" discipline the importer's own Runner and
			// MojibakeContentMigration already use for their own writes.
			$after = Settings::get();
			foreach ( $toWrite as $field => $target ) {
				if ( ( $after[ $field ] ?? '' ) !== $target ) { $statuses[ $field ] = 'pending_capability'; }
			}
		}
		return $statuses;
	}

	/** @return array{status: string, post_id: int, post_status?: string, message?: string} */
	private static function resolve_page( string $slug, array $spec ): array {
		try {
			$existing = get_page_by_path( $slug );
			if ( $existing ) { return array( 'status' => 'reused', 'post_id' => $existing->ID, 'post_status' => $existing->post_status ); }

			$id = wp_insert_post( array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $spec['title'],
				'post_name'      => $slug,
				'post_excerpt'   => $spec['excerpt'],
				'post_content'   => '',
				'post_author'    => get_current_user_id() ?: ( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ?? 1 ),
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				// Read by Editorial::native() straight from $postarr (never self::$rest_meta,
				// a REST-only channel this direct call never populates) so the review-approved
				// publish succeeds on this single call -- no separate create-then-republish step.
				'meta_input'     => array( '_psi_review_state' => 'approved' ),
			), true );
			if ( is_wp_error( $id ) ) { return array( 'status' => 'error', 'post_id' => 0, 'message' => substr( $id->get_error_message(), 0, 150 ) ); }
			if ( 'publish' !== get_post_status( $id ) ) { return array( 'status' => 'error', 'post_id' => (int) $id, 'message' => 'Editorial guard blocked publish (non-empty excerpt/title/approved review are supplied above -- check Editorial::publication() for what else might be rejecting it)' ); }
			return array( 'status' => 'created', 'post_id' => (int) $id, 'post_status' => 'publish' );
		} catch ( \Throwable $error ) {
			return array( 'status' => 'error', 'post_id' => 0, 'message' => substr( $error->getMessage(), 0, 150 ) );
		}
	}
}

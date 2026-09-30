<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/**
 * Legacy URL compatibility -- redirects a legacy path to its live canonical WordPress URL
 * ONLY when a destination has been demonstrated AND that destination is currently public.
 * Never redirects to draft/review content; those fall through to WordPress's normal 404,
 * exactly like any unmapped legacy URL. Two independent sources of rules:
 *  - flat filename map (data/legacy-url-map.php, generated from the reconciled
 *    docs/seo/redirect-map.csv) for legacy `.php` files, matched by exact basename.
 *  - legacy friendly-URL id patterns confirmed from the legacy .htaccess itself --
 *    `/productos/marca/{id}/{slug}/`, `/productos/categoria/{id}/{slug}/` and
 *    `/{id}/categoria/{slug}/` -- matched by regex.
 *
 * A psi_categoria/psi_marca destination is ALWAYS resolved through Identity::find() at
 * request time, from entity_key (e.g. 'category:39'), never from a stored numeric term_id --
 * term_ids are auto-increment and environment-specific (local, staging, production each
 * assign their own), so one is never portable, checked-in data. This was a real incident,
 * not a hypothetical: a term accidentally deleted and recreated locally during this
 * session's own test development came back as a different id than staging has; the flat map
 * had briefly captured that one local id, which destination() now never trusts. psi_producto
 * rules still carry a plain wp_id and share this same environment-specific-ID characteristic
 * in principle -- out of scope here, products are not part of this task.
 *
 * A 'page' rule may instead carry page_path (e.g. 'soluciones') rather than wp_id, resolved
 * through get_page_by_path() at request time -- the same portable-identity principle applied
 * to a Page: this project's CI/CD ships theme+psindustrial-core code only, never the local
 * database, so a Page created ad hoc locally (feature/legacy-contact-solutions, 2026-09-24:
 * Soluciones/Marcas, post IDs 2048/2049 here) would carry a *different* post_id once
 * InstitutionalContentMigration creates it for real on staging/production. wp_id is still
 * supported for existing 'page' rules (nosotros.php, contacto.php, ...) that were reconciled
 * by hand against a specific environment's redirect-map.csv and are not being revisited here;
 * page_path is simply the portable alternative for a rule that has no such fixed id to trust.
 *
 * Only ever applies once WordPress itself has already failed to resolve the request
 * (is_404()) -- a real, modern route is structurally never touched by this class.
 */
final class LegacyUrls {
	private static ?array $map = null;

	public static function boot(): void {
		add_action( 'template_redirect', array( self::class, 'maybe_redirect' ), 5 );
	}

	private static function map(): array {
		if ( null === self::$map ) {
			self::$map = require dirname( PSINDUSTRIAL_CORE_FILE ) . '/data/legacy-url-map.php';
		}
		return self::$map;
	}

	/**
	 * Pure: given a request path and whether WordPress already treated it as a 404,
	 * returns the live 301 destination, or null when there is nothing to redirect to
	 * (unmapped legacy file, or a mapped target that exists but is not public yet).
	 * Touches no superglobals, never exits -- safe to call directly from tests.
	 */
	public static function resolve( string $path, bool $is404 ): ?string {
		if ( ! $is404 ) { return null; }
		$rule = self::map()[ basename( $path ) ] ?? null;
		if ( $rule ) { return self::destination( $rule ); }

		$legacyId = self::brand_legacy_id_from_path( $path );
		if ( null !== $legacyId ) {
			return self::destination( array( 'wp_type' => 'psi_marca', 'entity_key' => 'brand:' . $legacyId ) );
		}

		$legacyId = self::category_legacy_id_from_path( $path );
		if ( null !== $legacyId ) {
			return self::destination( array( 'wp_type' => 'psi_categoria', 'entity_key' => 'category:' . $legacyId ) );
		}
		return null;
	}

	/**
	 * Legacy friendly-URL brand pattern, confirmed from the legacy .htaccess itself:
	 * `RewriteRule ^productos/marca/([\d]+)/(.*)/$ productos.php?cmd=search&MarcaId=$1`.
	 * Only the numeric id is ever significant -- the legacy rewrite ignored the slug
	 * segment too, so this never validates it against the term's current slug. Anchored
	 * only at the end, exactly like basename() matching elsewhere in this class, so it
	 * never has to assume whether $path carries a subdirectory prefix (this local install
	 * has one; a domain-root deploy won't) -- this only ever runs on an already-404'd
	 * request, so a coincidental mid-path match is not a realistic risk.
	 */
	private static function brand_legacy_id_from_path( string $path ): ?int {
		return preg_match( '#/productos/marca/(\d+)/[^/]*/?$#', $path, $m ) ? (int) $m[1] : null;
	}

	/**
	 * Two legacy friendly-URL category patterns, both confirmed from the legacy .htaccess:
	 * `RewriteRule ^productos/categoria/([\d]+)/(.*)/$ productos.php?cmd=search&CategoriaId=$1`
	 * and `RewriteRule ^([\d]+)/categoria/(.*)/$ categorias.php?cmd=search&categoriaId=$1` --
	 * two different legacy controllers (productos.php vs categorias.php), same category id,
	 * so both resolve to the exact same real WordPress canonical. Same slug-is-decorative,
	 * end-anchored-only reasoning as brand_legacy_id_from_path(). Deliberately does NOT
	 * attempt `^productos/([\d]+)/([\d]+)/(.*)/$` (.htaccess line 2, CategoriaId+MarcaId
	 * combined): a single psi_categoria term's canonical archive cannot represent "this
	 * category AND this brand" at once, and inventing a destination for it (e.g. ignoring the
	 * brand half) would not be evidence, just a guess.
	 */
	private static function category_legacy_id_from_path( string $path ): ?int {
		if ( preg_match( '#/productos/categoria/(\d+)/[^/]*/?$#', $path, $m ) ) { return (int) $m[1]; }
		return preg_match( '#/(\d+)/categoria/[^/]*/?$#', $path, $m ) ? (int) $m[1] : null;
	}

	public static function maybe_redirect(): void {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) { return; }

		$path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
		$destination = self::resolve( $path, is_404() );
		if ( ! $destination ) { return; }

		$query = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY );
		if ( $query ) { parse_str( $query, $args ); $destination = add_query_arg( $args, $destination ); }

		wp_safe_redirect( $destination, 301 );
		exit;
	}

	/**
	 * @return string|null the live canonical URL, or null when the target is not (yet)
	 * public, or (psi_categoria/psi_marca/page-by-path) genuinely does not exist in this
	 * environment.
	 *
	 * For psi_categoria/psi_marca, $rule['entity_key'] (e.g. 'category:39', 'brand:4') is the
	 * only identity ever trusted -- resolved to this environment's real, current term_id via
	 * Identity::find() (the same mechanism CategoriesMigration/BrandsMigration/every other
	 * identity lookup in this plugin already uses), never a stored $rule['wp_id']. A term_id
	 * is an auto-increment, per-environment number: the exact same entity can be (and, for one
	 * real term this session, was) a different id on local than on staging or production.
	 *
	 * For 'page', $rule['page_path'] is resolved the same way if present (get_page_by_path(),
	 * never a stored $rule['wp_id']); a rule with no page_path falls back to its plain wp_id,
	 * unchanged from before.
	 */
	private static function destination( array $rule ): ?string {
		if ( 'site_root' === $rule['wp_type'] ) { return home_url( '/' ); }
		if ( 'page' === $rule['wp_type'] ) {
			$id = isset( $rule['page_path'] ) ? get_page_by_path( $rule['page_path'] )?->ID : ( $rule['wp_id'] ?? null );
			return $id && 'publish' === get_post_status( $id ) ? get_permalink( $id ) : null;
		}
		if ( 'psi_producto' === $rule['wp_type'] ) {
			return 'publish' === get_post_status( $rule['wp_id'] ) ? get_permalink( $rule['wp_id'] ) : null;
		}
		if ( in_array( $rule['wp_type'], array( 'psi_categoria', 'psi_marca' ), true ) ) {
			$termId = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => $rule['wp_type'], 'entity_key' => $rule['entity_key'] ) );
			if ( ! $termId || ! TermPolicy::is_public( $termId ) ) { return null; }
			$link = get_term_link( $termId, $rule['wp_type'] );
			return is_wp_error( $link ) ? null : $link;
		}
		return null;
	}
}

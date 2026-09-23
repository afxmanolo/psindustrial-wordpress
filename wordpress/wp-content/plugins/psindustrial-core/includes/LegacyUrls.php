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
 *  - the legacy `/productos/marca/{id}/{slug}/` friendly-URL pattern (confirmed from the
 *    legacy .htaccess), matched by regex and resolved live via Identity::find() -- no
 *    generated data file needed since the id->term relationship already lives in real
 *    term identity.
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
			// Same identity mechanism every other legacy mapping in this plugin resolves
			// through (Identity::find()) -- never a second, independent brand lookup, and
			// portable across environments since it never depends on a hardcoded term_id.
			$termId = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => 'psi_marca', 'entity_key' => 'brand:' . $legacyId ) );
			if ( $termId ) { return self::destination( array( 'wp_type' => 'psi_marca', 'wp_id' => $termId ) ); }
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

	/** @return string|null the live canonical URL, or null when the target is not (yet) public. */
	private static function destination( array $rule ): ?string {
		if ( 'site_root' === $rule['wp_type'] ) { return home_url( '/' ); }
		if ( in_array( $rule['wp_type'], array( 'page', 'psi_producto' ), true ) ) {
			return 'publish' === get_post_status( $rule['wp_id'] ) ? get_permalink( $rule['wp_id'] ) : null;
		}
		if ( in_array( $rule['wp_type'], array( 'psi_categoria', 'psi_marca' ), true ) ) {
			if ( ! TermPolicy::is_public( $rule['wp_id'] ) ) { return null; }
			$link = get_term_link( $rule['wp_id'], $rule['wp_type'] );
			return is_wp_error( $link ) ? null : $link;
		}
		return null;
	}
}

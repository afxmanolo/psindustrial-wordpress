<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/**
 * Legacy `.php` URL compatibility -- redirects a legacy filename to its live canonical
 * WordPress URL ONLY when a destination has been demonstrated (data/legacy-url-map.php,
 * generated from the reconciled docs/seo/redirect-map.csv) AND that destination is
 * currently public. Never redirects to draft/review content; those fall through to
 * WordPress's normal 404, exactly like any unmapped legacy URL.
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
		return $rule ? self::destination( $rule ) : null;
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

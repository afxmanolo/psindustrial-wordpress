<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class TermPolicy {
	public static function boot(): void {
		add_filter( 'wp_update_term_data', array( self::class, 'protect' ), 10, 4 );
		add_filter( 'wp_update_term_parent', array( self::class, 'parent' ), 20, 3 );
		add_filter( 'rest_request_before_callbacks', array( self::class, 'rest' ), 9, 3 );
		add_filter( 'get_terms_args', array( self::class, 'public_args' ), 10, 2 );
		add_action( 'template_redirect', static function() {
			if ( is_tax( array( 'psi_categoria', 'psi_marca' ) ) && ! self::is_public( get_queried_object_id() ) ) { global $wp_query; $wp_query->set_404(); status_header( 404 ); nocache_headers(); }
		} );
		add_filter( 'get_the_terms', static function( $terms, $id, $tax ) {
			if ( ! is_admin() && in_array( $tax, array( 'psi_categoria', 'psi_marca' ), true ) && is_array( $terms ) ) { return array_values( array_filter( $terms, static fn( $term ) => self::is_public( $term->term_id ) ) ); }
			return $terms;
		}, 10, 3 );
		add_action( 'pre_get_posts', static function( $q ) {
			if ( ! is_admin() && $q->is_main_query() && $q->is_tax( 'psi_categoria' ) ) {
				$q->set( 'tax_query', array( array( 'taxonomy' => 'psi_categoria', 'field' => 'slug', 'terms' => $q->get( 'psi_categoria' ), 'include_children' => false ) ) );
			}
		} );
	}
	public static function is_public( int $id ): bool { return 'public' === get_term_meta( $id, '_psi_public_state', true ); }
	public static function public_args( array $args, array $taxonomies ): array {
		if ( ! array_intersect( $taxonomies, array( 'psi_categoria', 'psi_marca' ) ) || is_admin() || ( current_user_can( 'psi_manage_categories' ) && ( ! empty( $args['psi_include_review'] ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || PHP_SAPI === 'cli' ) ) ) { return $args; }
		$args['meta_query'] = array( 'relation' => 'AND', (array) ( $args['meta_query'] ?? array() ), array( 'key' => '_psi_public_state', 'value' => 'public' ) );
		return $args;
	}
	public static function protect( array $data, int $id, string $tax, array $args ): array {
		if ( ! in_array( $tax, array( 'psi_categoria', 'psi_marca' ), true ) ) { return $data; }
		$old = get_term( $id, $tax );
		if ( self::is_public( $id ) ) {
			if ( ! current_user_can( 'psi_manage_routes' ) ) { $data['slug'] = $old->slug; }
		}
		return $data;
	}
	public static function parent( int $parent, int $id, string $tax ): int {
		if ( ! in_array( $tax, array( 'psi_categoria', 'psi_marca' ), true ) ) { return $parent; }
		$old = get_term( $id, $tax );
		if ( ( self::is_public( $id ) && ! current_user_can( 'psi_manage_structure' ) ) || $parent === $id || in_array( $id, get_ancestors( $parent, $tax, 'taxonomy' ), true ) ) { return (int) $old->parent; }
		return $parent;
	}
	public static function rest( mixed $result, array $handler, \WP_REST_Request $r ): mixed {
		if ( null !== $result || ! preg_match( '#^/wp/v2/(psi_categoria|psi_marca)(?:/([0-9]+))?$#', $r->get_route(), $match ) ) { return $result; }
		$id = (int) ( $match[2] ?? 0 );
		$old = $id ? get_term( $id, $match[1] ) : null;
		if ( 'GET' === $r->get_method() && $id && ! self::is_public( $id ) && ! current_user_can( 'edit_term', $id ) ) { return new \WP_Error( 'rest_term_invalid', __( 'No se encontró el término.', 'psindustrial-core' ), array( 'status' => 404 ) ); }
		if ( ! in_array( $r->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) || ! $old || is_wp_error( $old ) ) { return $result; }
		if ( self::is_public( $id ) && ( ( $r->has_param( 'slug' ) && $r['slug'] !== $old->slug && ! current_user_can( 'psi_manage_routes' ) ) || ( $r->has_param( 'parent' ) && (int) $r['parent'] !== (int) $old->parent && ! current_user_can( 'psi_manage_structure' ) ) ) ) {
			return new \WP_Error( 'psi_term_structure', __( 'El administrador debe autorizar cambios en la URL o jerarquía pública.', 'psindustrial-core' ), array( 'status' => 403 ) );
		}
		$parent = (int) $r['parent'];
		if ( $parent && ( $parent === $id || in_array( $id, get_ancestors( $parent, $match[1], 'taxonomy' ), true ) ) ) { return new \WP_Error( 'psi_cycle', __( 'La jerarquía no puede contener ciclos.', 'psindustrial-core' ), array( 'status' => 400 ) ); }
		return $result;
	}
}

<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;

/** Presentation of existing public destinations; never changes editorial approval. */
function catalog_navigation( bool $all_categories = false ): array {
	$items = array();
	$url = get_post_type_archive_link( 'psi_producto' );
	if ( $url ) { $items[] = array( 'label' => __( 'Productos', 'psindustrial' ), 'url' => $url, 'active' => is_post_type_archive( 'psi_producto' ), 'icon' => 'briefcase' ); }
	foreach ( array( 'psi_categoria', 'psi_marca' ) as $taxonomy ) {
		if ( $all_categories && is_tax( 'psi_marca' ) && 'psi_categoria' === $taxonomy ) { continue; }
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) { continue; }
		$terms = array_values( array_filter( $terms, static fn( $term ) => 'public' === get_term_meta( $term->term_id, '_psi_public_state', true ) ) );
		$public_ids = wp_list_pluck( $terms, 'term_id' );
		// Preserve the static header's order for the unambiguous existing family slugs.
		$order = array( 'industrial', 'comercial', 'equipos-y-accesorios-para-anden-de-carga', 'puertas-peatonales-de-salida-de-emergencia', 'puertas-peatonales-contra-incendio-contra-explosia%c2%b3n-y-blindadas', 'puertas-peatonales-para-hospitales', 'residenciales' );
		if ( 'psi_categoria' === $taxonomy ) {
			usort( $terms, static function( $a, $b ) use ( $order ) {
				$ai = array_search( $a->slug, $order, true ); $bi = array_search( $b->slug, $order, true );
				return ( false === $ai ? 99 : $ai ) <=> ( false === $bi ? 99 : $bi );
			} );
		}
		foreach ( $terms as $term ) {
			if ( 'psi_categoria' === $taxonomy && in_array( (int) $term->parent, $public_ids, true ) && ! ( $all_categories && is_tax( $taxonomy, $term->term_id ) ) ) { continue; }
			$url = get_term_link( $term );
			if ( is_wp_error( $url ) ) { continue; }
			$icons = array( 'industrial' => 'briefcase', 'comercial' => 'bag', 'equipos-y-accesorios-para-anden-de-carga' => 'truck', 'puertas-peatonales-de-salida-de-emergencia' => 'users', 'puertas-peatonales-para-hospitales' => 'plus', 'residenciales' => 'home' );
			$items[] = array( 'label' => $term->name, 'url' => $url, 'active' => is_tax( $taxonomy, $term->term_id ), 'icon' => 'psi_marca' === $taxonomy ? 'star' : ( $icons[ $term->slug ] ?? 'disc' ) );
		}
	}
	return $items;
}

function catalog_contact_url(): string {
	$url = published_page_url( 'contacto' );
	if ( $url ) { return $url; }
	$settings = class_exists( \PSIndustrial\Core\Settings::class ) ? \PSIndustrial\Core\Settings::get() : array();
	return ! empty( $settings['contact_email'] ) && is_email( $settings['contact_email'] ) ? 'mailto:' . $settings['contact_email'] : '';
}

/** Invalid placeholder links and unpublished entities are not public navigation. */
add_filter( 'wp_nav_menu_objects', static function( array $items, $args ): array {
	if ( ! in_array( $args->theme_location ?? '', array( 'primary', 'catalog_sidebar', 'brand_sidebar' ), true ) ) { return $items; }
	return array_values( array_filter( $items, static function( $item ) {
		if ( ! $item->url || '#' === $item->url ) { return false; }
		if ( 'taxonomy' === $item->type && in_array( $item->object, array( 'psi_categoria', 'psi_marca' ), true ) ) { return 'public' === get_term_meta( $item->object_id, '_psi_public_state', true ); }
		return 'post_type' !== $item->type || 'publish' === get_post_status( $item->object_id );
	} ) );
}, 10, 2 );

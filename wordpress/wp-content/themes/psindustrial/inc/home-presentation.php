<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;

/** Static Home artwork is separate from editorial attachment relationships. */
function home_image( string $name, string $alt, string $sizes = '100vw', bool $priority = false ): string {
	$asset = home_data()['assets'][ $name ] ?? null;
	if ( ! $asset ) { return ''; }
	$variants = $asset['variants'];
	$largest = end( $variants );
	$srcset = array_map( static fn( $v ) => get_theme_file_uri( $v['file'] ) . ' ' . $v['width'] . 'w', $variants );
	return sprintf( '<img src="%s" srcset="%s" sizes="%s" width="%d" height="%d" alt="%s" %s decoding="async">', esc_url( get_theme_file_uri( $largest['file'] ) ), esc_attr( implode( ', ', $srcset ) ), esc_attr( $sizes ), $asset['width'], $asset['height'], esc_attr( $alt ), $priority ? 'fetchpriority="high" loading="eager"' : 'loading="lazy"' );
}

function home_family_url( string $slug ): string {
	$term = get_term_by( 'slug', $slug, 'psi_categoria' );
	if ( ! $term || 'public' !== get_term_meta( $term->term_id, '_psi_public_state', true ) ) { return ''; }
	$url = get_term_link( $term );
	return is_wp_error( $url ) ? '' : $url;
}

/**
 * Home "Marcas" grid, sourced from real psi_marca terms -- never the historical hardcoded
 * list. hide_empty=false is required: a brand with zero published products must still
 * appear (TermPolicy's own get_terms_args filter already restricts this to
 * _psi_public_state=public terms for an anonymous/public request, so no product-count or
 * review-state logic belongs here). Ordered by _psi_brand_home_order (BrandsMigration),
 * never accidental term_id/alphabetical order. The rendered image stays the theme's own
 * pre-optimized WebP variant (home_image()) -- resolved from the term's real _psi_logo_id
 * attachment's filename, never a second, hardcoded name -- so a brand only appears once it
 * both has a stored logo and a prepared Home asset for it.
 *
 * @return array<int, array{name: string, link: string, image: string}>
 */
function home_brands(): array {
	$terms = get_terms( array(
		'taxonomy' => 'psi_marca',
		'hide_empty' => false,
		'orderby' => 'meta_value_num',
		'meta_key' => '_psi_brand_home_order',
		'order' => 'ASC',
	) );
	if ( is_wp_error( $terms ) || ! $terms ) { return array(); }
	$brands = array();
	foreach ( $terms as $term ) {
		$logoId = (int) get_term_meta( $term->term_id, '_psi_logo_id', true );
		if ( ! $logoId ) { continue; }
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) { continue; }
		$file = get_attached_file( $logoId );
		$assetKey = $file ? basename( $file ) : '';
		if ( '' === $assetKey || ! isset( home_data()['assets'][ $assetKey ] ) ) { continue; }
		$brands[] = array( 'name' => $term->name, 'link' => $link, 'image' => $assetKey );
	}
	return $brands;
}

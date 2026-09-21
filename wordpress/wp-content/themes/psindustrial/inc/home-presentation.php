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

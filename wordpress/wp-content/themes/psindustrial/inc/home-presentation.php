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

/**
 * Home "Conoce nuestros Productos" family sections, sourced from real psi_categoria parent/
 * child terms -- never a hardcoded labels/slug list. home_data()['sections'] keeps only what
 * cannot be derived from the taxonomy (which theme image, and the reverse/narrow/pattern
 * layout flags -- pure presentation, not category data). For each configured root:
 *  - resolved via Identity::find() by legacy_id, exactly like CategoriesMigration itself,
 *    portable across environments and never a hardcoded term_id;
 *  - 'link' is get_term_link() on the PARENT -- "Ver todos" always goes to the real parent
 *    category archive, never the global psi_producto catalog;
 *  - 'children' are the parent's real, direct psi_categoria children (hide_empty=false, so a
 *    child with zero products still appears -- TermPolicy's own get_terms_args filter already
 *    restricts this to _psi_public_state=public for an anonymous/public request), ordered by
 *    _psi_category_menu_order (CategoriesMigration's SIBLING_ORDER), each a real
 *    get_term_link() on that child.
 * A root that does not resolve, or resolves to a non-public term, is skipped entirely (no
 * partial section, no fallback to the global catalog) -- matches home_brands()' own
 * never-invent-a-destination posture.
 *
 * @return array<int, array{title: string, link: string, children: array<int, array{label: string, link: string}>, image: string, reverse: bool, narrow: bool, pattern: bool}>
 */
function home_category_sections(): array {
	$sections = array();
	foreach ( home_data()['sections'] as $config ) {
		$parentId = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => 'category:' . $config['legacy_id'] ) );
		if ( ! $parentId || 'public' !== get_term_meta( $parentId, '_psi_public_state', true ) ) { continue; }
		$parent = get_term( $parentId, 'psi_categoria' );
		$link = get_term_link( $parent );
		if ( ! $parent || is_wp_error( $parent ) || is_wp_error( $link ) ) { continue; }

		$childTerms = get_terms( array( 'taxonomy' => 'psi_categoria', 'parent' => $parentId, 'hide_empty' => false ) );
		if ( is_wp_error( $childTerms ) ) { $childTerms = array(); }
		usort( $childTerms, static function( $a, $b ) {
			$ao = get_term_meta( $a->term_id, '_psi_category_menu_order', true );
			$bo = get_term_meta( $b->term_id, '_psi_category_menu_order', true );
			return ( '' === $ao ? PHP_INT_MAX : (int) $ao ) <=> ( '' === $bo ? PHP_INT_MAX : (int) $bo );
		} );
		$children = array();
		foreach ( $childTerms as $child ) {
			$childLink = get_term_link( $child );
			if ( is_wp_error( $childLink ) ) { continue; }
			$children[] = array( 'label' => $child->name, 'link' => $childLink );
		}

		$sections[] = array(
			'title' => $parent->name,
			'link' => $link,
			'children' => $children,
			'image' => $config['image'],
			'reverse' => $config['reverse'],
			'narrow' => $config['narrow'],
			'pattern' => $config['pattern'],
		);
	}
	return $sections;
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

<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;

function is_catalog(): bool {
	return is_singular( 'psi_producto' ) || is_post_type_archive( 'psi_producto' ) || is_tax( array( 'psi_categoria', 'psi_marca' ) );
}

/** Navigation only: never publish a page or manufacture a legacy URL. */
function published_page_url( string $slug ): string {
	$page = get_page_by_path( $slug );
	return $page && 'publish' === $page->post_status ? get_permalink( $page ) : '';
}

add_action( 'after_setup_theme', static function() {
	register_nav_menus( array( 'catalog_sidebar' => __( 'Lateral de soluciones', 'psindustrial' ), 'brand_sidebar' => __( 'Lateral de marcas', 'psindustrial' ) ) );
} );

add_filter( 'body_class', static function( array $classes ): array {
	$classes[] = 'psi-site';
	if ( is_front_page() || is_catalog() || is_institutional() ) { $classes[] = 'psi-has-hero'; }
	if ( is_catalog() ) { $classes[] = 'psi-catalog'; }
	return $classes;
} );

// Imported text can contain an empty PDF anchor/heading after media extraction.
// Remove only empty markup at display time; the stored editorial content is untouched.
add_filter( 'the_content', static function( string $content ): string {
	if ( is_singular( 'psi_producto' ) && in_the_loop() && is_main_query() ) {
		$content = preg_replace( '~<(a|h[2-6])\b[^>]*>\s*</\1>~i', '', $content );
		$content = preg_replace( '~<p>\s*</p>~i', '', $content );
		$html = new \WP_HTML_Tag_Processor( $content );
		while ( $html->next_tag( 'table' ) ) {
			if ( null === $html->get_attribute( 'tabindex' ) ) { $html->set_attribute( 'tabindex', '0' ); }
			if ( null === $html->get_attribute( 'aria-label' ) ) { $html->set_attribute( 'aria-label', __( 'Información técnica. Desplace horizontalmente si es necesario.', 'psindustrial' ) ); }
		}
		return $html->get_updated_html();
	}
	return $content;
}, 20 );

/** The historical PDF button is a theme resource, never a product photograph. */
function product_image( int $id ): bool {
	if ( ! $id || 'attachment' !== get_post_type( $id ) || 'trash' === get_post_status( $id ) || ! wp_attachment_is_image( $id ) ) { return false; }
	static $checked = array();
	if ( ! array_key_exists( $id, $checked ) ) {
		$file = get_attached_file( $id );
		$button = get_theme_file_path( 'assets/images/ver-ficha-tecnica.png' );
		$checked[ $id ] = ! ( $file && is_file( $file ) && is_file( $button ) && filesize( $file ) === filesize( $button ) && hash_file( 'sha256', $file ) === hash_file( 'sha256', $button ) );
	}
	return $checked[ $id ];
}

function product_images( int $post_id ): array {
	$gallery = get_post_meta( $post_id, '_psi_gallery_ids', true );
	$ids = array_merge( array( get_post_thumbnail_id( $post_id ) ), is_array( $gallery ) ? $gallery : array() );
	return array_values( array_filter( array_unique( array_map( 'absint', $ids ) ), __NAMESPACE__ . '\\product_image' ) );
}

function product_datasheets( int $post_id ): array {
	$rows = get_post_meta( $post_id, '_psi_datasheets', true );
	$result = array();
	foreach ( is_array( $rows ) ? $rows : array() as $row ) {
		$id = absint( $row['attachment_id'] ?? 0 );
		if ( ! $id || isset( $result[ $id ] ) || 'attachment' !== get_post_type( $id ) || 'trash' === get_post_status( $id ) || 'application/pdf' !== get_post_mime_type( $id ) ) { continue; }
		$url = wp_get_attachment_url( $id );
		if ( $url ) {
			$label = wp_strip_all_tags( (string) ( $row['label'] ?? '' ) );
			$label = preg_replace( '/\.pdf$/i', '', basename( str_replace( '\\', '/', $label ) ) );
			if ( preg_match( '/^[a-f0-9]{32,64}$/i', $label ) ) { $label = ''; }
			$result[ $id ] = array( 'url' => $url, 'label' => trim( str_replace( array( '-', '_' ), ' ', $label ) ) );
		}
	}
	return array_values( $result );
}

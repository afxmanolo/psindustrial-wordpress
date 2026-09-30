<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;

/**
 * The historical "Soluciones" landing (legacy/public/soluciones.php): exactly the three
 * conceptual cards -- Industrial, Residencial, Marcas. Industrial/Residencial resolve through
 * the same real, portable psi_categoria roots used everywhere else in this theme
 * (Identity::find() by legacy_id, never a hardcoded term_id -- category:1/category:7 are the
 * same entity_keys data/legacy-url-map.php already uses for industrial.php/residencial.php).
 * Marcas resolves to the real Marcas Page. A card whose destination does not resolve is
 * skipped entirely -- never a placeholder link, matching home_category_sections()' own
 * never-invent-a-destination posture.
 */
function soluciones_cards(): array {
	$category = static function( int $legacyId ): ?array {
		$termId = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => "category:$legacyId" ) );
		if ( ! $termId ) { return null; }
		$link = get_term_link( $termId, 'psi_categoria' );
		return is_wp_error( $link ) ? null : array( 'link' => $link );
	};
	$cards = array();
	$industrial = $category( 1 );
	if ( $industrial ) { $cards[] = array( 'title' => __( 'Industrial', 'psindustrial' ), 'tagline' => __( 'Soluciones Industriales', 'psindustrial' ), 'image' => 'puerta432', 'link' => $industrial['link'] ); }
	$residencial = $category( 7 );
	if ( $residencial ) { $cards[] = array( 'title' => __( 'Residencial', 'psindustrial' ), 'tagline' => __( 'Soluciones Residenciales', 'psindustrial' ), 'image' => 'operadores', 'link' => $residencial['link'] ); }
	$marcas = published_page_url( 'marcas' );
	if ( $marcas ) { $cards[] = array( 'title' => __( 'Marcas', 'psindustrial' ), 'tagline' => __( 'Trabajamos con los mejores de la industria.', 'psindustrial' ), 'image' => 'marcas-soluciones', 'link' => $marcas ); }
	return $cards;
}

/** Same 480w/800w institutional convention as quienes-int (page-nosotros.php); these three are
 * plain content images, not Home presentation, so they intentionally do not go through
 * home_data()/home_image(). */
function soluciones_card_image( string $base, string $alt ): string {
	$w480 = get_theme_file_uri( "assets/images/institutional/{$base}-480.webp" );
	$w800 = get_theme_file_uri( "assets/images/institutional/{$base}-800.webp" );
	return sprintf( '<img src="%s" srcset="%s 480w, %s 800w" sizes="(min-width: 992px) 33vw, 90vw" width="800" height="635" alt="%s" loading="lazy" decoding="async">', esc_url( $w800 ), esc_url( $w480 ), esc_url( $w800 ), esc_attr( $alt ) );
}

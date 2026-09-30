<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class AdminList {
	public static function boot(): void {
		add_filter( 'manage_psi_producto_posts_columns', static function( $columns ) {
			return array_slice( $columns, 0, 1, true ) + array( 'psi_image' => __( 'Imagen', 'psindustrial-core' ) ) + $columns + array( 'psi_pdf' => __( 'Fichas PDF', 'psindustrial-core' ), 'psi_review' => __( 'Revisión', 'psindustrial-core' ) );
		} );
		add_action( 'manage_psi_producto_posts_custom_column', array( self::class, 'column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( self::class, 'filters' ) );
		add_action( 'pre_get_posts', array( self::class, 'query' ) );
	}
	public static function column( string $column, int $id ): void {
		if ( 'psi_image' === $column ) { echo get_the_post_thumbnail( $id, array( 48, 48 ) ); }
		if ( 'psi_pdf' === $column ) { echo esc_html( (string) count( (array) get_post_meta( $id, '_psi_datasheets', true ) ) ); }
		if ( 'psi_review' === $column ) { echo esc_html( 'approved' === get_post_meta( $id, '_psi_review_state', true ) ? __( 'Revisado', 'psindustrial-core' ) : __( 'Pendiente', 'psindustrial-core' ) ); }
	}
	public static function filters( string $type ): void {
		if ( 'psi_producto' !== $type ) { return; }
		foreach ( array( 'psi_categoria' => __( 'Todas las categorías', 'psindustrial-core' ), 'psi_marca' => __( 'Todas las marcas', 'psindustrial-core' ) ) as $tax => $label ) {
			wp_dropdown_categories( array( 'taxonomy' => $tax, 'name' => 'filter_' . $tax, 'show_option_all' => $label, 'hide_empty' => false, 'hierarchical' => 'psi_categoria' === $tax, 'selected' => self::filter_id( $tax ), 'value_field' => 'term_id' ) );
		}
		echo '<select name="psi_review_filter" aria-label="' . esc_attr__( 'Revisión', 'psindustrial-core' ) . '">';
		foreach ( array( '' => __( 'Todas las revisiones', 'psindustrial-core' ), 'pending' => __( 'Pendiente', 'psindustrial-core' ), 'approved' => __( 'Revisado', 'psindustrial-core' ) ) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( is_string( $_GET['psi_review_filter'] ?? '' ) ? ( $_GET['psi_review_filter'] ?? '' ) : '', $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}
	public static function query( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'psi_producto' !== $query->get( 'post_type' ) ) { return; }
		$tax_query = array( 'relation' => 'AND' );
		foreach ( array( 'psi_categoria', 'psi_marca' ) as $tax ) {
			$id = self::filter_id( $tax );
			if ( $id ) { $tax_query[] = array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => $id, 'include_children' => false ); }
		}
		if ( count( $tax_query ) > 1 ) { $query->set( 'tax_query', $tax_query ); }
		$review = $_GET['psi_review_filter'] ?? '';
		if ( 'pending' === $review ) {
			$query->set( 'meta_query', array( 'relation' => 'OR', array( 'key' => '_psi_review_state', 'value' => 'pending' ), array( 'key' => '_psi_review_state', 'compare' => 'NOT EXISTS' ) ) );
		} elseif ( 'approved' === $review ) { $query->set( 'meta_query', array( array( 'key' => '_psi_review_state', 'value' => 'approved' ) ) ); }
	}
	private static function filter_id( string $taxonomy ): int {
		$value = $_GET[ 'filter_' . $taxonomy ] ?? 0;
		return is_scalar( $value ) ? absint( $value ) : 0;
	}
}

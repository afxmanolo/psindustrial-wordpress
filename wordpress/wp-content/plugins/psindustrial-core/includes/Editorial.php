<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class Editorial {
	private static array $rest_meta = array();
	public static function boot(): void {
		foreach ( array( 'psi_producto', 'page' ) as $type ) {
			add_filter( 'rest_pre_insert_' . $type, array( self::class, 'rest' ), 30, 2 );
			add_action( 'rest_after_insert_' . $type, static function() { self::$rest_meta = array(); } );
		}
		add_filter( 'wp_insert_post_data', array( self::class, 'native' ), 30, 4 );
		add_filter( 'rest_request_after_callbacks', static function( $response ) { self::$rest_meta = array(); return $response; } );
	}
	public static function publication( string $title, string $body, string $excerpt, string $review ): true|\WP_Error {
		if ( '' === trim( wp_strip_all_tags( $title ) ) || ( '' === trim( wp_strip_all_tags( $body ) ) && '' === trim( wp_strip_all_tags( $excerpt ) ) ) || 'approved' !== $review ) {
			return new \WP_Error( 'psi_publication', __( 'Antes de publicar: indique un nombre, una descripción o resumen y marque el contenido como revisado.', 'psindustrial-core' ), array( 'status' => 400 ) );
		}
		return true;
	}
	public static function rest( mixed $prepared, \WP_REST_Request $r ): mixed {
		self::$rest_meta = array();
		if ( is_wp_error( $prepared ) ) { return $prepared; }
		$id = (int) $r['id'];
		$old = $id ? get_post( $id ) : null;
		if ( str_starts_with( $r->get_route(), '/wp/v2/psi_producto' ) && $r->has_param( 'parent' ) && (int) $r['parent'] ) { return new \WP_Error( 'psi_product_parent', __( 'Los productos no tienen un producto padre; utilice categorías.', 'psindustrial-core' ), array( 'status' => 400 ) ); }
		$meta = (array) $r->get_param( 'meta' );
		if ( $r->has_param( 'menu_order' ) && (int) $r['menu_order'] < 0 ) { return new \WP_Error( 'psi_order', __( 'El orden no puede ser negativo.', 'psindustrial-core' ), array( 'status' => 400 ) ); }
		$featured = $r->has_param( 'featured_media' ) ? (int) $r['featured_media'] : ( $id ? (int) get_post_thumbnail_id( $id ) : 0 );
		$gallery = $meta['_psi_gallery_ids'] ?? ( $id ? get_post_meta( $id, '_psi_gallery_ids', true ) : array() );
		if ( ( $featured && ! Media::valid( $featured, 'image' ) ) || ( $featured && in_array( $featured, (array) $gallery, true ) ) ) {
			return new \WP_Error( 'psi_featured', __( 'La imagen destacada debe ser válida y no repetirse en la galería.', 'psindustrial-core' ), array( 'status' => 400 ) );
		}
		$status = $prepared->post_status ?? $old?->post_status ?? 'draft';
		if ( in_array( $status, array( 'publish', 'future' ), true ) ) {
			$valid = self::publication( $prepared->post_title ?? $old?->post_title ?? '', $prepared->post_content ?? $old?->post_content ?? '', $prepared->post_excerpt ?? $old?->post_excerpt ?? '', $meta['_psi_review_state'] ?? ( $id ? get_post_meta( $id, '_psi_review_state', true ) : 'pending' ) );
			if ( is_wp_error( $valid ) ) { return $valid; }
		}
		self::$rest_meta = $meta;
		return $prepared;
	}
	public static function native( array $data, array $postarr, array $raw, bool $update ): array {
		if ( ! in_array( $data['post_type'], array( 'psi_producto', 'page' ), true ) ) { return $data; }
		$data['post_title'] = wp_slash( sanitize_text_field( wp_unslash( $data['post_title'] ) ) );
		$data['menu_order'] = max( 0, (int) $data['menu_order'] );
		if ( 'psi_producto' === $data['post_type'] ) { $data['post_parent'] = 0; }
		$id = (int) ( $postarr['ID'] ?? 0 );
		$meta = self::$rest_meta ?: (array) ( $postarr['meta_input'] ?? array() );
		$nonce = $_POST['psi_product_nonce'] ?? '';
		if ( is_string( $nonce ) && wp_verify_nonce( $nonce, 'psi_save_product' ) && current_user_can( $id ? 'edit_post' : 'publish_psi_productos', $id ) && isset( $_POST['psi_fields'] ) && is_array( $_POST['psi_fields'] ) ) { $meta = wp_unslash( $_POST['psi_fields'] ); }
		if ( is_string( $nonce ) && wp_verify_nonce( $nonce, 'psi_save_product' ) && isset( $_POST['psi_menu_order'] ) && is_scalar( $_POST['psi_menu_order'] ) ) { $data['menu_order'] = max( 0, (int) $_POST['psi_menu_order'] ); }
		if ( in_array( $data['post_status'], array( 'publish', 'future' ), true ) ) {
			$review = $meta['_psi_review_state'] ?? ( $id ? get_post_meta( $id, '_psi_review_state', true ) : 'pending' );
			$valid = self::publication( $data['post_title'], $data['post_content'], $data['post_excerpt'], is_string( $review ) ? $review : '' );
			if ( is_wp_error( $valid ) ) {
				// Keep an already public document intact when a partial/invalid write arrives.
				$old = $id ? get_post( $id ) : null;
				if ( $old && 'publish' === $old->post_status ) {
					foreach ( array( 'post_title', 'post_content', 'post_excerpt' ) as $field ) { $data[ $field ] = wp_slash( $old->$field ); }
				} else { $data['post_status'] = 'draft'; }
				ProductEditor::error( $valid->get_error_message() );
			}
		}
		return $data;
	}
}

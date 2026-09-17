<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class TermEditor {
	public static function boot(): void {
		foreach ( array( 'psi_categoria', 'psi_marca' ) as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( self::class, 'add_form' ) );
			add_action( $taxonomy . '_edit_form_fields', array( self::class, 'edit_form' ) );
			add_action( 'created_' . $taxonomy, array( self::class, 'save' ) );
			add_action( 'edited_' . $taxonomy, array( self::class, 'save' ) );
		}
		add_filter( 'add_term_metadata', array( self::class, 'guard' ), 10, 5 );
		add_filter( 'update_term_metadata', array( self::class, 'guard' ), 10, 5 );
		add_filter( 'rest_request_before_callbacks', array( self::class, 'rest_validate' ), 10, 3 );
	}
	public static function image_key( string $taxonomy ): string {
		return 'psi_marca' === $taxonomy ? '_psi_logo_id' : '_psi_image_id';
	}
	public static function register_meta(): void {
		foreach ( array( 'psi_categoria', 'psi_marca' ) as $taxonomy ) {
			register_term_meta( $taxonomy, self::image_key( $taxonomy ), array(
				'type' => 'integer', 'single' => true, 'default' => 0,
				'show_in_rest' => array( 'schema' => array( 'type' => 'integer', 'minimum' => 0 ) ),
				'sanitize_callback' => 'absint',
				'auth_callback' => static fn() => current_user_can( get_taxonomy( $taxonomy )->cap->edit_terms ),
			) );
		}
	}
	private static function valid( mixed $value ): bool {
		return ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) && (int) $value >= 0 && ( 0 === (int) $value || wp_attachment_is_image( (int) $value ) );
	}
	public static function guard( mixed $check, int $id, string $key, mixed $value, mixed $unused ): mixed {
		if ( in_array( $key, array( '_psi_logo_id', '_psi_image_id' ), true ) ) {
			$term = get_term( $id );
			if ( $term && ! is_wp_error( $term ) && in_array( $term->taxonomy, array( 'psi_categoria', 'psi_marca' ), true ) && ! self::valid( $value ) ) {
				return false;
			}
		}
		return $check;
	}
	public static function rest_validate( mixed $prepared, array $handler, \WP_REST_Request $request ): mixed {
		if ( null !== $prepared || ! in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) || ! preg_match( '#^/wp/v2/psi_(?:categoria|marca)(?:/[0-9]+)?$#', $request->get_route() ) ) {
			return $prepared;
		}
		foreach ( (array) $request->get_param( 'meta' ) as $key => $value ) {
			if ( in_array( $key, array( '_psi_logo_id', '_psi_image_id' ), true ) && ! self::valid( $value ) ) {
				return new \WP_Error( 'psi_invalid_logo', __( 'Seleccione una imagen existente para el término.', 'psindustrial-core' ), array( 'status' => 400 ) );
			}
		}
		return $prepared;
	}
	public static function add_form( string $taxonomy ): void {
		echo '<div class="form-field">';
		self::control( $taxonomy, 0 );
		echo '</div>';
	}
	public static function edit_form( \WP_Term $term ): void {
		echo '<tr class="form-field"><th scope="row">' . esc_html__( 'Imagen del término', 'psindustrial-core' ) . '</th><td>';
		self::control( $term->taxonomy, $term->term_id );
		echo '</td></tr>';
	}
	private static function control( string $taxonomy, int $id ): void {
		wp_nonce_field( 'psi_save_term', 'psi_term_nonce' );
		echo '<p>' . esc_html( 'psi_marca' === $taxonomy ? __( 'Logo de marca', 'psindustrial-core' ) : __( 'Imagen de categoría', 'psindustrial-core' ) ) . '</p>';
		ProductEditor::media_control( 'psi_term_image', $id ? (int) get_term_meta( $id, self::image_key( $taxonomy ), true ) : 0, 'single' );
	}
	public static function save( int $id ): void {
		$term = get_term( $id );
		if ( ! $term || is_wp_error( $term ) || ! current_user_can( get_taxonomy( $term->taxonomy )->cap->edit_terms ) ) {
			return;
		}
		$nonce = $_POST['psi_term_nonce'] ?? '';
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'psi_save_term' ) || ! isset( $_POST['psi_term_image'] ) ) {
			return;
		}
		$value = wp_unslash( $_POST['psi_term_image'] );
		if ( ! self::valid( $value ) ) {
			ProductEditor::error( __( 'El logo o imagen debe ser un adjunto de imagen válido.', 'psindustrial-core' ) );
			return;
		}
		update_term_meta( $id, self::image_key( $term->taxonomy ), (int) $value );
	}
}

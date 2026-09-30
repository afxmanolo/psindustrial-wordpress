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
		add_filter( 'delete_term_metadata', static function( $check, $id, $key ) {
			if ( ( Fields::private_key( $key ) && ! current_user_can( 'psi_manage_migration' ) ) || ( '_psi_public_state' === $key && TermPolicy::is_public( $id ) && ! current_user_can( 'psi_manage_routes' ) ) ) { return false; }
			return $check;
		}, 10, 3 );
		add_filter( 'rest_request_before_callbacks', array( self::class, 'rest_validate' ), 10, 3 );
	}
	public static function image_key( string $taxonomy ): string {
		return 'psi_marca' === $taxonomy ? '_psi_logo_id' : '_psi_image_id';
	}
	public static function register_meta(): void {
		foreach ( array( 'psi_categoria', 'psi_marca' ) as $taxonomy ) {
			foreach ( self::definitions() as $key => $schema ) {
				register_term_meta( $taxonomy, $key, array(
					'type' => $schema['type'], 'single' => true, 'default' => $schema['default'],
					'show_in_rest' => Fields::private_key( $key ) ? false : array( 'schema' => $schema ),
					'auth_callback' => static fn() => current_user_can( Fields::private_key( $key ) ? 'psi_manage_migration' : get_taxonomy( $taxonomy )->cap->edit_terms ),
					'sanitize_callback' => static fn( $value ) => '_psi_order' === $key ? $value : ( is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_text_field( $value ) ),
				) );
			}
			register_term_meta( $taxonomy, self::image_key( $taxonomy ), array(
				'type' => 'integer', 'single' => true, 'default' => 0,
				'show_in_rest' => array( 'schema' => array( 'type' => 'integer', 'minimum' => 0 ) ),
				'sanitize_callback' => static fn( $value ) => $value,
				'auth_callback' => static fn() => current_user_can( get_taxonomy( $taxonomy )->cap->edit_terms ),
			) );
		}
	}
	public static function definitions(): array {
		$fields = Fields::definitions();
		return array(
			'_psi_h1' => $fields['_psi_h1'], '_psi_source_keys' => $fields['_psi_source_keys'], '_psi_source_hash' => $fields['_psi_source_hash'],
			'_psi_order' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
			'_psi_public_state' => array( 'type' => 'string', 'enum' => array( 'review', 'public' ), 'default' => 'review' ),
		);
	}
	private static function valid( mixed $value ): bool {
		return ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) && (int) $value >= 0 && ( 0 === (int) $value || Media::valid( (int) $value, 'image' ) );
	}
	public static function guard( mixed $check, int $id, string $key, mixed $value, mixed $unused ): mixed {
		if ( isset( self::definitions()[ $key ] ) ) {
			if ( is_wp_error( rest_validate_value_from_schema( $value, self::definitions()[ $key ], $key ) ) || ( Fields::private_key( $key ) && ! current_user_can( 'psi_manage_migration' ) ) ) { return false; }
			if ( '_psi_public_state' === $key && 'public' !== $value && TermPolicy::is_public( $id ) && ! current_user_can( 'psi_manage_routes' ) ) { return false; }
		}
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
			if ( isset( self::definitions()[ $key ] ) && is_wp_error( rest_validate_value_from_schema( $value, self::definitions()[ $key ], $key ) ) ) {
				return new \WP_Error( 'psi_term_meta', __( 'Revise los datos del término.', 'psindustrial-core' ), array( 'status' => 400 ) );
			}
			if ( '_psi_public_state' === $key && 'public' !== $value && TermPolicy::is_public( (int) $request['id'] ) && ! current_user_can( 'psi_manage_routes' ) ) {
				return new \WP_Error( 'psi_term_withdraw', __( 'Solicite al administrador la retirada del término público.', 'psindustrial-core' ), array( 'status' => 403 ) );
			}
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
		foreach ( array( '_psi_h1' => __( 'Encabezado alternativo (opcional)', 'psindustrial-core' ), '_psi_order' => __( 'Orden', 'psindustrial-core' ) ) as $key => $label ) {
			$value = $id ? get_term_meta( $id, $key, true ) : self::definitions()[ $key ]['default'];
			printf( '<p><label>%s <input name="psi_term_fields[%s]" type="%s" value="%s" %s></label></p>', esc_html( $label ), esc_attr( $key ), '_psi_order' === $key ? 'number' : 'text', esc_attr( $value ), '_psi_order' === $key ? 'min="0" step="1"' : 'maxlength="200"' );
		}
		$state = $id ? get_term_meta( $id, '_psi_public_state', true ) : 'review';
		echo '<p><label>' . esc_html__( 'Visibilidad', 'psindustrial-core' ) . ' <select name="psi_term_fields[_psi_public_state]">';
		foreach ( array( 'review' => __( 'En revisión (oculto)', 'psindustrial-core' ), 'public' => __( 'Revisado y público', 'psindustrial-core' ) ) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $state, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label></p><p class="description">' . esc_html__( 'El logo o imagen se comparte desde Medios. Retirarlo aquí no elimina el archivo. Un término en revisión no tiene archivo público.', 'psindustrial-core' ) . '</p>';
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
		$input = $_POST['psi_term_fields'] ?? array();
		if ( is_array( $input ) ) {
			foreach ( array( '_psi_h1', '_psi_order', '_psi_public_state' ) as $key ) {
				if ( ! array_key_exists( $key, $input ) ) { continue; }
				$value = wp_unslash( $input[ $key ] );
				if ( is_wp_error( rest_validate_value_from_schema( $value, self::definitions()[ $key ], $key ) ) ) { ProductEditor::error( __( 'Valor de término inválido; se conservó el anterior.', 'psindustrial-core' ) ); continue; }
				if ( '_psi_public_state' === $key && 'public' !== $value && TermPolicy::is_public( $id ) && ! current_user_can( 'psi_manage_routes' ) ) { ProductEditor::error( __( 'Solicite al administrador la retirada del término público.', 'psindustrial-core' ) ); continue; }
				update_term_meta( $id, $key, '_psi_order' === $key ? (int) $value : $value );
			}
		}
	}
}

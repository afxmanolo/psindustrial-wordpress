<?php
namespace PSIndustrial\Core;
use PSIndustrial\Core\Migration\PdfApprovals;
defined( 'ABSPATH' ) || exit;

final class Fields {
	public static function definitions(): array {
		$id = array( 'type' => 'integer', 'minimum' => 1 );
		return array(
			'_psi_h1' => array( 'type' => 'string', 'default' => '', 'maxLength' => 200 ),
			'_psi_hero_id' => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
			'_psi_review_state' => array( 'type' => 'string', 'default' => 'pending', 'enum' => array( 'pending', 'approved' ) ),
			'_psi_related_ids' => array( 'type' => 'array', 'default' => array(), 'maxItems' => 50, 'uniqueItems' => true, 'items' => $id ),
			'_psi_source_keys' => array( 'type' => 'array', 'default' => array(), 'uniqueItems' => true, 'items' => array( 'type' => 'string', 'pattern' => '^[a-zA-Z0-9_-]+:.+$', 'maxLength' => 250 ) ),
			'_psi_source_hash' => array( 'type' => 'string', 'default' => '', 'pattern' => '^(?:[a-f0-9]{64})?$' ),
			'_psi_legacy_date' => array( 'type' => 'string', 'default' => '', 'maxLength' => 100 ),
			'_psi_primary_category_id' => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
			'_psi_gallery_ids' => array( 'type' => 'array', 'default' => array(), 'maxItems' => 50, 'uniqueItems' => true, 'items' => $id ),
			'_psi_datasheets' => array(
				'type' => 'array', 'default' => array(), 'maxItems' => 50,
				'items' => array( 'type' => 'object', 'additionalProperties' => false,
					'required' => array( 'attachment_id', 'label' ),
					'properties' => array( 'attachment_id' => $id, 'label' => array( 'type' => 'string', 'maxLength' => 200 ), 'language' => array( 'type' => 'string', 'enum' => array( '', 'es', 'en' ) ) ),
				),
			),
			'_psi_videos' => array(
				'type' => 'array', 'default' => array(), 'maxItems' => 10,
				'items' => array( 'type' => 'object', 'additionalProperties' => false,
					'required' => array( 'provider', 'video_id', 'title' ),
					'properties' => array(
						'provider' => array( 'type' => 'string', 'enum' => array( 'youtube' ) ),
						'video_id' => array( 'type' => 'string', 'pattern' => '^[A-Za-z0-9_-]{11}$' ),
						'title' => array( 'type' => 'string', 'maxLength' => 200 ),
					),
				),
			),
		);
	}
	public static function register(): void {
		foreach ( self::definitions() as $key => $schema ) {
			foreach ( array( 'psi_producto', 'page', 'attachment' ) as $type ) {
				if ( 'page' === $type && ! in_array( $key, array( '_psi_h1', '_psi_hero_id', '_psi_review_state', '_psi_source_keys', '_psi_source_hash' ), true ) ) { continue; }
				if ( 'attachment' === $type && '_psi_source_keys' !== $key ) { continue; }
			register_post_meta( $type, $key, array(
				'type' => $schema['type'],
				'single' => true,
				'default' => $schema['default'],
				'revisions_enabled' => 'attachment' !== $type && ! self::private_key( $key ),
				'show_in_rest' => self::private_key( $key ) ? false : array( 'schema' => $schema ),
				'auth_callback' => static fn( $allowed, $meta_key, $post_id ) => current_user_can( self::private_key( $key ) ? 'psi_manage_migration' : 'edit_post', $post_id ),
				'sanitize_callback' => static fn( $value ) => self::sanitize( $key, $value ),
			) );
			}
		}
		foreach ( array( '_psi_content_sha256', '_psi_original_name' ) as $key ) {
			register_post_meta( 'attachment', $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'auth_callback' => static fn() => current_user_can( 'psi_manage_migration' ), 'sanitize_callback' => 'sanitize_text_field' ) );
		}
		add_filter( 'add_post_metadata', array( self::class, 'guard' ), 10, 5 );
		add_filter( 'update_post_metadata', array( self::class, 'guard' ), 10, 5 );
		add_filter( 'delete_post_metadata', static function( $check, $id, $key ) {
			if ( ( self::private_key( $key ) && ! current_user_can( 'psi_manage_migration' ) ) || ( '_psi_review_state' === $key && in_array( get_post_status( $id ), array( 'publish', 'future' ), true ) ) ) { return false; }
			return $check;
		}, 10, 3 );
		add_filter( 'rest_pre_insert_psi_producto', array( self::class, 'validate_rest' ), 10, 2 );
		add_filter( 'rest_pre_insert_page', array( self::class, 'validate_rest' ), 10, 2 );
		add_action( 'set_object_terms', array( self::class, 'one_brand' ), 10, 6 );
		add_action( 'set_object_terms', array( self::class, 'clear_primary' ), 10, 4 );
	}
	public static function private_key( string $key ): bool {
		return in_array( $key, array( '_psi_source_keys', '_psi_source_hash', '_psi_legacy_date', '_psi_content_sha256', '_psi_original_name' ), true );
	}
	public static function validate( string $key, mixed $value, int $post_id = 0 ): true|\WP_Error {
		$schema = self::definitions()[ $key ] ?? null;
		if ( ! $schema ) {
			return new \WP_Error( 'psi_unknown_field', __( 'Campo desconocido.', 'psindustrial-core' ) );
		}
		$result = rest_validate_value_from_schema( $value, $schema, $key );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$invalid = false;
		if ( '_psi_gallery_ids' === $key ) {
			foreach ( $value as $id ) {
				$invalid = $invalid || ! Media::valid( (int) $id, 'image' );
			}
		}
		if ( '_psi_datasheets' === $key ) {
			foreach ( $value as $item ) {
				$invalid = $invalid || ! self::datasheet_valid( (int) $item['attachment_id'] );
			}
		}
		if ( '_psi_hero_id' === $key && (int) $value > 0 ) { $invalid = ! Media::valid( (int) $value, 'image' ); }
		if ( '_psi_related_ids' === $key ) {
			foreach ( $value as $id ) { $invalid = $invalid || (int) $id === $post_id || 'psi_producto' !== get_post_type( (int) $id ) || 'trash' === get_post_status( (int) $id ); }
		}
		if ( '_psi_primary_category_id' === $key && (int) $value > 0 ) {
			$invalid = ! term_exists( (int) $value, 'psi_categoria' );
		}
		return $invalid ? new \WP_Error( 'psi_invalid_relation', __( 'Seleccione medios o categorías existentes del tipo correcto.', 'psindustrial-core' ), array( 'status' => 400 ) ) : true;
	}
	/**
	 * A PDF datasheet is valid on Media::valid()'s own merits, OR when it is the EXACT,
	 * already human-reviewed PdfApprovals Group B exception recorded for that attachment:
	 * gated by the attachment's OWN recorded legacy path (_psi_import_origin, written once
	 * at import time, never guessed here) and its CURRENT on-disk sha256 -- never by
	 * filename or attachment id alone. Mirrors Runner::approved_datasheets_override()'s
	 * per-item check (the one-time import-write fix for the same gap, see
	 * docs/implementation/full-local-import/21-recovery-execution-result.md), so an
	 * approved attachment stays valid on every later admin/REST edit, not only at the
	 * moment of import. Never touches Media::valid()/Fields::guard() for any other field;
	 * an attachment with no matching approval stays rejected exactly as before.
	 */
	private static function datasheet_valid( int $attachment_id ): bool {
		if ( Media::valid( $attachment_id, 'pdf' ) ) {
			return true;
		}
		$origin = get_post_meta( $attachment_id, '_psi_import_origin', true );
		$legacy_path = is_array( $origin ) ? (string) ( $origin['file'] ?? '' ) : '';
		$file = $legacy_path ? get_attached_file( $attachment_id ) : false;
		if ( '' === $legacy_path || 'application/pdf' !== get_post_mime_type( $attachment_id ) || ! $file || ! is_file( $file ) ) {
			return false;
		}
		return PdfApprovals::isApprovedFalsePositive( $legacy_path, hash_file( 'sha256', $file ) );
	}
	public static function sanitize( string $key, mixed $value ): mixed {
		if ( is_wp_error( self::validate( $key, $value ) ) ) { return $value; } // Guards reject invalid values, never coerce them into valid IDs.
		if ( '_psi_source_keys' === $key ) { return array_values( array_unique( array_map( 'sanitize_text_field', $value ) ) ); }
		if ( in_array( $key, array( '_psi_review_state', '_psi_source_hash', '_psi_legacy_date' ), true ) ) { return sanitize_text_field( $value ); }
		if ( '_psi_h1' === $key ) {
			return mb_substr( sanitize_text_field( is_scalar( $value ) ? (string) $value : '' ), 0, 200 );
		}
		if ( in_array( $key, array( '_psi_primary_category_id', '_psi_hero_id' ), true ) ) {
			return absint( $value );
		}
		if ( in_array( $key, array( '_psi_gallery_ids', '_psi_related_ids' ), true ) ) {
			return array_values( array_unique( array_map( 'absint', (array) $value ) ) );
		}
		$result = array();
		foreach ( (array) $value as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( '_psi_datasheets' === $key ) {
				$result[] = array( 'attachment_id' => absint( $item['attachment_id'] ?? 0 ), 'label' => sanitize_text_field( $item['label'] ?? '' ), 'language' => sanitize_key( $item['language'] ?? '' ) );
			} else {
				$result[] = array( 'provider' => 'youtube', 'video_id' => sanitize_text_field( $item['video_id'] ?? '' ), 'title' => sanitize_text_field( $item['title'] ?? '' ) );
			}
		}
		return $result;
	}
	public static function guard( mixed $check, int $id, string $key, mixed $value, mixed $unused ): mixed {
		if ( self::private_key( $key ) && ! current_user_can( 'psi_manage_migration' ) ) { return false; }
		if ( '_psi_content_sha256' === $key && ( ! is_string( $value ) || ! preg_match( '/^(?:[a-f0-9]{64})?$/D', $value ) ) ) { return false; }
		if ( '_psi_review_state' === $key && 'approved' !== $value && in_array( get_post_status( $id ), array( 'publish', 'future' ), true ) ) { return false; }
		if ( '_thumbnail_id' === $key && (int) $value > 0 && ( ! Media::valid( (int) $value, 'image' ) || in_array( (int) $value, (array) get_post_meta( $id, '_psi_gallery_ids', true ), true ) ) ) { return false; }
		if ( '_psi_gallery_ids' === $key && is_array( $value ) && in_array( (int) get_post_thumbnail_id( $id ), array_map( 'intval', $value ), true ) ) { return false; }
		if ( 'psi_producto' === get_post_type( $id ) && '_psi_primary_category_id' === $key && (int) $value && ! has_term( (int) $value, 'psi_categoria', $id ) ) {
			return false;
		}
		if ( in_array( get_post_type( $id ), array( 'psi_producto', 'page', 'attachment' ), true ) && isset( self::definitions()[ $key ] ) && is_wp_error( self::validate( $key, $value, $id ) ) ) {
			return false;
		}
		return $check;
	}
	public static function validate_rest( mixed $prepared, \WP_REST_Request $request ): mixed {
		if ( is_wp_error( $prepared ) ) { return $prepared; }
		foreach ( (array) $request->get_param( 'meta' ) as $key => $value ) {
			if ( isset( self::definitions()[ $key ] ) ) {
				$result = self::validate( $key, $value, (int) $request['id'] );
				if ( is_wp_error( $result ) ) {
					$result->add_data( array( 'status' => 400 ) );
					return $result;
				}
			}
		}
		$brands = $request->get_param( 'psi_marca' );
		$meta = (array) $request->get_param( 'meta' );
		$primary = (int) ( $meta['_psi_primary_category_id'] ?? get_post_meta( (int) $request['id'], '_psi_primary_category_id', true ) );
		$categories = $request->get_param( 'psi_categoria' );
		if ( $primary && ( is_array( $categories ) ? ! in_array( $primary, array_map( 'intval', $categories ), true ) : ! has_term( $primary, 'psi_categoria', (int) $request['id'] ) ) ) {
			return new \WP_Error( 'psi_primary_category', __( 'La categoría principal debe estar asignada al producto.', 'psindustrial-core' ), array( 'status' => 400 ) );
		}
		if ( is_array( $brands ) && count( array_unique( $brands ) ) > 1 ) {
			return new \WP_Error( 'psi_one_brand', __( 'Seleccione como máximo una marca.', 'psindustrial-core' ), array( 'status' => 400 ) );
		}
		return $prepared;
	}
	public static function clear_primary( int $id, array $terms, array $tt_ids, string $taxonomy ): void {
		if ( 'psi_categoria' === $taxonomy && 'psi_producto' === get_post_type( $id ) ) {
			$primary = (int) get_post_meta( $id, '_psi_primary_category_id', true );
			if ( $primary && ! has_term( $primary, $taxonomy, $id ) ) { update_post_meta( $id, '_psi_primary_category_id', 0 ); }
		}
	}
	public static function one_brand( int $id, array $terms, array $tt_ids, string $taxonomy, bool $append, array $old ): void {
		if ( 'psi_marca' !== $taxonomy || 'psi_producto' !== get_post_type( $id ) ) {
			return;
		}
		$assigned = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $assigned ) && count( $assigned ) > 1 ) {
			// Restore the previous relation; never choose a brand arbitrarily.
			$previous = array();
			foreach ( $old as $tt_id ) {
				$term = get_term_by( 'term_taxonomy_id', $tt_id, $taxonomy );
				if ( $term ) { $previous[] = (int) $term->term_id; }
			}
			wp_set_object_terms( $id, count( $previous ) <= 1 ? $previous : array(), $taxonomy, false );
			ProductEditor::error( __( 'Seleccione como máximo una marca. Se conservó la relación anterior.', 'psindustrial-core' ) );
		}
	}
}

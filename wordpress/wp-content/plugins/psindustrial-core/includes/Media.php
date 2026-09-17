<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class Media {
	public static function boot(): void {
		add_filter( 'upload_mimes', static fn() => array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf' ) );
		add_filter( 'upload_size_limit', static fn( $size ) => min( $size, 20 * MB_IN_BYTES ) );
		add_filter( 'wp_handle_upload_prefilter', array( self::class, 'upload' ) );
		add_filter( 'wp_handle_sideload_prefilter', array( self::class, 'upload' ) );
		add_filter( 'pre_delete_attachment', array( self::class, 'deletion' ), 10, 3 );
	}
	public static function file_valid( string $path, string $mime ): bool {
		if ( ! is_file( $path ) || is_link( $path ) ) { return false; }
		$limit = 'application/pdf' === $mime ? 20 * MB_IN_BYTES : 10 * MB_IN_BYTES;
		if ( filesize( $path ) > $limit || filesize( $path ) < 1 ) { return false; }
		if ( 'application/pdf' === $mime ) {
			$data = file_get_contents( $path );
			return str_starts_with( $data, '%PDF-' ) && 'application/pdf' === ( new \finfo( FILEINFO_MIME_TYPE ) )->file( $path )
				&& ! preg_match( '#/(?:JavaScript|JS|OpenAction|Launch|EmbeddedFile)\b#i', $data );
		}
		$size = wp_getimagesize( $path );
		return in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) && $size && ( $size['mime'] ?? '' ) === $mime && $size[0] <= 8000 && $size[1] <= 8000 && $size[0] * $size[1] <= 40000000;
	}
	public static function valid( int $id, string $kind ): bool {
		if ( 'attachment' !== get_post_type( $id ) || 'trash' === get_post_status( $id ) ) { return false; }
		$mime = get_post_mime_type( $id );
		return ( 'pdf' === $kind ? 'application/pdf' === $mime : str_starts_with( (string) $mime, 'image/' ) )
			&& self::file_valid( (string) get_attached_file( $id ), (string) $mime );
	}
	public static function upload( array $file ): array {
		if ( ! empty( $file['error'] ) ) { return $file; }
		$type = wp_check_filetype( $file['name'], array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf' ) );
		if ( ! $type['type'] || preg_match( '/\.(?:php[0-9]?|phtml|phar|exe|html|js)\./i', $file['name'] ) || ! self::file_valid( $file['tmp_name'], $type['type'] ) ) {
			$file['error'] = __( 'Archivo no permitido: use JPG, PNG o WebP hasta 10 MB (máximo 8000 px y 40 megapíxeles), o PDF hasta 20 MB sin contenido activo detectado.', 'psindustrial-core' );
		}
		return $file;
	}
	public static function deletion( mixed $delete, \WP_Post $post, bool $force ): mixed {
		if ( ! current_user_can( 'manage_options' ) || self::referenced( $post->ID ) ) { return false; }
		return $delete;
	}
	public static function referenced( int $id ): bool {
		// Conservative scan on deletion only; never run for each admin list row.
		$url = wp_get_attachment_url( $id );
		foreach ( get_posts( array( 'post_type' => array( 'page', 'psi_producto', 'wp_block' ), 'post_status' => 'any', 'numberposts' => -1 ) ) as $post ) {
			if ( (int) get_post_thumbnail_id( $post->ID ) === $id || (int) get_post_meta( $post->ID, '_psi_hero_id', true ) === $id || in_array( $id, (array) get_post_meta( $post->ID, '_psi_gallery_ids', true ), true ) ) { return true; }
			foreach ( (array) get_post_meta( $post->ID, '_psi_datasheets', true ) as $pdf ) { if ( is_array( $pdf ) && (int) ( $pdf['attachment_id'] ?? 0 ) === $id ) { return true; } }
			if ( ( $url && str_contains( $post->post_content, $url ) ) || preg_match( '/(?:wp-image-|\"id\"\s*:\s*)' . $id . '\b/', $post->post_content ) ) { return true; }
		}
		foreach ( array( 'psi_categoria' => '_psi_image_id', 'psi_marca' => '_psi_logo_id' ) as $tax => $key ) {
			$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'psi_include_review' => true, 'meta_key' => $key, 'meta_value' => $id, 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) && $terms ) { return true; }
		}
		return false;
	}
}

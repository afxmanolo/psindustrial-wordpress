<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class ProductEditor {
	public static function boot(): void {
		add_action( 'add_meta_boxes_psi_producto', array( self::class, 'box' ) );
		add_action( 'save_post_psi_producto', array( self::class, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'admin_notices', array( self::class, 'notices' ) );
	}
	public static function box(): void {
		add_meta_box( 'psi-product-fields', __( 'Datos del producto', 'psindustrial-core' ), array( self::class, 'render' ), 'psi_producto', 'normal', 'default', array( '__block_editor_compatible_meta_box' => true ) );
	}
	public static function assets(): void {
		$screen = get_current_screen();
		if ( ! $screen || ( 'psi_producto' !== $screen->post_type && ! in_array( $screen->taxonomy, array( 'psi_categoria', 'psi_marca' ), true ) ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'psi-admin-fields', plugins_url( 'assets/admin-fields.js', PSINDUSTRIAL_CORE_FILE ), array( 'media-views' ), PSINDUSTRIAL_CORE_VERSION, true );
		wp_localize_script( 'psi-admin-fields', 'psiMediaLabels', array(
			'choose' => __( 'Seleccionar medios', 'psindustrial-core' ),
			'use' => __( 'Usar selección', 'psindustrial-core' ),
			'remove' => __( 'Quitar', 'psindustrial-core' ),
			'up' => __( 'Subir', 'psindustrial-core' ),
			'down' => __( 'Bajar', 'psindustrial-core' ),
			'label' => __( 'Etiqueta del PDF', 'psindustrial-core' ),
			'item' => __( 'Adjunto', 'psindustrial-core' ),
		) );
	}
	public static function media_control( string $name, mixed $value, string $type ): void {
		?>
		<div class="psi-media-control" data-kind="<?php echo esc_attr( $type ); ?>">
			<input class="psi-media-value" type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>">
			<ol class="psi-media-list"></ol>
			<button class="button psi-media-select" type="button"><?php esc_html_e( 'Seleccionar medios', 'psindustrial-core' ); ?></button>
		</div>
		<?php
	}
	public static function render( \WP_Post $post ): void {
		wp_nonce_field( 'psi_save_product', 'psi_product_nonce' );
		?>
		<p><label for="psi-h1"><?php esc_html_e( 'H1 alternativo (opcional)', 'psindustrial-core' ); ?></label>
		<input class="widefat" id="psi-h1" name="psi_fields[_psi_h1]" maxlength="200" value="<?php echo esc_attr( get_post_meta( $post->ID, '_psi_h1', true ) ); ?>"></p>
		<p><label for="psi-primary"><?php esc_html_e( 'Categoría principal (debe estar asignada al producto)', 'psindustrial-core' ); ?></label>
		<select id="psi-primary" name="psi_fields[_psi_primary_category_id]"><option value="0"><?php esc_html_e( 'Sin categoría principal', 'psindustrial-core' ); ?></option>
		<?php
		$terms = get_terms( array( 'taxonomy' => 'psi_categoria', 'hide_empty' => false ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				printf( '<option value="%d" %s>%s</option>', (int) $term->term_id, selected( (int) get_post_meta( $post->ID, '_psi_primary_category_id', true ), (int) $term->term_id, false ), esc_html( $term->name ) );
			}
		}
		?>
		</select></p>
		<h3><?php esc_html_e( 'Galería ordenada', 'psindustrial-core' ); ?></h3>
		<?php self::media_control( 'psi_fields[_psi_gallery_ids]', get_post_meta( $post->ID, '_psi_gallery_ids', true ), 'gallery' ); ?>
		<h3><?php esc_html_e( 'Fichas técnicas PDF', 'psindustrial-core' ); ?></h3>
		<?php self::media_control( 'psi_fields[_psi_datasheets]', get_post_meta( $post->ID, '_psi_datasheets', true ), 'pdf' ); ?>
		<p><label for="psi-videos"><?php esc_html_e( 'Videos YouTube: una URL por línea', 'psindustrial-core' ); ?></label>
		<textarea class="widefat" rows="3" id="psi-videos" name="psi_video_urls"><?php
		$videos = (array) get_post_meta( $post->ID, '_psi_videos', true );
		echo esc_textarea( implode( "\n", array_map( static fn( $video ) => 'https://www.youtube.com/watch?v=' . $video['video_id'], $videos ) ) );
		?></textarea></p>
		<p><?php esc_html_e( 'La imagen destacada, descripción, resumen, categorías y marca se editan en los controles de WordPress. No se han importado datos legacy.', 'psindustrial-core' ); ?></p>
		<?php
	}
	public static function parse_videos( string $text ): array|\WP_Error {
		$videos = array();
		foreach ( preg_split( '/\R/u', trim( $text ) ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$url = wp_parse_url( $line );
			$id = '';
			if ( is_array( $url ) && in_array( $url['scheme'] ?? '', array( 'http', 'https' ), true ) ) {
				$host = strtolower( $url['host'] ?? '' );
				$path = $url['path'] ?? '';
				if ( 'youtu.be' === $host ) {
					$id = trim( $path, '/' );
				} elseif ( in_array( $host, array( 'youtube.com', 'www.youtube.com', 'm.youtube.com' ), true ) ) {
					parse_str( $url['query'] ?? '', $query );
					$id = '/watch' === $path ? ( $query['v'] ?? '' ) : ( preg_match( '#^/(?:embed|shorts)/([A-Za-z0-9_-]{11})/?$#', $path, $m ) ? $m[1] : '' );
				}
			}
			if ( ! is_string( $id ) || ! preg_match( '/^[A-Za-z0-9_-]{11}$/D', $id ) ) {
				return new \WP_Error( 'psi_video_url', __( 'Utilice únicamente URLs válidas de YouTube.', 'psindustrial-core' ) );
			}
			$videos[ $id ] = array( 'provider' => 'youtube', 'video_id' => $id, 'title' => __( 'Video del producto', 'psindustrial-core' ) );
		}
		return array_values( $videos );
	}
	public static function save( int $id ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $id ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$nonce = $_POST['psi_product_nonce'] ?? '';
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'psi_save_product' ) ) {
			return;
		}
		$input = isset( $_POST['psi_fields'] ) && is_array( $_POST['psi_fields'] ) ? wp_unslash( $_POST['psi_fields'] ) : array();
		$values = array();
		foreach ( array( '_psi_h1', '_psi_primary_category_id', '_psi_gallery_ids', '_psi_datasheets' ) as $key ) {
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			$value = $input[ $key ];
			if ( in_array( $key, array( '_psi_gallery_ids', '_psi_datasheets' ), true ) ) {
				$value = is_string( $value ) ? json_decode( $value, true ) : null;
			} elseif ( '_psi_primary_category_id' === $key && is_scalar( $value ) && ctype_digit( (string) $value ) ) {
				$value = (int) $value;
			}
			$result = Fields::validate( $key, $value, $id );
			if ( is_wp_error( $result ) ) {
				self::error( $result->get_error_message() );
				return;
			}
			if ( '_psi_primary_category_id' === $key && $value && ! has_term( $value, 'psi_categoria', $id ) ) {
				self::error( __( 'La categoría principal debe estar asignada al producto. Guarde primero la selección de categorías.', 'psindustrial-core' ) );
				return;
			}
			$values[ $key ] = $value;
		}
		if ( isset( $_POST['psi_video_urls'] ) ) {
			$videos = is_string( $_POST['psi_video_urls'] ) ? self::parse_videos( wp_unslash( $_POST['psi_video_urls'] ) ) : new \WP_Error( 'psi_video_input', __( 'Formato de video inválido.', 'psindustrial-core' ) );
			$result = is_wp_error( $videos ) ? $videos : Fields::validate( '_psi_videos', $videos );
			if ( is_wp_error( $result ) ) {
				self::error( $result->get_error_message() );
				return;
			}
			$values['_psi_videos'] = $videos;
		}
		foreach ( $values as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
	}
	public static function error( string $message ): void {
		if ( get_current_user_id() ) {
			set_transient( 'psi_field_error_' . get_current_user_id(), $message, 60 );
		}
	}
	public static function notices(): void {
		$message = get_transient( 'psi_field_error_' . get_current_user_id() );
		if ( $message ) {
			delete_transient( 'psi_field_error_' . get_current_user_id() );
			echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
		}
	}
}


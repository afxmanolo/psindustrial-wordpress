<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class ProductEditor {
	public static function boot(): void {
		add_action( 'add_meta_boxes_psi_producto', array( self::class, 'box' ) );
		add_action( 'save_post_psi_producto', array( self::class, 'save' ) );
		add_action( 'add_meta_boxes_page', array( self::class, 'box' ) );
		add_action( 'save_post_page', array( self::class, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'admin_notices', array( self::class, 'notices' ) );
	}
	public static function box( \WP_Post $post ): void {
		add_meta_box( 'psi-product-fields', 'page' === $post->post_type ? __( 'Revisión y presentación', 'psindustrial-core' ) : __( 'Datos del producto', 'psindustrial-core' ), array( self::class, 'render' ), $post->post_type, 'normal', 'default', array( '__block_editor_compatible_meta_box' => true ) );
		remove_meta_box( 'postcustom', $post->post_type, 'normal' );
		remove_meta_box( 'tagsdiv-psi_marca', 'psi_producto', 'side' );
	}
	public static function assets(): void {
		$screen = get_current_screen();
		if ( $screen && 'edit' === $screen->base && 'psi_producto' === $screen->post_type ) {
			wp_enqueue_style( 'psi-admin-fields', plugins_url( 'assets/admin-fields.css', PSINDUSTRIAL_CORE_FILE ), array(), PSINDUSTRIAL_CORE_VERSION );
			return;
		}
		if ( ! $screen || ! ( ( 'post' === $screen->base && in_array( $screen->post_type, array( 'psi_producto', 'page' ), true ) ) || ( in_array( $screen->base, array( 'edit-tags', 'term' ), true ) && in_array( $screen->taxonomy, array( 'psi_categoria', 'psi_marca' ), true ) ) ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'psi-admin-fields', plugins_url( 'assets/admin-fields.js', PSINDUSTRIAL_CORE_FILE ), array( 'media-views', 'wp-data', 'wp-hooks', 'wp-element' ), PSINDUSTRIAL_CORE_VERSION, true );
		wp_enqueue_style( 'psi-admin-fields', plugins_url( 'assets/admin-fields.css', PSINDUSTRIAL_CORE_FILE ), array(), PSINDUSTRIAL_CORE_VERSION );
		wp_localize_script( 'psi-admin-fields', 'psiMediaLabels', array(
			'choose' => __( 'Seleccionar medios', 'psindustrial-core' ),
			'use' => __( 'Usar selección', 'psindustrial-core' ),
			'remove' => __( 'Quitar', 'psindustrial-core' ),
			'up' => __( 'Subir', 'psindustrial-core' ),
			'down' => __( 'Bajar', 'psindustrial-core' ),
			'label' => __( 'Etiqueta del PDF', 'psindustrial-core' ),
			'item' => __( 'Adjunto', 'psindustrial-core' ),
			'open' => __( 'Ver archivo (otra pestaña)', 'psindustrial-core' ),
			'replace' => __( 'Sustituir', 'psindustrial-core' ),
			'language' => __( 'Idioma del PDF', 'psindustrial-core' ),
			'unspecified' => __( 'Sin especificar', 'psindustrial-core' ),
			'spanish' => __( 'Español', 'psindustrial-core' ),
			'english' => __( 'Inglés', 'psindustrial-core' ),
			'url' => __( 'Enlace de YouTube', 'psindustrial-core' ),
			'title' => __( 'Título del video', 'psindustrial-core' ),
			'video' => __( 'Video del producto', 'psindustrial-core' ),
		) );
	}
	public static function media_control( string $name, mixed $value, string $type ): void {
		?>
		<div class="psi-media-control" data-kind="<?php echo esc_attr( $type ); ?>">
			<input class="psi-media-value" type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>">
			<ol class="psi-media-list"></ol>
			<button class="button psi-media-select" type="button"><?php echo esc_html( 'video' === $type ? __( 'Añadir video', 'psindustrial-core' ) : __( 'Seleccionar o subir archivos', 'psindustrial-core' ) ); ?></button>
		</div>
		<?php
	}
	public static function render( \WP_Post $post ): void {
		wp_nonce_field( 'psi_save_product', 'psi_product_nonce' );
		?>
		<p><label for="psi-review"><?php esc_html_e( 'Revisión del contenido', 'psindustrial-core' ); ?></label>
		<select id="psi-review" name="psi_fields[_psi_review_state]">
		<?php foreach ( array( 'pending' => __( 'Pendiente de revisión', 'psindustrial-core' ), 'approved' => __( 'Revisado: listo para publicar', 'psindustrial-core' ) ) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( get_post_meta( $post->ID, '_psi_review_state', true ), $value, false ) . '>' . esc_html( $label ) . '</option>';
		} ?></select></p>
		<p class="description"><?php esc_html_e( 'Revise nombre, descripción y archivos antes de publicar. Si no conoce la marca, déjela sin asignar.', 'psindustrial-core' ); ?></p>
		<p><label for="psi-h1"><?php esc_html_e( 'H1 alternativo (opcional)', 'psindustrial-core' ); ?></label>
		<input class="widefat" id="psi-h1" name="psi_fields[_psi_h1]" maxlength="200" value="<?php echo esc_attr( get_post_meta( $post->ID, '_psi_h1', true ) ); ?>"></p>
		<h3><?php esc_html_e( 'Imagen de cabecera (opcional)', 'psindustrial-core' ); ?></h3>
		<?php self::media_control( 'psi_fields[_psi_hero_id]', (int) get_post_meta( $post->ID, '_psi_hero_id', true ), 'single' ); ?>
		<?php if ( 'page' === $post->post_type ) { return; } ?>
		<p><label for="psi-order"><?php esc_html_e( 'Orden del producto', 'psindustrial-core' ); ?></label><input id="psi-order" name="psi_menu_order" type="number" min="0" step="1" value="<?php echo esc_attr( $post->menu_order ); ?>"></p>
		<p><label for="psi-brand"><?php esc_html_e( 'Marca', 'psindustrial-core' ); ?></label>
		<select id="psi-brand" name="psi_brand"><option value="0"><?php esc_html_e( 'Sin marca verificada', 'psindustrial-core' ); ?></option>
		<?php
		$brands = wp_get_object_terms( $post->ID, 'psi_marca', array( 'fields' => 'ids' ) );
		foreach ( get_terms( array( 'taxonomy' => 'psi_marca', 'hide_empty' => false ) ) as $brand ) {
			printf( '<option value="%d" %s>%s</option>', $brand->term_id, selected( $brands[0] ?? 0, $brand->term_id, false ), esc_html( $brand->name ) );
		}
		?></select></p>
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
		<h3><?php esc_html_e( 'Videos de YouTube (opcionales)', 'psindustrial-core' ); ?></h3>
		<?php self::media_control( 'psi_fields[_psi_videos]', get_post_meta( $post->ID, '_psi_videos', true ), 'video' ); ?>
		<p><label for="psi-related"><?php esc_html_e( 'Productos relacionados (selección manual)', 'psindustrial-core' ); ?></label>
		<input type="hidden" name="psi_related_present" value="1"><select id="psi-related" multiple size="5" name="psi_related[]">
		<?php
		$related = (array) get_post_meta( $post->ID, '_psi_related_ids', true );
		foreach ( get_posts( array( 'post_type' => 'psi_producto', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'exclude' => $post->ID ) ) as $candidate ) {
			printf( '<option value="%d" %s>%s</option>', $candidate->ID, selected( in_array( $candidate->ID, $related, true ), true, false ), esc_html( $candidate->post_title ) );
		}
		?></select></p>
		<p class="description"><?php esc_html_e( 'Seleccione relacionados con Ctrl o Cmd. La descripción, el resumen, la imagen destacada y las categorías se editan con los controles de WordPress. Retirar un archivo aquí conserva el original en Medios.', 'psindustrial-core' ); ?></p>
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
		foreach ( array( '_psi_h1', '_psi_hero_id', '_psi_review_state', '_psi_primary_category_id', '_psi_gallery_ids', '_psi_datasheets', '_psi_videos' ) as $key ) {
			if ( 'page' === get_post_type( $id ) && ! in_array( $key, array( '_psi_h1', '_psi_hero_id', '_psi_review_state' ), true ) ) { continue; }
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			$value = $input[ $key ];
			if ( in_array( $key, array( '_psi_gallery_ids', '_psi_datasheets', '_psi_videos' ), true ) ) {
				$value = is_string( $value ) ? json_decode( $value, true ) : null;
			} elseif ( in_array( $key, array( '_psi_primary_category_id', '_psi_hero_id' ), true ) && is_scalar( $value ) && ctype_digit( (string) $value ) ) {
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
		if ( 'psi_producto' === get_post_type( $id ) && isset( $_POST['psi_related_present'] ) ) {
			$related = $_POST['psi_related'] ?? array();
			if ( ! is_array( $related ) || is_wp_error( Fields::validate( '_psi_related_ids', $related, $id ) ) ) { self::error( __( 'Seleccione productos relacionados válidos.', 'psindustrial-core' ) ); return; }
			$values['_psi_related_ids'] = array_map( 'intval', $related );
		}
		$featured = (int) get_post_thumbnail_id( $id );
		if ( $featured && in_array( $featured, $values['_psi_gallery_ids'] ?? array(), true ) ) { self::error( __( 'La imagen destacada no debe repetirse en la galería.', 'psindustrial-core' ) ); return; }
		if ( 'psi_producto' === get_post_type( $id ) && isset( $_POST['psi_brand'] ) && current_user_can( 'psi_assign_brands' ) ) {
			$brand = $_POST['psi_brand'];
			if ( ! is_scalar( $brand ) || ! ctype_digit( (string) $brand ) || ( (int) $brand && ! term_exists( (int) $brand, 'psi_marca' ) ) ) { self::error( __( 'Seleccione una marca válida.', 'psindustrial-core' ) ); return; }
			wp_set_object_terms( $id, (int) $brand ? array( (int) $brand ) : array(), 'psi_marca' );
		}
		if ( 'psi_producto' === get_post_type( $id ) && isset( $_POST['psi_video_urls'] ) ) {
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

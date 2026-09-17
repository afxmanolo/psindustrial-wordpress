<?php
defined( 'ABSPATH' ) || exit;
$gallery = get_post_meta( get_the_ID(), '_psi_gallery_ids', true );
$pdfs = get_post_meta( get_the_ID(), '_psi_datasheets', true );
$videos = get_post_meta( get_the_ID(), '_psi_videos', true );
?>
<?php if ( is_array( $gallery ) && $gallery ) : ?>
<section aria-label="<?php esc_attr_e( 'Galería del producto', 'psindustrial' ); ?>" class="product-grid">
<?php foreach ( $gallery as $id ) { echo wp_get_attachment_image( (int) $id, 'large' ); } ?>
</section>
<?php endif; ?>
<?php if ( is_array( $pdfs ) && $pdfs ) : ?>
<section><h2><?php esc_html_e( 'Fichas técnicas', 'psindustrial' ); ?></h2><ul>
<?php foreach ( $pdfs as $pdf ) : ?>
	<?php $url = wp_get_attachment_url( (int) $pdf['attachment_id'] ); ?>
	<?php if ( $url ) : ?><li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $pdf['label'] ?: __( 'Descargar ficha PDF', 'psindustrial' ) ); ?></a> (PDF)</li><?php endif; ?>
<?php endforeach; ?>
</ul></section>
<?php endif; ?>
<?php if ( is_array( $videos ) && $videos ) : ?>
<section><h2><?php esc_html_e( 'Videos', 'psindustrial' ); ?></h2><ul>
<?php foreach ( $videos as $video ) : ?>
<li><a href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . rawurlencode( $video['video_id'] ) ); ?>" rel="noopener"><?php echo esc_html( $video['title'] ?: __( 'Ver video', 'psindustrial' ) ); ?></a></li>
<?php endforeach; ?>
</ul></section>
<?php endif; ?>


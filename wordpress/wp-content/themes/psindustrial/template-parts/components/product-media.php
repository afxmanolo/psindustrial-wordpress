<?php
defined( 'ABSPATH' ) || exit;
$pdfs = \PSIndustrial\Theme\product_datasheets( get_the_ID() );
?>
<?php if ( $pdfs ) : ?>
<div class="product-datasheets" aria-label="<?php esc_attr_e( 'Fichas técnicas', 'psindustrial' ); ?>">
	<?php foreach ( $pdfs as $index => $pdf ) : ?>
	<a class="datasheet-button" href="<?php echo esc_url( $pdf['url'] ); ?>" target="_blank" rel="noopener">
		<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/ver-ficha-tecnica.png' ) ); ?>" width="247" height="59" alt="<?php esc_attr_e( 'Ver ficha técnica', 'psindustrial' ); ?>">
		<?php if ( count( $pdfs ) > 1 ) : ?><span><?php echo esc_html( $pdf['label'] ?: sprintf( __( 'Documento técnico %d', 'psindustrial' ), $index + 1 ) ); ?></span><?php endif; ?>
		<span class="screen-reader-text"><?php esc_html_e( 'PDF, se abre en una pestaña nueva', 'psindustrial' ); ?></span>
	</a>
	<?php endforeach; ?>
</div>
<?php endif; ?>

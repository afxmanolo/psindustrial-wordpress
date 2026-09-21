<?php
defined( 'ABSPATH' ) || exit;
$id = absint( $args['product_id'] ?? get_the_ID() );
$images = \PSIndustrial\Theme\product_images( $id );
$terms = get_the_terms( $id, 'psi_categoria' );
if ( is_tax( 'psi_categoria' ) && is_array( $terms ) ) {
	$terms = array_filter( $terms, static fn( $term ) => $term->term_id !== get_queried_object_id() );
}
?>
<article class="product-card">
	<a class="product-card-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
		<?php if ( $images ) : ?><span class="product-card-visual">
			<?php echo wp_get_attachment_image( $images[0], 'medium_large', false, array( 'class' => 'product-card-image', 'alt' => '', 'sizes' => '(min-width: 992px) 240px, (min-width: 576px) 320px, calc(100vw - 62px)' ) ); ?>
			<span class="product-card-arrow psi-icon icon-arrow" aria-hidden="true"></span>
		</span><?php endif; ?>
		<h2><?php echo esc_html( get_the_title( $id ) ); ?></h2>
	</a>
	<?php if ( $terms && ! is_wp_error( $terms ) ) : ?><p class="product-card-categories"><?php echo esc_html( implode( ' · ', wp_list_pluck( $terms, 'name' ) ) ); ?></p><?php endif; ?>
	<?php if ( has_excerpt( $id ) ) : ?><p><?php echo esc_html( wp_trim_words( get_the_excerpt( $id ), 24 ) ); ?></p><?php endif; ?>
</article>

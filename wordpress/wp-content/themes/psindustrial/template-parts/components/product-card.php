<?php
defined( 'ABSPATH' ) || exit;
$id = absint( $args['product_id'] ?? get_the_ID() );
$images = \PSIndustrial\Theme\product_images( $id );
$brands = \PSIndustrial\Theme\product_brands( $id );
?>
<article class="product-card">
	<a class="product-card-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
		<?php if ( $images ) : ?><span class="product-card-visual">
			<?php echo wp_get_attachment_image( $images[0], 'medium_large', false, array( 'class' => 'product-card-image', 'alt' => '', 'sizes' => '(min-width: 992px) 240px, (min-width: 576px) 320px, calc(100vw - 62px)' ) ); ?>
			<span class="product-card-arrow psi-icon icon-arrow" aria-hidden="true"></span>
		</span><?php endif; ?>
		<h2><?php echo esc_html( get_the_title( $id ) ); ?></h2>
	</a>
	<?php foreach ( $brands as $brand ) : ?><p class="product-card-brand"><?php echo \PSIndustrial\Theme\brand_logo( $brand ); ?><span><?php echo esc_html( $brand->name ); ?></span></p><?php endforeach; ?>
	<?php if ( has_excerpt( $id ) ) : ?><p><?php echo esc_html( wp_trim_words( get_the_excerpt( $id ), 24 ) ); ?></p><?php endif; ?>
</article>

<?php defined( 'ABSPATH' ) || exit; ?>
<?php foreach ( array( 'psi_categoria' => __( 'Categorías', 'psindustrial' ), 'psi_marca' => __( 'Marca', 'psindustrial' ) ) as $taxonomy => $label ) : ?>
	<?php $terms = get_the_terms( get_the_ID(), $taxonomy ); ?>
	<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
		<div class="product-terms"><span class="product-label"><?php echo esc_html( $label ); ?></span><ul>
		<?php foreach ( $terms as $term ) : ?>
			<?php $url = get_term_link( $term ); if ( is_wp_error( $url ) ) { continue; } ?>
			<li><a href="<?php echo esc_url( $url ); ?>">
				<?php $logo = 'psi_marca' === $taxonomy ? (int) get_term_meta( $term->term_id, '_psi_logo_id', true ) : 0; ?>
				<?php if ( \PSIndustrial\Theme\product_image( $logo ) ) { echo wp_get_attachment_image( $logo, 'thumbnail', false, array( 'class' => 'product-brand-logo', 'alt' => '' ) ); } ?>
				<?php echo esc_html( $term->name ); ?>
			</a></li>
		<?php endforeach; ?>
		</ul></div>
	<?php endif; ?>
<?php endforeach; ?>

<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="catalog-main">
<?php while ( have_posts() ) : the_post(); ?>
<article <?php post_class( 'product-detail' ); ?>>
	<?php get_template_part( 'template-parts/components/catalog-heading', null, array( 'title' => get_post_meta( get_the_ID(), '_psi_h1', true ) ?: get_the_title() ) ); ?>
	<div class="psi-container product-body"><div class="catalog-columns">
		<?php get_template_part( 'template-parts/components/catalog-sidebar' ); ?>
		<div class="catalog-content">
			<?php $hero = (int) get_post_meta( get_the_ID(), '_psi_hero_id', true ); ?>
			<?php if ( \PSIndustrial\Theme\product_image( $hero ) ) { echo wp_get_attachment_image( $hero, 'large', false, array( 'class' => 'product-hero' ) ); } ?>
			<?php get_template_part( 'template-parts/components/product-media' ); ?>
			<?php $images = \PSIndustrial\Theme\product_images( get_the_ID() ); ?>
			<div class="product-layout<?php echo $images ? ' has-images' : ''; ?>">
				<div class="product-information">
					<?php if ( has_excerpt() ) : ?><p class="product-intro"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
					<div class="product-copy"><?php the_content(); wp_link_pages(); ?></div>
				</div>
				<?php if ( $images ) : ?>
				<section class="product-gallery" aria-label="<?php esc_attr_e( 'Imágenes del producto', 'psindustrial' ); ?>">
					<?php foreach ( $images as $index => $image_id ) : ?>
					<a class="product-gallery-item<?php echo 0 === $index ? ' is-primary' : ''; ?>" href="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'full' ) ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( sprintf( __( 'Ampliar imagen %1$d de %2$s (pestaña nueva)', 'psindustrial' ), $index + 1, get_the_title() ) ); ?>">
						<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'sizes' => '(min-width: 1400px) 377px, (min-width: 1200px) 350px, (min-width: 992px) 290px, (min-width: 768px) 330px, calc(100vw - 30px)' ) ); ?>
					</a>
					<?php endforeach; ?>
				</section>
				<?php endif; ?>
			</div>
			<?php get_template_part( 'template-parts/components/product-terms' ); ?>
			<?php get_template_part( 'template-parts/components/product-videos' ); ?>
			<?php $related = class_exists( \PSIndustrial\Core\Content::class ) ? \PSIndustrial\Core\Content::related( get_the_ID() ) : array(); ?>
			<?php if ( $related ) : ?>
			<section class="product-related"><h2><?php esc_html_e( 'Productos relacionados', 'psindustrial' ); ?></h2><div class="product-grid">
				<?php foreach ( $related as $item ) { get_template_part( 'template-parts/components/product-card', null, array( 'product_id' => $item->ID ) ); } ?>
			</div></section>
			<?php endif; ?>
		</div>
	</div></div>
</article>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>

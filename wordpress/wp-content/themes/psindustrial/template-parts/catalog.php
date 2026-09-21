<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="catalog-main">
	<?php get_template_part( 'template-parts/components/catalog-heading', null, array( 'title' => is_tax() ? ( get_term_meta( get_queried_object_id(), '_psi_h1', true ) ?: single_term_title( '', false ) ) : __( 'Productos', 'psindustrial' ), 'eyebrow' => is_tax( 'psi_marca' ) ? __( 'Marcas', 'psindustrial' ) : __( 'Soluciones', 'psindustrial' ) ) ); ?>
	<div class="psi-container product-body"><div class="catalog-columns">
		<?php get_template_part( 'template-parts/components/catalog-sidebar' ); ?>
		<div class="catalog-content">
			<?php if ( is_tax() ) : ?>
				<?php if ( term_description() ) : ?><div class="product-copy catalog-description"><?php echo wp_kses_post( term_description() ); ?></div><?php endif; ?>
			<?php endif; ?>
			<?php if ( have_posts() ) : ?>
				<div class="product-grid"><?php while ( have_posts() ) { the_post(); get_template_part( 'template-parts/components/product-card' ); } ?></div>
				<?php the_posts_pagination( array( 'prev_text' => __( 'Anterior', 'psindustrial' ), 'next_text' => __( 'Siguiente', 'psindustrial' ) ) ); ?>
			<?php else : ?><p><?php esc_html_e( 'Todavía no hay productos publicados en esta sección.', 'psindustrial' ); ?></p><?php endif; ?>
		</div>
	</div></div>
</main>
<?php get_footer(); ?>

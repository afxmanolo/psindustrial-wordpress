<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="shell">
<?php while ( have_posts() ) : the_post(); ?>
	<article <?php post_class(); ?>>
		<h1><?php $h1 = get_post_meta( get_the_ID(), '_psi_h1', true ); echo esc_html( $h1 ?: get_the_title() ); ?></h1>
		<?php echo wp_get_attachment_image( (int) get_post_meta( get_the_ID(), '_psi_hero_id', true ), 'large' ); ?>
		<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?>
		<?php the_content(); wp_link_pages(); ?>
		<?php foreach ( array( 'psi_categoria', 'psi_marca' ) as $taxonomy ) : ?>
			<?php if ( taxonomy_exists( $taxonomy ) ) : ?>
				<?php $terms = get_the_term_list( get_the_ID(), $taxonomy, '', ', ' ); ?>
				<?php if ( $terms && ! is_wp_error( $terms ) ) { echo '<p>' . wp_kses_post( $terms ) . '</p>'; } ?>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php get_template_part( 'template-parts/components/product-media' ); ?>
		<?php if ( class_exists( \PSIndustrial\Core\Content::class ) ) : ?>
			<?php $related = \PSIndustrial\Core\Content::related( get_the_ID() ); ?>
			<?php if ( $related ) : ?>
				<section><h2><?php esc_html_e( 'Productos relacionados', 'psindustrial' ); ?></h2><ul>
				<?php foreach ( $related as $item ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $item ) ); ?>"><?php echo esc_html( get_the_title( $item ) ); ?></a></li>
				<?php endforeach; ?>
				</ul></section>
			<?php endif; ?>
		<?php endif; ?>
	</article>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>

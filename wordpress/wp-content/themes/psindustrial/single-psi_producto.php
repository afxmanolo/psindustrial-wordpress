<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="shell">
<?php while ( have_posts() ) : the_post(); ?>
	<article <?php post_class(); ?>>
		<h1><?php $h1 = get_post_meta( get_the_ID(), '_psi_h1', true ); echo esc_html( $h1 ?: get_the_title() ); ?></h1>
		<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?>
		<?php the_content(); wp_link_pages(); ?>
		<?php foreach ( array( 'psi_categoria', 'psi_marca' ) as $taxonomy ) : ?>
			<?php if ( taxonomy_exists( $taxonomy ) ) : ?>
				<?php $terms = get_the_term_list( get_the_ID(), $taxonomy, '', ', ' ); ?>
				<?php if ( $terms && ! is_wp_error( $terms ) ) { echo '<p>' . wp_kses_post( $terms ) . '</p>'; } ?>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php get_template_part( 'template-parts/components/product-media' ); ?>
	</article>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>

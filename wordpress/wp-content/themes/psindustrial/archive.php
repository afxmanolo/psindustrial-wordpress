<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="shell">
	<h1><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
	<?php if ( is_tax( array( 'psi_categoria', 'psi_marca' ) ) ) : ?>
		<?php $term = get_queried_object(); $key = 'psi_marca' === $term->taxonomy ? '_psi_logo_id' : '_psi_image_id'; ?>
		<?php echo wp_get_attachment_image( (int) get_term_meta( $term->term_id, $key, true ), 'medium' ); ?>
		<?php echo wp_kses_post( term_description() ); ?>
	<?php endif; ?>
	<?php get_template_part( 'template-parts/components/post-grid' ); ?>
	<?php the_posts_pagination(); ?>
</main>
<?php get_footer(); ?>


<?php defined( 'ABSPATH' ) || exit; ?>
<article <?php post_class(); ?>>
	<h1><?php echo esc_html( get_post_meta( get_the_ID(), '_psi_h1', true ) ?: get_the_title() ); ?></h1>
	<?php echo wp_get_attachment_image( (int) get_post_meta( get_the_ID(), '_psi_hero_id', true ), 'large' ); ?>
	<?php the_content(); ?>
	<?php wp_link_pages(); ?>
</article>

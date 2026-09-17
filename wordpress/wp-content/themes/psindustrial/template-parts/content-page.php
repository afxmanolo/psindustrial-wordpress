<?php defined( 'ABSPATH' ) || exit; ?>
<article <?php post_class(); ?>>
	<h1><?php echo esc_html( get_the_title() ); ?></h1>
	<?php the_content(); ?>
	<?php wp_link_pages(); ?>
</article>

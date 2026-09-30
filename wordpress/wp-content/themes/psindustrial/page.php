<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="shell">
<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content', 'page' ); endwhile; ?>
</main>
<?php get_footer(); ?>


<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="shell">
<?php get_template_part( 'template-parts/components/post-grid' ); the_posts_pagination(); ?>
</main>
<?php get_footer(); ?>


<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( have_posts() ) : ?>
<div class="product-grid">
	<?php while ( have_posts() ) : the_post(); ?>
	<article <?php post_class( 'product-card' ); ?>>
		<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium' ); } ?>
		<h2><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h2>
		<?php the_excerpt(); ?>
	</article>
	<?php endwhile; ?>
</div>
<?php else : ?>
<p><?php esc_html_e( 'No hay contenido publicado todavía.', 'psindustrial' ); ?></p>
<?php endif; ?>

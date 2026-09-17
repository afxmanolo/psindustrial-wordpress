<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="shell">
	<?php if ( is_page() && have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content', 'page' ); endwhile; ?>
	<?php else : ?>
		<h1><?php esc_html_e( 'PS Industrial: entorno de desarrollo', 'psindustrial' ); ?></h1>
		<p><?php esc_html_e( 'WordPress, el tema y el modelo del catálogo están preparados para pruebas. No se ha migrado contenido legacy.', 'psindustrial' ); ?></p>
		<?php if ( post_type_exists( 'psi_producto' ) ) : ?>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'psi_producto' ) ); ?>"><?php esc_html_e( 'Ver catálogo de prueba', 'psindustrial' ); ?></a>
		<?php endif; ?>
	<?php endif; ?>
</main>
<?php get_footer(); ?>


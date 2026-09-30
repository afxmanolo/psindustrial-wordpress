<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="shell">
	<h1><?php esc_html_e( 'Página no encontrada', 'psindustrial' ); ?></h1>
	<p><?php esc_html_e( 'La dirección solicitada no existe en este entorno.', 'psindustrial' ); ?></p>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Volver al inicio', 'psindustrial' ); ?></a>
</main>
<?php get_footer(); ?>


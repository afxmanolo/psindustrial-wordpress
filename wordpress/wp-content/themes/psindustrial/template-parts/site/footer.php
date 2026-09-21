<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( \PSIndustrial\Theme\is_catalog() ) { get_template_part( 'template-parts/site/catalog-footer' ); return; } ?>
<footer class="site-footer shell">
	<p><?php esc_html_e( 'Entorno base de PS Industrial. Diseño temporal de desarrollo.', 'psindustrial' ); ?></p>
</footer>

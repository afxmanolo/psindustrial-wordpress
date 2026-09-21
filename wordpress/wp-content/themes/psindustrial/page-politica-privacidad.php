<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="institutional-main">
<?php get_template_part( 'template-parts/components/institutional-heading', null, array( 'title' => __( 'Política de privacidad', 'psindustrial' ) ) ); ?>
<section class="institutional-body psi-container privacy-copy" data-content-status="LEGAL_CONTENT_PENDING">
 <h2><?php esc_html_e( 'Contenido pendiente de revisión', 'psindustrial' ); ?></h2>
 <p><?php esc_html_e( 'El contenido de esta página está pendiente de revisión.', 'psindustrial' ); ?></p>
</section></main>
<?php get_footer(); ?>

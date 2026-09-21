<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="institutional-main">
<?php get_template_part( 'template-parts/components/institutional-heading', null, array( 'eyebrow' => __( 'Nosotros', 'psindustrial' ), 'title' => __( 'Quiénes somos', 'psindustrial' ) ) ); ?>
<section class="institutional-body psi-container about-institutional">
 <img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/institutional/quienes-int-800.webp' ) ); ?>" srcset="<?php echo esc_url( get_theme_file_uri( 'assets/images/institutional/quienes-int-480.webp' ) ); ?> 480w, <?php echo esc_url( get_theme_file_uri( 'assets/images/institutional/quienes-int-800.webp' ) ); ?> 800w" sizes="(min-width:992px) 600px, 90vw" width="800" height="770" alt="<?php esc_attr_e( 'Quiénes somos', 'psindustrial' ); ?>" loading="lazy" decoding="async">
 <div><h2><?php esc_html_e( 'Acerca de nosotros', 'psindustrial' ); ?></h2>
 <p class="about-lead"><?php esc_html_e( 'Empresa dedicada a la venta, instalación y mantenimiento de puertas automáticas para diferentes sectores de la industria.', 'psindustrial' ); ?></p>
 <p><?php esc_html_e( 'Contamos con más de 25 años de experiencia, nos especializamos en la fabricación de puertas y ventanas de alta calidad, utilizando materiales como aluminio y acero inoxidable.', 'psindustrial' ); ?></p>
 <p><?php esc_html_e( 'Nos destacamos por ofrecer soluciones personalizadas para cada cliente, abarcando tanto proyectos residenciales como industriales, asegurando productos que garanticen funcionalidad y diseño, además de tecnología avanzada.', 'psindustrial' ); ?></p>
 <a class="institutional-button" href="<?php echo esc_url( get_post_type_archive_link( 'psi_producto' ) ); ?>"><?php esc_html_e( 'Ver servicios', 'psindustrial' ); ?> <span aria-hidden="true">→</span></a></div>
</section></main>
<?php get_footer(); ?>

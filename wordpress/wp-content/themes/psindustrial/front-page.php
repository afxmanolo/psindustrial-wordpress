<?php
/** Historical Home composition; no editorial objects are created or published. */
defined( 'ABSPATH' ) || exit;
get_header();
$data = \PSIndustrial\Theme\home_data();
$catalog = get_post_type_archive_link( 'psi_producto' );
$slides = array(
 array( 'banner1.jpg', __( 'Conócenos.', 'psindustrial' ), __( 'Bienvenidos a nuestro sitio web.', 'psindustrial' ) ),
 array( 'banner2.jpg', __( 'Empresa dedicada a la', 'psindustrial' ), __( 'Venta, instalación y mantenimiento de puertas automáticas', 'psindustrial' ) ),
 array( 'banner3.jpeg', __( 'Empresa dedicada a la', 'psindustrial' ), __( 'Venta, instalación y mantenimiento de rampas para Taller o Niveladoras de Andén', 'psindustrial' ) ),
 array( 'banner4.jpg', __( 'Empresa dedicada a la', 'psindustrial' ), __( 'Venta, instalación y mantenimiento de rampas para Taller o Niveladoras de Andén', 'psindustrial' ) ),
);
?>
<main id="main" class="home-main">
 <h1 class="screen-reader-text"><?php esc_html_e( 'Puertas y Servicios Industriales', 'psindustrial' ); ?></h1>
 <section class="home-slider" aria-label="<?php esc_attr_e( 'Presentación de PS Industrial', 'psindustrial' ); ?>" aria-roledescription="<?php esc_attr_e( 'carrusel', 'psindustrial' ); ?>">
  <?php foreach ( $slides as $i => [ $image, $eyebrow, $title ] ) : ?>
  <div class="home-slide" id="home-slide-<?php echo (int) $i; ?>" role="group" aria-label="<?php echo esc_attr( sprintf( __( '%1$d de %2$d', 'psindustrial' ), $i + 1, count( $slides ) ) ); ?>">
   <?php echo \PSIndustrial\Theme\home_image( $image, '', '100vw', 0 === $i ); ?>
   <div class="psi-container"><div class="home-hero-copy"><p class="home-hero-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><p class="home-hero-title"><?php echo esc_html( $title ); ?></p><a class="home-hero-cta" href="<?php echo esc_url( $catalog ); ?>"><?php esc_html_e( 'Ver más', 'psindustrial' ); ?><span aria-hidden="true">→</span></a></div></div>
  </div>
  <?php endforeach; ?>
  <div class="home-slider-controls" hidden>
   <?php foreach ( $slides as $i => $slide ) : ?><button class="home-slide-dot" type="button" aria-controls="home-slide-<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Mostrar diapositiva %d', 'psindustrial' ), $i + 1 ) ); ?>" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"><span></span></button><?php endforeach; ?>
   <button class="home-slider-pause" type="button" data-pause="<?php esc_attr_e( 'Pausar presentación', 'psindustrial' ); ?>" data-play="<?php esc_attr_e( 'Reanudar presentación', 'psindustrial' ); ?>" aria-label="<?php esc_attr_e( 'Pausar presentación', 'psindustrial' ); ?>">Ⅱ</button>
  </div>
 </section>
 <section class="home-brands" aria-labelledby="home-brands-title"><div class="psi-container">
  <h2 id="home-brands-title"><?php esc_html_e( 'Marcas', 'psindustrial' ); ?></h2><p><?php esc_html_e( 'Trabajamos con los mejores de la industria.', 'psindustrial' ); ?></p>
  <ul class="home-brand-grid"><?php foreach ( \PSIndustrial\Theme\home_brands() as $brand ) : ?><li><a href="<?php echo esc_url( $brand['link'] ); ?>"><?php echo \PSIndustrial\Theme\home_image( $brand['image'], $brand['name'], '(min-width: 992px) 180px, 45vw' ); ?></a></li><?php endforeach; ?></ul>
 </div></section>
 <section class="home-products" aria-labelledby="home-products-title">
  <div class="home-products-heading psi-container"><h2 id="home-products-title"><?php esc_html_e( 'Conoce nuestros Productos', 'psindustrial' ); ?></h2></div>
  <?php foreach ( \PSIndustrial\Theme\home_category_sections() as $i => $family ) : ?>
  <section class="home-family<?php echo $family['reverse'] ? ' is-reversed' : ''; echo $family['pattern'] ? ' has-pattern' : ''; ?>" aria-labelledby="home-family-<?php echo (int) $i; ?>">
   <div class="psi-container home-family-grid">
    <div class="home-family-copy"><h3 id="home-family-<?php echo (int) $i; ?>"><?php echo esc_html( $family['title'] ); ?></h3>
     <ul><?php foreach ( $family['children'] as $child ) : ?><li><a href="<?php echo esc_url( $child['link'] ); ?>"><span aria-hidden="true">+</span> <?php echo esc_html( $child['label'] ); ?></a></li><?php endforeach; ?></ul>
     <a class="home-outline-button" href="<?php echo esc_url( $family['link'] ); ?>"><?php esc_html_e( 'Ver todos', 'psindustrial' ); ?> <span aria-hidden="true">→</span></a>
    </div>
    <div class="home-family-image<?php echo $family['narrow'] ? ' is-narrow' : ''; ?>"><?php echo \PSIndustrial\Theme\home_image( $family['image'], $family['title'], '(min-width: 992px) 480px, 90vw' ); ?></div>
   </div>
  </section>
  <?php endforeach; ?>
 </section>
 <section class="home-about"><div class="psi-container home-about-grid">
  <?php echo \PSIndustrial\Theme\home_image( 'quienes1.jpg', __( 'Quiénes somos', 'psindustrial' ), '(min-width: 992px) 380px, 90vw' ); ?>
  <div><h2><?php esc_html_e( 'Acerca de nosotros', 'psindustrial' ); ?></h2><p><?php esc_html_e( 'Empresa dedicada a la venta, instalación y mantenimiento de puertas automáticas para diferentes sectores de la industria.', 'psindustrial' ); ?></p>
  <?php $about = \PSIndustrial\Theme\published_page_url( 'nosotros' ); if ( $about ) : ?><a class="home-about-button" href="<?php echo esc_url( $about ); ?>"><?php esc_html_e( 'Conócenos', 'psindustrial' ); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
  <a href="<?php echo esc_url( $catalog ); ?>"><?php esc_html_e( 'Nuestros servicios', 'psindustrial' ); ?> <span aria-hidden="true">▸</span></a></div>
 </div></section>
</main>
<?php get_footer(); ?>

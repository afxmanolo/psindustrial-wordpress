<?php
defined( 'ABSPATH' ) || exit;
$slides = $args['slides'] ?? ( class_exists( \PSIndustrial\Core\Slides::class ) ? \PSIndustrial\Core\Slides::frontend() : array() );
if ( ! $slides ) { return; }
?>
 <section class="home-slider" aria-label="<?php esc_attr_e( 'Presentación de PS Industrial', 'psindustrial' ); ?>" aria-roledescription="<?php esc_attr_e( 'carrusel', 'psindustrial' ); ?>">
  <?php foreach ( $slides as $i => $slide ) : ?>
  <div class="home-slide" id="home-slide-<?php echo (int) $i; ?>" role="group" aria-label="<?php echo esc_attr( sprintf( __( '%1$d de %2$d', 'psindustrial' ), $i + 1, count( $slides ) ) ); ?>">
   <?php if ( ! empty( $slide['image_id'] ) && wp_attachment_is_image( $slide['image_id'] ) ) {
    echo wp_get_attachment_image( $slide['image_id'], 'full', false, array( 'alt'=>'', 'sizes'=>'100vw', 'loading'=>0 === $i ? 'eager' : 'lazy', 'fetchpriority'=>0 === $i ? 'high' : 'auto', 'decoding'=>'async' ) );
   } elseif ( ! empty( $slide['fallback_image'] ) ) { echo \PSIndustrial\Theme\home_image( $slide['fallback_image'], '', '100vw', 0 === $i ); } ?>
   <div class="psi-container"><div class="home-hero-copy"><p class="home-hero-eyebrow"><?php echo esc_html( $slide['eyebrow'] ); ?></p><p class="home-hero-title"><?php echo esc_html( $slide['title'] ); ?></p><?php if ( $slide['button_label'] && $slide['button_url'] ) : ?><a class="home-hero-cta" href="<?php echo esc_url( $slide['button_url'] ); ?>"><?php echo esc_html( $slide['button_label'] ); ?><span aria-hidden="true">→</span></a><?php endif; ?></div></div>
  </div>
  <?php endforeach; ?>
  <div class="home-slider-controls" hidden>
   <?php foreach ( $slides as $i => $slide ) : ?><button class="home-slide-dot" type="button" aria-controls="home-slide-<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Mostrar diapositiva %d', 'psindustrial' ), $i + 1 ) ); ?>" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"><span></span></button><?php endforeach; ?>
   <button class="home-slider-pause" type="button" data-pause="<?php esc_attr_e( 'Pausar presentación', 'psindustrial' ); ?>" data-play="<?php esc_attr_e( 'Reanudar presentación', 'psindustrial' ); ?>" aria-label="<?php esc_attr_e( 'Pausar presentación', 'psindustrial' ); ?>">Ⅱ</button>
  </div>
 </section>

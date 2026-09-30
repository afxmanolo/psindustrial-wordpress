<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="institutional-main">
<?php get_template_part( 'template-parts/components/institutional-heading', null, array( 'eyebrow' => __( 'Servicios', 'psindustrial' ), 'title' => __( 'Soluciones', 'psindustrial' ) ) ); ?>
<section class="institutional-body psi-container">
 <ul class="soluciones-cards">
 <?php foreach ( \PSIndustrial\Theme\soluciones_cards() as $card ) : ?>
 <li>
  <a href="<?php echo esc_url( $card['link'] ); ?>"><?php echo \PSIndustrial\Theme\soluciones_card_image( $card['image'], $card['title'] ); ?></a>
  <a href="<?php echo esc_url( $card['link'] ); ?>" class="soluciones-card-title"><?php echo esc_html( $card['title'] ); ?></a>
  <p><?php echo esc_html( $card['tagline'] ); ?></p>
 </li>
 <?php endforeach; ?>
 </ul>
</section>
</main>
<?php get_footer(); ?>

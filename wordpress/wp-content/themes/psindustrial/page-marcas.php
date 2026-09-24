<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main" class="institutional-main">
<?php get_template_part( 'template-parts/components/institutional-heading', null, array( 'eyebrow' => __( 'Soluciones', 'psindustrial' ), 'title' => __( 'Marcas', 'psindustrial' ) ) ); ?>
<section class="home-brands"><div class="psi-container">
 <p><?php esc_html_e( 'Trabajamos con los mejores de la industria.', 'psindustrial' ); ?></p>
 <ul class="home-brand-grid"><?php foreach ( \PSIndustrial\Theme\home_brands() as $brand ) : ?><li><a href="<?php echo esc_url( $brand['link'] ); ?>"><?php echo \PSIndustrial\Theme\home_image( $brand['image'], $brand['name'], '(min-width: 992px) 180px, 45vw' ); ?></a></li><?php endforeach; ?></ul>
</div></section>
</main>
<?php get_footer(); ?>

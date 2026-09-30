<?php
defined( 'ABSPATH' ) || exit;
$brands = is_tax( 'psi_marca' );
$location = $brands ? 'brand_sidebar' : 'catalog_sidebar';
$contact = \PSIndustrial\Theme\catalog_contact_url();
?>
<aside class="catalog-sidebar" aria-label="<?php esc_attr_e( 'Navegación del catálogo', 'psindustrial' ); ?>"><div class="catalog-sidebar-inner">
	<?php if ( has_nav_menu( $location ) ) : ?>
		<?php wp_nav_menu( array( 'theme_location' => $location, 'container' => false, 'menu_class' => 'catalog-side-links', 'depth' => 2 ) ); ?>
    <?php else : ?>
        <?php if ( $brands ) : ?><h2 class="sidebar-title"><?php esc_html_e( 'Marcas', 'psindustrial' ); ?></h2><?php endif; ?>
        <ul class="catalog-side-links">
        <?php foreach ( \PSIndustrial\Theme\catalog_navigation( true ) as $item ) : ?>
            <li<?php if ( $item['active'] ) : ?> class="is-active"<?php endif; ?>><a href="<?php echo esc_url( $item['url'] ); ?>"<?php if ( $item['active'] ) : ?> aria-current="page"<?php endif; ?>><span><?php echo esc_html( $item['label'] ); ?></span><span class="psi-icon icon-<?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></span></a></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
	<div class="catalog-contact">
		<h2><?php esc_html_e( 'Cotiza tu proyecto', 'psindustrial' ); ?></h2>
		<p><?php esc_html_e( 'Envíanos un mensaje, cuéntanos de tu proyecto.', 'psindustrial' ); ?></p>
		<?php if ( $contact ) : ?><a class="catalog-contact-button" href="<?php echo esc_url( $contact ); ?>"><span class="psi-icon icon-mail" aria-hidden="true"></span><?php esc_html_e( 'Contacto', 'psindustrial' ); ?></a>
		<?php else : ?><span class="catalog-contact-button" aria-disabled="true"><span class="psi-icon icon-mail" aria-hidden="true"></span><?php esc_html_e( 'Contacto', 'psindustrial' ); ?></span><?php endif; ?>
	</div>
</div></aside>

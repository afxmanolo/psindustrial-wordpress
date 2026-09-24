<?php defined( 'ABSPATH' ) || exit; ?>
<header class="catalog-site-header">
	<div class="psi-container catalog-header-inner">
		<a class="catalog-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img class="catalog-logo-light" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-white@2x.png' ) ); ?>" width="304" height="210" alt="<?php esc_attr_e( 'PSI · Puertas y Servicios Industriales', 'psindustrial' ); ?>">
			<img class="catalog-logo-dark" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-black-big@2x.png' ) ); ?>" width="356" height="241" alt="<?php esc_attr_e( 'PSI · Puertas y Servicios Industriales', 'psindustrial' ); ?>">
		</a>
		<button class="nav-toggle" type="button" hidden aria-controls="site-navigation" aria-expanded="true"><?php esc_html_e( 'Menú', 'psindustrial' ); ?></button>
		<nav id="site-navigation" aria-label="<?php esc_attr_e( 'Navegación principal', 'psindustrial' ); ?>">
		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'depth' => 2 ) ); ?>
		<?php else : ?>
            <ul>
                <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php if ( is_front_page() ) : ?> aria-current="page"<?php endif; ?>><?php esc_html_e( 'Inicio', 'psindustrial' ); ?></a></li>
                <?php $about = \PSIndustrial\Theme\published_page_url( 'nosotros' ); ?>
                <li><?php if ( $about ) : ?><a href="<?php echo esc_url( $about ); ?>"><?php esc_html_e( 'Nosotros', 'psindustrial' ); ?></a><?php else : ?><span class="navigation-pending" aria-disabled="true"><?php esc_html_e( 'Nosotros', 'psindustrial' ); ?></span><?php endif; ?></li>
                <li class="menu-item-has-children">
                    <?php $soluciones = \PSIndustrial\Theme\published_page_url( 'soluciones' ); ?>
                    <?php if ( $soluciones ) : ?><a href="<?php echo esc_url( $soluciones ); ?>"><?php esc_html_e( 'Soluciones', 'psindustrial' ); ?></a><?php else : ?><span class="nav-link"><?php esc_html_e( 'Soluciones', 'psindustrial' ); ?></span><?php endif; ?>
                    <ul class="sub-menu">
                        <?php foreach ( \PSIndustrial\Theme\catalog_navigation() as $item ) : ?>
                        <li><a href="<?php echo esc_url( $item['url'] ); ?>"<?php if ( $item['active'] ) : ?> aria-current="page"<?php endif; ?>><?php echo esc_html( $item['label'] ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php $contact = \PSIndustrial\Theme\catalog_contact_url(); if ( $contact ) : ?><li><a href="<?php echo esc_url( $contact ); ?>"><?php esc_html_e( 'Contacto', 'psindustrial' ); ?></a></li><?php endif; ?>
            </ul>
		<?php endif; ?>
		</nav>
	</div>
</header>

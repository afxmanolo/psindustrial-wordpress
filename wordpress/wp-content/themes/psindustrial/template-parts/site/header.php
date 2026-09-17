<?php defined( 'ABSPATH' ) || exit; ?>
<header class="site-header shell">
	<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
	<button class="nav-toggle" type="button" hidden aria-controls="site-navigation" aria-expanded="true"><?php esc_html_e( 'Menú', 'psindustrial' ); ?></button>
	<nav id="site-navigation" aria-label="<?php esc_attr_e( 'Navegación principal', 'psindustrial' ); ?>">
		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'depth' => 2 ) ); ?>
		<?php else : ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'psindustrial' ); ?></a>
			<?php if ( post_type_exists( 'psi_producto' ) ) : ?>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'psi_producto' ) ); ?>"><?php esc_html_e( 'Productos', 'psindustrial' ); ?></a>
			<?php endif; ?>
		<?php endif; ?>
	</nav>
</header>

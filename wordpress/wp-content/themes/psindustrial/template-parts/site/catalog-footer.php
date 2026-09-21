<?php
defined( 'ABSPATH' ) || exit;
$settings = class_exists( \PSIndustrial\Core\Settings::class ) ? \PSIndustrial\Core\Settings::get() : array();
$email = $settings['contact_email'] ?? '';
?>
<footer class="catalog-site-footer">
	<div class="psi-container">
		<?php if ( $email ) : ?><div class="catalog-footer-cta"><h2><?php esc_html_e( '¡Platícanos de tu proyecto!', 'psindustrial' ); ?></h2><div><span><?php esc_html_e( 'Envíanos un mensaje', 'psindustrial' ); ?></span><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></div></div><?php endif; ?>
		<div class="catalog-footer-columns">
			<a class="catalog-footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-white@2x.png' ) ); ?>" width="304" height="210" alt="<?php esc_attr_e( 'PSI · Puertas y Servicios Industriales', 'psindustrial' ); ?>"></a>
			<div><h2><?php esc_html_e( 'Soluciones', 'psindustrial' ); ?></h2><ul class="catalog-footer-links"><?php foreach ( \PSIndustrial\Theme\catalog_navigation() as $item ) : ?><li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li><?php endforeach; ?></ul></div>
			<div>
				<?php if ( $email || ! empty( $settings['contact_phone'] ) || ! empty( $settings['contact_address'] ) ) : ?><h2><?php esc_html_e( 'Contacto', 'psindustrial' ); ?></h2><?php endif; ?>
				<?php if ( ! empty( $settings['contact_address'] ) ) : ?><p><?php echo esc_html( $settings['contact_address'] ); ?></p><?php endif; ?>
				<?php if ( ! empty( $settings['contact_phone'] ) ) : ?><p><a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^+0-9]/', '', $settings['contact_phone'] ) ); ?>"><?php echo esc_html( $settings['contact_phone'] ); ?></a></p><?php endif; ?>
				<?php if ( $email ) : ?><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a><?php endif; ?>
                <?php $contact = \PSIndustrial\Theme\published_page_url( 'contacto' ); if ( $contact ) : ?><p><a href="<?php echo esc_url( $contact ); ?>"><?php esc_html_e( 'Contacto', 'psindustrial' ); ?></a></p><?php endif; ?>
                <?php $privacy = get_privacy_policy_url(); if ( $privacy ) : ?><p><a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Pol�tica de privacidad', 'psindustrial' ); ?></a></p><?php endif; ?>
                <p><?php echo esc_html( sprintf( __( 'Puertas y Servicios Industriales © %s', 'psindustrial' ), wp_date( 'Y' ) ) ); ?></p>
			</div>
		</div>
	</div>
</footer>

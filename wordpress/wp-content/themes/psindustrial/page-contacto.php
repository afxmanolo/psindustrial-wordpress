<?php
 defined( 'ABSPATH' ) || exit;
 use PSIndustrial\Core\Contact;
 get_header();
 $error = Contact::$error; $values = Contact::$values; $started = time();
?>
<main id="main" class="institutional-main">
<?php get_template_part( 'template-parts/components/institutional-heading', null, array( 'title' => __( 'Contacto', 'psindustrial' ) ) ); ?>
<section class="institutional-body psi-container contact-institutional">
 <div class="contact-details"><p class="contact-eyebrow"><?php esc_html_e( 'Ponte en contacto con nosotros', 'psindustrial' ); ?></p><h2><?php esc_html_e( '¿Necesitas ayuda? ¡Contáctanos ahora!', 'psindustrial' ); ?></h2>
 <div class="contact-detail"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/institutional/contact-04-160.webp' ) ); ?>" width="80" height="80" alt="" loading="lazy"><div><h3><?php esc_html_e( 'Email', 'psindustrial' ); ?></h3><a href="mailto:overheaddoor@hotmail.com">overheaddoor@hotmail.com</a><br><a href="mailto:vicenteaguilarleon@gmail.com">vicenteaguilarleon@gmail.com</a></div></div>
 <div class="contact-detail"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/institutional/contact-03-160.webp' ) ); ?>" width="80" height="80" alt="" loading="lazy"><div><h3><?php esc_html_e( 'Dirección', 'psindustrial' ); ?></h3><address>Blvd. Estrella #323 local 5-A, Fracc. Estrella, C.P. 36566, Irapuato, Gto.</address></div></div>
 </div>
 <div class="institutional-form"><h2><?php esc_html_e( 'Envíanos un mensaje', 'psindustrial' ); ?></h2>
 <?php if ( $error ) : ?><div class="contact-result" role="alert"><p><?php echo esc_html( $error->get_error_message() ); ?></p></div><?php elseif ( isset( $_GET['psi_contact'] ) && is_string( $_GET['psi_contact'] ) && 'sent' === $_GET['psi_contact'] ) : ?><p role="status"><?php esc_html_e( 'Solicitud enviada.', 'psindustrial' ); ?></p><?php endif; ?>
 <form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
 <?php wp_nonce_field( 'psi_contact' ); ?>
 <input type="hidden" name="started" value="<?php echo (int) $started; ?>"><input type="hidden" name="signature" value="<?php echo esc_attr( Contact::stamp( $started ) ); ?>">
 <div class="contact-honeypot" aria-hidden="true"><label for="psi-website">Website</label><input id="psi-website" name="psi_contact_website" type="text" tabindex="-1" autocomplete="off" value=""></div>
 <label for="psi-name"><?php esc_html_e( 'Nombre', 'psindustrial' ); ?> <span aria-hidden="true">*</span></label><input id="psi-name" name="psi_contact_name" required maxlength="150" autocomplete="name" value="<?php echo esc_attr( $values['name'] ?? '' ); ?>">
 <label for="psi-email"><?php esc_html_e( 'Email', 'psindustrial' ); ?> <span aria-hidden="true">*</span></label><input id="psi-email" name="psi_contact_email" type="email" required maxlength="254" autocomplete="email" value="<?php echo esc_attr( $values['email'] ?? '' ); ?>">
 <label for="psi-message"><?php esc_html_e( 'Tu mensaje', 'psindustrial' ); ?></label><textarea id="psi-message" name="psi_contact_message" maxlength="5000" rows="3"><?php echo esc_textarea( $values['message'] ?? '' ); ?></textarea>
 <button class="institutional-button" type="submit"><?php esc_html_e( 'Enviar', 'psindustrial' ); ?></button>
 <?php $privacy = \PSIndustrial\Theme\institutional_privacy_url(); if ( $privacy ) : ?><p class="contact-privacy"><a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Política de privacidad — contenido pendiente de revisión', 'psindustrial' ); ?></a></p><?php endif; ?>
 <?php if ( 'production' !== wp_get_environment_type() ) : ?><p class="contact-privacy"><?php esc_html_e( 'El envío de correo está deshabilitado en este entorno de pruebas.', 'psindustrial' ); ?></p><?php endif; ?>
 </form></div>
</section></main>
<?php get_footer(); ?>

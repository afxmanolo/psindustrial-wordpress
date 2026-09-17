<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class Settings {
	public static function defaults(): array {
		return array( 'whatsapp_number' => '', 'whatsapp_message' => '', 'whatsapp_enabled' => false, 'contact_phone' => '', 'contact_email' => '', 'contact_address' => '', 'mail_recipient' => '' );
	}
	public static function get(): array {
		return wp_parse_args( (array) get_option( 'psi_site_settings', array() ), self::defaults() );
	}
	public static function boot(): void {
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_filter( 'option_page_capability_psi_settings', static fn() => 'psi_edit_contact_settings' );
		add_filter( 'pre_update_option_psi_site_settings', static function( $value, $old ) {
			if ( ! is_array( $value ) ) { return $old; }
			if ( ! current_user_can( 'manage_options' ) ) { $value['mail_recipient'] = $old['mail_recipient'] ?? ''; }
			if ( $value !== $old ) { update_option( 'psi_contact_previous', array( 'values' => $old, 'user_id' => get_current_user_id(), 'changed_at' => current_time( 'mysql', true ) ), false ); }
			return $value;
		}, 10, 2 );
		add_action( 'admin_menu', static function() {
			add_menu_page( __( 'Contacto del sitio', 'psindustrial-core' ), __( 'Contacto del sitio', 'psindustrial-core' ), 'psi_edit_contact_settings', 'psindustrial-settings', array( self::class, 'render' ), 'dashicons-phone', 26 );
		} );
	}
	public static function register(): void {
		register_setting( 'psi_settings', 'psi_site_settings', array( 'type' => 'object', 'sanitize_callback' => array( self::class, 'sanitize' ), 'default' => self::defaults(), 'show_in_rest' => false ) );
	}
	public static function sanitize( mixed $value ): array {
		$old = self::get();
		if ( ! is_array( $value ) ) {
			add_settings_error( 'psi_site_settings', 'psi_settings_type', __( 'Configuración inválida.', 'psindustrial-core' ) );
			return $old;
		}
		$clean = $old;
		foreach ( self::defaults() as $key => $empty ) {
			if ( 'mail_recipient' === $key && ! current_user_can( 'manage_options' ) ) { continue; }
			if ( ! array_key_exists( $key, $value ) ) {
				continue;
			}
			if ( 'whatsapp_enabled' === $key ) {
				if ( ! in_array( $value[ $key ], array( true, false, 1, 0, '1', '0' ), true ) ) { return $old; }
				$clean[ $key ] = (bool) $value[ $key ]; continue;
			}
			if ( ! is_string( $value[ $key ] ) ) {
				add_settings_error( 'psi_site_settings', 'psi_settings_type', __( 'Los datos de contacto deben ser texto.', 'psindustrial-core' ) );
				return $old;
			}
			$text = trim( sanitize_text_field( $value[ $key ] ) );
			if ( 'whatsapp_number' === $key && '' !== $text && ! preg_match( '/^\+[1-9][0-9]{7,14}$/D', $text ) ) {
				add_settings_error( 'psi_site_settings', 'psi_phone', __( 'WhatsApp debe usar formato internacional, por ejemplo + seguido del código de país y número.', 'psindustrial-core' ) );
				return $old;
			}
			if ( in_array( $key, array( 'contact_email', 'mail_recipient' ), true ) && '' !== $text && ! is_email( $text ) ) {
				add_settings_error( 'psi_site_settings', 'psi_email', __( 'El correo de contacto no es válido.', 'psindustrial-core' ) );
				return $old;
			}
			$clean[ $key ] = mb_substr( $text, 0, 'whatsapp_message' === $key ? 1000 : 200 );
		}
		if ( $clean['whatsapp_enabled'] && '' === $clean['whatsapp_number'] ) { add_settings_error( 'psi_site_settings', 'psi_number_required', __( 'Indique un número antes de activar WhatsApp.', 'psindustrial-core' ) ); return $old; }
		return $clean;
	}
	public static function whatsapp_url(): string {
		$settings = self::get();
		return $settings['whatsapp_enabled'] && preg_match( '/^\+[1-9][0-9]{7,14}$/D', $settings['whatsapp_number'] ) ? 'https://wa.me/' . substr( $settings['whatsapp_number'], 1 ) . '?text=' . rawurlencode( $settings['whatsapp_message'] ) : '';
	}
	public static function render(): void {
		if ( ! current_user_can( 'psi_edit_contact_settings' ) ) {
			return;
		}
		$values = self::get();
		$labels = array(
			'whatsapp_number' => __( 'WhatsApp (formato internacional)', 'psindustrial-core' ),
			'whatsapp_message' => __( 'Mensaje de WhatsApp', 'psindustrial-core' ),
			'contact_phone' => __( 'Teléfono de contacto', 'psindustrial-core' ),
			'contact_email' => __( 'Correo de contacto', 'psindustrial-core' ),
			'contact_address' => __( 'Dirección pública (opcional)', 'psindustrial-core' ),
		);
		if ( current_user_can( 'manage_options' ) ) { $labels['mail_recipient'] = __( 'Destinatario del futuro formulario (sólo administrador)', 'psindustrial-core' ); }
		echo '<div class="wrap"><h1>' . esc_html__( 'Configuración de PS Industrial', 'psindustrial-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Valores locales vacíos hasta su confirmación. Esta pantalla no envía correo ni mensajes.', 'psindustrial-core' ) . '</p>';
		settings_errors( 'psi_site_settings' );
		echo '<form action="options.php" method="post">';
		settings_fields( 'psi_settings' );
		echo '<p><input type="hidden" name="psi_site_settings[whatsapp_enabled]" value="0"><label><input type="checkbox" name="psi_site_settings[whatsapp_enabled]" value="1" ' . checked( $values['whatsapp_enabled'], true, false ) . '>' . esc_html__( 'Activar enlace de WhatsApp', 'psindustrial-core' ) . '</label></p>';
		echo '<table class="form-table">';
		foreach ( $labels as $key => $label ) {
			printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input class="regular-text" id="%1$s" type="%3$s" name="psi_site_settings[%1$s]" value="%4$s"></td></tr>', esc_attr( $key ), esc_html( $label ), 'contact_email' === $key ? 'email' : 'text', esc_attr( $values[ $key ] ) );
		}
		echo '</table>';
		submit_button();
		echo '</form></div>';
		$url = self::whatsapp_url();
		if ( $url ) { echo '<p><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Comprobar enlace de WhatsApp (abre otra pestaña)', 'psindustrial-core' ) . '</a></p>'; }
	}
}

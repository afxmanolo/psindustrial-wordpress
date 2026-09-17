<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class Settings {
	public static function defaults(): array {
		return array( 'whatsapp_number' => '', 'whatsapp_message' => '', 'contact_phone' => '', 'contact_email' => '' );
	}
	public static function get(): array {
		return wp_parse_args( (array) get_option( 'psi_site_settings', array() ), self::defaults() );
	}
	public static function boot(): void {
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_action( 'admin_menu', static function() {
			add_options_page( __( 'PS Industrial', 'psindustrial-core' ), __( 'PS Industrial', 'psindustrial-core' ), 'manage_options', 'psindustrial-settings', array( self::class, 'render' ) );
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
			if ( ! array_key_exists( $key, $value ) ) {
				continue;
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
			if ( 'contact_email' === $key && '' !== $text && ! is_email( $text ) ) {
				add_settings_error( 'psi_site_settings', 'psi_email', __( 'El correo de contacto no es válido.', 'psindustrial-core' ) );
				return $old;
			}
			$clean[ $key ] = mb_substr( $text, 0, 'whatsapp_message' === $key ? 1000 : 200 );
		}
		return $clean;
	}
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$values = self::get();
		$labels = array(
			'whatsapp_number' => __( 'WhatsApp (formato internacional)', 'psindustrial-core' ),
			'whatsapp_message' => __( 'Mensaje de WhatsApp', 'psindustrial-core' ),
			'contact_phone' => __( 'Teléfono de contacto', 'psindustrial-core' ),
			'contact_email' => __( 'Correo de contacto', 'psindustrial-core' ),
		);
		echo '<div class="wrap"><h1>' . esc_html__( 'Configuración de PS Industrial', 'psindustrial-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Valores locales vacíos hasta su confirmación. Esta pantalla no envía correo ni mensajes.', 'psindustrial-core' ) . '</p>';
		settings_errors( 'psi_site_settings' );
		echo '<form action="options.php" method="post">';
		settings_fields( 'psi_settings' );
		echo '<table class="form-table">';
		foreach ( $labels as $key => $label ) {
			printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input class="regular-text" id="%1$s" type="%3$s" name="psi_site_settings[%1$s]" value="%4$s"></td></tr>', esc_attr( $key ), esc_html( $label ), 'contact_email' === $key ? 'email' : 'text', esc_attr( $values[ $key ] ) );
		}
		echo '</table>';
		submit_button();
		echo '</form></div>';
	}
}


<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/** Small, synchronous contact handler. No leads, attachments or legacy mail code. */
final class Contact {
 public static ?\WP_Error $error = null;
 public static array $values = array();
 public static function boot(): void {
  add_action( 'template_redirect', array( self::class, 'request' ) );
 }
 public static function stamp( int $time ): string { return hash_hmac( 'sha256', (string) $time, wp_salt( 'nonce' ) ); }
 public static function validate( array $input ): array|\WP_Error {
  foreach ( array( 'name', 'email', 'message', 'website', '_wpnonce', 'started', 'signature' ) as $key ) {
   if ( ! isset( $input[ $key ] ) || ! is_string( $input[ $key ] ) ) { return new \WP_Error( 'invalid', __( 'Revisa los datos del formulario.', 'psindustrial-core' ) ); }
  }
  if ( ! wp_verify_nonce( $input['_wpnonce'], 'psi_contact' ) ) { return new \WP_Error( 'nonce', __( 'El formulario caducó. Recarga la página e inténtalo de nuevo.', 'psindustrial-core' ) ); }
  $time = ctype_digit( $input['started'] ) ? (int) $input['started'] : 0;
  if ( ! hash_equals( self::stamp( $time ), $input['signature'] ) || time() - $time < 3 || time() - $time > HOUR_IN_SECONDS || '' !== $input['website'] ) { return new \WP_Error( 'abuse', __( 'No se pudo validar el formulario. Recarga la página e inténtalo de nuevo.', 'psindustrial-core' ) ); }
  if ( preg_match( '/[\r\n]/', $input['name'] . $input['email'] ) ) { return new \WP_Error( 'headers', __( 'El nombre o correo no es válido.', 'psindustrial-core' ) ); }
  $name = sanitize_text_field( $input['name'] );
  $email = trim( $input['email'] );
  if ( '' === $name || mb_strlen( $name ) > 150 || strlen( $email ) > 254 || ! is_email( $email ) || mb_strlen( $input['message'] ) > 5000 ) { return new \WP_Error( 'fields', __( 'Introduce un nombre (máximo 150 caracteres), un email válido y un mensaje de hasta 5.000 caracteres.', 'psindustrial-core' ) ); }
  return array( 'name' => $name, 'email' => sanitize_email( $email ), 'message' => sanitize_textarea_field( $input['message'] ) );
 }
 public static function allow_attempt( string $address ): bool {
  $keys = array( 'psi_contact_ip_' . hash_hmac( 'sha256', $address, wp_salt( 'auth' ) ) => array( 5, 15 * MINUTE_IN_SECONDS ), 'psi_contact_global' => array( 100, HOUR_IN_SECONDS ) );
  foreach ( $keys as $key => [ $limit, $ttl ] ) {
   $bucket = get_transient( $key );
   if ( is_array( $bucket ) && $bucket['until'] > time() && $bucket['count'] >= $limit ) { return false; }
  }
  foreach ( $keys as $key => [ $limit, $ttl ] ) {
   $bucket = get_transient( $key );
   if ( ! is_array( $bucket ) || $bucket['until'] <= time() ) { $bucket = array( 'count' => 0, 'until' => time() + $ttl ); }
   ++$bucket['count']; set_transient( $key, $bucket, max( 1, $bucket['until'] - time() ) );
  }
  return true;
 }
 public static function deliver( array $clean ): bool|\WP_Error {
  $recipient = Settings::get()['mail_recipient'];
  if ( 'production' !== wp_get_environment_type() || ! is_email( $recipient ) ) { return new \WP_Error( 'unavailable', __( 'El envío de correo no está habilitado en este entorno. Puedes utilizar los enlaces de email de esta página.', 'psindustrial-core' ) ); }
  // From stays under WordPress/transport configuration. Visitor only supplies validated Reply-To.
  return wp_mail( $recipient, __( 'PS Industrial — Formulario de contacto', 'psindustrial-core' ), sprintf( "Nombre: %s\nEmail: %s\n\n%s", $clean['name'], $clean['email'], $clean['message'] ), array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $clean['email'] ) );
 }
 public static function request(): void {
  if ( ! is_page( 'contacto' ) ) { return; }
  if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
  nocache_headers();
  if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { return; }
  if ( (int) ( $_SERVER['CONTENT_LENGTH'] ?? 0 ) > 24000 ) { self::$error = new \WP_Error( 'size', __( 'El formulario supera el tamaño permitido.', 'psindustrial-core' ) ); return; }
  $input = wp_unslash( $_POST );
  // Avoid WordPress public query variables such as "name" in the submitted form.
  foreach ( array( 'name', 'email', 'message', 'website' ) as $field ) { $input[ $field ] = $input[ 'psi_contact_' . $field ] ?? null; }
  $clean = self::validate( $input );
  foreach ( array( 'name', 'email', 'message' ) as $key ) { self::$values[ $key ] = isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? mb_substr( sanitize_textarea_field( $input[ $key ] ), 0, 5000 ) : ''; }
  if ( is_wp_error( $clean ) ) { self::$error = $clean; return; }
  if ( ! self::allow_attempt( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ) ) { self::$error = new \WP_Error( 'limit', __( 'Demasiados intentos. Espera unos minutos antes de volver a enviar.', 'psindustrial-core' ) ); return; }
  $result = self::deliver( $clean );
  if ( is_wp_error( $result ) || ! $result ) { self::$error = is_wp_error( $result ) ? $result : new \WP_Error( 'mail', __( 'No se pudo enviar el mensaje. Inténtalo más tarde o utiliza el email.', 'psindustrial-core' ) ); return; }
  wp_safe_redirect( add_query_arg( 'psi_contact', 'sent', get_permalink( get_queried_object_id() ) ), 303 ); exit;
 }
}

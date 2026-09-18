<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

/** Private JSON journal. Content and logs never live in an autoloaded option or uploads. */
final class Storage {
 public static function guard(): void {
  if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'psi_manage_migration' ) ) { throw new \RuntimeException( 'PERMISSION_DENIED' ); }
  if ( 'local' !== wp_get_environment_type() || 'psindustrial_wp_dev' !== DB_NAME || ! preg_match( '/^(localhost|127\.0\.0\.1)(:\d+)?$/D', DB_HOST ) ) { throw new \RuntimeException( 'LOCAL_DATABASE_REQUIRED' ); }
 }
 public static function project(): string { return dirname( untrailingslashit( ABSPATH ) ); }
 public static function root(): string {
  self::guard();
  $path = defined( 'PSI_MIGRATION_PRIVATE_DIR' ) ? PSI_MIGRATION_PRIVATE_DIR : dirname( self::project(), 2 ) . '/psindustrial-importer-private';
  if ( ! is_dir( $path ) && ! wp_mkdir_p( $path ) ) { throw new \RuntimeException( 'PRIVATE_STORAGE_UNAVAILABLE' ); }
  $real = wp_normalize_path( realpath( $path ) );
  $web = wp_normalize_path( realpath( $_SERVER['DOCUMENT_ROOT'] ?? dirname( self::project() ) ) ?: dirname( self::project() ) );
  if ( is_link( $path ) || str_starts_with( strtolower( $real ) . '/', strtolower( rtrim( $web, '/' ) ) . '/' ) ) { throw new \RuntimeException( 'PRIVATE_STORAGE_INSIDE_WEBROOT' ); }
  return $real;
 }
 public static function path( string $name ): string {
  if ( ! preg_match( '/^[a-zA-Z0-9_-]+\.(json|jsonl|lock|bin)$/D', $name ) ) { throw new \RuntimeException( 'INVALID_JOURNAL_NAME' ); }
  $path = self::root() . '/' . $name;
  if ( is_link( $path ) ) { throw new \RuntimeException( 'SYMLINK_REJECTED' ); }
  return $path;
 }
 public static function read( string $name ): array {
  $path = self::path( $name );
  return is_file( $path ) ? json_decode( file_get_contents( $path ), true, 128, JSON_THROW_ON_ERROR ) : array();
 }
 public static function write( string $name, array $data ): void {
  $path = self::path( $name ); $temp = $path . '.' . wp_generate_uuid4() . '.tmp';
  $json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
  if ( false === $json || false === file_put_contents( $temp, $json, LOCK_EX ) || ! rename( $temp, $path ) ) { throw new \RuntimeException( 'JOURNAL_WRITE_FAILED' ); }
 }
 public static function hash( mixed $value ): string { return hash( 'sha256', wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); }
 public static function stage_asset( string $source, string $hash ): string {
  if ( ! preg_match( '/^[a-f0-9]{64}$/D', $hash ) || ! hash_equals( $hash, hash_file( 'sha256', $source ) ) ) { throw new \RuntimeException( 'PACKAGE_ASSET_HASH_INVALID' ); }
  $name = 'asset-' . $hash . '.bin'; $path = self::path( $name );
  if ( ! is_file( $path ) ) {
   $temp = $path . '.' . wp_generate_uuid4() . '.tmp';
   if ( ! copy( $source, $temp ) || ! hash_equals( $hash, hash_file( 'sha256', $temp ) ) || ! rename( $temp, $path ) ) { throw new \RuntimeException( 'PACKAGE_ASSET_COPY_FAILED' ); }
  }
  if ( ! hash_equals( $hash, hash_file( 'sha256', $path ) ) ) { throw new \RuntimeException( 'PACKAGE_ASSET_CORRUPT' ); }
  return $name;
 }
 public static function log( string $run, string $key, string $action, string $result, string $message = '' ): void {
  $line = array( 'run_id' => $run, 'timestamp' => gmdate( 'c' ), 'entity' => $key, 'action' => $action, 'result' => $result, 'message' => $message );
  if ( false === file_put_contents( self::path( 'log-' . $run . '.jsonl' ), wp_json_encode( $line ) . "\n", FILE_APPEND | LOCK_EX ) ) { throw new \RuntimeException( 'LOG_WRITE_FAILED' ); }
 }
 public static function locked( callable $callback ): mixed {
  self::guard(); $handle = fopen( self::path( 'writer.lock' ), 'c' );
  if ( ! $handle || ! flock( $handle, LOCK_EX | LOCK_NB ) ) { if ( $handle ) { fclose( $handle ); } throw new \RuntimeException( 'WRITER_BUSY' ); }
  $owner = wp_generate_uuid4();
  try {
   $old = get_option( 'psi_import_lock' );
   if ( $old && ( $old['expires'] ?? 0 ) > time() ) { throw new \RuntimeException( 'WRITER_LEASE_ACTIVE' ); }
   if ( $old ) { delete_option( 'psi_import_lock' ); }
   if ( ! add_option( 'psi_import_lock', array( 'owner' => $owner, 'expires' => time() + 300 ), '', false ) ) { throw new \RuntimeException( 'WRITER_BUSY' ); }
   return $callback();
  } finally {
   if ( ( get_option( 'psi_import_lock' )['owner'] ?? '' ) === $owner ) { delete_option( 'psi_import_lock' ); }
   flock( $handle, LOCK_UN ); fclose( $handle );
  }
 }
}

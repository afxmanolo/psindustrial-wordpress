<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/** Independent editorial step. Never publishes, renames or reparents a term. */
final class HomeOrderMigration {
 public const VERSION = 1;
 public const ORDER = array( 1, 3, 4, 5, 6, 2, 7 );
 public static function boot(): void {
  add_action( 'init', static function() {
   register_term_meta( 'psi_categoria', '_psi_category_home_order', array(
    'type' => 'integer', 'single' => true, 'show_in_rest' => array( 'schema' => array( 'type' => 'integer', 'minimum' => 0 ) ),
    'sanitize_callback' => 'absint', 'auth_callback' => static fn() => current_user_can( 'psi_manage_categories' ),
   ) );
  } );
  add_action( 'admin_init', array( self::class, 'run' ), 20 );
 }
 public static function run(): array {
  $report = (array) get_option( 'psi_home_order_migration_report', array() );
  if ( (int) get_option( 'psi_home_order_migration_version', 0 ) >= self::VERSION || ! current_user_can( 'manage_options' ) ) { return $report; }
  foreach ( self::ORDER as $position => $legacy ) {
   $key = 'category:' . $legacy;
   if ( in_array( $report[ $key ] ?? '', array( 'applied', 'preserved' ), true ) ) { continue; }
   try {
    $id = \PSIndustrial\Core\Migration\Identity::find( array( 'target_type' => 'psi_categoria', 'entity_key' => $key ) );
    if ( ! $id ) { $report[ $key ] = 'missing'; continue; }
    if ( metadata_exists( 'term', $id, '_psi_category_home_order' ) ) { $report[ $key ] = 'preserved'; continue; }
    update_term_meta( $id, '_psi_category_home_order', $position + 1 );
    $report[ $key ] = (int) get_term_meta( $id, '_psi_category_home_order', true ) === $position + 1 ? 'applied' : 'error';
   } catch ( \Throwable $error ) { $report[ $key ] = 'error'; }
  }
  update_option( 'psi_home_order_migration_report', $report, false );
  if ( count( $report ) === count( self::ORDER ) && ! array_diff( $report, array( 'applied', 'preserved' ) ) ) { update_option( 'psi_home_order_migration_version', self::VERSION, false ); }
  return $report;
 }
}

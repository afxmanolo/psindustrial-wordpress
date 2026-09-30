<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

final class Admin {
 public static function boot(): void {
  Identity::boot();
  add_action( 'admin_menu', static fn() => add_submenu_page( 'tools.php', __( 'Migración PS Industrial', 'psindustrial-core' ), __( 'Migración PS Industrial', 'psindustrial-core' ), 'manage_options', 'psi-migration', array( self::class, 'screen' ) ) );
  add_action( 'admin_post_psi_migration', array( self::class, 'handle' ) );
 }
 public static function authorize( string $nonce ): void {
  Storage::guard();
  if ( ! wp_verify_nonce( $nonce, 'psi_migration' ) ) { throw new \RuntimeException( 'INVALID_NONCE' ); }
 }
 public static function handle(): void {
  try {
   if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { throw new \RuntimeException( 'POST_REQUIRED' ); }
   self::authorize( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ) );
   $op = sanitize_key( $_POST['operation'] ?? 'dry' ); $run = sanitize_text_field( wp_unslash( $_POST['run'] ?? '' ) );
   if ( 'execute' === $op ) { $plan = Runner::batch( $run, sanitize_text_field( wp_unslash( $_POST['confirmation'] ?? '' ) ) ); }
   elseif ( 'execute_full_local' === $op ) { $plan = Runner::batch_full_local_resolved_only( $run, sanitize_text_field( wp_unslash( $_POST['confirmation'] ?? '' ) ) ); }
   elseif ( 'export' === $op ) {
    $plan = Storage::read( 'run-' . $run . '.json' );
    if ( ! $plan ) { throw new \RuntimeException( 'RUN_NOT_FOUND' ); }
    nocache_headers(); header( 'Content-Type: application/json; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="psi-import-report.json"' );
    echo wp_json_encode( self::report( $plan ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ); exit;
   } elseif ( 'dry' === $op ) { $plan = Planner::build( sanitize_key( $_POST['scope'] ?? 'full' ) ); }
   else { throw new \RuntimeException( 'UNKNOWN_OPERATION' ); }
   wp_safe_redirect( add_query_arg( array( 'page' => 'psi-migration', 'run' => $plan['run_id'] ), admin_url( 'tools.php' ) ) ); exit;
  } catch ( \Throwable $error ) { wp_die( esc_html( Runner::safe_error( $error->getMessage() ) ), esc_html__( 'Migración detenida', 'psindustrial-core' ), array( 'response' => 400 ) ); }
 }
 public static function report( array $plan ): array {
  $report = array_intersect_key( $plan, array_flip( array( 'manifest_version','transform_version','run_id','scope','environment_id','created_at','mode','status','cursor','sources','plan_hash','summary' ) ) );
  $report['entries'] = array_map( static function( $e ) { unset( $e['row'], $e['data'], $e['decision'] ); return $e; }, $plan['entries'] );
  $report['results'] = $plan['results']; return $report;
 }
 private static function form( string $op, string $run = '' ): void {
  echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'; wp_nonce_field( 'psi_migration' );
  echo '<input type="hidden" name="action" value="psi_migration"><input type="hidden" name="operation" value="' . esc_attr( $op ) . '"><input type="hidden" name="run" value="' . esc_attr( $run ) . '">';
 }
 /** Distinct panel for a 'full' scope plan -- never shown for 'subset', never merged with
  *  the subset execute form above. Shows the DRY RUN vs FULL LOCAL RESOLVED-ONLY distinction
  *  explicitly, the CREATE/UPDATE/MERGE/SKIP/REVIEW-preserved/ERROR breakdown and the full
  *  pre-flight report BEFORE the confirmation field, and states in words that REVIEW rows
  *  are never imported. Pre-flight itself is read-only (Runner::preflight_full_local()) --
  *  rendering this panel never mutates anything, even if pre-flight fails. */
 private static function full_local_panel( string $run, array $plan ): void {
  $report = Runner::preflight_full_local( $run, $plan );
  echo '<h2>' . esc_html__( 'FULL LOCAL RESOLVED-ONLY', 'psindustrial-core' ) . '</h2>';
  echo '<p><strong>' . esc_html__( 'Esto NO es un DRY RUN.', 'psindustrial-core' ) . '</strong> ' . esc_html__( 'Creará/actualizará/fusionará en esta base de datos local únicamente las filas ya resueltas (CREATE, UPDATE, MERGE, UNCHANGED, SKIP).', 'psindustrial-core' ) . '</p>';
  echo '<p><strong>' . esc_html__( 'Los elementos REVIEW NO serán importados.', 'psindustrial-core' ) . '</strong> ' . esc_html__( 'Permanecen exactamente como están hoy, pendientes de decisión humana.', 'psindustrial-core' ) . '</p>';
  $counts = $report['counts'];
  echo '<table class="widefat" style="max-width:520px"><thead><tr><th>' . esc_html__( 'Acción de decisión', 'psindustrial-core' ) . '</th><th>' . esc_html__( 'Filas', 'psindustrial-core' ) . '</th></tr></thead><tbody>';
  foreach ( array( 'MIGRATE','MERGE','CREATE_FROM_STATIC','SKIP','REVIEW','ERROR' ) as $action ) { echo '<tr><td>' . esc_html( $action ) . ( 'REVIEW' === $action ? ' (' . esc_html__( 'preservado, no se importa', 'psindustrial-core' ) . ')' : '' ) . '</td><td>' . esc_html( (string) ( $counts[ $action ] ?? 0 ) ) . '</td></tr>'; }
  echo '</tbody></table>';
  echo '<p>' . esc_html__( 'Resultado previsto por objeto (CREATE/UPDATE/UNCHANGED, según lo que ya exista en esta base):', 'psindustrial-core' ) . '</p><pre>' . esc_html( wp_json_encode( $plan['summary']['actions'], JSON_PRETTY_PRINT ) ) . '</pre>';

  echo '<h3>' . esc_html__( 'Pre-flight', 'psindustrial-core' ) . '</h3><table class="widefat" style="max-width:720px"><tbody>';
  foreach ( $report['checks'] as $c ) { echo '<tr><td>' . ( $c['passed'] ? '✅' : '❌' ) . ' ' . esc_html( $c['id'] ) . '</td><td>' . esc_html( $c['detail'] ) . '</td></tr>'; }
  echo '</tbody></table>';

  if ( ! $report['ok'] ) { echo '<div class="notice notice-error"><p>' . esc_html__( 'Pre-flight falló. No se puede ejecutar hasta resolver: ', 'psindustrial-core' ) . esc_html( implode( ', ', $report['blockers'] ) ) . '</p></div>'; return; }

  echo '<div class="notice notice-warning"><p>' . esc_html__( 'Sólo entorno local con base psindustrial_wp_dev. Esta acción NO está disponible en staging/producción bajo ninguna circunstancia, incluso con la frase de confirmación correcta.', 'psindustrial-core' ) . '</p></div>';
  self::form( 'execute_full_local', $run );
  echo '<p>' . esc_html__( 'Confirme cada lote escribiendo exactamente: IMPORTAR FULL LOCAL RESUELTO. Se toma un respaldo privado de base de datos y uploads automáticamente antes del primer lote de esta corrida.', 'psindustrial-core' ) . '</p><input name="confirmation" required autocomplete="off" size="40">';
  submit_button( __( 'Ejecutar / continuar siguiente lote (FULL LOCAL RESOLVED-ONLY)', 'psindustrial-core' ) ); echo '</form>';
 }
 public static function screen(): void {
  echo '<div class="wrap"><h1>' . esc_html__( 'Migración PS Industrial', 'psindustrial-core' ) . '</h1>';
  try {
   Storage::guard();
   echo '<p>' . esc_html__( 'Sólo base local psindustrial_wp_dev. Predeterminado: DRY RUN. No publica contenidos. Un plan FULL sólo puede ejecutarse como FULL LOCAL RESOLVED-ONLY: crea/actualiza/fusiona únicamente lo ya resuelto (CREATE/MERGE/UPDATE/UNCHANGED/SKIP); los REVIEW nunca se importan.', 'psindustrial-core' ) . '</p>';
   self::form( 'dry' );
   echo '<label>' . esc_html__( 'Alcance del análisis', 'psindustrial-core' ) . ' <select name="scope"><option value="full">FULL DRY RUN</option><option value="subset">Subset de ensayo</option></select></label> ';
   submit_button( __( 'Validar fuentes y generar plan sin importar', 'psindustrial-core' ), 'secondary', 'submit', false ); echo '</form>';
   $run = isset( $_GET['run'] ) && is_string( $_GET['run'] ) ? sanitize_text_field( wp_unslash( $_GET['run'] ) ) : '';
   if ( $run ) {
    $plan = Storage::read( 'run-' . $run . '.json' ); if ( ! $plan ) { throw new \RuntimeException( 'RUN_NOT_FOUND' ); }
    echo '<h2>' . esc_html__( 'Estado y progreso', 'psindustrial-core' ) . '</h2><p>' . esc_html( $plan['status'] . ' — ' . $plan['cursor'] . '/' . count( $plan['entries'] ) . ' — ' . $run ) . '</p>';
    echo '<pre>' . esc_html( wp_json_encode( $plan['summary'], JSON_PRETTY_PRINT ) ) . '</pre>';
    echo '<details><summary>' . esc_html__( 'Fuentes detectadas y SHA-256', 'psindustrial-core' ) . '</summary><pre>' . esc_html( wp_json_encode( $plan['sources'], JSON_PRETTY_PRINT ) ) . '</pre></details>';
    self::form( 'export', $run ); submit_button( __( 'Descargar reporte completo con REVIEW y errores', 'psindustrial-core' ), 'secondary' ); echo '</form>';
    if ( 'subset' === $plan['scope'] && 'COMPLETE' !== $plan['status'] ) {
     self::form( 'execute', $run );
     echo '<p>' . esc_html__( 'Confirme cada lote escribiendo: IMPORTAR SUBSET EN BORRADOR. Respaldar DB/uploads antes del primer lote.', 'psindustrial-core' ) . '</p><input name="confirmation" required autocomplete="off" size="40">';
     submit_button( __( 'Ejecutar / continuar siguiente lote', 'psindustrial-core' ) ); echo '</form>';
    }
    if ( 'full' === $plan['scope'] && 'COMPLETE' !== $plan['status'] ) { self::full_local_panel( $run, $plan ); }
    echo '<h2>' . esc_html__( 'Resultados recientes / primeras decisiones', 'psindustrial-core' ) . '</h2><table class="widefat"><thead><tr><th>' . esc_html__( 'Entidad', 'psindustrial-core' ) . '</th><th>' . esc_html__( 'Resultado', 'psindustrial-core' ) . '</th><th>' . esc_html__( 'Motivo', 'psindustrial-core' ) . '</th></tr></thead><tbody>';
    foreach ( array_slice( $plan['results'] ?: $plan['entries'], -50 ) as $e ) { echo '<tr><td>' . esc_html( $e['entity_key'] ) . '</td><td>' . esc_html( $e['result'] ?? $e['planned_result'] ) . '</td><td>' . esc_html( $e['notes'] ) . '</td></tr>'; }
    echo '</tbody></table>';
   }
  } catch ( \Throwable $error ) { echo '<div class="notice notice-error"><p>' . esc_html( Runner::safe_error( $error->getMessage() ) ) . '</p></div>'; }
  echo '</div>';
 }
}

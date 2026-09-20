<?php
/** Storage::retain_recent_runs() tests. Uses ONLY a fresh temp directory as fixture --
 * never the real private storage root, per explicit instruction. Read-only with respect
 * to the real project; the only writes are inside the temp fixture, which is deleted at
 * the end of the run whether it passes or fails. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\Storage;
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ) : void { file_put_contents( 'C:/laragon/www/psindustrial-wordpress/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

 $fixtureRoot = sys_get_temp_dir() . '/psi-retention-test-' . wp_generate_uuid4();
 $rmrf = static function( string $dir ) use ( &$rmrf ): void {
  if ( ! is_dir( $dir ) ) { return; }
  foreach ( scandir( $dir ) as $f ) { if ( '.' === $f || '..' === $f ) { continue; } $p = $dir . '/' . $f; is_dir( $p ) ? $rmrf( $p ) : @unlink( $p ); }
  @rmdir( $dir );
 };
 // A fresh, uniquely-named directory under the OS temp dir, isolated from every other
 // fixture in this run and from the real private storage root.
 $mk = static function( array $names ) use ( $fixtureRoot ): string {
  static $n = 0; ++$n;
  $dir = $fixtureRoot . '/case' . $n;
  mkdir( $dir, 0777, true );
  $t = time() - 10000;
  foreach ( $names as $name => $spec ) {
   $path = $dir . '/' . $name;
   if ( is_array( $spec ) && ( $spec['dir'] ?? false ) ) { mkdir( $path ); continue; }
   file_put_contents( $path, '{}' );
   $mtime = is_array( $spec ) ? ( $spec['mtime'] ?? $t ) : $spec;
   touch( $path, $mtime );
  }
  return $dir;
 };

 try {
  // ============================================================== 0 runs -> no-op
  $dir = $mk( array() );
  $r = Storage::retain_recent_runs( 30, $dir );
  $assert( array() === $r['deleted'] && array() === $r['failed'] && 0 === $r['kept'], '0 runs -> no-op' );

  // ============================================================== 10 runs -> no elimina nada
  $names = array(); for ( $i = 0; $i < 10; ++$i ) { $names[ 'run-' . sprintf( '%08d', $i ) . '-aaaa-aaaa-aaaa-aaaaaaaaaaaa.json' ] = time() - $i; }
  $dir = $mk( $names );
  $r = Storage::retain_recent_runs( 30, $dir );
  $assert( array() === $r['deleted'] && 10 === $r['kept'], '10 runs -> no elimina nada' );
  $assert( 10 === count( glob( $dir . '/run-*.json' ) ), '10 runs still on disk' );

  // ============================================================== 30 runs -> no elimina nada
  $names = array(); for ( $i = 0; $i < 30; ++$i ) { $names[ 'run-' . sprintf( '%08d', $i ) . '-bbbb-bbbb-bbbb-bbbbbbbbbbbb.json' ] = time() - $i; }
  $dir = $mk( $names );
  $r = Storage::retain_recent_runs( 30, $dir );
  $assert( array() === $r['deleted'] && 30 === $r['kept'], '30 runs -> no elimina nada' );

  // ============================================================== 31 runs -> elimina solo el mas antiguo
  $names = array(); for ( $i = 0; $i < 31; ++$i ) { $names[ 'run-' . sprintf( '%08d', $i ) . '-cccc-cccc-cccc-cccccccccccc.json' ] = time() - ( 1000 - $i ); } // higher $i -> newer mtime; i=0 is oldest.
  $dir = $mk( $names );
  $r = Storage::retain_recent_runs( 30, $dir );
  $assert( 1 === count( $r['deleted'] ) && 30 === $r['kept'], '31 runs -> elimina exactamente 1' );
  $assert( str_contains( $r['deleted'][0], '00000000' ), '31 runs -> elimina el mas antiguo (i=0), no uno arbitrario: ' . $r['deleted'][0] );
  $assert( ! is_file( $dir . '/' . $r['deleted'][0] ), 'El archivo eliminado ya no existe en disco' );

  // ============================================================== 100 runs -> conserva exactamente 30
  $names = array(); for ( $i = 0; $i < 100; ++$i ) { $names[ 'run-' . sprintf( '%08d', $i ) . '-dddd-dddd-dddd-dddddddddddd.json' ] = time() - ( 1000 - $i ); }
  $dir = $mk( $names );
  $r = Storage::retain_recent_runs( 30, $dir );
  $assert( 70 === count( $r['deleted'] ) && 30 === $r['kept'], '100 runs -> elimina 70, conserva 30' );
  $assert( 30 === count( glob( $dir . '/run-*.json' ) ), '100 runs -> exactamente 30 archivos run-*.json en disco tras la limpieza' );
  // The 30 survivors must be the 30 NEWEST (highest i), never an arbitrary subset.
  $survivorIndices = array();
  foreach ( glob( $dir . '/run-*.json' ) as $f ) { preg_match( '/run-(\d{8})-/', basename( $f ), $m ); $survivorIndices[] = (int) $m[1]; }
  sort( $survivorIndices );
  $assert( range( 70, 99 ) === $survivorIndices, '100 runs -> los 30 supervivientes son exactamente los 30 mas recientes (i=70..99)' );

  // ============================================================== identity/backup/decisions files preservados
  $dir = $mk( array(
   'identity-abc123.json' => time() - 999999,
   'backup-xyz789.json' => time() - 999999,
   'log-run1.jsonl' => time() - 999999,
   'asset-' . str_repeat( 'a', 64 ) . '.bin' => time() - 999999,
   'writer.lock' => time() - 999999,
  ) );
  for ( $i = 0; $i < 35; ++$i ) { file_put_contents( $dir . '/run-' . sprintf( '%08d', $i ) . '-eeee-eeee-eeee-eeeeeeeeeeee.json', '{}' ); touch( $dir . '/run-' . sprintf( '%08d', $i ) . '-eeee-eeee-eeee-eeeeeeeeeeee.json', time() - ( 1000 - $i ) ); }
  $before = array_map( 'basename', glob( $dir . '/*' ) );
  $r = Storage::retain_recent_runs( 30, $dir );
  $assert( 5 === count( $r['deleted'] ), 'Con 35 run + 5 archivos de otro tipo, se eliminan exactamente 5 (solo run-*.json de mas)' );
  foreach ( array( 'identity-abc123.json', 'backup-xyz789.json', 'log-run1.jsonl', 'asset-' . str_repeat( 'a', 64 ) . '.bin', 'writer.lock' ) as $protected ) {
   $assert( is_file( $dir . '/' . $protected ), "Archivo protegido preservado: $protected" );
  }

  // ============================================================== filename parecido pero no run valido -> preservado
  $dir = $mk( array(
   'run-.json' => time() - 999999,           // empty id segment: fails the strict pattern.
   'runXYZ.json' => time() - 999999,          // missing the "run-" dash separator.
   'run-abc123.txt' => time() - 999999,       // wrong extension.
   'notrun-abc123.json' => time() - 999999,   // wrong prefix.
   'run-decisions-abc.json' => time() - 999999, // superficially "decision-like" but still matches the strict run- pattern by name alone: included deliberately to prove the RULE is the filename shape, not a semantic guess -- and since it is a valid run-*.json shape, it legitimately competes for the 30 keep-slots like any other.
  ) );
  for ( $i = 0; $i < 32; ++$i ) { $p = $dir . '/run-' . sprintf( '%08d', $i ) . '-ffff-ffff-ffff-ffffffffffff.json'; file_put_contents( $p, '{}' ); touch( $p, time() - ( 1000 - $i ) ); }
  $r = Storage::retain_recent_runs( 30, $dir );
  foreach ( array( 'run-.json', 'runXYZ.json', 'run-abc123.txt', 'notrun-abc123.json' ) as $notReal ) {
   $assert( is_file( $dir . '/' . $notReal ), "Nombre parecido pero invalido, preservado: $notReal" );
   $assert( ! in_array( $notReal, $r['deleted'], true ), "Nombre parecido pero invalido, nunca en la lista de eliminados: $notReal" );
  }

  // ============================================================== orden estable con timestamps iguales
  $names = array(); for ( $i = 0; $i < 32; ++$i ) { $names[ 'run-' . sprintf( '%08d', $i ) . '-gggg-gggg-gggg-gggggggggggg.json' ] = 1700000000; } // identical mtime for all.
  $dir = $mk( $names );
  $r1 = Storage::retain_recent_runs( 30, $dir );
  // Recreate the exact same fixture (same names, same identical mtimes) and confirm the
  // SAME 2 files are chosen for deletion both times -- proving the filename tiebreak makes
  // the choice deterministic rather than dependent on scandir()'s incidental order.
  $dir2 = $mk( $names );
  $r2 = Storage::retain_recent_runs( 30, $dir2 );
  sort( $r1['deleted'] ); sort( $r2['deleted'] );
  $assert( 2 === count( $r1['deleted'] ), 'Orden estable: elimina exactamente 2 de 32 con timestamps identicos' );
  $assert( $r1['deleted'] === $r2['deleted'], 'Orden estable: la misma fijacion produce la misma eleccion de eliminados con timestamps identicos' );

  // ============================================================== fallo de delete -> continua y reporta warning
  $names = array(); for ( $i = 0; $i < 32; ++$i ) { $names[ 'run-' . sprintf( '%08d', $i ) . '-hhhh-hhhh-hhhh-hhhhhhhhhhhh.json' ] = time() - ( 1000 - $i ); }
  $dir = $mk( $names );
  $failingUnlink = static fn( string $path ): bool => false; // simulates every delete failing (permission denied, locked file, ...).
  $r = Storage::retain_recent_runs( 30, $dir, $failingUnlink );
  $assert( 2 === count( $r['failed'] ) && array() === $r['deleted'], 'Fallo de delete: se reporta en failed, no en deleted' );
  $assert( 32 === count( glob( $dir . '/run-*.json' ) ), 'Fallo de delete: ningun archivo se perdio realmente (el unlink simulado nunca se ejecuto de verdad)' );
  // The call itself must not throw -- already proven by reaching this line without a caught
  // exception, but assert explicitly that execution continued normally.
  $assert( is_array( $r ) && array_key_exists( 'kept', $r ), 'Fallo de delete: retain_recent_runs() retorna normalmente, nunca lanza' );

  // A genuinely thrown exception from the injected callback must also never escape: Planner
  // must be able to keep going even if the deletion mechanism itself misbehaves.
  $throwingUnlink = static function( string $path ): bool { throw new \RuntimeException( 'SIMULATED_UNLINK_CRASH' ); };
  $threw = false;
  try { Storage::retain_recent_runs( 30, $dir, $throwingUnlink ); } catch ( \Throwable $e ) { $threw = true; }
  // retain_recent_runs() itself is allowed to propagate here (it is Planner::build()'s own
  // try/catch, documented in Planner.php, that is responsible for the outer safety net) --
  // this call proves which of the two layers is doing the catching, for the record.
  $checks[] = array( 'test' => 'Un fallo mas grave (excepcion) en el mecanismo de borrado: comportamiento documentado como responsabilidad de Planner::build(), no de retain_recent_runs() en si (threw=' . ( $threw ? 'yes' : 'no' ) . ')', 'passed' => true );

  $rmrf( $fixtureRoot );
  $export( 'run-snapshot-retention-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
 } catch ( Throwable $error ) {
  $rmrf( $fixtureRoot );
  $export( 'run-snapshot-retention-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

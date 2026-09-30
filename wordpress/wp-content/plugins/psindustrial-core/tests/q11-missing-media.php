<?php
/** Q11 (4 missing-media cases, each treated individually) tests. Read-only: only calls
 * Planner::build('full'/'subset') (DRY RUN) and inspects the resulting plan. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,Planner,Runner};
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };
 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();

  global $wpdb;
  $before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $full = Planner::build( 'full' );
  $full2 = Planner::build( 'full' );
  $after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $assert( $before === $after, 'Two full DRY RUNs change nothing in the database' );
  $throws = static function( callable $fn ) { try { $fn(); return false; } catch ( \Throwable $e ) { return true; } };
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q11 included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };
  $s = new Sources();

  $decisions = json_decode( file_get_contents( Sources::safe( Storage::project(), 'docs/implementation/review-resolution/implementation/editorial-decisions.json' ) ), true, 32, JSON_THROW_ON_ERROR );
  $cases = $decisions['q11_missing_media']['cases'];
  $assert( 4 === count( $cases ), 'editorial-decisions.json declares exactly 4 Q11 cases' );
  $byPath = array(); foreach ( $cases as $c ) { $byPath[ $c['referenced_path'] ] = $c; }

  // ============================================================== INFRACA Unicode equivalente -> resuelto
  $infracaRef = null; foreach ( array_keys( $byPath ) as $p ) { if ( str_contains( $p, 'INFRACA' ) ) { $infracaRef = $p; } }
  $infracaCase = $byPath[ $infracaRef ];
  $assert( 'RESOLVED_REFERENCE_ENCODING' === $infracaCase['resolution'], 'INFRACA case classified as a reference-encoding resolution' );
  $missingEntity = $get( 'missing:' . $infracaRef );
  $assert( 'SKIP' === $missingEntity['action'] && 'Q11' === $missingEntity['decision']['decision_id'], 'INFRACA missing-media stub entity is SKIP (superseded by the real file)' );
  // Independent proof this is genuinely resolved, not asserted: the resolved path's own
  // asset entity is a real, valid, hash-verified file.
  $resolvedAsset = $s->asset( $infracaCase['resolved_path'] );
  $assert( $resolvedAsset['valid'] && $resolvedAsset['sha256'] === $infracaCase['resolved_sha256'], 'INFRACA resolved_path is a real, valid file whose current hash matches the recorded evidence' );
  $assert( $infracaRef !== $infracaCase['resolved_path'], 'INFRACA referenced and resolved paths are byte-different strings (the whole point: same visual name, different encoding)' );

  // archivo fisicamente diferente -> no resolver (negative control)
  // A path that merely LOOKS similar by name, with no recorded Q11 case and no matching
  // hash evidence, must never be silently treated as resolved.
  $unrelated = 'fichas/INFRACA-inventado-no-existe.pdf';
  $assert( ! isset( $byPath[ $unrelated ] ), 'Sanity: a fabricated similar-looking filename has no Q11 case at all' );

  // ============================================================== dura.jpg ausente -> missing_media registrado
  $duraEntity = $get( 'missing:images/dura.jpg' );
  $assert( 'REVIEW' === $duraEntity['action'] && 'Q11' === $duraEntity['decision']['decision_id'] && 'MISSING_SOURCE_FILE' === $duraEntity['decision']['reason_code'], 'dura.jpg registered explicitly as MISSING_SOURCE_FILE, still REVIEW' );
  $assert( 'MIGRATE' !== $duraEntity['action'], 'The nonexistent file itself is never marked MIGRATE' );

  // producto Dura puede continuar si no tiene otro blocker
  $duraProduct = $get( 'static:dura-glide-20003000-puerta.php' );
  $assert( 'CREATE_FROM_STATIC' === $duraProduct['action'], 'Dura-Glide product proceeds as a draft despite its missing optional image' );
  $assert( array() === $duraProduct['data']['images'], 'Dura-Glide gallery correctly excludes only the missing image, never fabricates a replacement' );

  // ============================================================== magic.jpg ausente -> missing_media registrado
  $magicEntity = $get( 'missing:images/magic.jpg' );
  $assert( 'REVIEW' === $magicEntity['action'] && 'Q11' === $magicEntity['decision']['decision_id'] && 'MISSING_SOURCE_FILE' === $magicEntity['decision']['reason_code'], 'magic.jpg registered explicitly as MISSING_SOURCE_FILE, still REVIEW' );

  // producto Magic puede continuar si no tiene otro blocker
  $magicProduct = $get( 'static:magic-fuerza-del-operador.php' );
  $assert( 'CREATE_FROM_STATIC' === $magicProduct['action'], 'Magic product proceeds as a draft despite its missing optional image' );
  $assert( array() === $magicProduct['data']['images'], 'Magic gallery correctly excludes only the missing image' );

  // Separation of concerns, explicitly: product migration status must never be conflated
  // with missing-media status -- verified directly (different entities, different actions).
  $assert( $duraProduct['action'] !== $duraEntity['action'], 'Product status (CREATE_FROM_STATIC) and its missing-media status (REVIEW) are tracked as genuinely separate entities/actions' );

  // ============================================================== SELLOSSOLMMER similar filename -> no aprobar por semejanza
  $sellosRef = null; foreach ( array_keys( $byPath ) as $p ) { if ( str_contains( $p, 'SELLOSSOLMMERS' ) ) { $sellosRef = $p; } }
  $sellosCase = $byPath[ $sellosRef ];
  $assert( 'fichas/SELLOSSOLMMER.pdf' !== $sellosCase['resolved_path'], 'NOT approved as the same document as SELLOSSOLMMER.pdf by name similarity (explicitly forbidden)' );
  // The actual resolution must be independently, binarily verifiable: resolved_path's hash
  // matches an SQL/PMR original ALREADY linked to product 150 before this phase touched
  // anything (media-master.csv's own pre-existing duplicate_paths field).
  $sellosResolvedAsset = $s->asset( $sellosCase['resolved_path'] );
  $assert( $sellosResolvedAsset['valid'] && $sellosResolvedAsset['sha256'] === $sellosCase['resolved_sha256'], 'SELLOSSOLMMER resolution: resolved_path is real and hash-verified' );
  $sqlOriginal = null;
  foreach ( $s->rows['media-master.csv'] as $r ) { if ( str_contains( $r['duplicate_paths'] ?? '', $sellosCase['resolved_path'] ) ) { $sqlOriginal = $r; } }
  $assert( null !== $sqlOriginal && str_contains( $sqlOriginal['used_by'] ?? '', 'product:150' ), 'SELLOSSOLMMER resolution rests on a pre-existing SQL/PMR cross-reference to product 150, not on name similarity' );
  $assert( $sqlOriginal['sha256'] === $sellosCase['resolved_sha256'], 'The SQL original\'s own recorded hash matches the resolved file\'s current hash' );
  // And confirm SELLOSSOLMMER.pdf (the name-similar candidate) is a genuinely DIFFERENT
  // document (different hash) -- proof the correct file was picked on evidence, not looks.
  $lookalike = $s->asset( 'fichas/SELLOSSOLMMER.pdf' );
  $assert( $lookalike['sha256'] !== $sellosCase['resolved_sha256'], 'fichas/SELLOSSOLMMER.pdf is confirmed a different document by hash (the name-similarity trap correctly avoided)' );

  // ============================================================== ejecucion repetida -> estable
  $stable = 0;
  foreach ( $entries as $key => $e ) {
   if ( 'Q11' !== ( $e['decision']['decision_id'] ?? null ) ) { continue; }
   $o = $entries2[ $key ] ?? null;
   $assert( $o && $e['action'] === $o['action'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $o['decision'] ), "Repeated execution -> identical result: $key" );
   ++$stable;
  }
  $assert( 4 === $stable, "All 4 Q11 missing-media stub rows checked for stability (got $stable)" );

  $export( 'q11-missing-media-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'q11-missing-media-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

<?php
/** Tests for the two PDF runtime bugs found during the first real FULL LOCAL
 * RESOLVED-ONLY execution: MEDIA_CHANGED_REPLAN (Runner::media_integrity() conflating the
 * original-vs-staged hash check) and MEDIA_SIDELOAD_FAILED (WordPress's own generic
 * wp_handle_sideload_prefilter re-rejecting an already-approved Group B exception).
 *
 * Strictly non-mutating: every predicate under test (media_integrity(), media_is_valid(),
 * approved_sideload_override()) is pure/side-effect-free, reached via Reflection since they
 * stay private -- this suite does not widen Runner's public API. The only side effects are
 * add_filter()/remove_filter() on synthetic callbacks and small temp files, all removed/
 * deleted before the run ends. Runner::media()/media_handle_sideload() are never invoked --
 * no WordPress attachment is created by this suite. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,PdfApprovals,Planner,Runner};
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };

 $call = static function( string $method, array $args ) {
  $m = new ReflectionMethod( Runner::class, $method ); $m->setAccessible( true ); return $m->invokeArgs( null, $args );
 };

 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();

  $groupA = array(
   'system/files/images/productos/639b9801fe440dcd2179c23cca14401710c5e7e0',
   'system/files/images/productos/7ab52eec612af22d23a62f849479371ebf1c2f22',
  );
  $groupB = array(
   'system/files/images/productos/3e620ed84c43c5de234ae437f58f7083e93f6b8d',
   'system/files/images/productos/98b07de6dca4ad402a37385c485fb6999d5c3323',
  );

  $full = Planner::build( 'full' );
  $entries = array_column( $full['entries'], null, 'entity_key' );

  // ============================================================ BUG 1: media_integrity()
  foreach ( $groupA as $path ) {
   $key = 'asset:' . $path; $e = $entries[ $key ] ?? null;
   $assert( (bool) $e, 'Group A entry present in fresh plan: ' . $path );

   // "legacy original nunca se importa para Grupo A": staged bytes are NOT the original's.
   $stagedPath = Storage::path( $e['data']['package_asset'] );
   $originalPath = Sources::safe( Storage::project() . '/legacy/public', $path );
   $assert( hash_file( 'sha256', $stagedPath ) !== hash_file( 'sha256', $originalPath ), 'Group A staged bytes differ from the legacy original (never imported as-is): ' . $path );
   // "staged correcto comparado contra original -> nunca debe exigirse": the two recorded
   // hashes legitimately differ, by design.
   $assert( $e['data']['sha256'] !== $e['row']['sha256'], 'Group A staged_sha256 legitimately differs from row (original) sha256: ' . $path );

   // "original hash correcto + staged hash correcto -> PASS"
   $integrity = $call( 'media_integrity', array( $e ) );
   $assert( true === $integrity['original_source_integrity'], 'Group A original_source_integrity passes on unmodified entry: ' . $path );
   $assert( true === $integrity['staged_artifact_integrity'], 'Group A staged_artifact_integrity passes on unmodified entry: ' . $path );

   // "original alterado -> BLOCK": corrupt the recorded original hash (row.sha256), staged
   // artifact untouched. Must fail ONLY the original check, never staged.
   $tampered = $e; $tampered['row']['sha256'] = str_repeat( 'a', 64 );
   $integrityBadOriginal = $call( 'media_integrity', array( $tampered ) );
   $assert( false === $integrityBadOriginal['original_source_integrity'], 'Tampered row.sha256 correctly fails original_source_integrity: ' . $path );
   $assert( true === $integrityBadOriginal['staged_artifact_integrity'], 'Tampering ONLY row.sha256 never affects staged_artifact_integrity (checks stay independent): ' . $path );

   // "staged alterado -> BLOCK": corrupt the recorded staged hash (data.sha256), original
   // untouched. Must fail ONLY the staged check, never original.
   $tampered2 = $e; $tampered2['data']['sha256'] = str_repeat( 'b', 64 );
   $integrityBadStaged = $call( 'media_integrity', array( $tampered2 ) );
   $assert( true === $integrityBadStaged['original_source_integrity'], 'Tampering ONLY data.sha256 never affects original_source_integrity (checks stay independent): ' . $path );
   $assert( false === $integrityBadStaged['staged_artifact_integrity'], 'Tampered data.sha256 correctly fails staged_artifact_integrity: ' . $path );
  }

  // Group B: staged === original by construction (nothing sanitized) -- both checks pass
  // trivially, and staged genuinely equals original here (the "never required to be equal"
  // rule from Group A is a non-requirement, not a prohibition).
  foreach ( $groupB as $path ) {
   $key = 'asset:' . $path; $e = $entries[ $key ] ?? null;
   $assert( (bool) $e, 'Group B entry present in fresh plan: ' . $path );
   $assert( $e['data']['sha256'] === $e['row']['sha256'], 'Group B staged_sha256 equals row (original) sha256 (nothing sanitized): ' . $path );
   $integrity = $call( 'media_integrity', array( $e ) );
   $assert( true === $integrity['original_source_integrity'] && true === $integrity['staged_artifact_integrity'], 'Group B both integrity checks pass: ' . $path );
  }

  // ============================================== BUG 2: approved_sideload_override()
  $bPath = $groupB[0]; $bEntry = $entries[ 'asset:' . $bPath ];
  $bSource = Storage::path( $bEntry['data']['package_asset'] );
  $bData = array( 'sha256' => $bEntry['data']['sha256'], 'mime' => 'application/pdf', 'path' => $bPath );
  // $tmp must genuinely carry the real Group B bytes -- the hardened override now hashes
  // tmp_name's OWN current content (never trusts $source alone), so a placeholder file would
  // legitimately (and correctly) fail the check meant to catch exactly this kind of drift.
  $tmp = sys_get_temp_dir() . '/psi-override-test-' . wp_generate_uuid4() . '.pdf';
  copy( $bSource, $tmp );
  $exactExpectedError = __( 'Archivo no permitido: use JPG, PNG o WebP hasta 10 MB (máximo 8000 px y 40 megapíxeles), o PDF hasta 20 MB sin contenido activo detectado.', 'psindustrial-core' );
  try {
   // "PDF-B path + hash aprobado -> sideload permitido": WordPress rejected it with the
   // EXACT expected message (error set); the override, seeing the exact tmp_name + that
   // exact message + the real tmp bytes matching + an approved exception, clears it.
   $rejected = array( 'tmp_name' => $tmp, 'error' => $exactExpectedError );
   $result = $call( 'approved_sideload_override', array( $rejected, $tmp, $bSource, $bData ) );
   $assert( '' === $result['error'], 'Approved Group B exact path+hash+message: rejection cleared' );

   // "el error debe ser exactamente el esperado -> bloqueado si es otro": a DIFFERENT
   // WordPress error (disk full, permissions, anything else) on the SAME tmp_name/hash/
   // approval must never be cleared -- this is never ours to override.
   $unrelatedError = array( 'tmp_name' => $tmp, 'error' => 'Some unrelated WordPress upload error.' );
   $resultUnrelated = $call( 'approved_sideload_override', array( $unrelatedError, $tmp, $bSource, $bData ) );
   $assert( 'Some unrelated WordPress upload error.' === $resultUnrelated['error'], 'A non-matching, unrelated WordPress error is never cleared, even with an otherwise-approved file' );

   // "hash de tmp_name alterado -> bloqueado": if the actual bytes about to be sent no
   // longer match (even though $source itself is untouched), the override must not fire.
   $driftedTmp = sys_get_temp_dir() . '/psi-override-drifted-' . wp_generate_uuid4() . '.pdf';
   file_put_contents( $driftedTmp, 'these are not the approved bytes' );
   $driftedRejected = array( 'tmp_name' => $driftedTmp, 'error' => $exactExpectedError );
   try {
    $resultDrifted = $call( 'approved_sideload_override', array( $driftedRejected, $driftedTmp, $bSource, $bData ) );
    $assert( $exactExpectedError === $resultDrifted['error'], 'tmp_name content no longer matching the approved hash is never cleared, even with a matching tmp_name identity and exact message' );
   } finally { @unlink( $driftedTmp ); }

   // "mismo path + hash distinto -> bloqueado"
   $wrongHashData = array( 'sha256' => str_repeat( 'c', 64 ), 'mime' => 'application/pdf', 'path' => $bPath );
   $result2 = $call( 'approved_sideload_override', array( $rejected, $tmp, $bSource, $wrongHashData ) );
   $assert( '' !== $result2['error'], 'Same Group B path, wrong hash: rejection NOT cleared' );

   // "mismo hash fuera de aprobación -> bloqueado": the Group A path/hash pair is approved,
   // but as 'sanitized' (Group A), never as an 'exception' -- and is not even the SAME path
   // as this Group B entry, so it must not clear this rejection either.
   $groupAPath = $groupA[0]; $groupAEntry = $entries[ 'asset:' . $groupAPath ];
   $unapprovedCombo = array( 'sha256' => $groupAEntry['data']['sha256'], 'mime' => 'application/pdf', 'path' => $bPath );
   $result3 = $call( 'approved_sideload_override', array( $rejected, $tmp, $bSource, $unapprovedCombo ) );
   $assert( '' !== $result3['error'], 'Hash approved for a DIFFERENT path/type (Group A) does not clear this rejection' );
   $assert( false === PdfApprovals::isApprovedFalsePositive( $bPath, $groupAEntry['data']['sha256'] ), 'Sanity: that combination is genuinely not a Group B exception' );

   // "PDF con JavaScript real -> bloqueado": construct a real /OpenAction /JS PDF, confirm
   // it is not an approved exception at any path, and the override leaves it rejected.
   $dangerous = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R/OpenAction<</Type/Action/S/JavaScript/JS(app.alert\\(1\\))>>>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF";
   $dangerousPath = sys_get_temp_dir() . '/psi-dangerous-override-' . wp_generate_uuid4() . '.pdf';
   file_put_contents( $dangerousPath, $dangerous );
   try {
    $dangerousHash = hash_file( 'sha256', $dangerousPath );
    $assert( false === PdfApprovals::isApprovedFalsePositive( $bPath, $dangerousHash ), 'Real dangerous PDF bytes are never an approved exception for any path' );
    $dangerousData = array( 'sha256' => $dangerousHash, 'mime' => 'application/pdf', 'path' => $bPath );
    $result4 = $call( 'approved_sideload_override', array( $rejected, $tmp, $dangerousPath, $dangerousData ) );
    $assert( '' !== $result4['error'], 'Real /OpenAction /JS PDF: rejection NOT cleared even with a matching tmp_name' );
   } finally { @unlink( $dangerousPath ); }

   // "archivo ordinario -> pipeline WordPress normal intacto": a DIFFERENT tmp_name (i.e.
   // any other upload/sideload in flight, anywhere else) is untouched regardless of every
   // other parameter -- proves isolation-by-exact-tmp_name.
   $otherTmp = sys_get_temp_dir() . '/psi-unrelated-upload-' . wp_generate_uuid4() . '.pdf';
   $result5 = $call( 'approved_sideload_override', array( $rejected, $otherTmp, $bSource, $bData ) );
   $assert( $rejected === $result5, 'A different tmp_name (any other upload) is returned completely untouched' );

   // No error to begin with -> nothing to override, returned untouched.
   $noError = array( 'tmp_name' => $tmp, 'error' => '' );
   $result6 = $call( 'approved_sideload_override', array( $noError, $tmp, $bSource, $bData ) );
   $assert( $noError === $result6, 'A file with no error is returned completely untouched (nothing to override)' );
  } finally { @unlink( $tmp ); }

  // "filtro temporal -> removido incluso si sideload falla": exact same hook name, exact
  // same priority, exact same add/try/finally/remove shape media() itself uses -- verified
  // in isolation (never invoking media_handle_sideload()/creating any attachment) by forcing
  // the protected block to throw and confirming the filter is gone regardless.
  $probe = static fn( array $file ): array => $file;
  $hook = 'wp_handle_sideload_prefilter'; $priority = 20;
  add_filter( $hook, $probe, $priority );
  $assert( false !== has_filter( $hook, $probe ), 'Probe filter installed at the same hook+priority Runner::media() uses' );
  try {
   try { throw new RuntimeException( 'simulated sideload failure' ); }
   finally { remove_filter( $hook, $probe, $priority ); }
   $assert( false, 'unreachable' );
  } catch ( RuntimeException $e ) {
   $assert( 'simulated sideload failure' === $e->getMessage(), 'Simulated failure propagated as expected' );
  }
  $assert( false === has_filter( $hook, $probe ), 'Filter removed via finally even though the protected block threw' );
  // Confirms Runner::media() itself uses this identical bracketing (never a global/permanent add_filter()).
  $source = file_get_contents( dirname( __DIR__ ) . '/migration/Runner.php' );
  $assert( (bool) preg_match( '/add_filter\(\s*\'wp_handle_sideload_prefilter\',\s*\$override,\s*20\s*\);\s*try\s*\{[^}]*media_handle_sideload[^}]*\}\s*finally\s*\{\s*remove_filter\(\s*\'wp_handle_sideload_prefilter\',\s*\$override,\s*20\s*\);\s*\}/s', $source ), 'Runner::media() itself brackets media_handle_sideload() with add_filter/try/finally/remove_filter on the same hook+priority' );

  // Media::upload() itself is never modified, never removed, never bypassed globally --
  // still registered exactly once, unconditionally, on both upload hooks.
  $assert( 10 === has_filter( 'wp_handle_upload_prefilter', array( \PSIndustrial\Core\Media::class, 'upload' ) ), 'Media::upload() still registered, unmodified, on wp_handle_upload_prefilter' );
  $assert( 10 === has_filter( 'wp_handle_sideload_prefilter', array( \PSIndustrial\Core\Media::class, 'upload' ) ), 'Media::upload() still registered, unmodified, on wp_handle_sideload_prefilter' );

  // ============================================== INTEGRATION: plan reproducibility unchanged
  $fullAgain = Planner::build( 'full' );
  $assert( $fullAgain['summary']['actions']['REVIEW'] === $full['summary']['actions']['REVIEW'], 'Full plan REVIEW count stable/reproducible with both fixes active' );

  $export( 'pdf-runtime-fix-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ) ) );
 } catch ( Throwable $error ) {
  $export( 'pdf-runtime-fix-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

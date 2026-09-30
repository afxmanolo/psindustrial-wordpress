<?php
/** Real evidence, pure negative cases, and a disposable native metadata write. Never repairs catalogue posts. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once dirname( __DIR__ ) . '/migration/PartialPdfRepair.php';
use PSIndustrial\Core\Migration\{Storage,Planner,PartialPdfRepair,Runner,Sources};
wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();
$checks = array(); $testId = 0;
$assert = static function( $ok, $label ) use ( &$checks ) { $checks[] = array( 'test' => $label, 'passed' => (bool) $ok ); if ( ! $ok ) { throw new RuntimeException( $label ); } };
try {
 $plan = Planner::build( 'full' );
 $assert( Sources::is_ui_asset( 'asset:images/verficha.png' ) && ! Sources::is_ui_asset( 'asset:images/ficha-producto.png' ), 'UI exclusion requires the exact proven path and hash, not a name pattern' );
 foreach ( $plan['entries'] as $item ) {
  if ( in_array( $item['target_type'], array( 'psi_producto','page' ), true ) ) { $assert( ! in_array( 'asset:images/verficha.png', array_merge( $item['data']['images'] ?? array(), $item['data']['pdfs'] ?? array() ), true ), 'UI button absent from planned editorial relationships: ' . $item['entity_key'] ); }
 }
 foreach ( PartialPdfRepair::IDS as $n => $id ) {
  $f = PartialPdfRepair::evidence( 'sql:productos:' . $n, $plan );
  $assert( in_array( PartialPdfRepair::eligibility( $f ), array( 'REPAIRABLE','UNCHANGED' ), true ), 'Exact eligible or completed identity ' . $n );
 }
 $f = PartialPdfRepair::evidence( 'sql:productos:5', $plan );
 // After completion use preserved pre-write evidence to exercise exactly the same partial state.
 if ( 'UNCHANGED' === PartialPdfRepair::eligibility( $f ) ) { $f = Storage::read( 'partial-pdf-before-1360.json' ); }
 $assert( 'REPAIRABLE' === PartialPdfRepair::eligibility( $f ), 'Own partial + exact PDF-B is repairable' );
 foreach ( array(
  'other run' => static function( &$v ) { $v['ledger']['run_id'] = 'another-run'; },
  'wrong wp_id' => static function( &$v ) { ++$v['id']; },
  'changed source' => static function( &$v ) { $v['entry']['source_hash'] = str_repeat( '0', 64 ); },
  'wrong attachment' => static function( &$v ) { ++$v['pdf_checks'][0]['id']; },
  'wrong PDF hash' => static function( &$v ) { $v['pdf_checks'][0]['hash_matches'] = false; },
  'missing approval' => static function( &$v ) { $v['pdf_checks'][0]['approved'] = false; },
  'human edit' => static function( &$v ) { $v['snapshot']['post_content'] .= 'human edit'; },
  'replaced object' => static function( &$v ) { $v['identity']['created_by_run'] = 'other'; },
  'REVIEW' => static function( &$v ) { $v['entry']['action'] = 'REVIEW'; },
  'SKIP' => static function( &$v ) { $v['entry']['action'] = 'SKIP'; },
  'editorial conflict' => static function( &$v ) { $v['key'] = 'sql:productos:1'; $v['expected_id'] = 0; },
 ) as $label => $change ) { $v = $f; $change( $v ); $assert( 'REPAIRABLE' !== PartialPdfRepair::eligibility( $v ), 'Reject ' . $label ); }
 $v = $f; $v['ledger']['status'] = 'APPLIED'; $v['prediction'] = 'UNCHANGED'; $v['actual_pdfs'] = $v['expected_pdfs'];
 $assert( 'UNCHANGED' === PartialPdfRepair::eligibility( $v ), 'Complete product is a no-op' );
 $assert( 'UNCHANGED' === PartialPdfRepair::eligibility( $v ), 'Second attempt remains a no-op' );
 $testId = wp_insert_post( array( 'post_type' => 'psi_producto', 'post_status' => 'draft', 'post_title' => 'TEST partial PDF metadata persistence' ), true );
 if ( is_wp_error( $testId ) ) { throw new RuntimeException( 'TEST_POST_FAILED' ); }
 $e = $f['entry'];
 Runner::write_approved_datasheets( $e, $testId, $f['expected_pdfs'], $f['paths'] );
 $assert( get_post_meta( $testId, '_psi_datasheets', true ) === $f['expected_pdfs'], 'Native WordPress metadata physically persisted; not merely true from prefilter' );
 Runner::write_approved_datasheets( $e, $testId, $f['expected_pdfs'], $f['paths'] );
 $assert( 1 === count( get_post_meta( $testId, '_psi_datasheets' ) ), 'Second write creates no duplicate metadata' );
 $assert( false === update_post_meta( $testId, '_psi_datasheets', array( array( 'attachment_id' => 99999999, 'label' => 'invalid', 'language' => '' ) ) ), 'Invalid attachment rejected after scoped filter removed' );
 $assert( get_post_meta( $testId, '_psi_datasheets', true ) === $f['expected_pdfs'], 'Rejected write preserves real PDF relation' );
 $result = array( 'passed' => true, 'checks' => $checks );
} catch ( Throwable $ex ) { $result = array( 'passed' => false, 'checks' => $checks, 'error' => $ex->getMessage() ); }
finally { if ( $testId && ! is_wp_error( $testId ) ) { wp_delete_post( $testId, true ); } }
file_put_contents( Storage::project() . '/docs/implementation/importer-reports/partial-pdf-repair-tests.json', wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" );
echo wp_json_encode( array( 'passed' => $result['passed'], 'checks' => count( $checks ), 'error' => $result['error'] ?? null ) );
exit( $result['passed'] ? 0 : 1 );

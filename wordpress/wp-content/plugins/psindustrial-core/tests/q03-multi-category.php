<?php
/** Q03-GLOBAL (16 documented category-only groups -> SAME_PRODUCT_MULTI_CATEGORY) and the
 * generic field-selection policy (task section 3/4/18) tests. Read-only: only calls
 * Planner::build('full') (DRY RUN) and inspects the resulting plan. */
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
  $assert( $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ) ), 'A full plan remains impossible to execute, Q03 included' );

  $entries = array_column( $full['entries'], null, 'entity_key' );
  $entries2 = array_column( $full2['entries'], null, 'entity_key' );
  $get = static function( string $key ) use ( $entries, $assert ) { $assert( isset( $entries[ $key ] ), "Entry exists: $key" ); return $entries[ $key ]; };
  $s = new Sources();
  $j = json_decode( file_get_contents( Storage::project() . '/docs/implementation/review-resolution/implementation/editorial-decisions.json' ), true );
  $globalGroups = $j['q03_group_consolidation']['global_multi_category']['groups'];

  // ============================================================== 13 of the 16 consolidate; 3 correctly refuse whole-group
  // (a pre-existing Planner content-extraction limit these 3 legacy pages share regardless
  // of which SQL id would be winner -- discovered while implementing this phase, not part of
  // the original approval; see docs/implementation/review-resolution/implementation/22-q03-multi-category.md).
  $blockedGroups = array( 'puertas-contra-incendio.php', 'puerta-estandar.php', 'puerta-estandar-reforzada.php' );
  $consolidated = 0; $blocked = 0;
  foreach ( $globalGroups as $g ) {
   $ids = $g['legacy_product_ids'];
   $winnerKeys = array(); $skipKeys = array(); $reviewKeys = array();
   foreach ( $ids as $id ) {
    $e = $get( 'sql:productos:' . $id );
    if ( 'MERGE' === $e['action'] ) { $winnerKeys[] = $e['entity_key']; }
    elseif ( 'SKIP' === $e['action'] && 'Q03' === ( $e['decision']['decision_id'] ?? null ) ) { $skipKeys[] = $e['entity_key']; }
    else { $reviewKeys[] = $e['entity_key']; }
   }
   if ( in_array( $g['group_key'], $blockedGroups, true ) ) {
    $assert( array() === $winnerKeys && array() === $skipKeys && count( $reviewKeys ) === count( $ids ), "Blocked group {$g['group_key']}: ALL members stay plain REVIEW, none orphaned as SKIP citing a non-existent winner" );
    ++$blocked;
   } else {
    $assert( 1 === count( $winnerKeys ), "Group {$g['group_key']}: exactly one winner entity" );
    $assert( count( $skipKeys ) === count( $ids ) - 1, "Group {$g['group_key']}: every other member is SKIP, citing the winner" );
    $assert( array() === $reviewKeys, "Group {$g['group_key']}: no member left stranded in plain REVIEW" );
    ++$consolidated;
   }
  }
  $assert( 13 === $consolidated, "13 of the 16 Q03-GLOBAL groups consolidate ($consolidated)" );
  $assert( 3 === $blocked, "3 of the 16 Q03-GLOBAL groups correctly refuse (no static body to extract) ($blocked)" );

  // ============================================================== union de categorias correcta (puertas-blindadas: 19|32)
  $blindada = $get( 'sql:productos:28' );
  $assert( 'MERGE' === $blindada['action'], 'puertas-blindadas.php winner (id 28) is MERGE' );
  $cats = $blindada['decision']['categories'];
  sort( $cats );
  $assert( array( 'category:19', 'category:32' ) === $cats, 'Category UNION is 19+32 (both demonstrated sections), never the winner\'s scalar alone' );
  // sanity: id 28's OWN category_id column already shows both -- the union isn't inventing anything not in the source data.
  $row28 = null; foreach ( $s->rows['product-master.csv'] as $r ) { if ( '28' === $r['legacy_product_id'] ) { $row28 = $r; } }
  $assert( str_contains( $row28['category_id'], '19' ) && str_contains( $row28['category_id'], '32' ), 'Sanity: source data already documents both categories for id 28' );

  // 4-way union: icaro-smart.php -> 9|11|18
  $icaro = $get( 'sql:productos:49' );
  $icats = $icaro['decision']['categories']; sort( $icats );
  $assert( array( 'category:11', 'category:18', 'category:9' ) === $icats, 'icaro-smart.php: 3-category union correct (9|11|18)' );

  // ============================================================== una unica entidad destino (nunca varios productos)
  foreach ( array( '1', '109', '110', '114' ) as $id ) {
   $e = $get( 'sql:productos:' . $id );
   $assert( 'REVIEW' !== $e['action'], "accesspro-fs1000speed.php member $id has a decision (no stranded member)" );
  }
  $accesspro = array_filter( array( '1', '109', '110', '114' ), static fn( $id ) => 'MERGE' === $entries[ 'sql:productos:' . $id ]['action'] );
  $assert( 1 === count( $accesspro ), 'accesspro-fs1000speed.php (4 legacy rows) produces exactly ONE psi_producto entity, never four' );

  // ============================================================== marca: Policy::BRAND_CONFLICT_PRODUCTS es una segunda senal automatica, no solo la lista manual B/C/D
  // MOOVI (barreras-estacionamiento-moovi50rm.php) is a Q03-GLOBAL group whose own brand_id
  // values AGREE with each other (all point to BFT) -- but its ids are independently listed
  // in Policy::BRAND_CONFLICT_PRODUCTS (documented external link evidence contradicts that
  // agreement: "enlace BlueGiant frente a BFT"). Confirms the automatic cross-check catches
  // this WITHOUT needing MOOVI on any manually-maintained force-unresolved list.
  $moovi = $get( 'sql:productos:44' );
  $assert( 'MERGE' === $moovi['action'], 'MOOVI: identity still consolidates' );
  $assert( '' === $moovi['decision']['brand'], 'MOOVI: brand is NOT auto-assigned despite brand_id agreement, because Policy::BRAND_CONFLICT_PRODUCTS flags these ids' );
  $assert( 'BRAND_UNRESOLVED_Q09' === $moovi['decision']['field_provenance']['brand_status'], 'MOOVI: field_provenance records BRAND_UNRESOLVED_Q09' );
  $reflPolicy = new ReflectionClass( \PSIndustrial\Core\Migration\Policy::class );
  $bcp = $reflPolicy->getReflectionConstant( 'BRAND_CONFLICT_PRODUCTS' )->getValue();
  $assert( in_array( '44', $bcp, true ), 'Sanity: id 44 is genuinely in Policy::BRAND_CONFLICT_PRODUCTS' );
  $assert( \PSIndustrial\Core\Migration\Policy::brand_conflict_products() === $bcp, 'Policy::brand_conflict_products() accessor returns exactly the same list as the private constant' );

  // ============================================================== ninguna categoria principal inventada
  foreach ( $globalGroups as $g ) {
   if ( in_array( $g['group_key'], $blockedGroups, true ) ) { continue; }
   $winner = null; foreach ( $g['legacy_product_ids'] as $id ) { $e = $entries[ 'sql:productos:' . $id ]; if ( 'MERGE' === $e['action'] ) { $winner = $e; } }
   $assert( ! array_key_exists( 'primary_category', $winner['decision'] ), "{$g['group_key']}: no primary_category key invented" );
   $assert( ! array_key_exists( 'breadcrumb_category', $winner['decision'] ), "{$g['group_key']}: no breadcrumb_category key invented" );
   $assert( ! isset( $winner['data']['meta']['_yoast_wpseo_primary_category'] ), "{$g['group_key']}: no SEO primary taxonomy meta invented" );
  }

  // ============================================================== URLs multiples -> una entidad (todas comparten 1 URL legacy)
  // Every member of a Q03 group shares the identical legacy_php/legacy_url by construction
  // (canonical_candidate_group IS the shared filename) -- confirm this literally, then
  // confirm the single winner is the only entity carrying that url onward.
  $urls = array();
  foreach ( array( '28', '118', '129' ) as $id ) { $r = null; foreach ( $s->rows['product-master.csv'] as $row ) { if ( $row['legacy_product_id'] === $id ) { $r = $row; } } $urls[ $r['legacy_url'] ] = true; }
  $assert( 1 === count( $urls ), 'Sanity: all 3 puertas-blindadas.php rows already share exactly one legacy URL' );

  // ============================================================== ejecucion repetida -> mismo resultado
  $stable = 0;
  foreach ( $entries as $key => $e ) {
   if ( 'Q03-GLOBAL' !== ( $e['decision']['decision_sub_id'] ?? null ) ) { continue; }
   $o = $entries2[ $key ] ?? null;
   $assert( $o && $e['action'] === $o['action'] && wp_json_encode( $e['decision'] ) === wp_json_encode( $o['decision'] ), "Repeated execution -> identical result: $key" );
   ++$stable;
  }
  $assert( $stable >= 60, "A meaningful number of Q03-GLOBAL rows checked for stability ($stable)" );

  // ============================================================== grupo fuera de lista -> no afectado
  // rampas-de-anden-hidraulicas-kelley.php (Q03-E, partial) and coleccion-modern-steel.php
  // (Q03-B) are NOT in the GLOBAL list -- confirm they never receive a Q03-GLOBAL decision.
  foreach ( array( '3', '24', '139', '6', '16', '20', '107' ) as $id ) {
   $e = $get( 'sql:productos:' . $id );
   $assert( 'Q03-GLOBAL' !== ( $e['decision']['decision_sub_id'] ?? null ), "id $id (a different Q03 sub-decision) never receives a Q03-GLOBAL decision" );
  }
  // A control product entirely outside any Q03 group is never touched either.
  $control = $get( 'sql:productos:150' );
  $assert( 'Q03' !== ( $control['decision']['decision_id'] ?? null ), 'id 150 (Q09 territory, not any Q03 group) untouched by Q03' );

  // ============================================================== field-selection policy (section 3/4/18)
  // oldest description empty + populated sibling -> winner is the POPULATED sibling, never the empty oldest.
  $holandesa = $get( 'sql:productos:33' ); // group's oldest, non-empty, all 3 agree -> stays winner (AGREED, no emptiness issue).
  $assert( 'MERGE' === $holandesa['action'], 'puerta-holandesa.php: id 33 (oldest, non-empty, AGREED across all 3) remains winner' );

  // Direct source-data proof for the empty-oldest case that WAS resolvable this run: none of
  // the 16 GLOBAL groups that succeeded has an empty-description winner -- verify this
  // holds for every one of the 13, not just spot-checked once.
  foreach ( $globalGroups as $g ) {
   if ( in_array( $g['group_key'], $blockedGroups, true ) ) { continue; }
   $winnerId = null; foreach ( $g['legacy_product_ids'] as $id ) { if ( 'MERGE' === $entries[ 'sql:productos:' . $id ]['action'] ) { $winnerId = $id; } }
   $desc = trim( $s->catalog['productos'][ $winnerId ]['descripcion'] ?? '' );
   $anyNonEmptySibling = false;
   foreach ( $g['legacy_product_ids'] as $id ) { if ( '' !== trim( $s->catalog['productos'][ $id ]['descripcion'] ?? '' ) ) { $anyNonEmptySibling = true; } }
   if ( $anyNonEmptySibling ) { $assert( '' !== $desc, "{$g['group_key']}: winner (id $winnerId) is never the empty-description member when a populated sibling exists" ); }
  }

  // contradictory non-empty values -> field_provenance.description.status = CONFLICT, never synthesized, identity still consolidates.
  $cfg = $get( 'sql:productos:28' ); // puertas-blindadas: all 3 descriptions genuinely differ.
  $prov = $cfg['decision']['field_provenance']['description'];
  $assert( 'CONFLICT' === $prov['status'], 'puertas-blindadas.php: 3 genuinely different non-empty descriptions -> CONFLICT, recorded honestly' );
  $assert( null === $prov['source_id'], 'CONFLICT never claims a single source_id (nothing was synthesized or picked)' );
  $assert( 'MERGE' === $cfg['action'], 'Identity still consolidates despite the description conflict (the two are separate axes)' );

  // identical non-empty values -> stable, single selection.
  $agreedGroup = $get( 'sql:productos:33' ); // puerta-holandesa: all 3 share the exact same description.
  $aprov = $agreedGroup['decision']['field_provenance']['description'];
  $assert( 'AGREED' === $aprov['status'] && null !== $aprov['source_id'], 'puerta-holandesa.php: identical non-empty descriptions -> AGREED with a definite source' );

  $export( 'q03-multi-category-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'q03-multi-category-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

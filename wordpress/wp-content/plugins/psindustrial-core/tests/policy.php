<?php
/** LOW-risk policy layer tests. Read-mostly: only calls Planner::build() (DRY RUN,
 * writes nothing to WordPress content) and Policy::decisions() (pure function of the
 * versioned CSV masters). Never calls Runner::batch() with a real intent to execute.
 * Every fixture below is a REAL entity_key from the actual migration masters, the same
 * convention tests/importer.php already uses — no synthetic Sources double. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
use PSIndustrial\Core\Migration\{Storage,Sources,Policy,Planner,Runner,Admin,Identity};
(static function(): void {
 $checks = array();
 $assert = static function( bool $value, string $label ) use ( &$checks ): void { $checks[] = array( 'test' => $label, 'passed' => $value ); if ( ! $value ) { throw new RuntimeException( $label ); } };
 $throws = static function( callable $fn, string $label ) use ( $assert ): void { $caught = false; try { $fn(); } catch ( Throwable $e ) { $caught = true; } $assert( $caught, $label ); };
 $export = static function( string $name, array $data ): void { file_put_contents( Storage::project() . '/docs/implementation/importer-reports/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n" ); };
 try {
  wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID ); Storage::guard();
  $s = new Sources();
  $manual = Planner::decisions();
  $policy = Policy::decisions( $s, $manual );

  // ---------------------------------------------------------------- R-T01 (category)
  $assert( isset( $policy['category:14'] ) && 'MIGRATE' === $policy['category:14']['action'] && 'R-T01' === $policy['category:14']['rule_id'], 'R-T01 positive: confirmed-parent category migrates' );
  foreach ( array( 'category:25', 'category:26', 'category:33' ) as $key ) { $assert( ! isset( $policy[ $key ] ), 'R-T01 negative: conflict-registry category ' . $key . ' stays without a policy decision' ); }
  $assert( isset( $policy['category:4'] ) && '' === $policy['category:4']['parent'], 'R-T01 edge case: root category (empty parent_legacy_id) gets no parent dependency' );
  foreach ( $policy as $key => $d ) { if ( 'R-T01' === ( $d['rule_id'] ?? '' ) ) { $assert( ! isset( $d['categories'] ) && ! isset( $d['brand'] ), 'R-T01 never carries a product/brand assignment field: ' . $key ); } }

  // ---------------------------------------------------------------- R-T03 (brand)
  $assert( isset( $policy['brand:7'] ) && 'MIGRATE' === $policy['brand:7']['action'] && 'R-T03' === $policy['brand:7']['rule_id'], 'R-T03 positive: brand with page and logo evidence migrates' );
  foreach ( $policy as $key => $d ) { if ( 'R-T03' === ( $d['rule_id'] ?? '' ) ) { $assert( ! array_key_exists( 'categories', $d ), 'R-T03 never assigns products: ' . $key ); } }

  // ---------------------------------------------------------------- R-P01 (product)
  $assert( isset( $policy['sql:productos:34'] ) && 'MIGRATE' === $policy['sql:productos:34']['action']
   && array( 'category:35' ) === $policy['sql:productos:34']['categories'] && 'brand:6' === $policy['sql:productos:34']['brand'], 'R-P01 positive: brand_confidence=CONFIRMED assigns the brand' );
  $assert( isset( $policy['sql:productos:74'] ) && '' === $policy['sql:productos:74']['brand'], 'R-P01 positive: brand_confidence below CONFIRMED never assigns a brand (D03 stays open)' );
  foreach ( array( '150', '6', '16', '20', '107', '14', '57', '43', '81', '44', '123', '125', '135', '139', '3' ) as $id ) {
   $assert( ! isset( $policy[ 'sql:productos:' . $id ] ), 'R-P01 negative: D03/D06 conflict-registry product ' . $id . ' stays without a policy decision' );
  }
  foreach ( range( 151, 165 ) as $id ) { $assert( ! isset( $policy[ 'sql:productos:' . $id ] ), 'R-P01 negative: empty/test record ' . $id . ' never migrates (NAME_REQUIRED would reject it anyway)' ); }
  foreach ( $policy as $key => $d ) {
   if ( ! str_starts_with( $key, 'sql:productos:' ) ) { continue; }
   $assert( isset( $policy[ $d['categories'][0] ] ) && 'MIGRATE' === $policy[ $d['categories'][0] ]['action'], 'R-P01 never assigns a category policy itself has not approved: ' . $key );
   if ( '' !== $d['brand'] ) {
    $legacyId = substr( $key, strlen( 'sql:productos:' ) );
    $row = array_values( array_filter( $s->rows['product-master.csv'], static fn( $r ) => $r['legacy_product_id'] === $legacyId ) )[0] ?? null;
    $assert( $row && 'CONFIRMED' === $row['brand_confidence'], 'R-P01 never assigns a brand below CONFIRMED evidence: ' . $key );
   }
  }

  // ---------------------------------------------------------------- R-G02-LOW (page)
  $assert( isset( $policy['php:contacto.php'] ) && 'MIGRATE' === $policy['php:contacto.php']['action'] && 'R-G02-LOW' === $policy['php:contacto.php']['rule_id'], 'R-G02-LOW positive: contact page migrates' );
  $assert( ! isset( $policy['php:cortinas-enrollables-de-aluminio.php'] ), 'R-G02-LOW negative: SEO_LANDING (MEDIUM risk) never gets a LOW decision' );
  $assert( ! isset( $policy['php:1500-revolving-door.php'] ), 'R-G02-LOW negative: PRODUCT_PAGE never gets a page policy decision (owned by its product/static entity instead)' );

  // ---------------------------------------------------------------- R-M01 / R-M04 / R-M05 (media, owner rules)
  $assert( isset( $policy['asset:system/files/images/productos/0026e2b69ea6b2f5307ae424596371c7610f2fd8'] )
   && 'R-M01' === $policy['asset:system/files/images/productos/0026e2b69ea6b2f5307ae424596371c7610f2fd8']['rule_id'], 'R-M01 positive: image owned by an R-P01-approved product migrates' );
  $assert( isset( $policy['asset:images/rytec.png'] ) && 'R-M05' === $policy['asset:images/rytec.png']['rule_id'], 'R-M05 positive: brand logo of an approved brand migrates' );
  $assert( isset( $policy['asset:images/contact.jpg'] ) && 'R-M04' === $policy['asset:images/contact.jpg']['rule_id'], 'R-M04 positive: media referenced only by an approved institutional/contact page migrates' );

  // ---------------------------------------------------------------- R-M02 (derivative) / R-M03 (infrastructure)
  $assert( isset( $policy['asset:multimedia/imagenes/categorias/0068b4920fb7283782a51c12e22e83686e6bd310.png'] )
   && 'SKIP' === $policy['asset:multimedia/imagenes/categorias/0068b4920fb7283782a51c12e22e83686e6bd310.png']['action']
   && 'R-M02' === $policy['asset:multimedia/imagenes/categorias/0068b4920fb7283782a51c12e22e83686e6bd310.png']['rule_id'], 'R-M02 positive: unused derivative with a registered original SKIPs' );
  $assert( isset( $policy['asset:system/backoffice/calendar/skins/aqua/active-bg.gif'] ) && 'R-M03' === $policy['asset:system/backoffice/calendar/skins/aqua/active-bg.gif']['rule_id'], 'R-M03 positive: system/backoffice asset SKIPs' );
  $assert( isset( $policy['asset:fonts/bootstrap-icons.woff'] ) && 'R-M03' === $policy['asset:fonts/bootstrap-icons.woff']['rule_id'], 'R-M03 positive: frontend icon font SKIPs' );
  // Every original a derivative points to must stay eligible for its own separate decision — R-M02 never fires on a row that IS an original (empty original_ids).
  foreach ( $s->rows['media-master.csv'] as $row ) {
   $key = 'asset:' . $row['legacy_path'];
   if ( ( $policy[ $key ]['rule_id'] ?? '' ) === 'R-M02' ) { $assert( '' !== trim( $row['original_ids'] ?? '' ), 'R-M02 never SKIPs a row that has no registered original (would be discarding an original): ' . $key ); }
  }
  // R-M03 is an exact directory-prefix match; must never fire on a path merely containing "system" or "fonts" as a substring (false-positive guard against partial-word matches).
  foreach ( $policy as $key => $d ) {
   if ( 'R-M03' !== ( $d['rule_id'] ?? '' ) ) { continue; }
   $path = substr( $key, 6 );
   $assert(
    str_starts_with( $path, 'system/backoffice/' ) || str_starts_with( $path, 'fonts/' ) || ( str_starts_with( $path, 'system/' ) && ! str_starts_with( $path, 'system/files/' ) ),
    'R-M03 only fires on an exact infrastructure directory prefix: ' . $key
   );
  }
  $assert( ! isset( $policy['asset:system/files/images/categorias/0730f48454726934e392a937efc6b2c9e432e7c6'] ), 'Must-stay-REVIEW: unowned system/files/ asset (real content store, no owner, not derived) gets no policy decision' );

  // ---------------------------------------------------------------- Cross-cutting safety
  $assert( ! isset( $policy['asset:system/files/images/productos/9856266836c012c90673224e9379a1a7f4b32389'] ), 'Regression: a path declared as binary_aliases of a manual decision never receives its own independent policy decision (SOURCE_KEY_HAS_TWO_OWNERS guard)' );
  foreach ( array_keys( $policy ) as $key ) { $assert( ! str_starts_with( $key, 'static:' ), 'Never migrate a static_product automatically: ' . $key ); }
  foreach ( $policy as $key => $d ) { $assert( in_array( $d['action'], array( 'MIGRATE', 'SKIP' ), true ), 'Policy only ever proposes MIGRATE or SKIP, never MERGE/REVIEW: ' . $key ); }
  foreach ( $policy as $key => $d ) { $assert( 'low_rule' === $d['origin'] && isset( $d['rule_id'], $d['reason_code'], $d['evidence'] ), 'Every policy decision carries full provenance: ' . $key ); }
  $assert( count( $policy ) === count( array_unique( array_keys( $policy ) ) ), 'No duplicate entity_key across policy decisions' );
  // No two DIFFERENT policy-approved media entities can claim the same file_id-derived source_key (the exact class of bug the alias fix above addresses).
  $claimed = array();
  foreach ( $policy as $key => $d ) {
   if ( 'MIGRATE' !== $d['action'] || ! str_starts_with( $key, 'asset:' ) ) { continue; }
   $row = array_values( array_filter( $s->rows['media-master.csv'], static fn( $r ) => 'asset:' . $r['legacy_path'] === $key ) )[0] ?? null;
   foreach ( Sources::parts( $row['file_ids'] ?? '' ) as $fid ) {
    $assert( ! isset( $claimed[ $fid ] ), 'file_id ' . $fid . ' claimed by two different policy media entities (would duplicate an attachment): ' . $key );
    $claimed[ $fid ] = $key;
   }
  }

  // ---------------------------------------------------------------- Reproducibility
  $again = Policy::decisions( $s, $manual );
  $assert( Storage::hash( $policy ) === Storage::hash( $again ), 'Policy::decisions() is a pure, reproducible function of the same sources' );

  // ================================================================== INTEGRATION
  // Subset scope is untouched by the policy layer: identical to tests/importer.php's own baseline.
  $before = array( 'posts' => 0, 'terms' => 0 ); global $wpdb; $before = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $subset = Planner::build( 'subset' );
  $after = array( 'posts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" ) );
  $assert( $before === $after, 'Subset DRY RUN still changes nothing' );
  $assert( 17 === count( $subset['entries'] ) && 2 === $subset['summary']['actions']['REVIEW'], 'Subset scope entry count is byte-identical to the pre-policy baseline: the policy fallback never applies to scope=subset' );
  foreach ( $subset['entries'] as $e ) { $assert( ! isset( $e['policy_rule_id'] ), 'No subset entry is ever policy-attributed: ' . $e['entity_key'] ); }

  // Full scope: the actual FULL DRY RUN with the policy layer active.
  $full = Planner::build( 'full' );
  $assert( 'DRY_RUN' === $full['mode'] && 'VALIDATED' === $full['status'], 'Full plan remains DRY_RUN/VALIDATED, never executed' );
  $assert( 2399 === count( $full['entries'] ), 'Full plan still analyzes exactly 2,399 source rows' );
  foreach ( $subset['entries'] as $e ) { if ( in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) { continue; } $assert( Identity::find( $e ) > 0 && 'UNCHANGED' === Identity::prediction( $e ), 'Subset identity still present and unchanged: ' . $e['entity_key'] ); }
  $assert( $full['summary']['actions']['REVIEW'] < 1949, 'Policy measurably reduces REVIEW below the pre-policy baseline of 1,949' );
  $assert( ( $full['summary']['actions']['SKIP'] ?? 0 ) >= 435 + 846, 'SKIP grows by at least the R-M02+R-M03 media count on top of the 435 pre-existing internal SKIPs' );
  foreach ( $full['entries'] as $e ) {
   if ( empty( $e['policy_rule_id'] ) ) { continue; }
   $assert( isset( Policy::RULES[ $e['policy_rule_id'] ] ), 'policy_rule_id is always a real, registered rule: ' . $e['entity_key'] );
   $assert( 'LOW' === $e['policy_risk'] && ! empty( $e['policy_reason_code'] ) && ! empty( $e['policy_evidence'] ), 'Explainability fields are always complete together: ' . $e['entity_key'] );
   if ( in_array( $e['action'], array( 'MIGRATE', 'CREATE_FROM_STATIC' ), true ) ) { $assert( str_starts_with( $e['approval_ref'], 'Política LOW automatizada' ), 'A surviving policy decision is traceable in approval_ref: ' . $e['entity_key'] ); }
  }
  $manualEntry = array_values( array_filter( $full['entries'], static fn( $e ) => 'sql:productos:63' === $e['entity_key'] ) )[0];
  $assert( ! isset( $manualEntry['policy_rule_id'] ) && str_starts_with( $manualEntry['approval_ref'], 'Prompt 6' ), 'A manual/subset decision is never mistaken for a policy one, even when both would agree' );
  foreach ( $full['entries'] as $e ) { if ( 'MERGE' === $e['action'] ) { $assert( ! empty( $e['decision']['editorial_approval'] ) && ! empty( $e['decision']['field_winners'] ), 'Every MERGE has explicit editorial authority: ' . $e['entity_key'] ); } }

  // A full-scope plan built with the policy layer active still can never be executed.
  $throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ), 'A full plan (policy-enriched or not) remains impossible to execute' );

  $export( 'policy-tests.json', array( 'passed' => true, 'checks' => $checks ) );
  $export( 'full-dry-run-after-low-rules.json', Admin::report( $full ) );
  echo wp_json_encode( array( 'passed' => true, 'checks' => count( $checks ), 'full_run_id' => $full['run_id'], 'review_before' => 1949, 'review_after' => $full['summary']['actions']['REVIEW'] ) );
 } catch ( Throwable $error ) {
  $export( 'policy-tests.json', array( 'passed' => false, 'checks' => $checks, 'error' => $error->getMessage() ) ); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 );
 }
})();

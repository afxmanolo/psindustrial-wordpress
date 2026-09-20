<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

final class Planner {
 public const VERSION = '1.0.0';
 public static function decisions(): array {
  $path = Sources::safe( Storage::project(), 'docs/implementation/importer-reports/subset-decisions.json' );
  $data = json_decode( file_get_contents( $path ), true, 64, JSON_THROW_ON_ERROR );
  if ( 'LOCAL_DRAFT_REHEARSAL_ONLY' !== ( $data['scope'] ?? '' ) ) { throw new \RuntimeException( 'INVALID_DECISION_SCOPE' ); }
  return $data;
 }
 public static function build( string $scope = 'full' ): array {
  Storage::guard();
  if ( ! in_array( $scope, array( 'full', 'subset' ), true ) ) { throw new \RuntimeException( 'INVALID_SCOPE' ); }
  $s = new Sources(); $d = self::decisions(); $entries = array();
  // LOW-risk policy layer (see migration/Policy.php). Scoped to 'full' only: it never
  // participates in the 'subset' filter below, so it cannot change subset execution,
  // batching, or which entries a rehearsal run contains. A 'full' plan built with it
  // still cannot be executed — Runner::batch() rejects any scope other than 'subset'.
  $policy = 'full' === $scope ? Policy::decisions( $s, $d ) : array();
  // Explicit human editorial decisions (Q02, Q06 -- see migration/EditorialDecisions.php).
  // Same 'full'-only scoping guarantee as Policy: never seen by 'subset' execution.
  // Checked BEFORE the LOW-risk policy layer so an explicit human decision always takes
  // precedence over a generic inference, though in practice the two never overlap --
  // Policy already excludes every multi-record canonical group and every empty/test id.
  $editorial = 'full' === $scope ? EditorialDecisions::decisions( $s ) : array();
  $add = static function( string $key, string $type, array $row, string $reason ) use ( &$entries, $d, $editorial, $policy, $scope, $s ): void {
   $decision = $d['entities'][ $key ] ?? $editorial[ $key ] ?? $policy[ $key ] ?? null;
   if ( 'subset' === $scope && ! $decision && ! in_array( $key, $d['review_examples'], true ) ) { return; }
   $file = $row['legacy_php'] ?? $row['legacy_page'] ?? $row['legacy_file'] ?? $row['legacy_path'] ?? '';
   $e = array( 'manifest_version' => 1, 'entity_key' => $key, 'source_key' => $key, 'source_namespace' => explode( ':', $key )[0], 'source_type' => $type, 'content_type' => $type,
    'legacy_id' => $row['legacy_product_id'] ?? $row['legacy_id'] ?? '', 'legacy_file' => $file, 'legacy_url' => $row['legacy_url'] ?? '', 'source_keys' => array( $key ),
    'action' => 'REVIEW', 'status' => 'PLANNED', 'wordpress_id' => 0, 'confidence' => $row['php_match_confidence'] ?? $row['relationship_confidence'] ?? $row['confidence'] ?? 'UNKNOWN',
    'notes' => $reason, 'row' => $row, 'data' => array(), 'dependencies' => array(), 'approval_ref' => '', 'field_ownership' => 'importer-controlled draft fields; manual edits block whole object', 'warnings' => array() );
   if ( 'LEGACY_INTERNAL' === ( $row['classification'] ?? '' ) ) { $e['action'] = 'SKIP'; $e['notes'] = 'Código interno: no se migra como contenido; archivo y evidencias preservados.'; }
   if ( $decision ) {
    $e['approval_ref'] = 'editorial_decision' === ( $decision['origin'] ?? '' )
     ? 'Decisión editorial humana (' . ( $decision['decision_id'] ?? '' ) . '): migration/EditorialDecisions.php; docs/implementation/review-resolution/implementation/editorial-decisions.json:' . $key
     : ( 'low_rule' === ( $decision['origin'] ?? '' )
     ? 'Política LOW automatizada (Prompt 7): migration/Policy.php:' . ( $decision['rule_id'] ?? '' ) . ':' . $key
     : 'Prompt 6: ensayo privado; subset-decisions.json:' . $key );
    $e['decision'] = $decision; $e['action'] = $decision['action'];
    if ( ! in_array( $e['action'], array( 'MIGRATE', 'CREATE_FROM_STATIC', 'MERGE', 'SKIP', 'REVIEW' ), true ) ) { throw new \RuntimeException( 'INVALID_DECISION_ACTION' ); }
    if ( 'MERGE' === $e['action'] && ( empty( $decision['source_keys'] ) || empty( $decision['field_winners'] ) || empty( $decision['editorial_approval'] ) ) ) { $e['action'] = 'REVIEW'; $e['notes'] = 'MERGE requiere autorización editorial y ganador por campo.'; }
    if ( 'MERGE' === $e['action'] ) {
     // Mixed-content field merges need a reviewed normalized payload, never an implicit concatenation.
     foreach ( array( 'name','content','categories','brand','images','pdfs','videos' ) as $field ) {
      if ( ( $decision['field_winners'][ $field ] ?? '' ) !== $key ) { $e['action'] = 'REVIEW'; $e['notes'] = 'MERGE mixto requiere paquete editorial normalizado; no se elige ganador automáticamente.'; break; }
     }
    }
    if ( in_array( $e['action'], array( 'MIGRATE', 'CREATE_FROM_STATIC', 'MERGE' ), true ) ) {
     try {
      if ( 'media' === $type ) {
       $asset = $s->asset( $file );
       // The integrity check below always compares against the ORIGINAL legacy file's
       // hash ($asset['sha256'], from Sources::asset() on the /legacy/public path) —
       // this never changes meaning, approved or not. What actually gets staged/imported
       // is $asset['staged_path']/['staged_sha256'], which PdfApprovals may have pointed
       // at a sanitized substitute (Group A) while this check still confirms the legacy
       // original has not drifted from what media-master.csv recorded.
       if ( ! $asset['valid'] || ! hash_equals( $row['sha256'], $asset['sha256'] ) ) { throw new \RuntimeException( 'MEDIA_VALIDATION_OR_HASH' ); }
       $e['data'] = $asset + array( 'path' => $file, 'name' => $row['filename'] );
       $e['data']['sha256'] = $asset['staged_sha256'];
       $e['data']['package_asset'] = Storage::stage_asset( $asset['staged_path'], $asset['staged_sha256'] );
       if ( $asset['pdf_approval'] ) {
        $e['pdf_approval_type'] = $asset['pdf_approval']['type'];
        $e['pdf_approval_reason'] = $asset['pdf_approval']['reason'];
        if ( 'sanitized' === $asset['pdf_approval']['type'] ) { $e['pdf_approval_rule_id'] = $asset['pdf_approval']['rule_id']; $e['pdf_approval_sanitized_sha256'] = $asset['pdf_approval']['sha256']; }
        else { $e['pdf_approval_classification'] = $asset['pdf_approval']['classification']; $e['pdf_approval_reason_code'] = $asset['pdf_approval']['reason_code']; }
       }
       $e['source_keys'] = array_merge( array( $key ), array_map( static fn( $id ) => 'file:' . $id, Sources::parts( $row['file_ids'] ?? '' ) ) );
       $e['binary_aliases'] = array();
       foreach ( $decision['binary_aliases'] ?? array() as $alias ) {
        $other = $s->asset( $alias );
        if ( ! $other['valid'] || ! hash_equals( $asset['sha256'], $other['sha256'] ) ) { throw new \RuntimeException( 'BINARY_ALIAS_NOT_IDENTICAL' ); }
        $e['binary_aliases'][] = $alias; $e['source_keys'][] = 'asset:' . $alias;
        foreach ( $s->rows['media-master.csv'] as $candidate ) { if ( $candidate['legacy_path'] === $alias ) { $e['source_keys'] = array_merge( $e['source_keys'], array_map( static fn( $id ) => 'file:' . $id, Sources::parts( $candidate['file_ids'] ) ) ); } }
       }
      } else {
       $name = sanitize_text_field( $row['name'] ?? $decision['name'] ?? $row['title'] ?? '' );
       if ( '' === $name ) { throw new \RuntimeException( 'NAME_REQUIRED' ); }
       $e['data'] = array( 'name' => $name, 'slug' => sanitize_title( $decision['slug'] ?? $row['proposed_slug'] ?? $name ), 'description' => '', 'meta' => array() );
       if ( in_array( $type, array( 'product', 'page', 'static_product' ), true ) ) {
        $extracted = $s->content( $file ); $e['data']['content'] = $extracted['body']; $e['source_file_hash'] = $extracted['sha256']; $e['seo_evidence'] = $extracted['seo_evidence'];
        if ( '' === trim( wp_strip_all_tags( $extracted['body'] ) ) ) { throw new \RuntimeException( 'NO_STATIC_EDITORIAL_BODY' ); }
        $e['warnings'][] = 'Borrador: revisar extracción, títulos, tablas y enlaces antes de publicar; SEO/rutas sin activar.';
        $e['data']['categories'] = $decision['categories'] ?? array(); $e['data']['brand'] = $decision['brand'] ?? '';
        $e['data']['images'] = $decision['images'] ?? array(); $e['data']['pdfs'] = $decision['pdfs'] ?? array();
        $e['data']['videos'] = $decision['videos'] ?? array();
        if ( ! $e['data']['brand'] && 'page' !== $type ) { $e['warnings'][] = 'Sin marca: no inferir fabricante.'; }
        $e['dependencies'] = array_merge( $e['data']['categories'], $e['data']['brand'] ? array( $e['data']['brand'] ) : array(), $e['data']['images'], $e['data']['pdfs'] );
       } else {
        $e['data']['parent'] = $decision['parent'] ?? ''; $e['data']['image'] = $decision['image'] ?? '';
        $e['dependencies'] = array_values( array_filter( array( $e['data']['parent'], $e['data']['image'] ) ) );
       }
      }
      $e['notes'] = $decision['reason'];
     } catch ( \Throwable $error ) { $e['action'] = 'REVIEW'; $e['notes'] = $error->getMessage(); }
    }
   }
   // Explainability for the LOW-risk policy layer only, and only when the proposed action
   // actually survived the validation above (a policy MIGRATE that threw and fell back to
   // REVIEW above must NOT be reported as a successful LOW-risk resolution). Never touches
   // manual/subset decisions, which carry no 'origin' key.
   if ( $decision && 'low_rule' === ( $decision['origin'] ?? '' ) && $e['action'] === $decision['action'] ) {
    $e['notes'] = $decision['reason'];
    $e['policy_rule_id'] = $decision['rule_id'];
    $e['policy_reason_code'] = $decision['reason_code'];
    $e['policy_evidence'] = $decision['evidence'];
    $e['policy_risk'] = 'LOW';
   }
   if ( ! isset( $e['source_file_hash'] ) && $file && 'UNKNOWN' !== $file && 'media' !== $type ) {
    try { $e['source_file_hash'] = hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $file ) ); } catch ( \Throwable $error ) { $e['warnings'][] = $error->getMessage(); }
   }
   $catalog = match ( $type ) { 'product' => $s->catalog['productos'][ $e['legacy_id'] ] ?? array(), 'category' => $s->catalog['categorias'][ $e['legacy_id'] ] ?? array(), 'brand' => $s->catalog['marcas'][ $e['legacy_id'] ] ?? array(), default => array() };
   if ( 'category' === $type && isset( $e['data']['description'] ) ) { $e['data']['description'] = wp_kses_post( $catalog['descripcion'] ?? '' ); }
   if ( 'product' === $type ) { $e['legacy_date'] = $catalog['fecha'] ?? ''; }
   $e['source_hash'] = Storage::hash( array( self::VERSION, $row, $catalog, $e['source_file_hash'] ?? '', $e['data']['sha256'] ?? '' ) );
   $e['decision_hash'] = Storage::hash( $decision );
   if ( 'media' === $type && ! $decision && 'full' === $scope ) {
    if ( str_starts_with( $file, 'https://' ) || str_starts_with( $file, 'http://' ) ) { $e['media_validation'] = 'EXTERNAL_VIDEO_METADATA_ONLY'; }
    else {
     try {
      $audit = $s->asset( $file ); $e['media_validation'] = $audit['valid'] && hash_equals( $row['sha256'], $audit['sha256'] ) ? 'VALID_BYTES_REQUIRES_APPROVAL' : 'REVIEW_TYPE_LIMIT_OR_HASH';
     } catch ( \Throwable $error ) { $e['media_validation'] = 'REVIEW_MISSING_OR_UNSAFE_PATH'; }
    }
   }
   $e['target_type'] = match ( $type ) { 'category' => 'psi_categoria', 'brand' => 'psi_marca', 'product', 'static_product' => 'psi_producto', 'media' => 'attachment', default => 'page' };
   $e['planned_result'] = in_array( $e['action'], array( 'SKIP', 'REVIEW' ), true ) ? $e['action'] : Identity::prediction( $e );
   if ( ! in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) { $e['wordpress_id'] = Identity::find( $e ); }
   if ( 'product' === $type ) { $e['relationship_evidence'] = array( 'category_confidence' => $row['category_confidence'], 'brand_confidence' => $row['brand_confidence'], 'candidate_brand' => $row['brand_id'], 'evidence' => $row['source_of_relationship'] ); }
   $entries[ $key ] = $e;
  };
  foreach ( $s->rows['media-master.csv'] as $r ) { $add( 'asset:' . $r['legacy_path'], 'media', $r, 'Uso documental no equivale a aprobación de propósito/identidad de medios.' ); }
  foreach ( $s->rows['category-master.csv'] as $r ) { $add( 'category:' . $r['legacy_id'], 'category', $r, in_array( $r['legacy_id'], array( '25','26','33' ), true ) ? 'Jerarquía/identidad pendiente D06.' : 'Categoría candidata; requiere decisión por campo.' ); }
  foreach ( $s->rows['brand-master.csv'] as $r ) { $add( 'brand:' . $r['legacy_id'], 'brand', $r, 'Marca candidata y logo documentado; sin aprobación global de importación.' ); }
  foreach ( $s->rows['product-master.csv'] as $r ) { $add( 'sql:productos:' . $r['legacy_product_id'], 'product', $r, 'REVIEW' === $r['migration_status'] ? 'Maestro REVIEW: identidad, duplicado o excepción editorial; no fusionar ni descartar automáticamente.' : 'SHOULD_MIGRATE es candidato, no aprobación editorial.' ); }
  foreach ( $s->rows['static-product-supplement.csv'] as $r ) { $add( 'static:' . $r['legacy_php'], 'static_product', $r, 'Ficha/familia estática: decidir entidad y contenido; no convertir automáticamente a producto.' ); }
  foreach ( $s->rows['content-master.csv'] as $r ) { $add( 'php:' . $r['legacy_file'], 'page', $r, 'Clasificación ' . $r['classification'] . ': conservar evidencia/URL; definir dueño sin duplicar producto, archivo o Page.' ); }
  foreach ( $s->rows['missing-media-references.csv'] as $r ) { $add( 'missing:' . $r['referenced_path'], 'missing_media', $r, 'Recurso no resuelto: ' . $r['notes'] ); }
  $ordered = array(); $visiting = array();
  $visit = static function( string $key ) use ( &$visit, &$ordered, &$entries, &$visiting ): void {
   if ( isset( $ordered[ $key ] ) ) { return; }
   if ( isset( $visiting[ $key ] ) ) { throw new \RuntimeException( 'DEPENDENCY_CYCLE' ); }
   $visiting[ $key ] = true;
   foreach ( $entries[ $key ]['dependencies'] as $dep ) {
    if ( ! isset( $entries[ $dep ] ) || in_array( $entries[ $dep ]['action'], array( 'SKIP','REVIEW' ), true ) ) { $entries[ $key ]['action'] = 'REVIEW'; $entries[ $key ]['planned_result'] = 'REVIEW'; $entries[ $key ]['notes'] = 'Dependencia no aprobada: ' . $dep; }
    elseif ( isset( $entries[ $dep ] ) ) { $visit( $dep ); }
   }
   unset( $visiting[ $key ] ); $ordered[ $key ] = $entries[ $key ];
  };
  foreach ( array_keys( $entries ) as $key ) { $visit( $key ); }
  $owners = array();
  foreach ( $ordered as $e ) {
   if ( in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) { continue; }
   foreach ( array_unique( array_merge( $e['source_keys'], $e['decision']['source_keys'] ?? array() ) ) as $key ) {
    if ( isset( $owners[ $key ] ) && $owners[ $key ] !== $e['entity_key'] ) { throw new \RuntimeException( 'SOURCE_KEY_HAS_TWO_OWNERS' ); }
    $owners[ $key ] = $e['entity_key'];
   }
  }
  $run = wp_generate_uuid4();
  $plan = array( 'manifest_version' => 1, 'transform_version' => self::VERSION, 'run_id' => $run, 'scope' => $scope, 'environment_id' => Storage::hash( array( home_url(), DB_NAME ) ), 'created_at' => gmdate( 'c' ), 'mode' => 'DRY_RUN', 'status' => 'VALIDATED', 'cursor' => 0, 'sources' => $s->fingerprints, 'decisions_hash' => Storage::hash( $d ), 'entries' => array_values( $ordered ), 'results' => array() );
  $plan['plan_hash'] = self::digest( $plan ); $plan['summary'] = self::summary( $plan['entries'] );
  Storage::write( 'run-' . $run . '.json', $plan );
  return $plan;
 }
 public static function digest( array $plan ): string {
  return Storage::hash( array_intersect_key( $plan, array_flip( array( 'manifest_version','transform_version','run_id','scope','environment_id','created_at','sources','decisions_hash','entries' ) ) ) );
 }
 public static function summary( array $entries ): array {
  $summary = array( 'total' => count( $entries ), 'actions' => array(), 'types' => array() );
  foreach ( $entries as $e ) { $action = $e['planned_result']; $type = $e['source_type']; $summary['actions'][ $action ] = ( $summary['actions'][ $action ] ?? 0 ) + 1; $summary['types'][ $type ][ $action ] = ( $summary['types'][ $type ][ $action ] ?? 0 ) + 1; }
  return $summary;
 }
}

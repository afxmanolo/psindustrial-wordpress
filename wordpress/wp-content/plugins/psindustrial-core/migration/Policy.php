<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

/**
 * Explicit, auditable, reproducible LOW-risk decision layer.
 *
 * This class is intentionally SEPARATE from the importer's core engine.
 * Planner, Runner, Storage and Identity are not modified in their control
 * flow by this class; Planner only consults its output as a fallback,
 * strictly for `scope === 'full'` (see Planner::build()), so it can never
 * affect subset execution, batching, confirmation phrases or the full-import
 * block. It never writes anything, never touches WordPress content or the
 * database, and never calls Runner.
 *
 * Scope: this class implements ONLY the rules classified LOW risk in
 * docs/implementation/review-analysis/09-proposed-rules.md and
 * docs/implementation/review-analysis/12-risk-analysis.md:
 * R-P01, R-T01, R-T03, R-G02 (institutional/contact subset only — the
 * SEO_LANDING subset of R-G02 is MEDIUM and is deliberately NOT implemented
 * here), R-M01, R-M02, R-M03, R-M04, R-M05.
 *
 * No MEDIUM or HIGH rule is implemented here (R-P02/P03/P05, R-T02, R-S01,
 * R-G01/G03, R-M01b/04b/05b/06/07, R-X01). No MERGE. No human decision from
 * docs/implementation/review-analysis/10-human-decisions.md is answered.
 * No product/brand/category is assigned on inference weaker than the
 * written condition of its own rule.
 *
 * Every produced decision carries `origin => 'low_rule'` plus `rule_id`,
 * `reason_code` and `evidence`, so a human can trace exactly why a source
 * stopped being REVIEW. Absence of a decision here changes nothing: the
 * source stays REVIEW exactly as before this class existed.
 */
final class Policy {
 public const VERSION = '1.0.0';

 // Conflict registers transcribed verbatim from docs/migration/manual-decisions-required.md
 // (D03 fabricante, D06 jerarquía/identidad). This is a literal citation of an existing,
 // documented human decision register — the same pattern already used by Planner for the
 // category 25/26/33 exception — not an inference and not a new editorial judgment.
 private const BRAND_CONFLICT_PRODUCTS = array( '6', '16', '20', '107', '14', '57', '43', '81', '44', '123', '125', '135', '139', '150' );
 private const CATEGORY_CONFLICT_PRODUCTS = array( '3' );
 private const CONFLICT_CATEGORIES = array( '25', '26', '33' );
 // R-G02 LOW subset only. SEO_LANDING is MEDIUM risk (09-proposed-rules.md) and is excluded.
 private const LOW_PAGE_CLASSIFICATIONS = array( 'INSTITUTIONAL_PAGE', 'CONTACT_PAGE' );

 /** Rule registry: metadata for documentation/audit only. Never used to make a decision. */
 public const RULES = array(
  'R-T01' => array( 'source_type' => 'category', 'reason_code' => 'R01_AUTHORIZATION_ONLY', 'description' => 'Categoría con padre SQL confirmado, fuera del registro de conflicto D06. No asigna productos.' ),
  'R-T03' => array( 'source_type' => 'brand', 'reason_code' => 'R01_AUTHORIZATION_ONLY', 'description' => 'Marca con página dedicada y logo referenciado literalmente en marcas.php. No asigna productos.' ),
  'R-P01' => array( 'source_type' => 'product', 'reason_code' => 'R01_AUTHORIZATION_ONLY', 'description' => 'Ficha única en su grupo canónico, PHP unívoco, categoría propia aprobada, sin conflicto D03/D06 registrado. Marca sólo si CONFIRMED.' ),
  'R-G02-LOW' => array( 'source_type' => 'page', 'reason_code' => 'R01_AUTHORIZATION_ONLY', 'description' => 'Página institucional o de contacto sin otro propietario posible.' ),
  'R-M01' => array( 'source_type' => 'media', 'reason_code' => 'R09_MEDIA_OWNER_PENDING', 'description' => 'Medio con relación SQL explícita (product-media-relations.csv) a un producto aprobado por R-P01.' ),
  'R-M05' => array( 'source_type' => 'media', 'reason_code' => 'R09_MEDIA_OWNER_PENDING', 'description' => 'Medio con relación de categoría o logo de marca (media-usage-evidence.csv) a un término aprobado por R-T01/R-T03.' ),
  'R-M04' => array( 'source_type' => 'media', 'reason_code' => 'R09_MEDIA_OWNER_PENDING', 'description' => 'Medio referenciado públicamente por una página aprobada por R-G02-LOW.' ),
  'R-M02' => array( 'source_type' => 'media', 'reason_code' => 'R10_MEDIA_DERIVATIVE', 'description' => 'Derivado del CMS legacy desde un original conservado, sin referencia pública directa.' ),
  'R-M03' => array( 'source_type' => 'media', 'reason_code' => 'R11_MEDIA_INFRASTRUCTURE', 'description' => 'Asset de interfaz del CMS legacy o fuente de iconos del frontend; no es medio editorial.' ),
 );

 /**
  * @param array $manual The manual/subset decision file (Planner::decisions()'s return
  *        value), so this class never re-decides a legacy path a human already folded
  *        into a different object via `binary_aliases`. Optional — an empty array simply
  *        means no manual decisions exist to defer to.
  * @return array<string,array> entity_key => decision, in the same shape Planner
  *         expects from subset-decisions.json, plus provenance fields.
  */
 public static function decisions( Sources $s, array $manual = array() ): array {
  // A path a human has already declared as the binary alias of a DIFFERENT manual
  // decision must never receive its own independent policy decision — the manual
  // decision already owns its identity. This is the only way two entities could
  // otherwise claim the same underlying legacy resource (Planner's own
  // SOURCE_KEY_HAS_TWO_OWNERS check exists precisely to catch that and must not be
  // relaxed; Policy avoids ever triggering it instead).
  $reservedAliasPaths = array();
  foreach ( $manual['entities'] ?? array() as $decision ) {
   foreach ( $decision['binary_aliases'] ?? array() as $alias ) { $reservedAliasPaths[ $alias ] = true; }
  }
  $out = array();
  $categories = self::categories( $s, $out );
  $brands     = self::brands( $s, $out );
  $products   = self::products( $s, $categories, $out );
  $pages      = self::pages( $s, $out );
  self::media( $s, $products, $categories, $brands, $pages, $reservedAliasPaths, $out );
  return $out;
 }

 /** R-T01. @return string[] approved category legacy_ids. */
 private static function categories( Sources $s, array &$out ): array {
  $approved = array();
  foreach ( $s->rows['category-master.csv'] as $r ) {
   $id = $r['legacy_id'];
   if ( in_array( $id, self::CONFLICT_CATEGORIES, true ) ) { continue; }
   if ( 'CONFIRMED' !== ( $r['sql_parent_confidence'] ?? '' ) ) { continue; }
   $parent = trim( $r['parent_legacy_id'] ?? '' );
   $out[ 'category:' . $id ] = array(
    'action' => 'MIGRATE',
    'parent' => ( '' !== $parent && '0' !== $parent ) ? 'category:' . $parent : '',
    'reason' => 'R-T01: categoría con padre SQL confirmado, fuera del registro de conflicto D06 (25/26/33). No asigna productos.',
    'origin' => 'low_rule', 'rule_id' => 'R-T01', 'reason_code' => 'R01_AUTHORIZATION_ONLY',
    'evidence' => 'category-master.csv:' . $id . ' sql_parent_confidence=CONFIRMED parent_legacy_id=' . ( '' === $parent ? '(root)' : $parent ),
   );
   $approved[] = $id;
  }
  return $approved;
 }

 /** R-T03. @return string[] approved brand legacy_ids. */
 private static function brands( Sources $s, array &$out ): array {
  $approved = array();
  foreach ( $s->rows['brand-master.csv'] as $r ) {
   $id = $r['legacy_id'];
   if ( '' === trim( $r['legacy_page'] ?? '' ) || '' === trim( $r['logo_evidence'] ?? '' ) ) { continue; }
   $out[ 'brand:' . $id ] = array(
    'action' => 'MIGRATE',
    'reason' => 'R-T03: marca con página dedicada y logo referenciado literalmente en marcas.php. No asigna productos.',
    'origin' => 'low_rule', 'rule_id' => 'R-T03', 'reason_code' => 'R01_AUTHORIZATION_ONLY',
    'evidence' => 'brand-master.csv:' . $id . ' legacy_page=' . $r['legacy_page'] . ' logo_evidence=' . $r['logo_evidence'],
   );
   $approved[] = $id;
  }
  return $approved;
 }

 /** R-P01. @return string[] approved product legacy_ids. */
 private static function products( Sources $s, array $approvedCategories, array &$out ): array {
  $groups = array();
  foreach ( $s->rows['canonical-candidate-groups.csv'] as $g ) { $groups[ $g['candidate_group'] ] = $g; }
  $categorySet = array_flip( $approvedCategories );
  $pmrByProduct = array();
  foreach ( $s->rows['product-media-relations.csv'] as $r ) { $pmrByProduct[ $r['product_id'] ][] = $r; }

  $approved = array();
  foreach ( $s->rows['product-master.csv'] as $r ) {
   $id = $r['legacy_product_id'];
   if ( '' === trim( $r['name'] ?? '' ) ) { continue; } // empty/test records (151-165) never qualify.
   $group = trim( $r['canonical_candidate_group'] ?? '' );
   if ( '' === $group || '1' !== ( $groups[ $group ]['record_count'] ?? '' ) ) { continue; } // singleton canonical group only.
   if ( 'YES' !== ( $r['unambiguous_php'] ?? '' ) ) { continue; }
   if ( in_array( $id, self::BRAND_CONFLICT_PRODUCTS, true ) || in_array( $id, self::CATEGORY_CONFLICT_PRODUCTS, true ) ) { continue; }
   $catId = trim( $r['category_id'] ?? '' );
   if ( '' === $catId || '0' === $catId ) { continue; } // never invent a category.
   if ( ! in_array( $r['category_confidence'] ?? '', array( 'CONFIRMED', 'STRONG_INFERENCE' ), true ) ) { continue; }
   if ( ! isset( $categorySet[ $catId ] ) ) { continue; } // category must itself be R-T01-approved, not merely exist.

   $brand = '';
   $brandId = trim( $r['brand_id'] ?? '' );
   if ( 'CONFIRMED' === ( $r['brand_confidence'] ?? '' ) && '' !== $brandId && '0' !== $brandId ) { $brand = 'brand:' . $brandId; }
   // brand_confidence below CONFIRMED (STRONG_INFERENCE/WEAK_INFERENCE/UNKNOWN) never assigns a brand — D03 stays open.

   $images = array(); $pdfs = array();
   foreach ( $pmrByProduct[ $id ] ?? array() as $row ) {
    $key = 'asset:' . $row['path'];
    if ( 'IMAGE' === $row['kind'] ) { $images[] = $key; }
    elseif ( 'PDF' === $row['kind'] ) { $pdfs[] = $key; }
   }
   $images = array_values( array_unique( $images ) ); $pdfs = array_values( array_unique( $pdfs ) );

   $out[ 'sql:productos:' . $id ] = array(
    'action' => 'MIGRATE',
    'categories' => array( 'category:' . $catId ),
    'brand' => $brand,
    'images' => $images,
    'pdfs' => $pdfs,
    'videos' => array(),
    'reason' => 'R-P01: ficha única en su grupo canónico, PHP unívoco, categoría propia aprobada, sin conflicto D03/D06.'
     . ( '' === $brand ? ' Marca NO asignada (brand_confidence=' . ( $r['brand_confidence'] ?? 'UNKNOWN' ) . '; D03 pendiente).' : '' ),
    'origin' => 'low_rule', 'rule_id' => 'R-P01', 'reason_code' => 'R01_AUTHORIZATION_ONLY',
    'evidence' => 'product-master.csv:' . $id . ' canonical_candidate_group=' . $group . ' record_count=1 unambiguous_php=YES'
     . ' category_id=' . $catId . ' category_confidence=' . $r['category_confidence'],
   );
   $approved[] = $id;
  }
  return $approved;
 }

 /** R-G02 (institutional/contact subset only). @return string[] approved legacy_file names. */
 private static function pages( Sources $s, array &$out ): array {
  $approved = array();
  foreach ( $s->rows['content-master.csv'] as $r ) {
   if ( ! in_array( $r['classification'] ?? '', self::LOW_PAGE_CLASSIFICATIONS, true ) ) { continue; }
   if ( 'YES' !== ( $r['preserve_url'] ?? '' ) ) { continue; }
   $file = $r['legacy_file'];
   $out[ 'php:' . $file ] = array(
    'action' => 'MIGRATE',
    'categories' => array(), 'brand' => '', 'images' => array(), 'pdfs' => array(), 'videos' => array(),
    'reason' => 'R-G02-LOW: contenido editorial propio (' . $r['classification'] . '), sin otro propietario posible en el modelo aprobado.',
    'origin' => 'low_rule', 'rule_id' => 'R-G02-LOW', 'reason_code' => 'R01_AUTHORIZATION_ONLY',
    'evidence' => 'content-master.csv:' . $file . ' classification=' . $r['classification'] . ' preserve_url=YES',
   );
   $approved[] = $file;
  }
  return $approved;
 }

 /**
  * R-M01 / R-M05 / R-M04 (MIGRATE, priority in that order — an owner that is itself
  * approved always wins over a merely-referencing page) and R-M02 / R-M03 (SKIP).
  * A media row that matches none of these keeps no decision here and stays REVIEW.
  */
 private static function media( Sources $s, array $approvedProducts, array $approvedCategories, array $approvedBrands, array $approvedPages, array $reservedAliasPaths, array &$out ): void {
  $productSet = array_flip( $approvedProducts );
  $categorySet = array_flip( $approvedCategories );
  $brandSet = array_flip( $approvedBrands );
  $pageSet = array_flip( $approvedPages );

  $pathProducts = array();
  foreach ( $s->rows['product-media-relations.csv'] as $r ) { $pathProducts[ $r['path'] ][ $r['product_id'] ] = true; }
  $pathCategories = array(); $pathBrands = array(); $pathPages = array();
  foreach ( $s->rows['media-usage-evidence.csv'] as $r ) {
   $owner = $r['owner'] ?? '';
   if ( 'DATABASE_CATEGORY' === $r['usage_type'] && str_starts_with( $owner, 'category:' ) ) { $pathCategories[ $r['legacy_path'] ][ substr( $owner, 9 ) ] = true; }
   elseif ( 'BRAND_LOGO' === $r['usage_type'] && str_starts_with( $owner, 'brand:' ) ) { $pathBrands[ $r['legacy_path'] ][ substr( $owner, 6 ) ] = true; }
   elseif ( 'PUBLIC_SOURCE' === $r['usage_type'] && str_starts_with( $owner, 'page:' ) ) { $pathPages[ $r['legacy_path'] ][ substr( $owner, 5 ) ] = true; }
  }

  foreach ( $s->rows['media-master.csv'] as $r ) {
   $path = $r['legacy_path'];
   if ( isset( $reservedAliasPaths[ $path ] ) ) { continue; } // Already absorbed into another object by a human decision.
   $key = 'asset:' . $path;

   $owningProducts = array_intersect_key( $pathProducts[ $path ] ?? array(), $productSet );
   if ( $owningProducts ) {
    $out[ $key ] = array(
     'action' => 'MIGRATE',
     'reason' => 'R-M01: relación SQL explícita (product-media-relations.csv) con producto(s) aprobado(s) por R-P01.',
     'origin' => 'low_rule', 'rule_id' => 'R-M01', 'reason_code' => 'R09_MEDIA_OWNER_PENDING',
     'evidence' => 'product-media-relations.csv path=' . $path . ' products=' . implode( '|', array_keys( $owningProducts ) ),
    );
    continue;
   }

   $owningCategories = array_intersect_key( $pathCategories[ $path ] ?? array(), $categorySet );
   $owningBrands = array_intersect_key( $pathBrands[ $path ] ?? array(), $brandSet );
   if ( $owningCategories || $owningBrands ) {
    $out[ $key ] = array(
     'action' => 'MIGRATE',
     'reason' => 'R-M05: relación de categoría o logo de marca (media-usage-evidence.csv) a un término aprobado por R-T01/R-T03.',
     'origin' => 'low_rule', 'rule_id' => 'R-M05', 'reason_code' => 'R09_MEDIA_OWNER_PENDING',
     'evidence' => 'media-usage-evidence.csv path=' . $path . ' categories=' . implode( '|', array_keys( $owningCategories ) ) . ' brands=' . implode( '|', array_keys( $owningBrands ) ),
    );
    continue;
   }

   $owningPages = array_intersect_key( $pathPages[ $path ] ?? array(), $pageSet );
   if ( $owningPages ) {
    $out[ $key ] = array(
     'action' => 'MIGRATE',
     'reason' => 'R-M04: referencia pública literal (media-usage-evidence.csv PUBLIC_SOURCE) desde una página aprobada por R-G02-LOW.',
     'origin' => 'low_rule', 'rule_id' => 'R-M04', 'reason_code' => 'R09_MEDIA_OWNER_PENDING',
     'evidence' => 'media-usage-evidence.csv path=' . $path . ' pages=' . implode( '|', array_keys( $owningPages ) ),
    );
    continue;
   }

   // No approved owner. Only two structural SKIP rules apply here; anything else stays REVIEW.
   $flags = array_filter( explode( '|', $r['usage_type'] ?? '' ) );
   $used = array_intersect( array( 'IMAGE_USED', 'PDF_USED', 'LOGO' ), $flags );
   $derived = in_array( 'DERIVED', $flags, true );
   if ( $derived && ! $used && '' !== trim( $r['original_ids'] ?? '' ) ) {
    $out[ $key ] = array(
     'action' => 'SKIP',
     'reason' => 'R-M02: derivado del CMS legacy desde un original conservado (original_ids), sin referencia pública directa. WordPress regenera sus propios tamaños al importar el original.',
     'origin' => 'low_rule', 'rule_id' => 'R-M02', 'reason_code' => 'R10_MEDIA_DERIVATIVE',
     'evidence' => 'media-master.csv:' . $path . ' usage_type=' . $r['usage_type'] . ' original_ids=' . $r['original_ids'],
    );
    continue;
   }
   if ( self::isInfrastructure( $path ) ) {
    $out[ $key ] = array(
     'action' => 'SKIP',
     'reason' => 'R-M03: asset de interfaz del CMS legacy o fuente de iconos del frontend; no es medio editorial. El tema nuevo aporta sus propios assets.',
     'origin' => 'low_rule', 'rule_id' => 'R-M03', 'reason_code' => 'R11_MEDIA_INFRASTRUCTURE',
     'evidence' => 'media-master.csv:' . $path . ' bucket=' . self::bucket( $path ),
    );
   }
  }
 }

 /** Directory-prefix classification only. Exact prefixes with a trailing slash — never a
  *  substring match — so a path like images/system-diagram.jpg or fonts-old.jpg in images/
  *  is never mistaken for infrastructure. */
 private static function bucket( string $path ): string {
  if ( str_starts_with( $path, 'system/backoffice/' ) ) { return 'system/backoffice'; }
  if ( str_starts_with( $path, 'system/files/' ) ) { return 'system/files'; } // real content store; never infrastructure.
  if ( str_starts_with( $path, 'system/' ) ) { return 'system/other'; }
  if ( str_starts_with( $path, 'fonts/' ) ) { return 'fonts'; }
  return 'other';
 }
 private static function isInfrastructure( string $path ): bool {
  return in_array( self::bucket( $path ), array( 'system/backoffice', 'system/other', 'fonts' ), true );
 }
}

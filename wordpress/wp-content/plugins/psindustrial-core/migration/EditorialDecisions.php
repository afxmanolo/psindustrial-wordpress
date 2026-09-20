<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

/**
 * Explicit, auditable, human-approved editorial decisions — Q01, Q02, Q04 and Q06 only.
 *
 * This is NOT a heuristic. It is deliberately separate from Policy.php (general LOW-risk
 * inference from evidence patterns) and from PdfApprovals.php (per-file PDF security
 * decisions). This class answers exactly four closed, human-approved questions from
 * docs/implementation/review-resolution/decision-questionnaire.md:
 *
 *  - Q01 (option A): a legacy page that directly reexpresses a product/category/brand
 *    belongs to that canonical entity; no separate Page is created for it. Applies ONLY
 *    when the page-to-entity correspondence is unambiguous and that entity is itself
 *    already approved (a Q02 winner, or an R-P01/R-T01/R-T03 Policy decision) — never by
 *    weak semantic similarity. A page whose ownership is ambiguous (a Q03 multi-product
 *    editorial group, a D06 conflict category, a page with no documented owner) keeps no
 *    decision here and stays REVIEW exactly as before. This is a CONTENT ownership
 *    decision only — it never decides a URL, a redirect or a permalink.
 *  - Q02 (option B): consolidate each of the 26 identity-equivalent product groups
 *    (docs/implementation/review-resolution/03-identity-groups.md) into one WordPress
 *    product. The WHICH GROUPS list is a closed, versioned human decision
 *    (editorial-decisions.json). The winner, the media union and their order are never
 *    stored there — they are re-derived here, every run, from fields that already exist
 *    in product-master.csv and the legacy SQL catalog (`fecha`), so this class can never
 *    duplicate or drift from its source data.
 *  - Q04 (option B): the 31 static-product-supplement.csv fichas become real
 *    psi_producto drafts, with their own content/images/PDF, but WITHOUT category or
 *    brand — never inferred from filename, menu placement or a similar product.
 *  - Q06 (option A): exclude the 15 incomplete/test legacy records (151-164 empty name,
 *    165 "Prueba") from the migratable catalog, preserving any of their associated media
 *    that also belongs to a different, valid entity.
 *
 * No other REVIEW cause (Q03, Q05, Q07-Q13, any MEDIUM/HIGH rule) is answered here.
 * Absence of a decision changes nothing: the source stays REVIEW exactly as before this
 * class existed. Never writes anything, never touches WordPress content or the database,
 * never calls Runner. Consulted by Planner only for scope === 'full', exactly like Policy
 * — it can never affect subset execution, batching or the confirmation-gated full-import
 * block.
 *
 * Every produced decision carries `origin => 'editorial_decision'` plus `decision_id`
 * ('Q01', 'Q02', 'Q04' or 'Q06'), so a human can trace exactly which approved question
 * moved a source out of REVIEW.
 */
final class EditorialDecisions {
 public const VERSION = '2.0.0';
 private const DECISIONS_FILE = 'docs/implementation/review-resolution/implementation/editorial-decisions.json';
 private const MERGE_FIELDS = array( 'name', 'content', 'categories', 'brand', 'images', 'pdfs', 'videos' );

 private static ?array $cache = null;

 private static function data(): array {
  if ( null === self::$cache ) {
   $path = Sources::safe( Storage::project(), self::DECISIONS_FILE );
   $data = json_decode( file_get_contents( $path ), true, 32, JSON_THROW_ON_ERROR );
   if ( 'EDITORIAL_DECISIONS_APPROVED' !== ( $data['scope'] ?? '' ) ) { throw new \RuntimeException( 'INVALID_EDITORIAL_DECISIONS_SCOPE' ); }
   self::$cache = $data;
  }
  return self::$cache;
 }

 /**
  * @param Sources $s
  * @param array $policy Policy::decisions()'s own output for this same build — Q01/Q10
  *        read it to recognize an already-approved R-P01/R-T01/R-T03 owner and to ENRICH
  *        that exact decision (never to re-derive its eligibility rules, which would risk
  *        drifting from Policy.php's own conditions). Optional: an empty array simply
  *        means that enrichment finds no eligible owner anywhere and only the parts that
  *        need no such lookup still apply.
  * @param array $manual Planner::decisions()'s own output (subset-decisions.json) for this
  *        same build — Q13 reads its entities' `binary_aliases` to identify a second legacy
  *        path already proven (by Planner itself, when it built the winning entity)
  *        byte-identical to an already-approved asset. Optional: an empty array simply
  *        means Q13 finds nothing to do.
  * @return array<string,array> entity_key => decision, same shape Planner expects from
  *         subset-decisions.json / Policy::decisions(), plus provenance fields.
  */
 public static function decisions( Sources $s, array $policy = array(), array $manual = array() ): array {
  $out = array();
  self::q02( $s, $out );
  self::q01( $s, $policy, $out );
  self::q04( $s, $out );
  self::q06( $s, $out );
  self::q07( $s, $out );
  self::q10( $s, $out );
  self::q11_missing_entities( $out );
  self::q13( $manual, $out );
  return $out;
 }

 /** Q11's per-path resolution table, keyed by the exact broken/missing referenced_path:
  *  'exclude' => this ONE path is a known, human-reviewed, genuinely-missing file (never
  *  fabricated) — media_union() below drops it and continues, instead of failing the
  *  whole union; 'substitute' => this exact path is proven (SHA-256 + a pre-existing
  *  SQL/PMR cross-reference, never filename similarity) to be a reference-encoding/
  *  whitespace mismatch for a real, unmodified file at resolved_path — media_union() below
  *  transparently uses the real path instead. Never applies to any OTHER path: only the 4
  *  cases explicitly reviewed in docs/implementation/review-resolution/11-missing-media.md
  *  (see 15-q11-missing-media.md for this phase's additions). */
 private static function q11_resolutions(): array {
  $out = array();
  foreach ( self::data()['q11_missing_media']['cases'] ?? array() as $c ) {
   $out[ $c['referenced_path'] ] = 'MISSING_SOURCE_FILE' === $c['resolution']
    ? array( 'action' => 'exclude' )
    : array( 'action' => 'substitute', 'resolved_path' => $c['resolved_path'] );
  }
  return $out;
 }

 /** Deduplicated-by-content asset key list for one product-master.csv-shaped column
  *  ('images' or 'technical_pdf'), across one or more rows, in row order. Returns null if
  *  any referenced path lacks its own media-master.csv row or fails the exact same
  *  validity+hash check Planner itself applies when it independently builds that path's
  *  own media entity ('media' === $type branch of Planner::build()'s $add closure) — the
  *  caller must then refuse whatever it was about to approve, in full, never partially.
  *  The ONLY exception is a path explicitly resolved by Q11 (see q11_resolutions()): a
  *  known-missing reference is dropped (this one path only, the rest of the union is
  *  unaffected — a missing OPTIONAL image/pdf never blocks the entity that references it),
  *  and a known encoding/whitespace mismatch is transparently substituted for its real
  *  file. No other path gets either treatment — an unreviewed missing reference still
  *  fails closed, exactly as before Q11 existed. */
 private static function media_union( Sources $s, array $mediaRows, array $rows, string $field ): ?array {
  $resolutions = self::q11_resolutions();
  $keys = array(); $hashes = array();
  foreach ( $rows as $r ) {
   foreach ( Sources::parts( $r[ $field ] ?? '' ) as $path ) {
    $res = $resolutions[ $path ] ?? null;
    if ( $res ) {
     if ( 'exclude' === $res['action'] ) { continue; }
     $path = $res['resolved_path'];
    }
    $row = $mediaRows[ $path ] ?? null;
    if ( ! $row ) { return null; }
    $asset = $s->asset( $path );
    if ( ! $asset['valid'] || ! hash_equals( $row['sha256'], $asset['sha256'] ) ) { return null; }
    if ( isset( $hashes[ $asset['sha256'] ] ) ) { continue; }
    $hashes[ $asset['sha256'] ] = $path; $keys[] = 'asset:' . $path;
   }
  }
  return $keys;
 }

 private static function q02( Sources $s, array &$out ): void {
  $byId = array();
  foreach ( $s->rows['product-master.csv'] as $r ) { $byId[ $r['legacy_product_id'] ] = $r; }
  $mediaRows = array();
  foreach ( $s->rows['media-master.csv'] as $r ) { $mediaRows[ $r['legacy_path'] ] = $r; }
  $approvedGroups = self::data()['q02_identity_merge']['groups'];
  $editorialApproval = self::data()['q02_identity_merge']['editorial_approval'];

  foreach ( $approvedGroups as $group ) {
   $groupKey = $group['group_key'];
   $approvedIds = $group['legacy_product_ids'];

   // The approved group must still match the LIVE source data exactly: same members,
   // same canonical_candidate_group, no drift since approval. Any mismatch refuses the
   // whole group silently (no decision emitted -> stays REVIEW) rather than improvising.
   $members = array();
   $intact = true;
   foreach ( $approvedIds as $id ) {
    if ( ! isset( $byId[ $id ] ) || $byId[ $id ]['canonical_candidate_group'] !== $groupKey ) { $intact = false; break; }
    $members[] = $byId[ $id ];
   }
   if ( $intact ) {
    $liveIds = array();
    foreach ( $s->rows['product-master.csv'] as $r ) { if ( $r['canonical_candidate_group'] === $groupKey ) { $liveIds[] = $r['legacy_product_id']; } }
    sort( $liveIds ); $approvedSorted = $approvedIds; sort( $approvedSorted );
    if ( $liveIds !== $approvedSorted ) { $intact = false; }
   }
   if ( ! $intact || count( $members ) < 2 ) { continue; }

   // Deterministic winner: oldest legacy record by productos.fecha (SQL catalog, already
   // populated for every row), tie-broken by lowest legacy_product_id. Never filesystem
   // or CSV row order.
   usort( $members, static function ( $a, $b ) use ( $s ) {
    $fa = $s->catalog['productos'][ $a['legacy_product_id'] ]['fecha'] ?? '';
    $fb = $s->catalog['productos'][ $b['legacy_product_id'] ]['fecha'] ?? '';
    return $fa <=> $fb ?: ( (int) $a['legacy_product_id'] <=> (int) $b['legacy_product_id'] );
   } );
   $winner = $members[0];
   $winnerId = $winner['legacy_product_id'];
   $winnerKey = 'sql:productos:' . $winnerId;

   // Union of images/pdfs across members (winner first, then the remaining members in the
   // same deterministic order), deduplicated by REAL file content (SHA-256) — never by
   // path or filename. Any failure refuses the WHOLE group, winner included, so both stay
   // REVIEW exactly as before this class existed (see media_union()'s own docblock).
   $imgKeys = self::media_union( $s, $mediaRows, $members, 'images' );
   if ( null === $imgKeys ) { continue; }
   $pdfKeys = self::media_union( $s, $mediaRows, $members, 'technical_pdf' );
   if ( null === $pdfKeys ) { continue; }

   // Planner's own dependency check (build()'s $visit walk) requires every image/pdf the
   // winner's data references to itself already be an approved, non-REVIEW entity -- the
   // same requirement R-P01 singleton products satisfy via Policy's R-M01/R-M04. These 26
   // groups were excluded from R-P01 precisely for being multi-record, so none of their
   // media has any such approval yet; Q02 is itself that approval; without it the merge
   // would stay REVIEW forever on an unmet dependency, silently defeating the decision.
   foreach ( array_merge( $imgKeys, $pdfKeys ) as $assetKey ) {
    if ( isset( $out[ $assetKey ] ) ) { continue; } // already decided (shared static asset, e.g. a generic icon).
    $out[ $assetKey ] = array(
     'action' => 'MIGRATE',
     'reason' => "Q02: parte de la union de medios de $groupKey (ganador $winnerKey); ver 01-q02-duplicate-resolution.md.",
     'origin' => 'editorial_decision', 'decision_id' => 'Q02',
    );
   }

   $sourceKeys = array_map( static fn( $m ) => 'sql:productos:' . $m['legacy_product_id'], $members );
   $brand = '';
   if ( 'CONFIRMED' === ( $winner['brand_confidence'] ?? '' ) && '' !== trim( $winner['brand_id'] ?? '' ) && '0' !== $winner['brand_id'] ) { $brand = 'brand:' . $winner['brand_id']; }
   $fecha = $s->catalog['productos'][ $winnerId ]['fecha'] ?? '';

   $out[ $winnerKey ] = array(
    'action' => 'MERGE',
    'source_keys' => $sourceKeys,
    'field_winners' => array_fill_keys( self::MERGE_FIELDS, $winnerKey ),
    'editorial_approval' => $editorialApproval,
    'categories' => array( 'category:' . $winner['category_id'] ),
    'brand' => $brand,
    'images' => $imgKeys,
    'pdfs' => $pdfKeys,
    'videos' => array(),
    'reason' => "Q02: identidad equivalente confirmada ($groupKey); ganador = registro mas antiguo (legacy id $winnerId, fecha $fecha); union de " . count( $imgKeys ) . ' imagen(es) y ' . count( $pdfKeys ) . ' PDF(s) deduplicada por SHA-256.',
    'origin' => 'editorial_decision', 'decision_id' => 'Q02',
   );
   foreach ( $members as $i => $m ) {
    if ( 0 === $i ) { continue; } // winner already handled above.
    $out[ 'sql:productos:' . $m['legacy_product_id'] ] = array(
     'action' => 'SKIP',
     'reason' => "Q02: identidad equivalente a $winnerKey ($groupKey); consolidado en el ganador, no se crea como producto independiente.",
     'origin' => 'editorial_decision', 'decision_id' => 'Q02',
    );
   }
  }
 }

 /**
  * Q01. Two independent sub-problems, both gated on an unambiguous, already-approved
  * owner: (a) product pages -- retire the page, and where the owner is a Policy R-P01
  * singleton (not already a Q02 winner, which is already complete on its own), enrich
  * that product's media from the page+SQL union already used by Q02; (b) category/brand
  * pages -- retire the page, and where the owner is a Policy R-T01/R-T03 term, attach its
  * single documented image/logo. Never invents a field the current model does not have:
  * a category/brand gets exactly one image slot (matching Fields.php/TermEditor.php),
  * never a gallery: any OTHER page-referenced media beyond that one slot is left with no
  * decision here rather than assigned to a destination the model cannot represent yet.
  */
 private static function q01( Sources $s, array $policy, array &$out ): void {
  $byId = array();
  foreach ( $s->rows['product-master.csv'] as $r ) { $byId[ $r['legacy_product_id'] ] = $r; }
  $mediaRows = array();
  foreach ( $s->rows['media-master.csv'] as $r ) { $mediaRows[ $r['legacy_path'] ] = $r; }
  $q02Groups = array();
  foreach ( self::data()['q02_identity_merge']['groups'] as $g ) { $q02Groups[ $g['group_key'] ] = true; }

  // ---- (a) PRODUCT_PAGE with related_product_ids: the page is redundant with a product
  // that already exists (or will, via Q02) and claims this exact file as its content
  // source. Multi-id pages whose ids are NOT a single Q02 group are Q03 editorial-merge
  // territory (which of several candidates is canonical is not this phase's call) and are
  // deliberately left untouched.
  foreach ( $s->rows['content-master.csv'] as $r ) {
   if ( 'PRODUCT_PAGE' !== ( $r['classification'] ?? '' ) ) { continue; }
   $ids = Sources::parts( $r['related_product_ids'] ?? '' );
   if ( ! $ids ) { continue; } // empty related_product_ids -> static-product-supplement.csv territory, handled by q04().
   $rows = array(); $groups = array();
   foreach ( $ids as $id ) { if ( isset( $byId[ $id ] ) ) { $rows[] = $byId[ $id ]; $groups[ $byId[ $id ]['canonical_candidate_group'] ] = true; } }
   if ( count( $rows ) !== count( $ids ) || count( $groups ) > 1 ) { continue; } // missing row, or genuinely inconsistent grouping -> KEEP_REVIEW.
   $group = array_key_first( $groups );
   $pageKey = 'php:' . $r['legacy_file'];

   if ( isset( $q02Groups[ $group ] ) ) {
    // Q02 already fully owns this file's content AND media via its own winner (Q02's own
    // union already reads the same product-master.csv images/technical_pdf columns this
    // method would otherwise enrich with) — find that winner among $out, don't redecide it.
    $winnerKey = null;
    foreach ( $ids as $id ) { $k = 'sql:productos:' . $id; if ( isset( $out[ $k ] ) && 'MERGE' === ( $out[ $k ]['action'] ?? '' ) ) { $winnerKey = $k; break; } }
    if ( ! $winnerKey ) { continue; } // Q02 didn't actually resolve this group this run (e.g. an invalid union asset) -> stay REVIEW, consistent with Q02's own refusal.
    $out[ $pageKey ] = array( 'action' => 'SKIP', 'reason' => "Q01: contenido de $group ya es propiedad de $winnerKey (Q02); no se crea Page independiente.", 'origin' => 'editorial_decision', 'decision_id' => 'Q01' );
    continue;
   }

   if ( 1 !== count( $ids ) ) { continue; } // multiple ids, not a Q02 group -> Q03 editorial-merge, out of scope.
   $productKey = 'sql:productos:' . $ids[0];
   $base = $policy[ $productKey ] ?? null;
   if ( ! $base || 'MIGRATE' !== ( $base['action'] ?? '' ) ) { continue; } // product itself not R-P01-approved -> KEEP_REVIEW.

   $imgKeys = self::media_union( $s, $mediaRows, $rows, 'images' );
   $pdfKeys = self::media_union( $s, $mediaRows, $rows, 'technical_pdf' );
   if ( null === $imgKeys || null === $pdfKeys ) { continue; } // an invalid asset in the fuller union -> KEEP_REVIEW; do not partially enrich.

   // Same requirement as Q02 (see its own comment above): each asset referenced in
   // images/pdfs must independently be an approved, non-REVIEW entity of its own, or
   // Planner's dependency walk downgrades this whole product back to REVIEW -- silently
   // stranding it exactly like the very first (pre-fix) version of Q02 did.
   foreach ( array_merge( $imgKeys, $pdfKeys ) as $assetKey ) {
    if ( isset( $out[ $assetKey ] ) ) { continue; }
    $out[ $assetKey ] = array(
     'action' => 'MIGRATE',
     'reason' => "Q01: parte de la union de medios de {$r['legacy_file']} (propietario $productKey); ver 07-q01-content-ownership.md.",
     'origin' => 'editorial_decision', 'decision_id' => 'Q01',
    );
   }

   $enriched = $base;
   $enriched['images'] = $imgKeys; $enriched['pdfs'] = $pdfKeys;
   $enriched['reason'] = "Q01: enriquece la decision de $productKey (R-P01) con los medios de pagina de {$r['legacy_file']} (union completa pagina+SQL por SHA-256, misma tecnica que Q02); categoria y marca sin cambios respecto a la politica.";
   $enriched['origin'] = 'editorial_decision'; $enriched['decision_id'] = 'Q01';
   unset( $enriched['rule_id'], $enriched['reason_code'], $enriched['evidence'] ); // Policy-specific provenance fields; Q01 carries its own.
   $out[ $productKey ] = $enriched;
   $out[ $pageKey ] = array( 'action' => 'SKIP', 'reason' => "Q01: contenido de {$r['legacy_file']} ya es propiedad de $productKey; no se crea Page independiente.", 'origin' => 'editorial_decision', 'decision_id' => 'Q01' );
  }

  // ---- (b) CATEGORY_PAGE / BRAND_PAGE: legacy_file must match EXACTLY one term's own
  // legacy_page (0 or 2+ -> no unambiguous owner -> KEEP_REVIEW), and that term must
  // already be R-T01/R-T03-approved.
  $catByPage = array(); foreach ( $s->rows['category-master.csv'] as $r ) { if ( trim( $r['legacy_page'] ?? '' ) ) { $catByPage[ $r['legacy_page'] ][] = $r; } }
  $brandByPage = array(); foreach ( $s->rows['brand-master.csv'] as $r ) { if ( trim( $r['legacy_page'] ?? '' ) ) { $brandByPage[ $r['legacy_page'] ][] = $r; } }
  $fileIdIndex = array();
  foreach ( $s->rows['media-master.csv'] as $r ) { foreach ( Sources::parts( str_replace( ',', '|', $r['file_ids'] ?? '' ) ) as $fid ) { $fileIdIndex[ $fid ][] = $r['legacy_path']; } }

  foreach ( $s->rows['content-master.csv'] as $r ) {
   $cls = $r['classification'] ?? '';
   if ( ! in_array( $cls, array( 'CATEGORY_PAGE', 'BRAND_PAGE' ), true ) ) { continue; }
   $isCat = 'CATEGORY_PAGE' === $cls;
   $owners = ( $isCat ? $catByPage : $brandByPage )[ $r['legacy_file'] ] ?? array();
   if ( 1 !== count( $owners ) ) { continue; } // no owner, or shared by 2+ terms -> KEEP_REVIEW.
   $term = $owners[0];
   $termKey = ( $isCat ? 'category:' : 'brand:' ) . $term['legacy_id'];
   $base = $policy[ $termKey ] ?? null;
   if ( ! $base || 'MIGRATE' !== ( $base['action'] ?? '' ) ) { continue; } // term itself not R-T01/R-T03-approved -> KEEP_REVIEW.

   $imagePath = null;
   if ( $isCat ) {
    $fid = trim( $term['image_file_id'] ?? '' );
    if ( '' !== $fid && 1 === count( $fileIdIndex[ $fid ] ?? array() ) ) { $imagePath = $fileIdIndex[ $fid ][0]; }
   } else {
    $logo = trim( $term['logo'] ?? '' );
    if ( '' !== $logo && isset( $mediaRows[ $logo ] ) ) { $imagePath = $logo; }
   }
   $enriched = $base;
   if ( $imagePath ) {
    $row = $mediaRows[ $imagePath ];
    $asset = $s->asset( $imagePath );
    // An invalid/mismatched image is NOT evidence the page-to-term content ownership
    // itself is wrong -- it only means this one optional field can't be added yet. The
    // page still retires; it simply does so without an image, same as a Policy-only term.
    if ( $asset['valid'] && hash_equals( $row['sha256'], $asset['sha256'] ) ) {
     $assetKey = 'asset:' . $imagePath;
     $enriched['image'] = $assetKey;
     if ( ! isset( $out[ $assetKey ] ) ) {
      $out[ $assetKey ] = array(
       'action' => 'MIGRATE',
       'reason' => 'Q01: ' . ( $isCat ? 'imagen de categoria' : 'logo de marca' ) . " $termKey (evidencia: " . ( $isCat ? 'category-master.csv:image_file_id' : 'brand-master.csv:logo' ) . ').',
       'origin' => 'editorial_decision', 'decision_id' => 'Q01',
      );
     }
    }
   }
   $enriched['reason'] = "Q01: pagina {$r['legacy_file']} representa este termino; contenido no crea Page independiente." . ( isset( $enriched['image'] ) ? ' Imagen/logo transferido desde ' . ( $isCat ? 'image_file_id' : 'logo' ) . '.' : '' );
   $enriched['origin'] = 'editorial_decision'; $enriched['decision_id'] = 'Q01';
   unset( $enriched['rule_id'], $enriched['reason_code'], $enriched['evidence'] );
   $out[ $termKey ] = $enriched;
   $out[ 'php:' . $r['legacy_file'] ] = array( 'action' => 'SKIP', 'reason' => "Q01: contenido ya representado por $termKey; no se crea Page independiente.", 'origin' => 'editorial_decision', 'decision_id' => 'Q01' );
  }
 }

 /** Q04. Each of the 31 (+1 already migrated in the earlier ensayo, which keeps its
  *  MANUAL decision untouched by manual-first precedence) static_product rows becomes a
  *  real product draft — content via Planner's own extraction from its legacy_php,
  *  images/pdfs from the SAME page+SQL union technique as Q02, but category/brand always
  *  empty: Option B explicitly forbids inferring either. The matching PRODUCT_PAGE
  *  content-master.csv row (verified 1:1, no related_product_ids) is retired so the same
  *  physical file never becomes both a product and a Page. */
 private static function q04( Sources $s, array &$out ): void {
  $mediaRows = array();
  foreach ( $s->rows['media-master.csv'] as $r ) { $mediaRows[ $r['legacy_path'] ] = $r; }

  foreach ( $s->rows['static-product-supplement.csv'] as $r ) {
   $file = $r['legacy_php'];
   $staticKey = 'static:' . $file;

   $imgKeys = self::media_union( $s, $mediaRows, array( $r ), 'images' );
   $pdfKeys = self::media_union( $s, $mediaRows, array( $r ), 'technical_pdf' );
   if ( null === $imgKeys || null === $pdfKeys ) { continue; } // KEEP_REVIEW: invalid media reference, do not improvise.

   $out[ $staticKey ] = array(
    'action' => 'CREATE_FROM_STATIC',
    'categories' => array(), 'brand' => '', // Option B: never inferred.
    'images' => $imgKeys, 'pdfs' => $pdfKeys, 'videos' => array(),
    'reason' => 'Q04: ficha sin correspondencia SQL fuerte; producto real, sin categoria ni marca asignada (pendiente decision editorial posterior, no deducida por nombre/menu/semejanza).',
    'origin' => 'editorial_decision', 'decision_id' => 'Q04',
   );
   $out[ 'php:' . $file ] = array( 'action' => 'SKIP', 'reason' => "Q04: contenido ya es el producto $staticKey; no se crea Page independiente.", 'origin' => 'editorial_decision', 'decision_id' => 'Q04' );
   foreach ( array_merge( $imgKeys, $pdfKeys ) as $assetKey ) {
    if ( isset( $out[ $assetKey ] ) ) { continue; }
    $out[ $assetKey ] = array( 'action' => 'MIGRATE', 'reason' => "Q04: parte de la ficha estatica $staticKey.", 'origin' => 'editorial_decision', 'decision_id' => 'Q04' );
   }
  }
 }

 private static function q06( Sources $s, array &$out ): void {
  $q06 = self::data()['q06_incomplete_or_test_records'];
  $ids = array_flip( $q06['legacy_product_ids'] );
  $reasonCode = $q06['reason_code'];

  foreach ( $s->rows['product-master.csv'] as $r ) {
   $id = $r['legacy_product_id'];
   if ( ! isset( $ids[ $id ] ) ) { continue; }
   $out[ 'sql:productos:' . $id ] = array(
    'action' => 'SKIP',
    'reason' => 'Q06: registro legacy incompleto o de prueba (' . $reasonCode . '); legacy id ' . $id . ( '' === trim( $r['name'] ?? '' ) ? ', sin nombre.' : ', nombre="' . $r['name'] . '".' ),
    'origin' => 'editorial_decision', 'decision_id' => 'Q06',
   );
  }

  // Media exclusively owned by a Q06 record follows it into SKIP -- but ONLY when no
  // product outside the Q06 list also references that exact path in
  // product-media-relations.csv. A shared path keeps no decision here and is left
  // entirely alone, so its valid owner's approval (present or future) is never affected.
  $ownersByPath = array();
  foreach ( $s->rows['product-media-relations.csv'] as $r ) { $ownersByPath[ $r['path'] ][ $r['product_id'] ] = true; }
  foreach ( $ownersByPath as $path => $owners ) {
   if ( array_diff_key( $owners, $ids ) ) { continue; } // shared with a valid entity.
   $out[ 'asset:' . $path ] = array(
    'action' => 'SKIP',
    'reason' => 'Q06: medio exclusivo de registro(s) legacy incompleto(s)/prueba (product_id ' . implode( '|', array_keys( $owners ) ) . '); sin otro propietario en product-media-relations.csv.',
    'origin' => 'editorial_decision', 'decision_id' => 'Q06',
   );
  }
 }

 /** Q07. Each of the 9 SEO_LANDING pages becomes a WordPress Page draft with its own
  *  extracted content. A landing's OWN media is approved only when the relationship is
  *  demonstrable and does not take ownership away from a stronger claim: a
  *  product-media-relations.csv (SQL) relation, or any non-landing page classification
  *  (PRODUCT_PAGE/CATEGORY_PAGE/BRAND_PAGE/INSTITUTIONAL_PAGE/CONTACT_PAGE/UTILITY) that
  *  also references the same path. Media shared only among landing siblings (e.g. two
  *  "operadores" family pages reusing the same illustrative photo) is still safe to
  *  approve: no stronger, more specific claim exists anywhere for it. Anything with a
  *  stronger or genuinely contradictory claim gets no decision here at all — it keeps
  *  whatever status it already has (KEEP_REVIEW in practice, unless something else already
  *  resolved it). A Page's target_type never wires `data.images`/`data.pdfs` into any
  *  WordPress field today (Runner::apply()'s producto-only gallery/datasheet logic does
  *  not run for target_type==='page') — they are still recorded on the landing's own
  *  decision for a complete, self-contained audit trail, and each is independently
  *  MIGRATE-approved so Planner's dependency check never strands the page over media this
  *  same decision is the one vouching for. */
 private static function q07( Sources $s, array &$out ): void {
  $pages = self::data()['q07_landings']['pages'];
  $contentByFile = array();
  foreach ( $s->rows['content-master.csv'] as $r ) { $contentByFile[ $r['legacy_file'] ] = $r; }
  $pageOwners = array();
  foreach ( $s->rows['media-usage-evidence.csv'] as $r ) {
   if ( 'PUBLIC_SOURCE' !== $r['usage_type'] || ! str_starts_with( $r['owner'] ?? '', 'page:' ) ) { continue; }
   $pageOwners[ $r['legacy_path'] ][] = substr( $r['owner'], 5 );
  }
  $pmrPaths = array();
  foreach ( $s->rows['product-media-relations.csv'] as $r ) { $pmrPaths[ $r['path'] ] = true; }
  $mediaRows = array();
  foreach ( $s->rows['media-master.csv'] as $r ) { $mediaRows[ $r['legacy_path'] ] = $r; }

  foreach ( $pages as $file ) {
   if ( ! isset( $contentByFile[ $file ] ) ) { continue; } // defensive: listed page no longer in content-master.csv -> KEEP_REVIEW.
   $pageKey = 'php:' . $file;

   $imgKeys = array(); $imgHashes = array(); $pdfKeys = array(); $pdfHashes = array();
   foreach ( $pageOwners as $path => $owners ) {
    if ( ! in_array( $file, $owners, true ) ) { continue; }
    if ( isset( $pmrPaths[ $path ] ) ) { continue; } // an SQL/product relation always outranks a landing.
    $otherClasses = array();
    foreach ( array_diff( $owners, array( $file ) ) as $o ) { $otherClasses[ $contentByFile[ $o ]['classification'] ?? '?' ] = true; }
    unset( $otherClasses['SEO_LANDING'] ); // sibling landings never block each other.
    if ( $otherClasses ) { continue; } // a stronger page class already claims this path.
    $mrow = $mediaRows[ $path ] ?? null;
    if ( ! $mrow ) { continue; }
    $asset = $s->asset( $path );
    if ( ! $asset['valid'] || ! hash_equals( $mrow['sha256'], $asset['sha256'] ) ) { continue; }
    if ( 'application/pdf' === $mrow['detected_type'] ) {
     if ( isset( $pdfHashes[ $asset['sha256'] ] ) ) { continue; }
     $pdfHashes[ $asset['sha256'] ] = $path; $pdfKeys[] = 'asset:' . $path;
    } else {
     if ( isset( $imgHashes[ $asset['sha256'] ] ) ) { continue; }
     $imgHashes[ $asset['sha256'] ] = $path; $imgKeys[] = 'asset:' . $path;
    }
   }
   foreach ( array_merge( $imgKeys, $pdfKeys ) as $assetKey ) {
    if ( isset( $out[ $assetKey ] ) ) { continue; }
    $out[ $assetKey ] = array( 'action' => 'MIGRATE', 'reason' => "Q07: medio propio de la landing $file (exclusivo, o compartido solo con otras landings hermanas).", 'origin' => 'editorial_decision', 'decision_id' => 'Q07' );
   }

   $out[ $pageKey ] = array(
    'action' => 'MIGRATE',
    'categories' => array(), 'brand' => '',
    'images' => $imgKeys, 'pdfs' => $pdfKeys, 'videos' => array(),
    'reason' => 'Q07: landing SEO preservada como Page en borrador, con su propio contenido; ver 13-q07-landings.md.',
    'origin' => 'editorial_decision', 'decision_id' => 'Q07',
   );
  }
 }

 /** Q10. Three independent decisions by explicit legacy_id, never by name: category 26
  *  (SKIP, a nameless duplicate of 33 with zero products and no page of its own), category
  *  33 (MIGRATE, kept — has its own page and real products), category 25 (SKIP, no
  *  legitimate use beyond the Q06 empty/test records already excluded on their own merits).
  *  Category 33 is structurally excluded from Policy's own R-T01 (Policy::
  *  CATEGORY_CONFLICT_PRODUCTS lists it, precisely for sharing category 26's name) — there
  *  is no existing Policy decision to enrich here, so this originates the MIGRATE decision
  *  directly, replicating R-T01's own parent-resolution logic for this one explicitly
  *  human-approved exception, and additionally attaches its image_file_id the same way Q01
  *  does for other categories. */
 private static function q10( Sources $s, array &$out ): void {
  $categories = self::data()['q10_category_cleanup']['categories'];
  $byId = array();
  foreach ( $s->rows['category-master.csv'] as $r ) { $byId[ $r['legacy_id'] ] = $r; }
  $mediaRows = array();
  foreach ( $s->rows['media-master.csv'] as $r ) { $mediaRows[ $r['legacy_path'] ] = $r; }
  $fileIdIndex = array();
  foreach ( $s->rows['media-master.csv'] as $r ) { foreach ( Sources::parts( str_replace( ',', '|', $r['file_ids'] ?? '' ) ) as $fid ) { $fileIdIndex[ $fid ][] = $r['legacy_path']; } }

  foreach ( $categories as $id => $approved ) {
   $row = $byId[ $id ] ?? null;
   if ( ! $row ) { continue; } // defensive: id no longer exists -> KEEP_REVIEW.
   $key = 'category:' . $id;

   if ( 'SKIP' === $approved['action'] ) {
    $out[ $key ] = array( 'action' => 'SKIP', 'reason' => $approved['reason'], 'origin' => 'editorial_decision', 'decision_id' => 'Q10' );
    continue;
   }

   if ( 'CONFIRMED' !== ( $row['sql_parent_confidence'] ?? '' ) ) { continue; } // same gate R-T01 itself would apply -> KEEP_REVIEW rather than improvise.
   $parent = trim( $row['parent_legacy_id'] ?? '' );
   $imagePath = null;
   $fid = trim( $row['image_file_id'] ?? '' );
   if ( '' !== $fid && 1 === count( $fileIdIndex[ $fid ] ?? array() ) ) {
    $candidate = $fileIdIndex[ $fid ][0];
    $mrow = $mediaRows[ $candidate ] ?? null;
    if ( $mrow ) {
     $asset = $s->asset( $candidate );
     if ( $asset['valid'] && hash_equals( $mrow['sha256'], $asset['sha256'] ) ) {
      $imagePath = $candidate;
      $assetKey = 'asset:' . $candidate;
      if ( ! isset( $out[ $assetKey ] ) ) { $out[ $assetKey ] = array( 'action' => 'MIGRATE', 'reason' => "Q10: imagen de categoria $key.", 'origin' => 'editorial_decision', 'decision_id' => 'Q10' ); }
     }
    }
   }
   $out[ $key ] = array(
    'action' => 'MIGRATE',
    'parent' => ( '' !== $parent && '0' !== $parent ) ? 'category:' . $parent : '',
    'image' => $imagePath ? 'asset:' . $imagePath : '',
    'reason' => $approved['reason'],
    'origin' => 'editorial_decision', 'decision_id' => 'Q10',
   );
  }
 }

 /** Q11. The `missing:<referenced_path>` stub entities Planner itself builds from
  *  missing-media-references.csv can never legitimately reach MIGRATE (they have no
  *  legacy_php/legacy_page/legacy_file to extract content from) — this gives each of the 4
  *  reviewed cases an explicit, auditable decision instead of the generic default: the 2
  *  resolved-by-evidence cases (see q11_resolutions()) are SKIP, since the real content now
  *  migrates under its own resolved path and this stub would otherwise be a pointless
  *  duplicate; the 2 genuinely-missing cases stay REVIEW but with a clear, explicit
  *  MISSING_SOURCE_FILE reason — never MIGRATE, never a fabricated file. This never touches
  *  the product/page that referenced the file: that is decided separately (see
  *  media_union()'s own use of q11_resolutions() for how a product/page proceeds without a
  *  missing OPTIONAL image, or transparently with the resolved one). */
 private static function q11_missing_entities( array &$out ): void {
  foreach ( self::data()['q11_missing_media']['cases'] ?? array() as $c ) {
   $key = 'missing:' . $c['referenced_path'];
   $out[ $key ] = 'MISSING_SOURCE_FILE' === $c['resolution']
    ? array( 'action' => 'REVIEW', 'reason' => "Q11: {$c['evidence']}", 'origin' => 'editorial_decision', 'decision_id' => 'Q11', 'reason_code' => $c['reason_code'] )
    : array( 'action' => 'SKIP', 'reason' => "Q11: referencia resuelta a {$c['resolved_path']} ({$c['resolution']}); {$c['evidence']}", 'origin' => 'editorial_decision', 'decision_id' => 'Q11', 'reason_code' => $c['reason_code'] );
  }
 }

 /** Q13. Any legacy path a MANUAL decision (subset-decisions.json, the earlier ensayo)
  *  already declared as a binary_alias of its own winning entity gets an explicit SKIP of
  *  its own — never a second attachment of content Planner itself already proved (via
  *  hash_equals(), when it built that winning entity — BINARY_ALIAS_NOT_IDENTICAL is thrown
  *  otherwise) byte-identical. Reads the alias list directly from Planner::decisions()'s own
  *  output rather than keeping a separate copy: the single existing, already-approved
  *  source of truth, never duplicated here. Generic by construction (not a one-off hardcode
  *  for today's single known case): any future manual decision that adds its own
  *  binary_aliases is covered the same way, with no code change needed. */
 private static function q13( array $manual, array &$out ): void {
  foreach ( $manual['entities'] ?? array() as $winnerKey => $decision ) {
   foreach ( $decision['binary_aliases'] ?? array() as $aliasPath ) {
    $aliasKey = 'asset:' . $aliasPath;
    if ( isset( $out[ $aliasKey ] ) ) { continue; }
    $out[ $aliasKey ] = array(
     'action' => 'SKIP',
     'reason' => "Q13: contenido identico (SHA-256, ya verificado por Planner al construir $winnerKey) a una ruta ya migrada; no se crea un segundo attachment. Ruta conservada como alias auditable.",
     'origin' => 'editorial_decision', 'decision_id' => 'Q13',
    );
   }
  }
 }
}

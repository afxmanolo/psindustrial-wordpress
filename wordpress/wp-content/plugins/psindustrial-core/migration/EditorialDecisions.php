<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

/**
 * Explicit, auditable, human-approved editorial decisions — Q02 and Q06 only.
 *
 * This is NOT a heuristic. It is deliberately separate from Policy.php (general LOW-risk
 * inference from evidence patterns) and from PdfApprovals.php (per-file PDF security
 * decisions). This class answers exactly two closed, human-approved questions from
 * docs/implementation/review-resolution/decision-questionnaire.md:
 *
 *  - Q02 (option B): consolidate each of the 26 identity-equivalent product groups
 *    (docs/implementation/review-resolution/03-identity-groups.md) into one WordPress
 *    product. The WHICH GROUPS list is a closed, versioned human decision
 *    (editorial-decisions.json). The winner, the media union and their order are never
 *    stored there — they are re-derived here, every run, from fields that already exist
 *    in product-master.csv and the legacy SQL catalog (`fecha`), so this class can never
 *    duplicate or drift from its source data.
 *  - Q06 (option A): exclude the 15 incomplete/test legacy records (151-164 empty name,
 *    165 "Prueba") from the migratable catalog, preserving any of their associated media
 *    that also belongs to a different, valid entity.
 *
 * No other REVIEW cause (Q01, Q03-Q13, any MEDIUM/HIGH rule) is answered here. Absence of
 * a decision changes nothing: the source stays REVIEW exactly as before this class
 * existed. Never writes anything, never touches WordPress content or the database, never
 * calls Runner. Consulted by Planner only for scope === 'full', exactly like Policy — it
 * can never affect subset execution, batching or the confirmation-gated full-import block.
 *
 * Every produced decision carries `origin => 'editorial_decision'` plus `decision_id`
 * ('Q02' or 'Q06'), so a human can trace exactly which approved question moved a source
 * out of REVIEW.
 */
final class EditorialDecisions {
 public const VERSION = '1.0.0';
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

 /** @return array<string,array> entity_key => decision, same shape Planner expects from
  *          subset-decisions.json / Policy::decisions(), plus provenance fields. */
 public static function decisions( Sources $s ): array {
  $out = array();
  self::q02( $s, $out );
  self::q06( $s, $out );
  return $out;
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
   // same deterministic order), deduplicated by REAL file content (SHA-256 via
   // Sources::asset()) -- never by path or filename. Every union path must have its own
   // media-master.csv row AND pass the exact same validity+hash check Planner itself will
   // later apply when it independently builds that path's own media entity
   // ('media' === $type branch of Planner::build()'s $add closure: !$asset['valid'] ||
   // !hash_equals(...) => REVIEW). Checking it here too, before promising anything, avoids
   // an asymmetric failure: without this, a winner blocked by one unrelated bad asset
   // (e.g. a mismatched MIME/extension) would still have already SKIPped its losers,
   // discarding their data for nothing. Any failure refuses the WHOLE group -- winner
   // included -- so both stay REVIEW exactly as before this class existed.
   $mediaOk = true;
   $imgKeys = array(); $imgHashes = array();
   foreach ( $members as $m ) {
    foreach ( Sources::parts( $m['images'] ) as $path ) {
     $row = $mediaRows[ $path ] ?? null;
     if ( ! $row ) { $mediaOk = false; break 2; }
     $asset = $s->asset( $path );
     if ( ! $asset['valid'] || ! hash_equals( $row['sha256'], $asset['sha256'] ) ) { $mediaOk = false; break 2; }
     if ( isset( $imgHashes[ $asset['sha256'] ] ) ) { continue; }
     $imgHashes[ $asset['sha256'] ] = $path; $imgKeys[] = 'asset:' . $path;
    }
   }
   $pdfKeys = array(); $pdfHashes = array();
   if ( $mediaOk ) {
    foreach ( $members as $m ) {
     foreach ( Sources::parts( $m['technical_pdf'] ) as $path ) {
      $row = $mediaRows[ $path ] ?? null;
      if ( ! $row ) { $mediaOk = false; break 2; }
      $asset = $s->asset( $path );
      if ( ! $asset['valid'] || ! hash_equals( $row['sha256'], $asset['sha256'] ) ) { $mediaOk = false; break 2; }
      if ( isset( $pdfHashes[ $asset['sha256'] ] ) ) { continue; }
      $pdfHashes[ $asset['sha256'] ] = $path; $pdfKeys[] = 'asset:' . $path;
     }
    }
   }
   if ( ! $mediaOk ) { continue; }

   // Planner's own dependency check (build()'s $visit walk) requires every image/pdf the
   // winner's data references to itself already be an approved, non-REVIEW entity -- the
   // same requirement R-P01 singleton products satisfy via Policy's R-M01/R-M04. These 26
   // groups were excluded from R-P01 precisely for being multi-record, so none of their
   // media has any such approval yet; Q02 is itself that approval; without it the merge
   // would stay REVIEW forever on an unmet dependency, silently defeating the decision.
   foreach ( array_merge( $imgKeys, $pdfKeys ) as $assetKey ) {
    if ( isset( $out[ $assetKey ] ) ) { continue; } // already decided by an earlier Q02 group (shared static asset, e.g. a generic icon).
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
}

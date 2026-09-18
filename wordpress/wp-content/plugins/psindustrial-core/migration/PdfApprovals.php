<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

/**
 * Explicit, hash-gated human decisions for a small, closed set of PDF files.
 *
 * This is NOT a general heuristic and is deliberately separate from Policy.php (which
 * implements general LOW-risk rules from evidence patterns). PdfApprovals implements
 * exactly two individually-approved, per-file human decisions from the PDF security
 * review (docs/implementation/pdf-security-review/), each gated by the file's exact
 * SHA-256 — never by filename alone, never by MIME type alone.
 *
 * Reads two files this class does not produce:
 *  - docs/implementation/pdf-security-review/pdf-approvals.json — the human decision:
 *    which exact source_sha256 is approved, and how (sanitize vs. exception).
 *  - docs/implementation/pdf-security-review/sanitization-audit.json — the RESULT of
 *    actually running tools/pdf-sanitizer/sanitize.py (a local-only migration tool; see
 *    that tool's README — it is never a WordPress runtime dependency and this class does
 *    not invoke it) against the Group A approvals.
 *
 * An approval only ever applies when the CURRENT file's SHA-256 matches the approved
 * source_sha256 exactly. If the legacy file changes, resolve() returns null and the
 * caller (Sources::asset()) falls back to the existing, unmodified Media::file_valid()
 * behaviour — the file goes back to REVIEW with no further action needed here. Nothing
 * in this class widens what it matches beyond the literal entries in pdf-approvals.json.
 */
final class PdfApprovals {
 private const APPROVALS_FILE = 'docs/implementation/pdf-security-review/pdf-approvals.json';
 private const AUDIT_FILE = 'docs/implementation/pdf-security-review/sanitization-audit.json';
 private const SANITIZED_SUBDIR = 'pdf-sanitized';

 private static ?array $approvalsCache = null;
 private static ?array $auditCache = null;

 private static function approvals(): array {
  if ( null === self::$approvalsCache ) {
   $path = Sources::safe( Storage::project(), self::APPROVALS_FILE );
   $data = json_decode( file_get_contents( $path ), true, 32, JSON_THROW_ON_ERROR );
   if ( 'PDF_SECURITY_REVIEW_APPROVED' !== ( $data['scope'] ?? '' ) ) { throw new \RuntimeException( 'INVALID_PDF_APPROVAL_SCOPE' ); }
   self::$approvalsCache = $data;
  }
  return self::$approvalsCache;
 }

 /** Keyed by source_legacy_path. Missing or unreadable audit data means no Group A
  *  substitute is available yet — those entries simply stay REVIEW, exactly as before
  *  this class existed. A malformed pdf-approvals.json (the actual human decision file)
  *  is not treated this leniently; that still throws, above. */
 private static function auditRecords(): array {
  if ( null === self::$auditCache ) {
   self::$auditCache = array();
   try {
    $path = Sources::safe( Storage::project(), self::AUDIT_FILE );
    $data = json_decode( file_get_contents( $path ), true, 32, JSON_THROW_ON_ERROR );
    foreach ( $data['records'] ?? array() as $r ) { self::$auditCache[ $r['source_legacy_path'] ] = $r; }
   } catch ( \Throwable $e ) { self::$auditCache = array(); }
  }
  return self::$auditCache;
 }

 /**
  * @return array{type:'sanitized',source_path:string,sha256:string,rule_id:string,sanitizer_version:string,generated_at:string,reason:string}
  *       | array{type:'exception',classification:string,reason_code:string,reason:string}
  *       | null
  */
 public static function resolve( string $legacyPath, string $currentSha256 ): ?array {
  $a = self::approvals();

  foreach ( $a['group_a_sanitization'] ?? array() as $entry ) {
   if ( $entry['legacy_path'] !== $legacyPath || ! hash_equals( $entry['source_sha256'], $currentSha256 ) ) { continue; }
   $record = self::auditRecords()[ $legacyPath ] ?? null;
   if ( ! $record || 'SANITIZED_OK' !== ( $record['status'] ?? '' ) ) { return null; } // sanitizer not run, or it KEEP_REVIEW'd this file itself.
   if ( ! hash_equals( $record['source_sha256_approved'] ?? '', $currentSha256 ) ) { return null; }
   try {
    $sanitizedPath = Sources::safe( Storage::root() . '/' . self::SANITIZED_SUBDIR, $record['sanitized_file'] ?? '' );
   } catch ( \Throwable $e ) { return null; } // sanitized file missing/moved/unsafe path: no substitute available.
   if ( ! hash_equals( $record['sanitized_sha256'] ?? '', hash_file( 'sha256', $sanitizedPath ) ) ) { return null; } // sanitized copy itself changed/corrupted since generation.
   return array(
    'type' => 'sanitized',
    'source_path' => $sanitizedPath,
    'sha256' => $record['sanitized_sha256'],
    'rule_id' => $entry['sanitization_rule_id'],
    'sanitizer_version' => $record['sanitizer_version'] ?? '',
    'generated_at' => $record['generated_at'] ?? '',
    'reason' => $entry['reason'],
   );
  }

  foreach ( $a['group_b_exception'] ?? array() as $entry ) {
   if ( $entry['legacy_path'] !== $legacyPath || ! hash_equals( $entry['source_sha256'], $currentSha256 ) ) { continue; }
   return array(
    'type' => 'exception',
    'classification' => $entry['classification'],
    'reason_code' => $entry['reason_code'],
    'reason' => $entry['reason'],
   );
  }

  return null;
 }

 /**
  * Narrow, execution-time predicate for Runner::media()'s independent re-validation.
  * True ONLY when legacyPath+sha256 match a Group B (`exception`) approval exactly —
  * never for a Group A (`sanitized`) approval, which needs no exception here because the
  * staged sanitized bytes already pass Media::file_valid() on their own merits.
  *
  * This is deliberately a boolean, not the full resolve() payload: Runner already staged
  * the correct bytes at plan-build time (via Sources::asset()/Planner) and only needs a
  * yes/no answer to "is this exact content an approved false positive", never staging
  * details. Single source of truth remains pdf-approvals.json via resolve() above — this
  * method adds no new matching logic of its own.
  */
 public static function isApprovedFalsePositive( string $legacyPath, string $sha256 ): bool {
  $approval = self::resolve( $legacyPath, $sha256 );
  return null !== $approval && 'exception' === $approval['type'];
 }
}

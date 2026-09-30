<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

/**
 * The RUNTIME-portable half of the PDF "false positive" approval design (the migration-only
 * half is Migration\PdfApprovals -- see that class's own docblock). Looks up an already
 * human-approved exception from data/pdf-safe-exceptions.php, a deployable file reconciled
 * from docs/implementation/pdf-security-review/pdf-approvals.json's group_b_exception
 * entries: see that file's own docblock for the full provenance and field meanings.
 *
 * Never touches Sources::safe(), Storage::guard(), Planner, or any local migration-only
 * source tree -- this class has zero dependency on the migration/ namespace and works
 * identically in local, staging and production. This is what fixes SOURCE_NOT_FOUND: the
 * previous runtime check (Fields::datasheet_valid() calling
 * Migration\PdfApprovals::isApprovedFalsePositive()) went through Sources::safe(
 * Storage::project(), ... ), which resolves a path under the git project root -- present
 * locally, never deployed to staging/production, where only wp-content/theme+plugin files
 * exist. Migration\PdfApprovals itself is untouched and keeps using that same local-only path
 * for its own migration-time callers (Runner.php, PartialPdfRepair.php), which is legitimate
 * there since migration only ever runs locally.
 *
 * Same security contract as Migration\PdfApprovals::isApprovedFalsePositive(): true ONLY when
 * the exact legacy path AND the current file's exact sha256 both match one recorded entry --
 * never by path alone, never by hash alone, never inferred, never a "source missing, allow"
 * fallback. A PDF with no matching entry, a mismatched hash, or a mismatched path is always
 * rejected -- exactly as strict as before, just without the local-only dependency.
 */
final class PdfSafeExceptions {
	private static ?array $registry = null;

	/** @return array<string,array{source_sha256:string,classification:string,reason_code:string,products:array<string>,reason:string}> */
	private static function registry(): array {
		if ( null === self::$registry ) {
			self::$registry = require dirname( PSINDUSTRIAL_CORE_FILE ) . '/data/pdf-safe-exceptions.php';
		}
		return self::$registry;
	}

	public static function isApproved( string $legacyPath, string $currentSha256 ): bool {
		if ( '' === $legacyPath || '' === $currentSha256 ) {
			return false;
		}
		$entry = self::registry()[ $legacyPath ] ?? null;
		return null !== $entry && hash_equals( $entry['source_sha256'], $currentSha256 );
	}
}

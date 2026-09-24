<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

/**
 * Conservative, single-layer mojibake reversal: recovers text whose UTF-8 bytes were once
 * misread as Windows-1252 and re-encoded as UTF-8 for storage (e.g. "ExplosiÃ³n" -> "Explosión",
 * "â€“" -> "–") -- the exact, well-known double-encoding this legacy site's own content
 * inherited (confirmed: legacy/database's dump itself declares utf8mb3, so the corruption
 * predates this project's own import entirely). Pure, stateless, no WordPress/DB access --
 * never decides WHICH rows to touch; callers own that decision (see MojibakeContentMigration).
 */
final class MojibakeRepair {
	/** Signature characters that never legitimately appear in this project's Spanish content. */
	private const SIGNATURE = '/[ÃÂâ]|\x{FFFD}/u';

	/**
	 * @return string|null the corrected candidate, or null if $original should not be touched
	 * (no signature present, already correct, or the reversal is not safely, losslessly
	 * reversible -- e.g. a genuine emoji/CJK codepoint that Windows-1252 cannot represent,
	 * which mb_convert_encoding() would otherwise silently replace with a literal "?").
	 * Every one of the 6 conditions below must hold; if any is unmet this returns null rather
	 * than guess.
	 */
	public static function fix_candidate( string $original ): ?string {
		if ( '' === $original ) { return null; }
		if ( ! preg_match( self::SIGNATURE, $original ) ) { return null; } // 1: no mojibake signature at all
		$candidate = @mb_convert_encoding( $original, 'Windows-1252', 'UTF-8' );
		if ( false === $candidate || '' === $candidate ) { return null; }
		if ( ! mb_check_encoding( $candidate, 'UTF-8' ) ) { return null; } // 2: candidate must be valid UTF-8
		if ( str_contains( $candidate, "\u{FFFD}" ) ) { return null; } // 5: never introduces U+FFFD
		// 3: round-trip -- re-corrupting the candidate the same way must losslessly reproduce
		// the original exactly. Catches mb_convert_encoding()'s silent substitution of a
		// codepoint it cannot represent in Windows-1252 (e.g. an emoji) with a literal "?",
		// which is valid UTF-8 and therefore invisible to the check above alone.
		if ( @mb_convert_encoding( $candidate, 'UTF-8', 'Windows-1252' ) !== $original ) { return null; }
		if ( preg_match_all( self::SIGNATURE, $candidate ) >= preg_match_all( self::SIGNATURE, $original ) ) { return null; } // 4: signatures must strictly decrease
		if ( $candidate === $original ) { return null; } // 6: must actually differ
		return $candidate;
	}
}

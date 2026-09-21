<?php
namespace PSIndustrial\Core\Migration;
defined( 'ABSPATH' ) || exit;

/** Read-only, bounded readers. PHP and SQL input are never evaluated. */
final class Sources {
 public const FILES = array( 'product-master.csv', 'category-master.csv', 'brand-master.csv', 'content-master.csv', 'media-master.csv', 'product-media-relations.csv', 'static-product-supplement.csv', 'canonical-candidate-groups.csv', 'media-usage-evidence.csv', 'missing-media-references.csv', 'page-source-evidence.json' );
 public array $rows = array();
 public array $fingerprints = array();
 public array $pages = array();
 public array $catalog = array();
 public function __construct() {
  foreach ( self::FILES as $file ) {
   $path = self::safe( Storage::project(), 'docs/migration/' . $file );
   $this->fingerprints[ 'docs/migration/' . $file ] = hash_file( 'sha256', $path );
   if ( str_ends_with( $file, '.csv' ) ) { $this->rows[ $file ] = self::csv( $path ); }
   else { $this->pages = json_decode( file_get_contents( $path ), true, 64, JSON_THROW_ON_ERROR ); }
  }
  foreach ( array( 'legacy/database/psindustrial_db.sql', 'legacy/public/puerta34_administrador.sql', 'docs/seo/url-master.csv' ) as $file ) {
   $this->fingerprints[ $file ] = hash_file( 'sha256', self::safe( Storage::project(), $file ) );
  }
  // EditorialDecisions.php/PdfApprovals.php read these three directly (never through $this->
  // rows), so a full-scope plan's REVIEW/MIGRATE/MERGE decisions depend on their content
  // without it ever entering the hash coverage above -- meaning SOURCE_CHANGED_REPLAN
  // (Runner::batch()) could not previously detect an edit to any of them between plan-build
  // and execution. Fingerprinted here, the existing check covers them for free, with no new
  // logic in Runner. editorial-decisions.json and pdf-approvals.json are both REQUIRED (Q02/
  // Q03/etc. and the PDF exception path throw without them); sanitization-audit.json is
  // OPTIONAL, matching PdfApprovals::auditRecords()'s own tolerance for it being absent.
  foreach ( array(
   'docs/implementation/review-resolution/implementation/editorial-decisions.json',
   'docs/implementation/pdf-security-review/pdf-approvals.json',
  ) as $file ) {
   $this->fingerprints[ $file ] = hash_file( 'sha256', self::safe( Storage::project(), $file ) );
  }
  try { $this->fingerprints['docs/implementation/pdf-security-review/sanitization-audit.json'] = hash_file( 'sha256', self::safe( Storage::project(), 'docs/implementation/pdf-security-review/sanitization-audit.json' ) ); }
  catch ( \Throwable $e ) { /* optional: absent means no Group A substitute available yet, exactly as PdfApprovals itself already tolerates. */ }
  $this->catalog = self::sql_catalog( self::safe( Storage::project(), 'legacy/public/puerta34_administrador.sql' ) );
  if ( count( $this->catalog['productos'] ?? array() ) !== count( $this->rows['product-master.csv'] ) ) { throw new \RuntimeException( 'SQL_CANONICAL_COVERAGE_CHANGED' ); }
 }
 public static function sql_catalog( string $path ): array {
  if ( filesize( $path ) > 20 * MB_IN_BYTES ) { throw new \RuntimeException( 'SQL_TOO_LARGE' ); }
  $sql = file_get_contents( $path ); $result = array();
  // Only catalogue INSERT literals are decoded. Users/configuration/DDL are never returned or executed.
  preg_match_all( '/INSERT INTO `(productos|categorias|marcas|file)`\s*\(([^)]+)\)\s*VALUES\s*(.*?);\s*(?:\r?\n|$)/s', $sql, $matches, PREG_SET_ORDER );
  foreach ( $matches as $m ) {
   preg_match_all( '/`([^`]+)`/', $m[2], $columns ); $fields = $columns[1]; $text = $m[3]; $length = strlen( $text ); $i = 0;
   while ( $i < $length ) {
    while ( $i < $length && ( ctype_space( $text[$i] ) || ',' === $text[$i] ) ) { ++$i; }
    if ( $i >= $length ) { break; }
    if ( '(' !== $text[$i++] ) { throw new \RuntimeException( 'SQL_LITERAL_EXPECTED' ); }
    $values = array();
    while ( $i < $length ) {
     while ( ctype_space( $text[$i] ) ) { ++$i; }
     $v = '';
     if ( "'" === $text[$i] ) {
      ++$i; $closed = false;
      while ( $i < $length ) {
       $c = $text[$i++];
       if ( '\\' === $c ) { $c = $text[$i++] ?? ''; $v .= match( $c ) { 'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", default => $c }; }
       elseif ( "'" === $c ) { if ( ( $text[$i] ?? '' ) === "'" ) { $v .= "'"; ++$i; } else { $closed = true; break; } }
       else { $v .= $c; }
      }
      if ( ! $closed ) { throw new \RuntimeException( 'SQL_UNCLOSED_LITERAL' ); }
     } else {
      while ( $i < $length && ! in_array( $text[$i], array( ',',')' ), true ) ) { $v .= $text[$i++]; }
      $v = trim( $v ); if ( 'NULL' === $v ) { $v = ''; } elseif ( ! preg_match( '/^-?\d+(\.\d+)?$/D', $v ) ) { throw new \RuntimeException( 'SQL_NON_LITERAL_REJECTED' ); }
     }
     $values[] = $v; while ( $i < $length && ctype_space( $text[$i] ) ) { ++$i; }
     $next = $text[$i++] ?? ''; if ( ')' === $next ) { break; } if ( ',' !== $next ) { throw new \RuntimeException( 'SQL_SEPARATOR_INVALID' ); }
    }
    if ( count( $fields ) !== count( $values ) ) { throw new \RuntimeException( 'SQL_COLUMN_COUNT' ); }
    $row = array_combine( $fields, $values ); $result[ $m[1] ][ $values[0] ] = $row;
   }
  }
  return $result;
 }
 public static function safe( string $root, string $relative ): string {
  if ( '' === $relative || str_contains( $relative, "\0" ) || str_contains( $relative, '\\' ) || preg_match( '#(^/|:|(^|/)\.\.?(/|$))#', $relative ) ) { throw new \RuntimeException( 'UNSAFE_SOURCE_PATH' ); }
  $base = realpath( $root ); $part = $base;
  foreach ( explode( '/', $relative ) as $segment ) { $part .= '/' . $segment; if ( is_link( $part ) ) { throw new \RuntimeException( 'SOURCE_SYMLINK' ); } }
  $real = realpath( $part );
  if ( ! $base || ! $real || ! is_file( $real ) || ! str_starts_with( strtolower( wp_normalize_path( $real ) ), strtolower( wp_normalize_path( $base ) ) . '/' ) ) { throw new \RuntimeException( 'SOURCE_NOT_FOUND' ); }
  return $real;
 }
 public static function csv( string $path ): array {
  if ( filesize( $path ) > 20 * MB_IN_BYTES ) { throw new \RuntimeException( 'CSV_TOO_LARGE' ); }
  $f = fopen( $path, 'rb' ); $header = fgetcsv( $f, 0, ',', '"', '' ); $result = array();
  if ( ! $header ) { fclose( $f ); throw new \RuntimeException( 'CSV_HEADER_MISSING' ); }
  $header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );
  if ( count( array_unique( $header ) ) !== count( $header ) ) { fclose( $f ); throw new \RuntimeException( 'CSV_DUPLICATE_HEADER' ); }
  try { while ( false !== ( $row = fgetcsv( $f, 0, ',', '"', '' ) ) ) {
   if ( array( null ) === $row ) { continue; }
   if ( count( $row ) !== count( $header ) ) { throw new \RuntimeException( 'CSV_COLUMN_COUNT' ); }
   $result[] = array_combine( $header, $row );
  } } finally { fclose( $f ); }
  return $result;
 }
 public static function parts( string $value ): array { return array_values( array_filter( array_map( 'trim', explode( '|', $value ) ), static fn( $v ) => '' !== $v && 'UNKNOWN' !== $v ) ); }
 public function asset( string $relative ): array {
  $path = self::safe( Storage::project() . '/legacy/public', $relative );
  $mime = ( new \finfo( FILEINFO_MIME_TYPE ) )->file( $path );
  $valid = \PSIndustrial\Core\Media::file_valid( $path, $mime );
  if ( preg_match( '/\.(?:php[0-9]?|phtml|phar|exe|html|js)\./i', basename( $relative ) ) ) { $valid = false; }
  $extension = strtolower( pathinfo( $relative, PATHINFO_EXTENSION ) );
  $exts = array( 'image/jpeg' => array( 'jpg', 'jpeg', 'jpe' ), 'image/png' => array( 'png' ), 'image/webp' => array( 'webp' ), 'application/pdf' => array( 'pdf' ) );
  if ( $extension && ! in_array( $extension, $exts[ $mime ] ?? array(), true ) ) { $valid = false; }
  $sha256 = hash_file( 'sha256', $path );
  $result = array( 'mime' => $mime, 'sha256' => $sha256, 'size' => filesize( $path ), 'valid' => $valid, 'staged_path' => $path, 'staged_sha256' => $sha256, 'pdf_approval' => null );
  // Explicit, hash-gated human decisions only (docs/implementation/pdf-security-review/).
  // Never consulted for non-PDF assets; resolves to null for every path/hash not in that
  // literal, human-approved list, in which case behaviour is completely unchanged.
  if ( 'application/pdf' === $mime ) {
   $approval = PdfApprovals::resolve( $relative, $sha256 );
   if ( $approval ) {
    $result['pdf_approval'] = $approval;
    if ( 'sanitized' === $approval['type'] ) { $result['valid'] = true; $result['staged_path'] = $approval['source_path']; $result['staged_sha256'] = $approval['sha256']; }
    elseif ( 'exception' === $approval['type'] ) { $result['valid'] = true; }
   }
  }
  return $result;
 }
 public function content( string $file ): array {
  $path = self::safe( Storage::project() . '/legacy/public', $file );
  $source = file_get_contents( $path );
  // Tokenizer strips all executable PHP; no include/eval, even for trusted local sources.
  $html = '';
  foreach ( token_get_all( $source ) as $token ) { if ( is_array( $token ) && T_INLINE_HTML === $token[0] ) { $html .= $token[1]; } }
  $dom = new \DOMDocument(); $previous = libxml_use_internal_errors( true );
  try { $dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING ); } finally { libxml_clear_errors(); libxml_use_internal_errors( $previous ); }
  $xp = new \DOMXPath( $dom ); $body = '';
  // Preserve editorial elements; excludes form controls, navigation, scripts and legacy presentation wrappers.
  foreach ( $xp->query( '//p|//h2|//h3|//ul|//ol|//table' ) as $node ) {
   if ( $xp->query( 'ancestor::nav|ancestor::header|ancestor::footer|ancestor::form|ancestor::ul|ancestor::ol|ancestor::table', $node )->length ) { continue; }
   $body .= $dom->saveHTML( $node ) . "\n";
  }
  $allowed = array_fill_keys( array( 'p','h2','h3','ul','ol','li','table','thead','tbody','tr','td','th','strong','em','b','i','br','a' ), array() );
  $allowed['a'] = array( 'href' => true, 'title' => true );
  $body = wp_kses( $body, $allowed );
  $seo = array( 'title' => $this->pages[ $file ]['title'] ?? '', 'headers' => $this->pages[ $file ]['headers'] ?? array() );
  $tokens = token_get_all( $source );
  foreach ( $tokens as $index => $token ) {
   if ( ! is_array( $token ) || T_VARIABLE !== $token[0] || ! in_array( $token[1], array( '$title', '$description' ), true ) ) { continue; }
   $j = $index + 1; while ( isset( $tokens[$j] ) && is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { ++$j; }
   if ( '=' !== ( $tokens[$j++] ?? '' ) ) { continue; }
   while ( isset( $tokens[$j] ) && is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { ++$j; }
   if ( is_array( $tokens[$j] ?? null ) && T_CONSTANT_ENCAPSED_STRING === $tokens[$j][0] ) { $seo[ substr( $token[1], 1 ) ] = stripcslashes( substr( $tokens[$j][1], 1, -1 ) ); }
  }
  return array( 'body' => $body, 'sha256' => hash( 'sha256', $source ), 'seo_evidence' => $seo, 'extraction' => 'static-editorial-v1; PHP excluded; links retained pending route mapping' );
 }
}

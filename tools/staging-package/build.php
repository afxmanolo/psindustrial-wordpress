<?php
/** Builds a reproducible staging deployment package from a committed git ref (default:
 * develop). Reads the file list straight from `git ls-tree`, never the working tree, so
 * the package can never contain uncommitted local changes, untracked scratch files, or
 * anything .gitignore already keeps out (wp-config.php, dumps, backups, logs, uploads,
 * node_modules, .git itself). The only extra exclusion applied here is the plugin's own
 * tests/ directory, which IS tracked (for CI/regression use) but must never ship.
 *
 * Usage:  php tools/staging-package/build.php [git-ref]
 * Output: dist/staging/psindustrial-staging-<ref>.zip
 *         dist/staging/manifest.json
 *
 * Never touches any remote host. Never reads credentials of any kind. Safe to run
 * repeatedly; each run overwrites its own previous output only. */

$ref = $argv[1] ?? 'develop';
$root = dirname( __DIR__, 2 );
chdir( $root );

function run( string $cmd ): string {
	$out = array(); $code = 0;
	exec( $cmd . ' 2>&1', $out, $code );
	if ( 0 !== $code ) { fwrite( STDERR, "Command failed ($code): $cmd\n" . implode( "\n", $out ) . "\n" ); exit( 1 ); }
	return implode( "\n", $out );
}
/** Raw bytes of a file at a git ref, via a real pipe (never exec()'s line-joined array,
 * which would corrupt binary content) and never merging stderr into the captured bytes. */
function git_show_raw( string $ref, string $path ): string {
	$descriptors = array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) );
	$proc = proc_open( array( 'git', 'show', $ref . ':' . $path ), $descriptors, $pipes );
	if ( ! is_resource( $proc ) ) { fwrite( STDERR, "Could not start git show for $path\n" ); exit( 1 ); }
	$content = stream_get_contents( $pipes[1] ); fclose( $pipes[1] );
	$errors = stream_get_contents( $pipes[2] ); fclose( $pipes[2] );
	$code = proc_close( $proc );
	if ( 0 !== $code ) { fwrite( STDERR, "git show failed for $path: $errors\n" ); exit( 1 ); }
	return $content;
}

$commit = trim( run( 'git rev-parse ' . escapeshellarg( $ref ) ) );
$commitShort = substr( $commit, 0, 7 );

// ---- deployable paths only: theme + plugin, both fully qualified relative to repo root.
$themePath = 'wordpress/wp-content/themes/psindustrial';
$pluginPath = 'wordpress/wp-content/plugins/psindustrial-core';
$excludePrefixes = array( $pluginPath . '/tests/' );

$allFiles = array();
foreach ( array( $themePath, $pluginPath ) as $base ) {
	$listing = run( 'git ls-tree -r ' . escapeshellarg( $ref ) . ' --name-only -- ' . escapeshellarg( $base ) );
	foreach ( explode( "\n", $listing ) as $file ) {
		if ( '' === trim( $file ) ) { continue; }
		$excluded = false;
		foreach ( $excludePrefixes as $prefix ) { if ( str_starts_with( $file, $prefix ) ) { $excluded = true; break; } }
		if ( ! $excluded ) { $allFiles[] = $file; }
	}
}
sort( $allFiles );
if ( count( $allFiles ) < 10 ) { fwrite( STDERR, "Refusing to package a suspiciously small file list (" . count( $allFiles ) . ").\n" ); exit( 1 ); }

// ---- safety net: never let a secret-shaped filename slip through, even if some future
// commit accidentally tracked one. This is a last-resort guard, not the primary control
// (the primary control is .gitignore plus never packaging outside the theme/plugin paths).
foreach ( $allFiles as $file ) {
	if ( preg_match( '/wp-config|\.sql($|\.gz$)|\.env(\.|$)|\.pem$|\.key$/i', $file ) ) {
		fwrite( STDERR, "Refusing to package a secret-shaped file: $file\n" ); exit( 1 );
	}
}

$distDir = $root . '/dist/staging';
if ( is_dir( $distDir ) ) {
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $distDir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $f ) { $f->isDir() ? rmdir( $f->getPathname() ) : unlink( $f->getPathname() ); }
} else {
	mkdir( $distDir, 0777, true );
}

$zipPath = $distDir . "/psindustrial-staging-{$commitShort}.zip";
$zip = new ZipArchive();
if ( true !== $zip->open( $zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) { fwrite( STDERR, "Could not create $zipPath\n" ); exit( 1 ); }

$hashes = array();
foreach ( $allFiles as $file ) {
	// Deployed layout: the zip root IS wp-content, so extracting it directly onto the
	// staging wp-content/ directory places theme/plugin files exactly where they belong.
	$deployPath = preg_replace( '#^wordpress/#', '', $file );
	$content = git_show_raw( $ref, $file );
	$zip->addFromString( $deployPath, $content );
	$hashes[ $deployPath ] = hash( 'sha256', $content );
}
$zip->close();

$packageSha256 = hash_file( 'sha256', $zipPath );

$themeVersion = null; $pluginVersion = null;
foreach ( explode( "\n", run( 'git show ' . escapeshellarg( $ref . ':' . $themePath . '/style.css' ) ) ) as $line ) {
	if ( preg_match( '/^Version:\s*(.+)$/', trim( $line ), $m ) ) { $themeVersion = trim( $m[1] ); }
}
foreach ( explode( "\n", run( 'git show ' . escapeshellarg( $ref . ':' . $pluginPath . '/psindustrial-core.php' ) ) ) as $line ) {
	if ( preg_match( '/^\s*\*\s*Version:\s*(.+)$/', $line, $m ) ) { $pluginVersion = trim( $m[1] ); }
}

$manifest = array(
	'generated_at' => gmdate( 'c' ),
	'source' => array( 'ref' => $ref, 'commit' => $commit ),
	'theme' => array( 'slug' => 'psindustrial', 'version' => $themeVersion ),
	'plugin' => array( 'slug' => 'psindustrial-core', 'version' => $pluginVersion ),
	'file_count' => count( $allFiles ),
	'package' => array( 'path' => 'dist/staging/' . basename( $zipPath ), 'sha256' => $packageSha256, 'bytes' => filesize( $zipPath ) ),
	'file_sha256' => $hashes,
);
file_put_contents( $distDir . '/manifest.json', json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );

echo "OK\n";
echo "commit:  $commit\n";
echo "files:   " . count( $allFiles ) . "\n";
echo "package: $zipPath\n";
echo "bytes:   " . filesize( $zipPath ) . "\n";
echo "sha256:  $packageSha256\n";
echo "manifest: $distDir/manifest.json\n";

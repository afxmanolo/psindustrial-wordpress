<?php
/**
 * Plugin Name: PS Industrial Core
 * Description: Modelo y configuración base del catálogo PS Industrial.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.4
 * Text Domain: psindustrial-core
 */
defined( 'ABSPATH' ) || exit;

define( 'PSINDUSTRIAL_CORE_VERSION', '1.0.0' );
define( 'PSINDUSTRIAL_CORE_FILE', __FILE__ );

foreach ( array( 'Plugin', 'Content', 'Fields', 'ProductEditor', 'TermEditor', 'Settings', 'Roles', 'Media', 'Editorial', 'AdminList', 'TermPolicy', 'Contact', 'LegacyUrls', 'BrandsMigration', 'CategoriesMigration', 'IdentityHashRebaseline', 'MojibakeContentMigration', 'InstitutionalContentMigration' ) as $class ) {
	require_once __DIR__ . '/includes/' . $class . '.php';
}
foreach ( array( 'Storage', 'Sources', 'Identity', 'MojibakeRepair', 'PdfApprovals', 'EditorialDecisions', 'Policy', 'Planner', 'Runner', 'Admin' ) as $class ) {
	require_once __DIR__ . '/migration/' . $class . '.php';
}
PSIndustrial\Core\Migration\Admin::boot();
PSIndustrial\Core\Plugin::boot();
register_activation_hook( __FILE__, array( PSIndustrial\Core\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( PSIndustrial\Core\Plugin::class, 'deactivate' ) );

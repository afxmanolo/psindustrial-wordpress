<?php
/**
 * Plugin Name: PS Industrial Core
 * Description: Modelo y configuración base del catálogo PS Industrial.
 * Version: 0.4.0
 * Requires at least: 6.7
 * Requires PHP: 8.4
 * Text Domain: psindustrial-core
 */
defined( 'ABSPATH' ) || exit;

define( 'PSINDUSTRIAL_CORE_VERSION', '0.4.0' );
define( 'PSINDUSTRIAL_CORE_FILE', __FILE__ );

foreach ( array( 'Plugin', 'Content', 'Fields', 'ProductEditor', 'TermEditor', 'Settings', 'Roles', 'Media', 'Editorial', 'AdminList', 'TermPolicy', 'Contact', 'LegacyUrls' ) as $class ) {
	require_once __DIR__ . '/includes/' . $class . '.php';
}
foreach ( array( 'Storage', 'Sources', 'Identity', 'PdfApprovals', 'EditorialDecisions', 'Policy', 'Planner', 'Runner', 'Admin' ) as $class ) {
	require_once __DIR__ . '/migration/' . $class . '.php';
}
PSIndustrial\Core\Migration\Admin::boot();
PSIndustrial\Core\Plugin::boot();
register_activation_hook( __FILE__, array( PSIndustrial\Core\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( PSIndustrial\Core\Plugin::class, 'deactivate' ) );

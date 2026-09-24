<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class Plugin {
	public static function boot(): void {
		Roles::boot();
		Media::boot();
		Editorial::boot();
		AdminList::boot();
		TermPolicy::boot();
		add_action( 'init', array( Content::class, 'register' ) );
		add_action( 'init', array( Fields::class, 'register' ) );
		add_action( 'init', array( TermEditor::class, 'register_meta' ) );
		ProductEditor::boot();
		TermEditor::boot();
		Settings::boot();
		Contact::boot();
		LegacyUrls::boot();
		BrandsMigration::boot();
		CategoriesMigration::boot();
		IdentityHashRebaseline::boot();
	}
	public static function activate(): void {
		Content::register();
		// Bootstrap only: grant this plugin's capabilities to administrators.
		$role = get_role( 'administrator' );
		if ( $role ) {
			foreach ( Content::capabilities() as $capability ) {
				$role->add_cap( $capability );
			}
		}
		if ( false === get_option( 'psi_site_settings', false ) ) {
			add_option( 'psi_site_settings', Settings::defaults(), '', false );
		}
		flush_rewrite_rules( false );
	}
	public static function deactivate(): void {
		unregister_taxonomy( 'psi_categoria' );
		unregister_taxonomy( 'psi_marca' );
		unregister_post_type( 'psi_producto' );
		flush_rewrite_rules( false );
		// Intentionally preserve all content, metadata and capabilities.
	}
}

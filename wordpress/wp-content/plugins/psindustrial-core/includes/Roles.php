<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class Roles {
	public const VERSION = 2;
	public static function boot(): void {
		add_action( 'init', array( self::class, 'install' ), 1 );
		add_filter( 'map_meta_cap', array( self::class, 'map' ), 10, 4 );
		add_filter( 'wp_insert_post_data', array( self::class, 'protect_post' ), 20, 4 );
		foreach ( array( 'page', 'psi_producto' ) as $type ) {
			add_filter( 'rest_pre_insert_' . $type, array( self::class, 'rest_post' ), 20, 2 );
		}
		add_action( 'admin_menu', array( self::class, 'menu' ), 99 );
	}
	public static function install(): void {
		if ( (int) get_option( 'psi_schema_version', 0 ) >= self::VERSION ) { return; }
		$caps = array_fill_keys( array(
			'read', 'edit_psi_productos', 'edit_others_psi_productos', 'edit_published_psi_productos',
			'edit_private_psi_productos', 'read_private_psi_productos', 'publish_psi_productos', 'delete_psi_productos',
			'edit_pages', 'edit_others_pages', 'edit_published_pages', 'publish_pages', 'delete_pages',
			'upload_files', 'psi_edit_media', 'psi_edit_content_seo', 'psi_edit_contact_settings',
			'psi_manage_categories', 'psi_edit_categories', 'psi_assign_categories',
			'psi_manage_brands', 'psi_edit_brands', 'psi_assign_brands',
		), true );
		add_role( 'psi_gestor', __( 'Gestor de contenidos', 'psindustrial-core' ), $caps );
		$role = get_role( 'psi_gestor' );
		if ( $role ) {
			foreach ( array_keys( $role->capabilities ) as $cap ) { if ( ! isset( $caps[ $cap ] ) ) { $role->remove_cap( $cap ); } }
			foreach ( $caps as $cap => $value ) { $role->add_cap( $cap, $value ); }
		}
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array_merge( Content::capabilities(), array( 'psi_edit_media', 'psi_edit_contact_settings', 'psi_edit_content_seo', 'psi_manage_routes', 'psi_manage_structure', 'psi_manage_migration', 'psi_manage_seo' ) ) as $cap ) { $admin->add_cap( $cap ); }
		}
		update_option( 'psi_schema_version', self::VERSION, false );
	}
	public static function map( array $caps, string $cap, int $user_id, array $args ): array {
		if ( empty( $args[0] ) ) { return $caps; }
		$post = get_post( (int) $args[0] );
		if ( ! $post ) { return $caps; }
		$user = get_userdata( $user_id );
		if ( 'attachment' === $post->post_type && $user && $user->has_cap( 'psi_edit_media' ) ) {
			if ( in_array( $cap, array( 'edit_post', 'edit_attachment' ), true ) ) { return array( 'psi_edit_media' ); }
			if ( in_array( $cap, array( 'delete_post', 'delete_attachment' ), true ) && ! $user->has_cap( 'manage_options' ) ) { return array( 'do_not_allow' ); }
		}
		if ( in_array( $post->post_type, array( 'page', 'psi_producto' ), true ) && in_array( $cap, array( 'delete_post', 'delete_psi_producto' ), true ) && $user && ! $user->has_cap( 'psi_manage_routes' ) ) {
			if ( (int) $post->post_author !== $user_id || ! in_array( $post->post_status, array( 'draft', 'auto-draft', 'pending' ), true ) ) { return array( 'do_not_allow' ); }
		}
		return $caps;
	}
	public static function protected( int $id ): bool {
		return $id && in_array( get_post_status( $id ), array( 'publish', 'future' ), true ) && ! current_user_can( 'psi_manage_routes' );
	}
	public static function rest_post( mixed $prepared, \WP_REST_Request $r ): mixed {
		if ( is_wp_error( $prepared ) || ! self::protected( (int) $r['id'] ) ) { return $prepared; }
		$old = get_post( (int) $r['id'] );
		foreach ( array( 'slug' => 'post_name', 'parent' => 'post_parent', 'status' => 'post_status' ) as $key => $field ) {
			if ( $r->has_param( $key ) && (string) $r[ $key ] !== (string) $old->$field ) {
				return new \WP_Error( 'psi_protected_route', __( 'Solicite al administrador el cambio de URL o la retirada de contenido publicado.', 'psindustrial-core' ), array( 'status' => 403 ) );
			}
		}
		return $prepared;
	}
	public static function protect_post( array $data, array $postarr, array $raw, bool $update ): array {
		$id = (int) ( $postarr['ID'] ?? 0 );
		if ( $update && in_array( $data['post_type'], array( 'page', 'psi_producto' ), true ) && self::protected( $id ) ) {
			$old = get_post( $id );
			foreach ( array( 'post_name', 'post_parent', 'post_status' ) as $field ) {
				if ( (string) $data[ $field ] !== (string) $old->$field ) {
					$data[ $field ] = wp_slash( $old->$field );
					ProductEditor::error( __( 'Se conservó la URL y el estado publicado. Solicite esos cambios al administrador.', 'psindustrial-core' ) );
				}
			}
		}
		return $data;
	}
	public static function menu(): void {
		if ( current_user_can( 'manage_options' ) || ! current_user_can( 'psi_edit_media' ) ) { return; }
		foreach ( array( 'edit.php', 'edit-comments.php', 'tools.php', 'options-general.php', 'themes.php', 'plugins.php', 'users.php' ) as $slug ) { remove_menu_page( $slug ); }
	}
}

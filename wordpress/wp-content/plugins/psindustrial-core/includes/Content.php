<?php
namespace PSIndustrial\Core;
defined( 'ABSPATH' ) || exit;

final class Content {
	public static function register(): void {
		register_post_type( 'psi_producto', array(
			'labels' => array(
				'name' => __( 'Productos', 'psindustrial-core' ),
				'singular_name' => __( 'Producto', 'psindustrial-core' ),
				'add_new' => __( 'Añadir producto', 'psindustrial-core' ),
				'add_new_item' => __( 'Añadir producto', 'psindustrial-core' ),
				'edit_item' => __( 'Editar producto', 'psindustrial-core' ),
				'new_item' => __( 'Nuevo producto', 'psindustrial-core' ),
				'view_item' => __( 'Ver producto', 'psindustrial-core' ),
				'search_items' => __( 'Buscar productos', 'psindustrial-core' ),
				'not_found' => __( 'No hay productos.', 'psindustrial-core' ),
				'not_found_in_trash' => __( 'No hay productos en la papelera.', 'psindustrial-core' ),
				'all_items' => __( 'Todos los productos', 'psindustrial-core' ),
				'archives' => __( 'Productos', 'psindustrial-core' ),
			),
			'public' => true,
			'show_in_rest' => true,
			'hierarchical' => false,
			'has_archive' => 'productos',
			'rewrite' => array( 'slug' => 'producto', 'with_front' => false ),
			'capability_type' => array( 'psi_producto', 'psi_productos' ),
			'map_meta_cap' => true,
			'delete_with_user' => false,
			'menu_icon' => 'dashicons-products',
			'menu_position' => 6,
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
		) );
		self::taxonomy( 'psi_categoria', true, 'categoria', 'categories' );
		self::taxonomy( 'psi_marca', false, 'marca', 'brands' );
	}
	private static function taxonomy( string $name, bool $hierarchical, string $slug, string $caps ): void {
		$plural = $hierarchical ? __( 'Categorías', 'psindustrial-core' ) : __( 'Marcas', 'psindustrial-core' );
		$single = $hierarchical ? __( 'Categoría', 'psindustrial-core' ) : __( 'Marca', 'psindustrial-core' );
		register_taxonomy( $name, array( 'psi_producto' ), array(
			'labels' => array(
				'name' => $plural,
				'singular_name' => $single,
				'menu_name' => $plural,
				'search_items' => $hierarchical ? __( 'Buscar categorías', 'psindustrial-core' ) : __( 'Buscar marcas', 'psindustrial-core' ),
				'all_items' => $hierarchical ? __( 'Todas las categorías', 'psindustrial-core' ) : __( 'Todas las marcas', 'psindustrial-core' ),
				'edit_item' => $hierarchical ? __( 'Editar categoría', 'psindustrial-core' ) : __( 'Editar marca', 'psindustrial-core' ),
				'add_new_item' => $hierarchical ? __( 'Añadir categoría', 'psindustrial-core' ) : __( 'Añadir marca', 'psindustrial-core' ),
				'new_item_name' => $hierarchical ? __( 'Nombre de categoría', 'psindustrial-core' ) : __( 'Nombre de marca', 'psindustrial-core' ),
				'parent_item' => $hierarchical ? __( 'Categoría superior', 'psindustrial-core' ) : null,
			),
			'public' => true,
			'hierarchical' => $hierarchical,
			'show_in_rest' => true,
			'show_admin_column' => true,
			'rewrite' => array( 'slug' => $slug, 'with_front' => false, 'hierarchical' => false ),
			'capabilities' => array(
				'manage_terms' => 'psi_manage_' . $caps,
				'edit_terms' => 'psi_edit_' . $caps,
				'delete_terms' => 'psi_delete_' . $caps,
				'assign_terms' => 'psi_assign_' . $caps,
			),
		) );
	}
	public static function capabilities(): array {
		$caps = array();
		foreach ( array( 'edit', 'edit_others', 'edit_private', 'edit_published', 'publish', 'read_private', 'delete', 'delete_others', 'delete_private', 'delete_published' ) as $verb ) {
			$caps[] = $verb . '_psi_productos';
		}
		foreach ( array( 'categories', 'brands' ) as $group ) {
			foreach ( array( 'manage', 'edit', 'delete', 'assign' ) as $verb ) {
				$caps[] = 'psi_' . $verb . '_' . $group;
			}
		}
		return $caps;
	}
	public static function related( int $id ): array {
		$ids = array_map( 'absint', (array) get_post_meta( $id, '_psi_related_ids', true ) );
		return $ids ? get_posts( array( 'post_type' => 'psi_producto', 'post_status' => 'publish', 'post__in' => $ids, 'orderby' => 'post__in', 'numberposts' => 50 ) ) : array();
	}
}

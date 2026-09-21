<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;
add_action( 'wp_enqueue_scripts', static function() {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'psindustrial-base', get_theme_file_uri( 'assets/css/base.css' ), array(), $version );
	wp_enqueue_style( 'psindustrial-global', get_theme_file_uri( 'assets/css/global.css' ), array( 'psindustrial-base' ), (string) filemtime( get_theme_file_path( 'assets/css/global.css' ) ) );
	if ( is_front_page() ) {
		wp_enqueue_style( 'psindustrial-home', get_theme_file_uri( 'assets/css/home.css' ), array( 'psindustrial-global' ), (string) filemtime( get_theme_file_path( 'assets/css/home.css' ) ) );
		wp_enqueue_script( 'psindustrial-home', get_theme_file_uri( 'assets/js/home.js' ), array(), (string) filemtime( get_theme_file_path( 'assets/js/home.js' ) ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	if ( is_singular( 'psi_producto' ) || is_post_type_archive( 'psi_producto' ) || is_tax( array( 'psi_categoria', 'psi_marca' ) ) ) {
		wp_enqueue_style( 'psindustrial-catalog', get_theme_file_uri( 'assets/css/catalog.css' ), array( 'psindustrial-global' ), (string) filemtime( get_theme_file_path( 'assets/css/catalog.css' ) ) );
	}
	wp_enqueue_script( 'psindustrial-navigation', get_theme_file_uri( 'assets/js/navigation.js' ), array(), (string) filemtime( get_theme_file_path( 'assets/js/navigation.js' ) ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
} );

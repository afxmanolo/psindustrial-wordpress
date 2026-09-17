<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;
add_action( 'wp_enqueue_scripts', static function() {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'psindustrial-base', get_theme_file_uri( 'assets/css/base.css' ), array(), $version );
	wp_enqueue_script( 'psindustrial-navigation', get_theme_file_uri( 'assets/js/navigation.js' ), array(), $version, array( 'in_footer' => true, 'strategy' => 'defer' ) );
} );


<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;
add_action( 'after_setup_theme', static function() {
	load_theme_textdomain( 'psindustrial', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/base.css' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array( 'primary' => __( 'Navegación principal', 'psindustrial' ) ) );
} );


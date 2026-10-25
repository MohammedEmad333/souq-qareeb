<?php
/**
 * Theme setup and assets.
 *
 * @package SouqQareeb
 */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
    load_theme_textdomain( 'souq-qareeb', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
    register_nav_menus( array( 'primary' => __( 'Primary navigation', 'souq-qareeb' ) ) );
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'souq-qareeb-style', get_stylesheet_uri(), array(), (string) filemtime( get_stylesheet_directory() . '/style.css' ) );
    wp_enqueue_script( 'souq-qareeb-menu', get_template_directory_uri() . '/assets/js/menu.js', array(), (string) filemtime( get_template_directory() . '/assets/js/menu.js' ), true );
    wp_enqueue_style( 'souq-qareeb-storefront', get_template_directory_uri() . '/assets/css/storefront.css', array( 'souq-qareeb-style' ), (string) filemtime( get_template_directory() . '/assets/css/storefront.css' ) );
} );

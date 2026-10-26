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

/**
 * Apply only user-selected, existing product terms to native WooCommerce queries.
 * Price bounds are handled by WooCommerce's own min_price/max_price support.
 */
function souq_qareeb_apply_product_filters( $query ) {
    if ( is_admin() ) {
        return;
    }
    $filters = array(
        'sq_category' => 'product_cat',
        'sq_condition' => 'pa_condition',
    );
    $tax_query = (array) $query->get( 'tax_query' );
    $added = false;

    foreach ( $filters as $parameter => $taxonomy ) {
        if ( ! taxonomy_exists( $taxonomy ) || ! isset( $_GET[ $parameter ] ) || ! is_scalar( $_GET[ $parameter ] ) ) {
            continue;
        }
        $slug = sanitize_title( wp_unslash( $_GET[ $parameter ] ) );
        if ( '' === $slug || ! term_exists( $slug, $taxonomy ) ) {
            continue;
        }
        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => array( $slug ),
        );
        $added = true;
    }
    if ( $added ) {
        $query->set( 'tax_query', $tax_query );
    }
}
add_action( 'woocommerce_product_query', 'souq_qareeb_apply_product_filters', 20 );

/* WordPress product searches may use the native search template, not is_shop(). */
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
        return;
    }
    $post_type = $query->get( 'post_type' );
    if ( 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) ) {
        souq_qareeb_apply_product_filters( $query );
    }
}, 20 );

/* Small truthful label: show a real saved product condition when one exists. */
add_action( 'woocommerce_before_shop_loop_item_title', function () {
    if ( ! taxonomy_exists( 'pa_condition' ) ) {
        return;
    }
    $terms = get_the_terms( get_the_ID(), 'pa_condition' );
    if ( $terms && ! is_wp_error( $terms ) ) {
        echo '<span class="sq-product-condition">' . esc_html( $terms[0]->name ) . '</span>';
    }
}, 20 );

/* Avoid duplicate shop titles: the custom listing header provides the heading. */
add_filter( 'woocommerce_show_page_title', function ( $show ) {
    if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
        return false;
    }
    return $show;
} );

/* Product gallery behavior powered by WooCommerce's built-in scripts. */
add_action( 'after_setup_theme', function () {
    add_theme_support( 'wc-product-gallery-slider' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-zoom' );
}, 15 );

/**
 * Display product facts only when saved taxonomy terms actually exist.
 * No invented seller ratings, locations, or item conditions.
 */
add_action( 'woocommerce_single_product_summary', function () {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return;
    }
    global $product;
    if ( ! $product instanceof WC_Product ) {
        return;
    }
    $facts = array();
    if ( taxonomy_exists( 'pa_condition' ) ) {
        $conditions = get_the_terms( $product->get_id(), 'pa_condition' );
        if ( $conditions && ! is_wp_error( $conditions ) ) {
            $facts[] = array( 'label' => 'الحالة', 'text' => $conditions[0]->name );
        }
    }
    $categories = get_the_terms( $product->get_id(), 'product_cat' );
    if ( $categories && ! is_wp_error( $categories ) ) {
        foreach ( $categories as $category ) {
            if ( (int) $category->term_id !== (int) get_option( 'default_product_cat' ) ) {
                $facts[] = array( 'label' => 'التصنيف', 'text' => $category->name );
                break;
            }
        }
    }
    if ( ! $facts ) {
        return;
    }
    echo '<dl class="sq-single-facts">';
    foreach ( $facts as $fact ) {
        echo '<div><dt>' . esc_html( $fact['label'] ) . '</dt><dd>' . esc_html( $fact['text'] ) . '</dd></div>';
    }
    echo '</dl>';
}, 17 );

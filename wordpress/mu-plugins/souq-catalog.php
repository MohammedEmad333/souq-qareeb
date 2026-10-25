<?php
/**
 * Plugin Name: Souq Qareeb Catalog
 * Description: Product deal types: sale, swap, donation.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_taxonomy('souq_deal_type', ['product'], [
        'labels' => [
            'name' => 'طرق التعامل',
            'singular_name' => 'طريقة التعامل',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'deal-type'],
    ]);
});

add_action('init', function () {
    register_taxonomy('souq_area', ['product'], [
        'labels' => [
            'name' => 'مناطق غزة',
            'singular_name' => 'المنطقة',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'area'],
    ]);
});

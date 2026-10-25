<?php
/**
 * Plugin Name: Souq Qareeb Product Details
 * Description: Display area and deal type on product pages.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_single_product_summary', function () {
    if (!is_product()) {
        return;
    }

    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }

    $id = $product->get_id();

    $areas = get_the_terms($id, 'souq_area');
    $deals = get_the_terms($id, 'souq_deal_type');

    if (
        (!$areas || is_wp_error($areas)) &&
        (!$deals || is_wp_error($deals))
    ) {
        return;
    }

    echo '<div class="souq-product-details">';

    if ($areas && !is_wp_error($areas)) {
        echo '<p><strong>المنطقة:</strong> '
            . esc_html(implode('، ', wp_list_pluck($areas, 'name')))
            . '</p>';
    }

    if ($deals && !is_wp_error($deals)) {
        echo '<p><strong>طريقة التعامل:</strong> '
            . esc_html(implode('، ', wp_list_pluck($deals, 'name')))
            . '</p>';
    }

    echo '</div>';
}, 25);

add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    wp_register_style('souq-details', false, [], '1.0.0');
    wp_enqueue_style('souq-details');

    wp_add_inline_style('souq-details', '
        .souq-product-details {
            background: #eef7f2;
            border: 1px solid #d5e9df;
            border-radius: 12px;
            padding: 12px 16px;
            margin: 18px 0;
            color: #176b55;
        }
        .souq-product-details p {
            margin: 6px 0;
        }
    ');
});

<?php
/**
 * Plugin Name: Souq Qareeb Product Location
 * Description: Display product areas on store cards.
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function souq_render_product_area($content, $block, $instance) {
    if (!function_exists('is_shop') || !is_shop()) {
        return $content;
    }

    $id = (int) (
        $instance->context['postId']
        ?? $block['attrs']['productId']
        ?? $block['attrs']['postId']
        ?? get_the_ID()
    );

    if (!$id || get_post_type($id) !== 'product') {
        return $content;
    }

    if (str_contains($content, 'souq-product-location')) {
        return $content;
    }

    $terms = get_the_terms($id, 'souq_area');

    if (!$terms || is_wp_error($terms)) {
        return $content;
    }

    $area = reset($terms);

    return $content
        . '<div class="souq-product-location">'
        . '<span>الموقع: ' . esc_html($area->name) . '</span>'
        . '</div>';
}

add_filter('render_block_core/post-title', 'souq_render_product_area', 20, 3);
add_filter('render_block_woocommerce/product-title', 'souq_render_product_area', 20, 3);

add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_shop') || !is_shop()) {
        return;
    }

    wp_register_style('souq-location', false, [], '1.1.0');
    wp_enqueue_style('souq-location');

    wp_add_inline_style('souq-location', '
        .souq-product-location {
            color: #176b55;
            background: #eef7f2;
            border-radius: 8px;
            padding: 6px 10px;
            margin: 8px auto 12px;
            font-size: 0.85rem;
            text-align: center;
            width: fit-content;
        }
    ');
});

<?php
/**
 * Plugin Name: Souq Qareeb Demo Checkout Guard
 * Description: Blocks real orders during project development.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Block Checkout Block orders before WooCommerce processes them.
add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if (
        $request->get_method() === 'POST'
        && preg_match(
            '#^/wc/store(?:/v[0-9]+)?/checkout/?$#',
            $request->get_route()
        )
    ) {
        return new WP_Error(
            'souq_demo_checkout_disabled',
            'المتجر تجريبي حاليًا ولا يستقبل طلبات شراء.',
            ['status' => 403]
        );
    }

    return $result;
}, 10, 3);

// Block traditional WooCommerce checkout as well.
add_action('woocommerce_checkout_process', function () {
    wc_add_notice(
        'المتجر تجريبي حاليًا ولا يستقبل طلبات شراء.',
        'error'
    );
});

// Show a notice above the Checkout Block.
add_filter('render_block', function ($content, $block) {
    if (($block['blockName'] ?? '') !== 'woocommerce/checkout') {
        return $content;
    }

    return '<p style="padding:14px;border-radius:8px;background:#eef7f2;color:#176b55">'
        . 'هذا متجر تجريبي لمشروع جامعي. إتمام الطلبات غير متاح حاليًا.'
        . '</p>' . $content;
}, 10, 2);

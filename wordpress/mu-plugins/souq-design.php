<?php
/**
 * Plugin Name: Souq Qareeb Design
 * Description: Storefront layout improvements.
 * Version: 2.0.0
 */
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
    wp_register_style('souq-design', false, [], '2.0.0');
    wp_enqueue_style('souq-design');

    $css = <<<'CSS'
/* Souq Qareeb - WooCommerce Product Collection */
body.woocommerce-shop .wp-block-woocommerce-product-collection
ul.wc-block-product-template {
    display: grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    gap: 24px !important;
    width: 100% !important;
}

body.woocommerce-shop ul.wc-block-product-template > li.wc-block-product {
    width: 100% !important;
    max-width: none !important;
    min-width: 0 !important;
    margin: 0 !important;
    float: none !important;
    text-align: center;
}

body.woocommerce-shop ul.wc-block-product-template
li.wc-block-product img {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    border-radius: 10px;
}

body.woocommerce-shop .wp-block-post-title,
body.woocommerce-shop .wp-block-query-title {
    line-height: 1.35;
}

@media (max-width: 700px) {
    body.woocommerce-shop .wp-block-woocommerce-product-collection
    ul.wc-block-product-template {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 14px !important;
    }

    /* Center a single product */
    body.woocommerce-shop .wp-block-woocommerce-product-collection
    ul.wc-block-product-template:has(> li:only-child) {
        grid-template-columns: minmax(0, 1fr) !important;
        max-width: 380px;
        margin-inline: auto !important;
    }

    body.woocommerce-shop .wp-site-blocks > main {
        margin-block-start: 0 !important;
        padding-block-start: 0 !important;
    }

    body.woocommerce-shop h1 {
        font-size: clamp(1.6rem, 7vw, 2rem);
        line-height: 1.3;
    }

    body.woocommerce-shop ul.wc-block-product-template
    .wp-block-button__link {
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 0.9rem;
    }
}
CSS;

    wp_add_inline_style('souq-design', $css);
}, 20);

/* Hide only the default WordPress footer credit */
add_filter('render_block', function ($content, $block) {
    if (
        ($block['blockName'] ?? '') === 'core/paragraph'
        && stripos($content, 'Proudly powered by') !== false
        && stripos($content, 'wordpress.org') !== false
    ) {
        return '';
    }
    return $content;
}, 10, 2);

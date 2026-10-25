<?php
/**
 * Plugin Name: Souq Qareeb Arabic Labels
 * Description: Arabic product metadata labels.
 * Version: 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('render_block_core/post-terms', function ($content, $block) {
    if (
        !function_exists('is_product') ||
        !is_product() ||
        ($block['attrs']['term'] ?? '') !== 'product_cat'
    ) {
        return $content;
    }

    return preg_replace(
        '/(<span\b[^>]*class="wp-block-post-terms__prefix"[^>]*>)\s*Category:\s*(<\/span>)/i',
        '$1التصنيف: $2',
        $content,
        1
    );
}, 20, 2);

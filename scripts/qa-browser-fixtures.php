<?php
/**
 * Disposable CI browser fixtures for Souq Qareeb. NOT a production or staging script.
 * Execute only in the fresh WordPress installation created by GitHub Actions.
 * SQ_QA_PHASE=prepare|complete wp eval-file scripts/qa-browser-fixtures.php --path=/tmp/wordpress
 */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WC_Product_Simple' ) ||
     ! function_exists( 'sq_configure_listing_inventory' ) ) {
    throw new RuntimeException( 'BLOCKED: test requires WordPress, WooCommerce and Souq Qareeb theme' );
}
if ( '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
    throw new RuntimeException( 'REFUSED: QA fixtures must not touch a non-loopback site' );
}

add_filter( 'pre_wp_mail', '__return_true' );

function sq_browser_qa_expect( $condition, $message ) {
    if ( ! $condition ) {
        throw new RuntimeException( 'FAIL: ' . $message );
    }
    echo 'PASS: ' . $message . PHP_EOL;
}
function sq_browser_qa_page( $key, $slug, $shortcode ) {
    $id = (int) get_option( 'woocommerce_' . $key . '_page_id', 0 );
    if ( $id && 'publish' === get_post_status( $id ) ) {
        // WooCommerce installs Cart/Checkout blocks by default. Use server-rendered
        // classic shortcodes for a deterministic browser check without JS bundles.
        wp_update_post( array( 'ID' => $id, 'post_content' => $shortcode ) );
        return $id;
    }
    $id = wp_insert_post( array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'QA ' . ucfirst( $slug ),
        'post_name' => $slug,
        'post_content' => $shortcode,
    ), true );
    sq_browser_qa_expect( ! is_wp_error( $id ) && $id > 0, 'created ' . $slug . ' page' );
    update_option( 'woocommerce_' . $key . '_page_id', $id );
    return $id;
}

$phase = getenv( 'SQ_QA_PHASE' ) ?: '';
if ( 'prepare' === $phase ) {
    update_option( 'woocommerce_manage_stock', 'yes' );
    update_option( 'woocommerce_enable_guest_checkout', 'no' );
    update_option( 'woocommerce_currency', 'ILS' );
    $pages = array(
        'shop' => sq_browser_qa_page( 'shop', 'shop', '' ),
        'cart' => sq_browser_qa_page( 'cart', 'cart', '[woocommerce_cart]' ),
        'checkout' => sq_browser_qa_page( 'checkout', 'checkout', '[woocommerce_checkout]' ),
        'myaccount' => sq_browser_qa_page( 'myaccount', 'my-account', '[woocommerce_my_account]' ),
    );
    $buyers = array();
    foreach ( array( 'a', 'b' ) as $suffix ) {
        $name = 'qa_only_buyer_' . $suffix;
        $id = wp_insert_user( array(
            'user_login' => $name,
            'user_pass' => 'CI_only_password_2026!',
            'user_email' => $name . '@example.invalid',
            'role' => 'customer',
        ) );
        sq_browser_qa_expect( ! is_wp_error( $id ), 'created synthetic buyer ' . $suffix );
        $buyers[ $suffix ] = $id;
    }
    $product = new WC_Product_Simple();
    $product->set_name( 'QA Unique Item - Browser Test' );
    $product->set_status( 'publish' );
    $product->set_regular_price( '50.00' );
    sq_configure_listing_inventory( $product, 'sell' );
    $product_id = $product->save();
    sq_browser_qa_expect( $product_id > 0, 'saved one-off sale listing' );
    update_post_meta( $product_id, '_sq_listing_type', 'sell' );
    update_option( 'sq_browser_qa_product', $product_id );
    update_option( 'sq_browser_qa_buyer_a', $buyers['a'] );

    // GitHub Actions environment file is runner-local; all fixture data is synthetic.
    $env_path = getenv( 'GITHUB_ENV' );
    sq_browser_qa_expect( $env_path && is_writable( $env_path ), 'CI environment file available' );
    file_put_contents( $env_path, sprintf(
        "QA_PRODUCT_ID=%d\nQA_PRODUCT_URL=%s\nQA_CART_URL=%s\nQA_CHECKOUT_URL=%s\nQA_ACCOUNT_URL=%s\n",
        $product_id, get_permalink( $product_id ), get_permalink( $pages['cart'] ),
        get_permalink( $pages['checkout'] ), get_permalink( $pages['myaccount'] )
    ), FILE_APPEND );
    echo "PASS: local browser fixtures ready\n";
} elseif ( 'complete' === $phase ) {
    $product_id = (int) get_option( 'sq_browser_qa_product', 0 );
    $buyer_id = (int) get_option( 'sq_browser_qa_buyer_a', 0 );
    $product = wc_get_product( $product_id );
    sq_browser_qa_expect( $product && 1 === (int) $product->get_stock_quantity(), 'one item available before simulated payment' );
    $order = wc_create_order( array( 'customer_id' => $buyer_id ) );
    sq_browser_qa_expect( ! is_wp_error( $order ), 'synthetic first-buyer WooCommerce order created' );
    $order->add_product( $product, 1 );
    $order->calculate_totals();
    $order->set_payment_method( 'qa_no_external_payment' );
    $order->save();
    $order->payment_complete( 'qa_no_external_transaction' );
    sq_browser_qa_expect( $order->is_paid(), 'synthetic first-buyer order marked paid' );
    $product = wc_get_product( $product_id );
    sq_browser_qa_expect( 0 === (int) $product->get_stock_quantity(), 'payment simulation reduces stock to zero' );
    sq_browser_qa_expect( ! $product->is_in_stock(), 'product is out of stock' );
    echo "PASS: WooCommerce stock updated for browser second-buyer check\n";
} else {
    throw new RuntimeException( 'Usage: SQ_QA_PHASE=prepare|complete' );
}

<?php
/**
 * Isolated WooCommerce integration test: one genuine WC_Product, two synthetic buyers.
 * Run only after WordPress, WooCommerce and the Souq Qareeb theme are loaded.
 * PHP assertions here test WooCommerce's order/stock logic, not Stripe or a browser checkout.
 */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WC_Product_Simple' ) ||
     ! function_exists( 'sq_configure_listing_inventory' ) || ! function_exists( 'wc_create_order' ) ) {
    fwrite( STDERR, "BLOCKED: WordPress, WooCommerce and the Souq Qareeb theme must be active.\n" );
    exit( 1 );
}

function sq_accept( $condition, $message ) {
    if ( ! $condition ) {
        throw new RuntimeException( 'FAIL: ' . $message );
    }
    echo 'PASS: ' . $message . PHP_EOL;
}

// No outgoing email, real customer data, real payments or webhook calls.
require_once ABSPATH . 'wp-admin/includes/user.php';
add_filter( 'pre_wp_mail', '__return_true' );
update_option( 'woocommerce_manage_stock', 'yes' );
update_option( 'woocommerce_hold_stock_minutes', '' );

$run_id = 'sq-acceptance-' . wp_generate_password( 12, false, false );
$users = array();
$product_ids = array();
$order_ids = array();

try {
    foreach ( array( 'buyer-a', 'buyer-b' ) as $name ) {
        $user_id = wp_insert_user( array(
            'user_login' => $run_id . '-' . $name,
            'user_pass' => wp_generate_password( 32, true, true ),
            'user_email' => $run_id . '-' . $name . '@example.invalid',
            'role' => 'customer',
        ) );
        sq_accept( ! is_wp_error( $user_id ), 'created isolated synthetic ' . $name );
        $users[] = $user_id;
    }

    $product = new WC_Product_Simple();
    $product->set_name( 'QA one-off listing ' . $run_id );
    $product->set_status( 'publish' );
    $product->set_regular_price( '50.00' );
    sq_configure_listing_inventory( $product, 'sell' );
    $product_id = $product->save();
    sq_accept( $product_id > 0, 'published synthetic one-off listing' );
    $product_ids[] = $product_id;
    update_post_meta( $product_id, '_sq_listing_type', 'sell' );

    $product = wc_get_product( $product_id );
    sq_accept( $product->managing_stock(), 'sale: WooCommerce stock management enabled' );
    sq_accept( 1 === (int) $product->get_stock_quantity(), 'sale: initial stock is one' );
    sq_accept( ! $product->backorders_allowed(), 'sale: backorders disabled' );
    sq_accept( $product->is_in_stock(), 'sale: initially in stock' );

    // Buyer A places a real WooCommerce order but no external payment is processed.
    wp_set_current_user( $users[0] );
    $first = wc_create_order( array( 'customer_id' => $users[0] ) );
    sq_accept( ! is_wp_error( $first ), 'buyer A: created WooCommerce order' );
    $order_ids[] = $first->get_id();
    $first->add_product( $product, 1 );
    $first->calculate_totals();
    $first->set_payment_method( 'qa_simulated' );
    $first->save();
    sq_accept( (float) $first->get_total() === 50.0, 'buyer A: order total is correct' );
    $first->payment_complete( 'synthetic-transaction-' . $run_id );

    sq_accept( $first->is_paid(), 'buyer A: simulated completion marked paid' );
    $product = wc_get_product( $product_id );
    sq_accept( 0 === (int) $product->get_stock_quantity(), 'buyer A: WooCommerce reduced stock to zero' );
    sq_accept( ! $product->is_in_stock(), 'buyer A: listing now out of stock' );
    sq_accept( ! $product->backorders_allowed(), 'buyer A: backorders still disabled' );

    // Buyer B must not be able to add the exhausted item to a real WC cart.
    wp_set_current_user( $users[1] );
    if ( ! did_action( 'woocommerce_init' ) ) {
        do_action( 'woocommerce_init' );
    }
    wc_load_cart();
    WC()->cart->empty_cart();
    $added = WC()->cart->add_to_cart( $product_id, 1 );
    sq_accept( ! $added, 'buyer B: adding exhausted listing to cart is rejected' );
    sq_accept( 0 === WC()->cart->get_cart_contents_count(), 'buyer B: cart remains empty' );

    // A cancellation returns exactly one item to stock.
    $first->update_status( 'cancelled', 'QA cancellation and stock restoration' );
    $product = wc_get_product( $product_id );
    sq_accept( 1 === (int) $product->get_stock_quantity(), 'cancellation restores one item' );
    sq_accept( $product->is_in_stock(), 'cancellation makes listing available again' );

    // Swap/donation must remain non-purchasable regardless of their zero price.
    foreach ( array( 'swap', 'donate' ) as $type ) {
        $offer = new WC_Product_Simple();
        $offer->set_name( 'QA ' . $type . ' ' . $run_id );
        $offer->set_status( 'publish' );
        $offer->set_regular_price( '0' );
        sq_configure_listing_inventory( $offer, $type );
        $offer_id = $offer->save();
        $product_ids[] = $offer_id;
        update_post_meta( $offer_id, '_sq_listing_type', $type );
        $offer = wc_get_product( $offer_id );
        sq_accept( ! $offer->managing_stock(), $type . ': stock management remains disabled' );
        sq_accept( ! $offer->is_purchasable(), $type . ': product cannot be purchased' );
    }

    echo "PASS: isolated WooCommerce sale acceptance suite complete.\n";
} finally {
    if ( function_exists( 'WC' ) && WC()->cart ) {
        WC()->cart->empty_cart();
    }
    foreach ( $order_ids as $id ) {
        $order = wc_get_order( $id );
        if ( $order ) {
            $order->delete( true );
        }
    }
    foreach ( $product_ids as $id ) {
        wp_delete_post( $id, true );
    }
    foreach ( $users as $id ) {
        wp_delete_user( $id );
    }
}

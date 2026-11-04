<?php
/**
 * Plugin Name: Souq Qareeb Demo Checkout Guard
 * Description: Allows Stripe sandbox checkout for signed-in customers and managers; blocks guests and live payments.
 * Version: 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fail closed unless a logged-in account uses the active Stripe gateway
 * and WooCommerce's official Stripe plugin has test mode explicitly enabled.
 * The Stripe account itself may still reject test payments (e.g. restricted test account).
 */
function souq_demo_checkout_test_ready() {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $settings = get_option( 'woocommerce_stripe_settings', array() );
    if ( ! is_array( $settings )
        || 'yes' !== ( $settings['enabled'] ?? '' )
        || 'yes' !== ( $settings['testmode'] ?? '' ) ) {
        return false;
    }

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->payment_gateways() ) {
        return false;
    }

    $gateways = WC()->payment_gateways()->payment_gateways();
    return is_array( $gateways )
        && isset( $gateways['stripe'] )
        && 'yes' === $gateways['stripe']->enabled;
}

function souq_demo_checkout_permitted( $gateway_id ) {
    return 'stripe' === $gateway_id && souq_demo_checkout_test_ready();
}

function souq_demo_checkout_blocked_message() {
    return 'المتجر تجريبي حاليًا. يُسمح بإتمام طلبات Stripe الاختبارية للحسابات المسجلة فقط، عند تفعيل Test Mode.';
}

/*
 * Hide all payment gateways from guests. Signed-in accounts can see only the core
 * "stripe" gateway while test mode is active. This also covers checkout blocks
 * where supported by WooCommerce.
 */
add_filter( 'woocommerce_available_payment_gateways', function ( $gateways ) {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return $gateways; // Do not interfere with admin payment settings.
    }
    if ( ! is_array( $gateways )
        || ! souq_demo_checkout_test_ready()
        || ! isset( $gateways['stripe'] ) ) {
        return array();
    }
    return array( 'stripe' => $gateways['stripe'] );
}, 999 );

/*
 * Protect both block checkout paths:
 * POST /wc/store/v1/checkout and POST /wc/store/v1/checkout/{order_id}.
 * A WP Store API nonce alone is not sufficient: require sign-in and Stripe test mode.
 * Never interfere with webhooks, GET requests or unrelated REST endpoints.
 */
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
    if ( null !== $result || ! $request instanceof WP_REST_Request
        || 'POST' !== $request->get_method()
        || ! preg_match( '#^/wc/store(?:/v[0-9]+)?/checkout(?:/[0-9]+)?/?$#', $request->get_route() ) ) {
        return $result;
    }

    $payment_method = $request->get_param( 'payment_method' );
    if ( is_string( $payment_method ) && souq_demo_checkout_permitted( $payment_method ) ) {
        return $result;
    }

    return new WP_Error(
        'souq_demo_checkout_disabled',
        souq_demo_checkout_blocked_message(),
        array( 'status' => 403 )
    );
}, 10, 3 );

/* Classic checkout posts must pass the same Stripe-only sandbox gate. */
add_action( 'woocommerce_checkout_process', function () {
    $method = isset( $_POST['payment_method'] ) && is_scalar( $_POST['payment_method'] )
        ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : '';
    if ( ! souq_demo_checkout_permitted( $method ) ) {
        wc_add_notice( souq_demo_checkout_blocked_message(), 'error' );
    }
} );

/* Existing order-pay links must not bypass the sandbox gate. */
add_action( 'woocommerce_before_pay_action', function ( $order ) {
    $method = isset( $_POST['payment_method'] ) && is_scalar( $_POST['payment_method'] )
        ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : '';
    if ( ! souq_demo_checkout_permitted( $method ) ) {
        wp_die( souq_demo_checkout_blocked_message(), '', array( 'response' => 403 ) );
    }
} );

/* Clearly label the public checkout as a non-purchasable academic demo. */
add_filter( 'render_block', function ( $content, $block ) {
    if ( ( $block['blockName'] ?? '' ) !== 'woocommerce/checkout' ) {
        return $content;
    }

    $notice = souq_demo_checkout_test_ready()
        ? 'وضع اختبار Stripe نشط للحسابات المسجلة. لا تستخدم بيانات بطاقة حقيقية؛ لن يتم تحصيل أموال.'
        : 'الدفع غير متاح حاليًا. يلزم تسجيل الدخول وتفعيل Stripe في وضع الاختبار.';

    return '<p style="padding:14px;border-radius:8px;background:#eef7f2;color:#176b55">'
        . esc_html( $notice ) . '</p>' . $content;
}, 10, 2 );

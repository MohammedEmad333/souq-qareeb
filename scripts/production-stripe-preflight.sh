#!/usr/bin/env bash
# Read-only Stripe TEST readiness report from an existing WordPress Docker container.
# Does not send emails, create users/orders, submit payments or expose credentials.
# Run on the production host: bash scripts/production-stripe-preflight.sh
set -euo pipefail

wp_container="${SOUQ_WP_CONTAINER:-souq-qareeb-wordpress-1}"
if ! command -v docker >/dev/null 2>&1; then
  echo 'BLOCKED: docker is unavailable' >&2
  exit 2
fi
if ! docker inspect --format '{{.State.Running}}' "$wp_container" 2>/dev/null | grep -qx true; then
  echo 'BLOCKED: WordPress container is not running or not accessible' >&2
  exit 2
fi

docker exec -i -u www-data "$wp_container" php <<'PHP'
<?php
// Loading WordPress is observational; do not perform any explicit writes.
define( 'DISABLE_WP_CRON', true );
require '/var/www/html/wp-load.php';

$failure_count = 0;
function sq_prod_preflight( $label, $ok ) {
    global $failure_count;
    echo ( $ok ? 'PASS: ' : 'BLOCKED: ' ) . $label . PHP_EOL;
    if ( ! $ok ) {
        $failure_count++;
    }
}

sq_prod_preflight( 'Souq Qareeb theme active', 'souq-qareeb' === get_stylesheet() );
sq_prod_preflight( 'WooCommerce loaded', class_exists( 'WooCommerce' ) );
sq_prod_preflight( 'One-off inventory fix loaded', function_exists( 'sq_configure_listing_inventory' ) );
sq_prod_preflight( 'Global WooCommerce stock management enabled', 'yes' === get_option( 'woocommerce_manage_stock' ) );
sq_prod_preflight( 'Demo checkout guard loaded', function_exists( 'souq_demo_checkout_permitted' ) );

$settings = get_option( 'woocommerce_stripe_settings', array() );
if ( ! is_array( $settings ) ) {
    $settings = array();
}
sq_prod_preflight( 'Stripe configured as enabled', 'yes' === ( $settings['enabled'] ?? '' ) );
sq_prod_preflight( 'Stripe TEST MODE enabled (not live)', 'yes' === ( $settings['testmode'] ?? '' ) );

$gateway_ready = false;
if ( function_exists( 'WC' ) && WC() && WC()->payment_gateways() ) {
    $gateways = WC()->payment_gateways()->payment_gateways();
    $gateway_ready = is_array( $gateways ) && isset( $gateways['stripe'] )
        && 'yes' === $gateways['stripe']->enabled;
}
sq_prod_preflight( 'WooCommerce stripe gateway available', $gateway_ready );

echo 'PAYMENTS: NOT ATTEMPTED; no API keys, order IDs, customer data or transactions displayed.' . PHP_EOL;
echo 'LIMIT: test Stripe checkout and webhooks remain unverified.' . PHP_EOL;
echo 'SUMMARY: blocked=' . $failure_count . PHP_EOL;
exit( $failure_count > 0 ? 1 : 0 );
PHP

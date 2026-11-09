<?php
/** Standalone regression checks; no Stripe keys, requests or payments. */
define( 'ABSPATH', __DIR__ );
$registered_filters = array();
function add_filter( $tag, $callback ) {
    global $registered_filters;
    $registered_filters[ $tag ] = $callback;
}
function get_locale() {
    global $test_locale;
    return $test_locale;
}
require __DIR__ . '/../wordpress/themes/souq-qareeb/inc/checkout-i18n.php';
if ( array_keys( $registered_filters ) !== array( 'wc_stripe_save_to_account_text' ) ) {
    throw new RuntimeException( 'Only the Stripe copy filter should be registered.' );
}
$callback = $registered_filters['wc_stripe_save_to_account_text'];
$arabic = 'حفظ بيانات الدفع في حسابي لاستخدامها في عمليات الشراء القادمة.';
$original = 'Save payment information to my account for future purchases.';
foreach ( array( 'ar', 'ar_PS', 'ar_EG', 'ar_SA' ) as $test_locale ) {
    if ( $callback( $original ) !== $arabic ) {
        throw new RuntimeException( 'Missing Arabic copy for ' . $test_locale );
    }
}
foreach ( array( 'en_US', 'fr_FR', 'fa_IR', 'arc' ) as $test_locale ) {
    if ( $callback( 'Existing translated or customized label' ) !== 'Existing translated or customized label' ) {
        throw new RuntimeException( 'Non-Arabic copy was overwritten for ' . $test_locale );
    }
}
echo "PASS: Stripe copy hook and 8 locale cases\n";

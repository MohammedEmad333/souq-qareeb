<?php
/** Render the real extension's checkbox in disposable loopback WordPress only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true ) ) {
    throw new RuntimeException( 'Disposable loopback WP-CLI fixture only.' );
}
if ( ! class_exists( 'WC_Stripe_Payment_Gateway' ) ) {
    throw new RuntimeException( 'The official Stripe extension must be active.' );
}
// Skip gateway configuration/network setup; exercise its actual inherited renderer.
$gateway = new class() extends WC_Stripe_Payment_Gateway {
    public function __construct() {
        $this->id = 'stripe';
    }
};
add_filter( 'locale', static function () { return 'ar'; } );
ob_start();
$gateway->save_payment_method_checkbox();
$arabic = ob_get_clean();
if ( false === strpos( $arabic, 'حفظ بيانات الدفع في حسابي' ) || preg_match( '/<input[^>]+\bchecked\b/', $arabic ) ) {
    throw new RuntimeException( 'Arabic copy or default opt-in regression.' );
}
remove_filter( 'wc_stripe_save_to_account_text', 'souq_qareeb_stripe_save_to_account_text' );
ob_start();
$gateway->save_payment_method_checkbox();
$before = ob_get_clean();
add_filter( 'wc_stripe_save_to_account_text', 'souq_qareeb_stripe_save_to_account_text' );
// A standalone fixture, without a payment form, customer fields or Stripe.js.
echo '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>اختبار تعريب خيار Stripe</title>';
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<link rel="stylesheet" href="wp-content/themes/souq-qareeb/style.css">';
echo '<body><main style="max-width:640px;margin:32px auto;padding:16px"><h1>اختبار تعريب خيار Stripe</h1>';
echo '<p>معاينة محلية لخيار الحفظ من إضافة Stripe؛ لا يوجد نموذج دفع أو بيانات عميل.</p>';
echo '<section id="before"><h2>قبل الإصلاح</h2>' . str_replace( 'wc-stripe-new-payment-method', 'qa-before-save', $before ) . '</section>';
echo '<section id="after"><h2>بعد الإصلاح</h2>' . $arabic . '</section></main></body></html>';

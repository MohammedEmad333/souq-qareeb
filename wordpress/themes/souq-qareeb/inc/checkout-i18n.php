<?php
/** Arabic copy for the Stripe extension's own save-card control. */
defined( 'ABSPATH' ) || exit;

/**
 * Use Stripe's public text filter so its label association, opt-in state and
 * payment processing remain owned by the extension (including AJAX refreshes).
 */
function souq_qareeb_stripe_save_to_account_text( $text ) {
    $locale = get_locale();
    if ( 'ar' === $locale || 0 === strpos( $locale, 'ar_' ) ) {
        return 'حفظ بيانات الدفع في حسابي لاستخدامها في عمليات الشراء القادمة.';
    }
    return $text;
}
add_filter( 'wc_stripe_save_to_account_text', 'souq_qareeb_stripe_save_to_account_text' );

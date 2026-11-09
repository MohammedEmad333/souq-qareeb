<?php
/** Run without WordPress: international normalization and public contact links. */
define( 'ABSPATH', __DIR__ . '/' );
$hooks = array();
function add_action( $hook, $callback, $priority = 10 ) { global $hooks; $hooks[ $hook ][ $priority ] = $callback; }
function add_filter( ...$args ) {}
function is_product() { return true; }
function get_post_meta( $id, $key, $single = true ) { global $meta; return $meta[ $key ] ?? ''; }
function get_post_status( $id ) { global $status; return $status; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $value ) { return esc_html( $value ); }
class WC_Product { public function get_id() { return 42; } }
require __DIR__ . '/../wordpress/themes/souq-qareeb/inc/contact-phone.php';
require __DIR__ . '/../wordpress/themes/souq-qareeb/inc/listings.php';
function expect_same( $expected, $actual, $label ) {
    if ( $expected !== $actual ) { throw new RuntimeException( $label . ': ' . var_export( $actual, true ) ); }
}
$cases = array(
    array( '+970 59-123-4567', '970591234567' ),
    array( '00970591234567', '970591234567' ),
    array( '970591234567', '970591234567' ),
    array( '+٩٧٠٥٩١٢٣٤٥٦٧', '970591234567' ),
    array( '+۹۷۰۵۹۱۲۳۴۵۶۷', '970591234567' ),
    array( ' +(970) 59 1234567 ', '970591234567' ),
    array( '123456789012345', '123456789012345' ),
    array( '0591234567', '' ), array( '+0591234567', '' ),
    array( '0000000000', '' ), array( '12345678', '' ),
    array( '1234567890123456', '' ), array( '+', '' ), array( '', '' ),
    array( array( '970591234567' ), '' ), array( null, '' ),
    array( '970foo591234567', '' ), array( '970591234567;ext=1', '' ),
    array( '++970591234567', '' ), array( '970+591234567', '' ),
    array( 'https://wa.me/970591234567', '' ),
);
foreach ( $cases as $i => $case ) {
    expect_same( $case[1], sq_normalize_contact_phone( $case[0] ), 'normalization case ' . $i );
}
$product = new WC_Product();
$status = 'publish';
$meta = array( '_sq_listing_type' => 'sell', '_sq_item_city' => 'غزة', '_sq_contact_method' => 'phone' );
foreach ( array( '0591234567', '+0591234567', '0000000000', '970abc591234567' ) as $bad ) {
    $meta['_sq_contact_phone'] = $bad;
    ob_start(); $hooks['woocommerce_single_product_summary'][22](); $html = ob_get_clean();
    expect_same( false, strpos( $html, 'href=' ), 'invalid saved phone must not create a link' );
}
$meta['_sq_contact_phone'] = '+970 591234567';
ob_start(); $hooks['woocommerce_single_product_summary'][22](); $html = ob_get_clean();
expect_same( true, false !== strpos( $html, 'href="tel:+970591234567"' ), 'canonical tel URI' );
$meta['_sq_contact_method'] = 'whatsapp';
ob_start(); $hooks['woocommerce_single_product_summary'][22](); $html = ob_get_clean();
expect_same( true, false !== strpos( $html, 'href="https://wa.me/970591234567"' ), 'canonical WhatsApp URI' );
$status = 'pending';
ob_start(); $hooks['woocommerce_single_product_summary'][22](); $html = ob_get_clean();
expect_same( false, strpos( $html, 'href=' ), 'unpublished contact is hidden' );
echo "Contact phone checks passed (21 normalization cases; invalid, valid and unpublished link rendering).\n";

<?php
/** Real moderation callback, with a minimal WordPress capability/nonce harness. */
define( 'ABSPATH', __DIR__ . '/' );
$hooks = array();
$caps = array();
$nonce_valid = true;
$updates = 0;
function add_action( $hook, $callback, ...$args ) { global $hooks; $hooks[ $hook ] = $callback; }
function current_user_can( $cap, ...$args ) { global $caps; return in_array( $cap, $caps, true ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_unslash( $value ) { return $value; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
function wp_die( $message, $title = '', $args = array() ) { throw new RuntimeException( 'denied', $args['response'] ?? 403 ); }
function check_admin_referer( ...$args ) { global $nonce_valid; if ( ! $nonce_valid ) { wp_die( 'nonce' ); } }
function get_post( $id ) { global $post; return $post; }
function get_post_meta( ...$args ) { return 'sell'; }
function wp_update_post( $data, $error = false ) { global $updates; ++$updates; return $data['ID']; }
function is_wp_error( $result ) { return false; }
function admin_url( $path ) { return '/wp-admin/' . $path; }
function add_query_arg( $args, $url ) { return $url; }
function wp_safe_redirect( $url ) { throw new RuntimeException( 'redirect', 302 ); }
require __DIR__ . '/../wordpress/themes/souq-qareeb/inc/admin-dashboard.php';
$post = (object) array( 'post_type' => 'product', 'post_status' => 'pending' );
$cases = array(
    array( array(), 'approve', true, 403, 0 ),
    array( array( 'edit_products', 'edit_post' ), 'approve', true, 403, 0 ),
    array( array( 'edit_products', 'publish_products' ), 'approve', true, 403, 0 ),
    array( array( 'edit_products', 'edit_post', 'publish_products' ), 'approve', false, 403, 0 ),
    array( array( 'edit_products', 'edit_post', 'publish_products' ), 'approve', true, 302, 1 ),
    array( array( 'edit_products', 'edit_post' ), 'reject', true, 302, 1 ),
    array( array( 'edit_products', 'edit_post', 'publish_products' ), 'invalid', true, 403, 0 ),
);
foreach ( $cases as $i => $case ) {
    list( $caps, $decision, $nonce_valid, $expected_status, $expected_updates ) = $case;
    $_POST = array( 'listing_id' => '42', 'decision' => $decision );
    $updates = 0;
    try { $hooks['admin_post_sq_moderate_listing'](); throw new LogicException( 'No response' ); }
    catch ( RuntimeException $e ) {
        if ( $expected_status !== $e->getCode() || $expected_updates !== $updates ) {
            throw new LogicException( 'Moderation regression in case ' . $i );
        }
    }
}
echo "Moderation permission checks passed (7 capability/nonce cases).\n";

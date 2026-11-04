<?php
/**
 * Dependency-free behavioral tests for the fail-closed checkout gate.
 * Run: php scripts/test-demo-checkout-guard.php
 */
define( 'ABSPATH', '/' );
$GLOBALS['sq_hooks'] = array();
$GLOBALS['sq_user_logged_in'] = true;
$GLOBALS['sq_manager'] = false;
$GLOBALS['sq_settings'] = array( 'enabled' => 'yes', 'testmode' => 'yes' );
$GLOBALS['sq_gateways'] = array(
    'stripe' => (object) array( 'enabled' => 'yes' ),
    'stripe_link' => (object) array( 'enabled' => 'yes' ),
    'cod' => (object) array( 'enabled' => 'no' ),
);
$GLOBALS['sq_notices'] = array();
function add_filter( $name, $callback, $priority = 10, $args = 1 ) { $GLOBALS['sq_hooks'][ $name ] = $callback; }
function add_action( $name, $callback, $priority = 10, $args = 1 ) { $GLOBALS['sq_hooks'][ $name ] = $callback; }
function get_option( $name, $default = null ) { return 'woocommerce_stripe_settings' === $name ? $GLOBALS['sq_settings'] : $default; }
function current_user_can( $cap ) { return 'manage_woocommerce' === $cap && $GLOBALS['sq_manager']; }
function is_user_logged_in() { return $GLOBALS['sq_user_logged_in']; }
function is_admin() { return false; }
function wp_doing_ajax() { return false; }
function wp_unslash( $s ) { return $s; }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\\-]/', '', strtolower( $s ) ); }
function esc_html( $s ) { return htmlspecialchars( $s, ENT_QUOTES, 'UTF-8' ); }
function wc_add_notice( $s, $type ) { $GLOBALS['sq_notices'][] = array( $s, $type ); }
function wp_die( $s, $title = '', $args = array() ) { throw new RuntimeException( $s, $args['response'] ?? 0 ); }
class SQ_Mock_Gateways {
    public function payment_gateways() { return $GLOBALS['sq_gateways']; }
}
class SQ_Mock_WC {
    public function payment_gateways() { return new SQ_Mock_Gateways(); }
}
function WC() { return new SQ_Mock_WC(); }
class WP_Error {
    public $code;
    public function __construct( $code, $message, $data ) { $this->code = $code; }
}
class WP_REST_Request {
    private $method;
    private $route;
    private $params;
    public function __construct( $method, $route, $params = array() ) {
        $this->method = $method;
        $this->route = $route;
        $this->params = $params;
    }
    public function get_method() { return $this->method; }
    public function get_route() { return $this->route; }
    public function get_param( $key ) { return $this->params[ $key ] ?? null; }
}

require __DIR__ . '/../wordpress/mu-plugins/souq-demo-checkout.php';

function sq_expect( $condition, $label ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$label}\\n" );
        exit( 1 );
    }
    echo "PASS: {$label}\\n";
}

sq_expect( souq_demo_checkout_permitted( 'stripe' ), 'customer + enabled Stripe test mode allowed' );
sq_expect( ! souq_demo_checkout_permitted( 'stripe_link' ), 'Stripe Link not permitted as separate gateway' );
$available = $GLOBALS['sq_hooks']['woocommerce_available_payment_gateways']( $GLOBALS['sq_gateways'] );
sq_expect( array_keys( $available ) === array( 'stripe' ), 'only Stripe gateway appears for signed-in customer' );

$rest = $GLOBALS['sq_hooks']['rest_pre_dispatch'];
$endpoint = new WP_REST_Request( 'POST', '/wc/store/v1/checkout', array( 'payment_method' => 'stripe' ) );
sq_expect( null === $rest( null, null, $endpoint ), 'checkout block Stripe request passes in test mode' );
$endpoint_order = new WP_REST_Request( 'POST', '/wc/store/v1/checkout/123', array( 'payment_method' => 'stripe' ) );
sq_expect( null === $rest( null, null, $endpoint_order ), 'existing order Store API route covered' );
$bad_method = new WP_REST_Request( 'POST', '/wc/store/v1/checkout', array( 'payment_method' => 'stripe_link' ) );
sq_expect( $rest( null, null, $bad_method ) instanceof WP_Error, 'non-Stripe Store API gateway denied' );
sq_expect( null === $rest( null, null, new WP_REST_Request( 'POST', '/other-webhook', array() ) ), 'unrelated webhooks untouched' );
sq_expect( null === $rest( null, null, new WP_REST_Request( 'GET', '/wc/store/v1/checkout', array() ) ), 'checkout GET untouched' );

$GLOBALS['sq_user_logged_in'] = false;
sq_expect( ! souq_demo_checkout_permitted( 'stripe' ), 'guests denied' );
sq_expect( $rest( null, null, $endpoint ) instanceof WP_Error, 'guest Store API POST denied' );
$_POST['payment_method'] = 'stripe';
$GLOBALS['sq_hooks']['woocommerce_checkout_process']();
sq_expect( count( $GLOBALS['sq_notices'] ) > 0, 'guest classic checkout denied' );
$GLOBALS['sq_notices'] = array();
sq_expect( array() === $GLOBALS['sq_hooks']['woocommerce_available_payment_gateways']( $GLOBALS['sq_gateways'] ), 'guest cannot see gateways' );
$GLOBALS['sq_user_logged_in'] = true;

sq_expect( souq_demo_checkout_permitted( 'stripe' ), 'customer without manager capability allowed' );
sq_expect( null === $rest( null, null, $endpoint ), 'customer Store API POST allowed' );
$_POST['payment_method'] = 'stripe';
$GLOBALS['sq_hooks']['woocommerce_checkout_process']();
sq_expect( 0 === count( $GLOBALS['sq_notices'] ), 'customer classic Stripe checkout allowed' );
$GLOBALS['sq_manager'] = true;
sq_expect( souq_demo_checkout_permitted( 'stripe' ), 'shop manager remains allowed' );
$GLOBALS['sq_manager'] = false;
$GLOBALS['sq_settings']['testmode'] = 'no';
sq_expect( ! souq_demo_checkout_permitted( 'stripe' ), 'live mode blocked automatically' );
sq_expect( $rest( null, null, $endpoint ) instanceof WP_Error, 'live-mode block checkout POST denied' );
$GLOBALS['sq_hooks']['woocommerce_checkout_process']();
sq_expect( count( $GLOBALS['sq_notices'] ) > 0, 'classic checkout also blocked in live mode' );

$GLOBALS['sq_settings']['testmode'] = 'yes';
$GLOBALS['sq_gateways']['stripe']->enabled = 'no';
sq_expect( ! souq_demo_checkout_permitted( 'stripe' ), 'disabled Stripe gateway denied' );
$GLOBALS['sq_gateways']['stripe']->enabled = 'yes';

$_POST['payment_method'] = 'stripe_link';
try {
    $GLOBALS['sq_hooks']['woocommerce_before_pay_action']( null );
    sq_expect( false, 'order-pay non-Stripe route denied' );
} catch ( RuntimeException $e ) {
    sq_expect( 403 === $e->getCode(), 'order-pay non-Stripe route denied' );
}

echo "All sandbox guard tests passed.\\n";

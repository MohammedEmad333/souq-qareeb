<?php
/**
 * Dependency-free regression coverage for one-off community listing inventory.
 * Run: php scripts/test-listing-inventory.php
 */
define( 'ABSPATH', __DIR__ . '/../' );
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}

class SQ_Inventory_Test_Product {
    public $sold_individually = false;
    public $manage_stock = false;
    public $stock_quantity = null;
    public $backorders = null;
    public function set_sold_individually( $value ) { $this->sold_individually = $value; }
    public function set_manage_stock( $value ) { $this->manage_stock = $value; }
    public function set_stock_quantity( $value ) { $this->stock_quantity = $value; }
    public function set_backorders( $value ) { $this->backorders = $value; }
}
function sq_inventory_expect( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
    echo "PASS: {$message}\n";
}

require __DIR__ . '/../wordpress/themes/souq-qareeb/inc/listings.php';

$sale = new SQ_Inventory_Test_Product();
sq_configure_listing_inventory( $sale, 'sell' );
sq_inventory_expect( true === $sale->sold_individually, 'sale: sold individually' );
sq_inventory_expect( true === $sale->manage_stock, 'sale: managed stock enabled' );
sq_inventory_expect( 1 === $sale->stock_quantity, 'sale: exactly one item in stock' );
sq_inventory_expect( 'no' === $sale->backorders, 'sale: backorders disabled' );

foreach ( array( 'swap', 'donate' ) as $type ) {
    $listing = new SQ_Inventory_Test_Product();
    sq_configure_listing_inventory( $listing, $type );
    sq_inventory_expect( true === $listing->sold_individually, "{$type}: sold individually" );
    sq_inventory_expect( false === $listing->manage_stock, "{$type}: tracked stock not enabled" );
    sq_inventory_expect( null === $listing->stock_quantity, "{$type}: stock unchanged" );
    sq_inventory_expect( null === $listing->backorders, "{$type}: backorders unchanged" );
}
echo "All community listing inventory tests passed.\n";

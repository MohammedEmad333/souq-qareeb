<?php
/** Regression checks for optional storefront price bounds. */
define('ABSPATH', __DIR__);
function add_action(...$args) {}
function is_admin() { return false; }
require __DIR__ . '/../wordpress/themes/souq-qareeb/inc/catalog-filters.php';
class CatalogQuery {
    public function is_main_query() { return true; }
    public function is_search() { return true; }
    public function get($key) { return 'product'; }
    public function is_post_type_archive($type) { return true; }
    public function is_tax($types) { return false; }
}
foreach ([
    [['min_price'=>'','max_price'=>''], []],
    [['max_price'=>'0'], ['max_price'=>'0']],
    [['min_price'=>'35','max_price'=>'45'], ['min_price'=>'35','max_price'=>'45']],
    [['max_price'=>'  '], []],
    [['max_price'=>['bad']], []],
] as [$input, $expected]) {
    $_GET = $input;
    sq_normalize_optional_price_filters(new CatalogQuery());
    if ($_GET !== $expected) { fwrite(STDERR, "Price filter regression\n"); exit(1); }
}
echo "Optional price filter checks passed\n";

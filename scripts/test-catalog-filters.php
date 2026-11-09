<?php
/** Regression checks for optional storefront price bounds. */
define('ABSPATH', __DIR__);
function add_action(...$args) {}
function is_admin() { return false; }
function is_product_category() { global $category_archive; return $category_archive; }
function get_queried_object() { global $queried_term; return $queried_term; }
function wp_unslash($value) { return $value; }
function sanitize_title($value) { return strtolower(trim((string) $value)); }
class WP_Term {
    public $taxonomy = 'product_cat';
    public $slug = 'furniture';
}
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

$queried_term = new WP_Term();
foreach ([
    [true, [], 'furniture'],
    [false, [], ''],
    [true, ['sq_category'=>'school'], 'school'],
    [true, ['sq_category'=>''], ''],
    [true, ['sq_category'=>['bad']], ''],
] as [$category_archive, $input, $expected]) {
    $_GET = $input;
    if (sq_selected_catalog_category() !== $expected) {
        throw new RuntimeException('Category selection regression');
    }
}
$category_archive = true;
$_GET = [];
$queried_term->taxonomy = 'product_tag';
if (sq_selected_catalog_category() !== '') {
    throw new RuntimeException('Non-category term selected as category');
}
echo "Category archive selection checks passed (6 cases)\n";

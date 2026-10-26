<?php
/**
 * WooCommerce content wrapper: keep native product loops, details and cart.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$is_listing = function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() );
$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : -1;
$shop_url = $shop_id > 0 ? get_permalink( $shop_id ) : home_url( '/' );
$get_filter = static function ( $key ) {
    return isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
};
$cats = array();
$conditions = array();
if ( $is_listing && taxonomy_exists( 'product_cat' ) ) {
    $cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'name', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
    if ( is_wp_error( $cats ) ) { $cats = array(); }
}
if ( $is_listing && taxonomy_exists( 'pa_condition' ) ) {
    $conditions = get_terms( array( 'taxonomy' => 'pa_condition', 'hide_empty' => true, 'orderby' => 'name' ) );
    if ( is_wp_error( $conditions ) ) { $conditions = array(); }
}
?>
<main id="main" class="sq-container sq-main sq-shop <?php echo $is_listing ? 'sq-shop--listing' : 'sq-shop--detail'; ?>" dir="rtl">
<?php if ( $is_listing ) : ?>
  <header class="sq-shop-intro">
    <span class="sq-shop-eyebrow">سوق قريب — أغراض مستعملة، فرص جديدة</span>
    <h1><?php echo esc_html( is_product_category() || is_product_tag() ? single_term_title( '', false ) : 'المنتجات المستعملة' ); ?></h1>
    <p>اكتشف المنتجات المعروضة في مجتمع سوق قريب، واختر ما يناسب احتياجاتك وميزانيتك.</p>
  </header>
  <form class="sq-shop-filters" action="<?php echo esc_url( $shop_url ); ?>" method="get" role="search" aria-label="البحث وتصفية المنتجات">
    <div class="sq-shop-search-row">
      <label class="screen-reader-text" for="sq-listing-search">ابحث عن منتج مستعمل</label>
      <input type="search" id="sq-listing-search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="ابحث عن منتج مستعمل...">
      <input type="hidden" name="post_type" value="product">
      <button type="submit" class="sq-shop-search-submit">بحث</button>
    </div>
    <div class="sq-shop-filter-grid">
      <label class="sq-shop-filter-field" for="sq-listing-category"><span>التصنيف</span>
        <select id="sq-listing-category" name="sq_category">
          <option value="">كل التصنيفات</option>
          <?php foreach ( $cats as $cat ) : ?>
            <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( sanitize_title( $get_filter( 'sq_category' ) ), $cat->slug ); ?>><?php echo esc_html( $cat->name ); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ( $conditions ) : ?>
      <label class="sq-shop-filter-field" for="sq-listing-condition"><span>الحالة</span>
        <select id="sq-listing-condition" name="sq_condition">
          <option value="">كل الحالات</option>
          <?php foreach ( $conditions as $condition ) : ?>
            <option value="<?php echo esc_attr( $condition->slug ); ?>" <?php selected( sanitize_title( $get_filter( 'sq_condition' ) ), $condition->slug ); ?>><?php echo esc_html( $condition->name ); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php endif; ?>
      <label class="sq-shop-filter-field" for="sq-listing-min-price"><span>السعر من (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</span>
        <input type="number" id="sq-listing-min-price" name="min_price" min="0" step="0.01" inputmode="decimal" placeholder="0" value="<?php echo esc_attr( $get_filter( 'min_price' ) ); ?>">
      </label>
      <label class="sq-shop-filter-field" for="sq-listing-max-price"><span>حتى (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</span>
        <input type="number" id="sq-listing-max-price" name="max_price" min="0" step="0.01" inputmode="decimal" placeholder="أي سعر" value="<?php echo esc_attr( $get_filter( 'max_price' ) ); ?>">
      </label>
      <button type="submit" class="sq-shop-apply-filters">تطبيق الفلاتر</button>
      <a class="sq-shop-reset-filters" href="<?php echo esc_url( $shop_url ); ?>">مسح الفلاتر</a>
    </div>
  </form>
<?php endif; ?>
<?php if ( function_exists( 'woocommerce_content' ) ) : ?>
  <?php woocommerce_content(); ?>
<?php endif; ?>
</main>
<?php get_footer(); ?>

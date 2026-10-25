<?php
/**
 * Accessible product-aware site search.
 */
$search_id = wp_unique_id( 'sq-search-' );
$is_woocommerce = class_exists( 'WooCommerce' );
?>
<form role="search" method="get" class="sq-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
  <label class="screen-reader-text" for="<?php echo esc_attr( $search_id ); ?>"><?php esc_html_e( 'Search the marketplace', 'souq-qareeb' ); ?></label>
  <input id="<?php echo esc_attr( $search_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="ابحث عن أغراض مستعملة..." required>
  <?php if ( $is_woocommerce ) : ?>
    <input type="hidden" name="post_type" value="product">
  <?php endif; ?>
  <button type="submit">بحث</button>
</form>

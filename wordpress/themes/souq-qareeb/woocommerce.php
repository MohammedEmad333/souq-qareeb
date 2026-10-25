<?php
/**
 * WooCommerce archive and single-product wrapper.
 * WooCommerce owns cart, checkout and account page content.
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="sq-container sq-main sq-shop">
  <?php if ( function_exists( 'woocommerce_content' ) ) : ?>
    <?php woocommerce_content(); ?>
  <?php endif; ?>
</main>
<?php get_footer(); ?>

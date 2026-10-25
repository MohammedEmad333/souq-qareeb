<?php
/**
 * Responsive marketplace footer. Use existing destinations only.
 */
$footer_shop_url = function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0
    ? get_permalink( wc_get_page_id( 'shop' ) )
    : home_url( '/' );
$footer_cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
$footer_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
$footer_logo_exists = file_exists( get_template_directory() . '/assets/images/logo-header.png' );
?>
<footer class="sq-footer" dir="rtl">
  <div class="sq-container sq-footer-main">
    <div class="sq-footer-about">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sq-footer-brand" aria-label="سوق قريب - الرئيسية">
        <?php if ( $footer_logo_exists ) : ?>
          <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo-header.png' ); ?>" alt="سوق قريب" width="160" height="83" loading="lazy">
        <?php else : ?>
          <strong><?php echo esc_html( get_bloginfo( 'name' ) ?: 'سوق قريب' ); ?></strong>
        <?php endif; ?>
      </a>
      <p>مساحة مجتمعية لبيع وتبادل والتبرّع بالأغراض المستعملة. كل غرض يستحق فرصة ثانية.</p>
    </div>
    <nav class="sq-footer-links" aria-label="روابط سريعة">
      <h2>روابط سريعة</h2>
      <ul>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">الرئيسية</a></li>
        <li><a href="<?php echo esc_url( $footer_shop_url ); ?>">المنتجات المستعملة</a></li>
        <li><a href="<?php echo esc_url( home_url( '/#how-it-works' ) ); ?>">كيف يعمل سوق قريب؟</a></li>
      </ul>
    </nav>
    <nav class="sq-footer-links" aria-label="روابط الحساب">
      <h2>تصفح وحسابك</h2>
      <ul>
        <?php if ( $footer_account_url ) : ?><li><a href="<?php echo esc_url( $footer_account_url ); ?>">حسابي</a></li><?php endif; ?>
        <?php if ( $footer_cart_url ) : ?><li><a href="<?php echo esc_url( $footer_cart_url ); ?>">سلة المشتريات</a></li><?php endif; ?>
        <li><a href="<?php echo esc_url( $footer_shop_url ); ?>">تصفح المنتجات</a></li>
      </ul>
    </nav>
    <div class="sq-footer-message">
      <h2>سوق قريب</h2>
      <p>كل اللي بدك إياه أقرب</p>
      <span>بيع · بدّل · تبرّع</span>
    </div>
  </div>
  <div class="sq-footer-bottom">
    <div class="sq-container">© <?php echo esc_html( gmdate( 'Y' ) ); ?> سوق قريب. جميع الحقوق محفوظة.</div>
  </div>
</footer>
<?php wp_footer(); ?>
</body></html>

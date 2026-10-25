<?php
/**
 * Marketplace home page. Uses real WooCommerce products when available.
 */
get_header();
$shop_url = ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 )
    ? get_permalink( wc_get_page_id( 'shop' ) )
    : home_url( '/' );
?>
<main id="main">
<section class="sq-hero"><div class="sq-container sq-hero-grid">
  <div>
    <span class="sq-eyebrow">سوق قريب — غزة</span>
    <h1>أغراض مفيدة، بأسعار أقرب إليك</h1>
    <p>بيع، بدّل، أو تبرّع بأغراضك المستعملة، واكتشف ما تحتاجه من مجتمعك.</p>
    <?php get_search_form(); ?>
    <div class="sq-actions">
      <a class="sq-button" href="<?php echo esc_url( $shop_url ); ?>">تصفّح المنتجات</a>
      <a class="sq-button sq-button--outline" href="#how-it-works">كيف يعمل؟</a>
    </div>
  </div>
  <div class="sq-panel">
    <?php if ( file_exists( get_template_directory() . "/assets/images/hero-mobile-image.webp" ) ) : ?>
      <img class="sq-hero-illustration" src="<?php echo esc_url( get_template_directory_uri() . "/assets/images/hero-mobile-image.webp" ); ?>" alt="" width="600" height="600" loading="eager">
    <?php endif; ?>
    <h2>كل غرض يستحق فرصة ثانية</h2>
    <p>ابدأ بالبحث عن شيء تحتاجه، أو اعرض شيئًا لم تعد تستخدمه.</p>
  </div>
</div></section>
<section id="how-it-works" class="sq-container sq-features" aria-label="طرق الاستفادة من السوق">
  <div class="sq-feature"><?php if ( file_exists( get_template_directory() . '/assets/images/sell.webp' ) ) : ?><img class="sq-feature-art" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/sell.webp' ); ?>" alt="" loading="lazy"><?php endif; ?><h3>بيع</h3><p>أوصل أغراضك لمن يحتاجها بسعر مناسب.</p></div>
  <div class="sq-feature"><?php if ( file_exists( get_template_directory() . '/assets/images/exchange.webp' ) ) : ?><img class="sq-feature-art" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/exchange.webp' ); ?>" alt="" loading="lazy"><?php endif; ?><h3>بدّل</h3><p>تبادل الأغراض واستفد مما يملكه الآخرون.</p></div>
  <div class="sq-feature"><?php if ( file_exists( get_template_directory() . '/assets/images/donate.webp' ) ) : ?><img class="sq-feature-art" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/donate.webp' ); ?>" alt="" loading="lazy"><?php endif; ?><h3>تبرّع</h3><p>امنح أغراضك فرصة جديدة مع شخص آخر.</p></div>
</section>
<?php if ( class_exists( 'WooCommerce' ) ) : ?>
<section class="sq-container sq-products">
  <h2>أحدث المنتجات</h2>
  <?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); ?>
</section>
<?php endif; ?>
</main>
<?php get_footer(); ?>

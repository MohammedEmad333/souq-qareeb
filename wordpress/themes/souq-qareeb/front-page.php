<?php
/** Marketplace homepage — phase one of the approved visual redesign. */
get_header();
$shop_url = function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0
    ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );
$asset = static function ( $filename ) {
    if ( ! file_exists( get_template_directory() . '/assets/images/' . $filename ) ) {
        return '';
    }
    return get_template_directory_uri() . '/assets/images/' . $filename;
};
$categories = array();
if ( taxonomy_exists( 'product_cat' ) ) {
    $categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 5, 'orderby' => 'count', 'order' => 'DESC', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
    if ( is_wp_error( $categories ) ) {
        $categories = array();
    }
}
?>
<main id="main" class="sq-home-v2" dir="rtl">
  <section class="sq-home-hero">
    <div class="sq-container sq-home-hero-layout">
      <div class="sq-home-hero-copy">
        <span class="sq-home-eyebrow">سوق قريب — أغراض مستعملة، فرص جديدة</span>
        <h1>أغراض مفيدة، <span>بأسعار أقرب إليك</span></h1>
        <p>اشترِ، بدّل، أو تبرّع بأغراضك المستعملة، وساهم في مجتمع أقرب وأكثر استدامة.</p>
        <form class="sq-home-search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
          <label class="screen-reader-text" for="sq-home-query">ابحث عن منتج</label>
          <input id="sq-home-query" type="search" name="s" placeholder="ابحث عن كرسي، كتاب، مكتب..." required>
          <input type="hidden" name="post_type" value="product">
          <button type="submit">بحث</button>
        </form>
        <div class="sq-home-cta">
          <a class="sq-button" href="<?php echo esc_url( $shop_url ); ?>">تصفح المنتجات</a>
          <a class="sq-button sq-button--outline" href="#how-it-works">كيف يعمل؟</a>
        </div>
      </div>
      <?php if ( $asset( 'hero-mobile-image.webp' ) ) : ?>
      <div class="sq-home-hero-visual">
        <img src="<?php echo esc_url( $asset( 'hero-mobile-image.webp' ) ); ?>" alt="أثاث وأغراض مستعملة يمكن الاستفادة منها مجددًا" width="750" height="460" fetchpriority="high">
      </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="sq-container sq-home-benefits" aria-label="مميزات سوق قريب">
    <p>مجتمع محلي أقوى</p><p>أسعار مناسبة للجميع</p><p>استخدام أكثر استدامة</p>
  </section>

  <section class="sq-container sq-home-section">
    <div class="sq-home-feature-grid">
      <?php foreach ( array(
        array( 'title' => 'بيع', 'body' => 'أوصل أغراضك لمن يحتاجها بسعر مناسب.', 'image' => 'sell.webp', 'class' => 'sq-home-feature--sell' ),
        array( 'title' => 'بدّل', 'body' => 'تبادل الأغراض واستفد مما يملكه الآخرون.', 'image' => 'exchange.webp', 'class' => 'sq-home-feature--exchange' ),
        array( 'title' => 'تبرّع', 'body' => 'امنح أغراضك فرصة ثانية مع شخص آخر.', 'image' => 'donate.webp', 'class' => 'sq-home-feature--donate' ),
      ) as $feature ) : ?>
        <article class="sq-home-feature <?php echo esc_attr( $feature['class'] ); ?>">
          <?php if ( $asset( $feature['image'] ) ) : ?>
          <img src="<?php echo esc_url( $asset( $feature['image'] ) ); ?>" alt="" loading="lazy" width="488" height="211">
          <?php endif; ?>
          <h2><?php echo esc_html( $feature['title'] ); ?></h2>
          <p><?php echo esc_html( $feature['body'] ); ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ( ! empty( $categories ) ) : ?>
  <section class="sq-container sq-home-section" aria-labelledby="sq-home-cats-heading">
    <div class="sq-home-section-heading"><h2 id="sq-home-cats-heading">الأقسام الشائعة</h2><a href="<?php echo esc_url( $shop_url ); ?>">عرض جميع المنتجات</a></div>
    <div class="sq-home-categories">
      <?php foreach ( $categories as $category ) : ?>
      <a class="sq-home-category" href="<?php echo esc_url( get_term_link( $category ) ); ?>">
        <strong><?php echo esc_html( $category->name ); ?></strong>
        <span><?php echo esc_html( number_format_i18n( $category->count ) ); ?> منتج</span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( class_exists( 'WooCommerce' ) ) : ?>
  <section class="sq-container sq-home-section sq-products" aria-labelledby="sq-home-products-heading">
    <div class="sq-home-section-heading"><h2 id="sq-home-products-heading">أحدث المنتجات</h2><a href="<?php echo esc_url( $shop_url ); ?>">عرض جميع المنتجات</a></div>
    <?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); ?>
  </section>
  <?php endif; ?>
  <section class="sq-container sq-home-section sq-home-steps" id="how-it-works">
    <h2>كيف يعمل سوق قريب؟</h2>
    <div class="sq-home-step-grid">
      <div><b>1</b><h3>ابحث وتصفح</h3><p>اكتشف الأغراض التي تحتاجها.</p></div>
      <div><b>2</b><h3>تواصل واتفق</h3><p>تواصل مع صاحب المنتج واتفق على التفاصيل.</p></div>
      <div><b>3</b><h3>استفد من المنتج</h3><p>أعطِ الأغراض فرصة ثانية.</p></div>
    </div>
  </section>
</main>
<?php get_footer(); ?>

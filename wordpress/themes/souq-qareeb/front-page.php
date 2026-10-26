<?php
/**
 * The approved mobile-first Souq Qareeb marketplace homepage.
 * Real products, categories and URLs come from WordPress and WooCommerce.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$shop_url = function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0
    ? get_permalink( wc_get_page_id( 'shop' ) )
    : home_url( '/' );
$add_listing_page = get_page_by_path( 'add-listing' );
$listing_url = $add_listing_page && 'publish' === $add_listing_page->post_status ? get_permalink( $add_listing_page ) : '';
$asset = static function ( $filename ) {
    return file_exists( get_template_directory() . '/assets/images/' . $filename )
        ? get_template_directory_uri() . '/assets/images/' . $filename
        : '';
};
$categories = array();
if ( taxonomy_exists( 'product_cat' ) ) {
    $categories = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'number'     => 6,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
    ) );
    if ( is_wp_error( $categories ) ) {
        $categories = array();
    }
}
?>
<main id="main" class="sq-home-v2 sq-home-v3" dir="rtl">
  <section class="sq-home-hero" aria-labelledby="sq-home-title">
    <div class="sq-container sq-home-hero-layout">
      <div class="sq-home-hero-copy">
        <span class="sq-home-eyebrow">سوق قريب — أغراض مستعملة، فرص جديدة</span>
        <h1 id="sq-home-title">أغراض مفيدة، <span>بأسعار أقرب إليك</span></h1>
        <p>اشترِ، بدّل، وتبرّع بالمنتجات المستعملة في غزة. كن جزءًا من مجتمع محلي أكثر استدامة وتكافلًا.</p>
        <form class="sq-home-search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
          <label class="screen-reader-text" for="sq-home-query">ابحث عن منتج مستعمل</label>
          <input id="sq-home-query" type="search" name="s" placeholder="عن ماذا تبحث؟ كرسي، هاتف، كتاب..." required>
          <input type="hidden" name="post_type" value="product">
          <button type="submit" aria-label="ابحث عن المنتجات"><span aria-hidden="true">⌕</span> بحث</button>
        </form>
        <div class="sq-home-cta">
          <a class="sq-button" href="<?php echo esc_url( $shop_url ); ?>">تصفح المنتجات <span aria-hidden="true">←</span></a>
          <a class="sq-button sq-button--outline" href="#how-it-works">كيف يعمل؟</a>
        </div>
      </div>
      <?php if ( $asset( 'hero-mobile-image.webp' ) ) : ?>
      <div class="sq-home-hero-visual">
        <img src="<?php echo esc_url( $asset( 'hero-mobile-image.webp' ) ); ?>" alt="أغراض منزلية وأجهزة وأثاث يمكن إعادة استخدامها" width="750" height="460" fetchpriority="high">
      </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="sq-container sq-home-benefits" aria-label="مميزات سوق قريب">
    <p><span class="sq-benefit-symbol" aria-hidden="true">♻</span><span>من أجل بيئة أفضل</span></p>
    <p><span class="sq-benefit-symbol" aria-hidden="true">₪</span><span>أسعار مناسبة للجميع</span></p>
    <p><span class="sq-benefit-symbol" aria-hidden="true">♥</span><span>مجتمع محلي أقوى</span></p>
  </section>

  <section class="sq-container sq-home-section sq-home-services" aria-label="بيع أو بدّل أو تبرع">
    <div class="sq-home-feature-grid">
      <?php foreach ( array(
        array( 'title' => 'بيع', 'body' => 'أعلن عن أغراضك المستعملة بسعر مناسب.', 'image' => 'sell.webp', 'class' => 'sq-home-feature--sell', 'symbol' => '▣' ),
        array( 'title' => 'بدّل', 'body' => 'بدّل ما لا تحتاجه مع أشخاص قريبين منك.', 'image' => 'exchange.webp', 'class' => 'sq-home-feature--exchange', 'symbol' => '⇄' ),
        array( 'title' => 'تبرّع', 'body' => 'امنح أغراضك فرصة جديدة وساعد غيرك.', 'image' => 'donate.webp', 'class' => 'sq-home-feature--donate', 'symbol' => '♥' ),
      ) as $feature ) : ?>
      <article class="sq-home-feature <?php echo esc_attr( $feature['class'] ); ?>">
        <?php if ( $asset( $feature['image'] ) ) : ?>
          <img src="<?php echo esc_url( $asset( $feature['image'] ) ); ?>" alt="" width="488" height="211" loading="lazy">
        <?php endif; ?>
        <span class="sq-home-feature-icon" aria-hidden="true"><?php echo esc_html( $feature['symbol'] ); ?></span>
        <h2><?php echo esc_html( $feature['title'] ); ?></h2>
        <p><?php echo esc_html( $feature['body'] ); ?></p>
        <?php if ( $listing_url ) : ?>
          <a class="sq-home-feature-link" href="<?php echo esc_url( $listing_url ); ?>" aria-label="<?php echo esc_attr( 'أضف إعلانًا من نوع ' . $feature['title'] ); ?>">أضف إعلانك <span aria-hidden="true">←</span></a>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ( $categories ) : ?>
  <section class="sq-container sq-home-section sq-home-category-section" aria-labelledby="sq-home-cats-heading">
    <div class="sq-home-section-heading">
      <h2 id="sq-home-cats-heading">الأقسام الشائعة</h2>
      <a href="<?php echo esc_url( $shop_url ); ?>">عرض جميع الأقسام <span aria-hidden="true">←</span></a>
    </div>
    <div class="sq-home-categories" role="list">
      <?php foreach ( $categories as $category ) :
        $thumb_id = (int) get_term_meta( $category->term_id, 'thumbnail_id', true );
        if ( ! $thumb_id && function_exists( 'wc_get_products' ) ) {
            // Use a genuine product photo as a fallback, never a fabricated category count.
            $sample = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'category' => array( $category->slug ), 'return' => 'objects' ) );
            if ( $sample ) {
                $thumb_id = (int) $sample[0]->get_image_id();
            }
        }
        $term_url = get_term_link( $category );
        if ( is_wp_error( $term_url ) ) {
            continue;
        }
      ?>
      <a class="sq-home-category" role="listitem" href="<?php echo esc_url( $term_url ); ?>">
        <?php if ( $thumb_id ) : ?>
          <?php echo wp_get_attachment_image( $thumb_id, 'thumbnail', false, array( 'class' => 'sq-home-category-thumb', 'alt' => '', 'loading' => 'lazy' ) ); ?>
        <?php else : ?>
          <span class="sq-home-category-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="27" height="27" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
          </span>
        <?php endif; ?>
        <strong><?php echo esc_html( $category->name ); ?></strong>
        <span><?php echo esc_html( number_format_i18n( $category->count ) ); ?> منتج</span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( class_exists( 'WooCommerce' ) ) : ?>
  <section class="sq-container sq-home-section sq-products sq-home-products" aria-labelledby="sq-home-products-heading">
    <div class="sq-home-section-heading"><h2 id="sq-home-products-heading">أحدث المنتجات</h2><a href="<?php echo esc_url( $shop_url ); ?>">عرض جميع المنتجات <span aria-hidden="true">←</span></a></div>
    <?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); ?>
  </section>
  <?php endif; ?>

  <section class="sq-container sq-home-section sq-home-steps" id="how-it-works" aria-labelledby="sq-home-how-heading">
    <h2 id="sq-home-how-heading">كيف يعمل سوق قريب؟</h2>
    <div class="sq-home-step-grid">
      <div><b>1</b><h3>ابحث وتصفح</h3><p>اكتشف الأغراض التي تحتاجها.</p></div>
      <div><b>2</b><h3>تواصل واتفق</h3><p>اتفق مع صاحب المنتج على التفاصيل.</p></div>
      <div><b>3</b><h3>استفد من المنتج</h3><p>امنح أغراضك فرصة ثانية.</p></div>
    </div>
  </section>
</main>
<?php get_footer(); ?>
